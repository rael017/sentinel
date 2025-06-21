<?php
namespace Horus\Sentinel\Traits;

use Horus\Sentinel\Contracts\UserIdentityInterface;

/**
 * Este Trait dá a qualquer modelo a capacidade de ser usado pelo Sentinel.
 * Ele implementa a UserIdentityInterface e assume que a classe que o usa
 * terá os seus próprios métodos estáticos para encontrar utilizadores
 * (ex: findById, findByEmail), que geralmente são herdados de uma classe BaseModel.
 */
trait SentinelUser
{
    /**
     * Implementa os métodos do contrato UserIdentityInterface,
     * lendo as propriedades do objeto que usa este Trait.
     */
    public function getId(): int
    {
        // Usa o "magic method" __get da sua BaseModel para aceder ao atributo
        return $this->id ?? 0;
    }

    public function getPasswordHash(): string
    {
        return $this->password ?? '';
    }

    public function getRoles(): array
    {
        // Retorna a role do utilizador, ou 'user' como padrão
        return isset($this->role) ? [$this->role] : ['user'];
    }
}
