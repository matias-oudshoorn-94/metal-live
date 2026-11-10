<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\HttpException;

/**
 * Definición declarativa de los ABM simples. Un solo controlador y un juego de vistas
 * genéricas resuelven listado, alta, edición y baja de cada maestro.
 */
final class Catalog
{
    public const CLIENT_TYPES = [
        'obra_social' => 'Obra social',
        'prepaga' => 'Prepaga',
        'art' => 'ART',
        'institucion' => 'Institución',
        'particular' => 'Particular',
        'otro' => 'Otro',
    ];

    public static function definitions(): array
    {
        return [
            'pacientes' => [
                'table' => 'patients',
                'title' => 'Pacientes',
                'singular' => 'paciente',
                'icon' => 'user',
                'order' => 'last_name, first_name',
                'search' => ['last_name', 'first_name', 'document'],
                'list' => ['last_name' => 'Apellido', 'first_name' => 'Nombre', 'document' => 'DNI', 'health_insurance_id' => 'Obra social', 'phone' => 'Teléfono'],
                'fields' => [
                    'last_name' => ['label' => 'Apellido', 'rules' => 'required|max:120'],
                    'first_name' => ['label' => 'Nombre', 'rules' => 'required|max:120'],
                    'document' => ['label' => 'DNI', 'rules' => 'max:30'],
                    'birth_date' => ['label' => 'Fecha de nacimiento', 'type' => 'date', 'rules' => 'date'],
                    'phone' => ['label' => 'Teléfono', 'type' => 'tel', 'rules' => 'max:40'],
                    'email' => ['label' => 'Correo', 'type' => 'email', 'rules' => 'email|max:190'],
                    'health_insurance_id' => ['label' => 'Obra social / cobertura', 'type' => 'select', 'source' => ['health_insurances', 'name'], 'rules' => 'exists:health_insurances', 'manage' => 'obras-sociales'],
                    'member_number' => ['label' => 'N.º de afiliado', 'rules' => 'max:60'],
                    'notes' => ['label' => 'Observaciones', 'type' => 'textarea', 'rules' => 'max:2000', 'wide' => true],
                ],
                'in_use' => [['surgeries', 'patient_id']],
            ],
            'instituciones' => [
                'table' => 'institutions',
                'title' => 'Instituciones',
                'singular' => 'institución',
                'icon' => 'building',
                'order' => 'name',
                'search' => ['name', 'city', 'cuit'],
                'list' => ['name' => 'Nombre', 'city' => 'Localidad', 'phone' => 'Teléfono', 'contact' => 'Contacto'],
                'fields' => [
                    'name' => ['label' => 'Nombre', 'rules' => 'required|max:160'],
                    'cuit' => ['label' => 'CUIT', 'rules' => 'max:20'],
                    'address' => ['label' => 'Dirección', 'rules' => 'max:255'],
                    'city' => ['label' => 'Localidad', 'rules' => 'max:120'],
                    'phone' => ['label' => 'Teléfono', 'type' => 'tel', 'rules' => 'max:40'],
                    'email' => ['label' => 'Correo', 'type' => 'email', 'rules' => 'email|max:190'],
                    'contact' => ['label' => 'Contacto', 'rules' => 'max:120'],
                    'notes' => ['label' => 'Observaciones', 'type' => 'textarea', 'rules' => 'max:2000', 'wide' => true],
                ],
                'in_use' => [['surgeries', 'institution_id']],
            ],
            'medicos' => [
                'table' => 'doctors',
                'title' => 'Médicos',
                'singular' => 'médico',
                'icon' => 'stethoscope',
                'order' => 'last_name, first_name',
                'search' => ['last_name', 'first_name', 'license', 'specialty'],
                'list' => ['last_name' => 'Apellido', 'first_name' => 'Nombre', 'license' => 'Matrícula', 'specialty' => 'Especialidad', 'phone' => 'Teléfono'],
                'fields' => [
                    'last_name' => ['label' => 'Apellido', 'rules' => 'required|max:120'],
                    'first_name' => ['label' => 'Nombre', 'rules' => 'required|max:120'],
                    'license' => ['label' => 'Matrícula', 'rules' => 'max:40'],
                    'specialty' => ['label' => 'Especialidad', 'rules' => 'max:120'],
                    'phone' => ['label' => 'Teléfono', 'type' => 'tel', 'rules' => 'max:40'],
                    'email' => ['label' => 'Correo', 'type' => 'email', 'rules' => 'email|max:190'],
                    'notes' => ['label' => 'Observaciones', 'type' => 'textarea', 'rules' => 'max:2000', 'wide' => true],
                ],
                'in_use' => [['surgeries', 'doctor_id']],
            ],
            'clientes' => [
                'table' => 'clients',
                'title' => 'Clientes',
                'singular' => 'cliente',
                'icon' => 'briefcase',
                'order' => 'name',
                'search' => ['name', 'cuit'],
                'list' => ['name' => 'Nombre', 'type' => 'Tipo', 'cuit' => 'CUIT', 'contact' => 'Contacto', 'payment_terms' => 'Condición de pago'],
                'fields' => [
                    'name' => ['label' => 'Nombre / razón social', 'rules' => 'required|max:160'],
                    'type' => ['label' => 'Tipo', 'type' => 'select', 'options' => self::CLIENT_TYPES, 'rules' => 'required|in:' . implode(',', array_keys(self::CLIENT_TYPES))],
                    'cuit' => ['label' => 'CUIT', 'rules' => 'max:20'],
                    'address' => ['label' => 'Dirección', 'rules' => 'max:255'],
                    'phone' => ['label' => 'Teléfono', 'type' => 'tel', 'rules' => 'max:40'],
                    'email' => ['label' => 'Correo', 'type' => 'email', 'rules' => 'email|max:190'],
                    'contact' => ['label' => 'Contacto', 'rules' => 'max:120'],
                    'payment_terms' => ['label' => 'Condición de pago', 'rules' => 'max:120'],
                    'notes' => ['label' => 'Observaciones', 'type' => 'textarea', 'rules' => 'max:2000', 'wide' => true],
                ],
                'in_use' => [['surgeries', 'client_id'], ['consignment_movements', 'client_id']],
            ],
            'obras-sociales' => [
                'table' => 'health_insurances',
                'title' => 'Obras sociales',
                'singular' => 'obra social',
                'gender' => 'f',
                'icon' => 'shield',
                'order' => 'name',
                'search' => ['name', 'code', 'cuit'],
                'list' => ['name' => 'Nombre', 'code' => 'Sigla / código', 'cuit' => 'CUIT', 'phone' => 'Teléfono', 'active' => 'Activa'],
                'fields' => [
                    'name' => ['label' => 'Nombre', 'rules' => 'required|max:160'],
                    'code' => ['label' => 'Sigla / código', 'rules' => 'max:40'],
                    'cuit' => ['label' => 'CUIT', 'rules' => 'max:20'],
                    'phone' => ['label' => 'Teléfono', 'type' => 'tel', 'rules' => 'max:40'],
                    'email' => ['label' => 'Correo', 'type' => 'email', 'rules' => 'email|max:190'],
                    'active' => ['label' => 'Activa', 'type' => 'checkbox', 'rules' => 'bool', 'default' => 1],
                    'notes' => ['label' => 'Observaciones', 'type' => 'textarea', 'rules' => 'max:2000', 'wide' => true],
                ],
                'in_use' => [['patients', 'health_insurance_id']],
            ],
            'categorias-materiales' => [
                'table' => 'material_categories',
                'title' => 'Categorías de materiales',
                'singular' => 'categoría',
                'gender' => 'f',
                'icon' => 'layers',
                'permission' => 'stock.manage',
                'order' => 'name',
                'search' => ['name'],
                'list' => ['name' => 'Nombre', 'notes' => 'Descripción'],
                'fields' => [
                    'name' => ['label' => 'Nombre', 'rules' => 'required|max:80', 'unique' => true],
                    'notes' => ['label' => 'Descripción', 'type' => 'textarea', 'rules' => 'max:2000', 'wide' => true],
                ],
                'in_use' => [['materials', 'category_id']],
            ],
            'instrumentadoras' => [
                'table' => 'instrumentists',
                'title' => 'Instrumentadoras',
                'singular' => 'instrumentadora',
                'icon' => 'scissors',
                'order' => 'last_name, first_name',
                'search' => ['last_name', 'first_name'],
                'list' => ['last_name' => 'Apellido', 'first_name' => 'Nombre', 'phone' => 'Teléfono', 'email' => 'Correo', 'active' => 'Activa'],
                'fields' => [
                    'last_name' => ['label' => 'Apellido', 'rules' => 'required|max:120'],
                    'first_name' => ['label' => 'Nombre', 'rules' => 'required|max:120'],
                    'phone' => ['label' => 'Teléfono', 'type' => 'tel', 'rules' => 'max:40'],
                    'email' => ['label' => 'Correo', 'type' => 'email', 'rules' => 'email|max:190'],
                    'active' => ['label' => 'Activa', 'type' => 'checkbox', 'rules' => 'bool', 'default' => 1],
                    'notes' => ['label' => 'Observaciones', 'type' => 'textarea', 'rules' => 'max:2000', 'wide' => true],
                ],
                'in_use' => [['surgeries', 'instrumentist_id']],
            ],
            'proveedores' => [
                'table' => 'suppliers',
                'title' => 'Proveedores',
                'singular' => 'proveedor',
                'icon' => 'truck',
                'order' => 'name',
                'search' => ['name', 'cuit'],
                'list' => ['name' => 'Nombre', 'cuit' => 'CUIT', 'contact' => 'Contacto', 'phone' => 'Teléfono'],
                'fields' => [
                    'name' => ['label' => 'Nombre / razón social', 'rules' => 'required|max:160'],
                    'cuit' => ['label' => 'CUIT', 'rules' => 'max:20'],
                    'phone' => ['label' => 'Teléfono', 'type' => 'tel', 'rules' => 'max:40'],
                    'email' => ['label' => 'Correo', 'type' => 'email', 'rules' => 'email|max:190'],
                    'contact' => ['label' => 'Contacto', 'rules' => 'max:120'],
                    'notes' => ['label' => 'Observaciones', 'type' => 'textarea', 'rules' => 'max:2000', 'wide' => true],
                ],
                'in_use' => [['supplier_invoices', 'supplier_id']],
            ],
        ];
    }

