# Test- och acceptansrapport – 12 oktober 2026

## Administration V1 – senaste kontroll

- Nytt isolerat `admin_http.py`: PASS första kontot/engångslänk, CSRF, konto/IP-begränsning över nya sessioner, personalroller, sessionsåterkallelse och lösenordsbyte; riktiga manuella bokningar, konfliktavslag, ombokning med överenskommet belopp, kontakt/notering/betalstatus, prisregler och säker CSV; CMS-utkast/publicering, fullgallery, uppladdning/privat bildsynlighet, backup och faktisk återställning av databas plus bilder.
- `browser_admin.cjs`: PASS alla 13 adminvyer vid 390/1280 px, veckokalender, bokningsdetalj, sparad innehållspreview och bildvisare. Inget horisontellt överflöde eller JavaScript-fel.
- `booking.php`: PASS migration/idempotens, exakta priser, intervall, parallella bekräftelser/block, outbox samt backup/restore.
- `public_flow.py`: PASS isolerat öppet bokningsflöde, offert, idempotent förfrågan, prisändring från admin, CSRF/Origin/Content-Type och UI vid 390 px.
- `portability.py`: PASS `/`, `/ch/`, `/alpha/beta/`.
- `smtp.py`: PASS lokal STARTTLS och direkt TLS med certifikatverifiering, autentisering och leverans. Inget externt mejl skickades.
- Live: PASS personalinloggning, 13 adminvyer och 6 skyddade engelska previewsidor vid 390/1280 px. Publik bokningsflagga var false. Landningssida och förhandsadress gav 200; engelska, laddad huvudbild och 18 px radie verifierades. Testkontot och dess sessioner togs bort efteråt.
- Produktion: privat databas migrerad två gånger, fungerande worker och backup-service, verifierad backup, 0 äldre JSON-ärenden. Inga testgäster eller testmejl lades in i produktion. Egen SMTP-leverantör och faktiska verksamhetsvärden är inte ifyllda och publik bokning är stängd.

## Tidigare kodprov och acceptansmatris

Historiken nedan beskriver testerna före backendens driftsättning; exempelvis uppgifterna om att produktionsdatabas och cron inte aktiverats är nu historiska. En PASS-rad avser sitt beskrivna prov och ersätter inte verksamhetens konfiguration eller en separat full sajtlansering.

**Miljö:** PHP 8.3.6 med `pdo_sqlite`, isolerad SQLite i tillfällig privat katalog; dummy SMTP-värden i lokal fixture; Playwright/Chromium för UI. Livekontroll endast av preview och stängt bokningsläge. Produktions-DB, SMTP, cron och backup har inte aktiverats.

**Fortsättning 12 oktober:** `php ch/tests/booking.php` kördes om från projektroten och passerade efter tillagda regressioner för ändrad idempotent retry (409), aktuell status vid giltig retry och uteblivna gästsmejl från interna `TEST-`-ärenden vid bekräftelse/ombokning. `python3 ch/tests/public_flow.py` passerade med nya webbläsarfall för lokaliserat 422-fel på svenska och thai vid 390 px. `python3 ch/tests/admin_http.py`, `python3 ch/tests/portability.py` och `node ch/tests/browser.cjs` passerade också. PHP- och JavaScript-syntaxkontroller passerade för ändrade filer. Tidigare resultat nedan är fortfarande tidsbundna; ingen skarp SMTP- eller produktionsdatabas verifierades i denna fortsättning.

`booking.php` kontrollerar även att ett utgånget hold inte kan bekräftas innan expiry-workern körs. Interna testärendens pris- och konflikttest körs nu i samma databastransaktion som insättningen.

Ett injicerat äldre `TEST-`-mejl i outboxen kontrollerades i SMTP-läge med nekad anslutning: händelsen fick status `simulated`, medan de två riktiga fixturemeddelandena fick status `failed` för återförsök. Inget externt mejl skickades.

Körda kommandon och observerat resultat:

```text
php tests/booking.php                 PASS migration, pris, offert, idempotens, parallell confirm, outbox, backup/restore
python3 tests/admin_http.py            PASS preview, adminlogin, CSRF, rate limit, prisändring, internt testärende, sökning/detalj/notering, HTML-escape, admin UI 390/1024 px
python3 tests/public_flow.py           PASS isolerad öppen testbokning via API och browser vid 390 px, negativ CSRF/Origin/Content-Type
python3 tests/portability.py           PASS /, /ch/, /alpha/beta/
node tests/browser.cjs                 PASS 18 sidor vid 320, 390, 651, 768, 901, 1024, 1440 px; 38 bild-URL:er, galleri
find . -name '*.php' ... php -l       PASS inga syntaxfel
node --check assets/*.js               PASS
```

