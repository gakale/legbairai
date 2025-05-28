// resources/js/services/UserService.js
import axios from 'axios';

const API_URL = '/api/v1/users';

const getUserProfile = (userId) => {
    // L'endpoint public pour le profil ne nécessite pas d'authentification
    // mais l'API peut retourner des infos supplémentaires si l'utilisateur EST authentifié (ex: is_followed_by_current_user)
    // Axios enverra le token d'authentification s'il est configuré globalement, ce qui est bien.
    return axios.get(`${API_URL}/${userId}`);
};

const followUser = (userId) => {
    // Nécessite d'être authentifié (Axios devrait envoyer le token)
    return axios.post(`${API_URL}/${userId}/follow`);
};

const unfollowUser = (userId) => {
    // Nécessite d'être authentifié
    return axios.post(`${API_URL}/${userId}/unfollow`); // Ou axios.delete si vous avez utilisé DELETE
};

const UserService = {
    getUserProfile,
    followUser,
    unfollowUser,
};

export default UserService;