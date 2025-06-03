// resources/js/pages/SpaceDetailPage.jsx
import React, { useState, useEffect, useCallback, useRef } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import SpaceService from '../services/SpaceService';
import Button from '../components/common/Button';
// Importer les composants pour le chat, la liste des participants, etc. (nous les créerons/améliorerons)
// import ParticipantList from '../components/spaces/ParticipantList';
// import ChatWindow from '../components/spaces/ChatWindow';
// import SpaceControls from '../components/spaces/SpaceControls';

const defaultAvatar = "https://ui-avatars.com/api/?background=6B46C1&color=fff&size=128&name=";

const SpaceDetailPage = () => {
    const { spaceId } = useParams();
    const { currentUser, isAuthenticated } = useAuth();
    const navigate = useNavigate();
    
    // Définir currentUserId à partir de currentUser
    const currentUserId = currentUser ? currentUser.id : null;

    const [space, setSpace] = useState(null);
    const [participants, setParticipants] = useState([]); // Géré par Presence Channel
    const [messages, setMessages] = useState([]);
    const [pinnedMessage, setPinnedMessage] = useState(null); // Si on le gère séparément
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');
    const [connectionStatus, setConnectionStatus] = useState('Déconnecté');
    const [newMessageContent, setNewMessageContent] = useState('');

    // Références pour les callbacks Echo afin d'éviter des dépendances excessives dans useEffect
    const messagesRef = useRef(messages);
    const participantsRef = useRef(participants);
    const pinnedMessageRef = useRef(pinnedMessage);

    useEffect(() => {
        messagesRef.current = messages;
        participantsRef.current = participants;
        pinnedMessageRef.current = pinnedMessage;
    }, [messages, participants, pinnedMessage]);


    const fetchInitialData = useCallback(async () => {
        setIsLoading(true);
        setError('');
        try {
            const spaceDetailsResponse = await SpaceService.getSpaceDetails(spaceId);
            setSpace(spaceDetailsResponse.data.data); // Supposant UserResource enveloppe dans 'data'

            const messagesResponse = await SpaceService.getSpaceMessages(spaceId, 1); // Charger la première page de messages
            setMessages(messagesResponse.data.slice().reverse()); // Inverser pour afficher les plus anciens en premier
                                                                // ou gérer l'ordre dans le rendu

            // Trouver un message épinglé parmi les messages chargés (si la logique est là)
            // const foundPinned = messagesResponse.data.find(m => m.is_pinned);
            // if (foundPinned) setPinnedMessage(foundPinned);

        } catch (err) {
            console.error("Erreur chargement données du Space:", err);
            setError(err.response?.data?.message || "Impossible de charger les détails du Space.");
            if (err.response?.status === 404) setError("Space non trouvé.");
        }
        setIsLoading(false);
    }, [spaceId]);

    useEffect(() => {
        fetchInitialData();

        // Mode de développement: si nous sommes en environnement local, nous simulons les événements
        // de présence pour faciliter le développement sans dépendre de l'authentification WebSocket
        const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        
        // Ne pas initialiser la connexion WebSocket pour les utilisateurs non authentifiés
        if (!isAuthenticated || !currentUserId) {
            console.log("Utilisateur non authentifié ou ID manquant, Echo non initialisé pour le canal de présence.");
            setConnectionStatus("Mode lecture seule (non connecté)");
            
            // En mode développement, simuler les participants pour les utilisateurs non connectés
            if (isDevelopment) {
                console.log("Mode développement: simulation des participants pour utilisateur non connecté");
                setTimeout(() => {
                    if (space && space.participants) {
                        setParticipants(space.participants);
                    }
                }, 1000);
            }
            return; // Sortir de l'effet pour éviter d'initialiser Echo
        }

        // Vérifier si Echo est disponible
        if (!window.Echo) {
            console.error("Echo n'est pas initialisé !");
            setConnectionStatus("Erreur Echo");
            return;
        }

        console.log(`Tentative de connexion au canal de présence : space.${spaceId}`);
        setConnectionStatus('Connexion...');
        
        let presenceChannel;
        
        try {
            presenceChannel = window.Echo.join(`space.${spaceId}`);
        } catch (error) {
            console.error('Erreur lors de la connexion au canal de présence:', error);
            setConnectionStatus('Erreur de connexion WebSocket');
            
            // En mode développement, nous continuons malgré l'erreur
            if (isDevelopment && space && space.participants) {
                console.warn('Mode développement: simulation des participants');
                setTimeout(() => setParticipants(space.participants), 500);
            }
            return; // Sortir de la fonction si la connexion échoue
        }
        
        presenceChannel
            .on('pusher:subscription_succeeded', () => {
                setConnectionStatus(`Connecté au canal space.${spaceId}`);
                console.log('Connexion WebSocket réussie!');
            })
            .on('pusher:subscription_error', (status) => {
                setConnectionStatus(`Échec connexion canal (code: ${status}). Vérifiez l'auth et les logs.`);
                console.error(`Erreur abonnement PRÉSENCE canal space.${spaceId}:`, status);
                
                // En mode développement, nous continuons malgré l'erreur
                if (isDevelopment) {
                    console.warn('Mode développement: simulation des événements de présence');
                    // Simuler les participants déjà récupérés par l'API
                    setTimeout(() => {
                        if (space && space.participants) {
                            setParticipants(space.participants);
                        }
                    }, 500);
                }
            })
            .here((users) => {
                console.log('Membres présents:', users);
                setParticipants(users);
            })
            .joining((user) => {
                console.log('Nouveau membre:', user);
                setParticipants(prev => {
                    if (!prev.find(p => p.id === user.id)) return [...prev, user];
                    return prev;
                });
            })
            .leaving((user) => {
                console.log('Membre parti:', user);
                setParticipants(prev => prev.filter(p => p.id !== user.id));
            })
            .listen('.message.new', (eventData) => {
                console.log('Nouveau message reçu:', eventData);
                setMessages(prev => [...prev, eventData.message]);
                // Auto-scroll vers le bas du chat ici
            })
            .listen('.participant.hand_status', (eventData) => {
                setParticipants(prev =>
                    prev.map(p => p.id === eventData.user_id ? { ...p, has_raised_hand: eventData.has_raised_hand } : p)
                );
            })
            .listen('.participant.role_changed', (eventData) => {
                setParticipants(prev =>
                    prev.map(p => p.id === eventData.participant.user.id ? { ...p, ...eventData.participant.user, role: eventData.participant.role, role_label: eventData.participant.role_label } : p)
                );
                 // Mettre à jour les infos complètes du participant, y compris le rôle et le nom d'utilisateur au cas où
                 setParticipants(prev => prev.map(p => {
                    if (p.id === eventData.participant.user.id) {
                        return {
                            ...p, // Garder les autres infos de présence
                            role: eventData.participant.role, // Nouveau rôle depuis l'événement
                            role_label: eventData.participant.role_label,
                            // Mettre à jour d'autres champs si nécessaire, ex: username si l'événement le fournit
                            username: eventData.participant.user.username || p.username,
                        };
                    }
                    return p;
                }));
            })
            .listen('.message.pinned_status_changed', (eventData) => {
                const updatedMsg = eventData.message;
                setMessages(prevMsgs =>
                    prevMsgs.map(msg =>
                        msg.id === updatedMsg.id
                            ? { ...msg, is_pinned: updatedMsg.is_pinned }
                            : { ...msg, is_pinned: false } // Si un seul épinglé
                    )
                );
                if (updatedMsg.is_pinned) setPinnedMessage(updatedMsg);
                else if (pinnedMessageRef.current && pinnedMessageRef.current.id === updatedMsg.id) setPinnedMessage(null);
            })
            .listen('.participant.muted_status_changed', (eventData) => {
                setParticipants(prev =>
                    prev.map(p => p.id === eventData.user_id ? { ...p, is_muted_by_host: eventData.is_muted_by_host } : p)
                );
            });


        return () => {
            window.Echo.leave(`space.${spaceId}`);
            setConnectionStatus('Déconnecté');
        };
    }, [spaceId, fetchInitialData, currentUserId, isAuthenticated]); // Ajout de isAuthenticated aux dépendances

    const handleSendMessage = async (e) => {
        e.preventDefault();
        if (!newMessageContent.trim()) return;
        try {
            // L'événement WebSocket mettra à jour la liste des messages pour tout le monde
            await SpaceService.sendMessageInSpace(spaceId, newMessageContent);
            setNewMessageContent('');
            // On pourrait ajouter le message à l'état local ici pour un "optimistic update",
            // mais l'événement WebSocket devrait le faire de manière plus fiable.
        } catch (err) {
            console.error("Erreur envoi message:", err);
            // Afficher une erreur à l'utilisateur
        }
    };

    if (isLoading) return <div className="text-center py-20 text-gb-light-gray">Chargement du Space...</div>;
    if (error) return <div className="text-center py-20 text-red-400">{error}</div>;
    if (!space) return <div className="text-center py-20 text-gb-light-gray">Space non trouvé.</div>;

    const isHost = isAuthenticated && currentUser && currentUser.id === space.host?.id;

    return (
        <div className="container mx-auto py-10 px-4 min-h-screen">
            <div className="bg-gb-dark-lighter rounded-card shadow-gb-strong p-6 md:p-8">
                {/* En-tête du Space */}
                <div className="mb-6 pb-4 border-b border-[rgba(255,255,255,0.1)]">
                    <div className="flex justify-between items-start">
                        <div>
                            <h1 className="text-3xl md:text-4xl font-bold text-gb-white">{space.title}</h1>
                            <p className="text-gb-light-gray">Animé par <Link to={`/profile/${space.host?.id}`} className="font-semibold hover:text-gb-primary-light">{space.host?.username}</Link></p>
                        </div>
                        <span className={`px-3 py-1 rounded-full text-sm font-semibold ${space.status === 'live' ? 'bg-gb-accent text-gb-white animate-pulse' : 'bg-gb-gray text-gb-white'}`}>
                            {space.status === 'live' && '● '} {space.status_label}
                        </span>
                    </div>
                    {space.description && <p className="text-gb-gray mt-3">{space.description}</p>}
                </div>

                {/* Message épinglé */}
                {pinnedMessage && (
                    <div className="mb-4 p-3 bg-[rgba(255,215,0,0.1)] border border-yellow-500 rounded-md text-sm">
                        <p className="font-semibold text-yellow-400">📌 Message Épinglé par {pinnedMessage.sender?.username}:</p>
                        <p className="text-gb-light-gray">{pinnedMessage.content}</p>
                    </div>
                )}

                {/* Layout principal : Participants à gauche, Chat à droite */}
                <div className="flex flex-col md:flex-row gap-6 md:gap-8">
                    {/* Colonne Participants */}
                    <div className="w-full md:w-1/3 lg:w-1/4 bg-gb-dark p-4 rounded-lg">
                        <h3 className="text-xl font-semibold text-gb-white mb-3">Participants ({participants.length})</h3>
                        <ul className="space-y-3 max-h-[60vh] overflow-y-auto">
                            {participants.map(p => (
                                <li key={p.id} className="flex items-center gap-3 p-2 bg-gb-dark-lighter rounded-md">
                                    <img src={p.avatar_url || `${defaultAvatar}${encodeURIComponent(p.username)}`} alt={p.username} className="w-10 h-10 rounded-full object-cover"/>
                                    <div>
                                        <span className="font-medium text-gb-white">{p.username}</span>
                                        <span className="block text-xs text-gb-gray">{p.role_label || p.role}</span>
                                    </div>
                                    {p.has_raised_hand && <span className="ml-auto text-xl" title="Main levée">🖐️</span>}
                                    {/* TODO: Ajouter indicateur mute, actions de modération ici si isHost */}
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Colonne Chat */}
                    <div className="w-full md:w-2/3 lg:w-3/4 flex flex-col">
                        <div className="flex-grow bg-gb-dark p-4 rounded-lg mb-4 max-h-[60vh] overflow-y-auto flex flex-col-reverse"> {/* flex-col-reverse pour que les nouveaux messages soient en bas et scrollable */}
                            {/* Les messages seront mappés ici, de bas en haut */}
                            <div className="space-y-4">
                                {messages.map(msg => (
                                    <div key={msg.id} className={`flex ${currentUser?.id === msg.sender?.id ? 'justify-end' : 'justify-start'}`}>
                                        <div className={`max-w-[70%] p-3 rounded-lg ${currentUser?.id === msg.sender?.id ? 'bg-gb-primary text-gb-white' : 'bg-gb-dark-lighter text-gb-light-gray'}`}>
                                            {currentUser?.id !== msg.sender?.id && (
                                                <p className="text-xs font-semibold text-gb-primary-light mb-0.5">{msg.sender?.username}</p>
                                            )}
                                            <p className="text-sm whitespace-pre-wrap">{msg.content}</p>
                                            <p className="text-xs opacity-70 mt-1 text-right">{msg.created_at_formatted}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                        {/* Formulaire d'envoi de message */}
                        {isAuthenticated && space.status === 'live' && (
                            <form onSubmit={handleSendMessage} className="flex gap-2 items-center">
                                <input
                                    type="text"
                                    value={newMessageContent}
                                    onChange={(e) => setNewMessageContent(e.target.value)}
                                    placeholder="Écrivez un message..."
                                    className="flex-grow px-3 py-2 bg-gb-dark border border-gb-gray rounded-md shadow-sm placeholder-gb-gray focus:outline-none focus:ring-gb-primary focus:border-gb-primary sm:text-sm text-gb-white"
                                />
                                <Button type="submit" variant="primary" className="py-2">Envoyer</Button>
                            </form>
                        )}
                    </div>
                </div>
                 {/* TODO: Ajouter les contrôles du Space (Start/End, lever la main, dons, etc.) */}
            </div>
        </div>
    );
};

export default SpaceDetailPage;