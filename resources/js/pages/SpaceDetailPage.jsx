// resources/js/pages/SpaceDetailPage.jsx
import React, { useState, useEffect, useCallback, useRef } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import SpaceService, { joinSpace } from '../services/spaceService'; // Correction de la casse
import Button from '../components/common/Button';
import Peer from 'simple-peer'; // WebRTC library

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
    const [isJoining, setIsJoining] = useState(false); // New state for join button

    // WebRTC State
    const [localStream, setLocalStream] = useState(null);
    const [peers, setPeers] = useState({});
    const peersRef = useRef({});
    const [remoteStreams, setRemoteStreams] = useState({});
    const remoteStreamsRef = useRef({});
    const [isMuted, setIsMuted] = useState(true); // Start muted by default
    const [speakingParticipants, setSpeakingParticipants] = useState({}); // {[participantId]: boolean}

    // Refs for Web Audio API parts
    const audioContextRef = useRef(null); // Single AudioContext for all analysers
    const remoteAnalysersRef = useRef({}); // Stores { [streamOwnerId]: { analyser, sourceNode, animationFrameId, dataArray } }
                                        // streamOwnerId can be currentUserId for local stream


    // Références pour les callbacks Echo afin d'éviter des dépendances excessives dans useEffect
    const messagesRef = useRef(messages);
    const participantsRef = useRef(participants);
    const pinnedMessageRef = useRef(pinnedMessage);
    // No need for localStreamRef if setLocalStream is used correctly in its own effects or callbacks

    useEffect(() => {
        messagesRef.current = messages;
    }, [messages]);

    useEffect(() => {
        participantsRef.current = participants;
    }, [participants]);

    useEffect(() => {
        pinnedMessageRef.current = pinnedMessage;
    }, [pinnedMessage]);

    useEffect(() => {
        peersRef.current = peers;
    }, [peers]);

    useEffect(() => {
        remoteStreamsRef.current = remoteStreams;
    }, [remoteStreams]);


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
            })
            // New listener for our audio signals
            .listen('.audio.signal', (eventData) => {
                const { user_id: signalUserId, signal } = eventData;
                // console.log('Received signal from Echo:', signalUserId, signal, 'Current peers:', peersRef.current);
                const peer = peersRef.current[signalUserId];

                if (signalUserId === currentUserId) {
                    // console.log("Ignoring signal from self");
                    return;
                }

                if (peer) {
                    if (signal.renegotiate || signal.transceiverRequest) {
                       // console.log('Ignoring renegotiate or transceiverRequest signal from simple-peer');
                    } else if (peer.destroyed && (signal.type === 'offer' || signal.type === 'answer')) {
                       // console.warn(`Received signal for already destroyed peer: ${signalUserId}. Attempting to recreate.`);
                        // Potentially recreate the peer here if necessary. For now, just log.
                    } else if (peer.destroyed) {
                       // console.warn(`Received signal for already destroyed peer: ${signalUserId}. Ignoring.`);
                    } else {
                       // console.log(`Received signal from ${signalUserId}, processing with peer:`, signal);
                        peer.signal(signal);
                    }
                } else {
                   // console.warn(`Peer not found for user ID: ${signalUserId} when trying to process signal. Signal data:`, signal);
                    // This can happen if a signal arrives before the peer object is created,
                    // or if the user sending the signal is not yet in the local participant list,
                    // or if the peer was destroyed but a late signal arrived.
                    // If it's an offer, we might want to queue it or create a new peer.
                    // For now, simple logging.
                }
            });


        return () => {
            if (presenceChannel) { // Make sure presenceChannel is defined
                window.Echo.leave(`space.${spaceId}`);
            }
            setConnectionStatus('Déconnecté');
            // Clean up WebRTC resources
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
            Object.values(peersRef.current).forEach(peer => peer.destroy());
            setPeers({});
            setLocalStream(null);
            setRemoteStreams({});
        };
    }, [spaceId, fetchInitialData, currentUserId, isAuthenticated, localStream]); // Added localStream


    // Unified Speaking Detection Logic for Local and Remote Streams
    useEffect(() => {
        const SPEAKING_THRESHOLD = 20; // Example threshold, might need tuning
        const FFT_SIZE = 512;

        const setupAnalyserForStream = (stream, streamOwnerId) => {
            if (!audioContextRef.current) {
                audioContextRef.current = new (window.AudioContext || window.webkitAudioContext)();
            }
            const audioContext = audioContextRef.current;

            // Clean up existing analyser for this streamOwnerId if any
            if (remoteAnalysersRef.current[streamOwnerId]) {
                const existing = remoteAnalysersRef.current[streamOwnerId];
                if (existing.animationFrameId) cancelAnimationFrame(existing.animationFrameId);
                if (existing.sourceNode) existing.sourceNode.disconnect();
            }

            const analyser = audioContext.createAnalyser();
            analyser.fftSize = FFT_SIZE;
            const sourceNode = audioContext.createMediaStreamSource(stream);
            sourceNode.connect(analyser);
            const dataArray = new Uint8Array(analyser.frequencyBinCount);

            let animationFrameId;

            const processAudio = () => {
                analyser.getByteFrequencyData(dataArray);
                let sum = 0;
                for (let i = 0; i < dataArray.length; i++) {
                    sum += dataArray[i];
                }
                const average = sum / dataArray.length;
                const currentlySpeaking = average > SPEAKING_THRESHOLD;

                setSpeakingParticipants(prev => {
                    if (Boolean(prev[streamOwnerId]) !== currentlySpeaking) {
                        return { ...prev, [streamOwnerId]: currentlySpeaking };
                    }
                    return prev;
                });
                animationFrameId = requestAnimationFrame(processAudio);
            };
            processAudio(); // Start the loop

            remoteAnalysersRef.current[streamOwnerId] = { analyser, sourceNode, dataArray, animationFrameId };
        };

        const cleanupAnalyserForStream = (streamOwnerId) => {
            const analyserSetup = remoteAnalysersRef.current[streamOwnerId];
            if (analyserSetup) {
                if (analyserSetup.animationFrameId) cancelAnimationFrame(analyserSetup.animationFrameId);
                if (analyserSetup.sourceNode) {
                    try { analyserSetup.sourceNode.disconnect(); } catch (e) { /* ignore */ }
                }
                delete remoteAnalysersRef.current[streamOwnerId];
                setSpeakingParticipants(prev => {
                    if (prev[streamOwnerId]) {
                        const newState = { ...prev };
                        delete newState[streamOwnerId];
                        return newState;
                    }
                    return prev;
                });
            }
        };

        // Local stream analysis
        if (localStream && !isMuted && currentUserId) {
            setupAnalyserForStream(localStream, currentUserId);
        } else if (currentUserId) { // Cleanup if local stream removed or muted
            cleanupAnalyserForStream(currentUserId);
        }

        // Remote streams analysis
        Object.entries(remoteStreams).forEach(([peerId, stream]) => {
            if (stream && stream.active) { // Check if stream is valid and active
                 // Ensure peerId is treated consistently (e.g. as string if keys are strings)
                const id = String(peerId);
                // Check if analyser already exists and if the stream object is the same
                // This is tricky because MediaStream objects might be replaced.
                // For simplicity, if a stream exists for peerId, we ensure an analyser is running.
                // If an old analyser for a non-existent stream is found later, it will be cleaned up.
                if (!remoteAnalysersRef.current[id] || remoteAnalysersRef.current[id].sourceNode.mediaStream !== stream) {
                     setupAnalyserForStream(stream, id);
                }
            }
        });

        // Cleanup stale remote analysers (for peers who left)
        const currentRemoteStreamKeys = new Set(Object.keys(remoteStreams).map(String));
        Object.keys(remoteAnalysersRef.current).forEach(streamOwnerId => {
            if (String(streamOwnerId) !== String(currentUserId) && !currentRemoteStreamKeys.has(String(streamOwnerId))) {
                cleanupAnalyserForStream(streamOwnerId);
            }
        });

        return () => {
            // This cleanup runs when the component unmounts or dependencies change significantly
            // For a full cleanup on unmount, iterate all in remoteAnalysersRef
            Object.keys(remoteAnalysersRef.current).forEach(streamOwnerId => {
                cleanupAnalyserForStream(streamOwnerId);
            });
            // The main Echo useEffect handles closing the audioContextRef.current when the component unmounts
            // or localStream is permanently stopped. If not, it can be closed here too:
            // if (audioContextRef.current && Object.keys(remoteAnalysersRef.current).length === 0) {
            //   audioContextRef.current.close().catch(e => console.warn("Error closing audio context:", e));
            //   audioContextRef.current = null;
            // }
        };
    }, [localStream, remoteStreams, isMuted, currentUserId]);

    // Optional: Log speaking participants changes for verification
    useEffect(() => {
        console.log("Speaking participants:", speakingParticipants);
    }, [speakingParticipants]);


    // WebRTC: Start local audio stream
    const startLocalAudio = useCallback(async () => {
        if (localStream) return; // Already started
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
            setLocalStream(stream);
            setIsMuted(false); // Unmute when stream is acquired
            return stream;
        } catch (err) {
            console.error("Erreur accès microphone:", err);
            setError("Impossible d'accéder au microphone. Veuillez vérifier les permissions.");
            setIsMuted(true); // Stay muted if error
            return null;
        }
    }, [localStream]);

    // WebRTC: Stop local audio stream
    const stopLocalAudio = useCallback(() => {
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            setLocalStream(null);
            setIsMuted(true); // Mute when stream is stopped
        }
    }, [localStream]);

    // WebRTC: Mute/Unmute toggle
    const handleMuteToggle = useCallback(async () => {
        if (isMuted) { // User wants to unmute
            const stream = await startLocalAudio();
            if (stream) {
                setIsMuted(false);
                // Add stream to existing peers
                Object.values(peersRef.current).forEach(peer => {
                    if (!peer.destroyed) {
                        peer.addStream(stream);
                    }
                });
            }
        } else { // User wants to mute
            stopLocalAudio(); // This also sets isMuted to true
            // Remove stream from existing peers
            Object.values(peersRef.current).forEach(peer => {
                 if (!peer.destroyed && localStream) { // localStream check is technically redundant due to stopLocalAudio logic
                    // peer.removeStream(localStream); // This was causing issues with simple-peer renegotiation
                    // Instead, toggle track enabled status (more robust for temporary mute)
                    // However, for full stop/start, removing and re-adding is needed.
                    // Since stopLocalAudio stops tracks and nulls localStream, new peers won't get it.
                    // Existing peers need to be handled.
                    // For simplicity, if we stop the local stream, new peers won't have it.
                    // Existing peers might need renegotiation or track replacement.
                    // The simplest for now is to destroy peers if local stream is fully stopped,
                    // or rely on adding/removing tracks if the stream object itself persists.
                    // Given current start/stop logic, a full stop means peers can't send.
                    // Let's ensure tracks are disabled.
                 }
            });
             if (localStream) { // Ensure tracks are disabled if stream object still exists momentarily
                localStream.getAudioTracks().forEach(track => track.enabled = false);
             }
             setIsMuted(true); // Explicitly set, though stopLocalAudio does it.
        }
    }, [isMuted, startLocalAudio, stopLocalAudio, localStream]);


    // WebRTC: Manage Peer Connections based on participants and localStream
    useEffect(() => {
        if (!localStream || !isAuthenticated || !currentUserId || space?.status !== 'live') {
            // If local stream isn't ready, not authenticated, or space not live, destroy existing peers.
            Object.values(peersRef.current).forEach(peer => peer.destroy());
            setPeers({});
            setRemoteStreams({}); // Clear remote streams as well
            return;
        }

        const currentPeers = peersRef.current;
        const activeParticipantIds = new Set(participants.map(p => p.id));

        // Create peers for new participants
        participants.forEach(participant => {
            if (participant.id === currentUserId || currentPeers[participant.id]) {
                return; // Don't create peer for self or if already exists
            }

            console.log(`Creating peer for ${participant.id} (current user: ${currentUserId})`);
            // Determine initiator: simpler to have one side always initiate, e.g., user with lower ID.
            // This needs to be consistent for any pair of users.
            const initiator = currentUserId < participant.id;
            console.log(`Initiator status for peer with ${participant.id}: ${initiator}`);

            const newPeer = new Peer({
                initiator: initiator,
                trickle: true,
                stream: localStream, // Add local stream immediately
            });

            newPeer.on('signal', (data) => {
                // console.log(`Sending signal to ${participant.id}:`, data);
                SpaceService.sendAudioSignal(spaceId, data) // Pass signal data directly
                    .catch(err => console.error("Erreur envoi signal:", err));
            });

            newPeer.on('stream', (remoteStream) => {
                console.log('Stream reçu de:', participant.id, remoteStream);
                setRemoteStreams(prev => ({ ...prev, [participant.id]: remoteStream }));
                // Add a 'username' or other identifier to the stream object if needed for display
                // remoteStream.username = participant.username;
            });

            newPeer.on('connect', () => {
                console.log('CONNECTÉ avec peer:', participant.id);
            });

            newPeer.on('close', () => {
                console.log('Peer déconnecté (close):', participant.id);
                setRemoteStreams(prev => {
                    const newState = { ...prev };
                    delete newState[participant.id];
                    return newState;
                });
                setPeers(prev => {
                    const newState = { ...prev };
                    delete newState[participant.id];
                    return newState;
                });
            });

            newPeer.on('error', (err) => {
                console.error('Erreur Peer:', participant.id, err);
                // Attempt to clean up this specific peer
                if (currentPeers[participant.id]) {
                    currentPeers[participant.id].destroy();
                }
                 setRemoteStreams(prev => {
                    const newState = { ...prev };
                    delete newState[participant.id];
                    return newState;
                });
                setPeers(prev => {
                    const newState = { ...prev };
                    delete newState[participant.id];
                    return newState;
                });
            });

            setPeers(prev => ({ ...prev, [participant.id]: newPeer }));
        });

        // Remove peers for participants who left
        Object.keys(currentPeers).forEach(peerId => {
            // peerId is a string, participant.id is a number. Convert for comparison.
            if (!activeParticipantIds.has(parseInt(peerId))) {
                console.log(`Participant ${peerId} a quitté, destruction du peer.`);
                currentPeers[peerId].destroy();
                setRemoteStreams(prev => {
                    const newState = { ...prev };
                    delete newState[peerId];
                    return newState;
                });
                setPeers(prev => {
                    const newState = { ...prev };
                    delete newState[peerId];
                    return newState;
                });
            }
        });

    }, [participants, localStream, spaceId, currentUserId, isAuthenticated, space?.status]);


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

