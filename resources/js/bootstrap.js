import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js'; // Reverb utilise le protocole Pusher, donc PusherJS est le client.

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb', // Indique à Echo d'utiliser le driver Reverb
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT, // Pourrait être différent si vous utilisez un port TLS dédié
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'], // Autoriser les transports WebSocket sécurisés et non sécurisés
    // Si vous avez besoin de l'autorisation pour les canaux privés/présence:
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                axios.post('/broadcasting/auth', { // L'endpoint d'autorisation de Laravel
                    socket_id: socketId,
                    channel_name: channel.name
                })
                .then(response => {
                    callback(false, response.data); // false signifie pas d'erreur, response.data contient les infos d'auth
                })
                .catch(error => {
                    console.error('Echo authorizer error:', error);
                    callback(true, error); // true signifie erreur
                });
            }
        };
    },
    // Vous pouvez aussi définir l'endpoint d'autorisation globalement si vous préférez
    // authEndpoint: '/broadcasting/auth', // Assurez-vous que cette route est gérée par Broadcast::routes()
});

// Vous pouvez maintenant utiliser window.Echo dans vos composants React
// Exemple : window.Echo.channel('my-channel').listen('MyEvent', (e) => { ... });