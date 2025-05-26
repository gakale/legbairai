<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test des Notifications en Temps Réel - Legbairai</title>
    
    <!-- Styles -->
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f7f9fc;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2, h3, h4 {
            color: #333;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #f8fafc;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            font-weight: bold;
        }
        .card-body {
            padding: 15px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin-right: 8px;
            margin-bottom: 8px;
            background-color: #3490dc;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #3490dc;
        }
        .btn-success {
            background-color: #38c172;
        }
        .btn-warning {
            background-color: #f6993f;
        }
        .btn-danger {
            background-color: #e3342f;
        }
        .btn-info {
            background-color: #6cb2eb;
        }
        .list-group {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .list-group-item {
            border: 1px solid #e2e8f0;
            padding: 15px;
            margin-bottom: -1px;
        }
        .list-group-item:first-child {
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
        }
        .list-group-item:last-child {
            border-bottom-left-radius: 4px;
            border-bottom-right-radius: 4px;
            margin-bottom: 0;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-control {
            display: block;
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .input-group {
            display: flex;
            margin-bottom: 15px;
        }
        .input-group .form-control {
            flex: 1;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .input-group-append {
            display: flex;
        }
        .input-group-append .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            margin-right: 0;
        }
        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .notification-item.unread {
            background-color: #f0f8ff;
        }
        .notification-badge {
            display: inline-block;
            min-width: 20px;
            padding: 3px 7px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            background-color: #e3342f;
            border-radius: 10px;
        }
        .copy-btn {
            margin-left: 5px;
            padding: 2px 5px;
            font-size: 12px;
        }
    </style>
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>Test des Notifications en Temps Réel - Legbairai</h1>
        <p>Cette page permet de tester la réception de notifications en temps réel.</p>
        
        <!-- Liste des utilisateurs disponibles pour les tests -->
        <div class="mb-4">
            <h3>Utilisateurs disponibles pour les tests :</h3>
            @if(isset($users) && $users->count() > 0)
                <div class="list-group">
                    @foreach($users as $user)
                        <div class="list-group-item">
                            <h5>{{ $user->name }}</h5>
                            <p><strong>ID de l'utilisateur :</strong> <code>{{ $user->id }}</code></p>
                            <p><strong>Email :</strong> {{ $user->email }}</p>
                            <button class="btn btn-sm btn-primary copy-btn" data-clipboard-text="{{ $user->id }}">Copier l'ID</button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning">Aucun utilisateur disponible.</div>
            @endif
        </div>
        
        <!-- Boutons de test directs -->
        <div class="direct-test-buttons mt-4 mb-4">
            <h4>Actions de test directes :</h4>
            <a href="#" onclick="testSendFollowerNotification(); return false;" class="btn btn-primary">Envoyer une notification d'abonnement</a>
        </div>
        
        <!-- Section de test pour les notifications -->
        <div class="card mt-4">
            <div class="card-header">Écoute des notifications en temps réel</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="current-user-id" class="form-label">ID de l'utilisateur pour écouter les notifications :</label>
                    <div class="input-group">
                        <input type="text" id="current-user-id" class="form-control" placeholder="Collez l'ID de l'utilisateur ici">
                        <button onclick="subscribeToUserNotifications()" class="btn btn-secondary">Se connecter</button>
                    </div>
                    <div id="connection-status" class="mt-2">Non connecté</div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        Notifications reçues
                        <span id="notification-badge" class="notification-badge" style="display: none;">0</span>
                    </div>
                    <div class="card-body">
                        <div id="notifications-list" style="max-height: 300px; overflow-y: auto;">
                            <p class="text-muted">Aucune notification pour le moment.</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button onclick="markAllAsRead()" class="btn btn-info">Marquer toutes comme lues</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Initialiser Clipboard.js pour les boutons de copie
        new ClipboardJS('.copy-btn');
        
        // Pour les tests, nous allons créer une nouvelle instance d'Echo spécifique à cette page
        // plutôt que d'utiliser l'instance globale qui pourrait avoir d'autres configurations
        
        // D'abord, définir une fonction pour créer un faux canal pour les tests
        function createTestNotificationChannel(userId) {
            // Cette fonction crée un objet qui simule un canal Echo
            // mais qui ne fait pas réellement de connexion WebSocket
            return {
                notification: function(callback) {
                    // Stocker le callback pour l'utiliser plus tard
                    window.testNotificationCallback = callback;
                    console.log(`Canal de test configuré pour l'utilisateur ${userId}`);
                    
                    // Simuler un succès de connexion
                    setTimeout(() => {
                        document.getElementById('connection-status').textContent = `Simulé: Connecté au canal de l'utilisateur ${userId}`;
                        document.getElementById('connection-status').style.color = 'green';
                    }, 500);
                    
                    return this;
                }
            };
        }
        
        // Variables globales
        let currentUserId = null;
        let userChannel = null;
        let unreadCount = 0;
        let notifications = [];
        
        // Fonction pour s'abonner aux notifications d'un utilisateur
        function subscribeToUserNotifications() {
            const userId = document.getElementById('current-user-id').value.trim();
            if (!userId) {
                alert('Veuillez entrer un ID d\'utilisateur valide');
                return;
            }
            
            // Si déjà abonné à un canal, le quitter
            if (userChannel) {
                document.getElementById('connection-status').textContent = 'Déconnecté';
                document.getElementById('connection-status').style.color = 'red';
                userChannel = null;
            }
            
            currentUserId = userId;
            
            // Utiliser notre canal de test simulé au lieu d'Echo
            userChannel = createTestNotificationChannel(userId);
            
            // Configurer le canal pour recevoir des notifications
            userChannel.notification((notification) => {
                console.log('Nouvelle notification reçue:', notification);
                
                // Ajouter la notification à notre liste
                notifications.unshift(notification);
                unreadCount++;
                updateNotificationsList();
                updateUnreadBadge();
                
                // Afficher une alerte
                alert(`Nouvelle notification: ${notification.data.message}`);
            });
            
            // Charger les notifications initiales (simulation)
            console.log("Chargement des notifications initiales (simulation)");
        }
        
        // Fonction pour mettre à jour la liste des notifications
        function updateNotificationsList() {
            const container = document.getElementById('notifications-list');
            
            if (notifications.length === 0) {
                container.innerHTML = '<p class="text-muted">Aucune notification pour le moment.</p>';
                return;
            }
            
            container.innerHTML = '';
            
            notifications.forEach((notification, index) => {
                const isUnread = !notification.read_at;
                const div = document.createElement('div');
                div.className = `notification-item ${isUnread ? 'unread' : ''}`;
                
                div.innerHTML = `
                    <div class="d-flex justify-content-between">
                        <strong>${notification.data.message || 'Notification'}</strong>
                        <small>${formatDate(notification.created_at)}</small>
                    </div>
                    <p>${notification.data.description || ''}</p>
                    <button onclick="markAsRead('${notification.id}')" class="btn btn-sm btn-info" ${isUnread ? '' : 'disabled'}>
                        ${isUnread ? 'Marquer comme lue' : 'Lue'}
                    </button>
                `;
                
                container.appendChild(div);
            });
        }
        
        // Fonction pour mettre à jour le badge des notifications non lues
        function updateUnreadBadge() {
            const badge = document.getElementById('notification-badge');
            
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Fonction pour marquer une notification comme lue
        function markAsRead(notificationId) {
            // Simulation d'appel API
            console.log(`Marquer la notification ${notificationId} comme lue`);
            
            // Mise à jour locale
            const index = notifications.findIndex(n => n.id === notificationId);
            if (index !== -1) {
                notifications[index].read_at = new Date().toISOString();
                unreadCount = Math.max(0, unreadCount - 1);
                updateNotificationsList();
                updateUnreadBadge();
            }
            
            // Dans un cas réel, on ferait un appel API ici
            // fetch(`/api/v1/notifications/${notificationId}/read`, {
            //     method: 'PATCH',
            //     headers: {
            //         'Content-Type': 'application/json',
            //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            //     }
            // })
            // .then(response => response.json())
            // .then(data => {
            //     console.log('Notification marquée comme lue:', data);
            // })
            // .catch(error => {
            //     console.error('Erreur:', error);
            // });
        }
        
        // Fonction pour marquer toutes les notifications comme lues
        function markAllAsRead() {
            // Simulation d'appel API
            console.log('Marquer toutes les notifications comme lues');
            
            // Mise à jour locale
            notifications.forEach(notification => {
                notification.read_at = new Date().toISOString();
            });
            unreadCount = 0;
            updateNotificationsList();
            updateUnreadBadge();
            
            // Dans un cas réel, on ferait un appel API ici
            // fetch('/api/v1/notifications/mark-all-as-read', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            //     }
            // })
            // .then(response => response.json())
            // .then(data => {
            //     console.log('Toutes les notifications marquées comme lues:', data);
            // })
            // .catch(error => {
            //     console.error('Erreur:', error);
            // });
        }
        
        // Fonction pour tester l'envoi d'une notification d'abonnement
        function testSendFollowerNotification() {
            const followerId = prompt('Entrez l\'ID de l\'utilisateur qui s\'abonne:');
            if (!followerId) return;
            
            const targetId = prompt('Entrez l\'ID de l\'utilisateur cible (qui reçoit la notification):');
            if (!targetId) return;
            
            // Vérifier si l'utilisateur cible est celui auquel nous sommes abonnés
            if (targetId !== currentUserId) {
                alert(`Attention: Vous êtes abonné aux notifications de l'utilisateur ${currentUserId}, mais vous envoyez une notification à l'utilisateur ${targetId}. Vous ne verrez pas cette notification.`);
            }
            
            // Pour les tests, simuler directement l'envoi d'une notification sans passer par le backend
            if (window.testNotificationCallback) {
                // Créer une notification simulée
                const now = new Date();
                const simulatedNotification = {
                    id: 'simulated-' + Date.now(),
                    type: 'App\\Notifications\\NewFollowerNotification',
                    data: {
                        message: `L'utilisateur ${followerId} vous suit maintenant!`,
                        description: 'Cliquez pour voir son profil',
                        follower: {
                            id: followerId,
                            name: 'Utilisateur ' + followerId
                        }
                    },
                    read_at: null,
                    created_at: now.toISOString()
                };
                
                // Appeler le callback avec la notification simulée
                setTimeout(() => {
                    window.testNotificationCallback(simulatedNotification);
                    console.log('Notification simulée envoyée:', simulatedNotification);
                }, 1000);
                
                alert('Notification simulée en cours d\'envoi...');
            } else {
                alert('Vous devez d\'abord vous connecter à un canal de notifications');
            }
        }
        
        // Fonction utilitaire pour formater les dates
        function formatDate(dateString) {
            if (!dateString) return '';
            
            const date = new Date(dateString);
            return date.toLocaleString();
        }
    </script>
</body>
</html>
