import axios from 'axios';
window.axios = axios;

// Configure axios base URL for API calls
window.axios.defaults.baseURL = 'http://127.0.0.1:8000';
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Support des cookies pour Sanctum CSRF
window.axios.defaults.withCredentials = true;

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js'; // Reverb utilise le protocole Pusher, donc PusherJS est le client.

window.Pusher = Pusher;

// Fonction pour vérifier si l'utilisateur est authentifié
const isUserAuthenticated = () => {
    // Vérifier si un token d'authentification est présent dans localStorage
    // ou si une session utilisateur est active (cookie)
    return !!localStorage.getItem('authToken') || 
           document.cookie.includes('laravel_session=') || 
           document.cookie.includes('XSRF-TOKEN=');
};

// N'initialiser Echo que si l'utilisateur est authentifié
if (isUserAuthenticated()) {
    console.log('Initialisation de Laravel Echo pour utilisateur authentifié');
    window.Echo = new Echo({
        broadcaster: 'reverb', // Indique à Echo d'utiliser le driver Reverb
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT,
        wssPort: import.meta.env.VITE_REVERB_PORT, // Pourrait être différent si vous utilisez un port TLS dédié
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
        enabledTransports: ['ws', 'wss'], // Autoriser les transports WebSocket sécurisés et non sécurisés
        // Configuration simplifiée pour le débogage
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        withCredentials: true, // Important pour envoyer les cookies
        auth: {
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        }
    });
} else {
    console.log('Utilisateur non authentifié - Laravel Echo non initialisé');
    // Créer un Echo factice pour éviter les erreurs lorsque le code tente d'accéder à window.Echo
    window.Echo = {
        // Méthodes factices qui ne font rien
        channel: () => ({ listen: () => {} }),
        private: () => ({ listen: () => {} }),
        join: () => ({
            here: () => ({}),
            joining: () => ({}),
            leaving: () => ({}),
            listen: () => ({}),
            on: () => ({
                here: () => ({}),
                joining: () => ({}),
                leaving: () => ({}),
                on: () => ({})
            })
        })
    };
}

// Vous pouvez maintenant utiliser window.Echo dans vos composants React
// Exemple : window.Echo.channel('my-channel').listen('MyEvent', (e) => { ... });