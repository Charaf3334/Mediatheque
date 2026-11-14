# Journal des décisions

## Journal (14 novembre 2026)

- **Sujet 4 (médiathèques).** Choisi parce que la difficulté est dans des règles métier lisibles (limites, file d'attente, pénalités), sans placement de sièges ni calcul par tronçons, et parce que Open Library n'exige ni clé ni authentification. Je découvre Symfony pendant ce challenge : j'ai privilégié un code court et testé.
- **Docker dès le premier commit.** `php:8.3-apache` + `mysql:8.0`. Au démarrage, le conteneur installe les dépendances si besoin, applique les migrations et charge les données de démonstration uniquement si la base est vide.
- **Code minimal.** Entités à propriétés publiques (pas de getters/setters), formules d'abonnement en enum, statuts en constantes, pas de JavaScript, une feuille de style Pico CSS par CDN.
- **Règles métier regroupées dans `LoanService`** (emprunt, retour, prolongation, réservation, perte). Les contrôleurs ne font que l'appeler. Les prêts sont en lecture seule dans le back-office EasyAdmin : un bibliothécaire ne peut pas contourner les règles en éditant un prêt à la main.
- **Règles retenues.**
  - Limite de prêts et durée selon la formule (basic 3 prêts / 14 j, premium 6 prêts / 28 j).
  - Retard : 1 MAD par jour, calculé au retour.
  - Perte : 100 MAD, le prêt est clos et l'exemplaire passe à `lost`.
  - Prolongation : une seule fois, jamais en retard, jamais si quelqu'un attend le titre.
  - Réservation : seulement si plus aucun exemplaire du réseau n'est disponible.
- **Concurrence.** L'emprunt s'exécute dans une transaction qui verrouille la ligne de l'exemplaire (`FOR UPDATE`) et celle de l'adhérent : deux emprunts simultanés du même exemplaire, ou deux emprunts simultanés qui dépasseraient la limite d'un adhérent, sont sérialisés. J'ai écrit la transaction à la main car `wrapInTransaction` ferme l'EntityManager en cas d'exception, ce qui casserait l'affichage du message d'erreur.
- **Tests.** 8 tests sur `LoanService` (limite, durée selon la formule, exemplaire indisponible, frais de retard, prolongation bloquée par la file, prolongation unique, ordre de la file, perte) et 2 tests sur le client Open Library (champs manquants, découpage en lots de 50). Une garde refuse de lancer les tests si la base n'est pas une base `_test`.
- **Open Library.** Appel à `/api/books?bibkeys=ISBN:…` (jusqu'à 50 ISBN par requête) plutôt qu'à `/isbn/{isbn}.json` : les noms d'auteurs sont dans la réponse, sans requête supplémentaire par auteur. `User-Agent` explicite, timeout de 10 s, aucun appel répété.

## Questions à trancher

**Deux éditions d'un même roman sont-elles la même œuvre ?**
Non : une édition (un ISBN) = une fiche `Book`. Il n'y a pas d'entité « Œuvre ». Conséquence : une réservation porte sur une édition précise. C'est le choix le plus simple et il suffit pour le besoin décrit ; l'évolution naturelle serait une entité `Work` qui regroupe plusieurs `Book`.

**Un adhérent peut-il réserver un document disponible uniquement dans l'autre site ?**
Il n'a pas besoin de le réserver : tant qu'un exemplaire est disponible dans l'un des deux sites, il est empruntable directement, le prêt étant enregistré au comptoir du site où l'exemplaire se trouve. La réservation n'est ouverte que lorsque plus aucun exemplaire du réseau n'est disponible, et elle vaut pour tout le réseau, quel que soit le site. Limite assumée : il n'y a pas de workflow de transfert entre sites ; le bibliothécaire change le champ `site` de l'exemplaire dans le back-office quand il le déplace physiquement.

**Combien de temps un document réservé reste-t-il mis de côté une fois revenu ?**
Hypothèse : 3 jours. **Non implémenté** : aujourd'hui l'exemplaire reste `reserved` jusqu'à ce que son réservataire l'emprunte, et un bibliothécaire peut le libérer à la main dans le back-office. Avec plus de temps : un champ `readyAt` sur la réservation et une commande planifiée qui passe la réservation à `cancelled` et sert le suivant de la file.

**Comment représenter les auteurs multiples, les traducteurs, les illustrateurs ?**
Un seul champ texte `authors`, séparé par des virgules, ce que renvoie Open Library. Les rôles (traducteur, illustrateur) ne sont pas modélisés. Avec plus de temps : une table `Contributor (book, name, role)`.

**Que faire quand Open Library renvoie des données incomplètes ou différentes de celles du catalogue ?**
Le catalogue fait foi. L'import ne crée que les ISBN absents du catalogue et n'écrase jamais une fiche existante. Titre absent : la fiche est refusée et signalée dans le rapport. Auteurs absents : « Unknown author ». Éditeur et couverture sont facultatifs. Les ISBN invalides, en double ou introuvables sont listés dans le rapport d'import, qui indique aussi les champs manquants de chaque fiche créée.

## Usage de l'IA

J'ai construit le projet avec Claude (Anthropic), pas à pas : je demandais un morceau, je l'exécutais, puis je lui renvoyais la sortie réelle (erreurs, arborescence, journaux). Trois erreurs repérées en exécutant :

1. Une commande utilisait un alias Flex inexistant (`test-pack`) : l'installation a échoué et j'ai renvoyé l'erreur, ce qui a donné le nom complet du paquet.
2. Le chemin donné pour `security.yaml` était faux (`config/` au lieu de `config/packages/`) : repéré en comparant avec mon arborescence.
3. L'API d'EasyAdmin installée n'était pas celle de la réponse (`linkToCrud` vs `linkTo`) : en collant le `DashboardController` généré, le code a été réécrit pour la bonne version.
