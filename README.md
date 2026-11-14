# Médiathèques : PFE Challenge EMC

**Sujet choisi : 4, gestion d'un réseau de médiathèques** (Symfony 7.4, PHP 8.3, MySQL 8, Doctrine, Twig).

## Lancer le projet

```bash
docker compose up --build
```

Puis http://localhost:8000. Détails, comptes de démonstration et dépannage : [INSTALL.md](INSTALL.md).

## Niveaux atteints

| Niveau | Statut | Où |
|---|---|---|
| 1. Modéliser | fait | entités `src/Entity`, migration, fixtures (31 titres, ~200 exemplaires, 10 comptes, prêts en cours, en retard et passés), back-office EasyAdmin `/admin` |
| 2. Règles | fait | `src/Service/LoanService.php` + `tests/` (10 tests) |
| 3. Produit | fait, version minimale | connexion et rôles, catalogue avec recherche `/`, espace adhérent `/my`, comptoir `/admin/desk`, statistiques `/stats` |
| 4. API externe | fait | import Open Library par ISBN, à l'unité ou par lot (`/admin/import`) |
| 5. Livrer | fait | `docker compose up --build` + [INSTALL.md](INSTALL.md) |

## Ce qui fonctionne

- Prêt, retour, prolongation, réservation avec file d'attente (premier réservant servi au retour), pénalités de retard, exemplaires perdus.
- Limites et durées selon la formule `basic` ou `premium`.
- Emprunts simultanés sérialisés par verrou de base de données.
- Trois rôles : adhérent, bibliothécaire, administrateur.
- Statistiques : titres les plus empruntés, titres jamais empruntés, taux de rotation (prêts par exemplaire).

## Ce qui reste à faire (limites connues)

- Pas de délai de retrait pour un exemplaire réservé ni d'annulation de réservation par l'adhérent.
- Pas de workflow de transfert entre sites : le site d'un exemplaire se modifie dans le back-office.
- Les pénalités sont enregistrées mais ne bloquent pas de nouveaux prêts.
- Les auteurs sont un champ texte unique, sans rôles.
- Pas de pagination sur le catalogue, interface volontairement sobre.

## Documentation

- [docs/MODELE.md](docs/MODELE.md) : modèle de données et choix
- [docs/DECISIONS.md](docs/DECISIONS.md) : journal des décisions et réponses aux « Questions à trancher »

## Tests

```bash
docker compose exec app vendor/bin/phpunit
```
