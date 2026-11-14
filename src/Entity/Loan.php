<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Loan
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public ?Copy $copy = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public ?User $member = null;

    #[ORM\Column]
    public \DateTimeImmutable $borrowedAt;

    #[ORM\Column]
    public \DateTimeImmutable $dueAt;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $returnedAt = null;

    #[ORM\Column]
    public bool $renewed = false;

    #[ORM\Column]
    public int $fee = 0;

    public function __construct()
    {
        $this->borrowedAt = new \DateTimeImmutable();
        $this->dueAt = new \DateTimeImmutable('+14 days');
    }
}