<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CleanExpiredRedisLocks implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $patterns = config('telemedicine.maintenance.lock_key_patterns', []);
        $maxAgeMinutes = (int) config('telemedicine.maintenance.lock_cleanup_max_age_minutes', 60);
        $deleted = 0;

        foreach ($patterns as $pattern) {
            foreach ($this->scanKeys((string) $pattern) as $key) {
                if ($this->shouldDelete($key, $maxAgeMinutes)) {
                    Redis::del($key);
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            Log::info('EXPIRED_REDIS_LOCKS_CLEANED', [
                'count' => $deleted,
                'max_age_minutes' => $maxAgeMinutes,
            ]);
        }
    }

    /**
     * @return iterable<int, string>
     */
    private function scanKeys(string $pattern): iterable
    {
        $cursor = '0';

        do {
            $result = Redis::command('scan', [$cursor, 'MATCH', $pattern, 'COUNT', 100]);
            $cursor = (string) ($result[0] ?? '0');

            foreach (($result[1] ?? []) as $key) {
                yield (string) $key;
            }
        } while ($cursor !== '0');
    }

    private function shouldDelete(string $key, int $maxAgeMinutes): bool
    {
        $ttl = Redis::ttl($key);

        if ($ttl !== -1) {
            return false;
        }

        $payload = json_decode((string) Redis::get($key), true);

        if (! is_array($payload)) {
            return false;
        }

        $expiresAt = $payload['expires_at'] ?? null;
        if (is_string($expiresAt) && Carbon::parse($expiresAt)->isPast()) {
            return true;
        }

        $createdAt = $payload['created_at'] ?? null;
        if (! is_string($createdAt)) {
            return false;
        }

        return Carbon::parse($createdAt)->lte(now()->subMinutes($maxAgeMinutes));
    }
}
