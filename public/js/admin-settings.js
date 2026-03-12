// Admin Settings Modal JavaScript

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    
// Get CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Modal elements
const modalOverlay = document.getElementById('admin-modal-overlay');
const modalClose = document.getElementById('admin-modal-close');
const adminAlert = document.getElementById('admin-alert');

// Tab elements
const tabs = document.querySelectorAll('.admin-tab');
const tabPanes = document.querySelectorAll('.admin-tab-pane');

// User list pagination
let currentPage = 1;
let currentSearch = '';
let currentFilter = '';

// Admin map for configuration
let adminMap = null;
let adminMapMarker = null;

// Open modal function
function openAdminModal() {
    modalOverlay.classList.add('active');
    loadSettings();
    loadUsers();
    
    // Initialize admin map after modal is shown
    setTimeout(() => {
        initializeAdminMap();
    }, 100);
}

// Close modal function
function closeAdminModal() {
    modalOverlay.classList.remove('active');
}

// Show alert message
function showAlert(message, type = 'success') {
    adminAlert.className = `admin-alert admin-alert-${type}`;
    adminAlert.textContent = message;
    adminAlert.style.display = 'block';
    
    setTimeout(() => {
        adminAlert.style.display = 'none';
    }, 5000);
}

// Tab switching
tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        const tabName = tab.getAttribute('data-tab');
        
        // Remove active class from all tabs and panes
        tabs.forEach(t => t.classList.remove('active'));
        tabPanes.forEach(p => p.classList.remove('active'));
        
        // Add active class to clicked tab and corresponding pane
        tab.classList.add('active');
        document.getElementById(`tab-${tabName}`).classList.add('active');
        
        // Load data for specific tabs
        if (tabName === 'users') {
            loadUsers();
        } else if (tabName === 'configuration') {
            // Initialize admin map when configuration tab is opened
            setTimeout(() => {
                initializeAdminMap();
            }, 100);
        }
    });
});

// Close modal on overlay click
modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) {
        closeAdminModal();
    }
});

// Close modal on close button click
modalClose.addEventListener('click', closeAdminModal);

// Load settings from API
async function loadSettings() {
    try {
        const response = await fetch('/api/admin/settings', {
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Populate Configuration settings
            document.getElementById('config-app-name').value = data.data.app_name || 'Family Tracker';
            document.getElementById('config-map-lat').value = data.data.map_center_lat || '21.4225';
            document.getElementById('config-map-lng').value = data.data.map_center_lng || '39.8262';
            
            // Populate SMTP settings
            document.getElementById('smtp-host').value = data.data.smtp_host || '';
            document.getElementById('smtp-port').value = data.data.smtp_port || '';
            document.getElementById('smtp-encryption').value = data.data.smtp_encryption || 'ssl';
            document.getElementById('smtp-username').value = data.data.smtp_username || '';
            document.getElementById('smtp-password').value = data.data.smtp_password || '';
            
            // Populate Infobip settings
            document.getElementById('infobip-api-key').value = data.data.infobip_api_key || '';
            document.getElementById('infobip-base-url').value = data.data.infobip_base_url || '';
            document.getElementById('infobip-sender-number').value = data.data.infobip_sender_number || '';
        }
    } catch (error) {
        console.error('Failed to load settings:', error);
        showAlert('Failed to load settings', 'error');
    }
}

// Save email settings
document.getElementById('save-email').addEventListener('click', async () => {
    const button = document.getElementById('save-email');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="admin-spinner"></span> Saving...';
    
    try {
        const response = await fetch('/api/admin/settings', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                smtp_host: document.getElementById('smtp-host').value,
                smtp_port: document.getElementById('smtp-port').value,
                smtp_encryption: document.getElementById('smtp-encryption').value,
                smtp_username: document.getElementById('smtp-username').value,
                smtp_password: document.getElementById('smtp-password').value
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Email settings saved successfully', 'success');
        } else {
            showAlert(data.message || 'Failed to save email settings', 'error');
        }
    } catch (error) {
        console.error('Failed to save email settings:', error);
        showAlert('Failed to save email settings', 'error');
    } finally {
        button.disabled = false;
        button.innerHTML = originalText;
    }
});

// Test email
document.getElementById('test-email').addEventListener('click', async () => {
    const button = document.getElementById('test-email');
    const originalText = button.innerHTML;
    const email = document.getElementById('test-email-address').value;
    
    if (!email) {
        showAlert('Please enter a test email address', 'error');
        return;
    }
    
    button.disabled = true;
    button.innerHTML = '<span class="admin-spinner"></span> Sending...';
    
    try {
        const response = await fetch('/api/admin/test-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Test email sent successfully', 'success');
        } else {
            showAlert(data.message || 'Failed to send test email', 'error');
        }
    } catch (error) {
        console.error('Failed to send test email:', error);
        showAlert('Failed to send test email', 'error');
    } finally {
        button.disabled = false;
        button.innerHTML = originalText;
    }
});

