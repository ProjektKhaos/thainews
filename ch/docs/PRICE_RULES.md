# Prisregler

Alla belopp sparas som heltals-satang, 100 satang = 1 THB. Varje natt från incheckningsdatum till natten före utcheckning får baspris eller första matchande aktiva datumregel. Datumregler sorteras på högst prioritet, sedan lägst regel-ID. Veckodag är ISO 1–7. Regelns slutdatum är exklusivt.

Rabattregler kan ha obegränsat antal trappsteg med min/max nätter, start-/slutdatum, kod och min/max dagar i förväg. De sorteras på prioritet och ID. Standard är en vinnande automatisk rabatt totalt; `discount_stacking=1` tillåter en per familj och kräver separat ägarbeslut. Procent anges som baspunkter: 1000 = 10 %. Avrundning är `floor((nattkostnad × baspunkter + 5000)/10000)` till närmaste satang. Fast rabatt är satang. Rabatter får inte sänka nattkostnaden under noll. Fasta avgifter läggs därefter till som per vistelse, per natt eller per extra gäst. Procentavgifter beräknas sist på vald bas: nattkostnad efter rabatt eller nattkostnad efter rabatt plus fasta avgifter. Ingen moms eller skattesats antas utan godkänd regel och uttryckligt val av bas.

Offerten sparar full nattlista, regel-ID, rabatter, avgifter, total och `rule_version`. Token är slumpad och gäller 15 minuter. Vid submit valideras token, regelversion, aktuell total och tillgänglighet på servern. Ändringar kräver ny offert. Admins ombokning sparar tidigare och ny prisbild i `booking_revisions`; historiska ärenden prisräknas inte om av senare regeländringar.

Exempelvärdet 2800 THB/natt och 10 % vid sju nätter används enbart i isolerade tester. Inga verkliga priser har lagts in i produktion.
