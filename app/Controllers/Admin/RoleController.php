<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Session;
use App\Services\Audit;
use App\Services\Permissions;

/** Los accesos de cada rol se editan desde aquí (matriz de permisos). */
final class RoleController extends Controller
{
    public function index(): void
    {
        $roles = Database::fetchAll(
            'SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id AND u.active = 1) AS users,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS perms
               FROM roles r ORDER BY r.name'
        );
        $matrix = [];
        foreach (Database::fetchAll('SELECT role_id, permission FROM role_permissions') as $row) {
            $matrix[$row['role_id']][$row['permission']] = true;
        }
        $this->render('admin/roles/index', ['title' => 'Roles y accesos', 'roles' => $roles, 'matrix' => $matrix]);
    }

    public function create(): void
    {
        $this->render('admin/roles/form', ['title' => 'Nuevo rol', 'role' => null, 'granted' => []]);
    }

    public function store(): void
    {
        $data = $this->input();
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $data['name']) ?: 'rol')), '-');
        while (Database::value('SELECT 1 FROM roles WHERE slug = ?', [$slug])) {
            $slug .= '-' . random_int(2, 99);
        }
        $id = Database::transaction(function () use ($data, $slug): int {
            $id = Database::insert('roles', ['slug' => $slug, 'name' => $data['name'], 'description' => $data['description'], 'created_at' => now()]);
            $this->syncPermissions($id);
            return $id;
        });
        Audit::log('create', 'roles', $id, $data['name']);
        $this->redirect('/admin/roles', 'Rol creado.');
    }

    public function edit(string $id): void
    {
        $role = $this->find($id);
        $granted = array_fill_keys(array_column(Database::fetchAll('SELECT permission FROM role_permissions WHERE role_id = ?', [$role['id']]), 'permission'), true);
        $this->render('admin/roles/form', ['title' => 'Editar rol: ' . $role['name'], 'role' => $role, 'granted' => $granted]);
    }

    public function update(string $id): void
    {
        $role = $this->find($id);
        $data = $this->input();
        $selected = array_filter((array) ($_POST['permissions'] ?? []), 'is_string');
        // Evita que alguien se quite a sí mismo la administración de roles y quede sin salida.
        if ((int) $role['id'] === (int) Auth::user()['role_id'] && !in_array('roles.manage', $selected, true)) {
            $this->back('No podés quitarle "Administrar roles y permisos" a tu propio rol.');
        }
        Database::transaction(function () use ($role, $data): void {
            Database::update('roles', ['name' => $data['name'], 'description' => $data['description']], (int) $role['id']);
            $this->syncPermissions((int) $role['id']);
        });
        Audit::log('update', 'roles', (int) $role['id'], ['permissions' => $selected]);
        $this->redirect('/admin/roles', 'Accesos del rol actualizados.');
    }

    public function destroy(string $id): void
    {
        $role = $this->find($id);
        if (Database::value('SELECT 1 FROM users WHERE role_id = ? LIMIT 1', [$role['id']])) {
            $this->back('No se puede eliminar un rol con usuarios asignados.');
        }
        Database::delete('roles', (int) $role['id']);
        Audit::log('delete', 'roles', (int) $role['id'], $role['name']);
        $this->redirect('/admin/roles', 'Rol eliminado.');
    }

    private function find(string $id): array
    {
        return Database::fetch('SELECT * FROM roles WHERE id = ?', [(int) $id]) ?? throw new HttpException(404);
    }

    private function input(): array
    {
        return $this->validate(['name' => 'required|max:100', 'description' => 'max:255'], ['name' => 'Nombre', 'description' => 'Descripción']);
    }

    private function syncPermissions(int $roleId): void
    {
        Database::query('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);
        foreach (array_unique((array) ($_POST['permissions'] ?? [])) as $permission) {
            if (is_string($permission) && Permissions::exists($permission)) {
                Database::insert('role_permissions', ['role_id' => $roleId, 'permission' => $permission]);
            }
        }
    }
}
