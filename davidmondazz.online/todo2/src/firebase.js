// Firebase configuration
import { initializeApp } from "firebase/app";
import { getFirestore } from "firebase/firestore";
import { getAuth } from "firebase/auth";

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyDpQ4bDAQwvXQUbxG_nMOaA1yRLnPPYrvs",
  authDomain: "todo2-f7cf6.firebaseapp.com",
  projectId: "todo2-f7cf6",
  storageBucket: "todo2-f7cf6.firebasestorage.app",
  messagingSenderId: "484881366082",
  appId: "1:484881366082:web:b3d7800577f872705181db",
  measurementId: "G-DCNBRD47TK"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const db = getFirestore(app);
const auth = getAuth(app);

export { db, auth };
