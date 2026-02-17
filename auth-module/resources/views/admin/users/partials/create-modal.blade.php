<x-ui.modal :isOpen="false" modalId="createUserModal">
    <div class="p-6 sm:p-8">
        <h3 class="mb-6 text-2xl font-semibold text-gray-800 dark:text-white">{{ __('Add New User') }}</h3>
        <form id="createUserForm" onsubmit="submitCreateUser(event)">
            @csrf
            <div class="space-y-5">
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Full Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="{{ __('Full Name') }}">
                    <p class="mt-1.5 text-xs text-red-600 hidden" id="name-error"></p>
                </div>
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Email') }} <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" required class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="{{ __('example@email.com') }}">
                    <p class="mt-1.5 text-xs text-red-600 hidden" id="email-error"></p>
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Password') }} <span class="text-red-500">*</span></label>
                    <input type="password" name="password" id="password" required class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="{{ __('At least 8 characters') }}">
                    <p class="mt-1.5 text-xs text-red-600 hidden" id="password-error"></p>
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Confirm Password') }} <span class="text-red-500">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="{{ __('Enter password again') }}">
                    <p class="mt-1.5 text-xs text-red-600 hidden" id="password_confirmation-error"></p>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="role" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Role') }}</label>
                        <select name="role" id="role" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            @foreach($availableRoles ?? ['user' => __('User'), 'manager' => __('Manager'), 'admin' => __('Admin')] as $slug => $label)
                                <option value="{{ $slug }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <div class="flex h-11 w-full items-center rounded-lg border border-gray-300 bg-gray-50 px-4 dark:border-gray-700 dark:bg-gray-700">
                            <input type="checkbox" name="is_active" id="is_active" value="1" checked class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <label for="is_active" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Active') }}</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-ui.button size="md" variant="outline" type="button" onclick="window.closeCreateModal()">{{ __('Cancel') }}</x-ui.button>
                <x-ui.button size="md" variant="primary" type="submit" id="submitBtn">
                    <span id="submitBtnText">{{ __('Save') }}</span>
                    <span id="submitBtnLoader" class="hidden"><svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
                </x-ui.button>
            </div>
        </form>
    </div>
</x-ui.modal>
<script>
function submitCreateUser(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnLoader = document.getElementById('submitBtnLoader');
    submitBtn.disabled = true;
    submitBtnText.classList.add('hidden');
    submitBtnLoader.classList.remove('hidden');
    document.querySelectorAll('#createUserModal .text-red-600').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
    fetch('{{ route("admin.users.store") }}', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (typeof window.closeCreateModal === 'function') window.closeCreateModal();
            if (typeof loadUsers === 'function') loadUsers();
            showNotification(@json(__('User created successfully.')), 'success');
        } else {
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    const errorEl = document.getElementById(key + '-error');
                    if (errorEl) { errorEl.textContent = data.errors[key][0]; errorEl.classList.remove('hidden'); }
                });
            } else showNotification(data.message || @json(__('An error occurred.')), 'error');
        }
    })
    .catch(() => showNotification(@json(__('An error occurred. Please try again.')), 'error'))
    .finally(() => { submitBtn.disabled = false; submitBtnText.classList.remove('hidden'); submitBtnLoader.classList.add('hidden'); });
}
</script>
