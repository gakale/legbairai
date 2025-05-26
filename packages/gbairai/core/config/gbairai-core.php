<?php

declare(strict_types=1);

// config for Gbairai/Core
return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This is the Eloquent model class that should be used to retrieve your
    | users. Typically, this will be "App\\Models\\User" but you may
    | certainly customize this to your needs.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | If you want to customize the names of the tables used by this package,
    | you can specify them here.
    |
    */
    'table_names' => [
        'users' => 'users',
        'spaces' => 'spaces',
        'space_participants' => 'space_participants',
        'space_messages' => 'space_messages', // Nouveau
        'space_recordings' => 'space_recordings', // Nouveau
        'space_messages' => 'space_messages',
        'space_recordings' => 'space_recordings',
        'donations' => 'donations',
        'tickets' => 'tickets',
        'follows' => 'follows',
        'subscriptions' => 'subscriptions',
        'subscription_plans' => 'subscription_plans',
        'notifications' => 'notifications',
        'audio_clips' => 'audio_clips',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | If you want to use your own models, you can specify them here.
    | They should extend the package's base models.
    |
    */
    'models' => [
        'user' => \App\Models\User::class, 
        'space' => \Gbairai\Core\Models\Space::class,
        'space_participant' => \Gbairai\Core\Models\SpaceParticipant::class,
        'space_message' => \Gbairai\Core\Models\SpaceMessage::class, 
        'space_recording' => \Gbairai\Core\Models\SpaceRecording::class, 
        'follow' => \Gbairai\Core\Models\Follow::class,
        'audio_clip' => \Gbairai\Core\Models\AudioClip::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Space Settings
    |--------------------------------------------------------------------------
    */
    'spaces' => [
        'default_max_participants' => 100,
        'auto_close_on_host_leave' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | WebRTC Settings (Placeholders)
    |--------------------------------------------------------------------------
    */
    'webrtc' => [
        'stun_servers' => [
            ['urls' => 'stun:stun.l.google.com:19302'],
        ],
        'turn_servers' => [
            // [
            //     'urls' => 'turn:your-turn-server.com:3478',
            //     'username' => 'user',
            //     'credential' => 'pass',
            // ],
        ],
    ],
];
