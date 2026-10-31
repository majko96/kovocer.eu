<?php
// Copy this file to smtp-config.php (same folder) and fill in real values.
// smtp-config.php is gitignored — it must be uploaded to the server manually
// and is never committed to the repository.

return [
    'host' => 'smtp.example.sk',
    'port' => 587,
    'secure' => 'tls', // 'tls' or 'ssl'
    'username' => 'kovocer@example.sk',
    'password' => 'REPLACE_ME',
    'from_email' => 'kovocer@example.sk',
    'from_name' => 'KOVOCER.eu — web',
    'to_email' => 'kovocer@gmail.com',
];
