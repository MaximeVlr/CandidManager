# CandidManager

Application de gestion de candidatures spontanees.

## Stack

- Backend Symfony 8.1 / PHP 8.4
- Frontend Vue 3 / Vite / TypeScript
- PostgreSQL 16
- Nginx
- Worker Symfony Messenger
- Envoi email via Brevo

## Prerequis

- Docker
- Docker Compose

Aucune installation locale de PHP, Composer, Node.js ou PostgreSQL n'est necessaire : les services sont lances avec Docker.

## Installation avec Docker

Depuis la racine du projet, copier le fichier d'environnement d'exemple :

```bash
cp .env.example .env
```

Sur Windows PowerShell :

```powershell
Copy-Item .env.example .env
```

Construire et demarrer les conteneurs :

```bash
docker compose up --build
```

Lors du premier lancement, le conteneur backend installe automatiquement les dependances Composer si le dossier `backend/vendor` n'existe pas. Le conteneur frontend installe automatiquement les dependances npm avant de demarrer Vite.

Dans un deuxieme terminal, initialiser la base de donnees :

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate
docker compose exec backend php bin/console app:templates:create-default
```

L'application est ensuite disponible ici :

```text
Frontend : http://localhost:5173
API      : http://localhost:8080
Health   : http://localhost:8080/health
```

## Services Docker

Le fichier `docker-compose.yml` demarre les services suivants :

- `frontend` : interface Vue/Vite sur le port `5173`
- `nginx` : serveur HTTP de l'API sur le port `8080`
- `backend` : application Symfony / PHP-FPM
- `postgres` : base PostgreSQL sur le port `5432`
- `messenger-worker` : worker d'envoi asynchrone

Pour demarrer le projet en arriere-plan :

```bash
docker compose up --build -d
```

Pour arreter les conteneurs :

```bash
docker compose down
```

Pour arreter les conteneurs et supprimer les donnees PostgreSQL locales :

```bash
docker compose down -v
```

## Configuration email Brevo

La configuration se fait dans le fichier `.env` a la racine du projet :

```env
MAILER_DSN=brevo+smtp://USERNAME:PASSWORD@default
MAIL_FROM=ton-email-verifie@domaine.com
MAIL_FROM_NAME=CandidManager
MAIL_SEND_ENABLED=false
MAIL_MAX_BATCH_SIZE=20
MAIL_DRY_RUN=true
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

Par securite, l'envoi reel reste desactive tant que :

```env
MAIL_SEND_ENABLED=false
MAIL_DRY_RUN=true
```

Pour envoyer reellement via Brevo :

```env
MAIL_SEND_ENABLED=true
MAIL_DRY_RUN=false
```

## Commandes utiles

Executer les migrations :

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate
```

Creer les templates par defaut :

```bash
docker compose exec backend php bin/console app:templates:create-default
```

Lancer les tests backend :

```bash
docker compose run --rm backend php bin/phpunit
```

Compiler le frontend :

```bash
docker compose run --rm frontend npm run build
```

Consulter les logs :

```bash
docker compose logs -f
```

Consulter les logs d'un service :

```bash
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f messenger-worker
```

## API principale

Import JSON :

```text
POST http://localhost:8080/api/import
Content-Type: application/json
```

Format attendu :

```json
{
  "applications": [
    {
      "company": "Acme",
      "location": "Paris",
      "email": "jobs@acme.example",
      "subject": "Candidature PHP",
      "custom_message": "Bonjour",
      "official_source_url": "https://acme.example/jobs",
      "sent": false,
      "response": "none",
      "follow_up": false
    }
  ]
}
```

Listing des candidatures :

```text
GET http://localhost:8080/api/applications?search=acme&send_status=pending&sort=company&direction=asc&page=1&limit=25
```

Detail d'une candidature :

```text
GET http://localhost:8080/api/applications/{id}
```

Edition d'une candidature :

```text
PATCH http://localhost:8080/api/applications/{id}
Content-Type: application/json
```

Exports :

```text
GET http://localhost:8080/api/export.csv?search=acme&send_status=sent&limit=10000
GET http://localhost:8080/api/export.json?search=acme&send_status=sent&limit=10000
```

Templates :

```text
GET http://localhost:8080/api/templates
PUT http://localhost:8080/api/templates/default
```

Variables autorisees dans les templates :

```text
{{ company }}, {{ location }}, {{ email }}, {{ subject }}, {{ custom_message }}, {{ official_source_url }}
```
