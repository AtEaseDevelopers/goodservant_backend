<?php

namespace App\Http\Controllers;

class DeployWebhookController extends Controller
{
    public function pull(string $secret)
    {
        $expected = config('deploy.secret');

        if (empty($expected) || !hash_equals($expected, $secret)) {
            abort(404);
        }

        $basePath = base_path();
        $php = '/usr/bin/php8.3';
        $composer = '/usr/local/bin/composer';

        $commands = [
            'git config --global --add safe.directory ' . escapeshellarg($basePath),
            'git -c safe.directory=' . escapeshellarg($basePath) . ' pull origin main',
            "{$php} {$composer} install --no-dev --optimize-autoloader --no-interaction",
            "{$php} artisan migrate --force",
            "{$php} artisan config:clear",
            "{$php} artisan cache:clear",
            "{$php} artisan route:clear",
            "{$php} artisan view:clear",
            "{$php} artisan config:cache",
            "{$php} artisan route:cache",
        ];

        $output = '';
        foreach ($commands as $command) {
            $output .= "\$ {$command}\n";
            $output .= shell_exec('cd ' . escapeshellarg($basePath) . " && {$command} 2>&1");
            $output .= "\n\n";
        }

        return response("<pre>" . htmlspecialchars($output) . "</pre>");
    }
}
