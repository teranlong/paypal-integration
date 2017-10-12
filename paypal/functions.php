<?php
include("tableNames.php");
require_once("dbConnectPayPal.php");
require_once("dbConnectUnicenta.php");

$errorsExist = false;

function logTransaction($data){
    global $errors_table;
    global $dbhPayPal;
    
    $errors = array();
    
    // validate transaction ID
    if(!checkTransactionID($data)){
        $errorsExist = true;
        $errorMessage = "DUPLICATE TRANSACTION ID -- Investigate manually!!,";
        $errors[] = $errorMessage;
        
        // email admin
        mail('errors@example.com', 'PayPal ERROR - Invesitgate Manually', print_r($_POST, true), $errorMessage);
    }
    
    // validata amount paid
    if(!checkPrice($data)){
        $errorsExist = true;
        $errorMessage = "WRONG PRICE PAID -- Investigate manually!!";
        $errors[] = $errorMessage;
        
        // email admin
        mail('errors@example.com', 'PayPal ERROR - Invesitgate Manually', print_r($_POST, true), $errorMessage);
    } 
    
    // report errors
    if ($errorsExist) {
        
        $sql = "INSERT INTO $errors_table (TransactionID, Message, Data) VALUES (:TransactionID, :Message, :Data)"; 
        $stmt = $dbhPayPal->prepare($sql);
        $stmt->bindParam(':TransactionID', $transactionID, PDO::PARAM_STR);
        $stmt->bindParam(':Message', $errorsString, PDO::PARAM_STR);
        $stmt->bindParam(':Data', $dataString, PDO::PARAM_STR);
        
        // Errors message
        $errorsString = implode($errors);
        
        // Transaction ID
        $transactionID = $data['txn_id'];
        
        // JSON encode data
        $dataString = json_encode($data);
        
        $stmt->execute();
    }
    
    // save data in database
    if (!updatePayments($data,$errorsExist))
    {
        // email admin
        mail('errors@example.com', 'PayPal ERROR - Could not record transaction', print_r($_POST, true), $errorMessage);
    }
}




/*
 *Details lookup function: connects to the database and returns 
 *all information from the product details table defined in 
 *tableNames.php. This function is used by the other fuctions.
 */
function detailsLookup($isbn)
{
    global $product_table;
    global $dbhUnicenta;

    
    $sql = "SELECT * FROM $product_table WHERE isbn = :isbn"; 
    $stmt = $dbhUnicenta->prepare($sql);
    $stmt->bindParam(':isbn', $isbn, PDO::PARAM_STR);
    $stmt->execute();
    if($stmt->rowCount())
    {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }  else {
        // no product found
//            ********************************************
//                throw an error???
//            ********************************************
        return null;
    }
}

/*
 *Check price function: connects to the database and compares 
 *the amount paid from the PayPal data to the price listed in
 *the product details table defined in tableNames.php.
 *Returns true if all prices match and false otherwise.
 */
function checkPrice($data){
    
    $result = true; 
    
    // check currency type
    if ($data['mc_currency']!="USD")
    {
            $result = false;    
    }
    
    // check prices paid for each item
    if ($data['txn_type']!="cart")
    {
        
        // individual item purchase        
        $paypalPrice = $data['mc_gross'];
        $paypalQuantity = $data['quantity'];
        $isbn = $data['item_number'];
        $book = detailsLookup($isbn);
        $bookPrice = $book['ListPrice'];
        $databasePrice = $bookPrice*$paypalQuantity;

        // if price is within 10 cents then it is considered good -- this eliminates false positives from adding different data types
        $err = abs($databasePrice-$paypalPrice);
        if ($err>0.1)
        {
            mail('errors@example.com', 'WRONG PRICE: '.$i, 'Price per book: '. $bookPrice."|".  'Number of books: '. $paypalQuantity."|".  'Calculated cost: '. $databasePrice."|".'Amount Paid: '. $paypalPrice);
            $result = false;
        }

    } else {
        // cart purchase
        $i = 1;
        
        while(isset($data['mc_gross_'.$i]))
        {
            $paypalPrice = $data['mc_gross_'.$i];
            $paypalQuantity = $data['quantity'.$i];
            $isbn = $data['item_number'.$i];
            $book = detailsLookup($isbn);
            $bookPrice = $book['ListPrice'];
            $databasePrice = $bookPrice*$paypalQuantity;
            
            // if price is within 10 cents then it is considered good -- this eliminates false positives from adding different data types
            $err = abs($databasePrice-$paypalPrice);
            if ($err>0.1)
            {
                mail('errors@example.com', 'WRONG PRICE: '.$i, 'Price per book: '. $bookPrice."|".  'Number of books: '. $paypalQuantity."|".  'Calculated cost: '. $databasePrice."|".'Amount Paid: '. $paypalPrice);
                $result = false;
            }
            $i++;
        } 
    }
    return $result;
}

/*
 *Check transaction ID function: connects to the database and checks
 *the transaction ID for duplicates in the web payments table defined in 
 *tableNames.php. Returns true if transaction ID is unique false otherwise.
 */
function checkTransactionID($data){
        global $payments_table;
        global $dbhPayPal;

        $transactionID = $data['txn_id'];
    
        $sql = "SELECT TransactionID FROM $payments_table WHERE TransactionID = :TransactionID"; 
        $stmt = $dbhPayPal->prepare($sql);
        $stmt->bindParam(':TransactionID', $transactionID, PDO::PARAM_STR);
        $stmt->execute();
        if($stmt->rowCount())
        {            
        // non unique transaction id
            return false;
        }  else {
            return true;
        }  
    
}


/*
 *Update payments function: saves all necessary transaction data 
 *to the payhments table specified in tableNames.php.
 *Returns true if successful and false otherwise.
 */
