<?php

namespace App\Console\Commands;

use App\Events\InsightStreamed;
use App\Models\User;
use Illuminate\Console\Command;

class SecurityAudit extends Command
{
    protected $signature = 'finerp:security:audit';

    protected $description = 'Run a basic runtime security checklist for production readiness';

    public function handle(): int
    {
        $checks = [
            $this->check('APP_DEBUG should be false in production', fn () => ! (app()->environment('production') && config('app.debug'))),
            $this->check('APP_URL should use https in production', fn () => ! app()->environment('production') || str_starts_with((string) config('app.url'), 'https://')),
            $this->check('BCRYPT rounds should be at least 12', fn () => (int) config('hashing.bcrypt.rounds', 0) >= 12),
            $this->check('Broadcast driver should not be log in production', fn () => ! app()->environment('production') || config('broadcasting.default') !== 'log'),
            $this->check('No active user should keep passkey_plain', fn () => User::query()->whereNotNull('passkey_plain')->count() === 0),
        ];

        $this->table(['Check', 'Result'], array_map(static fn (array $row): array => [
            $row['label'],
            $row['passed'] ? 'PASS' : 'WARN',
        ], $checks));

        $warnings = collect($checks)->where('passed', false)->count();

        event(new InsightStreamed('insight.security.audit', [
            'scope' => 'security',
            'environment' => app()->environment(),
            'checks_total' => count($checks),
            'warnings' => $warnings,
            'generated_at' => now()->toIso8601String(),
        ]));

        if ($warnings > 0) {
            $this->warn(sprintf('Security audit completed with %d warning(s).', $warnings));
        } else {
            $this->info('Security audit passed with no warnings.');
        }

        return self::SUCCESS;
    }

    private function check(string $label, callable $validator): array
    {
        return [
            'label' => $label,
            'passed' => (bool) $validator(),
        ];
    }
}
