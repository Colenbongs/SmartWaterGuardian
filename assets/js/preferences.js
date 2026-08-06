/**
 * Smart Water Guardian - User Preferences Manager
 * Handles theme, notifications, and user settings
 */

const Preferences = {
    // ============================================================
    // GET / SET PREFERENCES
    // ============================================================
    get: function(key, defaultValue = null) {
        const value = localStorage.getItem('swg_pref_' + key);
        return value !== null ? JSON.parse(value) : defaultValue;
    },
    set: function(key, value) {
        localStorage.setItem('swg_pref_' + key, JSON.stringify(value));
        return this;
    },
    
    // ============================================================
    // THEME
    // ============================================================
    theme: {
        get: function() { return Preferences.get('theme', 'dark'); },
        set: function(theme) {
            Preferences.set('theme', theme);
            document.documentElement.setAttribute('data-theme', theme);
            if (theme === 'light') {
                document.body.classList.add('light-mode');
            } else {
                document.body.classList.remove('light-mode');
            }
            // Save to Firebase if user is logged in
            if (window.currentUser && window.database) {
                window.database.ref('users/' + window.currentUser.uid + '/preferences/theme').set(theme);
            }
            return this;
        },
        toggle: function() {
            const current = this.get();
            return this.set(current === 'dark' ? 'light' : 'dark');
        },
        getIcon: function() {
            return this.get() === 'dark' ? '🌙' : '☀️';
        },
        getLabel: function() {
            return this.get() === 'dark' ? 'Dark Mode' : 'Light Mode';
        }
    },
    
    // ============================================================
    // NOTIFICATIONS
    // ============================================================
    notifications: {
        get: function() { 
            return Preferences.get('notifications', { 
                email: true, 
                push: true, 
                sms: false,
                alerts: true,
                billing: true,
                marketing: false
            }); 
        },
        set: function(settings) {
            Preferences.set('notifications', settings);
            if (window.currentUser && window.database) {
                window.database.ref('users/' + window.currentUser.uid + '/preferences/notifications').set(settings);
            }
            return this;
        },
        toggle: function(type) {
            const current = this.get();
            if (current.hasOwnProperty(type)) {
                current[type] = !current[type];
                this.set(current);
                return current[type];
            }
            return false;
        },
        isEnabled: function(type) {
            const current = this.get();
            return current[type] || false;
        }
    },
    
    // ============================================================
    // LANGUAGE
    // ============================================================
    language: {
        get: function() { return Preferences.get('language', 'en'); },
        set: function(lang) {
            Preferences.set('language', lang);
            if (window.currentUser && window.database) {
                window.database.ref('users/' + window.currentUser.uid + '/preferences/language').set(lang);
            }
            document.documentElement.lang = lang;
            return this;
        }
    },
    
    // ============================================================
    // LOAD FROM FIREBASE
    // ============================================================
    loadFromFirebase: function(uid) {
        if (!uid || !window.database) return;
        
        // Load theme
        window.database.ref('users/' + uid + '/preferences/theme').once('value')
            .then(snapshot => {
                const theme = snapshot.val();
                if (theme) {
                    this.theme.set(theme);
                }
            });
        
        // Load notifications
        window.database.ref('users/' + uid + '/preferences/notifications').once('value')
            .then(snapshot => {
                const notif = snapshot.val();
                if (notif) {
                    this.notifications.set(notif);
                }
            });
        
        // Load language
        window.database.ref('users/' + uid + '/preferences/language').once('value')
            .then(snapshot => {
                const lang = snapshot.val();
                if (lang) {
                    this.language.set(lang);
                }
            });
    }
};

// ============================================================
// THEME TOGGLE BUTTON
// ============================================================
function toggleTheme() {
    Preferences.theme.toggle();
    const icon = Preferences.theme.getIcon();
    const label = Preferences.theme.getLabel();
    showToast(`${icon} ${label}`, 'info');
}

// ============================================================
// THEME CSS
// ============================================================
// Add this to your main CSS file
/*
:root {
    --bg-primary: #05080f;
    --bg-card: rgba(4,8,18,0.96);
    --bg-input: rgba(127,201,255,0.02);
    --text-primary: #ffffff;
    --text-secondary: rgba(127,201,255,0.4);
    --text-body: #b8e6ff;
    --border-color: rgba(127,201,255,0.06);
    --shadow-color: rgba(0,0,0,0.3);
}

.light-mode {
    --bg-primary: #f0f4f8;
    --bg-card: rgba(255,255,255,0.95);
    --bg-input: #f7fafc;
    --text-primary: #1a365d;
    --text-secondary: #4a5568;
    --text-body: #2d3748;
    --border-color: #e2e8f0;
    --shadow-color: rgba(0,0,0,0.05);
}
*/

// ============================================================
// INITIALIZE
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Apply saved theme
    const theme = Preferences.theme.get();
    if (theme === 'light') {
        document.body.classList.add('light-mode');
        document.documentElement.setAttribute('data-theme', 'light');
    }
    
    // Add theme toggle listener
    document.querySelectorAll('[data-theme-toggle]').forEach(el => {
        el.addEventListener('click', toggleTheme);
    });
    
    console.log('🌓 Preferences loaded! Theme:', Preferences.theme.get());
});

// Make global
window.Preferences = Preferences;
window.toggleTheme = toggleTheme;

console.log('⚙️ Preferences module loaded!');