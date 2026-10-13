<?php
declare(strict_types=1);
namespace Cosmos;
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string)$_SERVER['SCRIPT_FILENAME'])===__FILE__) { http_response_code(404); exit; }
final class Runtime {
    public static function load(): void {
        // Explicit environment values keep automated tests and portable installs isolated.
        if (getenv('COSMOS_DB_PATH')) return;
        $file='/etc/cosmos-house/runtime.php';
        if (!is_file($file) || !is_readable($file)) return;
        $values=require $file;
        if (!is_array($values)) return;
        foreach ($values as $key=>$value) if (str_starts_with((string)$key,'COSMOS_') && getenv((string)$key)===false) putenv($key.'='.(string)$value);
    }
}
Runtime::load();
