<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Book
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public string $title = '';

    #[ORM\Column]
    public string $authors = '';

    #[ORM\Column(nullable: true)]
    public ?string $publisher = null;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    public ?string $isbn = null;

    #[ORM\Column(nullable: true)]
    public ?string $coverUrl = null;

    public function __toString(): string { return $this->title; }
}