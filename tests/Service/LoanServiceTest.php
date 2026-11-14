<?php

namespace App\Tests\Service;

use App\Entity\{Book, Copy, Reservation, User};
use App\Enum\Plan;
use App\Service\LoanService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LoanServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private LoanService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();

        if (!str_contains($this->em->getConnection()->getParams()['dbname'] ?? '', '_test')) {
            throw new \RuntimeException('Tests must run on the *_test database.');
        }
        $tool = new SchemaTool($this->em);
        $meta = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($meta);
        $tool->createSchema($meta);

        $this->service = new LoanService($this->em);
    }

    private function save(object $o): object
    {
        $this->em->persist($o);
        $this->em->flush();

        return $o;
    }

    private function user(Plan $plan = Plan::Basic): User
    {
        $u = new User();
        $u->email = uniqid('u') . '@test.local';
        $u->plan = $plan;

        return $this->save($u);
    }

    private function book(): Book
    {
        $b = new Book();
        $b->title = 'Test book';
        $b->authors = 'Someone';

        return $this->save($b);
    }

    private function copy(Book $book): Copy
    {
        $c = new Copy();
        $c->book = $book;

        return $this->save($c);
    }

    private function reservation(Book $book, User $member, string $createdAt): Reservation
    {
        $r = new Reservation();
        $r->book = $book;
        $r->member = $member;
        $r->createdAt = new \DateTimeImmutable($createdAt);

        return $this->save($r);
    }

    public function testBorrowSetsDueDateFromPlan(): void
    {
        $copy = $this->copy($this->book());
        $now = new \DateTimeImmutable('2026-01-01 10:00');

        $loan = $this->service->borrow($this->user(Plan::Premium), $copy, $now);

        $this->assertEquals(new \DateTimeImmutable('2026-01-29 10:00'), $loan->dueAt); // premium = 28 days
        $this->assertSame(Copy::LOANED, $copy->status);
    }

    public function testBorrowRefusedWhenLimitReached(): void
    {
        $member = $this->user(); // basic = 3 loans max
        $book = $this->book();
        for ($i = 0; $i < 3; $i++) {
            $this->service->borrow($member, $this->copy($book));
        }

        $this->expectException(\DomainException::class);
        $this->service->borrow($member, $this->copy($book));
    }

    public function testBorrowRefusedWhenCopyNotAvailable(): void
    {
        $copy = $this->copy($this->book());
        $this->service->borrow($this->user(), $copy);

        $this->expectException(\DomainException::class);
        $this->service->borrow($this->user(), $copy);
    }

    public function testLateFeeIsChargedOnReturn(): void
    {
        $now = new \DateTimeImmutable();
        $loan = $this->service->borrow($this->user(), $this->copy($this->book()), $now->modify('-20 days'));

        $this->service->giveBack($loan, $now); // basic = 14 days, so 6 days late

        $this->assertSame(6 * LoanService::FEE_PER_DAY, $loan->fee);
    }

    public function testRenewBlockedWhenSomeoneIsWaiting(): void
    {
        $book = $this->book();
        $loan = $this->service->borrow($this->user(), $this->copy($book));
        $this->reservation($book, $this->user(), 'now');

        $this->expectException(\DomainException::class);
        $this->service->renew($loan);
    }

    public function testRenewOnlyOnce(): void
    {
        $now = new \DateTimeImmutable('2026-01-01 10:00');
        $loan = $this->service->borrow($this->user(), $this->copy($this->book()), $now);

        $this->service->renew($loan, $now);
        $this->assertEquals(new \DateTimeImmutable('2026-01-29 10:00'), $loan->dueAt);

        $this->expectException(\DomainException::class);
        $this->service->renew($loan, $now);
    }

    public function testReturnedCopyGoesToFirstInQueue(): void
    {
        $book = $this->book();
        $copy = $this->copy($book);
        [$borrower, $first, $second] = [$this->user(), $this->user(), $this->user()];
        $loan = $this->service->borrow($borrower, $copy);

        // inserted in the wrong order on purpose
        $late = $this->reservation($book, $second, '-1 hour');
        $early = $this->reservation($book, $first, '-2 hours');

        $this->service->giveBack($loan);

        $this->assertSame(Reservation::READY, $early->status);
        $this->assertSame(Reservation::WAITING, $late->status);
        $this->assertSame(Copy::RESERVED, $copy->status);

        try {
            $this->service->borrow($second, $copy);
            $this->fail('The second in the queue must not get the copy.');
        } catch (\DomainException) {
        }

        $this->service->borrow($first, $copy);
        $this->assertSame(Reservation::DONE, $early->status);
        $this->assertSame(Copy::LOANED, $copy->status);
    }

    public function testLostCopyClosesLoanAndChargesFee(): void
    {
        $copy = $this->copy($this->book());
        $loan = $this->service->borrow($this->user(), $copy);

        $this->service->markLost($loan);

        $this->assertSame(Copy::LOST, $copy->status);
        $this->assertSame(LoanService::LOST_FEE, $loan->fee);
        $this->assertNotNull($loan->returnedAt);
    }
}
