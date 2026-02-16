<div class="flex items-center gap-2">
    <button onclick="editUser({{ $user->id }})" class="rounded-lg bg-blue-500 px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-blue-600">
        Düzenle
    </button>
    <button onclick="deleteUser({{ $user->id }})" class="rounded-lg bg-red-500 px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-red-600">
        Sil
    </button>
</div>

<script>
function editUser(userId) {
    // TODO: Edit modal implementasyonu
    alert('Düzenleme modalı açılacak - User ID: ' + userId);
}

function deleteUser(userId) {
    if (!confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
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
            showNotification('Kullanıcı başarıyla silindi.', 'success');
        } else {
            showNotification(data.message || 'Kullanıcı silinirken bir hata oluştu.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
    });
}
</script>
