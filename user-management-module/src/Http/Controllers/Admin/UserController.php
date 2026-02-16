<?php

namespace Modules\UserManagementModule\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\UserManagementModule\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Admin veya manager rolü kontrolü
     */
    protected function checkAdminOrManager()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', __('You need to log in.'));
        }

        $role = $user->role ?? null;

        // Eğer role null veya boş ise, user olarak kabul et
        if (empty($role)) {
            $role = 'user';
        }

        // Debug için log ekle
        \Log::info('User role check', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'role' => $role,
            'is_admin_or_manager' => in_array($role, ['admin', 'manager'])
        ]);

        if (!in_array($role, ['admin', 'manager'])) {
            // Dashboard'a yönlendir ve hata mesajı göster
            return redirect()->route('dashboard')->with('error', __('You do not have permission to access this page. Only admin and manager users can access.'));
        }

        return null; // İzin var, devam et
    }

    /**
     * Kullanıcı listesi sayfası
     */
    public function index()
    {
        $check = $this->checkAdminOrManager();
        if ($check) {
            return $check; // Redirect varsa döndür
        }

        return view('user-management-module::admin.users.index', [
            'title' => __('User Management')
        ]);
    }

    /**
     * DataTable için kullanıcı verilerini döndür
     */
    public function datatable(Request $request): JsonResponse
    {
        try {
            $userModel = config('user-management-module.user_model', User::class);
            $currentUser = Auth::user();

            // Global scope'ları bypass et (tenant scope gibi)
            $query = $userModel::withoutGlobalScopes();

            // Admin kullanıcı tenant_id null ise tüm kullanıcıları göster


            // Arama
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Toplam kayıt sayısı (filtreleme öncesi)
            $totalQuery = $userModel::withoutGlobalScopes();

            $totalRecords = $totalQuery->count();

            // Filtrelenmiş kayıt sayısı (aynı query'yi kullan)
            $filteredRecords = $query->count();

            // Sıralama
            $orderColumn = 0;
            $orderDir = 'asc';

            if ($request->has('order') && is_array($request->order) && count($request->order) > 0) {
                $orderColumn = $request->order[0]['column'] ?? 0;
                $orderDir = $request->order[0]['dir'] ?? 'asc';
            }

            $columns = ['id', 'name', 'email', 'role', 'is_active', 'created_at'];
            $orderBy = $columns[$orderColumn] ?? 'id';
            $query->orderBy($orderBy, $orderDir);

            // Sayfalama
            $start = $request->start ?? 0;
            $length = $request->length ?? 10;
            $users = $query->skip($start)->take($length)->get();

            // DataTable formatına dönüştür
            $data = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ?? '-',
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at ? $user->created_at->format('d.m.Y H:i') : '-',
                    'actions' => view('user-management-module::admin.users.partials.actions', ['user' => $user])->render(),
                ];
            })->toArray();

            return response()->json([
                'draw' => intval($request->draw ?? 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('DataTable error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'draw' => intval($request->draw ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Yeni kullanıcı oluştur
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|in:admin,manager,user',
            'is_active' => 'nullable|boolean',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error'),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $this->userService->create($request->all());

            return response()->json([
                'success' => true,
                'message' => __('User created successfully.'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while creating the user: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    /**
     * Kullanıcı güncelle
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'nullable|string|in:admin,manager,user',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error'),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $this->userService->update($user, $request->all());

            return response()->json([
                'success' => true,
                'message' => __('User updated successfully.'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while updating the user: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    /**
     * Kullanıcı sil
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->delete($user);

            return response()->json([
                'success' => true,
                'message' => __('User deleted successfully.')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while deleting the user: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    /**
     * Kullanıcı durumunu değiştir
     */
    public function toggleStatus(User $user): JsonResponse
    {
        try {
            $user = $this->userService->toggleStatus($user);

            return response()->json([
                'success' => true,
                'message' => __('User status updated.'),
                'user' => [
                    'id' => $user->id,
                    'is_active' => $user->is_active,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while updating user status: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    /**
     * Kullanıcı bilgilerini getir (modal için)
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ]
        ], 200);
    }
}
