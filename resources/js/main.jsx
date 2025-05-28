import './bootstrap';
import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App'; // Importe le composant racine depuis App.jsx
import '../css/app.css';
import { AuthProvider } from './contexts/AuthContext'; // Importer

const rootElement = document.getElementById('app');

if (rootElement) {
    console.log("[main.jsx] Élément #app trouvé, montage de React...");
    ReactDOM.createRoot(rootElement).render(
        <React.StrictMode>
            <AuthProvider>
                <App />
            </AuthProvider>
        </React.StrictMode>
    );
    console.log("[main.jsx] Application React montée.");
} else {
    console.error("[main.jsx] L'élément racine #app non trouvé.");
}