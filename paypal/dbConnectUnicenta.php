<?php

// Connect to database server using PDO
// Provides access to the leftbank_paypal database
// tables: bookdetail (and all the others)

$servername = ''; // name of the server
$dbname = ''; // name of the database

$dsn = "mysql:host=$servername;dbname=$dbname";

// Enter the user credentials
$username = ''; // Username
$password = ''; // Password


// handle errors using try/catch
try {
    $dbhUnicenta = new PDO($dsn, $username, $password);
    $dbhUnicenta->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
         exit();
    }
                
?>
