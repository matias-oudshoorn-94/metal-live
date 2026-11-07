-- Esquema del sistema de gestión de cirugías traumatológicas.
-- {{PK}} y {{ENGINE}} se reemplazan según el motor (SQLite o MySQL) en bin/migrate.php.

CREATE TABLE roles (
    id {{PK}},
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NULL
){{ENGINE}};

CREATE TABLE role_permissions (
    role_id INTEGER NOT NULL,
    permission VARCHAR(80) NOT NULL,
    PRIMARY KEY (role_id, permission),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE users (
    id {{PK}},
    role_id INTEGER NOT NULL,
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
){{ENGINE}};

CREATE TABLE login_attempts (
    id {{PK}},
    ip VARCHAR(45) NOT NULL,
    email VARCHAR(190) NOT NULL,
    attempted_at DATETIME NOT NULL
){{ENGINE}};
CREATE INDEX idx_login_attempts_email ON login_attempts (email, attempted_at);
CREATE INDEX idx_login_attempts_ip ON login_attempts (ip, attempted_at);

-- ---------- Maestros ----------

CREATE TABLE clients (
    id {{PK}},
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
){{ENGINE}};

CREATE TABLE patients (
    id {{PK}},
    last_name VARCHAR(120) NOT NULL,
    first_name VARCHAR(120) NOT NULL,
    document VARCHAR(30) NULL,
    birth_date DATE NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    client_id INTEGER NULL,
    member_number VARCHAR(60) NULL,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
){{ENGINE}};
CREATE INDEX idx_patients_document ON patients (document);

CREATE TABLE institutions (
    id {{PK}},
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
){{ENGINE}};

CREATE TABLE doctors (
    id {{PK}},
    last_name VARCHAR(120) NOT NULL,
    first_name VARCHAR(120) NOT NULL,
    license VARCHAR(40) NULL,
    specialty VARCHAR(120) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
){{ENGINE}};

CREATE TABLE instrumentists (
    id {{PK}},
    last_name VARCHAR(120) NOT NULL,
    first_name VARCHAR(120) NOT NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    active TINYINT NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
){{ENGINE}};

CREATE TABLE suppliers (
    id {{PK}},
    name VARCHAR(160) NOT NULL,
    cuit VARCHAR(20) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    contact VARCHAR(120) NULL,
    notes TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
){{ENGINE}};

-- ---------- Stock ----------

CREATE TABLE materials (
    id {{PK}},
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
){{ENGINE}};

CREATE TABLE boxes (
    id {{PK}},
    code VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'disponible',
    current_surgery_id INTEGER NULL,
    active TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
){{ENGINE}};
CREATE INDEX idx_boxes_status ON boxes (status);

CREATE TABLE box_items (
    id {{PK}},
    box_id INTEGER NOT NULL,
    material_id INTEGER NOT NULL,
    expected_qty INTEGER NOT NULL DEFAULT 1,
    current_qty INTEGER NOT NULL DEFAULT 0,
    UNIQUE (box_id, material_id),
    FOREIGN KEY (box_id) REFERENCES boxes(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id)
){{ENGINE}};

-- ---------- Campos configurables ----------

CREATE TABLE custom_fields (
    id {{PK}},
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
){{ENGINE}};

-- ---------- Cirugías (eje del sistema) ----------

CREATE TABLE surgeries (
    id {{PK}},
    stage VARCHAR(30) NOT NULL DEFAULT 'cotizacion',
    status VARCHAR(20) NOT NULL DEFAULT 'activa',
    -- 1. Cotización
    patient_id INTEGER NOT NULL,
    institution_id INTEGER NOT NULL,
    doctor_id INTEGER NOT NULL,
    client_id INTEGER NOT NULL,
    seller_id INTEGER NULL,
    procedure_name VARCHAR(255) NULL,
    commission_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
    fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    quote_notes TEXT NULL,
    custom_data TEXT NULL,
    -- 2. Autorización
    auth_type VARCHAR(20) NULL,
    oc_number VARCHAR(80) NULL,
    authorized_at DATETIME NULL,
    authorized_by INTEGER NULL,
    -- 3. Asignación
    scheduled_at DATETIME NULL,
    instrumentist_id INTEGER NULL,
    driver_id INTEGER NULL,
    box_id INTEGER NULL,
    logistics_notes TEXT NULL,
    assigned_at DATETIME NULL,
    cx_confirmed_at DATETIME NULL,
    cx_confirmed_by INTEGER NULL,
    -- 4. Entrega
    preparing_at DATETIME NULL,
    dispatched_at DATETIME NULL,
    delivered_at DATETIME NULL,
    delivered_by INTEGER NULL,
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
    created_by INTEGER NULL,
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
){{ENGINE}};
CREATE INDEX idx_surgeries_stage ON surgeries (stage, status);
CREATE INDEX idx_surgeries_seller ON surgeries (seller_id);
CREATE INDEX idx_surgeries_driver ON surgeries (driver_id);
CREATE INDEX idx_surgeries_scheduled ON surgeries (scheduled_at);

CREATE TABLE quote_items (
    id {{PK}},
    surgery_id INTEGER NOT NULL,
    material_id INTEGER NULL,
    description VARCHAR(255) NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL
){{ENGINE}};

CREATE TABLE consumption_items (
    id {{PK}},
    surgery_id INTEGER NOT NULL,
    material_id INTEGER NOT NULL,
    source VARCHAR(10) NOT NULL DEFAULT 'caja',
    used_qty INTEGER NOT NULL DEFAULT 0,
    missing_qty INTEGER NOT NULL DEFAULT 0,
    replenished TINYINT NOT NULL DEFAULT 0,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id)
){{ENGINE}};

