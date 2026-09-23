# Systemdokumentation – Thai News

| Fält | Värde |
|---|---|
| System | Thai News |
| Dokumentversion | 1.0 |
| Dokumentdatum | 2026-09-23 |
| Produktionsadress | `https://thainews.aberg.online/` |
| Dokumentrot | `/var/www/abergonline/thainews` |
| Tidszon i applikationen | `Asia/Bangkok` |
| Tidszon i databas och loggar | UTC |
| Licens | Proprietär |

## 1. Syfte och omfattning

Thai News är en serverrenderad, responsiv nyhetsaggregator för engelskspråkig rapportering om Thailand. Systemet hämtar RSS- och Atom-flöden från elva nyhetskällor, normaliserar och lagrar artiklar, grupperar de fem senaste artiklarna per källa och låter varje anonym besökare välja språk, typografi, källordning, källsynlighet samt ljust eller mörkt tema.

Systemet omfattar:

- en publik webbapplikation i PHP;
- en JSON-API-endpoint för besökarinställningar;
- periodiska CLI-jobb för nyhetshämtning och valfri rubriköversättning;
- en MariaDB/MySQL-databas;
- PWA-funktioner med installation och offline-sida;
- automatiserade enhets-, integrations-, webbläsar- och tillgänglighetstester;
- Apache-konfiguration, cron-definitioner och installationshjälpmedel.

Systemet publicerar inte hela artiklar. Rubriker och korta utdrag länkar vidare till respektive utgivare. Det finns ingen inloggning, redaktionell publiceringsvy, prenumeration eller nyhetsbrevsfunktion i den aktuella kodbasen.

## 2. Övergripande arkitektur

```text
Nyhetsutgivare / Google News RSS
              |
              | HTTPS, RSS/Atom
              v
 cron/fetch_news.php --> HttpClient --> adapter --> RssAtomParser
              |                                  |
              |                         normaliserade artiklar
              v                                  v
       FetchService ----------------------> MariaDB
                                                 ^
                                                 |
 Webbläsare --> Apache --> PHP-sidor/API --> repositories
      |                         |
      |                         +--> HTML, JSON, cookies, session
      |
      +--> service worker --> statisk cache och offline.html

 cron/translate_news.php --> Google Cloud Translation v2 (valfritt)
              |                         |
              +-----------------------> MariaDB-cache
```

Arkitekturen är en traditionell monolit utan separat applikationsserver eller byggsteg vid varje webbförfrågan. Apache exponerar endast publika filer och kör PHP. Applikationsklasser laddas via Composer PSR-4. `includes/bootstrap.php` skapar konfiguration, databasanslutning, loggare, session, besökaridentitet, inställningsrepository, översättare och säkerhetsrubriker för varje webbförfrågan.

JavaScript används som progressiv förbättring. Grundläggande inställningar, inklusive flyttning av källor uppåt och nedåt, fungerar även utan JavaScript.

## 3. Produktionsmiljö – verifierad ögonblicksbild

Följande observerades på servern 2026-09-23. Versionsnummer och datamängder är en ögonblicksbild och förändras över tid.

| Komponent | Version/status |
|---|---|
| Operativsystemsmiljö | Linux/Ubuntu |
| PHP CLI | 8.3.6 NTS |
| Apache | 2.4.58 |
| MariaDB | 10.11.14 |
| Composer | 2.7.1 |
| Node.js | 18.19.1 |
| npm | 9.2.0 |
| Apache-tjänst | aktiv och aktiverad |
| MariaDB-tjänst | aktiv och aktiverad |
| cron-tjänst | aktiv och aktiverad |
| Apache-konfiguration | syntaxkontroll godkänd |
| Översättning | avstängd; ingen API-nyckel konfigurerad |

Databasens ögonblicksbild innehöll 11 källor, 1 366 artiklar, 388 hämtningskörningar och 178 anonyma inställningsprofiler. Senaste kontrollerade hämtning avslutades `success` 2026-09-23 03:30:09 UTC med 645 mottagna poster, 7 infogade, 638 uppdaterade och 0 avvisade.

## 4. Teknik och beroenden

### 4.1 Serverkrav

