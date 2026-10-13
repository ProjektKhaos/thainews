<?php
declare(strict_types=1);

/**
 * Koordinater för kartan. `approximate` markerar områdespunkter och platser
 * där marknaden är ett stråk, återkommande evenemang eller saknar egen kartnål.
 * Underlag: OpenStreetMap/Nominatim, kontrollerat 2026-10-13.
 */
function market_locations(): array
{
    return [
        'warorot' => [18.7902357, 99.0005350, false],
        'mueang-mai' => [18.7971013, 98.9982489, false],
        'ton-lam-yai' => [18.7907019, 99.0015333, false],
        'thanin' => [18.8042000, 98.9850500, false],
        'chiang-mai-gate' => [18.7818368, 98.9881765, false],
        'nong-hoi-fresh' => [18.7642556, 99.0061817, false],
        'kham-thiang' => [18.8061599, 98.9971379, true],
        'hmong-market' => [18.7906000, 98.9998000, true],
        'night-bazaar' => [18.7844410, 99.0003797, false],
        'kalare' => [18.7853760, 99.0009912, false],
        'anusarn' => [18.7828969, 99.0005561, false],
        'chang-phueak-night' => [18.7954331, 98.9865942, true],
        'cmu-night' => [18.8085049, 98.9557478, true],
        'malin-plaza' => [18.8083388, 98.9563198, false],
        'kad-manee' => [18.7708829, 98.9794120, false],
        'suthep-after-dark' => [18.7902816, 98.9614025, false],
        'meechok' => [18.8237594, 99.0104014, true],
        'wua-lai' => [18.7748561, 98.9808545, true],
        'sunday-walking' => [18.7881000, 98.9897000, true],
        'jing-jai' => [18.8080998, 98.9949021, false],
        'coconut-market' => [18.8178068, 99.0101105, false],
        'white-market' => [18.8001262, 98.9680725, true],
        'baan-kang-wat' => [18.7767499, 98.9482643, false],
        'nong-ho-flea' => [18.8222637, 98.9710948, true],
        'baan-haw' => [18.7866818, 99.0015372, true],
        'somphet' => [18.7914976, 98.9930819, false],
        'ming-muang' => [18.7916004, 98.9929727, false],
        'sanpakhoi' => [18.7854375, 99.0084930, true],
        'mae-hia' => [18.7477783, 98.9419258, false],
        'mae-kha' => [18.7770530, 98.9956330, true],
        'think-park' => [18.8013097, 98.9676099, false],
        'ngfm' => [18.8001262, 98.9680725, true],
        'keha-nong-hoi' => [18.7628490, 99.0112009, true],
        'san-pa-khoi-street' => [18.7871676, 99.0070297, false],
    ];
}
