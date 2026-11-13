-- Metal Live · base de datos completa para MySQL 8 / MariaDB 10.6+
-- Generado el 2026-11-12 15:30 desde sqlite (estructura y datos)
-- Importar en una base vacía, por ejemplo:
--   CREATE DATABASE metal_live CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   mysql -u usuario -p metal_live < metal_live.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `delivery_tokens`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `consignment_movements`;
DROP TABLE IF EXISTS `consignment_stock`;
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `box_events`;
DROP TABLE IF EXISTS `surgery_events`;
DROP TABLE IF EXISTS `supplier_invoices`;
DROP TABLE IF EXISTS `attachments`;
DROP TABLE IF EXISTS `consumption_items`;
DROP TABLE IF EXISTS `quote_items`;
DROP TABLE IF EXISTS `surgeries`;
DROP TABLE IF EXISTS `custom_fields`;
DROP TABLE IF EXISTS `box_items`;
DROP TABLE IF EXISTS `boxes`;
DROP TABLE IF EXISTS `materials`;
DROP TABLE IF EXISTS `material_categories`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `instrumentists`;
DROP TABLE IF EXISTS `doctors`;
DROP TABLE IF EXISTS `institutions`;
DROP TABLE IF EXISTS `patients`;
DROP TABLE IF EXISTS `clients`;
DROP TABLE IF EXISTS `health_insurances`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `roles`;

CREATE TABLE roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission VARCHAR(80) NOT NULL,
    PRIMARY KEY (role_id, permission),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    password_hash VARCHAR(255) NOT NULL,
    commission_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
    notify_email TINYINT NOT NULL DEFAULT 1,
    notify_whatsapp TINYINT NOT NULL DEFAULT 0,
    active TINYINT NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    email VARCHAR(190) NOT NULL,
    attempted_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_login_attempts_email ON login_attempts (email, attempted_at);

CREATE INDEX idx_login_attempts_ip ON login_attempts (ip, attempted_at);