- PHP 8.3 eller senare.
- PHP-tillägg: cURL, DOM/XML, Intl, JSON, Mbstring och PDO MySQL.
- MariaDB eller MySQL med InnoDB och `utf8mb4`.
- Apache 2.4 med moduler för SSL, headers och rewrite, eller motsvarande webbserverregler.
- Composer 2.
- Cron eller annan schemaläggare för bakgrundsjobben.
- Utgående HTTPS till nyhetsflöden och, om funktionen aktiveras, Google Cloud Translation.

### 4.2 PHP- och frontendberoenden

Produktionskoden har inga externa PHP-paket utöver Composer-autoloaden och plattformskraven. Utvecklingsberoendet är PHPUnit `^12.3`.

Frontend använder lokalt lagrade tillgångar:

- SortableJS 1.15.7 för drag-och-släpp i källistan;
- Inter och Noto Sans Thai som lokala webbfonter;
- ingen extern JavaScript-CDN;
- Playwright 1.55.1 och axe-core 4.11.1 endast för tester.

## 5. Katalog- och filstruktur

| Sökväg | Ansvar |
|---|---|
| `index.php` | Publik startsida; hämtar fem senaste artiklar per synlig källa. |
| `settings.php` | Inställningssida och formulärhantering med CSRF-skydd. |
| `api/preferences.php` | JSON-API för läsning och lagring av källordning/synlighet. |
| `app/` | Domän- och infrastrukturlager under namnrymden `ThaiNews`. |
| `app/News/` | Flödeshämtning, parser, adaptrar, normalisering och artikellagring. |
| `app/Preferences/` | Anonym identitet och persistenta besökarinställningar. |
| `app/Security/` | CSRF, HTTP-säkerhetsrubriker och rate limiting. |
| `app/Translation/` | Google-adapter, översättningskö, cache och felåterförsök. |
| `app/lang/` | Kompletta språkordlistor för engelska, svenska och thai. |
| `includes/bootstrap.php` | Gemensam initiering för webb och CLI. |
| `includes/helpers.php` | URL-, escaping-, översättnings- och datumhjälpare. |
| `includes/views/` | Sidhuvud, sidfot och artikelkort. |
| `cron/` | CLI-entrypoints för hämtning och översättning. |
| `sql/schema.sql` | Idempotent grundschema. |
| `sql/seed.sql` | Idempotent seed för elva nyhetskällor. |
| `sql/migrations/` | Separata kompletterande migreringar. |
| `assets/` | CSS, JavaScript, lokala fonter och vendorerad SortableJS. |
| `img/` | Logotyper, social bild, flaggor och PWA-ikoner. |
| `storage/cache/` | Runtime-cache, bland annat PHPUnit-cache. |
| `storage/locks/` | Icke-blockerande processlås för cron-jobb. |
| `storage/logs/` | JSON-applikationsloggar och omdirigerad cron-utdata. |
| `deploy/apache/` | Versionshanterade Apache-exempel. |
| `deploy/cron/` | Versionshanterad cron-definition. |
| `scripts/` | Produktionsinstallation och generering av PWA-ikoner. |
| `tests/` | Enhets-, integrations-, browser- och tillgänglighetstester. |
| `docs/` | Historiska bygg-, käll-, UI- och driftanteckningar. |
| `manifest.webmanifest`, `sw.js`, `offline.html` | PWA-manifest, service worker och offline-sida. |
| `vendor/` | Composer-autoload och installerade PHP-artefakter. |
| `node_modules/` | Lokala Node-beroenden för assets och tester. |

`app`, `includes`, `cron`, `sql`, `storage`, `tests`, `vendor`, `docs`, `node_modules`, `scripts` och `deploy` ska aldrig kunna läsas via webben. Produktions-vhosten har explicita `LocationMatch`- och `FilesMatch`-förbud. Katalogernas egna `.htaccess`-filer ger ett extra skydd när `AllowOverride` används, men produktionskonfigurationen använder `AllowOverride None` och förlitar sig på vhost-reglerna.

## 6. Applikationskomponenter

### 6.1 Bootstrap och gemensam infrastruktur

- `Config` läser endast den fil som anges av `THAI_NEWS_CONFIG_FILE`. Saknad/oläsbar fil stoppar uppstarten. Hemligheterna `app_secret` och `rate_limit_secret` måste vara minst 32 byte. Produktion kräver HTTPS i `public_origin`.
- `Database` öppnar PDO med exceptions, associativa resultat och riktiga prepared statements. Anslutningen sätts till UTC och `utf8mb4_unicode_ci`.
- `Logger` skriver en JSON-rad per händelse, använder fillås och maskerar kontextfält med namn som password, pass, token, secret och authorization.
- `Translator` laddar en PHP-ordlista för valt UI-språk och faller tillbaka till engelska.
- `Metadata` bygger canonical-, Open Graph- och Twitter-metadata med absolut bild-URL.
- `View` är en liten generell PHP-template-renderare; de aktuella entrypoint-filerna inkluderar huvudsakligen vyerna direkt.