    public static function get(string $slug): array
    {
        $def = self::definitions()[$slug] ?? throw new HttpException(404);
        return [...$def, 'slug' => $slug];
    }

    /** Permiso necesario para administrar el maestro. */
    public static function permission(array $def): string
    {
        return $def['permission'] ?? 'catalogs.manage';
    }

    /**
     * Opciones para selects que dependen de otra tabla. Con $current (formularios) se ocultan
     * los registros inactivos, salvo el que ya está elegido.
     */
    public static function sourceOptions(array $field, mixed $current = null): array
    {
        if (isset($field['options'])) {
            return $field['options'];
        }
        [$table, $label] = $field['source'];
        Database::assertIdentifier($table, $label);
        $where = '';
        $params = [];
        if (func_num_args() > 1 && self::hasActiveFlag($table)) {
            $where = 'WHERE active = 1 OR id = ?';
            $params[] = (int) $current;
        }
        $rows = Database::fetchAll("SELECT id, {$label} AS label FROM {$table} {$where} ORDER BY {$label}", $params);
        return array_column($rows, 'label', 'id');
    }

    private static function hasActiveFlag(string $table): bool
    {
        foreach (self::definitions() as $def) {
            if ($def['table'] === $table) {
                return isset($def['fields']['active']);
            }
        }
        return false;
    }

    /** Categorías de materiales para selects y filtros (id => nombre). */
    public static function materialCategories(): array
    {
        return array_column(self::named('material_categories'), 'name', 'id');
    }

    /** Listas para los selects de la cotización. */
    public static function people(string $table, bool $onlyActive = false): array
    {
        Database::assertIdentifier($table);
        $where = $onlyActive ? 'WHERE active = 1' : '';
        return Database::fetchAll("SELECT id, last_name, first_name FROM {$table} {$where} ORDER BY last_name, first_name");
    }

    public static function named(string $table): array
    {
        Database::assertIdentifier($table);
        return Database::fetchAll("SELECT id, name FROM {$table} ORDER BY name");
    }
}
