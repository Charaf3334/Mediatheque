<?php

namespace App\Entity;

use App\Enum\Plan;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    public string $email = '';

    #[ORM\Column]
    public string $password = '';

    #[ORM\Column(type: 'json')]
    public array $roles = [];

    #[ORM\Column(enumType: Plan::class)]
    public Plan $plan = Plan::Basic;

    public function getUserIdentifier(): string { return $this->email; }
    public function getRoles(): array { return array_unique([...$this->roles, 'ROLE_USER']); }
    public function getPassword(): string { return $this->password; }
    public function eraseCredentials(): void {}
    public function __toString(): string { return $this->email; }
}