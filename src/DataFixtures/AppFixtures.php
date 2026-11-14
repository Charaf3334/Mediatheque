<?php

namespace App\DataFixtures;

use App\Entity\{Book, Copy, Loan, Reservation, User};
use App\Enum\Plan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const BOOKS = [
        ['Le Petit Prince', 'Antoine de Saint-Exupéry'], ['L’Étranger', 'Albert Camus'],
        ['Les Misérables', 'Victor Hugo'], ['Germinal', 'Émile Zola'],
        ['Madame Bovary', 'Gustave Flaubert'], ['Le Rouge et le Noir', 'Stendhal'],
        ['Candide', 'Voltaire'], ['Notre-Dame de Paris', 'Victor Hugo'],
        ['Le Comte de Monte-Cristo', 'Alexandre Dumas'], ['Vingt mille lieues sous les mers', 'Jules Verne'],
        ['Le Tour du monde en quatre-vingts jours', 'Jules Verne'], ['1984', 'George Orwell'],
        ['La Ferme des animaux', 'George Orwell'], ['Fahrenheit 451', 'Ray Bradbury'],
        ['Le Meilleur des mondes', 'Aldous Huxley'], ['L’Alchimiste', 'Paulo Coelho'],
        ['Cent ans de solitude', 'Gabriel García Márquez'], ['Sapiens', 'Yuval Noah Harari'],
        ['Harry Potter à l’école des sorciers', 'J. K. Rowling'], ['Le Seigneur des anneaux', 'J. R. R. Tolkien'],
        ['Dune', 'Frank Herbert'], ['Tintin au Tibet', 'Hergé'],
        ['Astérix le Gaulois', 'René Goscinny, Albert Uderzo'], ['La Ballade des Dalton', 'Morris, René Goscinny'],
        ['Persepolis', 'Marjane Satrapi'], ['Maus', 'Art Spiegelman'],
        ['Le Pain nu', 'Mohamed Choukri'], ['La Boîte à merveilles', 'Ahmed Sefrioui'],
        ['Le Passé simple', 'Driss Chraïbi'], ['Inception (DVD)', 'Christopher Nolan'],
        ['Le Fabuleux Destin d’Amélie Poulain (DVD)', 'Jean-Pierre Jeunet'],
    ];

    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $em): void
    {
        mt_srand(42);

        $user = function (string $email, array $roles, Plan $plan = Plan::Basic) use ($em): User {
            $u = new User();
            $u->email = $email;
            $u->roles = $roles;
            $u->plan = $plan;
            $u->password = $this->hasher->hashPassword($u, 'password');
            $em->persist($u);
            return $u;
        };

        $user('admin@library.test', ['ROLE_ADMIN']);
        $user('librarian@library.test', ['ROLE_LIBRARIAN']);
        $members = [];
        for ($i = 1; $i <= 8; $i++) {
            $members[] = $user("member$i@library.test", [], $i % 3 === 0 ? Plan::Premium : Plan::Basic);
        }

        $copies = [];
        foreach (self::BOOKS as [$title, $authors]) {
            $book = new Book();
            $book->title = $title;
            $book->authors = $authors;
            $em->persist($book);
            for ($i = 0, $n = mt_rand(4, 9); $i < $n; $i++) {
                $copy = new Copy();
                $copy->book = $book;
                $copy->site = $i % 2 ? 'B' : 'A';
                $em->persist($copy);
                $copies[] = $copy;
            }
        }

        $loan = function (Copy $copy, User $m, int $daysAgo, ?int $keptDays) use ($em): void {
            $l = new Loan();
            $l->copy = $copy;
            $l->member = $m;
            $l->borrowedAt = new \DateTimeImmutable("-$daysAgo days");
            $l->dueAt = $l->borrowedAt->modify('+' . $m->plan->days() . ' days');
            if ($keptDays !== null) {
                $l->returnedAt = $l->borrowedAt->modify("+$keptDays days");
                $l->fee = max(0, $keptDays - $m->plan->days());
            } else {
                $copy->status = Copy::LOANED;
            }
            $em->persist($l);
        };

        shuffle($copies);
        $active = array_splice($copies, 0, 13);

        foreach ($active as $i => $copy) {
            $i >= 10
                ? $loan($copy, $members[$i - 10], 45, null)
                : $loan($copy, $members[array_rand($members)], mt_rand(1, 10), null);
        }
        for ($i = 0; $i < 40; $i++) {
            $loan($copies[array_rand($copies)], $members[array_rand($members)], mt_rand(50, 150), mt_rand(3, 40));
        }

        $r = new Reservation();
        $r->book = $active[0]->book;
        $r->member = $members[7];
        $em->persist($r);

        $em->flush();
    }
}