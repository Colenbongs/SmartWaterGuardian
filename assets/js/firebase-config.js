/**
 * Firebase Configuration for Smart Water Guardian
 * This file initializes Firebase and exports the services
 */


// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyCatcC7yo-a7E7dLAfAWh0iv1BCSoYxUP8",
  authDomain: "smartwaterguardian.firebaseapp.com",
  databaseURL: "https://smartwaterguardian-default-rtdb.firebaseio.com",
  projectId: "smartwaterguardian",
  storageBucket: "smartwaterguardian.firebasestorage.app",
  messagingSenderId: "12612851503",
  appId: "1:12612851503:web:ee8e80d5a46ed28e95b3da",
  measurementId: "G-525B8XXSKM"
};
// Initialize Firebase
firebase.initializeApp(firebaseConfig);

// Get Firebase services
const auth = firebase.auth();
const database = firebase.database();

// Make them globally available
window.auth = auth;
window.database = database;

// Set persistence (optional - keeps user logged in)
auth.setPersistence(firebase.auth.Auth.Persistence.SESSION)
    .then(() => {
        console.log('🔥 Firebase persistence set to SESSION');
    })
    .catch((error) => {
        console.error('⚠️ Firebase persistence error:', error);
    });

// Log Firebase initialization
console.log('🚀 Firebase initialized successfully for Smart Water Guardian!');
console.log('📱 Project ID:', firebaseConfig.projectId);
console.log('🌐 Database URL:', firebaseConfig.databaseURL);