function updatePayments($data,$errorsExist){
    global $dbhPayPal;
    global $payments_table;
    
//  Put data into database

//  Initialize local vars
    $transactionID = "";
    $total = "";
    $isbn = "";
    $name = "";
    $addr = "";
    $city = "";
    $state = "";
    $zip = "";
    $phone = "";
    $email = "";
    $notes = "";
    $shipped = null;
    $shippingDetails = "";
    
    if ($errorsExist) {
        $errorFlag = '1';
    } else {
        $errorFlag = '0';
    }
    
    $isbnNumbersString = "";
    

$stmt = $dbhPayPal->prepare("INSERT INTO `$payments_table` (`TransactionID`, `Date`, `Total`, `ISBN`, `Name`, `Address`, `City`, `State`, `Zip`, `Phone`, `Email`, `Shipped`, `ShippingDetails`, `ErrorFlag`, `Notes`) VALUES (:TransactionID, CURRENT_TIMESTAMP, :Total, :ISBN, :Name, :Address, :City, :State, :Zip, :Phone, :Email, NULL, :ShippingDetails, :ErrorFlag, :Notes)");
    
    $stmt->bindParam(':TransactionID', $transactionID);
    $stmt->bindParam(':Total', $total);
    $stmt->bindParam(':ISBN', $isbn);
    $stmt->bindParam(':Name', $name);
    $stmt->bindParam(':Address', $addr);
    $stmt->bindParam(':City', $city);
    $stmt->bindParam(':State', $state);
    $stmt->bindParam(':Zip', $zip);
    $stmt->bindParam(':Phone', $phone);
    $stmt->bindParam(':Email', $email);
    $stmt->bindParam(':ShippingDetails', $shippingDetails);
    $stmt->bindParam(':ErrorFlag', $errorFlag);
    $stmt->bindParam(':Notes', $notes);


//  Insert values if they are set

    if (isset($data['txn_id'])){
        // value exists
        $transactionID = $data['txn_id'];
    } else {
        // save error messages (should never happen)
//        $errorMessage = "Value 'txn_id' not found";
//        $errors[] = $errorMessage;
    }

    if (isset($data['mc_gross'])){
        // value exists
        $total = $data['mc_gross'];
    } else {
        // save error messages (should never happen)
//        $errorMessage = "Value 'mc_gross' not found";
//        $errors[] = $errorMessage;
    }
    
    if (isset($data['address_name'])){
        // value exists
        $name = $data['address_name'];
    } else {
        // save error messages (should never happen)
//        $errorMessage = "Value 'address_name' not found";
//        $errors[] = $errorMessage;
    }
    
    if (isset($data['address_street'])){
        // value exists
        $addr = $data['address_street'];
    } else {
        // save error messages (should never happen)
//        $errorMessage = "Value 'address_street' not found";
//        $errors[] = $errorMessage;
    }
    
    if (isset($data['address_city'])){
        // value exists
        $city = $data['address_city'];
    } else {
        // save error messages (should never happen)
//        $errorMessage = "Value 'address_city' not found";
//        $errors[] = $errorMessage;
    }
    
    if (isset($data['address_state'])){
        // value exists
        $state = $data['address_state'];
    } else {
        // save error messages (should never happen)
//        $errorMessage = "Value 'address_state' not found";
//        $errors[] = $errorMessage;
    }
    
    if (isset($data['address_zip'])){
        // value exists
        $zip = $data['address_zip'];
    } else {
        // save error messages (should never happen)
//        $errorMessage = "Value 'address_zip' not found";
//        $errors[] = $errorMessage;
    }

    if (isset($data['contact_phone'])){
        // value exists
        $phone = $data['contact_phone'];
    } else {
        // save error messages (should never happen)
//        $errorMessage = "Value 'contact_phone' not found";
//        $errors[] = $errorMessage;
    }
    
    if (isset($data['payer_email'])){
        // value exists
        $email = $data['payer_email'];
    } else {
        // save error messages (should never happen)
//        $errorMessage = "Value 'payer_email' not found";
//        $errors[] = $errorMessage;
    }
    
    if (isset($data['shipping_method'])){
        // value exists
        $shippingDetails = $data['shipping_method'];
        
        // shipping information email
        
        if ($shippingDetails == "Store Pickup")
        {
            $storePickupInstructions = "Pick up your book at leftbank books! ..... Duh!";
            $storePickupsubject = "In Store Pickup Instructions";
            mail($email, $storePickupsubject, $storePickupInstructions);
        }
        
        
    } else {
        // save error messages (should never happen)
//        $errorMessage = "Value 'shipping_method' not found";
//        $errors[] = $errorMessage;
    }


    // ISBN numbers

    if ($data['txn_type']!="cart")
    {
        
        mail('debug@example.com', 'BIN PURCHASE: ', '"'.$data['txn_type'].'"');

        // individual item purchase
        $isbnNumbersString .= $data['item_number'];
        $isbnNumbersString .= "-";
        $isbnNumbersString .= $data['quantity'];
    } else {
         mail('debug@example.com', 'ATC PURCHASE: ', '"'.$data['txn_type'].'"');
        // cart purchase
        $i = 1;
        $isbnNumbersString = "";
        while(isset($data['item_number'.$i]))
        {
            $isbnNumbersString .= $data['item_number'.$i];
            $isbnNumbersString .= "-";
            $isbnNumbersString .= $data['quantity'.$i];
            $isbnNumbersString .= ",";
            $i++;
        }
        $isbnNumbersString = rtrim($isbnNumbersString,",");
    }
    $isbn = $isbnNumbersString;

    // excecute statement, putting the data into the database
    $stmt->execute();

    if($stmt->rowCount())
    {
        return true;
    }  else {
        return false;
    }
}

?>
