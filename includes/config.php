<?php

// Change this to your project name
define("PROJECT_NAME", "CMPS 3420 Project");

date_default_timezone_set('America/Los_Angeles');
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("display_errors", 1);

/*
The commands above will allow PHP to display some error messages directly
on the webpage as it renders the page. Other errors may not be caught this
way, such as syntax errors that cause a HTTP 500 message without any 
indication as to why it occurred. Use php directly on the command line
on Artemis to troubleshoot this. You can also add file-based error logging.

If you want to turn on file error logging, run these commands on Artemis:

touch ~/php_errors.log
chmod 646 ~/php_errors.log

and then uncomment the line below and change the file path to match 
your own. Use an absolute path, not a relative path. 
e.g. /home/stu/yourusername/php_errors.log
*/

//ini_set("error_log", "/home/fac/nick/php_errors.log");

// Starts a PHP session and gives the client a cookie :3
// Will be useful for other features, like staying logged in.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Gets a connection to the database using PHP Data Objects (PDO)
function get_pdo_connection(){
    static $conn;

    if (!isset($conn)) {
        try {
            // Make persistent connection
            $options = array(
			  PDO::ATTR_PERSISTENT => true,
			  PDO::ATTR_EMULATE_PREPARES => true
            );

            $dbname = "gamestop2";
            $username = "gamestop2";
            $password = "Pbegz45Lef";

            $conn = new PDO(
                "mysql:host=localhost;dbname=$dbname",  // change dbname
                $username,                         // change username
                $password,                      // change password
                $options);
        }
        catch (PDOException $pe) {
            echo "Error connecting: " . $pe->getMessage() . "<br>";
            die();
        }
        
    }

    if ($conn === false) {
        echo "Unable to connect to database<br/>";
        die();
    }
  
    return $conn;
}
?>