CREATE TABLE health_insurances (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    code VARCHAR(40) NULL,
    cuit VARCHAR(20) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    active TINYINT NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clients (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'obra_social',
    cuit VARCHAR(20) NULL,
    address VARCHAR(255) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    contact VARCHAR(120) NULL,
    payment_terms VARCHAR(120) NULL,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patients (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(120) NOT NULL,
    first_name VARCHAR(120) NOT NULL,
    document VARCHAR(30) NULL,
    birth_date DATE NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    health_insurance_id INT UNSIGNED NULL,
    member_number VARCHAR(60) NULL,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (health_insurance_id) REFERENCES health_insurances(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_patients_document ON patients (document);

CREATE TABLE institutions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    cuit VARCHAR(20) NULL,
    address VARCHAR(255) NULL,
    city VARCHAR(120) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    contact VARCHAR(120) NULL,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctors (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(120) NOT NULL,
    first_name VARCHAR(120) NOT NULL,
    license VARCHAR(40) NULL,
    specialty VARCHAR(120) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE instrumentists (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(120) NOT NULL,
    first_name VARCHAR(120) NOT NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    active TINYINT NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suppliers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    cuit VARCHAR(20) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    contact VARCHAR(120) NULL,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE material_categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE materials (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    category_id INT UNSIGNED NULL,
    unit VARCHAR(20) NOT NULL DEFAULT 'u',
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock_qty INTEGER NOT NULL DEFAULT 0,
    min_stock INTEGER NOT NULL DEFAULT 0,
    active TINYINT NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (category_id) REFERENCES material_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE boxes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'disponible',
    current_surgery_id INT UNSIGNED NULL,
    active TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_boxes_status ON boxes (status);

CREATE TABLE box_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    box_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NOT NULL,
    expected_qty INTEGER NOT NULL DEFAULT 1,
    current_qty INTEGER NOT NULL DEFAULT 0,
    UNIQUE (box_id, material_id),
    FOREIGN KEY (box_id) REFERENCES boxes(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE custom_fields (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    entity VARCHAR(40) NOT NULL,
    field_key VARCHAR(60) NOT NULL,
    label VARCHAR(120) NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'text',
    options TEXT NULL,
    required TINYINT NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    active TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    UNIQUE (entity, field_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE surgeries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    stage VARCHAR(30) NOT NULL DEFAULT 'cotizacion',
    status VARCHAR(20) NOT NULL DEFAULT 'activa',
    -- 1. Cotización
    patient_id INT UNSIGNED NOT NULL,
    institution_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    seller_id INT UNSIGNED NULL,
    procedure_name VARCHAR(255) NULL,
    commission_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
    fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    quote_notes TEXT NULL,
    custom_data TEXT NULL,
    -- 2. Autorización
    auth_type VARCHAR(20) NULL,
    oc_number VARCHAR(80) NULL,
    authorized_at DATETIME NULL,
    authorized_by INT UNSIGNED NULL,
    -- 3. Asignación
    scheduled_at DATETIME NULL,
    instrumentist_id INT UNSIGNED NULL,
    driver_id INT UNSIGNED NULL,
    box_id INT UNSIGNED NULL,
    logistics_notes TEXT NULL,
    assigned_at DATETIME NULL,
    cx_confirmed_at DATETIME NULL,
    cx_confirmed_by INT UNSIGNED NULL,
    -- 4. Entrega
    preparing_at DATETIME NULL,
    dispatched_at DATETIME NULL,
    delivered_at DATETIME NULL,
    delivered_by INT UNSIGNED NULL,
    -- 5. Cirugía realizada
    performed_at DATETIME NULL,
    observations TEXT NULL,
    -- 6. Consumo
    consumption_at DATETIME NULL,
    box_final_status VARCHAR(30) NULL,
    -- 7. Facturación
    invoice_number VARCHAR(60) NULL,
    invoice_date DATE NULL,
    invoice_amount DECIMAL(12,2) NULL,
    invoiced_at DATETIME NULL,
    fee_liquidated TINYINT NOT NULL DEFAULT 0,
    collected TINYINT NOT NULL DEFAULT 0,
    -- 8. Cobranza
    payment_date DATE NULL,
    payment_type VARCHAR(30) NULL,
    payment_amount DECIMAL(12,2) NULL,
    cheque_bank VARCHAR(120) NULL,
    cheque_number VARCHAR(60) NULL,
    cheque_date DATE NULL,
    cheque_holder VARCHAR(160) NULL,
    collection_notes TEXT NULL,
    collected_at DATETIME NULL,
    -- Suspensión / reprogramación
    suspended_at DATETIME NULL,
    suspension_reason TEXT NULL,
    reprogram_count INTEGER NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (institution_id) REFERENCES institutions(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (instrumentist_id) REFERENCES instrumentists(id) ON DELETE SET NULL,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (box_id) REFERENCES boxes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_surgeries_stage ON surgeries (stage, status);

CREATE INDEX idx_surgeries_seller ON surgeries (seller_id);

CREATE INDEX idx_surgeries_driver ON surgeries (driver_id);

CREATE INDEX idx_surgeries_scheduled ON surgeries (scheduled_at);

CREATE TABLE quote_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    surgery_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NULL,
    description VARCHAR(255) NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE consumption_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    surgery_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NOT NULL,
    source VARCHAR(10) NOT NULL DEFAULT 'caja',
    used_qty INTEGER NOT NULL DEFAULT 0,
    missing_qty INTEGER NOT NULL DEFAULT 0,
    replenished TINYINT NOT NULL DEFAULT 0,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attachments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    surgery_id INT UNSIGNED NOT NULL,
    kind VARCHAR(20) NOT NULL,
    original_name VARCHAR(190) NOT NULL,
    stored_path VARCHAR(255) NOT NULL,
    mime VARCHAR(80) NOT NULL,
    size INTEGER NOT NULL DEFAULT 0,
    uploaded_by INT UNSIGNED NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_attachments_surgery ON attachments (surgery_id, kind);

CREATE TABLE supplier_invoices (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    surgery_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    invoice_number VARCHAR(60) NOT NULL,
    invoice_date DATE NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid TINYINT NOT NULL DEFAULT 0,
    paid_date DATE NULL,
    attachment_id INT UNSIGNED NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE surgery_events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    surgery_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    event VARCHAR(60) NOT NULL,
    detail TEXT NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_surgery_events ON surgery_events (surgery_id);

CREATE TABLE box_events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    box_id INT UNSIGNED NOT NULL,
    surgery_id INT UNSIGNED NULL,
    from_status VARCHAR(30) NULL,
    to_status VARCHAR(30) NOT NULL,
    user_id INT UNSIGNED NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (box_id) REFERENCES boxes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_box_events ON box_events (box_id);

CREATE TABLE stock_movements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    material_id INT UNSIGNED NOT NULL,
    box_id INT UNSIGNED NULL,
    surgery_id INT UNSIGNED NULL,
    type VARCHAR(20) NOT NULL,
    quantity INTEGER NOT NULL,
    reason VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_stock_movements ON stock_movements (material_id);

CREATE TABLE consignment_stock (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 0,
    updated_at DATETIME NULL,
    UNIQUE (client_id, material_id),
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (material_id) REFERENCES materials(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE consignment_movements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NOT NULL,
    type VARCHAR(20) NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    reference VARCHAR(120) NULL,
    notes VARCHAR(255) NULL,
    invoice_number VARCHAR(60) NULL,
    billed_at DATETIME NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (material_id) REFERENCES materials(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_consignment_movements ON consignment_movements (client_id, type);

CREATE TABLE notifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event VARCHAR(60) NOT NULL,
    channel VARCHAR(20) NOT NULL,
    user_id INT UNSIGNED NULL,
    surgery_id INT UNSIGNED NULL,
    recipient VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    attempts INTEGER NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    created_at DATETIME NULL,
    sent_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_notifications_status ON notifications (status);

CREATE TABLE delivery_tokens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    surgery_id INT UNSIGNED NOT NULL,
    driver_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(60) NOT NULL,
    entity VARCHAR(60) NULL,
    entity_id INT UNSIGNED NULL,
    detail TEXT NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_audit_entity ON audit_log (entity, entity_id);

CREATE TABLE contact_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    company VARCHAR(160) NULL,
    message TEXT NOT NULL,
    ip VARCHAR(45) NULL,
    read_at DATETIME NULL,
    created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- roles: 7 registros
INSERT INTO `roles` (`id`, `slug`, `name`, `description`, `created_at`) VALUES
(1, 'gerente', 'Gerentes', 'Acceso total, incluidos los estadios de caja.', '2026-11-12 15:30:14'),
(2, 'cotizacion', 'Cotización', 'Carga y edita cotizaciones y órdenes de compra.', '2026-11-12 15:30:15'),
(3, 'cx', 'Equipo de Cx', 'Confirma fecha y horario; carga PDF, consumo y observaciones.', '2026-11-12 15:30:15'),
(4, 'facturacion', 'Facturación', 'Emite facturas; marca honorario liquidado, cobrado y pago a proveedores.', '2026-11-12 15:30:15'),
(5, 'cobranzas', 'Cobranzas', 'Carga fecha de pago, tipo de pago y datos del cheque. No ve datos de Cx.', '2026-11-12 15:30:15'),
(6, 'vendedor', 'Vendedores', 'Cargan cotizaciones con la receta del médico. Ven solo sus cirugías.', '2026-11-12 15:30:15'),
(7, 'chofer', 'Choferes', 'Reciben avisos de preparación y reparto; confirman la entrega.', '2026-11-12 15:30:15');

-- role_permissions: 73 registros
INSERT INTO `role_permissions` (`role_id`, `permission`) VALUES
(1, 'audit.view'),
(1, 'billing.amounts.view'),
(1, 'billing.edit'),
(1, 'billing.view'),
(1, 'boxes.states.view'),
(1, 'catalogs.manage'),
(1, 'collection.edit'),
(1, 'collection.view'),
(1, 'consignment.manage'),
(1, 'consignment.view'),
(1, 'custom_fields.manage'),
(1, 'cx.confirm'),
(1, 'cx.post'),
(1, 'cx.view'),
(1, 'dashboard.view'),
(1, 'deliveries.view_own'),
(1, 'delivery.confirm'),
(1, 'fee.status.view'),
(1, 'logistics.manage'),
(1, 'messages.view'),
(1, 'notifications.view'),
(1, 'quote.amounts.view'),
(1, 'quote.edit'),
(1, 'quote.view'),
(1, 'roles.manage'),
(1, 'stock.manage'),
(1, 'stock.view'),
(1, 'surgeries.view_all'),
(1, 'surgeries.view_own'),
(1, 'surgery.history.view'),
(1, 'surgery.summary.view'),
(1, 'surgery.suspend'),
(1, 'users.manage'),
(2, 'catalogs.manage'),
(2, 'dashboard.view'),
(2, 'quote.amounts.view'),
(2, 'quote.edit'),
(2, 'quote.view'),
(2, 'surgeries.view_all'),
(2, 'surgery.summary.view'),
(3, 'boxes.states.view'),
(3, 'cx.confirm'),
(3, 'cx.post'),
(3, 'cx.view'),
(3, 'dashboard.view'),
(3, 'stock.view'),
(3, 'surgeries.view_all'),
(3, 'surgery.summary.view'),
(4, 'billing.amounts.view'),
(4, 'billing.edit'),
(4, 'billing.view'),
(4, 'consignment.view'),
(4, 'cx.view'),
(4, 'dashboard.view'),
(4, 'fee.status.view'),
(4, 'quote.amounts.view'),
(4, 'quote.view'),
(4, 'surgeries.view_all'),
(4, 'surgery.history.view'),
(4, 'surgery.summary.view'),
(5, 'billing.amounts.view'),
(5, 'collection.edit'),
(5, 'collection.view'),
(5, 'dashboard.view'),
(6, 'billing.amounts.view'),
(6, 'dashboard.view'),
(6, 'fee.status.view'),
(6, 'quote.amounts.view'),
(6, 'quote.edit'),
(6, 'quote.view'),
(6, 'surgeries.view_own'),
(7, 'deliveries.view_own'),
(7, 'delivery.confirm');

-- users: 1 registros
INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `phone`, `password_hash`, `commission_pct`, `notify_email`, `notify_whatsapp`, `active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Administrador', 'admin@metallive.com.ar', NULL, '$2y$12$SLKXL9vThBiC0jsAdY9OY.w6x6iH0hmfYV8t3VABmUlvB6SNGD43C', 0, 1, 0, 1, NULL, '2026-11-12 15:30:15', '2026-11-12 15:30:15');

SET FOREIGN_KEY_CHECKS = 1;
