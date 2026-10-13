# COSMOS HOUSE – MASTER MISSION TILL CODEX

**Version:** 1.01  
**Datum:** 2026-10-10  
**Projekt:** Cosmos House, Chiang Mai  
**Tillfällig URL:** https://thainews.aberg.online/ch/  
**Projektkatalog enligt statusrapport:** `/var/www/abergonline/thainews/ch`

## Instruktion till Codex

Det här är den sammanhållna huvudmissionen. Läs och genomför samtliga delmissioner nedan i nummerordning. Samma innehåll finns även som åtta separata Markdown-filer i katalogen `missions/` för enkel stegvis exekvering. De separata filerna är originalinnehåll och utgör kravspecifikationen; vid eventuell skillnad gäller de separata filerna.

**Kärnkrav:** implementera ett komplett administrerbart bokningssystem med datumkalender, säker reservationshantering, flexibel prissättning och långtidsrabatter, administration, e-post, tre språk, tester och dokumentation. Bevara existerande design och flyttbarhet mellan valfria kataloger. Behåll publik bokning stängd tills ägaren uttryckligen godkänt lanseringen. Ändra inte orelaterade Thai News-filer eller serverkonfiguration utan uttryckligt tillstånd.

## Uppdragsordning

1. Inventering och arkitektur
2. Databas, transaktioner och säkerhet
3. Pris- och rabattmotor
4. Kalender och bokningsflöde
5. Administration
6. E-post, språk och integritet
7. Tester, acceptans och överlämning

---


---

# Källa: missions/00_README_START_HERE.md

# COSMOS HOUSE — KOMPLETT BOKNINGSSYSTEM
Version 1.00 | 2026-10-10 | Auftrag till Codex av Klasse & Åberg

## Källstatus och avgränsning
Detta missionspaket baseras på användarens `status.md`, daterad 2026-10-10. Det är **inte** en oberoende kod-, drift- eller webbserverrevision. Läs först faktisk kod och `COSMOS_HOUSE_CODEX_HANDOVER.md`, `README.md`, `config.php`, `api.php`, `page-source.php`, `translations.php`, `scripts/build_languages.sh` på servern och verifiera vad som redan existerar. Återanvänd fungerande delar, ersätt inte dem slentrianmässigt.

Rapporten säger: projekt `/var/www/abergonline/thainews/ch`, preview `https://thainews.aberg.online/ch/`; 18 genererade PHP-sidor (sv/en/th), 38 WebP-bilder; gemensam `page-source.php` och `translations.php`; `BOOKING_OPEN=false`; inget aktivt bokningsformulär, ingen betalning, inga automatiska mail eller kalendersynk; `config.php` har obekräftade exempelvärden; bokningsdata tänkt utanför webroot, tidigare `/var/lib/cosmos-house/bookings.json`; aktiva filer saknar egen spårbar versionshantering enligt rapporten.

## Absoluta regler
1. Gör Cosmos House till en **fullständig administrationsbar bokningslösning**, inte en demo. Bevara befintlig design, bildgalleri, språk och sidstruktur. Ingen hårdkodad `/ch/` i någon URL; flyttbar mellan `/`, `/ch/`, `/cosmos/` eller flera nivåer via central base URL/url()-helper; privata filesystemvägar separat.
2. Projektkatalogen är DocumentRoot om användaren inte uttryckligen anger annat. Stötta också installering i undermapp under annan DocumentRoot utan att bryta intern navigering.
3. **Ändra inte** andra Thai News-filer/projekt. Ingen DNS/vhost/Apache/TLS/systemändring eller skarp mail-/betalningskoppling utan uttryckligt godkännande. Om SQL-databas/privat katalog saknar provisionering: skapa migrationsfiler och tydliga instruktioner, aldrig gissa produktionsuppgifter.
4. Behåll `BOOKING_OPEN=false` och preview-låsning tills ägaren godkänt riktiga husfakta, priser, bokningsregler, integritetstext och lansering. Systemet ska kunna testas med fixture-/testdata utan att oavsiktligt erbjuda bokning publikt. Sätt aldrig skarp bokning aktiv automatiskt.
5. Gör funktionerna konfigurerbara i admin med användarvänliga formulär, förhandsgranskning och tydlig validering; ingen kodredigering för rabatter, priser, tillgänglighet eller bokningsvillkor.
6. Vid ändring av PHP/HTML: tydlig versionsrad på rad 2 med senast uppdaterad (datum/tid). Pedagogiska svenska kodkommentarer på ändringskänslig logik. Versionshantera migrationer och dokumentera filändringar.
7. Leverera fungerande implementation, inte enbart förslag. Arbeta i ordning 01–07; läs varje missionsfil. Vid hinder: färdigställ säkra delar och redovisa exakt vad som blockerar.

