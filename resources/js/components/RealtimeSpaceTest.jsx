import React, { useEffect, useState } from 'react';
// Assurez-vous que Echo est initialisé et accessible,
// par exemple via window.Echo ou en l'important directement si vous l'exportez depuis bootstrap.js
// Pour cet exemple, nous supposons window.Echo est disponible.

const RealtimeSpaceTest = ({ spaceIdToListen }) => {
    const [eventData, setEventData] = useState(null);
    const [connectionStatus, setConnectionStatus] = useState('Déconnecté');

    useEffect(() => {
        if (!spaceIdToListen || !window.Echo) {
            console.warn("ID du Space manquant ou Echo non initialisé.");
            return;
        }

        console.log(`Tentative de connexion au canal : space.${spaceIdToListen}`);
        setConnectionStatus('Connexion...');

        const channel = window.Echo.channel(`space.${spaceIdToListen}`);

        channel
            .on('pusher:subscription_succeeded', () => {
                setConnectionStatus(`Connecté au canal space.${spaceIdToListen}`);
                console.log(`Abonnement réussi au canal : space.${spaceIdToListen}`);
            })
            .on('pusher:subscription_error', (status) => {
                setConnectionStatus(`Échec de la connexion: ${status}`);
                console.error(`Erreur d'abonnement au canal space.${spaceIdToListen}:`, status);
            })
            .listen('.space.started', (data) => { // Notez le '.' devant le nom de l'événement
                console.log('Événement "space.started" reçu:', data);
                setEventData(data);
            });

        // Nettoyage lors du démontage du composant
        return () => {
            console.log(`Déconnexion du canal : space.${spaceIdToListen}`);
            window.Echo.leaveChannel(`space.${spaceIdToListen}`);
            setConnectionStatus('Déconnecté');
        };

    }, [spaceIdToListen]); // Se réabonner si spaceIdToListen change

    return (
        <div>
            <h2>Test de Diffusion en Temps Réel pour le Space ID: {spaceIdToListen}</h2>
            <p>Statut de la connexion WebSocket: <strong>{connectionStatus}</strong></p>
            {eventData ? (
                <div>
                    <h3>Événement Reçu (space.started):</h3>
                    <p>Message: {eventData.message}</p>
                    <h4>Détails du Space :</h4>
                    <pre>{JSON.stringify(eventData.space, null, 2)}</pre>
                </div>
            ) : (
                <p>En attente de l'événement "space.started"...</p>
            )}
        </div>
    );
};

export default RealtimeSpaceTest;
