// resources/js/pages/RegisterPage.jsx
import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { useNavigate, Link } from 'react-router-dom';
import Button from '../components/common/Button';

const RegisterPage = () => {
    const [username, setUsername] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const { register } = useAuth();
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (password !== passwordConfirmation) {
            setError("Les mots de passe ne correspondent pas.");
            return;
        }
        setError('');
        setLoading(true);
        try {
            await register({ username, email, password, password_confirmation: passwordConfirmation });
            navigate('/'); // Rediriger vers l'accueil après inscription réussie
        } catch (err) {
            if (err.response?.data?.errors) {
                // Gérer les erreurs de validation multiples de Laravel
                const messages = Object.values(err.response.data.errors).flat().join(' ');
                setError(messages);
            } else {
                setError(err.response?.data?.message || err.message || 'Échec de l\'inscription.');
            }
        }
        setLoading(false);
    };

    return (
        <div className="flex flex-col items-center justify-center min-h-[calc(100vh-160px)] px-4">
            <div className="w-full max-w-md p-8 space-y-6 bg-gb-dark-lighter rounded-card shadow-gb-strong">
                <h2 className="text-3xl font-bold text-center text-gb-white">Créer un compte</h2>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label htmlFor="username" className="block text-sm font-medium text-gb-light-gray">Nom d'utilisateur</label>
                        <input id="username" type="text" required className="mt-1 block w-full input-style" value={username} onChange={(e) => setUsername(e.target.value)} />
                    </div>
                    <div>
                        <label htmlFor="email" className="block text-sm font-medium text-gb-light-gray">Email</label>
                        <input id="email" type="email" required className="mt-1 block w-full input-style" value={email} onChange={(e) => setEmail(e.target.value)} />
                    </div>
                    <div>
                        <label htmlFor="password" className="block text-sm font-medium text-gb-light-gray">Mot de passe</label>
                        <input id="password" type="password" required className="mt-1 block w-full input-style" value={password} onChange={(e) => setPassword(e.target.value)} />
                    </div>
                    <div>
                        <label htmlFor="passwordConfirmation" className="block text-sm font-medium text-gb-light-gray">Confirmer le mot de passe</label>
                        <input id="passwordConfirmation" type="password" required className="mt-1 block w-full input-style" value={passwordConfirmation} onChange={(e) => setPasswordConfirmation(e.target.value)} />
                    </div>
                    {error && <p className="text-sm text-red-400">{error}</p>}
                    <div>
                        <Button type="submit" variant="primary" className="w-full" disabled={loading}>
                            {loading ? 'Création...' : 'S\'inscrire'}
                        </Button>
                    </div>
                </form>
                 <p className="text-sm text-center text-gb-light-gray">
                    Déjà un compte ?{' '}
                    <Link to="/login" className="font-medium text-gb-primary-light hover:text-gb-accent">
                        Se connecter
                    </Link>
                </p>
            </div>
        </div>
    );
};

// Ajouter une classe CSS pour les inputs si vous voulez les réutiliser
// Dans app.css ou directement avec @apply dans le JSX (nécessite config Tailwind)
// .input-style { @apply px-3 py-2 bg-gb-dark border border-gb-gray rounded-md shadow-sm placeholder-gb-gray focus:outline-none focus:ring-gb-primary focus:border-gb-primary sm:text-sm; }
// Pour l'instant, j'ai mis les classes Tailwind directement sur les inputs.
// Pour la classe 'input-style', vous la définiriez dans resources/css/app.css sous @layer components:
// @layer components {
//   .input-style {
//     @apply mt-1 block w-full px-3 py-2 bg-gb-dark border border-gb-gray rounded-md shadow-sm placeholder-gb-gray focus:outline-none focus:ring-gb-primary focus:border-gb-primary sm:text-sm;
//   }
// }
export default RegisterPage;