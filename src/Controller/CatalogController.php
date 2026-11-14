<?php

namespace App\Controller;

use App\Entity\{Book, Copy};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

class CatalogController extends AbstractController
{
    #[Route('/', name: 'catalog')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $qb = $em->createQueryBuilder()
            ->select('b', 'SUM(CASE WHEN c.status = :av THEN 1 ELSE 0 END) AS available', 'COUNT(c.id) AS total')
            ->from(Book::class, 'b')
            ->leftJoin(Copy::class, 'c', 'WITH', 'c.book = b')
            ->groupBy('b.id')
            ->orderBy('b.title')
            ->setParameter('av', Copy::AVAILABLE);
        if ($q !== '') {
            $qb->where('b.title LIKE :q OR b.authors LIKE :q OR b.isbn LIKE :q')->setParameter('q', "%$q%");
        }

        return $this->render('catalog/index.html.twig', ['rows' => $qb->getQuery()->getResult(), 'q' => $q]);
    }
}
