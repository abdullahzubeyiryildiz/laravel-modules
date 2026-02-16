@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="{{ __('User Management') }}" />

    <div class="rounded-2xl border border-gray-200 bg-white px-5 py-7 dark:border-gray-800 dark:bg-white/[0.03] xl:px-10 xl:py-12">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">{{ __('Users') }}</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Manage system users') }}</p>
            </div>
            <x-ui.button size="md" variant="primary" onclick="openCreateModal()">
                <x-slot:startIcon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </x-slot:startIcon>
                {{ __('New User') }}
            </x-ui.button>
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
                    placeholder="{{ __('Search by name or email...') }}"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent pl-10 pr-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                />
            </div>
            <div class="flex items-center gap-2">
                <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                    <select id="statusFilter"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        :class="isOptionSelected && 'text-gray-800 dark:text-white/90'" @change="isOptionSelected = true">
                        <option value="" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">{{ __('All Statuses') }}</option>
                        <option value="1" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">{{ __('Active') }}</option>
                        <option value="0" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">{{ __('Inactive') }}</option>
                    </select>
                    <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                        <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="flex items-center justify-center py-12">
            <div class="flex flex-col items-center gap-3">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-brand-200 border-t-brand-500"></div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Loading...') }}</p>
            </div>
        </div>

        <!-- Error State -->
        <div id="errorState" class="hidden">
            <x-ui.alert variant="error" :title="__('Error')" :message="__('An error occurred while loading data.')">
                <x-slot name="actions">
                    <x-ui.button size="sm" variant="outline" onclick="loadUsers()" class="mt-3">
                        {{ __('Try Again') }}
                    </x-ui.button>
                </x-slot>
            </x-ui.alert>
        </div>

        <!-- Table Container -->
        <div id="tableContainer" class="hidden overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[1102px]">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">{{ __('ID') }}</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">{{ __('Name') }}</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">{{ __('Email') }}</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">{{ __('Role') }}</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">{{ __('Status') }}</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">{{ __('Created') }}</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">{{ __('Actions') }}</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody" class="divide-y divide-gray-100 dark:divide-gray-800">
                        <!-- DataTable ile doldurulacak -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="hidden rounded-xl border border-gray-200 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">{{ __('No users found') }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('No users match your search criteria.') }}</p>
        </div>

        <!-- Pagination -->
        <div id="usersTablePagination" class="mt-4 flex flex-col items-center justify-between gap-4 sm:flex-row">
            <!-- DataTable pagination -->
        </div>
    </div>

    <!-- Modals -->
    @include('user-management-module::admin.users.partials.create-modal')
    @include('user-management-module::admin.users.partials.edit-modal')
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
        const loadingState = document.getElementById('loadingState');
        const errorState = document.getElementById('errorState');
        const tableContainer = document.getElementById('tableContainer');
        const emptyState = document.getElementById('emptyState');

        if (loadingState) loadingState.classList.remove('hidden');
        if (errorState) errorState.classList.add('hidden');
        if (tableContainer) tableContainer.classList.add('hidden');
        if (emptyState) emptyState.classList.add('hidden');
    }

    function showError(message) {
        const loadingState = document.getElementById('loadingState');
        const errorState = document.getElementById('errorState');
        const tableContainer = document.getElementById('tableContainer');
        const emptyState = document.getElementById('emptyState');
        const errorMessage = document.getElementById('errorMessage');

        if (loadingState) loadingState.classList.add('hidden');
        if (errorState) errorState.classList.remove('hidden');
        if (tableContainer) tableContainer.classList.add('hidden');
        if (emptyState) emptyState.classList.add('hidden');
        if (message && errorMessage) {
            errorMessage.textContent = message;
        }
    }

    function showTable() {
        const loadingState = document.getElementById('loadingState');
        const errorState = document.getElementById('errorState');
        const tableContainer = document.getElementById('tableContainer');
        const emptyState = document.getElementById('emptyState');

        if (loadingState) loadingState.classList.add('hidden');
        if (errorState) errorState.classList.add('hidden');
        if (tableContainer) tableContainer.classList.remove('hidden');
        if (emptyState) emptyState.classList.add('hidden');
    }

    function showEmpty() {
        const loadingState = document.getElementById('loadingState');
        const errorState = document.getElementById('errorState');
        const tableContainer = document.getElementById('tableContainer');
        const emptyState = document.getElementById('emptyState');

        if (loadingState) loadingState.classList.add('hidden');
        if (errorState) errorState.classList.add('hidden');
        if (tableContainer) tableContainer.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
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
            console.log('DataTable response:', data);

            if (data.error) {
                showError('Hata: ' + data.error);
                return;
            }

            if (data.data && Array.isArray(data.data) && data.data.length > 0) {
                renderTable(data.data);
                renderPagination(data.recordsTotal, data.recordsFiltered, page, length);
                showTable();
            } else {
                showEmpty();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('{{ __('An error occurred while loading data.') }}: ' + error.message);
        });
    }

    // Tabloyu render et
    function renderTable(users) {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) {
            console.error('usersTableBody element not found');
            return;
        }

        tbody.innerHTML = '';

        if (!users || !Array.isArray(users) || users.length === 0) {
            console.warn('No users data to render');
            return;
        }

        users.forEach(user => {
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-100 dark:border-gray-800';

            // Actions HTML'ini güvenli bir şekilde ekle
            const actionsHtml = user.actions || '';

            row.innerHTML = `
                <td class="px-5 py-4 sm:px-6">
                    <p class="text-gray-800 text-theme-sm dark:text-white/90 font-medium">${user.id || '-'}</p>
                </td>
                <td class="px-5 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-sm font-semibold text-white">
                            ${(user.name || 'U').charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <span class="block font-medium text-gray-800 text-theme-sm dark:text-white/90">${user.name || '-'}</span>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4 sm:px-6">
                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">${user.email || '-'}</p>
                </td>
                <td class="px-5 py-4 sm:px-6">
                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">${user.role || '-'}</p>
                </td>
                <td class="px-5 py-4 sm:px-6">
                    <p class="text-theme-xs inline-block rounded-full px-2 py-0.5 font-medium ${
                        user.is_active
                            ? 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500'
                            : 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-500'
                    }">
                        ${user.is_active ? '{{ __('Active') }}' : '{{ __('Inactive') }}'}
                    </p>
                </td>
                <td class="px-5 py-4 sm:px-6">
                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">${user.created_at || '-'}</p>
                </td>
                <td class="px-5 py-4 sm:px-6">
                    ${actionsHtml}
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
                <span>{{ __('Total') }}: <strong>${filtered}</strong> {{ __('records') }} (${total} {{ __('Total') }})</span>
            </div>
            <div class="flex items-center gap-2">
        `;

        // Önceki sayfa
        const prevDisabled = currentPage <= 1 ? 'disabled cursor-not-allowed opacity-50' : '';
        html += `
            <button onclick="changePage(${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''}
                class="inline-flex items-center justify-center font-medium gap-2 rounded-lg transition px-4 py-3 text-sm bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03] dark:hover:text-gray-300 ${prevDisabled}">
                {{ __('Previous') }}
            </button>
        `;

        // Sayfa numaraları
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                const isActive = i === currentPage;
                const variantClass = isActive
                    ? 'bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600 disabled:bg-brand-300'
                    : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03] dark:hover:text-gray-300';
                html += `
                    <button onclick="changePage(${i})"
                        class="inline-flex items-center justify-center font-medium gap-2 rounded-lg transition px-4 py-3 text-sm ${variantClass}">
                        ${i}
                    </button>
                `;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += `<span class="px-2 text-gray-500">...</span>`;
            }
        }

        // Sonraki sayfa
        const nextDisabled = currentPage >= totalPages ? 'disabled cursor-not-allowed opacity-50' : '';
        html += `
            <button onclick="changePage(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''}
                class="inline-flex items-center justify-center font-medium gap-2 rounded-lg transition px-4 py-3 text-sm bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03] dark:hover:text-gray-300 ${nextDisabled}">
                {{ __('Next') }}
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

    // Edit User fonksiyonu
    window.editUser = function(userId) {
        if (typeof openEditModal === 'function') {
            openEditModal(userId);
        } else {
            console.error('openEditModal function not found');
        }
    }

    // Delete User fonksiyonu
    window.deleteUser = function(userId) {
        if (!confirm(@json(__('Are you sure you want to delete this user?')))) {
            return;
        }

        fetch(`{{ route("admin.users.destroy", ":id") }}`.replace(':id', userId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof loadUsers === 'function') {
                    loadUsers();
                }
                showNotification(@json(__('User deleted successfully.')), 'success');
            } else {
                showNotification(data.message || @json(__('An error occurred while deleting the user: :error')), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification(@json(__('An error occurred. Please try again.')), 'error');
        });
    }

    // Modal açma fonksiyonu
    window.openCreateModal = function() {
        // Alpine.js ile modal'ı aç
        const modal = document.getElementById('createUserModal');
        if (modal) {
            // Modal'ın x-data element'ini bul (modal'ın kendisi)
            const modalElement = modal;

            // Alpine.js ile modal'ı aç
            if (window.Alpine) {
                try {
                    // Alpine.js'in $data metodunu kullan
                    const modalData = Alpine.$data(modalElement);
                    if (modalData) {
                        modalData.open = true;
                    } else {
                        // Alternatif: x-show direktifini kullan
                        modalElement.setAttribute('x-show', 'true');
                        modalElement.removeAttribute('x-cloak');
                    }
                } catch (e) {
                    console.error('Alpine.js error:', e);
                    // Fallback: manuel göster
                    modal.style.display = 'flex';
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            } else {
                // Alpine.js yüklenmemişse manuel göster
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            // Form'u temizle
            const form = document.getElementById('createUserForm');
            if (form) {
                form.reset();
                // Hata mesajlarını temizle
                document.querySelectorAll('#createUserModal .text-red-600').forEach(el => {
                    el.classList.add('hidden');
                    el.textContent = '';
                });
            }
        }
    }

    window.closeCreateModal = function() {
        const modal = document.getElementById('createUserModal');
        if (modal) {
            const modalElement = modal;

            if (window.Alpine) {
                try {
                    const modalData = Alpine.$data(modalElement);
                    if (modalData) {
                        modalData.open = false;
                    } else {
                        modalElement.setAttribute('x-show', 'false');
                        modalElement.setAttribute('x-cloak', '');
                    }
                } catch (e) {
                    console.error('Alpine.js error:', e);
                    modal.style.display = 'none';
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'unset';
                }
            } else {
                modal.style.display = 'none';
                modal.classList.add('hidden');
                document.body.style.overflow = 'unset';
            }
        }
    }

    // Bildirim göster
    window.showNotification = function(message, type) {
        const variantClasses = {
            'success': {
                'container': 'border-green-500 bg-green-50 dark:border-green-500/30 dark:bg-green-500/15',
                'icon': 'text-green-500'
            },
            'error': {
                'container': 'border-red-500 bg-red-50 dark:border-red-500/30 dark:bg-red-500/15',
                'icon': 'text-red-500'
            }
        };

        const classes = variantClasses[type] || variantClasses['error'];
        const icon = type === 'success'
            ? '<svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.70186 12.0001C3.70186 7.41711 7.41711 3.70186 12.0001 3.70186C16.5831 3.70186 20.2984 7.41711 20.2984 12.0001C20.2984 16.5831 16.5831 20.2984 12.0001 20.2984C7.41711 20.2984 3.70186 16.5831 3.70186 12.0001ZM12.0001 1.90186C6.423 1.90186 1.90186 6.423 1.90186 12.0001C1.90186 17.5772 6.423 22.0984 12.0001 22.0984C17.5772 22.0984 22.0984 17.5772 22.0984 12.0001C22.0984 6.423 17.5772 1.90186 12.0001 1.90186ZM15.6197 10.7395C15.9712 10.388 15.9712 9.81819 15.6197 9.46672C15.2683 9.11525 14.6984 9.11525 14.347 9.46672L11.1894 12.6243L9.6533 11.0883C9.30183 10.7368 8.73198 10.7368 8.38051 11.0883C8.02904 11.4397 8.02904 12.0096 8.38051 12.3611L10.553 14.5335C10.7217 14.7023 10.9507 14.7971 11.1894 14.7971C11.428 14.7971 11.657 14.7023 11.8257 14.5335L15.6197 10.7395Z" fill=""></path></svg>'
            : '<svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.6501 12.0001C3.6501 7.38852 7.38852 3.6501 12.0001 3.6501C16.6117 3.6501 20.3501 7.38852 20.3501 12.0001C20.3501 16.6117 16.6117 20.3501 12.0001 20.3501C7.38852 20.3501 3.6501 16.6117 3.6501 12.0001ZM12.0001 1.8501C6.39441 1.8501 1.8501 6.39441 1.8501 12.0001C1.8501 17.6058 6.39441 22.1501 12.0001 22.1501C17.6058 22.1501 22.1501 17.6058 22.1501 12.0001C22.1501 6.39441 17.6058 1.8501 12.0001 1.8501ZM10.9992 7.52517C10.9992 8.07746 11.4469 8.52517 11.9992 8.52517H12.0002C12.5525 8.52517 13.0002 8.07746 13.0002 7.52517C13.0002 6.97289 12.5525 6.52517 12.0002 6.52517H11.9992C11.4469 6.52517 10.9992 6.97289 10.9992 7.52517ZM12.0002 17.3715C11.586 17.3715 11.2502 17.0357 11.2502 16.6215V10.945C11.2502 10.5308 11.586 10.195 12.0002 10.195C12.4144 10.195 12.7502 10.5308 12.7502 10.945V16.6215C12.7502 17.0357 12.4144 17.3715 12.0002 17.3715Z" fill=""></path></svg>';

        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-99999 rounded-xl border p-4 ${classes.container} transition-all duration-300`;
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';

        notification.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="-mt-0.5 ${classes.icon}">
                    ${icon}
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-800 dark:text-white/90">${message}</p>
                </div>
            </div>
        `;

        document.body.appendChild(notification);

        // Animasyon ile göster
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Otomatik kapat
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 4000);
    }

    // İlk yükleme
    initDataTable();
});
</script>
@endpush
