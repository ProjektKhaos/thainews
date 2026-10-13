# COSMOS HOUSE — aktuell projektdokumentation för ChatGPT

> Historiskt överlämningsdokument för tidigare förhandsvisning. Ny bokningskod från 12 oktober 2026 dokumenteras i `CODEX_SLUTRAPPORT.md`, `ARCHITECTURE.md` och `TEST_REPORT.md`.

**Dokumentversion:** 2.05  
**Uppdaterad:** 2026-10-09 18:38 (Asia/Bangkok)  
**Projekt:** Cosmos House, Chiang Mai  
**Syfte:** Ge nästa ChatGPT-session en korrekt bild av kod, design, innehåll, drift och öppna beslut.

> **Läs detta först:** Dokumentet beskriver den nuvarande koden. Den äldre v1.00-beskrivningen gällde ett annat driftläge. Kontrollera alltid filerna och servermiljön igen innan ändringar. Särskilt viktigt: **bokningen är stängd**, **priserna är exempelvärden**, och de längre styckena märkta som exempeltext är inte verifierat innehåll.

---

## 1. Kort sammanfattning

Cosmos House är en presentationssajt för ett hus i Chiang Mai. Sidan bygger på PHP 8.1+, genererade separata PHP-sidor med förgenererad HTML, CSS och vanlig JavaScript utan ramverk eller Composer i denna projektkatalog. Den har 38 lokala WebP-bilder, ett filtrerbart helskärmsgalleri, responsiv layout och lösenordsskyddade språkversioner på engelska, thailändska och svenska.

Nuvarande tillfälliga adress är `https://thainews.aberg.online/ch/`; `/ch/` leder som standard till engelska startsidan. Varje språk har dessutom egna undersidor för huset, galleri, upplevelse, Chiang Mai och bokning. Katalogen `/ch/` är **inte** en del av produktens identitet eller kodens URL-system. Projektkatalogen ska kunna ligga vid `/`, `/cosmos/`, `/cosmos-house/` eller flera nivåer ner utan att PHP, CSS eller JavaScript redigeras.

Bokningsdelen är för närvarande en informationsyta. Den visar inget bokningsformulär eller demopris. `POST api.php` svarar 401 utan inloggning och 503 efter inloggning, och admin kan inte ändra bokningsstatus. Den äldre bokningslogiken finns kvar bakom `BOOKING_OPEN = false` för fortsatt utveckling, men ska inte öppnas genom att bara ändra flaggan.

### Status i en mening

**Fungerande lösenordsskyddad förhandsvisning och galleri; förfrågningar, betalning och automatisk bekräftelse är inte öppna.**

---

## 2. Produkt, ton och verifierade fakta

Sajten ska kännas som ett personligt, varmt och arkitektoniskt boende: stora autentiska bilder, lugna naturtoner, luftig typografi och redaktionella rubriker. Inspirationen är boutique hospitality och slow living. Bevara formspråket när innehåll eller funktioner utvecklas.

| Uppgift | Nuvarande status |
|---|---|
| Namn Cosmos House och plats Chiang Mai, Thailand | Används som sajtens identitet |
| 38 levererade husfoton | Filerna finns under `assets/photos/` |
| Nattpris 2 800 THB och städavgift 600 THB | Endast konfigurerade **exempelvärden**; visas inte publikt nu |
| Max 4 gäster, 1–90 nätter | Teknisk demokonfiguration; inte bekräftad uthyrningspolicy |
| Takterrass, kök, arbetsplats och andra beskrivningar | Presentationspåståenden som ägaren bör verifiera |
| Koordinatliknande text i hero | Grafiskt element, inte verifierad husposition |
| Exakt adress, avstånd och restider | Inte publicerade eller verifierade |
| Längre text märkt Exempeltext / Sample text / ข้อความตัวอย่าง | Tillfällig layouttext, ska ersättas |
| Gästrecensioner, betalning, e-postutskick, extern kalendersynk | Inte implementerade |

Hitta inte på antal sovrum/badrum, Wi-Fi-hastighet, parkering, pool, tillgänglighetsanpassning, husregler, exakt position eller recensioner. Ägarens bekräftelse krävs före sådana fakta läggs till.

