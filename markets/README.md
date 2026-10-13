# Chiang Mai Market Journal – PHP 1.00

**En körklar marknadsguide med 34 marknader, recensioner, stjärnbetyg och egna bilder.**

> Projektkatalogen är DocumentRoot. Du kan lägga projektet i domänens rot eller i valfri undermapp, till exempel `/marknader/`. Projektet använder inga hårdkodade URL-prefix.

## Kom igång

1. Ladda upp **hela innehållet** i paketet till din webbserver.
2. Kontrollera att PHP **8.2+** och tilläggen **pdo_sqlite** och **fileinfo** är aktiverade. För ZIP-backup/import krävs även **zip**.
3. Ge webbserverns användare skrivbehörighet i katalogen `storage/` inklusive `storage/photos/` och `storage/tmp/` (inte hela webbplatsen).
4. Öppna `https://din-domän.se/admin.php` eller `https://din-domän.se/valfri-undermapp/admin.php`.
5. Skapa första adminkontot med installationsnyckeln `SETUP_KEY` från **`app/config.php`**. Välj ett eget långt lösenord (minst 12 tecken).
6. Öppna en marknad, skriv en recension och bifoga bilder och videor.

En recension kan ha högst **30 filer**. Bilder (JPG, PNG, WebP) får vara högst **25 MB per fil**, videor (MP4, WebM, MOV) högst **200 MB per fil**, och en uppladdning högst **500 MB totalt**. Videor visas i galleriet och spelas upp i bildvisaren. Webbläsarstöd för MOV varierar; MP4 och WebM är säkrast för delning.

Recensioner kan skrivas på svenska eller engelska. Välj textens originalspråk i formuläret; det förvalda språket följer sidans språk. När en besökare väljer det andra språket översätts titel och recension automatiskt och resultatet sparas i SQLite. Originaltexten ändras inte. Översättningen använder Googles publika översättningstjänst och kräver PHP-tillägget `curl` samt utgående HTTPS. Om tjänsten tillfälligt inte svarar visas originaltexten och översättningen försöks igen vid nästa besök. Säkerhetskopian sparar originaltext och originalspråk; översättningar skapas om efter återställning.

`storage/market_journal.sqlite` skapas automatiskt vid första öppningen. Ingen SQL-import krävs.

### Lagring och säkerhet

- Databasen och bildernas originalfiler ligger i `storage/`. De visas via kontrollerade PHP-endpoints, inte via direkta länkar till lagringsmappen.
- Apache skyddar `app/` och `storage/` genom medföljande `.htaccess`-filer. Se till att **AllowOverride** tillåter detta (eller lägg motsvarande regler i vhosten).
- **Nginx:** Nginx läser inte `.htaccess`. Lägg en regel som nekar alla HTTP-förfrågningar till `/app/` och `/storage/` inom rätt URL-prefix. Alternativt, och ännu säkrare, konfigurera `STORAGE_DIR` i `app/config.php` till en katalog utanför webbroten.
- Efter installationen används inte `SETUP_KEY` längre för inloggning. Den skyddar bara skapandet av första kontot.
- Lämna inte installationen öppet publicerad med en installationsnyckel som du delat. Byt den gärna i `app/config.php` innan publicering.
- Endast admin får publicera/ändra/radera recensioner; besökare kan läsa dem och visa bilder.
- Formulär använder CSRF-skydd. Lösenord hash:as med PHP:s `password_hash()`, frågor använder PDO-prepared statements, och text HTML-escapas.
- För publik driftsättning rekommenderas **HTTPS**, starkt lösenord och regelbundna backuper.

### Fungerar så här

- **Startsidan `index.php`:** samtliga 34 marknader, snabb sökning, områden, kategorifilter, kort med snittbetyg och recensionsantal.
- **Kartvy `map.php`:** interaktiv karta med alla 34 marknader, sökning, kategorifilter och länkar till marknadssidorna. Streckade markörer anger ungefärliga platser. Koordinaterna finns i `app/locations.php` och laddas från lokala data; kartunderlaget visas via OpenStreetMap.
- **Marknadssida `index.php?m=...`:** information, ungefärliga öppettider, Google Maps-länk, recensioner och bildgalleri.
- **Marknadsfoton:** ett foto per marknad i `assets/market-images/`. Där platsfoto saknas visas en märkt stämningsbild. Upphovsman, källa och licens finns på `credits.php`.
- **Admin `admin.php`:** första installation, login/logout, lösenordsbyte och backup.
- **Åtgärder `action.php`:** skapa, uppdatera, radera recensioner och bilder.
- **Media `photo.php`:** säker uppvisning av bilder och videor från `storage/photos/`, med byteintervall för videouppspelning.
- **Backup `backup.php`:** exporterar ZIP med databasens recensioner och originalbilder; import ersätter det nuvarande recensionsinnehållet men **behåller adminkontot**.

### Redigera marknadsuppgifter

Alla marknadsnamn, områden, beskrivningar, etiketter och ungefärliga öppettider ligger i **`app/markets.php`**. Redigera text där. **Byt inte en marknads `slug`** om det redan finns recensioner sparade för den; då kopplas recensionerna inte längre till marknaden. Lägg till en ny post i katalogen om du vill utöka guiden.

Öppettiderna i startpaketet är **vägledande** och inte en livekontroll. Kontrollera öppettider på plats och uppdatera `app/markets.php` vid behov. Små popup- och specialmarknader kan ändra plats eller arrangemangsdag.

### Säkerhetskopior

På adminsidan kan du klicka **Exportera ZIP-backup**, vilket ger en ZIP med `manifest.json`, bilder och videor. **Återställ ZIP** ersätter alla befintliga recensioner och mediafiler och behåller administratörskontot. Gör gärna en export **före** import. Importfunktionen accepterar ZIP-arkiv upp till **2 GB**.

För extra säkerhet: säkerhetskopiera `storage/market_journal.sqlite` och hela `storage/photos/` på servern. Kopiera gärna databasen när skrivningar inte pågår eller använd SQLite:s backupfunktion.

### PHP-inställningar (typiska)

`extension=pdo_sqlite` och `extension=fileinfo` (vanligen aktiva som PHP-paket/tillägg). På Debian/Ubuntu kan tilläggen heta `php-sqlite3` och `php-zip`. Webbservern behöver `upload_max_filesize` på minst 200M, `post_max_size` över 500M och `max_file_uploads` över 30. För återställning av stora ZIP-backuper behöver `backup.php` en separat gräns över 2 GB.

## Projektstruktur

```text
/index.php             Marknadslista och marknadssidor
/admin.php             Installera admin, login och kontoinställningar
/action.php            Spara/radera recensioner och bilder
/photo.php             Läs bilder från skyddad lagring
/backup.php            Export/import av ZIP-backuper
/app/config.php        Konfiguration och installationsnyckel
/app/markets.php       Register över 34 marknader
/app/bootstrap.php     SQLite, sessioner, hjälpmetoder
/app/views/           Gemensam header/footer
/assets/style.css      Responsiv design
/assets/app.js         Sök/filter och bildförhandsvisning
/storage/              SQLite, bilder och tillfälliga backuper
```

**Version:** 1.00, 2026-10-13 · KlⒶssⓔ & Ⓐberg
