<?php

declare(strict_types=1);

/*
 * Crea la base de datos y carga los datos iniciales.
 *
 *   php bin/migrate.php                 crea tablas, roles y usuario administrador
 *   php bin/migrate.php --demo          además carga datos de ejemplo
 *   php bin/migrate.php --fresh --demo  borra todo y vuelve a empezar (¡destructivo!)
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Auth;
use App\Core\Database;
use App\Services\Permissions;

$args = array_slice($argv, 1);
$fresh = in_array('--fresh', $args, true);
$demo = in_array('--demo', $args, true);
$driver = Database::driver();

if ($fresh) {
    if ($driver === 'sqlite') {
        $path = (string) env('DB_DATABASE', 'storage/database.sqlite');
        $path = str_starts_with($path, '/') ? $path : BASE_PATH . '/' . $path;
        foreach ([$path, "{$path}-wal", "{$path}-shm"] as $f) {
            is_file($f) && unlink($f);
        }
    } else {
        $pdo = Database::pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $pdo->exec("DROP TABLE `{$table}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    echo "Base de datos vaciada.\n";
}

$pdo = Database::pdo();
$tableExists = static fn (string $table): bool => $driver === 'sqlite'
    ? (bool) Database::value("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?", [$table])
    : (bool) Database::value('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$table]);

$sql = file_get_contents(BASE_PATH . '/database/schema.sql');
$sql = strtr($sql, $driver === 'mysql'
    ? ['{{PK}}' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', '{{ENGINE}}' => ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci']
    : ['{{PK}}' => 'INTEGER PRIMARY KEY AUTOINCREMENT', '{{ENGINE}}' => '']);
if ($driver === 'mysql') {
    // En MySQL las FK deben tener el mismo tipo que la PK (INT UNSIGNED).
    $sql = preg_replace('/^(\s+\w+_id|\s+(?:role|authorized|cx_confirmed|delivered|created|uploaded)_by|\s+attachment_id) INTEGER/m', '$1 INT UNSIGNED', $sql);
    $sql = preg_replace('/^(\s+(?:current_surgery_id|user_id|entity_id)) INTEGER/m', '$1 INT UNSIGNED', $sql);
}
$sql = preg_replace('/^--.*$/m', '', $sql);

// Crea solo las tablas que faltan: sirve para instalar desde cero y para sumar tablas nuevas sin perder datos.
$created = [];
foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    if (preg_match('/^CREATE TABLE (\w+)/i', $statement, $m)) {
        if ($tableExists($m[1])) {
            continue;
        }
        $created[$m[1]] = true;
    } elseif (preg_match('/^CREATE INDEX \w+ ON (\w+)/i', $statement, $m) && !isset($created[$m[1]])) {
        continue;
    }
    $pdo->exec($statement);
}
echo $created ? 'Tablas creadas (' . $driver . '): ' . implode(', ', array_keys($created)) . ".\n" : "La base ya estaba al día.\n";

// ---------------- Roles y permisos
$roleIds = [];
foreach (Permissions::defaultRoles() as $slug => $role) {
    $id = Database::value('SELECT id FROM roles WHERE slug = ?', [$slug]);
    if (!$id) {
        $id = Database::insert('roles', ['slug' => $slug, 'name' => $role['name'], 'description' => $role['description'], 'created_at' => now()]);
        foreach ($role['permissions'] as $permission) {
            Database::insert('role_permissions', ['role_id' => $id, 'permission' => $permission]);
        }
    }
    $roleIds[$slug] = (int) $id;
}
// El rol Gerentes siempre recibe los permisos nuevos que se agreguen al sistema.
foreach (array_keys(Permissions::all()) as $permission) {
    if (!Database::value('SELECT 1 FROM role_permissions WHERE role_id = ? AND permission = ?', [$roleIds['gerente'], $permission])) {
        Database::insert('role_permissions', ['role_id' => $roleIds['gerente'], 'permission' => $permission]);
    }
}
echo "Roles y permisos listos.\n";

// ---------------- Administrador
$adminEmail = mb_strtolower((string) env('ADMIN_EMAIL', 'admin@metallive.com.ar'));
if (!Database::value('SELECT 1 FROM users WHERE email = ?', [$adminEmail])) {
    $password = (string) env('ADMIN_PASSWORD', '') ?: rtrim(strtr(base64_encode(random_bytes(12)), '+/', 'Kx'), '=') . '7';
    Database::insert('users', [
        'role_id' => $roleIds['gerente'], 'name' => 'Administrador', 'email' => $adminEmail,
        'password_hash' => Auth::hash($password), 'created_at' => now(), 'updated_at' => now(),
    ]);
    echo "\nUsuario administrador creado:\n  correo:     {$adminEmail}\n  contraseña: {$password}\n  (cambiala desde Mi perfil)\n\n";
}

if (!$demo) {
    echo "Listo.\n";
    exit(0);
}

// ---------------- Datos de ejemplo
if (Database::value('SELECT COUNT(*) FROM materials')) {
    echo "Ya hay datos cargados; se omiten los datos de ejemplo.\n";
    exit(0);
}

$demoPassword = 'Demo12345!';
$users = [
    ['cotizacion', 'Lucía Cotizaciones', 'cotizacion@demo.local', 0],
    ['cx', 'Equipo Cx', 'cx@demo.local', 0],
    ['facturacion', 'Martín Facturación', 'facturacion@demo.local', 0],
    ['cobranzas', 'Paula Cobranzas', 'cobranzas@demo.local', 0],
    ['vendedor', 'Diego Vendedor', 'vendedor@demo.local', 5],
    ['vendedor', 'Sofía Vendedora', 'vendedora@demo.local', 4.5],
    ['chofer', 'Ramón Chofer', 'chofer@demo.local', 0],
];
$uid = [];
foreach ($users as [$role, $name, $email, $commission]) {
    $uid[$email] = Database::insert('users', [
        'role_id' => $roleIds[$role], 'name' => $name, 'email' => $email, 'phone' => '+54 9 11 5555-' . random_int(1000, 9999),
        'password_hash' => Auth::hash($demoPassword), 'commission_pct' => $commission, 'created_at' => now(), 'updated_at' => now(),
    ]);
}

$ins = fn (string $table, array $rows) => array_map(fn ($r) => Database::insert($table, [...$r, 'created_at' => now(), 'updated_at' => now()]), $rows);

$clients = $ins('clients', [
    ['name' => 'OSDE', 'type' => 'prepaga', 'cuit' => '30-54674271-6', 'payment_terms' => '60 días', 'contact' => 'Auditoría médica'],
    ['name' => 'PAMI', 'type' => 'obra_social', 'payment_terms' => '90 días'],
    ['name' => 'Swiss Medical', 'type' => 'prepaga', 'payment_terms' => '45 días'],
    ['name' => 'Galeno ART', 'type' => 'art', 'payment_terms' => '30 días'],
    ['name' => 'Particular', 'type' => 'particular', 'payment_terms' => 'Contado'],
]);
$institutions = $ins('institutions', [
    ['name' => 'Sanatorio Güemes', 'address' => 'Francisco Acuña de Figueroa 1240', 'city' => 'CABA', 'phone' => '011 4959-8200'],
    ['name' => 'Hospital Alemán', 'address' => 'Av. Pueyrredón 1640', 'city' => 'CABA', 'phone' => '011 4827-7000'],
    ['name' => 'Clínica San Camilo', 'address' => 'Av. Ángel Gallardo 899', 'city' => 'CABA'],
]);
$doctors = $ins('doctors', [
    ['last_name' => 'Fernández', 'first_name' => 'Julián', 'license' => 'MN 112233', 'specialty' => 'Traumatología'],
    ['last_name' => 'Gómez', 'first_name' => 'Carolina', 'license' => 'MN 445566', 'specialty' => 'Cirugía de columna'],
    ['last_name' => 'Ruiz', 'first_name' => 'Esteban', 'license' => 'MP 7788', 'specialty' => 'Artroscopía'],
]);
$patients = $ins('patients', [
    ['last_name' => 'Pérez', 'first_name' => 'María', 'document' => '28111222', 'client_id' => $clients[0], 'member_number' => '61 123456 7 01'],
    ['last_name' => 'López', 'first_name' => 'Héctor', 'document' => '12345678', 'client_id' => $clients[1]],
    ['last_name' => 'Sosa', 'first_name' => 'Valentina', 'document' => '40999888', 'client_id' => $clients[2]],
    ['last_name' => 'Acosta', 'first_name' => 'Ricardo', 'document' => '30555444', 'client_id' => $clients[3]],
]);
$ins('instrumentists', [
    ['last_name' => 'Benítez', 'first_name' => 'Laura', 'phone' => '11 4444-1111', 'active' => 1],
    ['last_name' => 'Medina', 'first_name' => 'Julieta', 'phone' => '11 4444-2222', 'active' => 1],
]);
$ins('suppliers', [
    ['name' => 'OrthoImport S.A.', 'cuit' => '30-71234567-8'],
    ['name' => 'Implantes del Sur S.R.L.', 'cuit' => '30-70987654-3'],
]);

$materials = [
    ['TOR-CORT-35', 'Tornillo cortical 3.5 mm', 'Tornillos', 18500, 180, 50],
    ['TOR-ESP-65', 'Tornillo esponjoso 6.5 mm', 'Tornillos', 24300, 90, 30],
    ['TOR-BLOQ-50', 'Tornillo bloqueado 5.0 mm', 'Tornillos', 31200, 120, 40],
    ['PLA-LCP-45', 'Placa LCP 4.5 mm (8 orificios)', 'Placas', 285000, 12, 4],
    ['PLA-TER-35', 'Placa tercio de caña 3.5 mm', 'Placas', 142000, 15, 5],
    ['CLA-END-11', 'Clavo endomedular de fémur 11 mm', 'Clavos', 690000, 6, 2],
    ['CLA-TIB-10', 'Clavo endomedular de tibia 10 mm', 'Clavos', 640000, 3, 3],
    ['PRO-CAD-CEM', 'Prótesis total de cadera cementada', 'Prótesis', 2450000, 4, 2],
    ['PRO-ROD-PS', 'Prótesis total de rodilla PS', 'Prótesis', 2980000, 3, 2],
    ['ANC-SUT-50', 'Anclaje con sutura 5.0 mm', 'Artroscopía', 210000, 25, 8],
    ['CEM-OSE-40', 'Cemento óseo 40 g', 'Consumibles', 98000, 30, 10],
    ['TOR-PED-55', 'Tornillo pedicular 5.5 mm', 'Columna', 165000, 40, 16],
    ['BAR-TIT-55', 'Barra de titanio 5.5 mm', 'Columna', 120000, 10, 4],
];
$mid = [];
foreach ($materials as [$code, $name, $cat, $price, $stock, $min]) {
    $mid[$code] = Database::insert('materials', [
        'code' => $code, 'name' => $name, 'category' => $cat, 'price' => $price, 'stock_qty' => $stock,
        'min_stock' => $min, 'unit' => 'u', 'created_at' => now(), 'updated_at' => now(),
    ]);
}

$boxes = [
    ['CJ-OST-001', 'Caja osteosíntesis pequeños fragmentos', ['TOR-CORT-35' => 30, 'PLA-TER-35' => 4]],
    ['CJ-OST-002', 'Caja osteosíntesis grandes fragmentos', ['TOR-ESP-65' => 20, 'TOR-BLOQ-50' => 24, 'PLA-LCP-45' => 3]],
    ['CJ-CLA-001', 'Caja clavos endomedulares', ['CLA-END-11' => 2, 'CLA-TIB-10' => 2, 'TOR-BLOQ-50' => 8]],
    ['CJ-COL-001', 'Caja instrumental de columna', ['TOR-PED-55' => 12, 'BAR-TIT-55' => 4]],
    ['CJ-ART-001', 'Caja artroscopía hombro', ['ANC-SUT-50' => 6]],
];
foreach ($boxes as [$code, $name, $content]) {
    $bid = Database::insert('boxes', ['code' => $code, 'name' => $name, 'status' => 'disponible', 'created_at' => now(), 'updated_at' => now()]);
    Database::insert('box_events', ['box_id' => $bid, 'to_status' => 'disponible', 'note' => 'Alta de caja', 'created_at' => now()]);
    foreach ($content as $m => $qty) {
        Database::insert('box_items', ['box_id' => $bid, 'material_id' => $mid[$m], 'expected_qty' => $qty, 'current_qty' => $qty]);
    }
}

Database::insert('custom_fields', ['entity' => 'surgery_quote', 'field_key' => 'lateralidad', 'label' => 'Lateralidad', 'type' => 'select', 'options' => "Derecha\nIzquierda\nBilateral\nNo aplica", 'required' => 0, 'sort_order' => 1, 'active' => 1, 'created_at' => now()]);
Database::insert('custom_fields', ['entity' => 'surgery_quote', 'field_key' => 'urgencia', 'label' => 'Cirugía de urgencia', 'type' => 'checkbox', 'required' => 0, 'sort_order' => 2, 'active' => 1, 'created_at' => now()]);

// Cirugías de ejemplo en distintos estadios.
$surgeries = [
    ['cotizacion', 0, 0, 0, 4, 'Osteosíntesis de radio distal', [['PLA-TER-35', 1], ['TOR-CORT-35', 6]], '+6 days'],
    ['autorizacion', 1, 1, 1, 4, 'Reemplazo total de cadera', [['PRO-CAD-CEM', 1], ['CEM-OSE-40', 2]], '+4 days'],
    ['asignacion', 2, 2, 2, 5, 'Artrodesis lumbar L4-L5', [['TOR-PED-55', 4], ['BAR-TIT-55', 2]], '+2 days'],
    ['facturacion', 3, 0, 0, 5, 'Enclavado endomedular de fémur', [['CLA-END-11', 1], ['TOR-BLOQ-50', 4]], '-3 days'],
];
foreach ($surgeries as $i => [$stage, $p, $inst, $doc, $sellerIdx, $proc, $items, $when]) {
    $seller = $uid[$sellerIdx === 4 ? 'vendedor@demo.local' : 'vendedora@demo.local'];
    $sid = Database::insert('surgeries', [
        'stage' => $stage, 'status' => 'activa', 'patient_id' => $patients[$p], 'institution_id' => $institutions[$inst],
        'doctor_id' => $doctors[$doc], 'client_id' => Database::value('SELECT client_id FROM patients WHERE id = ?', [$patients[$p]]),
        'seller_id' => $seller, 'procedure_name' => $proc, 'commission_pct' => 5, 'fee_amount' => 450000 + $i * 50000,
        'custom_data' => json_encode(['lateralidad' => 'Derecha', 'urgencia' => 0]),
        'scheduled_at' => date('Y-m-d 08:30:00', strtotime($when)),
        'auth_type' => $stage === 'cotizacion' || $stage === 'autorizacion' ? null : 'oc',
        'oc_number' => $stage === 'cotizacion' || $stage === 'autorizacion' ? null : 'OC-' . random_int(10000, 99999),
        'authorized_at' => $stage === 'cotizacion' || $stage === 'autorizacion' ? null : now(),
        'created_by' => $uid['cotizacion@demo.local'], 'created_at' => now(), 'updated_at' => now(),
    ]);
    foreach ($items as $k => [$code, $qty]) {
        Database::insert('quote_items', [
            'surgery_id' => $sid, 'material_id' => $mid[$code], 'quantity' => $qty, 'sort_order' => $k,
            'description' => Database::value('SELECT name FROM materials WHERE id = ?', [$mid[$code]]),
            'unit_price' => Database::value('SELECT price FROM materials WHERE id = ?', [$mid[$code]]),
        ]);
    }
    Database::insert('surgery_events', ['surgery_id' => $sid, 'event' => 'Cotización creada (datos de ejemplo)', 'created_at' => now()]);
}

// Material en consignación en un cliente, con un consumo informado pendiente de facturar.
App\Services\ConsignmentService::register($clients[1], 'envio', [
    ['material_id' => $mid['TOR-CORT-35'], 'quantity' => 20],
    ['material_id' => $mid['PLA-TER-35'], 'quantity' => 3],
    ['material_id' => $mid['ANC-SUT-50'], 'quantity' => 4],
], 'Remito R 0001-00000452');
App\Services\ConsignmentService::register($clients[1], 'consumo', [
    ['material_id' => $mid['TOR-CORT-35'], 'quantity' => 6],
    ['material_id' => $mid['PLA-TER-35'], 'quantity' => 1],
], 'Paciente Gutiérrez, A. · parte quirúrgico 8841');

echo "\nDatos de ejemplo cargados. Usuarios demo (contraseña: {$demoPassword}):\n";
foreach ($users as [$role, $name, $email]) {
    echo "  {$email}  ({$role})\n";
}
echo "Listo.\n";