## Körordning
01 Inventering & designkontrakt → 02 databas och säkerhet → 03 pris-/rabattmotor → 04 kalender/bokningsflöde → 05 adminpanel → 06 meddelanden och språk → 07 tester, dokumentation och acceptans.

## Viktiga beslut
- Primär valuta THB; spara belopp som heltals-*satang* eller exakta decimaler, aldrig flyttalsavrundningar.
- Lokal tidszon `Asia/Bangkok`.
- Förfrågan, preliminär reservation och **bekräftad** bokning är olika status; aldrig anta att en förfrågan är accepterad eller betald.
- V1 omfattar **manuell bekräftelse och betalning utanför sajten**, utan onlinebetalning. Bygg integrationspunkter men inga falska betalningsbekräftelser.
- Långtidsrabatter: obegränsat antal konfigurerbara trappsteg med minsta/ev. största nätter samt procentsats eller fast THB-belopp. Priseffekt, kombinationsregler och tak specificeras i 03.
- Inga fastslagna skarpa priser eller husregler i dokumentet: demonstrativa exempel är märkta DEMO.

## Leveransformat
Efter varje del: ändrade filer, migrationer, beslut, bevis för test, kvarstående risker och nästa steg. Skapa `docs/BOOKING_SYSTEM.md`, `docs/PRICE_RULES.md`, `docs/DEPLOY.md`, `docs/TEST_REPORT.md` och `docs/CHANGELOG.md`.


---

# Källa: missions/01_INVENTORY_ARCHITECTURE.md

# Mission 01 — Granska kod och fastställ arkitektur

## Uppdrag
- Inventera faktisk katalogstruktur, beroenden, `config.php`, `api.php`, session/login/admin, `page-source.php`, `translations.php`, bildgalleri, build-script, eventuell datafil och `.htaccess`/serverberoenden. Rapportera konkret vilka funktioner som redan finns respektive saknas. Kontrollera status.md mot kod; lita inte på att status=implementation.
- Kartlägg exakt varför genererade 18 sidor måste byggas via `scripts/build_languages.sh`; framtida bokningskomponenter och språktexter ska uppdateras i källorna, aldrig bara i genererad HTML.
- Bestäm en enkel PHP 8.2+ arkitektur för modellen: DB/PDO, PricingService, AvailabilityService, BookingService, Admin, Mailer/Notification och central config/helpers. Följ kodbasens stil så långt rimligt; inga orelaterade teknikbyten.
- Specificera URL-base-path: root, en nivå och två nivåer. Alla href, action, image, CSS/JS URL, AJAX/fetch, redirects, API och admin ska använda samma bas. Separera URL-path från filesystem-path. Ingen automatisk antagelse att `public/` är webroot.
- Föreslå SQLite (med välfungerande transaktioner) eller MariaDB/MySQL med PDO utifrån verklig servermiljö och befintlig kod; om användaren redan har DB-stack, föredra den. Dokumentera valet i ADR. Den officiella källan säger inte att DB redan finns.
- Inspektera preview-/adminskydd och säkerställ separat administration och verklig autentisering med password_hash, CSRF och rate limits.

