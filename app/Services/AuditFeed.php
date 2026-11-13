<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Registro de actividad legible: combina audit_log con el historial de cada cirugía
 * (surgery_events, que ya tiene textos claros) y traduce cada fila a una frase.
 */
final class AuditFeed
{
    /** Categorías del filtro "Tipo". */
    public const TYPES = [
        'cirugias' => 'Cirugías',
        'stock' => 'Stock y consignaciones',
        'maestros' => 'Maestros',
        'accesos' => 'Usuarios y accesos',
        'ingresos' => 'Ingresos al sistema',
    ];

    /** tabla => [artículo + nombre, columnas para el nombre, ruta (con {id}), icono, categoría] */
    private const ENTITIES = [
        'surgeries' => ['la cirugía', ['procedure_name'], '/admin/cirugias/{id}', 'activity', 'cirugias'],
        'materials' => ['el material', ['code', 'name'], '/admin/materiales/{id}', 'layers', 'stock'],
        'boxes' => ['la caja', ['code', 'name'], '/admin/cajas/{id}', 'box', 'stock'],
        'material_categories' => ['la categoría de materiales', ['name'], '/admin/maestros/categorias-materiales/{id}/editar', 'list', 'stock'],
        'patients' => ['el paciente', ['last_name', 'first_name'], '/admin/maestros/pacientes/{id}/editar', 'user', 'maestros'],
        'doctors' => ['el médico', ['last_name', 'first_name'], '/admin/maestros/medicos/{id}/editar', 'stethoscope', 'maestros'],
        'institutions' => ['la institución', ['name'], '/admin/maestros/instituciones/{id}/editar', 'building', 'maestros'],
        'clients' => ['el cliente', ['name'], '/admin/maestros/clientes/{id}/editar', 'briefcase', 'maestros'],
        'health_insurances' => ['la obra social', ['name'], '/admin/maestros/obras-sociales/{id}/editar', 'shield', 'maestros'],
        'instrumentists' => ['la instrumentadora', ['last_name', 'first_name'], '/admin/maestros/instrumentadoras/{id}/editar', 'scissors', 'maestros'],
        'suppliers' => ['el proveedor', ['name'], '/admin/maestros/proveedores/{id}/editar', 'truck', 'maestros'],
        'users' => ['el usuario', ['name'], '/admin/usuarios/{id}/editar', 'users', 'accesos'],
        'roles' => ['el rol', ['name'], '/admin/roles/{id}/editar', 'shield', 'accesos'],
        'custom_fields' => ['el campo de cotización', ['label'], '/admin/campos/{id}/editar', 'sliders', 'accesos'],
    ];

    private const VERBS = ['create' => 'Creó', 'update' => 'Modificó', 'delete' => 'Eliminó', 'disable' => 'Desactivó'];

