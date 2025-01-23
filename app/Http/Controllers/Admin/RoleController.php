<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MessageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Http\Resources\Admin\RoleResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Throwable;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::query()
            ->select(['id', 'name', 'guard_name', 'created_at'])
            ->when(request()->search, function ($query, $search) {
                $query->whereAny([
                    'name',
                    'guard_name',
                ], 'REGEXP', $search);
            })
            ->when(request()->field && request()->direction, fn($query) => $query->orderBy(request()->field, request()->direction))
            ->paginate(request()->load ?? 10);

        return inertia('Admin/Roles/Index', [
            'page_settings' => [
                'title' => 'Peran',
                'subtitle' => 'Menampilkan semua data peran yang tersedia pada Universitas Battuta.',
            ],
            'roles' => RoleResource::collection($roles)->additional([
                'meta' => [
                    'has_pages' => $roles->hasPages(),
                ],
            ]),
            'state' => [
                'page' => request()->page ?? 1,
                'search' => request()->search ?? '',
                'load' => 10,
            ],
        ]);
    }

    public function create(): Response
    {
        return inertia('Admin/Roles/Create', [
            'page_settings' => [
                'title' => 'Peran',
                'subtitle' => 'Buat peran baru disini. Klik simpan setelah selesai.',
                'method' => 'POST',
                'action' => route('admin.roles.store'),
            ],
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        try {
            Role::create([
                'name' => $request->name,
                'guard_name' => 'web',
            ]);

            flashMessage(MessageType::CREATED->message('Peran'));
            return to_route('admin.roles.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.roles.index');
        }
    }

    public function edit(Role $role): Response
    {
        return inertia('Admin/Roles/Edit', [
            'page_settings' => [
                'title' => 'Peran',
                'subtitle' => 'Edit peran disini. Klik simpan setelah selesai.',
                'method' => 'PUT',
                'action' => route('admin.roles.update', $role)
            ],
            'role' => $role,
        ]);
    }

    public function update(Role $role, RoleRequest $request): RedirectResponse
    {
        try {
            $role->update([
                'name' => $request->name,
            ]);

            flashMessage(MessageType::UPDATED->message('Peran'));
            return to_route('admin.roles.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.roles.index');
        }
    }

    public function destroy(Role $role): RedirectResponse
    {
        try {
            $role->delete();
            flashMessage(MessageType::DELETED->message('Peran'));
            return to_route('admin.roles.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.roles.index');
        }
    }
}