`PASS` avser exakt testet i raden, inte bredare produktionsklarhet. `DELVIS` betyder att minst ett kravfall saknar test eller implementation. `EJ KÖRT` är inte godkänt.

| ID | Status | Bevis eller kvarstående prov |
|---|---|---|
| CORE-01 | PASS | 18 sidor regenererade; 38 bilder kvar; browser-rundtur. |
| CORE-02 | PASS | `portability.py` i tre URL-baser. |
| CORE-03 | PASS | `admin_http.py`, live POST 503, intern testbokning. |
| DB-01 | PASS | Upprepad migration i `booking.php`. |
| DB-02 | PASS | Injicerat auditfel rullar tillbaka statusbyte. Diskfel är inte simulerat separat. |
| DB-03 | PASS | Två separata processer, en enda `confirmed`. |
| DB-04 | PASS | Två intilliggande bekräftade bokningar och halvöppet blockgränstest. |
| DB-05 | PASS | Aktiv hold blockerar; utgången hold frigör och expiry-worker uppdaterar status. |
| DB-06 | DELVIS | DB-path utanför DocumentRoot krävs; live `/ch/storage/bookings.json` gav 403; klass/schema/widget gav 404. Slutlig serveraudit kvar. |
| PRICE-01 | PASS | Baspris och tre olika nattliga datumregler testade. |
| PRICE-02 | PASS | 1/6/7/14/28/29 nätter testade mot två rabatttrappor. |
| PRICE-03 | PASS | Procent, fast avdrag, nollgolv, fast avgift och procentbas testade i satang. |
| PRICE-04 | PASS | Två matchande familjer ger en rabatt när stacking är av; två när den uttryckligen slås på. |
| PRICE-05 | PASS | Utgången/ändrad offert nekas; klientens manipulerade total ignoreras. |
| AVAIL-01 | PASS | Block mitt i period och hävning via tjänst. |
| AVAIL-02 | PASS | Två samtidiga `inquiry` på samma datum skapades. |
| BOOK-01 | PASS | Isolerat HTTP-flöde, referens, snapshot, historik. |
| BOOK-02 | PASS | Samma idempotensnyckel ger samma referens. |
| BOOK-03 | DELVIS | Ogiltig e-post, saknad integritetsbekräftelse och guestcount testade; bredare PII-fuzz kvar. |
| BOOK-04 | PASS | Avböjning, avbokning, ombokning, revisionspost och kalenderförändring testade. |
| ADMIN-01 | DELVIS | Hashad login/session, nekad CSRF och sex felaktiga inloggningsförsök via HTTP; sessionsbaserad spärr kräver hårdare servergranskning. |
| ADMIN-02 | PASS | Pris/rabatt ändras i admin; ny intern prisberäkning och ny publik quote ändras utan språkbygge. |
| ADMIN-03 | PASS | Samtidig bekräftelse och blockering ger en vinnare, en konflikt. |
| MAIL-01 | DELVIS | Outbox och simulerad leverans testad; skarp SMTP ej körd. |
| MAIL-02 | PASS | Nekad SMTP-anslutning efter DB-commit lämnar bokning kvar, markerar två misslyckade händelser; retry simuleras utan ny bokning. |
| LANG-01 | DELVIS | Stängt läge på tre språk, öppet EN-flöde; full Thai/SV-form och mailgranskning kvar. |
| UX-01 | DELVIS | 320/390/651/768/901/1024/1440 px i stängt läge, öppen EN/SV/TH vid 390 px och admin vid 390/1024; fördjupat tangentbordstest kvar. |
| MEDIA-01 | PASS | 38 bild-URL:er gav 200; filter/ljuslåda, Escape och återfört fokus provade. |
| SEC-01 | DELVIS | Preview/API/adminspärr, privat DB-path, direkt klass/schema/widget 404; full serveraudit kvar. |
| SEC-02 | DELVIS | Negativ CSRF, Origin och Content-Type via HTTP; intern notering med script-tag escape-testad. Bredare XSS/SQLi/open-redirect-prov kvar. |
| OPS-01 | PASS | `backup.php`, `restore_check.php`, återställningskopia och antal verifierade. |
| OPS-02 | PASS | Default av; samtliga godkännanden, policy och SMTP krävs. |
| OPS-03 | PASS | Setup-, API-, pris-, säkerhets-, privacy-, recovery- och ägardokument finns. |

**Omdöme:** tekniskt implementationsklart **DELVIS**. Publik lansering **NEJ**. Kör och dokumentera återstående acceptanstester innan ägaren överväger aktivering.
