<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Le Gbairai') }}</title> {{-- Titre personnalisé --}}

        {{-- Styles et Scripts Vite --}}
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body class="font-sans antialiased bg-gb-dark"> {{-- Appliquer la couleur de fond ici si vous voulez que ce soit géré par Blade initialement --}}
        <div id="app">
            {{-- Votre application React sera montée ici --}}
            {{-- Vous pouvez mettre un indicateur de chargement ici si React prend du temps à monter --}}
            {{-- <p>Chargement de Le Gbairai...</p> --}}
        </div>
    </body>
</html>