<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Session;
use App\Services\Audit;
use App\Services\CustomFields;

final class CustomFieldController extends Controller
{
    public function index(): void
    {
        $rows = Database::fetchAll('SELECT * FROM custom_fields ORDER BY entity, sort_order, id');
        $this->render('admin/custom_fields/index', ['title' => 'Campos de la cotización', 'rows' => $rows]);
    }

    public function create(): void
    {
        $this->render('admin/custom_fields/form', ['title' => 'Nuevo campo', 'field' => null]);
    }

    public function store(): void
    {
        $data = $this->input();
        $key = $this->slug($data['label']);
        $base = $key;
        $n = 2;
        while (Database::value('SELECT 1 FROM custom_fields WHERE entity = ? AND field_key = ?', [$data['entity'], $key])) {
            $key = $base . '_' . $n++;
        }
        $id = Database::insert('custom_fields', [...$data, 'field_key' => $key, 'created_at' => now()]);
        Audit::log('create', 'custom_fields', $id, $data['label']);
        $this->redirect('/admin/campos', 'Campo creado. Ya aparece en el formulario de cotización.');
    }

    public function edit(string $id): void
    {
        $this->render('admin/custom_fields/form', ['title' => 'Editar campo', 'field' => $this->find($id)]);
    }

    public function update(string $id): void
    {
        $field = $this->find($id);
        $data = $this->input();
        unset($data['entity']);
        Database::update('custom_fields', $data, (int) $field['id']);
        Audit::log('update', 'custom_fields', (int) $field['id']);
        $this->redirect('/admin/campos', 'Campo actualizado.');
    }

    public function destroy(string $id): void
    {
        $field = $this->find($id);
        // Los valores cargados quedan en las cotizaciones existentes; solo se desactiva el campo.
        Database::update('custom_fields', ['active' => 0], (int) $field['id']);
        Audit::log('disable', 'custom_fields', (int) $field['id']);
        $this->redirect('/admin/campos', 'Campo desactivado. Los datos ya cargados se conservan.');
    }

    private function find(string $id): array
    {
        return Database::fetch('SELECT * FROM custom_fields WHERE id = ?', [(int) $id]) ?? throw new HttpException(404);
    }

    private function input(): array
    {
        $data = $this->validate([
            'entity' => 'required|in:' . implode(',', array_keys(CustomFields::ENTITIES)),
            'label' => 'required|max:120',
            'type' => 'required|in:' . implode(',', array_keys(CustomFields::TYPES)),
            'options' => 'max:4000',
            'required' => 'bool',
            'sort_order' => 'int',
            'active' => 'bool',
        ], ['label' => 'Etiqueta', 'type' => 'Tipo', 'options' => 'Opciones', 'sort_order' => 'Orden']);
        if ($data['type'] === 'select' && !trim((string) $data['options'])) {
            Session::flash('_errors', ['options' => 'Indicá al menos una opción (una por línea).']);
            Session::flash('_old_input', $_POST);
            $this->back('Revisá los campos marcados.');
        }
        $data['sort_order'] ??= 0;
        return $data;
    }

    private function slug(string $label): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label) ?: $label;
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($ascii)), '_');
        return substr($slug ?: 'campo', 0, 50);
    }
}
