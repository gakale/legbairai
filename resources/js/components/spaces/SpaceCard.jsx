// resources/js/components/spaces/SpaceCard.jsx
import React from 'react';
import { Link } from 'react-router-dom';
import Button from '../common/Button';

const SpaceCard = ({ space, onDelete, showActions = false }) => {
    const defaultAvatar = "https://ui-avatars.com/api/?background=6B46C1&color=fff&size=128&name=";
    
    // Formatage des données
    const participantCount = space.participants_count || 0;
    const hostName = space.host?.display_name || space.host?.username || 'Hôte inconnu';
    const hostAvatar = space.host?.avatar || `${defaultAvatar}${encodeURIComponent(hostName)}`;
    
    // Gestion du statut
    const getStatusInfo = (status) => {
        switch (status) {
            case 'live':
                return {
                    text: 'En direct',
                    className: 'bg-red-600 text-white',
                    dot: '🔴'
                };
            case 'scheduled':
                return {
                    text: 'Programmé',
                    className: 'bg-blue-600 text-white',
                    dot: '📅'
                };
            case 'ended':
                return {
                    text: 'Terminé',
                    className: 'bg-gray-600 text-white',
                    dot: '⏹️'
                };
            default:
                return {
                    text: 'Inconnu',
                    className: 'bg-gray-500 text-white',
                    dot: '❓'
                };
        }
    };

    const statusInfo = getStatusInfo(space.status);

    // Formatage de la date
    const formatDate = (dateString) => {
        if (!dateString) return null;
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    // Formatage du type de space
    const getTypeInfo = (type) => {
        switch (type) {
            case 'public_free':
                return { text: 'Public', icon: '🌐', className: 'text-green-400' };
            case 'public_paid':
                return { text: 'Payant', icon: '💰', className: 'text-yellow-400' };
            case 'private':
                return { text: 'Privé', icon: '🔒', className: 'text-purple-400' };
            default:
                return { text: 'Public', icon: '🌐', className: 'text-green-400' };
        }
    };

    const typeInfo = getTypeInfo(space.type);

    return (
        <div className="bg-gb-dark-light rounded-lg border border-gb-gray-dark hover:border-gb-purple transition-all duration-200 overflow-hidden group">
            {/* En-tête avec statut */}
            <div className="p-4 pb-0">
                <div className="flex items-center justify-between mb-3">
                    <span className={`px-3 py-1 rounded-full text-xs font-medium ${statusInfo.className}`}>
                        {statusInfo.dot} {statusInfo.text}
                    </span>
                    <span className={`flex items-center text-xs ${typeInfo.className}`}>
                        {typeInfo.icon} {typeInfo.text}
                    </span>
                </div>

                {/* Titre et description */}
                <Link 
                    to={`/spaces/${space.id}`}
                    className="block group-hover:text-gb-purple transition-colors"
                >
                    <h3 className="text-lg font-semibold text-gb-white mb-2 line-clamp-2">
                        {space.title}
                    </h3>
                </Link>

                {space.description && (
                    <p className="text-gb-light-gray text-sm mb-3 line-clamp-2">
                        {space.description}
                    </p>
                )}
            </div>

            {/* Informations de l'hôte */}
            <div className="px-4 pb-3">
                <div className="flex items-center space-x-3">
                    <img
                        src={hostAvatar}
                        alt={`Avatar de ${hostName}`}
                        className="w-8 h-8 rounded-full object-cover border border-gb-gray-dark"
                    />
                    <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-gb-white truncate">
                            {hostName}
                        </p>
                        <div className="flex items-center space-x-4 text-xs text-gb-light-gray">
                            <span>👥 {participantCount} participant{participantCount > 1 ? 's' : ''}</span>
                            {space.scheduled_at && space.status === 'scheduled' && (
                                <span>📅 {formatDate(space.scheduled_at)}</span>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Actions et boutons */}
            <div className="px-4 pb-4">
                <div className="flex items-center justify-between">
                    <div className="flex space-x-2">
                        {space.status === 'live' && (
                            <Button
                                to={`/spaces/${space.id}`}
                                variant="primary"
                                size="sm"
                                className="flex items-center space-x-1"
                            >
                                <span>🎤</span>
                                <span>Rejoindre</span>
                            </Button>
                        )}
                        
                        {space.status === 'scheduled' && (
                            <Button
                                to={`/spaces/${space.id}`}
                                variant="outline"
                                size="sm"
                                className="flex items-center space-x-1"
                            >
                                <span>👁️</span>
                                <span>Voir</span>
                            </Button>
                        )}

                        {space.status === 'ended' && (
                            <Button
                                to={`/spaces/${space.id}`}
                                variant="secondary"
                                size="sm"
                                className="flex items-center space-x-1"
                            >
                                <span>📝</span>
                                <span>Résumé</span>
                            </Button>
                        )}
                    </div>

                    {/* Actions de gestion si c'est le propriétaire */}
                    {showActions && (
                        <div className="flex space-x-1">
                            <Button
                                to={`/spaces/${space.id}/edit`}
                                variant="ghost"
                                size="sm"
                                className="p-2"
                                title="Modifier"
                            >
                                ✏️
                            </Button>
                            {onDelete && (
                                <Button
                                    onClick={() => onDelete(space)}
                                    variant="ghost"
                                    size="sm"
                                    className="p-2 text-red-400 hover:text-red-300"
                                    title="Supprimer"
                                >
                                    🗑️
                                </Button>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Indicateur WebRTC pour les spaces live */}
            {space.status === 'live' && (
                <div className="bg-gradient-to-r from-gb-purple to-purple-600 px-4 py-2">
                    <div className="flex items-center justify-center text-white text-xs">
                        <span className="animate-pulse mr-2">🎧</span>
                        <span>Audio WebRTC en temps réel</span>
                    </div>
                </div>
            )}
        </div>
    );
};

export default SpaceCard;