// ==================== COMPLETE USER DELETION ====================
function confirmDelete() {
    if (!deleteTargetUid) return;
    
    const uid = deleteTargetUid;
    const user = allUsers.find(u => u.uid === uid);
    
    // Show loading state
    const btn = document.querySelector('#deleteModal .btn-submit');
    const originalText = btn.textContent;
    btn.textContent = '⏳ Deleting user...';
    btn.disabled = true;
    
    // Step 1: Send notification email to user
    if (user && user.email) {
        fetch('../api/send-notification.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: user.email,
                type: 'account_deleted',
                name: user.firstName || 'User'
            })
        }).catch(() => {});
    }
    
    // Step 2: Delete from Firebase Auth and all data
    // Using Firebase Admin SDK endpoint
    fetch('../api/delete-user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ uid: uid })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('🗑️ User completely deleted from Firebase!', 'success');
            
            // Also delete from MySQL
            return fetch('../api/users.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ firebase_uid: uid })
            });
        } else {
            throw new Error(data.error || 'Deletion failed');
        }
    })
    .then(() => {
        closeModal('deleteModal');
        deleteTargetUid = null;
        loadUsers(); // Refresh user list
        btn.textContent = originalText;
        btn.disabled = false;
    })
    .catch((error) => {
        console.error('Delete error:', error);
        showToast('❌ Error: ' + error.message, 'error');
        btn.textContent = originalText;
        btn.disabled = false;
    });
}