// Save SMS settings
document.getElementById('save-sms').addEventListener('click', async () => {
    const button = document.getElementById('save-sms');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="admin-spinner"></span> Saving...';
    
    try {
        const response = await fetch('/api/admin/settings', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                infobip_api_key: document.getElementById('infobip-api-key').value,
                infobip_base_url: document.getElementById('infobip-base-url').value,
                infobip_sender_number: document.getElementById('infobip-sender-number').value
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('SMS settings saved successfully', 'success');
        } else {
            showAlert(data.message || 'Failed to save SMS settings', 'error');
        }
    } catch (error) {
        console.error('Failed to save SMS settings:', error);
        showAlert('Failed to save SMS settings', 'error');
    } finally {
        button.disabled = false;
        button.innerHTML = originalText;
    }
});

// Test SMS
document.getElementById('test-sms').addEventListener('click', async () => {
    const button = document.getElementById('test-sms');
    const originalText = button.innerHTML;
    const phoneNumber = document.getElementById('test-phone-number').value;
    
    if (!phoneNumber) {
        showAlert('Please enter a test phone number', 'error');
        return;
    }
    
    button.disabled = true;
    button.innerHTML = '<span class="admin-spinner"></span> Sending...';
    
    try {
        const response = await fetch('/api/admin/test-sms', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ phone_number: phoneNumber })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(data.message, 'info');
        } else {
            showAlert(data.message || 'Failed to send test SMS', 'error');
        }
    } catch (error) {
        console.error('Failed to send test SMS:', error);
        showAlert('Failed to send test SMS', 'error');
    } finally {
        button.disabled = false;
        button.innerHTML = originalText;
    }
});

