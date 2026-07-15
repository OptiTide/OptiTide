<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\User;

/** Admin-only. Every action re-checks isAdmin (the route group also allows staff). */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->guard();

        $role  = (string) $request->query('role', '');
        $valid = array_key_exists($role, User::ROLES);

        $query = User::query();
        if ($valid) {
            $query->where('role', $role);
        }

        $counts = ['' => User::query()->count()];
        foreach (array_keys(User::ROLES) as $roleKey) {
            $counts[$roleKey] = User::query()->where('role', $roleKey)->count();
        }

        return $this->view('admin.users.index', [
            'title'        => 'Users',
            'users'        => $query->orderBy('name')->get(),
            'client_names' => array_column(Client::all(), 'business_name', 'id'),
            'role'         => $valid ? $role : '',
            'role_counts'  => $counts,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->guard();

        return $this->view('admin.users.form', [
            'title'   => 'New User',
            'user'    => null,
            'clients' => Client::query()->orderBy('business_name')->get(),
        ]);
    }

    public function store(Request $request): Response
    {
        $this->guard();
        $data = $this->validated($request);

        User::create([
            'name'          => $data['name'],
            'email'         => strtolower($data['email']),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role'          => $data['role'],
            'client_id'     => $data['role'] === User::ROLE_CLIENT ? (int) $data['client_id'] : null,
            'status'        => 'active',
        ]);

        Session::flash('success', 'User created.');

        return $this->redirect(route('admin.users.index'));
    }

    public function edit(Request $request, string $id): Response
    {
        $this->guard();

        return $this->view('admin.users.form', [
            'title'   => 'Edit User',
            'user'    => User::findOrFail($id),
            'clients' => Client::query()->orderBy('business_name')->get(),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $this->guard();
        $user = User::findOrFail($id);
        $data = $this->validated($request, (int) $id);

        // Guard: an admin can't strip their own admin role or deactivate
        // themselves (either would risk locking out the last administrator).
        if ((string) $user['id'] === (string) Auth::id()) {
            if ($data['role'] !== User::ROLE_ADMIN) {
                Session::flash('error', 'You cannot change your own role.');

                return $this->back();
            }
            if (($data['status'] ?? 'active') !== 'active') {
                Session::flash('error', 'You cannot deactivate your own account.');

                return $this->back();
            }
        }

        $update = [
            'name'      => $data['name'],
            'email'     => strtolower($data['email']),
            'role'      => $data['role'],
            'client_id' => $data['role'] === User::ROLE_CLIENT ? (int) $data['client_id'] : null,
            'status'    => $data['status'] ?? 'active',
        ];

        if (! empty($data['password'])) {
            $update['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        User::updateById($id, $update);
        Session::flash('success', 'User updated.');

        return $this->redirect(route('admin.users.index'));
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->guard();
        $user = User::findOrFail($id);

        if ((string) $user['id'] === (string) Auth::id()) {
            Session::flash('error', 'You cannot delete your own account.');

            return $this->back();
        }

        User::deleteById($id);
        Session::flash('status', 'User deleted.');

        return $this->redirect(route('admin.users.index'));
    }

    protected function validated(Request $request, ?int $id = null): array
    {
        $ignore = $id ? ",$id" : '';
        $rules = [
            'name'      => 'required|max:120',
            'email'     => "required|email|unique:users,email$ignore",
            'role'      => 'required|in:admin,staff,client',
            'client_id' => 'nullable|exists:clients,id',
            'status'    => 'nullable|in:active,inactive',
            'password'  => ($id ? 'nullable' : 'required') . '|min:8',
        ];

        $data = \App\Core\Validator::make($request->all(), $rules)->validate();

        if ($data['role'] === User::ROLE_CLIENT && empty($data['client_id'])) {
            throw new \App\Core\Exceptions\ValidationException(['client_id' => 'Select the client this login belongs to.']);
        }

        return $data;
    }

    protected function guard(): void
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can manage users.');
    }
}
