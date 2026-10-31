<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display the staff accounts list.
     */
    public function index(Request $request): View
    {
        $search = trim($request->string('buscar')->toString());

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('modules.usuarios.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a staff account.
     */
    public function create(): View
    {
        return view('modules.usuarios.create', [
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Store a newly created staff account.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        return redirect()
            ->route('usuarios.index')
            ->with('status', "Usuario «{$user->name}» creado.");
    }

    /**
     * Show the form for editing a staff account.
     */
    public function edit(User $user): View
    {
        return view('modules.usuarios.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Update the specified staff account.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'Usuario actualizado.');
    }

    /**
     * Activate or deactivate a staff account.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->update(['active' => ! $user->active]);

        return redirect()
            ->route('usuarios.index')
            ->with('status', $user->active
                ? "Usuario «{$user->name}» activado."
                : "Usuario «{$user->name}» desactivado.");
    }
}
