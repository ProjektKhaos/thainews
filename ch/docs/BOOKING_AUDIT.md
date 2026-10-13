# Bokningsaudit – 12 oktober 2026

Källa: faktisk `ch/` efter implementation, jämförd med `statusmandag.zip` i missionspaketet. Baslinjens ZIP är byte för byte samma arkiv som `ch/docs/statusmandag.zip`.

## Fanns i baslinjen

18 genererade och previewskyddade sidor, tre språk, 38 WebP-bilder, galleri, mobilmeny, portabla länkar, JSON-baserad vilande bokningskod och enkel admin. `BOOKING_OPEN=false` och exempelpriser.

## Finns nu i kod

- Privat SQLite/PDO-schema och idempotent migration, transaktionsserialisering med `BEGIN IMMEDIATE`.
- Serverpris i satang, datum-/veckodagspriser, rabattregler, avgifter och 15-minuters serveroffert.
- Tillgänglighet, manuella block, holds, förfrågningar, idempotens, statusövergångar, ombokning, betalstatus och revisionslogg.
- Admininloggning med hashat lösenord, CSRF, rate limit, dashboard och formulär för centrala arbetsflöden.
- Transaktionell mejlutkö, översatta textmallar och worker med logg/sandbox som standard. SMTP kräver separat konfiguration och godkännande.
- Bokningsformulär som monteras vid runtime. `api.php` med äldre JSON-flöde är permanent spärrat.
- Isolerade tester för databas, webbrutter, öppen testbokning, parallell bekräftelse, backup och tre URL-baser.

## Status och öppna gap

Ingen fungerande ny privat bokningsdatabas har verifierats på live-servern; live-API:t svarar 503 och admin kan visa ett övergångsläge. Publik bokning är stängd. Ägarens priser, husfakta, villkor, integritetstext, kontaktuppgifter och skarpa mejl är inte godkända. Ingen skarp SMTP-leverans eller återställning på produktionsserver har testats. Se `TEST_REPORT.md` för acceptans-ID och `DEPLOY.md` för manuella driftsteg. Den gamla JSON-filen har inte lästs eller importerats.
