<?php

namespace App\Controller;

use App\Entity\{Copy, Loan, Reservation, User};
use App\Service\LoanService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

class DeskController extends ActionController
{
    #[Route('/admin/desk', name: 'desk')]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('desk/index.html.twig', [
            'loans' => $em->getRepository(Loan::class)->findBy(['returnedAt' => null], ['dueAt' => 'ASC']),
            'held' => $em->getRepository(Copy::class)->findBy(['status' => Copy::RESERVED]),
            'ready' => $em->getRepository(Reservation::class)->findBy(['status' => Reservation::READY]),
        ]);
    }

    #[Route('/admin/desk/borrow', name: 'desk_borrow', methods: ['POST'])]
    public function borrow(Request $request, EntityManagerInterface $em, LoanService $loans): Response
    {
        return $this->run($request, function () use ($request, $em, $loans) {
            $member = $em->getRepository(User::class)->findOneBy(['email' => trim((string) $request->request->get('email'))]);
            $copy = $em->getRepository(Copy::class)->find((int) $request->request->get('copy'));
            if (!$member || !$copy) {
                throw new \DomainException('Unknown member or copy.');
            }
            $loans->borrow($member, $copy);
        }, 'Loan recorded.', 'desk');
    }

    #[Route('/admin/desk/return/{id}', name: 'desk_return', methods: ['POST'])]
    public function giveBack(Loan $loan, Request $request, LoanService $loans): Response
    {
        return $this->run($request, fn () => $loans->giveBack($loan), 'Returned (fee: ' . $loan->fee . ' MAD).', 'desk');
    }

    #[Route('/admin/desk/lost/{id}', name: 'desk_lost', methods: ['POST'])]
    public function lost(Loan $loan, Request $request, LoanService $loans): Response
    {
        return $this->run($request, fn () => $loans->markLost($loan), 'Marked as lost.', 'desk');
    }
}
