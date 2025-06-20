# Horus Sentinel

**Horus Sentinel** é um pacote de segurança completo e desacoplado, desenhado para fornecer funcionalidades robustas de autenticação, autorização e proteção para aplicações PHP modernas.

Ele oferece uma forma elegante de gerir:

- ✅ Autenticação de utilizadores  
- ✅ Gestão de sessões seguras com backend em Redis  
- ✅ Proteção contra CSRF e XSS  
- ✅ Rate Limiting para prevenir ataques de força bruta  
- ✅ Geração e validação de URLs assinadas para rotas seguras  

---

## 📦 Instalação

Use o [Composer](https://getcomposer.org):

```bash
composer require horus/sentinel


⚙️ Guia de Uso
O Sentinel foi desenhado para ser flexível. Ele depende de interfaces, permitindo que você o integre a qualquer framework ou aplicação, fornecendo suas próprias implementações.

1. Implementar as Interfaces
A. UserIdentityInterface
Sua classe de modelo User precisa implementar esta interface:


<?php
namespace App\Models;

use Horus\Sentinel\Contracts\UserIdentityInterface;

class User implements UserIdentityInterface
{
    public function getId(): int {
        return $this->id;
    }

    public function getPasswordHash(): string {
        return $this->password;
    }

    public function getRoles(): array {
        return [$this->role ?? 'user'];
    }
}
B. UserRepositoryInterface
Você precisa criar um repositório que saiba como buscar os utilizadores na base de dados:


<?php
namespace App\Repositories;

use Horus\Sentinel\Contracts\UserRepositoryInterface;
use Horus\Sentinel\Contracts\UserIdentityInterface;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?UserIdentityInterface {
        return User::find($id);
    }

    public function findByEmail(string $email): ?UserIdentityInterface {
        // Ex: return User::where('email', $email)->first();
    }
}
C. RateLimiterInterface (Opcional)
Implemente a interface de rate limiting se desejar controlar o número de tentativas de login.

2. Inicializar o Sentinel
No bootstrap da sua aplicação (bootstrap/app.php ou equivalente):


use Horus\Sentinel\Sentinel;
use App\Repositories\UserRepository;
use App\Repositories\RedisRateLimiter;

$appKey = getenv('APP_KEY'); // Chave secreta do seu .env

$sentinel = new Sentinel(
    new UserRepository(),
    new RedisRateLimiter(),
    $appKey
);
Se estiver usando um container de injeção de dependência (DI), registre como singleton:


$container->singleton(Sentinel::class, fn() => $sentinel);
3. Utilização
🔐 Autenticação
php
Copiar
Editar
$email = $_POST['email'];
$password = $_POST['password'];

if ($sentinel->login($email, $password)) {
    header('Location: /dashboard');
    exit;
}
🔒 Proteção de Rotas (Middleware)

class AuthMiddleware {
    public function handle($request, $next) {
        global $sentinel;

        if (!$sentinel->user()) {
            header('Location: /login');
            exit;
        }

        return $next($request);
    }
}
🛡️ CSRF Protection
No formulário:



<form method="POST">
    <?php echo $sentinel->csrfInput(); ?>
    <button>Enviar</button>
</form>
No controlador:


if (!$sentinel->validateCsrfToken($_POST['_csrf'] ?? null)) {
    // Erro 403
}
🔗 URLs Assinadas
Gerar o link:


$urlSegura = $sentinel->signUrl('http://meusite.com/cancelar-conta', 3600); // válido por 1 hora
Validar na rota:


if (!$sentinel->validateSignedUrl($urlCompleta)) {
    // Erro 401 - link inválido ou expirado
}
✅ Contribuição
Pull Requests são bem-vindos. Para contribuições maiores, abra uma Issue para discutir mudanças antes.

📄 Licença
MIT © 2025 [Seu Nome ou Organização]










