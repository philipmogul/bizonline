<?php
 $allowedOrigins = array(
    "https://bizonline.co.ke",
    "https://mobile.bizonline.co.ke",
    "http://localhost:4200",
    "http://localhost:8100",
    "http://localhost",
    "http://192.168.100.2",
 );
 
 $http_origin = "";
  
 if(in_array("".$_SERVER['HTTP_ORIGIN']."", $allowedOrigins))
 {
  $http_origin = $_SERVER['HTTP_ORIGIN'];
 } else {
  $http_origin = "https://example.com";
 }


// $http_origin = $_SERVER['HTTP_ORIGIN'];
 
 
 // Allow from any origin
 header("Access-Control-Allow-Origin: *");
 if ($_SERVER['HTTP_ORIGIN']) {
     // header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    //  header("Access-Control-Allow-Origin: *");
     // header("Access-Control-Allow-Origin: localhost:8100");
     header('Access-Control-Allow-Credentials: true');
     header('Access-Control-Max-Age: 86400');    // cache for 1 day
 }

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}



// Uwazii variables
$uwazii_url ='https://api.uwaziimobile.com/api/v2/SendSMS';


require_once("JWT/autoload.php");
use \Firebase\JWT\JWT;

// secret key can be a random string and keep in secret from anyone
define('SECRET_KEY', 'PhemtechKey');
// Algorithm used to sign the token
define('ALGORITHM', 'HS256');


// $servername = "localhost:3306";
// $username = "bizonlin";
// $password = "Steve Admin";
// $dbname = "bizonlin_shops";

$servername = "localhost:3306";
$username = "";
$password = "steve254";
$dbname = "otc";

$conn=mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// DEFINE GLOBAL FUNCTIONS

// function to encrypt string data
function encrypt_data($string) {

    // define AES encryption key and Initialization Vector
    // convert to hex
    $AESKEY_hex = bin2hex("liquorkey444yes99decrypt555liquo");
    $AESIV_hex =  bin2hex("liquorkey444yes9");
    // convert to bin
    $AESKEY_bin = hex2bin($AESKEY_hex);
    $AESIV_bin =  hex2bin($AESIV_hex);

    // convert values to hex and bin
    $hex_string =  bin2hex($string);
    $bin_string = hex2bin($hex_string);

    $encrypted_string = openssl_encrypt($bin_string, 'aes-256-cbc', $AESKEY_bin, OPENSSL_RAW_DATA, $AESIV_bin);

    // encode to base64
    $decoded_encrypted_string = base64_encode($encrypted_string);

    // perform final encode
    $final_string = base64_encode($decoded_encrypted_string);

    return $final_string;
}

// function to decrypt string data
function decrypt_data($string) {

    // decode the string
    $decoded_string = base64_decode($string);

    // define AES encryption key and Initialization Vector
    // convert to hex
    $AESKEY_hex = bin2hex("liquorkey444yes99decrypt555liquo");
    $AESIV_hex =  bin2hex("liquorkey444yes9");
    // convert to bin
    $AESKEY_bin = hex2bin($AESKEY_hex);
    $AESIV_bin =  hex2bin($AESIV_hex);

    return openssl_decrypt($decoded_string, 'aes-256-cbc', $AESKEY_bin, 0, $AESIV_bin);
}






function confirm_shop_current_monthly_subscription_renewal($conn1, $shop_id, $price) {
    $sql="SELECT * FROM shop_subscription_renewals
            WHERE shop_id='{$shop_id}' AND amount_paid='{$price}' ORDER BY id DESC LIMIT 1";
    $result=mysqli_query($conn1, $sql);

    if($result) {

        if(mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {

                // $current_month = date('Y-m', time());

                // $subscription_month = date('Y-m', strtotime($row['date_renewed']));

                $date_reniewed = new DateTime($row['date_renewed']);
                $TODAY = date('Y-m-d', time());

                $TODAY = date_create($TODAY);

                $difference = date_diff($date_reniewed, $TODAY);

                $diff = $difference->format("%a");

                if($diff <= 31) {

                    return '11';

                } else {
                    return '00';
                }
            }

        } else {
            return '00';
        }

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Could not get subscription status!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function confirm_shop_current_year_subscription_renewal($conn1, $shop_id, $price) {
    $sql="SELECT max(date_renewed) AS date_renewed FROM shop_subscription_renewals WHERE shop_id='{$shop_id}' AND amount_paid='{$price}'";
    $result=mysqli_query($conn1, $sql);

    if($result) {

        if(mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {

                // $current_year = date('Y');

                // $subscription_year = date('Y', strtotime($row['date_renewed']));

                // check difference in days
                if($row['date_renewed'] != null) {
                    
                    $date_reniewed = new DateTime($row['date_renewed']);
                    $TODAY = date('Y-m-d h:i', time());

                    $TODAY = date_create($TODAY);

                    $difference = date_diff($date_reniewed,$TODAY);

                    $diff = $difference->format("%a");

                    if($diff <= 365) {

                        return '1';

                    } else {
                        return '0';
                    }

                } else {
                    return '0';
                }

            }

        } else {
            return '0';
        }

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Could not get subscription status!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function get_subscription_features($conn1, $option_id) {
    $sql = "SELECT * FROM subscription_features WHERE subscription_option_id ='{$option_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $features=[];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            $features[$cr]['feature'] = $row['feature'];

            $cr++;
        }

        return $features;

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Could not get subscription features!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function get_subscription_option($conn1, $option_id) {
    $sql = "SELECT * FROM subscription_options WHERE id='{$option_id}'";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $subscription;

        if(mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {
                $subscription['type'] = $row['type'];
                $subscription['price'] = $row['price'];
                $subscription['extra_user_account'] = $row['extra_user_account'];

                $features = get_subscription_features($conn1, $option_id);
                $subscription['features'] = $features;
            }

            return $subscription;

        } else {
            $data_insert = array(
            "status" => "error",
            "message" => "Subscription option not found!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn1);

            exit();
        }

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Could not get subscription option!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function check_shop_subscription_status($conn1, $shop_id) {

    $sql = "SELECT * FROM shop_subscriptions WHERE shop_id='{$shop_id}'
                AND date_subscribed IN
                (SELECT max(date_subscribed) FROM shop_subscriptions WHERE shop_id='{$shop_id}')";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        if(mysqli_num_rows($result) > 0) {

            while($row=mysqli_fetch_assoc($result)) {
                // get the subscription option detail

                $subscription_option = get_subscription_option($conn1, $row['subscription_option_id']);

                if ($subscription_option['type'] === 'monthly') {

                    $price = $subscription_option['price'] + ($subscription_option['extra_user_account'] * ($row['user_accounts']-1));
                    // check renewal of this month
                    $subscription_status = confirm_shop_current_monthly_subscription_renewal($conn1, $shop_id, $price);

                    return $subscription_status;

                } else {

                    $price = $subscription_option['price'] + ($subscription_option['extra_user_account'] * ($row['user_accounts']-1));
                    // check renewal of this year
                    $subscription_status = confirm_shop_current_year_subscription_renewal($conn1, $shop_id, $price);

                    return $subscription_status;
                }
            }

        } else {
            $data_insert = array(
            "status" => "error",
            "message" => "No subscription!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn1);

            exit();
        }

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Could not get your shop subscription!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }

}




function get_shop_details($conn1, $shop_id) {
    $sql="SELECT * FROM shops WHERE id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $shop;

        while($row=mysqli_fetch_assoc($result)) {
            $shop['name'] = encrypt_data($row['name']);
            $shop['description'] = encrypt_data($row['description']);
            $shop['location'] = encrypt_data($row['location']);
        }

        return $shop;

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Could not get your shop!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function get_shop_contacts($conn1, $shop_id) {
    $sql = "SELECT * FROM shop_contacts WHERE shop_id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $contacts=[];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $contacts[$cr]['id'] = encrypt_data($row['id']);
            $contacts[$cr]['contact'] = encrypt_data($row['contact']);

            $cr++;
        }

        return $contacts;

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in contacts!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function get_shop_subscription_option($conn1, $option_id) {
    $sql = "SELECT * FROM subscription_options WHERE id='{$option_id}'";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $subscription;

        if(mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {
                $subscription['type'] = $row['type'];
                $subscription['price'] = $row['price'];
                $subscription['extra_user_account'] = $row['extra_user_account'];
            }

            return $subscription;

        } else {
            $data_insert = array(
            "status" => "error",
            "message" => "Subscription option not found!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn1);

            exit();
        }

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Could not get subscription option!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function get_shop_subscription($conn1, $shop_id) {

    $sql = "SELECT * FROM shop_subscriptions WHERE shop_id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $subscription;

        while($row = mysqli_fetch_assoc($result)) {
            // get the subscription
            $subscription = get_shop_subscription_option($conn1, $row['subscription_option_id']);
            
            $subscription['userAccounts'] = $row['user_accounts'];

        }

        return $subscription;

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in shop subscription!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}


function get_user_shops($conn1, $user_id) {
    $sql = "SELECT * FROM shop_users WHERE user_id='{$user_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $shops = [];
        $cr=0;

        if(mysqli_num_rows($result) > 0) {

            while($row=mysqli_fetch_assoc($result)) {

                $shop_id = $row['shop_id'];

                // check the shop subscription status
                $shop_subscription_status = check_shop_subscription_status($conn1, $shop_id);

                // get the shop details
                $shop_details = get_shop_details($conn1, $shop_id);

                $shops[$cr]['id'] = encrypt_data($shop_id);
                $shops[$cr]['sub'] = encrypt_data($shop_subscription_status);
                $shops[$cr]['shop_details'] = $shop_details;
                $shops[$cr]['contacts'] = get_shop_contacts($conn1, $shop_id);
                $shops[$cr]['user_roles'] = get_shop_user_roles($conn1, $user_id, $shop_id);

                $cr++;

            }

            return $shops;

        } else {
            $data_insert = array(
            "status" => "error",
            "message" => "Your shop is missing!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn1);

            exit();
        }

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in su!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function get_shop_username($conn1, $user_id) {
    $sql = "SELECT * FROM users WHERE id='{$user_id}'";
    $result=mysqli_query($conn1, $sql);

    $user_name = '';

    if($result) {

        while($row=mysqli_fetch_assoc($result)) {
            $user_name = $row['username'];
        }

        return encrypt_data($user_name);

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in user detail!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}


function get_shop_user_role($conn1, $user_id) {
    $sql = "SELECT * FROM users WHERE id='{$user_id}'";
    $result=mysqli_query($conn1, $sql);

    $user_role = '';

    if($result) {

        while($row=mysqli_fetch_assoc($result)) {
            $user_role = $row['role'];
        }

        return $user_role;

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in user detail!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}


function get_shop_user_record($conn1, $user_id) {
    $sql = "SELECT * FROM users WHERE id='{$user_id}'";
    $result=mysqli_query($conn1, $sql);

    $user = null;

    if($result) {

        while($row=mysqli_fetch_assoc($result)) {
            $user['id'] = encrypt_data($row['id']);
            $user['username'] = encrypt_data($row['username']);
        }

        return $user;

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in user detail!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function get_shop_user_details($conn1, $user_id) {
    $sql = "SELECT * FROM users WHERE id='{$user_id}'";
    $result=mysqli_query($conn1, $sql);

    $user = null;

    if($result) {

        while($row=mysqli_fetch_assoc($result)) {
            $user['id'] = encrypt_data($row['id']);
            $user['username'] = encrypt_data($row['username']);
            $user['mobile'] = encrypt_data($row['mobile']);
            $user['password'] = encrypt_data($row['password']);
            $user['role'] = encrypt_data($row['role']);
            $user['disabled'] = encrypt_data($row['disabled']);
        }

        return $user;

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in user shop detail!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function get_shop_user_roles($conn1, $user_id, $shop_id) {

    $sql = "SELECT * FROM shop_user_roles WHERE user_id='{$user_id}' AND shop_id='{$shop_id}'";
    $result=mysqli_query($conn1, $sql);

    if($result) {

        $user=null;

        while($row=mysqli_fetch_assoc($result)) {

            $user['sell_cash'] = encrypt_data($row['sell_cash']);
            $user['sell_differed'] = encrypt_data($row['sell_differed']);
            $user['add_suppliers'] = encrypt_data($row['add_suppliers']);
            $user['adding_stock'] = encrypt_data($row['adding_stock']);
            $user['reversing_payments'] = encrypt_data($row['reversing_payments']);
            $user['adding_expenses'] = encrypt_data($row['adding_expenses']);
            $user['receiving_pending_payments'] = encrypt_data($row['receiving_pending_payments']);
            $user['transfering_items'] = encrypt_data($row['transfering_items']);
            $user['add_transfer'] = encrypt_data($row['add_transfer']);
            $user['adding_new_debtors'] = encrypt_data($row['adding_new_debtors']);
            $user['send_debtor_messages'] = encrypt_data($row['send_debtor_messages']);
            $user['set_debtor_limits'] = encrypt_data($row['set_debtor_limits']);
            $user['close_sales'] = encrypt_data($row['close_sales']);
            $user['manage_stock'] = encrypt_data($row['manage_stock']);
          };

        return $user;

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in user shop roles!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}



function get_category_detail($conn1, $category_id, $company_id) {

    $sql2="SELECT * FROM stock_categories
    WHERE id='{$category_id}' AND shop_id='{$company_id}'";
    $result2=mysqli_query($conn1, $sql2);
    if ($result2) {
        $category;

        while($row2=mysqli_fetch_assoc($result2)) {
            $category['id'] = encrypt_data($row2['id']);
            $category['category_name'] = encrypt_data($row2['category_name']);
            $category['added_by'] = encrypt_data($row2['added_by']);
        }

        return $category;

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in category!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }

}

function get_all_shop_stock_categories($conn1, $shop_id) {

    $sql3="SELECT * FROM stock_categories WHERE shop_id='{$shop_id}' ORDER BY category_name ASC";
    $result3=mysqli_query($conn1, $sql3);

    $total_records2=0;
    $rc2=1;

    $cr3=0;
    if ($result3) {

        $categories = [];

        $total_records2 = mysqli_num_rows($result3);
        while($row3=mysqli_fetch_assoc($result3)) {

            $categories[$cr3]['id'] = encrypt_data($row3['id']);
            $categories[$cr3]['category_name'] = encrypt_data($row3['category_name']);
            $categories[$cr3]['added_by'] = encrypt_data($row3['added_by']);
            $cr3++;


        }

        return $categories;

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in all categories!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);
    }
}

function get_category_products_count($conn1, $category_id) {
    $sql3="SELECT COUNT(*) AS total_products FROM all_stock WHERE category_id='{$category_id}'";
    $result3=mysqli_query($conn1, $sql3);

    if ($result3) {

        $record = mysqli_fetch_assoc($result3);

        return encrypt_data($record['total_products']);


    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in products count!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function get_shop_minimum_stock_items($conn1, $shop_id) {

    $sql = "SELECT * FROM min_stock_items WHERE shop_id='{$shop_id}'";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        if (mysqli_num_rows($result) > 0) {

            $record = mysqli_fetch_assoc($result);
            $min_quantity = $record['min_quantity'];

            $sql="SELECT * FROM all_stock WHERE shop_id='{$shop_id}' AND quantity <= '{$min_quantity}'";
            $result2 = mysqli_query($conn1, $sql);

            if ($result2) {
                $allData=[];

                $cr = 0;

                while($row=mysqli_fetch_assoc($result2)) {
                    //  get the category for this item
                    $category = get_category_detail($conn1, $row['category_id'], $shop_id);
                    $allData[$cr]['id'] = encrypt_data($row['id']);
                    $allData[$cr]['item_name'] = encrypt_data($row['item_name']);
                    $allData[$cr]['category_id'] = encrypt_data($row['category_id']);
                    $allData[$cr]['category'] = $category;
                    $allData[$cr]['quantity'] = encrypt_data($row['quantity']);
                    $allData[$cr]['buying_price'] = encrypt_data($row['buying_price']);
                    $allData[$cr]['selling_price'] = encrypt_data($row['selling_price']);
                    $allData[$cr]['date_brought'] = encrypt_data($row['date_brought']);
                    $allData[$cr]['added_by'] = encrypt_data($row['added_by']);
                    $allData[$cr]['imageUrl'] = encrypt_data($row['imageUrl']);
                    $allData[$cr]['min_quantity'] = encrypt_data($min_quantity);
                    $cr++;
                }


                return $allData;

            } else {
                $data_insert = array(
                "status" => "error",
                "message" => "Something bad happened in all stock!"
                );
                // return the error
                echo json_encode($data_insert);
                // close connection
                mysqli_close($conn1);
            }

        } else {
            // get items below 5

            $min_quantity = 5;
            $sql="SELECT * FROM all_stock WHERE shop_id='{$shop_id}' AND quantity <= '{$min_quantity}'";
            $result2 = mysqli_query($conn1, $sql);

            if ($result2) {
                $allData=[];

                $cr = 0;

                while($row=mysqli_fetch_assoc($result2)) {
                    //  get the category for this item
                    $category = get_category_detail($conn1, $row['category_id'], $shop_id);
                    $allData[$cr]['id'] = encrypt_data($row['id']);
                    $allData[$cr]['item_name'] = encrypt_data($row['item_name']);
                    $allData[$cr]['category_id'] = encrypt_data($row['category_id']);
                    $allData[$cr]['category'] = $category;
                    $allData[$cr]['quantity'] = encrypt_data($row['quantity']);
                    $allData[$cr]['buying_price'] = encrypt_data($row['buying_price']);
                    $allData[$cr]['selling_price'] = encrypt_data($row['selling_price']);
                    $allData[$cr]['date_brought'] = encrypt_data($row['date_brought']);
                    $allData[$cr]['added_by'] = encrypt_data($row['added_by']);
                    $allData[$cr]['imageUrl'] = encrypt_data($row['imageUrl']);
                    $allData[$cr]['min_quantity'] = encrypt_data($min_quantity);
                    $cr++;
                }

                return $allData;

            } else {
                $data_insert = array(
                "status" => "error",
                "message" => "Something bad happened in all stock!"
                );
                // return the error
                echo json_encode($data_insert);
                // close connection
                mysqli_close($conn1);
            }
        }

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Something bad happened in min stock items!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);
    }
}


function get_items_in_print_list($conn1, $shop_id, $added_by) {

    $sql = "SELECT * FROM print_list WHERE shop_id='{$shop_id}'
                AND sold_by='{$added_by}' AND customer_id=0";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $cartItems = [];
        $cr=0;

        while ($row = mysqli_fetch_assoc($result)) {

            $cartItems[$cr]['id'] = encrypt_data($row['id']);
            $cartItems[$cr]['quantity'] = encrypt_data($row['quantity']);
            $cartItems[$cr]['buying_price'] = encrypt_data($row['buying_price']);
            $cartItems[$cr]['selling_price'] = encrypt_data($row['selling_price']);
            $cartItems[$cr]['item_detail'] = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $cr++;
        }

        return $cartItems;

    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get list items!"
            );

            echo json_encode($data_insert);
            mysqli_close($conn1);

            exit();
    }

}



function get_shop_item_details($conn1, $shop_id, $item_id) {
    $sql="SELECT * FROM all_stock
        WHERE id='{$item_id}' AND shop_id='{$shop_id}'";
    $result=mysqli_query($conn1, $sql);

    $itemDetails;

    if($result) {

        if (mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {

                $category = get_category_detail($conn1, $row['category_id'], $shop_id);
                $itemDetails['id'] = encrypt_data($row['id']);
                $itemDetails['item_name'] = encrypt_data($row['item_name']);
                $itemDetails['category_id'] = encrypt_data($row['category_id']);
                $itemDetails['category'] = $category;
                $itemDetails['quantity'] = encrypt_data($row['quantity']);
                $itemDetails['buying_price'] = encrypt_data($row['buying_price']);
                $itemDetails['selling_price'] = encrypt_data($row['selling_price']);
                $itemDetails['date_brought'] = encrypt_data($row['date_brought']);
                $itemDetails['added_by'] = encrypt_data($row['added_by']);
                $itemDetails['imageUrl'] = encrypt_data($row['imageUrl']);
            }

            return $itemDetails;

        } else {

            $sql="SELECT * FROM all_stock WHERE id='{$item_id}' AND shop_id='{$shop_id}'";
            $result=mysqli_query($conn1, $sql);

            if($result) {

                if (mysqli_num_rows($result) > 0) {

                    while($row = mysqli_fetch_assoc($result)) {

                        $category = get_category_detail($conn1, $row['category_id'], $shop_id);
                        $itemDetails['id'] = encrypt_data($row['id']);
                        $itemDetails['item_name'] = encrypt_data($row['item_name']);
                        $itemDetails['category_id'] = encrypt_data($row['category_id']);
                        $itemDetails['category'] = $category;
                        $itemDetails['quantity'] = encrypt_data($row['quantity']);
                        $itemDetails['buying_price'] = encrypt_data($row['buying_price']);
                        $itemDetails['selling_price'] = encrypt_data($row['selling_price']);
                        $itemDetails['date_brought'] = encrypt_data($row['date_brought']);
                        $itemDetails['added_by'] = encrypt_data($row['added_by']);
                        $itemDetails['imageUrl'] = encrypt_data($row['imageUrl']);
                    }

                    return $itemDetails;

                } else {

                    return null;


                }

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Could not get item details2"
                );

                echo json_encode($data_insert);
                mysqli_close($conn);

                exit();
            }

        }

    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get item details"
        );

        echo json_encode($data_insert);
        mysqli_close($conn);

        exit();
    }
}

function get_shop_invoice_items($conn1, $shop_id, $invoice_id) {
    $sql="SELECT * FROM added_stock WHERE
            invoice_id='{$invoice_id}' AND
            shop_id='{$shop_id}'
            ORDER BY id DESC";

    $result=mysqli_query($conn1, $sql);

    $allItems=[];

    if($result) {

        $cr = 0;

        $total=0;

        while($row=mysqli_fetch_assoc($result)) {

            // get item details
            $allItems[$cr]['id'] = encrypt_data($row['id']);
            $allItems[$cr]['quantity_added'] = encrypt_data($row['quantity_added']);
            $allItems[$cr]['old_buying_price'] = encrypt_data($row['old_buying_price']);
            $allItems[$cr]['buying_price'] = encrypt_data($row['buying_price']);
            $allItems[$cr]['old_selling_price'] = encrypt_data($row['old_selling_price']);
            $allItems[$cr]['selling_price'] = encrypt_data($row['selling_price']);
            $allItems[$cr]['date_added'] = encrypt_data($row['date_added']);
            $allItems[$cr]['item_detail'] = get_shop_item_details($conn1, $shop_id, $row['item_id']);
            $allItems[$cr]['added_by'] = encrypt_data($row['added_by']);

            $cr++;

            $total += ($row['buying_price'] * $row['quantity_added']);

        }

        $Items['items'] = $allItems;
        $Items['total'] = encrypt_data($total);

        return $Items;




    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get invoice items"
        );

        echo json_encode($data_insert);
        mysqli_close($conn);

        exit();
    }
}


function get_shop_invoice_items_new($conn1, $shop_id, $invoice_id) {
    $sql="SELECT * FROM added_stock WHERE
            invoice_id='{$invoice_id}' AND
            shop_id='{$shop_id}'
            ORDER BY id DESC";

    $result=mysqli_query($conn1, $sql);

    $allItems=[];

    if($result) {

        $cr = 0;

        $total=0;

        while($row=mysqli_fetch_assoc($result)) {

            // get item details
            $allItems[$cr]['id'] = encrypt_data($row['id']);
            $allItems[$cr]['quantity_added'] = $row['quantity_added'];
            $allItems[$cr]['old_buying_price'] = $row['old_buying_price'];
            $allItems[$cr]['buying_price'] = $row['buying_price'];
            $allItems[$cr]['old_selling_price'] = $row['old_selling_price'];
            $allItems[$cr]['selling_price'] = $row['selling_price'];
            $allItems[$cr]['date_added'] = $row['date_added'];
            $allItems[$cr]['item_detail'] = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $cr++;

            $total += ($row['buying_price'] * $row['quantity_added']);

        }

        $Items['items'] = $allItems;
        $Items['total'] = $total;

        return $Items;




    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get invoice items"
        );

        echo json_encode($data_insert);
        mysqli_close($conn);

        exit();
    }
}


function get_shop_invoice_total_items_count($conn1, $shop_id, $invoice_id) {
    $sql="SELECT COUNT(DISTINCT item_id) AS total_items FROM added_stock WHERE
            invoice_id='{$invoice_id}'
            AND shop_id='{$shop_id}'";

    $result=mysqli_query($conn1, $sql);


    if($result) {

        $record = mysqli_fetch_assoc($result);

        $total= encrypt_data($record['total_items']);

        return $total;

    } else {
        http_response_code(403);
             $data_insert=array(
             "status" => "error",
             "message" => "Could not get invoice items"
             );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

function get_shop_invoice_payments($conn1, $shop_id, $invoice_id) {

    $sql = "SELECT * FROM invoice_payments WHERE shop_id='{$shop_id}' AND invoice_id='{$invoice_id}'";
    $result=mysqli_query($conn1, $sql);

    if($result) {

        $payments=[];
        $cr=0;

        while($row=mysqli_fetch_assoc($result)) {

            $payments[$cr]['id'] = encrypt_data($row['id']);
            $payments[$cr]['amount_paid'] = encrypt_data($row['amount_paid']);
            $payments[$cr]['payment_method'] = encrypt_data($row['payment_method']);
            $payments[$cr]['date_paid'] = encrypt_data($row['date_paid']);
            $payments[$cr]['paid_by'] = get_shop_username($conn1, $row['paid_by']);

            $cr++;
        }

        return $payments;

    } else {
             $data_insert=array(
             "status" => "error",
             "message" => "Could not get invoice payments"
             );

        echo json_encode($data_insert);
        mysqli_close($conn1);
    }

}


function get_supplier_invoices($conn1, $shop_id, $supplier_id) {
    $sql2="SELECT * FROM add_invoices WHERE shop_id='{$shop_id}' AND supplier_id='{$supplier_id}' AND id IN
            (SELECT invoice_id FROM added_stock) ORDER BY id DESC";
    $result2=mysqli_query($conn1, $sql2);

    if($result2) {

        $invoices=[];
        $cr=0;

        $total_supplier_amount = 0;

        while($row = mysqli_fetch_assoc($result2)) {

            $id = $row['id'];

            $invoice_items = get_shop_invoice_items_new($conn1, $shop_id, $id);

            $invoices[$cr]['id'] = encrypt_data($row['id']);
            $invoices[$cr]['name'] = $row['name'];
            $invoices[$cr]['date_added'] = $row['date_added'];
            $invoices[$cr]['added_by'] = get_shop_username($conn1, $row['added_by']);
            $invoices[$cr]['invoice_items'] = $invoice_items['items'];
            $invoices[$cr]['total_invoice_amount'] = $invoice_items['total'];

            $total_supplier_amount += $invoice_items['total'];

            $cr++;
        }

        $invoicelist['invoices'] = $invoices;
        $invoicelist['total_supplier_amount'] = $total_supplier_amount;

        return $invoicelist;


    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get invoices"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);
    }

}


function get_supplier_invoices_new($conn1, $shop_id, $supplier_id) {
    $sql2="SELECT a.id, a.name, a.date_added, a.added_by, b.total_amount FROM
            (SELECT id, name, date_added, added_by FROM add_invoices WHERE shop_id='{$shop_id}' AND supplier_id='{$supplier_id}' ORDER BY id DESC) AS a
                INNER JOIN
            (SELECT invoice_id, SUM(buying_price * quantity_added) AS total_amount FROM added_stock WHERE shop_id='{$shop_id}' GROUP BY invoice_id) AS b
                ON b.invoice_id=a.id
            WHERE b.total_amount > 0 ORDER BY a.id DESC ";
    $result2=mysqli_query($conn1, $sql2);

    if($result2) {

        $invoices=[];
        $cr=0;

        $total_supplier_amount = 0;

        while($row = mysqli_fetch_assoc($result2)) {

            $id = $row['id'];

            // $invoice_items = get_shop_invoice_items_new($conn1, $shop_id, $id);

            $invoices[$cr]['id'] = encrypt_data($row['id']);
            $invoices[$cr]['name'] = $row['name'];
            $invoices[$cr]['date_added'] = $row['date_added'];
            $invoices[$cr]['added_by'] = get_shop_username($conn1, $row['added_by']);
            $invoices[$cr]['invoice_items'] = [];
            $invoices[$cr]['total_invoice_amount'] = $row['total_amount'];

            $total_supplier_amount += $row['total_amount'];

            $cr++;
        }

        $invoicelist['invoices'] = $invoices;
        $invoicelist['total_supplier_amount'] = $total_supplier_amount;

        return $invoicelist;


    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get invoices"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);
    }

}




function get_date_suppliers($conn1, $shop_id, $date) {

    $sql = "SELECT a.id, a.name, b.added_stock FROM
            (SELECT id, name FROM suppliers WHERE shop_id='{$shop_id}') AS a
            INNER JOIN
            (SELECT id, supplier_id FROM add_invoices WHERE shop_id='{$shop_id}' AND
                DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS c
            ON a.id = c.supplier_id
            INNER JOIN
            (SELECT invoice_id, IFNULL(SUM(buying_price*quantity_added), 0) AS added_stock FROM added_stock
                WHERE shop_id='{$shop_id}' AND
                DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                GROUP BY invoice_id
                ORDER BY invoice_id ASC) AS b
            ON c.id=b.invoice_id
            INNER JOIN
            (SELECT id, supplier_id FROM add_invoices WHERE shop_id='{$shop_id}' AND
                DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS d
            ON b.invoice_id = d.id";

    $result=mysqli_query($conn1, $sql);

    if($result) {

        $suppliers = [];
        $cr=0;

        while($row=mysqli_fetch_assoc($result)) {

            $suppliers[$cr]['id'] = encrypt_data($row['id']);
            $suppliers[$cr]['name'] = $row['name'];
            $suppliers[$cr]['added_stock'] = $row['added_stock'];

            $cr++;

        }

        return $suppliers;

    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in suppliers",
            "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}



function get_date_supplier_invoices($conn1, $date, $shop_id, $supplier_id) {

    $sql2="SELECT * FROM add_invoices WHERE shop_id='{$shop_id}' AND
            supplier_id='{$supplier_id}' AND
            DATE_FORMAT(date_added, '%Y-%m-%d')= DATE_FORMAT('{$date}', '%Y-%m-%d')
            ORDER BY id DESC";
    $result2=mysqli_query($conn1, $sql2);

    if($result2) {

        $invoices=[];
        $cr=0;

        $total_supplier_amount = 0;

        while($row = mysqli_fetch_assoc($result2)) {

            $id = $row['id'];

            $invoice_items = get_shop_invoice_items($conn1, $shop_id, $id);


            $invoices[$cr]['invoice_id'] = encrypt_data($row['id']);
            $invoices[$cr]['name'] = $row['name'];
            $invoices[$cr]['added_by'] = get_shop_username($conn1, $row['added_by']);
            $invoices[$cr]['invoice_items'] = $invoice_items['items'];
            $invoices[$cr]['total_invoice_amount'] = $invoice_items['total'];

            $total_supplier_amount += decrypt_data($invoice_items['total']);

            $cr++;
        }

        $invoicelist['invoices'] = $invoices;
        $invoicelist['total_supplier_amount'] = encrypt_data($total_supplier_amount);

        return $invoicelist;


    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get invoices"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);
    }

}


function get_current_user_supplier_invoices($conn1, $shop_id, $supplier_id, $added_by) {

    $TODAY = date('Y-m-d h:i:s', time());
    $sql2="SELECT * FROM add_invoices WHERE shop_id='{$shop_id}' AND supplier_id='{$supplier_id}'
            AND added_by='{$added_by}' AND DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
            ORDER BY id DESC";
    $result2=mysqli_query($conn1, $sql2);

    if($result2) {

        $invoices=[];
        $cr=0;

        $total_supplier_amount = 0;

        while($row = mysqli_fetch_assoc($result2)) {


            $id = $row['id'];

            $invoice_items = get_shop_invoice_items($conn1, $shop_id, $id);

            $invoices[$cr]['id'] = encrypt_data($row['id']);
            $invoices[$cr]['name'] = encrypt_data($row['name']);
            $invoices[$cr]['date_added'] = $row['date_added'];
            $invoices[$cr]['added_by'] = get_shop_username($conn1, $row['added_by']);
            $invoices[$cr]['invoice_items'] = $invoice_items['items'];
            $invoices[$cr]['total_invoice_amount'] = $invoice_items['total'];

            $total_supplier_amount += decrypt_data($invoice_items['total']);

            $cr++;
        }

        $invoicelist['invoices'] = $invoices;
        $invoicelist['total_supplier_amount'] = encrypt_data($total_supplier_amount);

        return $invoicelist;


    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get invoices"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}


function get_current_admin_supplier_invoices($conn1, $shop_id, $supplier_id) {

    $TODAY = date('Y-m-d h:i:s', time());
    $sql2="SELECT * FROM add_invoices WHERE shop_id='{$shop_id}' AND supplier_id='{$supplier_id}'
            AND DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
            ORDER BY UNIX_TIMESTAMP(date_added) DESC";
    $result2=mysqli_query($conn1, $sql2);

    if($result2) {

        $invoices=[];
        $cr=0;

        $total_supplier_amount = 0;

        while($row = mysqli_fetch_assoc($result2)) {

            $id = $row['id'];


            $invoice_items = get_shop_invoice_items($conn1, $shop_id, $id);

            $invoices[$cr]['id'] = encrypt_data($row['id']);
            $invoices[$cr]['name'] = encrypt_data($row['name']);
            $invoices[$cr]['date_added'] = $row['date_added'];
            $invoices[$cr]['added_by'] = get_shop_username($conn1, $row['added_by']);
            $invoices[$cr]['invoice_items'] = $invoice_items['items'];
            $invoices[$cr]['total_invoice_amount'] = $invoice_items['total'];

            $total_supplier_amount += decrypt_data($invoice_items['total']);

            $cr++;
        }

        $invoicelist['invoices'] = $invoices;
        $invoicelist['total_supplier_amount'] = encrypt_data($total_supplier_amount);

        return $invoicelist;


    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get invoices"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}

function get_supplier_payments($conn1, $shop_id, $supplier_id) {
    $sql="SELECT * FROM invoice_payments WHERE shop_id='{$shop_id}' AND supplier_id='{$supplier_id}' ORDER BY id DESC";
    $result=mysqli_query($conn1, $sql);

    if($result) {

        $payments=[];
        $cr=0;

        $total_paid=0;

        while($row=mysqli_fetch_assoc($result)) {

            $payments[$cr]['id'] = encrypt_data($row['id']);
            $payments[$cr]['amount_paid'] = encrypt_data($row['amount_paid']);
            $payments[$cr]['payment_method'] = encrypt_data($row['payment_method']);
            $payments[$cr]['date_paid'] = encrypt_data($row['date_paid']);
            $payments[$cr]['paid_to'] = get_shop_username($conn1, $row['paid_by']);

            $cr++;

            $total_paid += $row['amount_paid'];
        }

        $payment['records'] = $payments;
        $payment['total_paid'] = encrypt_data($total_paid);

        return $payment;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get payments"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);
    }
}

function get_supplier_payments_new($conn1, $shop_id, $supplier_id) {
    $sql="SELECT * FROM invoice_payments WHERE shop_id='{$shop_id}' AND supplier_id='{$supplier_id}' ORDER BY id DESC";
    $result=mysqli_query($conn1, $sql);

    if($result) {

        $payments=[];
        $cr=0;

        $total_paid=0;

        while($row=mysqli_fetch_assoc($result)) {

            $payments[$cr]['id'] = encrypt_data($row['id']);
            $payments[$cr]['amount_paid'] = $row['amount_paid'];
            $payments[$cr]['payment_method'] = $row['payment_method'];
            $payments[$cr]['date_paid'] = $row['date_paid'];
            $payments[$cr]['paid_to'] = get_shop_username($conn1, $row['paid_by']);

            $cr++;

            $total_paid += $row['amount_paid'];
        }

        $payment['records'] = $payments;
        $payment['total_paid'] = $total_paid;

        return $payment;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get payments"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);
    }
}

function get_supplier_amount_paid($conn1, $shop_id, $supplier_id) {
    $sql="SELECT SUM(amount_paid) AS amount_paid FROM invoice_payments WHERE shop_id='{$shop_id}' AND supplier_id='{$supplier_id}' ORDER BY id DESC";
    $result=mysqli_query($conn1, $sql);

    if($result) {

        $row=mysqli_fetch_assoc($result);

        return $row['amount_paid'];

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get supplier payments!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);
    }
}


function get_waiting_customers($conn1, $shop_id, $added_by) {

    $sql = "SELECT DISTINCT id, customer_name FROM temp_waiting_customers WHERE shop_id='{$shop_id}' AND sold_by='{$added_by}'";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $cr=0;
        $customers=[];

        while ($row = mysqli_fetch_assoc($result)) {

            $cname = $row['customer_name'];

            $customers[$cr]['id'] = encrypt_data($row['id']);
            $customers[$cr]['customer_name'] = encrypt_data($row['customer_name']);

            $sql2 = "SELECT * FROM temp_waiting_customers WHERE customer_name='{$cname}' AND shop_id='{$shop_id}' AND sold_by='{$added_by}'";
            $result2 = mysqli_query($conn1, $sql2);

            if ($result2) {

                $items = [];
                $cr2=0;

                while ($row2 = mysqli_fetch_assoc($result2)) {

                    $items[$cr2]['id'] = encrypt_data($row2['id']);
                    $items[$cr2]['quantity'] = encrypt_data($row2['quantity']);
                    $items[$cr2]['buying_price'] = encrypt_data($row2['buying_price']);
                    $items[$cr2]['selling_price'] = encrypt_data($row2['selling_price']);
                    $items[$cr2]['item_detail'] = get_shop_item_details($conn1, $shop_id, $row2['item_id']);

                    $cr2++;
                }

                $customers[$cr]['items'] = $items;

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Could not get waiting customers1"
                    );

                    echo json_encode($data_insert);
                    mysqli_close($conn1);

                    exit();
            }


        }


        return $customers;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get waiting customers"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}


function get_shop_user_transactions($conn1, $shop_id, $user_id) {

    $TODAY = date('Y-m-d h:i:s', time());
    // check if its a leap year
    $isLeapYear = date("L", time());

    $year = date("Y", time());
    $month = date("m", time());

    $sql='';

    $user = get_shop_user_details($conn1, $user_id);

    $role = decrypt_data($user['role']);

    if (!$isLeapYear && $month == 2) {
        if (date('m-d', time()) == '02-29') {

            $d = $year.'-'.'03'.'-01';

            $TODAY = date('Y-m-d h:i:s', strtotime($d)) ;

            if($role == 'admin') {

                $sql="SELECT DISTINCT transaction_id FROM sold_stock
                        WHERE shop_id='{$shop_id}'
                        AND date_sold >= DATE_SUB('{$TODAY}', INTERVAL 2 DAY)
                        ORDER BY transaction_id DESC";

            } else {
                $sql="SELECT DISTINCT transaction_id FROM sold_stock
                        WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                        AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                        ORDER BY transaction_id DESC";
            }

            
        } else {

            if($role == 'admin') {

                $sql="SELECT DISTINCT transaction_id FROM sold_stock
                        WHERE shop_id='{$shop_id}'
                        AND date_sold >= DATE_SUB('{$TODAY}', INTERVAL 7 DAY)
                        ORDER BY transaction_id DESC";

            } else {
                $sql="SELECT DISTINCT transaction_id FROM sold_stock
                        WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                        AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                        ORDER BY transaction_id DESC";
            }
            

        }
    } else {
        // DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
        if($role == 'admin') {

            $sql="SELECT DISTINCT transaction_id FROM sold_stock
                    WHERE shop_id='{$shop_id}'
                    AND date_sold >= DATE_SUB('{$TODAY}', INTERVAL 2 DAY)
                    ORDER BY transaction_id DESC";

        } else {
            $sql="SELECT DISTINCT transaction_id FROM sold_stock
                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                    AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    ORDER BY transaction_id DESC";
        }
        
    }

    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $transactions=[];
        $cr=0;

        if (mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {

                $sql2 = '';

                if($role == 'admin') {
                    $sql2="SELECT id, transaction_code, date_sold FROM sold_stock
                            WHERE transaction_id='{$row['transaction_id']}' AND shop_id='{$shop_id}'
                            ORDER BY id DESC";
                } else {
                    $sql2="SELECT id, transaction_code, date_sold FROM sold_stock
                            WHERE transaction_id='{$row['transaction_id']}' AND shop_id='{$shop_id}' AND sold_by='{$user_id}'
                            AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                            ORDER BY id DESC";
                }


                $result2=mysqli_query($conn1, $sql2);

                if ($result2) {

                    $row2 = mysqli_fetch_assoc($result2);

                    $transactions[$cr]['transaction_code'] = $row2['transaction_code'];
                    $transactions[$cr]['date'] = $row2['date_sold'];
                    $cr++;

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get transactions3!"
                    );

                    echo json_encode($data_insert);
                    mysqli_close($conn1);

                    exit();
                }


            }

            return $transactions;

        } else {
            return $transactions;
        }

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transactions2!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}



function get_shop_user_differed_transactions($conn1, $shop_id, $user_id) {

    $TODAY = date('Y-m-d', time());
    // check if its a leap year
    $isLeapYear = date("L", time());

    $year = date("Y", time());
    $month = date("m", time());


    $sql='';

    // check if user is admin
    $sql1="SELECT * FROM users WHERE id='{$user_id}'";
    $result1=mysqli_query($conn1, $sql1);
    if ($result1) {

        $record=mysqli_fetch_assoc($result1);

        if($record['role'] == 'admin') {

            $sql="SELECT DISTINCT transaction_id, date_picked FROM pending_payments
                    WHERE shop_id='{$shop_id}'
                    AND date_picked >= DATE_SUB('{$TODAY}', INTERVAL 31 DAY)
                    ORDER BY transaction_id DESC";

            $result=mysqli_query($conn1, $sql);

            if ($result) {

                $transactions=[];
                $cr=0;

                if (mysqli_num_rows($result) > 0) {

                    while($row = mysqli_fetch_assoc($result)) {

                        $sql2="SELECT * FROM pending_payments
                                WHERE shop_id='{$shop_id}'
                                AND transaction_id = '{$row['transaction_id']}'
                                AND DATE_FORMAT(date_picked, '%Y-%m-%d')=DATE_FORMAT('{$row['date_picked']}', '%Y-%m-%d')";
                        $result2=mysqli_query($conn1, $sql2);

                        if ($result2) {

                            $row = mysqli_fetch_assoc($result2);

                            $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                            $transactions[$cr]['sold_by'] = $row['sold_by'];
                            $transactions[$cr]['date'] = $row['date_picked'];
                            $transactions[$cr]['customer'] = get_customer_record($conn1, $row['customer_id'], $shop_id);
                            $cr++;

                        } else {
                            $data_insert=array(
                                "status" => "error",
                                "message" => "Could not get transactions2!"
                            );

                            echo json_encode($data_insert);
                            mysqli_close($conn1);

                            exit();
                        }

                    }

                    return $transactions;

                } else {
                    return $transactions;
                }

            } else {
                $data_insert=array(
                "status" => "error",
                "message" => "Could not get transactions4!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        } else {

            if (!$isLeapYear && $month == 2) {
                if (date('m-d', time()) == '02-29') {

                    $TODAY = $year.'-'.'03'.'-01';

                    $sql="SELECT DISTINCT transaction_id FROM pending_payments
                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    ORDER BY transaction_id DESC";
                } else {
                    $sql="SELECT DISTINCT transaction_id FROM pending_payments
                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    ORDER BY transaction_id DESC";
                }
            } else {

                $sql="SELECT DISTINCT transaction_id FROM pending_payments
                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    ORDER BY transaction_id DESC";
            }



            $result=mysqli_query($conn1, $sql);

            if ($result) {

                $transactions=[];
                $cr=0;

                if (mysqli_num_rows($result) > 0) {

                    while($row = mysqli_fetch_assoc($result)) {

                        $sql2="SELECT *  FROM pending_payments
                        WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                        AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                        AND transaction_id = '{$row['transaction_id']}'";

                        $result2=mysqli_query($conn1, $sql2);

                        if ($result2) {
                            $row = mysqli_fetch_assoc($result2);

                            $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                            $transactions[$cr]['sold_by'] = $row['sold_by'];
                            $transactions[$cr]['date'] = $row['date_picked'];
                            $transactions[$cr]['customer'] = get_customer_record($conn1, $row['customer_id'], $shop_id);
                            $cr++;

                        } else {
                            $data_insert=array(
                                "status" => "error",
                                "message" => "Could not get transactions2!"
                            );

                            echo json_encode($data_insert);
                            mysqli_close($conn1);

                            exit();
                        }


                    }

                    return $transactions;

                } else {
                    return $transactions;
                }

            } else {
                $data_insert=array(
                "status" => "error",
                "message" => "Could not get transactions!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        }


    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get user record!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_current_shop_user_differed_transactions($conn1, $shop_id, $user_id) {

    $TODAY = date('Y-m-d', time());
    // check if its a leap year
    $isLeapYear = date("L", time());

    $year = date("Y", time());
    $month = date("m", time());

    $sql='';

    if (!$isLeapYear && $month == 2) {
        if (date('m-d', time()) == '02-29') {

            $TODAY = $year.'-'.'03'.'-01';

            $sql="SELECT DISTINCT transaction_id FROM pending_payments
            WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
            AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
            ORDER BY transaction_id DESC";
        } else {
            $sql="SELECT DISTINCT transaction_id FROM pending_payments
            WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
            AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
            ORDER BY transaction_id DESC";
        }
    } else {

        $sql="SELECT DISTINCT transaction_id FROM pending_payments
            WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
            AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
            ORDER BY transaction_id DESC";
    }

    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $transactions=[];
        $cr=0;

        if (mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {

                $sql2="SELECT *  FROM pending_payments
                WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                AND transaction_id = '{$row['transaction_id']}'";

                $result2=mysqli_query($conn1, $sql2);

                if ($result2) {
                    $row = mysqli_fetch_assoc($result2);

                    $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                    $transactions[$cr]['sold_by'] = $row['sold_by'];
                    $transactions[$cr]['date'] = $row['date_picked'];
                    $transactions[$cr]['customer'] = get_customer_record($conn1, $row['customer_id'], $shop_id);
                    $cr++;

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get transactions2!"
                    );

                    echo json_encode($data_insert);
                    mysqli_close($conn1);

                    exit();
                }


            }

            return $transactions;

        } else {
            return $transactions;
        }

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transactions!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}


function get_transfer_customer_transactions($conn1, $shop_id, $customer_id, $date) {
    $sql="SELECT DISTINCT transaction_id, date_transfered FROM transfer_goods
            WHERE shop_id='{$shop_id}' AND customer_id='{$customer_id}'
            AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            ORDER BY transaction_id DESC";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $transactions=[];
        $cr=0;

        if (mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {

                $sql2="SELECT * FROM transfer_goods
                        WHERE shop_id='{$shop_id}' AND customer_id='{$customer_id}'
                        AND transaction_id = '{$row['transaction_id']}'
                        AND DATE_FORMAT(date_transfered, '%Y-%m-%d')=DATE_FORMAT('{$row['date_transfered']}', '%Y-%m-%d')
                        ORDER BY id DESC";
                $result2=mysqli_query($conn1, $sql2);

                if ($result2) {

                    $row = mysqli_fetch_assoc($result2);

                    $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                    $transactions[$cr]['sold_by'] = $row['sold_by'];
                    $transactions[$cr]['date'] = $row['date_transfered'];
                    $transactions[$cr]['customer'] = get_customer_record($conn1, $row['customer_id'], $shop_id);
                    $cr++;

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get transactions2!"
                    );

                    echo json_encode($data_insert);
                    mysqli_close($conn1);

                    exit();
                }

            }
        }

        return $transactions;


    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transactions4!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_shop_user_transfer_transactions($conn1, $shop_id, $user_id) {

    $TODAY = date('Y-m-d', time());
    // check if its a leap year
    $isLeapYear = date("L", time());

    $year = date("Y", time());
    $month = date("m", time());


    // check if user is admin
    $sql1="SELECT * FROM users WHERE id='{$user_id}'";
    $result1=mysqli_query($conn1, $sql1);
    if ($result1) {

        $record=mysqli_fetch_assoc($result1);

        if($record['role'] == 'admin') {

            $customers = [];
            $rc=0;

            $sql = "SELECT customer_id, SUM(quantity*buying_price) AS total FROM transfer_goods
                    WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    GROUP BY customer_id";
            $result2=mysqli_query($conn1, $sql);
            if ($result2) {

                while($record = mysqli_fetch_assoc($result2)) {

                    $customers[$rc]['id'] = encrypt_data($record['customer_id']);
                    $customers[$rc]['customer'] = get_customer_record($conn1, $record['customer_id'], $shop_id);
                    $customers[$rc]['total_transfered'] = $record['total'];
                
                    // get the customer transactions
                    $customers[$rc]['transactions'] = get_transfer_customer_transactions($conn1, $shop_id, $record['customer_id'], $TODAY);

                    $rc++;
                    
                }

                return $customers;

            } else {
                $data_insert=array(
                "status" => "error",
                "message" => "Could not get customers!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        } else {

            $sql='';

            if (!$isLeapYear && $month == 2) {
                if (date('m-d', time()) == '02-29') {

                    $TODAY = $year.'-'.'03'.'-01';

                    $sql = "SELECT customer_id, SUM(quantity*buying_price) AS total FROM transfer_goods
                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                        AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    GROUP BY customer_id";

                    // $sql="SELECT DISTINCT transaction_id FROM transfer_goods
                    // WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                    // AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    // ORDER BY transaction_id DESC";
                } else {
                    $sql = "SELECT customer_id, SUM(quantity*buying_price) AS total FROM transfer_goods
                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                        AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    GROUP BY customer_id";

                    // $sql="SELECT DISTINCT transaction_id FROM transfer_goods
                    // WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                    // AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    // ORDER BY transaction_id DESC";
                }
            } else {

                $sql = "SELECT customer_id, SUM(quantity*buying_price) AS total FROM transfer_goods
                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                        AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    GROUP BY customer_id";

                // $sql="SELECT DISTINCT transaction_id FROM transfer_goods
                //     WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                //     AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                //     ORDER BY transaction_id DESC";
            }

            $result=mysqli_query($conn1, $sql);

            if ($result) {

                $customers = [];
                $rc = 0;

                while($record = mysqli_fetch_assoc($result)) {

                    $customers[$rc]['id'] = $record['customer_id'];
                    $customers[$rc]['customer'] = get_customer_record($conn1, $record['customer_id'], $shop_id);
                    $customers[$rc]['total_transfered'] = $record['total'];

                    // get the customer transactions
                    $customers[$rc]['transactions'] = get_transfer_customer_transactions($conn1, $shop_id, $record['customer_id'], $TODAY);

                    $rc++;

                }

                return $customers;

            } else {
                $data_insert=array(
                "status" => "error",
                "message" => "Could not get customers!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        }


    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get user record!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}



function get_shop_user_admin_differed_transactions($conn1, $shop_id, $user_id) {

    $TODAY = date('Y-m-d', time());
    // check if its a leap year
    $isLeapYear = date("L", time());

    $year = date("Y", time());
    $month = date("m", time());


    $sql='';

    if (!$isLeapYear && $month == 2) {
        if (date('m-d', time()) == '02-29') {

            $TODAY = $year.'-'.'03'.'-01';

            $sql="SELECT * FROM pending_payments
            WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
            AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
            AND transaction_code IN (SELECT DISTINCT(transaction_code) FROM pending_payments WHERE shop_id='{$shop_id}')
            ORDER BY id DESC";
        } else {
            $sql="SELECT * FROM pending_payments
                WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                AND transaction_code IN (SELECT DISTINCT(transaction_code) FROM pending_payments WHERE shop_id='{$shop_id}')
                ORDER BY id DESC";
        }
    } else {
        $sql="SELECT DISTINCT transaction_id FROM pending_payments
                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    ORDER BY transaction_id DESC";

        // $sql="SELECT * FROM pending_payments
        //         WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
        //         AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
        //         AND transaction_code IN (SELECT DISTINCT(transaction_code) FROM pending_payments WHERE shop_id='{$shop_id}')
        //         ORDER BY id DESC";
    }

    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $transactions=[];
        $cr=0;

        if (mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {

                $sql2="SELECT * FROM pending_payments
                WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                AND transaction_id='{$row['transaction_id']}'
                AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                ORDER BY id DESC";

                $result2=mysqli_query($conn1, $sql2);

                if ($result2) {

                    $row2 = mysqli_fetch_assoc($result2);

                    $transactions[$cr]['transaction_code'] = $row2['transaction_code'];
                    $transactions[$cr]['date'] = $row2['date_picked'];
                    $transactions[$cr]['customer'] = get_customer_record($conn1, $row2['customer_id'], $shop_id);
                    $cr++;


                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get transactions customer!"
                        );

                        echo json_encode($data_insert);
                        mysqli_close($conn1);

                        exit();
                }



            }

            return $transactions;

        } else {
            return $transactions;
        }

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transactions!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_reversed_differed($conn1, $shop_id, $user_id) {
    $TODAY = date('Y-m-d h:i:s', time());
    // check if its a leap year
    $isLeapYear = date("L", time());

    $year = date("Y", time());
    $month = date("m", time());


    $sql='';

    // check if user is admin
    $sql1="SELECT * FROM users WHERE id='{$user_id}'";
    $result1=mysqli_query($conn1, $sql1);
    if ($result1) {

        $record=mysqli_fetch_assoc($result1);

        if($record['role'] == 'admin') {

            $sql="SELECT * FROM returned_stock
            WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
            AND date_returned >= DATE_SUB('{$TODAY}', INTERVAL 31 DAY)
            AND sale_type='differed' ORDER BY id DESC";

            $result=mysqli_query($conn1, $sql);

            if ($result) {

                $transactions=[];
                $cr=0;

                if (mysqli_num_rows($result) > 0) {

                    while($row = mysqli_fetch_assoc($result)) {
                        $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                        $transactions[$cr]['customer'] = get_customer_record($conn1, $row['customer_id'], $shop_id);
                        $cr++;
                    }

                    return $transactions;

                } else {
                    return $transactions;
                }

            } else {
                $data_insert=array(
                "status" => "error",
                "message" => "Could not get transactions!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        } else {

            if (!$isLeapYear && $month == 2) {
                if (date('m-d', time()) == '02-29') {

                    $TODAY = $year.'-'.'03'.'-01';

                    $sql="SELECT * FROM returned_stock
                            WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                            AND DATE_FORMAT(date_returned, '%Y-%m-%d') = '{$TODAY}'
                            AND sale_type='differed' ORDER BY id DESC";
                } else {
                    $sql="SELECT * FROM returned_stock
                            WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                            AND DATE_FORMAT(date_returned, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                            AND sale_type='differed' ORDER BY id DESC";
                }
            } else {
                $sql="SELECT * FROM returned_stock
                        WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                        AND DATE_FORMAT(date_returned, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                        AND sale_type='differed' ORDER BY id DESC";
            }


            $result=mysqli_query($conn1, $sql);

            if ($result) {

                $transactions=[];
                $cr=0;

                if (mysqli_num_rows($result) > 0) {

                    while($row = mysqli_fetch_assoc($result)) {
                        $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                        $transactions[$cr]['customer'] = get_customer_record($conn1, $row['customer_id'], $shop_id);
                        $cr++;
                    }

                    return $transactions;

                } else {
                    return $transactions;
                }

            } else {
                $data_insert=array(
                "status" => "error",
                "message" => "Could not get transactions!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        }

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get user record!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_admin_reversed_differed($conn1, $shop_id, $user_id) {
    $TODAY = date('Y-m-d h:i:s', time());
    // check if its a leap year
    $isLeapYear = date("L", time());

    $year = date("Y", time());
    $month = date("m", time());


    $sql='';

    if (!$isLeapYear && $month == 2) {
        if (date('m-d', time()) == '02-29') {

            $TODAY = $year.'-'.'03'.'-01';

            $sql="SELECT * FROM returned_stock
                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                    AND DATE_FORMAT(date_returned, '%Y-%m-%d') = '{$TODAY}'
                    AND sale_type='differed' ORDER BY id DESC";
        } else {
            $sql="SELECT * FROM returned_stock
                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                    AND DATE_FORMAT(date_returned, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                    AND sale_type='differed' ORDER BY id DESC";
        }
    } else {
        $sql="SELECT * FROM returned_stock
                WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                AND DATE_FORMAT(date_returned, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                AND sale_type='differed' ORDER BY id DESC";
    }


    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $transactions=[];
        $cr=0;

        if (mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {
                $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                $transactions[$cr]['customer'] = get_customer_record($conn1, $row['customer_id'], $shop_id);
                $cr++;
            }

            return $transactions;

        } else {
            return $transactions;
        }

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transactions!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_shop_user_paid_differed_transactions($conn1, $shop_id, $user_id) {

    $TODAY = date('Y-m-d h:i:s', time());
    $sql="SELECT * FROM differed_payments
            WHERE shop_id='{$shop_id}' AND paid_to='{$user_id}'
            AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
            AND transaction_code IN (SELECT DISTINCT transaction_code FROM differed_payments)";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $transactions=[];
        $cr=0;

        if (mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {
                $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                $transactions[$cr]['customer_id'] = $row['customer_id'];
                $transactions[$cr]['differed_sales_payments_id'] = $row['differed_sales_payments_id'];
                $cr++;
            }

            return $transactions;

        } else {
            return $transactions;
        }

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transactions!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_shop_user_paid_transfer_transactions($conn1, $shop_id, $user_id) {

    $TODAY = date('Y-m-d h:i:s', time());
    $sql="SELECT * FROM transfer_payment_records
            WHERE shop_id='{$shop_id}' AND paid_to='{$user_id}'
            AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
            AND transaction_code IN (SELECT DISTINCT transaction_code FROM transfer_payment_records  WHERE shop_id='{$shop_id}' AND paid_to='{$user_id}'
            AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d'))";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $transactions=[];
        $cr=0;

        if (mysqli_num_rows($result) > 0) {

            while($row = mysqli_fetch_assoc($result)) {
                $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                $transactions[$cr]['customer_id'] = $row['customer_id'];
                $transactions[$cr]['balance_before'] = $row['balance_before'];
                $transactions[$cr]['amount_paid'] = $row['amount_paid'];
                $transactions[$cr]['payment_method'] = $row['payment_method'];
                $cr++;
            }

            return $transactions;

        } else {
            return $transactions;
        }

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transactions!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}





function get_transaction_code_payment($conn1, $code, $shop_id, $date) {

    $sql = "SELECT * FROM sales_payments WHERE shop_id='{$shop_id}' AND transaction_code='{$code}'
            AND DATE_FORMAT(date_paid, '%Y=%m-%d') = DATE_FORMAT('{$date}', '%Y=%m-%d')";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

      if (mysqli_num_rows($result) > 0) {
        $record = mysqli_fetch_assoc($result);

        $payment['payment_method'] = encrypt_data($record['payment_method']);
        $payment['cash_amount'] = encrypt_data($record['cash']);
        $payment['mpesa_amount'] = encrypt_data($record['mpesa']);
        $payment['bank_amount'] = encrypt_data($record['bank']);
        $payment['discount_amount'] = encrypt_data($record['discount']);
        $payment['amount_reversed'] = encrypt_data($record['amount_reversed']);
        $payment['date_paid'] = encrypt_data($record['date_paid']);


        return $payment;
      } else {

        $payment['payment_method'] = encrypt_data('no record found');
        $payment['cash_amount'] = encrypt_data('0');
        $payment['mpesa_amount'] = encrypt_data('0');
        $payment['bank_amount'] = encrypt_data('0');
        $payment['discount_amount'] = encrypt_data('0');
        $payment['amount_reversed'] = encrypt_data('0');
        $payment['date_paid'] = encrypt_data('0');


        return $payment;
      }





    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transaction payment!",
        "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}

function get_paid_differed_transaction_code_payment($conn1, $shop_id, $id) {
    $sql = "SELECT * FROM differed_sales_payments
            WHERE id='{$id}' AND shop_id='{$shop_id}'";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $record = mysqli_fetch_assoc($result);

        $payment['cash_amount'] = encrypt_data($record['cash']);
        $payment['mpesa_amount'] = encrypt_data($record['mpesa']);
        $payment['bank_amount'] = encrypt_data($record['bank']);
        $payment['date_paid'] = encrypt_data($record['date_paid']);


        return $payment;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transaction payment!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_customer_record($conn1, $customer_id, $shop_id) {
    $sql="SELECT * FROM customers
        WHERE id='{$customer_id}' AND shop_id='{$shop_id}'";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $record = mysqli_fetch_assoc($result);

        $customer['id'] = encrypt_data($record['id']);
        $customer['name'] = encrypt_data($record['name']);
        $customer['mobile'] = encrypt_data($record['mobile_number']);

        return $customer;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get customer record!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_message_customers($conn1, $message, $shop_id) {

    $sql2="SELECT DISTINCT customer_id FROM sent_messages WHERE message='{$message}' AND shop_id='{$shop_id}'";
    $result2 = mysqli_query($conn1, $sql2);

    if ($result2) {

        $customers = [];
        $cr2=0;

        while($row2 = mysqli_fetch_assoc($result2)) {

            $cid = $row2['customer_id'];

            $customer = get_customer_record($conn1, $cid, $shop_id);

            $customers[$cr2] = $customer;

            $cr2++;
        }

        return $customers;



    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get messages2"
        );

        echo json_encode($data_insert);
        mysqli_close($conn);

        exit();

    }
}

function get_all_shop_customer_records($conn1, $shop_id) {
    $sql="SELECT * FROM customers WHERE shop_id='{$shop_id}' AND deleted=0 AND mobile_number LIKE '254*'";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $customers = [];
        $cr=0;

        while($record = mysqli_fetch_assoc($result)) {
            $customers[$cr]['id'] = encrypt_data($record['id']);
            $customers[$cr]['name'] = encrypt_data($record['name']);
            $customers[$cr]['mobile'] = encrypt_data($record['mobile_number']);

            $cr++;
        }

        return $customers;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get customer records!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function check_debtor_limit_status($conn1, $customer_id, $shop_id) {
    $sql = "SELECT * FROM customer_pending_payments WHERE shop_id='{$shop_id}' AND customer_id='{$customer_id}'";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        if(mysqli_num_rows($result) > 0) {

            $record = mysqli_fetch_assoc($result);

            $balance = $record['total_amount'] - $record['amount_paid'];

            if ($balance >= $record['balance_limit']) {
                return 1;
            } else {
                return 0;
            }

        } else {
            return 1;
        }

        

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get customer limit!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}



function get_transaction_code_sales($conn1, $code, $shop_id, $user_id, $date) {
    // $user_id = 5;
    $sql = "SELECT * FROM sold_stock
            WHERE transaction_code='{$code}' AND shop_id='{$shop_id}'
            AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $records=[];
        $cr=0;

        while ($row = mysqli_fetch_assoc($result)) {

            $records[$cr]['id'] = encrypt_data($row['id']);
            $records[$cr]['item_id'] = encrypt_data($row['item_id']);
            $records[$cr]['transaction_id'] = encrypt_data($row['transaction_id']);
            $records[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
            $records[$cr]['quantity'] = encrypt_data($row['quantity']);
            $records[$cr]['buying_price'] = encrypt_data($row['buying_price']);
            $records[$cr]['selling_price'] = encrypt_data($row['selling_price']);
            $records[$cr]['item'] = get_shop_item_details($conn1, $shop_id, $row['item_id']);
            $records[$cr]['date_sold'] = encrypt_data($date);

            $cr++;

        }

        return $records;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transaction sales records!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_transaction_code_returned_stock($conn1, $code, $shop_id, $sale_type) {
    $returned_stock = [];
    $cr=0;

    // get the reversed payment
    $sql2="SELECT * FROM returned_stock WHERE transaction_code='{$code}' AND sale_type='{$sale_type}' AND shop_id='{$shop_id}'";
    $result2=mysqli_query($conn1, $sql2);
    if($result2) {

        while($row=mysqli_fetch_assoc($result2)) {

            // get the item
            $item_details = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $quantity_returned = $row['quantity_returned'];
            $item_selling_price = $row['item_selling_price'];
            $date_returned = $row['date_returned'];
            $returning_reason = $row['returning_reason'];
            $customer_name = encrypt_data('Cash customer');

            if($row['sale_type']==='differed') {
                // get the customer
                $customer = get_customer_record($conn1, $row['customer_id'], $shop_id);
                $customer_name = $customer['name'];
            }

            // get the seller
            $seller_name = get_shop_username($conn1, $row['sold_by']);

            $returned_stock[$cr]['item'] = $item_details;
            $returned_stock[$cr]['item_id'] = encrypt_data($row['item_id']);
            $returned_stock[$cr]['quantity_returned'] = encrypt_data($quantity_returned);
            $returned_stock[$cr]['item_selling_price'] = encrypt_data($item_selling_price);
            $returned_stock[$cr]['returning_reason'] = encrypt_data($returning_reason);
            $returned_stock[$cr]['customer_name'] = $customer_name;
            $returned_stock[$cr]['date_returned'] = encrypt_data($date_returned);
            $returned_stock[$cr]['seller_name'] = $seller_name;

            $cr++;

        }


        return $returned_stock;



    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get reversed payments2!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}

function get_differed_transaction_code_items($conn1, $code, $shop_id, $user_id, $date) {
    $sql = "SELECT * FROM pending_payments
            WHERE transaction_code='{$code}' AND shop_id='{$shop_id}' AND sold_by='{$user_id}'
            AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $records=[];
        $cr=0;

        while ($row = mysqli_fetch_assoc($result)) {

            $records[$cr]['id'] = encrypt_data($row['id']);
            $records[$cr]['item_id'] = encrypt_data($row['item_id']);
            $records[$cr]['transaction_id'] = encrypt_data($row['transaction_id']);
            $records[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
            $records[$cr]['quantity'] = encrypt_data($row['item_quantity']);
            $records[$cr]['buying_price'] = encrypt_data($row['buying_price']);
            $records[$cr]['selling_price'] = encrypt_data($row['item_selling_price']);
            $records[$cr]['item'] = get_shop_item_details($conn1, $shop_id, $row['item_id']);
            $records[$cr]['date_sold'] = encrypt_data($date);

            $cr++;

        }

        return $records;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transaction sales records!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_transfer_transaction_code_items($conn1, $code, $shop_id, $user_id, $date) {
    $sql = "SELECT * FROM transfer_goods
            WHERE transaction_code='{$code}' AND shop_id='{$shop_id}' AND sold_by='{$user_id}'
            AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $records=[];
        $cr=0;

        while ($row = mysqli_fetch_assoc($result)) {

            $records[$cr]['id'] = encrypt_data($row['id']);
            $records[$cr]['item_id'] = encrypt_data($row['item_id']);
            $records[$cr]['transaction_id'] = encrypt_data($row['transaction_id']);
            $records[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
            $records[$cr]['quantity'] = encrypt_data($row['quantity']);
            $records[$cr]['buying_price'] = encrypt_data($row['buying_price']);
            $records[$cr]['selling_price'] = encrypt_data($row['buying_price']);
            $records[$cr]['item'] = get_shop_item_details($conn1, $shop_id, $row['item_id']);
            $records[$cr]['date_sold'] = encrypt_data($date);

            $cr++;

        }

        return $records;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transfer transaction sales records!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_shop_day_expenses($conn1, $shop_id, $date) {
    $sql = "SELECT * FROM expenses WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_incurred, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $cr=0;
        $expenses = [];
        while($row = mysqli_fetch_assoc($result)) {

            $expenses[$cr]['user'] = get_shop_username($conn1, $row['employee']);
            $expenses[$cr]['name'] = encrypt_data($row['name']);
            $expenses[$cr]['amount'] = encrypt_data($row['amount']);

            $cr++;
        }

        return $expenses;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get expenses!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_shop_day_user_expenses($conn1, $shop_id, $date, $added_by) {
    $sql = "SELECT * FROM expenses WHERE shop_id='{$shop_id}' AND employee='{$added_by}' AND DATE_FORMAT(date_incurred, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $cr=0;
        $expenses = [];
        while($row = mysqli_fetch_assoc($result)) {

            $expenses[$cr]['user'] = get_shop_username($conn1, $row['employee']);
            $expenses[$cr]['name'] = encrypt_data($row['name']);
            $expenses[$cr]['amount'] = encrypt_data($row['amount']);

            $cr++;
        }

        return $expenses;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get expenses!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_shop_admin_expenses($conn1, $shop_id, $date) {
    $sql = "SELECT * FROM expenses WHERE shop_id='{$shop_id}'
    AND date_incurred >= DATE_SUB('{$date}', INTERVAL 31 DAY) ORDER BY id DESC";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $cr=0;
        $expenses = [];
        while($row = mysqli_fetch_assoc($result)) {

            $expenses[$cr]['user'] = get_shop_username($conn1, $row['employee']);
            $expenses[$cr]['name'] = encrypt_data($row['name']);
            $expenses[$cr]['amount'] = encrypt_data($row['amount']);

            $cr++;
        }

        return $expenses;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get expenses!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_customer_total_amounts($conn1, $customer_id, $shop_id) {
    $sql = "SELECT * FROM customer_pending_payments WHERE customer_id='{$customer_id}' AND shop_id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $debtor_total_amounts = null;

        if(mysqli_num_rows($result) > 0) {
        
            while($row = mysqli_fetch_assoc($result)) {
                $debtor_total_amounts['total_amount'] = encrypt_data($row['total_amount']);
                $debtor_total_amounts['amount_paid'] = encrypt_data($row['amount_paid']);
                $debtor_total_amounts['balance_limit'] = encrypt_data($row['balance_limit']);
            }

        } else {
            $debtor_total_amounts['total_amount'] = encrypt_data(0);
            $debtor_total_amounts['amount_paid'] = encrypt_data(0);
            $debtor_total_amounts['balance_limit'] = encrypt_data(0);
        }

        return $debtor_total_amounts;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtors records!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_customer_transfer_total_amounts($conn1, $customer_id, $shop_id) {
    $sql = "SELECT * FROM transfer_payments WHERE customer_id='{$customer_id}' AND shop_id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $debtor_total_amounts = null;
        while($row = mysqli_fetch_assoc($result)) {
            $debtor_total_amounts['total_amount'] = encrypt_data($row['total_amount']);
            $debtor_total_amounts['amount_paid'] = encrypt_data($row['amount_paid']);
        }

        return $debtor_total_amounts;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtors records!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_all_shop_debtors($conn1, $shop_id) {
    $sql = "SELECT * FROM customers WHERE shop_id='{$shop_id}' AND type='differed' AND deleted=0";

    $sql22 = "SELECT customers.id, customers.name, customers.mobile_number, customer_pending_payments.total_amount, customer_pending_payments.amount_paid, customer_pending_payments.balance_limit
                FROM customers LEFT JOIN customer_pending_payments
                ON (customers.id=customer_pending_payments.customer_id AND customers.shop_id='{$shop_id}' AND customer_pending_payments.shop_id='{$shop_id}')
                WHERE customers.shop_id='{$shop_id}' AND customers.type='differed' AND customers.deleted=0";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $customers = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $debtor['id'] = encrypt_data($row['id']);
            $debtor['name'] = encrypt_data($row['name']);
            $debtor['mobile_number'] = encrypt_data($row['mobile_number']);


            $debtor_total_amounts = get_customer_total_amounts($conn1, $row['id'], $shop_id);
            $customers[$cr]['debtor'] = $debtor;
            $customers[$cr]['debtor_total_amounts'] = $debtor_total_amounts;

            $cr++;
        }

        return $customers;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtors!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}



function get_all_shop_transfer_customers($conn1, $shop_id) {
    $sql = "SELECT * FROM customers WHERE shop_id='{$shop_id}' AND type='shop' AND deleted=0";

    $sql22 = "SELECT customers.id, customers.name, customers.mobile_number, transfer_payments.total_amount, transfer_payments.amount_paid,
                FROM customers LEFT JOIN transfer_payments
                ON (customers.id=transfer_payments.customer_id AND customers.shop_id='{$shop_id}' AND transfer_payments.shop_id='{$shop_id}')
                WHERE customers.shop_id='{$shop_id}' AND customers.type='differed' AND customers.deleted=0";
    
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $customers = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $debtor['id'] = encrypt_data($row['id']);
            $debtor['name'] = encrypt_data($row['name']);
            $debtor['mobile_number'] = encrypt_data($row['mobile_number']);


            $debtor_total_amounts = get_customer_transfer_total_amounts($conn1, $row['id'], $shop_id);
            $customers[$cr]['debtor'] = $debtor;
            $customers[$cr]['debtor_total_amounts'] = $debtor_total_amounts;

            $cr++;
        }

        return $customers;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtors!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_debtor_total_amount($conn1, $debtor_id, $shop_id) {
    $sql="SELECT * FROM customer_pending_payments WHERE shop_id='{$shop_id}' AND customer_id='{$debtor_id}'";
    $result = mysqli_query($conn1, $sql);

    $debtor = null;

    if ($result) {

        $record = mysqli_fetch_assoc($result);

        $debtor['total_amount'] = encrypt_data($record['total_amount']);
        $debtor['amount_paid'] = encrypt_data($record['amount_paid']);
        $debtor['balance_limit'] = encrypt_data($record['balance_limit']);

        return $debtor;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor payments!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_debtor_payment_amounts($conn1, $debtor_id, $shop_id, $payment_record_id) {
    $sql = "SELECT * FROM differed_sales_payments
            WHERE customer_id='{$debtor_id}' AND id='{$payment_record_id}' AND shop_id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $payment = null;

        $record = mysqli_fetch_assoc($result);

        $payment['id'] = encrypt_data($record['id']);
        $payment['cash'] = encrypt_data($record['cash']);
        $payment['mpesa'] = encrypt_data($record['mpesa']);
        $payment['bank'] = encrypt_data($record['bank']);

        return $payment;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor payment amount record!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_all_debtor_payment_records($conn1, $debtor_id, $shop_id) {
    $sql = "SELECT * FROM differed_payments WHERE shop_id='{$shop_id}' AND customer_id='{$debtor_id}'
            ORDER BY id DESC";

    // $sql2 = "SELECT differed_payments.id, differed_payments.balance_before, differed_payments.amount_paid, differed_payments.date_paid, differed_payments.paid_to, differed_sales_payments.id AS sid, differed_sales_payments.cash, differed_sales_payments.mpesa, differed_sales_payments.bank
    //         FROM differed_payments LEFT JOIN differed_sales_payments ON differed_payments.customer_id = differed_sales_payments.customer_id AND differed_payments.shop_id=differed_sales_payments.shop_id AND differed_payments.differed_sales_payments_id = differed_sales_payments.id
    //         WHERE differed_payments.shop_id='{$shop_id}' AND differed_payments.customer_id='{$debtor_id}' AND differed_sales_payments.shop_id='{$shop_id}' AND differed_payments.customer_id='{$debtor_id}'
    //         ORDER BY differed_payments.id DESC";


    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $payment_records = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $payment_records[$cr]['id'] = encrypt_data($row['id']);
            $payment_records[$cr]['balance_before'] = encrypt_data($row['balance_before']);
            $payment_records[$cr]['amount_paid'] = encrypt_data($row['amount_paid']);
            $payment_records[$cr]['date_paid'] = encrypt_data($row['date_paid']);
            $payment_records[$cr]['paid_to'] = get_shop_user_record($conn1, $row['paid_to']);

            // get the payment records
            $did = $row['differed_sales_payments_id'];

            $sql2 = "SELECT * FROM differed_sales_payments WHERE id='{$did}' AND shop_id='{$shop_id}'";
            $result2 = mysqli_query($conn1, $sql2);

            if ($result2) {

                while($row2 = mysqli_fetch_assoc($result2)) {

                    $payment['id'] = encrypt_data($row2['id']);
                    $payment['cash'] = encrypt_data($row2['cash']);
                    $payment['mpesa'] = encrypt_data($row2['mpesa']);
                    $payment['bank'] = encrypt_data($row2['bank']);

                    $payment_records[$cr]['payment'] = $payment;

                }

            } else {
                $data_insert=array(
                "status" => "error",
                "message" => "Could not get debtor payment records2!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }


            $cr++;
        }

        return $payment_records;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor payment records!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}




function get_date_debtor_balance_before($conn1, $debtor_id, $shop_id, $date) {
    $sql = "SELECT id, balance_before FROM differed_payments
            WHERE shop_id='{$shop_id}' AND customer_id='{$debtor_id}'
            AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') ORDER BY id ASC LIMIT 1";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        if(mysqli_num_rows($result) > 0) {

            $record = mysqli_fetch_assoc($result);

            return $record['balance_before'];

        } else {
            return 0;
        }

    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get debtor balance before!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_debtor_date_payment_records($conn1, $debtor_id, $shop_id, $date) {
    $sql = "SELECT * FROM differed_sales_payments WHERE customer_id='{$debtor_id}' AND shop_id='{$shop_id}'
            AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') ORDER BY id DESC";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $payments = [];
        $cr=0;

        $date_total = 0;

        while($row = mysqli_fetch_assoc($result)) {
            // get the paid to
            $paid_to = get_shop_username($conn1, $row['paid_to']);

            $payments[$cr]['id'] = encrypt_data($row['id']);
            $payments[$cr]['cash'] = $row['cash'];
            $payments[$cr]['mpesa'] = $row['mpesa'];
            $payments[$cr]['bank'] = $row['bank'];
            $payments[$cr]['paid_to'] = encrypt_data($row['paid_to']);
            $payments[$cr]['paid_to_name'] = decrypt_data($paid_to);

            $date_total += $row['cash'] + $row['mpesa'] + $row['bank'];

            $cr++;
        }

        return $payments;

    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get debtor date payment records!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_all_debtor_payment_records_new($conn1, $debtor_id, $shop_id) {

    $sql = "SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_paid,
                    IFNULL(SUM(amount_paid), 0) AS amount_paid FROM differed_payments
            WHERE shop_id='{$shop_id}' AND customer_id='{$debtor_id}'
            GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d') ORDER BY date_paid DESC";


    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $payment_records = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            $payment_records[$cr]['balance_before'] = get_date_debtor_balance_before($conn1, $debtor_id, $shop_id, $row['date_paid']);
            $payment_records[$cr]['amount_paid'] = $row['amount_paid'];
            $payment_records[$cr]['date_paid'] = $row['date_paid'];

            // get the payment records
            $payments = get_debtor_date_payment_records($conn1, $debtor_id, $shop_id, $row['date_paid']);

            $payment_records[$cr]['payment_records'] = $payments;

            $cr++;

        }

        return $payment_records;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor payment records!",
        "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}




function get_debtor_sales($conn1, $debtor_id, $shop_id) {
    $sql = "SELECT SUM(`item_quantity`*`item_selling_price`) AS total, DATE_FORMAT(date_picked, '%Y-%m-%d') AS date_picked FROM `pending_payments`
            WHERE customer_id='{$debtor_id}' AND shop_id='{$shop_id}'
            GROUP BY DATE_FORMAT(date_picked, '%Y-%m-%d') ORDER BY DATE_FORMAT(date_picked, '%Y-%m-%d') DESC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $sales=[];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            $sales[$cr]['date_of_sale'] = $row['date_picked'];
            $sales[$cr]['date_total'] = $row['total'];

            // get the records for this date
            

            $cr++;

        }

        return $sales;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Could not get debtor sales records!",
            "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    }
}




function get_all_transfer_payment_records($conn1, $debtor_id, $shop_id) {
    $sql = "SELECT * FROM transfer_payment_records WHERE shop_id='{$shop_id}' AND customer_id='{$debtor_id}'
            ORDER BY id DESC";

    // $sql2 = "SELECT differed_payments.id, differed_payments.balance_before, differed_payments.amount_paid, differed_payments.date_paid, differed_payments.paid_to, differed_sales_payments.id AS sid, differed_sales_payments.cash, differed_sales_payments.mpesa, differed_sales_payments.bank
    //         FROM differed_payments LEFT JOIN differed_sales_payments ON differed_payments.customer_id = differed_sales_payments.customer_id AND differed_payments.shop_id=differed_sales_payments.shop_id AND differed_payments.differed_sales_payments_id = differed_sales_payments.id
    //         WHERE differed_payments.shop_id='{$shop_id}' AND differed_payments.customer_id='{$debtor_id}' AND differed_sales_payments.shop_id='{$shop_id}' AND differed_payments.customer_id='{$debtor_id}'
    //         ORDER BY differed_payments.id DESC";


    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $payment_records = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $payment_records[$cr]['id'] = encrypt_data($row['id']);
            $payment_records[$cr]['balance_before'] = encrypt_data($row['balance_before']);
            $payment_records[$cr]['amount_paid'] = encrypt_data($row['amount_paid']);
            $payment_records[$cr]['payment_method'] = encrypt_data($row['payment_method']);
            $payment_records[$cr]['date_paid'] = encrypt_data($row['date_paid']);
            $payment_records[$cr]['paid_to'] = get_shop_user_record($conn1, $row['paid_to']);

            $cr++;
        }

        return $payment_records;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transfer payment records!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}


function get_all_transfer_payment_records_new($conn1, $debtor_id, $shop_id) {
    $sql = "SELECT * FROM transfer_payment_records WHERE shop_id='{$shop_id}' AND customer_id='{$debtor_id}'
            ORDER BY id DESC";

    // $sql2 = "SELECT differed_payments.id, differed_payments.balance_before, differed_payments.amount_paid, differed_payments.date_paid, differed_payments.paid_to, differed_sales_payments.id AS sid, differed_sales_payments.cash, differed_sales_payments.mpesa, differed_sales_payments.bank
    //         FROM differed_payments LEFT JOIN differed_sales_payments ON differed_payments.customer_id = differed_sales_payments.customer_id AND differed_payments.shop_id=differed_sales_payments.shop_id AND differed_payments.differed_sales_payments_id = differed_sales_payments.id
    //         WHERE differed_payments.shop_id='{$shop_id}' AND differed_payments.customer_id='{$debtor_id}' AND differed_sales_payments.shop_id='{$shop_id}' AND differed_payments.customer_id='{$debtor_id}'
    //         ORDER BY differed_payments.id DESC";


    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $payment_records = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $payment_records[$cr]['id'] = encrypt_data($row['id']);
            $payment_records[$cr]['balance_before'] = $row['balance_before'];
            $payment_records[$cr]['amount_paid'] = $row['amount_paid'];
            $payment_records[$cr]['payment_method'] = $row['payment_method'];
            $payment_records[$cr]['date_paid'] = $row['date_paid'];
            $payment_records[$cr]['paid_to'] = get_shop_user_record($conn1, $row['paid_to']);

            $cr++;
        }

        return $payment_records;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transfer payment records!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}

function get_debtor_transaction_code_items($conn1, $code, $shop_id) {
    $sql = "SELECT * FROM pending_payments
            WHERE transaction_code='{$code}' AND shop_id='{$shop_id}'
            ORDER BY id DESC";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $records = [];
        $cr = 0;

        $total_amount = 0;
        $amount_credited = 0;

        $transaction_detail;

        while($row = mysqli_fetch_assoc($result)) {

            $records[$cr]['item'] = get_shop_item_details($conn1, $shop_id, $row['item_id']);
            $records[$cr]['quantity'] = encrypt_data($row['item_quantity']);
            $records[$cr]['quantity_returned'] = encrypt_data($row['quantity_returned']);
            $records[$cr]['buying_price'] = encrypt_data($row['buying_price']);
            $records[$cr]['selling_price'] = encrypt_data($row['item_selling_price']);

            $total_amount += $row['item_quantity'] * $row['item_selling_price'];
            $amount_credited += $row['quantity_returned'] * $row['item_selling_price'];

            $cr++;

        }

        $transaction_detail['records'] = $records;
        $transaction_detail['total'] = encrypt_data($total_amount);
        $transaction_detail['amount_credited'] = encrypt_data($amount_credited);


        return $transaction_detail;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor transaction items!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_transfer_customer_transaction_code_items($conn1, $code, $shop_id) {
    $sql = "SELECT * FROM transfer_goods
            WHERE transaction_code='{$code}' AND shop_id='{$shop_id}'
            ORDER BY id DESC";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $records = [];
        $cr = 0;

        $total_amount = 0;
        $amount_credited = 0;

        $transaction_detail;

        while($row = mysqli_fetch_assoc($result)) {

            $records[$cr]['item'] = get_shop_item_details($conn1, $shop_id, $row['item_id']);
            $records[$cr]['quantity'] = encrypt_data($row['quantity']);
            $records[$cr]['buying_price'] = encrypt_data($row['buying_price']);

            $total_amount += $row['quantity'] * $row['buying_price'];

            $cr++;

        }

        $transaction_detail['records'] = $records;
        $transaction_detail['total'] = encrypt_data($total_amount);


        return $transaction_detail;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get customer transaction items!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}



function get_debtor_date_transaction_codes($conn1, $debtor_id, $shop_id, $date) {

    $sql2="SELECT DISTINCT transaction_id FROM pending_payments WHERE shop_id='{$shop_id}'
        AND customer_id='{$debtor_id}'
        AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
        ORDER BY transaction_id DESC";


    $result2 = mysqli_query($conn1, $sql2);

    if($result2) {

        $transaction_records = [];
        $cr=0;

        $date_total = 0;
        $date_total_credited = 0;

        $date_records = null;

        while($row=mysqli_fetch_assoc($result2)) {

            $sql3="SELECT id,transaction_code, sold_by FROM pending_payments WHERE shop_id='{$shop_id}'
                    AND customer_id='{$debtor_id}'
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                    AND transaction_id = '{$row['transaction_id']}'
                    ORDER BY id DESC";

            $result3 = mysqli_query($conn1, $sql3);

            if($result3) {

                $row=mysqli_fetch_assoc($result3);

                $items = get_debtor_transaction_code_items($conn1, $row['transaction_code'], $shop_id);

                $transaction_records[$cr]['id'] = encrypt_data($row['id']);
                $transaction_records[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transaction_records[$cr]['transaction_total'] = $items['total'];
                $transaction_records[$cr]['amount_credited'] = $items['amount_credited'];
                $transaction_records[$cr]['sold_by'] = get_shop_username($conn1, $row['sold_by']);
                $transaction_records[$cr]['transaction_items'] = $items['records'];

                $date_total += decrypt_data($items['total']);
                $date_total_credited += decrypt_data($items['amount_credited']);

                $cr++;

            } else {
                $data_insert=array(
                    "status" => "error",
                    "error" => mysqli_error($conn1),
                    "message" => "Could not get debtor transaction records3!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        }

        $date_records['transaction_codes'] = $transaction_records;
        $date_records['date_total'] = encrypt_data($date_total);
        $date_records['date_total_credited'] = encrypt_data($date_total_credited);

        return $date_records;

    } else {
        $data_insert=array(
        "status" => "error",
        "error" => mysqli_error($conn1),
        "message" => "Could not get debtor transaction records2!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}



function get_transfer_customer_date_transaction_codes($conn1, $debtor_id, $shop_id, $date) {

    $sql2="SELECT DISTINCT transaction_id FROM transfer_goods WHERE shop_id='{$shop_id}'
        AND customer_id='{$debtor_id}'
        AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
        ORDER BY transaction_id DESC";

    $result2 = mysqli_query($conn1, $sql2);

    if($result2) {

        $transaction_records = [];
        $cr=0;

        $date_total = 0;
        $date_total_credited = 0;

        $date_records = null;

        while($row=mysqli_fetch_assoc($result2)) {

            $sql3="SELECT id, transaction_code, sold_by FROM transfer_goods WHERE shop_id='{$shop_id}'
                    AND customer_id='{$debtor_id}'
                    AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                    AND transaction_id = '{$row['transaction_id']}'
                    ORDER BY id DESC";

            $result3 = mysqli_query($conn1, $sql3);

            if($result3) {

                $row=mysqli_fetch_assoc($result3);

                $items = get_transfer_customer_transaction_code_items($conn1, $row['transaction_code'], $shop_id);
 
                $transaction_records[$cr]['id'] = encrypt_data($row['id']);
                $transaction_records[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transaction_records[$cr]['transaction_total'] = $items['total'];
                $transaction_records[$cr]['sold_by'] = get_shop_username($conn1, $row['sold_by']);
                $transaction_records[$cr]['transaction_items'] = $items['records'];

                $date_total += decrypt_data($items['total']);

                $cr++;

            } else {
                $data_insert=array(
                    "status" => "error",
                    "error" => mysqli_error($conn1),
                    "message" => "Could not get customer transaction records3!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        }

        $date_records['transaction_codes'] = $transaction_records;
        $date_records['date_total'] = encrypt_data($date_total);

        return $date_records;

    } else {
        $data_insert=array(
        "status" => "error",
        "error" => mysqli_error($conn1),
        "message" => "Could not get customer transaction records2!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_debtor_date_total_picked ($conn1, $debtor_id, $shop_id, $date) {
    $sql = "SELECT SUM(item_quantity * item_selling_price) AS date_total, SUM(quantity_returned * item_selling_price) AS amount_credited
            FROM pending_payments WHERE shop_id='{$shop_id}'
            AND customer_id='{$debtor_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";

    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $record = mysqli_fetch_assoc($result);

        $picked_items['date_total'] = encrypt_data($record['date_total']);
        $picked_items['date_total_credited'] = encrypt_data($record['amount_credited']);

        return $picked_items;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor totals!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}



function get_transfer_customer_date_total_picked ($conn1, $debtor_id, $shop_id, $date) {
    $sql = "SELECT SUM(quantity * buying_price) AS date_total
            FROM transfer_goods WHERE shop_id='{$shop_id}'
            AND customer_id='{$debtor_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";

    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $record = mysqli_fetch_assoc($result);

        $picked_items['date_total'] = encrypt_data($record['date_total']);

        return $picked_items;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor totals!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}

function get_all_debtor_picked_items($conn1, $debtor_id, $shop_id) {
    $sql = "SELECT DISTINCT(DATE_FORMAT(date_picked, '%Y-%m-%d')) AS date_picked FROM pending_payments
            WHERE shop_id='{$shop_id}'
            AND customer_id='{$debtor_id}'
            ORDER BY date_picked DESC";


    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $picked_items = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            // $sql2 = "SELECT date_picked, sold_by FROM pending_payments WHERE shop_id='{$shop_id}'
            //     AND customer_id='{$debtor_id}'
            //     AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$row['date_picked']}', '%Y-%m-%d')
            //     ORDER BY id DESC";

            // $result2 = mysqli_query($conn1, $sql2);

            // if ($result2) {

            // $row = mysqli_fetch_assoc($result2);

                // get transaction codes for this date
                $date = $row['date_picked'];

                $date_totals = get_debtor_date_total_picked ($conn1, $debtor_id, $shop_id, $date);

                // $transaction_codes = get_debtor_date_transaction_codes($conn1, $debtor_id, $shop_id, $date);

                $picked_items[$cr]['date_picked'] = encrypt_data($row['date_picked']);
                // $picked_items[$cr]['sold_by'] = get_shop_username($conn1, $row['sold_by']);
                // $picked_items[$cr]['transaction_codes'] = $transaction_codes['transaction_codes'];
                $picked_items[$cr]['date_total'] = $date_totals['date_total'];
                $picked_items[$cr]['date_total_credited'] = $date_totals['date_total_credited'];

                $cr++;

            // } else {
            //     $data_insert=array(
            //         "status" => "error",
            //         "message" => "Could not get debtor transactions2!"
            //         );

            //         echo json_encode($data_insert);
            //         mysqli_close($conn1);

            //         exit();
            // }
        }

        return $picked_items;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor transactions!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}



function get_all_transfer_picked_items($conn1, $debtor_id, $shop_id) {
    $sql = "SELECT DISTINCT(DATE_FORMAT(date_transfered, '%Y-%m-%d')) AS date_transfered FROM transfer_goods
            WHERE shop_id='{$shop_id}'
            AND customer_id='{$debtor_id}'
            ORDER BY date_transfered DESC";


    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $picked_items = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            // $sql2 = "SELECT date_picked, sold_by FROM pending_payments WHERE shop_id='{$shop_id}'
            //     AND customer_id='{$debtor_id}'
            //     AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$row['date_picked']}', '%Y-%m-%d')
            //     ORDER BY id DESC";

            // $result2 = mysqli_query($conn1, $sql2);

            // if ($result2) {

            // $row = mysqli_fetch_assoc($result2);

                // get transaction codes for this date
                $date = $row['date_transfered'];

                $date_totals = get_transfer_customer_date_total_picked ($conn1, $debtor_id, $shop_id, $date);

                // $transaction_codes = get_debtor_date_transaction_codes($conn1, $debtor_id, $shop_id, $date);

                $picked_items[$cr]['date_transfered'] = encrypt_data($row['date_transfered']);
                // $picked_items[$cr]['sold_by'] = get_shop_username($conn1, $row['sold_by']);
                // $picked_items[$cr]['transaction_codes'] = $transaction_codes['transaction_codes'];
                $picked_items[$cr]['date_total'] = $date_totals['date_total'];

                $cr++;

            // } else {
            //     $data_insert=array(
            //         "status" => "error",
            //         "message" => "Could not get debtor transactions2!"
            //         );

            //         echo json_encode($data_insert);
            //         mysqli_close($conn1);

            //         exit();
            // }
        }

        return $picked_items;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor transactions!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_sold_dates($conn1, $shop_id) {

    $sql = "SELECT DISTINCT DATE_FORMAT(date_picked, '%Y-%m-%d') AS date_picked FROM pending_payments WHERE shop_id='{$shop_id}'
            UNION SELECT DISTINCT DATE_FORMAT(date_sold, '%Y-%m-%d') FROM sold_stock WHERE shop_id='{$shop_id}'
            UNION SELECT DISTINCT DATE_FORMAT(date_transfered, '%Y-%m-%d') FROM transfer_goods WHERE shop_id='{$shop_id}'
            UNION SELECT DISTINCT DATE_FORMAT(date_paid, '%Y-%m-%d') FROM differed_payments WHERE shop_id='{$shop_id}'
            ORDER BY date_picked DESC";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $dates = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            $dates[$cr]['date_of_sale'] = encrypt_data($row['date_picked']);

            $cr++;

        }

        return $dates;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get sold dates!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_period_sold_dates($conn1, $shop_id, $from, $to) {

    $sql = "SELECT DISTINCT DATE_FORMAT(date_picked, '%Y-%m-%d') AS date_picked FROM pending_payments
            WHERE shop_id='{$shop_id}'
            AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
            AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            UNION SELECT DISTINCT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_picked FROM sold_stock WHERE shop_id='{$shop_id}'
            AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
            AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            UNION SELECT DISTINCT DATE_FORMAT(date_transfered, '%Y-%m-%d') AS date_picked FROM transfer_goods WHERE shop_id='{$shop_id}'
            AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
            AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            UNION SELECT DISTINCT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_picked FROM differed_payments WHERE shop_id='{$shop_id}'
            AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
            AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            UNION SELECT DISTINCT DATE_FORMAT(date_incurred, '%Y-%m-%d') AS date_picked FROM expenses WHERE shop_id='{$shop_id}'
            AND DATE_FORMAT(date_incurred, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
            AND DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            ORDER BY date_picked ASC";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $dates = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            $dates[$cr]['date_of_sale'] = encrypt_data($row['date_picked']);

            $cr++;

        }

        return $dates;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get sold dates!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_period_cash_dates($conn1, $shop_id, $from, $to) {
    $sql = "SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold, SUM(cash) AS cash, SUM(amount_reversed) AS reversed, SUM(discount) AS discounts FROM sales_payments WHERE shop_id='{$shop_id}' AND cash>0 AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d') ORDER BY date_sold ASC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $sales = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $sales[$cr]['date_sold'] = $row['date_sold'];
            $sales[$cr]['amount'] = $row['cash'];
            $sales[$cr]['discounts'] = $row['discounts'];
            $sales[$cr]['reversed'] = $row['reversed'];
            $cr++;
        }

        return $sales;

    } else {

        $data_insert=array(
        "status" => "error",
        "message" => "Could not get sold dates!",
        "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_period_mpesa_dates($conn1, $shop_id, $from, $to) {
    $sql = "SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold, SUM(mpesa) AS mpesa, SUM(amount_reversed) AS reversed, SUM(discount) AS discounts FROM sales_payments WHERE shop_id='{$shop_id}' AND mpesa>0 AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d') ORDER BY date_sold ASC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $sales = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $sales[$cr]['date_sold'] = $row['date_sold'];
            $sales[$cr]['amount'] = $row['mpesa'];
            $sales[$cr]['reversed'] = $row['reversed'];
            $cr++;
        }

        return $sales;

    } else {

        $data_insert=array(
        "status" => "error",
        "message" => "Could not get sold dates!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_period_bank_dates($conn1, $shop_id, $from, $to) {
    $sql = "SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold, SUM(bank) AS bank, SUM(amount_reversed) AS reversed, SUM(discount) AS discounts FROM sales_payments WHERE shop_id='{$shop_id}' AND bank>0 AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d') ORDER BY date_sold ASC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $sales = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $sales[$cr]['date_sold'] = $row['date_sold'];
            $sales[$cr]['amount'] = $row['bank'];
            $sales[$cr]['reversed'] = $row['reversed'];
            $cr++;
        }

        return $sales;

    } else {

        $data_insert=array(
        "status" => "error",
        "message" => "Could not get sold dates!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_period_expenses($conn1, $shop_id, $from, $to) {
    $sql = "SELECT DATE_FORMAT(date_incurred, '%Y-%m-%d') AS date_incurred, SUM(amount) AS amount FROM expenses WHERE shop_id='{$shop_id}' AND
            DATE_FORMAT(date_incurred, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
            DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(date_incurred, '%Y-%m-%d') ORDER BY date_incurred ASC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $sales = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $sales[$cr]['date_sold'] = $row['date_incurred'];
            $sales[$cr]['amount'] = $row['amount'];
            $cr++;
        }

        return $sales;

    } else {

        $data_insert=array(
        "status" => "error",
        "message" => "Could not get sold dates!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}



function get_user_period_cash_dates($conn1, $shop_id, $from, $to, $user_id) {

    $sql = "SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold, SUM(cash) AS cash, SUM(amount_reversed) AS reversed, SUM(discount) AS discounts FROM sales_payments WHERE shop_id='{$shop_id}' AND cash>0 AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND
            transaction_code IN (SELECT transaction_code FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}')
            GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d') ORDER BY date_sold ASC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $sales = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $sales[$cr]['date_sold'] = $row['date_sold'];
            $sales[$cr]['amount'] = $row['cash'];
            $sales[$cr]['discounts'] = $row['discounts'];
            $sales[$cr]['reversed'] = $row['reversed'];
            $cr++;
        }

        return $sales;

    } else {

        $data_insert=array(
        "status" => "error",
        "message" => "Could not get sold dates!",
        "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_user_period_mpesa_dates($conn1, $shop_id, $from, $to, $user_id) {
    $sql = "SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold, SUM(mpesa) AS mpesa, SUM(amount_reversed) AS reversed, SUM(discount) AS discounts FROM sales_payments WHERE shop_id='{$shop_id}' AND mpesa>0 AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND
            transaction_code IN (SELECT transaction_code FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$sold_by}')
            GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d') ORDER BY date_sold ASC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $sales = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $sales[$cr]['date_sold'] = $row['date_sold'];
            $sales[$cr]['amount'] = $row['mpesa'];
            $sales[$cr]['reversed'] = $row['reversed'];
            $cr++;
        }

        return $sales;

    } else {

        $data_insert=array(
        "status" => "error",
        "message" => "Could not get sold dates!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_user_period_bank_dates($conn1, $shop_id, $from, $to) {
    $sql = "SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold, SUM(bank) AS bank, SUM(amount_reversed) AS reversed, SUM(discount) AS discounts FROM sales_payments WHERE shop_id='{$shop_id}' AND bank>0 AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND
            transaction_code IN (SELECT transaction_code FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$sold_by}')
            GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d') ORDER BY date_sold ASC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $sales = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $sales[$cr]['date_sold'] = $row['date_sold'];
            $sales[$cr]['amount'] = $row['bank'];
            $sales[$cr]['reversed'] = $row['reversed'];
            $cr++;
        }

        return $sales;

    } else {

        $data_insert=array(
        "status" => "error",
        "message" => "Could not get sold dates!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}







function get_period_sales_summary($conn1, $shop_id, $from, $to) {
    $sql = "SELECT a.gross_cash_sales, b.gross_debtor_sales, b.reversed_d, c.gross_cash_profit, d.gross_debtor_profit, e.paid_debtors, f.transfers, g.sales_purchases, h.expenses, i.cash, i.mpesa, i.bank, i.discount, i.reversed,
                    j.recovered1, k.recovered2

            FROM 
            
            (
                (SELECT IFNULL(SUM(quantity * selling_price), 0) AS gross_cash_sales
                    FROM sold_stock WHERE shop_id='{$shop_id}'
                    AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS a
                INNER JOIN
                (SELECT IFNULL(SUM(item_quantity * item_selling_price), 0) AS gross_debtor_sales,
                        IFNULL(SUM(quantity_returned * item_selling_price), 0) AS reversed_d
                FROM pending_payments WHERE shop_id='{$shop_id}'
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS b
                INNER JOIN
                (SELECT IFNULL(SUM(quantity * (selling_price - buying_price)), 0) AS gross_cash_profit
                FROM sold_stock WHERE selling_price > buying_price AND shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS c
                INNER JOIN
                (SELECT IFNULL(SUM(item_quantity * (item_selling_price - buying_price)), 0) AS gross_debtor_profit
                FROM pending_payments WHERE item_selling_price > buying_price
                        AND shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS d
                INNER JOIN
                (SELECT IFNULL(SUM(amount_paid), 0) AS paid_debtors
                    FROM differed_payments WHERE shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS e
                INNER JOIN
                (SELECT IFNULL(SUM(quantity * buying_price), 0) AS transfers
                    FROM transfer_goods WHERE shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS f
                INNER JOIN
                (SELECT IFNULL(SUM(quantity * buying_price), 0) AS sales_purchases
                    FROM sales_purchases WHERE shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS g
                INNER JOIN
                (SELECT IFNULL(SUM(amount), 0) AS expenses
                    FROM expenses WHERE shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_incurred, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS h
                INNER JOIN
                (SELECT IFNULL(SUM(cash), 0) AS cash, IFNULL(SUM(mpesa), 0) AS mpesa,
                        IFNULL(SUM(bank), 0) AS bank, IFNULL(SUM(discount), 0) AS discount,
                        IFNULL(SUM(amount_reversed), 0) AS reversed
                    FROM sales_payments WHERE shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS i
                INNER JOIN
                (SELECT IFNULL(SUM(quantity * selling_price), 0) AS recovered1
                    FROM sold_stock WHERE selling_price = buying_price AND shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS j
                INNER JOIN
                (SELECT IFNULL(SUM(item_quantity * item_selling_price), 0) AS recovered2
                    FROM pending_payments WHERE item_selling_price = buying_price
                        AND shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS k
                
            )";

    $result = mysqli_query($conn1, $sql);

    if($result) {

        $record = mysqli_fetch_assoc($result);

        return $record;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get period summary!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_date_sales_summary($conn1, $shop_id, $date) {
    $sql = "SELECT a.gross_cash_sales, b.gross_debtor_sales, c.gross_cash_profit, d.gross_debtor_profit, h.expenses
            FROM
            (
                (SELECT IFNULL(SUM(quantity * selling_price), 0) AS gross_cash_sales
                    FROM sold_stock WHERE shop_id='{$shop_id}'
                    AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS a
                INNER JOIN
                (SELECT IFNULL(SUM(item_quantity * item_selling_price), 0) AS gross_debtor_sales,
                        IFNULL(SUM(quantity_returned * item_selling_price), 0) AS reversed
                FROM pending_payments WHERE shop_id='{$shop_id}'
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS b
                INNER JOIN
                (SELECT IFNULL(SUM(quantity * (selling_price - buying_price)), 0) AS gross_cash_profit
                FROM sold_stock WHERE selling_price > buying_price AND shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS c
                INNER JOIN
                (SELECT IFNULL(SUM(item_quantity * (item_selling_price - buying_price)), 0) AS gross_debtor_profit
                FROM pending_payments WHERE item_selling_price > buying_price
                        AND shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS d
                INNER JOIN
                (SELECT IFNULL(SUM(amount), 0) AS expenses
                    FROM expenses WHERE shop_id='{$shop_id}'
                        AND DATE_FORMAT(date_incurred, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS h
            )";
    
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $record = mysqli_fetch_assoc($result);

        return $record;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get date summary!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}












function get_transaction_code_date_sales($conn1, $code, $shop_id) {
    $sql = "SELECT * FROM sold_stock
            WHERE transaction_code='{$code}' AND shop_id='{$shop_id}'";
    $result=mysqli_query($conn1, $sql);

    if ($result) {

        $records=[];
        $cr=0;

        $transaction_code_total = 0;

        while ($row = mysqli_fetch_assoc($result)) {

            $records[$cr]['id'] = encrypt_data($row['id']);
            $records[$cr]['transaction_id'] = encrypt_data($row['transaction_id']);
            $records[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
            $records[$cr]['quantity'] = encrypt_data($row['quantity']);
            $records[$cr]['buying_price'] = encrypt_data($row['buying_price']);
            $records[$cr]['selling_price'] = encrypt_data($row['selling_price']);
            $records[$cr]['item'] = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $transaction_code_total += ($row['quantity'] * $row['selling_price']);

            $cr++;

        }

        $details['records'] = $records;
        $details['transaction_code_total'] = encrypt_data($transaction_code_total);

        return $details;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transaction sales records!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_date_cash_sales($conn1, $shop_id, $date) {

    $sql = "SELECT DISTINCT transaction_id, transaction_code, sold_by FROM sold_stock WHERE shop_id='{$shop_id}'
                AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                ORDER BY transaction_id DESC";

    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $sales=[];
        $reversed_sales=array();
        $cr=0;

        while($row=mysqli_fetch_assoc($result)) {

            // $sql2 = "SELECT transaction_code, sold_by FROM sold_stock WHERE shop_id='{$shop_id}'
            //     AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            //     AND transaction_id = '{$row['transaction_id']}'
            //     ORDER BY id DESC";

            // $result2 = mysqli_query($conn1, $sql2);

            // if ($result2) {

            //     $row = mysqli_fetch_assoc($result2);

                // get the transaction items
                $receipt_items = get_transaction_code_date_sales($conn1, $row['transaction_code'], $shop_id);
                $transaction_items = $receipt_items['records'];
                $transaction_total = $receipt_items['transaction_code_total'];
                // get the payment details
                $payment_details = get_transaction_code_payment($conn1, $row['transaction_code'], $shop_id, $date);
                // get the seller
                $sold_by = get_shop_username($conn1, $row['sold_by']);

                $sales[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $sales[$cr]['transaction_code_items'] = $transaction_items;
                $sales[$cr]['transaction_code_total'] = $transaction_total;
                $sales[$cr]['transaction_code_payment'] = $payment_details;
                $sales[$cr]['transaction_code_seller'] = $sold_by;

                if (intval(decrypt_data($payment_details['amount_reversed'])) > 0) {
                    // get the reversed transactions
                    $reversed_items['code'] = encrypt_data($row['transaction_code']);
                    $reversed_items['items'] = get_date_transaction_reversed_sales($conn1, $shop_id, $row['transaction_code']);
                    array_push($reversed_sales, $reversed_items);
                }

                $cr++;

            // } else {
            //     $data_insert=array(
            //     "status" => "error",
            //     "message" => "Could not get cash sales2!"
            //     );

            //     echo json_encode($data_insert);
            //     mysqli_close($conn1);

            //     exit();
            // }

        }

        $sale['sales'] = $sales;
        $sale['reversed'] = $reversed_sales;

        return $sale;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get cash sales!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}





function get_date_differed_sales($conn1, $shop_id, $date) {

    $sql = "SELECT DISTINCT transaction_id, transaction_code, customer_id, sold_by FROM pending_payments WHERE shop_id='{$shop_id}'
            AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            ORDER BY transaction_id DESC";


    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $sales=[];
        $reversed_sales=array();
        $cr=0;

        while($row=mysqli_fetch_assoc($result)) {

            // $sql2 = "SELECT id, transaction_code, customer_id, sold_by FROM pending_payments WHERE shop_id='{$shop_id}'
            // AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            // AND transaction_id='{$row['transaction_id']}'
            // ORDER BY id DESC";

            // $result2 = mysqli_query($conn1, $sql2);

            // if($result2) {

                // get the transaction items
                $receipt_items = get_debtor_transaction_code_items($conn1, $row['transaction_code'], $shop_id);
                $transaction_code_items = $receipt_items['records'];
                $transaction_code_total = $receipt_items['total'];

                // get the customer
                $customer = get_customer_record($conn1, $row['customer_id'], $shop_id);

                // get the seller
                $sold_by = get_shop_username($conn1, $row['sold_by']);

                $sales[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $sales[$cr]['transaction_code_items'] = $transaction_code_items;
                $sales[$cr]['transaction_code_total'] = $transaction_code_total;
                $sales[$cr]['transaction_code_customer'] = $customer;
                $sales[$cr]['transaction_code_seller'] = $sold_by;

                // check if this code is in returned items
                $sql2="SELECT * FROM returned_stock WHERE shop_id='{$shop_id}' AND transaction_code='{$row['transaction_code']}'";
                $result2 = mysqli_query($conn1, $sql2);

                if ($result2) {

                    if(mysqli_num_rows($result2) > 0) {
                        // get the reversed items
                        $reversed_items['code'] = encrypt_data($row['transaction_code']);
                        $reversed_items['items'] = get_date_transaction_reversed_sales($conn1, $shop_id, $row['transaction_code']);
                        array_push($reversed_sales, $reversed_items);
                    }

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not confirm debtor reversed transaction!"
                    );

                    echo json_encode($data_insert);
                    mysqli_close($conn1);

                    exit();
                }


                $cr++;

            // } else {
            //     $data_insert=array(
            //         "status" => "error",
            //         "message" => "Could not get debtor sales2!"
            //     );

            //     echo json_encode($data_insert);
            //     mysqli_close($conn1);

            //     exit();
            // }

        }

        $sale['sales'] = $sales;
        $sale['reversed'] = $reversed_sales;

        return $sale;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor sales!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_date_transaction_reversed_sales($conn1, $shop_id, $code) {
    $sql="SELECT * FROM returned_stock WHERE shop_id='{$shop_id}' AND transaction_code='{$code}'";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $sales=[];
        $cr=0;

        while($row=mysqli_fetch_assoc($result)) {
            // get the item details
            $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $sales[$cr]['id'] = encrypt_data($row['id']);
            $sales[$cr]['transaction_code'] = encrypt_data($code);
            $sales[$cr]['quantity_returned'] = encrypt_data($row['quantity_returned']);
            $sales[$cr]['item_selling_price'] = encrypt_data($row['item_selling_price']);
            $sales[$cr]['item'] = $item;

            $cr++;
        }

        return $sales;


    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get reversed code items!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_date_paid_debtors($conn1, $shop_id, $date) {
    $sql = "SELECT * FROM differed_payments WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $payments=[];
        $cr=0;

        while ($row=mysqli_fetch_assoc($result)) {

            // get the customer
            $customer = get_customer_record($conn1, $row['customer_id'], $shop_id);

            // get the seller
            $paid_to = get_shop_username($conn1, $row['paid_to']);

            // get the payment record
            $payment_record = get_debtor_payment_amounts($conn1, $row['customer_id'], $shop_id, $row['differed_sales_payments_id']);


            $payments[$cr]['id'] = encrypt_data($row['id']);
            $payments[$cr]['balance_before'] = encrypt_data($row['balance_before']);
            $payments[$cr]['amount_paid'] = encrypt_data($row['amount_paid']);
            $payments[$cr]['debtor'] = $customer;
            $payments[$cr]['paid_to'] = $paid_to;
            $payments[$cr]['payment_record'] = $payment_record;

            $cr++;
        }

        return $payments;



    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get paid debtors!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_transfer_code_items($conn1, $code, $shop_id) {
    $sql="SELECT * FROM transfer_goods WHERE transaction_code='{$code}' AND shop_id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $code_items=[];
        $cr=0;

        $total = 0;

        $customer = '';
        $sold_by = '';

        while ($row = mysqli_fetch_assoc($result)) {

            // get the item details
            $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $code_items[$cr]['id'] = encrypt_data($row['id']);
            $code_items[$cr]['item_quantity'] = encrypt_data($row['quantity']);
            $code_items[$cr]['item_buying_price'] = encrypt_data($row['buying_price']);
            $code_items[$cr]['item'] = $item;

            // get the customer
            $customer = get_customer_record($conn1, $row['customer_id'], $shop_id);

            // get the seller
            $sold_by = get_shop_username($conn1, $row['sold_by']);

            $code_items[$cr]['customer'] = $customer;
            $code_items[$cr]['sold_by'] = $sold_by;

            $cr++;

            $total += $row['quantity'] * $row['buying_price'];

        }

        $items['items'] = $code_items;
        $items['customer'] = $customer;
        $items['sold_by'] = $sold_by;
        $items['total'] = $total;

        return $items;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transfer code items!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}

function get_date_transfers($conn1, $shop_id, $date) {

    $sql="SELECT DISTINCT(transaction_code) AS transaction_code FROM transfer_goods WHERE shop_id='{$shop_id}'
            AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $transfers=[];
        $cr=0;

        while ($row = mysqli_fetch_assoc($result)) {

            // get the transaction code items
            $transaction_code_items = get_transfer_code_items($conn1, $row['transaction_code'], $shop_id);

            $transfers[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
            $transfers[$cr]['transaction_code_total'] = encrypt_data($transaction_code_items['total']);
            $transfers[$cr]['transaction_code_items'] = $transaction_code_items['items'];
            $transfers[$cr]['customer'] = $transaction_code_items['customer'];
            $transfers[$cr]['sold_by'] = $transaction_code_items['sold_by'];

            $cr++;

        }

        return $transfers;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transfers!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}


function get_date_transfers2($conn1, $shop_id, $date) {

    $sql = "SELECT customer_id, SUM(quantity*buying_price) AS total FROM transfer_goods
                    WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                    GROUP BY customer_id";

    $result2 = mysqli_query($conn1, $sql);

    if ($result2) {

        $transfers=[];
        $tc=0;

        while($row1=mysqli_fetch_assoc($result2)) {

            $cid = $row1['customer_id'];

            $sql2="SELECT DISTINCT(transaction_code) AS transaction_code FROM transfer_goods WHERE shop_id='{$shop_id}'
                    AND customer_id='{$cid}'
                    AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
            $result = mysqli_query($conn1, $sql2);

            if ($result) {

                $customer_codes=[];
                $cr=0;

                while ($row = mysqli_fetch_assoc($result)) {

                    // get the transaction code items
                    $transaction_code_items = get_transfer_code_items($conn1, $row['transaction_code'], $shop_id);

                    $customer_codes[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                    $customer_codes[$cr]['transaction_code_total'] = encrypt_data($transaction_code_items['total']);
                    $customer_codes[$cr]['transaction_code_items'] = $transaction_code_items['items'];
                    $customer_codes[$cr]['customer'] = $transaction_code_items['customer'];
                    $customer_codes[$cr]['sold_by'] = $transaction_code_items['sold_by'];

                    $cr++;

                }

                $transfers[$tc]['customer'] = $customer_codes[0]['customer'];
                $transfers[$tc]['total_transfered'] = $row1['total'];
                $transfers[$tc]['customer_codes'] = $customer_codes;

                $tc++;

            } else {
                $data_insert=array(
                "status" => "error",
                "message" => "Could not get transfers2!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        }

        return $transfers;

    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get transfers!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}


function get_date_recovered_sales($conn1, $shop_id, $date) {

    $sql = "SELECT id, item_id, customer_id, quantity, buying_price, selling_price, sold_by
                FROM sold_stock WHERE buying_price = selling_price AND shop_id='{$shop_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            UNION (SELECT id, item_id, customer_id, item_quantity AS quantity, buying_price, item_selling_price AS selling_price, sold_by
                    FROM pending_payments WHERE buying_price = item_selling_price AND shop_id='{$shop_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d'))
            ORDER BY id DESC";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $sales = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            // get the customer
            $customer = '';
            if ($row['customer_id'] == 0) {
                $customer = encrypt_data('Cash Customer');
            } else {
                $customer_ = get_customer_record($conn1, $row['customer_id'], $shop_id);
                $customer = $customer_['name'];
            }

            // get the seller
            $seller = get_shop_user_details($conn1, $row['sold_by']);

            // get the item details
            $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $sales[$cr]['id'] = encrypt_data($row['id']);
            $sales[$cr]['item'] = $item;
            $sales[$cr]['quantity'] = encrypt_data($row['quantity']);
            $sales[$cr]['buying_price'] = encrypt_data($row['buying_price']);
            $sales[$cr]['selling_price'] = encrypt_data($row['selling_price']);
            $sales[$cr]['customer'] = $customer;
            $sales[$cr]['sold_by'] = $seller;

            $cr++;
        }

        return $sales;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get recovered!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}

function get_items_sales_report($conn1, $item_id, $shop_id) {

    $sql = "SELECT SUM(quantity) AS sold_quantity,
            SUM((selling_price - buying_price) * quantity) AS profit
            FROM sold_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND selling_price > buying_price";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $result = mysqli_fetch_assoc($result);

        $quantity_sold = $result['sold_quantity'];
        $profit = $result['profit'];

        $report['quantity'] = $quantity_sold;
        $report['profit'] = $profit;

        return $report;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get items sales report!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_items_sales_periodic_report($conn1, $item_id, $shop_id, $from, $to) {

    $sql = "SELECT SUM(quantity) AS sold_quantity,
            SUM((selling_price - buying_price) * quantity) AS profit
            FROM sold_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND selling_price > buying_price
            AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
            AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $result = mysqli_fetch_assoc($result);

        $quantity_sold = $result['sold_quantity'];
        $profit = $result['profit'];

        $report['quantity'] = $quantity_sold;
        $report['profit'] = $profit;

        return $report;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get items sales report!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_items_sales_overall_periodic_report($conn1, $shop_id, $from, $to) {

    $sql = "SELECT item_id, SUM(sold_quantity) as quantity, SUM(profit) AS profit FROM
            
            (SELECT item_id, SUM(quantity) AS sold_quantity,
                SUM((selling_price - buying_price) * quantity) AS profit
                FROM sold_stock WHERE shop_id='{$shop_id}' AND selling_price > buying_price
                AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            GROUP BY item_id
            UNION ALL
            SELECT item_id, IFNULL(SUM(item_quantity), 0) AS sold_quantity,
                IFNULL(SUM((item_selling_price - buying_price) * item_quantity), 0) AS profit
                FROM pending_payments WHERE shop_id='{$shop_id}' AND item_selling_price > buying_price
                AND DATE_FORMAT('%Y-%m-%d', date_picked) >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                AND DATE_FORMAT('%Y-%m-%d', date_picked) <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                GROUP BY item_id
            ) AS z GROUP BY item_id";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $items = [];

        $cr=0;
        while($row=mysqli_fetch_assoc($result)) {
            $items[$cr]['item'] = get_shop_item_details($conn1, $shop_id, $row['item_id']);
            $items[$cr]['quantity'] = encrypt_data($row['quantity']);
            $items[$cr]['profit'] = encrypt_data($row['profit']);
            $cr++;
        }

        return $items;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get items sales report!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_items_debtors_report($conn1, $item_id, $shop_id) {
    $sql = "SELECT SUM(item_quantity) AS sold_quantity,
            SUM((item_selling_price - buying_price) * item_quantity) AS profit
            FROM pending_payments WHERE shop_id='{$shop_id}' AND item_id='{$item_id}'  AND item_selling_price > buying_price";
    $result = mysqli_query($conn1, $sql);

    if($result) {

    $result = mysqli_fetch_assoc($result);

    $quantity_sold = $result['sold_quantity'];
    $profit = $result['profit'];

    $report['quantity'] = $quantity_sold;
    $report['profit'] = $profit;

    return $report;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Could not get items debtors sales report!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    }
}

function get_items_debtors_periodic_report($conn1, $item_id, $shop_id, $from, $to) {
    $sql = "SELECT SUM(item_quantity) AS sold_quantity,
            SUM((item_selling_price - buying_price) * item_quantity) AS profit
            FROM pending_payments WHERE shop_id='{$shop_id}' AND item_id='{$item_id}'  AND item_selling_price > buying_price
            AND DATE_FORMAT('%Y-%m-%d', date_picked) >= DATE_FORMAT('%Y-%m-%d', '{$from}')
            AND DATE_FORMAT('%Y-%m-%d', date_picked) <= DATE_FORMAT('%Y-%m-%d', '{$to}')";
    $result = mysqli_query($conn1, $sql);

    if($result) {

    $result = mysqli_fetch_assoc($result);

    $quantity_sold = $result['sold_quantity'];
    $profit = $result['profit'];

    $report['quantity'] = $quantity_sold;
    $report['profit'] = $profit;

    return $report;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Could not get items debtors sales report!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    }
}



function get_item_dates_with_sales_or_invoices($conn1, $item_id, $shop_id, $from, $to) {

    $sql = "SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock WHERE
                    item_id='{$item_id}' AND shop_id='{$shop_id}'
                    AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                UNION
            SELECT DATE_FORMAT(date_picked, '%Y-%m-%d') AS date_sold FROM pending_payments WHERE
                    item_id='{$item_id}' AND shop_id='{$shop_id}'
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                UNION
            SELECT DATE_FORMAT(date_added, '%Y-%m-%d') AS date_sold FROM added_stock WHERE
                    item_id='{$item_id}' AND shop_id='{$shop_id}'
                    AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                UNION
            SELECT DATE_FORMAT(date_transfered, '%Y-%m-%d') AS date_sold FROM transfer_goods WHERE
                    item_id='{$item_id}' AND shop_id='{$shop_id}'
                    AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            ORDER BY DATE_FORMAT(date_sold, '%Y-%m-%d') ASC";

    $result = mysqli_query($conn1, $sql);

    if($result) {

        $dates = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $dates[$cr] = $row['date_sold'];

            $cr++;
        }

        return $dates;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get item date sales!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}

function get_item_quantity_before_period_sales($conn1, $item_id, $shop_id, $from) {

    $quantity1 = 0;
    $quantity2 = 0;
    $quantity3 = 0;

    $date1 = 0;
    $date2 = 0;
    $date3 = 0;

    $date_arrays = array();

    $sql1 = "SELECT IFNULL(old_quantity, 0) AS old_quantity, date_sold FROM sold_stock WHERE item_id='{$item_id}'
            AND id IN (SELECT MIN(id) FROM sold_stock WHERE item_id='{$item_id}'
                        AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d'))";

    $sql2 = "SELECT IFNULL(old_quantity, 0) AS old_quantity, date_picked FROM pending_payments WHERE item_id='{$item_id}'
            AND id IN (SELECT MIN(id) FROM pending_payments WHERE item_id='{$item_id}'
                        AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d'))";

    $sql3 = "SELECT IFNULL(old_quantity, 0) AS old_quantity, date_transfered FROM transfer_goods WHERE item_id='{$item_id}'
            AND id IN (SELECT MIN(id) FROM transfer_goods WHERE item_id='{$item_id}'
                        AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d'))";

    $result1 = mysqli_query($conn1, $sql1);
    $result2 = mysqli_query($conn1, $sql2);
    $result3 = mysqli_query($conn1, $sql3);

    if($result1) {

        if (mysqli_num_rows($result1) > 0) {

            $record = mysqli_fetch_assoc($result1);

            $quantity1 = $record['old_quantity'];
            $date1 = new DateTime($record['date_sold']);

            array_push($date_arrays, $date1);

            if ($result2) {

                if (mysqli_num_rows($result2) > 0) {

                    $record = mysqli_fetch_assoc($result2);

                    $quantity2 = $record['old_quantity'];
                    $date2 = new DateTime($record['date_picked']);

                    array_push($date_arrays, $date2);

                    if ($result3) {

                        if (mysqli_num_rows($result3) > 0) {

                            $record = mysqli_fetch_assoc($result3);

                            $quantity3 = $record['old_quantity'];
                            $date3 = new DateTime($record['date_transfered']);

                            array_push($date_arrays, $date3);

                            if ($quantity1 != null &&  $quantity2 != null &&  $quantity3 != null) {

                                $min_date = min($date_arrays);


                                if ($date1 === $min_date) {

                                    return encrypt_data($quantity1);

                                } else if ($date2 === $min_date) {

                                    return encrypt_data($quantity2);

                                } else if ($date3 === $min_date) {

                                    return encrypt_data($quantity3);

                                } else {

                                    return encrypt_data($quantity1);

                                }

                            } else if ($quantity1 != null &&  $quantity2 != null &&  $quantity3 == null) {

                                $date_arrays = array();

                                array_push($date_arrays, $date1, $date2);

                                $min_date = min($date_arrays);

                                if ($date1 === $min_date) {

                                    return encrypt_data($quantity1);

                                } else if ($date2 === $min_date) {

                                    return encrypt_data($quantity2);

                                } else {

                                    return encrypt_data($quantity1);

                                }

                            } else if ($quantity1 != null &&  $quantity2 == null &&  $quantity3 != null) {

                                $date_arrays = array();

                                array_push($date_arrays, $date1, $date3);

                                $min_date = min($date_arrays);

                                if ($date1 === $min_date) {

                                    return encrypt_data($quantity1);

                                } else if ($date3 === $min_date) {

                                    return encrypt_data($quantity3);

                                } else {

                                    return encrypt_data($quantity1);

                                }

                            } else if ($quantity1 == null &&  $quantity2 != null &&  $quantity3 != null)  {

                                $date_arrays = array();

                                array_push($date_arrays, $date2, $date3);

                                $min_date = min($date_arrays);

                                if ($date2 === $min_date) {

                                    return encrypt_data($quantity2);

                                } else if ($date3 === $min_date) {

                                    return encrypt_data($quantity3);

                                } else {

                                    return encrypt_data($quantity2);

                                }

                            } else if ($quantity1 == null &&  $quantity2 == null &&  $quantity3 == null) {

                                return encrypt_data(0);

                            } else if ($quantity1 == null &&  $quantity2 == null &&  $quantity3 != null) {

                                return encrypt_data($quantity3);

                            } else if ($quantity1 == null &&  $quantity2 != null &&  $quantity3 == null) {

                                return encrypt_data($quantity2);

                            } else if ($quantity1 != null &&  $quantity2 == null &&  $quantity3 == null) {

                                return encrypt_data($quantity1);

                            }

                        } else {
                            return encrypt_data(0);       
                        }

                    } else {
                        $data_insert=array(
                            "status" => "error",
                            "message" => "Could not get opening stock3!"
                        );

                        echo json_encode($data_insert);
                        mysqli_close($conn1);

                        exit();
                    }

                    

                } else {
                    return encrypt_data(0);
                }




            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Could not get opening stock2!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        } else {
            return encrypt_data(0);
        }

    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Could not get opening stock1!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }



}


function get_transaction_code_items($conn1, $tcode, $shop_id) {
    $sql = "SELECT * FROM sold_stock WHERE shop_id='{$shop_id}' AND transaction_code='{$tcode}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $items = [];
        $cr=0;

        $total=0;

        $profit = 0;

        $time_of_sale = '';



        while($row = mysqli_fetch_assoc($result)) {

            // get the item
            $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $items[$cr]['item'] = $item;
            $items[$cr]['quantity'] = encrypt_data($row['quantity']);
            $items[$cr]['buying_price'] = encrypt_data($row['buying_price']);
            $items[$cr]['selling_price'] = encrypt_data($row['selling_price']);

            $total += $row['quantity'] * $row['selling_price'];


            if ($row['selling_price'] > $row['buying_price']) {
              $profit += $row['quantity'] * ($row['selling_price'] -$row['buying_price']);
            }

            $time_of_sale = encrypt_data($row['date_sold']);

            $cr++;

        }

        $transaction['items'] = $items;
        $transaction['transaction_total'] = encrypt_data($total);
        $transaction['profit_total'] = $profit;
        $transaction['time_of_sale'] = $time_of_sale;

        return $transaction;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get transaction items!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_transaction_code_time_items($conn1, $tcode, $shop_id, $time) {
  $sql = "SELECT * FROM sold_stock WHERE shop_id='{$shop_id}' AND transaction_code='{$tcode}' AND date_sold='{$time}'";
  $result = mysqli_query($conn1, $sql);

  if($result) {

      $items = [];
      $cr=0;

      $total=0;

      $profit = 0;

      $time_of_sale = '';



      while($row = mysqli_fetch_assoc($result)) {

          // get the item
          $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

          $items[$cr]['item'] = $item;
          $items[$cr]['quantity'] = encrypt_data($row['quantity']);
          $items[$cr]['buying_price'] = encrypt_data($row['buying_price']);
          $items[$cr]['selling_price'] = encrypt_data($row['selling_price']);

          $total += $row['quantity'] * $row['selling_price'];


          if ($row['selling_price'] > $row['buying_price']) {
            $profit += $row['quantity'] * ($row['selling_price'] -$row['buying_price']);
          }

          $time_of_sale = encrypt_data($row['date_sold']);

          $cr++;

      }

      $transaction['items'] = $items;
      $transaction['transaction_total'] = encrypt_data($total);
      $transaction['profit_total'] = $profit;
      $transaction['time_of_sale'] = $time_of_sale;

      return $transaction;

  } else {
      $data_insert=array(
      "status" => "error",
      "message" => "Could not get transaction items!"
      );

      echo json_encode($data_insert);
      mysqli_close($conn1);

      exit();
  }
}

function get_transaction_code_differed_items($conn1, $tcode, $shop_id) {
    $sql = "SELECT * FROM pending_payments WHERE shop_id='{$shop_id}' AND transaction_code='{$tcode}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $items = [];
        $cr=0;

        $total=0;

        $profit = 0;

        $time_of_sale = '';

        while($row = mysqli_fetch_assoc($result)) {

            // get the item
            $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $items[$cr]['item'] = $item;
            $items[$cr]['quantity'] = encrypt_data($row['item_quantity']);
            $items[$cr]['buying_price'] = encrypt_data($row['buying_price']);
            $items[$cr]['selling_price'] = encrypt_data($row['item_selling_price']);

            $total += $row['item_quantity'] * $row['item_selling_price'];

            if ($row['item_selling_price'] > $row['buying_price']) {
              $profit += $row['item_quantity'] * ($row['item_selling_price'] -$row['buying_price']);
            }

            $time_of_sale = encrypt_data($row['date_picked']);

            $cr++;

        }

        $transaction['items'] = $items;
        $transaction['transaction_total'] = encrypt_data($total);
        $transaction['profit_total'] = $profit;
        $transaction['time_of_sale'] = $time_of_sale;

        return $transaction;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor transaction items!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_transaction_code_differed_time_items($conn1, $tcode, $shop_id, $time) {
  $sql = "SELECT * FROM pending_payments WHERE shop_id='{$shop_id}' AND transaction_code='{$tcode}' AND date_picked='{$time}'";
  $result = mysqli_query($conn1, $sql);

  if($result) {

      $items = [];
      $cr=0;

      $total=0;

      $profit = 0;

      $time_of_sale = '';

      while($row = mysqli_fetch_assoc($result)) {

          // get the item
          $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

          $items[$cr]['item'] = $item;
          $items[$cr]['quantity'] = encrypt_data($row['item_quantity']);
          $items[$cr]['buying_price'] = encrypt_data($row['buying_price']);
          $items[$cr]['selling_price'] = encrypt_data($row['item_selling_price']);

          $total += $row['item_quantity'] * $row['item_selling_price'];

          if ($row['item_selling_price'] > $row['buying_price']) {
            $profit += $row['item_quantity'] * ($row['item_selling_price'] -$row['buying_price']);
          }

          $time_of_sale = encrypt_data($row['date_picked']);

          $cr++;

      }

      $transaction['items'] = $items;
      $transaction['transaction_total'] = encrypt_data($total);
      $transaction['profit_total'] = $profit;
      $transaction['time_of_sale'] = $time_of_sale;

      return $transaction;

  } else {
      $data_insert=array(
      "status" => "error",
      "message" => "Could not get debtor transaction items!"
      );

      echo json_encode($data_insert);
      mysqli_close($conn1);

      exit();
  }
}

function get_transaction_code_trnasfer_items($conn1, $tcode, $shop_id) {
    $sql = "SELECT * FROM transfer_goods WHERE shop_id='{$shop_id}' AND transaction_code='{$tcode}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $items = [];
        $cr=0;

        $total=0;

        while($row = mysqli_fetch_assoc($result)) {

            // get the item
            $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $items[$cr]['item'] = $item;
            $items[$cr]['quantity'] = encrypt_data($row['quantity']);
            $items[$cr]['buying_price'] = encrypt_data($row['buying_price']);

            $total += $row['quantity'] * $row['buying_price'];

        }

        $transaction['items'] = $items;
        $transaction['transaction_total'] = encrypt_data($total);

        return $transaction;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get debtor transaction items!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_reversed_item_cash($conn1, $sale_id, $transaction_code, $item_id, $shop_id) {
    $sql = "SELECT * FROM returned_stock WHERE sale_id='{$sale_id}'
            AND sale_type='cash' AND transaction_code='{$transaction_code}'
            AND item_id='{$item_id}' AND shop_id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $returned = [];
        $cr=0;

        $total_returned = 0;

        while ($row = mysqli_fetch_assoc($result)) {

            $returned[$cr]['item'] = get_shop_item_details($conn1, $shop_id, $item_id);
            $returned[$cr]['quantity_returned'] = encrypt_data($row['quantity_returned']);
            $returned[$cr]['returning_reason'] = encrypt_data($row['returning_reason']);
            $returned[$cr]['date_returned'] = encrypt_data($row['date_returned']);

            $total_returned += $row['quantity_returned'];

            $cr++;
        }

        $returned_stock['records'] = $returned;
        $returned_stock['total_quantity_returned'] = encrypt_data($total_returned);

        return $returned_stock;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get returned stock!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_reversed_item_differed($conn1, $sale_id, $transaction_code, $item_id, $shop_id) {
    $sql = "SELECT * FROM returned_stock WHERE sale_id='{$sale_id}'
            AND sale_type='differed' AND transaction_code='{$transaction_code}'
            AND item_id='{$item_id}' AND shop_id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $returned = [];
        $cr=0;

        $total_returned = 0;

        while ($row = mysqli_fetch_assoc($result)) {

            $returned[$cr]['item'] = get_shop_item_details($conn1, $shop_id, $item_id);
            $returned[$cr]['quantity_returned'] = encrypt_data($row['quantity_returned']);
            $returned[$cr]['returning_reason'] = encrypt_data($row['returning_reason']);
            $returned[$cr]['date_returned'] = encrypt_data($row['date_returned']);

            $total_returned += $row['quantity_returned'];

            $cr++;
        }

        $returned_stock['records'] = $returned;
        $returned_stock['total_quantity_returned'] = encrypt_data($total_returned);

        return $returned_stock;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get returned stock!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_items_sales_period_report($conn1, $item_id, $shop_id, $date) {

    $sql = "SELECT * FROM sold_stock WHERE shop_id='{$shop_id}'
            AND item_id='{$item_id}'
            AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            AND transaction_id IN (SELECT DISTINCT transaction_id FROM sold_stock)
            ORDER BY transaction_id DESC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $sales=[];
        $cr=0;

        while($row=mysqli_fetch_assoc($result)) {

            $code = $row['transaction_code'];

            // get total for this transaction code
            $transaction_code_details = get_transaction_code_items($conn1, $code, $shop_id);

            $seller= get_shop_username($conn1, $row['sold_by']);

            // check if there was any reversed item
            $reversed_stock = get_reversed_item_cash($conn1, $row['id'], $code, $item_id, $shop_id);

            $sales[$cr]['transaction_code'] = encrypt_data($code);
            $sales[$cr]['transaction_code_items'] = $transaction_code_details;
            $sales[$cr]['date_sold'] = encrypt_data($date);
            $sales[$cr]['seller'] = encrypt_data($seller);
            $sales[$cr]['customer'] = encrypt_data('Cash customer');
            $sales[$cr]['returned_stock'] = $reversed_stock;

            $cr++;

        }

        return $sales;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get items period sales report!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_items_debtors_period_report($conn1, $item_id, $shop_id, $date) {

    $sql = "SELECT * FROM pending_payments WHERE shop_id='{$shop_id}'
            AND item_id='{$item_id}'
            AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            AND transaction_id IN (SELECT DISTINCT transaction_id FROM pending_payments)
            ORDER BY transaction_id DESC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $sales=[];
        $cr=0;

        while($row=mysqli_fetch_assoc($result)) {

            $code = $row['transaction_code'];

            // get total for this transaction code
            $transaction_code_details = get_transaction_code_differed_items($conn1, $code, $shop_id);

            $seller= get_shop_username($conn1, $row['sold_by']);
            $customer= get_customer_record($conn1, $row['customer_id'], $shop_id);

            // check if there was any reversed item
            $reversed_stock = get_reversed_item_differed($conn1, $row['id'], $code, $item_id, $shop_id);

            $sales[$cr]['transaction_code'] = encrypt_data($code);
            $sales[$cr]['transaction_code_items'] = $transaction_code_details;
            $sales[$cr]['date_sold'] = encrypt_data($date);
            $sales[$cr]['seller'] = encrypt_data($seller);
            $sales[$cr]['customer'] = $customer['name'];
            $sales[$cr]['returned_stock'] = $reversed_stock;

            $cr++;

        }

        return $sales;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get items period debtors sales report!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_items_transfer_period_report($conn1, $item_id, $shop_id, $date) {

    $sql = "SELECT * FROM transfer_goods WHERE shop_id='{$shop_id}'
            AND item_id='{$item_id}'
            AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            AND transaction_id IN (SELECT DISTINCT transaction_id FROM transfer_goods)
            ORDER BY transaction_id DESC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $sales=[];
        $cr=0;

        while($row=mysqli_fetch_assoc($result)) {

            $code = $row['transaction_code'];

            // get total for this transaction code
            $transaction_code_details = get_transaction_code_trnasfer_items($conn1, $code, $shop_id);

            $seller= get_shop_username($conn1, $row['sold_by']);
            $customer= get_customer_record($conn1, $row['customer_id'], $shop_id);

            $sales[$cr]['transaction_code'] = encrypt_data($code);
            $sales[$cr]['transaction_code_items'] = $transaction_code_details;
            $sales[$cr]['date_sold'] = encrypt_data($date);
            $sales[$cr]['seller'] = encrypt_data($seller);
            $sales[$cr]['customer'] = $customer['name'];

            $cr++;

        }

        return $sales;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get items period transfer sales report!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_invoice_supplier_name($conn1, $shop_id, $invoice_id) {

    $sql = "SELECT name FROM suppliers WHERE id IN (SELECT supplier_id FROM add_invoices WHERE id='{$invoice_id}' AND shop_id='{$shop_id}')";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $record = mysqli_fetch_assoc($result);

        return encrypt_data($record['name']);

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get supplier!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_today_user_sales_purchases($conn1, $shop_id, $user_id) {

    $TODAY = date('Y-m-d h:i:s', time());

    $sql = "SELECT * FROM sales_purchases WHERE shop_id='{$shop_id}' AND added_by='{$user_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $purchases = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            // get item details
            $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $purchases[$cr]['id'] = encrypt_data($row['id']);
            $purchases[$cr]['item'] = $item;
            $purchases[$cr]['quantity'] = encrypt_data($row['quantity']);
            $purchases[$cr]['buying_price'] = encrypt_data($row['buying_price']);

            $cr++;
        }

        return $purchases;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in sales purchases!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    }

}

function get_date_sales_purchases($conn1, $shop_id, $date) {


    $sql = "SELECT * FROM sales_purchases WHERE shop_id='{$shop_id}'
            AND DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $purchases = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            // get item details
            $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $purchases[$cr]['id'] = encrypt_data($row['id']);
            $purchases[$cr]['item'] = $item;
            $purchases[$cr]['quantity'] = encrypt_data($row['quantity']);
            $purchases[$cr]['buying_price'] = encrypt_data($row['buying_price']);
            $purchases[$cr]['added_by'] = get_shop_user_details($conn1, $row['added_by']);

            $cr++;
        }

        return $purchases;

    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in sales purchases!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}


function get_invoice_name($conn1, $shop_id, $invoice_id) {
    $sql = "SELECT name FROM add_invoices WHERE id='{$invoice_id}' AND shop_id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $record = mysqli_fetch_assoc($result);

        return encrypt_data($record['name']);



    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get invoice name!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_item_date_added_stock($conn1, $item_id, $shop_id, $date) {
    $sql = "SELECT * FROM added_stock WHERE shop_id='{$shop_id}'
            AND item_id='{$item_id}'
            AND DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT($date, '%Y-%m-%d')";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $invoice_records=[];
        $cr=0;

        $total_quantity = 0;

        while($record = mysqli_fetch_assoc($result)) {

            $supplier = get_invoice_supplier_name($conn1, $shop_id, $row['invoice_id']);
            $added_by = get_shop_username($conn1, $row['added_by']);

            $invoice_name = get_invoice_name($conn1, $shop_id, $row['invoice_id']);

            $invoices = get_shop_invoice_items($conn1, $shop_id, $row['invoice_id']);

            $invoice_records[$cr]['supplier'] = $supplier;
            $invoice_records[$cr]['added_by'] = $added_by;
            $invoice_records[$cr]['invoice_name'] = $invoice_name;
            $invoice_records[$cr]['invoice_items'] = $invoices['items'];
            $invoice_records[$cr]['quantity_added'] = $invoices['total'];

            $total_quantity += decrypt_data($invoices['total']);

            $cr++;

        }

        $added_stock['invoices'] = $invoice_records;
        $added_stock['quantity_added'] = encrypt_data($total_quantity);

        return $added_stock;

    } else {
        $data_insert=array(
        "status" => "error",
        "message" => "Could not get added stock!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}



function get_shop_receipt_options($conn1, $shop_id) {
    $sql = "SELECT * FROM shop_receipt_options WHERE shop_id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $options;

        while($row = mysqli_fetch_assoc($result)) {
            $options['image_url'] = encrypt_data($row['image_url']);
            $options['paybill'] = encrypt_data($row['paybill']);
            $options['paybill_account'] = encrypt_data($row['paybill_account']);
            $options['till'] = encrypt_data($row['till']);
            $options['mpesa_business_name'] = encrypt_data($row['mpesa_business_name']);
            $options['footer_message'] = encrypt_data($row['footer_message']);
        }

        return $options;

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Could not get your shop receipt options!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);
    }
}


function get_shop_profile_details($conn1, $shop_id, $user_id) {
    $sql="SELECT * FROM shops WHERE id='{$shop_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $shop;
        $shop_contacts=[];

        while($row=mysqli_fetch_assoc($result)) {
            $shop['name'] = encrypt_data($row['name']);
            $shop['description'] = encrypt_data($row['description']);
            $shop['location'] = encrypt_data($row['location']);
        }

        $sql2="SELECT * FROM shop_contacts WHERE shop_id='{$shop_id}'";
        $result2 = mysqli_query($conn1, $sql2);

        if($result) {

            $cr=0;

            while($row2=mysqli_fetch_assoc($result2)) {
                $shop_contacts[$cr] = encrypt_data($row2['contact']);
                $cr++;
            }

            $shop_user_name = get_shop_username($conn1, $user_id);

            $receipt_options = get_shop_receipt_options($conn1, $shop_id);

            $shop['contacts'] = $shop_contacts;
            $shop['receipt_options'] = $receipt_options;
            $shop['username'] = $shop_user_name;

            return $shop;

        } else {
            $data_insert = array(
            "status" => "error",
            "message" => "Could not get your shop contacts!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn1);
        }


    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Could not get your shop!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);
    }
}

function get_user_period_cash_sales($conn1, $shop_id, $user_id, $from, $to) {
  $sql1 = "SELECT IFNULL(SUM(quantity*selling_price), 0) AS sales,  IFNULL(SUM(quantity* (selling_price-buying_price)), 0) AS profit
          FROM sold_stock
          WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
          DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')";
  $result = mysqli_query($conn1, $sql1);
  if($result) {

      $record = mysqli_fetch_assoc($result);

      $sales['sales'] = $record['sales'];
      $sales['profit'] = $record['profit'];

      return $sales;

  } else {
    $data_insert = array(
    "status" => "error",
    "message" => "Could not get user cash sales!"
    );
    // return the error
    echo json_encode($data_insert);
    // close connection
    mysqli_close($conn1);
    exit();
  }
}


function get_user_date_expenses($conn1, $shop_id, $user_id, $date) {

  $sql = "SELECT IFNULL(SUM(amount), 0) AS total FROM expenses WHERE shop_id='{$shop_id}' AND employee='{$user_id}' AND DATE_FORMAT(date_incurred, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
  $result = mysqli_query($conn1, $sql);
  if($result) {

      $record = mysqli_fetch_assoc($result);

      if ($record['total'] != null) {
        if ($record['total'] != '') {
          return $record['total'];
        } else {
          return 0;
        }
      } else {
        return 0;
      }



    } else {

      $data_insert = array(
      "status" => "error",
      "message" => "Could not get user date expenses!"
      );
      // return the error
      echo json_encode($data_insert);
      // close connection
      mysqli_close($conn1);
      exit();
    }


}


function get_user_date_sales($conn1, $shop_id, $user_id, $date) {

  $sql11 = "SELECT a.transaction_code, a.receipt_total, a.profit, b.date_sold AS date_sold, c.name FROM

          (SELECT transaction_code AS transaction_code,
              SUM(quantity * selling_price) AS receipt_total,
              SUM(quantity * (selling_price-buying_price)) AS profit, shop_id, customer_id
            FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') GROUP BY transaction_code, customer_id) as a
              INNER JOIN
              (SELECT transaction_id, transaction_code,date_sold FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS b ON a.transaction_code = b.transaction_code
              INNER JOIN
              (SELECT id, name FROM customers
                  WHERE shop_id=0) AS c

              GROUP BY a.transaction_code, a.receipt_total, a.profit, b.date_sold, c.name

          UNION

          SELECT a.transaction_code, a.receipt_total, a.profit, b.date_sold, c.name FROM

          (SELECT transaction_code AS transaction_code,
              SUM(item_quantity * item_selling_price) AS receipt_total,
              SUM(item_quantity * (item_selling_price-buying_price)) AS profit, shop_id, customer_id
            FROM pending_payments WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') GROUP BY transaction_code, customer_id ) as a
              INNER JOIN
              (SELECT transaction_code,date_picked AS date_sold FROM pending_payments WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS b ON a.transaction_code = b.transaction_code
              INNER JOIN
              (SELECT id, name FROM customers
                  WHERE shop_id='{$shop_id}') AS c

              ON a.customer_id = c.id
              GROUP BY a.transaction_code, a.receipt_total, a.profit, b.date_sold, c.name ORDER BY date_sold DESC";


  $sql12 = "SELECT a.transaction_code, a.receipt_total, a.profit, a.date_sold AS date_sold, c.name FROM

              (SELECT transaction_code AS transaction_code,
                  SUM(quantity * selling_price) AS receipt_total,
                  SUM(quantity * (selling_price-buying_price)) AS profit, shop_id, customer_id, date_sold AS date_sold
                FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') GROUP BY transaction_code, customer_id, date_sold) as a

                  INNER JOIN
                  (SELECT id, name FROM customers
                      WHERE shop_id=0) AS c

                  GROUP BY a.transaction_code, a.receipt_total, a.profit, a.date_sold, c.name


              UNION



              SELECT a.transaction_code, a.receipt_total, a.profit, a.date_sold, c.name FROM

              (SELECT transaction_code AS transaction_code,
                  SUM(item_quantity * item_selling_price) AS receipt_total,
                  SUM(item_quantity * (item_selling_price-buying_price)) AS profit, shop_id, customer_id, date_picked AS date_sold
                FROM pending_payments WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') GROUP BY transaction_code, customer_id, date_sold ) as a

                  INNER JOIN
                  (SELECT id, name FROM customers
                      WHERE shop_id='{$shop_id}') AS c

                  ON a.customer_id = c.id
                  GROUP BY a.transaction_code, a.receipt_total, a.profit, date_sold, c.name ORDER BY date_sold DESC";

  $result1 = mysqli_query($conn1, $sql12);

  if ($result1) {

    $sales = [];
    $cr=0;

    // get transaction items
    while ($row=mysqli_fetch_assoc($result1)) {

      $sales[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
      $sales[$cr]['transaction_code_total'] = encrypt_data($row['receipt_total']);
      $sales[$cr]['transaction_code_profit'] = encrypt_data($row['profit']);
      $sales[$cr]['transaction_code_customer'] = encrypt_data($row['name']);
      $sales[$cr]['time_of_sale'] = encrypt_data($row['date_sold']);


      $cr++;

    }

    return $sales;

  } else {
    $data_insert = array(
      "status" => "error",
      "message" => "Could not get USER date sales!"
      );
      // return the error
      echo json_encode($data_insert);
      // close connection
      mysqli_close($conn1);
      exit();
  }

}


function get_user_date_sales_records($conn1, $shop_id, $user_id, $date) {

   $sql12 = "SELECT transaction_code, customer_id, item_id, buying_price, quantity, selling_price, date_sold FROM
                (SELECT transaction_code, customer_id, item_id, buying_price, quantity, selling_price, date_sold
                    FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                    
                    UNION SELECT transaction_code, customer_id, item_id, buying_price, item_quantity AS quantity, item_selling_price AS selling_price, date_picked AS date_sold
                    FROM pending_payments WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS a ORDER BY date_sold DESC";

    $result1 = mysqli_query($conn1, $sql12);
  
    if ($result1) {
  
      $sales = [];
      $cr=0;
  
      // get transaction items
      while ($row=mysqli_fetch_assoc($result1)) {
  
        $sales[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);


        $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);
        
        
        $sales[$cr]['item'] = $item['item_name'];
        $sales[$cr]['quantity'] = encrypt_data($row['quantity']);
        $sales[$cr]['buying_price'] = encrypt_data($row['buying_price']);
        $sales[$cr]['selling_price'] = encrypt_data($row['selling_price']);


        if ($row['selling_price'] > $row['buying_price']) {
          $profit = $row['quantity'] * ($row['selling_price'] -$row['buying_price']);
          $sales[$cr]['profit'] = encrypt_data($profit);
        } else {
          $sales[$cr]['profit'] = encrypt_data('0');
        }

        // get the customer
        if ($row['customer_id'] == 0) {
            $sales[$cr]['customer'] = encrypt_data('Cash customer');
        } else {
            $customer = get_customer_record($conn1, $row['customer_id'], $shop_id);
            $sales[$cr]['customer'] = $customer['name'];
        }

        $sales[$cr]['time_of_sale'] = encrypt_data($row['date_sold']);

        $cr++;

      }
  
      return $sales;
  
    } else {
      $data_insert = array(
        "status" => "error",
        "message" => "Could not get USER date sales!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);
        exit();
    }
  
  }


function get_user_date_payment_records($conn1, $shop_id, $user_id, $date) {

  $sql12 = "SELECT a.discounts AS discounts, a.reversed AS reversed1, b.reversed AS reversed2 FROM
                (SELECT SUM(discount) AS discounts, SUM(amount_reversed) AS reversed FROM sales_payments WHERE shop_id='{$shop_id}' AND transaction_code
                    IN (SELECT transaction_code FROM sold_stock
                            WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') GROUP BY transaction_code)) AS a
                INNER JOIN
                (SELECT SUM(quantity_returned) AS reversed FROM pending_payments WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND transaction_code
                    IN (SELECT transaction_code FROM pending_payments
                            WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') GROUP BY transaction_code)) AS b";

  $result1 = mysqli_query($conn1, $sql12);

  if ($result1) {

    $date_discounts = 0;
    $date_reversed = 0;

    // get transaction items
    while ($row=mysqli_fetch_assoc($result1)) {

      $reversed = $row['reversed1'] + $row['reversed2'];

      $date_discounts = encrypt_data($row['discounts']);
      $date_reversed = encrypt_data($reversed);

    }

    $all['discounts'] = $date_discounts;
    $all['reversed'] = $date_reversed;

    return $all;

  } else {
    $data_insert = array(
      "status" => "error",
      "message" => "Could not get USER date payment records!"
      );
      // return the error
      echo json_encode($data_insert);
      // close connection
      mysqli_close($conn1);
      exit();
  }
}

function get_user_date_cash_sales($conn1, $shop_id, $user_id, $date) {
    $sql = "SELECT IFNULL(SUM(cash), 0) AS cash,
                    IFNULL(SUM(mpesa), 0) AS mpesa,
                    IFNULL(SUM(bank), 0) AS bank,
                    IFNULL(SUM(discount), 0) AS discount,
                    IFNULL(SUM(amount_reversed), 0) AS reversed
            FROM sales_payments WHERE transaction_code IN
                (SELECT transaction_code FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d'))
            AND shop_id='{$shop_id}'
            AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
    
    $result = mysqli_query($conn1, $sql);
    if ($result) {

        $record = mysqli_fetch_assoc($result);

        $sales['cash'] = $record['cash'];
        $sales['mpesa'] = $record['mpesa'];
        $sales['bank'] = $record['bank'];
        $sales['discount'] = $record['discount'];
        $sales['reversed'] = $record['reversed'];

        return $sales;

    } else {
        $data_insert = array(
          "status" => "error",
          "message" => "Could not get USER date payment records!"
          );
          // return the error
          echo json_encode($data_insert);
          // close connection
          mysqli_close($conn1);
          exit();
      }
}

function get_user_date_reversed_differed_total($conn1, $shop_id, $user_id, $date) {
    $sql = "SELECT IFNULL(SUM(quantity_returned * item_selling_price), 0) AS total FROM pending_payments WHERE shop_id='{$shop_id}'
                AND sold_by='{$user_id}'
                AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $record = mysqli_fetch_assoc($result);

        return $record['total'];

    } else {
        $data_insert = array(
            "status" => "error",
            "message" => "Could not get USER reversed differed!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);
        exit();
    }
}


function get_user_date_profit($conn1, $shop_id, $user_id, $date) {
    $sql = "SELECT IFNULL(SUM((selling_price-buying_price) * quantity), 0) AS profit
                FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                    AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')

            UNION 

            (SELECT IFNULL(SUM((item_selling_price-buying_price) * item_quantity), 0) AS profit
                FROM pending_payments WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d'))";

    $result = mysqli_query($conn1, $sql);
    if ($result) {

        $profit = 0;

        while($row = mysqli_fetch_assoc($result)) {
            $profit += $row['profit'];
        }
        return $profit;

    } else {
        $data_insert = array(
          "status" => "error",
          "message" => "Could not get USER date profit!"
          );
          // return the error
          echo json_encode($data_insert);
          // close connection
          mysqli_close($conn1);
          exit();
      }
}

function get_user_date_differed_sales($conn1, $shop_id, $user_id, $date) {
    $sql = "SELECT IFNULL(SUM(item_quantity * item_selling_price), 0) AS total_sales FROM pending_payments
            WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                AND sold_by='{$user_id}'";
    
    $result = mysqli_query($conn1, $sql);
    if ($result) {

        $record = mysqli_fetch_assoc($result);

        return $record['total_sales'];

    } else {
        $data_insert = array(
          "status" => "error",
          "message" => "Could not get USER differed records!"
          );
          // return the error
          echo json_encode($data_insert);
          // close connection
          mysqli_close($conn1);
          exit();
      }
}

function get_user_date_paid_differed($conn1, $shop_id, $user_id, $date) {

    $sql = "SELECT IFNULL(SUM(cash + mpesa + bank), 0) AS total FROM differed_sales_payments WHERE
                shop_id='{$shop_id}' AND paid_to='{$user_id}' AND DATE_FORMAT(date_paid, '%Y-%m-%d')= DATE_FORMAT('{$date}', '%Y-%m-%d')";
    $result = mysqli_query($conn1, $sql);

    if ($result) {

        $record = mysqli_fetch_assoc($result);

        return $record['total'];

    } else {
        $data_insert = array(
            "status" => "error",
            "message" => "Could not get USER date sales!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);
        exit();
    }
}

function get_user_date_sales2($conn1, $shop_id, $user_id, $date) {
  $sql = "SELECT SUM(quantity * selling_price) AS sales, SUM((selling_price-buying_price) * quantity) AS profit
          FROM (SELECT quantity, selling_price, buying_price
                      FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')= DATE_FORMAT('{$date}', '%Y-%m-%d')
                UNION SELECT DISTINCT item_quantity AS quantity, item_selling_price AS selling_price, buying_price
                              FROM pending_payments WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS a";
  $result1 = mysqli_query($conn1, $sql);
  if ($result1) {

    $record = mysqli_fetch_assoc($result1);


    $sales['date_sales'] = encrypt_data($record['sales']);
    $sales['date_profit'] = encrypt_data($record['profit']);

    $date_expenses = get_user_date_expenses($conn1, $shop_id, $user_id, $date);

    $sales['expenses'] = encrypt_data($date_expenses);

    // get payment records
    $payments = get_user_date_payment_records($conn1, $shop_id, $user_id, $date);


    $sales['discounts'] = $payments['discounts'];
    $sales['reversed'] = $payments['reversed'];

    return $sales;


  } else {
    $data_insert = array(
      "status" => "error",
      "message" => "Could not get USER date sales!"
      );
      // return the error
      echo json_encode($data_insert);
      // close connection
      mysqli_close($conn1);
      exit();
  }

}



function get_user_period_sales_old($conn1, $shop_id, $user_id, $from, $to) {

  // GET THE DATES

    $sql11 = "SELECT SUM(quantity * selling_price) AS sales, SUM((selling_price-buying_price) * quantity) AS profit, DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold
                FROM (SELECT quantity, selling_price, buying_price, DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold
                            FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')
                              IN (
                                  SELECT DISTINCT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock
                                  WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                                  DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                              )

                UNION
                SELECT item_quantity AS quantity, item_selling_price AS selling_price, buying_price, DATE_FORMAT(date_picked, '%Y-%m-%d') AS date_sold FROM pending_payments
                  WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d')
                  IN (
                        SELECT DISTINCT DATE_FORMAT(date_picked, '%Y-%m-%d') AS date_sold FROM pending_payments
                        WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                        DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                      )

              ) AS c GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')";

  $result1 = mysqli_query($conn1, $sql11);

  if ($result1) {

    $sales =  [];
    $cr=0;

    // get sales for each date
    while($row = mysqli_fetch_assoc($result1)) {

      $date = $row['date_sold'];


      // $user_date_sales = get_user_date_sales($conn1, $shop_id, $user_id, $date);
      $sales[$cr]['date'] = encrypt_data($date);
      $sales[$cr]['date_sales'] = encrypt_data($row['sales']);
      $sales[$cr]['profit'] = encrypt_data($row['profit']);

      $date_expenses = get_user_date_expenses($conn1, $shop_id, $user_id, $date);

      // get payment records
      $payments = get_user_date_payment_records($conn1, $shop_id, $user_id, $date);

      $sales[$cr]['expenses'] = encrypt_data($date_expenses);
      $sales[$cr]['discounts'] = $payments['discounts'];
      $sales[$cr]['reversed'] = $payments['reversed'];

      $cr++;

    }

    return $sales;

  } else {
    $data_insert = array(
      "status" => "error",
      "message" => "Could not get date sales!"
      );
      // return the error
      echo json_encode($data_insert);
      // close connection
      mysqli_close($conn1);
      exit();
  }

}

function get_user_period_sales($conn1, $shop_id, $user_id, $from, $to) {

    // GET THE DATES
  
      $sql11 = "SELECT SUM(quantity * selling_price) AS sales, SUM((selling_price-buying_price) * quantity) AS profit, DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold
                  FROM (SELECT quantity, selling_price, buying_price, DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold
                              FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')
                                IN (
                                    SELECT DISTINCT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock
                                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                                    DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                )
  
                  UNION
                  SELECT item_quantity AS quantity, item_selling_price AS selling_price, buying_price, DATE_FORMAT(date_picked, '%Y-%m-%d') AS date_sold FROM pending_payments
                    WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d')
                    IN (
                          SELECT DISTINCT DATE_FORMAT(date_picked, '%Y-%m-%d') AS date_sold FROM pending_payments
                          WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                          DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        )
  
                ) AS c GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')";
    

    $sql2="SELECT DISTINCT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
            AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                                    DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
        UNION
        
        SELECT DISTINCT DATE_FORMAT(date_picked, '%Y-%m-%d')  AS date_sold FROM pending_payments WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}'
            AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                                    DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
        GROUP BY date_sold";
  
    $result1 = mysqli_query($conn1, $sql2);
  
    if ($result1) {
  
      $sales =  [];
      $cr=0;
  
      // get sales for each date
      while($row = mysqli_fetch_assoc($result1)) {
  
        $date = $row['date_sold'];

        // get cash sales
        $cash_sales = get_user_date_cash_sales($conn1, $shop_id, $user_id, $date);

        $differed_sales = get_user_date_differed_sales($conn1, $shop_id, $user_id, $date);

        // get reversed differed
        $reversed_differed = get_user_date_reversed_differed_total($conn1, $shop_id, $user_id, $date);

        $total_reversed = $cash_sales['reversed'];

        $profit = get_user_date_profit($conn1, $shop_id, $user_id, $date);

        $paid_differed = get_user_date_paid_differed($conn1, $shop_id, $user_id, $date);
  
        $date_expenses = get_user_date_expenses($conn1, $shop_id, $user_id, $date);
  
        $sales[$cr]['date'] = encrypt_data($date);
        $sales[$cr]['cash'] = encrypt_data($cash_sales['cash']);
        $sales[$cr]['mpesa'] = encrypt_data($cash_sales['mpesa']);
        $sales[$cr]['bank'] = encrypt_data($cash_sales['bank']);
        $sales[$cr]['discount'] = encrypt_data($cash_sales['discount']);
        $sales[$cr]['profit'] = encrypt_data($profit);
        $sales[$cr]['reversed'] = encrypt_data($total_reversed);
        $sales[$cr]['differed'] = encrypt_data($differed_sales);
        $sales[$cr]['paid_differed'] = encrypt_data($paid_differed);
        $sales[$cr]['expenses'] = encrypt_data($date_expenses);
  
        $cr++;
  
      }
  
      return $sales;
  
    } else {
      $data_insert = array(
        "status" => "error",
        "message" => "Could not get date sales!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);
        exit();
    }
  
}


function get_user_period_differed_sales($conn1, $shop_id, $user_id, $from, $to) {
  $sql2 = "SELECT sold_by, IFNULL(SUM(item_quantity* item_selling_price), 0) AS sales, IFNULL(SUM(item_quantity* (item_selling_price-buying_price)), 0) AS profit
              FROM pending_payments
              WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
              DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')";

  $result = mysqli_query($conn1, $sql2);
  if($result) {

      $record = mysqli_fetch_assoc($result);

      $sales['sales'] = $record['sales'];
      $sales['profit'] = $record['profit'];

      return $sales;

    } else {
      $data_insert = array(
      "status" => "error",
      "message" => "Could not get user differed sales!"
      );
      // return the error
      echo json_encode($data_insert);
      // close connection
      mysqli_close($conn1);
      exit();
  }
}

function get_user_period_expenses($conn1, $shop_id, $user_id, $from, $to) {
  $sql1 = "SELECT IFNULL(SUM(amount), 0) AS expenses
            FROM expenses
            WHERE shop_id='{$shop_id}' AND employee='{$user_id}' AND
            DATE_FORMAT(date_incurred, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
            DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')";
  $result = mysqli_query($conn1, $sql1);
  if($result) {

      $record = mysqli_fetch_assoc($result);

      return $record['expenses'];

    } else {
      $data_insert = array(
      "status" => "error",
      "message" => "Could not get user expenses!"
      );
      // return the error
      echo json_encode($data_insert);
      // close connection
      mysqli_close($conn1);

      exit();
  }
}


function get_top_products($conn1, $shop_id) {

    $sql = "SELECT IFNULL(item_id, '') AS item_id, IFNULL(SUM(quantity), 0) AS quantity_sold FROM sold_stock WHERE shop_id='{$shop_id}' GROUP BY item_id ORDER BY quantity_sold DESC LIMIT 5";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $products = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $products[$cr]['item'] = decrypt_data($item['item_name']);
            $products[$cr]['quantity_sold'] = $row['quantity_sold'];

            $cr++;

        }

        return $products;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in top product"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}

function get_top_profit_products($conn1, $shop_id) {

    $sql = "SELECT IFNULL(item_id, '') AS item_id, IFNULL(SUM((selling_price-buying_price) * quantity), 0) AS profit FROM sold_stock WHERE shop_id='{$shop_id}' AND selling_price>buying_price GROUP BY item_id ORDER BY profit DESC LIMIT 5";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $products = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            $item = get_shop_item_details($conn1, $shop_id, $row['item_id']);

            $products[$cr]['item'] = decrypt_data($item['item_name']);
            $products[$cr]['profit'] = $row['profit'];

            $cr++;

        }

        return $products;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in top product"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}



function get_current_sales($conn1, $shop_id) {

    $sql = "SELECT a.gross_sales, a.gross_profit,
                    e.expenses,
                    b.added_stock AS added_stock,
                    p.cash, p.mpesa, p.bank,
                    p.discounts, p.reversed AS cash_reversed, d.reversed AS debtor_reversed,
                    d.debtor_sales, d.debtor_profit, pd.paid_debtors, t.transfers,
                    (recovered1.recovered + recovered2.recovered) AS recovered,
                    top_sold.item_id AS top_sold_item,
                    top_sold.quantity_sold AS top_sold_quantity,
                    top_profitable.item_id AS top_profitable_item,
                    top_profitable.profit AS top_profit_amount

            FROM

            (SELECT IFNULL(SUM(`quantity`*`selling_price`), 0) AS gross_sales, IFNULL(SUM((`selling_price`-`buying_price`)*`quantity`),0) AS gross_profit FROM `sold_stock` WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT(CURRENT_DATE, '%Y-%m-%d')) AS a
            INNER JOIN
            (SELECT IFNULL(SUM(`buying_price`*quantity_added), 0) AS added_stock FROM added_stock WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT(CURRENT_DATE, '%Y-%m-%d')) AS b
            INNER JOIN
            (SELECT IFNULL(SUM(amount), 0) AS expenses FROM expenses WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_incurred, '%Y-%m-%d') = DATE_FORMAT(CURRENT_DATE, '%Y-%m-%d')) AS e
            INNER JOIN
            (SELECT IFNULL(SUM(cash), 0) AS cash, IFNULL(SUM(mpesa), 0) AS mpesa, IFNULL(SUM(bank), 0) AS bank, IFNULL(SUM(discount), 0) AS discounts, IFNULL(SUM(amount_reversed), 0) AS reversed FROM sales_payments WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT(CURRENT_DATE, '%Y-%m-%d')) AS p
            INNER JOIN
            (SELECT IFNULL(SUM(item_quantity * item_selling_price), 0) AS debtor_sales, IFNULL(SUM(item_quantity *(item_selling_price-buying_price)), 0) AS debtor_profit, IFNULL(SUM(quantity_returned * item_selling_price), 0) AS reversed FROM pending_payments WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT(CURRENT_DATE, '%Y-%m-%d')) AS d
            INNER JOIN
            (SELECT IFNULL(SUM(amount_paid), 0) AS paid_debtors FROM differed_payments WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT(CURRENT_DATE, '%Y-%m-%d')) AS pd
            INNER JOIN
            (SELECT IFNULL(SUM(quantity * buying_price), 0) AS transfers FROM transfer_goods WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT(CURRENT_DATE, '%Y-%m-%d')) AS t
            INNER JOIN
            (SELECT IFNULL(SUM(`quantity`*`selling_price`), 0) AS recovered FROM `sold_stock` WHERE shop_id='{$shop_id}' AND selling_price=buying_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT(CURRENT_DATE, '%Y-%m-%d')) AS recovered1
            INNER JOIN
            (SELECT IFNULL(SUM(item_quantity * item_selling_price), 0) AS recovered FROM pending_payments WHERE shop_id='{$shop_id}' AND item_selling_price=buying_price AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT(CURRENT_DATE, '%Y-%m-%d')) AS recovered2
            INNER JOIN
            (SELECT IFNULL(item_id, '') AS item_id, IFNULL(SUM(quantity), 0) AS quantity_sold FROM sold_stock WHERE shop_id='{$shop_id}' GROUP BY item_id ORDER BY quantity_sold DESC LIMIT 1) AS top_sold
            INNER JOIN
            (SELECT IFNULL(item_id, '') AS item_id, IFNULL(SUM((selling_price-buying_price) * quantity), 0) AS profit FROM sold_stock WHERE shop_id='{$shop_id}' AND selling_price>buying_price GROUP BY item_id ORDER BY profit DESC LIMIT 1) AS top_profitable";

        $result = mysqli_query($conn1, $sql);

        if($result) {

            $record = mysqli_fetch_assoc($result);

            if(mysqli_num_rows($result) > 0) {

                $report['gross_sales'] = $record['gross_sales'] + $record['debtor_sales'];
                $report['gross_profit'] = $record['gross_profit'] + $record['debtor_profit'];
                $report['expenses'] = $record['expenses'];
                $report['net_profit'] = ($record['gross_profit'] + $record['debtor_profit']) - $record['expenses'];
                $report['added_stock'] = $record['added_stock'];
                $report['cash'] = $record['cash'];
                $report['mpesa'] = $record['mpesa'];
                $report['bank'] = $record['bank'];
                $report['discounts'] = $record['discounts'];
                $report['cash_reversed'] = $record['cash_reversed'];
                $report['debtor_reversed'] = $record['debtor_reversed'];
                $report['debtor_sales'] = $record['debtor_sales'];
                $report['paid_debtors'] = $record['paid_debtors'];
                $report['transfers'] = $record['transfers'];
                $report['recovered'] = $record['recovered'];
                $report['top_sold_quantity'] = $record['top_sold_quantity'];
                $report['top_profit_amount'] = $record['top_profit_amount'];

                if($record['top_sold_item'] != '') {
                    $item = get_shop_item_details($conn1, $shop_id, $record['top_sold_item']);
                    $report['top_sold_item'] = decrypt_data($item['item_name']);
                }

                if($record['top_profitable_item'] != '') {
                    $item = get_shop_item_details($conn1, $shop_id, $record['top_profitable_item']);
                    $report['top_profitable_item'] = decrypt_data($item['item_name']);
                }


                $top_products = get_top_products($conn1, $shop_id);
                $top_profit_products = get_top_profit_products($conn1, $shop_id);

                $report['top_sale_products'] = $top_products;
                $report['top_profit_products'] = $top_profit_products;

                return $report;

            } else {
                $report['gross_sales'] = 0;
                $report['gross_profit'] = 0;
                $report['expenses'] = 0;
                $report['net_profit'] = 0;
                $report['added_stock'] = 0;
                $report['cash'] = 0;
                $report['mpesa'] = 0;
                $report['bank'] = 0;
                $report['discounts'] = 0;
                $report['cash_reversed'] = 0;
                $report['debtor_reversed'] = 0;
                $report['debtor_sales'] = 0;
                $report['paid_debtors'] = 0;
                $report['transfers'] = 0;
                $report['recovered'] = 0;
                $report['top_sold_quantity'] = 0;
                $report['top_profit_amount'] = 0;

                $report['top_sold_item'] = '-';

                $report['top_profitable_item'] = '-';


                $report['top_sale_products'] = [];
                $report['top_profit_products'] = [];

                return $report;
            }


        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened in current sales"
            );

            echo json_encode($data_insert);
            mysqli_close($conn1);

            exit();
        }
}

function get_item_returns_in_period($conn1, $shop_id, $item_id, $date, $to) {
    $sql = "SELECT IFNULL(SUM(quantity_returned), 0) AS returned FROM returned_stock WHERE
            shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$date}', '%Y-%m-%d') AND
            DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')";
    
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $record = mysqli_fetch_assoc($result);

        if(mysqli_num_rows($result) > 0) {

            return $record['returned'];

        } else {
            return 0;
        }


    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in item return!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}





function get_item_last_sell_date($conn1, $shop_id, $item_id, $from) {
    $sql = "SELECT b.date_sold, b.old_quantity FROM 
            (SELECT IFNULL(date_sold, '') AS date_sold, old_quantity - quantity AS old_quantity FROM sold_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                date_sold IN (SELECT MAX(date_sold) FROM sold_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$from}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_picked, '') AS date_sold, old_quantity - item_quantity AS old_quantity FROM pending_payments WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                date_picked IN (SELECT MAX(date_picked) FROM pending_payments WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$from}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_transfered, '') AS date_sold, old_quantity - quantity AS old_quantity FROM transfer_goods WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                date_transfered IN (SELECT MAX(date_transfered) FROM transfer_goods WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$from}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_added, '') AS date_sold, old_quantity + quantity_added AS old_quantity FROM added_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                date_added IN (SELECT MAX(date_added) FROM added_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$from}', '%Y-%m-%d'))
            ) AS b WHERE date_sold != '' ORDER BY date_sold DESC LIMIT 1";

    $result = mysqli_query($conn1, $sql);

    if($result) {

        $record = mysqli_fetch_assoc($result);

        if(mysqli_num_rows($result) > 0) {

            // check if there was returns after this date but before the from period
            $returned_items = get_item_returns_in_period($conn1, $shop_id, $item_id, $record['date_sold'], $from);

            return $record['old_quantity'] + $returned_items;

        } else {
            return 0;
        }


    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in last sell date!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}



function get_item_period_opening_quantity($conn1, $shop_id, $item_id, $from, $to) {

    $sql = "SELECT b.date_sold, b.old_quantity FROM 
            (SELECT IFNULL(date_sold, '') AS date_sold, old_quantity FROM sold_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND
                date_sold IN (SELECT MIN(date_sold) FROM sold_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_picked, '') AS date_sold, old_quantity FROM pending_payments WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND
                date_picked IN (SELECT MIN(date_picked) FROM pending_payments WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_transfered, '') AS date_sold, old_quantity FROM transfer_goods WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND
                date_transfered IN (SELECT MIN(date_transfered) FROM transfer_goods WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_added, '') AS date_sold, old_quantity FROM added_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND
                date_added IN (SELECT MIN(date_added) FROM added_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))
            ) AS b WHERE date_sold != '' ORDER BY date_sold ASC LIMIT 1";
    
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $record = mysqli_fetch_assoc($result);

        if(mysqli_num_rows($result) > 0) {
            return $record['old_quantity'];
        } else {
            return get_item_last_sell_date($conn1, $shop_id, $item_id, $from);
        }

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in opening quantity",
            "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_item_date_opening_quantity($conn1, $shop_id, $item_id, $date) {

    $sql = "SELECT b.date_sold, b.old_quantity FROM 
            (SELECT IFNULL(date_sold, '') AS date_sold, old_quantity FROM sold_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') AND
                date_sold IN (SELECT MIN(date_sold) FROM sold_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_picked, '') AS date_sold, old_quantity FROM pending_payments WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') AND
                date_picked IN (SELECT MIN(date_picked) FROM pending_payments WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_transfered, '') AS date_sold, old_quantity FROM transfer_goods WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') AND
                date_transfered IN (SELECT MIN(date_transfered) FROM transfer_goods WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_added, '') AS date_sold, old_quantity FROM added_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') AND
                date_added IN (SELECT MIN(date_added) FROM added_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d'))
            ) AS b WHERE date_sold != '' ORDER BY date_sold ASC LIMIT 1";
    
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $record = mysqli_fetch_assoc($result);

        if(mysqli_num_rows($result) > 0) {
            return $record['old_quantity'];
        } else {
            return get_item_last_sell_date($conn1, $shop_id, $item_id, $date);
        }

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in opening quantity",
            "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_item_period_closing_quantity($conn1, $shop_id, $item_id, $from, $to) {

    $sql = "SELECT b.date_sold AS date_sold, b.old_quantity AS old_quantity, b.quantity AS quantity FROM 
            (SELECT IFNULL(date_sold, '') AS date_sold, old_quantity, quantity AS quantity FROM sold_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND
                date_sold IN (SELECT MAX(date_sold) FROM sold_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_picked, '') AS date_sold, old_quantity, item_quantity AS quantity FROM pending_payments WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND
                date_picked IN (SELECT MAX(date_picked) FROM pending_payments WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_transfered, '') AS date_sold, old_quantity, quantity AS quantity FROM transfer_goods WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND
                date_transfered IN (SELECT MAX(date_transfered) FROM transfer_goods WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))
            UNION
            SELECT IFNULL(date_added, '') AS date_sold, old_quantity + quantity_added AS old_quantity, 0 AS quantity FROM added_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND
                date_added IN (SELECT MAX(date_added) FROM added_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))
            ) AS b WHERE date_sold != '' ORDER BY date_sold DESC LIMIT 1";

    $result = mysqli_query($conn1, $sql);

    if($result) {


        if(mysqli_num_rows($result) > 0) {

            $record = mysqli_fetch_assoc($result);

            if($record['old_quantity'] - $record['quantity'] < 0) {
                return 0;
            } else {
                return $record['old_quantity'] - $record['quantity'];
            }

            
        } else {
            return 0;
        }

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in closing quantity",
            "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_item_period_transaction_dates($conn1, $item_id, $from, $to) {
    $sql = "SELECT date_sold
            FROM
            (SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM `sold_stock` WHERE item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')) AS a
            UNION
            SELECT DATE_FORMAT(date_added, '%Y-%m-%d') AS date_sold FROM added_stock WHERE item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(date_added, '%Y-%m-%d')
            UNION
            SELECT DATE_FORMAT(date_picked, '%Y-%m-%d') AS date_sold FROM pending_payments WHERE item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(date_picked, '%Y-%m-%d')
            UNION
            SELECT DATE_FORMAT(date_transfered, '%Y-%m-%d') AS date_sold FROM transfer_goods  WHERE item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(date_transfered, '%Y-%m-%d')
            UNION
            SELECT DATE_FORMAT(date_returned, '%Y-%m-%d') AS date_sold FROM returned_stock WHERE item_id='{$item_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(date_returned, '%Y-%m-%d')
            
            ORDER BY date_sold DESC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $dates=[];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            $dates[$cr]['date_sold'] = $row['date_sold'];

            $cr++;
        }

        return $dates;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Could not get date transactions",
            "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }

}

function get_item_date_transactions($conn1, $item_id, $date) {
    $sql = "SELECT (a.gross_sales + d.debtor_sales) AS sold, b.added_stock AS added_stock, t.transfers AS transfers, r.returned AS returned_stock
            FROM
            (SELECT IFNULL(SUM(`quantity`), 0) AS gross_sales FROM `sold_stock` WHERE item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS a
            INNER JOIN
            (SELECT IFNULL(SUM(quantity_added), 0) AS added_stock FROM added_stock WHERE item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS b
            INNER JOIN
            (SELECT IFNULL(SUM(item_quantity), 0) AS debtor_sales FROM pending_payments WHERE item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS d
            INNER JOIN
            (SELECT IFNULL(SUM(quantity), 0) AS transfers FROM transfer_goods  WHERE item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS t
            INNER JOIN
            (SELECT IFNULL(SUM(quantity_returned), 0) AS returned FROM returned_stock WHERE item_id='{$item_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS r";
    
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $row = mysqli_fetch_assoc($result);

        $record['sold'] = $row['sold'];
        $record['added_stock'] = $row['added_stock'];
        $record['transfers'] = $row['transfers'];
        $record['returned_stock'] = $row['returned_stock'];

        return $record;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Could not get item date transactions",
            "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}


function get_top_sold_item($conn1, $shop_id, $from, $to) {
    $sql = "SELECT a.item_id, SUM(a.quantity_sold) AS quantity_sold FROM

            (SELECT item_id, IFNULL(SUM(quantity), 0) AS quantity_sold FROM sold_stock WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY item_id
            UNION ALL
            SELECT item_id, IFNULL(SUM(item_quantity), 0) AS quantity_sold FROM pending_payments WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY item_id
            UNION ALL
            SELECT item_id, IFNULL(SUM(quantity), 0) AS quantity_sold FROM transfer_goods WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY item_id) AS a GROUP BY item_id ORDER BY quantity_sold DESC LIMIT 1";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        if(mysqli_num_rows($result) > 0) {

            return mysqli_fetch_assoc($result);

        } else {
            return 'none';
        }

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in top item",
            "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();
    }
}

function get_top_profitable_item($conn1, $shop_id, $from, $to) {

    $sql = "SELECT a.item_id, a.profit AS profit FROM
            (SELECT item_id, IFNULL(SUM((selling_price-buying_price) * quantity), 0) AS profit FROM sold_stock
                WHERE shop_id='{$shop_id}' AND selling_price>buying_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY item_id
            UNION ALL
            SELECT item_id, IFNULL(SUM((item_selling_price-buying_price) * item_quantity), 0) AS profit FROM pending_payments
                WHERE shop_id='{$shop_id}' AND item_selling_price>buying_price AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY item_id) AS a ORDER BY profit DESC LIMIT 1";

    $result = mysqli_query($conn1, $sql);

    if($result) {

        if(mysqli_num_rows($result) > 0) {

            return mysqli_fetch_assoc($result);

        } else {
            return 'none';
        }

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened in top profit item!",
            "error" => mysqli_error($conn1)
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    }

}

function get_stock_taking_items($conn1, $date_id, $shop_id) {
    // get items not confirmed
    $sql = "SELECT * FROM all_stock WHERE shop_id='{$shop_id}' AND deleted=0 AND id NOT IN
            (SELECT item_id FROM stock_taking WHERE shop_id='{$shop_id}' AND date_id='{$date_id}')";
    $result = mysqli_query($conn1, $sql);
    if($result) {

        $records=[];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            // $records[$cr]['id'] = encrypt_data($row['id']);
            // $records[$cr]['name'] = $row['item_name'];
            // $records[$cr]['quantity'] = $row['quantity'];

            $records[$cr]['id'] = encrypt_data($row['id']);
            $records[$cr]['date_id'] = encrypt_data($date_id);
            $records[$cr]['name'] = $row['item_name'];
            $records[$cr]['imageUrl'] = $row['imageUrl'];
            $records[$cr]['current_quantity'] = $row['quantity'];
            $records[$cr]['quantity_confirmed'] = '';
            $records[$cr]['confirmed_by'] = '';

            $cr++;
        }

        return $records;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Could not fetch shop items!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    }
}

function get_existing_stock_taking_items($conn1, $date_id, $shop_id) {
    // get items not confirmed
    $sql = "SELECT a.id, a.item_name, a.imageUrl, IFNULL(b.current_quantity, '') AS current_quantity, IFNULL(b.quantity_confirmed, '') AS quantity_confirmed, IFNULL(b.confirmed_by, '') AS confirmed_by FROM
            (SELECT id, item_name, imageUrl, quantity FROM all_stock WHERE shop_id='{$shop_id}' and deleted=0) AS a
            INNER JOIN
            (SELECT item_id, current_quantity, quantity_confirmed, confirmed_by FROM stock_taking
                WHERE shop_id='{$shop_id}' AND date_id='{$date_id}') AS b
            ON a.id=b.item_id";
    $result = mysqli_query($conn1, $sql);
    if($result) {

        $records=[];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $records[$cr]['id'] = encrypt_data($row['id']);
            $records[$cr]['date_id'] = encrypt_data($date_id);
            $records[$cr]['name'] = $row['item_name'];
            $records[$cr]['imageUrl'] = $row['imageUrl'];
            $records[$cr]['current_quantity'] = $row['current_quantity'];
            $records[$cr]['quantity_confirmed'] = $row['quantity_confirmed'];

            // get confirmed by username
            $confirmed_by = decrypt_data(get_shop_username($conn1, $row['confirmed_by']));
            $records[$cr]['confirmed_by'] = $confirmed_by;

            $cr++;
        }

        return $records;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Could not fetch shop items2!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    }
}

function get_stock_taking_date_id_confirmed_items($conn1, $date_id, $shop_id) {
    $sql = "SELECT a.id, a.item_name, a.imageUrl, IFNULL(b.current_quantity, '') AS current_quantity,
            IFNULL(b.quantity_confirmed, '') AS quantity_confirmed, IFNULL(b.confirmed_by, '') AS confirmed_by
            FROM
            (SELECT id, item_name, imageUrl, quantity FROM all_stock WHERE shop_id='{$shop_id}' and deleted=0) AS a
            INNER JOIN
            (SELECT item_id, current_quantity, quantity_confirmed, confirmed_by FROM stock_taking
                WHERE shop_id='{$shop_id}' AND date_id='{$date_id}') AS b
            ON a.id=b.item_id";

    $result = mysqli_query($conn1, $sql);
    if($result) {

        $less_items = [];
        $lr=0;
        $more_items = [];
        $mr=0;
        $records=[];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            // $records[$cr]['id'] = encrypt_data($row['id']);
            // $records[$cr]['name'] = $row['item_name'];
            // $records[$cr]['quantity'] = $row['quantity'];

            if ($row['current_quantity']==$row['quantity_confirmed']) {

                $records[$cr]['id'] = encrypt_data($row['id']);
                $records[$cr]['date_id'] = encrypt_data($date_id);
                $records[$cr]['name'] = $row['item_name'];
                $records[$cr]['imageUrl'] = $row['imageUrl'];
                $records[$cr]['current_quantity'] = $row['current_quantity'];
                $records[$cr]['quantity_confirmed'] = $row['quantity_confirmed'];

                $confirmed_by = decrypt_data(get_shop_username($conn1, $row['confirmed_by']));
                $records[$cr]['confirmed_by'] = $confirmed_by;

                $cr++;

            } else if ($row['current_quantity'] < $row['quantity_confirmed']) {

                $less_items[$cr]['id'] = encrypt_data($row['id']);
                $less_items[$cr]['date_id'] = encrypt_data($date_id);
                $less_items[$cr]['name'] = $row['item_name'];
                $less_items[$cr]['imageUrl'] = $row['imageUrl'];
                $less_items[$cr]['current_quantity'] = $row['current_quantity'];
                $less_items[$cr]['quantity_confirmed'] = $row['quantity_confirmed'];

                $confirmed_by = decrypt_data(get_shop_username($conn1, $row['confirmed_by']));
                $less_items[$cr]['confirmed_by'] = $confirmed_by;

                $lr++;

            } else {

                $more_items[$cr]['id'] = encrypt_data($row['id']);
                $more_items[$cr]['date_id'] = encrypt_data($date_id);
                $more_items[$cr]['name'] = $row['item_name'];
                $more_items[$cr]['imageUrl'] = $row['imageUrl'];
                $more_items[$cr]['current_quantity'] = $row['current_quantity'];
                $more_items[$cr]['quantity_confirmed'] = $row['quantity_confirmed'];

                $confirmed_by = decrypt_data(get_shop_username($conn1, $row['confirmed_by']));
                $more_items[$cr]['confirmed_by'] = $confirmed_by;

                $lr++;

            }

        }

        $response['less_items'] = $less_items;
        $response['more_items'] = $more_items;
        $response['confirmed'] = $records;

        return $response;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Could not fetch shop items!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    }
}

function get_shop_month_sms($conn1, $shop_id, $month) {
    $sql = "SELECT * FROM sent_messages WHERE shop_id='{$shop_id}' AND
            DATE_FORMAT(date_sent, '%Y-%m') = DATE_FORMAT('{$month}', '%Y-%m') ORDER BY id DESC";

    $result = mysqli_query($conn1, $sql);
    if($result) {

        $records=[];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {

            $records[$cr]['message'] = $row['message'];
            $sent_by = get_shop_user_details($conn1, $row['sent_by']);
            $records[$cr]['sent_by'] = decrypt_data($sent_by['username']);
            $records[$cr]['users'] = $row['recipients_count'];
            $records[$cr]['date_sent'] = $row['date_sent'];

            $cr++;
        }

        return $records;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Could not date sms!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    }

}


function send_uwazii($number) {

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://restapi.uwaziimobile.com/v1/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $number,
    CURLOPT_HTTPHEADER => array(
        'X-Access-Token: efe36eb9de336ef133f0cb837a6afed7',
        'Content-Type: application/json'
    ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;

}




















$postdata = file_get_contents("php://input");
$request = json_decode($postdata);

$action = decrypt_data($request->action);


date_default_timezone_set('Africa/Nairobi');


$TODAY = date('Y-m-d h:i:s', time());








# Users Section

// Login section
if ($action === 'login') {

    $usernamedb = mysqli_escape_string($conn, decrypt_data($request->username));
    $passworddb = mysqli_escape_string($conn, decrypt_data($request->password));

    $token = '';

    // Check credential in database
    $sql="SELECT * FROM users WHERE mobile='{$usernamedb}' AND password='{$passworddb}' AND deleted=0 ORDER BY id DESC LIMIT 1";
    $result=mysqli_query($conn, $sql);
    if($result)
	{
	    if(mysqli_num_rows($result)>0)
	    {

            while($row = mysqli_fetch_assoc($result)) {

                // check if user account is disabled
                if($row['disabled'] == 1) {
                    $data_insert = array(
                    "status" => "error",
                    "message" => "Your account is not active. Talk to your admin!"
                    );
                    // return the error
                    echo json_encode($data_insert);
                    // close connection
                    mysqli_close($conn);
                } else {
                    $user_id = $row['id'];
                    // get the user shops
                    $user_shops = get_user_shops($conn, $user_id);


                    $iat = time(); // time of token issued at
                    $nbf = $iat; //not before in seconds
                    $exp = $iat + 86400; // expire time of token in seconds (1 day = 86400)

                    $token = array(
                        "iss" => "https://bizonline.co.ke",
                        "aud" => "https://bizonline.co.ke",
                        "iat" => $iat,
                        "nbf" => $nbf,
                        "exp" => $exp,
                        "data" => array(
                            "user_db_id" => encrypt_data($user_id),
                            "role" => encrypt_data($row['role'])
                        )
                    );


                    http_response_code(200);

                    $jwt = JWT::encode($token, SECRET_KEY);

                    $data_insert = array(
                            'access_token' => encrypt_data($jwt),
                            'shops'   => $user_shops,
                            'uid' => encrypt_data($user_id),
                            'nm' => encrypt_data($row['username']),
                            'img' => encrypt_data($row['image_url']),
                            'role' => encrypt_data($row['role']),
                            'status' => "success",
                            );

                    echo json_encode($data_insert);
                    mysqli_close($conn);
                }

            }

	    } else {
	        $data_insert=array(
            "status" => "error",
            "message" => "Wrong details"
             );
            echo json_encode($data_insert);
    	    mysqli_close($conn);
	    }
	} else {
	    $data_insert=array(
         "data" => "0",
         "status" => "error",
         "message" => "Some error occured!"
         );
         echo json_encode($data_insert);
	     mysqli_close($conn);
	}
}

if ($action === 'get_shop_subscription_plan') {

   $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

   $plan =  get_shop_subscription($conn, $shop_id);

   $data_insert=array(
        "status" => "success",
        "message" => "success",
        "plan" => $plan
    );
    echo json_encode($data_insert);
    mysqli_close($conn);

}

if ($action === 'mobile-login2') {

    $mobile = mysqli_escape_string($conn, decrypt_data($request->mobile));


    // Check credential in database
    $sql="SELECT * FROM users WHERE mobile='{$mobile}'";
    $result=mysqli_query($conn,$sql);
    if($result)
	{
	    if(mysqli_num_rows($result)>0)
	    {

            while($row = mysqli_fetch_assoc($result)) {

                // check if user account is disabled
                if($row['disabled'] == 1) {
                    $data_insert = array(
                    "status" => "error",
                    "message" => "Your account is not active. Talk to your admin!"
                    );
                    // return the error
                    echo json_encode($data_insert);
                    // close connection
                    mysqli_close($conn1);
                } else {

                    // send the message

                    // generate random otp
                    $random_otp = substr(str_shuffle("123456789"), 0, 5);

                    // Set your message
                    $message = "BizOnline: Your login code is: ".$random_otp.".\n";
                    $message .= "@bizonline.co.ke #".$random_otp."";


                    $number = array(
                        "number" => $mobile,
                        "senderID" => "BIZONLINE",
                        "text" => $message,
                        "type" => "sms",
                        "beginDate" => date('Y-m-d', time()),
                        "beginTime" => date('h:i', time()),
                        "lifetime" => 555,
                        "delivery" => false
                    );


                    send_uwazii($number);





                    if ($responseCode==0) {

                        // hash the otp
                        $otp_hash = password_hash($random_otp, PASSWORD_DEFAULT);

                        $sql = "INSERT INTO login_sms (user_mobile, otp, verified, date_created) VALUES ('{$mobile}', '{$otp_hash}', 0, '{$TODAY}')";
                        $result1 = mysqli_query($conn, $sql);

                        if($result1) {

                                $encrypted_user_mobile = encrypt_data($mobile);

                                $encrypted_sms = encrypt_data($random_otp);

                                $data_insert = array(

                                "mobile" => $encrypted_user_mobile,
                                "sms" => $encrypted_sms,
                                "status" => "success",
                                "message" => "Message sent!"
                                );
                                // return the message
                                echo json_encode($data_insert);

                                // close connection
                                mysqli_close($conn);
                            } else {
                                $data_insert = array(
                                    "status" => "error",
                                    "message" => "Could not record message!"
                                    );
                                    // return the error
                                    echo json_encode($data_insert);
                                    // close connection
                                    mysqli_close($conn);
                            }

                    } else {
                        $data_insert = array(
                            "status" => "error",
                            "message" => "Could not send message. Try again later!"
                            );
                            // return the error
                            echo json_encode($data_insert);
                            // close connection
                            mysqli_close($conn);
                    }


                }

            }

	    } else {
	        $data_insert=array(
            "status" => "error",
            "message" => "That mobile number is not registered!"
             );
            echo json_encode($data_insert);
    	    mysqli_close($conn);
	    }
	} else {
	    $data_insert=array(
         "data" => "0",
         "status" => "error",
         "message" => "Some error occured!"
         );
         echo json_encode($data_insert);
	     mysqli_close($conn);
	}
}

if ($action === 'mobile-login') {

    $mobile = mysqli_escape_string($conn, decrypt_data($request->mobile));


    // Check credential in database
    $sql="SELECT * FROM users WHERE mobile='{$mobile}'";
    $result=mysqli_query($conn,$sql);
    if($result)
	{
	    if(mysqli_num_rows($result)>0)
	    {

            while($row = mysqli_fetch_assoc($result)) {

                // check if user account is disabled
                if($row['disabled'] == 1) {
                    $data_insert = array(
                    "status" => "error",
                    "message" => "Your account is not active. Talk to your admin!"
                    );
                    // return the error
                    echo json_encode($data_insert);
                    // close connection
                    mysqli_close($conn1);
                } else {

                    // send the message

                    // generate random otp
                    $random_otp = substr(str_shuffle("123456789"), 0, 5);

                    // Set your message
                    $message = "BizOnline: Your login code is: ".$random_otp.".\n";
                    $message .= "@bizonline.co.ke #".$random_otp."";



                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, $uwazii_url);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json')); //setting custom header


                    $uwazii_data = array (
                        "SenderId" => 'BIZONLINE',
                        "Is_Unicode" => false,
                        "Message" => $message,
                        "MobileNumbers" => $mobile,
                        "ApiKey" => "n1rIF0H1xTB6q0hUDKUZSOqklSs83F3AN+BTvPSzwpo=",
                        "ClientId" => "cd732ad8-5a65-46ff-8e4e-0374d4fa8074"
                    );

                    $data_string = json_encode($uwazii_data);

                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

                    $curl_response = curl_exec($curl);

                    $response_code = '';

                    if ($curl_response != null) {
                        $responseData = json_decode($curl_response, TRUE);
                        $responseCode = $responseData['ErrorCode'];
                    } else {
                        echo "Null Response";
                    }


                    if ($responseCode==0) {

                        // hash the otp
                        $otp_hash = password_hash($random_otp, PASSWORD_DEFAULT);

                        $sql = "INSERT INTO login_sms (user_mobile, otp, verified, date_created) VALUES ('{$mobile}', '{$otp_hash}', 0, '{$TODAY}')";
                        $result1 = mysqli_query($conn, $sql);

                        if($result1) {

                                $encrypted_user_mobile = encrypt_data($mobile);

                                $encrypted_sms = encrypt_data($random_otp);

                                $data_insert = array(

                                "mobile" => $encrypted_user_mobile,
                                "sms" => $encrypted_sms,
                                "status" => "success",
                                "message" => "Message sent!"
                                );
                                // return the message
                                echo json_encode($data_insert);

                                // close connection
                                mysqli_close($conn);
                            } else {
                                $data_insert = array(
                                    "status" => "error",
                                    "message" => "Could not record message!"
                                    );
                                    // return the error
                                    echo json_encode($data_insert);
                                    // close connection
                                    mysqli_close($conn);
                            }

                    } else {
                        $data_insert = array(
                            "status" => "error",
                            "message" => "Could not send message. Try again later!"
                            );
                            // return the error
                            echo json_encode($data_insert);
                            // close connection
                            mysqli_close($conn);
                    }


                }

            }

	    } else {
	        $data_insert=array(
            "status" => "error",
            "message" => "That mobile number is not registered!"
             );
            echo json_encode($data_insert);
    	    mysqli_close($conn);
	    }
	} else {
	    $data_insert=array(
         "data" => "0",
         "status" => "error",
         "message" => "Some error occured!"
         );
         echo json_encode($data_insert);
	     mysqli_close($conn);
	}
}


// signup user
if ($action === 'mobile-signup') {

    $mobile = mysqli_escape_string($conn, decrypt_data($request->mobile));

    // Check credential in database
    $sql="SELECT * FROM users WHERE mobile='{$mobile}'";
    $result=mysqli_query($conn,$sql);
    if($result) {
	    if(mysqli_num_rows($result) < 1)
	    {

            // send the otp message
            // generate random otp
            $random_otp = substr(str_shuffle("123456789"), 0, 5);

            // Set your message
            $message = "BizOnline: Your code is: ".$random_otp.".\n";
            $message .= "@bizonline.co.ke #".$random_otp."";

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $uwazii_url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json')); //setting custom header


            $uwazii_data = array (
                "SenderId" => 'BIZONLINE',
                "Is_Unicode" => false,
                "Message" => $message,
                "MobileNumbers" => $mobile,
                "ApiKey" => "n1rIF0H1xTB6q0hUDKUZSOqklSs83F3AN+BTvPSzwpo=",
                "ClientId" => "cd732ad8-5a65-46ff-8e4e-0374d4fa8074"
            );

            $data_string = json_encode($uwazii_data);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

            $curl_response = curl_exec($curl);

            $response_code = '';

            if ($curl_response != null) {
                $responseData = json_decode($curl_response, TRUE);
                $responseCode = $responseData['ErrorCode'];
            } else {
                echo "Null Response";
            }


            if ($responseCode==0) {

                // hash the otp
                $otp_hash = password_hash($random_otp, PASSWORD_DEFAULT);

                $sql = "INSERT INTO login_sms (user_mobile, otp, verified, date_created) VALUES ('{$mobile}', '{$otp_hash}', 0, '{$TODAY}')";
                $result1 = mysqli_query($conn, $sql);

                if($result1) {

                        $encrypted_user_mobile = encrypt_data($mobile);

                        $encrypted_sms = encrypt_data($random_otp);

                        $data_insert = array(

                        "mobile" => $encrypted_user_mobile,
                        "sms" => $encrypted_sms,
                        "status" => "success",
                        "message" => "Message sent!"
                        );
                        // return the message
                        echo json_encode($data_insert);

                        // close connection
                        mysqli_close($conn);
                    } else {
                        $data_insert = array(
                            "status" => "error",
                            "message" => "Could not record message!"
                            );
                            // return the error
                            echo json_encode($data_insert);
                            // close connection
                            mysqli_close($conn);
                    }

            } else {
                $data_insert = array(
                    "status" => "error",
                    "message" => "Could not send message. Try again later!"
                    );
                    // return the error
                    echo json_encode($data_insert);
                    // close connection
                    mysqli_close($conn);
            }

	    } else {
	        $data_insert=array(
            "status" => "error",
            "message" => "Mobile number is already registered. Use a different number!"
             );
            echo json_encode($data_insert);
    	    mysqli_close($conn);
	    }
	} else {
	    $data_insert=array(
         "data" => "0",
         "status" => "error",
         "message" => "Some error occured!"
         );
         echo json_encode($data_insert);
	     mysqli_close($conn);
	}
}

if ($action === 'send-password') {

    $mobile = mysqli_escape_string($conn, decrypt_data($request->mobile));

    // Check credential in database
    $sql="SELECT * FROM users WHERE mobile='{$mobile}'";
    $result=mysqli_query($conn,$sql);
    if($result) {
	    if(mysqli_num_rows($result) > 0)
	    {

            $record = mysqli_fetch_assoc($result);
            $password = $record['password'];

            // Set your message
            $message = "Your Bizonline password is: ".$password."";

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $uwazii_url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json')); //setting custom header


            $uwazii_data = array (
                "SenderId" => 'BIZONLINE',
                "Is_Unicode" => false,
                "Message" => $message,
                "MobileNumbers" => $mobile,
                "ApiKey" => "n1rIF0H1xTB6q0hUDKUZSOqklSs83F3AN+BTvPSzwpo=",
                "ClientId" => "cd732ad8-5a65-46ff-8e4e-0374d4fa8074"
            );

            $data_string = json_encode($uwazii_data);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

            $curl_response = curl_exec($curl);

            $response_code = '';

            if ($curl_response != null) {
                $responseData = json_decode($curl_response, TRUE);
                $responseCode = $responseData['ErrorCode'];
            } else {
                echo "Null Response";
            }


            if ($responseCode==0) {

                // hash the otp
                $otp_hash = password_hash($random_otp, PASSWORD_DEFAULT);

                $sql = "INSERT INTO login_sms (user_mobile, otp, verified, date_created) VALUES ('{$mobile}', '{$otp_hash}', 0, '{$TODAY}')";
                $result1 = mysqli_query($conn, $sql);

                if($result1) {

                        $encrypted_user_mobile = encrypt_data($mobile);

                        $encrypted_sms = encrypt_data($random_otp);

                        $data_insert = array(

                        "mobile" => $encrypted_user_mobile,
                        "sms" => $encrypted_sms,
                        "status" => "success",
                        "message" => "Message sent!"
                        );
                        // return the message
                        echo json_encode($data_insert);

                        // close connection
                        mysqli_close($conn);
                    } else {
                        $data_insert = array(
                            "status" => "error",
                            "message" => "Could not record message!"
                            );
                            // return the error
                            echo json_encode($data_insert);
                            // close connection
                            mysqli_close($conn);
                    }

            } else {
                $data_insert = array(
                    "status" => "error",
                    "message" => "Could not send message. Try again later!"
                    );
                    // return the error
                    echo json_encode($data_insert);
                    // close connection
                    mysqli_close($conn);
            }

	    } else {
	        $data_insert=array(
            "status" => "error",
            "message" => "The mobile number is not registered!"
             );
            echo json_encode($data_insert);
    	    mysqli_close($conn);
	    }
	} else {
	    $data_insert=array(
         "data" => "0",
         "status" => "error",
         "message" => "Some error occured!"
         );
         echo json_encode($data_insert);
	     mysqli_close($conn);
	}
}

if ($action === 'authenticate_signup_message') {

    // Decrypt and filter the values to prevent sql injection
    $mobile = mysqli_escape_string($conn, decrypt_data($request->mobile));
    $otp_received = mysqli_escape_string($conn, decrypt_data($request->user_otp));

    $sql = "SELECT id, otp FROM login_sms WHERE user_mobile = '{$mobile}' AND verified=0 AND id IN
            (SELECT max(id) FROM login_sms WHERE user_mobile = '{$mobile}' AND verified=0)";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {

                $row_id = $row['id'];
                $otp_db_hash = $row['otp'];

                if (password_verify($otp_received, $otp_db_hash)) {
                    $sql2="UPDATE login_sms SET otp='', verified=1 WHERE id='{$row_id}' AND user_mobile='{$mobile}'";
                    $result2=mysqli_query($conn, $sql2);
                    if ($result2) {

                        $data_insert = array(
                            'status' => "success",
                        );

                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    
                    }
                } else {
                    $data_insert = array(
                        "status" => "error",
                        "message" => "Wrong code!"
                    );
                    // return the error
                    echo json_encode($data_insert);
                    // close connection
                    mysqli_close($conn);
                }
            }
        } else {
            $data_insert = array(
                "status" => "error",
                "message" => "Unauthorized"
            );

            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn);
        }
    } else {
        $data_insert = array(
            "status" => "error",
            "message" => "Something bad happened. Try again!"
        );

        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn);
    }
}

if ($action === 'get_subscription_plans') {
    $sql = "SELECT * FROM subscription_options";
    $result = mysqli_query($conn, $sql);

    if($result) {

        $plans;

        while($row = mysqli_fetch_assoc($result)) {

            if($row['type'] == 'monthly') {
                $plans['monthly'] = $row['price'];
                $plans['monthly_extra_account'] = $row['extra_user_account'];
            }

            if($row['type'] == 'yearly') {
                $plans['yearly'] = $row['price'];
                $plans['yearly_extra_account'] = $row['extra_user_account'];
            }
        }

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "plans" => $plans
         );
         echo json_encode($data_insert);
	     mysqli_close($conn);

    } else {
	    $data_insert=array(
            "status" => "error",
            "message" => "Some error occured!"
         );
         echo json_encode($data_insert);
	     mysqli_close($conn);
	}
}


if ($action === 'authenticate_login_message') {

    // Decrypt and filter the values to prevent sql injection
    $mobile = mysqli_escape_string($conn, decrypt_data($request->mobile));
    $otp_received = mysqli_escape_string($conn, decrypt_data($request->user_otp));

    $sql = "SELECT id, otp FROM login_sms WHERE user_mobile = '{$mobile}' AND verified=0 AND id IN
            (SELECT max(id) FROM login_sms WHERE user_mobile = '{$mobile}' AND verified=0)";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {

                $row_id = $row['id'];
                $otp_db_hash = $row['otp'];

                if (password_verify($otp_received, $otp_db_hash)) {
                    $sql2="UPDATE login_sms SET otp='', verified=1 WHERE id='{$row_id}' AND user_mobile='{$mobile}'";
                    $result2=mysqli_query($conn, $sql2);
                    if ($result2) {

                        // get the users credentials
                        $sql = "SELECT * FROM users WHERE mobile='{$mobile}'";
                        $result = mysqli_query($conn, $sql);

                        if ($result) {
                            if (mysqli_num_rows($result) > 0) {

                                while($row=mysqli_fetch_assoc($result)) {



                                    $user_id = $row['id'];
                                    // get the user shops
                                    $user_shops = get_user_shops($conn, $user_id);


                                    $iat = time(); // time of token issued at
                                    $nbf = $iat; //not before in seconds
                                    $exp = $iat + 86400; // expire time of token in seconds (1 day = 86400)

                                    $token = array(
                                        "iss" => "https://bizonline.co.ke",
                                        "aud" => "https://bizonline.co.ke",
                                        "iat" => $iat,
                                        "nbf" => $nbf,
                                        "exp" => $exp,
                                        "data" => array(
                                            "user_db_id" => encrypt_data($user_id),
                                            "role" => encrypt_data($row['role'])
                                        )
                                    );


                                    http_response_code(200);

                                    $jwt = JWT::encode($token, SECRET_KEY);

                                    $data_insert = array(
                                            'access_token' => encrypt_data($jwt),
                                            'shops'   => $user_shops,
                                            'uid' => encrypt_data($user_id),
                                            'nm' => encrypt_data($row['username']),
                                            'img' => encrypt_data($row['image_url']),
                                            'role' => encrypt_data($row['role']),
                                            'status' => "success",
                                            );

                                    echo json_encode($data_insert);
                                    mysqli_close($conn);


                                }

                            } else {
                                    $data_insert = array(
                                    "data" => "0",
                                    "status" => "error",
                                    "message" => "User does not exist!"
                                    );
                                    // return the error
                                    echo json_encode($data_insert);
                                    // close connection
                                    mysqli_close($conn);
                                }
                        } else {
                            $data_insert = array(
                                "data" => "0",
                                "status" => "error",
                                "message" => "Something bad happened"
                                );
                                // return the error
                                echo json_encode($data_insert);
                                // close connection
                                mysqli_close($conn);
                            }
                    }
                } else {
                    $data_insert = array(
                        "status" => "error",
                        "message" => "Wrong code!"
                        );
                        // return the error
                        echo json_encode($data_insert);
                        // close connection
                        mysqli_close($conn);
                }
            }
        } else {
            $data_insert = array(
                "status" => "error",
                "message" => "Wrong code!"
            );

            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn);
        }
    }
}

if ($action === 'signup') {
    $username = mysqli_escape_string($conn, decrypt_data($request->username));
    $mobile = mysqli_escape_string($conn, decrypt_data($request->mobile));
    $business_name = mysqli_escape_string($conn, decrypt_data($request->name));
    $description = mysqli_escape_string($conn, decrypt_data($request->description));
    $location = mysqli_escape_string($conn, decrypt_data($request->location));
    $accounts = mysqli_escape_string($conn, decrypt_data($request->accounts));
    $subscription = mysqli_escape_string($conn, decrypt_data($request->subscription));

    $sql = "SELECT * FROM signup_businesses WHERE mobile='{$mobile}'";
    $result = mysqli_query($conn, $sql);

    if ($result) {

        if (mysqli_num_rows($result) < 1) {

            $sql2 = "INSERT INTO signup_businesses (name, mobile, business_name, description, location, employee_accounts, subscription_type)
                        VALUES ('{$username}', '{$mobile}', '{$business_name}', '{$description}', '{$location}', '{$accounts}', '{$subscription}')";
            $result2 = mysqli_query($conn, $sql2);

            if ($result2) {


                // Send message to admin
                $message = 'A new user has signed up for BizOnline : '.$mobile.'';

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $uwazii_url);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json')); //setting custom header


                $admin_mobile = 254739406631;

                $uwazii_data = array (
                    "SenderId" => 'BIZONLINE',
                    "Is_Unicode" => true,
                    "Message" => $message,
                    "MobileNumbers" => $admin_mobile,
                    "ApiKey" => "n1rIF0H1xTB6q0hUDKUZSOqklSs83F3AN+BTvPSzwpo=",
                    "ClientId" => "cd732ad8-5a65-46ff-8e4e-0374d4fa8074"
                );

                $data_string = json_encode($uwazii_data);

                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

                $curl_response = curl_exec($curl);

                $response_code = '';

                if ($curl_response != null) {
                    $responseData = json_decode($curl_response, TRUE);
                    $responseCode = $responseData['ErrorCode'];
                } else {
                    // echo "Null Response";
                    $response_code = 'Null Response';
                }


                if ($responseCode==0) {

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );

                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {
                    $data_insert = array(
                        "status" => "error",
                        "message" => "Could not send message. Try again later!"
                        );
                        // return the error
                        echo json_encode($data_insert);
                        // close connection
                        mysqli_close($conn);
                }



            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Something bad happened2!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Your request was previously received. We will contact you shortly."
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}


// check if token is valid
if ($action === 'check_token') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);
    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $data_insert=array(
             "valid" => true,
             "status" => "success",
             "message" => "Request authorized"
             );
        echo json_encode($data_insert);
    } catch (Exception $e){

         
         $data_insert=array(
         "jwt" => $jwt,
         "status" => "error2",
         "message" => $e->getMessage(),
         );

         echo json_encode($data_insert);
    }
}

// check if token is valid
if ($action === 'check_admin_token') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $user_id = mysqli_escape_string($conn, decrypt_data($request->adid));

        $sql="SELECT * FROM users WHERE id='{$user_id}' AND role='admin'";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            if (mysqli_num_rows($result) > 0) {
                $data_insert=array(
                    "status" => "success",
                    "message" => "Request authorized"
                    );
                echo json_encode($data_insert);
            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Unauthorized!"
                    );
                echo json_encode($data_insert);
            }
        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened!"
                );
            echo json_encode($data_insert);
        }


    } catch (Exception $e){

        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
    }
}

// get user
if ($action === 'get_user') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $user_id = $request->user_id;
        $sql="SELECT * FROM users WHERE id='{$user_id}'";

        $result=mysqli_query($conn,$sql);

        $user_details = array();

        $allData=[];
        if($result) {
            $cr = 0;
             while($row=mysqli_fetch_assoc($result)) {
                 $allData[$cr]['username'] = $row['username'];
                 $allData[$cr]['password'] = $row['password'];
                 $allData[$cr]['role'] = $row['role'];
                 $allData[$cr]['disabled'] = $row['disabled'];
                 $cr++;
              }
             $data_insert=array(
             "UserDetails" => $allData,
             "status" => "success",
             "message" => "Request authorized"
             );

             echo json_encode($data_insert);
             mysqli_close($conn);
        } else {
            http_response_code(403);
                 $data_insert=array(
                 "jwt" => $jwt,
                 "status" => "error1",
                 "message" => "Could not get user"
                 );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

if ($action === 'check-customer-type') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));


        $customer_id=mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $shop_id=mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql = "SELECT * FROM customers WHERE id='{$customer_id}' AND shop_id='{$shop_id}'";
        $result = mysqli_query($conn, $sql);

        if($result) {

            if(mysqli_num_rows($result) > 0) {

                $row = mysqli_fetch_assoc($result);

                $type=$row['type'];

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "type" => encrypt_data($type)
                );
    
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Customer does not exist!"
                );
    
                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Could not confirm customer!"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}


// get all users
if ($action === 'get-all-shop-users') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here

        $shop_id=mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql="SELECT * FROM shop_users WHERE shop_id='{$shop_id}' AND user_id IN (SELECT id FROM users WHERE deleted=0)";

        $result=mysqli_query($conn,$sql);

        $users=[];
        if($result) {

            $cr = 0;

             while($row=mysqli_fetch_assoc($result)) {

                // get the user details
                $user_detail = get_shop_user_details($conn, $row['user_id']);
                $user_roles = get_shop_user_roles($conn, $row['user_id'], $shop_id);
                $user_detail['roles'] = $user_roles;

                $users[$cr] = $user_detail;

                $cr++;
              }

             $data_insert=array(
             "Users" => $users,
             "status" => "success",
             "message" => "success"
             );

             echo json_encode($data_insert);
             mysqli_close($conn);
        } else {
            http_response_code(403);
                 $data_insert=array(
                 "jwt" => $jwt,
                 "status" => "error1",
                 "message" => "Could not get user"
                 );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

# End users section












# Stock section

// get sales for the past 7 days
if ($action === 'get-week-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        date_default_timezone_set('Africa/Nairobi');

        // Access is granted. Add code of the operation here
        $company_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $allData=[];
        $cr = 0;
        $start_date = date ("Y-m-d", time());
        $end_date = date ("Y-m-d", strtotime("-6 days", strtotime($start_date)));

        do {
             // get the sales from this min date to present;
            $total_cash_sales=0;
            $total_cash_profit=0;

            $total_differed_sales=0;
            $total_differed_profit=0;

            $total_expenses=0;

            $total_sales=0;

            $gross_profit=0;
            $net_profit=0;

            // get the cash sales
            // $start_date = $start_date->format('Y-m-d H:i:s');
            $starting_date=date("Y-m-d", strtotime($end_date));


            $sql1 = "SELECT IFNULL(ss.sales, 0) AS cash_sales,
                            IFNULL(ss.profit, 0) AS cash_profit,
                            IFNULL(pp.sales, 0) AS differed_sales,
                            IFNULL(pp.profit, 0) AS differed_profit,
                            IFNULL(ee.expense, 0) AS expenses
                      FROM (SELECT IFNULL(SUM(quantity*selling_price), 0) AS sales,
                                    IFNULL(SUM((selling_price - buying_price) * quantity), 0) AS profit FROM sold_stock WHERE shop_id='{$company_id}' AND DATE_FORMAT(`date_sold`, '%Y-%m-%d') = DATE_FORMAT('{$starting_date}', '%Y-%m-%d')) AS ss
                      INNER JOIN
                      (SELECT IFNULL(SUM(item_quantity*item_selling_price), 0) AS sales,
                                IFNULL(SUM((item_selling_price - buying_price) * item_quantity), 0) AS profit FROM pending_payments WHERE shop_id='{$company_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$starting_date}', '%Y-%m-%d')) AS pp
                      INNER JOIN
                      (SELECT IFNULL(SUM(amount), 0) AS expense FROM expenses WHERE shop_id='{$company_id}' AND DATE_FORMAT(date_incurred, '%Y-%m-%d') = DATE_FORMAT('{$starting_date}', '%Y-%m-%d')) AS ee";

            $result1 = mysqli_query($conn,$sql1);
            if($result1) {
              $total=mysqli_fetch_assoc($result1);
              $total_cash_sales=$total['cash_sales'];
              $total_cash_profit=$total['cash_profit'];
              $total_differed_sales=$total['differed_sales'];
              $total_differed_profit=$total['differed_profit'];
              $total_expenses=$total['expenses'];
              if ($total_expenses <= 0) {
                  $total_expenses = 0;
              }
            }




            // $cash_sql="SELECT SUM(quantity * selling_price) AS cash_sales, SUM(quantity * (selling_price-buying_price)) AS cash_profit FROM sold_stock
            //             WHERE shop_id='{$company_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$starting_date}', '%Y-%m-%d')";
            // $cash_result=mysqli_query($conn,$cash_sql);
            // if($cash_result) {
            //     $total=mysqli_fetch_assoc($cash_result);
            //     $total_cash_sales=$total['cash_sales'];
            //     $total_cash_profit=$total['cash_profit'];
            // }

            // get the differed sales
            // $differed_sql="SELECT SUM(item_quantity * item_selling_price) AS differed_sales, SUM(item_quantity * (item_selling_price - buying_price)) AS differed_profit FROM pending_payments
            //                 WHERE shop_id='{$company_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$starting_date}', '%Y-%m-%d')";
            // $differed_result=mysqli_query($conn,$differed_sql);
            // if($differed_result) {
            //     $total=mysqli_fetch_assoc($differed_result);
            //     $total_differed_sales=$total['differed_sales'];
            //     $total_differed_profit=$total['differed_profit'];
            // }

            // get the expenses
            // $expenses_sql="SELECT SUM(amount) AS expenses FROM expenses
            //                 WHERE shop_id='{$company_id}' AND DATE_FORMAT(date_incurred, '%Y-%m-%d') = DATE_FORMAT('{$starting_date}', '%Y-%m-%d')";
            // $expenses_result=mysqli_query($conn,$expenses_sql);
            // if($expenses_result) {
            //     if (mysqli_num_rows($expenses_result) > 0) {
            //         $total=mysqli_fetch_assoc($expenses_result);
            //         $total_expenses=$total['expenses'];

            //         if ($total_expenses <= 0) {
            //             $total_expenses = 0;
            //         }
            //     } else {
            //         $total_expenses = 0;
            //     }

            // }






            $total_sales = $total_cash_sales + $total_differed_sales;

            $gross_profit = $total_differed_profit + $total_cash_profit;

            $net_profit = $gross_profit-$total_expenses;

            $allData[$cr]['date_of_sales'] = encrypt_data($end_date);
            $allData[$cr]['total_sales'] = encrypt_data($total_sales);
            $allData[$cr]['gross_profit'] = encrypt_data($gross_profit);
            $allData[$cr]['expenses'] = encrypt_data($total_expenses);
            $allData[$cr]['net_profit'] = encrypt_data($net_profit);

            $cr++;

            $end_date = date ("Y-m-d", strtotime("+1 days", strtotime($end_date)));
        } while (strtotime($end_date) <= strtotime($start_date));




        // get monthly sales
        $allMonthData=[];

        $cr2 = 0;

        $start_month = date ("Y-m", time());
        // $end_date = $min_date;

        $end_month = date ("Y-m", strtotime("-11 months", strtotime($start_month)));

        do {

            // get the sales from this min date to present;
            $total_cash_sales=0;
            $total_cash_profit=0;

            $total_differed_sales=0;
            $total_differed_profit=0;

            $total_expenses=0;

            $total_sales=0;

            $gross_profit=0;
            $net_profit=0;

            // get the cash sales
            // $start_date = $start_date->format('Y-m-d H:i:s');
            $starting_month=date("Y-m-d", strtotime($end_month));


            $sql1 = "SELECT IFNULL(ss.sales, 0) AS cash_sales,
                            IFNULL(ss.profit, 0) AS cash_profit,
                            IFNULL(pp.sales, 0) AS differed_sales,
                            IFNULL(pp.profit, 0) AS differed_profit,
                            IFNULL(ee.expense, 0) AS expenses
                      FROM (SELECT IFNULL(SUM(quantity*selling_price), 0) AS sales,
                                    IFNULL(SUM((selling_price - buying_price) * quantity), 0) AS profit FROM sold_stock WHERE shop_id='{$company_id}' AND DATE_FORMAT(`date_sold`, '%Y-%m') = DATE_FORMAT('{$starting_month}', '%Y-%m')) AS ss
                      INNER JOIN
                      (SELECT IFNULL(SUM(item_quantity*item_selling_price), 0) AS sales,
                                IFNULL(SUM((item_selling_price - buying_price) * item_quantity), 0) AS profit FROM pending_payments WHERE shop_id='{$company_id}' AND DATE_FORMAT(date_picked, '%Y-%m') = DATE_FORMAT('{$starting_month}', '%Y-%m')) AS pp
                      INNER JOIN
                      (SELECT IFNULL(SUM(amount), 0) AS expense FROM expenses WHERE shop_id='{$company_id}' AND DATE_FORMAT(date_incurred, '%Y-%m') = DATE_FORMAT('{$starting_month}', '%Y-%m')) AS ee";

            $result1 = mysqli_query($conn,$sql1);
            if($result1) {
              $total=mysqli_fetch_assoc($result1);
              $total_cash_sales=$total['cash_sales'];
              $total_cash_profit=$total['cash_profit'];
              $total_differed_sales=$total['differed_sales'];
              $total_differed_profit=$total['differed_profit'];
              $total_expenses=$total['expenses'];

              if ($total_expenses <= 0) {
                  $total_expenses = 0;
              }
            }






            // $cash_sql="SELECT SUM(quantity * selling_price) AS cash_sales, SUM(quantity * (selling_price-buying_price)) AS cash_profit FROM sold_stock WHERE shop_id='{$company_id}' AND DATE_FORMAT(date_sold, '%Y-%m') = DATE_FORMAT('{$starting_month}', '%Y-%m')";
            // $cash_result=mysqli_query($conn,$cash_sql);
            // if($cash_result) {
            //     $total=mysqli_fetch_assoc($cash_result);
            //     $total_cash_sales=$total['cash_sales'];
            //     $total_cash_profit=$total['cash_profit'];
            // }



            // get the differed sales
            // $differed_sql="SELECT SUM(item_quantity * item_selling_price) AS differed_sales, SUM(item_quantity * (item_selling_price - buying_price)) AS differed_profit FROM pending_payments WHERE shop_id='{$company_id}' AND DATE_FORMAT(date_picked, '%Y-%m') = DATE_FORMAT('{$starting_month}', '%Y-%m')";
            // $differed_result=mysqli_query($conn,$differed_sql);
            // if($differed_result) {
            //     $total=mysqli_fetch_assoc($differed_result);
            //     $total_differed_sales=$total['differed_sales'];
            //     $total_differed_profit=$total['differed_profit'];
            // }

            // get the expenses
            // $expenses_sql="SELECT SUM(amount) AS expenses FROM expenses WHERE shop_id='{$company_id}' AND DATE_FORMAT(date_incurred, '%Y-%m') = DATE_FORMAT('{$starting_month}', '%Y-%m')";
            // $expenses_result=mysqli_query($conn,$expenses_sql);
            // if($expenses_result) {

            //     if (mysqli_num_rows($expenses_result) > 0) {
            //         $total=mysqli_fetch_assoc($expenses_result);
            //         $total_expenses=$total['expenses'];
            //         if ($total_expenses <= 0) {
            //             $total_expenses = 0;
            //         }
            //     } else {
            //         $total_expenses = 0;
            //     }

            // }

            $total_sales = $total_cash_sales + $total_differed_sales;

            $gross_profit = $total_differed_profit + $total_cash_profit;

            $net_profit = $gross_profit-$total_expenses;

            $allMonthData[$cr2]['month_of_sales'] = encrypt_data($end_month);
            $allMonthData[$cr2]['total_sales'] = encrypt_data($total_sales);
            $allMonthData[$cr2]['gross_profit'] = encrypt_data($gross_profit);
            $allMonthData[$cr2]['expenses'] = encrypt_data($total_expenses);
            $allMonthData[$cr2]['net_profit'] = encrypt_data($net_profit);

            $cr2++;

            // $end_month = date ("Y-m-d", strtotime("+1 months", strtotime($end_month)));

            $d = new DateTime($end_month);

            $d->modify( 'first day of next month' );

            $end_month = $d->format( 'Y-m' );

        } while (strtotime($end_month) <= strtotime($start_month));



        // get top selling products
        $topProductsData=[];
        $cr3 = 0;

        $sql3="SELECT item_id, SUM(quantity) AS quantitysold, SUM(quantity * selling_price) AS totalsales FROM sold_stock WHERE shop_id='{$company_id}'
                GROUP BY item_id
                ORDER BY totalsales DESC LIMIT 11";

        $result3=mysqli_query($conn, $sql3);

        if($result3) {
            $total_records = mysqli_num_rows($result3);
            $record_count = 1;

                while($row1 = mysqli_fetch_assoc($result3)) {
                    $item_id=$row1['item_id'];
                    $total_sales = $row1['totalsales'];
                    $quantity_sold = $row1['quantitysold'];

                    $sql2="SELECT * FROM all_stock WHERE id='{$item_id}'";
                    $result2=mysqli_query($conn, $sql2);
                    if($result2) {

                        if (mysqli_num_rows($result2) > 0) {

                            while($row2=mysqli_fetch_assoc($result2)) {
                                $topProductsData[$cr3]['item_name'] = encrypt_data($row2['item_name']);
                                $topProductsData[$cr3]['imageUrl'] = encrypt_data($row2['imageUrl']);
                                $topProductsData[$cr3]['totalsales'] = encrypt_data($total_sales);
                                $topProductsData[$cr3]['quantitysold'] = encrypt_data($quantity_sold);
                                $cr3++;

                                $record_count++;
                            }
                        }
                    } else {
                        $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get item"
                        );

                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    }

                }

        } else {
            http_response_code(402);
                $data_insert=array(
                "jwt" => $jwt,
                "status" => "error22",
                "message" => $e->getMessage()
                );

                echo json_encode($data_insert);
                mysqli_close($conn);
        }

        $current_sales = get_current_sales($conn, $company_id);



        $data_insert=array(
        "WeeklySalesReports" => $allData,
        "MonthlySalesReports" => $allMonthData,
        "TopSellingProducts" => $topProductsData,
        "CurrentSales" => $current_sales,
        "status" => "success",
        "message" => "Request authorized"
        );

        echo json_encode($data_insert);
        mysqli_close($conn);

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


// get all stock
if ($action === 'get_all_stock') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $company_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql="SELECT * FROM all_stock WHERE shop_id='{$company_id}' AND deleted=0 ORDER BY id DESC";

        $result=mysqli_query($conn,$sql);

        // $user_details = array();

        $allData=[];
        $categories = [];

        if($result) {

            $cr = 0;

             while($row=mysqli_fetch_assoc($result)) {

                 //  get the category for this item
                 $category = get_category_detail($conn, $row['category_id'], $company_id);
                 $allData[$cr]['id'] = encrypt_data($row['id']);
                 $allData[$cr]['item_name'] = encrypt_data($row['item_name']);
                 $allData[$cr]['category_id'] = encrypt_data($row['category_id']);
                 $allData[$cr]['category'] = $category;
                 $allData[$cr]['quantity'] = encrypt_data($row['quantity']);
                 $allData[$cr]['buying_price'] = encrypt_data($row['buying_price']);
                 $allData[$cr]['selling_price'] = encrypt_data($row['selling_price']);
                 $allData[$cr]['date_brought'] = encrypt_data($row['date_brought']);
                 $allData[$cr]['added_by'] = encrypt_data($row['added_by']);
                 $allData[$cr]['imageUrl'] = encrypt_data($row['imageUrl']);

                 $cr++;
              }

              //  get all stock categories
              $categories = get_all_shop_stock_categories($conn, $company_id);

              $min_stock_items = get_shop_minimum_stock_items($conn, $company_id);


              $data_insert=array(
                "Stock" => $allData,
                "Categories" => $categories,
                "MinItems" => $min_stock_items,
                "status" => "success",
                "message" => "Request authorized"
              );


             echo json_encode($data_insert);
             mysqli_close($conn);

        } else {
            http_response_code(403);
                 $data_insert=array(
                 "jwt" => $jwt,
                 "status" => "error1",
                 "message" => "Could not get stock"
                 );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

// get all categories
if ($action === 'get_all_categories') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql = "SELECT * FROM stock_categories WHERE shop_id='{$shop_id}' AND deleted=0";

        $result=mysqli_query($conn, $sql);

        if ($result) {

            $categories = [];

            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {
                $categories[$cr]['id'] = encrypt_data($row['id']);
                $categories[$cr]['category_name'] = encrypt_data($row['category_name']);

                $id = $row['id'];

                // get the number of products in this category
                $category_products = get_category_products_count($conn, $id);

                $categories[$cr]['total_products'] = $category_products;

                $cr++;
            }


            $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Categories" => $categories
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
                 $data_insert=array(
                 "status" => "error",
                 "message" => "Could not get categories"
                 );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

// get all added stock
if ($action === 'find_all_shop_added_stock') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        // get the invoices
        $sql = "SELECT * FROM add_invoices WHERE shop_id='{$shop_id}' ORDER BY UNIX_TIMESTAMP(date_added) DESC";
        $result=mysqli_query($conn,$sql);


        if($result) {

            $allInvoices=[];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                $allInvoices[$cr]['id']= encrypt_data($row['id']);
                $allInvoices[$cr]['invoice_name']= encrypt_data($row['name']);
                $allInvoices[$cr]['added_by'] = get_shop_username($conn, $row['added_by']);
                $allInvoices[$cr]['date_added'] = encrypt_data($row['date_added']);
                $allInvoices[$cr]['total_items'] = get_shop_invoice_total_items_count($conn, $shop_id, $row['id']);

                $allInvoices[$cr]['invoice_items'] = get_shop_invoice_items($conn, $shop_id, $row['id']);
                $allInvoices[$cr]['invoice_payments'] = get_shop_invoice_payments($conn, $shop_id, $row['id']);



                $cr++;

            }

            //  get all stock categories
            $categories = get_all_shop_stock_categories($conn, $shop_id);

            $data_insert=array(
                 "status" => "success",
                 "message" => "success",
                 "Invoices" => $allInvoices,
                 "Categories" => $categories
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            http_response_code(403);
                 $data_insert=array(
                 "status" => "error",
                 "message" => "Something bad happened"
                 );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

// get all suppliers
if ($action === 'get_all_shop_suppliers') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql="SELECT * FROM suppliers WHERE shop_id='{$shop_id}' AND deleted=0";
        $result=mysqli_query($conn, $sql);

        if($result) {

            $suppliers = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                // get total amount supplied
                $supplier_id=$row['id'];

                $invoices = get_supplier_invoices($conn, $shop_id, $supplier_id);

                // get total credited
                $total_credited = get_supplier_payments_new($conn, $shop_id, $supplier_id);

                $suppliers[$cr]['id'] = encrypt_data($row['id']);
                $suppliers[$cr]['name'] = $row['name'];
                $suppliers[$cr]['invoices'] = $invoices['invoices'];
                $suppliers[$cr]['total_supplied'] = $invoices['total_supplier_amount'];
                $suppliers[$cr]['payments'] = $total_credited['records'];
                $suppliers[$cr]['total_credited'] = $total_credited['total_paid'];

                $cr++;

            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Suppliers" => $suppliers
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}


if ($action === 'get_all_shop_suppliers_new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql="SELECT a.id, a.name, SUM(c.total_amount) AS total_amount, d.amount_paid
                FROM
                (SELECT id, name FROM suppliers WHERE shop_id='{$shop_id}' AND deleted=0) AS a
                INNER JOIN
                (SELECT id, supplier_id FROM add_invoices WHERE shop_id='{$shop_id}') AS b
                ON a.id=b.supplier_id
                INNER JOIN
                (SELECT invoice_id, SUM(quantity_added * buying_price) AS total_amount FROM added_stock WHERE shop_id='{$shop_id}' GROUP BY invoice_id) AS c
                ON
                c.invoice_id = b.id
                INNER JOIN
                (SELECT supplier_id, SUM(amount_paid) AS amount_paid FROM invoice_payments WHERE shop_id='{$shop_id}' GROUP BY supplier_id) AS d
                ON
                d.supplier_id = a.id
                
                GROUP BY a.id";
        $result=mysqli_query($conn, $sql);

        if($result) {

            $suppliers = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                $suppliers[$cr]['id'] = encrypt_data($row['id']);
                $suppliers[$cr]['name'] = $row['name'];
                $suppliers[$cr]['total_supplied'] = $row['total_amount'];
                $suppliers[$cr]['total_credited'] = $row['amount_paid'];

                $cr++;

            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Suppliers" => $suppliers
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}


if ($action === 'get_shop_supplier_invoices') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));

        $sql="SELECT * FROM suppliers WHERE shop_id='{$shop_id}' AND id='{$supplier_id}' AND deleted=0";
        $result=mysqli_query($conn, $sql);

        if($result) {

            if(mysqli_num_rows($result) > 0) {

                $invoices = get_supplier_invoices($conn, $shop_id, $supplier_id);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "Invoices" => $invoices['invoices']
                );
    
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Supplier does not exist!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}


if ($action === 'get_shop_supplier_invoices_new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));

        $sql="SELECT * FROM suppliers WHERE shop_id='{$shop_id}' AND id='{$supplier_id}' AND deleted=0";
        $result=mysqli_query($conn, $sql);

        if($result) {

            if(mysqli_num_rows($result) > 0) {

                $invoices = get_supplier_invoices_new($conn, $shop_id, $supplier_id);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "Invoices" => $invoices['invoices']
                );
    
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Supplier does not exist!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

if ($action === 'get_shop_supplier_invoice_items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $invoice_id = mysqli_escape_string($conn, decrypt_data($request->invoice_id));

        $invoice_items = get_shop_invoice_items_new($conn, $shop_id, $invoice_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Invoices" => $invoice_items['items']
        );

        echo json_encode($data_insert);
        mysqli_close($conn);

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}



if ($action === 'get_shop_supplier_payments') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));

        $sql="SELECT * FROM suppliers WHERE shop_id='{$shop_id}' AND id='{$supplier_id}' AND deleted=0";
        $result=mysqli_query($conn, $sql);

        if($result) {

            if(mysqli_num_rows($result) > 0) {

                $payments = get_supplier_payments_new($conn, $shop_id, $supplier_id);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "Payments" => $payments
                );

                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Supplier does not exist!"
                );
    
                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}


if ($action === 'get_all_periodic_shop_suppliers') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        $sql1 = "SELECT a.id, a.name, b.added_stock FROM
                (SELECT id, name FROM suppliers WHERE shop_id='{$shop_id}') AS a
                INNER JOIN
                (SELECT DATE_FORMAT(date_added, '%Y-%m-%d') AS date_added, IFNULL(SUM(`buying_price`*quantity_added), 0) AS added_stock FROM added_stock
                    WHERE shop_id='{$shop_id}' AND
                    DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                    DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) GROUP BY date_added ORDER BY date_added ASC) AS b
                ON a.id=b.supplier_id";

        $sql="SELECT DATE_FORMAT(date_added, '%Y-%m-%d') AS date_added, IFNULL(SUM(`buying_price`*quantity_added), 0) AS added_stock FROM added_stock
                WHERE shop_id='{$shop_id}' AND
                DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                GROUP BY DATE_FORMAT(date_added, '%Y-%m-%d') ORDER BY date_added ASC";

        $result=mysqli_query($conn, $sql);

        if($result) {

            $suppliers = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                $suppliers[$cr]['date'] = $row['date_added'];
                $suppliers[$cr]['total_supplied'] = $row['added_stock'];

                $cr++;

            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Suppliers" => $suppliers
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}


if ($action === 'get_date_shop_suppliers') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        $sql = "SELECT a.id, a.name, SUM(b.added_stock) AS added_stock FROM
                (SELECT id, name FROM suppliers WHERE shop_id='{$shop_id}') AS a
                INNER JOIN
                (SELECT id, supplier_id FROM add_invoices WHERE shop_id='{$shop_id}' AND
                    DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS c
                ON a.id = c.supplier_id
                INNER JOIN
                (SELECT invoice_id, IFNULL(SUM(`buying_price`*quantity_added), 0) AS added_stock FROM added_stock
                    WHERE shop_id='{$shop_id}' AND
                    DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                    GROUP BY invoice_id
                    ORDER BY invoice_id ASC) AS b
                ON c.id=b.invoice_id
                
                GROUP BY a.id";

        $result=mysqli_query($conn, $sql);

        if($result) {

            $suppliers = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                $suppliers[$cr]['id'] = encrypt_data($row['id']);
                $suppliers[$cr]['name'] = $row['name'];
                $suppliers[$cr]['added_stock'] = $row['added_stock'];

                $cr++;

            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Suppliers" => $suppliers
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}


if ($action === 'get_date_supplier_invoices') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));


        $invoices = get_date_supplier_invoices($conn, $date, $shop_id, $supplier_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "invoices" => $invoices
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}



// get all invoices for a user
if ($action === 'get_all_today_user_invoices') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $added_by = mysqli_escape_string($conn, decrypt_data($request->uid));
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql="SELECT * FROM suppliers WHERE shop_id='{$shop_id}' AND deleted=0";
        $result=mysqli_query($conn, $sql);

        if($result) {

            $suppliers = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                // get total amount supplied
                $supplier_id=$row['id'];

                // check if user is admin to show monthly
                $user_role = get_shop_user_role($conn, $added_by);

                $invoices=null;

                $supplier_id = $row['id'];

                if($user_role === 'admin') {
                    // get monthly invoices
                    $invoices = get_current_admin_supplier_invoices($conn, $shop_id, $supplier_id);
                } else {
                    // get current invoices
                    $invoices = get_current_user_supplier_invoices($conn, $shop_id, $supplier_id, $added_by);
                }

                $suppliers[$cr]['id'] = encrypt_data($row['id']);
                $suppliers[$cr]['name'] = encrypt_data($row['name']);
                $suppliers[$cr]['invoices'] = $invoices['invoices'];
                $suppliers[$cr]['total_supplied'] = $invoices['total_supplier_amount'];

                $cr++;

            }

            // get today sales purchases
            $purchases = get_today_user_sales_purchases($conn, $shop_id, $added_by);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Suppliers" => $suppliers,
                "Purchases" => $purchases
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}



// get added stock for an invoice
if ($action === 'get-invoice-items') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;

        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $invoice_id = mysqli_escape_string($conn, decrypt_data($request->invoice_id));

        $invoice_items = get_shop_invoice_items($conn, $shop_id, $invoice_id);

        $data_insert=array(
        "Items" => $invoice_items['items'],
        "TotalAmount" => $invoice_items['total'],
        "status" => "success",
        "message" => "success"
        );

        echo json_encode($data_insert);
        mysqli_close($conn);



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

// get all cart items
if ($action === 'get-temp-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        $TODAY = date('Y-m-d h:i:s', time());

        $sql = "SELECT * FROM temp_transactions WHERE shop_id='{$shop_id}'
                    AND sold_by='{$added_by}' AND customer_id=0
                    AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')";
        $result = mysqli_query($conn, $sql);

        if ($result) {

            $cartItems = [];
            $cr=0;

            while ($row = mysqli_fetch_assoc($result)) {

                $cartItems[$cr]['id'] = encrypt_data($row['id']);
                $cartItems[$cr]['quantity'] = encrypt_data($row['quantity']);
                $cartItems[$cr]['buying_price'] = encrypt_data($row['buying_price']);
                $cartItems[$cr]['selling_price'] = encrypt_data($row['selling_price']);
                $cartItems[$cr]['item_detail'] = get_shop_item_details($conn, $shop_id, $row['item_id']);

                $cr++;
            }

            $waiting_customers  = get_waiting_customers($conn, $shop_id, $added_by);

            $items_in_print_list = get_items_in_print_list($conn, $shop_id, $added_by);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "CartItems" => $cartItems,
                "WaitingCustomers" => $waiting_customers,
                "PrintList" => $items_in_print_list
            );

            echo json_encode($data_insert);
            mysqli_close($conn);



        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Could not get cuustomer items!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn);
        }



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get-all-temp-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        $TODAY = date('Y-m-d h:i:s', time());

        $sql = "SELECT DISTINCT customer_id  FROM temp_transactions WHERE shop_id='{$shop_id}'
                    AND sold_by='{$added_by}'
                    AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')";
        $result = mysqli_query($conn, $sql);

        if ($result) {

            $cartItems = [];
            $cr=0;

            while ($row = mysqli_fetch_assoc($result)) {

                $cid = $row['customer_id'];

                $cartItems[$cr]['customer_id'] = encrypt_data($row['customer_id']);

                if ($row['customer_id'] == 0) {


                    $cartItems[$cr]['name'] = encrypt_data('Cash customer');

                    $sql2 = "SELECT * FROM temp_transactions WHERE shop_id='{$shop_id}'
                            AND sold_by='{$added_by}'
                            AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d') AND customer_id='{$cid}'";
                    $result2 = mysqli_query($conn, $sql2);
                    if($result2) {
                        $customerCartItems = [];
                        $cr2=0;
                        while($row2 = mysqli_fetch_assoc($result2)) {
                            $customerCartItems[$cr2]['id'] = encrypt_data($row2['id']);
                            $customerCartItems[$cr2]['quantity'] = encrypt_data($row2['quantity']);
                            $customerCartItems[$cr2]['buying_price'] = encrypt_data($row2['buying_price']);
                            $customerCartItems[$cr2]['selling_price'] = encrypt_data($row2['selling_price']);
                            $customerCartItems[$cr2]['item_detail'] = get_shop_item_details($conn, $shop_id, $row2['item_id']);

                            $cr2++;
                        }

                        $cartItems[$cr]['items'] = $customerCartItems;
                    }

                } else {

                    $customer_record = get_customer_record($conn, $cid, $shop_id);

                    $cartItems[$cr]['name'] = $customer_record['name'];
                    

                    $sql2 = "SELECT * FROM temp_transactions WHERE shop_id='{$shop_id}'
                            AND sold_by='{$added_by}'
                            AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d') AND customer_id='{$cid}'";
                    $result2 = mysqli_query($conn, $sql2);
                    if($result2) {
                        $customerCartItems = [];
                        $cr2=0;
                        while($row2 = mysqli_fetch_assoc($result2)) {
                            $customerCartItems[$cr2]['id'] = encrypt_data($row2['id']);
                            $customerCartItems[$cr2]['quantity'] = encrypt_data($row2['quantity']);
                            $customerCartItems[$cr2]['buying_price'] = encrypt_data($row2['buying_price']);
                            $customerCartItems[$cr2]['selling_price'] = encrypt_data($row2['selling_price']);
                            $customerCartItems[$cr2]['item_detail'] = get_shop_item_details($conn, $shop_id, $row2['item_id']);

                            $cr2++;
                        }

                        $cartItems[$cr]['items'] = $customerCartItems;
                    }

                }

                

                $cr++;
            }

            $waiting_customers  = get_waiting_customers($conn, $shop_id, $added_by);

            $items_in_print_list = get_items_in_print_list($conn, $shop_id, $added_by);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "CartItems" => $cartItems,
                "WaitingCustomers" => $waiting_customers,
                "PrintList" => $items_in_print_list
            );

            echo json_encode($data_insert);
            mysqli_close($conn);



        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Could not get cuustomer items!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn);
        }



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}





// get all cart items for a customer
if ($action === 'get-temp-transactions-customers') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer_name = mysqli_escape_string($conn, decrypt_data($request->customer_name));

        $TODAY = date('Y-m-d h:i:s', time());


        $sql2 = "SELECT * FROM temp_waiting_customers WHERE customer_name='{$customer_name}' AND shop_id='{$shop_id}' AND sold_by='{$added_by}'";
        $result2 = mysqli_query($conn, $sql2);

        if ($result2) {

            $items = [];
            $cr2=0;

            while ($row2 = mysqli_fetch_assoc($result2)) {

                $items[$cr2]['id'] = encrypt_data($row2['id']);
                $items[$cr2]['quantity'] = encrypt_data($row2['quantity']);
                $items[$cr2]['buying_price'] = encrypt_data($row2['buying_price']);
                $items[$cr2]['selling_price'] = encrypt_data($row2['selling_price']);
                $items[$cr2]['item_detail'] = get_shop_item_details($conn, $shop_id, $row2['item_id']);

                $cr2++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "CartItems" => $items
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Could not get items"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get-temp-transactions-differed') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));

        $TODAY = date('Y-m-d h:i:s', time());


        $sql2 = "SELECT * FROM temp_transactions WHERE customer_id='{$customer_id}' AND shop_id='{$shop_id}' AND sold_by='{$added_by}'";
        $result2 = mysqli_query($conn, $sql2);

        if ($result2) {

            $items = [];
            $cr2=0;

            while ($row2 = mysqli_fetch_assoc($result2)) {

                $items[$cr2]['id'] = encrypt_data($row2['id']);
                $items[$cr2]['quantity'] = encrypt_data($row2['quantity']);
                $items[$cr2]['buying_price'] = encrypt_data($row2['buying_price']);
                $items[$cr2]['selling_price'] = encrypt_data($row2['selling_price']);
                $items[$cr2]['item_detail'] = get_shop_item_details($conn, $shop_id, $row2['item_id']);

                $cr2++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "CartItems" => $items
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Could not get items"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-differed-customers') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql = "SELECT * FROM customers WHERE shop_id='{$shop_id}' AND type='differed'  AND deleted=0";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $customers = [];
            $cr = 0;

            while ($row=mysqli_fetch_assoc($result)) {

                $customers[$cr]['id'] = encrypt_data($row['id']);
                $customers[$cr]['name'] = encrypt_data($row['name']);
                $customers[$cr]['mobile_number'] = encrypt_data($row['mobile_number']);

                $id = $row['id'];

                $limit_reached = check_debtor_limit_status($conn, $id, $shop_id);


                $customers[$cr]['limit_reached'] = encrypt_data($limit_reached);

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Customers" => $customers
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        }



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get-all-differed-customers-records') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql = "SELECT a.id, a.name, a.mobile_number, b.total_amount, b.amount_paid, (b.total_amount - b.amount_paid) AS balance, b.balance_limit FROM
                (SELECT id, name, mobile_number FROM customers WHERE shop_id='{$shop_id}' AND type='differed'  AND deleted=0) AS a
                INNER JOIN
                (SELECT customer_id, total_amount, amount_paid, balance_limit FROM customer_pending_payments WHERE shop_id='{$shop_id}') AS b
                ON a.id = b.customer_id
                ORDER BY balance DESC";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $customers = [];
            $cr = 0;

            while ($row=mysqli_fetch_assoc($result)) {

                $customers[$cr]['id'] = encrypt_data($row['id']);
                $customers[$cr]['name'] = $row['name'];
                $customers[$cr]['mobile_number'] = $row['mobile_number'];
                $customers[$cr]['total_amount'] = $row['total_amount'];
                $customers[$cr]['amount_paid'] = $row['amount_paid'];
                $customers[$cr]['balance'] = $row['balance'];
                $customers[$cr]['balance_limit'] = $row['balance_limit'];

                $id = $row['id'];

                if ($row['balance'] >= $row['balance_limit']) {
                    $customers[$cr]['limit_reached'] = 1;
                } else {
                    $customers[$cr]['limit_reached'] = 0; 
                }
                

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Customers" => $customers
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        }



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

// get all today transactions for a user
if ($action === 'get-all-today-user-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));


        $cash_transactions = [];
        $cash_transactions_items = array();

        $mpesa_transactions = [];
        $mpesa_transactions_items = [];

        $bank_transactions = [];
        $bank_transactions_items = [];

        $reversed_transactions = [];
        $reversed_transactions_items = array();

        $multiple_transactions = [];
        $multiple_transactions_items = [];

        $differed_transactions = [];
        $paid_differed_transactions = [];
        $paid_differed_cash_transactions = 0;
        $paid_differed_mpesa_transactions = 0;
        $paid_differed_bank_transactions = 0;

        $transfer_transactions = [];
        $paid_transfer_transactions = [];
        $paid_transfer_cash_transactions = 0;
        $paid_transfer_mpesa_transactions = 0;
        $paid_transfer_bank_transactions = 0;

        $cr_cash=0;
        $cr_reversed=0;
        $cr_mpesa=0;
        $cr_bank=0;
        $cr_multiple=0;

        $cr_differed=0;
        $cr_paid_differed=0;


        $cr_transfer=0;
        $cr_paid_transfer=0;


        // get all today sales transactions

        $transaction_codes = get_shop_user_transactions($conn, $shop_id, $added_by);

        // get all today differed transactions
        $differed_transaction_codes = get_shop_user_differed_transactions($conn, $shop_id, $added_by);


        // get all today transfer transactions
        $transfer_transaction_codes = get_shop_user_transfer_transactions($conn, $shop_id, $added_by);

        // get reversed differed transactions
        $reversed_differed_transactions = get_reversed_differed($conn, $shop_id, $added_by);

        // get paid differed transactions
        $paid_differed_codes = get_shop_user_paid_differed_transactions($conn, $shop_id, $added_by);

        // get paid differed transactions
        $paid_transfer_codes = get_shop_user_paid_transfer_transactions($conn, $shop_id, $added_by);

        // get expenses
        $TODAY = date('Y-m-d h:i:s', time());
        $expenses = get_shop_day_user_expenses($conn, $shop_id, $TODAY, $added_by);

        if (count($transaction_codes) > 0) {

            foreach($transaction_codes as $record) {

                $code = $record['transaction_code'];

                // get the payment for this transaction
                $payment = get_transaction_code_payment($conn, $code, $shop_id, $record['date']);

                if (decrypt_data($payment['cash_amount']) > 0 || decrypt_data($payment['payment_method']) === 'cash') {

                    // get the sales transactions for this code
                    $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by, $record['date']);

                    $cash_transactions[$cr_cash]['code'] = encrypt_data($code);
                    $cash_transactions[$cr_cash]['date'] = encrypt_data($record['date']);
                    $cash_transactions[$cr_cash]['payment'] = $payment;
                    $cash_transactions[$cr_cash]['items'] = $transaction_sales;

                    array_push($cash_transactions_items,$transaction_sales);

                    // $cash_transactions_items[$cr_cash] = $transaction_sales;

                    $cr_cash++;


                }
                if (decrypt_data($payment['mpesa_amount']) > 0 || decrypt_data($payment['payment_method']) === 'mpesa') {

                    // get the sales transactions for this code
                    $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by, $record['date']);

                    $mpesa_transactions[$cr_mpesa]['code'] = encrypt_data($code);
                    $mpesa_transactions[$cr_mpesa]['payment'] = $payment;
                    $mpesa_transactions[$cr_mpesa]['items'] = $transaction_sales;

                    $mpesa_transactions_items[$cr_mpesa] = $transaction_sales;

                    $cr_mpesa++;

                }
                if (decrypt_data($payment['bank_amount']) > 0 || decrypt_data($payment['payment_method']) === 'bank') {

                    // get the sales transactions for this code
                    $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by, $record['date']);

                    $bank_transactions[$cr_bank]['code'] = encrypt_data($code);
                    $bank_transactions[$cr_bank]['payment'] = $payment;
                    $bank_transactions[$cr_bank]['items'] = $transaction_sales;

                    $bank_transactions_items[$cr_bank] = $transaction_sales;

                    $cr_bank++;
                }
                if (decrypt_data($payment['amount_reversed']) > 0) {

                    // get the sales transactions for this code
                    // $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by);

                    $returned_items = get_transaction_code_returned_stock($conn, $code, $shop_id, 'cash');

                    $reversed_transactions[$cr_reversed]['code'] = encrypt_data($code);
                    $reversed_transactions[$cr_reversed]['payment'] = $payment;
                    $reversed_transactions[$cr_reversed]['items'] = $returned_items;

                    // array_push($reversed_transactions_items, $transaction_sales);

                    // $cash_transactions_items[$cr_cash] = $transaction_sales;

                    $cr_reversed++;


                }

            }

        }

        if (count($reversed_differed_transactions) > 0) {

            foreach($reversed_differed_transactions as $record) {

                $code = $record['transaction_code'];

                $returned_items = get_transaction_code_returned_stock($conn, $code, $shop_id, 'differed');

                $reversed_transactions[$cr_reversed]['code'] = encrypt_data($code);
                $reversed_transactions[$cr_reversed]['customer'] = $record['customer'];
                $reversed_transactions[$cr_reversed]['items'] = $returned_items;

                $cr_reversed++;

                // array_push($reversed_transactions, $reversed_transaction);

            }

        }

        if (count($paid_differed_codes) > 0) {

            $pr=0;

            foreach($paid_differed_codes as $record) {

                $code = $record['transaction_code'];
                $customer_id = $record['customer_id'];
                $payment_id = $record['differed_sales_payments_id'];

                // get the customer record
                $customer = get_customer_record($conn, $customer_id, $shop_id);

                // get the payment for this transaction
                $payment = get_paid_differed_transaction_code_payment($conn, $shop_id, $payment_id);
                $paid_differed_cash_transactions += intVal(decrypt_data($payment['cash_amount']));
                $paid_differed_mpesa_transactions += intVal(decrypt_data($payment['mpesa_amount']));
                $paid_differed_bank_transactions += intVal(decrypt_data($payment['bank_amount']));

                $paid_differed_transactions[$pr]['customer'] = $customer;
                $paid_differed_transactions[$pr]['payment'] = $payment;

                $pr++;
            }

        }

        if (count($differed_transaction_codes) > 0) {

            foreach($differed_transaction_codes as $record) {
                $code = $record['transaction_code'];
                $customer = $record['customer'];

                // get the sales transactions for this code
                $transaction_items = get_differed_transaction_code_items($conn, $code, $shop_id, $record['sold_by'], $record['date']);

                $differed_transactions[$cr_differed]['code'] = encrypt_data($code);
                $differed_transactions[$cr_differed]['date'] = encrypt_data($record['date']);
                $differed_transactions[$cr_differed]['customer'] = $customer;
                $differed_transactions[$cr_differed]['items'] = $transaction_items;


                $cr_differed++;
            }

        }

       
        if (count($transfer_transaction_codes) > 0) {
            $tc = 0;
            foreach($transfer_transaction_codes as $record1) {

                $transfer_transactions[$tc]['id'] = $record1['id'];
                $transfer_transactions[$tc]['customer'] = $record1['customer'];
                $transfer_transactions[$tc]['total_transfered'] = $record1['total_transfered'];

                $customer_transactions = [];
                $tt=0;

                foreach($record1['transactions'] as $record) {
                    
                    $code = $record['transaction_code'];
                    $customer = $record['customer'];

                    // get the sales transactions for this code
                    $transaction_items = get_transfer_transaction_code_items($conn, $code, $shop_id, $record['sold_by'], $record['date']);

                    $customer_transactions[$tt]['code'] = encrypt_data($code);
                    $customer_transactions[$tt]['date'] = encrypt_data($record['date']);
                    $customer_transactions[$tt]['customer'] = $customer;
                    $customer_transactions[$tt]['items'] = $transaction_items;
                    $tt++;
                }

                $transfer_transactions[$tc]['transactions'] = $customer_transactions;
                $tc++;

            }
        }


        if (count($paid_transfer_codes) > 0) {

            $pr=0;

            foreach($paid_transfer_codes as $record) {

                $code = $record['transaction_code'];
                $customer_id = $record['customer_id'];

                // get the customer record
                $customer = get_customer_record($conn, $customer_id, $shop_id);

                // get the payment for this transaction
                if($record['payment_method'] === 'cash') {
                    $paid_transfer_cash_transactions += intVal(decrypt_data($record['amount_paid']));
                }

                if($record['payment_method'] === 'mpesa') {
                    $paid_transfer_mpesa_transactions += intVal(decrypt_data($record['amount_paid']));
                }

                if($record['payment_method'] === 'bank') {
                    $paid_transfer_bank_transactions += intVal(decrypt_data($record['amount_paid']));
                }
                

                $paid_transfer_transactions[$pr]['customer'] = $customer;
                $paid_transfer_transactions[$pr]['payment_method'] = encrypt_data($record['payment_method']);
                $paid_transfer_transactions[$pr]['balance_before'] = encrypt_data($record['balance_before']);
                $paid_transfer_transactions[$pr]['amount_paid'] = encrypt_data($record['amount_paid']);

                $pr++;
            }

        }





        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "CashTransactions" => $cash_transactions,
            "CashTransactionsItems" => $cash_transactions_items,
            "MpesaTransactions" => $mpesa_transactions,
            "MpesaTransactionsItems" => $mpesa_transactions_items,
            "BankTransactions" => $bank_transactions,
            "BankTransactionsItems" => $bank_transactions_items,
            "ReversedTransactions" => $reversed_transactions,
            "ReversedTransactionsItems" => $reversed_transactions_items,
            "MultipleTransactions" => $multiple_transactions,
            "MultipleTransactionsItems" => $multiple_transactions_items,
            "DifferedTransactions" => $differed_transactions,
            "PaidDifferedTransactions" => $paid_differed_transactions,
            "TransferTransactions" => $transfer_transactions,
            "PaidTransferTransactions" => $paid_transfer_transactions,
            "PaidTransferCash" => encrypt_data($paid_transfer_cash_transactions),
            "PaidTransferBank" => encrypt_data($paid_transfer_bank_transactions),
            "PaidTransferMpesa" => encrypt_data($paid_transfer_mpesa_transactions),
            "Expenses" => $expenses
        );

        echo json_encode($data_insert);
        mysqli_close($conn);



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-all-current-user-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));


        $cash_transactions = [];
        $cash_transactions_items = array();

        $mpesa_transactions = [];
        $mpesa_transactions_items = [];

        $bank_transactions = [];
        $bank_transactions_items = [];

        $reversed_transactions = [];
        $reversed_transactions_items = array();

        $multiple_transactions = [];
        $multiple_transactions_items = [];

        $differed_transactions = [];
        $paid_differed_transactions = [];
        $paid_differed_cash_transactions = 0;
        $paid_differed_mpesa_transactions = 0;
        $paid_differed_bank_transactions = 0;

        $transfer_transactions = [];
        $paid_transfer_transactions = [];
        $paid_transfer_cash_transactions = 0;
        $paid_transfer_mpesa_transactions = 0;
        $paid_transfer_bank_transactions = 0;

        $cr_cash=0;
        $cr_reversed=0;
        $cr_mpesa=0;
        $cr_bank=0;
        $cr_multiple=0;

        $cr_differed=0;
        $cr_paid_differed=0;


        $cr_transfer=0;
        $cr_paid_transfer=0;


        // get all today sales transactions

        $transaction_codes = get_shop_user_transactions($conn, $shop_id, $added_by);

        // get all today differed transactions
        $differed_transaction_codes = get_current_shop_user_differed_transactions($conn, $shop_id, $added_by);


        // get all today transfer transactions
        $transfer_transaction_codes = get_shop_user_transfer_transactions($conn, $shop_id, $added_by);

        // get reversed differed transactions
        $reversed_differed_transactions = get_reversed_differed($conn, $shop_id, $added_by);

        // get paid differed transactions
        $paid_differed_codes = get_shop_user_paid_differed_transactions($conn, $shop_id, $added_by);

        // get paid differed transactions
        $paid_transfer_codes = get_shop_user_paid_transfer_transactions($conn, $shop_id, $added_by);

        // get expenses
        $TODAY = date('Y-m-d h:i:s', time());
        $expenses = get_shop_day_user_expenses($conn, $shop_id, $TODAY, $added_by);

        if (count($transaction_codes) > 0) {

            foreach($transaction_codes as $record) {

                $code = $record['transaction_code'];

                // get the payment for this transaction
                $payment = get_transaction_code_payment($conn, $code, $shop_id, $record['date']);

                if (decrypt_data($payment['cash_amount']) > 0 || decrypt_data($payment['payment_method']) === 'cash') {

                    // get the sales transactions for this code
                    $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by, $record['date']);

                    $cash_transactions[$cr_cash]['code'] = encrypt_data($code);
                    $cash_transactions[$cr_cash]['date'] = encrypt_data($record['date']);
                    $cash_transactions[$cr_cash]['payment'] = $payment;
                    $cash_transactions[$cr_cash]['items'] = $transaction_sales;

                    array_push($cash_transactions_items,$transaction_sales);

                    // $cash_transactions_items[$cr_cash] = $transaction_sales;

                    $cr_cash++;


                }
                if (decrypt_data($payment['mpesa_amount']) > 0 || decrypt_data($payment['payment_method']) === 'mpesa') {

                    // get the sales transactions for this code
                    $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by, $record['date']);

                    $mpesa_transactions[$cr_mpesa]['code'] = encrypt_data($code);
                    $mpesa_transactions[$cr_mpesa]['payment'] = $payment;
                    $mpesa_transactions[$cr_mpesa]['items'] = $transaction_sales;

                    $mpesa_transactions_items[$cr_mpesa] = $transaction_sales;

                    $cr_mpesa++;

                }
                if (decrypt_data($payment['bank_amount']) > 0 || decrypt_data($payment['payment_method']) === 'bank') {

                    // get the sales transactions for this code
                    $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by, $record['date']);

                    $bank_transactions[$cr_bank]['code'] = encrypt_data($code);
                    $bank_transactions[$cr_bank]['payment'] = $payment;
                    $bank_transactions[$cr_bank]['items'] = $transaction_sales;

                    $bank_transactions_items[$cr_bank] = $transaction_sales;

                    $cr_bank++;
                }
                if (decrypt_data($payment['amount_reversed']) > 0) {

                    // get the sales transactions for this code
                    // $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by);

                    $returned_items = get_transaction_code_returned_stock($conn, $code, $shop_id, 'cash');

                    $reversed_transactions[$cr_reversed]['code'] = encrypt_data($code);
                    $reversed_transactions[$cr_reversed]['payment'] = $payment;
                    $reversed_transactions[$cr_reversed]['items'] = $returned_items;

                    // array_push($reversed_transactions_items, $transaction_sales);

                    // $cash_transactions_items[$cr_cash] = $transaction_sales;

                    $cr_reversed++;


                }

            }

        }

        if (count($reversed_differed_transactions) > 0) {

            foreach($reversed_differed_transactions as $record) {

                $code = $record['transaction_code'];

                $returned_items = get_transaction_code_returned_stock($conn, $code, $shop_id, 'differed');

                $reversed_transactions[$cr_reversed]['code'] = encrypt_data($code);
                $reversed_transactions[$cr_reversed]['customer'] = $record['customer'];
                $reversed_transactions[$cr_reversed]['items'] = $returned_items;

                $cr_reversed++;

                // array_push($reversed_transactions, $reversed_transaction);

            }

        }

        if (count($paid_differed_codes) > 0) {

            $pr=0;

            foreach($paid_differed_codes as $record) {

                $code = $record['transaction_code'];
                $customer_id = $record['customer_id'];
                $payment_id = $record['differed_sales_payments_id'];

                // get the customer record
                $customer = get_customer_record($conn, $customer_id, $shop_id);

                // get the payment for this transaction
                $payment = get_paid_differed_transaction_code_payment($conn, $shop_id, $payment_id);
                $paid_differed_cash_transactions += intVal(decrypt_data($payment['cash_amount']));
                $paid_differed_mpesa_transactions += intVal(decrypt_data($payment['mpesa_amount']));
                $paid_differed_bank_transactions += intVal(decrypt_data($payment['bank_amount']));

                $paid_differed_transactions[$pr]['customer'] = $customer;
                $paid_differed_transactions[$pr]['payment'] = $payment;

                $pr++;
            }

        }

        if (count($differed_transaction_codes) > 0) {

            foreach($differed_transaction_codes as $record) {
                $code = $record['transaction_code'];
                $customer = $record['customer'];

                // get the sales transactions for this code
                $transaction_items = get_differed_transaction_code_items($conn, $code, $shop_id, $record['sold_by'], $record['date']);

                $differed_transactions[$cr_differed]['code'] = encrypt_data($code);
                $differed_transactions[$cr_differed]['date'] = encrypt_data($record['date']);
                $differed_transactions[$cr_differed]['customer'] = $customer;
                $differed_transactions[$cr_differed]['items'] = $transaction_items;


                $cr_differed++;
            }

        }

       
        if (count($transfer_transaction_codes) > 0) {
            $tc = 0;
            foreach($transfer_transaction_codes as $record1) {

                $transfer_transactions[$tc]['id'] = $record1['id'];
                $transfer_transactions[$tc]['customer'] = $record1['customer'];
                $transfer_transactions[$tc]['total_transfered'] = $record1['total_transfered'];

                $customer_transactions = [];
                $tt=0;

                foreach($record1['transactions'] as $record) {
                    
                    $code = $record['transaction_code'];
                    $customer = $record['customer'];

                    // get the sales transactions for this code
                    $transaction_items = get_transfer_transaction_code_items($conn, $code, $shop_id, $record['sold_by'], $record['date']);

                    $customer_transactions[$tt]['code'] = encrypt_data($code);
                    $customer_transactions[$tt]['date'] = encrypt_data($record['date']);
                    $customer_transactions[$tt]['customer'] = $customer;
                    $customer_transactions[$tt]['items'] = $transaction_items;
                    $tt++;
                }

                $transfer_transactions[$tc]['transactions'] = $customer_transactions;
                $tc++;

            }
        }


        if (count($paid_transfer_codes) > 0) {

            $pr=0;

            foreach($paid_transfer_codes as $record) {

                $code = $record['transaction_code'];
                $customer_id = $record['customer_id'];

                // get the customer record
                $customer = get_customer_record($conn, $customer_id, $shop_id);

                // get the payment for this transaction
                if($record['payment_method'] === 'cash') {
                    $paid_transfer_cash_transactions += intVal(decrypt_data($record['amount_paid']));
                }

                if($record['payment_method'] === 'mpesa') {
                    $paid_transfer_mpesa_transactions += intVal(decrypt_data($record['amount_paid']));
                }

                if($record['payment_method'] === 'bank') {
                    $paid_transfer_bank_transactions += intVal(decrypt_data($record['amount_paid']));
                }
                

                $paid_transfer_transactions[$pr]['customer'] = $customer;
                $paid_transfer_transactions[$pr]['payment_method'] = encrypt_data($record['payment_method']);
                $paid_transfer_transactions[$pr]['balance_before'] = encrypt_data($record['balance_before']);
                $paid_transfer_transactions[$pr]['amount_paid'] = encrypt_data($record['amount_paid']);

                $pr++;
            }

        }





        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "CashTransactions" => $cash_transactions,
            "CashTransactionsItems" => $cash_transactions_items,
            "MpesaTransactions" => $mpesa_transactions,
            "MpesaTransactionsItems" => $mpesa_transactions_items,
            "BankTransactions" => $bank_transactions,
            "BankTransactionsItems" => $bank_transactions_items,
            "ReversedTransactions" => $reversed_transactions,
            "ReversedTransactionsItems" => $reversed_transactions_items,
            "MultipleTransactions" => $multiple_transactions,
            "MultipleTransactionsItems" => $multiple_transactions_items,
            "DifferedTransactions" => $differed_transactions,
            "PaidDifferedTransactions" => $paid_differed_transactions,
            "TransferTransactions" => $transfer_transactions,
            "PaidTransferTransactions" => $paid_transfer_transactions,
            "PaidTransferCash" => encrypt_data($paid_transfer_cash_transactions),
            "PaidTransferBank" => encrypt_data($paid_transfer_bank_transactions),
            "PaidTransferMpesa" => encrypt_data($paid_transfer_mpesa_transactions),
            "Expenses" => $expenses
        );

        echo json_encode($data_insert);
        mysqli_close($conn);



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

// get all today transactions for a user
if ($action === 'get-all-today-user-admin-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));


        $cash_transactions = [];
        $cash_transactions_items = array();

        $mpesa_transactions = [];
        $mpesa_transactions_items = [];

        $bank_transactions = [];
        $bank_transactions_items = [];

        $reversed_transactions = [];
        $reversed_transactions_items = array();

        $multiple_transactions = [];
        $multiple_transactions_items = [];

        $differed_transactions = [];
        $paid_differed_transactions = [];
        $paid_differed_cash_transactions = 0;
        $paid_differed_mpesa_transactions = 0;
        $paid_differed_bank_transactions = 0;


        $transfer_transactions = [];
        $paid_transfer_transactions = [];
        $paid_transfer_cash_transactions = 0;
        $paid_transfer_mpesa_transactions = 0;
        $paid_transfer_bank_transactions = 0;

        $cr_cash=0;
        $cr_reversed=0;
        $cr_mpesa=0;
        $cr_bank=0;
        $cr_multiple=0;

        $cr_differed=0;
        $cr_paid_differed=0;

        
        $cr_transfer=0;
        $cr_paid_transfer=0;


        $tc = 0;


        // get all today sales transactions
        $transaction_codes = get_shop_user_transactions($conn, $shop_id, $added_by);

        // get all today differed transactions
        $differed_transaction_codes = get_shop_user_admin_differed_transactions($conn, $shop_id, $added_by);

         // get all today transfer transactions
        $transfer_transaction_codes = get_shop_user_transfer_transactions($conn, $shop_id, $added_by);


        // get reversed differed transactions
        $reversed_differed_transactions = get_admin_reversed_differed($conn, $shop_id, $added_by);

        // get paid differed transactions
        $paid_differed_codes = get_shop_user_paid_differed_transactions($conn, $shop_id, $added_by);

        
        // get paid transfer transactions
        $paid_transfer_codes = get_shop_user_paid_transfer_transactions($conn, $shop_id, $added_by);

        // get expenses
        $TODAY = date('Y-m-d h:i:s', time());
        $expenses = get_shop_day_user_expenses($conn, $shop_id, $TODAY, $added_by);

        if (count($transaction_codes) > 0) {

            foreach($transaction_codes as $record) {

                $code = $record['transaction_code'];

                // get the payment for this transaction
                $payment = get_transaction_code_payment($conn, $code, $shop_id, $record['date']);

                if (decrypt_data($payment['cash_amount']) > 0) {

                    // get the sales transactions for this code
                    $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by, $record['date']);

                    $cash_transactions[$cr_cash]['code'] = encrypt_data($code);
                    $cash_transactions[$cr_cash]['payment'] = $payment;
                    $cash_transactions[$cr_cash]['items'] = $transaction_sales;

                    array_push($cash_transactions_items,$transaction_sales);

                    // $cash_transactions_items[$cr_cash] = $transaction_sales;

                    $cr_cash++;


                }
                if (decrypt_data($payment['mpesa_amount']) > 0) {

                    // get the sales transactions for this code
                    $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by, $record['date']);

                    $mpesa_transactions[$cr_mpesa]['code'] = encrypt_data($code);
                    $mpesa_transactions[$cr_mpesa]['payment'] = $payment;
                    $mpesa_transactions[$cr_mpesa]['items'] = $transaction_sales;

                    $mpesa_transactions_items[$cr_mpesa] = $transaction_sales;

                    $cr_mpesa++;

                }
                if (decrypt_data($payment['bank_amount']) > 0) {

                    // get the sales transactions for this code
                    $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by, $record['date']);

                    $bank_transactions[$cr_bank]['code'] = encrypt_data($code);
                    $bank_transactions[$cr_bank]['payment'] = $payment;
                    $bank_transactions[$cr_bank]['items'] = $transaction_sales;

                    $bank_transactions_items[$cr_bank] = $transaction_sales;

                    $cr_bank++;
                }
                if (decrypt_data($payment['amount_reversed']) > 0) {

                    // get the sales transactions for this code
                    // $transaction_sales = get_transaction_code_sales($conn, $code, $shop_id, $added_by);

                    $returned_items = get_transaction_code_returned_stock($conn, $code, $shop_id, 'cash');

                    $reversed_transactions[$cr_reversed]['code'] = encrypt_data($code);
                    $reversed_transactions[$cr_reversed]['payment'] = $payment;
                    $reversed_transactions[$cr_reversed]['items'] = $returned_items;

                    // array_push($reversed_transactions_items, $transaction_sales);

                    // $cash_transactions_items[$cr_cash] = $transaction_sales;

                    $cr_reversed++;


                }

            }

        }

        if (count($reversed_differed_transactions) > 0) {

            foreach($reversed_differed_transactions as $record) {

                $code = $record['transaction_code'];

                $returned_items = get_transaction_code_returned_stock($conn, $code, $shop_id, 'differed');

                $reversed_transactions[$cr_reversed]['code'] = encrypt_data($code);
                $reversed_transactions[$cr_reversed]['customer'] = $record['customer'];
                $reversed_transactions[$cr_reversed]['items'] = $returned_items;

                $cr_reversed++;

                // array_push($reversed_transactions, $reversed_transaction);

            }

        }

        if (count($paid_differed_codes) > 0) {

            $pr=0;

            foreach($paid_differed_codes as $record) {

                $code = $record['transaction_code'];
                $customer_id = $record['customer_id'];
                $payment_id = $record['differed_sales_payments_id'];

                // get the customer record
                $customer = get_customer_record($conn, $customer_id, $shop_id);

                // get the payment for this transaction
                $payment = get_paid_differed_transaction_code_payment($conn, $shop_id, $payment_id);
                $paid_differed_cash_transactions += intVal(decrypt_data($payment['cash_amount']));
                $paid_differed_mpesa_transactions += intVal(decrypt_data($payment['mpesa_amount']));
                $paid_differed_bank_transactions += intVal(decrypt_data($payment['bank_amount']));

                $paid_differed_transactions[$pr]['customer'] = $customer;
                $paid_differed_transactions[$pr]['payment'] = $payment;

                $pr++;
            }

        }

        if (count($differed_transaction_codes) > 0) {

            foreach($differed_transaction_codes as $record) {
                $code = $record['transaction_code'];
                $customer = $record['customer'];

                // get the sales transactions for this code
                $transaction_items = get_differed_transaction_code_items($conn, $code, $shop_id, $added_by, $record['date']);

                $differed_transactions[$cr_differed]['code'] = encrypt_data($code);
                $differed_transactions[$cr_differed]['customer'] = $customer;
                $differed_transactions[$cr_differed]['items'] = $transaction_items;


                $cr_differed++;
            }

        }


        if (count($transfer_transaction_codes) > 0) {

            foreach($transfer_transaction_codes as $record1) {

                $transfer_transactions[$tc]['id'] = $record1['id'];
                $transfer_transactions[$tc]['customer'] = $record1['customer'];
                $transfer_transactions[$tc]['total_transfered'] = $record1['total_transfered'];

                $customer_transactions = [];
                $tt=0;

                foreach($record1['transactions'] as $record) {
                    
                    $code = $record['transaction_code'];
                    $customer = $record['customer'];

                    // get the sales transactions for this code
                    $transaction_items = get_transfer_transaction_code_items($conn, $code, $shop_id, $record['sold_by'], $record['date']);

                    $customer_transactions[$tt]['code'] = encrypt_data($code);
                    $customer_transactions[$tt]['date'] = encrypt_data($record['date']);
                    $customer_transactions[$tt]['customer'] = $customer;
                    $customer_transactions[$tt]['items'] = $transaction_items;
                    $tt++;
                }

                $transfer_transactions[$tc]['transactions'] = $customer_transactions;
                $tc++;

            }

        }


        if (count($paid_transfer_codes) > 0) {

            $pr=0;

            foreach($paid_transfer_codes as $record) {

                $code = $record['transaction_code'];
                $customer_id = $record['customer_id'];

                // get the customer record
                $customer = get_customer_record($conn, $customer_id, $shop_id);

                // get the payment for this transaction
                if($record['payment_method'] === 'cash') {
                    $paid_transfer_cash_transactions += intVal($record['amount_paid']);
                }

                if($record['payment_method'] === 'mpesa') {
                    $paid_transfer_mpesa_transactions += intVal($record['amount_paid']);
                }

                if($record['payment_method'] === 'bank') {
                    $paid_transfer_bank_transactions += intVal($record['amount_paid']);
                }
                

                $paid_transfer_transactions[$pr]['customer'] = $customer;
                $paid_transfer_transactions[$pr]['payment_method'] = encrypt_data($record['payment_method']);
                $paid_transfer_transactions[$pr]['balance_before'] = encrypt_data($record['balance_before']);
                $paid_transfer_transactions[$pr]['amount_paid'] = encrypt_data($record['amount_paid']);

                $pr++;
            }

        }





        $data_insert=array(
            "status" => "success",
            "message" => "success22",
            "CashTransactions" => $cash_transactions,
            "CashTransactionsItems" => $cash_transactions_items,
            "MpesaTransactions" => $mpesa_transactions,
            "MpesaTransactionsItems" => $mpesa_transactions_items,
            "BankTransactions" => $bank_transactions,
            "BankTransactionsItems" => $bank_transactions_items,
            "ReversedTransactions" => $reversed_transactions,
            "ReversedTransactionsItems" => $reversed_transactions_items,
            "MultipleTransactions" => $multiple_transactions,
            "MultipleTransactionsItems" => $multiple_transactions_items,
            "DifferedTransactions" => $differed_transactions,
            "PaidDifferedTransactions" => $paid_differed_transactions,
            "TransferTransactions" => $transfer_transactions,
            "PaidTransferTransactions" => $paid_transfer_transactions,
            "PaidTransferCash" => encrypt_data($paid_transfer_cash_transactions),
            "PaidTransferBank" => encrypt_data($paid_transfer_bank_transactions),
            "PaidTransferMpesa" => encrypt_data($paid_transfer_mpesa_transactions),
            "Expenses" => $expenses,
            "Tcodes" => $transfer_transaction_codes
        );

        echo json_encode($data_insert);
        mysqli_close($conn);



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

// PENDING CUSTOMERS
if ($action === 'get-all-debtor-records') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        $all_debtor_records=[];
        $cr=0;


        $debtors = get_all_shop_debtors($conn, $shop_id);

        foreach($debtors as $debtor) {
            // get the total and balance records for this debtor
            $debtor_total_amounts = 0;
            $debtor_payment_records = [];
            $debtor_picked_items = [];

            $debtor_record['customer'] = $debtor['debtor'];
            $debtor_record['total_amount'] = $debtor['debtor_total_amounts'];
            // $debtor_record['payment_records'] = $debtor_payment_records;
            // $debtor_record['picked_items'] = $debtor_picked_items;
            array_push($all_debtor_records, $debtor_record);
        }


        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Debtors" => $all_debtor_records
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}



// PENDING CUSTOMERS
if ($action === 'get-all-transfer-customer-records') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        $all_debtor_records=[];
        $cr=0;


        $debtors = get_all_shop_transfer_customers($conn, $shop_id);

        foreach($debtors as $debtor) {
            // get the total and balance records for this debtor
            $debtor_total_amounts = 0;
            $debtor_payment_records = [];
            $debtor_picked_items = [];

            $debtor_record['customer'] = $debtor['debtor'];
            $debtor_record['total_amount'] = $debtor['debtor_total_amounts'];
            // $debtor_record['payment_records'] = $debtor_payment_records;
            // $debtor_record['picked_items'] = $debtor_picked_items;
            array_push($all_debtor_records, $debtor_record);
        }


        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Debtors" => $all_debtor_records
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}




if ($action === 'get-transfer-customer-payment-records') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));


        $debtor_payment_records = get_all_transfer_payment_records($conn, $customer_id, $shop_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Records" => $debtor_payment_records
        );

        echo json_encode($data_insert);
        mysqli_close($conn);

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}



if ($action === 'get-transfer-customer-payment-records_new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));


        $debtor_payment_records = get_all_transfer_payment_records_new($conn, $customer_id, $shop_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Records" => $debtor_payment_records
        );

        echo json_encode($data_insert);
        mysqli_close($conn);

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}


if ($action === 'get-debtor-payment-records') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));


        $debtor_payment_records = get_all_debtor_payment_records($conn, $customer_id, $shop_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Records" => $debtor_payment_records
        );

        echo json_encode($data_insert);
        mysqli_close($conn);



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}



if ($action === 'get-debtor-payment-records-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));


        $debtor_payment_records = get_all_debtor_payment_records_new($conn, $customer_id, $shop_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Records" => $debtor_payment_records
        );

        echo json_encode($data_insert);
        mysqli_close($conn);



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

if ($action === 'get-debtor-sales') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
    
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));


        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));


        $debtor_sales = get_debtor_sales($conn, $customer_id, $shop_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Records" => $debtor_sales
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

if ($action === 'get-debtor-date-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
    
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));


        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $debtor_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        $date_records = get_debtor_date_transaction_codes($conn, $debtor_id, $shop_id, $date);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Records" => $date_records
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}







if ($action === 'get-debtor-picked-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));

        $debtor_picked_items = get_all_debtor_picked_items($conn, $customer_id, $shop_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Records" => $debtor_picked_items
        );

        echo json_encode($data_insert);
        mysqli_close($conn);



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}



if ($action === 'get-transfer-picked-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));

        $debtor_picked_items = get_all_transfer_picked_items($conn, $customer_id, $shop_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Records" => $debtor_picked_items
        );

        echo json_encode($data_insert);
        mysqli_close($conn);



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

if ($action === 'get-debtor-date-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));


        $transaction_codes = get_debtor_date_transaction_codes($conn, $customer_id, $shop_id, $date);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Records" => $transaction_codes
        );

        echo json_encode($data_insert);
        mysqli_close($conn);




    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

if ($action === 'get-transfer-customer-date-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));


        $transaction_codes = get_transfer_customer_date_transaction_codes($conn, $customer_id, $shop_id, $date);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Records" => $transaction_codes
        );

        echo json_encode($data_insert);
        mysqli_close($conn);




    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}









// get sold dates
if ($action === 'get-shop-sold-dates') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));


        // get distinct dates
        $distinct_sold_dates = get_sold_dates($conn, $shop_id);


        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Dates" => $distinct_sold_dates
        );

        echo json_encode($data_insert);
        mysqli_close($conn);



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-date-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        $date_reversed_sales = array();
        $date_recovered_sales = array();

        // get cash sales
        $date_cash_sales = get_date_cash_sales($conn, $shop_id, $date);
        $cash_sales = $date_cash_sales['sales'];
        array_push($date_reversed_sales, $date_cash_sales['reversed']);

        // get differed sales
        $date_differed_sales = get_date_differed_sales($conn, $shop_id, $date);
        $differed_sales = $date_differed_sales['sales'];
        array_push($date_reversed_sales, $date_differed_sales['reversed']);

        // get any paid debtors
        $date_paid_debtors = get_date_paid_debtors($conn, $shop_id, $date);

        // get any transfers
        $date_transfers = get_date_transfers2($conn, $shop_id, $date);

        // get any recovered sales
        $recovered_sales = get_date_recovered_sales($conn, $shop_id, $date);

        // get the expenses
        $expenses = get_shop_day_expenses($conn, $shop_id, $date);

        // get sales purchases
        $purchases = get_date_sales_purchases($conn, $shop_id, $date);

        $added_stock = get_date_suppliers($conn, $shop_id, $date);



        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "CashSales" => $cash_sales,
            "DebtorSales" => $differed_sales,
            "ReversedSales" => $date_reversed_sales,
            "PaidDebtorSales" => $date_paid_debtors,
            "Transfers" => $date_transfers,
            "Recovered" => $recovered_sales,
            "Expenses" => $expenses,
            "Purchases" => $purchases,
            "AddedStock" => $added_stock
        );

        echo json_encode($data_insert);
        mysqli_close($conn);




    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get-date-sales_new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);
  
    try {
  
      JWT::$leeway = 10;
      $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));
  
      // Access is granted. Add code of the operation here
      $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
      $date = mysqli_escape_string($conn, decrypt_data($request->date));
  
      $sql = "SELECT  IFNULL(b.sales, 0) AS debtor_sales, IFNULL(c.transfers, 0) AS transfers,
            IFNULL(a.sales + b.sales, 0) AS gross_sales, IFNULL((a.profit + b.profit), 0)
            AS total_profit, IFNULL((a1.recovered + b1.recovered), 0) AS recovered,
            IFNULL(a2.mpesa_sales, 0) AS mpesa_sales,
            IFNULL(a2.cash_sales, 0) AS cash_sales,
            IFNULL(a2.bank_sales, 0) AS bank_sales,
            IFNULL(a2.discounts, 0) AS discounts,
            IFNULL(e.paid_debtors, 0) AS paid_debtors,
            IFNULL(f.reversed_cash, 0) AS reversed_cash,
            IFNULL(g.reversed_debtor, 0) AS reversed_debtor,
            IFNULL(h.expenses, 0) AS expenses,
            IFNULL(i.purchases, 0) AS purchases
  
      FROM
  
      (
          (SELECT IFNULL(SUM(`selling_price`*`quantity`), 0) AS sales, IFNULL(SUM(quantity * (selling_price-buying_price)), 0) AS profit FROM `sold_stock` WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS a
          INNER JOIN
  
          (SELECT IFNULL(SUM(`item_selling_price`*`item_quantity`), 0) AS sales, IFNULL(SUM(item_quantity * (item_selling_price-buying_price)), 0) AS profit FROM `pending_payments` WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS b
  
          INNER JOIN
  
          (SELECT IFNULL(SUM(`selling_price`*`quantity`), 0) AS recovered FROM `sold_stock` WHERE shop_id='{$shop_id}' AND buying_price=selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS a1
  
          INNER JOIN
  
          (SELECT IFNULL(SUM(`item_selling_price`*`item_quantity`), 0) AS recovered FROM `pending_payments` WHERE shop_id='{$shop_id}' AND buying_price=item_selling_price AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS b1
  
          INNER JOIN
  
          (SELECT IFNULL(SUM(cash), 0) AS cash_sales, IFNULL(SUM(mpesa), 0) AS mpesa_sales, IFNULL(SUM(bank), 0) AS bank_sales, IFNULL(SUM(discount), 0) AS discounts FROM `sales_payments` WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_paid,'%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS a2
  
          INNER JOIN
  
          (SELECT IFNULL(SUM(quantity * buying_price), 0) AS transfers FROM transfer_goods WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_transfered,'%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS c
  
          INNER JOIN
  
          (SELECT IFNULL(SUM(amount_paid), 0) AS paid_debtors FROM differed_payments WHERE shop_id='{$shop_id}' AND
              DATE_FORMAT(date_paid,'%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS e
  
          INNER JOIN
  
          (SELECT IFNULL(SUM(quantity_returned * item_selling_price), 0) AS reversed_cash FROM returned_stock WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_returned,'%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') AND transaction_code NOT LIKE '%D%' AND transaction_code NOT LIKE '%TT%') AS f
  
          INNER JOIN
  
          (SELECT IFNULL(SUM(quantity_returned * item_selling_price), 0) AS reversed_debtor FROM returned_stock WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_returned,'%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d') AND transaction_code LIKE '%D%') AS g
  
          INNER JOIN
  
          (SELECT IFNULL(SUM(amount), 0) AS expenses FROM expenses WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_incurred,'%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS h
  
          INNER JOIN
  
          (SELECT IFNULL(SUM(quantity * buying_price), 0) AS purchases FROM sales_purchases WHERE shop_id='{$shop_id}'
              AND DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')) AS i
  
      )";
  
  
      $result = mysqli_query($conn, $sql);
  
      if ($result) {
  
        $record = mysqli_fetch_assoc($result);
  
        $cash_sales = encrypt_data($record['cash_sales']);
        $mpesa_sales = encrypt_data($record['mpesa_sales']);
        $bank_sales = encrypt_data($record['bank_sales']);
        $differed_sales = encrypt_data($record['debtor_sales']);
        $date_reversed_sales_cash = encrypt_data($record['reversed_cash']);
        $date_reversed_sales_debtor = encrypt_data($record['reversed_debtor']);
        $date_paid_debtors = encrypt_data($record['paid_debtors']);
        $date_transfers = encrypt_data($record['transfers']);
        $recovered_sales = encrypt_data($record['recovered']);
        $expenses = encrypt_data($record['expenses']);
        $purchases = encrypt_data($record['purchases']);
        $discounts = encrypt_data($record['discounts']);
  
        $gross_sales = encrypt_data($record['gross_sales']);
        $net_sales = encrypt_data($record['gross_sales'] - $record['expenses']);
        $gross_profit = encrypt_data($record['total_profit']);
        $net_profit = encrypt_data($record['total_profit'] - $record['expenses']);
  
  
  
  
        $data_insert=array(
          "status" => "success",
          "message" => "success",
          "CashSales" => $cash_sales,
          "MpesaSales" => $mpesa_sales,
          "BankSales" => $bank_sales,
          "DebtorSales" => $differed_sales,
          "ReversedSalesCash" => $date_reversed_sales_cash,
          "ReversedSalesDiffered" => $date_reversed_sales_debtor,
          "PaidDebtorSales" => $date_paid_debtors,
          "Transfers" => $date_transfers,
          "Recovered" => $recovered_sales,
          "Expenses" => $expenses,
          "Purchases" => $purchases,
          "Discounts" => $discounts,
          "GrossSales" => $gross_sales,
          "NetSales" => $net_sales,
          "GrossProfit" => $gross_profit,
          "NetProfit" => $net_profit,
        );
  
        echo json_encode($data_insert);
        mysqli_close($conn);
  
  
      } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened!"
        );
  
        echo json_encode($data_insert);
        mysqli_close($conn);
      }
  
  
  
  
  
    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );
  
        echo json_encode($data_insert);
        mysqli_close($conn);
    }
  
}






if ($action === 'get-date-cash-sales') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));


    $sql = "SELECT users.username, sold_stock.transaction_id, sold_stock.transaction_code, SUM(sold_stock.quantity * sold_stock.selling_price) AS total, sales_payments.cash, sales_payments.discount, sales_payments.amount_reversed
              FROM sales_payments
            INNER JOIN sold_stock
            ON sold_stock.transaction_code = sales_payments.transaction_code
            AND sold_stock.shop_id='{$shop_id}' AND sales_payments.shop_id='{$shop_id}'
            AND DATE_FORMAT(sold_stock.date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            AND sales_payments.cash > 0
            INNER JOIN users ON sold_stock.sold_by = users.id
            GROUP BY users.username, sold_stock.transaction_id, sold_stock.transaction_code, sales_payments.cash, sales_payments.discount, sales_payments.amount_reversed
            ORDER BY sold_stock.transaction_id DESC";

    $result = mysqli_query($conn, $sql);

    if ($result) {

      $sales = [];
      $cr=0;
      while ($record = mysqli_fetch_assoc($result)) {

        $sales[$cr]['receipt_no'] = encrypt_data($record['transaction_code']);
        $sales[$cr]['receipt_amount'] = encrypt_data($record['total']);
        $sales[$cr]['receipt_cash'] = encrypt_data($record['cash']);
        $sales[$cr]['receipt_discount'] = encrypt_data($record['discount']);
        $sales[$cr]['receipt_reversed'] = encrypt_data($record['amount_reversed']);
        $sales[$cr]['receipt_items'] = get_transaction_code_items($conn, $record['transaction_code'], $shop_id);
        $sales[$cr]['sold_by'] = encrypt_data($record['username']);

        $cr++;

      }




      $data_insert=array(
          "status" => "success",
          "message" => "success",
          "sales" => $sales
      );

      echo json_encode($data_insert);
      mysqli_close($conn);

    } else {
      $data_insert=array(
          "status" => "error",
          "message" => "Something bad happened"
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
    }

  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}


if ($action === 'get-date-mpesa-sales') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));


    $sql = "SELECT users.username, sold_stock.transaction_id, sold_stock.transaction_code, SUM(sold_stock.quantity * sold_stock.selling_price) AS total, sales_payments.mpesa, sales_payments.discount, sales_payments.amount_reversed
              FROM sales_payments
            INNER JOIN sold_stock
            ON sold_stock.transaction_code = sales_payments.transaction_code
            AND sold_stock.shop_id='{$shop_id}' = sales_payments.shop_id='{$shop_id}'
            AND DATE_FORMAT(sold_stock.date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            AND sales_payments.mpesa > 0
            INNER JOIN users ON sold_stock.sold_by = users.id
            GROUP BY users.username, sold_stock.transaction_id, sold_stock.transaction_code, sales_payments.mpesa, sales_payments.discount, sales_payments.amount_reversed
            ORDER BY sold_stock.transaction_id DESC";

    $result = mysqli_query($conn, $sql);

    if ($result) {

      $sales = [];
      $cr=0;
      while ($record = mysqli_fetch_assoc($result)) {

        $sales[$cr]['receipt_no'] = encrypt_data($record['transaction_code']);
        $sales[$cr]['receipt_amount'] = encrypt_data($record['total']);
        $sales[$cr]['receipt_cash'] = encrypt_data($record['mpesa']);
        $sales[$cr]['receipt_discount'] = encrypt_data($record['discount']);
        $sales[$cr]['receipt_reversed'] = encrypt_data($record['amount_reversed']);
        $sales[$cr]['receipt_items'] = get_transaction_code_items($conn, $record['transaction_code'], $shop_id);
        $sales[$cr]['sold_by'] = encrypt_data($record['username']);

        $cr++;

      }




      $data_insert=array(
          "status" => "success",
          "message" => "success",
          "sales" => $sales
      );

      echo json_encode($data_insert);
      mysqli_close($conn);

    } else {
      $data_insert=array(
          "status" => "error",
          "message" => "Something bad happened"
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
    }

  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}


if ($action === 'get-date-bank-sales') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));


    $sql = "SELECT users.username, sold_stock.transaction_id, sold_stock.transaction_code, SUM(sold_stock.quantity * sold_stock.selling_price) AS total, sales_payments.bank, sales_payments.discount, sales_payments.amount_reversed
              FROM sales_payments
            INNER JOIN sold_stock
            ON sold_stock.transaction_code = sales_payments.transaction_code
            AND sold_stock.shop_id='{$shop_id}' = sales_payments.shop_id='{$shop_id}'
            AND DATE_FORMAT(sold_stock.date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            AND sales_payments.bank > 0
            INNER JOIN users ON sold_stock.sold_by = users.id
            GROUP BY users.username, sold_stock.transaction_id, sold_stock.transaction_code, sales_payments.bank, sales_payments.discount, sales_payments.amount_reversed
            ORDER BY sold_stock.transaction_id DESC";

    $result = mysqli_query($conn, $sql);

    if ($result) {

      $sales = [];
      $cr=0;
      while ($record = mysqli_fetch_assoc($result)) {

        $sales[$cr]['receipt_no'] = encrypt_data($record['transaction_code']);
        $sales[$cr]['receipt_amount'] = encrypt_data($record['total']);
        $sales[$cr]['receipt_cash'] = encrypt_data($record['bank']);
        $sales[$cr]['receipt_discount'] = encrypt_data($record['discount']);
        $sales[$cr]['receipt_reversed'] = encrypt_data($record['amount_reversed']);
        $sales[$cr]['receipt_items'] = get_transaction_code_items($conn, $record['transaction_code'], $shop_id);
        $sales[$cr]['sold_by'] = encrypt_data($record['username']);

        $cr++;

      }

      $data_insert=array(
          "status" => "success",
          "message" => "success",
          "sales" => $sales
      );

      echo json_encode($data_insert);
      mysqli_close($conn);


    } else {
      $data_insert=array(
          "status" => "error",
          "message" => "Something bad happened"
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
    }

  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}



if ($action === 'get-date-discounted-sales') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));


    $sql = "SELECT users.username, sold_stock.transaction_id, sold_stock.transaction_code, SUM(sold_stock.quantity * sold_stock.selling_price) AS total, sales_payments.discount, sales_payments.amount_reversed
              FROM sales_payments
            INNER JOIN sold_stock
            ON sold_stock.transaction_code = sales_payments.transaction_code
            AND sold_stock.shop_id='{$shop_id}' = sales_payments.shop_id='{$shop_id}'
            AND DATE_FORMAT(sold_stock.date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
            AND sales_payments.discount > 0
            INNER JOIN users ON sold_stock.sold_by = users.id
            GROUP BY users.username, sold_stock.transaction_id, sold_stock.transaction_code, sales_payments.bank, sales_payments.discount, sales_payments.amount_reversed
            ORDER BY sold_stock.transaction_id DESC";

    $result = mysqli_query($conn, $sql);

    if ($result) {

      $sales = [];
      $cr=0;
      while ($record = mysqli_fetch_assoc($result)) {

        $sales[$cr]['receipt_no'] = encrypt_data($record['transaction_code']);
        $sales[$cr]['receipt_amount'] = encrypt_data($record['total']);
        $sales[$cr]['receipt_discount'] = encrypt_data($record['discount']);
        $sales[$cr]['receipt_reversed'] = encrypt_data($record['amount_reversed']);
        $sales[$cr]['receipt_items'] = get_transaction_code_items($conn, $record['transaction_code'], $shop_id);
        $sales[$cr]['sold_by'] = encrypt_data($record['username']);

        $cr++;

      }

      $data_insert=array(
          "status" => "success",
          "message" => "success",
          "sales" => $sales
      );

      echo json_encode($data_insert);
      mysqli_close($conn);


    } else {
      $data_insert=array(
          "status" => "error",
          "message" => "Something bad happened"
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
    }

  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}


if ($action === 'get-date-reversed-sales') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));


    $sql = "SELECT users.username,
                    customers.name,
                    all_stock.item_name,
                    returned_stock.shop_id,
                    returned_stock.sold_by,
                    returned_stock.date_returned,
                    returned_stock.quantity_returned,
                    returned_stock.item_selling_price,
                    returned_stock.transaction_code

                    FROM returned_stock

                    INNER JOIN all_stock
                    ON returned_stock.item_id = all_stock.id AND all_stock.shop_id='{$shop_id}' AND returned_stock.shop_id='{$shop_id}'
                      AND DATE_FORMAT(returned_stock.date_returned, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')

                    INNER JOIN customers
                    ON (customers.id = returned_stock.customer_id AND customers.shop_id='{$shop_id}' AND returned_stock.shop_id='{$shop_id}')
                        OR (returned_stock.customer_id=0 AND customers.mobile_number=0)
                      AND DATE_FORMAT(returned_stock.date_returned, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')

                    INNER JOIN users
                    ON returned_stock.sold_by = users.id AND returned_stock.shop_id='{$shop_id}'

                    WHERE DATE_FORMAT(returned_stock.date_returned, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')

                    ORDER BY returned_stock.date_returned DESC";

    $result = mysqli_query($conn, $sql);

    if ($result) {

      $sales = [];
      $cr=0;
      while ($record = mysqli_fetch_assoc($result)) {

        $sales[$cr]['receipt_no'] = encrypt_data($record['transaction_code']);
        $sales[$cr]['item_name'] = encrypt_data($record['item_name']);
        $sales[$cr]['quantity_returned'] = encrypt_data($record['quantity_returned']);
        $sales[$cr]['item_selling_price'] = encrypt_data($record['item_selling_price']);
        $sales[$cr]['customer'] = encrypt_data($record['name']);
        $sales[$cr]['sold_by'] = encrypt_data($record['username']);

        $cr++;

      }

      $data_insert=array(
          "status" => "success",
          "message" => "success",
          "sales" => $sales
      );

      echo json_encode($data_insert);
      mysqli_close($conn);


    } else {
      $data_insert=array(
          "status" => "error",
          "message" => "Something bad happened"
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
    }

  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}

if ($action === 'get-date-debtor-sales') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));


    $sql = "SELECT users.username AS seller, customers.name AS customer, pending_payments.transaction_id, pending_payments.transaction_code, SUM(pending_payments.item_quantity * pending_payments.item_selling_price) AS total
              FROM pending_payments
            INNER JOIN customers
            ON pending_payments.customer_id = customers.id AND pending_payments.shop_id='{$shop_id}' AND customers.shop_id='{$shop_id}'
                AND DATE_FORMAT(pending_payments.date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')

            INNER JOIN users
            ON pending_payments.sold_by = users.id
            GROUP BY users.username, customers.name, pending_payments.transaction_id, pending_payments.transaction_code

            ORDER BY pending_payments.transaction_id DESC";

    $result = mysqli_query($conn, $sql);

    if ($result) {

      $sales = [];
      $cr=0;
      while ($record = mysqli_fetch_assoc($result)) {

        $sales[$cr]['receipt_no'] = encrypt_data($record['transaction_code']);
        $sales[$cr]['receipt_amount'] = encrypt_data($record['total']);
        $sales[$cr]['receipt_customer'] = encrypt_data($record['customer']);
        $sales[$cr]['sold_by'] = encrypt_data($record['seller']);
        $sales[$cr]['receipt_items'] = get_transaction_code_differed_items($conn, $record['transaction_code'], $shop_id);

        $cr++;

      }




      $data_insert=array(
          "status" => "success",
          "message" => "success",
          "sales" => $sales
      );

      echo json_encode($data_insert);
      mysqli_close($conn);

    } else {
      $data_insert=array(
          "status" => "error",
          "message" => "Something bad happened"
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
    }

  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}


if ($action === 'get-date-expenses') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));


    // get the expenses
    $expenses = get_shop_day_expenses($conn, $shop_id, $date);

    $data_insert=array(
        "status" => "success",
        "message" => "success",
        "expenses" => $expenses
    );

    echo json_encode($data_insert);
    mysqli_close($conn);

  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}

if ($action === 'get-date-purchases') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));


    // get the expenses
    $purchases = get_date_sales_purchases($conn, $shop_id, $date);

    $data_insert=array(
        "status" => "success",
        "message" => "success",
        "purchases" => $purchases
    );

    echo json_encode($data_insert);
    mysqli_close($conn);

  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}

if ($action === 'get-date-paid-debtors') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));


    // get the expenses
    $date_paid_debtors = get_date_paid_debtors($conn, $shop_id, $date);

    $data_insert=array(
        "status" => "success",
        "message" => "success",
        "records" => $date_paid_debtors
    );

    echo json_encode($data_insert);
    mysqli_close($conn);

  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}

if ($action === 'get-date-transfers') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));

    // get the expenses
    $date_transfers = get_date_transfers2($conn, $shop_id, $date);

    $data_insert=array(
        "status" => "success",
        "message" => "success",
        "records" => $date_transfers
    );

    echo json_encode($data_insert);
    mysqli_close($conn);

  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}

if ($action === 'get-date-recovered') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));


    // get the expenses
    $recovered_sales = get_date_recovered_sales($conn, $shop_id, $date);

    $data_insert=array(
        "status" => "success",
        "message" => "success",
        "records" => $recovered_sales
    );

    echo json_encode($data_insert);
    mysqli_close($conn);

  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}



if ($action === 'get-user-date-cash-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);
  
    try {
  
      JWT::$leeway = 10;
      $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));
  
      // Access is granted. Add code of the operation here
      $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
      $date = mysqli_escape_string($conn, decrypt_data($request->date));
      $sold_by = mysqli_escape_string($conn, decrypt_data($request->sold_by));
  
  
      $sql = "SELECT users.username, sold_stock.transaction_id, sold_stock.transaction_code, SUM(sold_stock.quantity * sold_stock.selling_price) AS total, sales_payments.cash, sales_payments.discount, sales_payments.amount_reversed
                FROM sales_payments
              INNER JOIN sold_stock
              ON sold_stock.transaction_code = sales_payments.transaction_code
              AND sold_stock.sold_by='{$sold_by}'
              AND sold_stock.shop_id='{$shop_id}' AND sales_payments.shop_id='{$shop_id}'
              AND DATE_FORMAT(sold_stock.date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
              AND sales_payments.cash > 0
              INNER JOIN users ON sold_stock.sold_by = users.id
              GROUP BY sold_stock.transaction_id, sold_stock.transaction_code, sales_payments.cash, sales_payments.discount, sales_payments.amount_reversed
              ORDER BY sold_stock.transaction_id DESC";
  
      $result = mysqli_query($conn, $sql);
  
      if ($result) {
  
        $sales = [];
        $cr=0;
        while ($record = mysqli_fetch_assoc($result)) {
  
          $sales[$cr]['receipt_no'] = encrypt_data($record['transaction_code']);
          $sales[$cr]['receipt_amount'] = encrypt_data($record['total']);
          $sales[$cr]['receipt_cash'] = encrypt_data($record['cash']);
          $sales[$cr]['receipt_discount'] = encrypt_data($record['discount']);
          $sales[$cr]['receipt_reversed'] = encrypt_data($record['amount_reversed']);
          $sales[$cr]['receipt_items'] = get_transaction_code_items($conn, $record['transaction_code'], $shop_id);
          $sales[$cr]['sold_by'] = encrypt_data($record['username']);
  
          $cr++;
  
        }
  
  
  
  
        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "sales" => $sales
        );
  
        echo json_encode($data_insert);
        mysqli_close($conn);
  
      } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened"
        );
  
        echo json_encode($data_insert);
        mysqli_close($conn);
      }
  
    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );
  
        echo json_encode($data_insert);
        mysqli_close($conn);
    }
  }







if ($action === 'get_periodic_reports') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));


        $sql = "SELECT a.gross_sales, a.gross_profit,
                       e.expenses,
                       b.added_stock AS added_stock,
                       p.cash, p.mpesa, p.bank,
                       p.discounts, p.reversed AS cash_reversed, d.reversed AS debtor_reversed,
                       d.debtor_sales AS debtor_sales, d.debtor_profit, pd.paid_debtors, t.transfers,
                       (recovered1.recovered + recovered2.recovered) AS recovered

                FROM

                (SELECT IFNULL(SUM(`quantity`*`selling_price`), 0) AS gross_sales, IFNULL(SUM((`selling_price`-`buying_price`)*`quantity`),0) AS gross_profit FROM `sold_stock` WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS a
                INNER JOIN
                (SELECT IFNULL(SUM(`buying_price`*quantity_added), 0) AS added_stock FROM added_stock WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS b
                INNER JOIN
                (SELECT IFNULL(SUM(amount), 0) AS expenses FROM expenses WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_incurred, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS e
                INNER JOIN
                (SELECT IFNULL(SUM(cash), 0) AS cash, IFNULL(SUM(mpesa), 0) AS mpesa, IFNULL(SUM(bank), 0) AS bank, IFNULL(SUM(discount), 0) AS discounts, IFNULL(SUM(amount_reversed), 0) AS reversed FROM sales_payments WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS p
                INNER JOIN
                (SELECT IFNULL(SUM(item_quantity * item_selling_price), 0) AS debtor_sales, IFNULL(SUM(item_quantity *(item_selling_price-buying_price)), 0) AS debtor_profit, IFNULL(SUM(quantity_returned * item_selling_price), 0) AS reversed FROM pending_payments WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS d
                INNER JOIN
                (SELECT IFNULL(SUM(amount_paid), 0) AS paid_debtors FROM differed_payments WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS pd
                INNER JOIN
                (SELECT IFNULL(SUM(quantity * buying_price), 0) AS transfers FROM transfer_goods WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS t
                INNER JOIN
                (SELECT IFNULL(SUM(`quantity`*`selling_price`), 0) AS recovered FROM `sold_stock` WHERE shop_id='{$shop_id}' AND selling_price=buying_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS recovered1
                INNER JOIN
                (SELECT IFNULL(SUM(item_quantity * item_selling_price), 0) AS recovered FROM pending_payments WHERE shop_id='{$shop_id}' AND item_selling_price=buying_price AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS recovered2
                ";


                // INNER JOIN
                // (SELECT IFNULL(item_id, '') AS item_id, IFNULL(SUM(quantity), 0) AS quantity_sold FROM sold_stock WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY item_id ORDER BY quantity_sold DESC LIMIT 1) AS top_sold
                // INNER JOIN
                // (SELECT IFNULL(item_id, '') AS item_id, IFNULL(SUM((selling_price-buying_price) * quantity), 0) AS profit FROM sold_stock WHERE shop_id='{$shop_id}' AND selling_price>buying_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY item_id ORDER BY profit DESC LIMIT 1) AS top_profitable

        $result = mysqli_query($conn, $sql);

        if($result) {

            $record = mysqli_fetch_assoc($result);

            $report['gross_sales'] = $record['gross_sales'] + $record['debtor_sales'];
            $report['gross_profit'] = $record['gross_profit'] + $record['debtor_profit'];
            $report['expenses'] = $record['expenses'];
            $report['net_profit'] = $report['gross_profit'] - $report['expenses'];
            $report['added_stock'] = $record['added_stock'];
            $report['cash'] = $record['cash'];
            $report['mpesa'] = $record['mpesa'];
            $report['bank'] = $record['bank'];
            $report['discounts'] = $record['discounts'];
            $report['cash_reversed'] = $record['cash_reversed'];
            $report['debtor_reversed'] = $record['debtor_reversed'];
            $report['debtor_sales'] = $record['debtor_sales'];
            $report['paid_debtors'] = $record['paid_debtors'];
            $report['transfers'] = $record['transfers'];
            $report['recovered'] = $record['recovered'];


            // get the top sold item
            $top_item = get_top_sold_item($conn, $shop_id, $from, $to);

            if($top_item != 'none') {
                $item = get_shop_item_details($conn, $shop_id, $top_item['item_id']);
                $report['top_sold_item'] = decrypt_data($item['item_name']);
                $report['top_sold_quantity'] = $top_item['quantity_sold'];
            } else {
                $report['top_sold_item'] = null;
            }

            $top_profit_item = get_top_profitable_item($conn, $shop_id, $from, $to);

            if($top_profit_item != 'none') {
                $item = get_shop_item_details($conn, $shop_id, $top_profit_item['item_id']);
                $report['top_profitable_item'] = decrypt_data($item['item_name']);
                $report['top_profit_amount'] = $top_profit_item['profit'];
            } else {
                $report['top_profitable_item'] = null;
            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Report" => $report
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);



        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}




if ($action === 'get_user_periodic_reports') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->user_id));


        $sql = "SELECT a.gross_sales, a.gross_profit,
                       e.expenses,
                       b.added_stock AS added_stock,
                       p.cash, p.mpesa, p.bank,
                       p.discounts, p.reversed AS cash_reversed, d.reversed AS debtor_reversed,
                       d.debtor_sales AS debtor_sales, d.debtor_profit, pd.paid_debtors, t.transfers,
                       (recovered1.recovered + recovered2.recovered) AS recovered,
                       top_sold.item_id AS top_sold_item,
                       top_sold.quantity_sold AS top_sold_quantity,
                       top_profitable.item_id AS top_profitable_item,
                       top_profitable.profit AS top_profit_amount

                FROM

                (SELECT IFNULL(SUM(`quantity`*`selling_price`), 0) AS gross_sales, IFNULL(SUM((`selling_price`-`buying_price`)*`quantity`),0) AS gross_profit FROM `sold_stock` WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS a
                INNER JOIN
                (SELECT IFNULL(SUM(`buying_price`*quantity_added), 0) AS added_stock FROM added_stock WHERE shop_id='{$shop_id}' AND added_by='{$user_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS b
                INNER JOIN
                (SELECT IFNULL(SUM(amount), 0) AS expenses FROM expenses WHERE shop_id='{$shop_id}' AND employee='{$user_id}' AND DATE_FORMAT(date_incurred, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS e
                INNER JOIN
                (SELECT IFNULL(SUM(cash), 0) AS cash, IFNULL(SUM(mpesa), 0) AS mpesa, IFNULL(SUM(bank), 0) AS bank, IFNULL(SUM(discount), 0) AS discounts, IFNULL(SUM(amount_reversed), 0) AS reversed FROM sales_payments WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                    AND transaction_code IN (SELECT transaction_code FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}')) AS p
                INNER JOIN
                (SELECT IFNULL(SUM(item_quantity * item_selling_price), 0) AS debtor_sales, IFNULL(SUM(item_quantity *(item_selling_price-buying_price)), 0) AS debtor_profit, IFNULL(SUM(quantity_returned * item_selling_price), 0) AS reversed FROM pending_payments WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS d
                INNER JOIN
                (SELECT IFNULL(SUM(amount_paid), 0) AS paid_debtors FROM differed_payments WHERE shop_id='{$shop_id}' AND paid_to='{$user_id}' AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS pd
                INNER JOIN
                (SELECT IFNULL(SUM(quantity * buying_price), 0) AS transfers FROM transfer_goods WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS t
                INNER JOIN
                (SELECT IFNULL(SUM(`quantity`*`selling_price`), 0) AS recovered FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND selling_price=buying_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS recovered1
                INNER JOIN
                (SELECT IFNULL(SUM(item_quantity * item_selling_price), 0) AS recovered FROM pending_payments WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND item_selling_price=buying_price AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS recovered2
                INNER JOIN
                (SELECT IFNULL(item_id, '') AS item_id, IFNULL(SUM(quantity), 0) AS quantity_sold FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY item_id ORDER BY quantity_sold DESC LIMIT 1) AS top_sold
                INNER JOIN
                (SELECT IFNULL(item_id, '') AS item_id, IFNULL(SUM((selling_price-buying_price) * quantity), 0) AS profit FROM sold_stock WHERE shop_id='{$shop_id}' AND sold_by='{$user_id}' AND selling_price>buying_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY item_id ORDER BY profit DESC LIMIT 1) AS top_profitable";

        $result = mysqli_query($conn, $sql);

        if($result) {

            $record = mysqli_fetch_assoc($result);

            $report['gross_sales'] = $record['gross_sales'] + $record['debtor_sales'];
            $report['gross_profit'] = $record['gross_profit'] + $record['debtor_profit'];
            $report['expenses'] = $record['expenses'];
            $report['net_profit'] = $report['gross_profit'] - $report['expenses'];
            $report['added_stock'] = $record['added_stock'];
            $report['cash'] = $record['cash'];
            $report['mpesa'] = $record['mpesa'];
            $report['bank'] = $record['bank'];
            $report['discounts'] = $record['discounts'];
            $report['cash_reversed'] = $record['cash_reversed'];
            $report['debtor_reversed'] = $record['debtor_reversed'];
            $report['debtor_sales'] = $record['debtor_sales'];
            $report['paid_debtors'] = $record['paid_debtors'];
            $report['transfers'] = $record['transfers'];
            $report['recovered'] = $record['recovered'];
            $report['top_sold_quantity'] = $record['top_sold_quantity'];
            $report['top_profit_amount'] = $record['top_profit_amount'];

            // if($record['top_sold_item'] != '') {
            //     $item = get_shop_item_details($conn, $shop_id, $record['top_sold_item']);
            //     $report['top_sold_item'] = decrypt_data($item['item_name']);
            // }

            // if($record['top_profitable_item'] != '') {
            //     $item = get_shop_item_details($conn, $shop_id, $record['top_profitable_item']);
            //     $report['top_profitable_item'] = decrypt_data($item['item_name']);
            // }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Report" => $report
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);



        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}




if ($action === 'get-period-cash-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        // get distinct dates between this period
        $period_sales = get_period_cash_dates($conn, $shop_id, $from, $to);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Sales" => $period_sales
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-period-mpesa-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        // get distinct dates between this period
        $period_sales = get_period_mpesa_dates($conn, $shop_id, $from, $to);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Sales" => $period_sales
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-period-bank-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        // get distinct dates between this period
        $period_sales = get_period_bank_dates($conn, $shop_id, $from, $to);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Sales" => $period_sales
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-period-expenses') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        // get distinct dates between this period
        $period_sales = get_period_expenses($conn, $shop_id, $from, $to);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Sales" => $period_sales
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}





if ($action === 'get-user-period-cash-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->user_id));

        // get distinct dates between this period
        $period_sales = get_user_period_cash_dates($conn, $shop_id, $from, $to, $user_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Sales" => $period_sales
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-user-period-mpesa-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->user_id));

        // get distinct dates between this period
        $period_sales = get_user_period_mpesa_dates($conn, $shop_id, $from, $to, $user_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Sales" => $period_sales
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-user-period-bank-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->user_id));

        // get distinct dates between this period
        $period_sales = get_user_period_bank_dates($conn, $shop_id, $from, $to, $user_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Sales" => $period_sales
        );

        echo json_encode($data_insert);
        mysqli_close($conn);

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}







if ($action === 'get_item_periodic_report') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));


        $sql = "SELECT a.gross_sales+d.debtor_sales AS sold, b.added_stock AS added_stock, t.transfers AS transfers, r.returned AS returned_stock
                FROM
                (SELECT IFNULL(SUM(`quantity`), 0) AS gross_sales FROM `sold_stock` WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS a
                INNER JOIN
                (SELECT IFNULL(SUM(quantity_added), 0) AS added_stock FROM added_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS b
                INNER JOIN
                (SELECT IFNULL(SUM(item_quantity), 0) AS debtor_sales FROM pending_payments WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS d
                INNER JOIN
                (SELECT IFNULL(SUM(quantity), 0) AS transfers FROM transfer_goods WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS t
                INNER JOIN
                (SELECT IFNULL(SUM(quantity_returned), 0) AS returned FROM returned_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS r;";

        $result = mysqli_query($conn, $sql);

        if($result) {

            $record = mysqli_fetch_assoc($result);

            $report['sold_quantity'] = $record['sold'];
            $report['added_quantity'] = $record['added_stock'];
            $report['transfers'] = $record['transfers'];
            $report['returned'] = $record['returned_stock'];

            $opening_quantity = get_item_period_opening_quantity($conn, $shop_id, $item_id, $from, $to);


            $report['opening_quantity'] = $opening_quantity;

            if ($record['sold'] > 0 || $record['transfers'] > 0) {

                $closing_quantity = get_item_period_closing_quantity($conn, $shop_id, $item_id, $from, $to);
                $report['closing_quantity'] = $closing_quantity;

            } else {

                $report['closing_quantity'] = $opening_quantity + $record['added_stock'] + $record['returned_stock'];

            }


            // get the report for each date with transaction
            $transaction_dates = get_item_period_transaction_dates($conn, $item_id, $from, $to);

            $date_transactions = [];
            $dr = 0;

            if(count($transaction_dates) > 0) {

                foreach($transaction_dates as $date) {

                    // get the records for this date
                    $date_records = get_item_date_transactions($conn, $item_id, $date['date_sold']);

                    $date_opening_quantity = get_item_date_opening_quantity($conn, $shop_id, $item_id, $date['date_sold']);

                    $date_transactions[$dr]['date'] = $date['date_sold'];
                    $date_transactions[$dr]['opening_quantity'] = $date_opening_quantity;
                    $date_transactions[$dr]['sold'] = $date_records['sold'];
                    $date_transactions[$dr]['added_stock'] = $date_records['added_stock'];
                    $date_transactions[$dr]['transfers'] = $date_records['transfers'];
                    $date_transactions[$dr]['returned_stock'] = $date_records['returned_stock'];

                    $dr++;

                }

            }



            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Report" => $report,
                "Transactions" => $date_transactions
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);



        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get_item_period_added_stock') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));


        $sql = "SELECT * FROM added_stock WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND
                DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id ASC";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $invoices = [];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {
                // get the invoice supplier
                $invoice_id = $row['invoice_id'];

                $invoice_supplier = decrypt_data(get_invoice_supplier_name($conn, $shop_id, $invoice_id));

                $invoices[$cr]['supplier'] = $invoice_supplier;
                $invoices[$cr]['date_added'] = $row['date_added'];
                $invoices[$cr]['quantity_added'] = $row['quantity_added'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "invoices" => $invoices
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


        
    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get_item_period_sold_stock') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));


        $sql = "SELECT customer_id, SUM(quantity) AS quantity, DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold, sold_by FROM
                (
                    (SELECT customer_id, SUM(quantity) AS quantity, DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold, sold_by FROM sold_stock
                        WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                        DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY customer_id, sold_by, DATE_FORMAT(date_sold, '%Y-%m-%d') ORDER BY id ASC)
                    UNION
                    (SELECT customer_id, SUM(item_quantity) AS quantity, DATE_FORMAT(date_picked, '%Y-%m-%d') AS date_sold, sold_by FROM pending_payments
                        WHERE shop_id='{$shop_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                        DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY customer_id, sold_by, DATE_FORMAT(date_picked, '%Y-%m-%d') ORDER BY id ASC)
                ) AS a GROUP BY customer_id, sold_by, DATE_FORMAT(date_sold, '%Y-%m-%d') ORDER BY date_sold ASC;";

        $result = mysqli_query($conn, $sql);

        if($result) {

            $sales = [];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {
                // get the invoice supplier
                $seller = get_shop_username($conn, $row['sold_by']);

                if($row['customer_id'] == 0) {
                    $sales[$cr]['customer'] = 'Cash customer';
                } else {
                    $customer_ = get_customer_record($conn, $row['customer_id'], $shop_id);
                    $sales[$cr]['customer'] = decrypt_data($customer_['name']);
                }

                $sales[$cr]['quantity'] = $row['quantity'];
                $sales[$cr]['date_sold'] = $row['date_sold'];
                $sales[$cr]['sold_by'] = decrypt_data($seller);

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "sales" => $sales
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened",
                "error" => mysqli_error($conn)
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


        
    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}












if ($action === 'get-period-date-sales-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        // get distinct dates between this period
        $distinct_sold_dates = get_period_sold_dates($conn, $shop_id, $from, $to);

        $period_summary = get_period_sales_summary($conn, $shop_id, $from, $to);

        $sales=[];
        $cr=0;

        
        $period_gross_sales = 0;
        $period_expenses = 0;
        $period_net_sales = 0;
        $period_gross_profit = 0;
        $period_net_profit = 0;
        $period_loss = 0;

        $period_gross_sales = $period_summary['gross_cash_sales'] + $period_summary['gross_debtor_sales'];
        $period_gross_profit = $period_summary['gross_cash_profit'] + $period_summary['gross_debtor_profit'];
        $period_expenses = $period_summary['expenses'];
        $period_cash_purchases = $period_summary['sales_purchases'];
        $period_transfers = $period_summary['transfers'];
        $period_paid_debtors = $period_summary['paid_debtors'];
        $period_debtors = $period_summary['gross_debtor_sales'];
        $period_reversed = $period_summary['reversed_d'] + $period_summary['reversed'];
        $period_reversed_cash = $period_summary['reversed'];
        $period_reversed_differed = $period_summary['reversed_d'];
        $period_discounts = $period_summary['discount'];
        $period_cash = $period_summary['cash'];
        $period_bank = $period_summary['bank'];
        $period_mpesa = $period_summary['mpesa'];

        $period_recovered = $period_summary['recovered1'] + $period_summary['recovered2'];
        $period_sales_purchases = $period_summary['sales_purchases'];


        $period_net_profit = $period_gross_profit - $period_expenses;
        $period_net_sales = $period_gross_sales - $period_expenses;

        if($period_expenses > $period_gross_profit) {
            $period_net_profit = 0;
            $period_loss = $period_expenses - $period_gross_profit;
        }




        foreach($distinct_sold_dates as $date_record) {

            $date = decrypt_data($date_record['date_of_sale']);


            $date_gross_sales = 0;
            $date_expenses = 0;
            $date_net_sales = 0;
            $date_gross_profit = 0;
            $date_net_profit = 0;
            $date_loss = 0;


            // get the date gross sales
            $date_summary = get_date_sales_summary($conn, $shop_id, $date);

            $date_gross_sales = $date_summary['gross_cash_sales'] + $date_summary['gross_debtor_sales'];
            $date_gross_profit = $date_summary['gross_cash_profit'] + $date_summary['gross_debtor_profit'];
            $date_expenses = $date_summary['expenses'];
            $date_net_profit = $date_gross_profit - $date_expenses;


            $date_sales['gross_sales'] = encrypt_data($date_gross_sales);
            $date_sales['gross_profit'] = encrypt_data($date_gross_profit);
            $date_sales['expenses'] = encrypt_data($date_expenses);
            $date_sales['net_profit'] = encrypt_data($date_net_profit);

            

            $sales[$cr]['date'] = encrypt_data($date);
            $sales[$cr]['date_sales'] = $date_sales;

            $cr++;

        }

        $period_sales['gross_sales'] = encrypt_data($period_gross_sales);
        $period_sales['gross_profit'] = encrypt_data($period_gross_profit);
        $period_sales['expenses'] = encrypt_data($period_expenses);
        $period_sales['cash_purchases'] = encrypt_data($period_cash_purchases);
        $period_sales['transfers'] = encrypt_data($period_transfers);
        $period_sales['paid_debtors'] = encrypt_data($period_paid_debtors);
        $period_sales['debtors'] = encrypt_data($period_debtors);
        $period_sales['reversed'] = encrypt_data($period_reversed);
        $period_sales['reversed_cash'] = encrypt_data($period_reversed_cash);
        $period_sales['reversed_differed'] = encrypt_data($period_reversed_differed);
        $period_sales['discounts'] = encrypt_data($period_discounts);
        $period_sales['cash'] = encrypt_data($period_cash);
        $period_sales['bank'] = encrypt_data($period_bank);
        $period_sales['mpesa'] = encrypt_data($period_mpesa);
        $period_sales['net_sales'] = encrypt_data($period_net_sales);
        $period_sales['net_profit'] = encrypt_data($period_net_profit);
        $period_sales['loss'] = encrypt_data($period_loss);
        $period_sales['recovered'] = encrypt_data($period_recovered);
        $period_sales['sales_purchases'] = encrypt_data($period_sales_purchases);
        $period_sales['dates'] = $sales;


        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Summary" => $period_sales
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}






if ($action === 'get-period-discounts-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));


        // get all dates with cash for this period
        $sql = "SELECT DISTINCT(DATE_FORMAT(date_paid, '%Y-%m-%d')) AS date_sold FROM sales_payments
                WHERE shop_id='{$shop_id}' AND discount>0
                    AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                ORDER BY date_sold ASC";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $dates=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {
                $date = $row['date_sold'];
                // get the cash amount for this date
                $sql = "SELECT SUM(discount) AS discount, SUM(amount_reversed) AS reversed FROM sales_payments WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
                $result1 = mysqli_query($conn, $sql);

                if($result1) {

                    $record = mysqli_fetch_assoc($result1);

                    $dates[$cr]['date'] = encrypt_data($date);
                    $dates[$cr]['discount'] = encrypt_data($record['discount']);
                    $dates[$cr]['reversed'] = encrypt_data($record['reversed']);

                    $cr++;

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get date cash!"
                    );
        
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                    exit();
                }


            }

            $data_insert=array(
                "status" => "success",
                "sales" => $dates
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }





    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-period-reversed-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));


        // get all dates with cash for this period
       
        $sql = "SELECT DISTINCT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM

                (SELECT DISTINCT(DATE_FORMAT(date_paid, '%Y-%m-%d')) AS date_sold FROM sales_payments
                            WHERE shop_id='{$shop_id}' AND amount_reversed>0
                        AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                UNION
                (SELECT DISTINCT(DATE_FORMAT(date_picked, '%Y-%m-%d')) AS date_sold FROM pending_payments
                            WHERE shop_id='{$shop_id}' AND quantity_returned>0
                        AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))) AS x
                        ORDER BY date_sold ASC";
        $result1 = mysqli_query($conn, $sql);

        if($result1) {

            $dates=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result1)) {
                $date = $row['date_sold'];
                // get the cash amount for this date

                $sql = "SELECT SUM(amount_reversed) AS amount_reversed FROM

                        (SELECT IFNULL(SUM(amount_reversed), 0) AS amount_reversed
                                    FROM sales_payments WHERE shop_id='{$shop_id}'
                                    AND amount_reversed > 0
                                    AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                                    
                        UNION
                        (SELECT IFNULL(SUM(quantity_returned * item_quantity), 0) AS amount_reversed
                               FROM pending_payments
                               WHERE shop_id='{$shop_id}' AND quantity_returned>0
                            AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d'))) AS x";

                $result11 = mysqli_query($conn, $sql);

                if($result11) {

                    $record = mysqli_fetch_assoc($result11);

                    $dates[$cr]['date'] = encrypt_data($date);
                    $dates[$cr]['amount_reversed'] = encrypt_data($record['amount_reversed']);

                    $cr++;

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get date cash!"
                    );
        
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                    exit();
                }


            }

            $data_insert=array(
                "status" => "success",
                "sales" => $dates
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }





    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get-period-debtor-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));


        // get all dates with cash for this period
        $sql = "SELECT DISTINCT(DATE_FORMAT(date_picked, '%Y-%m-%d')) AS date_sold FROM pending_payments
                WHERE shop_id='{$shop_id}' AND item_quantity>0
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                ORDER BY date_sold ASC";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $dates=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {
                $date = $row['date_sold'];
                // get the cash amount for this date
                $sql = "SELECT SUM(item_quantity * item_selling_price) AS total
                        FROM pending_payments WHERE shop_id='{$shop_id}'
                            AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
                $result1 = mysqli_query($conn, $sql);

                if($result1) {

                    $record = mysqli_fetch_assoc($result1);

                    $dates[$cr]['date_sold'] = $date;
                    $dates[$cr]['sales'] = $record['total'];

                    $cr++;

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get date debtors!"
                    );
        
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                    exit();
                }


            }

            $data_insert=array(
                "status" => "success",
                "sales" => $dates
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }





    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get-period-paid-debtor-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));


        // get all dates with cash for this period
        $sql = "SELECT DISTINCT(DATE_FORMAT(date_paid, '%Y-%m-%d')) AS date_sold FROM differed_payments
                WHERE shop_id='{$shop_id}' AND amount_paid>0
                    AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                ORDER BY date_sold ASC";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $dates=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {
                $date = $row['date_sold'];
                // get the cash amount for this date
                $sql = "SELECT SUM(amount_paid) AS total
                        FROM differed_payments WHERE shop_id='{$shop_id}'
                            AND DATE_FORMAT(date_paid, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
                $result1 = mysqli_query($conn, $sql);

                if($result1) {

                    $record = mysqli_fetch_assoc($result1);

                    $dates[$cr]['date'] = $date;
                    $dates[$cr]['sales'] = $record['total'];

                    $cr++;

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get date paid debtors!"
                    );
        
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                    exit();
                }


            }

            $data_insert=array(
                "status" => "success",
                "sales" => $dates
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }





    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-period-transfers') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));


        // get all dates with cash for this period
        $sql = "SELECT DISTINCT(DATE_FORMAT(date_transfered, '%Y-%m-%d')) AS date_sold FROM transfer_goods
                WHERE shop_id='{$shop_id}' AND quantity>0
                    AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                ORDER BY date_sold ASC";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $dates=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {
                $date = $row['date_sold'];
                // get the cash amount for this date
                $sql = "SELECT SUM(quantity * buying_price) AS total
                        FROM transfer_goods WHERE shop_id='{$shop_id}'
                            AND DATE_FORMAT(date_transfered, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
                $result1 = mysqli_query($conn, $sql);

                if($result1) {

                    $record = mysqli_fetch_assoc($result1);

                    $dates[$cr]['date'] = $date;
                    $dates[$cr]['transfers'] = $record['total'];

                    $cr++;

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get date transfers!"
                    );
        
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                    exit();
                }


            }

            $data_insert=array(
                "status" => "success",
                "sales" => $dates
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }





    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-period-purchases') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));


        // get all dates with cash for this period
        $sql = "SELECT DISTINCT(DATE_FORMAT(date_added, '%Y-%m-%d')) AS date_sold FROM sales_purchases
                WHERE shop_id='{$shop_id}' AND quantity>0
                    AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                ORDER BY date_sold ASC";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $dates=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {
                $date = $row['date_sold'];
                // get the cash amount for this date
                $sql = "SELECT SUM(quantity * buying_price) AS total
                        FROM sales_purchases WHERE shop_id='{$shop_id}'
                            AND DATE_FORMAT(date_added, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')";
                $result1 = mysqli_query($conn, $sql);

                if($result1) {

                    $record = mysqli_fetch_assoc($result1);

                    $dates[$cr]['date'] = encrypt_data($date);
                    $dates[$cr]['sales'] = encrypt_data($record['total']);

                    $cr++;

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get date sales purchases!"
                    );
        
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                    exit();
                }


            }

            $data_insert=array(
                "status" => "success",
                "sales" => $dates
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }





    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-period-recovered') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));


        // get all dates with cash for this period
       
        $sql = "SELECT DISTINCT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM

                (SELECT DISTINCT(DATE_FORMAT(date_sold, '%Y-%m-%d')) AS date_sold FROM sold_stock
                            WHERE shop_id='{$shop_id}' AND selling_price = buying_price
                        AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                UNION
                (SELECT DISTINCT(DATE_FORMAT(date_picked, '%Y-%m-%d')) AS date_sold FROM pending_payments
                            WHERE shop_id='{$shop_id}' AND item_selling_price = buying_price
                        AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))) AS x
                ORDER BY date_sold ASC";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $dates=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {
                $date = $row['date_sold'];
                // get the cash amount for this date

                $sql = "SELECT SUM(recovered) AS recovered FROM

                        (SELECT IFNULL(SUM(quantity * buying_price), 0) AS recovered
                                    FROM sold_stock WHERE shop_id='{$shop_id}'
                                    AND selling_price = buying_price
                                    AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                                    
                        UNION
                        (SELECT IFNULL(SUM(item_quantity * buying_price), 0) AS recovered
                               FROM pending_payments
                               WHERE shop_id='{$shop_id}' AND item_selling_price = buying_price
                            AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d'))) AS x";

                $result1 = mysqli_query($conn, $sql);

                if($result1) {

                    $record = mysqli_fetch_assoc($result1);

                    $dates[$cr]['date'] = encrypt_data($date);
                    $dates[$cr]['recovered'] = encrypt_data($record['recovered']);

                    $cr++;

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not get date recovered!"
                    );
        
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                    exit();
                }


            }

            $data_insert=array(
                "status" => "success",
                "sales" => $dates
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }





    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}









if ($action === 'get-all-items-reports') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql = "SELECT * FROM all_stock WHERE shop_id='{$shop_id}' AND deleted=0";
        $result=mysqli_query($conn, $sql);

        if($result) {

            $items=[];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                $id='';

                $id = $row['id'];

                // get report for this item
                $cash_reports = get_items_sales_report($conn, $id, $shop_id);
                $debtors_reports = get_items_debtors_report($conn, $id, $shop_id);

                $total_quantity_sold = $cash_reports['quantity'] + $debtors_reports['quantity'];
                $total_profit = $cash_reports['profit'] + $debtors_reports['profit'];

                $items[$cr]['item'] = get_shop_item_details($conn, $shop_id, $id);
                $items[$cr]['quantity'] = encrypt_data($total_quantity_sold);
                $items[$cr]['profit'] = encrypt_data($total_profit);

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Items" => $items
            );

            echo json_encode($data_insert);
            mysqli_close($conn);


        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get-all-items-periodic-reports') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        $cash_reports = get_items_sales_overall_periodic_report($conn, $shop_id, $from, $to);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Items" => $cash_reports
        );

        echo json_encode($data_insert);
        mysqli_close($conn);

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-item-periodic-report') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        $sales=[];
        $all_sales = array();
        $cr=0;

        // get all date with cash,debtor sales or added stock for this item
        $item_transaction_dates = get_item_dates_with_sales_or_invoices($conn, $item_id, $shop_id, $from, $to);

        // get quantity before
        $item_period_opening_quantity = get_item_quantity_before_period_sales($conn, $item_id, $shop_id, $from);

        foreach ($item_transaction_dates as $date_of_sale) {
            // get the cash sales for this date
            $cash_sales = get_items_sales_period_report($conn, $item_id, $shop_id, $date_of_sale);

            foreach ($cash_sales as $sale) {
                array_push($all_sales, $sale);
            }

            $debtors_sales = get_items_debtors_period_report($conn, $item_id, $shop_id, $date_of_sale);

            foreach ($debtors_sales as $sale) {
                array_push($all_sales, $sale);
            }

            $added_stock = get_item_date_added_stock($conn, $item_id, $shop_id, $date_of_sale);

            // get any transfer items
            $transfer_sales = get_items_transfer_period_report($conn, $item_id, $shop_id, $date_of_sale);

            // get quantity before
            $item_date_opening_quantity = get_item_quantity_before_period_sales($conn, $item_id, $shop_id, $date_of_sale);


            $sales[$cr]['date_of_sale'] = encrypt_data($date_of_sale);
            $sales[$cr]['date_opening_stock'] = $item_date_opening_quantity;
            $sales[$cr]['sales'] = $all_sales;
            $sales[$cr]['transfer_sales'] = $transfer_sales;
            $sales[$cr]['added_stock'] = $added_stock;

            $cr++;
        }

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Sales" => $sales,
            "OpeningQuantity" => $item_period_opening_quantity
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}



if ($action === 'get-employee-period-sales') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

      JWT::$leeway = 10;
      $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

      // Access is granted. Add code of the operation here
      $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
      $from = mysqli_escape_string($conn, decrypt_data($request->from));
      $to = mysqli_escape_string($conn, decrypt_data($request->to));

      $sales=[];
      $all_sales = array();
      $cr=0;


      // distinct sellers
      $sql0 = "SELECT DISTINCT sold_by FROM
               (SELECT DISTINCT sold_by AS sold_by FROM sold_stock
                WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
              UNION
                SELECT DISTINCT sold_by AS sold_by FROM pending_payments
                WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                DATE_FORMAT(date_picked, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS a";

      $result0 = mysqli_query($conn, $sql0);

      if ($result0) {
        while($record = mysqli_fetch_assoc($result0)) {

          // get the user details
          $user_detail = get_shop_user_details($conn, $record['sold_by']);

          // get the user cash sales
          // cash sales
          $user_cash_sales = get_user_period_cash_sales($conn, $shop_id, $record['sold_by'], $from, $to);

          $user_expenses = get_user_period_expenses($conn, $shop_id, $record['sold_by'], $from, $to);

          $cash_sales = $user_cash_sales['sales'];
          $cash_profit = $user_cash_sales['profit'];

          $user_differed_sales = get_user_period_differed_sales($conn, $shop_id, $record['sold_by'], $from, $to);

          $differed_sales = $user_differed_sales['sales'];
          $differed_profit = $user_differed_sales['profit'];

          $total_sales = $cash_sales + $differed_sales;
          $total_profit = $cash_profit + $differed_profit;



          $sales[$cr]['user'] = $user_detail;
          $sales[$cr]['sales'] = encrypt_data($total_sales);
          $sales[$cr]['profit'] = encrypt_data($total_profit);
          $sales[$cr]['expenses'] = encrypt_data($user_expenses);

          $cr++;
        }


        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Sales" => $sales
        );

        echo json_encode($data_insert);
        mysqli_close($conn);

      } else {
        $data_insert=array(
            "status" => "error",
            "message" => "Something bad happened"
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
      }



      } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get_user_period_sales') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $user_id = mysqli_escape_string($conn, decrypt_data($request->user_id));
    $from = mysqli_escape_string($conn, decrypt_data($request->from));
    $to = mysqli_escape_string($conn, decrypt_data($request->to));

    $user_sales = get_user_period_sales($conn, $shop_id, $user_id, $from, $to);


    $data_insert=array(
        "status" => "success",
        "message" => "success",
        "Sales" => $user_sales
    );

    echo json_encode($data_insert);
    mysqli_close($conn);


  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}


if ($action === 'get_user_date_sales_records') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $user_id = mysqli_escape_string($conn, decrypt_data($request->user_id));
    $date = mysqli_escape_string($conn, decrypt_data($request->date));

    $user_sales = get_user_date_sales_records($conn, $shop_id, $user_id, $date);


    $data_insert=array(
        "status" => "success",
        "message" => "success",
        "Sales" => $user_sales
    );

    echo json_encode($data_insert);
    mysqli_close($conn);


  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}


if ($action === 'get_user_time_code_items') {
  $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
  $temp_header = explode(" ",$authHeader);
  $jwt = decrypt_data($request->at);

  try {

    JWT::$leeway = 10;
    $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

    // Access is granted. Add code of the operation here
    $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
    $user_id = mysqli_escape_string($conn, decrypt_data($request->user_id));
    $time = mysqli_escape_string($conn, decrypt_data($request->date));
    $code = mysqli_escape_string($conn, decrypt_data($request->code));

    $user_sales = [];

    if(strpos($code,"D")) {
      $user_sales = get_transaction_code_differed_time_items($conn, $code, $shop_id, $time);
    } else {
      $user_sales = get_transaction_code_time_items($conn, $code, $shop_id, $time);
    }




    $data_insert=array(
        "status" => "success",
        "message" => "success",
        "Sales" => $user_sales
    );

    echo json_encode($data_insert);
    mysqli_close($conn);


  } catch (Exception $e) {
      
      $data_insert=array(
      //"data" => $data_from_server,
      "jwt" => $jwt,
      "status" => "error2",
      "message" => $e->getMessage()
      );

      echo json_encode($data_insert);
      mysqli_close($conn);
  }
}





if ($action === 'get-today-expenses') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        $TODAY = date('Y-m-d h:i:s', time());

        $sql="SELECT * FROM expenses WHERE shop_id='{$shop_id}' AND employee='{$added_by}'
                AND DATE_FORMAT(date_incurred, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d') ORDER BY id DESC";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $expenses = [];
            $cr = 0;

            while ($row=mysqli_fetch_assoc($result)) {
                $expenses[$cr]['id'] = encrypt_data($row['id']);
                $expenses[$cr]['name'] = encrypt_data($row['name']);
                $expenses[$cr]['amount'] = encrypt_data($row['amount']);

                $cr++;
            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Expenses" => $expenses
            );

            echo json_encode($data_insert);
            mysqli_close($conn);



        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get-today-admin-expenses') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $TODAY = date('Y-m-d h:i:s', time());

        // $sql="SELECT * FROM expenses WHERE shop_id='{$shop_id}' AND date_incurred >= DATE_SUB('{$TODAY}', INTERVAL 31 DAY) ORDER BY id DESC";

        $sql="SELECT * FROM expenses WHERE shop_id='{$shop_id}'
                AND DATE_FORMAT(date_incurred, '%Y-%m-%d') = DATE_FORMAT('{$TODAY}', '%Y-%m-%d')
                ORDER BY id DESC";

        $result = mysqli_query($conn, $sql);

        if($result) {

            $expenses = [];
            $cr = 0;

            while ($row=mysqli_fetch_assoc($result)) {
                $expenses[$cr]['id'] = encrypt_data($row['id']);
                $expenses[$cr]['user'] = get_shop_username($conn, $row['employee']);
                $expenses[$cr]['name'] = encrypt_data($row['name']);
                $expenses[$cr]['amount'] = encrypt_data($row['amount']);
                $expenses[$cr]['date'] = encrypt_data($row['date_incurred']);

                $cr++;
            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Expenses" => $expenses
            );

            echo json_encode($data_insert);
            mysqli_close($conn);



        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get-date-items-with-profit') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        $sql = "SELECT id, customer_id, item_id, buying_price, quantity, selling_price, sold_by, date_sold
                 FROM sold_stock WHERE quantity > 0 AND selling_price > buying_price AND shop_id='{$shop_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                UNION (SELECT id, customer_id, item_id, buying_price, item_quantity AS quantity, item_selling_price AS selling_price, sold_by, date_picked AS date_sold
                        FROM pending_payments WHERE item_quantity > 0 AND item_selling_price > buying_price AND shop_id='{$shop_id}' AND DATE_FORMAT(date_picked, '%Y-%m-%d') = DATE_FORMAT('{$date}', '%Y-%m-%d')
                ORDER BY id DESC)";

        $result = mysqli_query($conn, $sql);

        if ($result) {

            $sales = [];
            $cr = 0;

            while($row = mysqli_fetch_assoc($result)) {

                // get the customer
                $customer = '';
                if ($row['customer_id'] == 0) {
                    $customer = encrypt_data('Cash Customer');
                } else {
                    $customer_ = get_customer_record($conn, $row['customer_id'], $shop_id);
                    $customer = $customer_['name'];
                }

                // get the seller
                $seller = get_shop_user_details($conn, $row['sold_by']);

                // get the item details
                $item = get_shop_item_details($conn, $shop_id, $row['item_id']);

                $sales[$cr]['id'] = encrypt_data($row['id']);
                $sales[$cr]['item'] = $item;
                $sales[$cr]['quantity'] = encrypt_data($row['quantity']);
                $sales[$cr]['buying_price'] = encrypt_data($row['buying_price']);
                $sales[$cr]['selling_price'] = encrypt_data($row['selling_price']);
                $sales[$cr]['customer'] = $customer;
                $sales[$cr]['sold_by'] = $seller;

                $cr++;

            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Sales" => $sales
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

// transfer
if ($action === 'get-transfer-customers') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql = "SELECT a.id, a.name, a.mobile_number, IFNULL(b.total_amount, 0) AS total_amount, IFNULL(b.amount_paid, 0) AS amount_paid, IFNULL((b.total_amount - b.amount_paid), 0) AS balance
                FROM (SELECT id, name, mobile_number FROM customers WHERE shop_id='{$shop_id}' AND type='shop' AND deleted=0) AS a
                INNER JOIN
                (SELECT customer_id, total_amount, amount_paid FROM transfer_payments WHERE shop_id='{$shop_id}') AS b
                ON a.id = b.customer_id
                ORDER BY balance DESC";

        $result = mysqli_query($conn, $sql);

        if ($result) {
            $customers = [];
            $cr = 0;

            while ($row=mysqli_fetch_assoc($result)) {

                $customers[$cr]['id'] = encrypt_data($row['id']);
                $customers[$cr]['name'] = $row['name'];
                $customers[$cr]['mobile_number'] = $row['mobile_number'];
                $customers[$cr]['total_amount'] = $row['total_amount'];
                $customers[$cr]['amount_paid'] = $row['amount_paid'];
                $customers[$cr]['balance'] = $row['balance'];
                $customers[$cr]['limit_reached'] = '0';

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Customers" => $customers
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        }



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}



if ($action === 'get-temp-transactions-transfer') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));

        $TODAY = date('Y-m-d h:i:s', time());


        $sql2 = "SELECT * FROM temp_transactions WHERE customer_id='{$customer_id}' AND shop_id='{$shop_id}' AND sold_by='{$added_by}'";
        $result2 = mysqli_query($conn, $sql2);

        if ($result2) {

            $items = [];
            $cr2=0;

            while ($row2 = mysqli_fetch_assoc($result2)) {

                $items[$cr2]['id'] = encrypt_data($row2['id']);
                $items[$cr2]['quantity'] = encrypt_data($row2['quantity']);
                $items[$cr2]['buying_price'] = encrypt_data($row2['buying_price']);
                $items[$cr2]['selling_price'] = encrypt_data($row2['selling_price']);
                $items[$cr2]['item_detail'] = get_shop_item_details($conn, $shop_id, $row2['item_id']);

                $cr2++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "CartItems" => $items
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Could not get items"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

// messages
if ($action === 'get-all-messages') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql = "SELECT DISTINCT message, id, date_sent, sent_by FROM sent_messages WHERE shop_id='{$shop_id}' ORDER BY id DESC";
        $result = mysqli_query($conn, $sql);

        if ($result) {

            $messages = [];
            $cr1=0;

            while($row = mysqli_fetch_assoc($result)) {

                $messages[$cr1]['message'] = encrypt_data($row['message']);
                $messages[$cr1]['date_sent'] = encrypt_data($row['date_sent']);
                $messages[$cr1]['sent_by'] =  get_shop_username($conn, $row['sent_by']);

                // get the number of customers
                $messages[$cr1]['customers'] = get_message_customers($conn, $row['message'], $shop_id);

                $cr1++;

            }

            $shop_customers = get_all_shop_customer_records($conn, $shop_id);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Messages" => $messages,
                "Customers" => $shop_customers
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Could not get messages"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        }



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

// business profile
if ($action === 'get-business-profile') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        // get receipt options
        $shop_details = get_shop_profile_details($conn, $shop_id, $added_by);
        $subscriptionPlan = get_shop_subscription($conn, $shop_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Profile" => $shop_details,
            "Subscription" => $subscriptionPlan
        );

        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if($action === 'get_business_profile_details') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql = "SELECT * FROM shops WHERE id='{$shop_id}'";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $profile;

            if(mysqli_num_rows($result) > 0) {

                $record = mysqli_fetch_assoc($result);

                $profile['businessName'] = encrypt_data($record['name']);
                $profile['businessDescription'] = encrypt_data($record['description']);
                $profile['businessLocation'] = encrypt_data($record['location']);

                // get contacts
                $shop_contacts = get_shop_contacts($conn, $shop_id);
                $profile['business_contacts'] = $shop_contacts;
                $profile['subscriptionPlan'] = get_shop_subscription($conn, $shop_id);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "profile" => $profile
                );
        
                echo json_encode($data_insert);
                mysqli_close($conn);
                

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Profile does not exist!"
                );
        
                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Could not get business profile"
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

if ($action === 'check_today_closed_sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        
        $today = date('Y-m-d h:i:s', time());

        $sql = "SELECT * FROM closed_sales WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_closed, '%Y-%m-%d') = DATE_FORMAT('{$today}', '%Y-%m-%d') AND closed=1";
        $result = mysqli_query($conn, $sql);

        if($result) {

            if(mysqli_num_rows($result) > 0) {
                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "close" => encrypt_data('true')
                );
        
                echo json_encode($data_insert);
                mysqli_close($conn);
            } else {
                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "close" => encrypt_data('false')
                );
        
                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Could not get business profile"
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get_all_closed_sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql = "SELECT * FROM closed_sales WHERE shop_id='{$shop_id}' AND closed=1 ORDER BY id DESC";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $records=[];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $records[$cr]['date'] = encrypt_data($row['date_closed']);
                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "records" => $records
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Could not get business profile"
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'get_today_stock_taking_items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));
        $uid = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = date('Y-m-d h:i:s', time());

        $sql = "SELECT * FROM stock_taking_dates WHERE shop_id='{$shop_id}' AND
                DATE_FORMAT(date_started, '%Y-%m-%d') = DATE_FORMAT('{$today}', '%Y-%m-%d') AND
                completed=0 AND started_by='{$uid}'";
        $result = mysqli_query($conn, $sql);

        if($result) {

            if(mysqli_num_rows($result) > 0) {

                $records = [];
                $cr=0;

                while($row = mysqli_fetch_assoc($result)) {

                    // get the records
                    $stock_not_confirmed = get_stock_taking_items($conn, $row['id'], $shop_id);
                    $stock_confirmed = get_existing_stock_taking_items($conn, $row['id'], $shop_id);

                    if(count($stock_not_confirmed) > 0 && count($stock_confirmed) < 1) {
                        // no items have been counted
                        // return all the stock items
                        $data_insert=array(
                            "status" => "success",
                            "message" => "success",
                            "records" => $stock_not_confirmed,
                            "confirmed" => encrypt_data('none confirmed')
                        );
                
                        echo json_encode($data_insert);
                        mysqli_close($conn);

                    } else if(count($stock_not_confirmed) < 1 && count($stock_confirmed) > 0) {
                        // all items have been confirmed
                        $data_insert=array(
                            "status" => "success",
                            "message" => "success",
                            "records" => $stock_confirmed,
                            "confirmed" => encrypt_data('all confirmed')
                        );
                
                        echo json_encode($data_insert);
                        mysqli_close($conn);

                    } else if(count($stock_not_confirmed) > 0 && count($stock_confirmed) > 0)  {
                        // some items have not been confirmed
                        $new_items = array_merge($stock_not_confirmed, $stock_confirmed);

                        $data_insert=array(
                            "status" => "success",
                            "message" => "success",
                            "records" => $new_items,
                            "confirmed" => encrypt_data('some confirmed')
                        );
                
                        echo json_encode($data_insert);
                        mysqli_close($conn);

                    } else {
                        // no items exist for this shop
                        $data_insert=array(
                            "status" => "error",
                            "message" => "No items exist for this shop!"
                        );
                
                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    }

                }

            } else {
                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "confirmed" => encrypt_data('Not started'),
                    "today" => $today
                );
        
                echo json_encode($data_insert);
                mysqli_close($conn);   
            }

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Could not confirm!"
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}

if ($action === 'get_all_stock_taking_dates') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $today = date('Y-m-d h:i:s', time());
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql = "SELECT * FROM stock_taking_dates WHERE shop_id='{$shop_id}' AND completed=1 OR
                (shop_id='{$shop_id}' AND DATE_FORMAT(date_started, '%Y-%m-%d') = DATE_FORMAT('{$today}', '%Y-%m-%d') AND
                completed=0) ORDER BY id DESC";

        $result = mysqli_query($conn, $sql);

        if($result) {

            $records = [];
            $cr=0;

            if (mysqli_num_rows($result) > 0) {


                while($row = mysqli_fetch_assoc($result)) {
                    // date_diff(
                    // if(date('Y-m-d', $row['date_started']) === date('Y-m-d', time())) {
                    $origin = date_create($row['date_started']);
                    $target = date_create(date('Y-m-d', time()));
                    $interval = date_diff($origin, $target);

                    if($interval->format('%a') == 0) {

                        $records[$cr]['date_id'] = encrypt_data($row['id']);
                        $records[$cr]['date'] = $row['date_started'];

                        $started_by = decrypt_data(get_shop_username($conn, $row['started_by']));
                        $records[$cr]['started_by'] = $started_by;

                        $records[$cr]['today'] = 'true';
                        $cr++;

                    } else {

                        $stock_confirmed = get_stock_taking_date_id_confirmed_items($conn, $row['id'], $shop_id);
                        $records[$cr]['date_id'] = encrypt_data($row['id']);
                        $records[$cr]['date_started'] = $row['date_started'];

                        $started_by = decrypt_data(get_shop_username($conn, $row['started_by']));
                        $records[$cr]['started_by'] = $started_by;
                        $records[$cr]['date_completed'] = $row['completed_time'];

                        $records[$cr]['today'] = 'false';
                        $records[$cr]['less_items'] = $stock_confirmed['less_items'];
                        $records[$cr]['more_items'] = $stock_confirmed['more_items'];
                        $records[$cr]['confirmed'] = $stock_confirmed['confirmed'];

                        $cr++;
                            
                    }

                }

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "records" => $records
                );

                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "records" => $records
                );

                echo json_encode($data_insert);
                mysqli_close($conn);

            }

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Could not confirm!"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if ($action === 'check_shop_trial_period') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $today = date('Y-m-d h:i:s', time());
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql="SELECT * FROM shop_subscription_renewals
                WHERE shop_id='{$shop_id}' ORDER BY id DESC LIMIT 1";
        $result=mysqli_query($conn, $sql);

        if($result) {

            if(mysqli_num_rows($result) > 0) {

                $free_trial = 0;

                while($row = mysqli_fetch_assoc($result)) {

                    $date_reniewed = new DateTime($row['date_renewed']);
                    $TODAY = date('Y-m-d', time());

                    $TODAY = date_create($TODAY);

                    $difference = date_diff($date_reniewed, $TODAY);

                    $diff = $difference->format("%a");

                    if($diff <= 31) {

                        if($row['free_trial'] == 1) {
                            $free_trial = 1;
                        }

                    }

                }

                $data_insert = array(
                    "status" => "success",
                    "message" => "success",
                    "free_trial" => encrypt_data($free_trial),
                );
                // return the error
                echo json_encode($data_insert);
                // close connection
                mysqli_close($conn);
    
            } else {

                $data_insert = array(
                    "status" => "error",
                    "message" => "No subscription!"
                );
                // return the error
                echo json_encode($data_insert);
                // close connection
                mysqli_close($conn);

            }

        } else {
            $data_insert = array(
            "status" => "error",
            "message" => "Could not get subscription status!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get_shop_sent_sms') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $today = date('Y-m-d h:i:s', time());
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql="SELECT DISTINCT DATE_FORMAT(date_sent, '%Y-%m') AS date_sent FROM sent_messages
                WHERE shop_id='{$shop_id}' ORDER BY DATE_FORMAT(date_sent, '%Y-%m') DESC";
        $result=mysqli_query($conn, $sql);

        if($result) {

            $months = [];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $month = $row['date_sent'].'-01';

                $month_sms = get_shop_month_sms($conn, $shop_id, $month);

                $months[$cr]['month'] = $month;
                $months[$cr]['messages'] = $month_sms;

                $cr++;
                
            }

            $data_insert = array(
                "status" => "success",
                "message" => "success",
                "messages" => $months
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn);

        } else {

            $data_insert = array(
            "status" => "error",
            "message" => "Could not get subscription status!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'check_current_month_sms') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $today = date('Y-m-d h:i:s', time());
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql="SELECT IFNULL(SUM(recipients_count), 0) AS total FROM sent_messages
                WHERE shop_id='{$shop_id}' AND DATE_FORMAT(date_sent, '%Y-%m') = DATE_FORMAT('{$today}', '%Y-%m')";
        $result=mysqli_query($conn, $sql);

        if($result) {

            $record = mysqli_fetch_assoc($result);

            $data_insert = array(
                "status" => "success",
                "message" => "success",
                "total" => encrypt_data($record['total'])
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn);

        } else {

            $data_insert = array(
            "status" => "error",
            "message" => "Could not get current sms!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}

if ($action === 'get-shop-customers') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop_id = mysqli_escape_string($conn, decrypt_data($request->shop_id));

        $sql = "SELECT * FROM customers WHERE shop_id='{$shop_id}' AND deleted=0";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $customers = [];
            $cr = 0;

            while ($row=mysqli_fetch_assoc($result)) {

                $customers[$cr]['id'] = encrypt_data($row['id']);
                $customers[$cr]['name'] = $row['name'];
                $customers[$cr]['mobile_number'] = $row['mobile_number'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Customers" => $customers
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Something bad happened"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        }



    } catch (Exception $e) {
        
        $data_insert=array(
        //"data" => $data_from_server,
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}



# End Stock section
?>