### 6.2 Nyhetsinhämtning

- `HttpClient` accepterar endast HTTPS för flöden, validerar TLS, följer högst tre HTTPS-omdirigeringar och begränsar svarsstorleken till konfigurerat `max_bytes`.
- `AdapterRegistry` mappar `adapter_type` till implementation.
- `BangkokPostAdapter` hanterar Bangkok Posts flöde.
- `GenericRssAdapter` hanterar generiska RSS/Atom-flöden och provar konfigurerade fallback-URL:er i ordning.
- `RssAtomParser` stöder RSS och Atom, blockerar DTD/entity-deklarationer och använder `LIBXML_NONET`. Titel, länk, guid/id, utdrag, publiceringstid och valfri HTTPS-bild extraheras.
- `UrlNormalizer` tar bort fragment och vanliga spårningsparametrar, sorterar query-parametrar och normaliserar schema/värd.
- `FetchService` isolerar fel per källa och per artikel, samlar statistik och registrerar övergripande samt källspecifika körningar.
- `NewsRepository` läser källor, gör idempotent artikel-upsert och levererar senaste artiklar med giltig översatt rubrik när sådan finns.

Artiklar dedupliceras både på `(source_id, external_id)` och `(source_id, url_hash)`. En återkommande artikel uppdaterar rubrik, URL, publiceringstid och `last_seen_at`; tomt utdrag eller tom bild skriver inte över tidigare innehåll.

### 6.3 Besökarinställningar

Besökaren får cookien `tn_visitor`, ett 32-byte slumpvärde i URL-säker Base64, med livslängd ett år, `HttpOnly`, `SameSite=Lax`, säker flagga under HTTPS och korrekt base path. Endast en HMAC-SHA256-hash av värdet lagras i databasen.

`PreferencesRepository` hanterar:

- UI-språk: `en`, `th` eller `sv`;
- typsnitt: `inter`, `system`, `serif` eller `mono`;
- textstorlek: `small`, `medium`, `large` eller `xlarge`;
- källornas ordning och synlighet.

Temaval och status för introduktionsguider sparas endast i webbläsarens `localStorage`. De synkroniseras inte mellan enheter.

### 6.4 Rubriköversättning

Översättning är valfri och avstängd i den verifierade produktionen. När den aktiveras:

1. arbetaren väljer aktuella artiklar vars källspråk skiljer sig från målspråket;
2. giltiga resultat i `translation_memory` återanvänds;
3. övriga titlar dedupliceras, grupperas per källspråk och skickas i batchar om högst 25 till Google Cloud Translation Basic v2;
4. lyckade resultat sparas både per artikel och i översättningsminnet;
5. fel sparas med anonymiserad felkod och nästa tillåtna försökstid;
6. en ändrad originalrubrik gör den gamla översättningen ogiltig genom jämförelse av SHA-256-hash.

Originalrubriken visas alltid som fallback. Utdrag översätts inte.

## 7. Webbrutter och gränssnitt

| Metod | Sökväg | Funktion | Viktiga svar |
|---|---|---|---|
| GET | `/` eller `/index.php` | Nyhetsöversikt per synlig källa. `?lang=en|th|sv` byter språk och sparar valet. | `200`, generisk `500` vid bootstrapfel. |
| GET | `/settings.php` | Visar besökarens inställningar. | `200`, `X-Robots-Tag: noindex, follow`. |
| POST | `/settings.php` | Sparar språk, typografi, ordning och synlighet. | `302` till `?saved=1`; `400` vid CSRF-fel; `422` vid lagringsfel. |
| GET | `/api/preferences.php` | Returnerar aktuella inställningar som JSON. | `200`. |
| POST | `/api/preferences.php` | Sparar språk och källlista som JSON. CSRF skickas i `X-CSRF-Token` eller body. | `200`, `403`, `422`, `429`. |
| Övrigt | `/api/preferences.php` | Metod ej tillåten. | `405`. |
| GET | `/manifest.webmanifest` | PWA-manifest. | Statisk fil. |
| GET | `/sw.js` | Service worker. | Statisk fil. |
| GET | `/offline.html` | Lokal offline-fallback. | Statisk fil. |

