<!-- icons -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/material-icons@1.13.12/iconfont/material-icons.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.js"
        integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous">
</script>
<?php
// Figure out which page is loaded now
$current = basename($_SERVER['SCRIPT_NAME'], '.php');
?>
<!-- global styles / theme -->
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href='assets/css/<?php echo $current;?>.css'>