<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Services\ConsignmentService;

final class ConsignmentController extends Controller
{
    public function index(): void
    {
        $q = (string) $this->request->query('q', '');
        $params = [];
        $filter = '';
        if ($q !== '') {
            $filter = ' AND c.name LIKE ?';
            $params[] = "%{$q}%";
        }
        $rows = Database::fetchAll(
            "SELECT c.id, c.name, c.type,
                    COALESCE(st.units, 0) AS units, COALESCE(st.items, 0) AS items, COALESCE(st.value, 0) AS value,
                    COALESCE(pb.pending, 0) AS pending, COALESCE(pb.pending_amount, 0) AS pending_amount,
                    lm.last_at
               FROM clients c
               LEFT JOIN (SELECT cs.client_id, SUM(cs.quantity) AS units, COUNT(*) AS items, SUM(cs.quantity * m.price) AS value
                            FROM consignment_stock cs JOIN materials m ON m.id = cs.material_id
                           WHERE cs.quantity > 0 GROUP BY cs.client_id) st ON st.client_id = c.id
               LEFT JOIN (SELECT client_id, COUNT(*) AS pending, SUM(-quantity * unit_price) AS pending_amount
                            FROM consignment_movements WHERE type = 'consumo' AND billed_at IS NULL GROUP BY client_id) pb ON pb.client_id = c.id
               LEFT JOIN (SELECT client_id, MAX(created_at) AS last_at FROM consignment_movements GROUP BY client_id) lm ON lm.client_id = c.id
              WHERE (st.units IS NOT NULL OR pb.pending IS NOT NULL){$filter}
              ORDER BY c.name",
            $params
        );
        $clients = Database::fetchAll('SELECT id, name FROM clients ORDER BY name');
        $this->render('admin/consignments/index', [
            'title' => 'Stock en consignación',
            'rows' => $rows,
            'q' => $q,
            'clients' => $clients,
            'totals' => [
                'clients' => count($rows),
                'units' => array_sum(array_column($rows, 'units')),
                'value' => array_sum(array_column($rows, 'value')),
                'pending_amount' => array_sum(array_column($rows, 'pending_amount')),
            ],
        ]);
    }

    /** Desde el selector de la lista: lleva a la ficha del cliente elegido. */
    public function open(): void
    {
        $id = (int) $this->request->query('client_id', 0);
        $id > 0 ? $this->redirect('/admin/consignaciones/' . $id) : $this->redirect('/admin/consignaciones');
    }

    public function show(string $id): void
    {
        $client = $this->client($id);
        $type = (string) $this->request->query('tipo', '');
        $params = [$client['id']];
        $where = 'cm.client_id = ?';
        if (isset(ConsignmentService::TYPES[$type])) {
            $where .= ' AND cm.type = ?';
            $params[] = $type;
        }
        $movements = Database::fetchAll(
            "SELECT cm.*, m.code, m.name AS material_name, u.name AS user_name
               FROM consignment_movements cm
               JOIN materials m ON m.id = cm.material_id
               LEFT JOIN users u ON u.id = cm.user_id
              WHERE {$where} ORDER BY cm.id DESC LIMIT 300",
            $params
        );
        $pending = Database::fetchAll(
            "SELECT cm.*, m.code, m.name AS material_name FROM consignment_movements cm JOIN materials m ON m.id = cm.material_id
              WHERE cm.client_id = ? AND cm.type = 'consumo' AND cm.billed_at IS NULL ORDER BY cm.id",
            [$client['id']]
        );
        $balance = ConsignmentService::balance((int) $client['id']);
        $materials = Database::fetchAll('SELECT id, code, name, stock_qty FROM materials WHERE active = 1 ORDER BY name');

        $this->render('admin/consignments/show', [
            'title' => 'Consignación · ' . $client['name'],
            'client' => $client,
            'balance' => $balance,
            'movements' => $movements,
            'pending' => $pending,
            'materials' => $materials,
            'type' => $type,
        ]);
    }

    public function movement(string $id): void
    {
        $client = $this->client($id);
        $data = $this->validate([
            'type' => 'required|in:' . implode(',', array_keys(ConsignmentService::TYPES)),
            'reference' => 'max:120',
            'notes' => 'max:255',
        ], ['type' => 'Tipo', 'reference' => 'Remito / comprobante', 'notes' => 'Observaciones']);

        $items = [];
        foreach ((array) $this->request->input('items', []) as $row) {
            $material = filter_var($row['material_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $qty = filter_var($row['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
            if ($material && $qty) {
                $items[] = ['material_id' => $material, 'quantity' => $qty];
            }
        }
        if ($data['type'] === 'consumo' && empty($data['reference'])) {
            $this->back('Indicá la referencia del consumo (paciente, parte o remito del cliente).');
        }

        try {
            $count = ConsignmentService::register(
                (int) $client['id'],
                $data['type'],
                $items,
                ($data['reference'] ?? '') ?: null,
                ($data['notes'] ?? '') ?: null,
                $this->request->input('direction') === 'mas'
            );
        } catch (\DomainException $e) {
            $this->back($e->getMessage());
        }
        $this->back(success: ConsignmentService::TYPES[$data['type']] . ": {$count} " . ($count === 1 ? 'material registrado.' : 'materiales registrados.'));
    }

    public function bill(string $id): void
    {
        $client = $this->client($id);
        $data = $this->validate(['invoice_number' => 'required|max:60'], ['invoice_number' => 'Número de factura']);
        try {
            $count = ConsignmentService::markBilled((int) $client['id'], (array) $this->request->input('movements', []), $data['invoice_number']);
        } catch (\DomainException $e) {
            $this->back($e->getMessage());
        }
        $this->back(success: ($count === 1 ? '1 consumo marcado como facturado' : "{$count} consumos marcados como facturados") . " ({$data['invoice_number']}).");
    }

    private function client(string $id): array
    {
        return Database::fetch('SELECT * FROM clients WHERE id = ?', [(int) $id]) ?? throw new HttpException(404);
    }
}
