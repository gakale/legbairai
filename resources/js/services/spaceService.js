// resources/js/services/SpaceService.js
import axios from 'axios';

const API_URL = '/api/v1/spaces';

export const getSpacesFeed = async (page = 1, perPage = 15) => {
  return axios.get('/api/v1/spaces', {
    params: { page, per_page: perPage }
  });
};

const getSpaceDetails = (spaceId) => {
  return axios.get(`${API_URL}/${spaceId}`);
};

export const fetchSpaces = async (page = 1, perPage = 6) => {
  try {
    const response = await axios.get('/api/v1/spaces', {
      params: { page, per_page: perPage },
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
  getSpaceDetails,
  fetchSpaces,
};

export default SpaceService;