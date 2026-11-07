<?php

declare(strict_types=1);

use App\Controllers\Admin\AuditController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\BoxController;
use App\Controllers\Admin\ConsignmentController;
use App\Controllers\Admin\CatalogController;
use App\Controllers\Admin\CustomFieldController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DeliveryController;
use App\Controllers\Admin\FinanceController;
use App\Controllers\Admin\MaterialController;
use App\Controllers\Admin\MessageController;
use App\Controllers\Admin\NotificationController;
use App\Controllers\Admin\ProfileController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\SurgeryController;
use App\Controllers\Admin\UserController;
use App\Controllers\Site\SiteController;
use App\Core\Router;

return static function (Router $r): void {
    // ---------------- Sitio institucional
    $r->get('/', [SiteController::class, 'home']);
    $r->get('/productos', [SiteController::class, 'products']);
    $r->get('/nosotros', [SiteController::class, 'about']);
    $r->get('/contacto', [SiteController::class, 'contact']);
    $r->post('/contacto', [SiteController::class, 'sendContact']);

    // Confirmación de entrega desde el correo del chofer (enlace firmado, sin login).
    $r->get('/entrega/{token}', [DeliveryController::class, 'tokenForm']);
    $r->post('/entrega/{token}', [DeliveryController::class, 'tokenConfirm']);

    // ---------------- Panel de administración
    $r->get('/admin/login', [AuthController::class, 'loginForm'], 'guest');
    $r->post('/admin/login', [AuthController::class, 'login'], 'guest');
    $r->post('/admin/logout', [AuthController::class, 'logout']);

    $r->group('/admin', ['auth'], static function (Router $r): void {
        $r->get('', [DashboardController::class, 'index']);

        // Cirugías
        $r->get('/cirugias', [SurgeryController::class, 'index'], 'can:surgeries.view_all|surgeries.view_own');
        $r->get('/cirugias/nueva', [SurgeryController::class, 'create'], 'can:quote.edit');
        $r->post('/cirugias', [SurgeryController::class, 'store'], 'can:quote.edit');
        $r->get('/cirugias/{id}', [SurgeryController::class, 'show']);
        $r->get('/cirugias/{id}/cotizacion', [SurgeryController::class, 'edit'], 'can:quote.edit');
        $r->post('/cirugias/{id}/cotizacion', [SurgeryController::class, 'update'], 'can:quote.edit');
        $r->post('/cirugias/{id}/enviar-autorizacion', [SurgeryController::class, 'sendToAuthorization'], 'can:quote.edit');
        $r->post('/cirugias/{id}/autorizacion', [SurgeryController::class, 'registerAuthorization'], 'can:quote.edit');
        $r->post('/cirugias/{id}/asignacion', [SurgeryController::class, 'assign'], 'can:logistics.manage');
        $r->post('/cirugias/{id}/confirmar-cx', [SurgeryController::class, 'confirmCx'], 'can:cx.confirm');
        $r->post('/cirugias/{id}/preparacion', [SurgeryController::class, 'prepare'], 'can:logistics.manage');
        $r->post('/cirugias/{id}/reparto', [SurgeryController::class, 'dispatch'], 'can:logistics.manage');
        $r->post('/cirugias/{id}/entrega', [SurgeryController::class, 'deliver'], 'can:delivery.confirm|logistics.manage');
        $r->post('/cirugias/{id}/informe', [SurgeryController::class, 'report'], 'can:cx.post');
        $r->post('/cirugias/{id}/consumo', [SurgeryController::class, 'consumption'], 'can:cx.post');
        $r->post('/cirugias/{id}/facturacion', [SurgeryController::class, 'billing'], 'can:billing.edit');
        $r->post('/cirugias/{id}/proveedores', [SurgeryController::class, 'addSupplierInvoice'], 'can:billing.edit');
        $r->post('/cirugias/{id}/proveedores/{invoice}/pagada', [SurgeryController::class, 'toggleSupplierPaid'], 'can:billing.edit');
        $r->post('/cirugias/{id}/proveedores/{invoice}/eliminar', [SurgeryController::class, 'deleteSupplierInvoice'], 'can:billing.edit');
        $r->post('/cirugias/{id}/cobranza', [SurgeryController::class, 'collect'], 'can:collection.edit');
        $r->post('/cirugias/{id}/suspender', [SurgeryController::class, 'suspend'], 'can:surgery.suspend');
        $r->post('/cirugias/{id}/reprogramar', [SurgeryController::class, 'reprogram'], 'can:surgery.suspend');
        $r->get('/adjuntos/{id}', [SurgeryController::class, 'download']);

        // Áreas
        $r->get('/agenda', [SurgeryController::class, 'agenda'], 'can:surgeries.view_all');
        $r->get('/entregas', [DeliveryController::class, 'index'], 'can:deliveries.view_own|logistics.manage');
        $r->get('/facturacion', [FinanceController::class, 'billing'], 'can:billing.view');
        $r->get('/cobranzas', [FinanceController::class, 'collections'], 'can:collection.view');

        // Stock
        $r->get('/materiales', [MaterialController::class, 'index'], 'can:stock.view|stock.manage');
        $r->get('/materiales/nuevo', [MaterialController::class, 'create'], 'can:stock.manage');
        $r->post('/materiales', [MaterialController::class, 'store'], 'can:stock.manage');
        $r->get('/materiales/{id}', [MaterialController::class, 'show'], 'can:stock.view|stock.manage');
        $r->get('/materiales/{id}/editar', [MaterialController::class, 'edit'], 'can:stock.manage');
        $r->post('/materiales/{id}', [MaterialController::class, 'update'], 'can:stock.manage');
        $r->post('/materiales/{id}/movimiento', [MaterialController::class, 'movement'], 'can:stock.manage');
        $r->post('/materiales/{id}/eliminar', [MaterialController::class, 'destroy'], 'can:stock.manage');

        $r->get('/consignaciones', [ConsignmentController::class, 'index'], 'can:consignment.view|consignment.manage');
        $r->get('/consignaciones/abrir', [ConsignmentController::class, 'open'], 'can:consignment.manage');
        $r->get('/consignaciones/{id}', [ConsignmentController::class, 'show'], 'can:consignment.view|consignment.manage');
        $r->post('/consignaciones/{id}/movimiento', [ConsignmentController::class, 'movement'], 'can:consignment.manage');
        $r->post('/consignaciones/{id}/facturar', [ConsignmentController::class, 'bill'], 'can:consignment.manage|billing.edit');

        $r->get('/cajas', [BoxController::class, 'index'], 'can:boxes.states.view|stock.view|stock.manage');
        $r->get('/cajas/nueva', [BoxController::class, 'create'], 'can:stock.manage');
        $r->post('/cajas', [BoxController::class, 'store'], 'can:stock.manage');
        $r->get('/cajas/{id}', [BoxController::class, 'show'], 'can:boxes.states.view|stock.view|stock.manage');
        $r->get('/cajas/{id}/editar', [BoxController::class, 'edit'], 'can:stock.manage');
        $r->post('/cajas/{id}', [BoxController::class, 'update'], 'can:stock.manage');
        $r->post('/cajas/{id}/items', [BoxController::class, 'saveItem'], 'can:stock.manage');
        $r->post('/cajas/{id}/items/{item}/eliminar', [BoxController::class, 'deleteItem'], 'can:stock.manage');
        $r->post('/cajas/{id}/reponer', [BoxController::class, 'refill'], 'can:stock.manage');
        $r->post('/cajas/{id}/disponible', [BoxController::class, 'markAvailable'], 'can:stock.manage');
        $r->post('/cajas/{id}/eliminar', [BoxController::class, 'destroy'], 'can:stock.manage');

        // Maestros (ABM genérico)
        $r->get('/maestros/{catalog}', [CatalogController::class, 'index'], 'can:catalogs.manage');
        $r->get('/maestros/{catalog}/nuevo', [CatalogController::class, 'create'], 'can:catalogs.manage');
        $r->post('/maestros/{catalog}', [CatalogController::class, 'store'], 'can:catalogs.manage');
        $r->get('/maestros/{catalog}/{id}/editar', [CatalogController::class, 'edit'], 'can:catalogs.manage');
        $r->post('/maestros/{catalog}/{id}', [CatalogController::class, 'update'], 'can:catalogs.manage');
        $r->post('/maestros/{catalog}/{id}/eliminar', [CatalogController::class, 'destroy'], 'can:catalogs.manage');

        // Configuración
        $r->get('/campos', [CustomFieldController::class, 'index'], 'can:custom_fields.manage');
        $r->get('/campos/nuevo', [CustomFieldController::class, 'create'], 'can:custom_fields.manage');
        $r->post('/campos', [CustomFieldController::class, 'store'], 'can:custom_fields.manage');
        $r->get('/campos/{id}/editar', [CustomFieldController::class, 'edit'], 'can:custom_fields.manage');
        $r->post('/campos/{id}', [CustomFieldController::class, 'update'], 'can:custom_fields.manage');
        $r->post('/campos/{id}/eliminar', [CustomFieldController::class, 'destroy'], 'can:custom_fields.manage');

        $r->get('/usuarios', [UserController::class, 'index'], 'can:users.manage');
        $r->get('/usuarios/nuevo', [UserController::class, 'create'], 'can:users.manage');
        $r->post('/usuarios', [UserController::class, 'store'], 'can:users.manage');
        $r->get('/usuarios/{id}/editar', [UserController::class, 'edit'], 'can:users.manage');
        $r->post('/usuarios/{id}', [UserController::class, 'update'], 'can:users.manage');
        $r->post('/usuarios/{id}/eliminar', [UserController::class, 'destroy'], 'can:users.manage');

        $r->get('/roles', [RoleController::class, 'index'], 'can:roles.manage');
        $r->get('/roles/nuevo', [RoleController::class, 'create'], 'can:roles.manage');
        $r->post('/roles', [RoleController::class, 'store'], 'can:roles.manage');
        $r->get('/roles/{id}/editar', [RoleController::class, 'edit'], 'can:roles.manage');
        $r->post('/roles/{id}', [RoleController::class, 'update'], 'can:roles.manage');
        $r->post('/roles/{id}/eliminar', [RoleController::class, 'destroy'], 'can:roles.manage');

        $r->get('/avisos', [NotificationController::class, 'index'], 'can:notifications.view');
        $r->post('/avisos/{id}/reintentar', [NotificationController::class, 'retry'], 'can:notifications.view');
        $r->get('/mensajes', [MessageController::class, 'index'], 'can:messages.view');
        $r->post('/mensajes/{id}/leido', [MessageController::class, 'markRead'], 'can:messages.view');
        $r->get('/auditoria', [AuditController::class, 'index'], 'can:audit.view');

        $r->get('/perfil', [ProfileController::class, 'edit']);
        $r->post('/perfil', [ProfileController::class, 'update']);
    });
};
