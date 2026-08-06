/**
 * Smart Water Guardian - Main JavaScript File
 * Contains global functions, utilities, and common functionality
 */

// ============================
// NAVIGATION TOGGLE (Mobile)
// ============================
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        });
    }

    // Mobile sidebar toggle
    const sidebarToggle = document.createElement('button');
    sidebarToggle.className = 'sidebar-toggle';
    sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
    sidebarToggle.setAttribute('aria-label', 'Toggle sidebar');
    
    const topbar = document.querySelector('.topbar');
    if (topbar && window.innerWidth <= 992) {
        const topbarLeft = topbar.querySelector('.topbar-left');
        if (topbarLeft) {
            topbarLeft.prepend(sidebarToggle);
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
        });
    }

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.sidebar-toggle');
        if (window.innerWidth <= 992 && sidebar && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && !toggle?.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
});

// ============================
// LOGOUT FUNCTION
// ============================
function logoutUser() {
    if (confirm('👋 Are you sure you want to logout?')) {
        auth.signOut()
            .then(() => {
                // Clear session via PHP
                return fetch('../api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                });
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '../pages/login.php';
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                // Force redirect even if API fails
                window.location.href = '../pages/login.php';
            });
    }
}

// ============================
// PASSWORD VALIDATION
// ============================
function validatePassword(password) {
    const errors = [];
    
    if (password.length < 6) {
        errors.push('Password must be at least 6 characters long');
    }
    if (!/[A-Z]/.test(password)) {
        errors.push('Password must contain at least one uppercase letter');
    }
    if (!/[a-z]/.test(password)) {
        errors.push('Password must contain at least one lowercase letter');
    }
    if (!/[0-9]/.test(password)) {
        errors.push('Password must contain at least one number');
    }
    if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
        errors.push('Password must contain at least one special character');
    }
    
    return {
        valid: errors.length === 0,
        errors: errors
    };
}

// ============================
// PASSWORD STRENGTH INDICATOR
// ============================
function getPasswordStrength(password) {
    let strength = 0;
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;
    return strength;
}

function getPasswordStrengthText(strength) {
    const levels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    return levels[strength - 1] || 'Very Weak';
}

function getPasswordStrengthColor(strength) {
    const colors = ['#e53e3e', '#ed8936', '#ecc94b', '#48bb78', '#38a169'];
    return colors[strength - 1] || '#e2e8f0';
}

// ============================
// FORMATTING UTILITIES
// ============================
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-ZA', {
        style: 'currency',
        currency: 'ZAR'
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-ZA', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-ZA', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatDateTime(dateString) {
    return formatDate(dateString) + ' at ' + formatTime(dateString);
}

function formatNumber(num, decimals = 1) {
    return Number(num).toFixed(decimals);
}

function formatVolume(liters) {
    if (liters >= 1000) {
        return (liters / 1000).toFixed(2) + ' kL';
    }
    return liters.toFixed(0) + ' L';
}

// ============================
// VALIDATION UTILITIES
// ============================
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[0-9]{10}$/;
    return re.test(phone.replace(/[^0-9]/g, ''));
}

function sanitizeInput(input) {
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
}

// ============================
// ALERT UTILITIES
// ============================
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = message;
    
    // Check if there's an alert container
    let container = document.querySelector('.alert-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'alert-container';
        const main = document.querySelector('main') || document.body;
        main.prepend(container);
    }
    
    container.appendChild(alertDiv);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => {
            alertDiv.remove();
        }, 300);
    }, 5000);
}

function showError(message) {
    showAlert(message, 'danger');
}

function showSuccess(message) {
    showAlert(message, 'success');
}

// ============================
// LOADING UTILITIES
// ============================
function showLoading(elementId) {
    const el = document.getElementById(elementId);
    if (el) {
        el.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    }
}

function hideLoading(elementId) {
    const el = document.getElementById(elementId);
    if (el) {
        el.innerHTML = '';
    }
}

