// resources/js/App.jsx
import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import Layout from './components/Layout';
import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import SpaceDetailPage from './Pages/SpaceDetailPage';
import UserProfilePage from './pages/UserProfilePage';
import CreateSpacePage from './pages/CreateSpacePage';
import ProtectedRoute from './components/ProtectedRoute';

function App() {
    console.log("[App.jsx] - Le composant App est en cours de rendu.");
    
    return (
        <AuthProvider>
            <Router>
                <Routes>
                    <Route path="/" element={<Layout />}>
                        {/* Routes publiques */}
                        <Route index element={<HomePage />} />
                        <Route path="login" element={<LoginPage />} />
                        <Route path="register" element={<RegisterPage />} />
                        
                        {/* Routes des spaces - CORRIGÉES */}
                        <Route path="spaces/:spaceId" element={<SpaceDetailPage />} />
                        <Route 
                            path="spaces/create" 
                            element={
                                <ProtectedRoute>
                                    <CreateSpacePage />
                                </ProtectedRoute>
                            } 
                        />
                        
                        {/* Routes des profils */}
                        <Route path="profile/:username" element={<UserProfilePage />} />
                        <Route 
                            path="profile/edit" 
                            element={
                                <ProtectedRoute>
                                    <div className="text-center py-20">
                                        <h2>Édition de profil - À implémenter</h2>
                                    </div>
                                </ProtectedRoute>
                            } 
                        />
                        
                        {/* Page 404 */}
                        <Route path="*" element={<NotFoundPage />} />
                    </Route>
                </Routes>
            </Router>
        </AuthProvider>
    );
}

const NotFoundPage = () => (
    <div className="min-h-screen bg-gb-dark flex items-center justify-center">
        <div className="text-center">
            <h1 className="text-4xl font-bold text-gb-white mb-4">404</h1>
            <p className="text-gb-light-gray mb-6">Page non trouvée</p>
            <a 
                href="/" 
                className="px-6 py-3 bg-gb-purple hover:bg-gb-purple-dark text-white rounded-lg transition-colors"
            >
                Retour à l'accueil
            </a>
        </div>
    </div>
);

export default App;