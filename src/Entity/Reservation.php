<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Reservation
{
    public const WAITING = 'waiting';
    public const READY = 'ready';
    public const DONE = 'done';
    public const CANCELLED = 'cancelled';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public ?Book $book = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public ?User $member = null;

    #[ORM\Column]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 20)]
    public string $status = self::WAITING;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}