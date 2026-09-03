import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        define: {
            'process.env': JSON.stringify({
                VITE_FIREBASE_API_KEY: env.VITE_FIREBASE_API_KEY || '',
                VITE_FIREBASE_AUTH_DOMAIN: env.VITE_FIREBASE_AUTH_DOMAIN || '',
                VITE_FIREBASE_PROJECT_ID: env.VITE_FIREBASE_PROJECT_ID || 'z-syst',
                VITE_FIREBASE_APP_ID: env.VITE_FIREBASE_APP_ID || '',
                VITE_FIREBASE_STORAGE_BUCKET: env.VITE_FIREBASE_STORAGE_BUCKET || '',
                VITE_FIREBASE_MESSAGING_SENDER_ID: env.VITE_FIREBASE_MESSAGING_SENDER_ID || '',
                VITE_FIREBASE_MEASUREMENT_ID: env.VITE_FIREBASE_MEASUREMENT_ID || '',
                VITE_PUSHER_APP_KEY: env.VITE_PUSHER_APP_KEY || '',
                VITE_PUSHER_HOST: env.VITE_PUSHER_HOST || '',
                VITE_PUSHER_PORT: env.VITE_PUSHER_PORT || '',
                VITE_PUSHER_SCHEME: env.VITE_PUSHER_SCHEME || 'https',
                VITE_PUSHER_APP_CLUSTER: env.VITE_PUSHER_APP_CLUSTER || '',
            }),
        },
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js', 'public/css/admin-utilities.css'],
                refresh: true,
            }),
        ],
    };
});
