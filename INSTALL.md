# Installation

## Prérequis

- Docker avec Docker Compose v2 (`docker compose version`)
- Le port **8000** libre
- Un accès Internet au premier démarrage (Composer, feuille de style Pico CSS, API Open Library)

## Installation

```bash
git clone https://github.com/Charaf3334/Library.git
cd Library
docker compose up --build --wait
```

Au premier démarrage (environ 2 à 3 minutes), le conteneur installe les dépendances PHP, applique les migrations et charge les données de démonstration. L'application est prête quand le log affiche `apache2 -D FOREGROUND`.

Ouvrir http://localhost:8000.

## Comptes de démonstration (mot de passe : `password`)

| Compte | Rôle |
|---|---|
| `admin@library.test` | administrateur |
| `librarian@library.test` | bibliothécaire (comptoir, import, statistiques, back-office) |
| `member1@library.test` … `member8@library.test` | adhérents (les n°3 et n°6 sont *premium*) |

Pages utiles : `/` (catalogue), `/my` (espace adhérent), `/admin` (back-office), `/admin/desk` (prêts et retours), `/admin/import` (import ISBN), `/stats`.

## Tests automatisés

```bash
docker compose exec app vendor/bin/phpunit
```

Les tests utilisent une base séparée (`library_test`), créée automatiquement au premier démarrage ; la base de démonstration n'est jamais touchée.

## Commandes utiles

```bash
docker compose exec app php bin/console doctrine:fixtures:load -n   # remettre les données de démo à zéro
docker compose down                                                  # arrêter (les données sont conservées)
docker compose down -v                                               # arrêter et tout effacer (base comprise)
```

## Dépannage

- **Port 8000 déjà utilisé** : arrêter l'autre service, ou changer `"8000:80"` dans `docker-compose.yaml`.
- **Page blanche ou erreur 500 juste après le démarrage** : attendre la fin du premier démarrage (`docker compose logs -f app`).
- **`Permission denied` dans `var/`** (Linux) : `docker compose exec app chown -R www-data:www-data var`.
- **Les tests échouent avec « Unknown database library_test »** (volume MySQL créé avant ce script) : `docker compose down -v && docker compose up --build --wait`.
- **Page sans mise en forme** : la feuille de style est chargée depuis un CDN ; vérifier la connexion Internet.
