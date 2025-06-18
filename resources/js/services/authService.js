// resources/js/services/AuthService.js
import axios from 'axios'; // Axios est déjà configuré dans bootstrap.js

const API_URL = '/api/v1'; // Préfixe de base pour vos routes API

const register = async (userData) => {
    // userData: { username, email, password, password_confirmation }
        return axios.post(`${API_URL}/register`, userData);
};

const login = async (credentials) => {
    // credentials: { email, password, device_name (optionnel pour nommer le token) }
    // D'abord, obtenir le cookie CSRF (important pour les SPAs avec Sanctum)
    await axios.get('/sanctum/csrf-cookie');
        return axios.post(`${API_URL}/login`, { ...credentials, device_name: 'spa_session' });
};

const logout = async () => {
    // Nécessite que le token soit envoyé dans les headers (Axios le fait si configuré)
    return axios.post(`${API_URL}/logout`);
};

const getCurrentUser = async () => {
    // Récupère l'utilisateur authentifié
    // Nécessite que le token soit envoyé
    return axios.get(`${API_URL}/user`);
};

const AuthService = {
    register,
    login,
    logout,
    getCurrentUser,
};

export default AuthService;