API:ts POST-rate limit är 30 försök per 600 sekunder och klientnyckeln är HMAC av route, IP-adress och de första 160 tecknen av User-Agent. API:t bör betraktas som ett internt gränssnitt för samma origin, inte som ett publikt versionshanterat tredjeparts-API.

## 8. UI, språk, tillgänglighet och PWA

Startsidan använder tre kolumner på desktop, två på surfplatta och en på mobil. Varje källkort visar högst fem artiklar. Synliga rubriker kortas till 34 Unicode-tecken och utdrag till 95; full rubrik finns i länkens `title` och `aria-label`.

UI-texter finns på engelska, svenska och thai. Tester kräver samma uppsättning nycklar i alla språkfiler. Datum formateras med `IntlDateFormatter` i vald lokal och applikationens Bangkok-tidszon.

PWA-funktionerna består av:

- manifest med `display: standalone`, ikoner och genvägar till startsida/inställningar;
- installationsprompt på kompatibla plattformar och instruktion för iOS;
- service worker med versionsstyrd app-shell-cache;
- network-first för navigation med `offline.html` som fallback;
- cache-first för lokala assets, bilder och manifest;
- borttagning av gamla cacheversioner vid aktivering.

Introduktionsguiderna har fem steg vardera på start- respektive inställningssidan. Slutförd/avbruten guide sparas i `localStorage`. Browsertester kör axe och tillåter inga allvarliga eller kritiska tillgänglighetsfel.

## 9. Datamodell

Alla tidsstämplar lagras i UTC. Tabellerna använder InnoDB och `utf8mb4_unicode_ci`.

| Tabell | Syfte | Viktiga nycklar/relationer |
|---|---|---|
| `schema_migrations` | Registrerar installerade schemaversioner. | `version` PK. |
| `news_sources` | Källnamn, URL:er, adapter, språk, ordning och JSON-konfiguration. | `id` PK, `slug` unik. |
| `articles` | Normaliserade artiklar och senaste observerade innehåll. | FK till källa; unik external ID och URL-hash per källa. |
| `article_translations` | Översatt rubrik, status, försök och retry-tid per artikel/språk. | FK till artikel; unik `(article_id, language)`. |
| `translation_memory` | Återanvändbara översättningar av identiska originalrubriker. | Unik käll-/målspråk-/texthash. |
| `fetch_runs` | Summering av varje hämtningskörning. | Status `running`, `success`, `partial` eller `failed`. |
| `source_fetch_runs` | Resultat per källa inom en hämtningskörning. | FK till körning och källa. |
| `user_preferences` | Språk och typografi per anonym besökarhash. | `visitor_hash` PK. |
| `user_source_preferences` | Ordning och synlighet per besökare/källa. | Sammansatt PK; FK till källa. |
| `api_rate_limits` | Tidsfönster och räknare för API-anrop. | Unik route/klienthash/fönster. |

Relationerna kan sammanfattas så här:

```text
news_sources 1 --- n articles 1 --- n article_translations
      |                 |
      |                 +--- rubriktext återanvänds via translation_memory
      |
      +--- n source_fetch_runs n --- 1 fetch_runs
      |
      +--- n user_source_preferences n --- 1 user_preferences
```

`sql/schema.sql` och `sql/seed.sql` är utformade för att kunna köras flera gånger. Seed-filen uppdaterar källdefinitioner men tar inte bort artiklar eller besökarinställningar.

## 10. Nyhetskällor

| Ordning | Källa | Strategi |
|---:|---|---|
| 1 | Bangkok Post | Officiellt RSS, separat adapter. |
| 2 | Thai PBS World | Google News RSS-kompatibilitetsflöde. |
| 3 | The Nation Thailand | Google News RSS-kompatibilitetsflöde. |
| 4 | Khaosod English | Utgivar-RSS med Google News som fallback. |
| 5 | The Thaiger | Utgivar-RSS med två fallback-flöden. |
| 6 | Chiang Mai CityNews | Utgivar-RSS med Google News som fallback. |
| 7 | Chiang Rai Times | Utgivar-RSS med Google News som fallback. |
| 8 | Pattaya Mail | Utgivar-RSS med Google News som fallback. |
| 9 | TAT Newsroom | Utgivar-RSS. |
| 10 | The Phuket News | Google News RSS-kompatibilitetsflöde. |
| 11 | ASEAN NOW | Google News RSS-kompatibilitetsflöde. |

