<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Access Denied</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }

        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                url('assets/media/ban_image.jpg') no-repeat center center / contain,
                url('assets/media/doritos.gif') no-repeat center center / cover;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="background"></div>
</body>
</html>