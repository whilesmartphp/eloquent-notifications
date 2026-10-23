<?php

use Illuminate\Support\Facades\Route;

$controller = config('notifications.controller');
$actions = config('notifications.route_actions', []);
$writeMiddleware = config('notifications.route_write_middleware', []);

$routes = [
    'index' => ['get', 'notifications', 'index', false],
    'unread_count' => ['get', 'notifications/unread-count', 'unreadCount', false],
    'preferences' => ['get', 'notifications/preferences', 'preferences', false],
    'update_preferences' => ['put', 'notifications/preferences', 'updatePreferences', true],
    'read_all' => ['post', 'notifications/read-all', 'readAll', true],
    'show' => ['get', 'notifications/{notification}', 'show', false],
    'read' => ['post', 'notifications/{notification}/read', 'read', true],
    'unread' => ['post', 'notifications/{notification}/unread', 'unread', true],
    'destroy' => ['delete', 'notifications/{notification}', 'destroy', true],
];

foreach ($routes as $action => [$method, $uri, $handler, $writes]) {
    if (! in_array($action, $actions, true)) {
        continue;
    }
    $route = Route::{$method}($uri, [$controller, $handler])->name('notifications.'.str_replace('_', '-', $action));
    if ($writes && $writeMiddleware !== []) {
        $route->middleware($writeMiddleware);
    }
}
