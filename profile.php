<?php 
    require_once 'includes/config.php';
    if (!isset($_SESSION['username'])) {
        header('Location: access_denied.php');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profiles</title>
    <link href = "../assets/css/profile.css" rel = "stylesheet">
</head>
<body>
    <h1> Change User Info </h1>
</body>
</html>