En källas misslyckande avbryter inte övriga källor. Google News används som kompatibilitetslager där ett stabilt, direkt flöde saknas; länkarna leder vidare till originalutgivaren.

## 11. Konfiguration

Produktionskonfigurationen ligger utanför DocumentRoot i `/etc/thainews/config.php`, ägs av `root:www-data` och ska ha läsrättighet endast för dessa parter. Miljövariabeln `THAI_NEWS_CONFIG_FILE` måste sättas för både Apache/PHP och cron.

| Nyckel | Betydelse / verifierat produktionsvärde |
|---|---|
| `env` | `production`. |
| `public_origin` | `https://thainews.aberg.online`; måste vara HTTPS i produktion. |
| `base_url` | Tom sträng vid installation i domänroten. |
| `timezone` | `Asia/Bangkok`. |
| `default_language` | `en`. |
| `supported_languages` | `en`, `th`, `sv`. |
| `asset_version` | Cache-busting; verifierat `1.0.26`. Ska samordnas med service worker vid assetändring. |
| `app_secret` | Hemlig HMAC-nyckel, minst 32 byte; verifierad längd 64 utan att läsa in värdet i dokumentet. |
| `rate_limit_secret` | Separat hemlig HMAC-nyckel, minst 32 byte; verifierad längd 64. |
| `db.dsn` | PDO-DSN för MariaDB/MySQL med `utf8mb4`. |
| `db.user`, `db.pass` | Databaskonto med minsta nödvändiga rättigheter. |
| `fetch.user_agent` | Identifierar hämtaren och webbplatsen. |
| `fetch.connect_timeout` | Verifierat 5 sekunder. |
| `fetch.timeout` | Verifierat 20 sekunder. |
| `fetch.max_bytes` | Verifierat 2 097 152 byte per flödessvar. |
| `translation.enabled` | Aktiverar externa anrop; verifierat `false`. |
| `translation.provider` | `google_cloud_v2`. |
| `translation.api_key` | Hemlig Google API-nyckel; saknas i verifierad produktion. |
| `translation.connect_timeout` | Verifierat 5 sekunder. |
| `translation.timeout` | Verifierat 30 sekunder. |
| `translation.batch_limit` | Verifierat 50 artiklar per språk/körning. |
| `storage.cache/locks/logs` | Valfria absoluta överskrivningar; standard är under projektets `storage/`. |

Kopiera aldrig den riktiga konfigurationen till projektkatalogen, Git eller ett publikt dokument. `app/config.example.php` innehåller endast platshållare.

## 12. Schemalagda jobb

Installerad `/etc/cron.d/thainews` kör processerna som `www-data` med `umask 007`:

```cron
*/10 * * * * www-data ... php cron/fetch_news.php >> storage/logs/cron-fetch.log 2>&1
2,12,22,32,42,52 * * * * www-data ... php cron/translate_news.php >> storage/logs/cron-translate.log 2>&1
```

Nyhetshämtningen startar var tionde minut. Översättningen startar två minuter efter varje hämtningsfönster. Båda använder icke-blockerande exklusiva fillås; en överlappande körning hoppar över arbetet och returnerar normalt.

Manuell körning:

```bash
cd /var/www/abergonline/thainews
sudo -u www-data env THAI_NEWS_CONFIG_FILE=/etc/thainews/config.php \
  php cron/fetch_news.php --dry-run
sudo -u www-data env THAI_NEWS_CONFIG_FILE=/etc/thainews/config.php \
  php cron/fetch_news.php --source=bangkok-post
sudo -u www-data env THAI_NEWS_CONFIG_FILE=/etc/thainews/config.php \
  php cron/translate_news.php --language=sv --limit=50 --dry-run
```

Hämtaren returnerar exitkod 1 endast när alla valda källor misslyckas. Okänd/avstängd specificerad källa leder till ett bootstrap-/runtimefel. Översättaren returnerar 0 när funktionen är avstängd, 64 för ogiltigt språk och 1 när minst en översättning misslyckas.

## 13. Säkerhet och integritet

### 13.1 Implementerade skydd

