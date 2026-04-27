# Jura CMS upgrade guide

## Automatic update (Admin → System → Updates)

1. Click **Проверить обновления**.
2. CMS fetches update manifest.
3. Compares `VERSION` with installed version from DB/session.
4. Downloads ZIP package.
5. Verifies checksum.
6. Extracts package into `storage/updates/`.
7. Applies files and runs migrations.
8. Clears cache and logs result.

## Manual update

1. Upload a new release archive over current files.
2. Login as administrator.
3. If `VERSION` is newer than installed version, CMS shows **Требуется завершить обновление**.
4. Click **Запустить обновление**.
5. CMS runs migrations, clears cache, and updates `installed_version`.

## Important: keep local data

During manual update, do **not** overwrite:

- `.env`
- `uploads/`
- `storage/`
- `cache/`
- `logs/`
