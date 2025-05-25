<?php

use Illuminate\Support\Facades\Broadcast;
use Gbairai\Core\Models\Space;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('space.{spaceId}', function ($user, $spaceId) {
    return Space::where('id', $spaceId)->exists();
});