- externa hemligheter utanför DocumentRoot;
- HTTPS och HSTS i Apache;
- CSP med nonce för inline-script, `frame-ancestors 'none'` och `object-src 'none'`;
- `X-Content-Type-Options`, `X-Frame-Options`, Referrer Policy och Permissions Policy;
- secure/HttpOnly/SameSite-session- och besökarkakor;
- CSRF-token på alla inställningsskrivningar;
- prepared statements och avstängda emulerade PDO-prepares;
- kontextuell HTML-escaping med `htmlspecialchars`;
- rate limiting på JSON-API:s skrivning;
- TLS-verifiering, protokollbegränsning, timeout och storleksgräns för externa anrop;
- blockering av XML DTD/entity och nätverksåtkomst från XML-parsern;
- externa artikellänkar med `noopener noreferrer external`;
- generiska felmeddelanden till besökaren och hashade felkoder i loggen;
- explicita webbserverförbud för intern kod, loggar, SQL, tester och beroenden.

### 13.2 Personuppgifter och lokalt tillstånd

Ingen registrerad användaridentitet används. Systemet behandlar dock IP-adress och User-Agent kortvarigt för att skapa en envägs-HMAC för rate limiting. En slumpmässig besökarcookie kopplas till inställningar via en separat envägs-HMAC. Databasen innehåller inte råvärdet från cookien. Tema och guidehistorik finns lokalt i webbläsaren.

Det finns ingen automatiserad gallring i nuvarande kod för gamla `user_preferences`, `api_rate_limits`, körhistorik, artiklar eller loggfiler. Driftansvarig bör besluta om och införa dokumenterade retentionstider.

### 13.3 Kända säkerhets-/driftrisker

- Artikel-URL:er får vara HTTP eller HTTPS, även om flöden alltid måste hämtas via HTTPS. Detta är ett medvetet kompatibilitetsbeteende men kan ge länkar till osäkra utgivarsidor.
- Automatisk loggrotation ingår inte i projektet.
- Rate-limit-tabellen rensas inte automatiskt.
- Arkiv av hela projektet innehåller Git-historik och runtime-loggar och ska därför hanteras som internt material även om den externa konfigurationsfilen inte ingår.
- Google News-kompatibilitetsflöden är ett externt beroende vars format och tillgänglighet kan förändras.

## 14. Loggning och övervakning

| Fil/område | Innehåll |
|---|---|
| `storage/logs/fetch.log` | JSON-rader för källresultat, avvisade artiklar och fel. |
| `storage/logs/translation.log` | Strukturerade översättningsfel när funktionen används. |
| `storage/logs/app.log` | Bootstrap- och ohanterade applikationsfel när sådana uppstår. |
| `storage/logs/cron-fetch.log` | JSON-summary/stdout och stderr från cron. |
| `storage/logs/cron-translate.log` | Stdout/stderr från översättningscron. |
| Apache access/error log | HTTP-trafik och PHP-/serverfel enligt vhosten. |
| `fetch_runs`, `source_fetch_runs` | Sökbar historik och volym per körning/källa. |

Snabba driftkontroller:

```bash
systemctl is-active apache2 mariadb cron
sudo apache2ctl -t
sudo tail -n 100 /var/www/abergonline/thainews/storage/logs/cron-fetch.log
sudo tail -n 100 /var/www/abergonline/thainews/storage/logs/fetch.log
curl -fsS https://thainews.aberg.online/ >/dev/null
```

Rekommenderade larm är: utebliven lyckad hämtning under 30 minuter, alla källor misslyckas, snabbt växande loggar, låg diskplats, upprepade HTTP 500, databas otillgänglig och TLS-certifikat nära utgång.

## 15. Installation och driftsättning

### 15.1 Ny installation

```bash
cd /var/www/abergonline/thainews
composer install --no-dev --optimize-autoloader
npm ci
npm run assets
sudo install -d -o www-data -g www-data -m 0770 \
  storage/cache storage/locks storage/logs
```

Skapa därefter extern konfiguration från `app/config.example.php`, installera databasschema och seed, aktivera Apache-vhost och cron samt genomför en torrkörning. `scripts/install_production.php` kan skapa databas, minsta-rättighetskonto och externa hemligheter när det körs som root, men skriver aldrig över en befintlig `/etc/thainews/config.php`.

