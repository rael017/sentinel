<?php
namespace Horus\Sentinel;

use Horus\Sentinel\Contracts\UserIdentityInterface;
use Horus\Sentinel\Contracts\RateLimiterInterface;

class Sentinel
{
    public function __construct(
        private $userModelClass,
        private RateLimiterInterface $limiter,
        private string $appKey
    ) {
        SessionManager::start();
    }

    public function login(string $email, string $password): bool
    {
        $key = 'login_attempt:' . $email;
        if ($this->limiter->isBlocked($key, 5, 300)) {
            // Lançar uma exceção seria ainda melhor
            return false;
        }

        $user = $this->userModelClass::findByEmail($email);

        if ($user && password_verify($password, $user->getPasswordHash())) {
            SessionManager::create($user->getId());
            $this->limiter->clear($key);
            return true;
        }

        $this->limiter->attempt($key);
        return false;
    }

    public function logout(): void
    {
        SessionManager::destroy();
    }

    public function user(): ?UserIdentityInterface
    {
        $userId = SessionManager::get('user_id');
        // Também usa o método estático para encontrar o utilizador.
        return $userId ? $this->userModelClass::findById($userId) : null;
    }

    public function authorize(string $role): bool
    {
        $user = $this->user();
        return $user && in_array($role, $user->getRoles());
    }

     // --- PROTEÇÃO CONTRA XSS ---
    /**
     * Higieniza uma string para exibição segura em HTML.
     */
    public static function sanitize(?string $data): string
    {
        return htmlspecialchars($data ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // --- PROTEÇÃO CONTRA CSRF ---
    /**
     * Gera um token CSRF, armazena-o na sessão e retorna-o.
     */
    public function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        SessionManager::set('_csrf_token', $token);
        return $token;
    }
    
    /**
     * Valida um token CSRF enviado contra o que está na sessão.
     */
    public function validateCsrfToken(?string $submittedToken): bool
    {
        $token = SessionManager::get('_csrf_token');
        return $token && $submittedToken && hash_equals($token, $submittedToken);
    }
    
    /**
     * Retorna um campo de input HTML com o token CSRF.
     */
    public function csrfInput(): string
    {
        return '<input type="hidden" name="_csrf" value="' . $this->generateCsrfToken() . '">';
    }

    // --- PROTEÇÃO DE ROTAS (URLS ASSINADAS) ---
    /**
     * Gera uma URL segura e com tempo de expiração.
     */
    public function signUrl(string $url, int $expirationInSeconds = 3600): string
    {
        $expires = time() + $expirationInSeconds;
        $urlToSign = rtrim($url, '?&') . (str_contains($url, '?') ? '&' : '?') . "expires={$expires}";
        $signature = hash_hmac('sha256', $urlToSign, $this->appKey);
        return "{$urlToSign}&signature={$signature}";
    }
    
    /**
     * Valida se uma URL assinada é genuína e não expirou.
     */
    public function validateSignedUrl(string $fullUrl): bool
    {
        parse_str(parse_url($fullUrl, PHP_URL_QUERY), $queryParams);
        $expires = $queryParams['expires'] ?? 0;
        $signature = $queryParams['signature'] ?? '';

        if (empty($signature) || time() > $expires) {
            return false;
        }

        $urlToValidate = str_replace("&signature={$signature}", '', $fullUrl);
        $expectedSignature = hash_hmac('sha256', $urlToValidate, $this->appKey);

        return hash_equals($expectedSignature, $signature);
    }
}