---

## 3. Projektstruktur och ansvar

```text
<projektkatalog>/
├── index.php                 Kräver inloggning och leder sedan till /en/
├── access.php + login.php    Sessionsspärr och inloggningsformulär
├── page-source.php           Gemensam byggmall, ej en publik sida
├── en/, th/, sv/            Språkstartsidor och fem egna undersidor per språk
│   └── {house,gallery,experience,location,book}/index.php
├── scripts/                  Bygger 18 skyddade PHP-sidor: sex sidor × tre språk
├── translations.php          Engelska/thailändska översättningar; svenska original som reserv
├── config.php                Bokningsflagga, demovärden, URL- och storage-hjälpare
├── api.php                   GET tillgänglighet; POST avstängd medan bokning är stängd
├── admin.php                 Inloggning och vilande statusadministration
├── gallery.json              Manifest för 38 foton
├── README.md                 Kort installations- och driftbeskrivning
├── assets/
│   ├── style.css             Publik och admin-CSS, inklusive responsivitet
│   ├── app.js                Mobilmeny, galleri och vilande boknings-JS
│   ├── photos/photo-01.webp … photo-38.webp
│   └── flags/gb.svg, th.svg, se.svg   Lokala rektangulära språkflaggor
├── storage/
│   └── .htaccess             Extra skydd; ingen bokningsdata här
└── docs/
    └── COSMOS_HOUSE_CODEX_HANDOVER.md
```

Den aktiva bokningsfilen ligger **utanför** projekt- och webbkatalogen. På den nuvarande servern är den `/var/lib/cosmos-house/bookings.json`. Den katalogen måste skapas/konfigureras separat på en ny server.

Ändra inte orelaterade filer i den omgivande Thai News-kodbasen. Vid senaste dokumentkontrollen var `ch/` ännu en oversionerad katalog i Git och det fanns separata ändringar i moderprojektet. Kontrollera `git status` före varje nytt arbete.

---

## 4. Publik sidstruktur

Sajten är nu uppdelad i **18 separata genererade PHP-sidor med färdig HTML**: sex sidor för varje språk. Exempel för svenska:

| Sida | URL under nuvarande projektkatalog | Innehåll |
|---|---|---|
| Start | `/ch/sv/` | Hero och bildlänkar till övriga sidor |
| Huset | `/ch/sv/house/` | Introduktion och informationsrutor |
| Galleri | `/ch/sv/gallery/` | Bildförhandsvisning och filtrerbart helskärmsgalleri |
| Upplevelse | `/ch/sv/experience/` | Foto, presentationspunkter och citatyta |
| Chiang Mai | `/ch/sv/location/` | Områdestext och platsbild |
| Bokning | `/ch/sv/book/` | Informationsyta om stängd bokning |

Byt `sv` mot `en` eller `th` för samma sida på annat språk. Meny, sidkarta på startsidan, footer och länkar inne i innehållet leder till dessa URL:er utan sektionsankare. Språkvalet behåller aktuell undersida. Äldre bokmärken som `/sv/#book` skickas i webbläsaren till `/sv/book/` (samma princip för `#home`, `#gallery`, `#experience` och `#location`).

Sidorna delar en byggmall i `page-source.php`, men varje besökare får ett eget genererat `index.php` med inloggningskontroll och färdig HTML. De gamla direkt åtkomliga `index.html`-filerna är borttagna. Webbservern måste köra PHP och använda `index.php` som katalogindex. Den aktiva bokningssidan visar inget formulär eller demopris. Den vilande formulärkoden är kvar i mallen bakom `BOOKING_OPEN = false` och är ännu inte produktionsklar eller fullständigt översatt.

De längre styckena märkta som exempeltext finns kvar på respektive innehållssida. De är layouttext och ska inte misstas för verifierade fakta.

---

## 4A. Enkel inloggning för förhandsvisningen

