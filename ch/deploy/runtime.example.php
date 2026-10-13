<?php
// Install privately outside DocumentRoot, e.g. /etc/cosmos-house/runtime.php.
return [
    'COSMOS_DB_PATH'=>'/var/lib/cosmos-house/cosmos.sqlite',
    'COSMOS_BACKUP_DIR'=>'/var/lib/cosmos-house/backups',
    'COSMOS_DOCUMENT_ROOT'=>'/var/www/abergonline/thainews',
    'COSMOS_BOOKING_PUBLIC_ENABLED'=>'0',
];