## Resultat
Inventeringsrapport i `docs/BOOKING_AUDIT.md` med checklista och exakt fil-/funktionshänvisning; implementationsplan; schemaförslag och migrationsordning. Följande missionsfiler är bindande krav, men anpassa filnamn till faktisk kod.


---

# Källa: missions/02_DATABASE_SECURITY.md

# Mission 02 — Datamodell, transaktioner och säkerhet

## Datamodell (normaliserad och migrerbar)
- properties/settings (förbered stöd för ett hus nu; överkonstruera inte multi-property UI).
- admin_users/roles och säkra sessioner.
- bookings: publik slumpmässig referens, namn, e-post, telefon frivillig, språk, antal vuxna/barn (om relevant), check_in, check_out, guest_count, status, tidsstämplar, notes interna, anonymiseringsmarkörer och snapshots.
- booking_status_history / audit_log: vem, när, från→till, orsak, inga hemligheter.
- availability_blocks: start/slut, orsak, skapad av, valfri anteckning; blockerade datum kan inte bokas.
- rate_plans / nightly_rate_rules: baspris, specifika datum, veckodag, helg och säsong, giltighetsperiod och prioritet.
- discount_rules: typ `length_of_stay`, `early_bird`, `last_minute`, `date_promo`, `coupon`, `manual`; min/max nätter, dagar till ankomst, datumfönster, procent/fixbelopp, cap, prioritet, aktiv, kombinera-flagga, minbelopp, rabattkod/kvot vid behov, tidsstämplar.
- fees_and_taxes: städning, extra gäster, avgifter, skatt om beslutad; obligatoriska/valfria och korrekt visade.
- quotes: beräkningssnapshot, giltighetstid, servergenererad summa, regel-ID/versionssnapshot, integritetsvänlig token, idempotency key.
- notification_log/outbox: meddelandetyp, bokningsreferens, kanal, mottagaridentitet restriktivt, retrystatus (aldrig lösenord/kompletta känsliga payloads i logg).
- policies / localized content / consent receipts där det behövs för integritet, retention och avbokningsvillkor.

## Invarianta krav
- DB eller lagringsfiler utanför publik DocumentRoot. Ingen direkt HTTP-hämtbar bokningsfil. Om Apache `AllowOverride None`: `.htaccess` är inte tillräckligt skydd.
- Prepared statements; inputvalidering; kontextanpassad escaping; CSRF på state-changing admin och sessionsbundna formulär; begränsa anropsfrekvens; säker cookie/headers; utloggning och sessionregenerering.
- Transaktionssäker dubbelbokningsspärr för bekräftelse: servervalidera alla nätter, inklusive samtidiga requests. Använd lås/serialization som faktiskt fungerar för vald DB och test med parallella bokningar. Datumintervall är [check-in, check-out), vilket tillåter samma-dag-utcheckning/incheckning.
- Pending `request` reserverar INTE kalendern om admin inte explicit sätter hold. `hold`/`confirmed` blockerar datum; holds kan ha expiry. `cancelled`/`rejected` blockerar inte; ha definierat beteende för expirations och outbox.
- Säkra transitions; auditlogg; idempotenta API-anrop; adminvalidering av datum/gränser; säkert felmeddelande utan stacktrace till gäster.
- Dataminimering, åtkomstkontroll, retention/radering/export för gästens uppgifter och backup/restore-dokumentation. Läs inte ut personuppgifter i offentliga availability-svar.
- Migrerings- och rollbackstrategi. Sekretessvariabler i icke-committad config/env. Redovisa exakt vilka serverfiler som behöver admin-godkännande.

## Acceptans
Publik `/storage/bookings.json` ej åtkomlig; obehörig admin åtkomst nekas; testad rollback; två konkurrerande bekräftelser kan inte lyckas för överlappande datum; utcheckningsdagen är fri för nästa gäst.


---

# Källa: missions/03_PRICING_DISCOUNTS.md

# Mission 03 — Fullständig dynamisk prismotor och rabatter