`access.php` startar en PHP-session med en `HttpOnly`-cookie vars path härleds från projektets aktuella URL-nivå. Alla 18 genererade `index.php`, projektets `index.php`, `api.php` och `admin.php` kräver flaggan `cosmos_site_access` i sessionen. Oinloggade sidbesök omdirigeras till `login.php?next=...`; API-anrop får 401 som JSON. Loginformuläret har bara ett fält och godkänner ordet **huset**. Vid lyckad inloggning byts session-ID och besökaren återvänder till den begärda sidan. `next` godtas bara som lokal sökväg inom projektets URL-bas.

Detta är en enkel förhandsvisningsspärr, inte adminautentisering. `admin.php` kräver fortfarande separat `COSMOS_ADMIN_PASSWORD`. Statiska bilder, CSS och JavaScript ligger fortfarande som vanliga filer i publik DocumentRoot och kan nås via sina direkta URL:er. Om även resurserna ska vara privata måste de flyttas bakom en separat åtkomstkontroll. Lösenordet `huset` ska inte användas som skydd för personuppgifter eller bokningsadministration.

---

## 5. Visuell identitet och CSS

`assets/style.css` är källan till aktuellt utseende. Den är nu organiserad mobil först: grundregler för små skärmar, sedan `min-width: 651px` för surfplatta och `min-width: 901px` för desktop. En särskild regel för 901–1050 px håller desktopnavigationen rymlig. Kontrollera utseendet vid både brytpunkter och vanliga skärmbredder efter ändringar.

### Färger

| CSS-variabel | Värde | Användning |
|---|---|---|
| `--cream` | `#f7f5f0` | Ljus grund, hero-knapp, footer |
| `--paper` | `#fffefa` | Header och ljusa kort |
| `--ink` | `#252e2c` | Mörk text |
| `--muted` | `#777b76` | Sekundärtext |
| `--olive` | `#6f7863` | Naturtonad accent |
| `--line` | `#e2e2db` | Tunna avdelare |
| `--peach` | `#e4bd9c` | Varm accent |

Ytterligare sektionsfärger: galleri `#efeee8`, citatyta `#34453a`, bokningsyta `#eaece5`, mörk knapp `#303c34`. Undvik skarpa färger, standardkort och överdrivna effekter.

### Typografi och proportioner

- DM Sans används för löptext/UI och Manrope för stora latinska rubriker. Georgia italic markerar utvalda ord i rubriker. För thailändska laddas Noto Sans Thai; thailändska rubriker har egna storleksregler.
- Maxbredd för huvudinnehåll är ungefär 1260 px med breda marginaler. Desktopsektioner har cirka 122 px vertikalt utrymme.
- Headern är cirka 88 px på desktop och 75 px vid tablet/mobil. Hero är nära skärmhög med stora foto- och rubrikytor.
- Layouten använder asymmetriska gridkolumner och tunna linjer. Brytpunkter finns vid 900 och 650 px, med tillägg för språkvalet kring 901–1050 px.
- Lågmälda hovereffekter, stora foton och lätt rundade knappar är en del av identiteten.
- Exempeltexterna har egna regler (`.sample-copy`, `.quote-detail`) så längre innehåll kan provas utan att grunddesignen byts ut.

Google Fonts är en extern resurs för typografi. Bilderna är däremot lokala. Om typsnitten inte laddas visas reservtypsnitt.

---

## 6. Separata språksidor och översättningar

Språkvalet visas i ordningen **Eng 🇬🇧, Thai 🇹🇭, Swe 🇸🇪** med lokala SVG-flaggor. Engelska, thailändska och svenska har varsin genererad `index.php` för **varje** sida; det finns ingen gemensam HTML-fil som väljer språk vid besök. Roten `/` leder till `/en/`, medan äldre `?lang=`-URL:er fortfarande omdirigeras till vald språkstartsida.

`translations.php` använder svenska originalsträngar som nycklar. Engelska och thailändska värden ligger i varsin tabell. Byggskriptet kör `render_language.php` för varje språk och sidtyp och skriver 18 PHP-filer med förgenererad HTML och sessionskontroll. `gallery.json` behålls på originalspråket; bildetiketter översätts vid byggning. Uppdatera `page-source.php` eller `translations.php` och kör `bash scripts/build_languages.sh`. Ändra aldrig en genererad `index.php` för hand.

