<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Writes the admin / customer portal activity log (table activity_logs).
 *
 * - every login, logout and failed login
 * - every request that saves or deletes something (POST / PUT / PATCH / DELETE)
 * - every record created, changed, deleted or restored during such a request,
 *   with the old and new values
 *
 * The driver app is not logged here (api_logs already has it). Logging never
 * breaks the page: any error is written to the Laravel log and ignored.
 */
class ActivityLogger
{
    /** Values never stored. */
    const HIDDEN = ['password', 'password_confirmation', 'current_password', 'new_password', 'remember_token', 'session', 'portal_token', '_token', 'api_token', 'token'];

    /** Models whose changes are not logged (logs themselves, GPS, sessions). */
    const SKIP_MODELS = [
        \App\Models\ActivityLog::class,
        \App\Models\ApiLog::class,
        \App\Models\MobileErrorLog::class,
        \App\Models\DriverLocation::class,
    ];

    const MAX_VALUE = 2000;

    protected static $requestId;

    public static function requestId(): string
    {
        return static::$requestId ??= (string) Str::uuid();
    }

    /** The person using the portal, or null (driver app, console, guest). */
    public static function actor(): ?array
    {
        // No portal user in the driver API or in artisan commands, so nothing is logged there.
        if (request()->is('api/*')) {
            return null;
        }
        if (Auth::guard('web')->check()) {
            $u = Auth::guard('web')->user();
            return ['user', $u->id, $u->name ?? $u->email];
        }
        if (array_key_exists('customer', config('auth.guards', [])) && Auth::guard('customer')->check()) {
            $c = Auth::guard('customer')->user();
            return ['customer', $c->id, $c->company ?? $c->name ?? ('Customer #' . $c->id)];
        }

        return null;
    }

    public static function write(array $row, ?array $actor = null): void
    {
        try {
            $actor = $actor ?? static::actor();
            $req = request();
            ActivityLog::create($row + [
                'request_id' => static::requestId(),
                'user_type' => $actor[0] ?? null,
                'user_id' => $actor[1] ?? null,
                'user_name' => isset($actor[2]) ? Str::limit((string) $actor[2], 250, '') : null,
                'method' => $req->method(),
                'url' => Str::limit($req->fullUrl(), 2000, ''),
                'route' => optional($req->route())->getName(),
                'ip_address' => $req->ip(),
                'user_agent' => Str::limit((string) $req->userAgent(), 250, ''),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Activity log not written: ' . $e->getMessage());
        }
    }

    /** A record was created / updated / deleted / restored. */
    public static function model(string $event, Model $model): void
    {
        foreach (static::SKIP_MODELS as $skip) {
            if ($model instanceof $skip) {
                return;
            }
        }
        $actor = static::actor();
        if (!$actor) {
            return;
        }

        $old = null;
        $new = null;
        if ($event === 'updated') {
            $changed = array_diff(array_keys($model->getChanges()), ['updated_at']);
            if (!$changed) {
                return;
            }
            foreach ($changed as $key) {
                $old[$key] = $model->getRawOriginal($key);
                $new[$key] = $model->getAttributes()[$key] ?? null;
            }
        } elseif ($event === 'created') {
            $new = $model->getAttributes();
        } else { // deleted, restored
            $old = $model->getRawOriginal();
        }

        static::write([
            'event' => $event,
            'subject_type' => class_basename($model),
            'subject_id' => is_numeric($model->getKey()) ? $model->getKey() : null,
            'description' => Str::limit(class_basename($model) . ' ' . static::label($model) . ' ' . $event, 490, ''),
            'old_values' => static::clean($old),
            'new_values' => static::clean($new),
        ], $actor);
    }

    /** A save / delete request finished. */
    public static function request($request, $response): void
    {
        $actor = static::actor();
        if (!$actor) {
            return;
        }
        $input = $request->except(static::HIDDEN);
        foreach ($request->allFiles() as $key => $file) {
            $input[$key] = is_array($file) ? '[files]' : ('[file] ' . $file->getClientOriginalName());
        }
        $route = optional($request->route())->getName() ?? $request->path();
        static::write([
            'event' => 'request',
            'description' => Str::limit($request->method() . ' ' . $route, 490, ''),
            'new_values' => static::clean($input),
            'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
        ], $actor);
    }

    public static function auth(string $event, $user, ?string $guard = null, ?string $note = null): void
    {
        $type = $guard === 'customer' ? 'customer' : 'user';
        static::write([
            'event' => $event,
            'description' => Str::limit($event . ($note ? ' ' . $note : ''), 490, ''),
        ], $user ? [$type, $user->id ?? null, $user->name ?? $user->company ?? $user->email ?? null] : [null, null, null]);
    }

    protected static function label(Model $model): string
    {
        foreach (['invoiceno', 'dono', 'sono', 'code', 'lorryno', 'employeeid', 'company', 'name', 'email'] as $field) {
            $v = $model->getAttributes()[$field] ?? null;
            if ($v !== null && $v !== '') {
                return '#' . $model->getKey() . ' ' . $v;
            }
        }

        return '#' . $model->getKey();
    }

    protected static function clean($values)
    {
        if (!is_array($values)) {
            return $values;
        }
        $out = [];
        foreach ($values as $k => $v) {
            if (in_array(strtolower((string) $k), static::HIDDEN, true)) {
                $out[$k] = '[hidden]';
            } elseif (is_array($v)) {
                $out[$k] = static::clean($v);
            } elseif (is_string($v) && mb_strlen($v) > static::MAX_VALUE) {
                $out[$k] = mb_substr($v, 0, static::MAX_VALUE) . '...';
            } else {
                $out[$k] = $v;
            }
        }

        return $out;
    }
}
