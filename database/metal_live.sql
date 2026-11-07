-- Metal Live · base de datos completa para MySQL 8 / MariaDB 10.6+
-- Generado el 2026-11-07 10:10 desde sqlite (estructura y datos)
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
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `instrumentists`;
DROP TABLE IF EXISTS `doctors`;
DROP TABLE IF EXISTS `institutions`;
DROP TABLE IF EXISTS `patients`;
DROP TABLE IF EXISTS `clients`;
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
    client_id INT UNSIGNED NULL,
    member_number VARCHAR(60) NULL,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
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

CREATE TABLE materials (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(80) NULL,
    unit VARCHAR(20) NOT NULL DEFAULT 'u',
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock_qty INTEGER NOT NULL DEFAULT 0,
    min_stock INTEGER NOT NULL DEFAULT 0,
    active TINYINT NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
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
(1, 'gerente', 'Gerentes', 'Acceso total, incluidos los estadios de caja.', '2026-11-07 09:58:41'),
(2, 'cotizacion', 'Cotización', 'Carga y edita cotizaciones y órdenes de compra.', '2026-11-07 09:58:41'),
(3, 'cx', 'Equipo de Cx', 'Confirma fecha y horario; carga PDF, consumo y observaciones.', '2026-11-07 09:58:41'),
(4, 'facturacion', 'Facturación', 'Emite facturas; marca honorario liquidado, cobrado y pago a proveedores.', '2026-11-07 09:58:41'),
(5, 'cobranzas', 'Cobranzas', 'Carga fecha de pago, tipo de pago y datos del cheque. No ve datos de Cx.', '2026-11-07 09:58:41'),
(6, 'vendedor', 'Vendedores', 'Cargan cotizaciones con la receta del médico. Ven solo sus cirugías.', '2026-11-07 09:58:41'),
(7, 'chofer', 'Choferes', 'Reciben avisos de preparación y reparto; confirman la entrega.', '2026-11-07 09:58:41');

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

-- users: 8 registros
INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `phone`, `password_hash`, `commission_pct`, `notify_email`, `notify_whatsapp`, `active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Administrador', 'admin@metallive.com.ar', NULL, '$argon2id$v=19$m=65536,t=4,p=1$LlVycXBOWTViZzFYQWZvZg$34ti2K6CeK52E5ArTmOWi9ulatoHHqTf0ZTQiCBuncg', 0, 1, 0, 1, NULL, '2026-11-07 09:58:42', '2026-11-07 09:58:42'),
(2, 2, 'Lucía Cotizaciones', 'cotizacion@demo.local', '+54 9 11 5555-2063', '$argon2id$v=19$m=65536,t=4,p=1$RElTOWFPMFRWdlpkbVdpag$d4jdydzCxr7FK3mTDCifMzUGLIFZynrOi8stgNR8Xz0', 0, 1, 0, 1, NULL, '2026-11-07 09:58:42', '2026-11-07 09:58:42'),
(3, 3, 'Equipo Cx', 'cx@demo.local', '+54 9 11 5555-8118', '$argon2id$v=19$m=65536,t=4,p=1$VXpTL1pDMFBTZmE4VzIzTA$+syF6soVFxQ2hpNltxV7NSX+tD5uwazO6FX54Jqj7XI', 0, 1, 0, 1, NULL, '2026-11-07 09:58:42', '2026-11-07 09:58:42'),
(4, 4, 'Martín Facturación', 'facturacion@demo.local', '+54 9 11 5555-4974', '$argon2id$v=19$m=65536,t=4,p=1$dkpPMDNzeW9yRC5hVWxKSA$N63jJhHfrkqwHgNrI/ugQ0Dp3z4w2oky8kI+RKqOBJo', 0, 1, 0, 1, NULL, '2026-11-07 09:58:42', '2026-11-07 09:58:42'),
(5, 5, 'Paula Cobranzas', 'cobranzas@demo.local', '+54 9 11 5555-7111', '$argon2id$v=19$m=65536,t=4,p=1$Q2xmeGhwMndMLzVjai82Lg$SxGKsne0Gg/QoMeAB+daNOD9Zx4xhHPWQCAJRo+hVEc', 0, 1, 0, 1, NULL, '2026-11-07 09:58:42', '2026-11-07 09:58:42'),
(6, 6, 'Diego Vendedor', 'vendedor@demo.local', '+54 9 11 5555-8404', '$argon2id$v=19$m=65536,t=4,p=1$RE1XUEZKVXNOLzdkQmlYZA$JlDK7FITcz+tIBsSZStgcGNKA685HOUfWmNL+SNE/p4', 5, 1, 0, 1, NULL, '2026-11-07 09:58:42', '2026-11-07 09:58:42'),
(7, 6, 'Sofía Vendedora', 'vendedora@demo.local', '+54 9 11 5555-8331', '$argon2id$v=19$m=65536,t=4,p=1$RG9sQkpQQXVwRS5qZ1FxNg$VJEP6cnBmK8gXDptTxnvH5LViwDp3lfZOT9hrBoxLpY', 4.5, 1, 0, 1, NULL, '2026-11-07 09:58:42', '2026-11-07 09:58:42'),
(8, 7, 'Ramón Chofer', 'chofer@demo.local', '+54 9 11 5555-8216', '$argon2id$v=19$m=65536,t=4,p=1$S2ZzaVBEdC54U20wcGoxQg$sJ0/RkuD2PFiubKtsbL4bqz7vdzQnZe9nXiS0+OTpXw', 0, 1, 0, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43');

-- clients: 5 registros
INSERT INTO `clients` (`id`, `name`, `type`, `cuit`, `address`, `phone`, `email`, `contact`, `payment_terms`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'OSDE', 'prepaga', '30-54674271-6', NULL, NULL, NULL, 'Auditoría médica', '60 días', NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(2, 'PAMI', 'obra_social', NULL, NULL, NULL, NULL, NULL, '90 días', NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(3, 'Swiss Medical', 'prepaga', NULL, NULL, NULL, NULL, NULL, '45 días', NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(4, 'Galeno ART', 'art', NULL, NULL, NULL, NULL, NULL, '30 días', NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(5, 'Particular', 'particular', NULL, NULL, NULL, NULL, NULL, 'Contado', NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43');

-- patients: 4 registros
INSERT INTO `patients` (`id`, `last_name`, `first_name`, `document`, `birth_date`, `phone`, `email`, `client_id`, `member_number`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Pérez', 'María', '28111222', NULL, NULL, NULL, 1, '61 123456 7 01', NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(2, 'López', 'Héctor', '12345678', NULL, NULL, NULL, 2, NULL, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(3, 'Sosa', 'Valentina', '40999888', NULL, NULL, NULL, 3, NULL, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(4, 'Acosta', 'Ricardo', '30555444', NULL, NULL, NULL, 4, NULL, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43');

-- institutions: 3 registros
INSERT INTO `institutions` (`id`, `name`, `cuit`, `address`, `city`, `phone`, `email`, `contact`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Sanatorio Güemes', NULL, 'Francisco Acuña de Figueroa 1240', 'CABA', '011 4959-8200', NULL, NULL, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(2, 'Hospital Alemán', NULL, 'Av. Pueyrredón 1640', 'CABA', '011 4827-7000', NULL, NULL, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(3, 'Clínica San Camilo', NULL, 'Av. Ángel Gallardo 899', 'CABA', NULL, NULL, NULL, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43');

-- doctors: 3 registros
INSERT INTO `doctors` (`id`, `last_name`, `first_name`, `license`, `specialty`, `phone`, `email`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Fernández', 'Julián', 'MN 112233', 'Traumatología', NULL, NULL, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(2, 'Gómez', 'Carolina', 'MN 445566', 'Cirugía de columna', NULL, NULL, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(3, 'Ruiz', 'Esteban', 'MP 7788', 'Artroscopía', NULL, NULL, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43');

-- instrumentists: 2 registros
INSERT INTO `instrumentists` (`id`, `last_name`, `first_name`, `phone`, `email`, `active`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Benítez', 'Laura', '11 4444-1111', NULL, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(2, 'Medina', 'Julieta', '11 4444-2222', NULL, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43');

-- suppliers: 2 registros
INSERT INTO `suppliers` (`id`, `name`, `cuit`, `phone`, `email`, `contact`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'OrthoImport S.A.', '30-71234567-8', NULL, NULL, NULL, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(2, 'Implantes del Sur S.R.L.', '30-70987654-3', NULL, NULL, NULL, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43');

-- materials: 13 registros
INSERT INTO `materials` (`id`, `code`, `name`, `category`, `unit`, `price`, `stock_qty`, `min_stock`, `active`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'TOR-CORT-35', 'Tornillo cortical 3.5 mm', 'Tornillos', 'u', 18500, 160, 50, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(2, 'TOR-ESP-65', 'Tornillo esponjoso 6.5 mm', 'Tornillos', 'u', 24300, 90, 30, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(3, 'TOR-BLOQ-50', 'Tornillo bloqueado 5.0 mm', 'Tornillos', 'u', 31200, 120, 40, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(4, 'PLA-LCP-45', 'Placa LCP 4.5 mm (8 orificios)', 'Placas', 'u', 285000, 12, 4, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(5, 'PLA-TER-35', 'Placa tercio de caña 3.5 mm', 'Placas', 'u', 142000, 12, 5, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(6, 'CLA-END-11', 'Clavo endomedular de fémur 11 mm', 'Clavos', 'u', 690000, 6, 2, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(7, 'CLA-TIB-10', 'Clavo endomedular de tibia 10 mm', 'Clavos', 'u', 640000, 3, 3, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(8, 'PRO-CAD-CEM', 'Prótesis total de cadera cementada', 'Prótesis', 'u', 2450000, 4, 2, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(9, 'PRO-ROD-PS', 'Prótesis total de rodilla PS', 'Prótesis', 'u', 2980000, 3, 2, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(10, 'ANC-SUT-50', 'Anclaje con sutura 5.0 mm', 'Artroscopía', 'u', 210000, 21, 8, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(11, 'CEM-OSE-40', 'Cemento óseo 40 g', 'Consumibles', 'u', 98000, 30, 10, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(12, 'TOR-PED-55', 'Tornillo pedicular 5.5 mm', 'Columna', 'u', 165000, 40, 16, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(13, 'BAR-TIT-55', 'Barra de titanio 5.5 mm', 'Columna', 'u', 120000, 10, 4, 1, NULL, '2026-11-07 09:58:43', '2026-11-07 09:58:43');

-- boxes: 5 registros
INSERT INTO `boxes` (`id`, `code`, `name`, `description`, `status`, `current_surgery_id`, `active`, `created_at`, `updated_at`) VALUES
(1, 'CJ-OST-001', 'Caja osteosíntesis pequeños fragmentos', NULL, 'disponible', NULL, 1, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(2, 'CJ-OST-002', 'Caja osteosíntesis grandes fragmentos', NULL, 'disponible', NULL, 1, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(3, 'CJ-CLA-001', 'Caja clavos endomedulares', NULL, 'disponible', NULL, 1, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(4, 'CJ-COL-001', 'Caja instrumental de columna', NULL, 'disponible', NULL, 1, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(5, 'CJ-ART-001', 'Caja artroscopía hombro', NULL, 'disponible', NULL, 1, '2026-11-07 09:58:43', '2026-11-07 09:58:43');

-- box_items: 11 registros
INSERT INTO `box_items` (`id`, `box_id`, `material_id`, `expected_qty`, `current_qty`) VALUES
(1, 1, 1, 30, 30),
(2, 1, 5, 4, 4),
(3, 2, 2, 20, 20),
(4, 2, 3, 24, 24),
(5, 2, 4, 3, 3),
(6, 3, 6, 2, 2),
(7, 3, 7, 2, 2),
(8, 3, 3, 8, 8),
(9, 4, 12, 12, 12),
(10, 4, 13, 4, 4),
(11, 5, 10, 6, 6);

-- custom_fields: 2 registros
INSERT INTO `custom_fields` (`id`, `entity`, `field_key`, `label`, `type`, `options`, `required`, `sort_order`, `active`, `created_at`) VALUES
(1, 'surgery_quote', 'lateralidad', 'Lateralidad', 'select', 'Derecha\nIzquierda\nBilateral\nNo aplica', 0, 1, 1, '2026-11-07 09:58:43'),
(2, 'surgery_quote', 'urgencia', 'Cirugía de urgencia', 'checkbox', NULL, 0, 2, 1, '2026-11-07 09:58:43');

-- surgeries: 4 registros
INSERT INTO `surgeries` (`id`, `stage`, `status`, `patient_id`, `institution_id`, `doctor_id`, `client_id`, `seller_id`, `procedure_name`, `commission_pct`, `fee_amount`, `quote_notes`, `custom_data`, `auth_type`, `oc_number`, `authorized_at`, `authorized_by`, `scheduled_at`, `instrumentist_id`, `driver_id`, `box_id`, `logistics_notes`, `assigned_at`, `cx_confirmed_at`, `cx_confirmed_by`, `preparing_at`, `dispatched_at`, `delivered_at`, `delivered_by`, `performed_at`, `observations`, `consumption_at`, `box_final_status`, `invoice_number`, `invoice_date`, `invoice_amount`, `invoiced_at`, `fee_liquidated`, `collected`, `payment_date`, `payment_type`, `payment_amount`, `cheque_bank`, `cheque_number`, `cheque_date`, `cheque_holder`, `collection_notes`, `collected_at`, `suspended_at`, `suspension_reason`, `reprogram_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'cotizacion', 'activa', 1, 1, 1, 1, 6, 'Osteosíntesis de radio distal', 5, 450000, NULL, '{"lateralidad":"Derecha","urgencia":0}', NULL, NULL, NULL, NULL, '2026-11-13 08:30:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(2, 'autorizacion', 'activa', 2, 2, 2, 2, 6, 'Reemplazo total de cadera', 5, 500000, NULL, '{"lateralidad":"Derecha","urgencia":0}', NULL, NULL, NULL, NULL, '2026-11-11 08:30:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(3, 'asignacion', 'activa', 3, 3, 3, 3, 7, 'Artrodesis lumbar L4-L5', 5, 550000, NULL, '{"lateralidad":"Derecha","urgencia":0}', 'oc', 'OC-29641', '2026-11-07 09:58:43', NULL, '2026-11-09 08:30:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2, '2026-11-07 09:58:43', '2026-11-07 09:58:43'),
(4, 'facturacion', 'activa', 4, 1, 1, 4, 7, 'Enclavado endomedular de fémur', 5, 600000, NULL, '{"lateralidad":"Derecha","urgencia":0}', 'oc', 'OC-31091', '2026-11-07 09:58:43', NULL, '2026-11-04 08:30:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 2, '2026-11-07 09:58:43', '2026-11-07 09:58:43');

-- quote_items: 8 registros
INSERT INTO `quote_items` (`id`, `surgery_id`, `material_id`, `description`, `quantity`, `unit_price`, `sort_order`) VALUES
(1, 1, 5, 'Placa tercio de caña 3.5 mm', 1, 142000, 0),
(2, 1, 1, 'Tornillo cortical 3.5 mm', 6, 18500, 1),
(3, 2, 8, 'Prótesis total de cadera cementada', 1, 2450000, 0),
(4, 2, 11, 'Cemento óseo 40 g', 2, 98000, 1),
(5, 3, 12, 'Tornillo pedicular 5.5 mm', 4, 165000, 0),
(6, 3, 13, 'Barra de titanio 5.5 mm', 2, 120000, 1),
(7, 4, 6, 'Clavo endomedular de fémur 11 mm', 1, 690000, 0),
(8, 4, 3, 'Tornillo bloqueado 5.0 mm', 4, 31200, 1);

-- surgery_events: 4 registros
INSERT INTO `surgery_events` (`id`, `surgery_id`, `user_id`, `event`, `detail`, `created_at`) VALUES
(1, 1, NULL, 'Cotización creada (datos de ejemplo)', NULL, '2026-11-07 09:58:43'),
(2, 2, NULL, 'Cotización creada (datos de ejemplo)', NULL, '2026-11-07 09:58:43'),
(3, 3, NULL, 'Cotización creada (datos de ejemplo)', NULL, '2026-11-07 09:58:43'),
(4, 4, NULL, 'Cotización creada (datos de ejemplo)', NULL, '2026-11-07 09:58:43');

-- box_events: 5 registros
INSERT INTO `box_events` (`id`, `box_id`, `surgery_id`, `from_status`, `to_status`, `user_id`, `note`, `created_at`) VALUES
(1, 1, NULL, NULL, 'disponible', NULL, 'Alta de caja', '2026-11-07 09:58:43'),
(2, 2, NULL, NULL, 'disponible', NULL, 'Alta de caja', '2026-11-07 09:58:43'),
(3, 3, NULL, NULL, 'disponible', NULL, 'Alta de caja', '2026-11-07 09:58:43'),
(4, 4, NULL, NULL, 'disponible', NULL, 'Alta de caja', '2026-11-07 09:58:43'),
(5, 5, NULL, NULL, 'disponible', NULL, 'Alta de caja', '2026-11-07 09:58:43');

-- stock_movements: 5 registros
INSERT INTO `stock_movements` (`id`, `material_id`, `box_id`, `surgery_id`, `type`, `quantity`, `reason`, `user_id`, `created_at`) VALUES
(1, 1, NULL, NULL, 'consig_envio', -20, 'Consignación PAMI · Remito R 0001-00000452', NULL, '2026-11-07 09:58:43'),
(2, 5, NULL, NULL, 'consig_envio', -3, 'Consignación PAMI · Remito R 0001-00000452', NULL, '2026-11-07 09:58:43'),
(3, 10, NULL, NULL, 'consig_envio', -4, 'Consignación PAMI · Remito R 0001-00000452', NULL, '2026-11-07 09:58:43'),
(4, 1, NULL, NULL, 'consig_consumo', -6, 'Consignación PAMI · Paciente Gutiérrez, A. · parte quirúrgico 8841', NULL, '2026-11-07 09:58:43'),
(5, 5, NULL, NULL, 'consig_consumo', -1, 'Consignación PAMI · Paciente Gutiérrez, A. · parte quirúrgico 8841', NULL, '2026-11-07 09:58:43');

-- consignment_stock: 3 registros
INSERT INTO `consignment_stock` (`id`, `client_id`, `material_id`, `quantity`, `updated_at`) VALUES
(1, 2, 1, 14, '2026-11-07 09:58:43'),
(2, 2, 5, 2, '2026-11-07 09:58:43'),
(3, 2, 10, 4, '2026-11-07 09:58:43');

-- consignment_movements: 5 registros
INSERT INTO `consignment_movements` (`id`, `client_id`, `material_id`, `type`, `quantity`, `unit_price`, `reference`, `notes`, `invoice_number`, `billed_at`, `user_id`, `created_at`) VALUES
(1, 2, 1, 'envio', 20, 18500, 'Remito R 0001-00000452', NULL, NULL, NULL, NULL, '2026-11-07 09:58:43'),
(2, 2, 5, 'envio', 3, 142000, 'Remito R 0001-00000452', NULL, NULL, NULL, NULL, '2026-11-07 09:58:43'),
(3, 2, 10, 'envio', 4, 210000, 'Remito R 0001-00000452', NULL, NULL, NULL, NULL, '2026-11-07 09:58:43'),
(4, 2, 1, 'consumo', -6, 18500, 'Paciente Gutiérrez, A. · parte quirúrgico 8841', NULL, NULL, NULL, NULL, '2026-11-07 09:58:43'),
(5, 2, 5, 'consumo', -1, 142000, 'Paciente Gutiérrez, A. · parte quirúrgico 8841', NULL, NULL, NULL, NULL, '2026-11-07 09:58:43');

-- audit_log: 2 registros
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `entity`, `entity_id`, `detail`, `ip`, `created_at`) VALUES
(1, NULL, 'consignment.envio', 'clients', 2, 'Remito R 0001-00000452', 'cli', '2026-11-07 09:58:43'),
(2, NULL, 'consignment.consumo', 'clients', 2, 'Paciente Gutiérrez, A. · parte quirúrgico 8841', 'cli', '2026-11-07 09:58:43');

SET FOREIGN_KEY_CHECKS = 1;
