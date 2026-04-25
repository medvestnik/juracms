# Jura CMS

Jura CMS is a lightweight CMS focused on a **classic installation flow** and a modern, clean admin UI.

## Core idea

Jura CMS intentionally keeps `index.php` in the repository root (not in `public/`) to match the привычный сценарий установки классических CMS: upload files to hosting, open the site, run installer.

## Current MVP structure

```text
/
├── index.php
├── composer.json
├── app/
│   ├── Core/
│   │   ├── Asset.php
│   │   ├── Theme.php
│   │   └── View.php
│   └── helpers.php
├── bootstrap/
│   └── app.php
├── config/
│   └── ui.php
├── themes/
│   ├── admin/
│   │   └── jura/
│   │       ├── theme.json
│   │       ├── layouts/
│   │       ├── components/
│   │       └── views/
│   └── frontend/
│       └── default/
│           ├── theme.json
│           ├── layouts/
│           └── views/
└── public/
    └── assets/
        ├── jura-ui/
        └── cms/
```

## Jura CMS + Jura UI

- **Jura UI** is used as the default visual layer for:
  - installer,
  - admin panel,
  - default frontend theme.
- Jura UI is connected through assets/config and themes; it is **not hardcoded** as an unreplaceable kernel dependency.

## MVP routes

- `GET /` — public home page.
- `GET /admin` — admin dashboard placeholder.
- `GET /admin/login` — login page UI placeholder.
- `GET /admin/pages` — pages section placeholder.
- `GET /admin/media` — media section placeholder.
- `GET /admin/settings` — settings section placeholder.
- `GET /install` — installer wizard placeholder.

## Installation notes

For a regular installation, **npm/Vite/build tools are not required**.

Current MVP uses plain PHP includes + static CSS/JS under `public/assets`.

## Next stages

1. Implement real authentication and session policies.
2. Implement installer checks + database setup flow.
3. Add repositories/services for pages and media.
4. Add theme activation and settings persistence.
5. Add modules API with safe loading rules.
6. Add tests and deployment smoke checks.
