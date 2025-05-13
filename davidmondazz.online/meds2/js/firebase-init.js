// Initialize Firebase
const firebaseConfig = {
  apiKey: "AIzaSyDNac2hmCN7ldAtWieJW2l1cBW6gsjx6Rc",
  authDomain: "reacttimer-7ed91.firebaseapp.com",
  databaseURL: "https://reacttimer-7ed91-default-rtdb.europe-west1.firebasedatabase.app",
  projectId: "reacttimer-7ed91",
  storageBucket: "reacttimer-7ed91.firebasestorage.app",
  messagingSenderId: "539885183015",
  appId: "1:539885183015:web:06d7163c84ce2ae5122e02",
  measurementId: "G-ZK9NP96ZZD"
};

// Initialize Firebase
if (typeof firebase !== 'undefined') {
  firebase.initializeApp(firebaseConfig);
  console.log('Firebase initialized on client-side');

  // Check if user is already signed in
  firebase.auth().onAuthStateChanged(function(user) {
    if (user) {
      // User is signed in
      console.log('User is signed in with UID:', user.uid);
      // You could use this UID to match with your PHP session user ID
    } else {
      // User is not signed in
      console.log('User is not signed in');
      // You could sign in user anonymously here if needed
      // firebase.auth().signInAnonymously();
    }
  });
} else {
  console.error('Firebase SDK not loaded. Make sure to include the Firebase SDK script tags.');
} 