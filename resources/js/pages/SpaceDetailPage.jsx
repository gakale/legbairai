import React, { useState, useEffect, useCallback, useRef } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import spaceService from '../services/spaceService';
import Button from '../components/common/Button';
import Peer from 'simple-peer';

const defaultAvatar = "https://ui-avatars.com/api/?background=6B46C1&color=fff&size=128&name=";

const SpaceDetailPage = () => {
    const { spaceId } = useParams();
    const { currentUser, isAuthenticated } = useAuth();
    const navigate = useNavigate();
    
    // États principaux
    const [space, setSpace] = useState(null);
    const [participants, setParticipants] = useState([]);
    const [messages, setMessages] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');
    const [newMessageContent, setNewMessageContent] = useState('');
    const [isJoining, setIsJoining] = useState(false);
    const [hasJoined, setHasJoined] = useState(false);
    const [connectionStatus, setConnectionStatus] = useState('Déconnecté');

    // États WebRTC
    const [localStream, setLocalStream] = useState(null);
    const [peers, setPeers] = useState({});
    const [remoteStreams, setRemoteStreams] = useState({});
    const [isMuted, setIsMuted] = useState(true);
    
    // États d'enregistrement
    const [isRecording, setIsRecording] = useState(false);
    const [recorder, setRecorder] = useState(null);
    const [recordingTime, setRecordingTime] = useState(0);
    const recordingTimerRef = useRef(null);
    const [isDeafened, setIsDeafened] = useState(false);
    const [speakingParticipants, setSpeakingParticipants] = useState({});

    // Refs
    const peersRef = useRef({});
    const localStreamRef = useRef(null);
    const messagesEndRef = useRef(null);
    const echoChannelRef = useRef(null);

    // Charger les détails du space
    const fetchSpaceDetails = useCallback(async () => {
        try {
            setIsLoading(true);
            const response = await spaceService.getById(spaceId);
            const spaceData = response.data || response;
            setSpace(spaceData);
            
            // Charger les messages
            await loadMessages();
        } catch (err) {
            console.error('Erreur chargement space:', err);
            setError('Space non trouvé ou inaccessible');
        } finally {
            setIsLoading(false);
        }
    }, [spaceId]);

    // Charger les messages
    const loadMessages = useCallback(async () => {
        try {
            const response = await spaceService.getMessages(spaceId);
            setMessages(response.data || []);
        } catch (err) {
            console.error('Erreur chargement messages:', err);
        }
    }, [spaceId]);

    // Initialiser le stream audio local
    const initializeLocalStream = useCallback(async () => {
        try {
            // Vérifier l'état des permissions
            navigator.permissions.query({ name: 'microphone' }).then((result) => {
                console.log('Permission microphone:', result.state);
                if (result.state === 'denied') {
                    setError('Accès au microphone refusé. Veuillez autoriser l\'accès dans les paramètres de votre navigateur.');
                }
            });
            
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    sampleRate: 44100
                },
                video: false
            });
            
            localStreamRef.current = stream;
            setLocalStream(stream);
            
            // Démarrer en mode muet
            stream.getAudioTracks().forEach(track => {
                track.enabled = false;
            });
            
            return stream;
        } catch (err) {
            console.error('Erreur accès microphone:', err);
            setError('Impossible d\'accéder au microphone. Vérifiez vos permissions.');
            return null;
        }
    }, []);

    // Rejoindre le space
    const handleJoinSpace = useCallback(async () => {
        if (!isAuthenticated) {
            navigate('/login');
            return;
        }

        try {
            setIsJoining(true);
            
            // Rejoindre via l'API
            await spaceService.join(spaceId);
            
            // Initialiser le stream audio
            const stream = await initializeLocalStream();
            if (!stream) return;
            
            // Connecter aux WebSockets
            if (window.Echo) {
                connectToSpace();
            }
            
            setHasJoined(true);
            setConnectionStatus('Connecté');
        } catch (err) {
            console.error('Erreur rejoindre space:', err);
            setError('Impossible de rejoindre le space');
        } finally {
            setIsJoining(false);
        }
    }, [isAuthenticated, spaceId, initializeLocalStream, navigate]);

    // Connexion WebSocket et WebRTC
    const connectToSpace = useCallback(() => {
        if (!window.Echo || !spaceId) return;

        try {
            // Se connecter au canal de présence
            const channel = window.Echo.join(`space.${spaceId}`);
            echoChannelRef.current = channel;

            channel
                .here((users) => {
                    console.log('Utilisateurs présents:', users);
                    setParticipants(users);
                    setConnectionStatus('Connecté');
                })
                .joining((user) => {
                    console.log('Utilisateur rejoint:', user);
                    setParticipants(prev => [...prev, user]);
                    
                    // Créer une connexion peer pour ce nouvel utilisateur
                    if (currentUser && user.id !== currentUser.id) {
                        createPeerConnection(user.id, true);
                    }
                })
                .leaving((user) => {
                    console.log('Utilisateur parti:', user);
                    setParticipants(prev => prev.filter(p => p.id !== user.id));
                    
                    // Nettoyer la connexion peer
                    if (peersRef.current[user.id]) {
                        peersRef.current[user.id].destroy();
                        delete peersRef.current[user.id];
                        setPeers(prev => {
                            const newPeers = { ...prev };
                            delete newPeers[user.id];
                            return newPeers;
                        });
                    }
                })
                .error((error) => {
                    console.error('Erreur canal:', error);
                    setConnectionStatus('Erreur de connexion');
                });

            // Écouter les nouveaux messages
            channel.listen('MessageSent', (e) => {
                setMessages(prev => [...prev, e.message]);
                scrollToBottom();
            });

            // Écouter les signaux WebRTC
            channel.listen('.audio.signal', (e) => {
                console.log('Signal WebRTC reçu:', e);
                handleIncomingSignal(e.signal, e.user_id);
            });

        } catch (err) {
            console.error('Erreur connexion WebSocket:', err);
            setConnectionStatus('Erreur de connexion');
        }
    }, [spaceId, currentUser]);

    // Version améliorée de playRemoteStream avec plus de debugging
    const playRemoteStream = useCallback((stream, userId) => {
        console.log(`=== DEBUT playRemoteStream pour ${userId} ===`);
        
        // Vérifier le stream
        if (!stream) {
            console.error('Stream vide pour', userId);
            return;
        }
        
        const audioTracks = stream.getAudioTracks();
        console.log(`Nombre de pistes audio: ${audioTracks.length}`);
        
        audioTracks.forEach((track, index) => {
            console.log(`Piste ${index}:`, {
                enabled: track.enabled,
                readyState: track.readyState,
                muted: track.muted,
                kind: track.kind,
                label: track.label
            });
        });
        
        // Vérifier si un élément audio existe déjà
        let audio = document.getElementById(`audio-${userId}`);
        
        if (audio) {
            console.log('Élément audio existant trouvé, nettoyage...');
            audio.srcObject = null;
            audio.remove();
        }
        
        // Créer un nouvel élément audio
        audio = document.createElement('audio');
        audio.id = `audio-${userId}`;
        audio.autoplay = true;
        audio.playsInline = true;
        audio.controls = true; // TEMPORAIRE : montrer les contrôles pour debug
        audio.volume = 1.0;
        
        // Ajouter des listeners pour debug
        audio.addEventListener('loadstart', () => console.log(`${userId}: loadstart`));
        audio.addEventListener('loadeddata', () => console.log(`${userId}: loadeddata`));
        audio.addEventListener('canplay', () => console.log(`${userId}: canplay`));
        audio.addEventListener('playing', () => console.log(`${userId}: PLAYING - AUDIO MARCHE!`));
        audio.addEventListener('error', (e) => console.error(`${userId}: erreur audio:`, e));
        audio.addEventListener('pause', () => console.log(`${userId}: pause`));
        audio.addEventListener('ended', () => console.log(`${userId}: ended`));
        
        // Ajouter au DOM
        document.body.appendChild(audio);
        console.log('Élément audio ajouté au DOM');
        
        // Assigner le stream
        audio.srcObject = stream;
        console.log('Stream assigné à l\'élément audio');
        
        // Forcer la lecture
        audio.play()
            .then(() => {
                console.log(`${userId}: Lecture audio démarrée avec succès!`);
            })
            .catch(err => {
                console.error(`${userId}: Erreur lecture audio:`, err);
                
                // Si autoplay bloqué, demander interaction utilisateur
                if (err.name === 'NotAllowedError') {
                    console.log('Autoplay bloqué - cliquez quelque part sur la page puis testez à nouveau');
                    
                    // Ajouter un gestionnaire de clic temporaire
                    const enableAudio = () => {
                        audio.play();
                        document.removeEventListener('click', enableAudio);
                    };
                    document.addEventListener('click', enableAudio);
                }
            });
        
        console.log(`=== FIN playRemoteStream pour ${userId} ===`);
        return audio;
    }, []);
    
    // Fonction pour vérifier l'état complet des connexions
    const debugConnections = () => {
        console.log('=== DEBUG CONNEXIONS ===');
        console.log('Utilisateur actuel:', currentUser?.id);
        console.log('Participants:', participants.map(p => p.id));
        console.log('Peers connectés:', Object.keys(peersRef.current));
        console.log('Streams distants:', Object.keys(remoteStreams));
        console.log('Mode sourdine:', isDeafened);
        console.log('Microphone muet:', isMuted);
        
        if (localStreamRef.current) {
            console.log('Stream local:');
            localStreamRef.current.getAudioTracks().forEach((track, i) => {
                console.log(`  Piste ${i}:`, {
                    enabled: track.enabled,
                    readyState: track.readyState,
                    muted: track.muted
                });
            });
        }
        
        // Vérifier chaque élément audio dans le DOM
        participants.forEach(p => {
            if (p.id !== currentUser?.id) {
                const audioEl = document.getElementById(`audio-${p.id}`);
                if (audioEl) {
                    console.log(`Audio element pour ${p.id}:`, {
                        srcObject: !!audioEl.srcObject,
                        volume: audioEl.volume,
                        muted: audioEl.muted,
                        paused: audioEl.paused,
                        readyState: audioEl.readyState
                    });
                } else {
                    console.log(`Pas d'élément audio pour ${p.id}`);
                }
            }
        });
        console.log('=== FIN DEBUG ===');
    };

    // Créer une connexion peer WebRTC
    const createPeerConnection = useCallback((userId, initiator = false) => {
        if (!localStreamRef.current) {
            console.error('Pas de stream local disponible');
            return;
        }
        
        // Si une connexion existe déjà, la détruire pour en créer une nouvelle
        if (peersRef.current[userId]) {
            console.log('Connexion existante trouvée, recréation...');
            peersRef.current[userId].destroy();
            delete peersRef.current[userId];
        }

        try {
            console.log(`Création d'une connexion peer avec ${userId}, initiateur: ${initiator}`);
            
            const peer = new Peer({
                initiator,
                trickle: true, // Activer trickle ICE pour une meilleure connectivité
                stream: localStreamRef.current,
                config: {
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' },
                        { urls: 'stun:stun1.l.google.com:19302' },
                        { urls: 'stun:stun2.l.google.com:19302' },
                        { urls: 'stun:stun3.l.google.com:19302' },
                        { urls: 'stun:stun4.l.google.com:19302' }
                    ]
                }
            });

            peer.on('signal', (data) => {
                // Envoyer le signal via WebSocket
                console.log(`=== ENVOI SIGNAL ===`);
                console.log(`À: ${userId}`);
                console.log(`Type: ${data.type || 'candidat ICE'}`);
                console.log(`Signal complet:`, data);
                
                spaceService.sendAudioSignal(spaceId, {
                    signal: data,
                    to: userId,
                    from: currentUser.id
                });
            });

            peer.on('connect', () => {
                console.log(`Connexion établie avec ${userId}`);
            });
            
            peer.on('stream', (remoteStream) => {
                console.log(`Stream audio reçu de: ${userId}`);
                
                // Vérifier que le stream contient des pistes audio
                const audioTracks = remoteStream.getAudioTracks();
                console.log(`Nombre de pistes audio: ${audioTracks.length}`);
                
                setRemoteStreams(prev => ({
                    ...prev,
                    [userId]: remoteStream
                }));
                
                // Jouer l'audio distant si non en sourdine
                if (!isDeafened) {
                    playRemoteStream(remoteStream, userId);
                }
            });
            
            peer.on('error', (err) => {
                console.error(`Erreur peer avec ${userId}:`, err);
                // Nettoyer la référence en cas d'erreur
                if (peersRef.current[userId]) {
                    try {
                        peersRef.current[userId].destroy();
                    } catch (e) {
                        console.warn('Erreur lors de la destruction du peer après erreur:', e);
                    }
                    delete peersRef.current[userId];
                }
                
                // Tenter de recréer la connexion après une erreur (avec délai)
                setTimeout(() => {
                    if (!peersRef.current[userId] && participants.some(p => p.id === userId)) {
                        console.log(`Tentative de reconnexion avec ${userId} après erreur...`);
                        const newPeer = createPeerConnection(userId, true);
                        if (newPeer) {
                            peersRef.current[userId] = newPeer;
                        }
                    }
                }, 2000); // Délai de 2 secondes avant reconnexion
            });
            
            peer.on('close', () => {
                console.log(`Connexion fermée avec ${userId}`);
                delete peersRef.current[userId];
                
                // Supprimer également le stream distant et l'élément audio
                setRemoteStreams(prev => {
                    const newStreams = {...prev};
                    delete newStreams[userId];
                    return newStreams;
                });
                
                // Supprimer l'élément audio du DOM
                const audioElement = document.getElementById(`audio-${userId}`);
                if (audioElement) {
                    audioElement.srcObject = null;
                    audioElement.remove();
                }
            });

            peersRef.current[userId] = peer;
            setPeers(prev => ({ ...prev, [userId]: peer }));
            
            return peer;

        } catch (err) {
            console.error(`Erreur création peer avec ${userId}:`, err);
            return null;
        }
    }, [spaceId, currentUser, isDeafened]);

    // Gérer les signaux WebRTC entrants
    const handleIncomingSignal = useCallback((signal, fromUserId) => {
        if (!currentUser || fromUserId === currentUser.id) {
            console.log('Signal ignoré (même utilisateur)');
            return;
        }
        
        console.log(`Signal reçu de ${fromUserId}:`, signal.type || 'candidat ICE');
        
        let peer = peersRef.current[fromUserId];
        
        // Si on reçoit une offre, toujours recréer la connexion pour éviter les conflits
        if (signal.type === 'offer' && peer) {
            console.log(`Offre reçue pour une connexion existante avec ${fromUserId}, recréation...`);
            try {
                peer.destroy();
            } catch (e) {
                console.warn('Erreur lors de la destruction du peer existant:', e);
            }
            delete peersRef.current[fromUserId];
            peer = null;
        }
        
        // Si pas de peer, créer une nouvelle connexion (non-initiateur car on répond)
        if (!peer) {
            console.log(`Création d'une nouvelle connexion pour répondre à ${fromUserId}`);
            peer = createPeerConnection(fromUserId, false);
        }

        if (peer) {
            try {
                // Vérifier si le peer n'est pas détruit avant d'appliquer le signal
                if (peer._destroyed) {
                    console.warn(`Le peer pour ${fromUserId} a été détruit, recréation d'une nouvelle connexion`);
                    // Supprimer l'ancienne référence
                    delete peersRef.current[fromUserId];
                    // Créer un nouveau peer
                    peer = createPeerConnection(fromUserId, false);
                    peersRef.current[fromUserId] = peer;
                }
                
                console.log(`Application du signal de ${fromUserId}`);
                peer.signal(signal);
            } catch (err) {
                console.error(`Erreur lors de l'application du signal de ${fromUserId}:`, err);
                // En cas d'erreur, nettoyer la référence pour éviter des erreurs futures
                delete peersRef.current[fromUserId];
            }
        } else {
            console.error(`Impossible de créer une connexion peer avec ${fromUserId}`);
        }
    }, [currentUser, createPeerConnection]);

    // Version améliorée du toggleMute avec plus de logs
    const toggleMute = useCallback(() => {
        if (localStreamRef.current) {
            const audioTracks = localStreamRef.current.getAudioTracks();
            const newMuteState = !isMuted;
            
            console.log(`=== TOGGLE MUTE ===`);
            console.log('Ancien état muet:', isMuted);
            console.log('Nouvel état muet:', newMuteState);
            console.log('Nombre de pistes:', audioTracks.length);
            
            audioTracks.forEach((track, index) => {
                console.log(`Avant - Piste ${index}:`, track.enabled);
                track.enabled = !newMuteState;
                console.log(`Après - Piste ${index}:`, track.enabled);
            });
            
            setIsMuted(newMuteState);
            console.log(`Microphone ${newMuteState ? 'MUTE' : 'DEMUTE'}`);
        } else {
            console.error('Pas de stream local pour toggle mute!');
        }
    }, [isMuted]);

    // Basculer le mode sourd
    const toggleDeafen = useCallback(() => {
        const newDeafenState = !isDeafened;
        setIsDeafened(newDeafenState);
        
        console.log(`Mode sourdine ${newDeafenState ? 'activé' : 'désactivé'}`);
        
        // Mettre à jour tous les éléments audio
        Object.keys(remoteStreams).forEach(userId => {
            const audioElement = document.getElementById(`audio-${userId}`);
            if (audioElement) {
                if (newDeafenState) {
                    // Désactiver l'audio
                    audioElement.volume = 0;
                    audioElement.muted = true;
                } else {
                    // Réactiver l'audio
                    audioElement.volume = 1.0;
                    audioElement.muted = false;
                    
                    // Rejouer le stream si nécessaire
                    if (remoteStreams[userId]) {
                        playRemoteStream(remoteStreams[userId], userId);
                    }
                }
            }
        });
    }, [isDeafened, remoteStreams, playRemoteStream]);

    // Envoyer un message
    const sendMessage = useCallback(async (e) => {
        e.preventDefault();
        if (!newMessageContent.trim()) return;

        try {
            await spaceService.sendMessage(spaceId, newMessageContent.trim());
            setNewMessageContent('');
        } catch (err) {
            console.error('Erreur envoi message:', err);
        }
    }, [spaceId, newMessageContent]);

    // Fonction pour nettoyer toutes les connexions WebRTC
    const cleanupWebRTC = useCallback(() => {
        // Nettoyer les connexions WebRTC
        Object.values(peersRef.current).forEach(peer => {
            try {
                peer.destroy();
            } catch (e) {
                console.warn('Erreur destruction peer:', e);
            }
        });
        peersRef.current = {};
        setPeers({});
        setRemoteStreams({});

        // Arrêter le stream local
        if (localStreamRef.current) {
            localStreamRef.current.getTracks().forEach(track => track.stop());
            localStreamRef.current = null;
            setLocalStream(null);
        }

        // Déconnecter WebSocket
        if (echoChannelRef.current) {
            window.Echo.leave(`space.${spaceId}`);
            echoChannelRef.current = null;
        }
    }, [spaceId]);
    
    // Fonctions d'enregistrement audio (réservées au créateur)
    const startRecording = useCallback(() => {
        if (!localStreamRef.current) {
            console.error("Pas de flux audio local disponible");
            return;
        }
        
        try {
            // Détecter le format d'enregistrement supporté par le navigateur
            const mimeTypes = ['audio/webm', 'audio/webm;codecs=opus', 'audio/ogg;codecs=opus', 'audio/mp4'];
            let mimeType = '';
            
            for (const type of mimeTypes) {
                if (MediaRecorder.isTypeSupported(type)) {
                    mimeType = type;
                    console.log(`Format d'enregistrement supporté: ${mimeType}`);
                    break;
                }
            }
            
            if (!mimeType) {
                throw new Error("Aucun format d'enregistrement audio supporté par ce navigateur");
            }
            
            // Vérifier si nous avons des pistes audio disponibles
            if (!localStreamRef.current || !localStreamRef.current.getAudioTracks().length) {
                throw new Error("Aucune piste audio locale disponible pour l'enregistrement");
            }
            
            // Utiliser uniquement le flux audio local pour une meilleure compatibilité
            // La combinaison de flux peut causer des problèmes sur certains navigateurs
            const audioTracks = localStreamRef.current.getAudioTracks().map(track => track.clone());
            
            // Si nous avons des flux distants et qu'ils sont disponibles, les ajouter
            if (Object.values(remoteStreams).length > 0) {
                Object.values(remoteStreams).forEach(stream => {
                    if (stream && stream.getAudioTracks().length > 0) {
                        stream.getAudioTracks().forEach(track => {
                            audioTracks.push(track.clone());
                        });
                    }
                });
            }
            
            if (audioTracks.length === 0) {
                throw new Error("Aucune piste audio disponible pour l'enregistrement");
            }
            
            // Créer un nouveau MediaStream avec les pistes audio
            const combinedStream = new MediaStream(audioTracks);
            
            // Créer un MediaRecorder avec les options optimisées
            const mediaRecorder = new MediaRecorder(combinedStream, {
                mimeType: mimeType,
                audioBitsPerSecond: 128000 // 128 kbps pour une qualité audio raisonnable
            });
            
            const audioChunks = [];
            
            mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    audioChunks.push(event.data);
                }
            };
            
            // Stocker le type MIME détecté pour l'utiliser lors de la création du Blob
            const detectedMimeType = mimeType;
            
            mediaRecorder.onstop = async () => {
                // Déterminer l'extension de fichier appropriée en fonction du type MIME
                let fileExtension = 'webm';
                if (detectedMimeType.includes('mp4')) {
                    fileExtension = 'mp4';
                } else if (detectedMimeType.includes('ogg')) {
                    fileExtension = 'ogg';
                }
                
                // Créer un blob avec tous les chunks audio en utilisant le type MIME détecté
                const audioBlob = new Blob(audioChunks, { type: detectedMimeType });
                
                // Créer un fichier à partir du blob avec l'extension appropriée
                const audioFile = new File([audioBlob], `space-${spaceId}-recording.${fileExtension}`, {
                    type: detectedMimeType,
                    lastModified: Date.now()
                });
                
                // Créer un FormData pour l'envoi au serveur
                const formData = new FormData();
                formData.append('audio_file', audioFile);
                formData.append('duration_seconds', parseInt(recordingTime, 10) || 0);
                
                try {
                    // Envoyer l'enregistrement au serveur
                    const response = await spaceService.saveRecording(spaceId, formData);
                    console.log('Enregistrement sauvegardé avec succès', response);
                    
                    // Notifier les utilisateurs que l'enregistrement est disponible
                    if (window.Echo) {
                        window.Echo.private(`space.${spaceId}`)
                            .whisper('recording.saved', {
                                user_id: currentUser.id,
                                username: currentUser.username,
                                message: "L'enregistrement de cette session est maintenant disponible"
                            });
                    }
                } catch (error) {
                    console.error('Erreur lors de la sauvegarde de l\'enregistrement', error);
                }
                
                // Réinitialiser le timer
                clearInterval(recordingTimerRef.current);
                setRecordingTime(0);
            };
            
            // Démarrer l'enregistrement
            mediaRecorder.start(1000); // Enregistrer par blocs de 1 seconde
            setIsRecording(true);
            setRecorder(mediaRecorder);
            
            // Démarrer le timer d'enregistrement
            recordingTimerRef.current = setInterval(() => {
                setRecordingTime(prev => prev + 1);
            }, 1000);
            
            // Notifier les utilisateurs que l'enregistrement a commencé
            if (window.Echo) {
                window.Echo.private(`space.${spaceId}`)
                    .whisper('recording.started', {
                        user_id: currentUser.id,
                        username: currentUser.username,
                        message: "L'enregistrement de cette session a commencé"
                    });
            }
            
            console.log('Enregistrement démarré');
        } catch (error) {
            console.error('Erreur lors du démarrage de l\'enregistrement', error);
            
            // Afficher un message d'erreur plus détaillé
            let errorMessage = 'Impossible de démarrer l\'enregistrement audio.';
            
            if (error.name === 'NotSupportedError') {
                errorMessage = 'Votre navigateur ne prend pas en charge ce format d\'enregistrement audio. Essayez avec Chrome ou Firefox récent.';
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            // Réinitialiser l'état d'enregistrement
            setIsRecording(false);
            setRecorder(null);
        }
    }, [localStreamRef, remoteStreams, spaceId, currentUser]);
    
    // Fonction pour arrêter l'enregistrement
    const stopRecording = useCallback(() => {
        if (recorder && recorder.state !== 'inactive') {
            recorder.stop();
            setIsRecording(false);
            setRecorder(null);
            
            // Notifier les utilisateurs que l'enregistrement est terminé
            if (window.Echo) {
                window.Echo.private(`space.${spaceId}`)
                    .whisper('recording.stopped', {
                        user_id: currentUser.id,
                        username: currentUser.username,
                        message: "L'enregistrement de cette session est terminé"
                    });
            }
            
            console.log('Enregistrement arrêté');
        }
    }, [recorder, spaceId, currentUser]);

    // Formater le temps d'enregistrement (mm:ss)
    const formatRecordingTime = useCallback(() => {
        const minutes = Math.floor(recordingTime / 60);
        const seconds = recordingTime % 60;
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }, [recordingTime]);
    
    // Quitter le space
    const handleLeaveSpace = useCallback(async () => {
        try {
            // Arrêter l'enregistrement s'il est en cours
            if (isRecording) {
                stopRecording();
            }
            
            // Arrêter tous les streams et connexions
            cleanupWebRTC();
            
            // Déconnecter via l'API
            await spaceService.leave(spaceId);
            
            setHasJoined(false);
            setConnectionStatus('Déconnecté');
        } catch (err) {
            console.error('Erreur quitter space:', err);
        }
    }, [spaceId, cleanupWebRTC, isRecording, stopRecording]);

    // Faire défiler vers le bas
    const scrollToBottom = useCallback(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, []);

    // Exposer les fonctions de debug globalement
    useEffect(() => {
        if (typeof window !== 'undefined') {
            window.debugConnections = debugConnections;
        }
        
        return () => {
            if (typeof window !== 'undefined') {
                delete window.debugConnections;
            }
        };
    }, []);
    
    // Effets
    useEffect(() => {
        if (spaceId) {
            fetchSpaceDetails();
        }

        return () => {
            // Nettoyage à la fermeture
            Object.values(peersRef.current).forEach(peer => {
                peer.destroy();
            });
            if (localStreamRef.current) {
                localStreamRef.current.getTracks().forEach(track => track.stop());
            }
            if (echoChannelRef.current) {
                window.Echo.leave(`space.${spaceId}`);
            }
        };
    }, [spaceId, fetchSpaceDetails]);

    useEffect(() => {
        scrollToBottom();
    }, [messages, scrollToBottom]);

    // États de chargement et d'erreur
    if (isLoading) {
        return (
            <div className="min-h-screen bg-gb-dark flex items-center justify-center">
                <div className="text-center">
                    <div className="spinner mb-4"></div>
                    <div className="text-gb-white">Chargement du space...</div>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="min-h-screen bg-gb-dark flex items-center justify-center">
                <div className="text-center">
                    <div className="text-red-400 mb-4 text-xl">{error}</div>
                    <Button onClick={() => navigate('/')} variant="primary">
                        Retour à l'accueil
                    </Button>
                </div>
            </div>
        );
    }

    if (!space) {
        return (
            <div className="min-h-screen bg-gb-dark flex items-center justify-center">
                <div className="text-center">
                    <div className="text-gb-white mb-4 text-xl">Space non trouvé</div>
                    <Button onClick={() => navigate('/')} variant="primary">
                        Retour à l'accueil
                    </Button>
                </div>
            </div>
        );
    }

    const isHost = currentUser && space.host && currentUser.id === space.host.id;
    const canJoin = space.status === 'live' && isAuthenticated && !hasJoined;
    const isInSpace = hasJoined;

    return (
        <div className="min-h-screen bg-gb-dark">
            {/* En-tête du space */}
            <div className="bg-gb-dark-light border-b border-gb-gray-dark">
                <div className="max-w-7xl mx-auto px-4 py-6">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <Link to="/" className="text-gb-light-gray hover:text-gb-white">
                                ← Retour
                            </Link>
                            <div>
                                <h1 className="text-2xl font-bold text-gb-white">{space.title}</h1>
                                <div className="flex items-center space-x-4 mt-2">
                                    <span className={`px-2 py-1 rounded text-xs font-medium ${
                                        space.status === 'live' ? 'bg-red-600 text-white' :
                                        space.status === 'scheduled' ? 'bg-blue-600 text-white' :
                                        'bg-gray-600 text-white'
                                    }`}>
                                        {space.status === 'live' ? '🔴 En direct' :
                                         space.status === 'scheduled' ? '📅 Programmé' :
                                         '⏹️ Terminé'}
                                    </span>
                                    <span className="text-gb-light-gray text-sm">
                                        👥 {participants.length} participant{participants.length > 1 ? 's' : ''}
                                    </span>
                                    {isInSpace && (
                                        <span className={`text-xs px-2 py-1 rounded ${
                                            connectionStatus === 'Connecté' ? 'bg-green-900 text-green-200' :
                                            connectionStatus === 'Connexion...' ? 'bg-yellow-900 text-yellow-200' :
                                            'bg-red-900 text-red-200'
                                        }`}>
                                            {connectionStatus}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center space-x-3">
                            {canJoin && (
                                <Button
                                    onClick={handleJoinSpace}
                                    disabled={isJoining}
                                    variant="primary"
                                    className="webrtc-button"
                                >
                                    {isJoining ? (
                                        <>
                                            <div className="spinner"></div>
                                            <span>Connexion...</span>
                                        </>
                                    ) : (
                                        <>
                                            <span>🎤</span>
                                            <span>Rejoindre</span>
                                        </>
                                    )}
                                </Button>
                            )}
                            
                            {isInSpace && (
                                <Button
                                    onClick={handleLeaveSpace}
                                    variant="secondary"
                                >
                                    Quitter
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="max-w-7xl mx-auto px-4 py-6">
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    {/* Colonne principale */}
                    <div className="lg:col-span-3">
                        {/* Description */}
                        {space.description && (
                            <div className="bg-gb-dark-light rounded-lg p-6 mb-6">
                                <h2 className="text-lg font-semibold text-gb-white mb-3">Description</h2>
                                <p className="text-gb-light-gray">{space.description}</p>
                            </div>
                        )}

                        {/* Contrôles audio (si dans le space) */}
                        {isInSpace && localStream && (
                            <div className="bg-gb-dark-light rounded-lg p-6 mb-6">
                                <h2 className="text-lg font-semibold text-gb-white mb-4">Contrôles Audio</h2>
                                <div className="audio-controls">
                                    <button
                                        onClick={toggleMute}
                                        className={`mic-button ${isMuted ? 'muted' : 'unmuted'}`}
                                        title={isMuted ? 'Activer le micro' : 'Couper le micro'}
                                    >
                                        {isMuted ? '🎤' : '🔇'}
                                    </button>
                                    
                                    <button
                                        onClick={toggleDeafen}
                                        className={`mic-button ${isDeafened ? 'muted' : 'unmuted'}`}
                                        title={isDeafened ? 'Activer le son' : 'Couper le son'}
                                    >
                                        {isDeafened ? '🔇' : '🔊'}
                                    </button>
                                    
                                    <div className="text-center">
                                        <div className="text-gb-white font-medium">
                                            {isMuted ? 'Micro coupé' : 'Micro actif'}
                                        </div>
                                        <div className="text-gb-light-gray text-sm">
                                            {isDeafened ? 'Son coupé' : 'Son actif'}
                                        </div>
                                    </div>
                                    
                                    {/* Boutons d'enregistrement (uniquement pour le créateur) */}
                                    {isHost && (
                                        <div className="ml-6 flex items-center space-x-3">
                                            {isRecording ? (
                                                <>
                                                    <button
                                                        onClick={stopRecording}
                                                        className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center space-x-2"
                                                        title="Arrêter l'enregistrement"
                                                    >
                                                        <span>⏹ Arrêter</span>
                                                    </button>
                                                    <div className="text-red-500 animate-pulse flex items-center">
                                                        <span className="mr-2">⚫</span>
                                                        <span>{formatRecordingTime()}</span>
                                                    </div>
                                                </>
                                            ) : (
                                                <button
                                                    onClick={startRecording}
                                                    className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center space-x-2"
                                                    title="Démarrer l'enregistrement"
                                                >
                                                    <span>⏺ Enregistrer</span>
                                                </button>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Chat */}
                        <div className="bg-gb-dark-light rounded-lg p-6">
                            <h2 className="text-lg font-semibold text-gb-white mb-4">Chat</h2>
                            
                            {/* Messages */}
                            <div className="h-64 overflow-y-auto mb-4 space-y-3">
                                {messages.length === 0 ? (
                                    <div className="text-center text-gb-light-gray py-8">
                                        Aucun message pour le moment.
                                    </div>
                                ) : (
                                    messages.map((message) => (
                                        <div key={message.id} className="chat-message">
                                            <div className="flex items-start space-x-3">
                                                <img
                                                    src={message.user?.avatar || `${defaultAvatar}${encodeURIComponent(message.user?.username || 'User')}`}
                                                    alt="Avatar"
                                                    className="w-8 h-8 rounded-full"
                                                />
                                                <div className="flex-1">
                                                    <div className="flex items-center space-x-2">
                                                        <span className="font-medium text-gb-white">
                                                            {message.user?.display_name || message.user?.username}
                                                        </span>
                                                        <span className="text-xs text-gb-light-gray">
                                                            {message.created_at_formatted}
                                                        </span>
                                                    </div>
                                                    <p className="text-gb-light-gray mt-1">{message.content}</p>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}
                                <div ref={messagesEndRef} />
                            </div>

                            {/* Formulaire de message */}
                            {isInSpace && (
                                <form onSubmit={sendMessage} className="flex space-x-2">
                                    <input
                                        type="text"
                                        value={newMessageContent}
                                        onChange={(e) => setNewMessageContent(e.target.value)}
                                        placeholder="Tapez votre message..."
                                        className="flex-1 px-3 py-2 bg-gb-dark border border-gb-gray-dark rounded text-gb-white placeholder-gb-light-gray focus:outline-none focus:border-gb-purple"
                                    />
                                    <Button type="submit" variant="primary" size="sm">
                                        Envoyer
                                    </Button>
                                </form>
                            )}
                        </div>
                    </div>

                    {/* Sidebar */}
                    <div className="lg:col-span-1">
                        {/* Informations sur l'hôte */}
                        <div className="bg-gb-dark-light rounded-lg p-6 mb-6">
                            <h3 className="text-lg font-semibold text-gb-white mb-4">Hôte</h3>
                            <div className="flex items-center space-x-3">
                                <img
                                    src={space.host?.avatar || `${defaultAvatar}${encodeURIComponent(space.host?.username || 'Host')}`}
                                    alt="Avatar hôte"
                                    className="w-12 h-12 rounded-full"
                                />
                                <div>
                                    <div className="font-medium text-gb-white">
                                        {space.host?.display_name || space.host?.username}
                                    </div>
                                    <div className="text-sm text-gb-light-gray">
                                        @{space.host?.username}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Liste des participants */}
                        <div className="bg-gb-dark-light rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gb-white mb-4">
                                Participants ({participants.length})
                            </h3>
                            <div className="space-y-3">
                                {participants.map((participant) => (
                                    <div key={participant.id} className="flex items-center space-x-3">
                                        <img
                                            src={participant.avatar || `${defaultAvatar}${encodeURIComponent(participant.username || 'User')}`}
                                            alt="Avatar"
                                            className={`w-8 h-8 rounded-full participant-avatar ${
                                                speakingParticipants[participant.id] ? 'speaking' : ''
                                            }`}
                                        />
                                        <div className="flex-1">
                                            <div className="text-sm font-medium text-gb-white">
                                                {participant.display_name || participant.username}
                                                {participant.id === currentUser?.id && (
                                                    <span className="text-xs text-gb-purple ml-1">(Vous)</span>
                                                )}
                                                {participant.id === space.host?.id && (
                                                    <span className="text-xs text-yellow-400 ml-1">👑</span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SpaceDetailPage;