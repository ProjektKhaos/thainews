# Status och funktion – Cosmos House

> Historisk ögonblicksbild från 10 oktober 2026. För bokningskodens aktuella status, se `CODEX_SLUTRAPPORT.md` och `TEST_REPORT.md`.

**Uppdaterad:** 10 oktober 2026 (Asia/Bangkok)  
**Projektkatalog:** `/var/www/abergonline/thainews/ch`  
**Nuvarande förhandsvisningsadress:** `https://thainews.aberg.online/ch/`

Det här dokumentet sammanfattar hur långt Cosmos House har kommit och hur sajten fungerar. Beskrivningen bygger på koden och den detaljerade [projektöverlämningen](COSMOS_HOUSE_CODEX_HANDOVER.md). Den publika adressens aktuella driftstatus har inte kontrollerats vid denna dokumentation.

## Nuläge

Cosmos House är en fungerande, lösenordsskyddad förhandsvisning av en huspresentation i Chiang Mai. Designen är responsiv och innehåller 38 lokala husbilder, ett filtrerbart galleri och sidor på svenska, engelska och thai. Bokningssidan är ännu bara en informationssida. Sajten tar inte emot bokningsförfrågningar, betalningar eller automatiska bekräftelser.

| Område | Status |
|---|---|
| Sidor | 18 färdigbyggda PHP-sidor: startsida, huset, galleri, upplevelse, Chiang Mai och bokning på tre språk. |
| Navigation | Meny och sidlänkar går till egna undersidor. Språkbyte behåller motsvarande sida. |
| Galleri | 38 lokala WebP-bilder med filter och helskärmsvisning. |
| Layout | Mobil grundlayout, surfplattelayout från 651 px och desktoplayout från 901 px. |
| Åtkomst | Enkel sessionsinloggning för PHP-sidorna. Admin har ett separat lösenord. |
| Bokning | Stängd i både sidans HTML och serverns API genom `BOOKING_OPEN = false`. |
| Innehåll | Längre avsnitt märkta som exempeltext och flera uppgifter om huset väntar på ägarens bekräftelse. |
| Versionshantering | Hela `ch/` är fortfarande oversionerad i moderprojektets Git-arbetskatalog. |

## Så fungerar sajten

1. Besökaren går till `/ch/`. `index.php` kräver först en förhandsvisningssession och leder sedan till den engelska startsidan. En äldre länk med `?lang=` kan fortfarande välja språk.
2. Utan session skickas sidbesökaren till `login.php`. Efter godkänd inloggning öppnas den begärda sidan. Den här spärren är avsedd för förhandsvisning: bilder, CSS och JavaScript kan nås direkt via sina filadresser.
3. Varje språk har en startsida och fem undersidor under `en/`, `th/` och `sv/`. Besökaren kan läsa om huset, se galleriet, läsa om upplevelsen och området samt se information om kommande bokning.
4. Galleriet läser bildlistan från `gallery.json`, visar lokala bilder från `assets/photos/` och styrs av `assets/app.js`. Det har kategorifilter, bildvisning i helskärm, miniatyrer och tangentbordsstöd.
5. Sidorna byggs från den gemensamma mallen `page-source.php` och språktexterna i `translations.php`. `bash scripts/build_languages.sh` skapar alla 18 PHP-filer med färdig HTML och åtkomstkontroll. Vid innehållsändringar ändras källfilerna och byggskriptet körs igen; genererade `index.php`-filer ska inte redigeras för hand.
6. Interna länkar och sökvägar beräknas relativt projektet. Hela `ch/` kan därför flyttas till en annan URL-nivå utan att länkar i kod och genererade sidor behöver skrivas om. Webbserverns åtkomstregler och privat lagringskatalog måste konfigureras för den nya miljön.

### Sidöversikt

| Svensk sökväg | Innehåll |
|---|---|
| `/ch/sv/` | Startsida med introduktion och länkar vidare. |
| `/ch/sv/house/` | Huset och informationsrutor. |
| `/ch/sv/gallery/` | Bildförhandsvisning och galleri. |
| `/ch/sv/experience/` | Upplevelsetext och bildmaterial. |
| `/ch/sv/location/` | Presentation av Chiang Mai och området. |
| `/ch/sv/book/` | Information om att bokning ännu inte är öppen. |

Motsvarande sökvägar finns under `/ch/en/` och `/ch/th/`. Prefixet `/ch/` hör till nuvarande installation, inte till produktens namn.

## Bokning och administration

`BOOKING_OPEN` är `false` i `config.php`. Därför visas inget aktivt bokningsformulär eller pris. `GET api.php` ger efter inloggning `bookingOpen: false`; `POST api.php` ger 401 utan förhandsvisningssession och 503 efter inloggning. Admins statusändringar är avstängda. Kod för förfrågningar och administration finns kvar för vidare utveckling, men den är inte klar för riktiga gäster.

`config.php` innehåller exempelvärden för pris, städavgift, kapacitet och vistelselängd. De är inte bekräftade villkor. Privat bokningsdata är avsedd för en katalog utanför både projektkatalog och webbroten; den tidigare projektöverlämningen anger `/var/lib/cosmos-house/bookings.json` för nuvarande server. Den faktiska serverkonfigurationen har inte kontrollerats på nytt här.

## Kvar före publik lansering och riktig bokning

- Ersätt märkta exempeltexter och bekräfta alla faktapåståenden om huset, området, priser, kapacitet och regler med ägaren. Designens koordinatliknande element är inte en verifierad adress.
- Besluta om integritetstext, lagringstid, backup, radering och hur gäster och administratör ska få besked. Inga automatiska mejl, betalningar eller extern kalendersynk finns i nuläget.
- Färdigställ och testa formulär, översättningar, adminautentisering, lagringsfel, samtidiga förfrågningar och återställning innan `BOOKING_OPEN` ändras.
- Kontrollera skyddet för privata sökvägar och den externa lagringskatalogen när sajten flyttas eller serverkonfigurationen ändras. `.htaccess` kan inte ensam antas ge skydd.
- Lägg `ch/` i avsedd versionshantering när projektet ska få en spårbar release.

## Kontroller och avgränsning

Vid denna dokumentation lästes projektets kod och befintliga överlämning. `php -l` passerade för `ch/config.php` och `ch/api.php`. Ingen ny webbläsar-, HTTP- eller serverkontroll gjordes. Den detaljerade överlämningen beskriver äldre kontroller av de 18 sidorna, responsiv layout, galleriet och API-spärren; resultaten gäller tidpunkten då de utfördes.

För installation, designregler, språkbygge och hela listan med öppna beslut, se [README.md](../README.md) och [COSMOS_HOUSE_CODEX_HANDOVER.md](COSMOS_HOUSE_CODEX_HANDOVER.md).
