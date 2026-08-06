/**
 * Smart Water Guardian - Activity Logger
 * Logs user activities to MySQL
 */

const ActivityLogger = {
    /**
     * Log an activity
     * @param {string} action - The action name (e.g., 'login', 'profile_update')
     * @param {object} details - Additional details about the action
     */
    log: function(action, details = {}) {
        // Check if user is logged in
        if (!window.currentUser) {
            console.warn('⚠️ Cannot log activity: No user logged in');
            return;
        }
        
        // Don't log in development if disabled
        if (window.DISABLE_ACTIVITY_LOGGING) {
            return;
        }
        
        // Send to API
        fetch('../api/log-activity.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                firebase_uid: window.currentUser.uid,
                action: action,
                details: details
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.warn('⚠️ Activity log failed:', data.error);
            }
        })
        .catch(error => {
            console.warn('⚠️ Activity log error:', error);
        });
    },
    
    /**
     * Log common actions
     */
    login: function(method = 'email_password') {
        this.log('login', { method: method, success: true });
    },
    
    logout: function() {
        this.log('logout', { method: 'user_initiated' });
    },
    
    profileUpdate: function(fields = []) {
        this.log('profile_update', { fields: fields });
    },
    
    passwordChange: function() {
        this.log('password_change', { method: 'user_initiated' });
    },
    
    billPaid: function(billId, amount) {
        this.log('bill_paid', { bill_id: billId, amount: amount });
    },
    
    deviceRegistered: function(meterId) {
        this.log('device_registered', { meter_id: meterId });
    },
    
    propertyAdded: function(propertyId) {
        this.log('property_added', { property_id: propertyId });
    },
    
    alertRead: function(alertId) {
        this.log('alert_read', { alert_id: alertId });
    },
    
    thresholdUpdated: function(thresholdType, value) {
        this.log('threshold_updated', { type: thresholdType, value: value });
    },
    
    reviewSubmitted: function(rating) {
        this.log('review_submitted', { rating: rating });
    },
    
    messageSent: function(recipient) {
        this.log('message_sent', { recipient: recipient });
    },
    
    twoFAEnabled: function() {
        this.log('2fa_enabled', { method: 'google_authenticator' });
    },
    
    twoFADisabled: function() {
        this.log('2fa_disabled', { method: 'google_authenticator' });
    }
};

// Make global
window.ActivityLogger = ActivityLogger;

console.log('📋 Activity Logger loaded!');

// ============================================================
// AUTO-LOG PAGE VIEWS
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Log page view after user is loaded
    const checkUser = setInterval(() => {
        if (window.currentUser) {
            clearInterval(checkUser);
            const page = window.location.pathname.split('/').pop();
            ActivityLogger.log('page_view', { page: page });
        }
    }, 500);
});