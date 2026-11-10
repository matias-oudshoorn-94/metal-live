<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Catálogo de permisos del sistema y accesos iniciales por rol (tabla de accesos del documento).
 * Los permisos de cada rol se editan luego desde /admin/roles.
 */
final class Permissions
{
    public const GROUPS = [
        'General' => [
            'dashboard.view' => 'Ver el panel de inicio',
            'notifications.view' => 'Ver el registro de avisos enviados',
            'messages.view' => 'Ver mensajes del formulario de contacto',
            'audit.view' => 'Ver la auditoría del sistema',
        ],
        'Cirugías: alcance' => [
            'surgeries.view_all' => 'Ver todas las cirugías',
            'surgeries.view_own' => 'Ver solo las cirugías propias (vendedor)',
            'surgery.summary.view' => 'Ver resumen: institución, cliente, médico, paciente, fecha, material y vendedor',
            'surgery.history.view' => 'Ver el historial de movimientos de la cirugía',
            'surgery.suspend' => 'Suspender y reprogramar cirugías',
        ],
        'Cotización y autorización' => [
            'quote.view' => 'Ver datos de cotización',
            'quote.edit' => 'Cargar y editar cotizaciones, receta y OC',
            'quote.amounts.view' => 'Ver montos de cotización',
        ],
        'Cirugías y logística' => [
            'logistics.manage' => 'Asignar material, caja, instrumentadora, chofer y fecha',
            'cx.view' => 'Ver datos de cirugía (informe, consumo, observaciones)',
            'cx.confirm' => 'Confirmar fecha y horario (equipo de Cx)',
            'cx.post' => 'Cargar informe PDF, consumo y observaciones',
            'boxes.states.view' => 'Ver estadios de caja',
        ],
        'Choferes' => [
            'deliveries.view_own' => 'Ver las entregas asignadas',
            'delivery.confirm' => 'Confirmar material entregado',
        ],
        'Facturación' => [
            'billing.view' => 'Ver facturación completa',
            'billing.edit' => 'Emitir factura, marcar honorario liquidado, cobrado y pago a proveedores',
            'billing.amounts.view' => 'Ver montos de facturación',
            'fee.status.view' => 'Ver si el honorario está liquidado (sí/no)',
        ],
        'Cobranzas' => [
            'collection.view' => 'Ver datos de cobranza',
            'collection.edit' => 'Registrar fecha de pago, tipo de pago y cheque',
        ],
        'Stock' => [
            'stock.view' => 'Ver stock de materiales y cajas',
            'stock.manage' => 'Administrar materiales, categorías, cajas e ingresos de stock',
            'consignment.view' => 'Ver stock en consignación en clientes',
            'consignment.manage' => 'Enviar, devolver y registrar consumos de material en consignación',
        ],
        'Administración' => [
            'catalogs.manage' => 'ABM de pacientes, instituciones, médicos, clientes, obras sociales, instrumentadoras y proveedores',
            'custom_fields.manage' => 'Configurar campos de la cotización',
            'users.manage' => 'Administrar usuarios',
            'roles.manage' => 'Administrar roles y permisos',
        ],
    ];

    public static function all(): array
    {
        return array_merge(...array_values(self::GROUPS));
    }

    public static function exists(string $permission): bool
    {
        return isset(self::all()[$permission]);
    }

    /** Roles iniciales según la tabla de accesos del documento. */
    public static function defaultRoles(): array
    {
        return [
            'gerente' => [
                'name' => 'Gerentes',
                'description' => 'Acceso total, incluidos los estadios de caja.',
                'permissions' => array_keys(self::all()),
            ],
            'cotizacion' => [
                'name' => 'Cotización',
                'description' => 'Carga y edita cotizaciones y órdenes de compra.',
                'permissions' => ['dashboard.view', 'surgeries.view_all', 'surgery.summary.view', 'quote.view', 'quote.edit', 'quote.amounts.view', 'catalogs.manage'],
            ],
            'cx' => [
                'name' => 'Equipo de Cx',
                'description' => 'Confirma fecha y horario; carga PDF, consumo y observaciones.',
                'permissions' => ['dashboard.view', 'surgeries.view_all', 'surgery.summary.view', 'cx.view', 'cx.confirm', 'cx.post', 'boxes.states.view', 'stock.view'],
            ],
            'facturacion' => [
                'name' => 'Facturación',
                'description' => 'Emite facturas; marca honorario liquidado, cobrado y pago a proveedores.',
                'permissions' => ['dashboard.view', 'surgeries.view_all', 'surgery.summary.view', 'surgery.history.view', 'quote.view', 'quote.amounts.view', 'cx.view', 'billing.view', 'billing.edit', 'billing.amounts.view', 'fee.status.view', 'consignment.view'],
            ],
            'cobranzas' => [
                'name' => 'Cobranzas',
                'description' => 'Carga fecha de pago, tipo de pago y datos del cheque. No ve datos de Cx.',
                'permissions' => ['dashboard.view', 'collection.view', 'collection.edit', 'billing.amounts.view'],
            ],
            'vendedor' => [
                'name' => 'Vendedores',
                'description' => 'Cargan cotizaciones con la receta del médico. Ven solo sus cirugías.',
                'permissions' => ['dashboard.view', 'surgeries.view_own', 'quote.view', 'quote.edit', 'quote.amounts.view', 'billing.amounts.view', 'fee.status.view'],
            ],
            'chofer' => [
                'name' => 'Choferes',
                'description' => 'Reciben avisos de preparación y reparto; confirman la entrega.',
                'permissions' => ['deliveries.view_own', 'delivery.confirm'],
            ],
        ];
    }
}
