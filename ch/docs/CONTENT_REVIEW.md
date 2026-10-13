# Innehåll som ägaren måste granska

Källan är `page-source.php` och översättningarna i `translations.php`; de 18 genererade sidorna ska inte redigeras ensamma. Alla stycken som börjar med `Exempeltext`, `Sample text` eller `ข้อความตัวอย่าง` är layouttext. De finns på start-, hus-, galleri-, upplevelse-, plats- och bokningssidorna och måste ersättas med verifierat innehåll eller tas bort före publik release.

Ägaren ska särskilt kontrollera påståenden om takterrass, rum, kök, arbetsplats, bekvämligheter, gästantal, område och vägbeskrivning. Koordinatliknande text i heron är ett designelement och ingen verifierad adress. Samtliga 38 foton och deras bildtexter behöver bild-/rättighetsgodkännande. Pris, städning, rabatter, in-/utcheckning, avbokning, kontakt och integritetstext saknar ännu ägargodkännande. Se `AGARBESLUT.md` för beslutsposter.

Ingen ny faktisk husuppgift har hittats på i bokningsimplementationen. Testpriser och testpersoner förekommer bara i isolerade fixtures. När ägaren har lämnat slutlig text: uppdatera mall och översättningar, kör `bash scripts/build_languages.sh`, granska tre språk och kör `node tests/browser.cjs` igen.
