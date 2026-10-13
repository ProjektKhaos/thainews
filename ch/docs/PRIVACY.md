# Personuppgifter

Systemet lagrar namn, e-post, valfritt telefonnummer/meddelande, vistelsedatum och prissnapshot endast när en riktig förfrågan tas emot. Offentligt tillgänglighets-API visar inga gästuppgifter. Samtyckesögonblick och policyversion sparas med förfrågan. Admin kan anonymisera avslutade ärenden; det rensar även outbox-payload för ärendet. Ingen automatisk retentionkörning aktiveras före ägarens beslut.

Den juridiska informationen, personuppgiftsansvarig, kontaktväg, rättslig grund, lagringstid och eventuell överföring via SMTP måste tillhandahållas och godkännas av ägaren på svenska, engelska och thai. `privacy.php` visar ett neutralt stängt läge tills en godkänd text finns. Den publika bokningsgrinden kräver `privacy_text_en`, `privacy_text_sv`, `privacy_text_th` och `policy_version` samt `approved_privacy=1`. Detta dokument är teknisk datakarta, inte en färdig integritetspolicy eller ett påstående om juridisk efterlevnad.
