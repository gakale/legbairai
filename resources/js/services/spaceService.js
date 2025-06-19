// resources/js/services/spaceService.js
import axios from 'axios';

const API_URL = '/api/v1/spaces';

// Service principal pour les spaces
export const spaceService = {
    /**
     * Récupérer tous les espaces avec pagination et filtres
     */
    getAll: async (params = {}) => {
        try {
            const response = await axios.get(API_URL, { params });
            return response.data;
        } catch (error) {
            console.error('Erreur lors de la récupération des espaces:', error);
            throw error;
        }
    },

    /**
     * Récupérer les spaces créés par un utilisateur spécifique
     */
    getUserSpaces: async (userId, page = 1, perPage = 20, status = null) => {
        try {
            const params = { page, per_page: perPage };
            if (status) params.status = status;
            
            const response = await axios.get(`/api/v1/users/${userId}/spaces`, { params });
            return response;
        } catch (error) {
            console.error(`Erreur lors de la récupération des spaces de l'utilisateur ${userId}:`, error);
            throw error;
        }
    },
    
    /**
     * Récupérer les spaces où un utilisateur a participé
     */
    getUserParticipatedSpaces: async (userId, page = 1, perPage = 20) => {
        try {
            const response = await axios.get(`/api/v1/users/${userId}/participated-spaces`, {
                params: { page, per_page: perPage }
            });
            return response;
        } catch (error) {
            console.error(`Erreur lors de la récupération des spaces où l'utilisateur ${userId} a participé:`, error);
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
     * Créer un nouveau space
     */
    create: async (spaceData) => {
        try {
            const response = await axios.post(API_URL, spaceData);
            return response.data;
        } catch (error) {
            console.error('Erreur lors de la création du space:', error);
            throw error;
        }
    },

    /**
     * Mettre à jour un space
     */
    update: async (spaceId, updateData) => {
        try {
            const response = await axios.put(`${API_URL}/${spaceId}`, updateData);
            return response.data;
        } catch (error) {
            console.error(`Erreur lors de la mise à jour du space ${spaceId}:`, error);
            throw error;
        }
    },

    /**
     * Supprimer un space
     */
    delete: async (spaceId) => {
        try {
            await axios.delete(`${API_URL}/${spaceId}`);
            return { success: true };
        } catch (error) {
            console.error(`Erreur lors de la suppression du space ${spaceId}:`, error);
            throw error;
        }
    },

    /**
     * Démarrer un space programmé
     */
    start: async (spaceId) => {
        try {
            const response = await axios.post(`${API_URL}/${spaceId}/start`);
            return response.data;
        } catch (error) {
            console.error(`Erreur lors du démarrage du space ${spaceId}:`, error);
            throw error;
        }
    },

    /**
     * Terminer un space actif
     */
    end: async (spaceId) => {
        try {
            const response = await axios.post(`${API_URL}/${spaceId}/end`);
            return response.data;
        } catch (error) {
            console.error(`Erreur lors de l'arrêt du space ${spaceId}:`, error);
            throw error;
        }
    },

    /**
     * Rejoindre un espace
     */
    join: async (spaceId) => {
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
    leave: async (spaceId) => {
        try {
            const response = await axios.post(`${API_URL}/${spaceId}/leave`);
            return response.data;
        } catch (error) {
            console.error(`Erreur lors de la tentative de quitter l'espace ${spaceId}:`, error);
            throw error;
        }
    },

    /**
     * Envoyer un message dans un space
     */
    sendMessage: async (spaceId, content) => {
        try {
            const response = await axios.post(`${API_URL}/${spaceId}/messages`, { content });
            return response.data;
        } catch (error) {
            console.error(`Erreur lors de l'envoi du message dans le space ${spaceId}:`, error);
            throw error;
        }
    },

    /**
     * Récupérer les messages d'un space
     */
    getMessages: async (spaceId, page = 1, perPage = 50) => {
        try {
            const response = await axios.get(`${API_URL}/${spaceId}/messages`, {
                params: { page, per_page: perPage }
            });
            return response.data;
        } catch (error) {
            console.error(`Erreur lors de la récupération des messages du space ${spaceId}:`, error);
            throw error;
        }
    },

    /**
     * Envoyer des signaux WebRTC
     */
    sendAudioSignal: async (spaceId, signalData) => {
        try {
            // Extraire le signal seul pour l'envoyer au serveur
            const response = await axios.post(`${API_URL}/${spaceId}/audio-signal`, { 
                signalData: signalData.signal // Envoyer seulement le signal WebRTC
            });
            return response.data;
        } catch (error) {
            console.error(`Erreur lors de l'envoi du signal audio pour le space ${spaceId}:`, error);
            throw error;
        }
    },
    
    /**
     * Sauvegarder un enregistrement audio d'un espace
     */
    saveRecording: async (spaceId, formData) => {
        try {
            const response = await axios.post(`${API_URL}/${spaceId}/recordings`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
            return response.data;
        } catch (error) {
            console.error(`Erreur lors de la sauvegarde de l'enregistrement pour le space ${spaceId}:`, error);
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
     * Lever la main dans un space
     */
    raiseHand: async (spaceId) => {
        try {
            const response = await axios.post(`${API_URL}/${spaceId}/raise-hand`);
            return response.data;
        } catch (error) {
            console.error(`Erreur lors de la levée de main dans le space ${spaceId}:`, error);
            throw error;
        }
    },

    /**
     * Actions de modération
     */
    moderation: {
        muteParticipant: async (spaceId, participantId) => {
            try {
                const response = await axios.post(`${API_URL}/${spaceId}/participants/${participantId}/mute`);
                return response.data;
            } catch (error) {
                console.error('Erreur lors du mute du participant:', error);
                throw error;
            }
        },

        unmuteParticipant: async (spaceId, participantId) => {
            try {
                const response = await axios.post(`${API_URL}/${spaceId}/participants/${participantId}/unmute`);
                return response.data;
            } catch (error) {
                console.error('Erreur lors du unmute du participant:', error);
                throw error;
            }
        },

        changeParticipantRole: async (spaceId, participantId, role) => {
            try {
                const response = await axios.post(`${API_URL}/${spaceId}/participants/${participantId}/role`, { role });
                return response.data;
            } catch (error) {
                console.error('Erreur lors du changement de rôle du participant:', error);
                throw error;
            }
        }
    }
};

// Exports pour compatibilité avec l'existant
export const getSpaces = (page = 1, perPage = 15) => spaceService.getAll({ page, per_page: perPage });
export const getSpacesByUser = (userId, page = 1, perPage = 20, status = null) => spaceService.getUserSpaces(userId, page, perPage, status);
export const getUserParticipatedSpaces = (userId, page = 1, perPage = 20) => spaceService.getUserParticipatedSpaces(userId, page, perPage);
export const getSpacesFeed = (page = 1, perPage = 15) => spaceService.getAll({ page, per_page: perPage });
export const fetchSpaces = (page = 1, perPage = 15) => spaceService.getAll({ page, per_page: perPage });
export const getSpaceDetails = (spaceId) => spaceService.getById(spaceId);
export const createSpace = (spaceData) => spaceService.create(spaceData);
export const joinSpace = (spaceId) => spaceService.join(spaceId);

// Export par défaut
export default spaceService;