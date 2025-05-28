// resources/js/services/SpaceService.js
import axios from 'axios';

const API_URL = '/api/v1/spaces';

export const getSpaces = async (page = 1, perPage = 15) => {
  try {
    const response = await axios.get(API_URL, {
      params: { page, per_page: perPage }
    });
    return response.data;
  } catch (error) {
    console.error('Erreur API:', error);
    throw error;
  }
};

export const getSpaceDetails = (spaceId) => {
  return axios.get(`${API_URL}/${spaceId}`);
};

// Nous ajouterons d'autres fonctions ici plus tard (createSpace, joinSpace, etc.
// bien que certaines soient déjà dans UserSpaceInteractionApiController et pourraient
// être dans un service dédié ou ici)