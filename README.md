voici les étapes à suivre pour ce miniprojet

## build containers and up 

docker compose up -d --build

## entrer dans le conatainer 
docker compose exec server bash
## installer les dépendances 
composer install
## créer le fichier .env.local.php
composer dump-env dev
## créer la base de données 
php bin/console doctrine:database:create
## pour créer les tables dans mysql
php bin/console d:m:m 
## j'ai installer aussi phpCsFixer pour fixer mon code 

