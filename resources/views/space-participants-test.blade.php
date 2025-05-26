<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test des Participants en Temps Réel - Legbairai</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .space-view-component {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .participants-list {
            list-style-type: none;
            padding: 0;
        }
        .participant-item {
            padding: 8px;
            margin-bottom: 5px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }
        .role-badge {
            background-color: #007bff;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 5px;
            font-size: 0.8em;
        }
        .action-buttons {
            margin-top: 20px;
        }
        .action-buttons button {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>Test des Participants en Temps Réel - Legbairai</h1>
        <p>Cette page permet de tester la réception d'événements en temps réel pour les participants d'un space.</p>
        
        <!-- Liste des espaces disponibles pour les tests -->
        <div class="mb-4">
            <h3>Espaces disponibles pour les tests :</h3>
            @if(isset($spaces) && $spaces->count() > 0)
                <div class="list-group">
                    @foreach($spaces as $space)
                        <div class="list-group-item">
                            <h5>{{ $space->title ?? 'Sans titre' }}</h5>
                            <p><strong>ID de l'espace :</strong> <code>{{ $space->id }}</code></p>
                            <p><strong>ID de l'hôte :</strong> <code>{{ $space->host_user_id }}</code></p>
                            <button class="btn btn-sm btn-primary copy-btn" data-clipboard-text="{{ $space->id }}">Copier l'ID</button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning">Aucun espace disponible. Créez d'abord des espaces dans l'application.</div>
            @endif
        </div>
        
        <!-- Conteneur pour notre application React -->
        <div id="space-participants-test" data-space-id="">
            <div class="alert alert-info">
                Chargement du composant React...
            </div>
        </div>
        
        <!-- Boutons de test directs (sans React) -->
        <div class="direct-test-buttons mt-4 mb-4">
            <h4>Actions de test directes :</h4>
            <a href="#" onclick="testJoinSpace(); return false;" class="btn btn-primary">Ajouter un participant</a>
            <a href="#" onclick="testRaiseHand(); return false;" class="btn btn-warning">Test lever main (après avoir ajouté un participant)</a>
            <a href="#" onclick="testChangeRole(); return false;" class="btn btn-info">Test changer rôle (après avoir ajouté un participant)</a>
            <a href="#" onclick="testMuteParticipant(); return false;" class="btn btn-danger">Muter un participant</a>
            <a href="#" onclick="testUnmuteParticipant(); return false;" class="btn btn-success">Démuter un participant</a>
            <a href="#" onclick="testSendMessage(); return false;" class="btn btn-success">Envoyer un message de test</a>
        </div>
        
        <!-- Section de chat directe (sans React) -->
        <div class="chat-section mt-4 mb-4">
            <h3>Chat (sans React)</h3>
            
            <!-- Message épinglé -->
            <div id="pinned-message-container" style="display: none; border: 2px solid gold; padding: 10px; margin-bottom: 10px; background-color: #fffdf0;">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>📌 Message épinglé</strong>
                    <button id="unpin-button" class="btn btn-sm btn-warning">Détacher</button>
                </div>
                <div id="pinned-message-content" class="mt-2"></div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="current-space-id" class="form-label">ID de l'espace pour le chat:</label>
                        <div class="input-group">
                            <input type="text" id="current-space-id" class="form-control" placeholder="Collez l'UUID de l'espace ici">
                            <button onclick="subscribeToSpace()" class="btn btn-secondary">Se connecter</button>
                        </div>
                        <div id="connection-status" class="mt-2">Non connecté</div>
                    </div>
                    
                    <!-- Section de test pour l'épinglage -->
                    <div class="mb-3 p-2" style="background-color: #f8f9fa; border-radius: 5px;">
                        <h5>Test d'épinglage direct</h5>
                        <div class="input-group mb-2">
                            <input type="text" id="message-id-to-pin" class="form-control" placeholder="ID du message à épingler">
                            <button onclick="testPinMessage()" class="btn btn-warning">Tester épinglage</button>
                        </div>
                        <small class="text-muted">Copiez l'ID d'un message depuis la console et collez-le ici pour tester l'épinglage direct.</small>
                    </div>
                    <div id="chat-messages" style="height: 200px; overflow-y: scroll; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                        <p class="text-muted">Aucun message pour le moment.</p>
                    </div>
                    <div class="input-group">
                        <input type="text" id="chat-input" class="form-control" placeholder="Tapez votre message...">
                        <button onclick="sendDirectMessage()" class="btn btn-primary">Envoyer</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function testJoinSpace() {
            const spaceId = prompt('Entrez l\'UUID de l\'espace:', '');
            if (!spaceId) return;
            
            fetch(`/realtime-test/space/${spaceId}/join`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                alert('Participant ajouté : ' + JSON.stringify(data));
                console.log('Participant ajouté:', data);
                if (data.participant && data.participant.id) {
                    console.log('ID du participant à utiliser pour les tests:', data.participant.id);
                }
            })
            .catch(error => {
                alert('Erreur: ' + error);
                console.error('Erreur ajout participant:', error);
            });
        }
        
        function testRaiseHand() {
            // Demander l'ID de l'espace et du participant
            const spaceId = prompt('Entrez l\'UUID de l\'espace:', '');
            if (!spaceId) return;
            
            const participantId = prompt('Entrez l\'ID du participant (visible dans la console après avoir ajouté un participant):', '');
            if (participantId) {
                fetch(`/realtime-test/space/${spaceId}/participant/${participantId}/raise-hand`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    alert('Résultat: ' + JSON.stringify(data));
                    console.log('Réponse lever main:', data);
                })
                .catch(error => {
                    alert('Erreur: ' + error);
                    console.error('Erreur lever main:', error);
                });
            }
        }
        
        function testChangeRole() {
            // Demander l'ID de l'espace et du participant
            const spaceId = prompt('Entrez l\'UUID de l\'espace:', '');
            if (!spaceId) return;
            
            const participantId = prompt('Entrez l\'ID du participant:', '');
            if (!participantId) return;
            
            const newRole = prompt('Entrez le nouveau rôle (listener, speaker, co_host):', 'speaker');
            if (!newRole) return;
            
            fetch(`/realtime-test/space/${spaceId}/participant/${participantId}/change-role`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ new_role: newRole })
            })
            .then(response => response.json())
            .then(data => {
                alert('Résultat du changement de rôle: ' + JSON.stringify(data));
                console.log('Réponse changement rôle:', data);
            })
            .catch(error => {
                alert('Erreur: ' + error);
                console.error('Erreur changement rôle:', error);
            });
        }
        
        function testMuteParticipant() {
            // Demander l'ID de l'espace et du participant
            const spaceId = prompt('Entrez l\'UUID de l\'espace:', '');
            if (!spaceId) return;
            
            const participantId = prompt('Entrez l\'ID du participant:', '');
            if (!participantId) return;
            
            fetch(`/realtime-test/space/${spaceId}/participant/${participantId}/mute`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                alert('Résultat du mute: ' + JSON.stringify(data));
                console.log('Réponse mute:', data);
            })
            .catch(error => {
                alert('Erreur: ' + error);
                console.error('Erreur mute:', error);
            });
        }
        
        function testUnmuteParticipant() {
            // Demander l'ID de l'espace et du participant
            const spaceId = prompt('Entrez l\'UUID de l\'espace:', '');
            if (!spaceId) return;
            
            const participantId = prompt('Entrez l\'ID du participant:', '');
            if (!participantId) return;
            
            fetch(`/realtime-test/space/${spaceId}/participant/${participantId}/unmute`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                alert('Résultat du unmute: ' + JSON.stringify(data));
                console.log('Réponse unmute:', data);
            })
            .catch(error => {
                alert('Erreur: ' + error);
                console.error('Erreur unmute:', error);
            });
        }
        
        function testSendMessage() {
            // Demander l'ID de l'espace
            const spaceId = prompt('Entrez l\'UUID de l\'espace:', '');
            if (!spaceId) return;
            
            const messageContent = prompt('Entrez votre message:', '');
            if (!messageContent) return;
            
            sendMessage(spaceId, messageContent);
        }
        
        function sendDirectMessage() {
            const spaceId = document.getElementById('current-space-id')?.value || prompt('Entrez l\'UUID de l\'espace:', '');
            if (!spaceId) return;
            
            const messageContent = document.getElementById('chat-input').value;
            if (!messageContent.trim()) return;
            
            document.getElementById('chat-input').value = '';
            sendMessage(spaceId, messageContent);
        }
        
        function sendMessage(spaceId, content) {
            return fetch(`/realtime-test/space/${spaceId}/message`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ content: content })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Message envoyé avec succès:', data);
                // Le message sera ajouté via l'événement WebSocket
            })
            .catch(error => {
                console.error('Erreur lors de l\'envoi du message:', error);
                alert('Erreur lors de l\'envoi du message: ' + error.message);
            });
        }
        
        // Variable pour stocker la référence au canal privé
        let currentPrivateChannel = null;
        
        // Fonction pour envoyer un message depuis l'interface de chat
        function sendDirectMessage() {
            const spaceId = document.getElementById('current-space-id').value.trim();
            const messageContent = document.getElementById('chat-input').value.trim();
            
            if (!spaceId) {
                alert('Veuillez d\'abord vous connecter à un espace');
                return;
            }
            
            if (!messageContent) {
                return; // Ne rien faire si le message est vide
            }
            
            // Optimistic UI - Ajouter le message immédiatement
            addMessageToChat({
                content: messageContent,
                sender: {
                    name: 'Vous',
                    username: 'Vous'
                },
                created_at_formatted: new Date().toLocaleTimeString()
            });
            
            // Vider le champ de saisie
            document.getElementById('chat-input').value = '';
            
            // Envoyer le message au serveur
            try {
                fetch(`/realtime-test/space/${spaceId}/message`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ content: messageContent })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Message envoyé avec succès:', data);
                })
                .catch(error => {
                    console.error('Erreur lors de l\'envoi du message:', error);
                    // Afficher l'erreur dans le chat
                    addMessageToChat({
                        content: `Erreur: ${error.message}`,
                        sender: {
                            name: 'Système',
                            username: 'Système'
                        },
                        created_at_formatted: new Date().toLocaleTimeString()
                    });
                });
            } catch (error) {
                console.error('Erreur lors de l\'envoi du message:', error);
                // Afficher l'erreur dans le chat
                addMessageToChat({
                    content: `Erreur: ${error.message}`,
                    sender: {
                        name: 'Système',
                        username: 'Système'
                    },
                    created_at_formatted: new Date().toLocaleTimeString()
                });
            }
        }
        
        // Fonction pour s'abonner à un espace
        function subscribeToSpace() {
            const spaceId = document.getElementById('current-space-id').value.trim();
            if (!spaceId) {
                alert('Veuillez entrer un ID d\'espace valide');
                return;
            }
            
            // Quitter le canal précédent s'il existe
            if (currentPrivateChannel) {
                window.Echo.leaveChannel(`test-space.${currentPrivateChannel}`);
                currentPrivateChannel = null;
            }
            
            // Mettre à jour le statut de connexion
            const statusElement = document.getElementById('connection-status');
            statusElement.textContent = 'Connexion en cours...';
            statusElement.className = 'mt-2 text-warning';
            
            // S'abonner au canal public de test pour l'espace
            currentPrivateChannel = spaceId;
            const privateSpaceChannel = window.Echo.channel(`test-space.${spaceId}`);
            
            privateSpaceChannel
                .on('pusher:subscription_succeeded', () => {
                    statusElement.textContent = `Connecté au canal privé space.${spaceId}`;
                    statusElement.className = 'mt-2 text-success';
                    console.log(`Abonnement réussi au canal privé : space.${spaceId}`);
                    
                    // Vider les messages précédents
                    const chatMessages = document.getElementById('chat-messages');
                    chatMessages.innerHTML = '<p class="text-success">Connecté ! En attente de messages...</p>';
                })
                .on('pusher:subscription_error', (status) => {
                    statusElement.textContent = `Échec de la connexion au canal privé (code: ${status})`;
                    statusElement.className = 'mt-2 text-danger';
                    console.error(`Erreur d'abonnement au canal privé space.${spaceId}:`, status);
                })
                .listen('message.new', (eventData) => {
                    console.log('Événement "message.new" reçu:', eventData);
                    addMessageToChat(eventData.message);
                })
                .listen('message.pinned_status_changed', (eventData) => {
                    console.log('Événement "message.pinned_status_changed" reçu:', eventData);
                    updatePinnedStatus(eventData.message);
                });
        }
        
        // Fonction pour ajouter un message au chat
        function addMessageToChat(message) {
            console.log('Message reçu:', message);
            
            const chatMessages = document.getElementById('chat-messages');
            
            // Supprimer le message "Aucun message" s'il existe
            const emptyMessage = chatMessages.querySelector('.text-muted');
            if (emptyMessage) {
                chatMessages.innerHTML = '';
            }
            
            // Créer l'élément de message
            const messageElement = document.createElement('div');
            messageElement.className = 'message mb-2';
            
            // Récupérer l'ID du message de manière plus robuste
            // Essayer toutes les possibilités d'emplacement de l'ID
            const messageId = message.id || message.data?.id || (typeof message === 'object' && message.hasOwnProperty('message') ? message.message.id : null);
            console.log('ID du message pour affichage:', messageId);
            
            // Stocker l'ID du message pour référence future
            messageElement.dataset.messageId = messageId;
            
            // Vérifier si le message est épinglé
            if (message.is_pinned) {
                messageElement.classList.add('pinned-message');
            }
            
            // Créer le contenu du message
            messageElement.innerHTML = `
                <div style="display: flex; justify-content: space-between;">
                    <strong>${message.sender?.username || message.sender?.name || 'Anonyme'}</strong>
                    <small style="color: gray;">${message.created_at_formatted || new Date().toLocaleTimeString()}</small>
                </div>
                <div style="background-color: #f1f0f0; padding: 8px; border-radius: 5px; margin-top: 5px;">
                    ${message.content}
                </div>
                <div class="message-actions mt-1 text-right">
                    <button class="btn btn-sm btn-outline-primary pin-message-btn" data-message-id="${messageId}">
                        📌 Épingler (ID: ${messageId})
                    </button>
                </div>
            `;
            
            // Ajouter le message au chat
            chatMessages.appendChild(messageElement);
            
            // Faire défiler vers le bas
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Ajouter l'écouteur d'événement pour le bouton d'épinglage
            const pinButton = messageElement.querySelector('.pin-message-btn');
            pinButton.addEventListener('click', function() {
                // Récupérer l'ID du message depuis l'attribut data-message-id du bouton
                const messageId = this.getAttribute('data-message-id');
                console.log('Bouton épingler cliqué pour le message ID:', messageId);
                if (messageId) {
                    togglePinMessage(messageId, true);
                } else {
                    console.error('Impossible d\'obtenir l\'ID du message pour l\'action d\'\u00e9pinglage');
                    addMessageToChat({
                        content: `Erreur: Impossible d'identifier le message à épingler`,
                        sender: {
                            name: 'Système',
                            username: 'Système'
                        },
                        created_at_formatted: new Date().toLocaleTimeString()
                    });
                }
            });
            
            // Si le message est épinglé, mettre à jour la section de message épinglé
            if (message.is_pinned) {
                updatePinnedStatus(message);
            }
        }
        
        // Fonction pour mettre à jour le statut d'épinglage d'un message
        function updatePinnedStatus(message) {
            console.log('Mise à jour du statut d\'\u00e9pinglage pour le message:', message);
            
            const pinnedContainer = document.getElementById('pinned-message-container');
            const pinnedContent = document.getElementById('pinned-message-content');
            const unpinButton = document.getElementById('unpin-button');
            
            // Récupérer l'ID du message de manière plus robuste
            const messageId = message.id || message.data?.id || (typeof message === 'object' && message.hasOwnProperty('message') ? message.message.id : null);
            const isPinned = message.is_pinned || (typeof message === 'object' && message.hasOwnProperty('message') ? message.message.is_pinned : false);
            
            console.log('ID du message pour mise à jour du statut:', messageId, 'Est épinglé:', isPinned);
            
            if (!messageId) {
                console.error('Impossible d\'identifier le message pour la mise à jour du statut d\'\u00e9pinglage');
                return;
            }
            
            // Mettre à jour tous les messages dans la liste
            const allMessages = document.querySelectorAll('.message');
            allMessages.forEach(msgElement => {
                if (msgElement.dataset.messageId === messageId) {
                    if (isPinned) {
                        msgElement.classList.add('pinned-message');
                        msgElement.style.borderLeft = '3px solid gold';
                        
                        // Mettre à jour le bouton d'épinglage pour montrer "Détacher"
                        const pinButton = msgElement.querySelector('.pin-message-btn');
                        if (pinButton) {
                            pinButton.textContent = `📌 Détacher (ID: ${messageId})`;
                            pinButton.onclick = function() {
                                togglePinMessage(messageId, false);
                            };
                        }
                    } else {
                        msgElement.classList.remove('pinned-message');
                        msgElement.style.borderLeft = 'none';
                        
                        // Mettre à jour le bouton d'épinglage pour montrer "Épingler"
                        const pinButton = msgElement.querySelector('.pin-message-btn');
                        if (pinButton) {
                            pinButton.textContent = `📌 Épingler (ID: ${messageId})`;
                            pinButton.onclick = function() {
                                togglePinMessage(messageId, true);
                            };
                        }
                    }
                } else {
                    // Si un seul message peut être épinglé à la fois, détacher les autres
                    if (isPinned) {
                        msgElement.classList.remove('pinned-message');
                        msgElement.style.borderLeft = 'none';
                        
                        // Réinitialiser tous les autres boutons d'épinglage
                        const pinButton = msgElement.querySelector('.pin-message-btn');
                        if (pinButton) {
                            const otherMsgId = msgElement.dataset.messageId;
                            pinButton.textContent = `📌 Épingler (ID: ${otherMsgId})`;
                            pinButton.onclick = function() {
                                togglePinMessage(otherMsgId, true);
                            };
                        }
                    }
                }
            });
            
            // Mettre à jour la section de message épinglé
            if (isPinned) {
                // Afficher le message épinglé
                pinnedContainer.style.display = 'block';
                pinnedContent.innerHTML = `
                    <div>
                        <strong>${message.sender?.username || message.sender?.name || 'Anonyme'}</strong>
                        <small style="color: gray;">${message.created_at_formatted || new Date().toLocaleTimeString()}</small>
                    </div>
                    <div style="margin-top: 5px;">
                        ${message.content}
                    </div>
                `;
                
                // Configurer le bouton de détachement
                unpinButton.dataset.messageId = messageId;
                unpinButton.onclick = function() {
                    console.log('Bouton de détachement cliqué pour le message ID:', messageId);
                    togglePinMessage(messageId, false);
                };
            } else {
                // Cacher la section de message épinglé si le message a été détaché
                if (pinnedContainer.style.display !== 'none' && 
                    unpinButton.dataset.messageId === messageId) {
                    pinnedContainer.style.display = 'none';
                    pinnedContent.innerHTML = '';
                }
            }
        }
        
        // Fonction de test pour épingler un message avec un ID spécifique
        function testPinMessage() {
            const messageId = document.getElementById('message-id-to-pin').value.trim();
            if (!messageId) {
                alert('Veuillez entrer un ID de message valide');
                return;
            }
            
            console.log('Test d\'\u00e9pinglage pour le message ID:', messageId);
            togglePinMessage(messageId, true);
        }
        
        // Fonction pour épingler ou détacher un message
        function togglePinMessage(messageId, pin) {
            const spaceId = document.getElementById('current-space-id').value.trim();
            if (!spaceId) {
                alert('Veuillez d\'abord vous connecter à un espace');
                return;
            }
            
            if (!messageId) {
                console.error('ID de message invalide pour l\'action d\'\u00e9pinglage/détachement');
                return;
            }
            
            console.log(`Tentative de ${pin ? '\'\u00e9pinglage' : 'détachement'} du message avec ID:`, messageId);
            
            // Mise à jour visuelle immédiate pour donner un feedback à l'utilisateur
            if (!pin) {
                // Si on détache, mettre à jour l'UI immédiatement pour le bouton
                const messageElement = document.querySelector(`.message[data-message-id="${messageId}"]`);
                if (messageElement) {
                    const pinButton = messageElement.querySelector('.pin-message-btn');
                    if (pinButton) {
                        pinButton.textContent = `📌 Épingler (ID: ${messageId})`;
                    }
                }
            }
            
            // Appeler l'API de test pour épingler/détacher le message (sans authentification)
            fetch(`/realtime-test/messages/${messageId}/toggle-pin`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ pin: pin })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(`Message ${pin ? 'épinglé' : 'détaché'} avec succès:`, data);
                // Ajouter un message de confirmation dans le chat
                addMessageToChat({
                    content: `${pin ? 'Message épinglé' : 'Message détaché'} avec succès!`,
                    sender: {
                        name: 'Système',
                        username: 'Système'
                    },
                    created_at_formatted: new Date().toLocaleTimeString()
                });
                
                // Si l'UI n'est pas mise à jour automatiquement via WebSocket après 1 seconde,
                // forcer une mise à jour manuelle
                setTimeout(() => {
                    const messageElement = document.querySelector(`.message[data-message-id="${messageId}"]`);
                    if (messageElement) {
                        if (!pin && messageElement.classList.contains('pinned-message')) {
                            // Forcer la mise à jour manuelle si le détachement n'a pas été reflété
                            updatePinnedStatus({
                                id: messageId,
                                is_pinned: false,
                                content: data.data?.content || messageElement.querySelector('.message-content')?.textContent,
                                sender: data.data?.sender || { name: 'Utilisateur', username: 'Utilisateur' },
                                created_at_formatted: data.data?.created_at_formatted || new Date().toLocaleTimeString()
                            });
                        }
                    }
                }, 1000);
            })
            .catch(error => {
                console.error(`Erreur lors de l'${pin ? 'épinglage' : 'détachement'} du message:`, error);
                addMessageToChat({
                    content: `Erreur: ${error.message}`,
                    sender: {
                        name: 'Système',
                        username: 'Système'
                    },
                    created_at_formatted: new Date().toLocaleTimeString()
                });
            });
        }
        
        // Initialiser le bouton de copie
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser clipboard.js
            new ClipboardJS('.copy-btn');
            
            // Ajouter un feedback visuel lors de la copie
            document.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const originalText = this.textContent;
                    this.textContent = 'Copié !';
                    this.classList.add('btn-success');
                    this.classList.remove('btn-primary');
                    
                    // Copier également dans le champ de l'espace pour le chat
                    const spaceId = this.getAttribute('data-clipboard-text');
                    if (spaceId) {
                        document.getElementById('current-space-id').value = spaceId;
                    }
                    
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.classList.add('btn-primary');
                        this.classList.remove('btn-success');
                    }, 2000);
                });
            });
            
            // Écouter la touche Entrée dans le champ de saisie du chat
            document.getElementById('chat-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendDirectMessage();
                }
            });
        });
        </script>
        
        <hr>
        <div class="mt-4">
            <h3>Comment tester ?</h3>
            <ol>
                <li>Assurez-vous que le serveur Reverb est en cours d'exécution (<code>php artisan reverb:start</code>)</li>
                <li>Utilisez les boutons ci-dessus pour simuler des événements de participation</li>
                <li>Ou déclenchez les événements manuellement via les URLs :
                    <ul>
                        <li><code>/realtime-test/space/{spaceId}/join</code> - Pour simuler un utilisateur qui rejoint</li>
                        <li><code>/realtime-test/space/{spaceId}/leave</code> - Pour simuler un utilisateur qui quitte</li>
                        <li><code>/realtime-test/space/{spaceId}/participant/{participantId}/raise-hand</code> - Pour lever/baisser la main</li>
                        <li><code>/realtime-test/space/{spaceId}/participant/{participantId}/change-role</code> - Pour changer le rôle (POST avec JSON)</li>
                    </ul>
                </li>
                <li>Observez les résultats dans le composant ci-dessus</li>
            </ol>
        </div>
    </div>
</body>
</html>
