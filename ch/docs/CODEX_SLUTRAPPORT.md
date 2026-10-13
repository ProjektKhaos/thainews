# Codex slutrapport – Cosmos House, 12 oktober 2026

## Aktuell leverans: administration V1

Backend är driftsatt på `https://cosmoshouse.online/admin.php` med privat databas, egen inloggning, personalroller, full bokningsadministration, månad-/veckokalender, prisregler, CMS-utkast/publicering, bildhantering, SMTP-inställningar, aktivitet och full backup/recovery. Driftjobb och backup-timer är aktiva. Alla 13 adminvyer och sex skyddade engelska previewsidor verifierades på live vid mobil/desktop. Äldre lagring innehöll 0 ärenden. Engångslänk för första ägarkontot finns privat och inga testkonton eller testgäster finns kvar i produktionsdatabasen.

Verkliga verksamhetsvärden och SMTP-uppgifter fylls i genom admin. Lokal SMTP/TLS och databas-/bildåterställning är testade; skarp mejlleverans genom ägarens leverantör återstår. Publik bokning och full sajtlansering är fortsatt stängda. Aktuell dokumentation: `BACKEND_DEPLOY.md`, `ADMIN_MANUAL.md`, `TEST_REPORT.md`.

## Historik före driftsättning

Följande avsnitt beskriver den tidigare kodleveransen och dess dåvarande begränsningar. Uppgifter om saknad databas, cron eller administration gäller inte längre det aktuella driftläget.

### Tidigare status

**Fortsättning 12 oktober:** Idempotent återförsökning validerar nu oförändrad offert och kontaktdata, och interna testärenden kan inte köa gästsmejl vid statusändring eller ombokning. Fel i bokningsformuläret skiljs åt på engelska, svenska och thai. Isolerade regressionstester och webbläsartester passerade. Detta ändrar inte lanseringsbedömningen nedan.

- Tekniskt implementationsklart: **DELVIS**. Kärnan fungerar i isolerade tester, men acceptansmatrisen har flera DELVIS/EJ KÖRT.
- Godkänt för publik lansering: **NEJ**. Ägarfakta, privacy, priser, SMTP och driftbeslut saknas.
- Publik bokningsflagga: ingen `COSMOS_BOOKING_PUBLIC_ENABLED=1` i faktisk servermiljö verifierad; live-POST gav 503. Databasflagga sätts till 0 i migration.
- Preview-lås: aktivt; webbläsartest på live lyckades först efter previewinloggning.

## Genomförda missioner

00–04: faktisk baseline jämförd, SQLite vald, migrationer/priser/kalender/booking byggda. 05: admin för centrala arbetsflöden med månadskalender, prissimulator, sökning, paginering, ärendedetalj och interna noteringar; veckovy, export och ytterligare ergonomi kvar. 06: outbox och worker byggda, endast simulerad/felande SMTP testad. 07: gästdel tre språk med neutral placeholder för integritet; ägartexter saknas. 08: grundläggande autentisering och felhantering, full riktad säkerhetsgranskning kvar. 09: testsvit finns, resultat per ID i `TEST_REPORT.md`. 10: driftdokument/backup/restore finns; kodfilerna ligger i den befintliga live-previewkatalogen, men ingen produktions-DB, cron, SMTP eller publik bokning har aktiverats.

## Live-stödpunkter

PHP 8.3.6 och `pdo_sqlite` finns i CLI. Den privata befintliga katalogen `/var/lib/cosmos-house` finns men dess JSON-fil kunde inte läsas av denna användare. Ingen fungerande ny DB, SMTP-konfiguration eller ny adminanvändare har verifierats på live. Den faktiska PHP-processmiljön har inte lästs. Inga hemligheter skrivs ut här.

## Ändringar och återställning

Ändringar finns endast under `ch/`: 38 nya filer och 30 ändrade jämfört med `statusmandag.zip`; inga baslinjefiler togs bort. De 38 husbilderna är oförändrade. Nya huvuddelar är `app/Booking.php`, `db/migrations/001_initial.php`, `booking_api.php`, `booking_widget.php`, `admin_bookings.php`, `admin_extra.php`, CLI-skript, tester och dokument. Ändrade huvuddelar är `admin.php`, `access.php`, `login.php`, `page-source.php`, `translations.php`, `assets/` och de 18 genererade språkfilerna. `statusmandag.zip` är baslinje för kodåterställning och arkivet passerar integritetstest. Ny DB är inte driftsatt, så ingen produktionsdatamigration eller datarollback behövdes. Framtida DB-rollback beskrivs i `DEPLOY.md`. `ch/` är fortfarande oversionerad i moderprojektets Git och inga commits har skapats; gör en kontrollerad release innan driftbyte. Befintliga ändringar utanför `ch/` i moderprojektet lämnades orörda.

## Nästa grind

Åtgärda DELVIS/EJ KÖRT i `TEST_REPORT.md`, fyll i `AGARBESLUT.md`, provisionera privat DB/admin/backup och testa skarp miljö separat. Handhavandet finns i `ADMIN_MANUAL.md`. Publik bokning och skarpa mejl ska fortsätta vara avstängda tills separat instruktion ges.
