Voici le README complet en format Markdown (.md), prêt à être copié-collé tel quel dans ton fichier README.md ✨

⸻


# 🐹 Hamster Ranch – API REST (Symfony 7)

API de gestion d’élevage de hamsters développée avec **Symfony 7**, sécurisée par **JWT**, et incluant plusieurs mécaniques de jeu :

- Gestion des utilisateurs  
- 4 hamsters générés automatiquement à l’inscription  
- Vieillissement automatique  
- Nourrissage, vente, reproduction, sommeil  
- Gestion du gold  
- Fin de jeu si gold < 0  

---

# 1. 🚀 Installation

## 1.1 Cloner le projet

```bash
git clone <URL_DU_REPO_GIT>
cd <nom_du_projet>


⸻

2. 📦 Installation des dépendances

composer install


⸻

3. ⚙️ Configuration de l’environnement (.env.local)

Créer le fichier :

touch .env.local

Puis copier-coller ceci :

###> symfony/framework-bundle ###
APP_SECRET=943054975b3c3c1ccc8ba876af6cdb32

# MODIFIER avec VOS identifiants MySQL
# Exemple : mysql://utilisateur:motdepasse@127.0.0.1:3306/hamsterapi
DATABASE_URL="mysql://root:root@127.0.0.1:8889/hamsterapi?charset=utf8mb4"
###< symfony/framework-bundle ###

# Clés JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem

# Passphrase pour les clés JWT (à modifier selon votre projet)
JWT_PASSPHRASE=seif

⚠️ IMPORTANT :
	•	Change DATABASE_URL avec tes vraies infos MySQL
	•	Change JWT_PASSPHRASE si tu veux une autre passphrase

⸻

4. 🔐 Génération des clés JWT

Créer le dossier :

mkdir -p config/jwt

Générer la clé privée (mot de passe = valeur de JWT_PASSPHRASE, ici : seif) :

openssl genrsa -out config/jwt/private.pem -aes256 4096

Générer la clé publique :

openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem


⸻

5. 🗄️ Base de données

5.1 Créer la base

php bin/console doctrine:database:create

5.2 Exécuter les migrations

php bin/console doctrine:migrations:migrate

5.3 Charger les fixtures

php bin/console doctrine:fixtures:load

Cela crée :

👤 Utilisateur normal

{
    "email": "user@sf.com",
    "password": "password"
}

👨‍💼 Administrateur

{
    "email": "admin@sf.com",
    "password": "admin"
}

Chaque utilisateur commence avec :
	•	500 gold
	•	4 hamsters (2 mâles, 2 femelles)

⸻

6. ▶️ Lancer l’API

symfony server:start

API accessible sur :

http://127.0.0.1:8000


⸻

7. 🔐 Authentification JWT

7.1 Inscription (public)

POST /api/register

Body :

{
  "email": "exemple@gmail.com",
  "password": "motdepasse"
}

Retour :
	•	Utilisateur créé
	•	500 gold
	•	4 hamsters automatiques

⸻

7.2 Connexion (public)

POST /api/login

Body :

{
  "email": "user@sf.com",
  "password": "password"
}

Réponse :

{
  "token": "JWT_ICI"
}


⸻

7.3 Accès aux routes sécurisées

Ajouter dans Postman ou tout client HTTP :

Authorization: Bearer VOTRE_TOKEN_JWT


⸻

8. 🐹 Routes API

👤 Utilisateurs

Méthode	Route	Description
POST	/api/register	Inscription + hamsters
POST	/api/login	Token JWT
GET	/api/user	Informations utilisateur
DELETE	/api/delete/{id}	Suppression user (admin)


⸻

🐹 Hamsters

Méthode	Route	Description
GET	/api/hamsters	Liste les hamsters
GET	/api/hamsters/{id}	Voir un hamster
POST	/api/hamsters/{id}/feed	Nourrir
POST	/api/hamsters/{id}/sell	Vendre (+300 gold)
POST	/api/hamsters/reproduce	Reproduction
POST	/api/hamster/sleep/{nbDays}	Tous dorment
PUT	/api/hamsters/{id}/rename	Renommer


⸻

9. ⏳ Vieillissement Automatique

Après chaque action réussie (feed, sell, reproduce) :
	•	Tous les hamsters de l’utilisateur gagnent +5 jours
	•	Et perdent -5 hunger
	•	Un hamster devient inactif si :
	•	age > 500
	•	hunger < 0

⸻

10. ❌ Fin de jeu – Solde < 0

Si le joueur non admin tombe à moins de 0 gold :

Toutes les actions du jeu renvoient :

{
  "error": "Fin de jeu : votre solde de gold est inférieur à 0"
}

Code HTTP : 400 BAD_REQUEST

L’administrateur n’est pas concerné.

⸻

11. 🧪 Tests Postman – Workflow
	1.	POST /api/register
	2.	POST /api/login
	3.	Copier le token JWT
	4.	Ajouter :
Authorization: Bearer <token>
	5.	Tester :
	•	/api/user
	•	/api/hamsters
	•	/api/hamsters/{id}/feed
	•	/api/hamsters/reproduce
	•	/api/hamsters/{id}/sell
	•	/api/hamster/sleep/3

⸻