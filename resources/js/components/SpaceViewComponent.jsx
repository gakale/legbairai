import React, { useState, useEffect, useRef, useCallback } from 'react';
import axios from 'axios';

function SpaceViewComponent({ spaceId, currentUserId }) {
    const [spaceDetails, setSpaceDetails] = useState(null);
    const [participants, setParticipants] = useState([]);
    const [connectionStatus, setConnectionStatus] = useState('Déconnecté');
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [sendingMessage, setSendingMessage] = useState(false);
    const [pinnedMessage, setPinnedMessage] = useState(null);
    const messagesEndRef = useRef(null);

    // Fonction pour charger les détails initiaux du space et les messages
    const fetchInitialData = useCallback(async () => {
        try {
            // Dans une vraie app, ce serait un appel API
            console.log(`Chargement initial des données pour Space ${spaceId}`);
            // axios.get(`/api/v1/spaces/${spaceId}/initial-data`).then(response => {
            //     setSpaceDetails(response.data.space);
            //     setMessages(response.data.messages);
            //     setPinnedMessage(response.data.pinned_message);
            // });
            
            // Simulation :
            setSpaceDetails({ id: spaceId, title: `Titre du Space ${spaceId}` });
            setMessages([{ 
                id: 'msg1', 
                content: 'Premier message!', 
                sender: { username: 'TestUser' }, 
                created_at_formatted: '10:00'
            }]);
        } catch (error) {
            console.error("Erreur lors du chargement des données initiales:", error);
        }
    }, [spaceId]);

    useEffect(() => {
        if (!spaceId || !window.Echo) return;

        fetchInitialData(); // Charger les données initiales

        console.log(`Tentative de connexion au canal de présence : space.${spaceId}`);
        setConnectionStatus('Connexion...');

        // S'abonner au canal de PRÉSENCE du Space
        const presenceChannel = window.Echo.join(`space.${spaceId}`);

        presenceChannel
            .on('pusher:subscription_succeeded', () => {
                setConnectionStatus(`Connecté au canal de présence space.${spaceId}`);
                console.log(`Abonnement PRÉSENCE réussi au canal : space.${spaceId}`);
            })
            .on('pusher:subscription_error', (status) => {
                setConnectionStatus(`Échec de la connexion au canal de présence (code: ${status}).`);
                console.error(`Erreur d'abonnement PRÉSENCE au canal space.${spaceId}:`, status);
            })
            .here((users) => { // Se déclenche quand vous rejoignez avec succès le canal
                console.log('Membres présents initialement:', users);
                setParticipants(users); // Liste des utilisateurs actuellement sur le canal
            })
            .joining((user) => { // Se déclenche quand un NOUVEL utilisateur rejoint le canal
                console.log('Nouveau membre a rejoint:', user);
                setParticipants(prevParticipants => {
                    if (!prevParticipants.find(p => p.id === user.id)) {
                        return [...prevParticipants, user];
                    }
                    return prevParticipants;
                });
            })
            .leaving((user) => { // Se déclenche quand un utilisateur quitte le canal
                console.log('Membre a quitté:', user);
                setParticipants(prevParticipants =>
                    prevParticipants.filter(p => p.id !== user.id)
                );
            })
            // Écoute de nos événements métier personnalisés (diffusés sur le même canal de présence)
            .listen('.space.started', (eventData) => {
                console.log('Événement ".space.started" reçu:', eventData);
                // Mettre à jour l'état du space si nécessaire
            })
            .listen('.message.new', (eventData) => {
                console.log('Événement ".message.new" reçu:', eventData);
                setMessages(prev => [...prev, eventData.message]);
            })
            .listen('.participant.hand_status', (eventData) => {
                console.log('Événement ".participant.hand_status" reçu:', eventData);
                setParticipants(prev =>
                    prev.map(p =>
                        p.id === eventData.user_id // L'événement envoie user_id
                            ? { ...p, has_raised_hand: eventData.has_raised_hand }
                            : p
                    )
                );
            })
            .listen('.participant.role_changed', (eventData) => {
                console.log('Événement ".participant.role_changed" reçu:', eventData);
                setParticipants(prev =>
                    prev.map(p =>
                        p.id === eventData.participant.user.id // L'événement envoie participant.user.id
                            ? { ...p, role: eventData.participant.role, role_label: eventData.participant.role_label } // Mettre à jour le rôle
                            : p
                    )
                );
            })
            .listen('.participant.muted_status_changed', (eventData) => {
                console.log('Événement ".participant.muted_status_changed" reçu:', eventData);
                setParticipants(prev =>
                    prev.map(p =>
                        p.id === eventData.user_id // L'événement envoie user_id
                            ? { ...p, is_muted_by_host: eventData.is_muted_by_host }
                            : p
                    )
                );
            })
            .listen('.message.pinned_status_changed', (eventData) => {
                console.log('Événement ".message.pinned_status_changed" reçu:', eventData);
                const updatedMessage = eventData.message;
                
                // Mise à jour des messages dans la liste
                setMessages(prevMessages =>
                    prevMessages.map(msg =>
                        msg.id === updatedMessage.id
                            ? { ...msg, is_pinned: updatedMessage.is_pinned }
                            : updatedMessage.is_pinned ? { ...msg, is_pinned: false } : msg
                    )
                );
                
                // Mise à jour du message épinglé affiché
                if (updatedMessage.is_pinned) {
                    // Si le message est épinglé, on l'affiche comme message épinglé
                    setPinnedMessage(updatedMessage);
                } else if (pinnedMessage && pinnedMessage.id === updatedMessage.id) {
                    // Si le message actuellement épinglé est détaché, on retire l'affichage
                    setPinnedMessage(null);
                }
            });

        // Nettoyage lors du démontage du composant
        return () => {
            console.log(`Déconnexion du canal de présence : space.${spaceId}`);
            window.Echo.leave(`space.${spaceId}`); // Utiliser leave() pour les Presence Channels
            setConnectionStatus('Déconnecté');
        };
    }, [spaceId, fetchInitialData, currentUserId, pinnedMessage]);

    // Faire défiler vers le bas lorsque de nouveaux messages arrivent
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    // Envoyer un message
    const sendMessage = async () => {
        if (!newMessage.trim() || sendingMessage) return;

        setSendingMessage(true);
        try {
            const response = await axios.post(`/api/v1/spaces/${spaceId}/message`, {
                content: newMessage
            });
            console.log('Message envoyé:', response.data);
            setNewMessage(''); // Vider le champ après envoi
        } catch (error) {
            console.error('Erreur lors de l\'envoi du message:', error);
            alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
        } finally {
            setSendingMessage(false);
        }
    };

    // Épingler ou détacher un message
    const togglePinMessage = async (messageId, pin) => {
        try {
            const response = await axios.post(`/api/v1/spaces/messages/${messageId}/toggle-pin`, {
                pin: pin
            });
            console.log(`Message ${pin ? 'épinglé' : 'détaché'} avec succès:`, response.data);
            // L'UI sera mise à jour automatiquement via l'événement WebSocket
        } catch (error) {
            console.error(`Erreur lors de l'${pin ? 'épinglage' : 'détachement'} du message:`, error);
            alert(`Erreur lors de l'${pin ? 'épinglage' : 'détachement'} du message. Veuillez réessayer.`);
        }
    };

    return (
        <div className="space-view">
            <h2>Space: {spaceDetails?.title || `ID: ${spaceId}`}</h2>
            <p>Statut de connexion: <strong>{connectionStatus}</strong></p>
            
            {pinnedMessage && (
                <div style={{ border: '2px solid gold', padding: '10px', marginBottom: '10px', backgroundColor: '#fffdf0' }}>
                    <div className="d-flex justify-content-between align-items-center">
                        <strong>📌 Message Épinglé : {pinnedMessage.sender?.username || pinnedMessage.sender?.name}</strong>
                        <button 
                            className="btn btn-sm btn-warning" 
                            onClick={() => togglePinMessage(pinnedMessage.id, false)}
                        >
                            Détacher
                        </button>
                    </div>
                    <div className="mt-2">{pinnedMessage.content}</div>
                </div>
            )}
            
            <div className="row">
                <div className="col-md-8">
                    <div className="card">
                        <div className="card-header">Chat</div>
                        <div className="card-body">
                            <div className="chat-messages" style={{ height: '300px', overflowY: 'scroll', marginBottom: '15px' }}>
                                {messages.length === 0 ? (
                                    <p className="text-muted">Aucun message pour le moment.</p>
                                ) : (
                                    messages.map((message, index) => (
                                        <div 
                                            key={message.id || index} 
                                            className="message mb-3"
                                            style={{
                                                borderLeft: message.is_pinned ? '3px solid gold' : 'none',
                                                paddingLeft: message.is_pinned ? '10px' : '0'
                                            }}
                                        >
                                            <div className="d-flex justify-content-between">
                                                <strong>{message.sender?.username || message.sender?.name || 'Anonyme'}</strong>
                                                <small className="text-muted">{message.created_at_formatted}</small>
                                            </div>
                                            <div className="message-content p-2 bg-light rounded">
                                                {message.content}
                                            </div>
                                            <div className="message-actions mt-1 text-end">
                                                <button 
                                                    className="btn btn-sm btn-outline-primary" 
                                                    onClick={() => togglePinMessage(message.id, !message.is_pinned)}
                                                >
                                                    {message.is_pinned ? 'Détacher' : '📌 Épingler'}
                                                </button>
                                            </div>
                                        </div>
                                    ))
                                )}
                                <div ref={messagesEndRef} />
                            </div>
                            
                            <div className="input-group">
                                <input 
                                    type="text" 
                                    className="form-control" 
                                    placeholder="Tapez votre message..." 
                                    value={newMessage}
                                    onChange={(e) => setNewMessage(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && sendMessage()}
                                    disabled={sendingMessage}
                                />
                                <button 
                                    className="btn btn-primary" 
                                    onClick={sendMessage}
                                    disabled={sendingMessage}
                                >
                                    {sendingMessage ? 'Envoi...' : 'Envoyer'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div className="col-md-4">
                    <div className="card">
                        <div className="card-header">Participants ({participants.length})</div>
                        <div className="card-body">
                            <ul className="list-group">
                                {participants.map((participant, index) => (
                                    <li key={participant.id || index} className="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <img 
                                                src={participant.avatar_url || `https://ui-avatars.com/api/?name=${participant.username}&background=random`} 
                                                alt={participant.username} 
                                                width="25" 
                                                height="25" 
                                                style={{ borderRadius: '50%', marginRight: '5px' }} 
                                            />
                                            <span>{participant.username} ({participant.role})</span>
                                        </div>
                                        <div>
                                            {participant.has_raised_hand && <span title="Main levée" className="me-2">🖐️</span>}
                                            {participant.is_muted_by_host && <span title="Muté par l'hôte" className="me-2">🔇</span>}
                                            {!participant.is_muted_by_host && participant.role === 'speaker' && <span title="Micro ouvert" className="me-2">🎤</span>}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default SpaceViewComponent;
