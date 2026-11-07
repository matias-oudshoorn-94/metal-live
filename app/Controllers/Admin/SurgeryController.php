<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Session;
use App\Core\Upload;
use App\Enums\BoxStatus;
use App\Enums\SurgeryStage;
use App\Models\Catalog;
use App\Models\Surgery;
use App\Services\BoxService;
use App\Services\CustomFields;
use App\Services\SurgeryWorkflow;

final class SurgeryController extends Controller
{
    private const PER_PAGE = 25;

    // ------------------------------------------------------------------ Listado

    public function index(): void
    {
        [$where, $params] = Surgery::scope();
        $filters = [
            'q' => (string) $this->request->query('q', ''),
            'stage' => (string) $this->request->query('stage', ''),
            'status' => (string) $this->request->query('status', 'activa'),
            'seller' => (int) $this->request->query('seller', 0),
            'from' => (string) $this->request->query('from', ''),
            'to' => (string) $this->request->query('to', ''),
        ];

        $conds = [$where];
        if ($filters['stage'] !== '' && SurgeryStage::tryFrom($filters['stage'])) {
            $conds[] = 's.stage = ?';
            $params[] = $filters['stage'];
        }
        if (in_array($filters['status'], ['activa', 'suspendida'], true)) {
            $conds[] = 's.status = ?';
            $params[] = $filters['status'];
        }
        if ($filters['seller'] && Auth::can('surgeries.view_all')) {
            $conds[] = 's.seller_id = ?';
            $params[] = $filters['seller'];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from'])) {
            $conds[] = 's.scheduled_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to'])) {
            $conds[] = 's.scheduled_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }
        if ($filters['q'] !== '') {
            $like = '%' . $filters['q'] . '%';
            $id = (int) preg_replace('/\D/', '', $filters['q']);
            $conds[] = '(p.last_name LIKE ? OR p.first_name LIKE ? OR p.document LIKE ? OR i.name LIKE ? OR d.last_name LIKE ? OR c.name LIKE ? OR s.procedure_name LIKE ? OR s.id = ?)';
            array_push($params, $like, $like, $like, $like, $like, $like, $like, $id);
        }

        $sqlWhere = ' WHERE ' . implode(' AND ', $conds);
        $total = (int) Database::value(
            'SELECT COUNT(*) FROM surgeries s JOIN patients p ON p.id = s.patient_id JOIN institutions i ON i.id = s.institution_id
               JOIN doctors d ON d.id = s.doctor_id JOIN clients c ON c.id = s.client_id' . $sqlWhere,
            $params
        );
        $offset = ($this->page() - 1) * self::PER_PAGE;
        $rows = Database::fetchAll(
            Surgery::select() . $sqlWhere . ' ORDER BY COALESCE(s.scheduled_at, s.created_at) DESC LIMIT ' . self::PER_PAGE . ' OFFSET ' . $offset,
            $params
        );

        $this->render('admin/surgeries/index', [
            'title' => 'Cirugías',
            'rows' => $rows,
            'filters' => $filters,
            'total' => $total,
            'perPage' => self::PER_PAGE,
            'sellers' => Auth::can('surgeries.view_all') ? Surgery::usersWith('surgeries.view_own') : [],
        ]);
    }

    public function agenda(): void
    {
        $from = date('Y-m-d 00:00:00');
        $rows = Database::fetchAll(
            Surgery::select() . " WHERE s.status = 'activa' AND s.scheduled_at >= ? AND s.scheduled_at < ? ORDER BY s.scheduled_at",
            [$from, date('Y-m-d 00:00:00', strtotime('+30 days'))]
        );
        $days = [];
        foreach ($rows as $row) {
            $days[substr($row['scheduled_at'], 0, 10)][] = $row;
        }
        $this->render('admin/surgeries/agenda', ['title' => 'Agenda de cirugías', 'days' => $days]);
    }

    // ------------------------------------------------------------------ Cotización

    public function create(): void
    {
        $this->render('admin/surgeries/form', [
            'title' => 'Nueva cotización',
            'surgery' => null,
            'items' => [],
            ...$this->formData(),
        ]);
    }

    public function store(): void
    {
        [$data, $items, $custom] = $this->quoteInput();
        try {
            $id = SurgeryWorkflow::createQuote($data, $items, $custom, $this->request->file('prescription'));
            if ($this->request->input('send') === '1') {
                SurgeryWorkflow::sendToAuthorization(Surgery::find($id));
            }
        } catch (\DomainException $e) {
            Session::flash('_old_input', $_POST);
            $this->back($e->getMessage());
        }
        $this->redirect('/admin/cirugias/' . $id, 'Cotización guardada.');
    }

    public function edit(string $id): void
    {
        $s = $this->surgery($id);
        $this->ownQuote($s);
        $this->render('admin/surgeries/form', [
            'title' => 'Editar cotización ' . surgery_code($s['id']),
            'surgery' => $s,
            'items' => Surgery::quoteItems((int) $s['id']),
            ...$this->formData(),
        ]);
    }

    public function update(string $id): void
    {
        $s = $this->surgery($id);
        $this->ownQuote($s);
        [$data, $items, $custom] = $this->quoteInput();
        $this->attempt(fn () => SurgeryWorkflow::updateQuote($s, $data, $items, $custom, $this->request->file('prescription')));
        $this->redirect('/admin/cirugias/' . $s['id'], 'Cotización actualizada.');
    }

    public function sendToAuthorization(string $id): void
    {
        $s = $this->surgery($id);
        $this->ownQuote($s);
        $this->attempt(fn () => SurgeryWorkflow::sendToAuthorization($s));
        $this->back(success: 'La cotización pasó a autorización.');
    }

    public function registerAuthorization(string $id): void
    {
        $s = $this->surgery($id);
        $this->ownQuote($s);
        $data = $this->validate([
            'auth_type' => 'required|in:oc,particular',
            'oc_number' => 'max:80',
        ], ['auth_type' => 'Tipo de autorización', 'oc_number' => 'N.º de OC']);
        $this->attempt(fn () => SurgeryWorkflow::authorize($s, $data['auth_type'], $data['oc_number'], $this->request->file('oc_file')));
        $this->back(success: 'Autorización registrada. La cirugía pasó a asignación.');
    }

    // ------------------------------------------------------------------ Asignación y entrega

    public function assign(string $id): void
    {
        $s = $this->surgery($id);
        $data = $this->validate([
            'scheduled_at' => 'required|datetime',
            'instrumentist_id' => 'required|exists:instrumentists',
            'driver_id' => 'required|exists:users',
            'box_id' => 'required|exists:boxes',
            'logistics_notes' => 'max:2000',
        ], ['scheduled_at' => 'Fecha y hora', 'instrumentist_id' => 'Instrumentadora', 'driver_id' => 'Chofer', 'box_id' => 'Caja', 'logistics_notes' => 'Indicaciones']);
        $this->attempt(fn () => SurgeryWorkflow::assign($s, $data));
        $this->back(success: 'Asignación guardada. Se avisó al equipo de Cx.');
    }

    public function confirmCx(string $id): void
    {
        $s = $this->surgery($id);
        $data = $this->validate(['scheduled_at' => 'required|datetime'], ['scheduled_at' => 'Fecha y hora']);
        $this->attempt(fn () => SurgeryWorkflow::confirmCx($s, $data['scheduled_at']));
        $this->back(success: 'Fecha y horario confirmados.');
    }

    public function prepare(string $id): void
    {
        $s = $this->surgery($id);
        $this->attempt(fn () => SurgeryWorkflow::startPreparation($s));
        $this->back(success: 'Material en preparación. Se avisó al chofer.');
    }

    public function dispatch(string $id): void
    {
        $s = $this->surgery($id);
        $this->attempt(fn () => SurgeryWorkflow::dispatch($s));
        $this->back(success: 'Material listo para reparto. Se avisó al chofer.');
    }

    public function deliver(string $id): void
    {
        $s = $this->surgery($id);
        // El chofer solo confirma sus propias entregas; logística puede confirmar cualquiera.
        if (!Auth::can('logistics.manage') && (int) $s['driver_id'] !== Auth::id()) {
            throw new HttpException(403);
        }
        $this->attempt(fn () => SurgeryWorkflow::confirmDelivery($s));
        $this->back(success: 'Entrega confirmada. Se avisó al vendedor.');
    }

    // ------------------------------------------------------------------ Post-cirugía

    public function report(string $id): void
    {
        $s = $this->surgery($id);
        $data = $this->validate([
            'performed_at' => 'required|datetime',
            'observations' => 'max:5000',
        ], ['performed_at' => 'Fecha de realización', 'observations' => 'Observaciones']);
        $this->attempt(fn () => SurgeryWorkflow::report($s, $data['performed_at'], $data['observations'], $this->request->file('report_pdf')));
        $this->back(success: 'Informe cargado. Falta cargar el consumo.');
    }

    public function consumption(string $id): void
    {
        $s = $this->surgery($id);
        $rows = [];
        foreach ((array) ($_POST['items'] ?? []) as $item) {
            $materialId = (int) ($item['material_id'] ?? 0);
            $used = max(0, (int) ($item['used'] ?? 0));
            $missing = max(0, (int) ($item['missing'] ?? 0));
            if (!$materialId || ($used + $missing) === 0) {
                continue;
            }
            $rows[] = [
                'material_id' => $materialId,
                'source' => ($item['source'] ?? 'caja') === 'stock' ? 'stock' : 'caja',
                'used' => $used,
                'missing' => $missing,
                'replenish' => !empty($item['replenish']),
            ];
        }
        $final = BoxStatus::tryFrom((string) $this->request->input('box_final_status', '')) ?? BoxStatus::ParaEsterilizar;
        $this->attempt(fn () => SurgeryWorkflow::consumption($s, $rows, $final));
        $this->back(success: 'Consumo registrado. La cirugía pasó a facturación.');
    }

    // ------------------------------------------------------------------ Facturación y cobranza

    public function billing(string $id): void
    {
        $s = $this->surgery($id);
        $data = $this->validate([
            'invoice_number' => 'max:60',
            'invoice_date' => 'date',
            'invoice_amount' => 'numeric|min_value:0',
            'fee_liquidated' => 'bool',
            'collected' => 'bool',
        ], ['invoice_number' => 'N.º de factura', 'invoice_date' => 'Fecha de factura', 'invoice_amount' => 'Monto facturado']);
        $emit = $this->request->input('emit') === '1';
        $this->attempt(fn () => SurgeryWorkflow::billing($s, $data, $emit, $this->request->file('invoice_file')));
        $this->back(success: $emit ? 'Factura emitida. La cirugía pasó a cobranza.' : 'Facturación actualizada.');
    }

    public function addSupplierInvoice(string $id): void
    {
        $s = $this->surgery($id);
        $data = $this->validate([
            'supplier_id' => 'required|exists:suppliers',
            'invoice_number' => 'required|max:60',
            'invoice_date' => 'date',
            'amount' => 'required|numeric|min_value:0',
            'paid' => 'bool',
            'paid_date' => 'date',
            'notes' => 'max:1000',
        ], ['supplier_id' => 'Proveedor', 'invoice_number' => 'N.º de factura', 'amount' => 'Monto', 'paid_date' => 'Fecha de pago']);
        $this->attempt(fn () => SurgeryWorkflow::addSupplierInvoice($s, $data, $this->request->file('supplier_file')));
        $this->back(success: 'Factura de proveedor ingresada.');
    }

    public function toggleSupplierPaid(string $id, string $invoice): void
    {
        $s = $this->surgery($id);
        $row = Database::fetch('SELECT * FROM supplier_invoices WHERE id = ? AND surgery_id = ?', [(int) $invoice, $s['id']]) ?? throw new HttpException(404);
        $paid = (int) $row['paid'] ? 0 : 1;
        Database::update('supplier_invoices', ['paid' => $paid, 'paid_date' => $paid ? date('Y-m-d') : null], (int) $row['id']);
        Surgery::log((int) $s['id'], 'Pago a proveedor ' . ($paid ? 'registrado' : 'anulado'), $row['invoice_number']);
        $this->back(success: 'Pago a proveedor actualizado.');
    }

    public function deleteSupplierInvoice(string $id, string $invoice): void
    {
        $s = $this->surgery($id);
        $row = Database::fetch('SELECT * FROM supplier_invoices WHERE id = ? AND surgery_id = ?', [(int) $invoice, $s['id']]) ?? throw new HttpException(404);
        Database::delete('supplier_invoices', (int) $row['id']);
        Surgery::log((int) $s['id'], 'Factura de proveedor eliminada', $row['invoice_number']);
        $this->back(success: 'Factura de proveedor eliminada.');
    }

    public function collect(string $id): void
    {
        $s = $this->surgery($id);
        $isCheque = in_array($this->request->input('payment_type'), ['cheque', 'echeq'], true);
        $req = $isCheque ? 'required|' : '';
        $data = $this->validate([
            'payment_date' => 'required|date',
            'payment_type' => 'required|in:' . implode(',', array_keys(Surgery::PAYMENT_TYPES)),
            'payment_amount' => 'required|numeric|min_value:0',
            'cheque_bank' => $req . 'max:120',
            'cheque_number' => $req . 'max:60',
            'cheque_date' => $req . 'date',
            'cheque_holder' => 'max:160',
            'collection_notes' => 'max:2000',
        ], [
            'payment_date' => 'Fecha de pago', 'payment_type' => 'Tipo de pago', 'payment_amount' => 'Monto cobrado',
            'cheque_bank' => 'Banco', 'cheque_number' => 'N.º de cheque', 'cheque_date' => 'Fecha de cobro del cheque', 'cheque_holder' => 'Librador',
        ]);
        if (!$isCheque) {
            $data = [...$data, 'cheque_bank' => null, 'cheque_number' => null, 'cheque_date' => null, 'cheque_holder' => null];
        }
        $this->attempt(fn () => SurgeryWorkflow::collect($s, $data));
        $this->back(success: 'Cobranza registrada. Circuito finalizado.');
    }

    // ------------------------------------------------------------------ Suspensión

    public function suspend(string $id): void
    {
        $s = $this->surgery($id);
        $data = $this->validate(['suspension_reason' => 'required|max:1000'], ['suspension_reason' => 'Motivo']);
        $this->attempt(fn () => SurgeryWorkflow::suspend($s, $data['suspension_reason']));
        $this->back(success: 'Cirugía suspendida. La caja volvió al stock.');
    }

    public function reprogram(string $id): void
    {
        $s = $this->surgery($id);
        $data = $this->validate(['scheduled_at' => 'required|datetime'], ['scheduled_at' => 'Nueva fecha']);
        $this->attempt(fn () => SurgeryWorkflow::reprogram($s, $data['scheduled_at']));
        $this->back(success: 'Cirugía reprogramada. Conserva cotización y autorización.');
    }

    // ------------------------------------------------------------------ Detalle

    public function show(string $id): void
    {
        $s = $this->surgery($id);
        $sid = (int) $s['id'];
        $boxItems = $s['box_id'] ? Database::fetchAll(
            'SELECT bi.*, m.code, m.name FROM box_items bi JOIN materials m ON m.id = bi.material_id WHERE bi.box_id = ? ORDER BY m.name',
            [$s['box_id']]
        ) : [];

        $this->render('admin/surgeries/show', [
            'title' => surgery_code($sid),
            's' => $s,
            'stage' => SurgeryStage::from($s['stage']),
            'isOwner' => (int) $s['seller_id'] === Auth::id(),
            'showSummary' => Surgery::canSeeSummary($s),
            'items' => Surgery::quoteItems($sid),
            'quoteTotal' => Surgery::quoteTotal($sid),
            'consumption' => Surgery::consumption($sid),
            'attachments' => Surgery::attachments($sid),
            'supplierInvoices' => Surgery::supplierInvoices($sid),
            'events' => Auth::canAny('surgery.history.view', 'surgeries.view_all') ? Surgery::events($sid) : [],
            'customFields' => CustomFields::for('surgery_quote', false),
            'customData' => Surgery::customData($s),
            'boxes' => Auth::can('logistics.manage') ? BoxService::assignable($s['box_id'] ? (int) $s['box_id'] : null) : [],
            'instrumentists' => Auth::can('logistics.manage') ? Catalog::people('instrumentists', true) : [],
            'drivers' => Auth::can('logistics.manage') ? Surgery::usersWith('delivery.confirm') : [],
            'boxItems' => $boxItems,
            'materials' => Auth::can('cx.post') ? Database::fetchAll('SELECT id, code, name, stock_qty FROM materials WHERE active = 1 ORDER BY name') : [],
            'suppliers' => Auth::can('billing.edit') ? Catalog::named('suppliers') : [],
        ]);
    }

    public function download(string $id): void
    {
        $file = Database::fetch('SELECT * FROM attachments WHERE id = ?', [(int) $id]) ?? throw new HttpException(404);
        $s = $this->surgery((string) $file['surgery_id']);
        $perm = Surgery::ATTACHMENT_PERMS[$file['kind']] ?? null;
        if (!$perm || !Auth::can($perm)) {
            throw new HttpException(403);
        }
        $path = Upload::absolutePath($file['stored_path']);
        $inline = in_array($file['mime'], ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true);

        header_remove('Content-Security-Policy');
        header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
        header('Content-Type: ' . $file['mime']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($file['original_name']));
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }

    // ------------------------------------------------------------------ Auxiliares

    private function surgery(string $id): array
    {
        $s = Surgery::find((int) $id) ?? throw new HttpException(404);
        if (!Surgery::canAccess($s)) {
            throw new HttpException(403);
        }
        return $s;
    }

    /** Un vendedor (sin acceso total) solo edita sus propias cotizaciones. */
    private function ownQuote(array $s): void
    {
        if (!Auth::can('surgeries.view_all') && (int) $s['seller_id'] !== Auth::id()) {
            throw new HttpException(403);
        }
    }

    private function attempt(callable $action): void
    {
        try {
            $action();
        } catch (\DomainException $e) {
            $this->back($e->getMessage());
        }
    }

    private function formData(): array
    {
        return [
            'patients' => Database::fetchAll('SELECT id, last_name, first_name, document FROM patients ORDER BY last_name, first_name'),
            'institutions' => Catalog::named('institutions'),
            'doctors' => Catalog::people('doctors'),
            'clients' => Catalog::named('clients'),
            'sellers' => Surgery::usersWith('surgeries.view_own'),
            'materials' => Database::fetchAll('SELECT id, code, name, price FROM materials WHERE active = 1 ORDER BY name'),
            'customFields' => CustomFields::for('surgery_quote'),
            'canPickSeller' => Auth::can('surgeries.view_all'),
        ];
    }

    /** @return array{0:array, 1:array, 2:array} */
    private function quoteInput(): array
    {
        $data = $this->validate([
            'patient_id' => 'required|exists:patients',
            'institution_id' => 'required|exists:institutions',
            'doctor_id' => 'required|exists:doctors',
            'client_id' => 'required|exists:clients',
            'seller_id' => 'exists:users',
            'procedure_name' => 'max:255',
            'commission_pct' => 'numeric|min_value:0',
            'fee_amount' => 'numeric|min_value:0',
            'quote_notes' => 'max:5000',
        ], [
            'patient_id' => 'Paciente', 'institution_id' => 'Institución', 'doctor_id' => 'Médico', 'client_id' => 'Cliente',
            'seller_id' => 'Vendedor', 'procedure_name' => 'Procedimiento', 'commission_pct' => 'Comisión', 'fee_amount' => 'Honorario',
        ]);

        if (!Auth::can('surgeries.view_all')) {
            $data['seller_id'] = Auth::id();
        }
        if ($data['commission_pct'] === null && $data['seller_id']) {
            $data['commission_pct'] = (string) Database::value('SELECT commission_pct FROM users WHERE id = ?', [$data['seller_id']]);
        }
        $data['commission_pct'] ??= '0';
        $data['fee_amount'] ??= '0';

        $errors = [];
        $items = [];
        foreach (array_values((array) ($_POST['items'] ?? [])) as $i => $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $materialId = (int) ($item['material_id'] ?? 0);
            if ($description === '' && !$materialId) {
                continue;
            }
            if ($description === '' && $materialId) {
                $description = (string) Database::value('SELECT name FROM materials WHERE id = ?', [$materialId]);
            }
            $qty = filter_var($item['quantity'] ?? 1, FILTER_VALIDATE_INT);
            $price = str_replace(',', '.', (string) ($item['unit_price'] ?? '0'));
            if ($qty === false || $qty < 1 || !is_numeric($price) || (float) $price < 0) {
                $errors["items.{$i}"] = 'Revisá cantidad y precio del ítem ' . ($i + 1) . '.';
                continue;
            }
            $items[] = [
                'material_id' => $materialId ?: null,
                'description' => mb_substr($description, 0, 255),
                'quantity' => $qty,
                'unit_price' => (string) (float) $price,
            ];
        }

        [$custom, $customErrors] = CustomFields::collect('surgery_quote', (array) ($_POST['custom'] ?? []));
        $errors += $customErrors;

        if ($errors) {
            Session::flash('_errors', $errors);
            Session::flash('_old_input', $_POST);
            $this->back('Revisá los campos marcados.');
        }

        return [$data, $items, $custom];
    }
}
