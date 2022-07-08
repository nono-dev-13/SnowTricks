# P6-Snowtrick

Projet 6 de mon parcours Développeur d'application PHP/Symfony chez OpenClassrooms.
Création d'un site/blog via Symfony.

## Installation
__Etape 1__ : Cloner ce repo et mettre les fichiers à la racine du projet
__Etape 2__ : Configurez vos variables d'environnement tel que la connexion à la base de données ou votre serveur SMTP ou adresse mail dans le fichier .env.local qui devra être crée à la racine du projet en réalisant une copie du fichier .env.
__Etape 3__ : `composer install`
__Etape 4__ : `npm install`
__Etape 5__ : `php bin/console doctrine:database:create`
__Etape 6__ : `php bin/console doctrine:migrations:migrate`

## Lancer les assets
__Etape 1__ : `npm run watch`

## Accès messagerie via maildev
__Etape 1__ : Installer maildev
__Etape 2__ : taper `maildev` dans le terminal
__Etape 3__ : aller dans le navigateur sur http://0.0.0.0:1080 pour avoir accès à la messagerie
