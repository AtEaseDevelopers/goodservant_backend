<?php

namespace App\Providers;

use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/** Hooks the portal activity log into record changes and logins. */
class ActivityLogServiceProvider extends ServiceProvider
{
    public function boot()
    {
        foreach (['created', 'updated', 'deleted', 'restored'] as $event) {
            Event::listen('eloquent.' . $event . ': *', function ($name, $data) use ($event) {
                if (isset($data[0]) && $data[0] instanceof \Illuminate\Database\Eloquent\Model) {
                    ActivityLogger::model($event, $data[0]);
                }
            });
        }

        Event::listen(Login::class, function (Login $e) {
            if (!request()->is('api/*')) {
                ActivityLogger::auth('login', $e->user, $e->guard);
            }
        });
        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user && !request()->is('api/*')) {
                ActivityLogger::auth('logout', $e->user, $e->guard);
            }
        });
        Event::listen(Failed::class, function (Failed $e) {
            if (!request()->is('api/*')) {
                $who = $e->credentials['email'] ?? $e->credentials['username'] ?? $e->credentials['code'] ?? '';
                ActivityLogger::auth('login_failed', null, $e->guard, $who !== '' ? 'for ' . $who : null);
            }
        });
    }
}
