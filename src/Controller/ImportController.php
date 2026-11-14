<?php

namespace App\Controller;

use App\Entity\{Book, Copy};
use App\Service\OpenLibraryClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class ImportController extends AbstractController
{
    #[Route('/admin/import', name: 'import', methods: ['GET', 'POST'])]
    public function index(Request $request, OpenLibraryClient $client, EntityManagerInterface $em): Response
    {
        $report = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('act', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $copies = max(0, min(20, (int) $request->request->get('copies', 1)));
            $report = ['imported' => [], 'skipped' => []];

            $valid = [];
            foreach (preg_split('/[\s,;]+/', (string) $request->request->get('isbns'), -1, PREG_SPLIT_NO_EMPTY) as $raw) {
                $isbn = strtoupper(preg_replace('/[^0-9Xx]/', '', $raw));
                if (preg_match('/^(\d{9}[\dX]|\d{13})$/', $isbn)) {
                    $valid[$isbn] = true;
                } else {
                    $report['skipped'][] = [$raw, 'invalid ISBN'];
                }
            }
            $isbns = array_map('strval', array_keys($valid));

            $existing = $isbns ? array_flip(array_map(
                fn (Book $b) => (string) $b->isbn,
                $em->getRepository(Book::class)->findBy(['isbn' => $isbns]))) : [];
            $todo = [];
            foreach ($isbns as $isbn) {
                isset($existing[$isbn]) ? $report['skipped'][] = [$isbn, 'already in the catalogue'] : $todo[] = $isbn;
            }

            try {
                $found = $todo ? $client->fetch($todo) : []; // sending request to open library
            } catch (ExceptionInterface $e) {
                $this->addFlash('error', 'Open Library is unreachable: ' . $e->getMessage());

                return $this->redirectToRoute('import');
            }

            foreach ($todo as $isbn) {
                $d = $found[$isbn] ?? null;
                if (!$d) {
                    $report['skipped'][] = [$isbn, 'not found on Open Library'];
                    continue;
                }
                if (!$d['title']) {
                    $report['skipped'][] = [$isbn, 'incomplete record (no title)'];
                    continue;
                }

                $book = new Book();
                $book->isbn = $isbn;
                $book->title = $d['title'];
                $book->authors = $d['authors'] ?? 'Unknown author';
                $book->publisher = $d['publisher'];
                $book->coverUrl = $d['coverUrl'];
                $em->persist($book);
                for ($i = 0; $i < $copies; $i++) {
                    $copy = new Copy();
                    $copy->book = $book;
                    $copy->site = $i % 2 ? 'B' : 'A';
                    $em->persist($copy);
                }

                $missing = array_keys(array_filter(['authors' => !$d['authors'], 'publisher' => !$d['publisher'], 'cover' => !$d['coverUrl']]));
                $report['imported'][] = [$isbn, $book->title, $missing ? 'missing: ' . implode(', ', $missing) : ''];
            }
            $em->flush();
        }

        return $this->render('import/index.html.twig', ['report' => $report]);
    }
}
