/**
 * Mon Espace Client Pro - JavaScript
 */

// Toggle sidebar mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('visible');
    }
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// CSRF token for AJAX requests
const csrfToken = document.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.CSRF_TOKEN = csrfToken.content;
}

// Helper function for AJAX with CSRF
function fetchWithCSRF(url, options = {}) {
    options.headers = options.headers || {};
    options.headers['X-CSRF-Token'] = window.CSRF_TOKEN;
    return fetch(url, options);
}

// Upload document with AJAX
function uploadDocument(formId, callback) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        formData.append('csrf_token', window.CSRF_TOKEN);

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Document uploadé avec succès');
                if (callback) callback(data);
                form.reset();
            } else {
                alert(data.error || 'Erreur lors de l\'upload');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de l\'upload');
        });
    });
}

// Confirm action
function confirmAction(message) {
    return confirm(message || 'Êtes-vous sûr ?');
}

// Update task status
function updateTaskStatus(taskId, newStatus) {
    if (!confirmAction('Changer le statut de cette tâche ?')) return;

    fetch(`/collaborateur/tasks/${taskId}/update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': window.CSRF_TOKEN
        },
        body: `status=${newStatus}&csrf_token=${window.CSRF_TOKEN}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur lors de la mise à jour');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de la mise à jour');
    });
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 octets';
    const k = 1024;
    const sizes = ['octets', 'Ko', 'Mo', 'Go'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Show file info before upload
function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;

    const fileInfo = document.getElementById('fileInfo');
    if (fileInfo) {
        fileInfo.textContent = `${file.name} (${formatFileSize(file.size)})`;
    }
}