Admin-sidan och den vilande bokningsformens texter är fortfarande på svenska. Detta behöver hanteras före flerspråkig bokningslansering. Ändring av `BOOKING_OPEN` kräver ombyggning av alla sidor och får inte göras utan checklistan i avsnitt 11.

## 7. Bilder och galleri

`gallery.json` har 38 objekt med `src`, `label` och `category`. Kategorierna i datan är `living` (13), `bedroom` (6), `terrace` (5) och `details` (14). `all` är ett filter i gränssnittet, inte en sparad kategori. Alla 38 refererade WebP-filer fanns vid dokumentkontrollen.

JS bygger varje foto-URL som `window.COSMOS.base + '/' + photo.src`; basen kommer från PHP. Galleriet öppnas via `[data-open]` och knappen `#allPhotos`. Det stöder filter, föregående/nästa, tangentbord, miniatyrer, bildtext, bildräknare, fokusåtergång och låst bakgrundsskroll.

**Indexfälla:** `data-open` är nollbaserat, medan filnamnen `photo-01` till `photo-38` är ettbaserade. Exempel: `photo-02.webp` öppnas med `data-open="1"`. Om manifestet sorteras om måste förhandsvisningarnas index kontrolleras.

Bevara fotona. Ersätt dem inte med stockbilder och påstå inte mer om en bild än den visar. Hero-bilden definieras i CSS relativt själva CSS-filen: `url('photos/photo-02.webp')`.

---

## 8. URL-portabilitet

`config.php` beräknar normalt `site_base_path()` från katalogen i `$_SERVER['SCRIPT_NAME']`. Funktionen ger tom sträng vid webbroten och exempelvis `/house/booking` vid en djup installation. `url($path)` bygger interna URL:er och `asset_url($path)` bygger filer under `assets/`. `base_url()` finns som alias för bakåtkompatibilitet.

När språkfilerna byggs använder mallen `COSMOS_STATIC_EXPORT` och skapar **relativa vägar** från språkkatalogen: `../` på startsidan och `../../` på undersidorna. `window.COSMOS.base` får samma värde. JavaScript använder basen för API och galleribilder. CSS-bilden använder en väg relativ till CSS-filen. Därför fungerar samma färdiga språkfiler både direkt under DocumentRoot och flera kataloger ner. Rotens omdirigering beräknas separat med `url()` och har inget fast `/ch/`-prefix.

**Vid flytt:** flytta hela projektkatalogen och konfigurera serverns publiceringsväg. Ändra inte interna URL:er i PHP/HTML/CSS/JS. Den privata lagringsvägen och webbserverns åtkomstregler är däremot driftkonfiguration och måste följa med som separata installationssteg. Kör byggskriptet igen när innehåll eller mall ändras, men inte enbart för att katalogen byter namn.

---

## 9. Privat data och nuvarande Apache-drift

`COSMOS_STORAGE_DIR` måste vara en absolut, existerande och skrivbar fysisk katalog **utanför både projektkatalog och DocumentRoot**. `store_path()` lägger till `bookings.json` och avvisar kataloger inne i de publika rötterna. `read_bookings()` kräver en giltig JSON-lista; den ersätter inte trasig data tyst med en tom lista.

På den nuvarande servern:

- Apache har DocumentRoot `/var/www/abergonline/thainews` och projektet finns för tillfället i undermappen `ch`.
- `COSMOS_STORAGE_DIR` sätts till `/var/lib/cosmos-house` i SSL-värdens konfiguration.
- `/var/lib/cosmos-house` har ägare `www-data:www-data` och rättighet 750; `bookings.json` har samma ägare och rättighet 660. Filen var `[]` vid dokumentkontrollen.
- `/ch/storage/bookings.json` finns inte längre. Den publika `storage/` innehåller endast `.htaccess` som extra skydd.
- Apache har `AllowOverride None`. Därför **används inte** `.htaccess` på denna server. Den aktiva SSL-värden har en `<LocationMatch>` som nekar `storage` på valfritt URL-djup.
- Servern använder HTTPS och skickar HSTS-header. Apache-konfigurationen ligger utanför projektkatalogen och följer inte med när filer kopieras.

