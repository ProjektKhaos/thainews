# Tillfällig landningssida och domän

**Status 12 oktober 2026:** `https://cosmoshouse.online/` visar den tillfälliga engelska landningssidan (`ch/index.html`, version 3.02) och åtta lokala foton. Huvudbilden har 18 px rundade hörn. Svenska och thai är inaktiverade på landningssidan; tidigare sparade språkval i webbläsaren påverkar den inte.

## DNS och HTTPS

- `cosmoshouse.online` och `www.cosmoshouse.online` hade redan A-poster till `85.190.102.171` hos One.com när serverkonfigurationen slutfördes. DNS-posterna ändrades inte här.
- Let's Encrypt-certifikatet omfattar båda namnen och löper ut 10 januari 2027 kl. 09:08 UTC. Certbot har automatisk förnyelse via `certbot.timer`; deploy-hooken `/etc/letsencrypt/renewal-hooks/deploy/cosmoshouse-apache-reload.sh` laddar om Apache efter förnyelse. Torrkörning av förnyelse lyckades.
- HTTP på båda namnen och HTTPS på `www` omdirigerar permanent till `https://cosmoshouse.online/`. HTTP-vägen `/.well-known/acme-challenge/` är tillgänglig för certifikatförnyelse.
- HTTPS-värden tillåter landningssidan, bildresurser, egen personalinloggning på `/admin.php` och den skyddade engelska fullsite-previewn. Källkod, dokument, interna kataloger och databasfiler nekas. Se `BACKEND_DEPLOY.md` för den nya administrationen.

## Filer och förhandsvisning

- Landningssidans mall ligger i `ch/index.html`; `/` och `/index.html` renderas genom `public.php` med publicerat CMS-innehåll. De åtta WebP-bilderna ligger i `ch/photos/` och kommer från ägarens `cosmos_house_landing_med_bilder.zip`.
- Apache-konfigurationerna finns i `ch/deploy/cosmoshouse.online.conf` och `ch/deploy/cosmoshouse.online-ssl.conf`; installerade kopior ligger i `/etc/apache2/sites-available/` och båda värdarna är aktiverade.
- `https://thainews.aberg.online/ch/` visar också landningssidan. Den skyddade förhandsvisningen nås efter personalinloggning eller via `https://thainews.aberg.online/ch/login.php` och dess PHP-sidor. Publik bokning och skarp SMTP är fortfarande avstängda.

## Verifiering

- DNS kontrollerades via lokal resolver, 1.1.1.1 och 8.8.8.8. Certifikatets båda namn kontrollerades med TLS-klient.
- HTTP- och `www`-omdirigeringar, HTTPS-startsida, åtta bilder, ACME-väg och spärrade privata sökvägar kontrollerades med HTTP-anrop. `apache2ctl configtest` gav `Syntax OK`.
- Mobil och desktop kontrollerades i Chromium: huvudbilden laddades med 18 px hörnradie, utan horisontellt överflöde eller JavaScript-fel. Bildvisaren fungerar. Landningssidan ska vara på engelska oavsett webbläsarspråk eller tidigare lokalt språkval.
