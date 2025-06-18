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

const sendAudioSignal = (spaceId, signalData) => {
  // The backend expects `signalData` directly, which is the WebRTC signal object.
  // The key `signal` was used in the thought process, but the controller expects `signalData`.
  // Let's ensure the payload is { signalData: signalData } as per the controller validation rule.
  // Correction: The controller action validates `signalData` in the request,
  // so the payload should be ` { signalData: signalData } `.
  // However, the initial prompt for the backend route and controller action was:
  // `broadcast(new AudioStreamEvent($space->id, $validated['signalData'], $user->id))->toOthers();`
  // and `$request->validate(['signalData' => 'required|array'])`.
  // The event on the client side is `eventData.signal`.
  // Let's stick to what the controller expects: `signalData` as the key in the JSON payload.
  return axios.post(`/api/v1/spaces/${spaceId}/audio-signal`, { signalData: signalData }, {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json'
    }
  });
};

const SpaceService = {
  getSpacesFeed,
  getSpaces, // Maintenir temporairement
  getSpaceDetails,
  fetchSpaces,
  getSpaceMessages,
  sendAudioSignal, // Add the new function here
};

export default SpaceService;