# Driftsatt administration – 12 oktober 2026

Backend V1 är driftsatt på `https://cosmoshouse.online/admin.php`, med eget konto och lösenord per användare. Inloggningen kräver inte längre det delade previewordet. En godkänd admininloggning ger även tillgång till den skyddade fullständiga engelska sajten. Den publika roten visar fortsatt den tillfälliga engelska landningssidan.

## Installerad drift

- PHP 8.3 med PDO SQLite, mbstring, GD, OpenSSL och Phar. Webbservern är Apache med HTTPS.
- Privat runtime-konfiguration: `/etc/cosmos-house/runtime.php`, root:www-data 0640. Explicit `COSMOS_DB_PATH` i miljön går före serverkonfigurationen, så tester och flyttbara installationer kan isoleras.
- Databas: `/var/lib/cosmos-house/cosmos.sqlite`, www-data:www-data 0600. Migration 001 och 002 är installerade. Upprepad migration bevarar data och flaggor.
- Uppladdade bilder: `/var/lib/cosmos-house/media/`, 0700; bilder 0600. Publika bildsvar levereras av `media.php` endast om bilden är publicerad; annars krävs en aktiv personalsession.
- Backup: `/var/lib/cosmos-house/backups/`, 0700. Varje TAR innehåller databas, uppladdade bilder och en SHA-256-manifestfil. Integritet, främmande nycklar och varje bild verifieras vid återställningskontroll.
- `cosmos-house-worker.timer` kör hold-expiry och mejlkö varje minut. `cosmos-house-backup.timer` kör nattlig backup kl. 03:15 Asia/Bangkok. Tjänsterna kör som www-data med begränsad skrivåtkomst till privat lagring.
- Apache begränsar publika sökvägar. Admin, dess CSS/JS, bildserver och skyddad engelska preview är tillåtna; källkod, databaser, dokument, tester och driftfiler nekas. Adminens query-parametrar utelämnas ur accessloggen. `index.html` är landningssidans mall; `/` och `/index.html` renderar mallen genom `public.php` med publicerat innehåll från databasen.
- Äldre JSON-filen bevaras. Kontroll visade **0 äldre ärenden**, så ingen import behövdes.

## Första konto och återställning av konto

Den privata engångslänken finns i `/var/lib/cosmos-house/admin-setup-link.txt` (root 0600) och levereras till ägaren. Den gäller i 48 timmar. Bara en hash lagras i databasen; första lyckade kontoskapandet förbrukar alla kvarvarande setup-token. Ingen standardinloggning eller administratör med delat lösenord har installerats.

Om länken löpt ut innan kontot skapats kan serveroperatören köra `sudo php ch/scripts/issue_setup.php` från projektroten och förmedla den nya länken privat. När ett konto redan finns används **Staff accounts**, eller CLI-verktyget `scripts/manage_admin.php reset-password ANVÄNDARE` med `COSMOS_ADMIN_MAINTENANCE=1`. CLI frågar efter lösenordet interaktivt.

## Verifierade flöden

`php ch/tests/booking.php`, `python3 ch/tests/admin_http.py`, `python3 ch/tests/public_flow.py`, `python3 ch/tests/portability.py` och `python3 ch/tests/smtp.py` kontrollerar isolerade databaser och lokala SMTP-fixtures. Administrationstestet omfattar första konto, CSRF, inloggningsbegränsning mellan nya sessioner, personalroller, sessionsåterkallelse, manuella bokningar, konflikter, ombokning, betalstatus, prisregler, CSV, innehållsutkast/publicering, uppladdning, bildsynlighet samt verifierad backup och faktisk återställning.

Chromium kontrollerade alla 13 adminvyer vid 390/1280 px. Livekontrollen verifierade inloggning, alla adminvyer, sex skyddade engelska previewsidor, stängd bokningsflagga, samt landningssidans engelska språk och 18 px hörnradie. Det tillfälliga kontrollkontot och dess sessioner raderades efter provet. Inga skarpa bokningar eller gästsmejl skapades under livekontrollen.

SMTP testades mot lokala TLS-servrar, inklusive certifikatverifiering, autentisering och leverans över STARTTLS respektive direkt TLS. Verklig leverans genom ägarens mejlleverantör återstår tills SMTP-uppgifterna fyllts i. Produktionsdatabasen innehåller inga antagna priser eller kapacitetsvärden, inga gäster och inga mejlhändelser från testerna.

## Backup och recovery

Skapa backup: `sudo -u www-data php ch/scripts/backup_backend.php`.

Verifiera en backup: `sudo -u www-data php ch/scripts/restore_backend.php /privat/sökväg/backup.tar --check-only`.

Återställning är ett uttryckligt driftkommando: `sudo env COSMOS_ADMIN_MAINTENANCE=1 php ch/scripts/restore_backend.php /privat/sökväg/backup.tar --restore`. Verktyget verifierar arkivet före förändring, går in i underhållsläge, väntar på pågående databasjobb och bevarar tidigare databas/bilder med `.before-restore-…`-namn. Det återkallar gamla personalsessioner och stänger publik bokning och skarp mejl innan data installeras. Logga sedan in, kontrollera kalender/ärenden och mejlkö, och ta en ny backup innan drift öppnas igen. Programkod och originalbilder återställs från den separata privata releasekopian under `/var/backups/cosmos-house/releases/`.

## Kvarvarande konfiguration för verksamheten

Ägaren fyller i faktiska husregler, kapacitet, priser, kontakt, policies och SMTP genom adminpanelen och skapar personalens konton. Den tillfälliga landningssidan ersätter ännu inte full sajtlansering. Både `COSMOS_BOOKING_PUBLIC_ENABLED=0` på servern och `booking_public_enabled=0` i databasen hålls stängda under denna period. Full publik bokning kräver ett separat lanseringsbeslut och verifierad mejlleverans. Ingen onlinebetalning eller extern kalender-/OTA-synk ingår i V1.
