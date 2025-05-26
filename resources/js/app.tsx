import './bootstrap.js';
import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App.jsx'; // Importe le composant racine depuis App.jsx
import '../css/app.css';

const rootElement = document.getElementById('app');

if (rootElement) {
    console.log("Point d'entrée (app.jsx): Élément #app trouvé, montage de React...");
    try {
        ReactDOM.createRoot(rootElement).render(
            <React.StrictMode>
                <App />
            </React.StrictMode>
        );
        console.log("Point d'entrée (app.jsx): Application React montée.");
    } catch (error) {
        console.error("Point d'entrée (app.jsx): Erreur lors du montage:", error);
    }
} else {
    console.error("Point d'entrée (app.jsx): Élément #app non trouvé.");
}