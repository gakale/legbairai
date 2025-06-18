import axios from 'axios';

const API_URL = '/api/v1/spaces';

export const getSpacesFeed = async (page = 1, perPage = 15) => {
  try {
    const response = await axios.get('/api/v1/spaces', {
      params: { page, per_page: perPage }
    });
    return response.data;
  } catch (error) {
    console.error('Erreur API:', error);
    throw error;
  }
};

// Alias pour compatibilité (à supprimer progressivement)
export const getSpaces = getSpacesFeed;

export const getSpaceDetails = (spaceId) => {
  return axios.get(`${API_URL}/${spaceId}`);
};

const getSpaceMessages = async (spaceId, page = 1, perPage = 50) => {
  try {
    const response = await axios.get(`/api/v1/spaces/${spaceId}/messages`, {
      params: { page, per_page: perPage }
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
      params: { page, per_page: perPage }
    });
    return response.data;
  } catch (error) {
    console.error('Erreur chargement des spaces:', error);
    throw error;
  }
};

// Fonctions ajoutées
const sendAudioSignal = (spaceId, signalData) => {
  return axios.post(`/api/v1/spaces/${spaceId}/audio-signal`, { signalData: signalData });
};

export const joinSpace = async (spaceId) => {
  return axios.post(`${API_URL}/${spaceId}/join`, {});
};

const SpaceService = {
  getSpacesFeed,
  getSpaces, // Maintenir temporairement
  getSpaceDetails,
  fetchSpaces,
  getSpaceMessages,
  sendAudioSignal, // Function from voice chat feature
  joinSpace,       // Function from main branch
};

export default SpaceService;