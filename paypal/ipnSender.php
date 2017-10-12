<?php

    // Include Functions
    include("functions.php");

    ///////////////////////////////////////////////////////////////////////////
    //  Sandbox Settings - you will have to configure your own sanbox account
    ///////////////////////////////////////////////////////////////////////////
    //$paypal_email = '';
    //$return_url = '';
    //$cancel_url = '';
    //$notify_url = '';

    ///////////////////////////////////////////////////////////////////////////
    //  Live Settings
    ///////////////////////////////////////////////////////////////////////////
    $paypal_email = 'paypal_email'; // Email associated with PayPal business account
    $return_url = 'return_url'; // Page to which the user is returned after the purchase
    $cancel_url = 'cancel_url'; // Page to which the user is returned if the purchase is canceled.
    $notify_url = 'notify_url'; // Page which is notified of the payment via IPN -- ipnReciever.php

    // start temp query string depending on purchase type
    $tempquerystring = '';

    // get sensitive information from database using ISBN number
    if (isset($_POST['upload'])&&$_POST['upload']==1)
    {
        // cart upload command -- cart checkout button
        $i=1;
        while(isset($_POST['isbn_'.$i]))
        {
            $isbn = $_POST['isbn_'.$i];
            // return book data as an array from database
            $bookData = detailsLookup($isbn);

            // save important variables
            $item_name = $bookData['Title'];
            $item_number = $isbn;
            $item_amount = $bookData['ListPrice'];

            // append to temporary query string 
            $tempquerystring .= "item_name_".$i."=".urlencode($item_name)."&";
            $tempquerystring .= "item_number_".$i."=".urlencode($item_number)."&";
            $tempquerystring .= "amount_".$i."=".urlencode($item_amount)."&";
            $i++;
        }
        
    } else {
        // single item purchase -- buy it now button
        $isbn = $_POST['isbn'];

        // return book data as an array from database
        $bookData = detailsLookup($isbn);

        // save important variables
        $item_name = $bookData['Title'];
        $item_number = $isbn;
        $item_amount = $bookData['ListPrice'];

        // append to temporary query string 
        $tempquerystring .= "item_name=".urlencode($item_name)."&";
        $tempquerystring .= "item_number=".urlencode($item_number)."&";
        $tempquerystring .= "amount=".urlencode($item_amount)."&";
    }
    
    // start final querystring
	$querystring = '';

	// Firstly Append paypal account to querystring
	$querystring .= "?business=".urlencode($paypal_email)."&";
	
    // Append the tempquerystring from above to query string 
    $querystring .= $tempquerystring;

    // currency type
    $querystring .= "currency_code=USD&";

	
	//loop for posted values and append to querystring
	foreach($_POST as $key => $value){
		$value = urlencode(stripslashes($value));
		$querystring .= "$key=$value&";
	}
	
	// Append paypal return addresses
	$querystring .= "return=".urlencode(stripslashes($return_url))."&";
	$querystring .= "cancel_return=".urlencode(stripslashes($cancel_url))."&";
	$querystring .= "notify_url=".urlencode($notify_url);
	
    // debugging 
//	mail('debug@example.com', "IPN Sent!", $querystring.print_r($_POST, true));


	// Redirect to paypal IPN

    /// SANDBOX ///
//	header('location:https://www.sandbox.paypal.com/cgi-bin/webscr'.$querystring);
	
    /// LIVE ///
    header('location:https://www.paypal.com/cgi-bin/webscr'.$querystring);

	exit();
    
?>
