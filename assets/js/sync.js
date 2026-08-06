/**
 * Smart Water Guardian - Complete Sync Helper
 * Syncs ALL data between Firebase and MySQL
 */

const SyncManager = {
    /**
     * Sync ALL user data from Firebase to MySQL
     */
    async syncAllUserData(uid) {
        console.log('🔄 Syncing all data for user:', uid);
        
        const results = {
            user: false,
            alerts: false,
            thresholds: false,
            billing: false,
            messages: false,
            reviews: false
        };
        
        try {
            // Sync user profile
            results.user = await this.syncUser(uid);
            
            // Sync alerts
            results.alerts = await this.syncAlerts(uid);
            
            // Sync thresholds
            results.thresholds = await this.syncThresholds(uid);
            
            // Sync billing
            results.billing = await this.syncBilling(uid);
            
            // Sync messages
            results.messages = await this.syncMessages(uid);
            
            // Sync reviews
            results.reviews = await this.syncReviews();
            
            console.log('✅ Sync complete:', results);
            return results;
            
        } catch (error) {
            console.error('❌ Sync error:', error);
            return results;
        }
    },
    
    /**
     * Sync user profile
     */
    async syncUser(uid) {
        try {
            const snapshot = await database.ref('users/' + uid).once('value');
            const userData = snapshot.val();
            
            if (!userData) return false;
            
            const response = await fetch('../api/sync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'sync_user',
                    firebase_uid: uid,
                    user_data: userData
                })
            });
            
            const result = await response.json();
            console.log('✅ User synced:', result.message);
            return result.success;
            
        } catch (error) {
            console.error('❌ User sync error:', error);
            return false;
        }
    },
    
    /**
     * Sync alerts
     */
    async syncAlerts(uid) {
        try {
            const snapshot = await database.ref('alerts/' + uid).once('value');
            const alerts = snapshot.val();
            
            if (!alerts) return true;
            
            let synced = 0;
            for (let key in alerts) {
                const alert = alerts[key];
                const response = await fetch('../api/sync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_alert',
                        firebase_uid: uid,
                        alert_type: alert.type || 'system',
                        severity: alert.severity || 'info',
                        title: alert.title || '',
                        message: alert.message || '',
                        firebase_key: key
                    })
                });
                const result = await response.json();
                if (result.success) synced++;
            }
            
            console.log(`✅ Synced ${synced} alerts`);
            return true;
            
        } catch (error) {
            console.error('❌ Alert sync error:', error);
            return false;
        }
    },
    
    /**
     * Sync thresholds
     */
    async syncThresholds(uid) {
        try {
            const snapshot = await database.ref('thresholds/' + uid).once('value');
            const thresholds = snapshot.val();
            
            if (!thresholds) return true;
            
            let synced = 0;
            for (let key in thresholds) {
                const threshold = thresholds[key];
                const response = await fetch('../api/sync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_threshold',
                        firebase_uid: uid,
                        threshold_type: threshold.thresholdType || 'daily_limit',
                        threshold_value: threshold.thresholdValue || 1000,
                        is_active: threshold.isActive !== false ? 1 : 0,
                        firebase_key: key
                    })
                });
                const result = await response.json();
                if (result.success) synced++;
            }
            
            console.log(`✅ Synced ${synced} thresholds`);
            return true;
            
        } catch (error) {
            console.error('❌ Threshold sync error:', error);
            return false;
        }
    },
    
    /**
     * Sync billing
     */
    async syncBilling(uid) {
        try {
            const snapshot = await database.ref('bills/' + uid).once('value');
            const bills = snapshot.val();
            
            if (!bills) return true;
            
            let synced = 0;
            for (let key in bills) {
                const bill = bills[key];
                const response = await fetch('../api/sync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_bill',
                        firebase_uid: uid,
                        month: bill.month || new Date().toISOString().slice(0, 7) + '-01',
                        amount: bill.amount || 0,
                        volume: bill.usage || 0,
                        firebase_key: key
                    })
                });
                const result = await response.json();
                if (result.success) synced++;
            }
            
            console.log(`✅ Synced ${synced} bills`);
            return true;
            
        } catch (error) {
            console.error('❌ Billing sync error:', error);
            return false;
        }
    },
    
    /**
     * Sync messages
     */
    async syncMessages(uid) {
        try {
            const snapshot = await database.ref('messages/' + uid).once('value');
            const messages = snapshot.val();
            
            if (!messages) return true;
            
            let synced = 0;
            for (let key in messages) {
                const msg = messages[key];
                const response = await fetch('../api/sync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'send_message',
                        firebase_uid: uid,
                        from_uid: msg.fromUid || 'admin',
                        from_name: msg.fromName || 'Admin',
                        from_email: msg.from || 'admin@smartwater.com',
                        subject: msg.subject || 'Message',
                        message: msg.message || '',
                        firebase_key: key
                    })
                });
                const result = await response.json();
                if (result.success) synced++;
            }
            
            console.log(`✅ Synced ${synced} messages`);
            return true;
            
        } catch (error) {
            console.error('❌ Message sync error:', error);
            return false;
        }
    },
    
    /**
     * Sync reviews (global)
     */
    async syncReviews() {
        try {
            const snapshot = await database.ref('reviews').once('value');
            const reviews = snapshot.val();
            
            if (!reviews) return true;
            
            let synced = 0;
            for (let key in reviews) {
                const review = reviews[key];
                const response = await fetch('../api/sync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_review',
                        firebase_uid: review.userId || '',
                        user_name: review.userName || 'Anonymous',
                        rating: review.rating || 5,
                        title: review.title || '',
                        comment: review.comment || '',
                        firebase_key: key
                    })
                });
                const result = await response.json();
                if (result.success) synced++;
            }
            
            console.log(`✅ Synced ${synced} reviews`);
            return true;
            
        } catch (error) {
            console.error('❌ Review sync error:', error);
            return false;
        }
    },
    
    /**
     * Force sync ALL data from Firebase to MySQL
     */
    async forceSyncAll(uid) {
        console.log('🔄 Force syncing all data...');
        
        try {
            const response = await fetch('../api/sync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'force_sync_all',
                    firebase_uid: uid
                })
            });
            
            const result = await response.json();
            if (result.success) {
                console.log('✅ Force sync complete:', result.results);
                return result.results;
            } else {
                throw new Error(result.error);
            }
            
        } catch (error) {
            console.error('❌ Force sync error:', error);
            return null;
        }
    },
    
    /**
     * Get sync status from MySQL
     */
    async getSyncStatus(uid) {
        try {
            const response = await fetch(
                '../api/sync.php?action=get_sync_status&firebase_uid=' + uid
            );
            const result = await response.json();
            
            if (result.success) {
                return result.status;
            } else {
                console.error('❌ Get sync status error:', result.error);
                return null;
            }
            
        } catch (error) {
            console.error('❌ Sync status error:', error);
            return null;
        }
    }
};

// Export for use
window.SyncManager = SyncManager;
console.log('🔄 Sync Manager loaded!');