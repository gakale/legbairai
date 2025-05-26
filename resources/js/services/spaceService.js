/**
 * Service pour les appels API liés aux espaces
 */

import axios from 'axios';

const API_URL = '/api/v1/spaces';

export const spaceService = {
    /**
     * Récupérer tous les espaces
     */
    getAll: async () => {
        try {
            const response = await axios.get(API_URL);
            return response.data;
        } catch (error) {
            console.error('Erreur lors de la récupération des espaces:', error);
            throw error;
        }
    },

    /**
     * Récupérer un espace par son ID
     */
    getById: async (id) => {
        try {
            const response = await axios.get(`${API_URL}/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Erreur lors de la récupération de l'espace ${id}:`, error);
            throw error;
        }
    },

    /**
     * Créer un clip audio pour un espace
     */
    createClip: async (spaceId, clipData) => {
        try {
            const response = await axios.post(`${API_URL}/${spaceId}/clips`, clipData);
            return response.data;
        } catch (error) {
            console.error('Erreur lors de la création du clip audio:', error);
            throw error;
        }
    },

    /**
     * Rejoindre un espace
     */
    joinSpace: async (spaceId) => {
        try {
            const response = await axios.post(`${API_URL}/${spaceId}/join`);
            return response.data;
        } catch (error) {
            console.error(`Erreur lors de la tentative de rejoindre l'espace ${spaceId}:`, error);
            throw error;
        }
    },

    /**
     * Quitter un espace
     */
    leaveSpace: async (spaceId) => {
        try {
            const response = await axios.post(`${API_URL}/${spaceId}/leave`);
            return response.data;
        } catch (error) {
            console.error(`Erreur lors de la tentative de quitter l'espace ${spaceId}:`, error);
            throw error;
        }
    }
};

export default spaceService;
