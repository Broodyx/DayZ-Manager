# DayZ Manager

Webová aplikace pro správu, validaci a úpravu DayZ serverových konfigurací. První fáze podporuje správu projektů, importů a revizí, základní detekci platformy, bezpečnou XML validaci a administrační rozhraní Filament.

## Požadavky

- Docker Engine 24+ s Docker Compose v2
- Git

Lokální PHP, Composer, PostgreSQL ani Redis nejsou potřeba.

## Lokální spuštění

1. Zkopírujte `.env.example` jako `.env`.
2. Nastavte alespoň `APP_KEY`, `DB_PASSWORD` a případně `APP_URL`.
3. Vygenerujte klíč jednorázovým kontejnerem:

   ```bash
   docker run --rm php:8.4-cli-alpine php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
   ```

   Výsledek vložte do `APP_KEY`. Aplikace klíč při startu záměrně negeneruje.

4. Spusťte prostředí:

   ```bash
   docker compose up --build -d
   docker compose ps
   curl http://localhost:8080/up
   ```

   Health endpoint vrací `{"status":"ok"}`.

5. Vytvořte prvního administrátora:

   ```bash
   docker compose exec app php artisan make:filament-user
   ```

6. Aplikace je na `http://localhost:8080`, administrace na `/admin`.

Migrace se při startu služby `app` spouští přes `php artisan migrate --force`. Konfigurace, routy a views se cacheují. Worker a scheduler běží v samostatných službách.

## Testy a kontrola

```bash
docker compose exec app php artisan test
docker compose exec app sh -c "find app config database routes tests -name '*.php' -exec php -l {} \;"
docker compose config
```

CI provádí syntax check, migrace a testy na PHP 8.4 s PostgreSQL a Redis.

## Úložiště a upload

- `postgres_data`: databázová data
- `redis_data`: persistentní Redis AOF
- `dayz_storage`: importované/exportované DayZ soubory v `storage/app/dayz`

Upload přijímá pouze XML, JSON a ZIP, kontroluje příponu i MIME, limit velikosti, ukládá mimo `public` pod náhodným názvem a počítá SHA-256. ZIP extractor odmítá cesty vedoucí mimo cílový adresář a jiné než datové soubory.

## Git workflow

Vývoj probíhá ve větvi `develop`:

`develop` → pull request → `main` → automatický deploy v Coolify

Produkční změny neposílejte přímo do `main`. Zapněte branch protection, povinný pull request a CI kontrolu pro `main`.

## Nasazení přes Coolify

1. V Coolify vytvořte nový Resource a zvolte **Private Repository with GitHub App**.
2. Vyberte repozitář přesně `dayZ-manager`.
3. Jako produkční větev nastavte `main`.
4. Zvolte build pack **Docker Compose**.
5. Compose file nastavte na `docker-compose.yml`.
6. Zapněte **Auto Deploy**.
7. Nastavte doménu pro službu `app` a interní port `8080`.
8. Nastavte healthcheck na `/up`.
9. Ověřte persistentní volumes pro PostgreSQL, Redis a `dayz_storage`.
10. Nastavte následující proměnné a spusťte první deploy.

### Povinné proměnné

| Proměnná | Produkční hodnota / význam |
|---|---|
| `APP_NAME` | `DayZ Manager` |
| `APP_ENV` | `production` |
| `APP_KEY` | bezpečný Laravel klíč `base64:...` |
| `APP_DEBUG` | `false` |
| `APP_URL` | veřejná HTTPS URL |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `database` |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | název databáze |
| `DB_USERNAME` | databázový uživatel |
| `DB_PASSWORD` | dlouhé náhodné heslo |
| `REDIS_HOST` | `redis` |
| `REDIS_PASSWORD` | volitelné; nastavte stejné pro aplikaci i Redis |
| `CACHE_STORE` | `redis` |
| `QUEUE_CONNECTION` | `redis` |
| `SESSION_DRIVER` | `database` |
| `FILESYSTEM_DISK` | `local` |
| `MAX_UPLOAD_SIZE` | maximum v KB, např. `10240` |

Tajné hodnoty neukládejte do Git repozitáře. V Coolify je označte jako secrets. Po deployi ověřte stav všech pěti služeb, `/up`, migrace v logu `app` a přihlášení do `/admin`.

## PWA

Projekt obsahuje základní manifest, service worker a offline fallback. Jde o instalační základ; offline editace dat zatím není implementovaná.
