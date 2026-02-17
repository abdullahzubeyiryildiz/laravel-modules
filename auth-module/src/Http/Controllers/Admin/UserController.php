<?php

namespace Modules\AuthModule\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\AuthModule\Http\Controllers\Controller;
use Modules\AuthModule\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    protected function checkAdminOrManager(): \Illuminate\Http\RedirectResponse|null
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login')->with('error', __('You need to log in.'));
        }
        $role = $this->getUserPrimaryRole($user);
        if (! in_array($role, ['admin', 'manager'], true)) {
            return redirect()->route('dashboard')->with('error', __('You do not have permission to access this page. Only admin and manager users can access.'));
        }
        return null;
    }

    /**
     * Rolü role-permission modülünden al (user_id bazlı, Spatie mantığı - tenant_id kullanılmaz).
     */
    protected function getUserPrimaryRole($user, ?int $tenantId = null): string
    {
        if (class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class)
            && config('role-permission-module.enabled', true)) {
            $role = app(\Modules\RolePermissionModule\Services\RolePermissionService::class)->getPrimaryRole($user, null);
            return $role ?? 'user';
        }
        return $user->role ?? 'user';
    }

    protected function userModel(): string
    {
        return config('auth-module.multi_tenant.user_model', User::class);
    }

    public function index()
    {
        if ($redirect = $this->checkAdminOrManager()) {
            return $redirect;
        }
        $availableRoles = ['user' => __('User'), 'manager' => __('Manager'), 'admin' => __('Admin')];
        if (class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class) && config('role-permission-module.enabled', true)) {
            $slugs = app(\Modules\RolePermissionModule\Services\RolePermissionService::class)->getAvailableRoleSlugs();
            if (! empty($slugs)) {
                $availableRoles = array_combine($slugs, array_map(fn ($s) => __(ucfirst($s)), $slugs));
            }
        }
        return view('auth-module::admin.users.index', [
            'title' => __('User Management'),
            'availableRoles' => $availableRoles,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        try {
            $userModel = $this->userModel();
            $query = $userModel::withoutGlobalScopes();

            if ($request->has('search') && ! empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                });
            }
            if ($request->filled('status') && in_array($request->input('status'), ['0', '1'], true)) {
                $query->where('is_active', (bool) $request->input('status'));
            }

            $totalRecords = $userModel::withoutGlobalScopes()->count();
            $filteredRecords = $query->count();

            $orderColumn = (int) ($request->order[0]['column'] ?? 0);
            $orderDir = $request->order[0]['dir'] ?? 'asc';
            $columns = ['id', 'name', 'email', 'role', 'is_active', 'created_at'];
            $query->orderBy($columns[$orderColumn] ?? 'id', $orderDir);

            $start = (int) ($request->start ?? 0);
            $length = (int) ($request->length ?? 10);
            $users = $query->skip($start)->take($length)->get();

            $data = $users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $this->getUserPrimaryRole($user) ?: '-',
                'is_active' => $user->is_active,
                'created_at' => $user->created_at?->format('d.m.Y H:i') ?? '-',
                'actions' => view('auth-module::admin.users.partials.actions', ['user' => $user])->render(),
            ])->toArray();

            return response()->json([
                'draw' => (int) ($request->draw ?? 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('AuthModule datatable', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'draw' => (int) ($request->draw ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $roleRule = 'nullable|string|in:admin,manager,user';
        if (class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class) && config('role-permission-module.enabled', true)) {
            $roles = app(\Modules\RolePermissionModule\Services\RolePermissionService::class)->getAvailableRoleSlugs();
            if (! empty($roles)) {
                $roleRule = 'nullable|string|in:'.implode(',', $roles);
            }
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => $roleRule,
            'is_active' => 'nullable|boolean',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => __('Validation error'), 'errors' => $validator->errors()], 422);
        }
        try {
            $user = $this->userService->create($request->all());
            return response()->json([
                'success' => true,
                'message' => __('User created successfully.'),
                'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $this->getUserPrimaryRole($user, $user->tenant_id), 'is_active' => $user->is_active],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('An error occurred while creating the user: :error', ['error' => $e->getMessage()])], 500);
        }
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $roleRule = 'nullable|string|in:admin,manager,user';
        if (class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class) && config('role-permission-module.enabled', true)) {
            $roles = app(\Modules\RolePermissionModule\Services\RolePermissionService::class)->getAvailableRoleSlugs();
            if (! empty($roles)) {
                $roleRule = 'nullable|string|in:'.implode(',', $roles);
            }
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => $roleRule,
            'is_active' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => __('Validation error'), 'errors' => $validator->errors()], 422);
        }
        try {
            $user = $this->userService->update($user, $request->all());
            return response()->json([
                'success' => true,
                'message' => __('User updated successfully.'),
                'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $this->getUserPrimaryRole($user, $user->tenant_id), 'is_active' => $user->is_active],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('An error occurred while updating the user: :error', ['error' => $e->getMessage()])], 500);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->delete($user);
            return response()->json(['success' => true, 'message' => __('User deleted successfully.')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('An error occurred while deleting the user: :error', ['error' => $e->getMessage()])], 500);
        }
    }

    public function toggleStatus(User $user): JsonResponse
    {
        try {
            $user = $this->userService->toggleStatus($user);
            return response()->json(['success' => true, 'message' => __('User status updated.'), 'user' => ['id' => $user->id, 'is_active' => $user->is_active]], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('An error occurred while updating user status: :error', ['error' => $e->getMessage()])], 500);
        }
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $this->getUserPrimaryRole($user, $user->tenant_id), 'is_active' => $user->is_active],
        ], 200);
    }
}
