<?php
// Senast uppdaterad: 2026-10-13 11:00 (Asia/Bangkok) | Version 1.00 | av: KlⒶssⓔ & Ⓐberg
/**
 * Marknadskatalog. Ändra öppettider/beskrivningar här när du verifierat dem.
 * Öppettider är vägledande och kan skilja sig mellan enskilda stånd.
 * Slugs är permanenta eftersom recensioner i databasen länkas till dem.
 */
declare(strict_types=1);

function market_catalog(): array
{
    $markets = [
        // DAGMARKNADER
        ['slug'=>'warorot', 'name'=>'Warorot Market (Kad Luang)', 'category'=>'Dagmarknad', 'area'=>'Chang Moi', 'schedule'=>'Dagligen · cirka 06–18', 'tags'=>['Mat', 'Kläder', 'Lokalt'], 'icon'=>'🥭', 'description'=>'Klassisk lokal marknad med nordthailändska specialiteter, kryddor, textilier och vardagsvaror.'],
        ['slug'=>'mueang-mai', 'name'=>'Mueang Mai Market', 'category'=>'Dagmarknad', 'area'=>'Chang Moi', 'schedule'=>'Dagligen · många handlare dygnet runt', 'tags'=>['Frukt', 'Grönt', 'Lokalt'], 'icon'=>'🥬', 'description'=>'Stor grossist- och färskvarumarknad, särskilt spännande tidigt på morgonen.'],
        ['slug'=>'ton-lam-yai', 'name'=>'Ton Lam Yai Flower Market', 'category'=>'Dagmarknad', 'area'=>'Chang Moi', 'schedule'=>'Dagligen · morgon till kväll', 'tags'=>['Blommor', 'Lokalt'], 'icon'=>'💐', 'description'=>'Blommor, kransar och växter vid floden, nära Warorot.'],
        ['slug'=>'thanin', 'name'=>'Thanin Market (Siri Wattana)', 'category'=>'Dagmarknad', 'area'=>'Chang Phueak', 'schedule'=>'Dagligen · cirka 06–20', 'tags'=>['Mat', 'Lokalt'], 'icon'=>'🍜', 'description'=>'Omtyckt för billig färdiglagad mat, färskvaror och lokal vardagshandel.'],
        ['slug'=>'chiang-mai-gate', 'name'=>'Chiang Mai Gate Market', 'category'=>'Dagmarknad', 'area'=>'Old City / söder', 'schedule'=>'Dagligen · morgon och kväll', 'tags'=>['Mat', 'Lokalt'], 'icon'=>'🍚', 'description'=>'Morgonmarknad med ingredienser och mat, och välbesökt street food på kvällen.'],
        ['slug'=>'nong-hoi-fresh', 'name'=>'Nong Hoi Fresh Market', 'category'=>'Dagmarknad', 'area'=>'Nong Hoi', 'schedule'=>'Dagligen · främst dagtid', 'tags'=>['Mat', 'Lokalt'], 'icon'=>'🥕', 'description'=>'Lokal kvartersmarknad för färskvaror och hushållsinköp.'],
        ['slug'=>'kham-thiang', 'name'=>'Kham Thiang Garden Market', 'category'=>'Dagmarknad', 'area'=>'Chang Phueak', 'schedule'=>'Dagligen · cirka 09–18', 'tags'=>['Blommor', 'Växter'], 'icon'=>'🪴', 'description'=>'Trädgårdsväxter, krukor, blommor och grönska i mängder.'],
        ['slug'=>'hmong-market', 'name'=>'Hmong Market', 'category'=>'Dagmarknad', 'area'=>'Chang Moi', 'schedule'=>'Dagligen · cirka 07–17', 'tags'=>['Textil', 'Hantverk'], 'icon'=>'🧵', 'description'=>'Färgstarka broderier, traditionella textilier, tyger och hantverk.'],
        // KVÄLL/NATT
        ['slug'=>'night-bazaar', 'name'=>'Chiang Mai Night Bazaar', 'category'=>'Nattmarknad', 'area'=>'Chang Klan', 'schedule'=>'Dagligen · cirka 17–00', 'tags'=>['Kläder', 'Souvenirer', 'Mat'], 'icon'=>'🌙', 'description'=>'Den klassiska nattbasaren längs Chang Klan Road, med shopping i flera kvarter.'],
        ['slug'=>'kalare', 'name'=>'Kalare Night Bazaar', 'category'=>'Nattmarknad', 'area'=>'Chang Klan', 'schedule'=>'Dagligen · cirka 17–23', 'tags'=>['Mat', 'Kläder', 'Souvenirer'], 'icon'=>'🏮', 'description'=>'Matområde och marknadsstånd inne i Night Bazaar-kvarteren.'],
        ['slug'=>'anusarn', 'name'=>'Anusarn Market', 'category'=>'Nattmarknad', 'area'=>'Chang Klan', 'schedule'=>'Dagligen · cirka 16–23.30', 'tags'=>['Mat', 'Hantverk', 'Shopping'], 'icon'=>'✨', 'description'=>'Marknadsområde med street food, shopping och kvällsfolkliv.'],
        ['slug'=>'chang-phueak-night', 'name'=>'Chang Phueak Gate Night Market', 'category'=>'Nattmarknad', 'area'=>'Chang Phueak', 'schedule'=>'Dagligen · cirka 17–00', 'tags'=>['Mat', 'Street food'], 'icon'=>'🍲', 'description'=>'Välkänt område med kvällsmat i gatustånd norr om gamla stan.'],
        ['slug'=>'cmu-night', 'name'=>'Chiang Mai University Night Market', 'category'=>'Nattmarknad', 'area'=>'Universitetsområdet', 'schedule'=>'Dagligen · främst kvällstid', 'tags'=>['Student', 'Mat', 'Kläder'], 'icon'=>'🎓', 'description'=>'Livligt studentområde med mat, småinköp och informell kvällsshopping.'],
        ['slug'=>'malin-plaza', 'name'=>'Malin Plaza (Kad Na Mor)', 'category'=>'Nattmarknad', 'area'=>'Universitetsområdet', 'schedule'=>'Dagligen · cirka 17–22.30', 'tags'=>['Kläder', 'Student', 'Mat'], 'icon'=>'👕', 'description'=>'Ungdomligt shoppingområde vid universitetet, bra för budgetkläder och snacks.'],
        ['slug'=>'kad-manee', 'name'=>'Kad Manee Market', 'category'=>'Nattmarknad', 'area'=>'Hai Ya', 'schedule'=>'Dagligen · främst kvällstid', 'tags'=>['Mat', 'Shopping'], 'icon'=>'🍢', 'description'=>'Avslappnad mat- och kvällsmarknad i södra stadsområdet.'],
        ['slug'=>'suthep-after-dark', 'name'=>'Suthep Market / studentmat', 'category'=>'Nattmarknad', 'area'=>'Suthep', 'schedule'=>'Dagligen · främst kvällstid', 'tags'=>['Student', 'Mat'], 'icon'=>'🥤', 'description'=>'Små stånd och lokala matställen i universitetskvarteren; verksamheten varierar.'],
        ['slug'=>'meechok', 'name'=>'Meechok Night Market', 'category'=>'Nattmarknad', 'area'=>'Fa Ham', 'schedule'=>'Störst mån–tis · cirka 17–21', 'tags'=>['Kläder', 'Mat', 'Lokalt'], 'icon'=>'🛍️', 'description'=>'En lokal kvällsmarknad med veckovis schema; särskilt livlig måndag och tisdag.'],
        // HELG & SPECIELLA
        ['slug'=>'wua-lai', 'name'=>'Saturday Walking Street (Wua Lai)', 'category'=>'Helgmarknad', 'area'=>'Hai Ya', 'schedule'=>'Lördag · cirka 17–23', 'tags'=>['Hantverk', 'Silver', 'Mat'], 'icon'=>'🪙', 'description'=>'Lördagsmarknad längs Wua Lai Road med hantverk, silver och gatumat.'],
        ['slug'=>'sunday-walking', 'name'=>'Sunday Walking Street (Tha Phae)', 'category'=>'Helgmarknad', 'area'=>'Old City', 'schedule'=>'Söndag · cirka 17–22.30', 'tags'=>['Hantverk', 'Kläder', 'Mat'], 'icon'=>'🎏', 'description'=>'Den stora söndagsmarknaden längs Ratchadamnoen Road genom gamla stan.'],
        ['slug'=>'jing-jai', 'name'=>'Jing Jai Market', 'category'=>'Helgmarknad', 'area'=>'Chang Phueak', 'schedule'=>'Lör–sön · cirka 06.30–15', 'tags'=>['Design', 'Ekologiskt', 'Hantverk'], 'icon'=>'🌿', 'description'=>'Helgfavorit med ekologiska råvaror, kreativa produkter, kaffe och hantverk.'],
        ['slug'=>'coconut-market', 'name'=>'Coconut Market', 'category'=>'Helgmarknad', 'area'=>'Fa Ham', 'schedule'=>'Fre–sön · oftast 08–15', 'tags'=>['Mat', 'Design', 'Hantverk'], 'icon'=>'🥥', 'description'=>'Trevlig utomhusmarknad bland kokosnötspalmer med småskaligt hantverk och mat.'],
        ['slug'=>'white-market', 'name'=>'White Market at One Nimman', 'category'=>'Helgmarknad', 'area'=>'Nimman', 'schedule'=>'Ofta fre–sön · cirka 15–21', 'tags'=>['Design', 'Kläder', 'Smycken'], 'icon'=>'🤍', 'description'=>'Design- och hantverksstånd vid One Nimman; kontrollera aktuell kalender.'],
        ['slug'=>'baan-kang-wat', 'name'=>'Baan Kang Wat', 'category'=>'Helgmarknad', 'area'=>'Suthep', 'schedule'=>'Kreativa byn tis–sön · cirka 10–18', 'tags'=>['Konst', 'Keramik', 'Design'], 'icon'=>'🎨', 'description'=>'Kreativt byområde med ateljéer, keramik och småbutiker; vissa söndagar extra marknad.'],
        ['slug'=>'nong-ho-flea', 'name'=>'Nong Ho Flea Market', 'category'=>'Helgmarknad', 'area'=>'Chang Phueak', 'schedule'=>'Lör–sön · cirka 07–14', 'tags'=>['Loppis', 'Begagnat'], 'icon'=>'📻', 'description'=>'Loppis med blandade begagnade föremål, verktyg och udda fynd.'],
        ['slug'=>'baan-haw', 'name'=>'Kad Baan Haw Friday Market', 'category'=>'Helgmarknad', 'area'=>'Chang Klan', 'schedule'=>'Fredag · cirka 05–12', 'tags'=>['Mat', 'Halal', 'Lokalt'], 'icon'=>'🫓', 'description'=>'Fredagsmarknad känd för yunnanesisk och muslimsk mat samt specialingredienser.'],
        // FLER MINDRE MARKNADER FRÅN GUIDEN
        ['slug'=>'somphet', 'name'=>'Somphet Market', 'category'=>'Kvartersmarknad', 'area'=>'Old City / öster', 'schedule'=>'Dagligen · främst dagtid', 'tags'=>['Mat', 'Frukt', 'Lokalt'], 'icon'=>'🍍', 'description'=>'Mindre lokal matmarknad vid nordöstra delen av gamla stan.'],
        ['slug'=>'ming-muang', 'name'=>'Ming Muang Market', 'category'=>'Kvartersmarknad', 'area'=>'Old City', 'schedule'=>'Dagligen · främst morgon', 'tags'=>['Mat', 'Lokalt'], 'icon'=>'🍚', 'description'=>'Traditionell liten matmarknad inne i gamla stan.'],
        ['slug'=>'sanpakhoi', 'name'=>'Sanpakhoi Market', 'category'=>'Kvartersmarknad', 'area'=>'San Pa Khoi', 'schedule'=>'Dagligen · främst dagtid', 'tags'=>['Mat', 'Lokalt'], 'icon'=>'🥗', 'description'=>'Kvartersmarknad öster om Pingfloden med mat och vardagsutbud.'],
        ['slug'=>'mae-hia', 'name'=>'Mae Hia Fresh Market', 'category'=>'Kvartersmarknad', 'area'=>'Mae Hia', 'schedule'=>'Dagligen · främst dagtid', 'tags'=>['Mat', 'Lokalt'], 'icon'=>'🌶️', 'description'=>'Lokal färskvarumarknad i sydvästra stadsområdet.'],
        ['slug'=>'mae-kha', 'name'=>'Mae Kha Canal Evening Stalls', 'category'=>'Specialmarknad', 'area'=>'Chang Klan', 'schedule'=>'Främst kvällstid · varierar', 'tags'=>['Mat', 'Promenad', 'Hantverk'], 'icon'=>'🌉', 'description'=>'Promenadstråk med mindre försäljningsstånd; inte en traditionell stor marknad.'],
        ['slug'=>'think-park', 'name'=>'Think Park Night Market', 'category'=>'Specialmarknad', 'area'=>'Nimman', 'schedule'=>'Kvällar · varierar', 'tags'=>['Design', 'Mat', 'Shopping'], 'icon'=>'💡', 'description'=>'Pop-up-betonat shopping- och matområde vid Nimman; kontrollera aktuella evenemang.'],
        ['slug'=>'ngfm', 'name'=>'NGFM Night Ground Flea Market', 'category'=>'Specialmarknad', 'area'=>'Chang Phueak', 'schedule'=>'Ofta tis–ons · kväll', 'tags'=>['Loppis', 'Kläder', 'Vintage'], 'icon'=>'🧥', 'description'=>'Alternativ kvällsloppis och vintageinriktad marknad; datum kan variera.'],
        ['slug'=>'keha-nong-hoi', 'name'=>'Saturday Evening Flea Market (Keha Nong Hoi)', 'category'=>'Specialmarknad', 'area'=>'Nong Hoi', 'schedule'=>'Lördag · kväll (kontrollera)', 'tags'=>['Loppis', 'Begagnat'], 'icon'=>'🧺', 'description'=>'Mindre helgloppis nära Keha Nong Hoi, med varierande arrangemang.'],
        ['slug'=>'san-pa-khoi-street', 'name'=>'San Pa Khoi Street Market', 'category'=>'Specialmarknad', 'area'=>'San Pa Khoi', 'schedule'=>'Ofta första söndagen i månaden · 08–13', 'tags'=>['Design', 'Hantverk', 'Mat'], 'icon'=>'🎐', 'description'=>'Periodisk gatufestival/marknad med lokala försäljare och kulturinslag.'],
    ];
    return array_map('translate_market', $markets);
}

function market_by_slug(string $slug): ?array
{
    foreach (market_catalog() as $market) {
        if ($market['slug'] === $slug) {
            return $market;
        }
    }
    return null;
}
