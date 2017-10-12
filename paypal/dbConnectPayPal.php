<?php

// Connect to database server using PDO
// Provides access to the leftbank_paypal database
// tables: webtransactions, errorlog, inventoryday

$servername = 'ServerName';
$dbname = 'DatabaseName';

$dsn = "mysql:host=$servername;dbname=$dbname";

// Enter the user credentials
$username = 'Username';
$password = 'Password';


// handle errors using try/catch
try {
    $dbhPayPal = new PDO($dsn, $username, $password);
    $dbhPayPal->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
    exit();
    }
                
?>