Lägg aldrig personuppgifter, lösenord eller privata bokningsfiler i den publika projektkatalogen. Nya servermiljöer behöver motsvarande skydd och en separat privat lagringskatalog. Dokumentera och testa den nya konfigurationen, särskilt om Apache ersätts av Nginx.

---

## 10. Bokningskodens läge och dataflöde

### Publikt läge just nu

`BOOKING_OPEN = false` i `config.php` är den avgörande bokningsspärren. Bokningssidan renderar ett informationskort utan fält eller pris. Efter inloggning ger `GET api.php` ungefär:

```json
{"bookingOpen":false,"blocked":[]}
```

`POST api.php` ger HTTP 401 utan sajtinloggning. Efter inloggning ger det HTTP 503 med felmeddelande **innan** payload läses och innan lagring används. Admin kan inte ändra status när flaggan är falsk. Detta skydd finns på servern; det är inte bara en dold knapp.

### Vilande förfrågningsflöde

När flaggan en dag öppnas innehåller koden redan validering av `checkin`, `checkout`, `guests`, `name`, `email`, `message` och honeypotfältet `website`. Den använder Bangkok-tidszon, 1–90 nätter, konfigurerat max antal gäster, konflikttest mot **bekräftade** intervall och `flock` vid läsning/skrivning. Ny förfrågan får status `pending`; admin kan i den vilande grenen sätta `pending`, `confirmed` eller `rejected`. Endast `confirmed` spärrar datum; intervallen är halvöppna `[checkin, checkout)`.

Lagringen är en JSON-array. Varje post innehåller ungefär `id`, `created_at`, `status`, `checkin`, `checkout`, `nights`, `guests`, `name`, `email`, `message` och `total`. `total` är ett historiskt snapshot av dåvarande exempelberäkning. Inget kundnamn eller e-post lämnas ut i publika GET-svaret.

Den vilande skrivkoden öppnar en existerande privat fil, låser med `flock`, validerar JSON-listan och kontrollerar skrivresultat. Den har **inte** en transaktionssäker lagringsmodell med återställning vid partiella skrivfel. Testa och stärk den innan riktiga data tas emot; överväg SQLite först när kraven motiverar det.

### Admin

`admin.php` delar den publika åtkomstspärrens PHP-session med strikt session-ID, `HttpOnly`, `SameSite=Lax` och `Secure` under HTTPS. Lösenordet hämtas från `COSMOS_ADMIN_PASSWORD`, måste vara minst 12 tecken och jämförs med `hash_equals`. Inloggning regenererar session-ID; statusändring kräver CSRF-token. Besökarinnehåll HTML-escapas.

Nuvarande SSL-värd sätter inte uttryckligen `COSMOS_ADMIN_PASSWORD` i sin vhost-fil; kontrollera hela PHP-processmiljön innan slutsats om faktisk konfiguration dras. Admin har ingen rate limiting, revisionslogg, användarroller, lösenordsåterställning eller automatisk e-post. Statusändringar är avstängda så länge `BOOKING_OPEN` är falsk.

---

## 11. Det som krävs före riktig bokning

Ändra **inte** bara `BOOKING_OPEN` till `true`. Följande behöver ett eget beslut och implementation/test:

1. Ägaren bekräftar verkliga priser, städavgift, maxkapacitet, nätter, husregler, kontaktväg och hur förfrågningar ska hanteras.
2. Exempeltexterna ersätts med granskad information på tre språk. Kontrollera också övriga påståenden, bilder och koordinatliknande designelement.
3. Integritetstext, lagringstid, radering, säkerhetskopior och återställning bestäms för personuppgifter.
4. Adminautentisering konfigureras och förstärks med begränsning av inloggningsförsök, sessionspolicy och loggning av ändringar.
5. Bokningsformulär, JavaScript-meddelanden och API-fel översätts; formulärflödet testas på mobil och med hjälpmedel.
6. JSON-skrivning/felhantering, samtidighet, korruption och filrättigheter granskas med riktiga testfall. Överväg transaktionssäker lagring om kraven ökar.
7. Bestäm hur administratör och gäst meddelas om ny förfrågan, bekräftelse och avslag. Inga mejl skickas i nuläget.
8. Testa HTTPS, privata sökvägar, otillåtna HTTP-metoder, valideringsfel, gränsdatum och dubbelbokning på slutlig server. Betalning och extern synk är separata framtida projekt.

