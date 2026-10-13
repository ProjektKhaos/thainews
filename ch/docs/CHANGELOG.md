# Ändringslogg

## 2026-10-12 – Komplett personaladministration driftsatt

- Ny admin med 13 separata vyer, egen kontoinloggning, engångssetup, roller och sessionsåterkallelse.
- Riktiga manuella bokningar, gästkontakt, datum-/prisändringar, betalstatus, anteckningar, CSV, månad-/veckokalender samt kompletta pris-, rabatt- och avgiftsformulär.
- CMS med privata utkast och publicering, redigerbar engelsk landningssida/fullsite, bildval, bilduppladdning och galleriinställningar.
- Privat SQLite och migration 002 installerad; worker varje minut och verifierad databas-/bildbackup varje natt. Kontrollerad recovery med underhållslås och bevarad tidigare data.
- SMTP kan konfigureras av administratör; STARTTLS/direkt TLS testades lokalt. Publik bokning och skarp mejl är fortfarande avstängda.
- Isolerade flödestester, mobil/desktop och live-inloggning/adminvyer verifierades. Inga testkonton eller testgäster finns kvar i produktion.

## 2026-10-12 – Publik domän och engelsk landningssida version 3.02

- DNS för `cosmoshouse.online` och `www` verifierades till `85.190.102.171`. HTTPS med Let's Encrypt-certifikat för båda namnen aktiverades; HTTP och `www` omdirigerar till `https://cosmoshouse.online/`. Certifikatförnyelse torrkördes och Apache laddas om efter förnyelse.
- Publik domän tillåter endast landningssidan och dess åtta foton. Skyddade PHP-sidor och admin finns kvar på den befintliga förhandsvisningsadressen.
- Huvudbilden fick 18 px hörnradie. Språkvalet och automatisk språkväxling togs bort från landningssidan; den visas alltid på engelska.

## 2026-10-12 – Bildbaserad landningssida version 3.00

- Ägarens ZIP-paket `cosmos_house_landing_med_bilder.zip` ersatte `ch/index.html` och levererade åtta lokala WebP-foton under `ch/photos/`.
- Den förberedda domänens HTTP-värd tillåter nu exakt dessa åtta bildadresser, men fortsätter neka inloggning och backend över okrypterad HTTP.
- Live-sidan, åtta bild-URL:er, mobil/desktop, språkbyte och bildvisare verifierades. Preview och bokningsspärr är oförändrade.

## 2026-10-12 – Ägarens nya landningssida på `/ch/`

- Apache prioriterar nu `index.html` i katalogen `ch/`, medan övriga kataloger fortsätter använda sina PHP-index.
- Ägarens nya `index.html` version 2.00 lämnades oförändrad. Den tidigare `/ch/`-loginomdirigeringen ersattes av landningssidan; direkta skyddade adresser fungerar som tidigare.

## 2026-10-12 – Tillfällig landningssida

- Ny `index.html` med tre språk och en neutral CSS-illustration, publicerad på den befintliga HTTPS-adressen som direkt fil.
- Separat HTTP-värd för `cosmoshouse.online` installerad med endast landningssidan och ACME-kontroll tillåtna. Nuvarande previewlogin på `/ch/` är oförändrad.
- DNS och HTTPS för den nya domänen återstår tills domänen är registrerad och pekar mot servern; se `LANDING_DEPLOY.md`.

## 2026-10-12 – Fortsatt hårdning

- En idempotensnyckel får nu bara återanvändas med exakt samma offert och kontaktuppgifter. Ändrade uppgifter ger 409; en giltig återförsökning får ärendets aktuella status.
- Interna `TEST-`-ärenden köar inga gästsmejl vid bekräftelse, avslag, avbokning eller ombokning. Detta gäller även om SMTP senare aktiveras.
- Regressionstester för båda fallen körs i `tests/booking.php`.
- Bokningsformulärets API-fel visas nu med sakligt åtskilda meddelanden på engelska, svenska och thai. Webbläsartestet kontrollerar ogiltiga datum på svenska och thai.
- Interna testärendens pris beräknas inom samma skrivtransaktion som ärendet skapas. Ett utgånget `hold` kan inte längre bekräftas före expiry-workern hunnit märka det som utgånget.
- Mejlworkern markerar även äldre köade `TEST-`-händelser som simulerade, utan SMTP-utskick.

## 2026-10-12 – Bokningsgrund bakom stängd publik spärr

- Ny privat SQLite-modell, migration, transaktionsskydd, pris-/rabatt-/avgiftsregler, offerter, tillgänglighet, förfrågningar, revisionshistorik och mejlutkö.
- Ny admininloggning, adminformulär, sökning/filter/paginering, bokningsdetalj och interna noteringar; äldre JSON-boknings-API permanent spärrat.
- Runtime-bokningsyta byggd från gemensam mall på tre språk; 18 sidor regenererade. Befintligt galleri och 38 bilder bevarade.
- CLI för adminskapande, mejlworker, hold-expiry, backup, restore-check och separat legacy-import med dry-run.
- Isolerade DB-/HTTP-/browser-/portabilitetstester och ny driftdokumentation.

Vid bokningskodens införande gjordes inga ändringar i Thai News utanför `ch/`, produktionsdatabas eller skarp SMTP. Senare domän- och TLS-arbete redovisas ovan.
