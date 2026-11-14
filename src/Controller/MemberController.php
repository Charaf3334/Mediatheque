<?php

namespace App\Controller;

use App\Entity\{Book, Loan, Reservation};
use App\Service\LoanService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

class MemberController extends ActionController
{
    #[Route('/my', name: 'my')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        return $this->render('member/index.html.twig', [
            'loans' => $em->getRepository(Loan::class)->findBy(['member' => $user], ['borrowedAt' => 'DESC']),
            'reservations' => $em->getRepository(Reservation::class)->findBy(
                ['member' => $user, 'status' => [Reservation::WAITING, Reservation::READY]], ['createdAt' => 'ASC']),
        ]);
    }

    #[Route('/my/renew/{id}', name: 'renew', methods: ['POST'])]
    public function renew(Loan $loan, Request $request, LoanService $loans): Response
    {
        if ($loan->member->id !== $this->getUser()->id) {
            throw $this->createAccessDeniedException();
        }

        return $this->run($request, fn () => $loans->renew($loan), 'Loan renewed.', 'my');
    }

    #[Route('/my/reserve/{id}', name: 'reserve', methods: ['POST'])]
    public function reserve(Book $book, Request $request, LoanService $loans): Response
    {
        return $this->run($request, fn () => $loans->reserve($this->getUser(), $book), 'Reservation added.', 'my');
    }
}
