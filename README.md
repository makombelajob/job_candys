# Job Candys

**Job Candys** est une plateforme de gestion et d'automatisation du recrutement développée avec **Symfony 8.1** et **PHP 8.4**. Elle permet de gérer les utilisateurs, profils, entreprises, candidatures, contacts professionnels et communications email, avec intégration des APIs **INSEE** et **Hunter**.

## Stack technique

* **PHP 8.4**
* **Symfony 8.1**
* **MySQL 8.0**
* **Doctrine ORM / Migrations**
* **Twig, Stimulus, UX Turbo, Asset Mapper**
* **Symfony Mailer / MailHog**
* **PHPUnit**
* **Docker Compose**
* **INSEE API / Hunter API / IMAP**

## Structure

```text
app/
├── src/
│   ├── Controller/       # Requêtes HTTP et routes
│   ├── Entity/           # Entités Doctrine
│   ├── Service/          # Logique métier
│   ├── Repository/       # Accès aux données
│   ├── Form/             # Formulaires Symfony
│   ├── Security/         # Authentification
│   └── Kernel.php
├── config/               # Configuration Symfony
├── templates/            # Templates Twig
├── public/               # Racine web
├── tests/                # Tests PHPUnit
├── migrations/           # Migrations Doctrine
├── var/                  # Cache et logs
└── vendor/               # Dépendances Composer
```

## Entités

⚠️ Les entités utilisent des noms **au pluriel**, contrairement aux conventions Symfony habituelles :

`Users`, `Profils`, `Companies`, `CompanyContacts`, `Applications`, `Notifications`.

Toujours utiliser les noms réels des classes présentes dans `src/Entity/`.

## Services principaux

| Service                       | Rôle                                       |
| ----------------------------- | ------------------------------------------ |
| `WebsiteFinderService`        | Recherche du site web d'une entreprise     |
| `WebsiteContactFinderService` | Recherche de contacts sur un site          |
| `EmailService`                | Envoi d'emails                             |
| `UserCreatorService`          | Création des utilisateurs                  |
| `HunterEmailVerify`           | Vérification des emails via Hunter         |
| `ImapService`                 | Lecture des emails via IMAP                |
| `TechnologyDetectorService`   | Détection des technologies d'un site       |
| `InseeApiService`             | Récupération des données entreprises INSEE |
| `FileUploader`                | Gestion des fichiers                       |

Les services utilisent l'injection de dépendances Symfony et l'autowiring.

## Controllers principaux

`MainController` gère les pages publiques, `RegistrationController` l'inscription, `SecurityController` l'authentification, `ResetPasswordController` la récupération de mot de passe, `FindWebController` la recherche de sites, `FindContactController` la recherche de contacts, `FreelanceApplicationController` et `SpontaneousApplicationController` les candidatures, `AdminController` l'administration et `ProfilesController` les profils.

Les routes utilisent les attributs PHP :

```php
#[Route('/find/web/{siret}', name: 'app_find_web')]
```

## Règle importante : BDD prioritaire

Pour les recherches d'entreprises et de contacts, **la base de données est toujours prioritaire**.

### Recherche de site

```text
SIRET
  ↓
Companies en BDD
  ↓
Site présent ?
  ├── Oui → utiliser le site de la BDD
  └── Non → INSEE → WebsiteFinderService → sauvegarde
```

`WebsiteFinderService` ne doit donc pas être appelé lorsqu'un site existe déjà en BDD.

### Recherche de contacts

```text
SIRET
  ↓
Companies en BDD
  ↓
CompanyContacts existants ?
  ├── Oui → utiliser les contacts de la BDD
  └── Non → WebsiteContactFinderService → sauvegarde
```

Cette règle évite les recherches externes inutiles et garantit la conservation des données déjà enregistrées.

## Améliorations des services

`WebsiteFinderService` et `WebsiteContactFinderService` utilisent désormais `HttpClientInterface` à la place de `curl`, avec logging PSR-3, validation des entrées, gestion des erreurs et configuration HTTP cohérente.

Configuration HTTP :

