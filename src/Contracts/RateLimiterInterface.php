<?php
namespace Horus\Sentinel\Contracts;

/**
 * Define o contrato para uma classe que gere limites de tentativas.
 */
interface RateLimiterInterface
{
    public function attempt(string $key): void;
    public function isBlocked(string $key, int $maxAttempts, int $decaySeconds): bool;
    public function clear(string $key): void;
}
