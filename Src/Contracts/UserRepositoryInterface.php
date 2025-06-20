<?php
namespace Horus\Sentinel\Contracts;

/**
 * Define o contrato para uma classe que sabe como encontrar utilizadores.
 */
interface UserRepositoryInterface
{
    public function findById(int $id): ?UserIdentityInterface;
    public function findByEmail(string $email): ?UserIdentityInterface;
}
