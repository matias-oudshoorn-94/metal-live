# Metal Live

Sitio institucional y sistema de gestión de cirugías traumatológicas (panel `/admin`).
PHP 8.4+ sin frameworks, MVC propio, HTML/CSS/JS nativos, sin dependencias externas.

## Puesta en marcha (desarrollo)

```bash
cp .env.example .env                       # completar APP_KEY y ADMIN_PASSWORD
php bin/migrate.php --demo                 # crea la base SQLite, roles, admin y datos de ejemplo
php -S localhost:8000 -t public public/index.php
```

- Sitio: http://localhost:8000
- Panel: http://localhost:8000/admin
- Usuarios demo (contraseña `Demo12345!`): `cotizacion@`, `cx@`, `facturacion@`, `cobranzas@`, `vendedor@`, `vendedora@`, `chofer@demo.local`.
- Con `MAIL_DRIVER=log`, los correos se guardan como `.eml` en `storage/mail/`.

`php bin/migrate.php --fresh --demo` borra todo y vuelve a empezar.
`php bin/migrate.php` sin opciones crea las tablas nuevas que falten sin tocar los datos existentes (usarlo al actualizar).

## Producción

- Si el hosting no deja cambiar el document root (p. ej. Hostinger con `public_html`), subir el proyecto completo dentro de `public_html`: el `.htaccess` de la raíz manda todo a `public/` y deja inaccesible el resto.
- Apuntar el document root del servidor a `public/` (incluye `.htaccess` para Apache; en Nginx usar `try_files $uri /index.php?$query_string`).
- `APP_DEBUG=false`, `APP_KEY` aleatoria, HTTPS (las cookies pasan a `Secure` automáticamente).
- MySQL/MariaDB: `DB_CONNECTION=mysql` y credenciales; luego `php bin/migrate.php`.
- Extensiones: `pdo_sqlite` o `pdo_mysql`, `fileinfo`, `curl` (WhatsApp), `mbstring` recomendada.
- `storage/` debe ser escribible por PHP y nunca servirse públicamente.
- Avisos en segundo plano: `NOTIFY_QUEUE=true` y cron `* * * * * php bin/notify.php`.

### Importar la base en MySQL / MariaDB (phpMyAdmin)

`database/metal_live.sql` trae la base completa (estructura y datos) lista para importar:

```bash
mysql -u usuario -p -e "CREATE DATABASE metal_live CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u usuario -p metal_live < database/metal_live.sql
```

Para regenerarlo con los datos actuales: `php bin/export-sql.php` (`--structure-only` para exportar solo las tablas, `--output=archivo.sql` para otro destino). El archivo borra y vuelve a crear las tablas: importarlo sobre una base con datos los reemplaza.

## Estructura

```
public/            front controller, assets (css, js, img)
config/routes.php  todas las rutas
app/Core/          router, request, sesión, CSRF, auth, validador, uploads, DB (PDO)
app/Controllers/   Site/ (institucional) y Admin/ (panel)
app/Models/        Surgery (consultas y alcance por rol), Catalog (ABM declarativo)
app/Services/      SurgeryWorkflow (8 estadios), BoxService (cajas y stock), ConsignmentService,
                   Permissions, CustomFields, Audit, Notifications/ (motor de avisos)
app/Views/         layouts, sitio, panel, correo
database/schema.sql
bin/               migrate.php, notify.php, export-sql.php
```

## Funcionalidad del panel

- **Recorrido de la cirugía** (`SurgeryWorkflow`): cotización → autorización (OC opcional) → asignación (caja, instrumentadora, chofer, fecha; Cx confirma) → entrega (preparación, reparto, confirmación del chofer por panel o enlace firmado del correo) → informe PDF y observaciones → consumo y faltantes (reposición desde stock) → facturación (factura, honorario liquidado, cobrado, facturas de proveedores) → cobranza (tipo de pago y cheque).
- **Suspensión y reprogramación**: la caja vuelve al stock sin consumo; al reprogramar conserva cotización y autorización.
- **Stock**: cajas con código, contenido, estadios e historial de cirugías; materiales con stock suelto, mínimos y movimientos.
- **Stock en consignación** (`/admin/consignaciones`): material en poder de clientes. Envíos (descuentan del depósito), devoluciones, consumos informados por el cliente (quedan pendientes de facturar hasta cargar el número de factura) y ajustes por recuento.
- **Roles y accesos editables**: matriz de permisos por rol (`/admin/roles`). Los roles iniciales siguen la tabla de accesos del documento.
- **Campos de cotización configurables** (`/admin/campos`).
- **Motor único de avisos**: canales intercambiables (correo SMTP y WhatsApp Cloud API), cola con reintentos y registro en `/admin/avisos`.
- ABM de pacientes, médicos, instituciones, clientes, obras sociales, instrumentadoras, proveedores, materiales, categorías de materiales, cajas y usuarios.
- **Agenda** (`/admin/agenda`): listado de los próximos 30 días y, debajo, calendario mensual con todas las cirugías (navegable por mes; en el celular se ve como lista de días). Auditoría y mensajes del formulario de contacto.

## Seguridad

- Sentencias preparadas reales (PDO sin emulación); identificadores SQL validados.
- Contraseñas con Argon2id y rehash automático; límite de intentos de login por correo e IP; tiempo de respuesta igualado.
- Sesión con `strict_mode`, `HttpOnly`, `SameSite`, regeneración al ingresar, expiración por inactividad e invalidación al cambiar la contraseña.
- CSRF en todos los POST; los GET no modifican datos (también el enlace del correo del chofer).
- CSP con nonce y sin estilos/scripts inline, `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, HSTS bajo HTTPS.
- Archivos fuera de `public/`, nombre aleatorio, tipo real validado con `finfo`, descarga con control de permisos por tipo de adjunto.
- Control de acceso por permiso en cada ruta y por registro (vendedor solo lo suyo, chofer solo sus entregas, cobranzas sin datos de Cx).
- Enlaces de entrega con token aleatorio guardado como hash, vencimiento y uso único.
- Escape de toda salida en las vistas; honeypot y límite por IP en el formulario de contacto.

## WhatsApp

Completar `WHATSAPP_TOKEN` y `WHATSAPP_PHONE_ID` (WhatsApp Business Cloud API). Cada usuario elige en su perfil si recibe avisos por WhatsApp. Meta exige plantillas aprobadas para iniciar conversaciones fuera de la ventana de 24 h; para eso hay que adaptar `WhatsAppChannel::send()` al formato de plantilla.
