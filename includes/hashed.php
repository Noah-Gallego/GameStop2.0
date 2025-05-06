<?php
require_once("config.php");

$passwords = 
[
    "De Maria",
    'McNulty',
    'Mirfield',
    'Seiler',
    'Craze',
    'Mainstone',
    'Handford',
    'Kerby',
    'Daftor',
    'Riggey',
    'Pollastrone',
    'Tiler',
    'Ashburner',
    'Blurton',
    'Duigenan',
    'Meininking',
    'Ambrogio',
    'Bend',
    'Damarell',
    'Labeuil',
];
$db = get_pdo_connection();

// Prepare once, then reuse
$sql = "
    UPDATE Users
    SET `Password` = :hpw
    WHERE `Password` = :pw
";
$st = $db->prepare($sql);

foreach ($passwords as $pw) {
    // Compute the hash for this plaintext password
    $hash = password_hash($pw, PASSWORD_DEFAULT);

    // Execute the update
    $st->execute([
        ':hpw' => $hash,
        ':pw'  => $pw,
    ]);
}