CREATE TABLE attachments (
    id {{PK}},
    surgery_id INTEGER NOT NULL,
    kind VARCHAR(20) NOT NULL,
    original_name VARCHAR(190) NOT NULL,
    stored_path VARCHAR(255) NOT NULL,
    mime VARCHAR(80) NOT NULL,
    size INTEGER NOT NULL DEFAULT 0,
    uploaded_by INTEGER NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE
){{ENGINE}};
CREATE INDEX idx_attachments_surgery ON attachments (surgery_id, kind);

CREATE TABLE supplier_invoices (
    id {{PK}},
    surgery_id INTEGER NOT NULL,
    supplier_id INTEGER NOT NULL,
    invoice_number VARCHAR(60) NOT NULL,
    invoice_date DATE NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid TINYINT NOT NULL DEFAULT 0,
    paid_date DATE NULL,
    attachment_id INTEGER NULL,
    notes TEXT NULL,
    created_by INTEGER NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
){{ENGINE}};

CREATE TABLE surgery_events (
    id {{PK}},
    surgery_id INTEGER NOT NULL,
    user_id INTEGER NULL,
    event VARCHAR(60) NOT NULL,
    detail TEXT NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE
){{ENGINE}};
CREATE INDEX idx_surgery_events ON surgery_events (surgery_id);

CREATE TABLE box_events (
    id {{PK}},
    box_id INTEGER NOT NULL,
    surgery_id INTEGER NULL,
    from_status VARCHAR(30) NULL,
    to_status VARCHAR(30) NOT NULL,
    user_id INTEGER NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (box_id) REFERENCES boxes(id) ON DELETE CASCADE
){{ENGINE}};
CREATE INDEX idx_box_events ON box_events (box_id);

CREATE TABLE stock_movements (
    id {{PK}},
    material_id INTEGER NOT NULL,
    box_id INTEGER NULL,
    surgery_id INTEGER NULL,
    type VARCHAR(20) NOT NULL,
    quantity INTEGER NOT NULL,
    reason VARCHAR(255) NULL,
    user_id INTEGER NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
){{ENGINE}};
CREATE INDEX idx_stock_movements ON stock_movements (material_id);

-- ---------- Stock en consignación en clientes ----------

CREATE TABLE consignment_stock (
    id {{PK}},
    client_id INTEGER NOT NULL,
    material_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 0,
    updated_at DATETIME NULL,
    UNIQUE (client_id, material_id),
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (material_id) REFERENCES materials(id)
){{ENGINE}};

CREATE TABLE consignment_movements (
    id {{PK}},
    client_id INTEGER NOT NULL,
    material_id INTEGER NOT NULL,
    type VARCHAR(20) NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    reference VARCHAR(120) NULL,
    notes VARCHAR(255) NULL,
    invoice_number VARCHAR(60) NULL,
    billed_at DATETIME NULL,
    user_id INTEGER NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (material_id) REFERENCES materials(id)
){{ENGINE}};
CREATE INDEX idx_consignment_movements ON consignment_movements (client_id, type);

-- ---------- Motor de avisos ----------

CREATE TABLE notifications (
    id {{PK}},
    event VARCHAR(60) NOT NULL,
    channel VARCHAR(20) NOT NULL,
    user_id INTEGER NULL,
    surgery_id INTEGER NULL,
    recipient VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    attempts INTEGER NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    created_at DATETIME NULL,
    sent_at DATETIME NULL
){{ENGINE}};
CREATE INDEX idx_notifications_status ON notifications (status);

CREATE TABLE delivery_tokens (
    id {{PK}},
    surgery_id INTEGER NOT NULL,
    driver_id INTEGER NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NULL,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE
){{ENGINE}};

-- ---------- Auditoría y sitio ----------

CREATE TABLE audit_log (
    id {{PK}},
    user_id INTEGER NULL,
    action VARCHAR(60) NOT NULL,
    entity VARCHAR(60) NULL,
    entity_id INTEGER NULL,
    detail TEXT NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NULL
){{ENGINE}};
CREATE INDEX idx_audit_entity ON audit_log (entity, entity_id);

CREATE TABLE contact_messages (
    id {{PK}},
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    company VARCHAR(160) NULL,
    message TEXT NOT NULL,
    ip VARCHAR(45) NULL,
    read_at DATETIME NULL,
    created_at DATETIME NULL
){{ENGINE}};