* Timeout : **10 secondes**
* Redirections maximales : **5**
* User-Agent navigateur

Les erreurs sont gérées avec `try/catch` dans les services et Controllers afin d'éviter d'exposer des erreurs HTTP 500 à l'utilisateur.

Les niveaux de logs utilisés sont `INFO`, `DEBUG`, `WARNING` et `ERROR`.

## Docker / développement

Démarrer le projet :

```bash
docker compose up -d --build
docker exec -it job_candys_php /bin/bash
cd /var/www/html
composer install
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
```

### Accès locaux

| Service     | Adresse                 |
| ----------- | ----------------------- |
| Application | `http://localhost:8080` |
| phpMyAdmin  | `http://localhost:8081` |
| MailHog     | `http://localhost:8025` |

### Base de données

```text
Host: database
Port: 3306
Database: job_candys
User: admin
```

La configuration complète se trouve dans les variables d'environnement.

## Variables d'environnement

Exemple :

```env
APP_ENV=dev
APP_SECRET=...
DATABASE_URL=mysql://admin:admin7791@database:3306/job_candys
MAILER_DSN=smtp://mailhog:1025

INSEE_API_KEY=...
HUNTER_API_KEY=...

IMAP_HOST=...
IMAP_PORT=...
IMAP_ENCRYPTION=...
IMAP_USERNAME=...
IMAP_PASSWORD=...
```

⚠️ Ne jamais versionner les clés API ou identifiants. Utiliser `.env.local` pour les valeurs sensibles.

## Commandes utiles

```bash
# Tests
php bin/phpunit

# Routes
php bin/console debug:router

# Services
php bin/console debug:container

# Cache
php bin/console cache:clear

# Créer une migration
php bin/console doctrine:migrations:diff

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# SQL
php bin/console doctrine:query:sql
```

## Conventions de développement

1. Utiliser l'injection de dépendances par constructeur.
2. Ne jamais instancier manuellement les services.
3. Placer la logique métier dans les `Service`.
4. Utiliser les `Repository` pour les requêtes complexes.
5. Utiliser les contraintes Symfony pour la validation.
6. Utiliser les attributs `#[Route]` pour les routes.
7. Les Controllers doivent rester légers.
8. Utiliser Doctrine pour les relations entre entités.
9. Respecter les noms d'entités existants, même lorsqu'ils sont au pluriel.
10. Conserver les chaînes et interfaces utilisateur principalement en français.

## Sécurité

L'authentification utilise Symfony Security avec :

* `app_user_provider`
* `LoginAuthenticator`
* authentification par formulaire
* cookie `remember_me`
* hachage des mots de passe via `auto`
* entité utilisateur `Users`

## Fichiers uploadés

```text
public/uploads/profiles/
public/uploads/temp/
```

La gestion des uploads est centralisée dans `FileUploader`.

## Tests et contribution

Avant toute livraison :

```bash
php bin/phpunit
php bin/console cache:clear
```

Puis tester les fonctionnalités concernées dans le navigateur et vérifier les emails via MailHog en environnement local.

Workflow recommandé :

```text
Nouvelle branche
    ↓
Développement
    ↓
Tests PHPUnit
    ↓
Vérification navigateur
    ↓
Commit
    ↓
Push
    ↓
Pull Request
```

## Architecture : quelle couche utiliser ?

| Besoin                   | Couche                        |
| ------------------------ | ----------------------------- |
| Requête HTTP / affichage | `Controller`                  |
| Logique métier / API     | `Service`                     |
| Requêtes BDD             | `Repository`                  |
| Données / relations      | `Entity`                      |
| Validation               | `Entity` / Symfony Validator  |
| Formulaire               | `Form`                        |
| Authentification         | `Security`                    |
| Envoi email              | `EmailService`                |
| Recherche de site        | `WebsiteFinderService`        |
| Recherche de contacts    | `WebsiteContactFinderService` |
| Vérification email       | `HunterEmailVerify`           |

---

**Projet : Job Candys**
**Symfony 8.1 · PHP 8.4 · MySQL 8.0**
**Dernière mise à jour : 13/08/2026**
