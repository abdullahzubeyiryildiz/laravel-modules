<!-- Create User Modal -->
<div id="createUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeCreateModal()"></div>

        <!-- Modal panel -->
        <div class="relative inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
            <form id="createUserForm" onsubmit="submitCreateUser(event)">
                @csrf
                
                <!-- Header -->
                <div class="border-b border-gray-200 bg-white px-4 py-5 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white" id="modal-title">
                            Yeni Kullanıcı Ekle
                        </h3>
                        <button type="button" onclick="closeCreateModal()" class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <span class="sr-only">Kapat</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="bg-white px-4 py-5 dark:bg-gray-800 sm:p-6">
                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Ad Soyad <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" required
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                placeholder="Ad Soyad">
                            <p class="mt-1 text-xs text-red-600 hidden" id="name-error"></p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                E-posta <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" id="email" required
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                placeholder="ornek@email.com">
                            <p class="mt-1 text-xs text-red-600 hidden" id="email-error"></p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Şifre <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="password" id="password" required
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                placeholder="En az 8 karakter">
                            <p class="mt-1 text-xs text-red-600 hidden" id="password-error"></p>
                        </div>

                        <!-- Password Confirmation -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Şifre Tekrar <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                placeholder="Şifreyi tekrar giriniz">
                            <p class="mt-1 text-xs text-red-600 hidden" id="password_confirmation-error"></p>
                        </div>

                        <!-- Role -->
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Rol
                            </label>
                            <select name="role" id="role"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                                <option value="user">Kullanıcı</option>
                                <option value="manager">Yönetici</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <!-- Is Active -->
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" checked
                                class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Aktif
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-700 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="submit" id="submitBtn"
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                        <span id="submitBtnText">Kaydet</span>
                        <span id="submitBtnLoader" class="hidden">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                    <button type="button" onclick="closeCreateModal()"
                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        İptal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function submitCreateUser(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnLoader = document.getElementById('submitBtnLoader');
    
    // Loading state
    submitBtn.disabled = true;
    submitBtnText.classList.add('hidden');
    submitBtnLoader.classList.remove('hidden');
    
    // Hata mesajlarını temizle
    document.querySelectorAll('.text-red-600').forEach(el => {
        el.classList.add('hidden');
        el.textContent = '';
    });
    
    fetch('{{ route("admin.users.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Başarılı
            closeCreateModal();
            // Kullanıcıları yeniden yükle
            if (typeof loadUsers === 'function') {
                loadUsers();
            }
            // Başarı mesajı göster
            showNotification('Kullanıcı başarıyla oluşturuldu.', 'success');
        } else {
            // Hata mesajlarını göster
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    const errorEl = document.getElementById(key + '-error');
                    if (errorEl) {
                        errorEl.textContent = data.errors[key][0];
                        errorEl.classList.remove('hidden');
                    }
                });
            } else {
                showNotification(data.message || 'Bir hata oluştu.', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
    })
    .finally(() => {
        // Loading state'i kaldır
        submitBtn.disabled = false;
        submitBtnText.classList.remove('hidden');
        submitBtnLoader.classList.add('hidden');
    });
}

// ESC tuşu ile modal'ı kapat
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeCreateModal();
    }
});
</script>
