<div class="flex items-center gap-2">
    <x-ui.button size="sm" variant="outline" onclick="editUser({{ $user->id }})" className="!px-3 !py-1.5 text-xs">
        <x-slot:startIcon>
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
        </x-slot:startIcon>
        {{ __('Edit') }}
    </x-ui.button>
    <button onclick="deleteUser({{ $user->id }})" class="inline-flex items-center justify-center font-medium gap-2 rounded-lg transition px-3 py-1.5 text-xs bg-white text-red-700 ring-1 ring-inset ring-red-300 hover:bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:ring-red-700 dark:hover:bg-red-900/20">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        {{ __('Delete') }}
    </button>
</div>
