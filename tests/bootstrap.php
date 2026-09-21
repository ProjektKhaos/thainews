<?php
// Senast uppdaterad: 2026-09-20 18:46
$root=dirname(__DIR__);
$autoload=$root.'/vendor/autoload.php';
if(is_file($autoload)) require $autoload;
else spl_autoload_register(static function(string $class) use($root): void { if(str_starts_with($class,'ThaiNews\\')){ $path=$root.'/app/'.str_replace('\\','/',substr($class,9)).'.php'; if(is_file($path))require $path; }});
require_once $root.'/includes/helpers.php';
