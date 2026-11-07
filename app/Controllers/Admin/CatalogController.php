<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Models\Catalog;
use App\Services\Audit;

/** ABM genérico para los maestros definidos en App\Models\Catalog. */
final class CatalogController extends Controller
{
    private const PER_PAGE = 30;

    public function index(string $catalog): void
    {
        $def = Catalog::get($catalog);
        $q = (string) $this->request->query('q', '');
        $where = '1 = 1';
        $params = [];
        if ($q !== '') {
            $where = '(' . implode(' OR ', array_map(fn ($c) => "{$c} LIKE ?", $def['search'])) . ')';
            $params = array_fill(0, count($def['search']), "%{$q}%");
        }
        $total = (int) Database::value("SELECT COUNT(*) FROM {$def['table']} WHERE {$where}", $params);
        $offset = ($this->page() - 1) * self::PER_PAGE;
        $rows = Database::fetchAll("SELECT * FROM {$def['table']} WHERE {$where} ORDER BY {$def['order']} LIMIT " . self::PER_PAGE . " OFFSET {$offset}", $params);

        // Resuelve etiquetas de columnas tipo select.
        $lookups = [];
        foreach ($def['list'] as $col => $_) {
            $field = $def['fields'][$col] ?? [];
            if (($field['type'] ?? '') === 'select') {
                $lookups[$col] = Catalog::sourceOptions($field);
            }
        }

        $this->render('admin/catalog/index', [
            'title' => $def['title'],
            'def' => $def,
            'rows' => $rows,
            'lookups' => $lookups,
            'q' => $q,
            'total' => $total,
            'perPage' => self::PER_PAGE,
        ]);
    }

    public function create(string $catalog): void
    {
        $def = Catalog::get($catalog);
        $this->render('admin/catalog/form', ['title' => 'Nuevo ' . $def['singular'], 'def' => $def, 'row' => null]);
    }

    public function store(string $catalog): void
    {
        $def = Catalog::get($catalog);
        $data = $this->input($def);
        $id = Database::insert($def['table'], [...$data, 'created_at' => now(), 'updated_at' => now()]);
        Audit::log('create', $def['table'], $id);
        $back = (string) $this->request->input('return', '');
        // Permite volver a la cotización tras crear un maestro "al vuelo".
        if (str_starts_with($back, '/admin/cirugias')) {
            $this->redirect($back, ucfirst($def['singular']) . ' creado.');
        }
        $this->redirect('/admin/maestros/' . $def['slug'], ucfirst($def['singular']) . ' creado.');
    }

    public function edit(string $catalog, string $id): void
    {
        $def = Catalog::get($catalog);
        $row = $this->find($def, $id);
        $this->render('admin/catalog/form', ['title' => 'Editar ' . $def['singular'], 'def' => $def, 'row' => $row]);
    }

    public function update(string $catalog, string $id): void
    {
        $def = Catalog::get($catalog);
        $row = $this->find($def, $id);
        $data = $this->input($def);
        Database::update($def['table'], [...$data, 'updated_at' => now()], (int) $row['id']);
        Audit::log('update', $def['table'], (int) $row['id']);
        $this->redirect('/admin/maestros/' . $def['slug'], 'Cambios guardados.');
    }

    public function destroy(string $catalog, string $id): void
    {
        $def = Catalog::get($catalog);
        $row = $this->find($def, $id);
        foreach ($def['in_use'] as [$table, $column]) {
            if (Database::value("SELECT 1 FROM {$table} WHERE {$column} = ? LIMIT 1", [$row['id']])) {
                $this->back('No se puede eliminar: está en uso en otros registros.');
            }
        }
        Database::delete($def['table'], (int) $row['id']);
        Audit::log('delete', $def['table'], (int) $row['id']);
        $this->redirect('/admin/maestros/' . $def['slug'], 'Registro eliminado.');
    }

    private function find(array $def, string $id): array
    {
        return Database::fetch("SELECT * FROM {$def['table']} WHERE id = ?", [(int) $id]) ?? throw new HttpException(404);
    }

    private function input(array $def): array
    {
        $rules = [];
        $labels = [];
        foreach ($def['fields'] as $name => $field) {
            $rules[$name] = $field['rules'];
            $labels[$name] = $field['label'];
        }
        return $this->validate($rules, $labels);
    }
}
