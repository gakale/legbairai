<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test de Diffusion en Temps Réel - Legbairai</title>
    
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
    </style>
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="container">
        <h1>Test de Diffusion en Temps Réel - Legbairai</h1>
        <p>Cette page permet de tester la réception d'événements en temps réel pour les spaces.</p>
        
        <!-- Conteneur pour notre application React -->
        <div id="realtime-space-test"></div>
        
        <hr>
        <div class="mt-4">
            <h3>Comment tester ?</h3>
            <ol>
                <li>Assurez-vous que le serveur Reverb est en cours d'exécution (<code>php artisan reverb:start</code>)</li>
                <li>Déclenchez l'événement en démarrant un space ou via Tinker :
                    <pre>php artisan tinker --execute="
$space = \Gbairai\Core\Models\Space::find('7b398b36-9195-4cb6-8a8d-478cb1ccc9fe');
\App\Events\SpaceStartedEvent::dispatch($space);"</pre>
                </li>
                <li>Observez les résultats dans le composant ci-dessus</li>
            </ol>
        </div>
    </div>
</body>
</html>
