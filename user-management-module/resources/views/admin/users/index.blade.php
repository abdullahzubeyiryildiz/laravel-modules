@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Kullanıcı Yönetimi" />
    
    <div class="rounded-2xl border border-gray-200 bg-white px-5 py-7 dark:border-gray-800 dark:bg-white/[0.03] xl:px-10 xl:py-12">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">Kullanıcılar</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Sistem kullanıcılarını yönetin</p>
            </div>
            <button onclick="openCreateModal()" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Yeni Kullanıcı
                </span>
            </button>
        </div>

        <!-- Search and Filters -->
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative flex-1 max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input 
                    type="text" 
                    id="searchInput"
                    placeholder="Ad veya e-posta ile ara..." 
                    class="block w-full rounded-lg border border-gray-300 bg-white pl-10 pr-3 py-2 text-sm text-gray-900 placeholder-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                />
            </div>
            <div class="flex items-center gap-2">
                <select id="statusFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Tüm Durumlar</option>
                    <option value="1">Aktif</option>
                    <option value="0">Pasif</option>
                </select>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="flex items-center justify-center py-12">
            <div class="flex flex-col items-center gap-3">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-brand-200 border-t-brand-500"></div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Yükleniyor...</p>
            </div>
        </div>

        <!-- Error State -->
        <div id="errorState" class="hidden rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-400">Hata</h3>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300" id="errorMessage">Veriler yüklenirken bir hata oluştu.</p>
                </div>
                <button onclick="loadUsers()" class="ml-auto rounded-lg bg-red-100 px-3 py-1.5 text-sm font-medium text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400">
                    Tekrar Dene
                </button>
            </div>
        </div>

        <!-- Table Container -->
        <div id="tableContainer" class="hidden overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table id="usersTable" class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Ad</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">E-posta</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Rol</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Durum</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Oluşturulma</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody" class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <!-- DataTable ile doldurulacak -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="hidden rounded-lg border border-gray-200 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Kullanıcı bulunamadı</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Arama kriterlerinize uygun kullanıcı bulunamadı.</p>
        </div>

        <!-- Pagination -->
        <div id="usersTablePagination" class="mt-4 flex flex-col items-center justify-between gap-4 sm:flex-row">
            <!-- DataTable pagination -->
        </div>
    </div>

    <!-- Modals -->
    @include('user-management-module::admin.users.partials.create-modal')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let pageLength = 10;
    let searchValue = '';
    let statusFilter = '';

    // State management
    function showLoading() {
        document.getElementById('loadingState').classList.remove('hidden');
        document.getElementById('errorState').classList.add('hidden');
        document.getElementById('tableContainer').classList.add('hidden');
        document.getElementById('emptyState').classList.add('hidden');
    }

    function showError(message) {
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorState').classList.remove('hidden');
        document.getElementById('tableContainer').classList.add('hidden');
        document.getElementById('emptyState').classList.add('hidden');
        if (message) {
            document.getElementById('errorMessage').textContent = message;
        }
    }

    function showTable() {
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorState').classList.add('hidden');
        document.getElementById('tableContainer').classList.remove('hidden');
        document.getElementById('emptyState').classList.add('hidden');
    }

    function showEmpty() {
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorState').classList.add('hidden');
        document.getElementById('tableContainer').classList.add('hidden');
        document.getElementById('emptyState').classList.remove('hidden');
    }

    // DataTable başlat
    function initDataTable() {
        loadUsers();
    }

    // Kullanıcıları yükle
    window.loadUsers = function(page = 1, length = 10, search = '', status = '') {
        showLoading();
        const start = (page - 1) * length;
        
        fetch('{{ route("admin.users.datatable") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                draw: page,
                start: start,
                length: length,
                search: {
                    value: search
                },
                status: status,
                order: [{
                    column: 0,
                    dir: 'asc'
                }]
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.data && data.data.length > 0) {
                renderTable(data.data);
                renderPagination(data.recordsTotal, data.recordsFiltered, page, length);
                showTable();
            } else {
                showEmpty();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Veriler yüklenirken bir hata oluştu: ' + error.message);
        });
    }

    // Tabloyu render et
    function renderTable(users) {
        const tbody = document.getElementById('usersTableBody');
        tbody.innerHTML = '';

        users.forEach(user => {
            const row = document.createElement('tr');
            row.className = 'transition-colors hover:bg-gray-50 dark:hover:bg-gray-800';
            row.innerHTML = `
                <td class="px-4 py-3 text-sm text-gray-800 dark:text-white">${user.id}</td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">${user.name}</td>
                <td class="px-4 py-3 text-sm text-gray-800 dark:text-white">${user.email}</td>
                <td class="px-4 py-3 text-sm text-gray-800 dark:text-white">${user.role}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                        user.is_active 
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' 
                            : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                    }">
                        ${user.is_active ? 'Aktif' : 'Pasif'}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">${user.created_at}</td>
                <td class="px-4 py-3 text-sm">
                    ${user.actions}
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Pagination render et
    function renderPagination(total, filtered, currentPage, length) {
        const totalPages = Math.ceil(filtered / length);
        const pagination = document.getElementById('usersTablePagination');
        
        let html = `
            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <span>Toplam: <strong>${filtered}</strong> kayıt (${total} toplam)</span>
            </div>
            <div class="flex items-center gap-2">
        `;

        // Önceki sayfa
        html += `
            <button onclick="changePage(${currentPage - 1})" 
                ${currentPage <= 1 ? 'disabled' : ''}
                class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Önceki
            </button>
        `;

        // Sayfa numaraları
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += `
                    <button onclick="changePage(${i})" 
                        class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors ${
                            i === currentPage 
                                ? 'border-brand-500 bg-brand-500 text-white' 
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'
                        }">
                        ${i}
                    </button>
                `;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += `<span class="px-2 text-gray-500">...</span>`;
            }
        }

        // Sonraki sayfa
        html += `
            <button onclick="changePage(${currentPage + 1})" 
                ${currentPage >= totalPages ? 'disabled' : ''}
                class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Sonraki
            </button>
        `;

        html += '</div>';
        pagination.innerHTML = html;
    }

    // Sayfa değiştir
    window.changePage = function(page) {
        if (page < 1) return;
        currentPage = page;
        loadUsers(currentPage, pageLength, searchValue, statusFilter);
    }

    // Arama
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchValue = e.target.value;
            currentPage = 1;
            loadUsers(currentPage, pageLength, searchValue, statusFilter);
        }, 500);
    });

    // Durum filtresi
    const statusFilterSelect = document.getElementById('statusFilter');
    statusFilterSelect.addEventListener('change', function(e) {
        statusFilter = e.target.value;
        currentPage = 1;
        loadUsers(currentPage, pageLength, searchValue, statusFilter);
    });

    // Modal açma fonksiyonu
    window.openCreateModal = function() {
        const modal = document.getElementById('createUserModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            // Form'u temizle
            const form = document.getElementById('createUserForm');
            if (form) {
                form.reset();
                // Hata mesajlarını temizle
                document.querySelectorAll('.text-red-600').forEach(el => {
                    el.classList.add('hidden');
                    el.textContent = '';
                });
            }
        }
    }

    window.closeCreateModal = function() {
        const modal = document.getElementById('createUserModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }

    // Bildirim göster
    window.showNotification = function(message, type) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 rounded-lg px-4 py-3 shadow-lg transition-all ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // İlk yükleme
    initDataTable();
});
</script>
@endpush
