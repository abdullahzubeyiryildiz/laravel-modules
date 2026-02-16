<?php

namespace Modules\UserManagementModule\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\UserManagementModule\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Kullanıcı listesi sayfası
     */
    public function index()
    {
        return view('user-management-module::admin.users.index', [
            'title' => 'Kullanıcı Yönetimi'
        ]);
    }

    /**
     * DataTable için kullanıcı verilerini döndür
     */
    public function datatable(Request $request): JsonResponse
    {
        $userModel = config('user-management-module.user_model', User::class);
        
        // Global scope'ları bypass et (tenant scope gibi)
        $query = $userModel::withoutGlobalScopes();

        // Arama
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Durum filtresi
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        // Toplam kayıt sayısı (filtreleme öncesi)
        $totalRecords = $userModel::withoutGlobalScopes()->count();

        // Filtrelenmiş kayıt sayısı (kopya query oluştur)
        $filteredQuery = clone $query;
        $filteredRecords = $filteredQuery->count();

        // Sıralama
        $orderColumn = $request->order[0]['column'] ?? 0;
        $orderDir = $request->order[0]['dir'] ?? 'asc';
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
                'created_at' => $user->created_at->format('d.m.Y H:i'),
                'actions' => view('user-management-module::admin.users.partials.actions', ['user' => $user])->render(),
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
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
                'message' => 'Doğrulama hatası',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $this->userService->create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Kullanıcı başarıyla oluşturuldu.',
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
                'message' => 'Kullanıcı oluşturulurken bir hata oluştu: ' . $e->getMessage()
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
                'message' => 'Doğrulama hatası',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $this->userService->update($user, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Kullanıcı başarıyla güncellendi.',
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
                'message' => 'Kullanıcı güncellenirken bir hata oluştu: ' . $e->getMessage()
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
                'message' => 'Kullanıcı başarıyla silindi.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı silinirken bir hata oluştu: ' . $e->getMessage()
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
                'message' => 'Kullanıcı durumu güncellendi.',
                'user' => [
                    'id' => $user->id,
                    'is_active' => $user->is_active,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı durumu güncellenirken bir hata oluştu: ' . $e->getMessage()
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