## Målet
En serverauktoritativ och deterministisk `PricingService` som ger exakt samma pris i publik kalendersammanställning, bokningsförfrågan och admin, och som bevarar pris-/regel-snapshot vid bokning. Alla regler hanteras i admin utan kodändring.

## Prisbyggstenar
1. Baspris per natt THB; regler för vardag/helg enligt konfigurerbara veckodagar.
2. Säsonger med från/till (ex. hög-/lågsäsong), datum-specifika nattpriser, specialdagar. Tydlig prioritet: datumoverride > säsongsregel > veckodag/helg > baspris; admin visar konflikter och löser lika prioritet deterministiskt (förbjud eller explicit prioritetsordning).
3. Min/max nätter globalt och per datum/säsong, minsta förvarning, maximal bokningshorisont, blockerade ankomst/avresedagar, bytesdagar när aktiva regler kräver det.
4. Extra-gäst-avgift över konfigurerad inkluderad beläggning; maximal kapacitet kontrolleras.
5. Städavgift en gång per vistelse, ytterligare fasta/valfria avgifter och skatter endast om faktiskt fastställda; separat redovisning.
6. **Obegränsade längdrabattsteg:** t.ex. DEMO 7–13 nätter = 5 %, 14–20 = 10 %, 21–29 = 15 %, 30+ = 20 %. Dessa är EXEMPEL, inte livepriser. Admin ska kunna lägga till/ändra/radera regler, min/max, procent eller fast THB, aktivera/deaktivera, giltighet, prioritet. Hantera exakt tröskel och maxgräns.
7. Övriga rabattgrupper: early bird (bokning långt innan incheckning), last minute (nära ankomst), kampanjdatum, kupongkoder, manuella rabatter; stöd procent eller fast THB efter vald regeltyp. Konfigurerbara kvalifikationsvillkor.
8. Stöd admin-val för *kombinationspolicy*: standard = bästa ENDA längdrabatt; därefter **icke-kombinerbara** övriga rabatter (välj mest fördelaktig giltig regel). Admin kan uttryckligen aktivera stapling per regelgrupp, bestämma ordning och ett totalrabattstak t.ex. max 30 % (konfigurerbart, inte påslaget utan beslut). Aldrig dubbelrabatt av samma typ utan explicit konfiguration. Ingen negativ summa.
9. Rabattbas = summa nattpriser exkl. avgifter och skatt som standard. Om vissa avgifter ska rabatteras måste admin göra ett separat explicit val med tydlig förhandsgranskning. Applicera nattrabatter före avgifter och skatt; definiera skatteberäkningsordning utifrån ägarens verkliga krav, inte gissad thailändsk skattesats.
10. Visa alltid total i THB, antal nätter, pris per natt (om det varierar: natt-för-natt), rabattnamn, rabattbelopp, städning, extra-gäst-avgift, andra avgifter, skatter om tillämpligt samt slutsumma och eventuella bokningsvillkor. Särskilj prisuppskattning och bekräftat pris.

## Tekniska preciseringar
- Använd korrekt decimal-/satang-aritmetik med konsekvent avrundning på definierade steg; skriv tester för kantfall, inklusive 1 satang, 100%, stora rabatter och blandade nattpriser.
- Beräkna på servern (frontend endast visning och preliminär respons); ignorera manipulerad klienttotal/rabatt.
- Offert är tidsbunden och knyts till exakt datum, antal gäster, kampanjkod och aktuell prisregelversion. Ändras villkoren mellan offert och inskick: ge omprisning och uttryckligt nytt godkännande, inte tyst högre pris.
- Lagra snapshots per bokning med tillämpade regel-ID och belopp; en senare prisändring får inte retroaktivt ändra en befintlig bokningssumma.
- Prisuträkning i serverlogik skild från bokningsstatus och e-post.

