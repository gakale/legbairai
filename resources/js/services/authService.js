/**
 * Service pour les appels API liés à l'authentification
 */

import axios from 'axios';

const API_URL = '/api/v1';

export const authService = {
    /**
     * Connexion utilisateur
     */
    login: async (credentials) => {
        try {
            const response = await axios.post(`${API_URL}/login`, credentials);
            if (response.data.token) {
                localStorage.setItem('user', JSON.stringify(response.data));
                axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
            }
            return response.data;
        } catch (error) {
            console.error('Erreur de connexion:', error);
            throw error;
        }
    },

    /**
     * Inscription utilisateur
     */
    register: async (userData) => {
        try {
            const response = await axios.post(`${API_URL}/register`, userData);
            return response.data;
        } catch (error) {
            console.error('Erreur d\'inscription:', error);
            throw error;
        }
    },

    /**
     * Déconnexion utilisateur
     */
    logout: async () => {
        try {
            await axios.post(`${API_URL}/logout`);
            localStorage.removeItem('user');
            delete axios.defaults.headers.common['Authorization'];
        } catch (error) {
            console.error('Erreur de déconnexion:', error);
        }
    },

    /**
     * Récupérer l'utilisateur courant
     */
    getCurrentUser: () => {
        return JSON.parse(localStorage.getItem('user'));
    },

    /**
     * Vérifier si l'utilisateur est connecté
     */
    isAuthenticated: () => {
        const user = localStorage.getItem('user');
        return !!user;
    }
};

export default authService;