```bash
mariadb -u ADMIN -p DATABAS < sql/schema.sql
mariadb -u ADMIN -p DATABAS < sql/seed.sql
sudo apache2ctl -t
sudo systemctl reload apache2
sudo -u www-data env THAI_NEWS_CONFIG_FILE=/etc/thainews/config.php \
  php cron/fetch_news.php --dry-run
```

### 15.2 Uppgradering

1. Säkerhetskopiera databas, extern konfiguration och nuvarande release.
2. Installera ny kod i en separat releasekatalog om möjligt.
3. Kör `composer install --no-dev --optimize-autoloader` och `npm ci && npm run assets`.
4. Granska och kör nya idempotenta SQL-filer/migreringar.
5. Säkerställ ägare/rättigheter för `storage` och extern konfiguration.
6. Kör lint, tester och `fetch_news.php --dry-run`.
7. Växla release eller synkronisera filerna, uppdatera `asset_version` och service worker-cacheversion tillsammans.
8. Syntaxkontrollera och ladda om Apache.
9. Verifiera startsida, inställningar, privata URL:er och nästa cron-körning.

### 15.3 Återställning/rollback

Återställ tidigare kodrelease och kompatibel databasbackup. Återställ även motsvarande extern konfiguration om konfigurationsformatet ändrats. Kör inte destruktiva nedmigreringar utan separat verifierad procedur. Efter rollback ska Apache laddas om, PHP-opcache tömmas vid behov, startsida testas och cron-loggar följas.

## 16. Backup och återläsning

Minsta backupomfattning:

- MariaDB-databasen med schema, triggers/rutiner om sådana tillkommer, och data;
- `/etc/thainews/config.php` via krypterad hemlighetsbackup;
- projektkoden eller reproducerbar Git-release;
- vid behov `storage/logs` för revision/felsökning.

`storage/cache` och `storage/locks` behöver normalt inte återställas. `vendor` och `node_modules` kan reproduceras från låsfilerna men ingår i det arkiv som skapats tillsammans med denna dokumentation.

Exempel på logisk databasbackup:

```bash
mariadb-dump --single-transaction --routines --triggers \
  --databases thai_news > thai_news-YYYYMMDD-HHMMSS.sql
```

Återläsning ska provas i en isolerad databas. Kör därefter applikationens testsvit eller åtminstone schema-/seedkontroll och en hämtning i `--dry-run` innan produktionsväxling. Backupmedier måste krypteras och åtkomstbegränsas eftersom databasen innehåller pseudonyma besökarprofiler.

## 17. Tester och kvalitetskontroll

```bash
# PHP-syntax
find app api cron includes scripts tests -name '*.php' -print0 \
  | xargs -0 -n1 php -l

# Enhets- och integrationstester; integrationstester hoppas över utan test-DSN
composer test

# Fristående kontroll av varumärkestillgångar
php tests/check_assets.php

# Browser- och tillgänglighetstester
THAI_NEWS_BASE_URL='https://thainews.aberg.online' npm run test:e2e
```

Databasintegrationstester kräver en separat, disponibel databas via `THAI_NEWS_TEST_DSN`, `THAI_NEWS_TEST_USER` och `THAI_NEWS_TEST_PASS`. De får aldrig riktas mot produktion. Playwright kör mobil-, surfplatte- och desktop-profiler och påverkar besökarinställningar för sina egna nygenererade cookies.

Testerna täcker bland annat parsern, URL-normalisering, adapterregister, språknyckelparitet, metadata, PWA-tillgångar, schema/seed-idempotens, deduplicering, felisolering, inställningar, översättningscache, responsiv layout, no-JavaScript-flöde och tillgänglighet.

## 18. Felsökning

### Webbplatsen visar generiskt startfel

1. Kontrollera Apache error log och `storage/logs/app.log`.
2. Kontrollera att `THAI_NEWS_CONFIG_FILE` når en läsbar fil.
3. Kontrollera att `www-data` kan läsa konfigurationen och skriva i `storage`.
4. Prova databasanslutningen utan att skriva ut lösenord.
5. Kör `php -l` på ändrade PHP-filer och `apache2ctl -t`.

### Inga eller gamla nyheter

1. Kontrollera senaste `fetch_runs` och `source_fetch_runs`.
2. Läs `cron-fetch.log` och `fetch.log`.
3. Kontrollera cron-tjänst och `/etc/cron.d/thainews`.
4. Kör arbetaren manuellt som `www-data`, först med `--dry-run`.
5. Kontrollera DNS, utgående HTTPS, CA-certifikat och om en utgivare ändrat flödet.
6. Ett kvarvarande låsfilnamn är normalt; låsstatusen avgörs av `flock`, inte filens existens.