## Exempeltest (DEMO, inte faktiska priser)
Baspris 2 000 THB/natt, 7 nätter, 10 % längdrabatt, 500 THB städning => nattbelopp 14 000, rabatt 1 400, städning 500, summa 13 100 THB (ingen annan skatt/avgift). Testa också 6/7/13/14/29/30 nätter och när flera rabatter konkurrerar.

## Admin-UI
En tydlig sida `Priser och rabatter` med tabbar/prisregler/perioder/längdrabatter/kampanjer/koder/avgifter, formulär med förhandsgranskning, tydlig prioritet och ångra-/inaktiveringsmöjlighet. Kalender med färg/legend och pris per natt. Visa vem som senast ändrade regeln.


---

# Källa: missions/04_CALENDAR_BOOKING_FLOW.md

# Mission 04 — Gästkalender och komplett bokningsflöde

## Gästupplevelse
- Tillgänglighetskalender med lediga/upptagna/blockerade dagar och, vid tillåtna datum, serverbaserat nattpris. Tillgänglighet får inte avslöja gästnamn eller interna blockeringsorsaker.
- Val av check-in, check-out, vuxna/barn eller gästantal enligt bekräftad kapacitet, live-offert med nattlista, rabatter och avgifter. Mobilvänligt och tillgängligt med tangentbord/skärmläsare.
- Formulär med namn, e-post, ev. telefon/meddelande, checkbox för nödvändig integritetstext och villkorsgodkännande, spam-/bot-skydd, CSRF och serverside validering.
- När `BOOKING_OPEN=false`: informativ bokningssida utan aktivt POST-flöde; API svarar lämplig spärr (t.ex. 503), även vid direktanrop. Inga demopriser som riktiga erbjudanden.
- När ägaren senare öppnar: skapa `requested` och referensnummer; tydligt 'förfrågan mottagen – INTE bekräftad'. Kalender låses **inte** av vanlig förfrågan; håll-/bekräftelselogiken hanteras i admin.
- Ett request-id/idempotency-token förhindrar dubbla förfrågningar från dubbelklick/nätverksretry. Rate limiting utan att blockera normala användare.
- Datum valideras i tidszonen Asia/Bangkok; check-in < check-out; antal nätter differens mellan lokala datum. Blockerade datum/kapacitet regleras server-side.
- Utformning följer husets befintliga varma visuella språk, befintlig layout och tre språk. Lämna galleri och övriga presentationssidor oförändrade om de redan fungerar.

## Bokningslivscykel
requested → accepted/held → confirmed eller rejected/cancelled/expired. Förslag: låt administratör godkänna offert och manuellt bekräfta mottagen betalning separat (om relevant); **betald** får aldrig sättas av frontend utan verifierad faktisk händelse. Dokumentera exakta statusövergångar och vilka som blockerar kalender. Admin kan också lägga manuell bokning med gästkontakt och blockering av period.

## API-kontrakt
GET availability?from=YYYY-MM-DD&to=...; POST quote (datum/gäster/kod); POST inquiry (offerttoken + kontakt/villkor); admin endpoints för prisregler, blockering, status och inställningar. Versionera API vid behov och förse endpoint med behörighetskrav, CSRF och tydliga felkoder. Skydda mot pris-/datum-manipulation. Begränsa urvalsintervall och parametrar.

## Acceptans
Det går att välja längre vistelse och direkt se lägre total när motsvarande rabatt aktiverats i admin; upptagna nätter går inte att bekräfta två gånger; check-out-datum är tillgängligt för nästa bokning; formuläret är stängt på en låst förhandsvisning.


---

# Källa: missions/05_ADMIN_WORKFLOWS.md

# Mission 05 — Fullständig enkel adminpanel

## Princip
Admin ska gå snabbt att förstå även för ovana användare: tydliga svenska etiketter (översätt gärna senare), få beslut per vy, mobilanpassat, bra felmeddelanden, visuellt konsekvent med Cosmos House. Behåll eventuell befintlig admin, förbättra den.

