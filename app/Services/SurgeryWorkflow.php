<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Upload;
use App\Enums\BoxStatus;
use App\Enums\SurgeryStage;
use App\Models\Surgery;
use App\Services\Notifications\Message;
use App\Services\Notifications\Notifier;

/**
 * Reglas de negocio del recorrido de una cirugía (los 8 estadios),
 * suspensión / reprogramación y los avisos asociados.
 * Lanza \DomainException con un mensaje legible cuando una acción no corresponde.
 */
final class SurgeryWorkflow
{
    // ---------------------------------------------------------------- 1. Cotización

    public static function createQuote(array $data, array $items, array $custom, ?array $prescription): int
    {
        return Database::transaction(function () use ($data, $items, $custom, $prescription): int {
            $id = Database::insert('surgeries', [
                ...$data,
                'stage' => SurgeryStage::Cotizacion->value,
                'status' => 'activa',
                'custom_data' => json_encode($custom, JSON_UNESCAPED_UNICODE),
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            self::saveItems($id, $items);
            if ($prescription) {
                self::attach($id, 'receta', $prescription);
            }
            Surgery::log($id, 'Cotización creada');
            Audit::log('create', 'surgeries', $id);
            return $id;
        });
    }

    public static function updateQuote(array $s, array $data, array $items, array $custom, ?array $prescription): void
    {
        if (!SurgeryStage::from($s['stage'])->isBefore(SurgeryStage::Cobranza)) {
            throw new \DomainException('La cotización ya no puede modificarse: la cirugía está facturada.');
        }
        Database::transaction(function () use ($s, $data, $items, $custom, $prescription): void {
            $id = (int) $s['id'];
            Database::update('surgeries', [
                ...$data,
                'custom_data' => json_encode($custom, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ], $id);
            Database::query('DELETE FROM quote_items WHERE surgery_id = ?', [$id]);
            self::saveItems($id, $items);
            if ($prescription) {
                self::attach($id, 'receta', $prescription);
            }
            Surgery::log($id, 'Cotización modificada');
            Audit::log('update', 'surgeries', $id, 'cotización');
        });

        // Aviso al vendedor cuando otra persona cambia su cotización.
        $sellerId = (int) ($data['seller_id'] ?? $s['seller_id']);
        if ($sellerId && $sellerId !== Auth::id()) {
            $fresh = Surgery::find((int) $s['id']);
            Notifier::send('quote.updated', [Notifier::user($sellerId)], new Message(
                subject: 'Cambios en la cotización ' . surgery_code($s['id']),
                intro: Auth::user()['name'] . ' modificó la cotización de una de tus cirugías.',
                details: self::summary($fresh),
                actionUrl: absolute_url('admin/cirugias/' . $s['id']),
                actionLabel: 'Ver cirugía',
            ), (int) $s['id']);
        }
    }

    public static function sendToAuthorization(array $s): void
    {
        self::requireStage($s, SurgeryStage::Cotizacion);
        if (!Database::value('SELECT COUNT(*) FROM quote_items WHERE surgery_id = ?', [$s['id']])) {
            throw new \DomainException('Cargá al menos un ítem de material cotizado.');
        }
        self::moveTo($s, SurgeryStage::Autorizacion, 'Enviada a autorización');
    }

    // ---------------------------------------------------------------- 2. Autorización

    public static function authorize(array $s, string $type, ?string $ocNumber, ?array $ocFile): void
    {
        self::requireStage($s, SurgeryStage::Autorizacion);
        Database::transaction(function () use ($s, $type, $ocNumber, $ocFile): void {
            Database::update('surgeries', [
                'auth_type' => $type,
                'oc_number' => $type === 'oc' ? $ocNumber : null,
                'authorized_at' => now(),
                'authorized_by' => Auth::id(),
            ], (int) $s['id']);
            if ($ocFile) {
                self::attach((int) $s['id'], 'oc', $ocFile);
            }
            $detail = $type === 'particular' ? 'Paciente particular (sin OC)' : 'OC ' . ($ocNumber ?: 's/n');
            self::moveTo($s, SurgeryStage::Asignacion, 'Autorizada', $detail);
        });

        $fresh = Surgery::find((int) $s['id']);
        Notifier::send('surgery.authorized', Notifier::usersWithPermission('logistics.manage'), new Message(
            subject: 'Cirugía autorizada para asignar: ' . surgery_code($s['id']),
            intro: 'La cirugía fue autorizada y está lista para asignar material, caja, instrumentadora y chofer.',
            details: self::summary($fresh),
            actionUrl: absolute_url('admin/cirugias/' . $s['id']),
            actionLabel: 'Asignar',
        ), (int) $s['id']);
    }

    // ---------------------------------------------------------------- 3. Asignación

    public static function assign(array $s, array $data): void
    {
        self::requireStage($s, SurgeryStage::Asignacion);
        if ($s['preparing_at']) {
            throw new \DomainException('El material ya está en preparación; no se puede cambiar la asignación.');
        }
        $id = (int) $s['id'];
        $newBox = (int) $data['box_id'];
        $oldBox = (int) ($s['box_id'] ?? 0);
        $firstAssignment = !$s['assigned_at'];
        $dateChanged = $s['scheduled_at'] !== $data['scheduled_at'];

        Database::transaction(function () use ($s, $data, $id, $newBox, $oldBox, $firstAssignment, $dateChanged): void {
            if ($newBox !== $oldBox) {
                $box = Database::fetch('SELECT * FROM boxes WHERE id = ? AND active = 1', [$newBox]);
                if (!$box || $box['status'] !== BoxStatus::Disponible->value) {
                    throw new \DomainException('La caja elegida no está disponible.');
                }
                if ($oldBox) {
                    BoxService::setStatus($oldBox, BoxStatus::Disponible, $id, 'Liberada por cambio de caja');
                }
                BoxService::setStatus($newBox, BoxStatus::Asignada, $id);
            }
            Database::update('surgeries', [
                'scheduled_at' => $data['scheduled_at'],
                'instrumentist_id' => $data['instrumentist_id'],
                'driver_id' => $data['driver_id'],
                'box_id' => $newBox,
                'logistics_notes' => $data['logistics_notes'],
                'assigned_at' => $s['assigned_at'] ?: now(),
                // Si cambia la fecha, Cx debe volver a confirmar.
                'cx_confirmed_at' => $dateChanged ? null : $s['cx_confirmed_at'],
                'cx_confirmed_by' => $dateChanged ? null : $s['cx_confirmed_by'],
                'updated_at' => now(),
            ], $id);
            Surgery::log($id, $firstAssignment ? 'Material y logística asignados' : 'Asignación modificada', 'Fecha: ' . fdatetime($data['scheduled_at']));
        });

        if ($firstAssignment || $dateChanged) {
            $fresh = Surgery::find($id);
            Notifier::send('surgery.assigned', Notifier::usersWithPermission('cx.confirm'), new Message(
                subject: ($firstAssignment ? 'Nueva cirugía: ' : 'Cambio de fecha: ') . surgery_code($id) . ' · ' . fdatetime($fresh['scheduled_at']),
                intro: $firstAssignment
                    ? 'Llegó una cirugía al módulo de Cx. Confirmá fecha y horario.'
                    : 'Se modificó la fecha de la cirugía. Confirmá nuevamente fecha y horario.',
                details: self::summary($fresh, withMaterial: true),
                actionUrl: absolute_url('admin/cirugias/' . $id),
                actionLabel: 'Confirmar fecha y horario',
            ), $id);
        }
    }

    public static function confirmCx(array $s, string $scheduledAt): void
    {
        if (!in_array($s['stage'], [SurgeryStage::Asignacion->value, SurgeryStage::Entrega->value], true) || !$s['assigned_at']) {
            throw new \DomainException('La cirugía todavía no tiene asignación para confirmar.');
        }
        Database::update('surgeries', [
            'scheduled_at' => $scheduledAt,
            'cx_confirmed_at' => now(),
            'cx_confirmed_by' => Auth::id(),
            'updated_at' => now(),
        ], (int) $s['id']);
        Surgery::log((int) $s['id'], 'Fecha y horario confirmados por Cx', fdatetime($scheduledAt));
    }

    // ---------------------------------------------------------------- 4. Entrega

    public static function startPreparation(array $s): void
    {
        self::requireStage($s, SurgeryStage::Asignacion);
        if (!$s['box_id'] || !$s['driver_id'] || !$s['scheduled_at']) {
            throw new \DomainException('Asigná caja, chofer y fecha antes de preparar el material.');
        }
        if ($s['preparing_at']) {
            throw new \DomainException('El material ya está en preparación.');
        }
        Database::transaction(function () use ($s): void {
            BoxService::setStatus((int) $s['box_id'], BoxStatus::EnPreparacion, (int) $s['id']);
            Database::update('surgeries', ['preparing_at' => now(), 'updated_at' => now()], (int) $s['id']);
            Surgery::log((int) $s['id'], 'Material en preparación');
        });

        $fresh = Surgery::find((int) $s['id']);
        Notifier::send('box.preparing', [Notifier::user((int) $s['driver_id'])], new Message(
            subject: 'Material en preparación · ' . surgery_code($s['id']) . ' · ' . fdatetime($fresh['scheduled_at']),
            intro: 'Se está preparando el material de una cirugía que tenés asignada.',
            details: self::driverDetails($fresh),
            outro: 'Te avisaremos cuando esté listo para reparto.',
        ), (int) $s['id']);
    }

    public static function dispatch(array $s): void
    {
        self::requireStage($s, SurgeryStage::Asignacion);
        if (!$s['preparing_at']) {
            throw new \DomainException('Primero marcá el material como "en preparación".');
        }
        if (!$s['cx_confirmed_at']) {
            throw new \DomainException('El equipo de Cx todavía no confirmó fecha y horario.');
        }

        $token = bin2hex(random_bytes(32));
        Database::transaction(function () use ($s, $token): void {
            BoxService::setStatus((int) $s['box_id'], BoxStatus::EnReparto, (int) $s['id']);
            Database::update('surgeries', ['dispatched_at' => now()], (int) $s['id']);
            Database::query('UPDATE delivery_tokens SET used_at = ? WHERE surgery_id = ? AND used_at IS NULL', [now(), $s['id']]);
            Database::insert('delivery_tokens', [
                'surgery_id' => (int) $s['id'],
                'driver_id' => (int) $s['driver_id'],
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+' . (int) env('DELIVERY_LINK_DAYS', 5) . ' days')),
                'created_at' => now(),
            ]);
            self::moveTo($s, SurgeryStage::Entrega, 'Listo para reparto');
        });

        $fresh = Surgery::find((int) $s['id']);
        Notifier::send('box.ready', [Notifier::user((int) $s['driver_id'])], new Message(
            subject: 'Listo para reparto · ' . surgery_code($s['id']) . ' · ' . $fresh['institution_name'],
            intro: 'El material está listo para reparto. Cuando lo entregues, confirmalo con el botón.',
            details: self::driverDetails($fresh),
            actionUrl: absolute_url('entrega/' . $token),
            actionLabel: 'Confirmar material entregado',
            outro: 'También podés confirmarlo desde el panel, en "Mis entregas".',
        ), (int) $s['id']);
    }

    public static function confirmDelivery(array $s, string $via = 'panel'): void
    {
        self::requireStage($s, SurgeryStage::Entrega);
        Database::transaction(function () use ($s, $via): void {
            BoxService::setStatus((int) $s['box_id'], BoxStatus::Entregada, (int) $s['id']);
            Database::update('surgeries', [
                'delivered_at' => now(),
                'delivered_by' => Auth::id() ?? (int) $s['driver_id'],
            ], (int) $s['id']);
            Database::query('UPDATE delivery_tokens SET used_at = ? WHERE surgery_id = ? AND used_at IS NULL', [now(), $s['id']]);
            self::moveTo($s, SurgeryStage::Cirugia, 'Material entregado', $via === 'email' ? 'Confirmado desde el correo' : 'Confirmado desde el panel');
        });

        $fresh = Surgery::find((int) $s['id']);
        Notifier::send('delivery.confirmed', [Notifier::user((int) $s['seller_id'])], new Message(
            subject: 'Material entregado · ' . surgery_code($s['id']),
            intro: 'Se confirmó la entrega del material de tu cirugía.',
            details: self::summary($fresh),
            actionUrl: absolute_url('admin/cirugias/' . $s['id']),
            actionLabel: 'Ver cirugía',
        ), (int) $s['id']);
    }

    // ---------------------------------------------------------------- 5. Cirugía realizada

    public static function report(array $s, string $performedAt, ?string $observations, ?array $pdf): void
    {
        self::requireStage($s, SurgeryStage::Cirugia);
        $hasReport = (bool) Database::value("SELECT COUNT(*) FROM attachments WHERE surgery_id = ? AND kind = 'informe'", [$s['id']]);
        if (!$pdf && !$hasReport) {
            throw new \DomainException('Adjuntá el PDF con la información de la cirugía.');
        }
        Database::transaction(function () use ($s, $performedAt, $observations, $pdf): void {
            if ($pdf) {
                self::attach((int) $s['id'], 'informe', $pdf, ['application/pdf' => 'pdf']);
            }
            Database::update('surgeries', ['performed_at' => $performedAt, 'observations' => $observations], (int) $s['id']);
            self::moveTo($s, SurgeryStage::Consumo, 'Cirugía realizada');
        });
    }

    // ---------------------------------------------------------------- 6. Consumo

    /**
     * @param array<int, array{material_id:int, source:string, used:int, missing:int, replenish:bool}> $rows
     */
    public static function consumption(array $s, array $rows, BoxStatus $boxFinal): void
    {
        self::requireStage($s, SurgeryStage::Consumo);
        if (!in_array($boxFinal, [BoxStatus::Usada, BoxStatus::ParaEsterilizar], true)) {
            throw new \DomainException('Indicá si la caja queda usada o para esterilizar.');
        }
        $id = (int) $s['id'];
        $boxId = (int) $s['box_id'];

        Database::transaction(function () use ($s, $rows, $boxFinal, $id, $boxId): void {
            foreach ($rows as $row) {
                $qty = $row['used'] + $row['missing'];
                if ($qty <= 0) {
                    continue;
                }
                if ($row['source'] === 'caja') {
                    if (!$boxId) {
                        throw new \DomainException('La cirugía no tiene caja asignada; cargá el consumo como stock suelto.');
                    }
                    BoxService::moveBoxItem($boxId, $row['material_id'], -$qty);
                    BoxService::moveStock($row['material_id'], -$row['used'], 'consumo_caja', 'Consumo ' . surgery_code($id), $boxId, $id, affectsLoose: false);
                    if ($row['missing'] > 0) {
                        BoxService::moveStock($row['material_id'], -$row['missing'], 'faltante_caja', 'Faltante al regreso ' . surgery_code($id), $boxId, $id, affectsLoose: false);
                    }
                    if ($row['replenish']) {
                        BoxService::moveStock($row['material_id'], -$qty, 'reposicion_caja', 'Reposición tras ' . surgery_code($id), $boxId, $id);
                        BoxService::moveBoxItem($boxId, $row['material_id'], $qty);
                    }
                } else {
                    BoxService::moveStock($row['material_id'], -$qty, 'consumo_stock', 'Consumo ' . surgery_code($id), null, $id);
                }
                Database::insert('consumption_items', [
                    'surgery_id' => $id,
                    'material_id' => $row['material_id'],
                    'source' => $row['source'],
                    'used_qty' => $row['used'],
                    'missing_qty' => $row['missing'],
                    'replenished' => $row['replenish'] ? 1 : 0,
                ]);
            }
            if ($boxId) {
                BoxService::setStatus($boxId, $boxFinal, $id, 'Regreso de cirugía');
            }
            Database::update('surgeries', ['consumption_at' => now(), 'box_final_status' => $boxFinal->value], $id);
            self::moveTo($s, SurgeryStage::Facturacion, 'Consumo cargado', 'Caja: ' . $boxFinal->label());
        });
    }

    // ---------------------------------------------------------------- 7. Facturación

    public static function billing(array $s, array $data, bool $emit, ?array $invoiceFile): void
    {
        $stage = SurgeryStage::from($s['stage']);
        if ($stage->isBefore(SurgeryStage::Facturacion)) {
            throw new \DomainException('La cirugía todavía no llegó a facturación.');
        }
        Database::transaction(function () use ($s, $data, $emit, $invoiceFile, $stage): void {
            Database::update('surgeries', [...$data, 'updated_at' => now()], (int) $s['id']);
            if ($invoiceFile) {
                self::attach((int) $s['id'], 'factura', $invoiceFile);
            }
            if ($emit && $stage === SurgeryStage::Facturacion) {
                if (empty($data['invoice_number']) || empty($data['invoice_date']) || $data['invoice_amount'] === null) {
                    throw new \DomainException('Para emitir la factura completá número, fecha y monto.');
                }
                Database::update('surgeries', ['invoiced_at' => now()], (int) $s['id']);
                self::moveTo($s, SurgeryStage::Cobranza, 'Factura emitida', 'Factura ' . $data['invoice_number']);
            } else {
                Surgery::log((int) $s['id'], 'Datos de facturación actualizados',
                    'Honorario liquidado: ' . ($data['fee_liquidated'] ? 'sí' : 'no') . ' · Cobrado: ' . ($data['collected'] ? 'sí' : 'no'));
            }
        });
    }

    public static function addSupplierInvoice(array $s, array $data, ?array $file): void
    {
        Database::transaction(function () use ($s, $data, $file): void {
            $attachmentId = $file ? self::attach((int) $s['id'], 'proveedor', $file) : null;
            Database::insert('supplier_invoices', [
                ...$data,
                'surgery_id' => (int) $s['id'],
                'attachment_id' => $attachmentId,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);
            Surgery::log((int) $s['id'], 'Factura de proveedor ingresada', $data['invoice_number']);
        });
    }

    // ---------------------------------------------------------------- 8. Cobranza

    public static function collect(array $s, array $data): void
    {
        self::requireStage($s, SurgeryStage::Cobranza);
        Database::transaction(function () use ($s, $data): void {
            Database::update('surgeries', [...$data, 'collected' => 1, 'collected_at' => now()], (int) $s['id']);
            self::moveTo($s, SurgeryStage::Finalizada, 'Cobranza registrada',
                (Surgery::PAYMENT_TYPES[$data['payment_type']] ?? $data['payment_type']) . ' · ' . fdate($data['payment_date']));
        });
    }

    // ---------------------------------------------------------------- Suspensión / reprogramación

    public static function suspend(array $s, string $reason): void
    {
        if ($s['status'] === 'suspendida') {
            throw new \DomainException('La cirugía ya está suspendida.');
        }
        if (!SurgeryStage::from($s['stage'])->isBefore(SurgeryStage::Consumo)) {
            throw new \DomainException('No se puede suspender una cirugía que ya tiene informe o consumo cargado.');
        }
        Database::transaction(function () use ($s, $reason): void {
            if ($s['box_id']) {
                // La caja vuelve al stock, sin consumo.
                BoxService::setStatus((int) $s['box_id'], BoxStatus::Disponible, (int) $s['id'], 'Cirugía suspendida: la caja vuelve al stock');
            }
            Database::query('UPDATE delivery_tokens SET used_at = ? WHERE surgery_id = ? AND used_at IS NULL', [now(), $s['id']]);
            Database::update('surgeries', [
                'status' => 'suspendida',
                'suspended_at' => now(),
                'suspension_reason' => $reason,
                'box_id' => null,
                'updated_at' => now(),
            ], (int) $s['id']);
            Surgery::log((int) $s['id'], 'Cirugía suspendida', $reason);
        });

        $recipients = [...Notifier::usersWithPermission('cx.confirm'), Notifier::user((int) $s['seller_id']), Notifier::user((int) $s['driver_id'])];
        Notifier::send('surgery.suspended', array_filter($recipients), new Message(
            subject: 'Cirugía suspendida · ' . surgery_code($s['id']),
            intro: 'La cirugía fue suspendida. La caja vuelve al stock.',
            details: [...self::summary($s), 'Motivo' => $reason],
        ), (int) $s['id']);
    }

    public static function reprogram(array $s, string $scheduledAt): void
    {
        if ($s['status'] !== 'suspendida') {
            throw new \DomainException('Solo se reprograman cirugías suspendidas. Para cambiar la fecha usá la asignación.');
        }
        // Sale ya cotizada y autorizada: vuelve a asignación conservando cotización y OC.
        $stage = $s['authorized_at'] ? SurgeryStage::Asignacion : SurgeryStage::from($s['stage']);
        Database::update('surgeries', [
            'status' => 'activa',
            'stage' => $stage->value,
            'scheduled_at' => $scheduledAt,
            'assigned_at' => null,
            'cx_confirmed_at' => null,
            'cx_confirmed_by' => null,
            'preparing_at' => null,
            'dispatched_at' => null,
            'delivered_at' => null,
            'delivered_by' => null,
            'reprogram_count' => (int) $s['reprogram_count'] + 1,
            'updated_at' => now(),
        ], (int) $s['id']);
        Surgery::log((int) $s['id'], 'Cirugía reprogramada', 'Nueva fecha: ' . fdatetime($scheduledAt));
        Audit::log('reprogram', 'surgeries', (int) $s['id']);
    }

    // ---------------------------------------------------------------- Auxiliares

    public static function attach(int $surgeryId, string $kind, array $file, array $allowed = Upload::ALLOWED): int
    {
        $stored = Upload::store($file, $allowed);
        return Database::insert('attachments', [
            ...$stored,
            'surgery_id' => $surgeryId,
            'kind' => $kind,
            'uploaded_by' => Auth::id(),
            'created_at' => now(),
        ]);
    }

    private static function saveItems(int $surgeryId, array $items): void
    {
        foreach (array_values($items) as $i => $item) {
            Database::insert('quote_items', [
                'surgery_id' => $surgeryId,
                'material_id' => $item['material_id'] ?: null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $i,
            ]);
        }
    }

    private static function requireStage(array $s, SurgeryStage $stage): void
    {
        if ($s['status'] === 'suspendida') {
            throw new \DomainException('La cirugía está suspendida. Reprogramala para continuar.');
        }
        if ($s['stage'] !== $stage->value) {
            throw new \DomainException('Esta acción corresponde al estadio "' . $stage->label() . '" y la cirugía está en "' . SurgeryStage::from($s['stage'])->label() . '".');
        }
    }

    private static function moveTo(array $s, SurgeryStage $to, string $event, ?string $detail = null): void
    {
        Database::update('surgeries', ['stage' => $to->value, 'updated_at' => now()], (int) $s['id']);
        Surgery::log((int) $s['id'], $event, $detail);
        Audit::log('stage:' . $to->value, 'surgeries', (int) $s['id']);
    }

    private static function summary(array $s, bool $withMaterial = false): array
    {
        $out = [
            'Cirugía' => surgery_code($s['id']) . ($s['procedure_name'] ? ' · ' . $s['procedure_name'] : ''),
            'Fecha' => fdatetime($s['scheduled_at']),
            'Institución' => (string) $s['institution_name'],
            'Cliente' => (string) $s['client_name'],
            'Médico' => person_name($s, 'doctor_'),
            'Paciente' => person_name($s, 'patient_'),
            'Vendedor' => (string) ($s['seller_name'] ?? '—'),
        ];
        if ($withMaterial) {
            $items = array_map(fn ($i) => $i['quantity'] . ' × ' . $i['description'], Surgery::quoteItems((int) $s['id']));
            $out['Material aprobado'] = $items ? implode('; ', $items) : '—';
            $out['Caja'] = $s['box_code'] ? $s['box_code'] . ' · ' . $s['box_name'] : '—';
            $out['Instrumentadora'] = person_name($s, 'instrumentist_');
        }
        return $out;
    }

    private static function driverDetails(array $s): array
    {
        return [
            'Cirugía' => surgery_code($s['id']),
            'Fecha y hora' => fdatetime($s['scheduled_at']),
            'Institución' => $s['institution_name'],
            'Dirección' => trim(($s['institution_address'] ?? '') . ', ' . ($s['institution_city'] ?? ''), ', ') ?: '—',
            'Médico' => person_name($s, 'doctor_'),
            'Caja' => $s['box_code'] ? $s['box_code'] . ' · ' . $s['box_name'] : '—',
            'Indicaciones' => $s['logistics_notes'] ?: '—',
        ];
    }
}
