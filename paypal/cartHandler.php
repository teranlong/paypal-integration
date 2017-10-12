<?php
/* cartHandler.php
 * Include this file anywhere that you want to post cart data to
 * it might be worth double checking the php security of the shopping cart
 * I had a very limited time to do this. I think I prevented bad things by
 * excaping input, but it could be worth looking up a php security checklist 
 * with a fresh perspective that didnt write the code.
 */
    require_once('functions.php');

    // Start session which will hold our shopping cart 
    if(session_status()!=PHP_SESSION_ACTIVE)
    {
        session_start();
    }
    $go = false;

    //////////////
    // Add book //
    //////////////
    if (isset($_POST['add_isbn']))
    {
        $isbn = $_POST['add_isbn'];
        
        if (isset($_SESSION['cart']['products'])){
            
            // there are already items in cart
            $i=1;
            $added = false;
            
            // check each item in cart
            while(isset($_SESSION['cart']['products']['isbn_'.$i]))
            {
                
                // product is in cart
                if ($_SESSION['cart']['products']['isbn_'.$i] == $isbn) 
                {
                    // increse quantity of item if less than max
                    $value = $_SESSION['cart']['products']['quantity_'.$isbn];
                    $max = 2;
                    if ($value >= 1 && $value <= ($max-1) && is_numeric($value)) {
                        $_SESSION['cart']['products']['quantity_'.$isbn]+=1;
                    } 
                    
                    $added = true;
                }   
                $i++;
            }
            
            // item was not in cart
            if (!$added) 
            {
                // add new product
                $_SESSION['cart']['products']['isbn_'.$i] = $isbn;
                $_SESSION['cart']['products']['quantity_'.$isbn] = 1; 
                $_SESSION['cart']['size'] += 1;
            }
            
        } else {
            
            // there are no items in cart -- create new cart
            $_SESSION['cart']['products'] = array();
            $_SESSION['cart']['total'] = 0; 
            $_SESSION['cart']['size'] = 1;
            
            // add item to cart
            $_SESSION['cart']['products']['isbn_1'] = $isbn;
            $_SESSION['cart']['products']['quantity_'.$isbn] = 1;
        }
        $go= true;
        
    } 


    /////////////////////
    // Update Quantity //
    /////////////////////
    if (isset($_POST['update_quantity']))
    {
//        // Debugging:
//        echo '<pre>';
//        print_r($_POST);
//        echo '</pre>';
        
        $i=1;
        while(isset($_POST['update_quantity_'.$i])){
            $isbn = $_POST['update_quantity_'.$i];
            
            // validate quantitiy
            $value = $_POST['new_quantity_'.$isbn];
            $max = 2;
            if ($value >= 1 && $value <= $max && is_numeric($value)) {
                $_SESSION['cart']['products']['quantity_'.$isbn] = $value;
            } 

            $i++;
        }
        $go= true;
    }
    
    /////////////////
    // Remove Book //
    /////////////////
    if (isset($_POST['remove_isbn']))
    {
        $removeIsbn = $_POST['remove_isbn'];
        // create temporary array
        $temp = array();
        
        // save all products except the removed product to the temp array
        $i=1;
        while(isset($_SESSION['cart']['products']['isbn_'.$i])){
            $isbn = $_SESSION['cart']['products']['isbn_'.$i];
            if($isbn!=$removeIsbn)
            {
                $temp[$isbn] = $_SESSION['cart']['products']['quantity_'.$isbn];
            }
            $i++;
        }
        
        // clear cart
        $_SESSION['cart']['products'] = array();
                
        // add all remaining products to cart
        $i=1;
        foreach ($temp as $key => $value)
        {
            $_SESSION['cart']['products']['isbn_'.$i] = $key;
            $_SESSION['cart']['products']['quantity_'.$key] = $value;
            $i++;
        }
        // decrease size 
        $_SESSION['cart']['size'] -= 1;
        
        // if cart size is zero, destroy cart for consitency
        if ($_SESSION['cart']['size'] == 0)
        {
            session_destroy();
        }
        
        $go= true;
    }
    
    ////////////////
    // Clear Cart //
    ////////////////
    if (isset($_POST['clear_cart']))
    {
        session_destroy();
        $go= true;
    }
    
//        // Debugging:
//    if (isset($_SESSION))
//    {
//        echo "<pre>";
//        print_r($_SESSION);
//        echo "</pre>";
//    } else {
//        echo 'no session';
//    }
    
    // if changes have been made then update total
    if ($go) {
        
        //////////////////
        // Update Total //
        //////////////////
        $i=1;
        $total = 0;
        while(isset($_SESSION['cart']['products']['isbn_'.$i])){
            $isbn = $_SESSION['cart']['products']['isbn_'.$i];

            // look up book details
            $data = detailsLookup($isbn);

            $quantity = $_SESSION['cart']['products']['quantity_'.$isbn];
            $itemPrice = $data['ListPrice'];
            $subtotal = $data['ListPrice'] * $quantity;
            $total += $subtotal;
            $i++;
        }
        // Update session variables
        $_SESSION['cart']['total'] = $total;
        
        
        
        // Redirect to this page to avoid double posts
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
    ?>
