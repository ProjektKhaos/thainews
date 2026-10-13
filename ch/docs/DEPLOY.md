# Drift och återställning

**Backend är nu driftsatt.** Aktuella serverfiler, databas, kontoflöde, schemalagda jobb, backup och recovery beskrivs i [BACKEND_DEPLOY.md](BACKEND_DEPLOY.md). Den tillfälliga landningssidan är publik; full sajt och publika bokningsförfrågningar väntar på separat lansering och faktiska verksamhetsinställningar.

Vid flytt behövs PHP 8.3 med PDO SQLite, mbstring, GD, OpenSSL och Phar, HTTPS, skyddade interna kataloger och privat lagring utanför DocumentRoot. Ange `COSMOS_DB_PATH`, `COSMOS_BACKUP_DIR` och `COSMOS_DOCUMENT_ROOT`, kör migrationerna två gånger, skapa ett individuellt administratörskonto och verifiera backup/recovery. Kopiera originalbilder och programkod från releasekopian samt återställ databas/uppladdningar från den privata fullbackupen.

Adminformulär, priser, publicerade texter och kalenderblock lagras i databasen och kräver inget språkbygge. `scripts/build_languages.sh` behövs när själva sidmallen ändras. Kör portabilitetstestet efter byte av URL-bas.

Publik bokning ska öppnas först efter ägarens verkliga inställningar, godkända villkor, fungerande SMTP och ett separat lanseringsbeslut. Den gamla `COSMOS_ADMIN_PASSWORD`-inloggningen används inte av den nya administrationen. Personalen får egna databasbaserade konton.
