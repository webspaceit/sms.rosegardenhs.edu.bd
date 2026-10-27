<?php
declare(strict_types=1);

return [
    'host' => 'localhost',
    'database' => 'school_management',
    'username' => 'your_database_user',
    'password' => 'your_database_password',
    'charset' => 'utf8mb4',
    // Optional read replica(s) for load balancing. The primary above stays
    // the write node; SELECTs route to one of these when present.
    'read' => [
        // ['host' => 'db-replica-1'],
        // ['host' => 'db-replica-2'],
    ],
];
