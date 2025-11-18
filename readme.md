# 🐹 Hamster Ranch – API REST (Symfony 7)

API de gestion d’élevage de hamsters développée avec **Symfony 7**, sécurisée par **JWT**, et incluant plusieurs mécaniques de jeu :

- Gestion des utilisateurs  
- 4 hamsters générés automatiquement à l’inscription  
- Vieillissement automatique  
- Nourrissage, vente, reproduction, sommeil  
- Gestion du gold  
- Fin de jeu si gold < 0  

## 1. 🚀 Installation

### 1.1 Cloner le projet

```bash
git clone <URL_DU_REPO_GIT>
cd <nom_du_projet>
```

## 2. 📦 Installation des dépendances

```bash
composer install
```

## 3. ⚙️ Configuration de l’environnement (.env.local)

Créer le fichier :

```bash
touch .env.local
```

Puis copier-coller ceci :

```dotenv
###> symfony/framework-bundle ###
APP_SECRET=943054975b3c3c1ccc8ba876af6cdb32
DATABASE_URL="mysql://root:root@127.0.0.1:8889/hamsterapi?charset=utf8mb4"
###< symfony/framework-bundle ###

JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=seif
```

## 4. 🔐 Génération des clés JWT

```bash
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

## 5. 🗄️ Base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

## 6. ▶️ Lancer l’API

```bash
symfony server:start
```

## 7. 🔐 Authentification JWT

### Inscription

```http
POST /api/register
```

### Connexion

```http
POST /api/login
```

## 8. 🐹 Routes API

### Utilisateurs
- POST /api/register
- POST /api/login
- GET /api/user
- DELETE /api/delete/{id}

### Hamsters
- GET /api/hamsters
- GET /api/hamsters/{id}
- POST /api/hamsters/{id}/feed
- POST /api/hamsters/{id}/sell
- POST /api/hamsters/reproduce
- POST /api/hamster/sleep/{nbDays}
- PUT /api/hamsters/{id}/rename
