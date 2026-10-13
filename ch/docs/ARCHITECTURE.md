# Arkitektur – boknings-V1

En fastighet, PHP 8.3 och SQLite via PDO. Ingen ramverksmigration. `app/Booking.php` äger pris, tillgänglighet, offert, bokning och transaktioner. `db/migrations/001_initial.php` definierar tabeller och nekar direkt HTTP-åtkomst. `booking_api.php` exponerar gästens status, tillgänglighet, offert och förfrågan. `admin.php` hanterar interna åtgärder. `booking_widget.php` renderas vid varje request på de tre genererade bokningssidorna; därför kräver nya priser och blockeringar inget språkbygge. `assets/booking.js` visar kalender och prisuppställning.

`COSMOS_DB_PATH` måste peka på en existerande privat SQLite-fil utanför projekt och DocumentRoot. Saknas den returnerar boknings-API 503 och bokningssidorna visar stängt läge. Migration körs separat via CLI. SQLite `BEGIN IMMEDIATE` serialiserar varje bekräftelse, blockering och ombokning runt konfliktkontroll och skrivning. Intervall är halvöppna `[check_in, check_out)`. Vanliga förfrågningar blockerar inte datum; aktiv hold, bekräftad bokning och aktiv manuell blockering gör det.

Publik bokning öppnas bara när miljöflaggan `COSMOS_BOOKING_PUBLIC_ENABLED=1`, DB-inställningen `booking_public_enabled=1`, samtliga ägargodkännanden, godkända integritetstexter och SMTP-konfiguration finns. Previewinloggningen är en separat spärr. Inga CLI- eller adminformulär aktiverar den publika flaggan automatiskt.

Mejlhändelser skapas i samma databastransaktion som en förfrågan eller statusändring. `scripts/mail_worker.php` levererar senare. Logg/sandbox simulerar utan nätverksutskick; SMTP kräver dessutom `COSMOS_MAIL_APPROVED=1` och öppet publikt läge. SMTP ger inte matematiskt exakt-en-gång-garanti om servern accepterar ett mejl men processens statusuppdatering kraschar; admin måste granska osäkra retryfall.