    /** @return array{0: list<array>, 1: int} filas de la página y total */
    public static function fetch(array $filters, int $page, int $perPage): array
    {
        // Las altas y cambios de estadio de cirugías ya están en surgery_events con mejor texto.
        $union = "SELECT 'audit' AS src, a.id, a.created_at, a.user_id, a.action, a.entity, a.entity_id, a.detail, a.ip
                    FROM audit_log a
                   WHERE NOT (a.entity = 'surgeries' AND (a.action = 'create' OR a.action LIKE 'stage:%'))
                  UNION ALL
                  SELECT 'event' AS src, e.id, e.created_at, e.user_id, e.event AS action, 'surgeries' AS entity, e.surgery_id AS entity_id, e.detail, NULL AS ip
                    FROM surgery_events e";

        $conds = [];
        $params = [];
        if ($filters['user'] !== '') {
            $conds[] = 'f.user_id = ?';
            $params[] = (int) $filters['user'];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from'])) {
            $conds[] = 'f.created_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to'])) {
            $conds[] = 'f.created_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }
        if (isset(self::TYPES[$filters['type']])) {
            $type = $filters['type'];
            if ($type === 'ingresos') {
                $conds[] = "f.action = 'login'";
            } else {
                $tables = array_keys(array_filter(self::ENTITIES, fn ($d) => $d[4] === $type));
                $in = "f.entity IN ('" . implode("','", $tables) . "')";
                $conds[] = match ($type) {
                    'stock' => "($in OR f.action LIKE 'consignment.%')",
                    'maestros' => "($in AND f.action NOT LIKE 'consignment.%')",
                    'accesos' => "($in AND f.action <> 'login')",
                    default => $in,
                };
            }
        }
        $where = $conds ? ' WHERE ' . implode(' AND ', $conds) : '';

        $total = (int) Database::value("SELECT COUNT(*) FROM ($union) f" . $where, $params);
        $rows = Database::fetchAll(
            "SELECT f.*, u.name AS user_name FROM ($union) f LEFT JOIN users u ON u.id = f.user_id" . $where
            . ' ORDER BY f.created_at DESC, f.id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $params
        );
        return [$rows, $total];
    }

    /**
     * Convierte las filas en elementos listos para mostrar:
     * [icon, tone, title, target (label, url|null), details (list), time, user, ip]
     */
    public static function describe(array $rows): array
    {
        $names = self::lookupNames($rows);
        $out = [];
        foreach ($rows as $r) {
            $isLogin = $r['src'] === 'audit' && $r['action'] === 'login';
            $last = array_key_last($out);
            // Ingresos seguidos del mismo usuario en el mismo día: una sola línea con la cantidad.
            if ($isLogin && $last !== null && !empty($out[$last]['login'])
                && $out[$last]['user_id'] === $r['user_id']
                && substr((string) $out[$last]['created_at'], 0, 10) === substr((string) $r['created_at'], 0, 10)) {
                $out[$last]['count']++;
                $out[$last]['first_at'] = $r['created_at'];
                continue;
            }
            $item = $r['src'] === 'event' ? self::describeEvent($r, $names) : self::describeAudit($r, $names);
            $out[] = $item + [
                'created_at' => $r['created_at'], 'first_at' => $r['created_at'], 'count' => 1, 'login' => $isLogin,
                'user_id' => $r['user_id'], 'user' => $r['user_name'] ?? 'Sistema', 'ip' => $r['ip'],
            ];
        }
        foreach ($out as &$item) {
            if ($item['login'] && $item['count'] > 1) {
                [$from, $to] = [substr((string) $item['first_at'], 11, 5), substr((string) $item['created_at'], 11, 5)];
                $item['details'][] = $from === $to
                    ? sprintf('%d veces a las %s.', $item['count'], $to)
                    : sprintf('%d veces entre las %s y las %s.', $item['count'], $from, $to);
            }
        }
        unset($item);
        return $out;
    }

    // ------------------------------------------------------------------ Descripciones

    private static function describeEvent(array $r, array $names): array
    {
        return [
            'icon' => 'activity',
            'tone' => 'cirugias',
            'title' => $r['action'],
            'target' => self::target('surgeries', (int) $r['entity_id'], $names),
            'details' => $r['detail'] ? [$r['detail']] : [],
        ];
    }

    private static function describeAudit(array $r, array $names): array
    {
        $action = (string) $r['action'];
        $entity = (string) $r['entity'];
        $id = (int) $r['entity_id'];
        $detail = (string) ($r['detail'] ?? '');
        $def = self::ENTITIES[$entity] ?? null;
        $item = [
            'icon' => $def[3] ?? 'list',
            'tone' => $def[4] ?? 'otros',
            'title' => '',
            'target' => null,
            'details' => [],
        ];

        if ($action === 'login') {
            return ['icon' => 'logout', 'tone' => 'ingresos', 'title' => 'Ingresó al sistema'] + $item;
        }
        if ($action === 'password_change') {
            $self = (int) $r['user_id'] === $id;
            return ['icon' => 'shield', 'tone' => 'accesos', 'title' => $self ? 'Cambió su contraseña' : 'Cambió la contraseña de',
                'target' => $self ? null : self::target('users', $id, $names)] + $item;
        }
        if (str_starts_with($action, 'consignment.')) {
            $type = substr($action, strlen('consignment.'));
            $titles = [
                'envio' => 'Registró un envío en consignación a',
                'devolucion' => 'Registró una devolución de consignación de',
                'consumo' => 'Registró un consumo en consignación de',
                'ajuste' => 'Registró un ajuste por recuento en consignación de',
                'billed' => 'Facturó consumos en consignación de',
            ];
            return ['icon' => 'briefcase', 'tone' => 'stock', 'title' => $titles[$type] ?? 'Movimiento de consignación de',
                'target' => self::target('clients', $id, $names, '/admin/consignaciones/{id}'),
                'details' => $detail !== '' ? [$detail] : []] + $item;
        }
        if ($action === 'box_item') {
            $d = json_decode($detail, true) ?: [];
            $material = $names['materials'][(int) ($d['material_id'] ?? 0)] ?? 'Material #' . (int) ($d['material_id'] ?? 0);
            return ['title' => 'Actualizó el contenido de la caja', 'target' => self::target('boxes', $id, $names),
                'details' => [sprintf('%s: esperado %d, actual %d', $material, (int) ($d['expected_qty'] ?? 0), (int) ($d['current_qty'] ?? 0))]] + $item;
        }
        if ($entity === 'surgeries') {
            $title = match ($action) {
                'update' => 'Editó la cotización de',
                'reprogram' => 'Reprogramó',
                default => 'Actualizó',
            };
            return ['title' => $title, 'target' => self::target('surgeries', $id, $names)] + $item;
        }
        if ($entity === 'roles' && $action === 'update' && str_starts_with($detail, '{')) {
            return ['title' => 'Modificó los accesos del rol', 'target' => self::target('roles', $id, $names),
                'details' => self::roleChanges($r)] + $item;
        }

        // Altas, cambios, bajas y desactivaciones genéricas.
        $verb = self::VERBS[$action] ?? ucfirst($action);
        $noun = $def[0] ?? ($entity !== '' ? $entity : 'un registro');
        $target = self::target($entity, $id, $names);
        if ($action === 'delete') {
            // El registro ya no existe: se usa el nombre guardado en el detalle, sin link.
            $target = ['label' => $detail !== '' ? $detail : '#' . $id, 'url' => null];
            $detail = '';
        }
        $details = [];
        if ($entity === 'users' && $detail === 'con cambio de contraseña') {
            $details[] = 'Incluye cambio de contraseña.';
        } elseif ($detail !== '' && $action !== 'create') {
            $details[] = $detail;
        }
        return ['title' => $verb . ' ' . $noun, 'target' => $target, 'details' => $details] + $item;
    }

    /** Cambios de permisos de un rol respecto del registro anterior del mismo rol. */
    private static function roleChanges(array $r): array
    {
        $now = json_decode((string) $r['detail'], true)['permissions'] ?? [];
        $prevJson = Database::value(
            "SELECT detail FROM audit_log WHERE action = 'update' AND entity = 'roles' AND entity_id = ? AND id < ? ORDER BY id DESC LIMIT 1",
            [(int) $r['entity_id'], (int) $r['id']]
        );
        $labels = Permissions::all();
        $name = fn (string $k): string => $labels[$k] ?? $k;
        if (!$prevJson) {
            return ['Quedó con ' . count($now) . ' permisos.'];
        }
        $prev = json_decode((string) $prevJson, true)['permissions'] ?? [];
        $added = array_values(array_diff($now, $prev));
        $removed = array_values(array_diff($prev, $now));
        $out = [];
        if ($added) {
            $out[] = 'Agregó: ' . implode(' · ', array_map($name, $added));
        }
        if ($removed) {
            $out[] = 'Quitó: ' . implode(' · ', array_map($name, $removed));
        }
        return $out ?: ['Guardó sin cambios en los permisos.'];
    }

    // ------------------------------------------------------------------ Nombres y links

    private static function target(string $entity, int $id, array $names, ?string $route = null): ?array
    {
        if (!$id) {
            return null;
        }
        $def = self::ENTITIES[$entity] ?? null;
        $label = $names[$entity][$id] ?? null;
        if ($entity === 'surgeries') {
            $label = surgery_code($id) . ($label ? ' · ' . $label : '');
        }
        if ($label === null) {
            return ['label' => '#' . $id . ' (ya no existe)', 'url' => null];
        }
        $route ??= $def[2] ?? null;
        return ['label' => $label, 'url' => $route ? str_replace('{id}', (string) $id, $route) : null];
    }

    /** Busca de una vez los nombres de todos los registros mencionados en la página. */
    private static function lookupNames(array $rows): array
    {
        $ids = [];
        foreach ($rows as $r) {
            $entity = (string) $r['entity'];
            if ($r['entity_id'] && isset(self::ENTITIES[$entity])) {
                $ids[$entity][(int) $r['entity_id']] = true;
            }
            if ($r['action'] === 'box_item') {
                $d = json_decode((string) $r['detail'], true) ?: [];
                if (!empty($d['material_id'])) {
                    $ids['materials'][(int) $d['material_id']] = true;
                }
            }
            if (in_array($r['action'], ['password_change'], true) && $r['entity_id']) {
                $ids['users'][(int) $r['entity_id']] = true;
            }
        }
        $names = [];
        foreach ($ids as $entity => $set) {
            [, $cols] = self::ENTITIES[$entity];
            $list = array_keys($set);
            $marks = implode(',', array_fill(0, count($list), '?'));
            foreach (Database::fetchAll('SELECT id, ' . implode(', ', $cols) . " FROM {$entity} WHERE id IN ({$marks})", $list) as $row) {
                $names[$entity][(int) $row['id']] = match (true) {
                    $cols === ['last_name', 'first_name'] => person_name($row),
                    $cols === ['code', 'name'] => $row['code'] . ' · ' . $row['name'],
                    default => (string) ($row[$cols[0]] ?? ''),
                };
            }
        }
        return $names;
    }

    /** Usuarios que aparecen en el registro (para el filtro). */
    public static function users(): array
    {
        return Database::fetchAll('SELECT id, name FROM users ORDER BY name');
    }
}
