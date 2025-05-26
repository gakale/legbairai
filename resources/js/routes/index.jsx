import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

// Pages
import HomePage from '../pages/HomePage';
// Importez d'autres pages ici au fur et à mesure que vous les créez

// Layout et composants communs
// import MainLayout from '../components/layouts/MainLayout';

// Route protégée qui nécessite une authentification
const ProtectedRoute = ({ children }) => {
  const { isAuthenticated, loading } = useAuth();
  
  if (loading) {
    return <div className="flex justify-center items-center min-h-screen">
      <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
    </div>;
  }
  
  if (!isAuthenticated) {
    return <Navigate to="/login" />;
  }
  
  return children;
};

// Configuration des routes de l'application
const AppRoutes = () => {
  return (
    <BrowserRouter>
      <Routes>
        {/* Routes publiques */}
        <Route path="/" element={<HomePage />} />
        
        {/* Routes qui nécessitent une authentification */}
        <Route 
          path="/spaces/create" 
          element={
            <ProtectedRoute>
              {/* <CreateSpacePage /> */}
              <div>Page de création d'espace (à implémenter)</div>
            </ProtectedRoute>
          } 
        />
        
        <Route 
          path="/spaces/:id" 
          element={
            // <SpaceDetailPage />
            <div>Page de détail d'un espace (à implémenter)</div>
          } 
        />
        
        <Route 
          path="/profile" 
          element={
            <ProtectedRoute>
              {/* <ProfilePage /> */}
              <div>Page de profil (à implémenter)</div>
            </ProtectedRoute>
          } 
        />
        
        {/* Route pour la page 404 */}
        <Route 
          path="*" 
          element={
            <div className="flex flex-col items-center justify-center min-h-screen">
              <h1 className="text-4xl font-bold mb-4">404</h1>
              <p className="text-xl mb-6">Page non trouvée</p>
              <a href="/" className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Retour à l'accueil
              </a>
            </div>
          } 
        />
      </Routes>
    </BrowserRouter>
  );
};

export default AppRoutes;
