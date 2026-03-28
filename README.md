<div align="center">

# ˗ˏˋ monark ˎˊ˗

**Hub développeur — monitoring de projets, dépendances et vulnérabilités.**<br>
Un dashboard. Tous tes projets. Zéro angle mort.

<br>

[![Symfony](https://img.shields.io/badge/Symfony-8.0-7aa2f7?style=for-the-badge&logo=symfony&logoColor=white)](https://symfony.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-7dcfff?style=for-the-badge&logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-bb9af7?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![RabbitMQ](https://img.shields.io/badge/RabbitMQ-4-f7768e?style=for-the-badge&logo=rabbitmq&logoColor=white)](https://www.rabbitmq.com)

<br>

[Features](#-features) · [Architecture](#-architecture) · [Stack](#-stack) · [Installation](#-installation) · [Commandes](#-commandes)

</div>

<br>

## 🧰 Features

**Catalog** — Importe tes projets depuis GitHub et GitLab. Détection automatique du tech stack (langages, frameworks, versions). Suivi des merge requests et métadonnées.

**Dépendances** — Scan Composer, npm et pip. Inventaire avec versions courantes, latest et LTS. Classification runtime vs dev. Détection automatique des dépendances obsolètes.

**Vulnérabilités** — Scan CVE avec classification par sévérité. Suivi du statut (open, acknowledged, fixed, ignored). Zéro surprise en prod.

**Dashboard** — Stats agrégées, widgets configurables par utilisateur, journal d'activité chronologique avec filtres. Notifications in-app et email.

**Temps réel** — Mercure pour les push notifications. RabbitMQ + Symfony Messenger pour le traitement asynchrone.

<br>

## 💡 Architecture

> Quatre bounded contexts, des frontières strictes, et Deptrac pour s'assurer que personne ne triche.

- **Identity** — Authentification JWT, profils, gestion des tokens d'accès providers.
- **Catalog** — Projets, repos externes, détection de stack, sync metadata.
- **Dependency** — Inventaire des dépendances, versions LTS, scan CVE.
- **Activity** — Dashboard, journal d'activité, notifications.

Le backend suit DDD/CQRS. Chaque contexte a sa propre couche Domain, Application, Infrastructure et Presentation. Le frontend Vue.js reflète exactement la même structure par module.

<details>
<summary><b>Voir la structure complète</b></summary>

<br>

```
monark/
├── backend/
│   └── src/
│       ├── Identity/           # Auth, profils, tokens
│       ├── Catalog/            # Projets, tech stacks
│       ├── Dependency/         # Dépendances, CVE
│       ├── Activity/           # Dashboard, notifications
│       └── Shared/             # Cross-cutting
│           Each context:
│           ├── Domain/         # Entités, value objects, ports
│           ├── Application/    # Commands, queries, DTOs
│           ├── Infrastructure/ # Repositories, adapters
│           └── Presentation/   # Controllers API
│
├── frontend/src/
│   ├── identity/               # Auth & profils
│   ├── catalog/                # Gestion projets
│   ├── dependency/             # Suivi dépendances
│   ├── activity/               # Dashboard
│   ├── shared/                 # Composants, layouts, i18n
│   └── app/                    # Router, entry point
│
└── docker/                     # Compose, Dockerfiles
```

</details>

<br>

## ⚙️ Stack

| Couche | Technologie |
|--------|-------------|
| **Backend** | Symfony 8.0, PHP 8.4 |
| **Frontend** | Vue.js 3, TypeScript, Tailwind CSS 4 |
| **Base de données** | PostgreSQL 17 |
| **Messaging** | RabbitMQ 4 + Symfony Messenger |
| **Temps réel** | Mercure |
| **Tests** | Pest (PHP), Vitest (TS) |
| **Qualité** | PHPStan, PHP-CS-Fixer, ESLint, Prettier |
| **Mutation testing** | Infection |
| **Architecture** | Deptrac (isolation des bounded contexts) |
| **Infra** | Docker Compose, Make |

<br>

## 🚀 Installation

> [!NOTE]
> Tout passe par Docker et Make. Aucune dépendance à installer en local.

```bash
git clone git@github.com:keyxmare/monark.git
cd monark
make doctor    # Vérifie les prérequis
make install   # Build, start, install deps, migrate DB
```

C'est tout. L'application tourne sur `localhost:3000` (frontend) et `localhost:8000` (API).

<br>

## 🔧 Commandes

```bash
# Dev quotidien
make up              # Démarrer les containers
make down            # Stopper les containers
make logs            # Suivre les logs
make shell-backend   # Shell dans le container PHP
make shell-frontend  # Shell dans le container Node

# Qualité
make test            # Tests backend + frontend
make lint            # Lint complet
make quality         # Lint + tests
make ci              # CI dashboard (lint, tests, coverage, mutation)

# Base de données
make migrate         # Appliquer les migrations
make seed            # Charger les fixtures

# Audit
make outdated        # Dépendances obsolètes
make audit           # Audit de sécurité
```

<br>

---

<div align="center">

[MIT](LICENSE) · Fait avec Symfony et du café

</div>
