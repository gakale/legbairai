import './bootstrap';

import React from 'react';
import { createRoot } from 'react-dom/client';
import RealtimeSpaceTest from './components/RealtimeSpaceTest';

// ID du space en direct que nous avons testé précédemment
const spaceId = '7b398b36-9195-4cb6-8a8d-478cb1ccc9fe';

// Fonction pour initialiser l'application React
const initReactApp = () => {
    const container = document.getElementById('realtime-space-test');
    
    if (container) {
        const root = createRoot(container);
        root.render(
            <React.StrictMode>
                <RealtimeSpaceTest spaceIdToListen={spaceId} />
            </React.StrictMode>
        );
        console.log('Composant React monté avec succès pour le space ID:', spaceId);
    } else {
        console.error('Élément #realtime-space-test non trouvé dans le DOM');
    }
};

// Initialiser l'application React une fois que le DOM est chargé
document.addEventListener('DOMContentLoaded', initReactApp);
