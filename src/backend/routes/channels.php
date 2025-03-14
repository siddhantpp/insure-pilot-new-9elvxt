<?php

use Illuminate\Support\Facades\Broadcast; // ^10.0
use App\Models\Document;
use App\Policies\DocumentPolicy;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Channel for real-time updates about a specific document
Broadcast::presence('document.{id}', function ($user, $id) {
    $document = Document::find($id);
    return $document && app(DocumentPolicy::class)->view($user, $document);
});

// Channel for user-specific notifications
Broadcast::private('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Channel for document processing notifications
Broadcast::private('document-processing', function ($user) {
    return $user->hasPermission('document.process');
});

// Channel for general document update notifications
Broadcast::private('document-updates', function ($user) {
    return $user->hasPermission('document.view');
});