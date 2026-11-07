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
                'list' => ['last_name' => 'Apellido', 'first_name' => 'Nombre', 'document' => 'DNI', 'client_id' => 'Cobertura', 'phone' => 'Teléfono'],
                'fields' => [
                    'last_name' => ['label' => 'Apellido', 'rules' => 'required|max:120'],
                    'first_name' => ['label' => 'Nombre', 'rules' => 'required|max:120'],
                    'document' => ['label' => 'DNI', 'rules' => 'max:30'],
                    'birth_date' => ['label' => 'Fecha de nacimiento', 'type' => 'date', 'rules' => 'date'],
                    'phone' => ['label' => 'Teléfono', 'type' => 'tel', 'rules' => 'max:40'],
                    'email' => ['label' => 'Correo', 'type' => 'email', 'rules' => 'email|max:190'],
                    'client_id' => ['label' => 'Cobertura (cliente)', 'type' => 'select', 'source' => ['clients', 'name'], 'rules' => 'exists:clients'],
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
                'in_use' => [['surgeries', 'client_id'], ['patients', 'client_id'], ['consignment_movements', 'client_id']],
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

    /** Opciones para selects que dependen de otra tabla. */
    public static function sourceOptions(array $field): array
    {
        if (isset($field['options'])) {
            return $field['options'];
        }
        [$table, $label] = $field['source'];
        Database::assertIdentifier($table, $label);
        $rows = Database::fetchAll("SELECT id, {$label} AS label FROM {$table} ORDER BY {$label}");
        return array_column($rows, 'label', 'id');
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