### Inställningar sparas inte

Kontrollera session/cookies, CSRF-svar, `user_preferences`, `user_source_preferences`, API-svarskod och om databaskontot har INSERT/UPDATE. HTTP 429 betyder att samma klient nått 30 API-skrivningar inom tio minuter.

### Översättningar visas inte

Kontrollera först `translation.enabled` och att API-nyckel finns. Därefter: kör `--dry-run`, inspektera `article_translations.status`, `last_error_code`, `next_attempt_at` och översättningsloggen. I den dokumenterade produktionen är funktionen avsiktligt avstängd, så originalrubriker är korrekt beteende.

### Gamla assets efter release

Säkerställ att `asset_version` i konfigurationen och `CACHE_NAME`/app-shell-URL:erna i `sw.js` har uppdaterats samordnat. Kontrollera därefter att den nya service workern aktiverats och att Apache skickar rätt fil.

## 19. Löpande underhåll

- Dagligen: övervaka senaste lyckade hämtning, felandel per källa och diskplats.
- Veckovis: granska återkommande källfel och loggtillväxt.
- Månadsvis: verifiera backup/restore, TLS-livslängd och beroendeuppdateringar.
- Vid källändring: uppdatera `sql/seed.sql`, adapter/fixture och dokumentation; kör seed idempotent.
- Vid språkändring: håll nycklar identiska i alla tre språkfiler och kör `LanguageParityTest`.
- Vid assetändring: uppdatera både applikationens assetversion och service worker-cachen.
- Inför schemalagd gallring för gamla rate-limit-rader, körhistorik, besökarprofiler, artiklar och loggar efter beslutad retentionpolicy.

## 20. Kända begränsningar och rekommenderade förbättringar

1. Ingen automatisk datagallring eller loggrotation finns i applikationen.
2. Ingen inbyggd health/metrics-endpoint finns; driftstatus avläses via HTTP, loggar och databas.
3. Ingen separat kö används. Cron och fillås är tillräckliga för nuvarande skala men begränsar horisontell körning över flera servrar.
4. Fem artiklar per källa och rubrik-/utdragslängder är hårdkodade i vyerna.
5. Översättningsprovider är hårdkodad till Google-adaptern i CLI-entrypointen trots providergränssnittet.
6. Ingen automatisk rensning av föråldrade service worker-cachar kan ske förrän klienten hämtar och aktiverar den nya arbetaren.
7. Dokumentationsfilen `docs/BUILD_REPORT.md` är historisk och nämner äldre funktionalitet som inte finns i aktuell kod. Denna fil är normerande för nuläget.

Prioriterade förbättringar är retention/logrotate, en autentiseringsfri men informationssnål health-endpoint, central övervakning av cron, automatiserad deploy med atomisk releaseväxling och ett dokumenterat återläsningstest.

## 21. Kontrollista för incidenter

1. Fastställ påverkan: webb, databas, enskild källa, alla flöden eller översättning.
2. Notera tid i både UTC och Asia/Bangkok.
3. Bevara relevanta loggar och senaste körningsrader innan rotation eller återstart.
4. Kontrollera tjänster, disk, certifikat, databas och extern nätåtkomst.
5. Begränsa påverkan med reversibla åtgärder; stäng vid behov av berört cron-jobb utan att radera data.
6. Återställ från känd release/backup om normal felsökning inte räcker.
7. Verifiera webb, inställningar, privata URL-skydd och en manuell torrkörning.
8. Dokumentera rotorsak, tidslinje, dataförlust, åtgärd och förebyggande arbete.

## 22. Dokumentstatus och sanningskällor

Denna dokumentation baseras på den faktiska kodbasen, versionslåsen, installerad Apache/cron-konfiguration, sanerad produktionskonfiguration och read-only databasstatistik den 2026-09-23. Vid konflikt gäller i första hand körbar kod och `sql/schema.sql`, därefter installerad extern driftkonfiguration. Historiska filer i `docs/` kan beskriva tidigare releaser och ska inte ensamma användas som nulägesbeskrivning.

Dokumentet innehåller medvetet inga lösenord, API-nycklar, råa besökarkakor eller hemliga HMAC-värden.
