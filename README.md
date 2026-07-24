# DayZ Manager

Laravel 12 aplikace pro správu, validaci a úpravu DayZ serverových konfigurací. Projekt se lokálně i v Coolify nasazuje přes Docker Compose. Nixpacks se nepoužívá.

## Docker architektura

Compose spouští pět služeb ze souboru `/docker-compose.yml`:

| Služba | Úloha |
|---|---|
| `app` | PHP 8.4-FPM + Nginx, interní port `80`, migrace a HTTP healthcheck `/up` |
| `worker` | stejný image, `php artisan queue:work --sleep=2 --tries=3 --timeout=300` |
| `scheduler` | stejný image, `php artisan schedule:work` |
| `database` | PostgreSQL 17 Alpine s persistentním volume |
| `redis` | Redis 7 Alpine s AOF persistentním volume |

Volume `dayz_storage` je připojený do `storage/app/dayz`, mimo veřejný adresář aplikace. Worker ani scheduler nespouští webový server.

## Lokální vývoj

Požadavky: Docker Desktop nebo Docker Engine s Compose v2 a Git.

1. Zkopírujte `.env.example` do `.env`.
2. Nastavte bezpečné hodnoty `APP_KEY` a `DB_PASSWORD`. Pro spuštění testů uvnitř lokálního kontejneru nastavte `INSTALL_DEV_DEPENDENCIES=true`. V Coolify tuto proměnnou nepřidávejte nebo ji ponechte `false`. Aplikace `APP_KEY` nikdy automaticky negeneruje.
3. Pokud klíč ještě nemáte, můžete jej vytvořit jednorázově:

   ```bash
   docker run --rm php:8.4-cli-alpine php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
   ```

4. Sestavte a spusťte projekt:

   ```bash
   docker compose build
   docker compose up -d
   docker compose exec app php artisan migrate
   docker compose exec app php artisan test
   docker compose exec app php -v
   docker compose logs -f app
   ```

Aplikace je lokálně dostupná na `http://localhost:8080`, administrace na `/admin` a health endpoint na `http://localhost:8080/up`. Endpoint vrací HTTP 200:

```json
{"status":"ok"}
```

Prvního uživatele Filament administrace vytvoříte pomocí:

```bash
docker compose exec app php artisan make:filament-user
```

## Produkční start

Entrypoint před spuštěním:

1. ověří přítomnost `APP_KEY`,
2. vytvoří `storage/app/dayz` a pracovní Laravel adresáře,
3. nastaví oprávnění `storage` a `bootstrap/cache`,
4. počká na PostgreSQL a Redis,
5. vytvoří config, route a view cache.

Pouze režim `app` spustí `php artisan migrate --force` a následně PHP-FPM s Nginx. Režimy `worker` a `scheduler` spustí pouze svůj Artisan proces. Migrace tedy neběží v nekonečné smyčce ani souběžně ve všech službách.

## Nasazení v Coolify přes Docker Compose

V Coolify nastavte:

1. **New Resource**
2. **Private Repository with GitHub App**
3. **Repository:** `DayZ-Manager`
4. **Branch:** `develop` pro testovací nasazení
5. **Build Pack:** `Docker Compose`
6. **Compose file:** `/docker-compose.yml`
7. U služby `app` nastavte doménu aplikace.
8. Zapněte **Auto Deploy**.
9. Nastavte **healthcheck path:** `/up`.
10. Nastavte **interní port aplikace:** `80`.
11. Ověřte persistentní volumes `postgres_data`, `redis_data` a `dayz_storage`.
12. Nastavte environment variables a spusťte deploy.

Minimální produkční proměnné:

```dotenv
APP_NAME=DayZ Manager
APP_ENV=production
APP_DEBUG=false
APP_URL=https://zvolena-domena
APP_KEY=
DB_DATABASE=dayz_manager
DB_USERNAME=dayz_manager
DB_PASSWORD=
MAX_UPLOAD_SIZE=100M
```

`APP_KEY` a `DB_PASSWORD` doplňte jako Coolify secrets. Compose nastavuje následující síťové a Laravel hodnoty přímo:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=database
DB_PORT=5432
REDIS_HOST=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database
FILESYSTEM_DISK=local
```

`REDIS_PASSWORD` je volitelný. Pokud ho v Coolify nastavíte, stejná hodnota se automaticky předá aplikaci i Redis serveru. Bez něj Redis běží pouze uvnitř privátní Compose sítě bez hesla.

Volitelné PHP limity:

```dotenv
POST_MAX_SIZE=110M
PHP_MEMORY_LIMIT=512M
PHP_MAX_EXECUTION_TIME=300
APP_PORT=8080
INSTALL_DEV_DEPENDENCIES=false
TRUSTED_PROXIES=172.16.0.0/12
```

`APP_PORT` ovlivňuje pouze lokální publikovaný port. Coolify směruje doménu přímo na interní port `80`.

## Bezpečnost

- `.env` je v `.gitignore` a žádné secrets nejsou v repozitáři.
- Start aplikace odmítne chybějící `APP_KEY`; `key:generate` se automaticky nespouští.
- Importy se ukládají do `storage/app/dayz`, nikoli do `public`.
- Upload přijímá pouze XML, JSON a ZIP, kontroluje MIME i příponu a počítá SHA-256.
- ZIP extractor odmítá zip-slip cesty a jiné než povolené datové soubory.
- XML validátor zakazuje externí entity.
- Aplikace nepoužívá `eval` ani `shell_exec`.

## Kontroly

```bash
docker compose config
composer validate
php artisan test
docker compose build
docker compose up -d
curl http://localhost:8080/up
```

GitHub Actions kontroluje PHP syntaxi, migrace a testy na PHP 8.4 s PostgreSQL a Redis.

## Git workflow

Testovací nasazení používá `develop`. Produkční změny postupují:

`develop` → pull request → `main`

Do `main` se nemerguje ani neposílá přímo bez výslovného schválení.