## Vyer
1. **Översikt:** kommande ankomster, avresor, förfrågningar att hantera, bekräftade bokningar, blockerade dagar, nästa lediga period; varning när bokning inte är öppen.
2. **Bokningar:** filtrerbar lista med referens, datum, nätter, gäster, pris, status; detaljvy med gästkontakt, nattlista och discounts-snapshot; anteckningar, adminåtgärder, historik. Statusåtgärder med bekräftelsedialog och överlappstest.
3. **Kalender:** månad/veckovy för bokningar, manuella blockeringar, ankomst/utcheckning, fritidsperioder; admin kan skapa/ändra blockering, lägga manuell reservation och se konflikter.
4. **Priser:** baspris/veckodag/helg/säsong/datumpris, högre/lägre priser samt min-stay per period; preview och testverktyg på datumintervall.
5. **Rabatter:** obundet antal längdrabatttrappor, procent/fix THB, datum-/bokningstidsvillkor, kodkampanjer, staplingsregler, caps, prioritet, inaktivering, synliga prisexempel.
6. **Avgifter och regler:** städning, gästantal, extra gäster, bokningshorisont, check-in/out-tider och avbokningsvillkor som ägaren beslutar.
7. **Inställningar:** `BOOKING_OPEN` får en säker adminstatusvy men produktionsaktivering kräver ägarens godkännande och komplett readiness-check; kontaktmail, notifieringsmallar, huskapacitet, språk och integrationsstatus.
8. **Händelselogg:** viktiga ändringar, avbokningar, ändrat pris och vem som ändrat. Inga hemligheter i loggar.

## Kvalitet
- Spara och radera via CSRF-skyddade POST/PUT, korrekt rollkontroll och validering; ingen state-change via GET.
- Dubbla popup-/feedbackmeddelanden och serverfel hanteras tydligt. Förhindra att en adminändring raderar pågående bokningsdata utan bekräftelse.
- Minst administratörsroll; föreslå ytterligare roller först vid konkret behov.
- Export av bokningar begränsas till admin och dataminimeras; aldrig offentlig CSV.
- Villkors-/priseditor måste visa utkast och aktuellt publicerat tillstånd för att undvika oavsiktliga liveändringar.

## Acceptans
En icke-teknisk admin kan skapa en 14-nättersrabatt, aktivera den, testa priset, blockera datum, godkänna eller avvisa förfrågan och hitta ändringshistoriken utan att ändra kod.


---

# Källa: missions/06_EMAIL_I18N_PRIVACY.md

# Mission 06 — E-post, språk och integritet

## Notifieringar
- Bygg utbytbar mailtransport (SMTP konfigurerad med hemligheter utanför repo) och outbox för återförsök; låtsas aldrig att mejl skickats när transport saknas.
- Template på svenska/engelska/thai med: mottagen förfrågan, intern ny förfrågan, manuellt accepterad, bekräftad, avvisad, avbokad, ev. kommande ankomst endast om ägaren väljer det.
- Alla mail ska skilja 'förfrågan' från 'bekräftad bokning' och innehålla referens, datum, antal nätter, totalpris och tillämpade rabatter enligt bokningens lagrade snapshot.
- Undvik fullständiga personuppgifter i SMTP/loggar mer än nödvändigt; bygg säkert från-datum, reply-to och escapad HTML/plaintext.
- Separera notifieringsresultat från transaktionsutfall: ett misslyckat mail ska inte duplicera en bokning, och status ska synas i admin.

## Tre språk
- Ändra `translations.php`/`page-source.php` och bygg med `scripts/build_languages.sh`; respektera existerande 18 sidors genereringsflöde. Alla nya sidor, adminmeddelanden och e-postmallar som är gästsynliga måste ha sv/en/th med naturliga översättningar; svensk admin kan vara default om ej annat beslutas.
- Lägg inte användartext hårdkodat i JS; hantera locale/koppling till API. Valutavisning THB, datum på rätt språk men kalenderlogik Asia/Bangkok.

