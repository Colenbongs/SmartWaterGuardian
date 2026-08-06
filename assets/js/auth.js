/**
 * Smart Water Guardian - Authentication JavaScript
 * Handles login, registration, and session management
 */

// ============================
// LOGIN HANDLER
// ============================
function handleLogin(email, password) {
    return new Promise((resolve, reject) => {
        if (!email || !password) {
            reject(new Error('Email and password are required'));
            return;
        }
        
        if (!validateEmail(email)) {
            reject(new Error('Please enter a valid email address'));
            return;
        }
        
        auth.signInWithEmailAndPassword(email, password)
            .then(async (userCredential) => {
                const user = userCredential.user;
                
                // Get user data from Firebase
                const userRef = database.ref('users/' + user.uid);
                const snapshot = await userRef.once('value');
                const userData = snapshot.val();
                
                if (!userData) {
                    reject(new Error('User data not found'));
                    return;
                }
                
                // Set session via PHP
                const response = await fetch('../api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'set_session',
                        uid: user.uid,
                        email: user.email,
                        firstName: userData.firstName || '',
                        lastName: userData.lastName || '',
                        role: userData.role || 'consumer'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resolve({
                        user: user,
                        userData: userData,
                        session: result
                    });
                } else {
                    reject(new Error('Session creation failed'));
                }
            })
            .catch((error) => {
                // Handle specific Firebase auth errors
                let message = error.message;
                switch (error.code) {
                    case 'auth/user-not-found':
                        message = '❌ No account found with this email address';
                        break;
                    case 'auth/wrong-password':
                        message = '❌ Incorrect password. Please try again';
                        break;
                    case 'auth/too-many-requests':
                        message = '⚠️ Too many failed attempts. Please try again later';
                        break;
                    case 'auth/user-disabled':
                        message = '⚠️ This account has been disabled';
                        break;
                    case 'auth/invalid-email':
                        message = '❌ Invalid email format';
                        break;
                    default:
                        message = '❌ ' + error.message;
                }
                reject(new Error(message));
            });
    });
}

// ============================
// REGISTRATION HANDLER
// ============================
function handleRegister(userData) {
    return new Promise((resolve, reject) => {
        const { 
            email, 
            password, 
            firstName, 
            lastName, 
            phone = '', 
            address = '' 
        } = userData;
        
        // Validate required fields
        if (!email || !password || !firstName || !lastName) {
            reject(new Error('Please fill in all required fields'));
            return;
        }
        
        if (!validateEmail(email)) {
            reject(new Error('Please enter a valid email address'));
            return;
        }
        
        // Validate password strength
        const passwordValidation = validatePassword(password);
        if (!passwordValidation.valid) {
            reject(new Error(passwordValidation.errors.join('. ')));
            return;
        }
        
        // Create user with Firebase Auth
        auth.createUserWithEmailAndPassword(email, password)
            .then(async (userCredential) => {
                const user = userCredential.user;
                
                // Save user data to Firebase Realtime Database
                const userRef = database.ref('users/' + user.uid);
                await userRef.set({
                    firstName: firstName,
                    lastName: lastName,
                    email: email,
                    phone: phone || '',
                    address: address || '',
                    role: 'consumer',
                    createdAt: new Date().toISOString()
                });
                
                // Create initial property for the user
                const propertyRef = database.ref('properties/' + user.uid).push();
                await propertyRef.set({
                    propertyName: 'My Home',
                    address: address || 'Not specified',
                    meterId: 'meter_' + Date.now(),
                    createdAt: new Date().toISOString()
                });
                
                // Create default threshold
                await database.ref('thresholds/' + user.uid).push({
                    thresholdType: 'daily_limit',
                    thresholdValue: 1000,
                    isActive: true,
                    createdAt: new Date().toISOString()
                });
                
                // Set session via PHP
                const response = await fetch('../api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'set_session',
                        uid: user.uid,
                        email: user.email,
                        firstName: firstName,
                        lastName: lastName,
                        role: 'consumer'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resolve({
                        user: user,
                        session: result
                    });
                } else {
                    reject(new Error('Session creation failed'));
                }
            })
            .catch((error) => {
                // Handle specific Firebase auth errors
                let message = error.message;
                switch (error.code) {
                    case 'auth/email-already-in-use':
                        message = '⚠️ This email is already registered. Please login instead';
                        break;
                    case 'auth/invalid-email':
                        message = '❌ Invalid email format';
                        break;
                    case 'auth/weak-password':
                        message = '❌ Password is too weak. Use at least 6 characters';
                        break;
                    default:
                        message = '❌ ' + error.message;
                }
                reject(new Error(message));
            });
    });
}

// ============================
// LOGOUT HANDLER
// ============================
function handleLogout() {
    return new Promise((resolve, reject) => {
        auth.signOut()
            .then(() => {
                return fetch('../api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                });
            })
            .then(response => response.json())
            .then((data) => {
                if (data.success) {
                    resolve(data);
                } else {
                    reject(new Error('Logout failed'));
                }
            })
            .catch((error) => {
                reject(error);
            });
    });
}

// ============================
// PASSWORD RESET
// ============================
function sendPasswordReset(email) {
    return new Promise((resolve, reject) => {
        if (!email || !validateEmail(email)) {
            reject(new Error('Please enter a valid email address'));
            return;
        }
        
        auth.sendPasswordResetEmail(email)
            .then(() => {
                resolve({ message: 'Password reset email sent! 📧 Check your inbox' });
            })
            .catch((error) => {
                let message = error.message;
                switch (error.code) {
                    case 'auth/user-not-found':
                        message = '❌ No account found with this email address';
                        break;
                    default:
                        message = '❌ ' + error.message;
                }
                reject(new Error(message));
            });
    });
}

// ============================
// CHECK AUTH STATE
// ============================
function checkAuthState() {
    return new Promise((resolve) => {
        const unsubscribe = auth.onAuthStateChanged((user) => {
            unsubscribe();
            resolve(user);
        });
    });
}

function getAuthUser() {
    return auth.currentUser;
}

async function verifySession() {
    try {
        const response = await fetch('../api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check_session' })
        });
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Session check error:', error);
        return { logged_in: false };
    }
}

// ============================
// UPDATE USER PROFILE
// ============================
function updateUserProfile(userId, updates) {
    return new Promise((resolve, reject) => {
        if (!userId) {
            reject(new Error('User ID required'));
            return;
        }
        
        // Update Firebase Realtime Database
        const userRef = database.ref('users/' + userId);
        userRef.update(updates)
            .then(() => {
                // Also update MySQL via API
                return fetch('../api/users.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: userId,
                        ...updates
                    })
                });
            })
            .then(response => response.json())
            .then((data) => {
                if (data.success) {
                    resolve(data);
                } else {
                    reject(new Error(data.error || 'Update failed'));
                }
            })
            .catch(reject);
    });
}

// ============================
// EXPOSE FUNCTIONS
// ============================
window.handleLogin = handleLogin;
window.handleRegister = handleRegister;
window.handleLogout = handleLogout;
window.sendPasswordReset = sendPasswordReset;
window.checkAuthState = checkAuthState;
window.getAuthUser = getAuthUser;
window.verifySession = verifySession;
window.updateUserProfile = updateUserProfile;

console.log('🔐 Auth module loaded successfully!');