// ============================
// TOAST NOTIFICATION
// ============================
function showToast(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icons = {
        info: 'ℹ️',
        success: '✅',
        warning: '⚠️',
        error: '❌'
    };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || 'ℹ️'}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close">&times;</button>
    `;
    
    document.body.appendChild(toast);
    
    // Show with animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Auto close
    const timer = setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, duration);
    
    // Close button
    toast.querySelector('.toast-close').addEventListener('click', function() {
        clearTimeout(timer);
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    });
}

// ============================
// CHECK AUTH STATUS
// ============================
function checkAuth() {
    return new Promise((resolve) => {
        auth.onAuthStateChanged((user) => {
            resolve(user);
        });
    });
}

function getCurrentUser() {
    return auth.currentUser;
}

function getUserData() {
    return new Promise((resolve, reject) => {
        const user = auth.currentUser;
        if (!user) {
            reject(new Error('No user logged in'));
            return;
        }
        
        const userRef = database.ref('users/' + user.uid);
        userRef.once('value')
            .then((snapshot) => {
                resolve(snapshot.val());
            })
            .catch(reject);
    });
}

// ============================
// WATER USAGE FUNCTIONS
// ============================
function getRealtimeUsage(userId) {
    return new Promise((resolve, reject) => {
        if (!userId) {
            reject(new Error('User ID required'));
            return;
        }
        
        // First get the user's meter ID
        const propertiesRef = database.ref('properties/' + userId);
        propertiesRef.once('value')
            .then((snapshot) => {
                const properties = snapshot.val();
                if (!properties) {
                    resolve(null);
                    return;
                }
                
                const firstProperty = Object.values(properties)[0];
                if (!firstProperty || !firstProperty.meterId) {
                    resolve(null);
                    return;
                }
                
                // Get the meter reading
                const meterRef = database.ref('meters/' + firstProperty.meterId + '/lastReading');
                return meterRef.once('value');
            })
            .then((snapshot) => {
                resolve(snapshot.val());
            })
            .catch(reject);
    });
}

function getUsageHistory(meterId, days = 7) {
    return new Promise((resolve, reject) => {
        if (!meterId) {
            reject(new Error('Meter ID required'));
            return;
        }
        
        const historyRef = database.ref('meters/' + meterId + '/history');
        historyRef.once('value')
            .then((snapshot) => {
                const data = snapshot.val();
                const result = [];
                const today = new Date();
                
                if (data) {
                    for (let i = days - 1; i >= 0; i--) {
                        const date = new Date(today);
                        date.setDate(date.getDate() - i);
                        const dateStr = date.toISOString().split('T')[0];
                        
                        if (data[dateStr] && data[dateStr].hourly) {
                            const total = Object.values(data[dateStr].hourly).reduce((a, b) => a + b, 0);
                            result.push({
                                date: dateStr,
                                total: total
                            });
                        } else {
                            result.push({
                                date: dateStr,
                                total: 0
                            });
                        }
                    }
                }
                
                resolve(result);
            })
            .catch(reject);
    });
}

function simulateMeterData(meterId, userId) {
    return new Promise((resolve, reject) => {
        if (!meterId) {
            reject(new Error('Meter ID required'));
            return;
        }
        
        const reading = {
            flow: (Math.random() * 25 + 1),
            volume: (Math.random() * 150 + 30),
            timestamp: new Date().toISOString(),
            battery: 75 + Math.random() * 20,
            status: 'online'
        };
        
        // Save to Firebase
        const updates = {};
        updates['meters/' + meterId + '/lastReading'] = reading;
        
        // Save to history
        const today = new Date().toISOString().split('T')[0];
        const hour = new Date().getHours();
        updates['meters/' + meterId + '/history/' + today + '/hourly/' + hour] = reading.volume;
        
        if (userId) {
            updates['realtime_usage/user_' + userId] = {
                current: reading.flow,
                today: reading.volume,
                updated_at: reading.timestamp
            };
        }
        
        database.ref().update(updates)
            .then(() => {
                resolve(reading);
            })
            .catch(reject);
    });
}

// ============================
// EXPOSE GLOBALLY
// ============================
window.logoutUser = logoutUser;
window.validatePassword = validatePassword;
window.getPasswordStrength = getPasswordStrength;
window.getPasswordStrengthText = getPasswordStrengthText;
window.getPasswordStrengthColor = getPasswordStrengthColor;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.formatTime = formatTime;
window.formatDateTime = formatDateTime;
window.formatNumber = formatNumber;
window.formatVolume = formatVolume;
window.validateEmail = validateEmail;
window.validatePhone = validatePhone;
window.sanitizeInput = sanitizeInput;
window.showAlert = showAlert;
window.showError = showError;
window.showSuccess = showSuccess;
window.showToast = showToast;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.checkAuth = checkAuth;
window.getCurrentUser = getCurrentUser;
window.getUserData = getUserData;
window.getRealtimeUsage = getRealtimeUsage;
window.getUsageHistory = getUsageHistory;
window.simulateMeterData = simulateMeterData;

console.log('📦 Smart Water Guardian main.js loaded successfully!');
console.log('💡 Available functions:', Object.keys(window).filter(key => 
    typeof window[key] === 'function' && 
    ['validatePassword', 'formatCurrency', 'showToast', 'simulateMeterData'].includes(key)
));