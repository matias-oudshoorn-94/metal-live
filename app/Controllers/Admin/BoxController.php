<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Session;
use App\Enums\BoxStatus;
use App\Services\Audit;
use App\Services\BoxService;

final class BoxController extends Controller
{
    public function index(): void
    {
        $status = (string) $this->request->query('status', '');
        $params = [];
        $where = 'b.active = 1';
        if (BoxStatus::tryFrom($status)) {
            $where .= ' AND b.status = ?';
            $params[] = $status;
        }
        $rows = Database::fetchAll(
            "SELECT b.*, s.scheduled_at, i.name AS institution_name,
                    (SELECT COUNT(*) FROM box_items bi WHERE bi.box_id = b.id) AS items,
                    (SELECT COUNT(*) FROM box_items bi WHERE bi.box_id = b.id AND bi.current_qty < bi.expected_qty) AS incomplete,
                    (SELECT COUNT(DISTINCT be.surgery_id) FROM box_events be WHERE be.box_id = b.id AND be.surgery_id IS NOT NULL) AS surgeries
               FROM boxes b
               LEFT JOIN surgeries s ON s.id = b.current_surgery_id
               LEFT JOIN institutions i ON i.id = s.institution_id
              WHERE {$where} ORDER BY b.code",
            $params
        );
        $counts = array_column(Database::fetchAll('SELECT status, COUNT(*) AS total FROM boxes WHERE active = 1 GROUP BY status'), 'total', 'status');
        $this->render('admin/boxes/index', ['title' => 'Cajas', 'rows' => $rows, 'status' => $status, 'counts' => $counts]);
    }

    public function show(string $id): void
    {
        $box = $this->find($id);
        $items = Database::fetchAll(
            'SELECT bi.*, m.code, m.name, m.stock_qty FROM box_items bi JOIN materials m ON m.id = bi.material_id WHERE bi.box_id = ? ORDER BY m.name',
            [$box['id']]
        );
        $events = Database::fetchAll(
            'SELECT be.*, u.name AS user_name FROM box_events be LEFT JOIN users u ON u.id = be.user_id WHERE be.box_id = ? ORDER BY be.id DESC LIMIT 100',
            [$box['id']]
        );
        // Historial de cirugías por caja.
        $surgeries = Database::fetchAll(
            'SELECT s.id, s.scheduled_at, s.stage, s.status, s.box_final_status, i.name AS institution_name
               FROM surgeries s JOIN institutions i ON i.id = s.institution_id
              WHERE s.id IN (SELECT DISTINCT surgery_id FROM box_events WHERE box_id = ? AND surgery_id IS NOT NULL)
              ORDER BY s.scheduled_at DESC',
            [$box['id']]
        );
        $materials = Database::fetchAll('SELECT id, code, name FROM materials WHERE active = 1 ORDER BY name');
        $this->render('admin/boxes/show', [
            'title' => 'Caja ' . $box['code'],
            'box' => $box,
            'items' => $items,
            'events' => $events,
            'surgeries' => $surgeries,
            'materials' => $materials,
        ]);
    }

    public function create(): void
    {
        $this->render('admin/boxes/form', ['title' => 'Nueva caja', 'box' => null]);
    }

    public function store(): void
    {
        $data = $this->input();
        $id = Database::insert('boxes', [...$data, 'status' => BoxStatus::Disponible->value, 'created_at' => now(), 'updated_at' => now()]);
        Database::insert('box_events', ['box_id' => $id, 'to_status' => BoxStatus::Disponible->value, 'user_id' => \App\Core\Auth::id(), 'note' => 'Alta de caja', 'created_at' => now()]);
        Audit::log('create', 'boxes', $id);
        $this->redirect('/admin/cajas/' . $id, 'Caja creada. Ahora cargá su contenido.');
    }

    public function edit(string $id): void
    {
        $this->render('admin/boxes/form', ['title' => 'Editar caja', 'box' => $this->find($id)]);
    }

