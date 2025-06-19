// resources/js/components/ProtectedRoute.jsx
import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const ProtectedRoute = ({ children }) => {
    const { isAuthenticated, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return (
            <div className="min-h-screen bg-gb-dark flex items-center justify-center">
                <div className="text-center">
                    <div className="spinner mb-4"></div>
                    <div className="text-gb-white">Vérification de l'authentification...</div>
                </div>
            </div>
        );
    }

    if (!isAuthenticated) {
        // Rediriger vers la page de connexion avec l'URL de retour
        return <Navigate to="/login" state={{ from: location }} replace />;
    }

    return children;
};

export default ProtectedRoute;