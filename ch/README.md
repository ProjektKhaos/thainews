# Cosmos House

**Status 12 oktober 2026:** Administration V1 är driftsatt på **https://cosmoshouse.online/admin.php** med privat SQLite-databas, personalroller, innehållsredigering och automatiska driftjobb. Den tillfälliga engelska landningssidan visas på **https://cosmoshouse.online/** och **https://thainews.aberg.online/ch/**. Publika bokningsförfrågningar är fortfarande stängda.

## Administration

Personalen kan hantera bokningar, reservationer, ombokning, betalstatus, gästkontakt, interna noteringar, datumblock, månad-/veckokalender, priser, rabatter, avgifter, textutkast, publicering, foton och mejlstatus. Administratörer kan även skapa personliga konton, ändra husinställningar och SMTP, skapa/ladda ned backup samt dokumentera lanseringsbeslut.

Ägaren skapar det första kontot via en privat engångslänk och väljer själv användarnamn och lösenord. Inga standardlösenord har installerats. Se [personalhandboken](docs/ADMIN_MANUAL.md) och [aktuell driftsdokumentation](docs/BACKEND_DEPLOY.md).

## Drift och verksamhetsinställningar

Databasen ligger i `/var/lib/cosmos-house/cosmos.sqlite`, uppladdade bilder i dess privata `media/`-katalog och backup i `backups/`. PHP läser `/etc/cosmos-house/runtime.php` när en explicit `COSMOS_DB_PATH` inte redan är satt. Miljövariabler kan användas vid flytt eller isolerade tester; lagring ska alltid ligga utanför projekt och DocumentRoot.

`cosmos-house-worker.timer` kör reservationsexpiry och mejlkö varje minut. `cosmos-house-backup.timer` kör verifierad databas-/bildbackup kl. 03:15 Bangkoktid varje natt. Äldre JSON-lagring bevaras; den hade inga ärenden vid driftbytet.

Personalen fyller i verklig kapacitet, priser, kontakt, villkor och integritetstext genom administrationen. SMTP kan konfigureras och testas där. Inga skarpa mejl skickas förrän real leverans uttryckligen aktiverats. Offentlig bokning kräver separat full sajtlansering, serverflagga, databasflagga, verksamhetsgodkännanden och godkänd mejlkonfiguration. Ingen onlinebetalning eller extern OTA-synk ingår i V1.

## Webbplats och källor

- `index.html` är mallen för landningssidan. `public.php` renderar den med publicerad CMS-text och fotoval. Apache prioriterar denna sida vid `/` och `/index.html`.
- `admin.php`, `app/Management.php`, `app/Auth.php`, `app/views/` och `assets/admin.*` är administrationen.
- `app/Booking.php` äger priser, tillgänglighet, bokningar och transaktionsskydd; `db/migrations/` innehåller versionerade migrationer.
- `app/Cms.php`, `media.php` och `app/Backup.php` hanterar text, bilder och full backup.
- `page-source.php`, `translations.php` och `scripts/build_languages.sh` bygger skyddade PHP-sidor. Det finns äldre previewfiler på tre språk; den engelska navigeringen och landningssidan erbjuder bara engelska. CMS publicerar engelska sidtexter utan ombygge.
- `booking_api.php`, `booking_widget.php` och `assets/booking.js` är gästens bokningsflöde bakom separata driftspärrar. Äldre `api.php` är spärrat.
- `assets/photos/` har de 38 ursprungliga husbilderna. Landningspaketets åtta bilder ligger i `photos/`.

Den skyddade förhandsvisningen nås efter personalinloggning eller den befintliga previewinloggningen. Husfakta och äldre exempeltexter ska granskas före full sajtlansering.

## Verifiering

```sh
php ch/tests/booking.php
python3 ch/tests/admin_http.py
python3 ch/tests/public_flow.py
python3 ch/tests/portability.py
python3 ch/tests/smtp.py
node ch/tests/browser.cjs
```

HTTP-/administrationstester använder isolerade databaser; SMTP-provet använder lokala TLS-fixtures. Det sista kommandot testar den befintliga skyddade live-previewn. Resultat och praktiska begränsningar finns i [testprotokollet](docs/TEST_REPORT.md). DNS och certifikat beskrivs i [landningssidans driftinformation](docs/LANDING_DEPLOY.md).
