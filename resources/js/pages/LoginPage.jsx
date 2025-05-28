// resources/js/pages/LoginPage.jsx
import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { useNavigate, Link } from 'react-router-dom';
import Button from '../components/common/Button'; // Notre composant bouton

const LoginPage = () => {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const { login } = useAuth();
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setLoading(true);
        try {
            await login({ email, password });
            navigate('/'); // Rediriger vers l'accueil après connexion réussie
        } catch (err) {
            setError(err.response?.data?.message || err.message || 'Échec de la connexion.');
        }
        setLoading(false);
    };

    return (
        <div className="flex flex-col items-center justify-center min-h-[calc(100vh-160px)] px-4"> {/* 160px = approx hauteur navbar + footer */}
            <div className="w-full max-w-md p-8 space-y-6 bg-gb-dark-lighter rounded-card shadow-gb-strong">
                <h2 className="text-3xl font-bold text-center text-gb-white">Se connecter</h2>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div>
                        <label htmlFor="email" className="block text-sm font-medium text-gb-light-gray">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autoComplete="email"
                            required
                            className="mt-1 block w-full px-3 py-2 bg-gb-dark border border-gb-gray rounded-md shadow-sm placeholder-gb-gray focus:outline-none focus:ring-gb-primary focus:border-gb-primary sm:text-sm"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                        />
                    </div>
                    <div>
                        <label htmlFor="password" className="block text-sm font-medium text-gb-light-gray">Mot de passe</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autoComplete="current-password"
                            required
                            className="mt-1 block w-full px-3 py-2 bg-gb-dark border border-gb-gray rounded-md shadow-sm placeholder-gb-gray focus:outline-none focus:ring-gb-primary focus:border-gb-primary sm:text-sm"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                        />
                    </div>
                    {error && <p className="text-sm text-red-400">{error}</p>}
                    <div>
                        <Button type="submit" variant="primary" className="w-full" disabled={loading}>
                            {loading ? 'Connexion...' : 'Se connecter'}
                        </Button>
                    </div>
                </form>
                <p className="text-sm text-center text-gb-light-gray">
                    Pas encore de compte ?{' '}
                    <Link to="/register" className="font-medium text-gb-primary-light hover:text-gb-accent">
                        S'inscrire
                    </Link>
                </p>
            </div>
        </div>
    );
};
export default LoginPage;