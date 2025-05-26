import './bootstrap';

import React from 'react';
import { createRoot } from 'react-dom/client';
import RealtimeSpaceTest from './components/RealtimeSpaceTest';
import SpaceViewComponent from './components/SpaceViewComponent';

// L'ID du space sera récupéré depuis l'attribut data-space-id

// Fonction pour initialiser l'application React
const initReactApp = () => {
    // Initialisation du composant RealtimeSpaceTest
    const realtimeContainer = document.getElementById('realtime-space-test');
    if (realtimeContainer) {
        const realtimeRoot = createRoot(realtimeContainer);
        realtimeRoot.render(
            React.createElement(React.StrictMode, null,
                React.createElement(RealtimeSpaceTest, { spaceIdToListen: spaceId })
            )
        );
        console.log('Composant RealtimeSpaceTest monté avec succès pour le space ID:', spaceId);
    }
    
    // Initialisation du composant SpaceViewComponent
    const spaceViewContainer = document.getElementById('space-participants-test');
    if (spaceViewContainer) {
        // Récupérer l'ID du space depuis l'attribut data-space-id
        const spaceIdToUse = spaceViewContainer.getAttribute('data-space-id') || '1';
        
        const spaceViewRoot = createRoot(spaceViewContainer);
        spaceViewRoot.render(
            React.createElement(React.StrictMode, null,
                React.createElement(SpaceViewComponent, { 
                    spaceId: spaceIdToUse,
                    currentUserId: window.currentUserId || null
                })
            )
        );
        console.log('Composant SpaceViewComponent monté avec succès pour le space ID:', spaceIdToUse);
    }
    
    // Afficher un message si aucun conteneur n'est trouvé
    if (!realtimeContainer && !spaceViewContainer) {
        console.error('Aucun conteneur React trouvé dans le DOM');
    }
};

// Initialiser l'application React une fois que le DOM est chargé
document.addEventListener('DOMContentLoaded', initReactApp);
