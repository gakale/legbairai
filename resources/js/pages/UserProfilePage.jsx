import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import UserService from '../services/UserService';
import { useAuth } from '../contexts/AuthContext';
import Button from '../components/common/Button';

// Placeholder pour l'avatar par défaut
const defaultAvatar = "https://ui-avatars.com/api/?background=random&color=fff&size=128&name=";

const UserProfilePage = () => {
    const { userId } = useParams(); // Récupère l'ID de l'utilisateur depuis l'URL
    const { currentUser, isAuthenticated } = useAuth(); // Utilisateur actuellement connecté
    const navigate = useNavigate();

    const [profileUser, setProfileUser] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');
    const [isFollowing, setIsFollowing] = useState(false);
    const [followInProgress, setFollowInProgress] = useState(false);

    const fetchProfile = useCallback(async () => {
        setIsLoading(true);
        setError('');
        try {
            const response = await UserService.getUserProfile(userId);
            setProfileUser(response.data.data); // Supposant que UserResource enveloppe dans 'data'
            // La propriété 'is_followed_by_current_user' devrait venir de l'API
            setIsFollowing(response.data.data.is_followed_by_current_user || false);
        } catch (err) {
            console.error("Erreur chargement profil:", err);
            setError("Impossible de charger le profil de l'utilisateur.");
            // Gérer le cas où l'utilisateur n'existe pas (404 de l'API)
            if (err.response && err.response.status === 404) {
                setError("Utilisateur non trouvé.");
                // navigate('/404'); // Optionnel: rediriger vers une page 404
            }
        }
        setIsLoading(false);
    }, [userId, navigate]);

    useEffect(() => {
        if (userId) {
            fetchProfile();
        }
    }, [userId, fetchProfile]);

    const handleFollowToggle = async () => {
        if (!isAuthenticated || !currentUser) {
            navigate('/login'); // Rediriger vers login si pas connecté
            return;
        }
        if (currentUser.id === profileUser?.id) return; // Ne pas se suivre soi-même

        setFollowInProgress(true);
        try {
            if (isFollowing) {
                await UserService.unfollowUser(userId);
                setIsFollowing(false);
                // Mettre à jour le compteur de followers localement (optimistic update ou refetch)
                setProfileUser(prev => ({ ...prev, followers_count: Math.max(0, (prev.followers_count || 1) - 1) }));
            } else {
                await UserService.followUser(userId);
                setIsFollowing(true);
                setProfileUser(prev => ({ ...prev, followers_count: (prev.followers_count || 0) + 1 }));
            }
        } catch (err) {
            console.error("Erreur follow/unfollow:", err);
            setError("Une erreur s'est produite.");
            // Revenir à l'état précédent en cas d'erreur
            setIsFollowing(prev => !prev);
            // Revertir le compteur si l'état a été changé avant l'erreur
        }
        setFollowInProgress(false);
    };


    if (isLoading) {
        return <div className="text-center py-20 text-gb-light-gray">Chargement du profil...</div>;
    }

    if (error) {
        return <div className="text-center py-20 text-red-400">{error}</div>;
    }

    if (!profileUser) {
        return <div className="text-center py-20 text-gb-light-gray">Profil non disponible.</div>;
    }

    const canFollow = isAuthenticated && currentUser && currentUser.id !== profileUser.id;

    return (
        <div className="container mx-auto py-10 px-4">
            <div className="max-w-3xl mx-auto bg-gb-dark-lighter rounded-card shadow-gb-strong p-6 md:p-10">
                <div className="flex flex-col items-center md:flex-row md:items-start gap-6 md:gap-8">
                    <img
                        src={profileUser.avatar_url || `${defaultAvatar}${encodeURIComponent(profileUser.username)}`}
                        alt={profileUser.username}
                        className="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-gb-primary object-cover"
                    />
                    <div className="flex-grow text-center md:text-left">
                        <h1 className="text-3xl md:text-4xl font-bold text-gb-white mb-1">{profileUser.username}</h1>
                        {profileUser.is_verified && (
                            <span className="inline-block bg-gb-primary text-gb-white text-xs px-2 py-0.5 rounded-full mb-3">
                                ✅ Vérifié
                            </span>
                        )}
                        {/* Bio de l'utilisateur à ajouter si disponible */}
                        {/* <p className="text-gb-light-gray mb-4">{profileUser.bio || "Aucune biographie."}</p> */}

                        <div className="flex justify-center md:justify-start gap-6 my-4">
                            <div className="text-center">
                                <span className="block text-2xl font-bold text-gb-white">{profileUser.followers_count || 0}</span>
                                <span className="text-sm text-gb-gray">Followers</span>
                            </div>
                            <div className="text-center">
                                <span className="block text-2xl font-bold text-gb-white">{profileUser.followings_count || 0}</span>
                                <span className="text-sm text-gb-gray">Following</span>
                            </div>
                            {/* Ajouter d'autres stats comme 'Spaces créés' */}
                        </div>

                        {canFollow && (
                            <Button
                                onClick={handleFollowToggle}
                                variant={isFollowing ? 'secondary' : 'primary'}
                                disabled={followInProgress}
                                className="w-full md:w-auto"
                            >
                                {followInProgress ? '...' : (isFollowing ? 'Ne plus suivre' : 'Suivre')}
                            </Button>
                        )}
                         {isAuthenticated && currentUser && currentUser.id === profileUser.id && (
                            <Button to="/profile/edit" variant="secondary" className="w-full md:w-auto mt-2 md:mt-0 md:ml-4">
                                Modifier le profil
                            </Button>
                        )}
                    </div>
                </div>

                {/* Section pour les Spaces créés par l'utilisateur (à venir) */}
                <div className="mt-10 pt-6 border-t border-[rgba(255,255,255,0.1)]">
                    <h2 className="text-2xl font-semibold text-gb-white mb-4">Spaces Créés</h2>
                    <p className="text-gb-light-gray">Les Spaces de {profileUser.username} apparaîtront ici.</p>
                    {/* Grille de SpaceCard ici */}
                </div>

                 {/* Section pour les Clips Audio créés par l'utilisateur (à venir) */}
                <div className="mt-8 pt-6 border-t border-[rgba(255,255,255,0.1)]">
                    <h2 className="text-2xl font-semibold text-gb-white mb-4">Clips Audio</h2>
                    <p className="text-gb-light-gray">Les clips de {profileUser.username} apparaîtront ici.</p>
                </div>
            </div>
        </div>
    );
};

export default UserProfilePage;