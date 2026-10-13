# Bokningssystem och admin

Gästen väljer datum och gäster, hämtar serveroffert, läser integritetsinformation och skickar en förfrågan med opak offerttoken och idempotensnyckel. Kvittensen säger uttryckligen att bokningen inte är bekräftad. Inga pengar tas på webbplatsen.

Admin loggar in efter previewspärren på `admin.php`. Översikten visar väntande ärenden, block och mejlkö. Admin kan skapa interna testärenden när publik bokning är stängd, blockera eller häva datum, lägga till/prioritera priser och rabatter, bekräfta/avböja/avboka, omboka med omprisning, registrera manuell betalstatus och anonymisera avslutade ärenden. Bekräftelse gör en ny konfliktkontroll i transaktion. Hold löper ut efter 24 timmar i nuvarande adminformulär; kör `scripts/expire_holds.php` periodiskt i en framtida godkänd driftsättning.

`admin_users` initieras med `scripts/create_admin.php`; det gamla delade `COSMOS_ADMIN_PASSWORD` används inte av den nya adminvyn. När ny DB saknas kan äldre env-lösenord endast öppna en begränsad läsvy för äldre JSON-ärenden. Inga konton eller priser seedas i produktion. Adminvyn har månadskalender, prissimulator, bokningssökning med status/datumfilter och paginering samt en ärendedetalj med prissnapshot, historik, mejlstatus och redigerbar intern notering. Veckokalender, CSV-export och mer avancerad adminergonomi återstår. Gästens kalender har månadsvy med veckodagsrubriker och datumfält som tangentbordsfallback.

Den gamla `api.php` accepterar inte bokningar. En eventuell import av privat äldre JSON-data måste granskas separat med `scripts/import_legacy_json.php` i dry-run och kräver explicit importflagga, privat backup och behörighet innan `--apply` används.
