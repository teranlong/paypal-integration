<?php 
require('PayPalIPN.php');
require('functions.php');

 mail('debug@example.com', "IPN RECIEVED!", print_r($_POST, true));

$ipn = new PaypalIPN();

// Use the sandbox endpoint during testing.
//$ipn->useSandbox();

try {
	$verified = $ipn->verifyIPN();
} catch (Exception $e) {
    mail('debug@example.com', 'Caught exception: ', $e->getMessage());
}


if ($verified) {
    
    /*
     * Process IPN
     * A list of variables is available here:
     * https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNandPDTVariables/
     */
    
//    mail('debug@example.com', "IPN VERIFIED!", print_r($_POST, true));
    
    $data = $_POST;
    try {
        logTransaction($data);
    } catch (PDOException $e) {
        $errorMessage = $e->getMessage();
//        mail('debug@example.com', "Error message", $errorMessage);
    }
        
} else {
//    mail('debug@example.com', "IPN NOT VERIFIED!", print_r($_POST, true));
}

// Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
header("HTTP/1.1 200 OK");
?>
