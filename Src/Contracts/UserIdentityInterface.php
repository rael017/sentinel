<?php
namespace Horus\Sentinel\Contracts;

/**
 * Define o contrato para um objeto de Utilizador.
 */
interface UserIdentityInterface
{
    public function getId(): int;
    public function getPasswordHash(): string;
    public function getRoles(): array; // Ex: ['admin', 'editor']
}