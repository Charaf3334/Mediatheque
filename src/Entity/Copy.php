<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'book_copy')]
class Copy
{
    public const AVAILABLE = 'available';
    public const LOANED = 'loaned';
    public const RESERVED = 'reserved';
    public const LOST = 'lost';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public ?Book $book = null;

    #[ORM\Column(length: 20)]
    public string $site = 'A';

    #[ORM\Column(length: 20)]
    public string $status = self::AVAILABLE;

    public function __toString(): string { return "#{$this->id} {$this->book} (site {$this->site})"; }
}