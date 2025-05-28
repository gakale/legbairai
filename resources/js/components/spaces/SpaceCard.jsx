// resources/js/components/spaces/SpaceCard.jsx
import React from 'react';
import { Link } from 'react-router-dom';
import Button from '../common/Button'; // Notre composant bouton

// Placeholder pour l'avatar par défaut de l'hôte ou image de couverture
const defaultAvatar = "https://ui-avatars.com/api/?background=6B46C1&color=fff&size=128&name=";
const defaultCover = "https://via.placeholder.com/400x200/1A1A2E/E2E8F0?text=Space+Image"; // Placeholder

const SpaceCard = ({ space }) => {
    if (!space) return null;

    const hostName = space.host?.username || 'Hôte inconnu';
    const coverImageUrl = space.cover_image_url || `${defaultCover}&text=${encodeURIComponent(space.title)}`;

    return (
        <div className="bg-gb-dark-lighter rounded-card shadow-lg overflow-hidden flex flex-col transition-all duration-300 hover:shadow-gb-strong hover:translate-y-[-5px]">
            <Link to={`/space/${space.id}`} className="block">
                <img
                    src={coverImageUrl}
                    alt={`Couverture pour ${space.title}`}
                    className="w-full h-40 object-cover"
                />
            </Link>

            <div className="p-5 flex flex-col flex-grow">
                <div className="mb-3">
                    {space.status === 'live' && (
                        <span className="inline-block bg-gb-accent text-gb-white px-3 py-1 rounded-full text-xs font-semibold mr-2 animate-pulse">
                            ● LIVE
                        </span>
                    )}
                    {space.status === 'scheduled' && (
                        <span className="inline-block bg-gb-primary-light text-gb-dark px-3 py-1 rounded-full text-xs font-semibold mr-2">
                            Programmé
                        </span>
                    )}
                    <span className="text-xs text-gb-gray">{space.type_label}</span>
                </div>

                <Link to={`/space/${space.id}`} className="block mb-2">
                    <h3 className="text-xl font-bold text-gb-white hover:text-gb-primary-light transition-colors duration-200 truncate" title={space.title}>
                        {space.title}
                    </h3>
                </Link>

                <div className="flex items-center text-sm text-gb-light-gray mb-3">
                    <img
                        src={space.host?.avatar_url || `${defaultAvatar}${encodeURIComponent(hostName)}`}
                        alt={hostName}
                        className="w-6 h-6 rounded-full mr-2 object-cover"
                    />
                    <span>Animé par <Link to={`/profile/${space.host?.id}`} className="font-semibold hover:underline">{hostName}</Link></span>
                </div>

                <p className="text-sm text-gb-gray mb-4 flex-grow line-clamp-3">
                    {space.description || "Aucune description pour ce Space."}
                </p>
                <div className="flex justify-between items-center text-sm text-gb-light-gray mt-auto pt-3 border-t border-[rgba(255,255,255,0.1)]">
                    <span>
                        {space.status === 'live'
                            ? `${space.active_participants_count || 0} participant(s)`
                            : `Début: ${space.scheduled_at ? new Date(space.scheduled_at).toLocaleDateString('fr-FR', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A'}`
                        }
                    </span>
                    <Link to={`/space/${space.id}`}>
                        <Button variant="secondary" className="py-1 px-3 text-xs">
                            {space.status === 'live' ? 'Rejoindre' : 'Voir Détails'}
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    );
};

// Helper CSS pour clamp-lines (si vous voulez du clamping JS ou pour info)
// Tailwind a un plugin pour line-clamp: @tailwindcss/line-clamp
// npm install -D @tailwindcss/line-clamp
// puis dans tailwind.config.js: plugins: [require('@tailwindcss/line-clamp')]
// et utiliser: className="line-clamp-3"
// Pour l'instant, on peut utiliser une astuce CSS ou laisser le texte déborder un peu.
// J'ai ajouté une classe `clamp-lines-3` pour l'exemple, vous l'ajouteriez à votre app.css :
/*
.clamp-lines-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
*/

export default SpaceCard;