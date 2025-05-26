<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test du Feed des Espaces - Legbairai</title>
    
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
        .space-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: white;
        }
        .space-item.live {
            border-left: 5px solid #38c172;
        }
        .space-item.scheduled {
            border-left: 5px solid #f6993f;
        }
        .space-item.followed {
            background-color: #f0f8ff;
        }
        .space-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .space-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        .space-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .status-live {
            background-color: #38c172;
        }
        .status-scheduled {
            background-color: #f6993f;
        }
        .space-host {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .space-host.followed {
            color: #3490dc;
            font-weight: bold;
        }
        .space-details {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            background-color: #6c757d;
            border-radius: 10px;
            margin-left: 5px;
        }
        .badge-primary {
            background-color: #3490dc;
        }
        .badge-success {
            background-color: #38c172;
        }
        .badge-warning {
            background-color: #f6993f;
        }
        .badge-info {
            background-color: #6cb2eb;
        }
        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 20px 0;
            justify-content: center;
        }
        .pagination li {
            margin: 0 5px;
        }
        .pagination li a, .pagination li span {
            display: block;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #3490dc;
            text-decoration: none;
        }
        .pagination li.active span {
            background-color: #3490dc;
            color: white;
            border-color: #3490dc;
        }
        .pagination li.disabled span {
            color: #6c757d;
            pointer-events: none;
            cursor: not-allowed;
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
        .tab-content {
            margin-top: 20px;
        }
        .nav-tabs {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            border-bottom: 1px solid #dee2e6;
        }
        .nav-tabs .nav-item {
            margin-bottom: -1px;
        }
        .nav-tabs .nav-link {
            display: block;
            padding: 8px 16px;
            border: 1px solid transparent;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
            color: #495057;
            text-decoration: none;
        }
        .nav-tabs .nav-link.active {
            color: #495057;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        .nav-tabs .nav-link:hover {
            border-color: #e9ecef #e9ecef #dee2e6;
        }
    </style>
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="container">
        <h1>Test du Feed des Espaces - Legbairai</h1>
        <p>Cette page permet de tester l'algorithme de tri des espaces pour le feed.</p>
        
        <div class="card mb-4">
            <div class="card-header">Options de test</div>
            <div class="card-body">
                <div class="form-group">
                    <label for="auth-status">Statut d'authentification :</label>
                    <select id="auth-status" class="form-control">
                        <option value="guest">Non authentifié</option>
                        <option value="auth-no-following" @if(Auth::check()) selected @endif>Authentifié (sans abonnements)</option>
                        <option value="auth-with-following" @if(Auth::check() && Auth::user()->followings()->count() > 0) selected @endif>Authentifié (avec abonnements)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="per-page">Nombre d'espaces par page :</label>
                    <select id="per-page" class="form-control">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                    </select>
                </div>
                
                <button id="fetch-spaces" class="btn btn-primary">Charger les espaces</button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Résultats du feed</div>
            <div class="card-body">
                <div id="spaces-container">
                    <div class="alert alert-info">
                        Cliquez sur "Charger les espaces" pour voir les résultats.
                    </div>
                </div>
                
                <div id="pagination-container" class="mt-4" style="display: none;">
                    <nav>
                        <ul class="pagination" id="pagination">
                            <!-- Pagination sera générée dynamiquement -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">Explication de l'algorithme</div>
            <div class="card-body">
                <h4>Ordre de priorité :</h4>
                <ol>
                    <li>Espaces <strong>LIVE</strong> en premier</li>
                    <li>Parmi les LIVE, ceux des créateurs suivis (si utilisateur connecté)</li>
                    <li>Ensuite, les autres LIVE par popularité (nombre de participants)</li>
                    <li>Ensuite, les espaces <strong>SCHEDULED</strong>, ceux des créateurs suivis en premier</li>
                    <li>Enfin, les autres SCHEDULED par date de programmation la plus proche</li>
                </ol>
                
                <h4>Légende :</h4>
                <ul>
                    <li><span class="badge status-live">LIVE</span> - Espace en direct</li>
                    <li><span class="badge status-scheduled">SCHEDULED</span> - Espace programmé</li>
                    <li><span class="badge badge-primary">Suivi</span> - Créateur que vous suivez</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fetchSpacesButton = document.getElementById('fetch-spaces');
            const spacesContainer = document.getElementById('spaces-container');
            const paginationContainer = document.getElementById('pagination-container');
            const pagination = document.getElementById('pagination');
            
            fetchSpacesButton.addEventListener('click', function() {
                fetchSpaces(1);
            });
            
            function fetchSpaces(page = 1) {
                const authStatus = document.getElementById('auth-status').value;
                const perPage = document.getElementById('per-page').value;
                
                // Afficher un indicateur de chargement
                spacesContainer.innerHTML = '<div class="alert alert-info">Chargement des espaces...</div>';
                paginationContainer.style.display = 'none';
                
                // Construire l'URL de l'API (utiliser la route de test sans authentification)
                let apiUrl = `/api/v1/test/spaces?page=${page}&per_page=${perPage}`;
                
                // Faire la requête à l'API
                fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    displaySpaces(data);
                })
                .catch(error => {
                    spacesContainer.innerHTML = `<div class="alert alert-danger">Erreur: ${error.message}</div>`;
                    console.error('Erreur lors du chargement des espaces:', error);
                });
            }
            
            function displaySpaces(data) {
                if (!data.data || data.data.length === 0) {
                    spacesContainer.innerHTML = '<div class="alert alert-warning">Aucun espace trouvé.</div>';
                    paginationContainer.style.display = 'none';
                    return;
                }
                
                // Afficher les espaces
                let html = '';
                
                data.data.forEach(space => {
                    const isLive = space.status === 'live';
                    const isFollowed = space.host && space.host.is_followed;
                    
                    html += `
                        <div class="space-item ${isLive ? 'live' : 'scheduled'} ${isFollowed ? 'followed' : ''}">
                            <div class="space-header">
                                <h3 class="space-title">${space.title}</h3>
                                <span class="space-status status-${isLive ? 'live' : 'scheduled'}">
                                    ${isLive ? 'LIVE' : 'SCHEDULED'}
                                </span>
                            </div>
                            <div class="space-host ${isFollowed ? 'followed' : ''}">
                                Hôte: ${space.host ? space.host.name : 'Inconnu'}
                                ${isFollowed ? '<span class="badge badge-primary">Suivi</span>' : ''}
                            </div>
                            <div class="space-details">
                                <span>
                                    <i class="fas fa-users"></i> ${space.active_participants_count || 0} participants
                                </span>
                                <span>
                                    ${isLive 
                                        ? `Démarré le ${formatDate(space.started_at)}`
                                        : `Programmé pour le ${formatDate(space.scheduled_at)}`
                                    }
                                </span>
                            </div>
                        </div>
                    `;
                });
                
                spacesContainer.innerHTML = html;
                
                // Afficher la pagination
                if (data.meta && data.meta.links) {
                    let paginationHtml = '';
                    
                    data.meta.links.forEach(link => {
                        if (link.url === null) {
                            paginationHtml += `<li class="page-item disabled"><span class="page-link">${link.label}</span></li>`;
                        } else {
                            const isActive = link.active ? 'active' : '';
                            paginationHtml += `<li class="page-item ${isActive}">
                                <a href="#" class="page-link" data-page="${link.url.split('page=')[1].split('&')[0]}">${link.label}</a>
                            </li>`;
                        }
                    });
                    
                    pagination.innerHTML = paginationHtml;
                    paginationContainer.style.display = 'block';
                    
                    // Ajouter des écouteurs d'événements pour les liens de pagination
                    document.querySelectorAll('#pagination .page-link').forEach(link => {
                        link.addEventListener('click', function(e) {
                            e.preventDefault();
                            const page = this.getAttribute('data-page');
                            fetchSpaces(page);
                        });
                    });
                }
            }
            
            function formatDate(dateString) {
                if (!dateString) return 'N/A';
                
                const date = new Date(dateString);
                return date.toLocaleString('fr-FR', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        });
    </script>
</body>
</html>
