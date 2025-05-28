// resources/js/contexts/AuthContext.jsx
import React, { createContext, useState, useContext, useEffect } from 'react';
import AuthService from '../services/AuthService';
import axios from 'axios'; // Pour configurer les intercepteurs ou les headers par défaut

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
    const [currentUser, setCurrentUser] = useState(null);
    const [isLoading, setIsLoading] = useState(true); // Pour gérer le chargement initial de l'utilisateur

    // Fonction pour configurer le header Authorization d'Axios
    const setAuthToken = (token) => {
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            localStorage.setItem('authToken', token); // Stocker le token
        } else {
            delete axios.defaults.headers.common['Authorization'];
            localStorage.removeItem('authToken');
        }
    };

    useEffect(() => {
        // Essayer de charger l'utilisateur au démarrage si un token existe
        const token = localStorage.getItem('authToken');
        if (token) {
            setAuthToken(token); // Configurer Axios avec le token existant
            AuthService.getCurrentUser()
                .then(response => {
                    setCurrentUser(response.data.data); // Supposant que la réponse est { data: UserResource }
                })
                .catch(() => {
                    // Token invalide ou expiré
                    setAuthToken(null); // Nettoyer
                    setCurrentUser(null);
                })
                .finally(() => setIsLoading(false));
        } else {
            setIsLoading(false);
        }
    }, []);

    const login = async (credentials) => {
        try {
            const response = await AuthService.login(credentials);
            if (response.data.token && response.data.user) {
                setAuthToken(response.data.token);
                setCurrentUser(response.data.user); // Ou response.data.user.data si enveloppé
                return response.data;
            }
        } catch (error) {
            setAuthToken(null); // Nettoyer en cas d'échec
            setCurrentUser(null);
            throw error; // Relancer l'erreur pour que le composant puisse la gérer
        }
    };

    const register = async (userData) => {
        try {
            const response = await AuthService.register(userData);
             if (response.data.token && response.data.user) {
                setAuthToken(response.data.token);
                setCurrentUser(response.data.user);
                return response.data;
            }
        } catch (error) {
            setAuthToken(null);
            setCurrentUser(null);
            throw error;
        }
    };

    const logout = async () => {
        try {
            await AuthService.logout();
        } catch (error) {
            console.error("Erreur lors de la déconnexion API:", error);
            // Continuer la déconnexion côté client même si l'API échoue (par ex. token expiré)
        } finally {
            setAuthToken(null);
            setCurrentUser(null);
        }
    };

    const value = {
        currentUser,
        isLoading,
        login,
        register,
        logout,
        isAuthenticated: !!currentUser, // Un booléen pratique
    };

    return (
        <AuthContext.Provider value={value}>
            {!isLoading && children} {/* Ne rend les enfants que lorsque le chargement initial est terminé */}
        </AuthContext.Provider>
    );
};

// Hook personnalisé pour utiliser facilement le contexte
export const useAuth = () => {
    const context = useContext(AuthContext);
    if (context === undefined) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};