    public function update(string $id): void
    {
        $box = $this->find($id);
        $data = $this->input((int) $box['id']);
        Database::update('boxes', [...$data, 'updated_at' => now()], (int) $box['id']);
        Audit::log('update', 'boxes', (int) $box['id']);
        $this->redirect('/admin/cajas/' . $box['id'], 'Caja actualizada.');
    }

    public function saveItem(string $id): void
    {
        $box = $this->find($id);
        $data = $this->validate([
            'material_id' => 'required|exists:materials',
            'expected_qty' => 'required|int|min_value:0',
            'current_qty' => 'required|int|min_value:0',
        ], ['material_id' => 'Material', 'expected_qty' => 'Cantidad esperada', 'current_qty' => 'Cantidad actual']);
        $existing = Database::fetch('SELECT id FROM box_items WHERE box_id = ? AND material_id = ?', [$box['id'], $data['material_id']]);
        if ($existing) {
            Database::update('box_items', ['expected_qty' => $data['expected_qty'], 'current_qty' => $data['current_qty']], (int) $existing['id']);
        } else {
            Database::insert('box_items', [...$data, 'box_id' => (int) $box['id']]);
        }
        Audit::log('box_item', 'boxes', (int) $box['id'], $data);
        $this->back(success: 'Contenido de la caja actualizado.');
    }

    public function deleteItem(string $id, string $item): void
    {
        $box = $this->find($id);
        Database::query('DELETE FROM box_items WHERE id = ? AND box_id = ?', [(int) $item, $box['id']]);
        $this->back(success: 'Material quitado de la caja.');
    }

    public function refill(string $id): void
    {
        $box = $this->find($id);
        try {
            $moved = Database::transaction(fn () => BoxService::refill((int) $box['id']));
        } catch (\DomainException $e) {
            $this->back($e->getMessage());
        }
        $this->back(success: $moved ? "Se repusieron {$moved} unidades desde el stock suelto." : 'No había faltantes que reponer con el stock disponible.');
    }

    /** Usada / para esterilizar → disponible (tras esterilizar y reponer). */
    public function markAvailable(string $id): void
    {
        $box = $this->find($id);
        if (!in_array($box['status'], [BoxStatus::Usada->value, BoxStatus::ParaEsterilizar->value], true)) {
            $this->back('Solo las cajas usadas o para esterilizar pueden volver a disponible.');
        }
        BoxService::setStatus((int) $box['id'], BoxStatus::Disponible, null, $box['status'] === BoxStatus::ParaEsterilizar->value ? 'Esterilizada' : 'Revisada y repuesta');
        $this->back(success: 'La caja está disponible.');
    }

    public function destroy(string $id): void
    {
        $box = $this->find($id);
        if ($box['status'] !== BoxStatus::Disponible->value) {
            $this->back('Solo se pueden dar de baja cajas disponibles.');
        }
        $hasHistory = Database::value('SELECT 1 FROM surgeries WHERE box_id = ? UNION SELECT 1 FROM box_events WHERE box_id = ? AND surgery_id IS NOT NULL LIMIT 1', [$box['id'], $box['id']]);
        if ($hasHistory) {
            Database::update('boxes', ['active' => 0], (int) $box['id']);
        } else {
            Database::delete('boxes', (int) $box['id']);
        }
        Audit::log('delete', 'boxes', (int) $box['id'], $box['code']);
        $this->redirect('/admin/cajas', 'Caja dada de baja.');
    }

    private function find(string $id): array
    {
        return Database::fetch('SELECT * FROM boxes WHERE id = ?', [(int) $id]) ?? throw new HttpException(404);
    }

    private function input(int $ignore = 0): array
    {
        $data = $this->validate([
            'code' => 'required|max:60|regex:/^[A-Za-z0-9\-_.]+$/',
            'name' => 'required|max:160',
            'description' => 'max:2000',
        ], ['code' => 'Código', 'name' => 'Nombre', 'description' => 'Descripción']);
        if (Database::value('SELECT 1 FROM boxes WHERE code = ? AND id <> ?', [$data['code'], $ignore])) {
            Session::flash('_errors', ['code' => 'Ya existe una caja con ese código.']);
            Session::flash('_old_input', $_POST);
            $this->back('Revisá los campos marcados.');
        }
        return $data;
    }
}
