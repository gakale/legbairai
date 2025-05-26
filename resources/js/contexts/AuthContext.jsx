import React, { createContext, useState, useEffect, useContext } from 'react';
import { authService } from '../services/authService';

// Création du contexte d'authentification
const AuthContext = createContext(null);

// Hook personnalisé pour utiliser le contexte d'authentification
export const useAuth = () => useContext(AuthContext);

export const AuthProvider = ({ children }) => {
  const [currentUser, setCurrentUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Vérifier si un utilisateur est déjà connecté au chargement
    const user = authService.getCurrentUser();
    if (user) {
      setCurrentUser(user.user);
      // Configurer le token pour les requêtes API
      axios.defaults.headers.common['Authorization'] = `Bearer ${user.token}`;
    }
    setLoading(false);
  }, []);

  // Fonction de connexion
  const login = async (credentials) => {
    try {
      const data = await authService.login(credentials);
      setCurrentUser(data.user);
      return data;
    } catch (error) {
      throw error;
    }
  };

  // Fonction d'inscription
  const register = async (userData) => {
    try {
      const data = await authService.register(userData);
      setCurrentUser(data.user);
      return data;
    } catch (error) {
      throw error;
    }
  };

  // Fonction de déconnexion
  const logout = async () => {
    await authService.logout();
    setCurrentUser(null);
  };

  // Valeur du contexte
  const value = {
    currentUser,
    loading,
    login,
    register,
    logout,
    isAuthenticated: !!currentUser
  };

  return (
    <AuthContext.Provider value={value}>
      {!loading && children}
    </AuthContext.Provider>
  );
};

export default AuthContext;
