/**
 * Smart Water Guardian - Login Warning System
 * Warns users after 2 failed login attempts before locking
 * 
 * Usage:
 * Include this file in login.php and call LoginWarning.init()
 * 
 * Dependencies: Firebase Auth SDK
 */

const LoginWarning = {
    // Configuration
    config: {
        maxAttempts: 3,
        warningThreshold: 2, // Show warning after 2 failed attempts
        blockDurationMinutes: 5,
        apiEndpoint: '../api/auth.php'
    },

    // State
    state: {
        attempts: 0,
        isBlocked: false,
        blockTimer: null,
        apiAvailable: true
    },

    /**
     * Initialize the login warning system
     */
    init: function() {
        this.checkServerStatus();
        this.attachEventListeners();
        console.log('🔐 Login Warning System initialized');
    },

    /**
     * Check block status from server
     */
    checkServerStatus: async function() {
        try {
            const response = await fetch(this.config.apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_attempt_status' })
            });
            const data = await response.json();

            if (data.success) {
                this.state.attempts = data.attempts;
                this.updateUI(data.attempts, data.remaining);

                if (data.blocked) {
                    this.showBlockedOverlay(data.blocked_until);
                } else if (data.show_warning) {
                    this.showWarning(data.warning_message);
                }
            }
        } catch (error) {
            console.warn('⚠️ Server status check failed, using local tracking');
            this.state.apiAvailable = false;
        }
    },

    /**
     * Record a failed attempt
     */
    recordFailedAttempt: async function() {
        if (this.state.apiAvailable) {
            try {
                const response = await fetch(this.config.apiEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'login_attempt' })
                });
                const data = await response.json();
                return this.handleServerResponse(data);
            } catch (error) {
                console.warn('⚠️ Server error, falling back to local');
                this.state.apiAvailable = false;
            }
        }

        // Fallback to local counting
        return this.handleLocalAttempt();
    },

    /**
     * Handle server response
     */
    handleServerResponse: function(data) {
        this.state.attempts = data.attempts || this.state.attempts + 1;
        const remaining = data.remaining || (this.config.maxAttempts - this.state.attempts);

        this.updateUI(this.state.attempts, remaining);

        if (data.blocked) {
            this.showBlockedOverlay(data.remaining_minutes || this.config.blockDurationMinutes);
            return { blocked: true, warning: false };
        }

        if (data.show_warning) {
            this.showWarning(data.warning_message);
            return { blocked: false, warning: true, message: data.warning_message };
        }

        return { blocked: false, warning: false };
    },

    /**
     * Handle local attempt tracking (fallback)
     */
    handleLocalAttempt: function() {
        this.state.attempts++;
        const remaining = this.config.maxAttempts - this.state.attempts;

        this.updateUI(this.state.attempts, remaining);

        if (this.state.attempts >= this.config.maxAttempts) {
            this.showBlockedOverlay(this.config.blockDurationMinutes);
            return { blocked: true, warning: false };
        }

        if (this.state.attempts >= this.config.warningThreshold) {
            const msg = `⚠️ Warning: You have ${remaining} attempt(s) remaining. Your account will be locked after ${remaining} more failed attempt(s).`;
            this.showWarning(msg);
            return { blocked: false, warning: true, message: msg };
        }

        return { blocked: false, warning: false };
    },

    /**
     * Reset attempts on success
     */
    resetAttempts: async function() {
        this.state.attempts = 0;
        this.updateUI(0, this.config.maxAttempts);

        if (this.state.apiAvailable) {
            try {
                await fetch(this.config.apiEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'reset_attempts' })
                });
            } catch (error) {
                console.warn('⚠️ Could not reset attempts on server');
            }
        }
    },

    /**
     * Show warning message
     */
    showWarning: function(message) {
        const warningEl = document.getElementById('alert-warning');
        const warningMsg = document.getElementById('warningMessage');

        if (warningEl && warningMsg) {
            warningMsg.textContent = message;
            warningEl.classList.add('show');

            // Add shake animation
            warningEl.style.animation = 'none';
            warningEl.offsetHeight; // Trigger reflow
            warningEl.style.animation = 'shake 0.5s ease';

            // Add shake keyframes if not present
            if (!document.getElementById('shake-keyframes')) {
                const style = document.createElement('style');
                style.id = 'shake-keyframes';
                style.textContent = `
                    @keyframes shake {
                        0%, 100% { transform: translateX(0); }
                        10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
                        20%, 40%, 60%, 80% { transform: translateX(4px); }
                    }
                `;
                document.head.appendChild(style);
            }

            // Auto-hide after 8 seconds (longer for warning)
            setTimeout(() => {
                warningEl.classList.remove('show');
            }, 8000);
        }
    },

    /**
     * Update UI attempt counter
     */
    updateUI: function(attempts, remaining) {
        const counter = document.getElementById('attemptCounter');
        const remainingEl = document.getElementById('attemptsRemaining');

        if (!counter) return;

        if (attempts > 0) {
            counter.classList.add('show');
            if (remainingEl) remainingEl.textContent = remaining;

            // Update dots
            for (let i = 1; i <= this.config.maxAttempts; i++) {
                const dot = document.getElementById('dot' + i);
                if (!dot) continue;

                dot.className = 'dot';

                if (i <= attempts) {
                    if (i === this.config.warningThreshold && attempts === this.config.warningThreshold) {
                        dot.classList.add('warning');
                    } else {
                        dot.classList.add('failed');
                    }
                } else {
                    dot.classList.add('remaining');
                }
            }
        } else {
            counter.classList.remove('show');
        }
    },

    /**
     * Show blocked overlay with countdown
     */
    showBlockedOverlay: function(minutes) {
        this.state.isBlocked = true;

        const overlay = document.getElementById('blockedOverlay');
        if (!overlay) return;

        overlay.classList.add('show');

        // Disable login form
        const loginBtn = document.getElementById('loginBtn');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        if (loginBtn) loginBtn.disabled = true;
        if (emailInput) emailInput.disabled = true;
        if (passwordInput) passwordInput.disabled = true;

        let totalSeconds = minutes * 60;
        const timerEl = document.getElementById('blockedTimer');
        const retryBtn = document.getElementById('retryBtn');
        const retryCountdown = document.getElementById('retryCountdown');

        if (this.state.blockTimer) {
            clearInterval(this.state.blockTimer);
        }

        this.state.blockTimer = setInterval(() => {
            totalSeconds--;

            if (totalSeconds <= 0) {
                clearInterval(this.state.blockTimer);
                overlay.classList.remove('show');
                this.state.isBlocked = false;
                this.state.attempts = 0;

                this.updateUI(0, this.config.maxAttempts);

                if (loginBtn) loginBtn.disabled = false;
                if (emailInput) emailInput.disabled = false;
                if (passwordInput) passwordInput.disabled = false;

                this.showSuccess('✅ You can now try logging in again.');
                return;
            }

            const mins = Math.floor(totalSeconds / 60);
            const secs = totalSeconds % 60;

            if (timerEl) {
                timerEl.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            }
            if (retryCountdown) {
                retryCountdown.textContent = Math.ceil(totalSeconds / 60);
            }
            if (retryBtn && totalSeconds <= 300) {
                retryBtn.disabled = false;
            }
        }, 1000);
    },

    /**
     * Show success message
     */
    showSuccess: function(message) {
        const el = document.getElementById('alert-success');
        const msg = document.getElementById('successMessage');
        if (el && msg) {
            msg.textContent = message;
            el.classList.add('show');
            setTimeout(() => el.classList.remove('show'), 5000);
        }
    },

    /**
     * Attach event listeners
     */
    attachEventListeners: function() {
        const form = document.getElementById('loginForm');
        if (!form) return;

        // Intercept form submission to track attempts
        form.addEventListener('submit', async (e) => {
            // Check if blocked
            if (this.state.isBlocked) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true); // Use capture phase
    },

    /**
     * Get current attempt status
     */
    getStatus: function() {
        return {
            attempts: this.state.attempts,
            remaining: this.config.maxAttempts - this.state.attempts,
            isBlocked: this.state.isBlocked,
            maxAttempts: this.config.maxAttempts
        };
    }
};

// Make it globally available
window.LoginWarning = LoginWarning;

console.log('📦 Login Warning System loaded!');
