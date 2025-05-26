import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Layout from './components/Layout';
import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import SpaceDetailPage from './pages/SpaceDetailPage';
import UserProfilePage from './pages/UserProfilePage';

function App() {
    console.log("[App.jsx] - Le composant App est en cours de rendu.");
    return (
        <Router>
            <Routes>
                <Route path="/" element={<Layout />}>
                    <Route index element={<HomePage />} />
                    <Route path="login" element={<LoginPage />} />
                    <Route path="register" element={<RegisterPage />} />
                    <Route path="space/:spaceId" element={<SpaceDetailPage />} />
                    <Route path="profile/:userId" element={<UserProfilePage />} />
                    <Route path="*" element={<NotFoundPage />} />
                </Route>
            </Routes>
        </Router>
    );
}

const NotFoundPage = () => <div className="text-center py-20"><h2>404 - Page Non Trouvée</h2></div>;

export default App; // Assurez-vous que App est exporté par défaut