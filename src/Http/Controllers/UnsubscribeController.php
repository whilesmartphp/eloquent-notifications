<?php

namespace Whilesmart\Notifications\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use Whilesmart\Notifications\Contracts\ResponseFormatter;
use Whilesmart\Notifications\Support\Preferences;

class UnsubscribeController extends Controller
{
    public function __invoke(Request $request, Preferences $preferences, string $type, string $channel): Response
    {
        abort_unless($preferences->typeChannelKey($type, $channel) !== null, 404);
        $class = Relation::getMorphedModel((string) $request->query('notifiable_type')) ?? (string) $request->query('notifiable_type');
        abort_unless(is_a($class, Model::class, true), 404);
        $notifiable = $class::query()->findOrFail($request->query('notifiable_id'));

        $preferences->update($notifiable, ['type_channels' => [$type => [$channel => false]]]);

        $redirect = config('notifications.unsubscribe.redirect');
        if ($redirect) {
            return redirect()->away($redirect.(str_contains($redirect, '?') ? '&' : '?').http_build_query(['type' => $type, 'channel' => $channel]));
        }

        return app(ResponseFormatter::class)->format([
            'success' => true,
            'message' => 'You will no longer receive these notifications on this channel.',
            'data' => ['type' => $type, 'channel' => $channel],
        ]);
    }
}
