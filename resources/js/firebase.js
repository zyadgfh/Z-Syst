import { initializeApp, getApps, getApp } from 'firebase/app';
import { getFirestore } from 'firebase/firestore';

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY || '',
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN || '',
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID || 'z-syst',
    appId: import.meta.env.VITE_FIREBASE_APP_ID || '',
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET || '',
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID || '',
    measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID || '',
};

const hasRequiredConfig = Boolean(
    firebaseConfig.apiKey && firebaseConfig.authDomain && firebaseConfig.appId
);

let firebaseApp = null;
let firestoreDb = null;

if (hasRequiredConfig) {
    firebaseApp = getApps().length ? getApp() : initializeApp(firebaseConfig);
    firestoreDb = getFirestore(firebaseApp);
} else {
    console.warn(
        'Firebase is not initialized because VITE_FIREBASE_API_KEY, VITE_FIREBASE_AUTH_DOMAIN, and VITE_FIREBASE_APP_ID are not set.'
    );
}

export const db = firestoreDb;
export { firebaseApp, firestoreDb, firebaseConfig };
