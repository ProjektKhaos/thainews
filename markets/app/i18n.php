<?php
declare(strict_types=1);

// Swedish is the source language. Keep the selected language through page and form requests.
function market_language(): string
{
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['sv', 'en'], true)) {
        $_SESSION['market_language'] = $_GET['lang'];
        setcookie('market_language', $_GET['lang'], [
            'expires' => time() + 365 * 86400,
            'path' => rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/markets/index.php'), '/') . '/',
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
    }
    if (!isset($_SESSION['market_language'])) {
        $_SESSION['market_language'] = in_array($_COOKIE['market_language'] ?? '', ['sv', 'en'], true) ? $_COOKIE['market_language'] : 'sv';
    }
    return $_SESSION['market_language'];
}

function tr(string $swedish, ...$args): string
{
    static $en = null;
    if ($en === null) $en = require __DIR__ . '/lang/en.php';
    $text = market_language() === 'en' ? ($en[$swedish] ?? $swedish) : $swedish;
    return $args ? sprintf($text, ...$args) : $text;
}

function market_language_url(string $language): string
{
    $path = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    return app_url($path, array_merge($_GET, ['lang' => $language]));
}

function translate_market(array $market): array
{
    if (market_language() !== 'en') return $market;
    static $descriptions = null;
    if ($descriptions === null) $descriptions = require __DIR__ . '/lang/market-descriptions-en.php';
    $market['description'] = $descriptions[$market['slug']] ?? $market['description'];
    $market['name'] = tr($market['name']);
    $market['category'] = tr($market['category']);
    $market['area'] = tr($market['area']);
    $market['tags'] = array_map('tr', $market['tags']);
    $market['schedule'] = str_replace(
        ['Kreativa byn tis–sön','Ofta första söndagen i månaden','många handlare dygnet runt','Störst mån–tis','Ofta fre–sön','Ofta tis–ons','Lör–sön','Fre–sön','morgon till kväll','morgon och kväll','främst kvällstid','främst dagtid','främst morgon','kväll (kontrollera)','Dagligen','Lördag','Söndag','Fredag','Kvällar','cirka','oftast','varierar','kväll'],
        ['Creative village Tue–Sun','Often the first Sunday of the month','many vendors around the clock','Busiest Mon–Tue','Often Fri–Sun','Often Tue–Wed','Sat–Sun','Fri–Sun','morning to evening','morning and evening','mainly evenings','mainly daytime','mainly mornings','evening (check first)','Daily','Saturday','Sunday','Friday','Evenings','around','usually','varies','evening'],
        $market['schedule']
    );
    return $market;
}
