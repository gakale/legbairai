import React, { useEffect, useState, useCallback, useRef } from 'react';
import axios from 'axios';
// Assurez-vous que window.Echo est initialisé

function SpaceViewComponent({ spaceId, currentUserId }) { // currentUserId est l'ID de l'utilisateur connecté
    const [spaceDetails, setSpaceDetails] = useState(null);
    const [participants, setParticipants] = useState([]);
    const [connectionStatus, setConnectionStatus] = useState('Déconnecté');
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [sendingMessage, setSendingMessage] = useState(false);
    const messagesEndRef = useRef(null);

    // Fonction pour charger les détails initiaux du space et les participants
    const fetchSpaceData = useCallback(async () => {
        try {
            // Remplacez par votre appel API réel
            // const response = await axios.get(`/api/v1/spaces/${spaceId}`);
            // setSpaceDetails(response.data.data);
            // setParticipants(response.data.data.participants || []); // Suppose que les participants sont inclus
            console.log(`Données initiales pour Space ${spaceId} chargées (simulation)`);
            // Simulation:
            setSpaceDetails({ id: spaceId, title: "Titre du Space (initial)"});
            setParticipants([{ id: 'user-host-uuid', user: { id: 'user-host-uuid', username: 'HôteInitial'}, role_label: 'Hôte' }]);

        } catch (error) {
            console.error("Erreur lors du chargement des données du space:", error);
        }
    }, [spaceId]);

    useEffect(() => {
        if (!spaceId || !window.Echo) return;

        fetchSpaceData(); // Charger les données initiales

        console.log(`Tentative de connexion au canal privé : space.${spaceId}`);
        setConnectionStatus('Connexion...');

        // S'abonner au canal PRIVÉ du Space
        const privateSpaceChannel = window.Echo.private(`space.${spaceId}`);

        privateSpaceChannel
            .on('pusher:subscription_succeeded', () => {
                setConnectionStatus(`Connecté au canal privé space.${spaceId}`);
                console.log(`Abonnement réussi au canal privé : space.${spaceId}`);
            })
            .on('pusher:subscription_error', (status) => {
                setConnectionStatus(`Échec de la connexion au canal privé (code: ${status}). Vérifiez l'autorisation et les logs serveur Reverb/Laravel.`);
                console.error(`Erreur d'abonnement au canal privé space.${spaceId}:`, status);
            })
            .listen('.user.joined', (eventData) => {
                console.log('Événement "user.joined" reçu:', eventData);
                // Vérifier si le participant n'est pas déjà dans la liste pour éviter les doublons
                setParticipants(prevParticipants => {
                    if (!prevParticipants.find(p => p.id === eventData.participant.id)) {
                        return [...prevParticipants, eventData.participant];
                    }
                    return prevParticipants;
                });
            })
            .listen('.user.left', (eventData) => {
                console.log('Événement "user.left" reçu:', eventData);
                setParticipants(prevParticipants =>
                    prevParticipants.filter(p => p.user.id !== eventData.user_id)
                );
            })
            .listen('.participant.hand_status', (eventData) => {
                console.log('Événement "participant.hand_status" reçu:', eventData);
                setParticipants(prevParticipants =>
                    prevParticipants.map(p =>
                        p.id === eventData.participant_id
                            ? { ...p, has_raised_hand: eventData.has_raised_hand }
                            : p
                    )
                );
            })
            .listen('.participant.role_changed', (eventData) => {
                console.log('Événement "participant.role_changed" reçu:', eventData);
                setParticipants(prevParticipants =>
                    prevParticipants.map(p =>
                        p.id === eventData.participant_id
                            ? { ...p, role: eventData.role, user: { ...p.user, name: eventData.name }, role_label: eventData.role } // Assuming role_label can be derived or is same as role for now
                            : p
                    )
                );
            })
            .listen('.message.new', (eventData) => {
                console.log('Événement "message.new" reçu:', eventData);
                setMessages(prevMessages => {
                    // Éviter d'ajouter un message si son ID est déjà dans la liste
                    if (prevMessages.find(m => m.id === eventData.message.id)) {
                        return prevMessages;
                    }
                    return [...prevMessages, eventData.message];
                });
            });
            // ... écouter d'autres événements (new.message etc.)

        return () => {
            console.log(`Déconnexion du canal privé : space.${spaceId}`);
            window.Echo.leaveChannel(`private-space.${spaceId}`); // leaveChannel est correct pour public et privé
            setConnectionStatus('Déconnecté');
        };
    }, [spaceId, fetchSpaceData]);
    
    // Effet pour faire défiler vers le bas quand de nouveaux messages arrivent
    useEffect(() => {
        if (messagesEndRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages]);
    
    // Fonction pour envoyer un message
    const handleSendMessage = async (e) => {
        e.preventDefault();
        if (!newMessage.trim() || sendingMessage) return;
        
        setSendingMessage(true);
        try {
            const response = await axios.post(`/api/v1/spaces/${spaceId}/message`, {
                content: newMessage
            }, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            console.log('Message envoyé avec succès:', response.data);
            setNewMessage(''); // Vider le champ après envoi
        } catch (error) {
            console.error('Erreur lors de l\'envoi du message:', error);
            alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
        } finally {
            setSendingMessage(false);
        }
    };

    return (
        <div className="space-view-component">
            <h2>Space: {spaceDetails?.title || spaceId}</h2>
            <p>Statut Connexion: <strong>{connectionStatus}</strong></p>
            <h3>Participants ({participants.length})</h3>
            <ul className="participants-list">
                {participants.map(p => (
                    <li key={p.id || p.user.id} className="participant-item">
                        {p.user?.username || p.user?.name || 'Utilisateur inconnu'} 
                        <span className="role-badge">({p.role_label || p.role})</span>
                        {p.has_raised_hand && <span className="hand-raised-indicator">✋</span>}
                    </li>
                ))}
            </ul>
            {/* Section de chat */}
            <div className="chat-section mt-4">
                <h3>Chat</h3>
                <div className="chat-messages" style={{ height: '300px', overflowY: 'scroll', border: '1px solid #ddd', padding: '10px', marginBottom: '10px', borderRadius: '5px' }}>
                    {messages.length === 0 ? (
                        <p className="text-muted">Aucun message pour le moment. Soyez le premier à écrire !</p>
                    ) : (
                        messages.map(msg => (
                            <div key={msg.id} className={`message ${msg.sender?.id === currentUserId ? 'message-mine' : ''}`} style={{ marginBottom: '10px' }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                    <strong>{msg.sender?.username || msg.sender?.name || 'Anonyme'}</strong>
                                    <small style={{ color: 'gray' }}>{msg.created_at_formatted || new Date().toLocaleTimeString()}</small>
                                </div>
                                <div style={{ 
                                    backgroundColor: msg.sender?.id === currentUserId ? '#dcf8c6' : '#f1f0f0', 
                                    padding: '8px', 
                                    borderRadius: '5px',
                                    marginTop: '5px'
                                }}>
                                    {msg.content}
                                </div>
                            </div>
                        ))
                    )}
                    <div ref={messagesEndRef} />
                </div>
                
                <form onSubmit={handleSendMessage} className="chat-form">
                    <div className="input-group">
                        <input 
                            type="text" 
                            className="form-control" 
                            placeholder="Tapez votre message..." 
                            value={newMessage} 
                            onChange={(e) => setNewMessage(e.target.value)}
                            disabled={sendingMessage}
                        />
                        <button 
                            type="submit" 
                            className="btn btn-primary"
                            disabled={!newMessage.trim() || sendingMessage}
                        >
                            {sendingMessage ? 'Envoi...' : 'Envoyer'}
                        </button>
                    </div>
                </form>
            </div>
            
            <div className="action-buttons mt-4">
                <h4>Actions de test:</h4>
                <button 
                    className="btn btn-primary"
                    onClick={() => window.open(`/realtime-test/space/${spaceId}/join`, '_blank')}
                >
                    Simuler un utilisateur qui rejoint (nouvel onglet)
                </button>
                <button 
                    className="btn btn-danger"
                    onClick={() => window.open(`/realtime-test/space/${spaceId}/leave`, '_blank')}
                >
                    Simuler un utilisateur qui quitte (nouvel onglet)
                </button>
                
                <h4>Actions pour participants:</h4>
                {participants.length > 0 && (
                    <div className="participant-actions">
                        {participants.map(p => (
                            <div key={`actions-${p.id || p.user.id}`} className="participant-action-row">
                                <span>{p.user?.username || p.user?.name || 'Utilisateur'}</span>
                                <button 
                                    className="btn btn-sm btn-warning"
                                    onClick={() => {
                                        const url = `/realtime-test/space/${spaceId}/participant/${p.id}/raise-hand`;
                                        fetch(url, { method: 'POST' })
                                            .then(response => response.json())
                                            .then(data => console.log('Réponse lever main:', data))
                                            .catch(error => console.error('Erreur lever main:', error));
                                    }}
                                >
                                    {p.has_raised_hand ? 'Baisser la main' : 'Lever la main'}
                                </button>
                                <select 
                                    className="form-select form-select-sm"
                                    onChange={(e) => {
                                        const url = `/realtime-test/space/${spaceId}/participant/${p.id}/change-role`;
                                        fetch(url, { 
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                                            },
                                            body: JSON.stringify({ new_role: e.target.value })
                                        })
                                            .then(response => response.json())
                                            .then(data => console.log('Réponse changement rôle:', data))
                                            .catch(error => console.error('Erreur changement rôle:', error));
                                    }}
                                >
                                    <option value="" disabled selected>Changer rôle</option>
                                    <option value="listener">Auditeur</option>
                                    <option value="speaker">Intervenant</option>
                                    <option value="co_host">Co-hôte</option>
                                </select>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

export default SpaceViewComponent;