---

## 12. Kontroller som faktiskt har gjorts

Vid senaste åtkomständringen kontrollerades följande på den tillfälliga adressen:

- Efter ombyggnaden till mobil först kontrollerades 180 kombinationer av sidor och skärmbredder (320–1440 px) utan horisontellt överflöde eller JavaScript-fel. Mobilmeny och galleri provades vid 320, 768 och 1024 px.

- Alla 18 språk- och innehållssidor skickade oinloggade besökare till `login.php`; efter rätt ord öppnades den begärda undersidan.
- Byggskriptet skapade 18 skyddade PHP-sidor och tog bort alla gamla `index.html`-filer.
- Webbläsarkontroll vid 390, 768 och 1280 px i alla tre språk gav inga JavaScript-fel eller horisontellt överflöde. Hero-knapparna låg ovanför den nedre hero-raden.
- Lazy loading för områdesbilden fungerade när sektionen scrollades in.
- API-POST gav 401 utan inloggning och 503 efter inloggning; admin krävde fortfarande separat lösenord. Tidigare säkerhetskontroll gav 403 för `/ch/storage/bookings.json`.

Tidigare kontrollerades även att 38 manifestfiler finns, att galleriet öppnas och stängs på desktop/mobil samt att URL-hjälparen ger korrekta vägar för `/`, `/ch/`, `/cosmos/`, `/cosmos-house/` och `/house/booking/`. Dessa är tidsbundna resultat, inte en garanti för en framtida driftsmiljö. Kör om relevanta kontroller efter ändringar.

---

## 13. Arbetsregler för nästa ChatGPT-session

1. Läs detta dokument, `README.md` och berörda källfiler innan ändring. Koden och aktuell serverkonfiguration är yttersta sanningen om beteendet.
2. Bevara designens palett, typografi, stora verkliga foton, asymmetriska grids, sektioner och mobilupplevelse om inte ägaren begär annat.
3. Bevara portabiliteten. Skapa aldrig hårdkodade interna URL-prefix som `/ch/assets/` eller `/ch/api.php`. Använd `url()`, `asset_url()` och PHP-sänd `window.COSMOS.base`.
4. Håll personuppgifter utanför webbroten. Förutsätt inte att `.htaccess` används. Kontrollera åtkomst via HTTP efter serverändring.
5. Förnya inte demopriser eller exempeltext till faktapåståenden utan ägarens besked. Markera det som fortfarande är provisoriskt.
6. När publik text ändras, uppdatera `page-source.php` och översättningarna i `translations.php`, kör byggskriptet och kontrollera berörda språk- och undersidor visuellt.
7. Bokningsflaggan ska förbli stängd tills checklistan i avsnitt 11 är genomförd och ägaren uttryckligen vill öppna riktiga förfrågningar.
8. Rapportera vilka filer och serverinställningar som ändrats, tester som faktiskt körts och kvarstående begränsningar. Rör inte orelaterade ändringar i moderprojektet.

**Bra startinstruktion till nästa ChatGPT:**

> Du arbetar med Cosmos House. Läs `ch/docs/COSMOS_HOUSE_CODEX_HANDOVER.md` och kontrollera aktuell kod innan du ändrar något. Sajten är flerspråkig, portabel mellan URL-nivåer och visar för närvarande endast en förhandsvisning: bokningsförfrågningar är avstängda på servern. Bevara design och 38 lokala foton, skydda privat lagring, skilj exempeltext/demovärden från bekräftade fakta och rapportera genomförda tester utan att anta att vilande bokningskod är produktionsklar.

---

*Dokumenterat utifrån den aktuella projektkatalogen och nuvarande Apache-konfiguration den 9 oktober 2026, Bangkok-tid.*
