# Job Candys

Job Candys est une application web développée avec Symfony 8.1 pour gérer des parcours de recrutement, des candidatures et des profils utilisateurs. Le projet est pensé comme une plateforme moderne, modulable et prête à évoluer avec des fonctions d’authentification, d’emailing, de gestion de base de données et d’administration.

## Vue d’ensemble

Ce projet combine :
- un front-office avec des pages de présentation et de collecte de candidatures ;
- un back-office côté templates d’administration ;
- une couche sécurité complète avec inscription, connexion et récupération de mot de passe ;
- une architecture Docker pour simplifier le développement local ;
- une base de données et des migrations Doctrine pour l’évolution du modèle de données.

## Fonctionnalités principales

### Fonctionnalités déjà présentes
- page d’accueil, à propos et contact ;
- formulaires de candidature :
  - recherche de contact ;
  - recherche de site web ;
  - candidature freelance ;
  - candidature spontanée ;
- inscription utilisateur avec mot de passe hashé ;
- gestion de profils utilisateurs ;
- système de réinitialisation de mot de passe prêt à l’emploi ;
- templates dédiés à l’administration.

### Fonctionnalités prévues ou déjà préparées par la configuration
- authentification complète avec firewall Symfony Security ;
- envoi d’emails via Symfony Mailer ;
- gestion asynchrone des messages avec Messenger ;
- intégration de la base de données via Doctrine ORM et migrations ;
- administration étendue des utilisateurs, profils et entreprises ;
- intégration possible d’un système de notifications et d’alertes.

## Stack technique

- PHP 8.4
- Symfony 8.1
- Twig
- Doctrine ORM / Doctrine Migrations
- Symfony Security
- Symfony Mailer
- Symfony Messenger
- PHPUnit
- Docker / Docker Compose
- MySQL 8.0
- phpMyAdmin
- MailHog

## Structure du projet

- app/ : application Symfony principale
- apache/ : configuration Apache
- mysql/ : données de la base MySQL locale
- php/ : image Docker PHP
- docker-compose.yaml : orchestration des services locaux

## Prérequis

Avant de démarrer, assurez-vous d’avoir installé :
- Docker
- Docker Compose
- Composer
- PHP 8.4 (si vous souhaitez exécuter Symfony localement sans conteneur)

## Démarrage rapide

### 1. Cloner le projet

```bash
git clone <url-du-projet>
cd job-candys
```

### 2. Démarrer les services Docker

```bash
docker compose up -d --build
```

### 3. Installer les dépendances Symfony

```bash
docker compose exec php composer install
```

### 4. Configurer la base de données

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

### 5. Accéder à l’application

- application : http://localhost:8080
- phpMyAdmin : http://localhost:8081
- MailHog : http://localhost:8025

## Variables d’environnement

Le projet utilise des fichiers dotenv dans app/.env et app/.env.dev.

Les variables principales à vérifier sont :
- APP_ENV
- APP_SECRET
- DATABASE_URL
- MAILER_DSN
- MESSENGER_TRANSPORT_DSN

> Note importante : la configuration Docker du projet est pensée autour de MySQL, tandis que la configuration Symfony par défaut pointe vers PostgreSQL. Il est donc conseillé d’ajuster DATABASE_URL selon votre environnement de travail.

## Développement local

Depuis le dossier de l’application Symfony :

```bash
cd app
php bin/console server:run
```

Ou via Docker :

```bash
docker compose exec php php bin/console cache:clear
```

## Tests

```bash
cd app
php bin/phpunit
```

## Déploiement et évolution prévue

Le projet est déjà bien avancé pour une application de recrutement et de mise en relation. Les prochaines évolutions naturelles, cohérentes avec la configuration actuelle, sont :
- mise en place complète du workflow d’inscription/connexion ;
- validation avancée des formulaires de candidature ;
- gestion des rôles administrateur et utilisateur ;
- intégration de l’emailing réel pour les confirmations et réinitialisations ;
- ajout de notifications et de files de traitement asynchrones ;
- extension du modèle métier avec entreprises, profils, offres et suivi des candidatures.

## Notes de contribution

Pour contribuer au projet :
1. créer une branche dédiée ;
2. implémenter les changements ;
3. exécuter les tests ;
4. soumettre une pull request avec une description claire.

---

Ce README est volontairement orienté développement et préparation du projet, afin de refléter à la fois l’état actuel du code et les fonctionnalités attendues selon la configuration Symfony et Docker déjà en place.
