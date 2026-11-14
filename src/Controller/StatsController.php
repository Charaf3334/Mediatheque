<?php

namespace App\Controller;

use App\Entity\{Book, Copy, Loan};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatsController extends AbstractController
{
    #[Route('/stats', name: 'stats')]
    public function index(EntityManagerInterface $em): Response
    {
        $top = $em->createQueryBuilder()
            ->select('b.title', 'COUNT(l.id) AS n')
            ->from(Loan::class, 'l')->join('l.copy', 'c')->join('c.book', 'b')
            ->groupBy('b.id')->addGroupBy('b.title')
            ->orderBy('n', 'DESC')->setMaxResults(10)
            ->getQuery()->getArrayResult();

        $never = $em->createQuery(
            'SELECT b.title FROM ' . Book::class . ' b WHERE NOT EXISTS (SELECT l.id FROM '
            . Loan::class . ' l JOIN l.copy c WHERE c.book = b) ORDER BY b.title'
        )->getArrayResult();

        $loans = $em->getRepository(Loan::class)->count([]);
        $copies = $em->getRepository(Copy::class)->count([]);

        return $this->render('stats/index.html.twig', [
            'top' => $top,
            'never' => $never,
            'loans' => $loans,
            'copies' => $copies,
            'rotation' => $copies ? round($loans / $copies, 2) : 0,
        ]);
    }
}