// Load users
async function loadUsers(page = 1) {
    currentPage = page;
    const tableBody = document.getElementById('users-table-body');
    tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #7f8c8d;">Loading users...</td></tr>';
    
    try {
        const params = new URLSearchParams({
            page: page,
            search: currentSearch,
            status: currentFilter
        });
        
        const response = await fetch(`/api/admin/users?${params}`, {
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.data.users.length > 0) {
            tableBody.innerHTML = data.data.users.map(user => `
                <tr>
                    <td>${user.username}</td>
                    <td>${user.email || '-'}</td>
                    <td>${user.phone_number || '-'}</td>
                    <td>${user.devices_count || 0}/10</td>
                    <td>
                        ${user.license_key ? `
                            <span style="font-size: 11px; color: #6c757d; font-family: monospace;" title="${user.license_key}">
                                ${user.license_key}
                            </span>
                        ` : `
                            <span style="color: #dc3545; font-size: 11px;">No License</span>
                        `}
                    </td>
                    <td>
                        <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                            ${!user.approved ? `
                                <span style="padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600; background: #f8d7da; color: #721c24;">
                                    Pending Approval
                                </span>
                            ` : user.suspended ? `
                                <span style="padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600; background: #f8d7da; color: #721c24;">
                                    Suspended
                                </span>
                            ` : `
                                <span style="padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600; background: #d4edda; color: #155724;">
                                    Active
                                </span>
                            `}
                        </div>
                    </td>
                    <td>${new Date(user.created_at).toLocaleDateString()}</td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                            ${!user.license_key ? `
                                <span class="material-symbols-outlined" onclick="generateLicenseKey(${user.id})" 
                                      style="font-size: 18px; color: #28a745; cursor: pointer; padding: 2px; border-radius: 3px; transition: background 0.2s;"
                                      onmouseover="this.style.background='rgba(40,167,69,0.1)'" onmouseout="this.style.background='transparent'"
                                      title="Generate License Key">
                                    vpn_key_alert
                                </span>
                            ` : ''}
                            
                            ${!user.approved ? `
                                <span class="material-symbols-outlined" onclick="approveUser(${user.id})" 
                                      style="font-size: 18px; color: #17a2b8; cursor: pointer; padding: 2px; border-radius: 3px; transition: background 0.2s;"
                                      onmouseover="this.style.background='rgba(23,162,184,0.1)'" onmouseout="this.style.background='transparent'"
                                      title="Approve User">
                                    check_circle
                                </span>
                            ` : `
                                <span class="material-symbols-outlined" onclick="unapproveUser(${user.id})" 
                                      style="font-size: 18px; color: #6c757d; cursor: pointer; padding: 2px; border-radius: 3px; transition: background 0.2s;"
                                      onmouseover="this.style.background='rgba(108,117,125,0.1)'" onmouseout="this.style.background='transparent'"
                                      title="Unapprove User">
                                    cancel
                                </span>
                            `}
                            
                            ${user.approved && !user.suspended ? `
                                <span class="material-symbols-outlined" onclick="suspendUser(${user.id})" 
                                      style="font-size: 18px; color: #ffc107; cursor: pointer; padding: 2px; border-radius: 3px; transition: background 0.2s;"
                                      onmouseover="this.style.background='rgba(255,193,7,0.1)'" onmouseout="this.style.background='transparent'"
                                      title="Suspend User">
                                    pause_circle
                                </span>
                            ` : ''}
                            
                            ${user.approved && user.suspended ? `
                                <span class="material-symbols-outlined" onclick="unsuspendUser(${user.id})" 
                                      style="font-size: 18px; color: #17a2b8; cursor: pointer; padding: 2px; border-radius: 3px; transition: background 0.2s;"
                                      onmouseover="this.style.background='rgba(23,162,184,0.1)'" onmouseout="this.style.background='transparent'"
                                      title="Unsuspend User">
                                    play_circle
                                </span>
                            ` : ''}
                            
                            ${user.id !== 1 ? `
                                <span class="material-symbols-outlined" onclick="deleteUser(${user.id})" 
                                      style="font-size: 18px; color: #dc3545; cursor: pointer; padding: 2px; border-radius: 3px; transition: background 0.2s;"
                                      onmouseover="this.style.background='rgba(220,53,69,0.1)'" onmouseout="this.style.background='transparent'"
                                      title="Delete User">
                                    delete
                                </span>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
            
            // Update pagination
            updatePagination(data.data.pagination);
        } else {
            tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #7f8c8d;">No users found</td></tr>';
        }
    } catch (error) {
        console.error('Failed to load users:', error);
        tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #e74c3c;">Failed to load users</td></tr>';
    }
}

// Update pagination
function updatePagination(pagination) {
    document.getElementById('users-page-info').textContent = `Page ${pagination.current_page} of ${pagination.last_page}`;
    document.getElementById('users-prev').disabled = pagination.current_page === 1;
    document.getElementById('users-next').disabled = pagination.current_page === pagination.last_page;
}

// Search users
document.getElementById('search-users').addEventListener('click', () => {
    currentSearch = document.getElementById('user-search').value;
    currentFilter = document.getElementById('user-filter').value;
    loadUsers(1);
});

// Pagination buttons
document.getElementById('users-prev').addEventListener('click', () => {
    if (currentPage > 1) {
        loadUsers(currentPage - 1);
    }
});

document.getElementById('users-next').addEventListener('click', () => {
    loadUsers(currentPage + 1);
});

// Save configuration
document.getElementById('save-configuration').addEventListener('click', async () => {
    const button = document.getElementById('save-configuration');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="admin-spinner"></span> Saving...';
    
    try {
        const response = await fetch('/api/admin/settings', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                app_name: document.getElementById('config-app-name').value,
                map_center_lat: document.getElementById('config-map-lat').value,
                map_center_lng: document.getElementById('config-map-lng').value
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Configuration saved successfully', 'success');
        } else {
            showAlert(data.message || 'Failed to save configuration', 'error');
        }
    } catch (error) {
        console.error('Failed to save configuration:', error);
        showAlert('Failed to save configuration', 'error');
    } finally {
        button.disabled = false;
        button.innerHTML = originalText;
    }
});

// Export function to open modal globally
window.openAdminModal = openAdminModal;

// User Management Functions
async function generateLicenseKey(userId) {
    if (!confirm('Generate new license key for this user?')) return;
    
    try {
        const response = await fetch(`/api/admin/users/${userId}/generate-license`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('License key generated successfully', 'success');
            loadUsers(currentPage);
        } else {
            showAlert(data.message || 'Failed to generate license key', 'error');
        }
    } catch (error) {
        console.error('Failed to generate license key:', error);
        showAlert('Failed to generate license key', 'error');
    }
}

async function approveUser(userId) {
    if (!confirm('Approve this user? They will be able to login after approval.')) return;
    
    try {
        const response = await fetch(`/api/admin/users/${userId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('User approved successfully', 'success');
            loadUsers(currentPage);
        } else {
            showAlert(data.message || 'Failed to approve user', 'error');
        }
    } catch (error) {
        console.error('Failed to approve user:', error);
        showAlert('Failed to approve user', 'error');
    }
}

async function unapproveUser(userId) {
    if (!confirm('Unapprove this user? They will not be able to login until re-approved.')) return;
    
    try {
        const response = await fetch(`/api/admin/users/${userId}/unapprove`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('User unapproved successfully', 'success');
            loadUsers(currentPage);
        } else {
            showAlert(data.message || 'Failed to unapprove user', 'error');
        }
    } catch (error) {
        console.error('Failed to unapprove user:', error);
        showAlert('Failed to unapprove user', 'error');
    }
}

async function suspendUser(userId) {
    const reason = prompt('Enter suspension reason (optional):');
    if (reason === null) return; // User cancelled
    
    try {
        const response = await fetch(`/api/admin/users/${userId}/suspend`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ reason: reason || null })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('User suspended successfully', 'success');
            loadUsers(currentPage);
        } else {
            showAlert(data.message || 'Failed to suspend user', 'error');
        }
    } catch (error) {
        console.error('Failed to suspend user:', error);
        showAlert('Failed to suspend user', 'error');
    }
}

async function unsuspendUser(userId) {
    if (!confirm('Unsuspend this user? They will be able to login again.')) return;
    
    try {
        const response = await fetch(`/api/admin/users/${userId}/unsuspend`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('User unsuspended successfully', 'success');
            loadUsers(currentPage);
        } else {
            showAlert(data.message || 'Failed to unsuspend user', 'error');
        }
    } catch (error) {
        console.error('Failed to unsuspend user:', error);
        showAlert('Failed to unsuspend user', 'error');
    }
}

async function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;
    
    try {
        const response = await fetch(`/api/admin/users/${userId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('User deleted successfully', 'success');
            loadUsers(currentPage);
        } else {
            showAlert(data.message || 'Failed to delete user', 'error');
        }
    } catch (error) {
        console.error('Failed to delete user:', error);
        showAlert('Failed to delete user', 'error');
    }
}

// Initialize admin map for configuration
function initializeAdminMap() {
    if (adminMap) {
        adminMap.remove();
    }
    
    const lat = parseFloat(document.getElementById('config-map-lat').value) || 21.4225;
    const lng = parseFloat(document.getElementById('config-map-lng').value) || 39.8262;
    
    // Initialize map
    adminMap = L.map('admin-map').setView([lat, lng], 13);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(adminMap);
    
    // Add marker
    adminMapMarker = L.marker([lat, lng], {
        draggable: true
    }).addTo(adminMap);
    
    // Handle map click
    adminMap.on('click', function(e) {
        const newLat = e.latlng.lat.toFixed(6);
        const newLng = e.latlng.lng.toFixed(6);
        
        // Update marker position
        adminMapMarker.setLatLng([newLat, newLng]);
        
        // Update input fields
        document.getElementById('config-map-lat').value = newLat;
        document.getElementById('config-map-lng').value = newLng;
    });
    
    // Handle marker drag
    adminMapMarker.on('dragend', function(e) {
        const newLat = e.target.getLatLng().lat.toFixed(6);
        const newLng = e.target.getLatLng().lng.toFixed(6);
        
        // Update input fields
        document.getElementById('config-map-lat').value = newLat;
        document.getElementById('config-map-lng').value = newLng;
    });
}

// Update admin map when coordinates change
function updateAdminMapFromInputs() {
    if (adminMap && adminMapMarker) {
        const lat = parseFloat(document.getElementById('config-map-lat').value) || 21.4225;
        const lng = parseFloat(document.getElementById('config-map-lng').value) || 39.8262;
        
        adminMap.setView([lat, lng], 13);
        adminMapMarker.setLatLng([lat, lng]);
    }
}

// Export functions to global scope for onclick handlers
window.generateLicenseKey = generateLicenseKey;
window.approveUser = approveUser;
window.unapproveUser = unapproveUser;
window.suspendUser = suspendUser;
window.unsuspendUser = unsuspendUser;
window.deleteUser = deleteUser;

}); // End DOMContentLoaded
