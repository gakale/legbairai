// resources/js/services/SpaceService.js
import axios from 'axios';

const API_URL = '/api/v1/spaces';

export const getSpacesFeed = async (page = 1, perPage = 15) => {
  try {
    const response = await axios.get('/api/v1/spaces', {
      params: { page, per_page: perPage },
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    });
    return response.data;
  } catch (error) {
    console.error('Erreur API:', error);
    throw error;
  }
};

// Alias pour compatibilité (à supprimer progressivement)
export const getSpaces = getSpacesFeed;

const getSpaceDetails = (spaceId) => {
  return axios.get(`${API_URL}/${spaceId}`, {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json'
    }
  });
};

const getSpaceMessages = async (spaceId, page = 1, perPage = 50) => {
  try {
    const response = await axios.get(`/api/v1/spaces/${spaceId}/messages`, {
      params: { page, per_page: perPage },
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      }
    });
    return response.data;
  } catch (error) {
    console.error('Erreur chargement des messages:', error);
    throw error;
  }
};

export const fetchSpaces = async (page = 1, perPage = 15) => {
  try {
    const response = await axios.get('/api/v1/spaces', {
      params: { page, per_page: perPage },
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    });
    return response.data;
  } catch (error) {
    console.error('Erreur chargement des spaces:', error);
    throw error;
  }
};

// Nous ajouterons d'autres fonctions ici plus tard (createSpace, joinSpace, etc.
// bien que certaines soient déjà dans UserSpaceInteractionApiController et pourraient
// être dans un service dédié ou ici)

const SpaceService = {
  getSpacesFeed,
  getSpaces, // Maintenir temporairement
  getSpaceDetails,
  fetchSpaces,
  getSpaceMessages,
};

export default SpaceService;