<?php

namespace App\Service;

use App\Entity\{Book, Copy, Loan, Reservation, User};
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class LoanService
{
    public const FEE_PER_DAY = 1;
    public const LOST_FEE = 100; 

    public function __construct(private EntityManagerInterface $em) {}

    public function borrow(User $member, Copy $copy, ?\DateTimeImmutable $now = null): Loan
    {
        $now ??= new \DateTimeImmutable();

        return $this->transactional(function () use ($member, $copy, $now) {
            $this->em->refresh($copy, LockMode::PESSIMISTIC_WRITE);   // concurrent borrowers wait here
            $this->em->refresh($member, LockMode::PESSIMISTIC_WRITE); // and so do concurrent loans of one member

            $reservation = $copy->status === Copy::RESERVED
                ? $this->em->getRepository(Reservation::class)->findOneBy(
                    ['book' => $copy->book, 'member' => $member, 'status' => Reservation::READY])
                : null;

            if ($copy->status !== Copy::AVAILABLE && !$reservation) {
                throw new \DomainException('This copy is not available.');
            }
            $active = $this->em->getRepository(Loan::class)->count(['member' => $member, 'returnedAt' => null]);
            if ($active >= $member->plan->maxLoans()) {
                throw new \DomainException('Loan limit reached for this plan.');
            }

            $loan = new Loan();
            $loan->copy = $copy;
            $loan->member = $member;
            $loan->borrowedAt = $now;
            $loan->dueAt = $now->modify("+{$member->plan->days()} days");
            $copy->status = Copy::LOANED;
            if ($reservation) {
                $reservation->status = Reservation::DONE;
            }
            $this->em->persist($loan);

            return $loan;
        });
    }

    public function giveBack(Loan $loan, ?\DateTimeImmutable $now = null): void
    {
        $now ??= new \DateTimeImmutable();
        if ($loan->returnedAt) {
            throw new \DomainException('Already returned.');
        }

        $this->transactional(function () use ($loan, $now) {
            $loan->returnedAt = $now;
            $loan->fee = $this->lateDays($loan, $now) * self::FEE_PER_DAY;
            $next = $this->em->getRepository(Reservation::class)->findOneBy(
                ['book' => $loan->copy->book, 'status' => Reservation::WAITING],
                ['createdAt' => 'ASC', 'id' => 'ASC']);
            if ($next) {
                $next->status = Reservation::READY;
                $loan->copy->status = Copy::RESERVED;
            } else {
                $loan->copy->status = Copy::AVAILABLE;
            }
        });
    }

    public function renew(Loan $loan, ?\DateTimeImmutable $now = null): void
    {
        $now ??= new \DateTimeImmutable();
        if ($loan->returnedAt) {
            throw new \DomainException('Loan is already closed.');
        }
        if ($loan->renewed) {
            throw new \DomainException('A loan can be renewed only once.');
        }
        if ($now > $loan->dueAt) {
            throw new \DomainException('Overdue loans cannot be renewed.');
        }
        $waiting = $this->em->getRepository(Reservation::class)
            ->count(['book' => $loan->copy->book, 'status' => Reservation::WAITING]);
        if ($waiting) {
            throw new \DomainException('Someone is waiting for this book.');
        }

        $loan->dueAt = $loan->dueAt->modify("+{$loan->member->plan->days()} days");
        $loan->renewed = true;
        $this->em->flush();
    }

    public function reserve(User $member, Book $book): Reservation
    {
        $already = $this->em->getRepository(Reservation::class)->findOneBy(
            ['book' => $book, 'member' => $member, 'status' => [Reservation::WAITING, Reservation::READY]]);
        if ($already) {
            throw new \DomainException('You already reserved this book.');
        }
        if ($this->em->getRepository(Copy::class)->count(['book' => $book, 'status' => Copy::AVAILABLE])) {
            throw new \DomainException('A copy is available, borrow it directly.');
        }

        $r = new Reservation();
        $r->book = $book;
        $r->member = $member;
        $this->em->persist($r);
        $this->em->flush();

        return $r;
    }

    public function markLost(Loan $loan, ?\DateTimeImmutable $now = null): void
    {
        if ($loan->returnedAt) {
            throw new \DomainException('Loan is already closed.');
        }
        $loan->returnedAt = $now ?? new \DateTimeImmutable();
        $loan->fee = self::LOST_FEE;
        $loan->copy->status = Copy::LOST;
        $this->em->flush();
    }

    private function lateDays(Loan $loan, \DateTimeImmutable $now): int
    {
        return $now > $loan->dueAt ? (int) $loan->dueAt->diff($now)->days : 0;
    }

    private function transactional(callable $fn): mixed
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            $result = $fn();
            $this->em->flush();
            $conn->commit();

            return $result;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}