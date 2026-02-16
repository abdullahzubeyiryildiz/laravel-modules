<x-ui.modal :isOpen="false" modalId="editUserModal">
    <div class="p-6 sm:p-8">
        <h3 class="mb-6 text-2xl font-semibold text-gray-800 dark:text-white">
            {{ __('Edit User') }}
        </h3>

        <form id="editUserForm" onsubmit="submitEditUser(event)">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit_user_id" name="user_id">

            <div class="space-y-5">
                <!-- Name -->
                <div>
                    <label for="edit_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        {{ __('Full Name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="edit_name" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        placeholder="{{ __('Full Name') }}">
                    <p class="mt-1.5 text-xs text-red-600 hidden" id="edit_name-error"></p>
                </div>

                <!-- Email -->
                <div>
                    <label for="edit_email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        {{ __('Email') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" id="edit_email" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        placeholder="{{ __('example@email.com') }}">
                    <p class="mt-1.5 text-xs text-red-600 hidden" id="edit_email-error"></p>
                </div>

                <!-- Password (Optional) -->
                <div>
                    <label for="edit_password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        {{ __('Password') }} <span class="text-xs text-gray-500">({{ __('Leave blank to keep current password') }})</span>
                    </label>
                    <div x-data="{ showPassword: false }" class="relative">
                        <input :type="showPassword ? 'text' : 'password'" name="password" id="edit_password"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="{{ __('At least 8 characters') }}">
                        <span @click="showPassword = !showPassword"
                            class="absolute top-1/2 right-4 z-30 -translate-y-1/2 cursor-pointer">
                            <svg x-show="!showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20"
                                viewBox="0 0 20 20" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" />
                            </svg>
                            <svg x-show="showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20"
                                viewBox="0 0 20 20" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-1.5 text-xs text-red-600 hidden" id="edit_password-error"></p>
                </div>

                <!-- Password Confirmation (Optional) -->
                <div>
                    <label for="edit_password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        {{ __('Confirm Password') }}
                    </label>
                    <div x-data="{ showPassword: false }" class="relative">
                        <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" id="edit_password_confirmation"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="{{ __('Enter password again') }}">
                        <span @click="showPassword = !showPassword"
                            class="absolute top-1/2 right-4 z-30 -translate-y-1/2 cursor-pointer">
                            <svg x-show="!showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20"
                                viewBox="0 0 20 20" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" />
                            </svg>
                            <svg x-show="showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20"
                                viewBox="0 0 20 20" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-1.5 text-xs text-red-600 hidden" id="edit_password_confirmation-error"></p>
                </div>

                <!-- Role and Active Status -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <!-- Role -->
                    <div>
                        <label for="edit_role" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            {{ __('Role') }}
                        </label>
                        <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                            <select name="role" id="edit_role"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                :class="isOptionSelected && 'text-gray-800 dark:text-white/90'" @change="isOptionSelected = true">
                                <option value="user" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">{{ __('User') }}</option>
                                <option value="manager" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">{{ __('Manager') }}</option>
                                <option value="admin" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">{{ __('Admin') }}</option>
                            </select>
                            <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </div>
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-end">
                        <div class="flex h-11 w-full items-center rounded-lg border border-gray-300 bg-gray-50 px-4 dark:border-gray-700 dark:bg-gray-700">
                            <input type="checkbox" name="is_active" id="edit_is_active" value="1"
                                class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <label for="edit_is_active" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Active') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-ui.button size="md" variant="outline" type="button" onclick="window.closeEditModal()">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button size="md" variant="primary" type="submit" id="editSubmitBtn">
                    <span id="editSubmitBtnText">{{ __('Update') }}</span>
                    <span id="editSubmitBtnLoader" class="hidden">
                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </x-ui.button>
            </div>
        </form>
    </div>
</x-ui.modal>

<script>
window.openEditModal = function(userId) {
    // Kullanıcı bilgilerini yükle
    fetch(`{{ route("admin.users.show", ":id") }}`.replace(':id', userId), {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.user) {
            const user = data.user;
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_name').value = user.name || '';
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_role').value = user.role || 'user';
            document.getElementById('edit_is_active').checked = user.is_active || false;

            // Modal'ı aç - Alpine.js ile
            const modal = document.getElementById('editUserModal');
            if (modal) {
                // Modal'ın x-data element'ini bul (modal'ın kendisi)
                const modalElement = modal;

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
            }
        } else {
            showNotification(data.message || @json(__('An error occurred.')), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(@json(__('An error occurred. Please try again.')), 'error');
    });
}

window.closeEditModal = function() {
    const modal = document.getElementById('editUserModal');
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

        // Form'u temizle
        const form = document.getElementById('editUserForm');
        if (form) {
            form.reset();
            // Hata mesajlarını temizle
            document.querySelectorAll('#editUserModal .text-red-600').forEach(el => {
                el.classList.add('hidden');
                el.textContent = '';
            });
        }
    }
}

function submitEditUser(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const userId = document.getElementById('edit_user_id').value;
    const submitBtn = document.getElementById('editSubmitBtn');
    const submitBtnText = document.getElementById('editSubmitBtnText');
    const submitBtnLoader = document.getElementById('editSubmitBtnLoader');

    // Loading state
    submitBtn.disabled = true;
    submitBtnText.classList.add('hidden');
    submitBtnLoader.classList.remove('hidden');

    // Hata mesajlarını temizle
    document.querySelectorAll('#editUserModal .text-red-600').forEach(el => {
        el.classList.add('hidden');
        el.textContent = '';
    });

    fetch(`{{ route("admin.users.update", ":id") }}`.replace(':id', userId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-HTTP-Method-Override': 'PUT'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Başarılı
            if (typeof window.closeEditModal === 'function') {
                window.closeEditModal();
            }
            // Kullanıcıları yeniden yükle
            if (typeof loadUsers === 'function') {
                loadUsers();
            }
            // Başarı mesajı göster
            showNotification(@json(__('User updated successfully.')), 'success');
        } else {
            // Hata mesajlarını göster
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    const errorEl = document.getElementById('edit_' + key + '-error');
                    if (errorEl) {
                        errorEl.textContent = data.errors[key][0];
                        errorEl.classList.remove('hidden');
                    }
                });
            } else {
                showNotification(data.message || @json(__('An error occurred.')), 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(@json(__('An error occurred. Please try again.')), 'error');
    })
    .finally(() => {
        // Loading state'i kaldır
        submitBtn.disabled = false;
        submitBtnText.classList.remove('hidden');
        submitBtnLoader.classList.add('hidden');
    });
}
</script>
