<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Services\Audit;
use App\Services\BoxService;

final class MaterialController extends Controller
{
    private const RULES = [
        'code' => 'required|max:60',
        'name' => 'required|max:190',
        'category' => 'max:80',
        'unit' => 'required|max:20',
        'price' => 'numeric|min_value:0',
        'min_stock' => 'int|min_value:0',
        'active' => 'bool',
        'notes' => 'max:2000',
    ];
    private const LABELS = ['code' => 'Código', 'name' => 'Nombre', 'category' => 'Categoría', 'unit' => 'Unidad', 'price' => 'Precio', 'min_stock' => 'Stock mínimo'];

    public function index(): void
    {
        $q = (string) $this->request->query('q', '');
        $category = (string) $this->request->query('category', '');
        $conds = ['1 = 1'];
        $params = [];
        if ($q !== '') {
            $conds[] = '(code LIKE ? OR name LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%");
        }
        if ($category !== '') {
            $conds[] = 'category = ?';
            $params[] = $category;
        }
        if ($this->request->query('bajo') === '1') {
            $conds[] = 'stock_qty <= min_stock AND min_stock > 0';
        }
        $rows = Database::fetchAll(
            'SELECT m.*, (SELECT COALESCE(SUM(current_qty), 0) FROM box_items bi WHERE bi.material_id = m.id) AS in_boxes,
                    (SELECT COALESCE(SUM(quantity), 0) FROM consignment_stock cs WHERE cs.material_id = m.id) AS in_consignment
               FROM materials m WHERE ' . implode(' AND ', $conds) . ' ORDER BY active DESC, name LIMIT 500',
            $params
        );
        $categories = array_column(Database::fetchAll("SELECT DISTINCT category FROM materials WHERE category IS NOT NULL AND category <> '' ORDER BY category"), 'category');
        $this->render('admin/materials/index', ['title' => 'Materiales y stock', 'rows' => $rows, 'q' => $q, 'category' => $category, 'categories' => $categories]);
    }

    public function show(string $id): void
    {
        $m = $this->find($id);
        $movements = Database::fetchAll(
            'SELECT sm.*, u.name AS user_name, b.code AS box_code FROM stock_movements sm
               LEFT JOIN users u ON u.id = sm.user_id LEFT JOIN boxes b ON b.id = sm.box_id
              WHERE sm.material_id = ? ORDER BY sm.id DESC LIMIT 100',
            [$m['id']]
        );
        $boxes = Database::fetchAll(
            'SELECT b.id, b.code, b.name, b.status, bi.expected_qty, bi.current_qty FROM box_items bi JOIN boxes b ON b.id = bi.box_id WHERE bi.material_id = ? ORDER BY b.code',
            [$m['id']]
        );
        $consigned = Database::fetchAll(
            'SELECT c.id, c.name, cs.quantity FROM consignment_stock cs JOIN clients c ON c.id = cs.client_id WHERE cs.material_id = ? AND cs.quantity > 0 ORDER BY c.name',
            [$m['id']]
        );
        $this->render('admin/materials/show', ['title' => $m['name'], 'm' => $m, 'movements' => $movements, 'boxes' => $boxes, 'consigned' => $consigned]);
    }

    public function create(): void
    {
        $this->render('admin/materials/form', ['title' => 'Nuevo material', 'm' => null]);
    }

    public function store(): void
    {
        $data = $this->validate([...self::RULES, 'stock_qty' => 'int|min_value:0'], self::LABELS);
        $this->assertUniqueCode($data['code']);
        $initial = (int) ($data['stock_qty'] ?? 0);
        unset($data['stock_qty']);
        $id = Database::transaction(function () use ($data, $initial): int {
            $id = Database::insert('materials', [...$this->normalize($data), 'stock_qty' => 0, 'created_at' => now(), 'updated_at' => now()]);
            if ($initial > 0) {
                BoxService::moveStock($id, $initial, 'ingreso', 'Stock inicial');
            }
            return $id;
        });
        Audit::log('create', 'materials', $id);
        $this->redirect('/admin/materiales/' . $id, 'Material creado.');
    }

    public function edit(string $id): void
    {
        $m = $this->find($id);
        $this->render('admin/materials/form', ['title' => 'Editar material', 'm' => $m]);
    }

    public function update(string $id): void
    {
        $m = $this->find($id);
        $data = $this->validate(self::RULES, self::LABELS);
        $this->assertUniqueCode($data['code'], (int) $m['id']);
        Database::update('materials', [...$this->normalize($data), 'updated_at' => now()], (int) $m['id']);
        Audit::log('update', 'materials', (int) $m['id']);
        $this->redirect('/admin/materiales/' . $m['id'], 'Material actualizado.');
    }

    /** Ingreso o ajuste de stock suelto. */
    public function movement(string $id): void
    {
        $m = $this->find($id);
        $data = $this->validate([
            'type' => 'required|in:ingreso,egreso,ajuste',
            'quantity' => 'required|int|min_value:1',
            'reason' => 'max:255',
        ], ['type' => 'Tipo', 'quantity' => 'Cantidad', 'reason' => 'Motivo']);
        $qty = $data['type'] === 'ingreso' ? $data['quantity'] : -$data['quantity'];
        if ($data['type'] === 'ajuste' && $this->request->input('direction') === 'mas') {
            $qty = $data['quantity'];
        }
        try {
            BoxService::moveStock((int) $m['id'], $qty, $data['type'], $data['reason']);
        } catch (\DomainException $e) {
            $this->back($e->getMessage());
        }
        $this->back(success: 'Movimiento registrado.');
    }

    public function destroy(string $id): void
    {
        $m = $this->find($id);
        $used = Database::value('SELECT 1 FROM box_items WHERE material_id = ? UNION SELECT 1 FROM consumption_items WHERE material_id = ? UNION SELECT 1 FROM consignment_movements WHERE material_id = ? LIMIT 1', [$m['id'], $m['id'], $m['id']]);
        if ($used) {
            Database::update('materials', ['active' => 0], (int) $m['id']);
            $this->redirect('/admin/materiales', 'El material tiene historial: se desactivó en lugar de eliminarse.');
        }
        Database::delete('materials', (int) $m['id']);
        Audit::log('delete', 'materials', (int) $m['id'], $m['name']);
        $this->redirect('/admin/materiales', 'Material eliminado.');
    }

    private function find(string $id): array
    {
        return Database::fetch('SELECT * FROM materials WHERE id = ?', [(int) $id]) ?? throw new HttpException(404);
    }

    private function normalize(array $data): array
    {
        $data['price'] ??= '0';
        $data['min_stock'] ??= 0;
        return $data;
    }

    private function assertUniqueCode(string $code, int $ignore = 0): void
    {
        if (Database::value('SELECT 1 FROM materials WHERE code = ? AND id <> ?', [$code, $ignore])) {
            \App\Core\Session::flash('_errors', ['code' => 'Ya existe un material con ese código.']);
            \App\Core\Session::flash('_old_input', $_POST);
            $this->back('Revisá los campos marcados.');
        }
    }
}
