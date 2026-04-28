# Jura CMS

Jura CMS keeps a classic CMS bootstrap flow with a modern core architecture.

## Key decisions

- `bootstrap/` is not used.
- App entrypoint is `core/start.php`.
- Root `index.php` includes:
  - `vendor/autoload.php`
  - `core/start.php`
- Explicit installer lives in `/install/`.
- Installation lock file: `storage/installed.lock`.
- Built-in content editor by default: **simple-js-editor**.

## Install flow

- On first launch, if `storage/installed.lock` does not exist, CMS redirects from `/` to `/install/`.
- Installer at `/install/` can be opened directly and works as a standalone entry point.
- MVP installer creates `storage/installed.lock`, prepares runtime directories, and blocks repeated installation.
- After completion, remove or disable `/install/` for production security.

## Update flow

- **Automatic**: Admin → System → Updates.
- **Manual**: upload new files, then finalize update from admin panel if `VERSION` is newer than installed version.

## Default editor assets

- `public/assets/admin/editor/simple-js-editor.js`
- `public/assets/admin/editor/simple-js-editor.css`