const handleJoinSpace = async () => {
  setIsJoining(true);
  setError(''); // Clear previous errors
  try {
    await SpaceService.joinSpace(spaceId);
    // Le WebSocket event 'joining' devrait gérer l'update de la liste des participants.
    // console.log("Successfully joined space");
    // Optionnel: Afficher une notification de succès discrète si nécessaire.
  } catch (err) {
    console.error("Erreur pour rejoindre le Space:", err);
    setError(err.response?.data?.message || "Impossible de rejoindre le Space.");
  } finally {
    setIsJoining(false);
  }
};

    if (isLoading) return <div className="text-center py-20 text-gb-light-gray">Chargement du Space...</div>;
if (error && !isJoining) return <div className="text-center py-20 text-red-400">{error}</div>; // Ne pas afficher l'erreur de chargement si on tente de join
    if (!space) return <div className="text-center py-20 text-gb-light-gray">Space non trouvé.</div>;

    const isHost = isAuthenticated && currentUser && currentUser.id === space.host?.id;
const isParticipant = participants.some(p => p.id === currentUser?.id);
const canJoinSpace = isAuthenticated && currentUser && currentUser.id !== space?.host?.id && !isParticipant && space?.status !== 'ended';

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

                {/* Join Space Button */}
                {canJoinSpace && (
                    <div className="my-4"> {/* Adjusted margin to 'my-4' for spacing */}
                        <Button
                            onClick={handleJoinSpace}
                            disabled={isJoining}
                            variant="primary"
                        >
                            {isJoining ? 'Chargement...' : 'Rejoindre le Space'}
                        </Button>
                    </div>
                )}
                {error && isJoining && <p className="text-red-400 text-sm mt-2">{error}</p>} {/* Display error specific to joining */}


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
                                <li key={p.id} className={`flex items-center gap-3 p-2 bg-gb-dark-lighter rounded-md transition-all duration-200 ${
                                    speakingParticipants[p.id] ? 'ring-2 ring-gb-teal shadow-lg' : ''
                                }`}>
                                    <img src={p.avatar_url || `${defaultAvatar}${encodeURIComponent(p.username)}`} alt={p.username} className="w-10 h-10 rounded-full object-cover"/>
                                    <div>
                                        <span className="font-medium text-gb-white">{p.username}</span>
                                        <span className="block text-xs text-gb-gray">{p.role_label || p.role}</span>
                                    </div>
                                    {p.has_raised_hand && <span className="ml-auto text-xl" title="Main levée">🖐️</span>}
                                    {speakingParticipants[p.id] && <span className="ml-auto text-xs text-gb-teal">🎙️</span>}
                                    {/* TODO: Ajouter indicateur mute distant, actions de modération ici si isHost */}
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

                {/* Audio Elements for WebRTC */}
                { Object.entries(remoteStreams).map(([peerId, stream]) => (
                    <audio
                        key={peerId}
                        ref={audioEl => { if (audioEl) audioEl.srcObject = stream; }}
                        autoPlay
                        playsInline
                        // controls // For debugging
                        style={{ display: 'none' }} // Hide audio elements
                    />
                ))}

                {/* Mute/Unmute Button */}
                {isAuthenticated && space?.status === 'live' && (
                     <div className="mt-4 text-center">
                        <Button onClick={handleMuteToggle} variant={isMuted ? "secondary" : "danger"} className="px-6 py-3">
                            {isMuted ? '🎙️ Activer Micro' : '🔇 Couper Micro'}
                        </Button>
                    </div>
                )}
                 {/* TODO: Ajouter les contrôles du Space (Start/End, lever la main, dons, etc.) */}
            </div>
        </div>
    );
};

export default SpaceDetailPage;