## GDPR/integritet
- Dokumentera personuppgiftsansvarig (måste bestämmas av ägaren), syfte, rättslig grund efter ägarens beslut, lagringstid, kontakt, export/radering och backups. Skapa utkast, inte påhittade juridiska beslut.
- Minimera data, skydda admin, indexera inte privata bokningar; logga samtycke/godkännande där relevant utan att påstå att allt kräver samtycke.

## Ej i V1 utan separat beställning
Onlinebetalning, kortlagring, automatisk Airbnb/Booking.com tvåvägssynk. Dokumentera framtida hooks och risker, men aktivera inga externa integrationer på egen hand.


---

# Källa: missions/07_TESTS_RELEASE_ACCEPTANCE.md

# Mission 07 — Testning, utrullningskontroll och överlämning

## Testmatris
- Portabilitet: simulera `/`, `/ch/`, `/cosmos/` och `/foo/bar/`; gå igenom CSS, bilder, galleri, fetch/API, språkbyte, redirects, admin, login/logout, länkar och mobilnavigering. Får inte finnas ett beroende på texten `/ch/` i källan annat än dokumenterat *exempel* eller nuvarande installation.
- Datum: datumskifte, månadsskifte, skottår, samma-dag-out/in, 0 nätter, förflutet datum, min/max längd, övergång mellan säsonger, out-of-range tillgänglighetsbegäran, många samtidiga förfrågningar.
- Pris: deterministiskt per natt, prioritet per regel, prisoverride över säsong, 6/7/13/14/20/21/29/30/31 nätter, 10% vs fast belopp, multiple stacking-kombinationer, maxcap, kuponger, rabattkod som går ut, specialdatumsintervall, städning, extra gäster, avrundning.
- Konkurrens: två admin/users försöker bekräfta överlappande bokningar parallellt; precis en får lås, ingen överbokning. Misstänkta idempotenta retry skickar inte dubletter eller dubbla mail.
- Säkerhet: 403/404 för privat data, unauth admin, CSRF, rate limiting, injektion/XSS, mass assignment, session fix, manipulerad offert, ogiltiga antal gäster, behörighetsgränser, API låst när `BOOKING_OPEN=false`.
- Innehåll: alla 18 genererade sidor, 38 bilder, sv/en/th text och mail, preview-låsning, bokningens stängda status, responsiv desktop/mobil, keyboard/a11y.
- Drift: migrering från ren installation, test-backup + restore, fel vid DB/mail otillgänglig, tydlig loggning, rollback.

## Lanseringsspärr
**Håll bokningen stängd i produktion** tills ägaren skriftligen/uttryckligen fastställt: bas- och specialpriser, kapacitet, städ-/extraavgifter, in-/utcheckningsregler, avbokningsregler, privacy/retention, destinationsmail, äkta adminlösenord, SMTP och backup/restore samt verifierad privat lagring. Slå inte på `BOOKING_OPEN` själv.

## Definition of Done
- Dokumenterade och körbara migrationer, fungerande bokningsmotor/prismotor/admin på testmiljö, tre språk, testade statusregler och skydd.
- Separata tydliga rapporter: `docs/BOOKING_AUDIT.md`, `docs/BOOKING_SYSTEM.md`, `docs/PRICE_RULES.md`, `docs/DEPLOY.md`, `docs/TEST_REPORT.md`, `docs/CHANGELOG.md`.
- Rapportera varje ändrad fil, resultat för varje test, kvarstående fel, exakta serverjusteringar som kräver godkännande, reproducerbara kommandon för nya utvecklare.
- Ingen orelaterad ändring utanför Cosmos House. Inga uppgifter får påstås verifierade om de inte testats i aktuell miljö.

## Rapportmall efter leverans
1. Redan befintliga moduler återanvända
2. Nya filer och migrationer
3. Genomförda funktioner och adminflöden
4. Testfall och faktiska utfall (pass/fail/not run)
5. Säkerhets-/portabilitetscheck
6. Öppna beslut som ägaren måste fatta
7. Bekräfta `BOOKING_OPEN=false` tills vidare
