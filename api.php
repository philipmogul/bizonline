<?php

$http_origin = $_SERVER['HTTP_ORIGIN'];


 // Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Origin: $http_origin");
    // header("Access-Control-Allow-Origin: localhost:8100");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
} else {
    header("Access-Control-Allow-Origin: *");
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

require_once("JWT/autoload.php");
use \Firebase\JWT\JWT;

// secret key can be a random string and keep in secret from anyone
define('SECRET_KEY', 'BizOnlineKey');
// Algorithm used to sign the token
define('ALGORITHM', 'HS256');

date_default_timezone_set('Africa/Nairobi');

$TODAY = date('Y-m-d H:i:s', time());

$servername = "database-1.chm6scy2wvib.eu-west-1.rds.amazonaws.com";
$username = "admin";
$password = "ROVyPaSmp2oR2GwTA9Ge";
$dbname = "contactform";

// $servername = "localhost";
// $username = "root";
// $password = "steve254";
// $dbname = "fedhamar_bizonline";

$conn=mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$postdata = file_get_contents("php://input");
$request = json_decode($postdata);

// DEFINE GLOBAL FUNCTIONS
// function to encrypt string data
function encrypt_data($string) {

    // define AES encryption key and Initialization Vector
    // convert to hex
    $AESKEY_hex = bin2hex("bizonlinekeyforencryptinginfo444");
    $AESIV_hex =  bin2hex("bizonlinekeyfore");
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
    $AESKEY_hex = bin2hex("bizonlinekeyforencryptinginfo444");
    $AESIV_hex =  bin2hex("bizonlinekeyfore");
    // convert to bin
    $AESKEY_bin = hex2bin($AESKEY_hex);
    $AESIV_bin =  hex2bin($AESIV_hex);

    return openssl_decrypt($decoded_string, 'aes-256-cbc', $AESKEY_bin, 0, $AESIV_bin);
}

function run_query($conn1, $sql) {
    $result = mysqli_query($conn1, $sql);

    if($result) {

        return $result;

    } else {
        $data_insert = array(
            "status" => "error",
            "message" => "Query error!",
            "error" => mysqli_error($conn1)
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}





function get_shop_receipt_options($conn1) {
    $sql = "SELECT * FROM shop_receipt_options";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $options=[];

        while($row = mysqli_fetch_assoc($result)) {
            $options['image_url'] = encrypt_data($row['image_url']);
            $options['paybill'] = encrypt_data($row['paybill']);
            $options['paybill_account'] = encrypt_data($row['paybill_account_name']);
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

function get_shop_contacts($conn1) {
    $sql = "SELECT * FROM shop_contacts";
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

function get_shop_user_roles($conn1, $user_id) {

    $sql = "SELECT * FROM user_rights WHERE user_id='{$user_id}'";
    $result=run_query($conn1, $sql);

    $user=null;

    while($row=mysqli_fetch_assoc($result)) {

        $user['sell_cash'] = encrypt_data($row['sell_cash']);
        $user['sell_debtors'] = encrypt_data($row['sell_debtors']);
        $user['sell_transfer'] = encrypt_data($row['sell_transfer']);
        $user['reversing_cash'] = encrypt_data($row['reversing_cash']);
        $user['reversing_debtors'] = encrypt_data($row['reversing_debtors']);
        $user['reversing_transfer'] = encrypt_data($row['reversing_transfer']);
        $user['receiving_debtor_payments'] = encrypt_data($row['receiving_debtor_payments']);
        $user['adding_new_debtors'] = encrypt_data($row['adding_new_debtors']);
        $user['set_debtor_limits'] = encrypt_data($row['set_debtor_limits']);
        $user['adding_transfer_customers'] = encrypt_data($row['adding_transfer_customers']);
        $user['adding_expenses'] = encrypt_data($row['adding_expenses']);
        $user['adding_supplier_stock'] = encrypt_data($row['adding_supplier_stock']);
        $user['adding_suppliers'] = encrypt_data($row['adding_suppliers']);
        $user['adding_stock_positive'] = encrypt_data($row['adding_stock_positive']);
        $user['adding_stock_negative'] = encrypt_data($row['adding_stock_negative']);
        $user['adding_stock_returns'] = encrypt_data($row['adding_stock_returns']);
        $user['managing_shop_locations'] = encrypt_data($row['managing_shop_locations']);
        $user['adding_interlocation'] = encrypt_data($row['adding_interlocation']);
        $user['updating_buying_price'] = encrypt_data($row['updating_buying_price']);
        $user['add_waiting_customer'] = encrypt_data($row['add_waiting_customer']);
        $user['print_items'] = encrypt_data($row['print_items']);
        $user['current_sales'] = encrypt_data($row['current_sales']);
        $user['close_sales'] = encrypt_data($row['close_sales']);
        $user['manage_stock'] = encrypt_data($row['manage_stock']);
        $user['stock_taking'] = encrypt_data($row['stock_taking']);
        $user['sending_customer_messages'] = encrypt_data($row['sending_customer_messages']);  
        $user['view_stock_report'] = encrypt_data($row['view_stock_report']);  
        $user['view_sales_report'] = encrypt_data($row['view_sales_report']);  
    
    };

    return $user;

}


function get_user_location_rights($conn1, $user_id, $location_id) {

    // check if user is admin
    $role = get_user_record($conn1, $user_id)['role'];

    $rights=null;
    
    if(decrypt_data($role)==='admin') {

        $rights['sell_cash'] = encrypt_data(1);
        $rights['sell_debtor'] = encrypt_data(1);
        $rights['transfer'] = encrypt_data(1);
        $rights['reversing_sales'] = encrypt_data(1);
        $rights['add_supplier_stock'] = encrypt_data(1);
        $rights['add_stock_negative'] = encrypt_data(1);
        $rights['add_stock_positive'] = encrypt_data(1);
        $rights['manage_stock'] = encrypt_data(1);
        $rights['stock_taking'] = encrypt_data(1);

        return $rights;

    } else {


        $sql = "SELECT * FROM user_location_rights WHERE user_id='{$user_id}' AND location_id='{$location_id}'";
        $result = run_query($conn1, $sql);

        if(mysqli_num_rows($result) > 0) {

            $row=mysqli_fetch_assoc($result);

            $rights['sell_cash'] = encrypt_data($row['sell_cash']);
            $rights['sell_debtor'] = encrypt_data($row['sell_debtor']);
            $rights['transfer'] = encrypt_data($row['transfer']);
            $rights['reversing_sales'] = encrypt_data($row['reversing_sales']);
            $rights['add_supplier_stock'] = encrypt_data($row['add_supplier_stock']);
            $rights['add_stock_negative'] = encrypt_data($row['add_stock_negative']);
            $rights['add_stock_positive'] = encrypt_data($row['add_stock_positive']);
            $rights['manage_stock'] = encrypt_data($row['manage_stock']);
            $rights['stock_taking'] = encrypt_data($row['stock_taking']);

        } else {

            $rights['sell_cash'] = encrypt_data(0);
            $rights['sell_debtor'] = encrypt_data(0);
            $rights['transfer'] = encrypt_data(0);
            $rights['reversing_sales'] = encrypt_data(0);
            $rights['add_supplier_stock'] = encrypt_data(0);
            $rights['add_stock_negative'] = encrypt_data(0);
            $rights['add_stock_positive'] = encrypt_data(0);
            $rights['manage_stock'] = encrypt_data(0);
            $rights['stock_taking'] = encrypt_data(0);
        }

        return $rights;

    }

}

function get_all_user_locations_rights($conn1, $user_id) {

    $sql = "SELECT DISTINCT location_id FROM user_location_rights";
    $result = run_query($conn1, $sql);

    $locations=[];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $locations[$cr]['id'] = $row['location_id'];
        $cr++;
    }

    return $locations;
}


function check_on_going_stock_taking($conn1, $location_id) {

    $today = get_today();

    $sql = "SELECT * FROM stock_taking_dates WHERE completed=0
            AND location_id='{$location_id}'
            AND DATE_FORMAT(date_started, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {
        return true;
    } else {
        return false;
    }
}


function confirm_stock_taking_today($conn1) {
    $today = date('Y-m-d', time());
    $day_name = date('l', time());

    $week_day_number = date('N', time());

    // $day = strtolower($day_name).'_take';

    $sql = "SELECT status AS bool FROM stock_taking_settings WHERE week_day='{$week_day_number}' AND status=1";
    $result = mysqli_query($conn1, $sql);
    if($result) {

        if(mysqli_num_rows($result) > 0) {

            $total_locations = mysqli_num_rows($result);

            // $record = mysqli_fetch_assoc($result);

            // if($record['bool'] == 1) {

                $sql = "SELECT * FROM stock_taking_dates WHERE completed=0 AND DATE_FORMAT(date_started, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')";
                $result = mysqli_query($conn1, $sql);
                if($result) {

                    if(mysqli_num_rows($result) > 0) {
                        return 1;
                    } else {

                        // check if it should be started
                        $sql = "SELECT * FROM stock_taking_dates WHERE DATE_FORMAT(date_started, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')";
                        $result = mysqli_query($conn1, $sql);
                        if($result) {

                            if(mysqli_num_rows($result) === $total_locations) {
                                return 0;
                            } else {
                                return 1;
                            }

                        } else {
                            $data_insert = array(
                                "status" => "error",
                                "message" => "Could not confirm stock taking3!"
                            );
                            // return the error
                            echo json_encode($data_insert);
                            // close connection
                            mysqli_close($conn1);
                    
                            exit();
                        }
                    }

                } else {
                    $data_insert = array(
                        "status" => "error",
                        "message" => "Could not confirm stock taking2!"
                    );
                    // return the error
                    echo json_encode($data_insert);
                    // close connection
                    mysqli_close($conn1);
            
                    exit();
                }

            // }

            // return $record['bool'];

        } else {
            return 0;
        }

    } else {
        $data_insert = array(
        "status" => "error",
        "message" => "Could not confirm stock taking!"
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }
}

function get_user_shop_details($conn1, $user_id) {

    $sql="SELECT * FROM shop";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $return_value=null;

        $shop_details = null;
        $shop_id = '';

        while($row=mysqli_fetch_assoc($result)) {

            $shop_details['name'] = encrypt_data($row['name']);
            $shop_details['description'] = encrypt_data($row['description']);
            $shop_details['location'] = encrypt_data($row['location']);
            $shop_details['online_offline'] = encrypt_data($row['online_offline_mode']);

            $shop_details['imageUrl'] = get_shop_receipt_options($conn1)['image_url'];
        }


        $shop_contacts = get_shop_contacts($conn1);
        $user_roles = get_shop_user_roles($conn1, $user_id);
        $stock_taking = confirm_stock_taking_today($conn1);

        $return_value['shop_details'] = $shop_details;
        $return_value['contacts'] = $shop_contacts;
        $return_value['user_roles'] = $user_roles;
        $return_value['stock_taking'] = encrypt_data($stock_taking);

        return $return_value;

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

function get_category($conn1, $category_id) {
    $sql = "SELECT * FROM stock_categories WHERE id='{$category_id}'";
    $result = run_query($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    $category=[];

    $category['id'] = encrypt_data($record['id']);
    $category['name'] = $record['category_name'];

    return $category;
}

function get_all_categories($conn1) {
    $sql = "SELECT * FROM stock_categories WHERE deleted=0";
    $result = run_query($conn1, $sql);

    $categories=[];
    $cr=0;

    while($record = mysqli_fetch_assoc($result)) {
        $categories[$cr]['id'] = encrypt_data($record['id']);
        $categories[$cr]['name'] = $record['category_name'];

        $cr++;
    }

    return $categories;
}

function get_user_record($conn1, $user_id) {
    $sql = "SELECT * FROM users WHERE id='{$user_id}'";
    $result = run_query($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    $user=[];

    $user['id'] = encrypt_data($user_id);
    $user['name'] = $record['username'];
    $user['image_url'] = $record['image_url'];
    $user['mobile'] = $record['mobile'];
    $user['password'] = encrypt_data($record['password']);
    $user['role'] = encrypt_data($record['role']);
    $user['disabled'] = encrypt_data($record['disabled']);

    return $user;
}

function get_item_selling_units($conn1, $item_id) {
    $sql = "SELECT * FROM stock_units WHERE item_id='{$item_id}' AND deleted=0 ORDER BY unit_quantity DESC";
    $result = run_query($conn1, $sql);

    $units=[];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $units[$cr]['id'] = encrypt_data($row['id']);
        $units[$cr]['unit_name'] = $row['unit_name'];
        $units[$cr]['unit_quantity'] = $row['unit_quantity'];
        $units[$cr]['min_selling_price'] = $row['unit_min_selling_price'];
        $units[$cr]['recom_selling_price'] = $row['unit_recom_selling_price'];
        $units[$cr]['markup'] = $row['markup'];
        $units[$cr]['markup_discount'] = $row['markup_discount'];
        $units[$cr]['date_created'] = $row['date_created'];

        $cr++;
    }

    return $units;
}

function get_item_vat_record($conn1, $vat_id) {
    $sql = "SELECT * FROM vat_records WHERE id='{$vat_id}'";
    $result = run_query($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    $vat=[];

    $vat['id'] = encrypt_data($record['id']);
    $vat['vat_percent'] = $record['vat_percent'];

    return $vat;
}


function get_shop_locations($conn1) {
    $sql = "SELECT * FROM shop_locations WHERE deleted=0";
    $result = run_query($conn1, $sql);

    $locations=[];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $locations[$cr]['id'] = encrypt_data($row['id']);
        $locations[$cr]['name'] = $row['name'];
        $locations[$cr]['color'] = $row['color'];
        $locations[$cr]['date_created'] = $row['date_created'];

        $cr++;
    }

    return $locations;
}


function get_shop_location_record($conn1, $location_id) {
    $sql = "SELECT * FROM shop_locations WHERE id='{$location_id}'";
    $result = run_query($conn1, $sql);

    $location=[];

    $row=mysqli_fetch_assoc($result);

    $location['id'] = encrypt_data($row['id']);
    $location['name'] = $row['name'];
    $location['color'] = $row['color'];
    $location['date_created'] = $row['date_created'];

    return $location;

}


function get_item_all_locations_quantity($conn1, $item_id) {
    $sql = "SELECT * FROM stock_locations WHERE item_id='{$item_id}' AND deleted=0";
    $result = run_query($conn1, $sql);

    $stock_locations=[];
    $locations = [];
    $cr=0;

    $total_quantity = 0;

    while($row = mysqli_fetch_assoc($result)) {
        $stock_locations[$cr]['id'] = encrypt_data($row['id']);
        $location = get_shop_location_record($conn1, $row['location_id']);
        $stock_locations[$cr]['location'] = $location;
        $stock_locations[$cr]['location_quantity'] = $row['location_quantity'];

        $locations[$cr]['location'] = $location;

        $total_quantity += $row['location_quantity'];

        $cr++;
    }

    $return=[];

    $return['locations_quantity'] = $stock_locations;
    $return['locations'] = $locations;
    $return['total_quantity'] = $total_quantity;

    return $return;
}



function get_item_location_quantity($conn1, $item_id, $location_id) {

    $sql = "SELECT IFNULL (a.quantity, 0) AS total_quantity,
                    IFNULL (b.quantity, 0) AS location_quantity
            FROM
            (SELECT item_id, SUM(location_quantity) AS quantity FROM stock_locations WHERE item_id='{$item_id}') AS a
            LEFT JOIN
            (SELECT item_id, location_quantity as quantity FROM stock_locations WHERE item_id='{$item_id}' AND location_id='{$location_id}') AS b
            ON a.item_id = b.item_id";
    $result = run_query($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    $total_quantity = $record['total_quantity'];
    $location_quantity = $record['location_quantity'];

    $return['total_quantity'] = $total_quantity;
    $return['location_quantity'] = $location_quantity;

    return $return;

}






function get_item_alert_quantity($conn1, $item_id) {

    $sql = "SELECT unit_id, IFNULL(min_quantity, 0) AS min_quantity
            FROM
            min_stock_items WHERE item_id='{$item_id}'";
    $result2 = run_query($conn1, $sql);

    $stock = null;

    if(mysqli_num_rows($result2) > 0) {

        $record = mysqli_fetch_assoc($result2);

        $alert_unit = get_unit_record($conn1, $record['unit_id']);
        // $alert_quantity = $record['min_quantity'];
        $stock['alert_unit'] = $alert_unit;
        $stock['alert_quantity'] = $record['min_quantity'];

    } else {
        $stock['alert_unit'] = null;
        $stock['alert_quantity'] = 0;
    }

    return $stock;

}



function get_vat_records($conn1) {
    $sql = "SELECT * FROM vat_records WHERE deleted=0 ORDER BY vat_percent ASC";
    $result = run_query($conn1, $sql);

    $records=[];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {
        $records[$cr]['id'] = encrypt_data($row['id']);
        $records[$cr]['vat_percent'] = $row['vat_percent'];
        $cr++;
    }

    return $records;
}

function get_item_details($conn1, $item_id) {
    $sql = "SELECT * FROM stock WHERE id='{$item_id}'";
    $result = run_query($conn1, $sql);

    $row = mysqli_fetch_assoc($result);

    $item=[];

    $item['id'] = encrypt_data($row['id']);
    $item['name'] = utf8_encode($row['name']);
    $item['buying_price'] = $row['buying_price'];
    $item['image'] = $row['image'];
    $item['last_edited'] = $row['last_edited'];

    return $item;
}


function get_item_full_details($conn1, $item_id) {
    $sql = "SELECT * FROM stock WHERE id='{$item_id}'";
    $result = run_query($conn1, $sql);

    $row = mysqli_fetch_assoc($result);

    $item=[];

    $item['id'] = encrypt_data($row['id']);
    $item['name'] = utf8_encode($row['name']);
    $item['category'] = get_category($conn1, $row['category_id']);
    $item['buying_price'] = $row['buying_price'];
    $item['image'] = $row['image'];
    $item['last_edited'] = $row['last_edited'];

    return $item;
}

function get_unit_record($conn1, $unit_id) {

    $sql = "SELECT * FROM stock_units WHERE id='{$unit_id}'";
    $result1 = run_query($conn1, $sql);

    if(mysqli_num_rows($result1) > 0) {
        $row = mysqli_fetch_assoc($result1);

        $result['id'] = encrypt_data($row['id']);
        $result['unit_name'] = $row['unit_name'];
        $result['unit_quantity'] = $row['unit_quantity'];
        $result['min_selling_price'] = $row['unit_min_selling_price'];
        $result['recom_selling_price'] = $row['unit_recom_selling_price'];
    
        return $result;
    } else {
        $result['id'] = encrypt_data('0');
        $result['unit_name'] = '-';
        $result['unit_quantity'] = 0;
        $result['min_selling_price'] = 0;
        $result['recom_selling_price'] = 0;
    
        return $result;
    }

    // $row = mysqli_fetch_assoc($result1);

    // $result['id'] = encrypt_data($row['id']);
    // $result['unit_name'] = $row['unit_name'];
    // $result['unit_quantity'] = $row['unit_quantity'];
    // $result['min_selling_price'] = $row['unit_min_selling_price'];
    // $result['recom_selling_price'] = $row['unit_recom_selling_price'];

    // return $result;
}


function get_invoice_items($conn1, $invoice_id) {
    $sql = "SELECT * FROM supplier_invoice_items WHERE invoice_id='{$invoice_id}' ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $items = [];
    $cr=0;

    $total = 0;

    while($row = mysqli_fetch_assoc($result)) {
        $unit = get_unit_record($conn1, $row['unit_id']);;

        $items[$cr]['id'] = encrypt_data($row['id']);
        $items[$cr]['item'] = get_item_details($conn1, $row['item_id']);
        $items[$cr]['location'] = get_shop_location_record($conn1, $row['location_id']);
        $items[$cr]['unit'] = $unit;
        $items[$cr]['unit_qty'] = $row['unit_quantity'];
        $items[$cr]['total_old_qty'] = $row['total_old_quantity'];
        $items[$cr]['old_bp'] = $row['old_buying_price'];
        $items[$cr]['buying_price'] = $row['unit_buying_price'];
        $items[$cr]['selling_price'] = $row['new_selling_price'];
        $items[$cr]['vat'] = get_item_vat_record($conn1, $row['vat_id']);


        // $total += $row['new_buying_price'] * ($row['unit_quantity'] * $unit['unit_quantity']);
        $total += $row['unit_buying_price'] * $row['unit_quantity'];

        $cr++;
    }

    $return['items'] = $items;
    $return['total'] = $total;

    return $return;
}

function get_supplier_invoice_items($conn1, $supplier_id) {
    $sql = "SELECT * FROM supplier_invoice_items WHERE invoice_id IN
            (SELECT id FROM supplier_invoices WHERE supplier_id='{$supplier_id}' AND approved=1)
            ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $items = [];
    $cr=0;

    $total = 0;

    while($row = mysqli_fetch_assoc($result)) {
        $unit1 = get_unit_record($conn1, $row['unit_id']);

        // check if there was any return
        $sql2 = "SELECT * FROM stock_returns WHERE invoice_item_id='{$row['id']}'";
        $result2 = run_query($conn1, $sql2);

        $quantity_returned = 0;

        if(mysqli_num_rows($result2) > 0) {

            while($row2=mysqli_fetch_assoc($result2)) {
                $unit = get_unit_record($conn1, $row2['unit_id']);

                $qty_returned = $unit['unit_quantity'] * $row2['unit_quantity'];

                $quantity_returned += $qty_returned;
            }

        }

        $items[$cr]['id'] = encrypt_data($row['id']);
        $items[$cr]['item'] = get_item_details($conn1, $row['item_id']);
        $items[$cr]['selling_units']  = get_item_selling_units($conn1, $row['item_id']);
        $items[$cr]['item_units'] = get_item_details($conn1, $row['item_id']);
        $items[$cr]['location'] = get_shop_location_record($conn1, $row['location_id']);
        $items[$cr]['unit'] = $unit1;
        $items[$cr]['unit_qty'] = $row['unit_quantity'];
        $items[$cr]['total_old_qty'] = $row['total_old_quantity'];
        $items[$cr]['old_bp'] = $row['old_buying_price'];
        $items[$cr]['buying_price'] = $row['unit_buying_price'];
        $items[$cr]['selling_price'] = $row['new_selling_price'];
        $items[$cr]['vat'] = get_item_vat_record($conn1, $row['vat_id']);
        $items[$cr]['date'] = $row['date_added'];
        $items[$cr]['quantity_returned'] = $quantity_returned;


        $cr++;
    }


    return $items;
}

function get_supplier_incomplete_returns($conn1, $supplier_id, $added_by) {
    
    $sql = "SELECT * FROM stock_returns WHERE supplier_id='{$supplier_id}' AND added_by='{$added_by}' AND transaction_code=''";
    $result = run_query($conn1, $sql);

    $items = [];
    $cr = 0;

    while($row = mysqli_fetch_assoc($result)) {
        $items[$cr]['id'] = encrypt_data($row['id']);
        $items[$cr]['item'] = get_item_details($conn1, $row['item_id']);
        $items[$cr]['location'] = get_shop_location_record($conn1, $row['location_id']);
        $items[$cr]['unit'] = get_unit_record($conn1, $row['unit_id']);
        $items[$cr]['unit_qty'] = $row['unit_quantity'];
        $items[$cr]['buying_price'] = $row['buying_price'];

        $cr++;
    }

    return $items;

}

function get_supplier_return_code_items($conn1, $code) {
    
    $sql = "SELECT * FROM stock_returns WHERE transaction_code='{$code}'";
    $result = run_query($conn1, $sql);

    $items = [];
    $cr = 0;

    while($row = mysqli_fetch_assoc($result)) {
        $items[$cr]['id'] = encrypt_data($row['id']);
        $items[$cr]['item'] = get_item_details($conn1, $row['item_id']);
        $items[$cr]['location'] = get_shop_location_record($conn1, $row['location_id']);
        $items[$cr]['unit'] = get_unit_record($conn1, $row['unit_id']);
        $items[$cr]['unit_qty'] = $row['unit_quantity'];
        $items[$cr]['buying_price'] = $row['buying_price'];

        $cr++;
    }

    return $items;

}


function get_supplier_unconfirmed_invoices($conn1, $id) {
    $sql = "SELECT * FROM supplier_invoices WHERE approved=0 AND supplier_id='{$id}' ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $invoices = [];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $invoices[$cr]['id'] = encrypt_data($row['id']);
        $invoices[$cr]['invoice_no'] = $row['invoice_number'];
        $invoices[$cr]['date_supplied'] = $row['date_supplied'];
        $invoices[$cr]['date_created'] = $row['date_created'];
        $invoices[$cr]['uid'] = $row['user_id'];
        $invoices[$cr]['created_by'] = get_user_record($conn1, $row['user_id'])['name'];
        $invoice_items = get_invoice_items($conn1, $row['id']);
        $invoices[$cr]['items'] = $invoice_items['items'];
        $invoices[$cr]['total'] = $invoice_items['total'];

        $cr++;

    }

    return $invoices;
}

function get_today() {

    // echo date('Y-m-d H:i:s');
    // exit();
    date_default_timezone_set('Africa/Nairobi');
    return date('Y-m-d H:i:s', time());
}

function lock_item($conn1, $item_id, $location_id, $user_id) {
    $sql = "SELECT * FROM item_lock_status WHERE item_id='{$item_id}' AND location_id='{$location_id}'";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {

        $sql = "UPDATE item_lock_status SET locked=1, user_id='{$user_id}'
                WHERE item_id='{$item_id}' AND location_id='{$location_id}'";
        $result = run_query($conn1, $sql);
    } else {
        $today = get_today();

        $sql = "INSERT INTO item_lock_status
                (item_id, location_id, locked, start_time, user_id)
                VALUES ('{$user_id}', '{$location_id}', 1, '{$today}', '{$user_id}')";
        $result = run_query($conn1, $sql);
    }
}

function unlock_item($conn1, $item_id, $location_id, $user_id) {
    $sql = "UPDATE item_lock_status SET locked=0
            WHERE user_id='{$user_id}' AND locked=1";
    $result = run_query($conn1, $sql);
}

function unlock_all_user_locked_items($conn1, $user_id) {
    $sql = "UPDATE item_lock_status SET locked=0
            WHERE user_id='{$user_id}'";
    run_query($conn1, $sql);

    $sql = "UPDATE table_locks SET locked=0, unlock_time=CURRENT_DATE() WHERE user_id='{$user_id}' AND locked=1";
    run_query($conn1, $sql);
}

function check_item_lock_status($conn1, $item_id, $location_id, $user_id) {
    $sql = "SELECT * FROM item_lock_status WHERE locked=1 AND item_id='{$item_id}' AND location_id='{$location_id}'";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {

        $record = mysqli_fetch_assoc($result);

        if($record['user_id'] === $user_id) {

            return true;

        } else {

            $user_locked = get_user_record($conn1, $record['user_id']);
            $name = $user_locked['name'];

            $data_insert=array(
                "status" => "error",
                "message" => "locked",
                "user" => $name
            );
            echo json_encode($data_insert);
            mysqli_close($conn1);

            exit();
        }

    } else {

        $today = get_today();

        $sql = "INSERT INTO item_lock_status
                (item_id, location_id, locked, start_time, user_id)
                VALUES ('{$item_id}', '{$location_id}', 1, '{$today}', '{$user_id}')";

        $result = run_query($conn1, $sql);

        return true;

    }
}


function check_item_lock_status_selling($conn1, $item_id, $location_id, $user_id) {
    $sql = "SELECT * FROM item_lock_status WHERE locked=1 AND item_id='{$item_id}' AND location_id='{$location_id}'";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {

        $record = mysqli_fetch_assoc($result);

        if($record['user_id'] === $user_id) {

            return true;

        } else {

            return false;
        }

    } else {

        $today = get_today();

        $sql = "INSERT INTO item_lock_status
                (item_id, location_id, locked, start_time, user_id)
                VALUES ('{$item_id}', '{$location_id}', 1, '{$today}', '{$user_id}')";

        $result = run_query($conn1, $sql);

        return true;

    }
}


function get_temp_customer_items($conn1, $customer, $user_id) {
    $customer = mysqli_escape_string($conn1, $customer);

    $sql = "SELECT * FROM temp_waiting_customers 
            WHERE customer_name='{$customer}' AND sold_by='{$user_id}' ORDER BY id DESC";

    $result = run_query($conn1, $sql);

    $cart_items = [];
    $cr=0;

    $total = 0;

    while($row=mysqli_fetch_assoc($result)) {

        $item_id = $row['item_id'];
        $location_id = $row['location_id'];
        $unit_id = $row['unit_id'];

       
        $recom_sp = $row['recom_selling_price'];
        $unit_sp = $row['selling_price'];

        // $cart_items[$cr]['id'] = encrypt_data($row['id']);
        // $cart_items[$cr]['item_id'] = $item_id;
        // $cart_items[$cr]['location_id'] = $location_id;
        // $cart_items[$cr]['unit_id'] = $unit_id;
        // $cart_items[$cr]['unit_qty'] = $unit_qty;
        // $cart_items[$cr]['unit_bp'] = $unit_bp;
        // $cart_items[$cr]['min_sp'] = $min_sp;
        // $cart_items[$cr]['recom_sp'] = $recom_sp;
        // $cart_items[$cr]['sp'] = $unit_sp;



        $item = get_item_details($conn1, $item_id);
        $location = get_shop_location_record($conn1, $location_id);
        $unit = get_unit_record($conn1, $unit_id);

        $unit_qty = $row['unit_quantity'];
        $unit_bp = $row['buying_price'];
        $min_sp = $row['min_selling_price'];
        $unit_sp = $row['selling_price'];

        $cart_items[$cr]['id'] = encrypt_data($row['id']);
        $cart_items[$cr]['item'] = $item;
        $cart_items[$cr]['item_id'] = $item_id;
        $cart_items[$cr]['location'] = $location;
        $cart_items[$cr]['location_id'] = $location_id;
        $cart_items[$cr]['unit'] = $unit;
        $cart_items[$cr]['unit_id'] = $unit_id;
        $cart_items[$cr]['unit_qty'] = $unit_qty;
        $cart_items[$cr]['unit_bp'] = $unit_bp;
        $cart_items[$cr]['min_sp'] = $min_sp;
        $cart_items[$cr]['recom_sp'] = $recom_sp;
        $cart_items[$cr]['qty'] = $unit_qty;
        $cart_items[$cr]['sp'] = $unit_sp;

        $total += $unit_sp * $unit_qty;

        $cr++;

    }

    $return['items'] = $cart_items;
    $return['total'] = $total;

    return $return;

}

function get_my_cash_customers($conn1) {
    $sql = "SELECT * FROM customers WHERE customer_type='cash' AND deleted=0";
    $result = run_query($conn1, $sql);

    $customers=[];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $customers[$cr]['id'] = encrypt_data($row['id']);
        $customers[$cr]['name'] = $row['name'];

        // get total amount bought
        $sql2 = "SELECT IFNULL(SUM(unit_quantity*selling_price), 0) AS total FROM sold_stock WHERE customer_id='{$row['id']}'";
        $result2 = run_query($conn1, $sql2);

        $record = mysqli_fetch_assoc($result2);


        // get any reversed
        $sql3 = "SELECT SUM(quantity_returned*selling_price) AS total FROM `sold_stock_reversed` WHERE sale_id IN (SELECT id FROM sold_stock WHERE customer_id='{$row['id']}')";
        $result3 = run_query($conn1, $sql3);
        $record2 = mysqli_fetch_assoc($result3);

        $total_reversed = $record2['total'];

        $total = $record['total'] - $total_reversed;

        $customers[$cr]['sales'] = $total;
        $customers[$cr]['items'] = [];
        $customers[$cr]['total'] = 0;


        $cr++;

    }

    return $customers;

}



function get_temp_my_customer_items($conn1, $user_id) {

    $customers = get_my_cash_customers($conn1);

    foreach($customers as $index => $row) {

        $id=decrypt_data($row['id']);
        
        // get the items
        $sql = "SELECT * FROM temp_transactions 
                WHERE customer_id='{$id}'
                AND sold_by='{$user_id}'
                ORDER BY id DESC";

        $result = run_query($conn1, $sql);

        $cart_items = [];
        $cr1=0;

        $total = 0;

        while($row=mysqli_fetch_assoc($result)) {

            $item_id = $row['item_id'];
            $location_id = $row['location_id'];
            $unit_id = $row['unit_id'];

            $recom_sp = $row['recom_selling_price'];
            $unit_sp = $row['selling_price'];

            $item = get_item_details($conn1, $item_id);
            $location = get_shop_location_record($conn1, $location_id);
            $unit = get_unit_record($conn1, $unit_id);

            $unit_qty = $row['unit_quantity'];
            $unit_bp = $row['buying_price'];
            $min_sp = $row['min_selling_price'];
            $unit_sp = $row['selling_price'];

            $cart_items[$cr1]['id'] = encrypt_data($row['id']);
            $cart_items[$cr1]['item'] = $item;
            $cart_items[$cr1]['item_id'] = $item_id;
            $cart_items[$cr1]['location'] = $location;
            $cart_items[$cr1]['location_id'] = $location_id;
            $cart_items[$cr1]['unit'] = $unit;
            $cart_items[$cr1]['unit_id'] = $unit_id;
            $cart_items[$cr1]['unit_qty'] = $unit_qty;
            $cart_items[$cr1]['unit_bp'] = $unit_bp;
            $cart_items[$cr1]['min_sp'] = $min_sp;
            $cart_items[$cr1]['recom_sp'] = $recom_sp;
            $cart_items[$cr1]['qty'] = $unit_qty;
            $cart_items[$cr1]['sp'] = $unit_sp;

            $total += $unit_sp * $unit_qty;

            $cr1++;

        }

        $customers[$index]['items'] = $cart_items;
        $customers[$index]['total'] = $total;

    }

    return $customers;

}



function get_temp_waiting_transactions($conn1, $user_id) {

    $sql = "SELECT DISTINCT customer_name FROM temp_waiting_customers WHERE sold_by='{$user_id}'";
    $result = run_query($conn1, $sql);

    $customers = [];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $customers[$cr]['customer'] = $row['customer_name'];

        $items = get_temp_customer_items($conn1, $row['customer_name'], $user_id);
        
        $customers[$cr]['items'] = $items['items'];
        $customers[$cr]['total'] = $items['total'];

        $cr++;
    }
    return $customers;
}

function get_duplicated_transactions($conn1, $user_id) {
    $today = get_today();
    $sql = "SELECT a.transaction_code, (a.cash+a.mpesa+a.bank) AS total, b.date_sold
            FROM
            (SELECT * FROM sales_payments WHERE sold_by='{$user_id}' AND
                DATE_FORMAT(`date_paid`, '%Y-%m-%d')<=DATE_FORMAT('{$today}', '%Y-%m-%d')) AS a
            INNER JOIN
            (SELECT MAX(id) AS id, SUM(cash+mpesa+bank-amount_reversed) AS total1,
                SUM(cash+mpesa+bank)/2 AS total2,
                COUNT(*) AS total,
                DATE_FORMAT(`date_paid`, '%Y-%m-%d %H:%i:%s') AS date_sold
                FROM `sales_payments` WHERE sold_by='{$user_id}' AND
                DATE_FORMAT(`date_paid`, '%Y-%m-%d')<=DATE_FORMAT('{$today}', '%Y-%m-%d')
            GROUP BY DATE_FORMAT(`date_paid`, '%Y-%m-%d %H:%i:%s')
            HAVING total1>total2 AND total>1
            ORDER BY DATE_FORMAT(`date_paid`, '%Y-%m-%d %H:%i:%s') DESC) AS b
            ON b.id=a.id ORDER BY date_sold DESC";

    $result = run_query($conn1, $sql);
    $records = [];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $records[$cr]['transaction_code'] = $row['transaction_code'];
        $records[$cr]['total'] = $row['total'];
        $records[$cr]['date_sold'] = $row['date_sold'];
        $cr++;
    }

    return $records;

}



function get_temp_debtor_transactions($conn1, $user_id, $customer_id) {

    $sql="SELECT * FROM temp_transactions WHERE selling_price<min_selling_price AND sold_by='{$user_id}' AND customer_id='{$customer_id}'";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {

        $data_insert=array(
            "status" => "error",
            "message" => "Selling prices for some items are not correct!",
        );
        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    } else {
        
        $sql = "SELECT * FROM temp_transactions WHERE sold_by='{$user_id}' AND customer_id='{$customer_id}' ORDER BY id DESC";
        $result = run_query($conn1, $sql);
    
        $cart_items = [];
        $cr=0;
    
        while($row=mysqli_fetch_assoc($result)) {
    
            $item_id = $row['item_id'];
            $location_id = $row['location_id'];
            $unit_id = $row['unit_id'];
    
            $unit_qty = $row['unit_quantity'];
            $unit_bp = $row['buying_price'];
            $min_sp = $row['min_selling_price'];
            $recom_sp = $row['recom_selling_price'];
            $unit_sp = $row['selling_price'];
    
            $cart_items[$cr]['id'] = encrypt_data($row['id']);
            $cart_items[$cr]['item_id'] = $item_id;
            $cart_items[$cr]['location_id'] = $location_id;
            $cart_items[$cr]['unit_id'] = $unit_id;
            $cart_items[$cr]['unit_qty'] = $unit_qty;
            $cart_items[$cr]['unit_bp'] = $unit_bp;
            $cart_items[$cr]['min_sp'] = $min_sp;
            $cart_items[$cr]['recom_sp'] = $recom_sp;
            $cart_items[$cr]['sp'] = $unit_sp;
    
            $cr++;
    
        }
    
        return $cart_items;
    }
    

}

function get_temp_inter_locations($conn1, $user_id) {

    $sql = "SELECT * FROM temp_inter_location WHERE transfered_by='{$user_id}' ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $cart_items = [];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $item = get_item_details($conn1, $row['item_id']);
        $location_from = get_shop_location_record($conn1, $row['location_1_id']);
        $location_to = get_shop_location_record($conn1, $row['location_2_id']);
        $unit = get_unit_record($conn1, $row['unit_id']);


        $cart_items[$cr]['id'] = encrypt_data($row['id']);
        $cart_items[$cr]['item'] = $item;
        $cart_items[$cr]['location_from'] = $location_from;
        $cart_items[$cr]['item_id'] = $row['item_id'];
        $cart_items[$cr]['location_1_id'] = $row['location_1_id'];
        $cart_items[$cr]['location_to'] = $location_to;
        $cart_items[$cr]['location_2_id'] = $row['location_2_id'];
        $cart_items[$cr]['unit_id'] = $row['unit_id'];
        $cart_items[$cr]['unit'] = $unit;
        $cart_items[$cr]['unit_quantity'] = $row['unit_quantity'];
        $cart_items[$cr]['location_from_old_quantity'] = $row['location_1_old_quantity'];

        $cr++;

    }

    return $cart_items;

}


function get_transaction_code_item_reversed_pieces($conn1, $code, $item_id, $sale_id) {
    $sql = "SELECT * FROM sold_stock_reversed WHERE sale_id='{$sale_id}' AND item_id='{$item_id}' AND transaction_code='{$code}'";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {

        $total_pieces = 0;

        while($row=mysqli_fetch_assoc($result)) {

            $unit = get_unit_record($conn1, $row['unit_id']);

            $unit_qty = $unit['unit_quantity'];

            $total_pieces += ($row['quantity_returned'] * $unit_qty);

        }

        return $total_pieces;

    } else {
        return 0;
    }
}

function get_transaction_code_debtor_reversed_total($conn1, $code) {

    $sql = "SELECT IFNULL(SUM(quantity_returned*selling_price), 0) AS reversed
            FROM sold_stock_reversed
            WHERE transaction_code='{$code}'";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {

        $row=mysqli_fetch_assoc($result);

        return $row['reversed'];

    } else {
        return 0;
    }
}


function get_supplier_info($conn1, $supplier_id) {
    $sql = "SELECT * FROM suppliers WHERE id='{$supplier_id}'";
    $result = run_query($conn1, $sql);
    
    $info=[];

    $row = mysqli_fetch_assoc($result);
    $info['id'] = encrypt_data($row['id']);
    $info['supplier_name'] = encrypt_data($row['supplier_name']);
    $info['date_created'] = encrypt_data($row['date_created']);

    return $info;
}

function get_date_user_added_stock($conn1, $user_id, $today) {
    // GET THE STOCK FROM SUPPLIER
    $sql = "SELECT * FROM supplier_invoices WHERE user_id='{$user_id}' AND DATE_FORMAT(date_created, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')";
    $result = run_query($conn1, $sql);

    $suppliers = [];
    $suppliers_total = 0;
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $suppliers[$cr]['id'] = encrypt_data($row['id']);
        $suppliers[$cr]['supplier'] = get_supplier_info($conn1, $row['supplier_id']);
        $suppliers[$cr]['invoice_no'] = $row['invoice_number'];
        $suppliers[$cr]['approved'] = $row['approved'];

        $items = get_invoice_items($conn1, $row['id']);

        $suppliers[$cr]['invoice_items'] = $items['items'];
        $suppliers[$cr]['invoice_total'] = $items['total'];

        $suppliers_total += $items['total'];

        $cr++;
    }

    // GET STOCK POSITIVE
    $sql = "SELECT * FROM stock_positive WHERE added_by='{$user_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')";
    $result = run_query($conn1, $sql);

    $stock_positive_items=[];
    $stock_positive_total=0;
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $item = get_item_details($conn1, $row['item_id']);
        $stock_positive_items[$cr]['item'] = get_item_details($conn1, $row['item_id']);
        $stock_positive_items[$cr]['item_units'] =get_item_selling_units($conn1, $row['item_id']);
        $stock_positive_items[$cr]['location'] = get_shop_location_record($conn1, $row['location_id']);
        $stock_positive_items[$cr]['quantity_added'] = $row['total_quantity_added'];
        $stock_positive_items[$cr]['buying_price_per_piece'] = $row['buying_price_per_piece'];
        
        $stock_positive_total += $row['total_quantity_added'] * $row['buying_price_per_piece'];

        $cr++;
    }

    // GET STOCK NEGATIVE
    $sql = "SELECT * FROM stock_negative WHERE removed_by='{$user_id}' AND DATE_FORMAT(date_removed, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')";
    $result = run_query($conn1, $sql);

    $stock_negative_items=[];
    $stock_negative_total=0;
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $item = get_item_details($conn1, $row['item_id']);
        $stock_negative_items[$cr]['item'] = get_item_details($conn1, $row['item_id']);
        $stock_negative_items[$cr]['item_units'] =get_item_selling_units($conn1, $row['item_id']);
        $stock_negative_items[$cr]['location'] = get_shop_location_record($conn1, $row['location_id']);
        $stock_negative_items[$cr]['quantity_removed'] = $row['total_quantity_removed'];
        $stock_negative_items[$cr]['buying_price_per_piece'] = $row['buying_price_per_piece'];
        
        $stock_negative_total += $row['total_quantity_removed'] * $row['buying_price_per_piece'];

        $cr++;
    }


    $return['added_supplier_stock'] = $suppliers;
    $return['total_supplier_stock'] = $suppliers_total;
    $return['stock_positive_total'] = $stock_positive_total;
    $return['stock_positive_items'] = $stock_positive_items;
    $return['stock_negative_total'] = $stock_negative_total;
    $return['stock_negative_items'] = $stock_negative_items;

    return $return;


}


function get_period_user_added_stock($conn1, $user_id, $from, $to) {
    // GET THE STOCK FROM SUPPLIER
    $sql = "SELECT * FROM supplier_invoices
            WHERE user_id='{$user_id}' AND
            DATE_FORMAT(date_created, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_created, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $suppliers = [];
    $suppliers_total = 0;
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $suppliers[$cr]['id'] = encrypt_data($row['id']);
        $suppliers[$cr]['supplier'] = get_supplier_info($conn1, $row['supplier_id']);
        $suppliers[$cr]['invoice_no'] = $row['invoice_number'];
        $suppliers[$cr]['approved'] = $row['approved'];
        $suppliers[$cr]['date_created'] = $row['date_created'];

        $items = get_invoice_items($conn1, $row['id']);

        $suppliers[$cr]['invoice_items'] = $items['items'];
        $suppliers[$cr]['invoice_total'] = $items['total'];

        $suppliers_total += $items['total'];

        $cr++;
    }

    // GET STOCK POSITIVE
    $sql = "SELECT * FROM stock_positive WHERE added_by='{$user_id}' AND
            DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $stock_positive_items=[];
    $stock_positive_total=0;
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $item = get_item_details($conn1, $row['item_id']);
        $stock_positive_items[$cr]['item'] = get_item_details($conn1, $row['item_id']);
        $stock_positive_items[$cr]['item_units'] =get_item_selling_units($conn1, $row['item_id']);
        $stock_positive_items[$cr]['location'] = get_shop_location_record($conn1, $row['location_id']);
        $stock_positive_items[$cr]['quantity_added'] = $row['total_quantity_added'];
        $stock_positive_items[$cr]['buying_price_per_piece'] = $row['buying_price_per_piece'];
        $stock_positive_items[$cr]['date_added'] = $row['date_added'];
        
        $stock_positive_total += $row['total_quantity_added'] * $row['buying_price_per_piece'];

        $cr++;
    }

    // GET STOCK NEGATIVE
    $sql = "SELECT * FROM stock_negative WHERE removed_by='{$user_id}' AND
            DATE_FORMAT(date_removed, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_removed, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $stock_negative_items=[];
    $stock_negative_total=0;
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $item = get_item_details($conn1, $row['item_id']);
        $stock_negative_items[$cr]['item'] = get_item_details($conn1, $row['item_id']);
        $stock_negative_items[$cr]['item_units'] =get_item_selling_units($conn1, $row['item_id']);
        $stock_negative_items[$cr]['location'] = get_shop_location_record($conn1, $row['location_id']);
        $stock_negative_items[$cr]['quantity_removed'] = $row['total_quantity_removed'];
        $stock_negative_items[$cr]['buying_price_per_piece'] = $row['buying_price_per_piece'];
        $stock_negative_items[$cr]['date_removed'] = $row['date_removed'];
        
        $stock_negative_total += $row['total_quantity_removed'] * $row['buying_price_per_piece'];

        $cr++;
    }


    $return['added_supplier_stock'] = $suppliers;
    $return['total_supplier_stock'] = $suppliers_total;
    $return['stock_positive_total'] = $stock_positive_total;
    $return['stock_positive_items'] = $stock_positive_items;
    $return['stock_negative_total'] = $stock_negative_total;
    $return['stock_negative_items'] = $stock_negative_items;

    return $return;


}

function get_paid_suppliers($conn1, $user_id, $from, $to) {
    // $sql = "SELECT
    //     CASE WHEN payment_channel='Stock return' THEN 'return'
    //     ELSE 'paid'
    //     END AS pay_type,
    //     IFNULL(SUM(amount_paid), 0) AS amount, user_id FROM supplier_payments
    //     WHERE user_id='{$user_id}' AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
    //     GROUP BY pay_type, user_id";

    
    $sql = "SELECT * FROM supplier_payments
            WHERE user_id='{$user_id}' AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
            DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')";
    
    $result = run_query($conn1, $sql);

    $return['returns'] = 0;
    $return['paid'] = 0;

    $payments = [];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {

        $payments[$cr]['supplier'] = decrypt_data(get_supplier_info($conn1, $row['supplier_id'])['supplier_name']);
        $payments[$cr]['amount_paid'] = $row['amount_paid'];
        $payments[$cr]['date_paid'] = $row['date_paid'];
        $payments[$cr]['payment_channel'] = $row['payment_channel'];
        $payments[$cr]['paid_by'] = $row['paid_by'];
        $payments[$cr]['paid_to'] = $row['paid_to'];


        if($row['payment_channel']=='Stock return') {
            $return['returns'] += $row['amount_paid'];
        } else {
            $return['paid'] += $row['amount_paid'];
        }

        $cr++;
    }

    $return['payments'] = $payments;

    return $return;

}



function get_debtor_record($conn1, $customer_id) {
    $sql = "SELECT * FROM customers WHERE id='{$customer_id}'";
    $result = run_query($conn1, $sql);

    $debtor=[];

    while($row = mysqli_fetch_assoc($result)) {
        $debtor['id'] = encrypt_data($row['id']);
        $debtor['name'] = $row['name'];
        $debtor['sales_limit'] = $row['sales_limit'];
        $debtor['mobile_number'] = $row['mobile_number'];
    }

    return $debtor;
}

function get_date_user_paid_debtors($conn1, $user_id, $today) {
    $sql = "SELECT * FROM debtors_payments WHERE added_by='{$user_id}' AND DATE_FORMAT(date_created, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')";
    $result = run_query($conn1, $sql);

    $paid_debtors = [];
    $cash_total = 0;
    $mpesa_total = 0;
    $bank_total = 0;
    $total_credited = 0;
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $paid_debtors[$cr]['id'] = encrypt_data($row['id']);
        $paid_debtors[$cr]['debtor'] = get_debtor_record($conn1, $row['customer_id']);
        $paid_debtors[$cr]['balance_before'] = $row['balance_before'];
        $paid_debtors[$cr]['cash'] = $row['cash'];
        $paid_debtors[$cr]['mpesa'] = $row['mpesa'];
        $paid_debtors[$cr]['bank'] = $row['bank'];
        $paid_debtors[$cr]['amount_credited'] = $row['amount_credited'];

        $cash_total += $row['cash'];
        $mpesa_total += $row['mpesa'];
        $bank_total += $row['bank'];
        $total_credited += $row['amount_credited'];

        $cr++;
    }

    $return['paid_debtors'] = $paid_debtors;
    $return['paid_debtors_cash'] = $cash_total;
    $return['paid_debtors_mpesa'] = $mpesa_total;
    $return['paid_debtors_bank'] = $bank_total;
    $return['paid_debtors_credited'] = $total_credited;

    return $return;
}


function get_period_user_paid_debtors($conn1, $user_id, $from, $to) {
    $sql = "SELECT * FROM debtors_payments WHERE added_by='{$user_id}' AND
            DATE_FORMAT(date_created, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_created, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $paid_debtors = [];
    $cash_total = 0;
    $mpesa_total = 0;
    $bank_total = 0;
    $total_credited = 0;
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $paid_debtors[$cr]['id'] = encrypt_data($row['id']);
        $paid_debtors[$cr]['debtor'] = get_debtor_record($conn1, $row['customer_id']);
        $paid_debtors[$cr]['balance_before'] = $row['balance_before'];
        $paid_debtors[$cr]['cash'] = $row['cash'];
        $paid_debtors[$cr]['mpesa'] = $row['mpesa'];
        $paid_debtors[$cr]['bank'] = $row['bank'];
        $paid_debtors[$cr]['amount_credited'] = $row['amount_credited'];
        $paid_debtors[$cr]['date_created'] = $row['date_created'];

        $cash_total += $row['cash'];
        $mpesa_total += $row['mpesa'];
        $bank_total += $row['bank'];
        $total_credited += $row['amount_credited'];

        $cr++;
    }

    $return['paid_debtors'] = $paid_debtors;
    $return['paid_debtors_cash'] = $cash_total;
    $return['paid_debtors_mpesa'] = $mpesa_total;
    $return['paid_debtors_bank'] = $bank_total;
    $return['paid_debtors_credited'] = $total_credited;

    return $return;
}


function get_payment_record($conn1, $payment_id) {
    $sql = "SELECT * FROM sales_payments WHERE id='{$payment_id}'";
    $result = run_query($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    return $record;
}


function get_debtor_transaction_code_items($conn1, $transaction_code) {
    $sql = "SELECT * FROM sold_stock_debtors WHERE transaction_code='{$transaction_code}' ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $cart_items=[];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {

        $item_id = $row['item_id'];
        $location_id = $row['location_id'];
        $unit_id = $row['unit_id'];

        $item = get_item_details($conn1, $item_id);
        $item['selling_units'] = get_item_selling_units($conn1, $item_id);

        $location = get_shop_location_record($conn1, $location_id);
        $unit = get_unit_record($conn1, $unit_id);

        $unit_qty = $row['unit_quantity'];
        $unit_bp = $row['buying_price'];
        $min_sp = $row['min_selling_price'];
        $recom_sp = $row['recom_selling_price'];
        $unit_sp = $row['selling_price'];

        // find any reversed pieces
        $pieces_reversed = get_transaction_code_item_reversed_pieces($conn1, $transaction_code, $item_id, $row['id']);
        
        $cart_items[$cr]['id'] = encrypt_data($row['id']);
        $cart_items[$cr]['item'] = $item;
        $cart_items[$cr]['location'] = $location;
        $cart_items[$cr]['unit'] = $unit;
        $cart_items[$cr]['qty'] = $unit_qty;
        $cart_items[$cr]['pieces_reversed'] = $pieces_reversed;
        $cart_items[$cr]['sp'] = $unit_sp;
        $cart_items[$cr]['bp'] = $unit_bp;
        $cart_items[$cr]['min_sp'] = $min_sp;

        

        // calculate the discount
        $discount = 0;
        if($unit_sp < $recom_sp) {
            $disc = $recom_sp - $unit_sp;
            $discount = $disc * $unit_qty;
        }
        $cart_items[$cr]['discount'] = $discount;

        // calculate any overcharge
        $overcharge = 0;
        if($unit_sp > $recom_sp) {
            $over = $unit_sp - $recom_sp;
            $overcharge = $over * $unit_qty;
        }
        $cart_items[$cr]['overcharge'] = $overcharge;

        // calculate profit
        $profit=0;
        if($unit_sp > $unit_bp) {
            $pro = $unit_sp - $unit_bp;
            $profit = $pro * $unit_qty;
        }
        $cart_items[$cr]['profit'] = $profit;

        $cr++;


    }

    return $cart_items;
}

function get_transfer_transaction_code_items($conn1, $transaction_code) {
    $sql = "SELECT * FROM sold_stock_transfers WHERE transaction_code='{$transaction_code}' ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $cart_items=[];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {

        $item_id = $row['item_id'];
        $location_id = $row['location_id'];
        $unit_id = $row['unit_id'];

        $item = get_item_details($conn1, $item_id);
        $item['selling_units'] = get_item_selling_units($conn1, $item_id);

        $location = get_shop_location_record($conn1, $location_id);
        $unit = get_unit_record($conn1, $unit_id);

        $unit_qty = $row['unit_quantity'];
        $unit_bp = $row['buying_price'];
        $min_sp = $row['buying_price'];
        $recom_sp = $row['buying_price'];
        $unit_sp = $row['buying_price'];

        // find any reversed pieces
        $pieces_reversed = get_transaction_code_item_reversed_pieces($conn1, $transaction_code, $item_id, $row['id']);
        
        $cart_items[$cr]['id'] = encrypt_data($row['id']);
        $cart_items[$cr]['item'] = $item;
        $cart_items[$cr]['location'] = $location;
        $cart_items[$cr]['unit'] = $unit;
        $cart_items[$cr]['qty'] = $unit_qty;
        $cart_items[$cr]['pieces_reversed'] = $pieces_reversed;
        $cart_items[$cr]['sp'] = $unit_sp;
        $cart_items[$cr]['bp'] = $unit_bp;
        $cart_items[$cr]['min_sp'] = $min_sp;

        

        // calculate the discount
        $discount = 0;
        if($unit_sp < $recom_sp) {
            $disc = $recom_sp - $unit_sp;
            $discount = $disc * $unit_qty;
        }
        $cart_items[$cr]['discount'] = $discount;

        // calculate any overcharge
        $overcharge = 0;
        if($unit_sp > $recom_sp) {
            $over = $unit_sp - $recom_sp;
            $overcharge = $over * $unit_qty;
        }
        $cart_items[$cr]['overcharge'] = $overcharge;

        // calculate profit
        $profit=0;
        if($unit_sp > $unit_bp) {
            $pro = $unit_sp - $unit_bp;
            $profit = $pro * $unit_qty;
        }
        $cart_items[$cr]['profit'] = $profit;

        $cr++;


    }

    return $cart_items;
}

function get_stock_and_cart_items($conn1, $user_id) {

    $stock= get_all_stock_new($conn1);

    $shop_locations = get_shop_locations($conn1);
    $vat_records = get_vat_records($conn1);

    $return['stock'] = $stock['stock'];
    $return['categories'] = $stock['categories'];
    $return['locations'] = $stock['locations'];
    $return['vat_records'] = $stock['vat_records'];



    $sql = "SELECT * FROM print_items WHERE sold_by='{$user_id}' ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $print_items = [];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $item_id = $row['item_id'];
        $location_id = $row['location_id'];
        $unit_id = $row['unit_id'];

        $item = get_item_details($conn1, $item_id);
        $location = get_shop_location_record($conn1, $location_id);
        $unit = get_unit_record($conn1, $unit_id);

        $unit_qty = $row['unit_quantity'];
        $unit_sp = $row['selling_price'];

        $print_items[$cr]['id'] = encrypt_data($row['id']);
        $print_items[$cr]['item'] = $item;
        $print_items[$cr]['location'] = $location;
        $print_items[$cr]['unit'] = $unit;
        $print_items[$cr]['qty'] = $unit_qty;
        $print_items[$cr]['sp'] = $unit_sp;

        $cr++;

    }



    $sql = "SELECT * FROM temp_transactions WHERE customer_id=0 AND sold_by='{$user_id}' ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $cart_items = [];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $item_id = $row['item_id'];
        $location_id = $row['location_id'];
        $unit_id = $row['unit_id'];

        $item = get_item_details($conn1, $item_id);
        $location = get_shop_location_record($conn1, $location_id);
        $unit = get_unit_record($conn1, $unit_id);

        $unit_qty = $row['unit_quantity'];
        $unit_bp = $row['buying_price'];
        $min_sp = $row['min_selling_price'];
        $unit_sp = $row['selling_price'];

        $cart_items[$cr]['id'] = encrypt_data($row['id']);
        $cart_items[$cr]['item'] = $item;
        $cart_items[$cr]['location'] = $location;
        $cart_items[$cr]['unit'] = $unit;
        $cart_items[$cr]['qty'] = $unit_qty;
        $cart_items[$cr]['sp'] = $unit_sp;

        $cr++;

    }




    $waiting = get_temp_waiting_transactions($conn1, $user_id);
    $my_customers = get_temp_my_customer_items($conn1, $user_id);
    
    $return['waiting'] = $waiting;
    $return['cartItems'] = $cart_items;
    $return['myCustomers'] = $my_customers;
    $return['print_list'] = $print_items;

    return $return;


}


function confirm_cart_items_quantity($conn1, $user_id) {

    $sql1 = "SELECT a.item_id, a.location_id, SUM(b.unit_quantity*a.unit_qty) AS total
            FROM
            (SELECT item_id, location_id, unit_id, unit_quantity AS unit_qty
            FROM temp_transactions
            WHERE sold_by='{$user_id}' AND customer_id=0) AS a
            LEFT JOIN
            (SELECT * FROM stock_units) AS b
            ON b.id=a.unit_id
            
            GROUP BY a.item_id, a.location_id";
    $result1 = run_query($conn1, $sql1);

    while($row1=mysqli_fetch_assoc($result1)) {

        $item_id = $row1['item_id'];
        $location_id = $row1['location_id'];
        $total_sold_pieces = $row1['total'];

        if(check_item_lock_status_selling($conn1, $item_id, $location_id, $user_id)) {

            $location_old_qty = get_item_location_quantity($conn1, $item_id, $location_id)['location_quantity'];

            if($total_sold_pieces > $location_old_qty) {

                unlock_all_user_locked_items($conn1, $user_id);
                unlock_table($conn1, 'sold_stock', $user_id);

                $item = get_item_details($conn1, $item_id)['name'];

                $data_insert=array(
                    "status" => "error",
                    "message" => "The item ".$item." has less quantity available!"
                );
                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        } else {

            unlock_all_user_locked_items($conn1, $user_id);
            unlock_table($conn1, 'sold_stock', $user_id);

            $item = get_item_details($conn1, $item_id)['name'];

            $data_insert=array(
                "status" => "error",
                "message" => "The item ".$item ." is locked by another user!"
            );
            echo json_encode($data_insert);
            mysqli_close($conn1);

            exit(); 
        }

    }

    return true;

}


function confirm_cart_items_quantity_waiting($conn1, $user_id, $customer_name) {

    $sql1 = "SELECT a.item_id, a.location_id, SUM(b.unit_quantity*a.unit_qty) AS total
            FROM
            (SELECT item_id, location_id, unit_id, unit_quantity AS unit_qty
            FROM temp_waiting_customers
            WHERE sold_by='{$user_id}' AND customer_name='{$customer_name}') AS a
            LEFT JOIN
            (SELECT * FROM stock_units) AS b
            ON b.id=a.unit_id
            
            GROUP BY a.item_id, a.location_id";
    $result1 = run_query($conn1, $sql1);

    while($row1=mysqli_fetch_assoc($result1)) {

        $item_id = $row1['item_id'];
        $location_id = $row1['location_id'];
        $total_sold_pieces = $row1['total'];

        if(check_item_lock_status_selling($conn1, $item_id, $location_id, $user_id)) {

            $location_old_qty = get_item_location_quantity($conn1, $item_id, $location_id)['location_quantity'];

            if($total_sold_pieces > $location_old_qty) {

                unlock_all_user_locked_items($conn1, $user_id);
                unlock_table($conn1, 'sold_stock', $user_id);

                $item = get_item_details($conn1, $item_id)['name'];

                $data_insert=array(
                    "status" => "error",
                    "message" => "The item ".$item." has less quantity available!"
                );
                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        } else {

            unlock_all_user_locked_items($conn1, $user_id);
            unlock_table($conn1, 'sold_stock', $user_id);

            $item = get_item_details($conn1, $item_id)['name'];

            $data_insert=array(
                "status" => "error",
                "message" => "The item ".$item ." is locked by another user!"
            );
            echo json_encode($data_insert);
            mysqli_close($conn1);

            exit(); 
        }

    }

    return true;

}


function confirm_cart_items_quantity_debtor($conn1, $user_id, $customer_id) {

    $sql1 = "SELECT a.item_id, a.location_id, SUM(b.unit_quantity*a.unit_qty) AS total
            FROM
            (SELECT item_id, location_id, unit_id, unit_quantity AS unit_qty
            FROM temp_transactions
            WHERE sold_by='{$user_id}' AND customer_id='{$customer_id}') AS a
            LEFT JOIN
            (SELECT * FROM stock_units) AS b
            ON b.id=a.unit_id
            
            GROUP BY a.item_id, a.location_id";
    $result1 = run_query($conn1, $sql1);

    while($row1=mysqli_fetch_assoc($result1)) {

        $item_id = $row1['item_id'];
        $location_id = $row1['location_id'];
        $total_sold_pieces = $row1['total'];

        if(check_item_lock_status_selling($conn1, $item_id, $location_id, $user_id)) {

            $location_old_qty = get_item_location_quantity($conn1, $item_id, $location_id)['location_quantity'];

            if($total_sold_pieces > $location_old_qty) {

                unlock_all_user_locked_items($conn1, $user_id);
                unlock_table($conn1, 'sold_stock', $user_id);

                $item = get_item_details($conn1, $item_id)['name'];

                $data_insert=array(
                    "status" => "error",
                    "message" => "The item ".$item." has less quantity available!"
                );
                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        } else {

            unlock_all_user_locked_items($conn1, $user_id);
            unlock_table($conn1, 'sold_stock', $user_id);

            $item = get_item_details($conn1, $item_id)['name'];

            $data_insert=array(
                "status" => "error",
                "message" => "The item ".$item ." is locked by another user!"
            );
            echo json_encode($data_insert);
            mysqli_close($conn1);

            exit(); 
        }

    }

    return true;

}


function confirm_cart_items_quantity_interlocation($conn1, $user_id) {

    $sql1 = "SELECT a.item_id, a.location_1_id AS location_id, SUM(b.unit_quantity*a.unit_qty) AS total
            FROM
            (SELECT item_id, location_1_id, unit_id, unit_quantity AS unit_qty
            FROM temp_inter_location
            WHERE transfered_by='{$user_id}') AS a
            LEFT JOIN
            (SELECT * FROM stock_units) AS b
            ON b.id=a.unit_id
            
            GROUP BY a.item_id, a.location_1_id";
    $result1 = run_query($conn1, $sql1);

    while($row1=mysqli_fetch_assoc($result1)) {

        $item_id = $row1['item_id'];
        $location_id = $row1['location_id'];
        $total_sold_pieces = $row1['total'];

        if(check_item_lock_status_selling($conn1, $item_id, $location_id, $user_id)) {

            $location_old_qty = get_item_location_quantity($conn1, $item_id, $location_id)['location_quantity'];

            if($total_sold_pieces > $location_old_qty) {

                unlock_all_user_locked_items($conn1, $user_id);
                unlock_table($conn1, 'sold_stock', $user_id);

                $item = get_item_details($conn1, $item_id)['name'];

                $data_insert=array(
                    "status" => "error",
                    "message" => "The item ".$item." has less quantity available!"
                );
                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();
            }

        } else {

            unlock_all_user_locked_items($conn1, $user_id);
            unlock_table($conn1, 'sold_stock', $user_id);

            $item = get_item_details($conn1, $item_id)['name'];

            $data_insert=array(
                "status" => "error",
                "message" => "The item ".$item ." is locked by another user!"
            );
            echo json_encode($data_insert);
            mysqli_close($conn1);

            exit(); 
        }

    }

    return true;

}



function get_stock_and_cart_items_new($conn1, $user_id) {

    // get the items
    $sql="SELECT 
            stock.id AS item_id,
            stock.name AS item_name,
            stock.buying_price AS buying_price,
            IFNULL(su.unit_quantity, 0) AS min_unit_ok,
            GROUP_CONCAT(sl.location_id ORDER BY sl.location_id SEPARATOR ',') AS location_ids
        FROM 
            stock
        JOIN 
            stock_locations sl ON stock.id = sl.item_id AND sl.deleted=0 AND stock.deleted=0
        INNER JOIN
            stock_units su ON su.item_id=stock.id AND su.unit_quantity=1 AND su.deleted=0
        GROUP BY 
            stock.id";
    $result = run_query($conn1, $sql);

    $stock = [];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $stock[$cr]['id'] = encrypt_data($row['item_id']);
        $stock[$cr]['name'] = $row['item_name'];
        $stock[$cr]['buying_price'] = $row['buying_price'];
        $stock[$cr]['min_unit_ok'] = $row['min_unit_ok'];
        $stock[$cr]['locations'] = $row['location_ids'];
        $cr++;
    }

    // get cart items
    $sql = "SELECT * FROM temp_transactions WHERE customer_id=0 AND sold_by='{$user_id}' ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $cart_items = [];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $item_id = $row['item_id'];
        $location_id = $row['location_id'];
        $unit_id = $row['unit_id'];

        $item = get_item_details($conn1, $item_id);
        $location = get_shop_location_record($conn1, $location_id);
        $unit = get_unit_record($conn1, $unit_id);

        $unit_qty = $row['unit_quantity'];
        $unit_bp = $row['buying_price'];
        $min_sp = $row['min_selling_price'];
        $unit_sp = $row['selling_price'];

        $cart_items[$cr]['id'] = encrypt_data($row['id']);
        $cart_items[$cr]['item'] = $item;
        $cart_items[$cr]['location'] = $location;
        $cart_items[$cr]['unit'] = $unit;
        $cart_items[$cr]['qty'] = $unit_qty;
        $cart_items[$cr]['sp'] = $unit_sp;

        $cr++;
    }

    // get quotation items
    $sql = "SELECT * FROM print_items WHERE sold_by='{$user_id}' ORDER BY id DESC";
    $result = run_query($conn1, $sql);

    $print_items = [];
    $cr=0;
    while($row=mysqli_fetch_assoc($result)) {
        $item_id = $row['item_id'];
        $location_id = $row['location_id'];
        $unit_id = $row['unit_id'];

        $item = get_item_details($conn1, $item_id);
        $location = get_shop_location_record($conn1, $location_id);
        $unit = get_unit_record($conn1, $unit_id);

        $unit_qty = $row['unit_quantity'];
        $unit_sp = $row['selling_price'];

        $print_items[$cr]['id'] = encrypt_data($row['id']);
        $print_items[$cr]['item'] = $item;
        $print_items[$cr]['location'] = $location;
        $print_items[$cr]['unit'] = $unit;
        $print_items[$cr]['qty'] = $unit_qty;
        $print_items[$cr]['sp'] = $unit_sp;

        $cr++;
    }
    
    $waiting = get_temp_waiting_transactions($conn1, $user_id);
    $my_customers = get_temp_my_customer_items($conn1, $user_id);
    $shop_locations = get_shop_locations($conn1);
    $duplicated_transactions = get_duplicated_transactions($conn1, $user_id);

    $return['stock'] = $stock;
    $return['cart_items'] = $cart_items;
    $return['waiting'] = $waiting;
    $return['duplicated'] = $duplicated_transactions;
    $return['my_customers'] = $my_customers;
    $return['print_items'] = $print_items;
    $return['shop_locations'] = $shop_locations;

    return $return;
}


function get_stock_and_cart_items_inter_location($conn1, $user_id) {
    $sql = "SELECT * FROM stock WHERE deleted=0";
    $result = run_query($conn1, $sql);

    $stock= get_all_stock_new($conn1);


    $return['stock'] = $stock['stock'];
    $return['categories'] = $stock['categories'];
    $return['locations'] = $stock['locations'];
    $return['vat_records'] = $stock['vat_records'];



    $cart_items = get_temp_inter_locations($conn1, $user_id);

    $return['cartItems'] = $cart_items;

    return $return;

}


function get_stock_and_cart_items_inter_location_new($conn1, $user_id) {
    

    // get the items
    $sql="SELECT 
            stock.id AS item_id,
            stock.name AS item_name,
            stock.buying_price AS buying_price,
            IFNULL(su.unit_quantity, 0) AS min_unit_ok,
            GROUP_CONCAT(sl.location_id ORDER BY sl.location_id SEPARATOR ',') AS location_ids
        FROM 
            stock
        JOIN 
            stock_locations sl ON stock.id = sl.item_id AND sl.deleted=0 AND stock.deleted=0
        INNER JOIN
            stock_units su ON su.item_id=stock.id AND su.unit_quantity=1
        GROUP BY 
            stock.id";
    $result = run_query($conn1, $sql);

    $stock = [];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $stock[$cr]['id'] = encrypt_data($row['item_id']);
        $stock[$cr]['name'] = $row['item_name'];
        $stock[$cr]['buying_price'] = $row['buying_price'];
        $stock[$cr]['min_unit_ok'] = $row['min_unit_ok'];
        $stock[$cr]['locations'] = $row['location_ids'];
        $cr++;
    }



    

    // $return['stock'] = $stock['stock'];
    // $return['categories'] = $stock['categories'];
    // $return['locations'] = $stock['locations'];
    // $return['vat_records'] = $stock['vat_records'];



    $cart_items = get_temp_inter_locations($conn1, $user_id);

    $return['cartItems'] = $cart_items;



    $shop_locations = get_shop_locations($conn1);

    $return['stock'] = $stock;
    $return['cartItems'] = $cart_items;
    $return['locations'] = $shop_locations;


    return $return;

}


function get_shop_details($conn1, $user_id) {
    $sql="SELECT * FROM shop";
    $result = run_query($conn1, $sql);

    $shop=[];
    $shop_contacts=[];

    while($row=mysqli_fetch_assoc($result)) {
        $shop['name'] = encrypt_data($row['name']);
        $shop['description'] = encrypt_data($row['description']);
        $shop['location'] = encrypt_data($row['location']);
    }

    $sql2="SELECT * FROM shop_contacts";
    $result2 = run_query($conn1, $sql2);

    $cr=0;

    while($row2=mysqli_fetch_assoc($result2)) {
        $shop_contacts[$cr] = encrypt_data($row2['contact']);
        $cr++;
    }


    $shop_user_name = encrypt_data(get_user_record($conn1, $user_id)['name']);

    $receipt_options = get_shop_receipt_options($conn1);

    $shop['imageUrl'] = $receipt_options['image_url'];
    $shop['contacts'] = $shop_contacts;
    $shop['receipt_options'] = $receipt_options;
    $shop['username'] = $shop_user_name;

    return $shop;

    
}

function get_stock_value($conn1) {

    $locations = get_shop_locations($conn1);

    $total_stock_value = 0;

    foreach ($locations as &$loc) {
        # code...
        // get the total amount
        $id = decrypt_data($loc['id']);

        $sql = "SELECT SUM(s.buying_price * l.quantity) AS total FROM
                (SELECT id, buying_price FROM stock WHERE deleted=0) AS s
                INNER JOIN
                (SELECT item_id, SUM(location_quantity) AS quantity FROM stock_locations WHERE location_id='{$id}'
                GROUP BY item_id) AS l
                ON l.item_id=s.id
                INNER JOIN
                (SELECT item_id FROM stock_units WHERE unit_quantity=1 AND deleted=0) AS st
                ON st.item_id=s.id
                ";

        $result = run_query($conn1, $sql);

        $record = mysqli_fetch_assoc($result);

        $loc['total_amount'] = $record['total'];

        $total_stock_value += floatval($loc['total_amount']);

    }
    unset($loc);






    $return['total'] = $total_stock_value;
    $return['locationsValue'] = $locations;

    return $return;

}

function get_categories_stock_value($conn1) {

    $locations = get_all_categories($conn1);

    $total_stock_value = 0;

    foreach ($locations as &$loc) {
        # code...
        // get the total amount
        $id = decrypt_data($loc['id']);

        $sql = "SELECT SUM(s.buying_price * l.quantity) AS total FROM
                (SELECT id, buying_price FROM stock WHERE category_id='{$id}' AND deleted=0) AS s
                INNER JOIN
                (SELECT item_id, SUM(location_quantity) AS quantity FROM stock_locations
                GROUP BY item_id) AS l
                ON l.item_id=s.id
                INNER JOIN
                (SELECT item_id FROM stock_units WHERE unit_quantity=1 AND deleted=0) AS st
                ON st.item_id=s.id
                ";

        $result = run_query($conn1, $sql);

        $record = mysqli_fetch_assoc($result);

        $loc['total_amount'] = $record['total'];

        $total_stock_value += floatval($loc['total_amount']);

    }
    unset($loc);






    // $return['total'] = $total_stock_value;
    $return['categoriesValue'] = $locations;

    return $return;

}


function get_all_stock($conn1) {

    $sql = "SELECT * FROM stock WHERE deleted=0";
    $result = run_query($conn1, $sql);

    $stock=[];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {
        $stock[$cr]['id'] = encrypt_data($row['id']);
        $stock[$cr]['name'] = utf8_encode($row['name']);
        $stock[$cr]['category'] = get_category($conn1, $row['category_id']);
        $stock[$cr]['buying_price'] = $row['buying_price'];
        $stock[$cr]['vat'] = get_item_vat_record($conn1, $row['vat_id']);
        $stock[$cr]['image'] = $row['image'];
        $stock[$cr]['last_edited'] = $row['last_edited'];
        $stock[$cr]['edited_by'] = get_user_record($conn1, $row['edited_by'])['name'];

        $loc_quantity = get_item_all_locations_quantity($conn1, $row['id']);

        $stock[$cr]['locations_qty'] = $loc_quantity['locations_quantity'];
        $stock[$cr]['locations'] = $loc_quantity['locations'];
        $stock[$cr]['total_qty'] = $loc_quantity['total_quantity'];
        $stock[$cr]['selling_units']  = get_item_selling_units($conn1, $row['id']);

        $cr++;
    }

    $categories = get_all_categories($conn1);


    $return['stock'] = $stock;
    $return['categories'] = $categories;


    return $return;

}

function get_all_stock_new($conn1) {

    $sql = "SELECT st.id, st.name, IFNULL(sc.category_name, '-') AS category_name, IFNULL(st.category_id, 0) AS category_id, st.vat_id,
                st.buying_price AS buying_price, units_max_qty.unit_qty AS max_unit_qty,
                vat.vat_percent, IFNULL(sl.total, 0) AS locations, sus.units AS units,
                IFNULL(units_min_qty.total, 0) AS min_qty_ok,
                IFNULL(units_prices.item_id, 0) AS prices_ok,
                users.username AS edited_by, st.last_edited, st.image
                
                FROM      

            (SELECT * FROM `stock` WHERE deleted=0) AS st
            LEFT JOIN
            (SELECT * FROM stock_categories) AS sc
            ON sc.id=st.category_id

            LEFT JOIN
            (SELECT * FROM stock_units WHERE deleted=0) AS su
            ON su.item_id=st.id

            LEFT JOIN
            (SELECT item_id, MAX(unit_quantity) AS unit_qty FROM stock_units WHERE deleted=0 GROUP BY item_id) AS units_max_qty
            ON units_max_qty.item_id=st.id

            LEFT JOIN
            (SELECT * FROM vat_records) AS vat
            ON vat.id=st.vat_id

            LEFT JOIN
            (SELECT item_id, COUNT(*) AS total FROM stock_locations WHERE deleted=0 GROUP BY item_id) AS sl
            ON sl.item_id=st.id

            LEFT JOIN
            (SELECT item_id, COUNT(*) AS units FROM stock_units WHERE deleted=0 GROUP BY item_id) AS sus
            ON sus.item_id=st.id

            LEFT JOIN
            (SELECT item_id, COUNT(*) AS total FROM stock_units WHERE deleted=0 AND unit_quantity=1 GROUP BY item_id) AS units_min_qty
            ON units_min_qty.item_id=st.id

            LEFT JOIN
            (SELECT item_id, unit_min_selling_price, unit_quantity, unit_recom_selling_price FROM stock_units WHERE deleted = 0) AS units_prices
            ON (units_prices.item_id = st.id) AND
            ((units_prices.unit_min_selling_price < (st.buying_price * units_prices.unit_quantity)) OR 
            units_prices.unit_recom_selling_price < (st.buying_price * units_prices.unit_quantity))

            LEFT JOIN
            (SELECT * FROM users) AS users
            ON users.id=st.edited_by

            GROUP BY st.id";

    $result = run_query($conn1, $sql);

    $stock=[];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {
        $stock[$cr]['id'] = encrypt_data($row['id']);
        $stock[$cr]['name'] = utf8_encode($row['name']);
        $stock[$cr]['category_id'] = encrypt_data($row['category_id']);
        $stock[$cr]['category'] = $row['category_name'];
        $stock[$cr]['buying_price'] = $row['buying_price'];
        $stock[$cr]['max_unit_qty'] = $row['max_unit_qty'];
        $stock[$cr]['vat_id'] = encrypt_data($row['vat_id']);
        $stock[$cr]['vat'] = $row['vat_percent'];
        $stock[$cr]['image'] = $row['image'];
        $stock[$cr]['last_edited'] = $row['last_edited'];
        $stock[$cr]['edited_by'] = $row['edited_by'];;

        $stock[$cr]['locations_count'] = $row['locations'];
        $stock[$cr]['units'] = $row['units'];
        $stock[$cr]['min_qty_ok'] = $row['min_qty_ok'];
        $stock[$cr]['prices_ok'] = $row['prices_ok'];

        $cr++;
    }


    $min_items =  get_total_min_stock_items($conn1);

    $categories = get_all_categories($conn1);
    $shop_locations = get_shop_locations($conn1);
    $vat_records = get_vat_records($conn1);


    $return['stock'] = $stock;
    $return['categories'] = $categories;
    $return['locations'] = $shop_locations;
    $return['vat_records'] = $vat_records;
    $return['min_items'] = $min_items;



    return $return;

}

function get_stock_taking_items($conn1, $date_id) {
    $sql = "SELECT * FROM stock_taking_items WHERE stock_taking_date_id='{$date_id}'";
    $result = run_query($conn1, $sql);

    $items=[];
    $cr=0;

    $missing = 0;
    $excess = 0;
    $stable = 0;

    while($row=mysqli_fetch_assoc($result)) {

        $items[$cr]['id'] = encrypt_data($row['id']);
        $item = get_item_details($conn1, $row['item_id']);
        $items[$cr]['item'] = $item;
        $items[$cr]['current_qty'] = $row['current_quantity'];
        $items[$cr]['confirmed_qty'] = $row['quantity_confirmed'];
        $items[$cr]['buying_price'] = $row['buying_price'];
        $items[$cr]['selling_units'] = get_item_selling_units($conn1, $row['item_id']);

        $diff = $row['quantity_confirmed'] - $row['current_quantity'];

        if($diff > 0) {
            $excess += $diff * $row['buying_price'];
        }

        if($diff < 0) {
            $missing += abs($diff) * $row['buying_price'];
        }

        if($diff == 0) {
            $stable += $row['buying_price'] * $row['quantity_confirmed'];
        }

        $cr++;

    }

    $return['items'] = $items;
    $return['missing'] = $missing;
    $return['excess'] = $excess;
    $return['stable'] = $stable;

    return $return;
}



function check_today_stock_taking($conn1) {
    $today = get_today();

    $sql = "SELECT * FROM stock_taking_dates WHERE DATE_FORMAT(date_started, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')
            AND completed=0";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {
        return true;
    } else {
        return false;
    }
}

function get_location_items($conn1, $location_id, $date_id) {
    $sql = "SELECT * FROM stock WHERE deleted=0 AND id IN
            (SELECT item_id FROM stock_locations WHERE location_id='{$location_id}' AND deleted=0)
            AND id NOT IN
            (SELECT item_id FROM stock_taking_items WHERE stock_taking_date_id='{$date_id}')";
    $result = run_query($conn1, $sql);

    $stock=[];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {
        $stock[$cr]['id'] = encrypt_data($row['id']);
        $stock[$cr]['name'] = $row['name'];
        $stock[$cr]['category'] = get_category($conn1, $row['category_id']);
        $stock[$cr]['buying_price'] = $row['buying_price'];
        $stock[$cr]['vat'] = get_item_vat_record($conn1, $row['vat_id']);
        $stock[$cr]['image'] = $row['image'];
        $stock[$cr]['last_edited'] = $row['last_edited'];
        $stock[$cr]['edited_by'] = get_user_record($conn1, $row['edited_by'])['name'];

        $loc_quantity = get_item_location_quantity($conn1, $row['id'], $location_id);

        $stock[$cr]['location_qty'] = $loc_quantity['location_quantity'];
        $stock[$cr]['selling_units']  = get_item_selling_units($conn1, $row['id']);

        $cr++;
    }

    $categories = get_all_categories($conn1);

    $return['stock'] = $stock;
    $return['categories'] = $categories;

    return $return;


}

function get_user_roles($conn1, $user_id) {
    $sql = "SELECT * FROM ";
}

function get_shop_profile_details($conn1) {
    $sql="SELECT * FROM shop";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $shop=[];
        $shop_contacts=[];

        while($row=mysqli_fetch_assoc($result)) {
            $shop['name'] = $row['name'];
            $shop['description'] = $row['description'];
            $shop['location'] = $row['location'];
        }

        $sql2="SELECT * FROM shop_contacts";
        $result2 = mysqli_query($conn1, $sql2);

        if($result) {

            $cr=0;

            while($row2=mysqli_fetch_assoc($result2)) {
                $shop_contacts[$cr] = $row2['contact'];
                $cr++;
            }

            $receipt_options = get_shop_receipt_options($conn1);

            $shop['contacts'] = $shop_contacts;
            $shop['receipt_options'] = $receipt_options;

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

function get_debtor_payments($conn1, $debtor_id) {
    $payments=[];
    $cr=0;

    $sql = "SELECT * FROM debtors_payments WHERE customer_id='{$debtor_id}' AND (cash+mpesa+bank+amount_credited)>0 ORDER BY id DESC";

    $result = run_query($conn1, $sql);

    while($row=mysqli_fetch_assoc($result)) {
        $payments[$cr]['id'] = encrypt_data($row['id']);
        $payments[$cr]['balance_before'] = $row['balance_before'];
        $payments[$cr]['cash'] = $row['cash'];
        $payments[$cr]['bank'] = $row['bank'];
        $payments[$cr]['mpesa'] = $row['mpesa'];
        $payments[$cr]['amount_credited'] = $row['amount_credited'];
        $payments[$cr]['date_paid'] = $row['date_of_payment'];
        $payments[$cr]['added_by'] = get_user_record($conn1, $row['added_by'])['name'];
        
        $cr++;
    }

    return $payments;
}

function get_debtor_summary($conn1, $debtor_id) {
    
    $sql = "SELECT customers.id, customers.name,customers.sales_limit,customers.mobile_number, IFNULL(sales.sales, 0) AS sales, IFNULL(payments.payments, 0) AS payments, IFNULL(payments.credit_note, 0) AS credit_note
            FROM
            (SELECT id,name,sales_limit,mobile_number FROM customers WHERE id='{$debtor_id}' AND customer_type='debtor' AND deleted=0) AS customers
            LEFT JOIN
            (SELECT customer_id, SUM(unit_quantity*selling_price) AS sales FROM sold_stock_debtors GROUP BY customer_id) AS sales
            ON sales.customer_id=customers.id
            LEFT JOIN
            (SELECT customer_id, SUM(cash+mpesa+bank) AS payments, SUM(amount_credited) AS credit_note FROM debtors_payments GROUP BY customer_id) AS payments
            ON payments.customer_id=customers.id";
    $result = run_query($conn1, $sql);

    $debtor=null;

    while($row=mysqli_fetch_assoc($result)) {
        $debtor['id'] = encrypt_data($row['id']);
        $debtor['name'] = $row['name'];
        $debtor['mobile_number'] = $row['mobile_number'];
        $debtor['sales_limit'] = $row['sales_limit'];
        $debtor['sales'] = $row['sales'];
        $debtor['payments'] = $row['payments'];
        $debtor['credit_note'] = $row['credit_note'];
    }

    return $debtor;

}

function get_week_sales_old($conn1) {

    $from = date ("Y-m", strtotime("-6 days"));
    $to = date ("Y-m-d", time());

    $sql = "SELECT dates.date_sold,
                IFNULL(cash_sales.gross_sales, 0) AS cash_sales,
                IFNULL(cash_sales.gross_profit, 0) AS cash_profit,
                IFNULL(reversed_cash.sales_reversed, 0) AS cash_reversed,
                IFNULL(reversed_cash.reversed_profit, 0) AS reversed_cash_profit,
                
                IFNULL(debtor_sales.gross_sales, 0) AS debtor_sales,
                IFNULL(debtor_sales.gross_profit, 0) AS debtor_profit,
                IFNULL(reversed_debtor.sales_reversed, 0) AS debtor_reversed,
                IFNULL(reversed_debtor.reversed_profit, 0) AS reversed_debtor_profit,
                
                IFNULL(transfer_sales.gross_sales, 0) AS transfer_sales,
                IFNULL(reversed_transfer.sales_reversed, 0) AS transfer_reversed,
                
                IFNULL(expenses.expense, 0) AS expenses,
                IFNULL(float_amount.cash_float, 0) AS cash_float,
                
                IFNULL(banked_amount.cash, 0) AS cash_banked,
                IFNULL(banked_amount.mpesa, 0) AS mpesa_banked,
                IFNULL(banked_amount.bank, 0) AS bank_banked,
                
                IFNULL(banked_reversed_cash_only.reversed_cash_amount, 0) AS reversed_cash_only,
                IFNULL(banked_reversed_mpesa_only.reversed_mpesa_amount, 0) AS reversed_mpesa_only,
                IFNULL(banked_reversed_bank_only.reversed_bank_amount, 0) AS reversed_bank_only,
                IFNULL(banked_reversed_multiple.reversed_multiple, 0) AS reversed_multiple,

                IFNULL(cash_discounts.discounts, 0) AS cash_discounts,
                IFNULL(debtor_discounts.discounts, 0) AS debtor_discounts,

                IFNULL(cash_overcharges.overcharges, 0) AS cash_overcharges,
                IFNULL(debtor_overcharges.overcharges, 0) AS debtor_overcharges
                
                FROM

                (SELECT date_sold FROM
                    (SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock
                        WHERE DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY), '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')
                    UNION
                    SELECT DATE_FORMAT(date_returned, '%Y-%m-%d') AS date_sold FROM sold_stock_reversed
                        WHERE DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY), '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(date_returned, '%Y-%m-%d')
                    UNION
                    SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock_debtors
                        WHERE DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY), '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')
                    UNION
                    SELECT DATE_FORMAT(date_transfered, '%Y-%m-%d') AS date_sold FROM sold_stock_transfers
                        WHERE DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY), '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(date_transfered, '%Y-%m-%d')
                    UNION
                    SELECT DATE_FORMAT(date_incurred, '%Y-%m-%d') AS date_sold FROM expenses
                        WHERE DATE_FORMAT(date_incurred, '%Y-%m-%d') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY), '%Y-%m-%d') AND DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(date_incurred, '%Y-%m-%d')
                    ) AS dates ORDER BY DATE_FORMAT(date_sold, '%Y-%m-%d') DESC
                ) AS dates
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit
                    FROM sold_stock
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')) AS cash_sales
                ON cash_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m-%d') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit
                    FROM sold_stock_reversed WHERE sale_type='cash'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m-%d')) AS reversed_cash
                ON reversed_cash.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit
                    FROM sold_stock_debtors
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')) AS debtor_sales
                ON debtor_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m-%d') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit
                    FROM sold_stock_reversed WHERE sale_type='debtor'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m-%d')) AS reversed_debtor
                ON reversed_debtor.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_transfered, '%Y-%m-%d') AS date_sold, SUM(unit_quantity * buying_price) AS gross_sales
                    FROM sold_stock_transfers
                    GROUP BY DATE_FORMAT(date_transfered, '%Y-%m-%d')) AS transfer_sales
                ON transfer_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m-%d') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed
                    FROM sold_stock_reversed WHERE sale_type='transfer'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m-%d')) AS reversed_transfer
                ON reversed_transfer.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_incurred, '%Y-%m-%d') AS date_sold, SUM(amount) AS expense
                    FROM expenses
                    GROUP BY DATE_FORMAT(date_incurred, '%Y-%m-%d')) AS expenses
                ON expenses.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_created, '%Y-%m-%d') AS date_sold, SUM(float_amount) AS cash_float
                    FROM opening_float
                    GROUP BY DATE_FORMAT(date_created, '%Y-%m-%d')) AS float_amount
                ON float_amount.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold, SUM(cash) AS cash, SUM(mpesa) AS mpesa, SUM(bank) AS bank
                    FROM sales_payments
                    GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d')) AS banked_amount
                ON banked_amount.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold, SUM(amount_reversed) AS reversed_cash_amount
                    FROM sales_payments
                    GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d')) AS banked_reversed_cash_only
                ON banked_reversed_cash_only.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold, SUM(amount_reversed) AS reversed_mpesa_amount FROM sales_payments
                    WHERE cash < 1 AND mpesa > 0 AND bank < 1
                    GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d')) AS banked_reversed_mpesa_only
                ON banked_reversed_mpesa_only.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold, SUM(amount_reversed) AS reversed_bank_amount FROM sales_payments
                    WHERE cash < 1 AND mpesa < 1 AND bank > 0
                    GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d')) AS banked_reversed_bank_only
                ON banked_reversed_bank_only.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold, SUM(amount_reversed) AS reversed_multiple FROM sales_payments
                    WHERE (cash > 0 AND mpesa > 0) OR (cash>0 AND bank>0) OR (mpesa>0 AND bank > 0)
                    GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d')) AS banked_reversed_multiple
                ON banked_reversed_multiple.date_sold=dates.date_sold

                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock
                    WHERE selling_price<recom_selling_price
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')) AS cash_discounts
                ON cash_discounts.date_sold=dates.date_sold

                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock_debtors
                    WHERE selling_price<recom_selling_price
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')) AS debtor_discounts
                ON debtor_discounts.date_sold=dates.date_sold

                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock
                    WHERE selling_price>recom_selling_price
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')) AS cash_overcharges
                ON cash_overcharges.date_sold=dates.date_sold

                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock_debtors
                    WHERE selling_price>recom_selling_price
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')) AS debtor_overcharges
                ON debtor_overcharges.date_sold=dates.date_sold
                
                
                ORDER BY dates.date_sold";

    $result = run_query($conn1, $sql);

    $sales=[];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {
        
        // $user = get_user_record($row['sold_by']);

        $sales[$cr]['date_sold'] = $row['date_sold'];


        $cash_sales = $row['cash_sales'];
        $debtor_sales = $row['debtor_sales'];
        $transfer_sales = $row['transfer_sales'];

        $cash_sales_reversed = $row['cash_reversed'];
        $debtor_sales_reversed = $row['debtor_reversed'];
        $transfer_sales_reversed = $row['transfer_reversed'];

        $gross_sales = $cash_sales + $debtor_sales - $cash_sales_reversed - $debtor_sales_reversed;
        $gross_transfer_sales = $transfer_sales - $transfer_sales_reversed;

        $sales[$cr]['gross_sales'] = $gross_sales;
        $sales[$cr]['transfer_sales'] = $gross_transfer_sales;
        $sales[$cr]['debtor_sales'] = $debtor_sales;
        $sales[$cr]['expenses'] = $row['expenses'];


        $cash_profit = $row['cash_profit'];
        $reversed_cash_profit = $row['reversed_cash_profit'];
        $debtor_profit = $row['debtor_profit'];
        $reversed_debtor_profit = $row['reversed_debtor_profit'];

        $gross_profit = ($cash_profit - $reversed_cash_profit) + ($debtor_profit - $reversed_debtor_profit);
        $sales[$cr]['gross_profit'] = $gross_profit;


        $cr++;

    }

    return $sales;
}

function get_week_sales($conn1) {

    $sql = "SELECT dates.date_sold,
                IFNULL(cash_sales.gross_sales, 0) AS cash_sales,
                IFNULL(cash_sales.gross_profit, 0) AS cash_profit,
                IFNULL(reversed_cash.sales_reversed, 0) AS cash_reversed,
                IFNULL(reversed_cash.reversed_profit, 0) AS reversed_cash_profit,
                
                IFNULL(debtor_sales.gross_sales, 0) AS debtor_sales,
                IFNULL(debtor_sales.gross_profit, 0) AS debtor_profit,
                IFNULL(reversed_debtor.sales_reversed, 0) AS debtor_reversed,
                IFNULL(reversed_debtor.reversed_profit, 0) AS reversed_debtor_profit,
                
                IFNULL(transfer_sales.gross_sales, 0) AS transfer_sales,
                IFNULL(reversed_transfer.sales_reversed, 0) AS transfer_reversed,
                
                IFNULL(expenses.expense, 0) AS expenses,
                
                (IFNULL(cash_sales.gross_sales, 0) +
                 IFNULL(debtor_sales.gross_sales, 0) -
                 IFNULL(reversed_cash.sales_reversed, 0) - 
                 IFNULL(reversed_debtor.sales_reversed, 0)
                ) AS gross_sales,

                (IFNULL(transfer_sales.gross_sales, 0) - 
                 IFNULL(reversed_transfer.sales_reversed, 0)
                ) AS gross_transfer_sales,

                (IFNULL(cash_sales.gross_profit, 0)-
                 IFNULL(reversed_cash.reversed_profit, 0) +
                 IFNULL(debtor_sales.gross_profit, 0) -
                 IFNULL(reversed_debtor.reversed_profit, 0)
                ) AS gross_profit

                FROM

                (SELECT date_sold FROM
                    (SELECT DATE(date_sold) AS date_sold FROM sold_stock
                        WHERE date_sold BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) AND CURRENT_DATE()
                        GROUP BY DATE(date_sold)
                    UNION
                    SELECT DATE(date_returned) AS date_sold FROM sold_stock_reversed
                        WHERE date_returned BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) AND CURRENT_DATE()
                        GROUP BY DATE(date_returned)
                    UNION
                    SELECT DATE(date_sold) AS date_sold FROM sold_stock_debtors
                        WHERE date_sold BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) AND CURRENT_DATE()
                        GROUP BY DATE(date_sold)
                    UNION
                    SELECT DATE(date_transfered) AS date_sold FROM sold_stock_transfers
                        WHERE date_transfered BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) AND CURRENT_DATE()
                        GROUP BY DATE(date_transfered)
                    UNION
                    SELECT DATE(date_incurred) AS date_sold FROM expenses
                        WHERE date_incurred BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) AND CURRENT_DATE()
                        GROUP BY DATE(date_incurred)
                    ) AS dates ORDER BY DATE(date_sold) DESC
                ) AS dates
                
                LEFT JOIN
                (SELECT DATE(date_sold) AS date_sold, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit
                    FROM sold_stock
                    GROUP BY DATE(date_sold)) AS cash_sales
                ON cash_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE(date_returned) AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit
                    FROM sold_stock_reversed WHERE sale_type='cash'
                    GROUP BY DATE(date_returned)) AS reversed_cash
                ON reversed_cash.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE(date_sold) AS date_sold, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit
                    FROM sold_stock_debtors
                    GROUP BY DATE(date_sold)) AS debtor_sales
                ON debtor_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE(date_returned) AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit
                    FROM sold_stock_reversed WHERE sale_type='debtor'
                    GROUP BY DATE(date_returned)) AS reversed_debtor
                ON reversed_debtor.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE(date_transfered) AS date_sold, SUM(unit_quantity * buying_price) AS gross_sales
                    FROM sold_stock_transfers
                    GROUP BY DATE(date_transfered)) AS transfer_sales
                ON transfer_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE(date_returned) AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed
                    FROM sold_stock_reversed WHERE sale_type='transfer'
                    GROUP BY DATE(date_returned)) AS reversed_transfer
                ON reversed_transfer.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE(date_incurred) AS date_sold, SUM(amount) AS expense
                    FROM expenses
                    GROUP BY DATE(date_incurred)) AS expenses
                ON expenses.date_sold=dates.date_sold
                
            
                ORDER BY dates.date_sold";

    $result = run_query($conn1, $sql);

    $sales=[];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {
        
        $sales[$cr]['date_sold'] = $row['date_sold'];

        $cash_sales = $row['cash_sales'];
        $debtor_sales = $row['debtor_sales'];
        $transfer_sales = $row['transfer_sales'];

        $cash_sales_reversed = $row['cash_reversed'];
        $debtor_sales_reversed = $row['debtor_reversed'];
        $transfer_sales_reversed = $row['transfer_reversed'];

        // $gross_sales = $cash_sales + $debtor_sales - $cash_sales_reversed - $debtor_sales_reversed;
        $gross_sales = $row['gross_sales'];
        
        // $gross_transfer_sales = $transfer_sales - $transfer_sales_reversed;
        $gross_transfer_sales = $row['gross_transfer_sales'];

        $sales[$cr]['gross_sales'] = $gross_sales;
        $sales[$cr]['transfer_sales'] = $gross_transfer_sales;
        $sales[$cr]['debtor_sales'] = $debtor_sales;
        $sales[$cr]['expenses'] = $row['expenses'];


        $cash_profit = $row['cash_profit'];
        $reversed_cash_profit = $row['reversed_cash_profit'];
        $debtor_profit = $row['debtor_profit'];
        $reversed_debtor_profit = $row['reversed_debtor_profit'];

        // $gross_profit = ($cash_profit - $reversed_cash_profit) + ($debtor_profit - $reversed_debtor_profit);
        $gross_profit = $row['gross_profit'];
        
        $sales[$cr]['gross_profit'] = $gross_profit;


        $cr++;

    }

    return $sales;
}

function get_months_sales_old($conn1) {

    
    $from = date ("Y-m", strtotime("-12 months"));
    $to = date ("Y-m-d", time());

    $sql = "SELECT dates.date_sold,
                IFNULL(cash_sales.gross_sales, 0) AS cash_sales,
                IFNULL(cash_sales.gross_profit, 0) AS cash_profit,
                IFNULL(reversed_cash.sales_reversed, 0) AS cash_reversed,
                IFNULL(reversed_cash.reversed_profit, 0) AS reversed_cash_profit,
                
                IFNULL(debtor_sales.gross_sales, 0) AS debtor_sales,
                IFNULL(debtor_sales.gross_profit, 0) AS debtor_profit,
                IFNULL(reversed_debtor.sales_reversed, 0) AS debtor_reversed,
                IFNULL(reversed_debtor.reversed_profit, 0) AS reversed_debtor_profit,
                
                IFNULL(transfer_sales.gross_sales, 0) AS transfer_sales,
                IFNULL(reversed_transfer.sales_reversed, 0) AS transfer_reversed,
                
                IFNULL(expenses.expense, 0) AS expenses,
                IFNULL(float_amount.cash_float, 0) AS cash_float,
                
                IFNULL(banked_amount.cash, 0) AS cash_banked,
                IFNULL(banked_amount.mpesa, 0) AS mpesa_banked,
                IFNULL(banked_amount.bank, 0) AS bank_banked,
                
                IFNULL(banked_reversed_cash_only.reversed_cash_amount, 0) AS reversed_cash_only,
                IFNULL(banked_reversed_mpesa_only.reversed_mpesa_amount, 0) AS reversed_mpesa_only,
                IFNULL(banked_reversed_bank_only.reversed_bank_amount, 0) AS reversed_bank_only,
                IFNULL(banked_reversed_multiple.reversed_multiple, 0) AS reversed_multiple,

                IFNULL(cash_discounts.discounts, 0) AS cash_discounts,
                IFNULL(debtor_discounts.discounts, 0) AS debtor_discounts,

                IFNULL(cash_overcharges.overcharges, 0) AS cash_overcharges,
                IFNULL(debtor_overcharges.overcharges, 0) AS debtor_overcharges
                
                FROM

                (SELECT date_sold FROM
                    (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold FROM sold_stock
                        WHERE DATE_FORMAT(date_sold, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m') AND DATE_FORMAT(date_sold, '%Y-%m') <= DATE_FORMAT('{$to}', '%Y-%m')
                        GROUP BY DATE_FORMAT(date_sold, '%Y-%m')
                    UNION
                    SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold FROM sold_stock_reversed
                        WHERE DATE_FORMAT(date_returned, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m') AND DATE_FORMAT(date_returned, '%Y-%m') <= DATE_FORMAT('{$to}', '%Y-%m')
                        GROUP BY DATE_FORMAT(date_returned, '%Y-%m')
                    UNION
                    SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold FROM sold_stock_debtors
                        WHERE DATE_FORMAT(date_sold, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m') AND DATE_FORMAT(date_sold, '%Y-%m') <= DATE_FORMAT('{$to}', '%Y-%m')
                        GROUP BY DATE_FORMAT(date_sold, '%Y-%m')
                    UNION
                    SELECT DATE_FORMAT(date_transfered, '%Y-%m') AS date_sold FROM sold_stock_transfers
                        WHERE DATE_FORMAT(date_transfered, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m') AND DATE_FORMAT(date_transfered, '%Y-%m') <= DATE_FORMAT('{$to}', '%Y-%m')
                        GROUP BY DATE_FORMAT(date_transfered, '%Y-%m')
                    UNION
                    SELECT DATE_FORMAT(date_incurred, '%Y-%m') AS date_sold FROM expenses
                        WHERE DATE_FORMAT(date_incurred, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m') AND DATE_FORMAT(date_incurred, '%Y-%m') <= DATE_FORMAT('{$to}', '%Y-%m')
                        GROUP BY DATE_FORMAT(date_incurred, '%Y-%m')
                    ) AS dates ORDER BY DATE_FORMAT(date_sold, '%Y-%m') DESC
                ) AS dates
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit
                    FROM sold_stock
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m')) AS cash_sales
                ON cash_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit
                    FROM sold_stock_reversed WHERE sale_type='cash'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m')) AS reversed_cash
                ON reversed_cash.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit
                    FROM sold_stock_debtors
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m')) AS debtor_sales
                ON debtor_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit
                    FROM sold_stock_reversed WHERE sale_type='debtor'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m')) AS reversed_debtor
                ON reversed_debtor.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_transfered, '%Y-%m') AS date_sold, SUM(unit_quantity * buying_price) AS gross_sales
                    FROM sold_stock_transfers
                    GROUP BY DATE_FORMAT(date_transfered, '%Y-%m')) AS transfer_sales
                ON transfer_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed
                    FROM sold_stock_reversed WHERE sale_type='transfer'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m')) AS reversed_transfer
                ON reversed_transfer.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_incurred, '%Y-%m') AS date_sold, SUM(amount) AS expense
                    FROM expenses
                    GROUP BY DATE_FORMAT(date_incurred, '%Y-%m')) AS expenses
                ON expenses.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_created, '%Y-%m') AS date_sold, SUM(float_amount) AS cash_float
                    FROM opening_float
                    GROUP BY DATE_FORMAT(date_created, '%Y-%m')) AS float_amount
                ON float_amount.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_paid, '%Y-%m') AS date_sold, SUM(cash) AS cash, SUM(mpesa) AS mpesa, SUM(bank) AS bank
                    FROM sales_payments
                    GROUP BY DATE_FORMAT(date_paid, '%Y-%m')) AS banked_amount
                ON banked_amount.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_paid, '%Y-%m') AS date_sold, SUM(amount_reversed) AS reversed_cash_amount
                    FROM sales_payments
                    GROUP BY DATE_FORMAT(date_paid, '%Y-%m')) AS banked_reversed_cash_only
                ON banked_reversed_cash_only.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_paid, '%Y-%m') AS date_sold, SUM(amount_reversed) AS reversed_mpesa_amount FROM sales_payments
                    WHERE cash < 1 AND mpesa > 0 AND bank < 1
                    GROUP BY DATE_FORMAT(date_paid, '%Y-%m')) AS banked_reversed_mpesa_only
                ON banked_reversed_mpesa_only.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_paid, '%Y-%m') AS date_sold, SUM(amount_reversed) AS reversed_bank_amount FROM sales_payments
                    WHERE cash < 1 AND mpesa < 1 AND bank > 0
                    GROUP BY DATE_FORMAT(date_paid, '%Y-%m')) AS banked_reversed_bank_only
                ON banked_reversed_bank_only.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_paid, '%Y-%m') AS date_sold, SUM(amount_reversed) AS reversed_multiple FROM sales_payments
                    WHERE (cash > 0 AND mpesa > 0) OR (cash>0 AND bank>0) OR (mpesa>0 AND bank > 0)
                    GROUP BY DATE_FORMAT(date_paid, '%Y-%m')) AS banked_reversed_multiple
                ON banked_reversed_multiple.date_sold=dates.date_sold

                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock
                    WHERE selling_price<recom_selling_price
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m')) AS cash_discounts
                ON cash_discounts.date_sold=dates.date_sold

                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock_debtors
                    WHERE selling_price<recom_selling_price
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m')) AS debtor_discounts
                ON debtor_discounts.date_sold=dates.date_sold

                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock
                    WHERE selling_price>recom_selling_price
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m')) AS cash_overcharges
                ON cash_overcharges.date_sold=dates.date_sold

                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock_debtors
                    WHERE selling_price>recom_selling_price
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m')) AS debtor_overcharges
                ON debtor_overcharges.date_sold=dates.date_sold
                
                
                ORDER BY dates.date_sold ASC";

    $result = run_query($conn1, $sql);

    $sales=[];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {
        
        // $user = get_user_record($row['sold_by']);

        $sales[$cr]['date_sold'] = $row['date_sold'];


        $cash_sales = $row['cash_sales'];
        $debtor_sales = $row['debtor_sales'];
        $transfer_sales = $row['transfer_sales'];

        $cash_sales_reversed = $row['cash_reversed'];
        $debtor_sales_reversed = $row['debtor_reversed'];
        $transfer_sales_reversed = $row['transfer_reversed'];

        $gross_sales = $cash_sales + $debtor_sales - $cash_sales_reversed - $debtor_sales_reversed;
        $gross_transfer_sales = $transfer_sales - $transfer_sales_reversed;

        $sales[$cr]['gross_sales'] = $gross_sales;
        $sales[$cr]['transfer_sales'] = $gross_transfer_sales;
        $sales[$cr]['debtor_sales'] = $debtor_sales;
        $sales[$cr]['expenses'] = $row['expenses'];


        $cash_profit = $row['cash_profit'];
        $reversed_cash_profit = $row['reversed_cash_profit'];
        $debtor_profit = $row['debtor_profit'];
        $reversed_debtor_profit = $row['reversed_debtor_profit'];

        $gross_profit = ($cash_profit - $reversed_cash_profit) + ($debtor_profit - $reversed_debtor_profit);
        $sales[$cr]['gross_profit'] = $gross_profit;


        $cr++;

    }

    return $sales;
}



function get_months_sales($conn1) {

    
    $from = date ("Y-m", strtotime("-12 months"));
    $to = date ("Y-m-d", time());

    $sql1 = "SELECT dates.date_sold,
                IFNULL(cash_sales.gross_sales, 0) AS cash_sales,
                IFNULL(cash_sales.gross_profit, 0) AS cash_profit,
                IFNULL(reversed_cash.sales_reversed, 0) AS cash_reversed,
                IFNULL(reversed_cash.reversed_profit, 0) AS reversed_cash_profit,
                
                IFNULL(debtor_sales.gross_sales, 0) AS debtor_sales,
                IFNULL(debtor_sales.gross_profit, 0) AS debtor_profit,
                IFNULL(reversed_debtor.sales_reversed, 0) AS debtor_reversed,
                IFNULL(reversed_debtor.reversed_profit, 0) AS reversed_debtor_profit,
                
                IFNULL(transfer_sales.gross_sales, 0) AS transfer_sales,
                IFNULL(reversed_transfer.sales_reversed, 0) AS transfer_reversed,
                
                IFNULL(expenses.expense, 0) AS expenses
                
                FROM

                (SELECT date_sold FROM
                    (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold FROM sold_stock
                        WHERE date_sold >= LAST_DAY(DATE_SUB(NOW(), INTERVAL 12 MONTH)) + INTERVAL 1 DAY - INTERVAL 1 MONTH
                            AND date_sold < LAST_DAY('{$to}') + INTERVAL 1 DAY
                        GROUP BY DATE_FORMAT(date_sold, '%Y-%m')
                    UNION
                    SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold FROM sold_stock_reversed
                        WHERE date_returned >= LAST_DAY(DATE_SUB(NOW(), INTERVAL 12 MONTH)) + INTERVAL 1 DAY - INTERVAL 1 MONTH
                            AND date_returned < LAST_DAY('{$to}') + INTERVAL 1 DAY
                        GROUP BY DATE_FORMAT(date_returned, '%Y-%m')
                    UNION
                    SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold FROM sold_stock_debtors
                        WHERE date_sold >= LAST_DAY(DATE_SUB(NOW(), INTERVAL 12 MONTH)) + INTERVAL 1 DAY - INTERVAL 1 MONTH
                            AND date_sold < LAST_DAY('{$to}') + INTERVAL 1 DAY
                        GROUP BY DATE_FORMAT(date_sold, '%Y-%m')
                    UNION
                    SELECT DATE_FORMAT(date_transfered, '%Y-%m') AS date_sold FROM sold_stock_transfers
                        WHERE date_transfered >= LAST_DAY(DATE_SUB(NOW(), INTERVAL 12 MONTH)) + INTERVAL 1 DAY - INTERVAL 1 MONTH
                            AND date_transfered < LAST_DAY('{$to}') + INTERVAL 1 DAY
                        GROUP BY DATE_FORMAT(date_transfered, '%Y-%m')
                    UNION
                    SELECT DATE_FORMAT(date_incurred, '%Y-%m') AS date_sold FROM expenses
                        WHERE date_incurred >= LAST_DAY(DATE_SUB(NOW(), INTERVAL 12 MONTH)) + INTERVAL 1 DAY - INTERVAL 1 MONTH
                            AND date_incurred < LAST_DAY('{$to}') + INTERVAL 1 DAY
                        GROUP BY DATE_FORMAT(date_incurred, '%Y-%m')
                    ) AS dates ORDER BY DATE_FORMAT(date_sold, '%Y-%m') DESC
                ) AS dates
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit
                    FROM sold_stock
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m')) AS cash_sales
                ON cash_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit
                    FROM sold_stock_reversed WHERE sale_type='cash'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m')) AS reversed_cash
                ON reversed_cash.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit
                    FROM sold_stock_debtors
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m')) AS debtor_sales
                ON debtor_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit
                    FROM sold_stock_reversed WHERE sale_type='debtor'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m')) AS reversed_debtor
                ON reversed_debtor.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_transfered, '%Y-%m') AS date_sold, SUM(unit_quantity * buying_price) AS gross_sales
                    FROM sold_stock_transfers
                    GROUP BY DATE_FORMAT(date_transfered, '%Y-%m')) AS transfer_sales
                ON transfer_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed
                    FROM sold_stock_reversed WHERE sale_type='transfer'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m')) AS reversed_transfer
                ON reversed_transfer.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_incurred, '%Y-%m') AS date_sold, SUM(amount) AS expense
                    FROM expenses
                    GROUP BY DATE_FORMAT(date_incurred, '%Y-%m')) AS expenses
                ON expenses.date_sold=dates.date_sold
                
                ORDER BY dates.date_sold ASC";


    $sql = "SELECT dates.date_sold,
                IFNULL(cash_sales.gross_sales, 0) AS cash_sales,
                IFNULL(cash_sales.gross_profit, 0) AS cash_profit,
                IFNULL(reversed_cash.sales_reversed, 0) AS cash_reversed,
                IFNULL(reversed_cash.reversed_profit, 0) AS reversed_cash_profit,
                
                IFNULL(debtor_sales.gross_sales, 0) AS debtor_sales,
                IFNULL(debtor_sales.gross_profit, 0) AS debtor_profit,
                IFNULL(reversed_debtor.sales_reversed, 0) AS debtor_reversed,
                IFNULL(reversed_debtor.reversed_profit, 0) AS reversed_debtor_profit,
                
                IFNULL(transfer_sales.gross_sales, 0) AS transfer_sales,
                IFNULL(reversed_transfer.sales_reversed, 0) AS transfer_reversed,
                
                IFNULL(expenses.expense, 0) AS expenses,
                
                
                (IFNULL(cash_sales.gross_sales, 0) +
                 IFNULL(debtor_sales.gross_sales, 0) -
                 IFNULL(reversed_cash.sales_reversed, 0) - 
                 IFNULL(reversed_debtor.sales_reversed, 0)
                ) AS gross_sales,

                (IFNULL(transfer_sales.gross_sales, 0) - 
                 IFNULL(reversed_transfer.sales_reversed, 0)
                ) AS gross_transfer_sales,

                (IFNULL(cash_sales.gross_profit, 0)-
                 IFNULL(reversed_cash.reversed_profit, 0) +
                 IFNULL(debtor_sales.gross_profit, 0) -
                 IFNULL(reversed_debtor.reversed_profit, 0)
                ) AS gross_profit
                
                FROM

                (SELECT date_sold FROM
                    (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold
                        FROM sold_stock
                        WHERE date_sold BETWEEN 
                            DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 11 MONTH), '%Y-%m-01') 
                            AND LAST_DAY(CURRENT_DATE)
                        GROUP BY DATE_FORMAT(date_sold, '%Y-%m')
                    UNION
                    
                    SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold
                    FROM sold_stock_reversed
                    WHERE date_returned BETWEEN 
                        DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 11 MONTH), '%Y-%m-01') 
                        AND LAST_DAY(CURRENT_DATE)
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m')

                    UNION

                    SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold
                    FROM sold_stock_debtors
                    WHERE date_sold BETWEEN 
                        DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 11 MONTH), '%Y-%m-01') 
                        AND LAST_DAY(CURRENT_DATE)
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m')

                    UNION

                    SELECT DATE_FORMAT(date_transfered, '%Y-%m') AS date_sold
                    FROM sold_stock_transfers
                    WHERE date_transfered BETWEEN 
                        DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 11 MONTH), '%Y-%m-01') 
                        AND LAST_DAY(CURRENT_DATE)
                    GROUP BY DATE_FORMAT(date_transfered, '%Y-%m')


                    UNION

                    SELECT DATE_FORMAT(date_incurred, '%Y-%m') AS date_sold
                    FROM expenses
                    WHERE date_incurred BETWEEN 
                        DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 11 MONTH), '%Y-%m-01') 
                        AND LAST_DAY(CURRENT_DATE)
                    GROUP BY DATE_FORMAT(date_incurred, '%Y-%m')
                    ) AS dates ORDER BY date_sold DESC
                ) AS dates
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit
                    FROM sold_stock
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m')) AS cash_sales
                ON cash_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit
                    FROM sold_stock_reversed WHERE sale_type='cash'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m')) AS reversed_cash
                ON reversed_cash.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_sold, '%Y-%m') AS date_sold, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit
                    FROM sold_stock_debtors
                    GROUP BY DATE_FORMAT(date_sold, '%Y-%m')) AS debtor_sales
                ON debtor_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit
                    FROM sold_stock_reversed WHERE sale_type='debtor'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m')) AS reversed_debtor
                ON reversed_debtor.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_transfered, '%Y-%m') AS date_sold, SUM(unit_quantity * buying_price) AS gross_sales
                    FROM sold_stock_transfers
                    GROUP BY DATE_FORMAT(date_transfered, '%Y-%m')) AS transfer_sales
                ON transfer_sales.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_returned, '%Y-%m') AS date_sold, SUM(quantity_returned*selling_price) AS sales_reversed
                    FROM sold_stock_reversed WHERE sale_type='transfer'
                    GROUP BY DATE_FORMAT(date_returned, '%Y-%m')) AS reversed_transfer
                ON reversed_transfer.date_sold=dates.date_sold
                
                LEFT JOIN
                (SELECT DATE_FORMAT(date_incurred, '%Y-%m') AS date_sold, SUM(amount) AS expense
                    FROM expenses
                    GROUP BY DATE_FORMAT(date_incurred, '%Y-%m')) AS expenses
                ON expenses.date_sold=dates.date_sold
                
                
                ORDER BY dates.date_sold ASC";

    $result = run_query($conn1, $sql);

    $sales=[];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {
        
        $sales[$cr]['date_sold'] = $row['date_sold'];

        $cash_sales = $row['cash_sales'];
        $debtor_sales = $row['debtor_sales'];
        $transfer_sales = $row['transfer_sales'];

        $cash_sales_reversed = $row['cash_reversed'];
        $debtor_sales_reversed = $row['debtor_reversed'];
        $transfer_sales_reversed = $row['transfer_reversed'];

        // $gross_sales = $cash_sales + $debtor_sales - $cash_sales_reversed - $debtor_sales_reversed;
        $gross_sales = $row['gross_sales'];
        
        // $gross_transfer_sales = $transfer_sales - $transfer_sales_reversed;
        $gross_transfer_sales = $row['gross_transfer_sales'];


        $sales[$cr]['gross_sales'] = $gross_sales;
        $sales[$cr]['transfer_sales'] = $gross_transfer_sales;
        $sales[$cr]['debtor_sales'] = $debtor_sales;
        $sales[$cr]['expenses'] = $row['expenses'];

        $cash_profit = $row['cash_profit'];
        $reversed_cash_profit = $row['reversed_cash_profit'];
        $debtor_profit = $row['debtor_profit'];
        $reversed_debtor_profit = $row['reversed_debtor_profit'];

        // $gross_profit = ($cash_profit - $reversed_cash_profit) + ($debtor_profit - $reversed_debtor_profit);
        $gross_profit = $row['gross_profit'];
        $sales[$cr]['gross_profit'] = $gross_profit;
        
        $cr++;

    }

    return $sales;
}

function get_top_selling_products_period($conn1, $from, $to, $location_id) {

    $sql = "";
    if($location_id=='0') {

        $sql = "SELECT item_id, SUM(total) AS total FROM

                (SELECT item_id, COUNT(*) AS total FROM sold_stock WHERE
                DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                GROUP BY item_id
                UNION ALL

                SELECT item_id, COUNT(*) AS total FROM sold_stock_debtors WHERE
                DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                GROUP BY item_id
                UNION ALL

                SELECT item_id, COUNT(*) AS total FROM sold_stock_transfers WHERE
                DATE_FORMAT(date_transfered, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                GROUP BY item_id

                UNION ALL
                SELECT item_id, (0-COUNT(*)) AS total FROM sold_stock_reversed WHERE
                DATE_FORMAT(date_returned, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                GROUP BY item_id
                ) AS a
                
                GROUP BY item_id
                ORDER BY total DESC
                LIMIT 4";

    } else {

        $sql = "SELECT item_id, SUM(total) AS total FROM

                (SELECT item_id, COUNT(*) AS total FROM sold_stock WHERE location_id='{$location_id}' AND
                DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                GROUP BY item_id
                UNION ALL

                SELECT item_id, COUNT(*) AS total FROM sold_stock_debtors WHERE location_id='{$location_id}' AND
                DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                GROUP BY item_id
                UNION ALL

                SELECT item_id, COUNT(*) AS total FROM sold_stock_transfers WHERE location_id='{$location_id}' AND
                DATE_FORMAT(date_transfered, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                GROUP BY item_id

                UNION ALL
                SELECT item_id, (0-COUNT(*)) AS total FROM sold_stock_reversed WHERE location_id='{$location_id}' AND
                DATE_FORMAT(date_returned, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                GROUP BY item_id
                ) AS a
                
                GROUP BY item_id
                ORDER BY total DESC
                LIMIT 4";
    }


    $result = run_query($conn1, $sql);

    $products=[];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $products[$cr]['item'] = get_item_details($conn1, $row['item_id']);
        $products[$cr]['total'] = $row['total'];
        $cr++;
    }

    return $products;

}

function get_period_sales_profit($conn1, $from, $to, $location_id) {

    $sql ="";

    if($location_id=='0') {

        $sql = "SELECT item_id, SUM(total_quantity) AS total_quantity, SUM(purchases) AS purchases, SUM(sales) AS sales, SUM(profit) AS profit
                FROM

                (SELECT a.item_id, SUM(a.unit_quantity*b.unit_quantity) AS total_quantity,
                        SUM(a.unit_quantity*a.buying_price) AS purchases,
                        SUM(a.unit_quantity*a.selling_price) AS sales,
                        SUM((a.selling_price-a.buying_price)*a.unit_quantity) AS profit
                FROM
                (SELECT * FROM sold_stock WHERE transaction_code!='' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS a
                LEFT JOIN
                (SELECT * FROM stock_units) AS b
                ON a.unit_id=b.id
                
                GROUP BY a.item_id

                UNION ALL
                SELECT a.item_id, (0-SUM(b.unit_quantity*a.quantity_returned)) AS total_quantity, 0 AS purchases, (0-SUM(a.quantity_returned*a.selling_price)) AS sales, (0 - SUM((a.selling_price-a.buying_price)*a.quantity_returned)) AS profit
                FROM 
                
                (SELECT * FROM sold_stock_reversed WHERE
                DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS a
                LEFT JOIN
                (SELECT * FROM stock_units) AS b
                ON a.unit_id=b.id
                GROUP BY a.item_id
                
                UNION ALL
                SELECT item_id, 0 AS total_quantity, (0-SUM(unit_quantity*buying_price)) AS purchases, 0 AS sales, 0 AS profit
                FROM stock_returns WHERE DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND invoice_item_id IN
                    (SELECT id FROM supplier_invoice_items WHERE DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))
                GROUP BY item_id
                ) AS a


                GROUP BY item_id
                
                ORDER BY sales DESC";

    } else {

        $sql = "SELECT item_id, SUM(total_quantity) AS total_quantity, SUM(purchases) AS purchases, SUM(sales) AS sales, SUM(profit) AS profit
                FROM

                (SELECT a.item_id, SUM(a.unit_quantity*b.unit_quantity) AS total_quantity,
                        SUM(a.unit_quantity*a.buying_price) AS purchases,
                        SUM(a.unit_quantity*a.selling_price) AS sales,
                        SUM((a.selling_price-a.buying_price)*a.unit_quantity) AS profit
                FROM
                (SELECT * FROM sold_stock WHERE location_id='{$location_id}' AND transaction_code!='' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS a
                LEFT JOIN
                (SELECT * FROM stock_units) AS b
                ON a.unit_id=b.id
                
                GROUP BY a.item_id

                UNION ALL
                SELECT a.item_id, (0-SUM(b.unit_quantity*a.quantity_returned)) AS total_quantity, 0 AS purchases, (0-SUM(a.quantity_returned*a.selling_price)) AS sales, (0 - SUM((a.selling_price-a.buying_price)*a.quantity_returned)) AS profit
                FROM 
                
                (SELECT * FROM sold_stock_reversed WHERE location_id='{$location_id}' AND
                DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS a
                LEFT JOIN
                (SELECT * FROM stock_units) AS b
                ON a.unit_id=b.id
                GROUP BY a.item_id
                
                UNION ALL
                SELECT item_id, 0 AS total_quantity, (0-SUM(unit_quantity*buying_price)) AS purchases, 0 AS sales, 0 AS profit
                FROM stock_returns WHERE location_id='{$location_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND invoice_item_id IN
                    (SELECT id FROM supplier_invoice_items WHERE DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))
                GROUP BY item_id
                ) AS a


                GROUP BY item_id
                
                ORDER BY sales DESC";

    }

    $result = run_query($conn1, $sql);

    $sales = [];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {

        $sales[$cr]['item'] = get_item_full_details($conn1, $row['item_id']);
        $sales[$cr]['sales'] = $row['sales'];
        $sales[$cr]['purchases'] = $row['purchases'];
        $sales[$cr]['quantity'] = $row['total_quantity'];
        $sales[$cr]['profit'] = $row['profit'];
        $sales[$cr]['item_units'] = get_item_selling_units($conn1, $row['item_id']);

        $cr++;

    }

    return $sales;

}

function get_period_expenses($conn1, $from, $to, $location_id) {
    $sql = "";

    if($location_id=='0') {

        $sql = "SELECT purpose, SUM(amount) AS amount FROM expenses WHERE DATE_FORMAT(date_incurred, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
            DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            GROUP BY purpose";

    } else {
        $sql = "SELECT purpose, SUM(amount) AS amount FROM expenses WHERE DATE_FORMAT(date_incurred, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
            DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
            AND added_by IN (SELECT user_id FROM user_location_rights WHERE location_id='{$location_id}')
            GROUP BY purpose";
    }
    
    $result = run_query($conn1, $sql);

    $expenses=[];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $expenses[$cr]['type'] = $row['purpose'];
        $expenses[$cr]['amount'] = $row['amount'];

        $cr++;
    }

    return $expenses;
}



function get_top_selling_products($conn1) {
    $sql = "SELECT item_id, SUM(total) AS total FROM

            (SELECT item_id, COUNT(*) AS total FROM sold_stock GROUP BY item_id
            UNION ALL
            SELECT item_id, COUNT(*) AS total FROM sold_stock_debtors GROUP BY item_id
            ) AS a
            
            GROUP BY item_id
            ORDER BY total DESC
            LIMIT 4";
    $result = run_query($conn1, $sql);

    $products=[];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {
        $products[$cr]['item'] = get_item_details($conn1, $row['item_id']);
        $products[$cr]['total'] = $row['total'];
        $cr++;
    }

    return $products;

}

function get_period_chart_data($conn1, $from, $to) {
   
    $sql = "SELECT date_sold, SUM(cash_gross) AS cash_gross, SUM(debtor_gross) AS debtor_gross, SUM(cash_reversed) AS cash_reversed, SUM(debtor_reversed) AS debtor_reversed, SUM(expenses) AS expenses, SUM(cash_profit) AS cash_profit, SUM(gross_profit) AS gross_profit, SUM(reversed_profit) AS reversed_profit, SUM(reversed_profit_2) AS reversed_profit_2
    
            FROM
            
            (
            
            SELECT a.date_sold AS date_sold,
                    IFNULL(SUM(cash_sales.gross_sales), 0) AS cash_gross,
                    IFNULL(SUM(debtor_sales.gross_sales), 0) AS debtor_gross,
                    IFNULL(SUM(reversed_cash.sales_reversed), 0) AS cash_reversed,
                    IFNULL(SUM(reversed_debtor.sales_reversed), 0) AS debtor_reversed,
                    IFNULL(SUM(expenses.expense), 0) AS expenses,
                    IFNULL(SUM(cash_sales.gross_profit), 0) AS cash_profit,
                    IFNULL(SUM(debtor_sales.gross_profit), 0) AS gross_profit,
                    IFNULL(SUM(reversed_cash.reversed_profit), 0) AS reversed_profit,
                    IFNULL(SUM(reversed_debtor.reversed_profit), 0) AS reversed_profit_2
                    
                    
                    FROM
                    
                    (SELECT date_sold
                    FROM (
                        SELECT DATE_FORMAT(ss.date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock ss WHERE DATE_FORMAT(ss.date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ss.date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(ss.date_sold, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') AS date_sold FROM sold_stock_transfers sst WHERE DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(sst.date_transfered, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock_debtors ssd WHERE DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(ssd.date_sold, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') AS date_sold FROM expenses ex WHERE DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(ex.date_incurred, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(si.date_created, '%Y-%m-%d') AS date_sold FROM supplier_invoices si WHERE DATE_FORMAT(si.date_created, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(si.date_created, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(si.date_created, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(sp.date_added, '%Y-%m-%d') AS date_sold FROM stock_positive sp WHERE DATE_FORMAT(sp.date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(sp.date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(sp.date_added, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(sn.date_removed, '%Y-%m-%d') AS date_sold FROM stock_negative sn WHERE DATE_FORMAT(sn.date_removed, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(sn.date_removed, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(sn.date_removed, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(sr.date_returned, '%Y-%m-%d') AS date_sold FROM stock_returns sr WHERE DATE_FORMAT(sr.date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(sr.date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(sr.date_returned, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(dp.date_of_payment, '%Y-%m-%d') AS date_sold FROM debtors_payments dp WHERE DATE_FORMAT(dp.date_of_payment, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(dp.date_of_payment, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(dp.date_of_payment, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(tp.date_paid, '%Y-%m-%d') AS date_sold FROM transfer_payments tp WHERE DATE_FORMAT(tp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(tp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') GROUP BY DATE_FORMAT(tp.date_paid, '%Y-%m-%d')
                    ) AS dates ORDER BY dates.date_sold DESC) AS a
                    
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(ss1.date_sold, '%Y-%m-%d') AS date_sold, SUM(ss1.unit_quantity * ss1.selling_price) AS gross_sales, SUM((ss1.selling_price-ss1.buying_price) * ss1.unit_quantity) AS gross_profit FROM sold_stock ss1
                        WHERE DATE_FORMAT(ss1.date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ss1.date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(ss1.date_sold, '%Y-%m-%d')) AS cash_sales
                    ON cash_sales.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') AS date_sold, SUM(ssr.quantity_returned*selling_price) AS sales_reversed, SUM((ssr.selling_price-buying_price)*ssr.quantity_returned) AS reversed_profit FROM sold_stock_reversed ssr
                        WHERE DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND sale_type='cash'
                        GROUP BY DATE_FORMAT(ssr.date_returned, '%Y-%m-%d')) AS reversed_cash
                    ON reversed_cash.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') AS date_sold, SUM(ssd.unit_quantity * ssd.selling_price) AS gross_sales, SUM((ssd.selling_price-ssd.buying_price) * ssd.unit_quantity) AS gross_profit FROM sold_stock_debtors ssd
                        WHERE DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(ssd.date_sold, '%Y-%m-%d')) AS debtor_sales
                    ON debtor_sales.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') AS date_sold, SUM(ssr.quantity_returned*ssr.selling_price) AS sales_reversed, SUM((ssr.selling_price-ssr.buying_price)*ssr.quantity_returned) AS reversed_profit FROM sold_stock_reversed ssr
                        WHERE DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND sale_type='debtor'
                        GROUP BY DATE_FORMAT(ssr.date_returned, '%Y-%m-%d')) AS reversed_debtor
                    ON reversed_debtor.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') AS date_sold, SUM(sst.unit_quantity * sst.buying_price) AS gross_sales FROM sold_stock_transfers sst
                        WHERE DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(sst.date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(sst.date_transfered, '%Y-%m-%d')) AS transfer_sales
                    ON transfer_sales.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') AS date_sold, SUM(ssr.quantity_returned*ssr.selling_price) AS sales_reversed FROM sold_stock_reversed ssr
                        WHERE DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ssr.date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND sale_type='transfer'
                        GROUP BY DATE_FORMAT(ssr.date_returned, '%Y-%m-%d')) AS reversed_transfer
                    ON reversed_transfer.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') AS date_sold, SUM(ex.amount) AS expense FROM expenses ex
                        WHERE DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') >=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ex.date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(ex.date_incurred, '%Y-%m-%d')) AS expenses
                    ON expenses.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(of.date_created, '%Y-%m-%d') AS date_sold, SUM(of.float_amount) AS cash_float FROM opening_float of
                        WHERE DATE_FORMAT(of.date_created, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(of.date_created, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(of.date_created, '%Y-%m-%d')) AS float_amount
                    ON float_amount.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(sp.date_paid, '%Y-%m-%d') AS date_sold, SUM(sp.cash) AS cash, SUM(sp.mpesa) AS mpesa, SUM(sp.bank) AS bank FROM sales_payments sp
                        WHERE DATE_FORMAT(sp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(sp.date_paid, '%Y-%m-%d')) AS banked_amount
                    ON banked_amount.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(sp.date_paid, '%Y-%m-%d') AS date_sold, SUM(sp.amount_reversed) AS reversed_cash_amount FROM sales_payments sp
                        WHERE sp.cash > 0 AND sp.mpesa < 1 AND sp.bank < 1 AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(sp.date_paid, '%Y-%m-%d')) AS banked_reversed_cash_only
                    ON banked_reversed_cash_only.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(sp.date_paid, '%Y-%m-%d') AS date_sold, SUM(sp.amount_reversed) AS reversed_mpesa_amount FROM sales_payments sp
                        WHERE sp.cash < 1 AND sp.mpesa > 0 AND sp.bank < 1 AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(sp.date_paid, '%Y-%m-%d')) AS banked_reversed_mpesa_only
                    ON banked_reversed_mpesa_only.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(sp.date_paid, '%Y-%m-%d') AS date_sold, SUM(sp.amount_reversed) AS reversed_bank_amount FROM sales_payments sp
                        WHERE sp.cash < 1 AND sp.mpesa < 1 AND sp.bank > 0 AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(sp.date_paid, '%Y-%m-%d')) AS banked_reversed_bank_only
                    ON banked_reversed_bank_only.date_sold=a.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(sp.date_paid, '%Y-%m-%d') AS date_sold, SUM(sp.amount_reversed) AS reversed_multiple FROM sales_payments sp
                        WHERE DATE_FORMAT(sp.date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(sp.date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND ((sp.cash > 0 AND sp.mpesa > 0) OR (sp.cash>0 AND sp.bank>0) OR (sp.mpesa>0 AND sp.bank > 0))
                        GROUP BY DATE_FORMAT(sp.date_paid, '%Y-%m-%d')) AS banked_reversed_multiple
                    ON banked_reversed_multiple.date_sold=a.date_sold

                    LEFT JOIN
                    (SELECT DATE_FORMAT(ss.date_sold, '%Y-%m-%d') AS date_sold, SUM((ss.recom_selling_price-selling_price) * ss.unit_quantity) AS discounts FROM sold_stock ss
                        WHERE ss.selling_price<ss.recom_selling_price AND DATE_FORMAT(ss.date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ss.date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(ss.date_sold, '%Y-%m-%d')) AS cash_discounts
                    ON cash_discounts.date_sold=a.date_sold

                    LEFT JOIN
                    (SELECT DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') AS date_sold, SUM((ssd.recom_selling_price-ssd.selling_price) * ssd.unit_quantity) AS discounts FROM sold_stock_debtors ssd
                        WHERE ssd.selling_price<ssd.recom_selling_price AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(ssd.date_sold, '%Y-%m-%d')) AS debtor_discounts
                    ON debtor_discounts.date_sold=a.date_sold

                    LEFT JOIN
                    (SELECT DATE_FORMAT(ss.date_sold, '%Y-%m-%d') AS date_sold, SUM((ss.selling_price-ss.recom_selling_price) * ss.unit_quantity) AS overcharges FROM sold_stock ss
                        WHERE ss.selling_price>ss.recom_selling_price AND DATE_FORMAT(ss.date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ss.date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(ss.date_sold, '%Y-%m-%d')) AS cash_overcharges
                    ON cash_overcharges.date_sold=a.date_sold

                    LEFT JOIN
                    (SELECT DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') AS date_sold, SUM((ssd.selling_price-ssd.recom_selling_price) * ssd.unit_quantity) AS overcharges FROM sold_stock_debtors ssd
                        WHERE ssd.selling_price>ssd.recom_selling_price AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(ssd.date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY DATE_FORMAT(ssd.date_sold, '%Y-%m-%d')) AS debtor_overcharges
                    ON debtor_overcharges.date_sold=a.date_sold
                    
                    
                    GROUP BY a.date_sold
                    
                    
                    
                    
                    
                    
                    ) AS s GROUP BY date_sold";
    
    

    $result = run_query($conn1, $sql);

    $data = [];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $data[$cr]['date'] = $row['date_sold'];
        $data[$cr]['gross_sales'] = $row['cash_gross'] + $row['debtor_gross'] - $row['cash_reversed'] - $row['debtor_reversed'];
        $data[$cr]['expenses'] = $row['expenses'];
        $data[$cr]['gross_profit'] = $row['cash_profit'] + $row['gross_profit'] - $row['reversed_profit'] - $row['reversed_profit_2'];
        $data[$cr]['net_profit'] = $row['cash_profit'] + $row['gross_profit'] - $row['reversed_profit'] - $row['reversed_profit_2'] - $row['expenses'];

        $cr++;
    }

    return $data;



}

function backupDatabase_local($conn1) {

    $sql = "SELECT * FROM backups WHERE id IN (SELECT MAX(id) FROM backups)";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {

        // compare the time
        $today = date('Y-m-d H:i:s', time());

        $record = mysqli_fetch_assoc($result);

        $last_time = $record['last_backup'];
        $previous_name = $record['file_name'];
        $id = $record['id'];

        // convert the dates to timestamps
        $current = strtotime($today);
        $last = strtotime($last_time);

        // difference
        $diff = $current - $last;

        // check if it is more than or equal to 5 minutes
        if($diff >= (5*60)) {

            global $dbname;
            $database_name = $dbname;

            // backup the database
            $backupDir = "backups/";

            $file_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

            $backupFile = $backupDir . $file_name;


            $command = ('C:\xampp\mysql\bin\mysqldump.exe --user=root --password=steve254 --host=localhost '.$database_name.' > '.$backupFile);

        

            exec($command, $output, $returnValue);

            $successfull = 1;

            if($returnValue != 0) {

                $successfull = 0;

                // update the record
                $sql = "UPDATE backups
                        SET
                        file_name='{$file_name}',
                        last_backup='{$today}',
                        successfull='{$successfull}'
                        WHERE id='{$id}'";

                $result = run_query($conn1, $sql);

                return true;

            } else {

                // update the record
                $sql = "UPDATE backups
                        SET
                        file_name='{$file_name}',
                        last_backup='{$today}',
                        successfull='{$successfull}'
                        WHERE id='{$id}'";

                $result = run_query($conn1, $sql);

                // delete the previous one if successfull
                // if($successfull==1) {
                    $upload_dir = 'backups/';

                    if (file_exists($upload_dir.$previous_name)) {

                        unlink($upload_dir.$previous_name);
                    }


                    return true;
                // }

            }

        } else {
            return true;
        }

    } else {

        $today = date('Y-m-d H:i:s', time());

        global $dbname;
        $database_name = $dbname;
        // backup the first time
        $backupDir = 'backups/';

        $file_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

        $backupFile = $backupDir . $file_name;
        
        $command = ('C:\xampp\mysql\bin\mysqldump.exe --user=root --password=steve254 --host=localhost '.$database_name.' > '.$backupFile);

        exec($command, $output, $returnValue);

        $succssfull = 1;

        if($returnValue != 0) {
            $succssfull = 0;

            $sql = "INSERT INTO backups (last_backup, successfull, file_name)
                    VALUES ('{$today}', '{$succssfull}', '{$file_name}')";
            $result = run_query($conn1, $sql);

            return true;

        } else {

            $sql = "INSERT INTO backups (last_backup, successfull, file_name)
                    VALUES ('{$today}', '{$succssfull}', '{$file_name}')";
            $result = run_query($conn1, $sql);

            return true;

        }


    }
    
   
}

function backupDatabase_new($conn1) {
    $user_id = $request->uid;
    
    // set_download_time($conn, $user_id);
    
    // build the mysqldump
    
    global $db_name;
    $filename = $db_name.'.sql';

    global $password;
    
    $command = ("mysqldump --user=rydenent_admin --password=\"$password\" --host=localhost $db_name > $filename 2>error_log");
    
    // execute the command and capture the output
    exec($command, $output, $returnCode);
    
    if($returnCode===0) {
        
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Content-Length: ' . filesize($filename));
        
        file_put_contents('backup.sql', $filename);
        // Read the file and output it
        readfile($filename);

        unlink($filename); // remove the temporary file
        
        //$backupFile = implode("/n", $output);
        
        // mysqli_close($conn);
        
        
        
    } else {
        
        // reset_download_time($conn);
        
        // $data_insert = array(
        //     "status" => "error",
        //     "message" => "Could not download file!"
        // );
        // // return the error
        // echo json_encode($data_insert);
        // mysqli_close($conn);
    }
}

function backupDatabase($conn1) {
    
    
    $sql = "SELECT * FROM backups WHERE id IN (SELECT MAX(id) FROM backups)";
    $result = run_query($conn1, $sql);

    // compare the time
    $today = date('Y-m-d H:i:s', time());
    if(mysqli_num_rows($result) > 0) {
        
        

        $record = mysqli_fetch_assoc($result);

        $last_time = $record['last_backup'];
        $previous_name = $record['file_name'];
        $id = $record['id'];

        // convert the dates to timestamps
        $current = strtotime($today);
        $last = strtotime($last_time);

        // difference
        $diff = $current - $last;
        
        // check if it is more than or equal to 5 minutes
        if($diff >= (1*60)) {
            
            global $dbname;
            global $username;
            global $password;
            // build the mysqldump
            $backupDir = "backups/";

            $file_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

            $backupFile = $backupDir . $file_name;
            
            // $command = "mysqldump -h localhost -u $username -p\"$password\" $dbname > $backupFile 2>error_log";

            $command = ("mysqldump --user=\"$username\" --password=\"$password\" --host=localhost $db_name > $filename 2>error_log");
    
            
            // execute the command and capture the output
            exec($command, $output, $returnCode);
            
            $successfull = 1;
            
            if($returnCode==0) {
                
                // update the record
                $sql = "UPDATE backups
                        SET
                        file_name='{$file_name}',
                        last_backup='{$today}',
                        successfull='{$successfull}'
                        WHERE id='{$id}'";

                $result = run_query($conn1, $sql);

                // delete the previous one if successfull
                $upload_dir = 'backups/';

                if (file_exists($upload_dir.$previous_name)) {

                    unlink($upload_dir.$previous_name);
                }

                return true;
                
            } else {
                
                $successfull = 0;

                // update the record
                $sql = "UPDATE backups
                        SET
                        file_name='{$file_name}',
                        last_backup='{$today}',
                        successfull='{$successfull}'
                        WHERE id='{$id}'";

                $result = run_query($conn1, $sql);

                return true;
            }
            
        } else {
            return true;
        }
        
        
     
    } else {
        
        global $dbname;
        global $username;
        global $password;
        
        // build the mysqldump
        $backupDir = "backups/";

        $file_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

        $backupFile = $backupDir . $file_name;
        
        // $command = "mysqldump -h localhost -u $username -p\"$password\" $dbname > $backupFile 2>error_log";

        $command = ("mysqldump --user=\"$username\" --password=\"$password\" --host=localhost $db_name > $filename 2>error_log");
    
        
        // execute the command and capture the output
        exec($command, $output, $returnCode);
        
        $successfull = 1;
        
        if($returnCode != 0) {
            $successfull = 0;

            $sql = "INSERT INTO backups (last_backup, successfull, file_name)
                    VALUES ('{$today}', '{$successfull}', '{$file_name}')";
            $result = run_query($conn1, $sql);

            return true;

        } else {

            $sql = "INSERT INTO backups (last_backup, successfull, file_name)
                    VALUES ('{$today}', '{$successfull}', '{$file_name}')";
            $result = run_query($conn1, $sql);

            return true;

        }

        
    }
    
    
    
    
    
    
}




function check_device_mac_old($conn1, $mac) {

    $sql = "SELECT * FROM registered_devices WHERE mac='{$mac}'";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {
        return true;
    } else {

        // check if the mac is a domain name of the current host
        $host = $_SERVER['HTTP_HOST'];

        if($host!=='localhost' && $host===$mac) {
            return true;
        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "This computer is not registered to access system."
            );
            echo json_encode($data_insert);
            mysqli_close($conn1);
    
            exit();
        }
        
    }
}


function check_device_mac_login_old($conn1, $mac) {
    $sql = "SELECT * FROM registered_devices WHERE mac='{$mac}'";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {

        return true;
        
    } else {
        // check if the mac is a domain name of the current host
        $host = $_SERVER['HTTP_HOST'];

        if($host!=='localhost' && $host===$mac) {
            return true;
        } else {
            return false;
        }
    }
}


function check_device_mac($conn1, $mac) {

    $sql = "SELECT * FROM registered_devices WHERE mac='{$mac}'";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {
        return true;
    } else {

        // check if the mac is a domain name of the current host
        $host = $_SERVER['HTTP_ORIGIN'];
        $hostname = explode('//', $host);
        
        $name = $hostname[1];

        if($name!=='localhost' && $name===$mac) {
            return true;
        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "This computer is not registered to access system."
            );
            echo json_encode($data_insert);
            mysqli_close($conn1);
    
            exit();
        }
        
    }
}


function check_device_mac_login($conn1, $mac) {
    $sql = "SELECT * FROM registered_devices WHERE mac='{$mac}'";
    $result = run_query($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {

        // echo $_SERVER['HTTP_ORIGIN'];
        // exit();

        return true;
        
    } else {
        // check if the mac is a domain name of the current host
        $host = $_SERVER['HTTP_ORIGIN'];
        $hostname = explode('//', $host);
        
        $name = $hostname[1];

        if($name!=='localhost' && $name===$mac) {
            return true;
        } else {
           return false;
        }
    }
}


function get_min_stock_items($conn1) {


    $sql = "SELECT st.id, st.name, IFNULL(sc.category_name, '-') AS category_name, IFNULL(st.category_id, 0) AS category_id, st.vat_id,
                st.buying_price AS buying_price, units_max_qty.unit_qty AS max_unit_qty,
                vat.vat_percent, IFNULL(sl.total, 0) AS locations, sus.units AS units,
                IFNULL(units_min_qty.total, 0) AS min_qty_ok,
                IFNULL(units_prices.item_id, 0) AS prices_ok,
                users.username AS edited_by, st.last_edited, st.image
                
                FROM      

            (SELECT * FROM `stock` WHERE deleted=0 AND id IN
                (
                    SELECT id FROM stock WHERE deleted=0
            AND id IN
            (
                SELECT loc.item_id FROM
                    (SELECT item_id, IFNULL(SUM(location_quantity), 0) AS location_quantity FROM stock_locations WHERE deleted=0 GROUP BY item_id, location_id) AS loc
                    
                        LEFT JOIN
                        (SELECT item_id, unit_id, IFNULL(min_quantity, 0) AS min_quantity FROM min_stock_items) AS min_qty
                        ON min_qty.item_id=loc.item_id

                        LEFT JOIN
                        (SELECT id, item_id, IFNULL(unit_quantity, 0) AS unit_qty FROM stock_units WHERE deleted=0) AS unit_qty
                        ON unit_qty.id = min_qty.unit_id
                    WHERE (loc.location_quantity/unit_qty.unit_qty) < min_qty.min_quantity
                )
                )
            ) AS st
            LEFT JOIN
            (SELECT * FROM stock_categories) AS sc
            ON sc.id=st.category_id

            LEFT JOIN
            (SELECT * FROM stock_units WHERE deleted=0) AS su
            ON su.item_id=st.id

            LEFT JOIN
            (SELECT item_id, MAX(unit_quantity) AS unit_qty FROM stock_units WHERE deleted=0 GROUP BY item_id) AS units_max_qty
            ON units_max_qty.item_id=st.id

            LEFT JOIN
            (SELECT * FROM vat_records) AS vat
            ON vat.id=st.vat_id

            LEFT JOIN
            (SELECT item_id, COUNT(*) AS total FROM stock_locations WHERE deleted=0 GROUP BY item_id) AS sl
            ON sl.item_id=st.id

            LEFT JOIN
            (SELECT item_id, COUNT(*) AS units FROM stock_units WHERE deleted=0 GROUP BY item_id) AS sus
            ON sus.item_id=st.id

            LEFT JOIN
            (SELECT item_id, COUNT(*) AS total FROM stock_units WHERE deleted=0 AND unit_quantity=1 GROUP BY item_id) AS units_min_qty
            ON units_min_qty.item_id=st.id

            LEFT JOIN
            (SELECT item_id, unit_min_selling_price, unit_quantity, unit_recom_selling_price FROM stock_units WHERE deleted = 0) AS units_prices
            ON (units_prices.item_id = st.id) AND
            ((units_prices.unit_min_selling_price < (st.buying_price * units_prices.unit_quantity)) OR 
            units_prices.unit_recom_selling_price < (st.buying_price * units_prices.unit_quantity))

            LEFT JOIN
            (SELECT * FROM users) AS users
            ON users.id=st.edited_by


            GROUP BY st.id";



    // $sql = "SELECT * FROM stock WHERE deleted=0
    //         AND id IN
    //         (
    //             SELECT loc.item_id FROM
    //                 (SELECT item_id, IFNULL(location_quantity, 0) AS location_quantity FROM stock_locations WHERE deleted=0) AS loc
    //                 LEFT JOIN
    //                 (SELECT item_id, IFNULL(MAX(unit_quantity), 0) AS unit_qty FROM stock_units WHERE deleted=0 GROUP BY item_id) AS unit_qty
    //                 ON unit_qty.item_id = loc.item_id
    //                 LEFT JOIN
    //                 (SELECT IFNULL(min_quantity, 0) AS min_quantity FROM min_stock_items) AS min_qty
    //                 ON min_qty.min_quantity >=0
    //             WHERE (loc.location_quantity/unit_qty.unit_qty) < min_qty.min_quantity
    //         )";


    $result = run_query($conn1, $sql);

    $stock=[];
    $cr=0;

    while($row = mysqli_fetch_assoc($result)) {
        $stock[$cr]['id'] = encrypt_data($row['id']);
        $stock[$cr]['name'] = utf8_encode($row['name']);
        $stock[$cr]['category_id'] = encrypt_data($row['category_id']);
        $stock[$cr]['category'] = $row['category_name'];
        $stock[$cr]['buying_price'] = $row['buying_price'];
        $stock[$cr]['max_unit_qty'] = $row['max_unit_qty'];
        $stock[$cr]['vat_id'] = encrypt_data($row['vat_id']);
        $stock[$cr]['vat'] = $row['vat_percent'];
        $stock[$cr]['image'] = $row['image'];
        $stock[$cr]['last_edited'] = $row['last_edited'];
        $stock[$cr]['edited_by'] = $row['edited_by'];;

        $stock[$cr]['locations_count'] = $row['locations'];
        $stock[$cr]['units'] = $row['units'];
        $stock[$cr]['min_qty_ok'] = $row['min_qty_ok'];
        $stock[$cr]['prices_ok'] = $row['prices_ok'];
        $cr++;
    }


    $return['stock'] = $stock;

    return $stock;

}

function get_total_min_stock_items($conn1) {

    $sql = "SELECT COUNT(*) AS total FROM stock WHERE deleted=0
            AND id IN
            (
                SELECT loc.item_id FROM
                    (SELECT item_id, IFNULL(SUM(location_quantity), 0) AS location_quantity FROM stock_locations WHERE deleted=0 GROUP BY item_id, location_id) AS loc
                    
                    LEFT JOIN
                    (SELECT item_id, unit_id, IFNULL(min_quantity, 0) AS min_quantity FROM min_stock_items) AS min_qty
                    ON min_qty.item_id=loc.item_id
                
                    LEFT JOIN
                    (SELECT id, item_id, IFNULL(unit_quantity, 0) AS unit_qty FROM stock_units WHERE deleted=0) AS unit_qty
                    ON unit_qty.id = min_qty.unit_id
                WHERE (loc.location_quantity/unit_qty.unit_qty) < min_qty.min_quantity
            )";

    // $sql_old = "SELECT IFNULL(COUNT(*), 0) AS total FROM stock WHERE deleted=0
    //         AND id IN
    //         (
    //             SELECT loc.item_id FROM
    //                 (SELECT item_id, IFNULL(location_quantity, 0) AS location_quantity FROM stock_locations WHERE deleted=0) AS loc
    //                 LEFT JOIN
    //                 (SELECT item_id, IFNULL(MAX(unit_quantity), 0) AS unit_qty FROM stock_units WHERE deleted=0 GROUP BY item_id) AS unit_qty
    //                 ON unit_qty.item_id = loc.item_id
    //                 LEFT JOIN
    //                 (SELECT IFNULL(min_quantity, 0) AS min_quantity FROM min_stock_items) AS min_qty
    //                 ON min_qty.min_quantity >=0
    //             WHERE (loc.location_quantity/unit_qty.unit_qty) < min_qty.min_quantity
    //         )";

    $result = run_query($conn1, $sql);

    $row = mysqli_fetch_assoc($result);

    // $sql2 = "SELECT IFNULL(min_quantity, 0) AS min_quantity FROM min_stock_items";
    // $result2 = run_query($conn1, $sql2);

    // $row2 = mysqli_fetch_assoc($result2);

    // $return['total'] = $row['total'];
    // $return['min_quantity'] = $row2['min_quantity'];

    return $row['total'];

}

function get_debtor_all_transactions($conn1, $debtor_id) {

    $sql = "SELECT * FROM
            (SELECT 'Invoice' AS transaction_type, invoices.transaction_code AS invoice_number,
                IFNULL(invoices.amount, 0) AS total,
                invoices.date_sold AS date_created, '-' AS payment_channel, IFNULL(users.username, '') AS added_by, '-' AS paid_to
                FROM
                
                (SELECT transaction_code, SUM(unit_quantity*selling_price) AS amount, sold_by, date_sold FROM sold_stock_debtors WHERE customer_id='{$debtor_id}' GROUP BY transaction_code, sold_by, date_sold) AS invoices
                
                
                LEFT JOIN
                (SELECT id, username FROM users) AS users
                ON users.id=invoices.sold_by

                
                UNION ALL
                SELECT 'Payment' AS transaction_type, '-' AS invoice_number, payments.amount_paid AS total, payments.date_of_payment AS date_created,  payments.payment_channel AS payment_channel, IFNULL(users.username, '') AS added_by, IFNULL(users.username, '') AS paid_to
                    FROM
                    (SELECT (cash+mpesa+bank+amount_credited) AS amount_paid, added_by, date_of_payment, CASE WHEN cash>0 THEN 'Cash' WHEN mpesa>0 THEN 'Mpesa' WHEN bank>0 THEN 'Bank' WHEN amount_credited>0 THEN 'Return' ELSE '-' END AS payment_channel
                    FROM debtors_payments WHERE customer_id='{$debtor_id}') AS payments
                        LEFT JOIN
                    (SELECT id, username FROM users) AS users
                    ON users.id=payments.added_by
            ) AS z WHERE z.total>0
            ORDER BY z.date_created ASC";

    $result = run_query($conn1, $sql);

    $cr=0;
    $transactions = [];

    $opening_balance = 0;
    $closing_balance = 0;

    while($row=mysqli_fetch_assoc($result)) {
        if($row['transaction_type']==='Payment') {
            $closing_balance = $opening_balance - $row['total'];
        }

        if($row['transaction_type']==='Invoice') {
            $closing_balance = $opening_balance + $row['total'];
        }

        $transactions[$cr]['opening_balance'] = $opening_balance;
        $transactions[$cr]['transaction_type'] = $row['transaction_type'];
        $transactions[$cr]['invoice_number'] = $row['invoice_number'];
        $transactions[$cr]['amount'] = $row['total'];
        $transactions[$cr]['payment_channel'] = $row['payment_channel'];
        $transactions[$cr]['added_by'] = $row['added_by'];
        $transactions[$cr]['paid_to'] = $row['paid_to'];
        $transactions[$cr]['closing_balance'] = $closing_balance;
        $transactions[$cr]['date_created'] = $row['date_created'];

        $opening_balance = $closing_balance;

        $cr++;
    }

    return $transactions;
}



function get_debtor_transaction_opening_balance($conn1, $debtor_id, $transaction_code) {

    $sql = "SELECT * FROM
            (SELECT 'invoice' AS transaction_type, invoices.transaction_code AS invoice_number,
                IFNULL(invoices.amount, 0) AS total,
                invoices.date_sold AS date_created, '-' AS payment_channel, IFNULL(users.username, '') AS added_by, '-' AS paid_to
                FROM
                
                (SELECT transaction_code, SUM(unit_quantity*selling_price) AS amount, sold_by, date_sold FROM sold_stock_debtors WHERE customer_id='{$debtor_id}' GROUP BY transaction_code, sold_by, date_sold) AS invoices
                
                
                LEFT JOIN
                (SELECT id, username FROM users) AS users
                ON users.id=invoices.sold_by

                
                UNION ALL
                SELECT 'payment' AS transaction_type, '-' AS invoice_number, payments.amount_paid AS total, payments.date_of_payment AS date_created,  payments.payment_channel AS payment_channel, IFNULL(users.username, '') AS added_by, IFNULL(users.username, '') AS paid_to
                    FROM
                    (SELECT (cash+mpesa+bank+amount_credited) AS amount_paid, added_by, date_of_payment, CASE WHEN cash>0 THEN 'cash' WHEN mpesa>0 THEN 'mpesa' WHEN bank>0 THEN 'bank' WHEN amount_credited>0 THEN 'return' ELSE '-' END AS payment_channel
                    FROM debtors_payments WHERE customer_id='{$debtor_id}') AS payments
                        LEFT JOIN
                    (SELECT id, username FROM users) AS users
                    ON users.id=payments.added_by
            ) AS z WHERE z.total>0
            ORDER BY z.date_created ASC";

    $result = run_query($conn1, $sql);

    $cr=0;
    $transactions = [];

    $opening_balance = 0;
    $closing_balance = 0;

    while($row=mysqli_fetch_assoc($result)) {
        if($row['transaction_type']==='payment') {
            $closing_balance = $opening_balance - $row['total'];
        }

        if($row['transaction_type']==='invoice') {
            $closing_balance = $opening_balance + $row['total'];
        }

        $transactions[$cr]['opening_balance'] = $opening_balance;
        $transactions[$cr]['transaction_type'] = $row['transaction_type'];
        $transactions[$cr]['invoice_number'] = $row['invoice_number'];
        $transactions[$cr]['amount'] = $row['total'];
        $transactions[$cr]['payment_channel'] = $row['payment_channel'];
        $transactions[$cr]['added_by'] = $row['added_by'];
        $transactions[$cr]['paid_to'] = $row['paid_to'];
        $transactions[$cr]['closing_balance'] = $closing_balance;
        $transactions[$cr]['date_created'] = $row['date_created'];

        if($row['invoice_number'] == $transaction_code) {
            return $opening_balance;
        } else {
            $opening_balance = $closing_balance;
        }

    }

    // return $transactions;
}









function user_log($conn1, $user_id, $action, $mac) {
    $time = date('Y-m-d H:i:s', time());
    $sql = "INSERT INTO user_logs (user_id, action, device_mac, date_created)
            VALUES ('{$user_id}', '{$action}', '{$mac}', '{$time}')";
    
    return run_query($conn1, $sql);
}


function get_all_customer_invoices($conn1, $customer_id) {

    $sql = "SELECT * FROM customer_invoices WHERE customer_id='{$customer_id}' ORDER BY id DESC";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $invoices = [];
        $cr=0;

        while($row=mysqli_fetch_assoc($result)) {

            $invoices[$cr]['id'] = encrypt_data($row['id']);
            $invoices[$cr]['title'] = $row['title'];
            $invoices[$cr]['number'] = $row['inv_number'];
            $invoices[$cr]['date'] = $row['invoice_date'];
            $invoices[$cr]['date_created'] = $row['date_created'];
            $invoices[$cr]['created_by'] = get_user_record($conn1, $row['created_by'])['name'];
            $invoices[$cr]['created_by_id'] = encrypt_data($row['created_by']);

            $items = get_customer_invoice_items($conn1, $row['id']);
            $invoices[$cr]['items'] = $items['items'];
            $invoices[$cr]['invoice_total'] = $items['invoice_total'];
            
            $cr++;

        }

        return $invoices;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Could not get invoices!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    }

}

function get_customer_invoice_items($conn1, $invoice_id) {
    $sql = "SELECT * FROM customer_invoice_items WHERE invoice_id='{$invoice_id}'";
    $result = mysqli_query($conn1, $sql);

    if($result) {

        $return=[];
        $items = [];
        $cr=0;

        $invoice_total = 0;

        while($row=mysqli_fetch_assoc($result)) {

            $items[$cr]['id'] = encrypt_data($row['id']);
            $items[$cr]['item'] = get_item_details($conn1, $row['item_id'])['name'];
            $items[$cr]['unit'] = get_unit_record($conn1, $row['unit_id']);
            
            $items[$cr]['unit_quantity'] = $row['unit_quantity'];
            $items[$cr]['selling_price'] = $row['selling_price'];

            $invoice_total += $row['unit_quantity'] * $row['selling_price'];

            $cr++;

        }

        $return['items'] = $items;
        $return['invoice_total'] = $invoice_total;

        return $return;

    } else {

        $data_insert=array(
            "status" => "error",
            "message" => "Could not get invoice items!"
        );

        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    }
}


function get_latest_upload_date($conn1) {
    $sql = "SELECT * FROM online_uploads ORDER BY id DESC LIMIT 1";
    $result = run_query($conn1, $sql);
    
    if($result) {
        
        if(mysqli_num_rows($result) > 0) {
            $record = mysqli_fetch_assoc($result);
            
            return $record['upload_date'];
            
        } else {
            return '2023-01-01';
        }
    
    } else {
        $data_insert = array(
            "status" => "error",
            "message" => "Could not get latest upload date"
        );
        // return the error
        echo json_encode($data_insert);
        mysqli_close($conn1);
        
        exit();
    }

}










$action = decrypt_data($request->action);


if($action === 'login-offline') {

    $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
    $usernamedb = mysqli_escape_string($conn, decrypt_data($request->mobile));
    $passworddb = mysqli_escape_string($conn, decrypt_data($request->password));

    // backupDatabase();
    // check the device mac
    if(check_device_mac_login($conn, $mac)) {

        $sql="SELECT * FROM users WHERE (mobile='{$usernamedb}' OR username='{$usernamedb}') AND password='{$passworddb}' AND deleted=0";
        $result=run_query($conn, $sql);

        if(mysqli_num_rows($result)>0) {

            while($row = mysqli_fetch_assoc($result)) {
                // check if user account is disabled
                if($row['disabled'] == 0) {
        
                    $token = '';

                    $user_id = $row['id'];

                    unlock_all_user_locked_items($conn, $user_id);


                    $shop_details = get_user_shop_details($conn, $user_id);

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

                    $jwt = JWT::encode($token, SECRET_KEY);

                    // record the login action
                    if(user_log($conn, $user_id, 'login', $mac)) {

                        $latest_date = '';

                        if($row['role'] === 'admin') {
                            $latest_date = get_latest_upload_date($conn);
                        }


                        $data_insert = array(
                            'access_token' => encrypt_data($jwt),
                            'shop' => $shop_details,
                            'uid' => encrypt_data($user_id),
                            'nm' => encrypt_data($row['username']),
                            'img' => encrypt_data($row['image_url']),
                            'role' => encrypt_data($row['role']),
                            'latest_date' => encrypt_data($latest_date),
                            'status' => "success",
                        );

                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    }

                } else {

                    $data_insert = array(
                        "status" => "error",
                        "message" => "Your account is not active. Talk to your admin!"
                    );
                    // return the error
                    echo json_encode($data_insert);
                    // close connection
                    mysqli_close($conn);

                }
            }

        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Username or password is incorrect"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } else {

        // check if user is admin
        $sql="SELECT * FROM users WHERE (mobile='{$usernamedb}' OR username='{$usernamedb}') AND password='{$passworddb}' AND deleted=0 AND role='admin'";
        $result=run_query($conn, $sql);

        if(mysqli_num_rows($result) > 0) {
            $data_insert=array(
                "status" => "error",
                "message" => "Register this computer to allow system access!",
                "ad" => encrypt_data(1)
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        } else {
            $data_insert=array(
                "status" => "error",
                "message" => "Ask your system admin to first register this computer!",
                "ad" => encrypt_data(0)
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    }

}


if($action === 'get-all-stock') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);


            if(backupDatabase($conn)) {

                $sql = "SELECT * FROM stock WHERE deleted=0";
                $result = run_query($conn, $sql);

                $stock=[];
                $cr=0;

                while($row = mysqli_fetch_assoc($result)) {
                    $stock[$cr]['id'] = encrypt_data($row['id']);
                    $stock[$cr]['name'] = utf8_encode($row['name']);
                    $stock[$cr]['category'] = get_category($conn, $row['category_id']);
                    $stock[$cr]['buying_price'] = $row['buying_price'];
                    $stock[$cr]['vat'] = get_item_vat_record($conn, $row['vat_id']);
                    $stock[$cr]['image'] = $row['image'];
                    $stock[$cr]['last_edited'] = $row['last_edited'];
                    $stock[$cr]['edited_by'] = get_user_record($conn, $row['edited_by'])['name'];

                    $loc_quantity = get_item_all_locations_quantity($conn, $row['id']);

                    $stock[$cr]['locations_qty'] = $loc_quantity['locations_quantity'];
                    $stock[$cr]['locations'] = $loc_quantity['locations'];
                    $stock[$cr]['total_qty'] = $loc_quantity['total_quantity'];
                    $stock[$cr]['selling_units']  = get_item_selling_units($conn, $row['id']);

                    $cr++;
                }


                $min_items =  get_total_min_stock_items($conn);

                $categories = get_all_categories($conn);
                $shop_locations = get_shop_locations($conn);
                $vat_records = get_vat_records($conn);


                $return['stock'] = $stock;
                $return['categories'] = $categories;
                $return['locations'] = $shop_locations;
                $return['vat_records'] = $vat_records;
                $return['min_items'] = $min_items;
                // $return['min_quantity'] = $min_items['min_quantity'];


                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "data" => $return
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Backup failed",
                    "data" => $return
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }

            

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-all-stock-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {

                $stock = get_all_stock_new($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "data" => $stock
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_item_all_locations_quantity') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {

                $loc_quantity = get_item_all_locations_quantity($conn, $item_id);

                $locations_qty = $loc_quantity['locations_quantity'];
                $locations = $loc_quantity['locations'];
                $total_qty = $loc_quantity['total_quantity'];
                $selling_units  = get_item_selling_units($conn, $item_id);

                $alert_quantity = get_item_alert_quantity($conn, $item_id);
                

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "locations_qty" => $locations_qty,
                    "locations" => $locations,
                    "total_qty" => $total_qty,
                    "selling_units" => $selling_units,
                    "alert_unit" => $alert_quantity['alert_unit'],
                    "alert_quantity" => $alert_quantity['alert_quantity']
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }

            

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-shop-locations') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->uid));
        
        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {

                $locations = get_shop_locations($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "locations" => $locations
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-today-suppliers') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        unlock_all_user_locked_items($conn, $added_by);
            
        if(check_device_mac($conn, $mac)) {

            if(backupDatabase($conn)) {
                
                $sql = "SELECT * FROM suppliers WHERE deleted=0";
                $result = run_query($conn, $sql);

                $suppliers = [];
                $cr = 0;

                while($row = mysqli_fetch_assoc($result)) {
                    $suppliers[$cr]['id'] = encrypt_data($row['id']);
                    $suppliers[$cr]['supplier_name'] = $row['supplier_name'];
                    $suppliers[$cr]['today_invoices'] = get_supplier_unconfirmed_invoices($conn, $row['id']);

                    $cr++;
                }


                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "suppliers" => $suppliers
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-stock-value') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));

        // get locations

        if(check_device_mac($conn, $mac)) {

            // check for any items missing the correct units
            $sql = "SELECT * FROM stock WHERE deleted=0
                    AND id NOT IN (SELECT item_id FROM stock_units WHERE unit_quantity=1)
                    AND id IN (SELECT item_id FROM stock_locations WHERE location_quantity > 0)";
            $result = run_query($conn, $sql);

            if(mysqli_num_rows($result) > 0) {

                $categories_Value = get_categories_stock_value($conn);

                $data_insert=array(
                    "status" => "success",
                    "total" => 'error',
                    "categoriesValue" => $categories_Value['categoriesValue'],
                    "message" => "Selling units for some items are not setup correctly. Correct them to get the accurate stock value!"
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {

                $value = get_stock_value($conn);
                $categories_Value = get_categories_stock_value($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "total" => $value['total'],
                    "locationsValue" => $value['locationsValue'],
                    "categoriesValue" => $categories_Value['categoriesValue']
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }


            

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-min-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);


            if(backupDatabase($conn)) {

                $min_items = get_min_stock_items($conn);



                $categories = get_all_categories($conn);
                $shop_locations = get_shop_locations($conn);
                $vat_records = get_vat_records($conn);


                $return['stock'] = $min_items;
                $return['categories'] = $categories;
                $return['locations'] = $shop_locations;
                $return['vat_records'] = $vat_records;

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "data" => $return
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }

            

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'check-lock-status') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $right = mysqli_escape_string($conn, decrypt_data($request->right));

        if(check_device_mac($conn, $mac)) {

            // check location rights
            $rights = get_user_location_rights($conn, $user_id, $location_id);

            $stock_taking = check_on_going_stock_taking($conn, $location_id);

            if($stock_taking==false) {
            
                if(decrypt_data($rights[$right]) == '1') {

                    if(check_item_lock_status($conn, $item_id, $location_id, $user_id)) {

                        $location_qty = get_item_location_quantity($conn, $item_id, $location_id)['location_quantity'];

                        $data_insert=array(
                            "status" => "success",
                            "message" => "success",
                            "qty" => $location_qty,
                            "item_id" => $item_id,
                            "location_id" => $location_id,
                        );
                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    }

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "You dont have rights for this location!"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                    exit();
                }

            } else {

                $data_insert=array(
                    "status" => "error",
                    "message" => "Stock taking for that location is in progress!"
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

                exit();
                    
            }

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-item-locations') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        // $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $right = mysqli_escape_string($conn, decrypt_data($request->right));

        if(check_device_mac($conn, $mac)) {

            $user = get_user_record($conn, $user_id);

            // check location rights
            $locations = [];
            $cr=0;
            if(decrypt_data($user['role'])==='admin') {

                $sql = "SELECT * FROM stock_locations WHERE item_id='{$item_id}' AND deleted=0";
                $result = run_query($conn, $sql);

                while($row=mysqli_fetch_assoc($result)) {
                    $locations[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);
                    $locations[$cr]['quantity'] = $row['location_quantity'];
                    $cr++;
                }

            } else {
                $location_rights = get_all_user_locations_rights($conn, $user_id);
                if(count($location_rights) > 1) {
                    $sql = "SELECT * FROM stock_locations WHERE item_id='{$item_id}' AND deleted=0";
                    $result = run_query($conn, $sql);

                    while($row=mysqli_fetch_assoc($result)) {
                        $locations[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);
                        $locations[$cr]['quantity'] = $row['location_quantity'];
                        $cr++;
                    }

                } else {

                    $location_id = $location_rights[0]['id'];
                    
                    $sql = "SELECT * FROM stock_locations WHERE item_id='{$item_id}' AND location_id='{$location_id}' AND deleted=0";
                    $result = run_query($conn, $sql);

                    while($row=mysqli_fetch_assoc($result)) {
                        $locations[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);
                        $locations[$cr]['quantity'] = $row['location_quantity'];
                        $cr++;
                    }

                }
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "locations" => $locations
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'check-lock-status-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $right = mysqli_escape_string($conn, decrypt_data($request->right));

        if(check_device_mac($conn, $mac)) {

            // check location rights
            $rights = get_user_location_rights($conn, $user_id, $location_id);

            $stock_taking = check_on_going_stock_taking($conn, $location_id);

            if($stock_taking==false) {
            
                if(decrypt_data($rights[$right]) == '1') {

                    // if(check_item_lock_status($conn, $item_id, $location_id, $user_id)) {

                        $location_qty = get_item_location_quantity($conn, $item_id, $location_id)['location_quantity'];
                        $item_units = get_item_selling_units($conn, $item_id);

                        $data_insert=array(
                            "status" => "success",
                            "message" => "success",
                            "qty" => $location_qty,
                            "item_id" => $item_id,
                            "location_id" => $location_id,
                            "units" => $item_units
                        );
                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    // }

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "You dont have rights for this location!"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                    exit();
                }

            } else {

                $data_insert=array(
                    "status" => "error",
                    "message" => "Stock taking for that location is in progress!"
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

                exit();
                    
            }

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'check-edit-lock-status') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $right = mysqli_escape_string($conn, decrypt_data($request->right));

        if(check_device_mac($conn, $mac)) {

            // check location rights
            $rights = get_user_location_rights($conn, $user_id, $location_id);

            $stock_taking = check_on_going_stock_taking($conn, $location_id);

            if($stock_taking==false) {
            
                if(decrypt_data($rights[$right]) == '1') {

                    if(check_item_lock_status($conn, $item_id, $location_id, $user_id)) {

                        $location_qty = get_item_location_quantity($conn, $item_id, $location_id)['location_quantity'];
                        $selling_units = get_item_selling_units($conn, $item_id);

                        $item_locations = get_item_all_locations_quantity($conn, $item_id)['locations_quantity'];

                        $data_insert=array(
                            "status" => "success",
                            "message" => "success",
                            "qty" => $location_qty,
                            "units" => $selling_units,
                            "locations" => $item_locations
                        );
                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    }

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "You dont have rights for this location!"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                    exit();
                }

            } else {

                $data_insert=array(
                    "status" => "error",
                    "message" => "Stock taking for that location is in progress!"
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

                exit();
                    
            }

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'unlock-item') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->user_id));

        if(check_device_mac($conn, $mac)) {

            unlock_item($conn, $item_id, $location_id, $user_id);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-cart-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM temp_transactions WHERE sold_by='{$user_id}' ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $cart_items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                $item_id = $row['item_id'];
                $location_id = $row['location_id'];
                $unit_id = $row['unit_id'];

                $item = get_item_details($conn, $item_id);
                $location = get_shop_location_record($conn, $location_id);
                $unit = get_unit_record($conn, $unit_id);

                $unit_qty = $row['unit_quantity'];
                $unit_bp = $row['buying_price'];
                $min_sp = $row['min_selling_price'];
                $unit_sp = $row['selling_price'];

                $cart_items[$cr]['id'] = encrypt_data($row['id']);
                $cart_items[$cr]['item'] = $item;
                $cart_items[$cr]['location'] = $location;
                $cart_items[$cr]['unit'] = $unit;
                $cart_items[$cr]['qty'] = $unit_qty;
                $cart_items[$cr]['sp'] = $unit_sp;

                $cr++;

            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "cartItems" => $cart_items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-stock-and-cart-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            if(backupDatabase($conn)) {
         
                $items = get_stock_and_cart_items($conn, $user_id);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "stock" => $items['stock'],
                    "locations" => $items['locations'],
                    "categories" => $items['categories'],
                    "cartItems" => $items['cartItems'],
                    "waiting" => $items['waiting'],
                    "myCustomers" => $items['myCustomers'],
                    "print_list" => $items['print_list']
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }
            
        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-stock-and-cart-items-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {
            unlock_all_user_locked_items($conn, $user_id);

            // get the items
            $items = get_stock_and_cart_items_new($conn, $user_id);

            
            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "stock" => $items['stock'],
                "locations" => $items['shop_locations'],
                "cartItems" => $items['cart_items'],
                "waiting" => $items['waiting'],
                "duplicated" => $items['duplicated'],
                "myCustomers" => $items['my_customers'],
                "print_list" => $items['print_items']
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

        
    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-all-debtors') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $sql = "SELECT * FROM customers WHERE customer_type='debtor' AND deleted=0";
        $result = run_query($conn, $sql);

        $debtors = [];
        $cr=0;

        while($row = mysqli_fetch_assoc($result)) {
            $debtors[$cr]['id'] = encrypt_data($row['id']);
            $debtors[$cr]['name'] = encrypt_data($row['name']);
            $debtors[$cr]['sales_limit'] = encrypt_data($row['sales_limit']);
            $debtors[$cr]['mobile_number'] = encrypt_data($row['mobile_number']);
            $cr++;
        }

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "debtors" => $debtors
        );
        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-all-debtors-and-pending-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            if(backupDatabase($conn)) {
         
                $sql = "SELECT * FROM customers WHERE customer_type='debtor' AND deleted=0";
                $result = run_query($conn, $sql);

                $debtors = [];
                $cr=0;

                while($row = mysqli_fetch_assoc($result)) {
                    $debtors[$cr]['id'] = encrypt_data($row['id']);
                    $debtors[$cr]['name'] = $row['name'];
                    $debtors[$cr]['sales_limit'] = $row['sales_limit'];
                    $debtors[$cr]['mobile_number'] = $row['mobile_number'];
                    
                    // check any pending transactions
                    $temp_transactions = get_temp_debtor_transactions($conn, $user_id, $row['id']);
                    $debtors[$cr]['pending_transactions'] = $temp_transactions;

                    // check limit
                    $summary = get_debtor_summary($conn, $row['id']);
                    $balance = $summary['sales'] - $summary['credit_note'] - $summary['payments'];
                    
                    $debtors[$cr]['balance'] = $balance;

                    if($balance > $row['sales_limit']) {
                        $debtors[$cr]['limit_reached'] = 1;
                    } else {
                        $debtors[$cr]['limit_reached'] = 0;
                    }
                    

                    $cr++;
                }

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "debtors" => $debtors
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-stock-and-debtor-cart-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));

        if(check_device_mac($conn, $mac)) {

            $stock= get_all_stock_new($conn);

            // $categories = get_all_categories($conn);
            // $shop_locations = get_shop_locations($conn);
            // $vat_records = get_vat_records($conn);


            $return['stock'] = $stock['stock'];
            $return['categories'] = $stock['categories'];
            $return['locations'] = $stock['locations'];
            $return['vat_records'] = $stock['vat_records'];





            $sql = "SELECT * FROM temp_transactions WHERE sold_by='{$user_id}' AND customer_id='{$customer_id}' ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $cart_items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                $item_id = $row['item_id'];
                $location_id = $row['location_id'];
                $unit_id = $row['unit_id'];

                $item = get_item_details($conn, $item_id);
                $location = get_shop_location_record($conn, $location_id);
                $unit = get_unit_record($conn, $unit_id);

                $unit_qty = $row['unit_quantity'];
                $unit_bp = $row['buying_price'];
                $min_sp = $row['min_selling_price'];
                $unit_sp = $row['selling_price'];

                $cart_items[$cr]['id'] = encrypt_data($row['id']);
                $cart_items[$cr]['item'] = $item;
                $cart_items[$cr]['location'] = $location;
                $cart_items[$cr]['unit'] = $unit;
                $cart_items[$cr]['qty'] = $unit_qty;
                $cart_items[$cr]['sp'] = $unit_sp;

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "cartItems" => $cart_items,
                "data" => $return
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
            

        
        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-stock-and-debtor-cart-items-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));

        if(check_device_mac($conn, $mac)) {

            $items = get_stock_and_cart_items_new($conn, $user_id);

            $sql = "SELECT * FROM temp_transactions WHERE sold_by='{$user_id}' AND customer_id='{$customer_id}' ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $cart_items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                $item_id = $row['item_id'];
                $location_id = $row['location_id'];
                $unit_id = $row['unit_id'];

                $item = get_item_details($conn, $item_id);
                $location = get_shop_location_record($conn, $location_id);
                $unit = get_unit_record($conn, $unit_id);

                $unit_qty = $row['unit_quantity'];
                $unit_bp = $row['buying_price'];
                $min_sp = $row['min_selling_price'];
                $unit_sp = $row['selling_price'];

                $cart_items[$cr]['id'] = encrypt_data($row['id']);
                $cart_items[$cr]['item'] = $item;
                $cart_items[$cr]['location'] = $location;
                $cart_items[$cr]['unit'] = $unit;
                $cart_items[$cr]['qty'] = $unit_qty;
                $cart_items[$cr]['sp'] = $unit_sp;

                $cr++;

            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "stock" => $items['stock'],
                "locations" => $items['shop_locations'],
                "cartItems" => $cart_items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-all-transfer-customers-and-pending-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));


        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            if(backupDatabase($conn)) {
         
                $sql = "SELECT * FROM customers WHERE customer_type='transfer' AND deleted=0";
                $result = run_query($conn, $sql);

                $debtors = [];
                $cr=0;

                while($row = mysqli_fetch_assoc($result)) {
                    $debtors[$cr]['id'] = encrypt_data($row['id']);
                    $debtors[$cr]['name'] = $row['name'];
                    $debtors[$cr]['sales_limit'] = $row['sales_limit'];
                    $debtors[$cr]['mobile_number'] = $row['mobile_number'];
                    
                    // check any pending transactions
                    $temp_transactions = get_temp_debtor_transactions($conn, $user_id, $row['id']);
                    $debtors[$cr]['pending_transactions'] = $temp_transactions;

                    $cr++;
                }

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "debtors" => $debtors
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-stock-and-cart-items-inter-location') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            if(backupDatabase($conn)) {

                $items = get_stock_and_cart_items_inter_location($conn, $user_id);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "stock" => $items['stock'],
                    "locations" => $items['locations'],
                    "cartItems" => $items['cartItems'],
                    "categories" => $items['categories']
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }
        
        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-stock-and-cart-items-inter-location-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            if(backupDatabase($conn)) {

                $items = get_stock_and_cart_items_inter_location_new($conn, $user_id);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "stock" => $items['stock'],
                    "locations" => $items['locations'],
                    "cartItems" => $items['cartItems']
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }
        
        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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




if($action === 'get-current-user-cash-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = get_today();

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            if(backupDatabase($conn)) {
         
                $sql = "SELECT a.transaction_code, IFNULL(a.total, 0) AS total,
                            a.date_sold AS date_sold, IFNULL(b.customer_name, '') AS customer,
                            a.sales_payments_id AS payment_id,
                            IFNULL(b.cash, 0) AS cash, IFNULL(b.mpesa, 0) AS mpesa,
                            IFNULL(b.bank, 0) AS bank,
                            IFNULL(b.amount_reversed, 0) AS reversed,
                            b.payment_channel AS payment_channel
                        FROM
                        (SELECT SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code, sales_payments_id FROM sold_stock
                        WHERE sold_by='{$user_id}' GROUP BY transaction_code) AS a
                        INNER JOIN
                        (SELECT id, customer_name, cash, mpesa, bank, amount_reversed, payment_channel FROM sales_payments) AS b
                        ON a.sales_payments_id = b.id
                        ORDER BY a.transaction_id DESC";
                $result = run_query($conn, $sql);

                $transactions=[];
                $cr=0;

                while($row = mysqli_fetch_assoc($result)) {

                    $customer['id'] = encrypt_data('0');
                    $customer['name'] = $row['customer'];

                    $payment['id'] = encrypt_data($row['payment_id']);
                    $payment['payment_channel'] = $row['payment_channel'];
                    $payment['cash'] = $row['cash'];
                    $payment['mpesa'] = $row['mpesa'];
                    $payment['bank'] = $row['bank'];
                    $payment['reversed'] = $row['reversed'];

                    $total = $row['total'] - $row['reversed'];

                    $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                    $transactions[$cr]['transaction_total'] = $total;
                    $transactions[$cr]['customer'] = $customer;
                    $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                    $transactions[$cr]['items'] = [];
                    $transactions[$cr]['payment'] = $payment;

                    $cr++;

                }


                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "transactions" => $transactions
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-week-user-cash-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = get_today();

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            if(backupDatabase($conn)) {

                // $start_date = date ("Y-m-d", time());
                // $end_date = date ("Y-m-d", strtotime("-6 days", strtotime($start_date)));

            

                $sql = "SELECT a.transaction_code, IFNULL(a.total, 0) AS total,
                            a.date_sold AS date_sold, IFNULL(b.customer_name, '') AS customer,
                            a.sales_payments_id AS payment_id,
                            IFNULL(b.cash, 0) AS cash, IFNULL(b.mpesa, 0) AS mpesa,
                            IFNULL(b.bank, 0) AS bank,
                            IFNULL(b.amount_reversed, 0) AS reversed,
                            b.payment_channel AS payment_channel,
                            IFNULL(u.username, '') AS sold_by
                        FROM
                        (SELECT SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code, sales_payments_id, sold_by
                            FROM sold_stock GROUP BY transaction_code) AS a
                        LEFT JOIN
                        (SELECT id, customer_name, cash, mpesa, bank, amount_reversed, payment_channel FROM sales_payments) AS b
                        ON a.sales_payments_id = b.id

                        LEFT JOIN
                        (SELECT id, username FROM users) AS u
                        ON u.id=a.sold_by

                        ORDER BY a.transaction_id DESC";
                $result = run_query($conn, $sql);

                $transactions=[];
                $cr=0;

                while($row = mysqli_fetch_assoc($result)) {

                    $customer['id'] = encrypt_data('0');
                    $customer['name'] = $row['customer'];

                    $payment['id'] = encrypt_data($row['payment_id']);
                    $payment['payment_channel'] = $row['payment_channel'];
                    $payment['cash'] = $row['cash'];
                    $payment['mpesa'] = $row['mpesa'];
                    $payment['bank'] = $row['bank'];
                    $payment['reversed'] = $row['reversed'];

                    $total = $row['total'] - $row['reversed'];

                    $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                    $transactions[$cr]['transaction_total'] = $total;
                    $transactions[$cr]['customer'] = $customer;
                    $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                    $transactions[$cr]['items'] = [];
                    $transactions[$cr]['payment'] = $payment;
                    $transactions[$cr]['sold_by'] = $row['sold_by'];

                    $cr++;

                }


                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "transactions" => $transactions
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-cash-transaction-code-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $transaction_code = mysqli_escape_string($conn, decrypt_data($request->code));


        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM sold_stock WHERE transaction_code='{$transaction_code}' ORDER BY id DESC";
            
                    
            $result = run_query($conn, $sql);

            $cart_items=[];
            $cr=0;
            
            

            while($row = mysqli_fetch_assoc($result)) {

                $item_id = $row['item_id'];
                $location_id = $row['location_id'];
                $unit_id = $row['unit_id'];

                $item = get_item_details($conn, $item_id);
                // $item = null;
                // $item['id'] = encrypt_data($row['item_id']);
                // $item['name'] = utf8_encode($row['name']);
                
                // $item['name'] = mysqli_escape_string($conn, $item['name']);
                $item['selling_units'] = get_item_selling_units($conn, $item_id);
                
                

                $location = get_shop_location_record($conn, $location_id);
                $unit = get_unit_record($conn, $unit_id);

                $unit_qty = $row['unit_quantity'];
                $unit_bp = $row['buying_price'];
                $min_sp = $row['min_selling_price'];
                $recom_sp = $row['recom_selling_price'];
                $unit_sp = $row['selling_price'];

                // find any reversed pieces
                $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $transaction_code, $item_id, $row['id']);
               
               
                
                $cart_items[$cr]['id'] = encrypt_data($row['id']);
                $cart_items[$cr]['item'] = $item;
                $cart_items[$cr]['location'] = $location;
                $cart_items[$cr]['unit'] = $unit;
                $cart_items[$cr]['qty'] = $unit_qty;
                $cart_items[$cr]['pieces_reversed'] = $pieces_reversed;
                $cart_items[$cr]['sp'] = $unit_sp;
                $cart_items[$cr]['bp'] = $unit_bp;
                $cart_items[$cr]['min_sp'] = $min_sp;
                
                

                

                // calculate the discount
                $discount = 0;
                if($unit_sp < $recom_sp) {
                    $disc = $recom_sp - $unit_sp;
                    $discount = $disc * $unit_qty;
                }
                $cart_items[$cr]['discount'] = $discount;

                // calculate any overcharge
                $overcharge = 0;
                if($unit_sp > $recom_sp) {
                    $over = $unit_sp - $recom_sp;
                    $overcharge = $over * $unit_qty;
                }
                $cart_items[$cr]['overcharge'] = $overcharge;

                // calculate profit
                $profit=0;
                if($unit_sp > $unit_bp) {
                    $pro = $unit_sp - $unit_bp;
                    $profit = $pro * $unit_qty;
                }
                $cart_items[$cr]['profit'] = $profit;

                $cr++;


            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $cart_items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-current-user-cash-reversed-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = get_today();

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM sold_stock_reversed WHERE
                    AND sold_by='{$user_id}'
                    AND DATE_FORMAT(date_returned, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')
                    AND transaction_code IN (SELECT transaction_code FROM sales_payments WHERE payment_channel='cash')";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $transactions[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $transactions[$cr]['qty_returned'] =  $row['quantity_returned'];
                $transactions[$cr]['price'] =  $row['selling_price'];
                $transactions[$cr]['total'] =  $row['selling_price'] * $row['quantity_returned'];

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-current-user-debtor-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = get_today();

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            // $start_date = date ("Y-m-d", time());
            // $end_date = date ("Y-m-d", strtotime("-6 days", strtotime($start_date)));
         
            if(backupDatabase($conn)) {

                $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total,
                            a.date_sold AS date_sold, IFNULL(b.name, '') AS customer
                        FROM
                        (SELECT customer_id, SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code FROM sold_stock_debtors
                        WHERE sold_by='{$user_id}'
                        GROUP BY transaction_code) AS a
                        INNER JOIN
                        (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        ORDER BY a.transaction_id DESC";
                $result = run_query($conn, $sql);

                $transactions=[];
                $cr=0;

                while($row = mysqli_fetch_assoc($result)) {

                    $customer['id'] = encrypt_data($row['customer_id']);
                    $customer['name'] = $row['customer'];

                    $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                    $total = $row['total'] - $reversed_amount;

                    $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                    $transactions[$cr]['transaction_total'] = $total;
                    $transactions[$cr]['customer'] = $customer;
                    $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                    $transactions[$cr]['items'] = [];
                    $transactions[$cr]['reversed'] = $reversed_amount;

                    $cr++;

                }


                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "transactions" => $transactions
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-week-user-debtor-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = get_today();

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            if(backupDatabase($conn)) {

                $start_date = date ("Y-m-d", time());
                $end_date = date ("Y-m-d", strtotime("-6 days", strtotime($start_date)));
            

                $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total,
                            a.date_sold AS date_sold, IFNULL(b.name, '') AS customer, IFNULL(u.username, '') AS sold_by
                        FROM
                        (SELECT customer_id, SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code, sold_by
                            FROM sold_stock_debtors GROUP BY transaction_code) AS a
                        LEFT JOIN
                        (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id

                        LEFT JOIN
                        (SELECT id, username FROM users) AS u
                        ON u.id=a.sold_by

                        ORDER BY a.transaction_id DESC";
                $result = run_query($conn, $sql);

                $transactions=[];
                $cr=0;

                while($row = mysqli_fetch_assoc($result)) {

                    $customer['id'] = encrypt_data($row['customer_id']);
                    $customer['name'] = $row['customer'];

                    $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                    $total = $row['total'] - $reversed_amount;

                    $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                    $transactions[$cr]['transaction_total'] = $total;
                    $transactions[$cr]['customer'] = $customer;
                    $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                    $transactions[$cr]['items'] = [];
                    $transactions[$cr]['reversed'] = $reversed_amount;
                    $transactions[$cr]['sold_by'] = $row['sold_by'];

                    $cr++;

                }


                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "transactions" => $transactions
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-debtor-transaction-code-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $transaction_code = mysqli_escape_string($conn, decrypt_data($request->code));


        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM sold_stock_debtors WHERE transaction_code='{$transaction_code}' ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $cart_items=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $item_id = $row['item_id'];
                $location_id = $row['location_id'];
                $unit_id = $row['unit_id'];

                $item = get_item_details($conn, $item_id);
                $item['selling_units'] = get_item_selling_units($conn, $item_id);

                $location = get_shop_location_record($conn, $location_id);
                $unit = get_unit_record($conn, $unit_id);

                $unit_qty = $row['unit_quantity'];
                $unit_bp = $row['buying_price'];
                $min_sp = $row['min_selling_price'];
                $recom_sp = $row['recom_selling_price'];
                $unit_sp = $row['selling_price'];

                // find any reversed pieces
                $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $transaction_code, $item_id, $row['id']);
                
                $cart_items[$cr]['id'] = encrypt_data($row['id']);
                $cart_items[$cr]['item'] = $item;
                $cart_items[$cr]['location'] = $location;
                $cart_items[$cr]['unit'] = $unit;
                $cart_items[$cr]['qty'] = $unit_qty;
                $cart_items[$cr]['pieces_reversed'] = $pieces_reversed;
                $cart_items[$cr]['sp'] = $unit_sp;
                $cart_items[$cr]['bp'] = $unit_bp;
                $cart_items[$cr]['min_sp'] = $min_sp;

                

                // calculate the discount
                $discount = 0;
                if($unit_sp < $recom_sp) {
                    $disc = $recom_sp - $unit_sp;
                    $discount = $disc * $unit_qty;
                }
                $cart_items[$cr]['discount'] = $discount;

                // calculate any overcharge
                $overcharge = 0;
                if($unit_sp > $recom_sp) {
                    $over = $unit_sp - $recom_sp;
                    $overcharge = $over * $unit_qty;
                }
                $cart_items[$cr]['overcharge'] = $overcharge;

                // calculate profit
                $profit=0;
                if($unit_sp > $unit_bp) {
                    $pro = $unit_sp - $unit_bp;
                    $profit = $pro * $unit_qty;
                }
                $cart_items[$cr]['profit'] = $profit;

                $cr++;


            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $cart_items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-current-user-transfer-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = get_today();

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            if(backupDatabase($conn)) {

                $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total, a.date_transfered AS date_sold, IFNULL(b.name, '') AS customer
                        FROM
                        (SELECT customer_id, SUM(unit_quantity*buying_price) AS total, date_transfered, transaction_id, transaction_code FROM sold_stock_transfers
                            WHERE sold_by='{$user_id}'
                            GROUP BY transaction_code) AS a
                        INNER JOIN (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        ORDER BY a.transaction_id DESC";
                $result = run_query($conn, $sql);

                $transactions=[];
                $cr=0;

                while($row = mysqli_fetch_assoc($result)) {

                    $customer['id'] = encrypt_data($row['customer_id']);
                    $customer['name'] = $row['customer'];

                    $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                    $total = $row['total'] - $reversed_amount;

                    $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                    $transactions[$cr]['transaction_total'] = $total;
                    $transactions[$cr]['customer'] = $customer;
                    $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                    $transactions[$cr]['items'] = [];
                    $transactions[$cr]['reversed'] = $reversed_amount;

                    $cr++;

                }


                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "transactions" => $transactions
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-week-user-transfer-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = get_today();

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            $start_date = date ("Y-m-d", time());
            $end_date = date ("Y-m-d", strtotime("-6 days", strtotime($start_date)));

            $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total, a.date_transfered AS date_sold, IFNULL(b.name, '') AS customer
                    FROM
                    (SELECT customer_id, SUM(unit_quantity*buying_price) AS total, date_transfered, transaction_id, transaction_code FROM sold_stock_transfers WHERE sold_by='{$user_id}'
                    AND
                    DATE_FORMAT(date_transfered, '%Y-%m-%d')<=DATE_FORMAT('{$start_date}', '%Y-%m-%d') AND
                    DATE_FORMAT(date_transfered, '%Y-%m-%d')>=DATE_FORMAT('{$end_date}', '%Y-%m-%d')
                    GROUP BY transaction_code) AS a
                    INNER JOIN (SELECT id, name FROM customers) AS b
                    ON a.customer_id = b.id
                    ORDER BY a.transaction_id DESC";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $customer['id'] = encrypt_data($row['customer_id']);
                $customer['name'] = $row['customer'];

                $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                $total = $row['total'] - $reversed_amount;

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['customer'] = $customer;
                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['items'] = [];
                $transactions[$cr]['reversed'] = $reversed_amount;

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-transfer-transaction-code-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $transaction_code = mysqli_escape_string($conn, decrypt_data($request->code));


        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM sold_stock_transfers WHERE transaction_code='{$transaction_code}' ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $cart_items=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $item_id = $row['item_id'];
                $location_id = $row['location_id'];
                $unit_id = $row['unit_id'];

                $item = get_item_details($conn, $item_id);
                $item['selling_units'] = get_item_selling_units($conn, $item_id);

                $location = get_shop_location_record($conn, $location_id);
                $unit = get_unit_record($conn, $unit_id);

                $unit_qty = $row['unit_quantity'];
                $unit_bp = $row['buying_price'];
                $min_sp = $row['buying_price'];
                $recom_sp = $row['buying_price'];
                $unit_sp = $row['buying_price'];

                // find any reversed pieces
                $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $transaction_code, $item_id, $row['id']);
                
                $cart_items[$cr]['id'] = encrypt_data($row['id']);
                $cart_items[$cr]['item'] = $item;
                $cart_items[$cr]['location'] = $location;
                $cart_items[$cr]['unit'] = $unit;
                $cart_items[$cr]['qty'] = $unit_qty;
                $cart_items[$cr]['pieces_reversed'] = $pieces_reversed;
                $cart_items[$cr]['sp'] = $unit_sp;
                $cart_items[$cr]['bp'] = $unit_bp;
                $cart_items[$cr]['min_sp'] = $min_sp;

                

                // calculate the discount
                $discount = 0;
                $cart_items[$cr]['discount'] = $discount;

                // calculate any overcharge
                $overcharge = 0;
                $cart_items[$cr]['overcharge'] = $overcharge;

                // calculate profit
                $profit=0;
                $cart_items[$cr]['profit'] = $profit;

                $cr++;


            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $cart_items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-user-today-expenses') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        
        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            if(backupDatabase($conn)) {
            
                $today = get_today();

                $user_name = get_user_record($conn, $user_id)['name'];

                $sql = "SELECT * FROM expenses WHERE added_by='{$user_id}' AND DATE_FORMAT(date_created, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')";
                $result = run_query($conn, $sql);

                $expenses = [];
                $cr=0;

                while($row=mysqli_fetch_assoc($result)) {
                    $expenses[$cr]['id'] = encrypt_data($row['id']);
                    $expenses[$cr]['purpose'] = $row['purpose'];
                    $expenses[$cr]['paid_to'] = $row['paid_to'];
                    $expenses[$cr]['channel'] = $row['payment_channel'];
                    $expenses[$cr]['amount'] = $row['amount'];
                    $expenses[$cr]['date_created'] = $row['date_created'];
                    $expenses[$cr]['date_incurred'] = $row['date_incurred'];
                    $expenses[$cr]['added_by'] = $user_name;

                    $cr++;
                }

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "expenses" => $expenses
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }
        }



    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-user-today-floats') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $user_name = get_user_record($conn, $user_id)['name'];

            $sql = "SELECT * FROM opening_float WHERE added_by='{$user_id}' AND DATE_FORMAT(date_created, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')";
            $result = run_query($conn, $sql);

            $float = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $float[$cr]['id'] = encrypt_data($row['id']);
                $float[$cr]['amount'] = $row['float_amount'];
                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "float" => $float
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-dates-with-any-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));

        if(check_device_mac($conn, $mac)) {
            
            $sql = "SELECT date_sold AS date_sold
                    FROM (
                        SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(date_transfered, '%Y-%m-%d') AS date_sold FROM sold_stock_transfers GROUP BY DATE_FORMAT(date_transfered, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock_debtors GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(date_incurred, '%Y-%m-%d') AS date_sold FROM expenses GROUP BY DATE_FORMAT(date_incurred, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(date_created, '%Y-%m-%d') AS date_sold FROM supplier_invoices GROUP BY DATE_FORMAT(date_created, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(date_added, '%Y-%m-%d') AS date_sold FROM stock_positive GROUP BY DATE_FORMAT(date_added, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(date_removed, '%Y-%m-%d') AS date_sold FROM stock_negative GROUP BY DATE_FORMAT(date_removed, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(date_returned, '%Y-%m-%d') AS date_sold FROM stock_returns GROUP BY DATE_FORMAT(date_returned, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(date_of_payment, '%Y-%m-%d') AS date_sold FROM debtors_payments GROUP BY DATE_FORMAT(date_of_payment, '%Y-%m-%d')
                        UNION
                        SELECT DATE_FORMAT(date_paid, '%Y-%m-%d') AS date_sold FROM transfer_payments GROUP BY DATE_FORMAT(date_paid, '%Y-%m-%d')
                    ) AS dates ORDER BY date_sold DESC";
            $result = run_query($conn, $sql);

            $dates=[];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                $dates[$cr]['date_sold'] = $row['date_sold'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "dates" => $dates
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-date-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        $today = $date;

        if(check_device_mac($conn, $mac)) {

            // get gross sales, gross profit, expenses,
            // debtors, transfers, cash, bank, mpesa,
            // reversed amounts
            // opening float per user
            $sql = "SELECT a.id, a.username,
                    IFNULL(cash_sales.gross_sales, 0) AS cash_sales,
                    IFNULL(cash_sales.gross_profit, 0) AS cash_profit,
                    IFNULL(reversed_cash.sales_reversed, 0) AS cash_reversed,
                    IFNULL(reversed_cash.reversed_profit, 0) AS reversed_cash_profit,
                    
                    IFNULL(debtor_sales.gross_sales, 0) AS debtor_sales,
                    IFNULL(debtor_sales.gross_profit, 0) AS debtor_profit,
                    IFNULL(reversed_debtor.sales_reversed, 0) AS debtor_reversed,
                    IFNULL(reversed_debtor.reversed_profit, 0) AS reversed_debtor_profit,
                    
                    IFNULL(transfer_sales.gross_sales, 0) AS transfer_sales,
                    IFNULL(reversed_transfer.sales_reversed, 0) AS transfer_reversed,
                    
                    IFNULL(expenses.expense, 0) AS expenses,
                    IFNULL(float_amount.cash_float, 0) AS cash_float,
                    
                    IFNULL(banked_amount.cash, 0) AS cash_banked,
                    IFNULL(banked_amount.mpesa, 0) AS mpesa_banked,
                    IFNULL(banked_amount.bank, 0) AS bank_banked,
                    
                    IFNULL(banked_reversed_cash_only.reversed_cash_amount, 0) AS reversed_cash_only,
                    IFNULL(banked_reversed_mpesa_only.reversed_mpesa_amount, 0) AS reversed_mpesa_only,
                    IFNULL(banked_reversed_bank_only.reversed_bank_amount, 0) AS reversed_bank_only,
                    IFNULL(banked_reversed_multiple.reversed_multiple, 0) AS reversed_multiple,

                    IFNULL(cash_discounts.discounts, 0) AS cash_discounts,
                    IFNULL(debtor_discounts.discounts, 0) AS debtor_discounts,

                    IFNULL(cash_overcharges.overcharges, 0) AS cash_overcharges,
                    IFNULL(debtor_overcharges.overcharges, 0) AS debtor_overcharges
                    
                    FROM 
                    
                    (SELECT id, username FROM users WHERE deleted=0) AS a
                    
                    LEFT JOIN
                    (SELECT sold_by, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit FROM sold_stock WHERE DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS cash_sales
                    ON cash_sales.sold_by=a.id
                    
                    LEFT JOIN
                    (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit FROM sold_stock_reversed WHERE DATE_FORMAT(date_returned, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') AND sale_type='cash' GROUP BY sold_by) AS reversed_cash
                    ON reversed_cash.sold_by=a.id
                    
                    LEFT JOIN
                    (SELECT sold_by, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit FROM sold_stock_debtors WHERE DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS debtor_sales
                    ON debtor_sales.sold_by=a.id
                    
                    LEFT JOIN
                    (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit FROM sold_stock_reversed WHERE DATE_FORMAT(date_returned, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') AND sale_type='debtor' GROUP BY sold_by) AS reversed_debtor
                    ON reversed_debtor.sold_by=a.id
                    
                    LEFT JOIN
                    (SELECT sold_by, SUM(unit_quantity * buying_price) AS gross_sales FROM sold_stock_transfers WHERE DATE_FORMAT(date_transfered, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS transfer_sales
                    ON transfer_sales.sold_by=a.id
                    
                    LEFT JOIN
                    (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed FROM sold_stock_reversed WHERE DATE_FORMAT(date_returned, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') AND sale_type='transfer' GROUP BY sold_by) AS reversed_transfer
                    ON reversed_transfer.sold_by=a.id
                    
                    LEFT JOIN
                    (SELECT added_by, SUM(amount) AS expense FROM expenses WHERE DATE_FORMAT(date_incurred, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY added_by) AS expenses
                    ON expenses.added_by=a.id
                    
                    LEFT JOIN
                    (SELECT added_by, SUM(float_amount) AS cash_float FROM opening_float WHERE DATE_FORMAT(date_created, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY added_by) AS float_amount
                    ON float_amount.added_by=a.id
                    
                    LEFT JOIN
                    (SELECT sold_by, SUM(cash) AS cash, SUM(mpesa) AS mpesa, SUM(bank) AS bank FROM sales_payments WHERE DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS banked_amount
                    ON banked_amount.sold_by=a.id
                    
                    LEFT JOIN
                    (SELECT sold_by, SUM(amount_reversed) AS reversed_cash_amount FROM sales_payments WHERE cash > 0 AND mpesa < 1 AND bank < 1 AND DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS banked_reversed_cash_only
                    ON banked_reversed_cash_only.sold_by=a.id
                    
                    LEFT JOIN
                    (SELECT sold_by, SUM(amount_reversed) AS reversed_mpesa_amount FROM sales_payments WHERE cash < 1 AND mpesa > 0 AND bank < 1 AND DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS banked_reversed_mpesa_only
                    ON banked_reversed_mpesa_only.sold_by=a.id
                    
                    LEFT JOIN
                    (SELECT sold_by, SUM(amount_reversed) AS reversed_bank_amount FROM sales_payments WHERE cash < 1 AND mpesa < 1 AND bank > 0 AND DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS banked_reversed_bank_only
                    ON banked_reversed_bank_only.sold_by=a.id
                    
                    LEFT JOIN
                    (SELECT sold_by, SUM(amount_reversed) AS reversed_multiple FROM sales_payments WHERE DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') AND ((cash > 0 AND mpesa > 0) OR (cash>0 AND bank>0) OR (mpesa>0 AND bank > 0)) GROUP BY sold_by) AS banked_reversed_multiple
                    ON banked_reversed_multiple.sold_by=a.id

                    LEFT JOIN
                    (SELECT sold_by, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock WHERE selling_price<recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS cash_discounts
                    ON cash_discounts.sold_by=a.id

                    LEFT JOIN
                    (SELECT sold_by, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock_debtors WHERE selling_price<recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS debtor_discounts
                    ON debtor_discounts.sold_by=a.id

                    LEFT JOIN
                    (SELECT sold_by, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock WHERE selling_price>recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS cash_overcharges
                    ON cash_overcharges.sold_by=a.id

                    LEFT JOIN
                    (SELECT sold_by, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock_debtors WHERE selling_price>recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS debtor_overcharges
                    ON debtor_overcharges.sold_by=a.id
                    
                    
                    GROUP BY a.id";

            $result = run_query($conn, $sql);

            $sales=[];
            $cr=0;


            $dateGrossSales = 0;
            $dateExpenses = 0;
            $dateGrossProfit = 0;


            while($row = mysqli_fetch_assoc($result)) {
                
                // $user = get_user_record($row['sold_by']);

                $sales[$cr]['user_id'] = encrypt_data($row['id']);
                $sales[$cr]['user_name'] = $row['username'];


                $cash_sales = $row['cash_sales'];
                $debtor_sales = $row['debtor_sales'];
                $transfer_sales = $row['transfer_sales'];

                $cash_sales_reversed = $row['cash_reversed'];
                $debtor_sales_reversed = $row['debtor_reversed'];
                $transfer_sales_reversed = $row['transfer_reversed'];

                $sales[$cr]['debtor_reversed'] = $debtor_sales_reversed;
                $sales[$cr]['transfer_reversed'] = $transfer_sales_reversed;

                $gross_sales = $cash_sales + $debtor_sales - $cash_sales_reversed - $debtor_sales_reversed;
                $gross_transfer_sales = $transfer_sales - $transfer_sales_reversed;

                $sales[$cr]['gross_sales'] = $gross_sales;
                $sales[$cr]['transfer_sales'] = $gross_transfer_sales;
                $sales[$cr]['debtor_sales'] = $debtor_sales;
                $sales[$cr]['expenses'] = $row['expenses'];


                $cash_profit = $row['cash_profit'];
                $reversed_cash_profit = $row['reversed_cash_profit'];
                $debtor_profit = $row['debtor_profit'];
                $reversed_debtor_profit = $row['reversed_debtor_profit'];

                $gross_profit = ($cash_profit - $reversed_cash_profit) + ($debtor_profit - $reversed_debtor_profit);
                $sales[$cr]['gross_profit'] = $gross_profit;


                $cash_banked = $row['cash_banked'];
                $reversed_banked_cash = $row['reversed_cash_only'];
                $sales[$cr]['cash_banked'] = $cash_banked;
                $sales[$cr]['cash_reversed'] = $reversed_banked_cash;

                $float = $row['cash_float'];
                $sales[$cr]['float'] = $float;

                $mpesa_banked = $row['mpesa_banked'];
                $reversed_banked_mpesa = $row['reversed_mpesa_only'];
                $sales[$cr]['mpesa_banked'] = $mpesa_banked;
                $sales[$cr]['mpesa_reversed'] = $reversed_banked_mpesa;

                $bank_banked = $row['bank_banked'];
                $reversed_banked_bank = $row['reversed_bank_only'];
                $sales[$cr]['bank_banked'] = $bank_banked;
                $sales[$cr]['bank_reversed'] = $reversed_banked_bank;


                $sales[$cr]['reversed_multiple'] = $row['reversed_multiple'];


                // get any added stock for this user
                $user_added_stock = get_date_user_added_stock($conn, $row['id'], $today);
                
                $sales[$cr]['added_stock'] = $user_added_stock;

                // get any paid debtors
                $user_paid_debtors = get_date_user_paid_debtors($conn, $user_id, $today);

                $sales[$cr]['paid_debtors'] = $user_paid_debtors;

                $sales[$cr]['cash_discounts'] = $row['cash_discounts'];
                $sales[$cr]['debtor_discounts'] = $row['debtor_discounts'];
                
                $sales[$cr]['cash_overcharges'] = $row['cash_overcharges'];
                $sales[$cr]['debtor_overcharges'] = $row['debtor_overcharges'];

                $cr++;


                $dateGrossSales += $gross_sales;
                $dateExpenses += $row['expenses'];
                $dateGrossProfit += $gross_profit;

            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "sales" => $dateGrossSales,
                "expenses" => $dateExpenses,
                "grossProfit" => $dateGrossProfit,
                "userSales" => $sales
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get_user_date_cash_transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        $today = $date;

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT a.transaction_code, IFNULL(a.total, 0) AS total,
                        a.date_sold AS date_sold, IFNULL(b.customer_name, '') AS customer,
                        a.sales_payments_id AS payment_id,
                        IFNULL(b.cash, 0) AS cash, IFNULL(b.mpesa, 0) AS mpesa,
                        IFNULL(b.bank, 0) AS bank,
                        IFNULL(b.amount_reversed, 0) AS reversed,
                        b.payment_channel AS payment_channel
                    FROM
                    (SELECT SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code, sales_payments_id FROM sold_stock WHERE sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY transaction_code) AS a
                    INNER JOIN
                    (SELECT id, customer_name, cash, mpesa, bank, amount_reversed, payment_channel FROM sales_payments WHERE DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')) AS b
                    ON a.sales_payments_id = b.id
                    ORDER BY a.transaction_id DESC";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $customer['id'] = encrypt_data('0');
                $customer['name'] = $row['customer'];

                $payment['id'] = encrypt_data($row['payment_id']);
                $payment['payment_channel'] = $row['payment_channel'];
                $payment['cash'] = $row['cash'];
                $payment['mpesa'] = $row['mpesa'];
                $payment['bank'] = $row['bank'];
                $payment['reversed'] = $row['reversed'];

                $total = $row['total'] - $row['reversed'];

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['customer'] = $customer;
                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['items'] = [];
                $transactions[$cr]['payment'] = $payment;

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_user_date_sale_type_reversed_transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));
        $type = mysqli_escape_string($conn, decrypt_data($request->type));
        
        $today = $date;

        if(check_device_mac($conn, $mac)) {

            $table = ($type==='cash' || $type==='bank' || $type==='mpesa') ? 'sold_stock' : '';

            $sql = "SELECT * FROM sold_stock_reversed WHERE
                    sold_by='{$user_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')
                    AND transaction_code IN (SELECT transaction_code FROM sales_payments WHERE payment_channel='{$type}')";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $transactions[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $transactions[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);
                $transactions[$cr]['qty_returned'] =  $row['quantity_returned'];
                $transactions[$cr]['selling_price'] =  $row['selling_price'];
                $transactions[$cr]['total'] =  $row['selling_price'] * $row['quantity_returned'];

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_user_date_debtors_transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        $today = $date;

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total,
                        a.date_sold AS date_sold, IFNULL(b.name, '') AS customer
                    FROM
                    (SELECT customer_id, SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code FROM sold_stock_debtors WHERE sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY transaction_code) AS a
                    INNER JOIN
                    (SELECT id, name FROM customers) AS b
                    ON a.customer_id = b.id
                    ORDER BY a.transaction_id DESC";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $customer['id'] = encrypt_data($row['customer_id']);
                $customer['name'] = $row['customer'];

                $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                $total = $row['total'] - $reversed_amount;

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['customer'] = $customer;
                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['items'] = [];
                $transactions[$cr]['reversed'] = $reversed_amount;

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_user_date_transfers_transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        $today = $date;

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total, a.date_transfered AS date_sold, IFNULL(b.name, '') AS customer
                    FROM
                    (SELECT customer_id, SUM(unit_quantity*buying_price) AS total, date_transfered, transaction_id, transaction_code FROM sold_stock_transfers WHERE sold_by='{$user_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY transaction_code) AS a
                    INNER JOIN (SELECT id, name FROM customers) AS b
                    ON a.customer_id = b.id
                    ORDER BY a.transaction_id DESC";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $customer['id'] = encrypt_data($row['customer_id']);
                $customer['name'] = $row['customer'];

                $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                $total = $row['total'] - $reversed_amount;

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['customer'] = $customer;
                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['items'] = [];
                $transactions[$cr]['reversed'] = $reversed_amount;

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-user-date-expenses') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        
        if(check_device_mac($conn, $mac)) {

            $today = $date;

            $user_name = get_user_record($conn, $user_id)['name'];

            $sql = "SELECT * FROM expenses WHERE added_by='{$user_id}' AND DATE_FORMAT(date_created, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')";
            $result = run_query($conn, $sql);

            $expenses = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $expenses[$cr]['id'] = encrypt_data($row['id']);
                $expenses[$cr]['purpose'] = $row['purpose'];
                $expenses[$cr]['paid_to'] = $row['paid_to'];
                $expenses[$cr]['channel'] = $row['payment_channel'];
                $expenses[$cr]['amount'] = $row['amount'];
                $expenses[$cr]['date_created'] = $row['date_created'];
                $expenses[$cr]['date_incurred'] = $row['date_incurred'];
                $expenses[$cr]['added_by'] = $user_name;

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "expenses" => $expenses
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-user-date-cash-discount-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        
        if(check_device_mac($conn, $mac)) {

            $today = $date;

            $user_name = get_user_record($conn, $user_id)['name'];

            $sql = "SELECT * FROM sold_stock WHERE selling_price<recom_selling_price AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $items[$cr]['customer'] = get_payment_record($conn, $row['sales_payments_id'])['customer_name'];

                $items[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $items[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $items[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $items[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);

                
                $items[$cr]['unit_qty'] = $row['unit_quantity'];
                $items[$cr]['recom_selling_price'] = $row['recom_selling_price'];
                $items[$cr]['selling_price'] = $row['selling_price'];
                $items[$cr]['discount'] = ($row['recom_selling_price'] - $row['selling_price']) * $row['unit_quantity'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-user-date-debtor-discount-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));


        if(check_device_mac($conn, $mac)) {

            $today = $date;

            $user_name = get_user_record($conn, $user_id)['name'];

            $sql = "SELECT * FROM sold_stock_debtors WHERE selling_price<recom_selling_price AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $items[$cr]['customer'] = get_debtor_record($conn, $row['customer_id'])['name'];

                $items[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $items[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $items[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $items[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);

                
                $items[$cr]['unit_qty'] = $row['unit_quantity'];
                $items[$cr]['recom_selling_price'] = $row['recom_selling_price'];
                $items[$cr]['selling_price'] = $row['selling_price'];
                $items[$cr]['discount'] = ($row['recom_selling_price'] - $row['selling_price']) * $row['unit_quantity'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-user-date-cash-overcharged-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        
        if(check_device_mac($conn, $mac)) {

            $today = $date;

            $user_name = get_user_record($conn, $user_id)['name'];

            $sql = "SELECT * FROM sold_stock WHERE selling_price>recom_selling_price AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $items[$cr]['customer'] = get_payment_record($conn, $row['sales_payments_id'])['customer_name'];

                $items[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $items[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $items[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $items[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);

                
                $items[$cr]['unit_qty'] = $row['unit_quantity'];
                $items[$cr]['recom_selling_price'] = $row['recom_selling_price'];
                $items[$cr]['selling_price'] = $row['selling_price'];
                $items[$cr]['overcharge'] = ($row['selling_price'] - $row['recom_selling_price']) * $row['unit_quantity'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-user-date-debtor-overcharged-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        
        if(check_device_mac($conn, $mac)) {

            $today = $date;

            $user_name = get_user_record($conn, $user_id)['name'];

            $sql = "SELECT * FROM sold_stock_debtors WHERE selling_price>recom_selling_price AND sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $items[$cr]['customer'] = get_debtor_record($conn, $row['customer_id'])['name'];

                $items[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $items[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $items[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $items[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);

                
                $items[$cr]['unit_qty'] = $row['unit_quantity'];
                $items[$cr]['recom_selling_price'] = $row['recom_selling_price'];
                $items[$cr]['selling_price'] = $row['selling_price'];
                $items[$cr]['overcharge'] = ($row['selling_price'] - $row['recom_selling_price']) * $row['unit_quantity'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-all-suppliers-summary') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));
        
        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {
            
            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {
            
                $suppliers=[];
                $cr=0;

                $sql = "SELECT suppliers.id AS id, suppliers.supplier_name AS supplier_name, IFNULL(SUM(invoice_items.unit_quantity*invoice_items.unit_buying_price), 0) AS total_bp, IFNULL(payments.amount_paid, 0) AS payments
                        FROM
                        (SELECT id, supplier_name FROM suppliers WHERE deleted=0) AS suppliers
                        LEFT JOIN
                        (SELECT id, supplier_id FROM supplier_invoices) AS invoices
                        ON invoices.supplier_id=suppliers.id
                        LEFT JOIN
                        (SELECT invoice_id, new_buying_price, unit_quantity, unit_buying_price, unit_id FROM supplier_invoice_items) AS invoice_items
                        ON invoice_items.invoice_id = invoices.id
                        LEFT JOIN
                        (SELECT id, unit_quantity FROM stock_units) AS stock_units
                        ON stock_units.id=invoice_items.unit_id
                        LEFT JOIN
                        (SELECT supplier_id, SUM(amount_paid) AS amount_paid FROM supplier_payments GROUP BY supplier_id) AS payments
                        ON payments.supplier_id=suppliers.id
                        GROUP BY suppliers.id";

                $result = run_query($conn, $sql);

                while($row=mysqli_fetch_assoc($result)) {
                    $suppliers[$cr]['id'] = encrypt_data($row['id']);
                    $suppliers[$cr]['supplier_name'] = $row['supplier_name'];
                    $suppliers[$cr]['total_supplied'] = $row['total_bp'];
                    $suppliers[$cr]['total_paid'] = $row['payments'];
                    $cr++;
                }


                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "suppliers" => $suppliers
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }
            
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-all-invoices') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));

        if(check_device_mac($conn, $mac)) {

            $invoices=[];
            $cr=0;

            $sql = "SELECT suppliers.supplier_name, invoices.id, invoices.invoice_number, invoices.approved,
                    IFNULL(users.username, '') AS added_by,
                    IFNULL(SUM(invoice_items.unit_quantity*stock_units.unit_quantity*invoice_items.new_buying_price), 0) AS total_bp,
                    IFNULL(SUM(invoice_items.unit_quantity*stock_units.unit_quantity*invoice_items.new_buying_price) - (SUM(invoice_items.unit_quantity*stock_units.unit_quantity*invoice_items.new_buying_price) / ((vat.vat_percent/100) + 1)), 0) AS vat_amount,
                    invoices.date_created,
                    invoices.date_approved
                    FROM
                    
                    (SELECT id, supplier_id, invoice_number, user_id, date_created, date_approved, approved FROM supplier_invoices WHERE id IN (SELECT invoice_id FROM supplier_invoice_items)) AS invoices
                    
                    LEFT JOIN
                    (SELECT id, supplier_name FROM suppliers) AS suppliers
                    ON suppliers.id=invoices.supplier_id
                    
                    LEFT JOIN
                    (SELECT invoice_id, new_buying_price, unit_quantity, unit_id, vat_id FROM `supplier_invoice_items`) AS invoice_items
                    ON invoice_items.invoice_id = invoices.id
                    
                    LEFT JOIN
                    (SELECT id, unit_quantity FROM stock_units) AS stock_units
                    ON stock_units.id=invoice_items.unit_id
                    
                    LEFT JOIN
                    (SELECT id, username FROM users) AS users
                    ON users.id=invoices.user_id

                    LEFT JOIN
                    (SELECT id, vat_percent FROM vat_records) AS vat
                    ON vat.id=invoice_items.vat_id
                    
                    GROUP BY invoices.id
                    ORDER BY invoices.id DESC";

            $result = run_query($conn, $sql);

            while($row=mysqli_fetch_assoc($result)) {
                $invoices[$cr]['id'] = encrypt_data($row['id']);
                $invoices[$cr]['supplier_name'] = $row['supplier_name'];
                $invoices[$cr]['invoice_no'] = $row['invoice_number'];
                $invoices[$cr]['invoice_total'] = $row['total_bp'];
                $invoices[$cr]['vat_total'] = $row['vat_amount'];
                $invoices[$cr]['approved'] = $row['approved'];
                $invoices[$cr]['date_created'] = $row['date_created'];
                $invoices[$cr]['date_approved'] = $row['date_approved'];
                $invoices[$cr]['added_by'] = $row['added_by'];
                $cr++;
            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "invoices" => $invoices
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
            
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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




if($action === 'get-supplier-invoices') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));
        
        if(check_device_mac($conn, $mac)) {

            $invoices=[];
            $cr=0;

            $sql = "SELECT invoices.id, invoices.invoice_number, IFNULL(users.username, '') AS added_by,
                    IFNULL(SUM(invoice_items.unit_quantity*invoice_items.unit_buying_price), 0) AS total_bp,
                    invoices.date_created, invoices.date_approved,
                    SUM(invoice_items.total_quantity_added*new_buying_price - (invoice_items.total_quantity_added*new_buying_price/((v.vat_percent/100+1)))) AS vat_total
                    FROM
                    
                    (SELECT id, invoice_number, user_id, date_created, date_approved FROM supplier_invoices WHERE approved=1
                        AND supplier_id='{$supplier_id}' AND id IN (SELECT invoice_id FROM supplier_invoice_items)) AS invoices
                    LEFT JOIN
                    (SELECT invoice_id, new_buying_price, unit_quantity, unit_buying_price, unit_id, total_quantity_added, vat_id FROM `supplier_invoice_items`) AS invoice_items
                    ON invoice_items.invoice_id = invoices.id
                    
                    LEFT JOIN
                    (SELECT id, unit_quantity FROM stock_units) AS stock_units
                    ON stock_units.id=invoice_items.unit_id
                    
                    LEFT JOIN
                    (SELECT id, username FROM users) AS users
                    ON users.id=invoices.user_id

                    LEFT JOIN
                    (SELECT id, vat_percent FROM vat_records) as v
                    ON v.id=invoice_items.vat_id
                    
                    GROUP BY invoices.id
                    ORDER BY invoices.id DESC";

            $result = run_query($conn, $sql);

            while($row=mysqli_fetch_assoc($result)) {
                $invoices[$cr]['id'] = encrypt_data($row['id']);
                $invoices[$cr]['invoice_no'] = $row['invoice_number'];
                $invoices[$cr]['invoice_total'] = $row['total_bp'];
                $invoices[$cr]['vat_total'] = $row['vat_total'];
                $invoices[$cr]['date_created'] = $row['date_created'];
                $invoices[$cr]['date_approved'] = $row['date_approved'];
                $invoices[$cr]['added_by'] = $row['added_by'];
                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "invoices" => $invoices
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-invoice-items') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $invoice_id = mysqli_escape_string($conn, decrypt_data($request->invoice_id));
        
        if(check_device_mac($conn, $mac)) {

            $items = get_invoice_items($conn, $invoice_id);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
            
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-supplier-payments') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));
        
        if(check_device_mac($conn, $mac)) {

            $payments=[];
            $cr=0;

            $sql = "SELECT * FROM supplier_payments WHERE supplier_id='{$supplier_id}' ORDER BY id DESC";

            $result = run_query($conn, $sql);

            while($row=mysqli_fetch_assoc($result)) {
                $payments[$cr]['id'] = encrypt_data($row['id']);
                $payments[$cr]['amount'] = $row['amount_paid'];
                $payments[$cr]['payment_channel'] = $row['payment_channel'];
                $payments[$cr]['date_paid'] = $row['date_paid'];
                $payments[$cr]['paid_by'] = $row['paid_by'];
                $payments[$cr]['paid_to'] = $row['paid_to'];
                $payments[$cr]['added_by'] = get_user_record($conn, $row['user_id'])['name'];

                $cr++;
            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "payments" => $payments
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
            
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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




if($action === 'get-supplier-returns') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));

        if(check_device_mac($conn, $mac)) {

            $returns=[];
            $cr=0;

            $sql = "SELECT * FROM stock_returns WHERE supplier_id='{$supplier_id}' AND transaction_code!='' GROUP BY DATE_FORMAT(date_returned, '%Y-%m-%d') ORDER BY id DESC";

            $result = run_query($conn, $sql);

            while($row=mysqli_fetch_assoc($result)) {

                $date_returned = $row['date_returned'];

                $date_items=[];
                $date_total = 0;
                $cr2=0;

                // get total amount
                $sql2 = "SELECT * FROM stock_returns WHERE DATE_FORMAT(date_returned, '%Y-%m-%d')=DATE_FORMAT('{$date_returned}', '%Y-%m-%d')";
                $result2 = run_query($conn, $sql2);

                while($row2=mysqli_fetch_assoc($result2)) {

                    $sql3 = "SELECT * FROM supplier_invoice_items WHERE id='{$row2['invoice_item_id']}'";
                    $result3 = run_query($conn, $sql3);

                    $record = mysqli_fetch_assoc($result3);

                    $unit_returned = get_unit_record($conn, $row2['unit_id']);

                    // $buying_price = $record['new_buying_price'] * $unit_returned['unit_quantity'];
                    $buying_price = $record['unit_buying_price'];

                    $date_items[$cr2]['date_supplied'] = $record['date_added'];
                    $date_items[$cr2]['transaction_code'] = $row2['transaction_code'];
                    $date_items[$cr2]['item'] = get_item_details($conn, $row2['item_id']);
                    $date_items[$cr2]['unit_supplied'] = get_unit_record($conn, $record['unit_id']);
                    $date_items[$cr2]['quantity_supplied'] = $record['unit_quantity'];
                    $date_items[$cr2]['unit_returned'] = $unit_returned;
                    $date_items[$cr2]['quantity_returned'] = $row2['unit_quantity'];
                    $date_items[$cr2]['buying_price'] = $buying_price;
                    $date_items[$cr2]['returned_by'] = get_user_record($conn, $row2['added_by'])['name'];
                    
                    // $unit_supplied = get_unit_record($conn, $record['unit_id']);
                    // $quantity_supplied = $record['unit_quantity'];
                    // $unit_returned = get_unit_record($conn, $row2['unit_id']);
                    $quantity_returned = $row2['unit_quantity'];
                    // $buying_price = $record['new_buying_price'];

                    $date_total += $buying_price * $quantity_returned;

                    $cr2++;
                }

                $returns[$cr]['date_returned'] = $date_returned;
                $returns[$cr]['date_items'] = $date_items;
                $returns[$cr]['total_returned'] = $date_total;

                $cr++;
            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "returns" => $returns
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
            
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-supplier-invoice-items') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->uid));
        
        if(check_device_mac($conn, $mac)) {

            $items = get_supplier_invoice_items($conn, $supplier_id);
            $temp_items = get_supplier_incomplete_returns($conn, $supplier_id, $added_by);
            
            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items,
                "temp_items" => $temp_items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
            
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-supplier-transactions') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));
        
        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM
                        (SELECT 'invoice' AS transaction_type, invoices.invoice_number AS invoice_number,
                            IFNULL(SUM(invoice_items.unit_quantity*invoice_items.unit_buying_price), 0) AS total,
                            invoices.date_created AS date_created, '-' AS payment_channel, IFNULL(users.username, '') AS added_by, '-' AS paid_to
                            FROM
                            
                            (SELECT id, invoice_number, user_id, date_created, date_approved FROM supplier_invoices WHERE approved=1 AND supplier_id='{$supplier_id}' AND id IN (SELECT invoice_id FROM supplier_invoice_items)) AS invoices
                            LEFT JOIN
                            (SELECT invoice_id, new_buying_price, unit_quantity, unit_buying_price, unit_id, total_quantity_added, vat_id FROM `supplier_invoice_items`) AS invoice_items
                            ON invoice_items.invoice_id = invoices.id
                            
                            LEFT JOIN
                            (SELECT id, unit_quantity FROM stock_units) AS stock_units
                            ON stock_units.id=invoice_items.unit_id
                            
                            LEFT JOIN
                            (SELECT id, username FROM users) AS users
                            ON users.id=invoices.user_id

                            LEFT JOIN
                            (SELECT id, vat_percent FROM vat_records) as v
                            ON v.id=invoice_items.vat_id
                            GROUP BY invoices.id
                            
                            UNION ALL
                            SELECT 'payment' AS transaction_type, '-' AS invoice_number, payments.amount_paid AS total, payments.date_paid AS date_created,  payments.payment_channel AS 							payment_channel, IFNULL(users.username, '') AS added_by, payments.paid_to AS paid_to FROM
                                (SELECT amount_paid, user_id, date_paid, payment_channel, paid_to FROM supplier_payments WHERE supplier_id='{$supplier_id}') AS payments
                                    LEFT JOIN
                                (SELECT id, username FROM users) AS users
                                ON users.id=payments.user_id
                        ) AS z
                        ORDER BY z.date_created ASC;";

            $result = run_query($conn, $sql);

            $cr=0;
            $transactions = [];

            $opening_balance = 0;
            $closing_balance = 0;

            while($row=mysqli_fetch_assoc($result)) {

                if($row['transaction_type']==='payment') {
                    $closing_balance = $opening_balance - $row['total'];
                }

                if($row['transaction_type']==='invoice') {
                    $closing_balance = $opening_balance + $row['total'];
                }

                $transactions[$cr]['opening_balance'] = $opening_balance;
                $transactions[$cr]['transaction_type'] = $row['transaction_type'];
                $transactions[$cr]['invoice_number'] = $row['invoice_number'];
                $transactions[$cr]['amount'] = $row['total'];
                $transactions[$cr]['payment_channel'] = $row['payment_channel'];
                $transactions[$cr]['added_by'] = $row['added_by'];
                $transactions[$cr]['paid_to'] = $row['paid_to'];
                $transactions[$cr]['closing_balance'] = $closing_balance;
                $transactions[$cr]['date_created'] = $row['date_created'];

                $opening_balance = $closing_balance;

                $cr++;
            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
            
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-all-suppliers-payments-transactions') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        
        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM
                    (SELECT 'invoice' AS transaction_type, invoices.invoice_number AS invoice_number, IFNULL(suppliers.supplier_name, '') AS supplier,
                        IFNULL(SUM(invoice_items.unit_quantity*invoice_items.unit_buying_price), 0) AS total,
                        invoices.date_created AS date_created, '-' AS payment_channel, IFNULL(users.username, '') AS added_by, '-' AS paid_to
                        FROM
                        
                        (SELECT id, supplier_id, invoice_number, user_id, date_created, date_approved FROM supplier_invoices WHERE approved=1 AND id IN (SELECT invoice_id FROM supplier_invoice_items)) AS invoices
                        LEFT JOIN
                        (SELECT invoice_id, new_buying_price, unit_quantity, unit_buying_price, unit_id, total_quantity_added, vat_id FROM `supplier_invoice_items`) AS invoice_items
                        ON invoice_items.invoice_id = invoices.id
                        
                        LEFT JOIN
                        (SELECT id, unit_quantity FROM stock_units) AS stock_units
                        ON stock_units.id=invoice_items.unit_id
                        
                        LEFT JOIN
                        (SELECT id, username FROM users) AS users
                        ON users.id=invoices.user_id
                        
                        LEFT JOIN
                        (SELECT id, supplier_name FROM suppliers) AS suppliers
                        ON suppliers.id=invoices.supplier_id 

                        LEFT JOIN
                        (SELECT id, vat_percent FROM vat_records) as v
                        ON v.id=invoice_items.vat_id
                        GROUP BY invoices.id
                        
                        UNION ALL
                        SELECT 'payment' AS transaction_type, '-' AS invoice_number, IFNULL(suppliers.supplier_name, '') AS supplier, payments.amount_paid AS total, payments.date_paid AS date_created,  payments.payment_channel AS payment_channel, IFNULL(users.username, '') AS added_by, payments.paid_to AS paid_to FROM
                            (SELECT supplier_id, amount_paid, user_id, date_paid, payment_channel, paid_to FROM supplier_payments) AS payments
                                LEFT JOIN
                            (SELECT id, username FROM users) AS users
                            ON users.id=payments.user_id
                            
                            LEFT JOIN
                            (SELECT id, supplier_name FROM suppliers) AS suppliers
                            ON suppliers.id=payments.supplier_id 
                            
                    ) AS z
                    ORDER BY z.date_created ASC;";

            $result = run_query($conn, $sql);

            $cr=0;
            $transactions = [];

            $opening_balance = 0;
            $closing_balance = 0;

            while($row=mysqli_fetch_assoc($result)) {

                if($row['transaction_type']==='payment') {
                    $closing_balance = $opening_balance - $row['total'];
                }

                if($row['transaction_type']==='invoice') {
                    $closing_balance = $opening_balance + $row['total'];
                }

                $transactions[$cr]['opening_balance'] = $opening_balance;
                $transactions[$cr]['transaction_type'] = $row['transaction_type'];
                $transactions[$cr]['supplier'] = $row['supplier'];
                $transactions[$cr]['invoice_number'] = $row['invoice_number'];
                $transactions[$cr]['amount'] = $row['total'];
                $transactions[$cr]['payment_channel'] = $row['payment_channel'];
                $transactions[$cr]['added_by'] = $row['added_by'];
                $transactions[$cr]['paid_to'] = $row['paid_to'];
                $transactions[$cr]['closing_balance'] = $closing_balance;
                $transactions[$cr]['date_created'] = $row['date_created'];

                $opening_balance = $closing_balance;

                $cr++;
            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
            
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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





if($action === 'get-all-debtor-summary') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));
        
        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {
            
                $sql = "SELECT customers.id, customers.name,customers.sales_limit,customers.mobile_number, IFNULL(sales.sales, 0) AS sales, IFNULL(payments.payments, 0) AS payments, IFNULL(payments.credit_note, 0) AS credit_note
                        FROM
                        (SELECT id,name,sales_limit,mobile_number FROM customers WHERE customer_type='debtor' AND deleted=0) AS customers
                        LEFT JOIN
                        (SELECT customer_id, SUM(unit_quantity*selling_price) AS sales FROM sold_stock_debtors GROUP BY customer_id) AS sales
                        ON sales.customer_id=customers.id
                        LEFT JOIN
                        (SELECT customer_id, SUM(cash+mpesa+bank) AS payments, SUM(amount_credited) AS credit_note FROM debtors_payments GROUP BY customer_id) AS payments
                        ON payments.customer_id=customers.id";
                $result = run_query($conn, $sql);

                $debtors=[];
                $cr=0;

                while($row=mysqli_fetch_assoc($result)) {
                    $debtors[$cr]['id'] = encrypt_data($row['id']);
                    $debtors[$cr]['name'] = $row['name'];
                    $debtors[$cr]['mobile_number'] = $row['mobile_number'];
                    $debtors[$cr]['sales_limit'] = $row['sales_limit'];
                    $debtors[$cr]['sales'] = $row['sales'];
                    $debtors[$cr]['payments'] = $row['payments'];
                    $debtors[$cr]['credit_note'] = $row['credit_note'];

                    $cr++;
                }

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "debtors" => $debtors
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }
        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_all_debtor_sales_and_reversals') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));

        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT dates.date_sold, IFNULL(sales.sales, 0) AS sales, IFNULL(reversed.reversed, 0) AS reversed

                    FROM
                    (SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold FROM sold_stock_debtors WHERE customer_id='{$customer_id}' GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')
                    UNION
                    SELECT DATE_FORMAT(date_returned, '%Y-%m-%d') AS date_sold FROM sold_stock_reversed WHERE customer_id='{$customer_id}' GROUP BY DATE_FORMAT(date_returned, '%Y-%m-%d')
                    ORDER BY date_sold DESC) AS dates
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(date_sold, '%Y-%m-%d') AS date_sold, SUM(unit_quantity*selling_price) AS sales FROM sold_stock_debtors WHERE customer_id='{$customer_id}' GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d')) AS sales
                    ON sales.date_sold=dates.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(date_returned, '%Y-%m-%d') AS date_returned, SUM(quantity_returned*selling_price) AS reversed FROM sold_stock_reversed WHERE customer_id='{$customer_id}' GROUP BY DATE_FORMAT(date_returned, '%Y-%m-%d')) AS reversed
                    ON reversed.date_returned=dates.date_sold
                    
                    ORDER BY dates.date_sold DESC;";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {
                $transactions[$cr]['transaction_date'] = $row['date_sold'];
                $transactions[$cr]['sales'] = $row['sales'];
                $transactions[$cr]['reversed'] = $row['reversed'];
                $cr++;
            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-debtor-date-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        $today = $date;

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT sold_by, SUM(unit_quantity*selling_price) AS total, transaction_code FROM sold_stock_debtors WHERE customer_id='{$customer_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')
                    GROUP BY transaction_code, sold_by";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                $total = $row['total'] - $reversed_amount;

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['items'] = get_debtor_transaction_code_items($conn, $row['transaction_code']);
                $transactions[$cr]['reversed'] = $reversed_amount;
                $transactions[$cr]['sold_by'] = get_user_record($conn, $row['sold_by'])['name'];

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-debtor-payments') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $debtor_id = mysqli_escape_string($conn, decrypt_data($request->debtor_id));
        
        if(check_device_mac($conn, $mac)) {

            $payments = get_debtor_payments($conn, $debtor_id);


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "payments" => $payments
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
            
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-all-debtor-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));
        
        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $debtor_id = mysqli_escape_string($conn, decrypt_data($request->debtor_id));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {

                $transactions = get_debtor_all_transactions($conn, $debtor_id);

                


                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "transactions" => $transactions
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }
        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-debtor-period-statement') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));
        
        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $debtor_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        if(check_device_mac($conn, $mac)) {

            if(backupDatabase($conn)) {

                $transactions = get_debtor_all_transactions($conn, $debtor_id);

                // filter the invoices according to the period specified
                $from = new DateTime($from);
                $to = new DateTime($to);
                
                $result_records = array_values(array_filter($transactions, function($invoice) use ($from, $to, &$period_amount, &$period_payment) {
                    
                    $invoice_date = DateTime::createFromFormat('Y-m-d H:i:s', $invoice['date_created']);
                    
                    // Set the time part to a consistent value (midnight)
                    $invoice_date->setTime(0, 0, 0);
                    
                    $start_datetime = clone $from; // Create a clone to avoid modifying the original object
                    $start_datetime->setTime(0, 0, 0);
            
                    $end_datetime = clone $to; // Create a clone to avoid modifying the original object
                    $end_datetime->setTime(23, 59, 59);

                    // Check if the date_of_sale is within the given period
                    if ($invoice_date >= $start_datetime && $invoice_date <= $end_datetime) {
                        // If yes, update the external variable
                        if($invoice['transaction_type']==='Invoice') {
                            $period_amount += $invoice['amount'];
                        } else {
                            $period_payment += $invoice['amount'];
                        }
                        return true; // Include the invoice in the filtered array
                    }
                        
                    return false;
                
                }));

                $shop = get_shop_details($conn, 1);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "transactions" => $result_records,
                    "shop" => $shop
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }
        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-all-transfer-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));
        
        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $debtor_id = mysqli_escape_string($conn, decrypt_data($request->debtor_id));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {
            
                $sql = "SELECT * FROM
                        (SELECT 'invoice' AS transaction_type, invoices.transaction_code AS invoice_number,
                            IFNULL(invoices.amount, 0)-IFNULL(reversed.amount_reversed, 0) AS total,
                            invoices.date_sold AS date_created, '-' AS payment_channel, IFNULL(users.username, '') AS added_by, '-' AS paid_to
                            FROM
                            
                            (SELECT id, transaction_code, SUM(unit_quantity*buying_price) AS amount, sold_by, date_transfered as date_sold FROM sold_stock_transfers WHERE customer_id='{$debtor_id}' GROUP BY transaction_code, id, sold_by, date_sold) AS invoices
                            LEFT JOIN
                            (SELECT transaction_code, SUM(quantity_returned*buying_price) AS amount_reversed FROM sold_stock_reversed GROUP BY transaction_code) AS reversed
                            ON reversed.transaction_code = invoices.transaction_code
                            
                            
                            LEFT JOIN
                            (SELECT id, username FROM users) AS users
                            ON users.id=invoices.sold_by

                            
                            UNION ALL
                            SELECT 'payment' AS transaction_type, '-' AS invoice_number, payments.amount_paid AS total, payments.date_of_payment AS date_created,  payments.payment_channel AS payment_channel, IFNULL(users.username, '') AS added_by, IFNULL(users.username, '') AS paid_to
                                FROM
                                (SELECT (cash+mpesa+bank+amount_credited) AS amount_paid, added_by, date_paid AS date_of_payment, CASE WHEN cash>0 THEN 'cash' WHEN mpesa>0 THEN 'mpesa' WHEN bank>0 THEN 'bank' WHEN amount_credited>0 THEN 'return' ELSE '-' END AS payment_channel
                                FROM transfer_payments WHERE customer_id='{$debtor_id}') AS payments
                                    LEFT JOIN
                                (SELECT id, username FROM users) AS users
                                ON users.id=payments.added_by
                        ) AS z WHERE z.total>0
                        ORDER BY z.date_created ASC";
                
                $result = run_query($conn, $sql);

                $cr=0;
                $transactions = [];

                $opening_balance = 0;
                $closing_balance = 0;

                while($row=mysqli_fetch_assoc($result)) {

                    if($row['transaction_type']==='payment') {
                        $closing_balance = $opening_balance - $row['total'];
                    }

                    if($row['transaction_type']==='invoice') {
                        $closing_balance = $opening_balance + $row['total'];
                    }

                    $transactions[$cr]['opening_balance'] = $opening_balance;
                    $transactions[$cr]['transaction_type'] = $row['transaction_type'];
                    $transactions[$cr]['invoice_number'] = $row['invoice_number'];
                    $transactions[$cr]['amount'] = $row['total'];
                    $transactions[$cr]['payment_channel'] = $row['payment_channel'];
                    $transactions[$cr]['added_by'] = $row['added_by'];
                    $transactions[$cr]['paid_to'] = $row['paid_to'];
                    $transactions[$cr]['closing_balance'] = $closing_balance;
                    $transactions[$cr]['date_created'] = $row['date_created'];

                    $opening_balance = $closing_balance;

                    $cr++;
                }


                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "transactions" => $transactions
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-all-transfers-summary') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));
        
        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->atu));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {

                $sql = "SELECT customers.id, customers.name,customers.sales_limit,customers.mobile_number, IFNULL(sales.sales, 0) AS sales, IFNULL(payments.payments, 0) AS payments, IFNULL(payments.credit_note, 0) AS credit_note
                        FROM
                        (SELECT id,name,sales_limit,mobile_number FROM customers WHERE customer_type='transfer' AND deleted=0) AS customers
                        LEFT JOIN
                        (SELECT customer_id, SUM(unit_quantity*buying_price) AS sales FROM sold_stock_transfers GROUP BY customer_id) AS sales
                        ON sales.customer_id=customers.id
                        LEFT JOIN
                        (SELECT customer_id, SUM(cash+mpesa+bank) AS payments, SUM(amount_credited) AS credit_note FROM transfer_payments GROUP BY customer_id) AS payments
                        ON payments.customer_id=customers.id";
                $result = run_query($conn, $sql);

                $debtors=[];
                $cr=0;

                while($row=mysqli_fetch_assoc($result)) {
                    $debtors[$cr]['id'] = encrypt_data($row['id']);
                    $debtors[$cr]['name'] = $row['name'];
                    $debtors[$cr]['mobile_number'] = $row['mobile_number'];
                    $debtors[$cr]['sales_limit'] = $row['sales_limit'];
                    $debtors[$cr]['sales'] = $row['sales'];
                    $debtors[$cr]['payments'] = $row['payments'];
                    $debtors[$cr]['credit_note'] = $row['credit_note'];

                    $cr++;
                }

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "debtors" => $debtors
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_all_transfer_sales_and_reversals') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));

        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT dates.date_sold, IFNULL(sales.sales, 0) AS sales, IFNULL(reversed.reversed, 0) AS reversed

                    FROM
                    (SELECT DATE_FORMAT(date_transfered, '%Y-%m-%d') AS date_sold FROM sold_stock_transfers WHERE customer_id='{$customer_id}' GROUP BY DATE_FORMAT(date_transfered, '%Y-%m-%d')
                    UNION
                    SELECT DATE_FORMAT(date_returned, '%Y-%m-%d') AS date_sold FROM sold_stock_reversed WHERE customer_id='{$customer_id}' GROUP BY DATE_FORMAT(date_returned, '%Y-%m-%d')
                    ORDER BY date_sold DESC) AS dates
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(date_transfered, '%Y-%m-%d') AS date_sold, SUM(unit_quantity*buying_price) AS sales FROM sold_stock_transfers WHERE customer_id='{$customer_id}' GROUP BY DATE_FORMAT(date_transfered, '%Y-%m-%d')) AS sales
                    ON sales.date_sold=dates.date_sold
                    
                    LEFT JOIN
                    (SELECT DATE_FORMAT(date_returned, '%Y-%m-%d') AS date_returned, SUM(quantity_returned*selling_price) AS reversed FROM sold_stock_reversed WHERE customer_id='{$customer_id}' GROUP BY DATE_FORMAT(date_returned, '%Y-%m-%d')) AS reversed
                    ON reversed.date_returned=dates.date_sold
                    
                    ORDER BY dates.date_sold DESC;";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {
                $transactions[$cr]['transaction_date'] = $row['date_sold'];
                $transactions[$cr]['sales'] = $row['sales'];
                $transactions[$cr]['reversed'] = $row['reversed'];
                $cr++;
            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-transfer-date-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));

        $today = $date;

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT sold_by, SUM(unit_quantity*buying_price) AS total, transaction_code FROM sold_stock_transfers WHERE customer_id='{$customer_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')
                    GROUP BY transaction_code, sold_by";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                $total = $row['total'] - $reversed_amount;

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['items'] = get_transfer_transaction_code_items($conn, $row['transaction_code']);
                $transactions[$cr]['reversed'] = $reversed_amount;
                $transactions[$cr]['sold_by'] = get_user_record($conn, $row['sold_by'])['name'];

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-transfer-payments') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $debtor_id = mysqli_escape_string($conn, decrypt_data($request->debtor_id));
        
        if(check_device_mac($conn, $mac)) {

            $payments=[];
            $cr=0;

            $sql = "SELECT * FROM transfer_payments WHERE customer_id='{$debtor_id}' AND (cash+mpesa+bank+amount_credited)>0 ORDER BY id DESC";

            $result = run_query($conn, $sql);

            while($row=mysqli_fetch_assoc($result)) {
                $payments[$cr]['id'] = encrypt_data($row['id']);
                $payments[$cr]['cash'] = $row['cash'];
                $payments[$cr]['bank'] = $row['bank'];
                $payments[$cr]['mpesa'] = $row['mpesa'];
                $payments[$cr]['amount_credited'] = $row['amount_credited'];
                $payments[$cr]['date_paid'] = $row['date_paid'];
                $payments[$cr]['added_by'] = get_user_record($conn, $row['added_by'])['name'];

                $cr++;
            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "payments" => $payments
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
            
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-user-reprint-cash-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = get_today();

        $start_date = date ("Y-m-d", time());
        $end_date = date ("Y-m-d", strtotime("-6 days", strtotime($start_date)));


        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT a.transaction_code, IFNULL(a.total, 0) AS total,
                        a.date_sold AS date_sold, IFNULL(b.customer_name, '') AS customer,
                        a.sales_payments_id AS payment_id,
                        IFNULL(b.cash, 0) AS cash, IFNULL(b.mpesa, 0) AS mpesa,
                        IFNULL(b.bank, 0) AS bank,
                        IFNULL(b.amount_reversed, 0) AS reversed,
                        b.payment_channel AS payment_channel
                    FROM
                    (SELECT SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code, sales_payments_id FROM sold_stock WHERE
                        sold_by='{$user_id}'
                        GROUP BY date_sold, transaction_id, transaction_code, sales_payments_id) AS a
                    INNER JOIN
                    (SELECT id, customer_name, cash, mpesa, bank, amount_reversed, payment_channel FROM sales_payments) AS b
                    ON a.sales_payments_id = b.id
                    ORDER BY a.transaction_id DESC";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $customer['id'] = encrypt_data('0');
                $customer['name'] = $row['customer'];

                $payment['id'] = encrypt_data($row['payment_id']);
                $payment['payment_channel'] = $row['payment_channel'];
                $payment['cash'] = $row['cash'];
                $payment['mpesa'] = $row['mpesa'];
                $payment['bank'] = $row['bank'];
                $payment['reversed'] = $row['reversed'];

                $total = $row['total'] - $row['reversed'];

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['customer'] = $customer;
                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['items'] = [];
                $transactions[$cr]['payment'] = $payment;

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-user-cash-reprint-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $transaction_code = mysqli_escape_string($conn, decrypt_data($request->code));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->uid));


        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM sold_stock WHERE transaction_code='{$transaction_code}' ORDER BY id ASC";
            $result = run_query($conn, $sql);

            $cart_items=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $item_id = $row['item_id'];
                $location_id = $row['location_id'];
                $unit_id = $row['unit_id'];

                $item = get_item_details($conn, $item_id);
                $item['selling_units'] = get_item_selling_units($conn, $item_id);

                $location = get_shop_location_record($conn, $location_id);
                $unit = get_unit_record($conn, $unit_id);

                $unit_qty = $row['unit_quantity'];
                $unit_bp = $row['buying_price'];
                $min_sp = $row['min_selling_price'];
                $recom_sp = $row['recom_selling_price'];
                $unit_sp = $row['selling_price'];

                // find any reversed pieces
                $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $transaction_code, $item_id, $row['id']);
                
                $cart_items[$cr]['id'] = encrypt_data($row['id']);
                $cart_items[$cr]['item'] = $item;
                $cart_items[$cr]['location'] = $location;
                $cart_items[$cr]['unit'] = $unit;
                $cart_items[$cr]['qty'] = $unit_qty;
                $cart_items[$cr]['pieces_reversed'] = $pieces_reversed;
                $cart_items[$cr]['sp'] = $unit_sp;
                $cart_items[$cr]['bp'] = $unit_bp;
                $cart_items[$cr]['min_sp'] = $min_sp;

                

                // calculate the discount
                $discount = 0;
                if($unit_sp < $recom_sp) {
                    $disc = $recom_sp - $unit_sp;
                    $discount = $disc * $unit_qty;
                }
                $cart_items[$cr]['discount'] = $discount;

                // calculate any overcharge
                $overcharge = 0;
                if($unit_sp > $recom_sp) {
                    $over = $unit_sp - $recom_sp;
                    $overcharge = $over * $unit_qty;
                }
                $cart_items[$cr]['overcharge'] = $overcharge;

                // calculate profit
                $profit=0;
                if($unit_sp > $unit_bp) {
                    $pro = $unit_sp - $unit_bp;
                    $profit = $pro * $unit_qty;
                }
                $cart_items[$cr]['profit'] = $profit;

                $cr++;


            }


            // get the shop name and description for printing
            $shop_details = get_shop_details($conn, $added_by);

            $shop_details['tcode'] = encrypt_data($transaction_code);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $cart_items,
                "Shop" => $shop_details
            );

            echo json_encode($data_insert);
            mysqli_close($conn);



        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-user-reprint-debtor-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = get_today();

        $start_date = date ("Y-m-d", time());
        $end_date = date ("Y-m-d", strtotime("-6 days", strtotime($start_date)));


        if(check_device_mac($conn, $mac)) {

            $user_role = get_user_record($conn, $user_id)['role'];

            $sql = '';

            if(decrypt_data($user_role) === 'admin') {

                $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total,
                            a.date_sold AS date_sold, IFNULL(b.name, '') AS customer
                        FROM
                        (SELECT customer_id, SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code FROM sold_stock_debtors
                        GROUP BY customer_id, date_sold, transaction_id, transaction_code) AS a
                        INNER JOIN
                        (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        ORDER BY a.transaction_id DESC";

            } else {
                $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total,
                        a.date_sold AS date_sold, IFNULL(b.name, '') AS customer
                    FROM
                    (SELECT customer_id, SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code FROM sold_stock_debtors WHERE sold_by='{$user_id}'
                    GROUP BY customer_id, date_sold, transaction_id, transaction_code) AS a
                    INNER JOIN
                    (SELECT id, name FROM customers) AS b
                    ON a.customer_id = b.id
                    ORDER BY a.transaction_id DESC";
            }

            
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $customer['id'] = encrypt_data($row['customer_id']);
                $customer['name'] = $row['customer'];

                $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                $total = $row['total'] - $reversed_amount;

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['customer'] = $customer;
                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['items'] = [];
                $transactions[$cr]['reversed'] = $reversed_amount;

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-user-debtor-reprint-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $transaction_code = mysqli_escape_string($conn, decrypt_data($request->code));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            $debtor_id = '';

            $sql = "SELECT * FROM sold_stock_debtors WHERE transaction_code='{$transaction_code}' ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $cart_items=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $debtor_id = $row['customer_id'];

                $item_id = $row['item_id'];
                $location_id = $row['location_id'];
                $unit_id = $row['unit_id'];

                $item = get_item_details($conn, $item_id);
                $item['selling_units'] = get_item_selling_units($conn, $item_id);

                $location = get_shop_location_record($conn, $location_id);
                $unit = get_unit_record($conn, $unit_id);

                $unit_qty = $row['unit_quantity'];
                $unit_bp = $row['buying_price'];
                $min_sp = $row['min_selling_price'];
                $recom_sp = $row['recom_selling_price'];
                $unit_sp = $row['selling_price'];

                // find any reversed pieces
                $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $transaction_code, $item_id, $row['id']);
                
                $cart_items[$cr]['id'] = encrypt_data($row['id']);
                $cart_items[$cr]['item'] = $item;
                $cart_items[$cr]['location'] = $location;
                $cart_items[$cr]['unit'] = $unit;
                $cart_items[$cr]['qty'] = $unit_qty;
                $cart_items[$cr]['pieces_reversed'] = $pieces_reversed;
                $cart_items[$cr]['sp'] = $unit_sp;
                $cart_items[$cr]['bp'] = $unit_bp;
                $cart_items[$cr]['min_sp'] = $min_sp;

                

                // calculate the discount
                $discount = 0;
                if($unit_sp < $recom_sp) {
                    $disc = $recom_sp - $unit_sp;
                    $discount = $disc * $unit_qty;
                }
                $cart_items[$cr]['discount'] = $discount;

                // calculate any overcharge
                $overcharge = 0;
                if($unit_sp > $recom_sp) {
                    $over = $unit_sp - $recom_sp;
                    $overcharge = $over * $unit_qty;
                }
                $cart_items[$cr]['overcharge'] = $overcharge;

                // calculate profit
                $profit=0;
                if($unit_sp > $unit_bp) {
                    $pro = $unit_sp - $unit_bp;
                    $profit = $pro * $unit_qty;
                }
                $cart_items[$cr]['profit'] = $profit;

                $cr++;


            }

            // get the shop name and description for printing
            $shop_details = get_shop_details($conn, $added_by);
            $shop_details['tcode'] = encrypt_data($transaction_code);
            
            $balance_bf = get_debtor_transaction_opening_balance($conn, $debtor_id, $transaction_code);
            
            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $cart_items,
                "Shop" => $shop_details,
                "balance_bf" => $balance_bf

            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-user-reprint-transfer-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = get_today();

        $start_date = date ("Y-m-d", time());
        $end_date = date ("Y-m-d", strtotime("-6 days", strtotime($start_date)));


        if(check_device_mac($conn, $mac)) {

            $user_role = get_user_record($conn, $user_id)['role'];

            $sql = '';

            if(decrypt_data($user_role) === 'admin') {

                $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total, a.date_transfered AS date_sold, IFNULL(b.name, '') AS customer
                        FROM
                        (SELECT customer_id, SUM(unit_quantity*buying_price) AS total, date_transfered, transaction_id, transaction_code FROM sold_stock_transfers
                        GROUP BY customer_id, date_transfered, transaction_id, transaction_code) AS a
                        INNER JOIN (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        ORDER BY a.transaction_id DESC;";

            } else {

                $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total, a.date_transfered AS date_sold, IFNULL(b.name, '') AS customer
                        FROM
                        (SELECT customer_id, SUM(unit_quantity*buying_price) AS total, date_transfered, transaction_id, transaction_code FROM sold_stock_transfers
                        WHERE sold_by='{$user_id}'
                        GROUP BY customer_id, date_transfered, transaction_id, transaction_code) AS a
                        INNER JOIN (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        ORDER BY a.transaction_id DESC;";

            }



            
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $customer['id'] = encrypt_data($row['customer_id']);
                $customer['name'] = $row['customer'];

                $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                $total = $row['total'] - $reversed_amount;

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['customer'] = $customer;
                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['items'] = [];
                $transactions[$cr]['reversed'] = $reversed_amount;

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-user-transfer-reprint-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $transaction_code = mysqli_escape_string($conn, decrypt_data($request->code));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->uid));


        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM sold_stock_transfers WHERE transaction_code='{$transaction_code}' ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $cart_items=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $item_id = $row['item_id'];
                $location_id = $row['location_id'];
                $unit_id = $row['unit_id'];

                $item = get_item_details($conn, $item_id);
                $item['selling_units'] = get_item_selling_units($conn, $item_id);

                $location = get_shop_location_record($conn, $location_id);
                $unit = get_unit_record($conn, $unit_id);

                $unit_qty = $row['unit_quantity'];
                $unit_bp = $row['buying_price'];
                $min_sp = $row['buying_price'];
                $recom_sp = $row['buying_price'];
                $unit_sp = $row['buying_price'];

                // find any reversed pieces
                $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $transaction_code, $item_id, $row['id']);
                
                $cart_items[$cr]['id'] = encrypt_data($row['id']);
                $cart_items[$cr]['item'] = $item;
                $cart_items[$cr]['location'] = $location;
                $cart_items[$cr]['unit'] = $unit;
                $cart_items[$cr]['qty'] = $unit_qty;
                $cart_items[$cr]['pieces_reversed'] = $pieces_reversed;
                $cart_items[$cr]['sp'] = $unit_sp;
                $cart_items[$cr]['bp'] = $unit_bp;
                $cart_items[$cr]['min_sp'] = $min_sp;

                

                // calculate the discount
                $discount = 0;
                $cart_items[$cr]['discount'] = $discount;

                // calculate any overcharge
                $overcharge = 0;
                $cart_items[$cr]['overcharge'] = $overcharge;

                // calculate profit
                $profit=0;
                $cart_items[$cr]['profit'] = $profit;

                $cr++;


            }


            // get the shop name and description for printing
            $shop_details = get_shop_details($conn, $added_by);
            $shop_details['tcode'] = encrypt_data($transaction_code);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $cart_items,
                "Shop" => $shop_details
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-user-inter-location-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $today = get_today();

        $start_date = date ("Y-m-d", time());
        $end_date = date ("Y-m-d", strtotime("-6 days", strtotime($start_date)));


        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT transaction_code, location_1_id AS from_id, location_2_id AS to_id, DATE_FORMAT(date_transfered, '%Y-%m-%d') AS date_transfered
                    FROM stock_location_transfer
                    WHERE transfered_by='{$user_id}'
                    AND DATE_FORMAT(date_transfered, '%Y-%m-%d')>=DATE_FORMAT('{$end_date}', '%Y-%m-%d')
                    GROUP BY transaction_code
                    ORDER BY DATE_FORMAT(date_transfered, '%Y-%m-%d') DESC";
            $result = run_query($conn, $sql);

            $transactions = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                $location_from = get_shop_location_record($conn, $row['from_id']);
                $location_to = get_shop_location_record($conn, $row['to_id']);

                $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                $transactions[$cr]['date_transfered'] = $row['date_transfered'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-user-interlocation-reprint-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $transaction_code = mysqli_escape_string($conn, decrypt_data($request->code));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->uid));
        $print = mysqli_escape_string($conn, decrypt_data($request->print));


        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM stock_location_transfer WHERE transaction_code='{$transaction_code}' ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $cart_items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                $item = get_item_details($conn, $row['item_id']);
                $location_from = get_shop_location_record($conn, $row['location_1_id']);
                $location_to = get_shop_location_record($conn, $row['location_2_id']);
                $unit = get_unit_record($conn, $row['unit_id']);



                $cart_items[$cr]['id'] = encrypt_data($row['id']);
                $cart_items[$cr]['item'] = $item;
                $cart_items[$cr]['location_from'] = $location_from;
                $cart_items[$cr]['location_to'] = $location_to;
                $cart_items[$cr]['unit'] = $unit;
                $cart_items[$cr]['unit_quantity'] = $row['unit_quantity'];
                $cart_items[$cr]['location_from_old_quantity'] = $row['location_1_old_quantity'];

                $cr++;

            }


            if($print === '1') {

                // get the shop name and description for printing
                $shop_details = get_shop_details($conn, $added_by);
                $shop_details['tcode'] = encrypt_data($transaction_code);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "items" => $cart_items,
                    "Shop" => $shop_details
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "items" => $cart_items
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }
            

        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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




if($action === 'get-items-report-summary') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));

        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        if(check_device_mac($conn, $mac)) {

            if(backupDatabase($conn)) {
           
                $sql = "SELECT a.id, a.name, a.category_id,
                        (IFNULL(cash_sales.sales, 0) + IFNULL(debtors_sales.sales, 0)) AS sales,
                        (IFNULL(cash_sales.profit, 0) + IFNULL(debtors_sales.profit, 0) - IFNULL(reversed.reversed_profit, 0)) AS profit,
                        IFNULL(reversed.reversed, 0) AS reversed,
                        IFNULL(transfer.transfers, 0) AS transfers,
                        (IFNULL(added_supplier.added_invoices, 0) + IFNULL(positive.added_positive, 0)) AS added_amount,
                        IFNULL(negative.added_negative, 0) AS reduced,
                        IFNULL(returned.returned_stock, 0) AS returned

                        FROM

                        (SELECT id, name, category_id FROM stock WHERE deleted=0) AS a


                        LEFT JOIN
                        (SELECT item_id, SUM(unit_quantity*selling_price) AS sales, SUM(unit_quantity*(selling_price-buying_price)) AS profit FROM sold_stock
                            WHERE DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS cash_sales
                        ON cash_sales.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(unit_quantity*selling_price) AS sales, SUM(unit_quantity*(selling_price-buying_price)) AS profit FROM sold_stock_debtors
                            WHERE DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS debtors_sales
                        ON debtors_sales.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(quantity_returned*selling_price) AS reversed, SUM(quantity_returned*(selling_price-buying_price)) AS reversed_profit FROM sold_stock_reversed
                            WHERE transaction_code NOT LIKE 'TT%'
                            AND DATE_FORMAT(date_returned, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS reversed
                        ON reversed.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(unit_quantity*buying_price) AS transfers FROM sold_stock_transfers
                            WHERE DATE_FORMAT(date_transfered, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS transfer
                        ON transfer.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(total_quantity_added*new_buying_price) AS added_invoices FROM supplier_invoice_items
                            WHERE DATE_FORMAT(date_added, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS added_supplier
                        ON added_supplier.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(total_quantity_added*buying_price_per_piece) AS added_positive FROM stock_positive
                            WHERE DATE_FORMAT(date_added, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS positive
                        ON positive.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(total_quantity_removed*buying_price_per_piece) AS added_negative FROM stock_negative
                            WHERE DATE_FORMAT(date_removed, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_removed, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS negative
                        ON negative.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(unit_quantity*buying_price) AS returned_stock FROM stock_returns
                            WHERE DATE_FORMAT(date_returned, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS returned
                        ON returned.item_id=a.id;";

                $result = run_query($conn, $sql);

                $items=[];
                $cr=0;

                while($row = mysqli_fetch_assoc($result)) {

                    $items[$cr]['item'] = get_item_details($conn, $row['id']);
                    $items[$cr]['category'] = get_category($conn, $row['category_id']);

                    $items[$cr]['sales'] = $row['sales'];
                    $items[$cr]['reversed'] = $row['reversed'];
                    $items[$cr]['profit'] = $row['profit'];
                    $items[$cr]['transfers'] = $row['transfers'];
                    $items[$cr]['added'] = $row['added_amount'];
                    $items[$cr]['reduced'] = $row['reduced'];
                    $items[$cr]['returned'] = $row['returned'];

                    $cr++;
                }

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "items" => $items
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        }
        
    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-items-report-summary-location') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));

        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));

        if(check_device_mac($conn, $mac)) {

            if(backupDatabase($conn)) {
           
                $sql = "SELECT a.id, a.name, a.category_id,
                        (IFNULL(cash_sales.sales, 0) + IFNULL(debtors_sales.sales, 0)) AS sales,
                        (IFNULL(cash_sales.profit, 0) + IFNULL(debtors_sales.profit, 0) - IFNULL(reversed.reversed_profit, 0)) AS profit,
                        IFNULL(reversed.reversed, 0) AS reversed,
                        IFNULL(transfer.transfers, 0) AS transfers,
                        (IFNULL(added_supplier.added_invoices, 0) + IFNULL(positive.added_positive, 0)) AS added_amount,
                        IFNULL(negative.added_negative, 0) AS reduced,
                        IFNULL(returned.returned_stock, 0) AS returned

                        FROM

                        (SELECT id, name, category_id FROM stock WHERE deleted=0) AS a


                        LEFT JOIN
                        (SELECT item_id, SUM(unit_quantity*selling_price) AS sales, SUM(unit_quantity*(selling_price-buying_price)) AS profit FROM sold_stock
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS cash_sales
                        ON cash_sales.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(unit_quantity*selling_price) AS sales, SUM(unit_quantity*(selling_price-buying_price)) AS profit FROM sold_stock_debtors
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS debtors_sales
                        ON debtors_sales.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(quantity_returned*selling_price) AS reversed, SUM(quantity_returned*(selling_price-buying_price)) AS reversed_profit FROM sold_stock_reversed
                            WHERE location_id='{$location_id}' AND transaction_code NOT LIKE 'TT%'
                            AND DATE_FORMAT(date_returned, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS reversed
                        ON reversed.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(unit_quantity*buying_price) AS transfers FROM sold_stock_transfers
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS transfer
                        ON transfer.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(total_quantity_added*new_buying_price) AS added_invoices FROM supplier_invoice_items
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS added_supplier
                        ON added_supplier.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(total_quantity_added*buying_price_per_piece) AS added_positive FROM stock_positive
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS positive
                        ON positive.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(total_quantity_removed*buying_price_per_piece) AS added_negative FROM stock_negative
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_removed, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_removed, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS negative
                        ON negative.item_id=a.id
                        
                        LEFT JOIN
                        (SELECT item_id, SUM(unit_quantity*buying_price) AS returned_stock FROM stock_returns
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY item_id) AS returned
                        ON returned.item_id=a.id;";

                $result = run_query($conn, $sql);

                $items=[];
                $cr=0;

                while($row = mysqli_fetch_assoc($result)) {

                    $items[$cr]['item'] = get_item_details($conn, $row['id']);
                    $items[$cr]['category'] = get_category($conn, $row['category_id']);

                    $items[$cr]['sales'] = $row['sales'];
                    $items[$cr]['reversed'] = $row['reversed'];
                    $items[$cr]['profit'] = $row['profit'];
                    $items[$cr]['transfers'] = $row['transfers'];
                    $items[$cr]['added'] = $row['added_amount'];
                    $items[$cr]['reduced'] = $row['reduced'];
                    $items[$cr]['returned'] = $row['returned'];

                    $cr++;
                }

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "items" => $items
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        }
        
    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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




if($action === 'get-item-period-report') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));

        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        if(check_device_mac($conn, $mac)) {
            $sql = "SELECT date_sold, transaction_code, unit_id, unit_quantity, type, sold_by, total_old_quantity, location_id FROM

                    ((SELECT id, date_sold, transaction_code, unit_id, unit_quantity, 'Cash sale' AS type, sold_by, total_old_quantity, location_id FROM sold_stock WHERE item_id='{$item_id}' AND (DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))  ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_sold, transaction_code, unit_id, unit_quantity, 'Debtor sale' AS type, sold_by, total_old_quantity, location_id FROM sold_stock_debtors WHERE item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_transfered AS date_sold, transaction_code, unit_id, unit_quantity, 'Transfer sale' AS type, sold_by, total_old_quantity, location_id FROM sold_stock_transfers WHERE item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_returned AS date_sold, transaction_code, unit_id, quantity_returned AS unit_quantity, 'Reversal' AS type, sold_by, total_old_quantity, location_id FROM sold_stock_reversed WHERE item_id='{$item_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_added AS date_Sold, 'Invoice' AS transaction_code, unit_id, unit_quantity, 'Supplier stock' AS type, added_by AS sold_by, total_old_quantity, location_id FROM supplier_invoice_items WHERE item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_added AS date_sold, 'Added quantity' AS transaction_code, unit_id, unit_quantity, 'Stock positive' AS type, added_by AS sold_by, total_old_quantity, location_id FROM stock_positive WHERE item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_removed AS date_sold, 'Reduced quantity' AS transaction_code, unit_id, unit_quantity, 'Stock negative' AS type, removed_by AS sold_by, total_old_quantity, location_id FROM stock_negative WHERE item_id='{$item_id}' AND DATE_FORMAT(date_removed, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_removed, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_returned AS date_sold, 'Returned supplier stock' AS transaction_code, unit_id, unit_quantity, 'Stock return' AS type, added_by AS sold_by, total_old_quantity, location_id FROM stock_returns WHERE item_id='{$item_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_transfered AS date_sold, 'Interlocation transfer' AS transaction_code, unit_id, unit_quantity, 'Location transfer' AS type, transfered_by AS sold_by, location_1_old_quantity AS total_old_quantity, location_1_id AS location_id FROM stock_location_transfer WHERE item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    ) AS a
                    
                    
                    ORDER BY date_sold DESC";

            $result = run_query($conn, $sql);

            $transactions = [];
            $cr=0;

            $total_quantity_sold = 0;
            $total_quantity_reversed = 0;
            $total_quantity_transfered = 0;
            $total_quantity_added = 0;
            $total_quantity_reduced = 0;
            $total_quantity_returned = 0;

            while($row=mysqli_fetch_assoc($result)) {

                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['opening_quantity'] = $row['total_old_quantity'];
                $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                $transactions[$cr]['type'] = $row['type'];
                $unit = get_unit_record($conn, $row['unit_id']);
                $transactions[$cr]['unit'] = $unit;
                $transactions[$cr]['unit_quantity'] = $row['unit_quantity'];
                $transactions[$cr]['sold_by'] = get_user_record($conn, $row['sold_by'])['name'];

                if($row['type'] === 'Cash sale' || $row['type'] === 'Debtor sale') {
                    $total_quantity_sold += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                if($row['type'] === 'Reversal') {
                    $total_quantity_sold -= $row['unit_quantity'] * $unit['unit_quantity'];
                    $total_quantity_reversed += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                if($row['type'] === 'Transfer sale') {
                    // $total_quantity_sold -= $row['unit_quantity'] * $unit['unit_quantity'];
                    $total_quantity_transfered += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                if($row['type'] === 'Supplier stock' || $row['type'] === 'Stock positive') {
                    // $total_quantity_sold -= $row['unit_quantity'] * $unit['unit_quantity'];
                    $total_quantity_added += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                if($row['type'] === 'Stock negative') {
                    // $total_quantity_sold -= $row['unit_quantity'] * $unit['unit_quantity'];
                    $total_quantity_reduced += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                if($row['type'] === 'Stock return') {
                    // $total_quantity_sold -= $row['unit_quantity'] * $unit['unit_quantity'];
                    $total_quantity_returned += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                $transactions[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);

                
                $cr++;

            }

            $item_units = get_item_selling_units($conn, $item_id);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions,
                "units" => $item_units,
                "sold" => $total_quantity_sold,
                "reversed" => $total_quantity_reversed,
                "transfered" => $total_quantity_transfered,
                "added" => $total_quantity_added,
                "reduced" => $total_quantity_reduced,
                "returned" => $total_quantity_returned
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-item-period-report-location') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));

        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));

        if(check_device_mac($conn, $mac)) {
            $sql = "SELECT date_sold, transaction_code, unit_id, unit_quantity, type, sold_by, total_old_quantity, location_id FROM

                    ((SELECT id, date_sold, transaction_code, unit_id, unit_quantity, 'Cash sale' AS type, sold_by, location_old_quantity AS total_old_quantity, location_id FROM sold_stock WHERE location_id='{$location_id}' AND item_id='{$item_id}' AND (DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d'))  ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_sold, transaction_code, unit_id, unit_quantity, 'Debtor sale' AS type, sold_by, location_old_quantity AS total_old_quantity, location_id FROM sold_stock_debtors WHERE location_id='{$location_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_transfered AS date_sold, transaction_code, unit_id, unit_quantity, 'Transfer sale' AS type, sold_by, location_old_quantity AS total_old_quantity, location_id FROM sold_stock_transfers WHERE location_id='{$location_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_returned AS date_sold, transaction_code, unit_id, quantity_returned AS unit_quantity, 'Reversal' AS type, sold_by, total_old_quantity, location_id FROM sold_stock_reversed WHERE location_id='{$location_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_added AS date_Sold, 'Invoice' AS transaction_code, unit_id, unit_quantity, 'Supplier stock' AS type, added_by AS sold_by, location_old_quantity AS total_old_quantity, location_id FROM supplier_invoice_items WHERE location_id='{$location_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_added AS date_sold, 'Added quantity' AS transaction_code, unit_id, unit_quantity, 'Stock positive' AS type, added_by AS sold_by, location_old_quantity AS total_old_quantity, location_id FROM stock_positive WHERE location_id='{$location_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_added, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_added, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_removed AS date_sold, 'Reduced quantity' AS transaction_code, unit_id, unit_quantity, 'Stock negative' AS type, removed_by AS sold_by, location_old_quantity AS total_old_quantity, location_id FROM stock_negative WHERE location_id='{$location_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_removed, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_removed, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_returned AS date_sold, 'Returned supplier stock' AS transaction_code, unit_id, unit_quantity, 'Stock return' AS type, added_by AS sold_by, location_old_quantity AS total_old_quantity, location_id FROM stock_returns WHERE location_id='{$location_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    
                    UNION ALL
                    (SELECT id, date_transfered AS date_sold, 'Interlocation transfer' AS transaction_code, unit_id, unit_quantity, 'Location transfer' AS type, transfered_by AS sold_by, location_1_old_quantity AS total_old_quantity, location_1_id AS location_id FROM stock_location_transfer WHERE location_1_id='{$location_id}' AND item_id='{$item_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC)
                    ) AS a
                    
                    ORDER BY date_sold DESC";

            $result = run_query($conn, $sql);

            $transactions = [];
            $cr=0;

            $total_quantity_sold = 0;
            $total_quantity_reversed = 0;
            $total_quantity_transfered = 0;
            $total_quantity_added = 0;
            $total_quantity_reduced = 0;
            $total_quantity_returned = 0;

            while($row=mysqli_fetch_assoc($result)) {

                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['opening_quantity'] = $row['total_old_quantity'];
                $transactions[$cr]['transaction_code'] = $row['transaction_code'];
                $transactions[$cr]['type'] = $row['type'];
                $unit = get_unit_record($conn, $row['unit_id']);
                $transactions[$cr]['unit'] = $unit;
                $transactions[$cr]['unit_quantity'] = $row['unit_quantity'];
                $transactions[$cr]['sold_by'] = get_user_record($conn, $row['sold_by'])['name'];

                if($row['type'] === 'Cash sale' || $row['type'] === 'Debtor sale') {
                    $total_quantity_sold += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                if($row['type'] === 'Reversal') {
                    $total_quantity_sold -= $row['unit_quantity'] * $unit['unit_quantity'];
                    $total_quantity_reversed += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                if($row['type'] === 'Transfer sale') {
                    // $total_quantity_sold -= $row['unit_quantity'] * $unit['unit_quantity'];
                    $total_quantity_transfered += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                if($row['type'] === 'Supplier stock' || $row['type'] === 'Stock positive') {
                    // $total_quantity_sold -= $row['unit_quantity'] * $unit['unit_quantity'];
                    $total_quantity_added += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                if($row['type'] === 'Stock negative') {
                    // $total_quantity_sold -= $row['unit_quantity'] * $unit['unit_quantity'];
                    $total_quantity_reduced += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                if($row['type'] === 'Stock return') {
                    // $total_quantity_sold -= $row['unit_quantity'] * $unit['unit_quantity'];
                    $total_quantity_returned += $row['unit_quantity'] * $unit['unit_quantity'];
                }

                $transactions[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);

                
                $cr++;

            }

            $item_units = get_item_selling_units($conn, $item_id);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions,
                "units" => $item_units,
                "sold" => $total_quantity_sold,
                "reversed" => $total_quantity_reversed,
                "transfered" => $total_quantity_transfered,
                "added" => $total_quantity_added,
                "reduced" => $total_quantity_reduced,
                "returned" => $total_quantity_returned
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



// periodic reports
if($action === 'get-periodic-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        $chart = mysqli_escape_string($conn, decrypt_data($request->chart));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));

        if(check_device_mac($conn, $mac)) {

            // if(backupDatabase($conn)) {

                // get gross sales, gross profit, expenses,
                // debtors, transfers, cash, bank, mpesa,
                // reversed amounts
                // opening float per user
                $sql = "";

                if($location_id=='0') {

                    $sql = "SELECT a.id, a.username,
                            IFNULL(cash_float.float_amount, 0) AS float_amount,
                            IFNULL(cash_sales.gross_sales, 0) AS cash_sales,
                            IFNULL(cash_sales.gross_profit, 0) AS cash_profit,
                            IFNULL(reversed_cash.sales_reversed, 0) AS cash_reversed,
                            IFNULL(reversed_cash.reversed_profit, 0) AS reversed_cash_profit,
                            
                            IFNULL(debtor_sales.gross_sales, 0) AS debtor_sales,
                            IFNULL(debtor_sales.gross_profit, 0) AS debtor_profit,
                            IFNULL(reversed_debtor.sales_reversed, 0) AS debtor_reversed,
                            IFNULL(reversed_debtor.reversed_profit, 0) AS reversed_debtor_profit,
                            
                            IFNULL(transfer_sales.gross_sales, 0) AS transfer_sales,
                            IFNULL(reversed_transfer.sales_reversed, 0) AS transfer_reversed,
                            
                            IFNULL(expenses.expense, 0) AS expenses,
                            IFNULL(float_amount.cash_float, 0) AS cash_float,
                            
                            IFNULL(banked_amount.cash, 0) AS cash_banked,
                            IFNULL(banked_amount.mpesa, 0) AS mpesa_banked,
                            IFNULL(banked_amount.bank, 0) AS bank_banked,
                            
                            IFNULL(banked_reversed_cash_only.reversed_cash_amount, 0) AS reversed_cash_only,
                            IFNULL(banked_reversed_mpesa_only.reversed_mpesa_amount, 0) AS reversed_mpesa_only,
                            IFNULL(banked_reversed_bank_only.reversed_bank_amount, 0) AS reversed_bank_only,
                            IFNULL(banked_reversed_multiple.reversed_multiple, 0) AS reversed_multiple,

                            IFNULL(cash_discounts.discounts, 0) AS cash_discounts,
                            IFNULL(debtor_discounts.discounts, 0) AS debtor_discounts,

                            IFNULL(cash_overcharges.overcharges, 0) AS cash_overcharges,
                            IFNULL(debtor_overcharges.overcharges, 0) AS debtor_overcharges

                            FROM 
                            
                            (SELECT id, username FROM users WHERE deleted=0) AS a
                            
                            LEFT JOIN
                            (SELECT added_by, SUM(float_amount) AS float_amount FROM opening_float
                                WHERE DATE_FORMAT(date_created, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_created, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY added_by) AS cash_float
                            ON cash_float.added_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit FROM sold_stock
                                WHERE DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS cash_sales
                            ON cash_sales.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit FROM sold_stock_reversed
                                WHERE DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND sale_type='cash'
                                GROUP BY sold_by) AS reversed_cash
                            ON reversed_cash.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit FROM sold_stock_debtors
                                WHERE DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS debtor_sales
                            ON debtor_sales.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit FROM sold_stock_reversed
                                WHERE DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND sale_type='debtor'
                                GROUP BY sold_by) AS reversed_debtor
                            ON reversed_debtor.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(unit_quantity * buying_price) AS gross_sales FROM sold_stock_transfers
                                WHERE DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS transfer_sales
                            ON transfer_sales.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed FROM sold_stock_reversed
                                WHERE DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND sale_type='transfer'
                                GROUP BY sold_by) AS reversed_transfer
                            ON reversed_transfer.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT added_by, SUM(amount) AS expense FROM expenses
                                WHERE DATE_FORMAT(date_incurred, '%Y-%m-%d') >=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY added_by) AS expenses
                            ON expenses.added_by=a.id
                            
                            LEFT JOIN
                            (SELECT added_by, SUM(float_amount) AS cash_float FROM opening_float
                                WHERE DATE_FORMAT(date_created, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_created, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY added_by) AS float_amount
                            ON float_amount.added_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(cash) AS cash, SUM(mpesa) AS mpesa, SUM(bank) AS bank FROM sales_payments
                                WHERE DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS banked_amount
                            ON banked_amount.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(amount_reversed) AS reversed_cash_amount FROM sales_payments
                                WHERE cash > 0 AND mpesa < 1 AND bank < 1 AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS banked_reversed_cash_only
                            ON banked_reversed_cash_only.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(amount_reversed) AS reversed_mpesa_amount FROM sales_payments
                                WHERE cash < 1 AND mpesa > 0 AND bank < 1 AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS banked_reversed_mpesa_only
                            ON banked_reversed_mpesa_only.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(amount_reversed) AS reversed_bank_amount FROM sales_payments
                                WHERE cash < 1 AND mpesa < 1 AND bank > 0 AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS banked_reversed_bank_only
                            ON banked_reversed_bank_only.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(amount_reversed) AS reversed_multiple FROM sales_payments
                                WHERE DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND ((cash > 0 AND mpesa > 0) OR (cash>0 AND bank>0) OR (mpesa>0 AND bank > 0))
                                GROUP BY sold_by) AS banked_reversed_multiple
                            ON banked_reversed_multiple.sold_by=a.id

                            LEFT JOIN
                            (SELECT sold_by, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock
                                WHERE selling_price<recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS cash_discounts
                            ON cash_discounts.sold_by=a.id

                            LEFT JOIN
                            (SELECT sold_by, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock_debtors
                                WHERE selling_price<recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS debtor_discounts
                            ON debtor_discounts.sold_by=a.id

                            LEFT JOIN
                            (SELECT sold_by, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock
                                WHERE selling_price>recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS cash_overcharges
                            ON cash_overcharges.sold_by=a.id

                            LEFT JOIN
                            (SELECT sold_by, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock_debtors
                                WHERE selling_price>recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS debtor_overcharges
                            ON debtor_overcharges.sold_by=a.id

                            GROUP BY a.id";

                } else {

                    $sql = "SELECT a.id, a.username,
                            IFNULL(cash_float.float_amount, 0) AS float_amount,
                            IFNULL(cash_sales.gross_sales, 0) AS cash_sales,
                            IFNULL(cash_sales.gross_profit, 0) AS cash_profit,
                            IFNULL(reversed_cash.sales_reversed, 0) AS cash_reversed,
                            IFNULL(reversed_cash.reversed_profit, 0) AS reversed_cash_profit,
                            
                            IFNULL(debtor_sales.gross_sales, 0) AS debtor_sales,
                            IFNULL(debtor_sales.gross_profit, 0) AS debtor_profit,
                            IFNULL(reversed_debtor.sales_reversed, 0) AS debtor_reversed,
                            IFNULL(reversed_debtor.reversed_profit, 0) AS reversed_debtor_profit,
                            
                            IFNULL(transfer_sales.gross_sales, 0) AS transfer_sales,
                            IFNULL(reversed_transfer.sales_reversed, 0) AS transfer_reversed,
                            
                            IFNULL(expenses.expense, 0) AS expenses,
                            IFNULL(float_amount.cash_float, 0) AS cash_float,
                            
                            IFNULL(banked_amount.cash, 0) AS cash_banked,
                            IFNULL(banked_amount.mpesa, 0) AS mpesa_banked,
                            IFNULL(banked_amount.bank, 0) AS bank_banked,
                            
                            IFNULL(banked_reversed_cash_only.reversed_cash_amount, 0) AS reversed_cash_only,
                            IFNULL(banked_reversed_mpesa_only.reversed_mpesa_amount, 0) AS reversed_mpesa_only,
                            IFNULL(banked_reversed_bank_only.reversed_bank_amount, 0) AS reversed_bank_only,
                            IFNULL(banked_reversed_multiple.reversed_multiple, 0) AS reversed_multiple,

                            IFNULL(cash_discounts.discounts, 0) AS cash_discounts,
                            IFNULL(debtor_discounts.discounts, 0) AS debtor_discounts,

                            IFNULL(cash_overcharges.overcharges, 0) AS cash_overcharges,
                            IFNULL(debtor_overcharges.overcharges, 0) AS debtor_overcharges

                            FROM 
                            
                            (SELECT id, username, role FROM users WHERE deleted=0) AS a

                            INNER JOIN
                            (SELECT user_id FROM user_location_rights WHERE location_id='{$location_id}') AS rights
                            ON rights.user_id=a.id OR a.role='admin'
                            
                            LEFT JOIN
                            (SELECT added_by, SUM(float_amount) AS float_amount FROM opening_float
                                WHERE DATE_FORMAT(date_created, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_created, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY added_by) AS cash_float
                            ON cash_float.added_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit FROM sold_stock
                                WHERE location_id='{$location_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS cash_sales
                            ON cash_sales.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit FROM sold_stock_reversed
                                WHERE location_id='{$location_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND sale_type='cash'
                                GROUP BY sold_by) AS reversed_cash
                            ON reversed_cash.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit FROM sold_stock_debtors
                                WHERE location_id='{$location_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS debtor_sales
                            ON debtor_sales.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit FROM sold_stock_reversed
                                WHERE location_id='{$location_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND sale_type='debtor'
                                GROUP BY sold_by) AS reversed_debtor
                            ON reversed_debtor.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(unit_quantity * buying_price) AS gross_sales FROM sold_stock_transfers
                                WHERE location_id='{$location_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS transfer_sales
                            ON transfer_sales.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed FROM sold_stock_reversed
                                WHERE location_id='{$location_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND sale_type='transfer'
                                GROUP BY sold_by) AS reversed_transfer
                            ON reversed_transfer.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT added_by, SUM(amount) AS expense FROM expenses
                                WHERE DATE_FORMAT(date_incurred, '%Y-%m-%d') >=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY added_by) AS expenses
                            ON expenses.added_by=a.id
                            
                            LEFT JOIN
                            (SELECT added_by, SUM(float_amount) AS cash_float FROM opening_float
                                WHERE DATE_FORMAT(date_created, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_created, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY added_by) AS float_amount
                            ON float_amount.added_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(cash) AS cash, SUM(mpesa) AS mpesa, SUM(bank) AS bank FROM sales_payments
                                WHERE DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS banked_amount
                            ON banked_amount.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(amount_reversed) AS reversed_cash_amount FROM sales_payments
                                WHERE cash > 0 AND mpesa < 1 AND bank < 1 AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS banked_reversed_cash_only
                            ON banked_reversed_cash_only.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(amount_reversed) AS reversed_mpesa_amount FROM sales_payments
                                WHERE cash < 1 AND mpesa > 0 AND bank < 1 AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS banked_reversed_mpesa_only
                            ON banked_reversed_mpesa_only.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(amount_reversed) AS reversed_bank_amount FROM sales_payments
                                WHERE cash < 1 AND mpesa < 1 AND bank > 0 AND DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS banked_reversed_bank_only
                            ON banked_reversed_bank_only.sold_by=a.id
                            
                            LEFT JOIN
                            (SELECT sold_by, SUM(amount_reversed) AS reversed_multiple FROM sales_payments
                                WHERE DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') AND ((cash > 0 AND mpesa > 0) OR (cash>0 AND bank>0) OR (mpesa>0 AND bank > 0))
                                GROUP BY sold_by) AS banked_reversed_multiple
                            ON banked_reversed_multiple.sold_by=a.id

                            LEFT JOIN
                            (SELECT sold_by, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock
                                WHERE location_id='{$location_id}' AND selling_price<recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS cash_discounts
                            ON cash_discounts.sold_by=a.id

                            LEFT JOIN
                            (SELECT sold_by, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock_debtors
                                WHERE location_id='{$location_id}' AND selling_price<recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS debtor_discounts
                            ON debtor_discounts.sold_by=a.id

                            LEFT JOIN
                            (SELECT sold_by, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock
                                WHERE location_id='{$location_id}' AND selling_price>recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS cash_overcharges
                            ON cash_overcharges.sold_by=a.id

                            LEFT JOIN
                            (SELECT sold_by, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock_debtors
                                WHERE location_id='{$location_id}' AND selling_price>recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                                GROUP BY sold_by) AS debtor_overcharges
                            ON debtor_overcharges.sold_by=a.id

                            GROUP BY a.id";
                }
                        
                
                $result = run_query($conn, $sql);

                $sales=[];
                $cr=0;


                $dateGrossSales = 0;
                $dateExpenses = 0;
                $dateGrossProfit = 0;
                $dateDebtors = 0;


                while($row = mysqli_fetch_assoc($result)) {
                    
                    // $user = get_user_record($row['sold_by']);

                    $sales[$cr]['user_id'] = encrypt_data($row['id']);
                    $sales[$cr]['user_name'] = $row['username'];


                    $float_amount = $row['float_amount'];
                    $cash_sales = $row['cash_sales'];
                    $debtor_sales = $row['debtor_sales'];
                    $transfer_sales = $row['transfer_sales'];

                    $cash_sales_reversed = $row['cash_reversed'];
                    $debtor_sales_reversed = $row['debtor_reversed'];
                    $transfer_sales_reversed = $row['transfer_reversed'];

                    $sales[$cr]['debtor_reversed'] = $debtor_sales_reversed;
                    $sales[$cr]['transfer_reversed'] = $transfer_sales_reversed;

                    $gross_sales = $cash_sales + $debtor_sales - $cash_sales_reversed - $debtor_sales_reversed;
                    $gross_transfer_sales = $transfer_sales - $transfer_sales_reversed;

                    $sales[$cr]['gross_sales'] = $gross_sales;
                    $sales[$cr]['transfer_sales'] = $gross_transfer_sales;
                    $sales[$cr]['debtor_sales'] = $debtor_sales;
                    $sales[$cr]['expenses'] = $row['expenses'];


                    $cash_profit = $row['cash_profit'];
                    $reversed_cash_profit = $row['reversed_cash_profit'];
                    $debtor_profit = $row['debtor_profit'];
                    $reversed_debtor_profit = $row['reversed_debtor_profit'];

                    $gross_profit = ($cash_profit - $reversed_cash_profit) + ($debtor_profit - $reversed_debtor_profit);
                    $sales[$cr]['gross_profit'] = $gross_profit;


                    $cash_banked = $row['cash_banked'];
                    $reversed_banked_cash = $row['reversed_cash_only'];
                    $sales[$cr]['opening_float'] = $float_amount;
                    $sales[$cr]['cash_banked'] = $cash_banked;
                    $sales[$cr]['cash_reversed'] = $reversed_banked_cash;

                    $float = $row['cash_float'];
                    $sales[$cr]['float'] = $float;

                    $mpesa_banked = $row['mpesa_banked'];
                    $reversed_banked_mpesa = $row['reversed_mpesa_only'];
                    $sales[$cr]['mpesa_banked'] = $mpesa_banked;
                    $sales[$cr]['mpesa_reversed'] = $reversed_banked_mpesa;

                    $bank_banked = $row['bank_banked'];
                    $reversed_banked_bank = $row['reversed_bank_only'];
                    $sales[$cr]['bank_banked'] = $bank_banked;
                    $sales[$cr]['bank_reversed'] = $reversed_banked_bank;


                    $sales[$cr]['reversed_multiple'] = $row['reversed_multiple'];


                    // get any added stock for this user
                    $user_added_stock = get_period_user_added_stock($conn, $row['id'], $from, $to);
                    
                    $sales[$cr]['added_stock'] = $user_added_stock;

                    // get any paid debtors
                    $user_paid_debtors = get_period_user_paid_debtors($conn, $row['id'], $from, $to);

                    $sales[$cr]['paid_debtors'] = $user_paid_debtors;

                    
                    $sales[$cr]['cash_discounts'] = $row['cash_discounts'];
                    $sales[$cr]['debtor_discounts'] = $row['debtor_discounts'];
                    
                    $sales[$cr]['cash_overcharges'] = $row['cash_overcharges'];
                    $sales[$cr]['debtor_overcharges'] = $row['debtor_overcharges'];


                    $paid_suppliers = get_paid_suppliers($conn, $row['id'], $from, $to);
                    $sales[$cr]['paid_suppliers'] = $paid_suppliers;


                    $cr++;


                    $dateGrossSales += $gross_sales;
                    $dateExpenses += $row['expenses'];
                    $dateGrossProfit += $gross_profit;
                    $dateDebtors += ($row['debtor_sales'] - $row['debtor_reversed']);


                }


                // get the chart data
                $chart_data = [];
                if($chart=='1') {
                    // $chart_data = get_period_chart_data($conn, $from, $to);
                }
                

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "sales" => $dateGrossSales,
                    "expenses" => $dateExpenses,
                    "grossProfit" => $dateGrossProfit,
                    "debtors" => $dateDebtors,
                    "userSales" => $sales,
                    "chart_data" => $chart_data
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            // }

        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-periodic-all-sales-transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));

        // $today = $date;

        if(check_device_mac($conn, $mac)) {

            // get gross sales, gross profit, expenses,
            // debtors, transfers, cash, bank, mpesa,
            // reversed amounts
            // opening float per user
            if($location_id=='0') {

            
                $sql = "SELECT transaction_type, customer, transaction_code, transaction_total, payment_channel, cash, mpesa, bank, reversed, sold_by, profit, date_sold
                        FROM

                        (SELECT 'cash' AS transaction_type, IFNULL(b.customer_name, '') AS customer, a.transaction_code, IFNULL(a.total, 0) AS transaction_total, b.payment_channel AS payment_channel,
                                                
                            IFNULL(b.cash, 0) AS cash, IFNULL(b.mpesa, 0) AS mpesa,
                            IFNULL(b.bank, 0) AS bank,
                            IFNULL(b.amount_reversed, 0) AS reversed,
                            c.username AS sold_by,
                            a.profit,
                            a.date_sold AS date_sold
                        FROM
                        (SELECT SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code, sales_payments_id, (SUM(unit_quantity*selling_price)-SUM(unit_quantity*buying_price)) AS profit, sold_by FROM sold_stock
                            WHERE DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY date_sold, transaction_id, transaction_code, sales_payments_id, sold_by) AS a
                        LEFT JOIN
                        (SELECT id, IFNULL(customer_name, '') AS customer_name, IFNULL(cash, 0) AS cash, IFNULL(mpesa, 0) AS mpesa, IFNULL(bank, 0) AS bank, IFNULL(amount_reversed, 0) AS amount_reversed, IFNULL(payment_channel, '') AS payment_channel
                            FROM sales_payments
                            WHERE DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS b
                        ON a.sales_payments_id = b.id
                        
                        LEFT JOIN
                        (SELECT id, username FROM users) AS c
                        ON c.id=a.sold_by
                                            

                        UNION ALL

                        SELECT  'debtor' AS transaction_type, IFNULL(b.name, '') AS customer, a.transaction_code, IFNULL(a.total, 0) AS transaction_total, '-' AS payment_channel,
                                0 AS cash, 0 AS mpesa, 0 AS bank, IFNULL(r.reversed, 0) AS reversed,
                                c.username AS sold_by,
                                a.profit,
                                a.date_sold AS date_sold
                                
                        FROM
                        (SELECT customer_id, SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code, (SUM(unit_quantity*selling_price)-SUM(unit_quantity*buying_price)) AS profit, sold_by FROM sold_stock_debtors
                            WHERE DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY customer_id, date_sold, transaction_id, transaction_code, sold_by) AS a
                            
                        LEFT JOIN
                        (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        
                        LEFT JOIN
                        (SELECT transaction_code, SUM(quantity_returned*selling_price) AS reversed FROM sold_stock_reversed WHERE sale_type='debtor' GROUP BY transaction_code) AS r
                        ON r.transaction_code=a.transaction_code
                        
                        LEFT JOIN
                        (SELECT id, username FROM users) AS c
                        ON c.id=a.sold_by
                            
                        UNION ALL
                            
                        SELECT 'reversal' AS transaction_type, IFNULL(b.name, '') AS customer, a.transaction_code, 0 AS transaction_total, '-' AS payment_channel,
                        0 AS cash, 0 AS mpesa, 0 AS bank, IFNULL(a.reversed, 0) AS reversed,
                            c.username AS sold_by,
                            0-a.profit AS profit,
                            a.date_returned AS date_sold
                            
                        FROM
                        (SELECT id, transaction_code, SUM(quantity_returned*selling_price) AS reversed, SUM((selling_price-buying_price) * quantity_returned) AS profit, date_returned, sold_by, customer_id FROM sold_stock_reversed
                            WHERE DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY id, transaction_code, date_returned, sold_by, customer_id) AS a
                            
                        LEFT JOIN
                        (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        
                        LEFT JOIN
                        (SELECT id, username FROM users) AS c
                        ON c.id=a.sold_by
                            
                        UNION ALL
                        
                        SELECT 'transfer' AS transaction_type, IFNULL(b.name, '') AS customer, a.transaction_code, IFNULL(a.total, 0) AS transaction_total, '-' AS payment_channel,
                        0 AS cash, 0 AS mpesa, 0 AS bank, 0 AS reversed,
                            c.username AS sold_by,
                            0 AS profit,
                            a.date_transfered AS date_sold
                        FROM
                        (SELECT customer_id, SUM(unit_quantity*buying_price) AS total, date_transfered, transaction_id, transaction_code, sold_by FROM sold_stock_transfers
                            WHERE DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                            AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY customer_id, date_transfered, transaction_id, transaction_code, sold_by) AS a
                            
                        LEFT JOIN (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        
                        LEFT JOIN
                        (SELECT id, username FROM users) AS c
                        ON c.id=a.sold_by
                        
                        LEFT JOIN
                        (SELECT transaction_code, SUM(quantity_returned*selling_price) AS reversed FROM sold_stock_reversed WHERE sale_type='transfer' GROUP BY transaction_code) AS r
                        ON r.transaction_code=a.transaction_code
                            
                        UNION ALL
                        
                        SELECT 'debtor payment' AS transaction_type, IFNULL(b.name, '') AS customer, 'debtor payment' AS transaction_code, a.cash+a.mpesa+a.bank AS transaction_total, '-' AS payment_channel,
                                a.cash, a.mpesa, a.bank, 0 AS reversed,
                                c.username AS sold_by,
                                0 AS profit,
                                a.date_created AS date_sold
                        FROM 
                        
                        (SELECT id, customer_id, cash, mpesa, bank, amount_credited, date_created, added_by FROM debtors_payments WHERE cash+mpesa+bank > 0 AND DATE_FORMAT(date_created, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_created, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS a
                        
                        LEFT JOIN
                        (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        
                        LEFT JOIN
                        (SELECT id, username FROM users) AS c
                        ON c.id=a.added_by) AS a ORDER BY a.date_sold DESC";

            } else {
                
                $sql = "SELECT transaction_type, customer, transaction_code, transaction_total, payment_channel, cash, mpesa, bank, reversed, sold_by, profit, date_sold
                        FROM

                        (SELECT 'cash' AS transaction_type, IFNULL(b.customer_name, '') AS customer, a.transaction_code, IFNULL(a.total, 0) AS transaction_total, b.payment_channel AS payment_channel,
                                                
                            IFNULL(b.cash, 0) AS cash, IFNULL(b.mpesa, 0) AS mpesa,
                            IFNULL(b.bank, 0) AS bank,
                            IFNULL(b.amount_reversed, 0) AS reversed,
                            c.username AS sold_by,
                            a.profit,
                            a.date_sold AS date_sold
                        FROM
                        (SELECT SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code, sales_payments_id, (SUM(unit_quantity*selling_price)-SUM(unit_quantity*buying_price)) AS profit, sold_by FROM sold_stock
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY date_sold, transaction_id, transaction_code, sales_payments_id, sold_by) AS a
                        LEFT JOIN
                        (SELECT id, IFNULL(customer_name, '') AS customer_name, IFNULL(cash, 0) AS cash, IFNULL(mpesa, 0) AS mpesa, IFNULL(bank, 0) AS bank, IFNULL(amount_reversed, 0) AS amount_reversed, IFNULL(payment_channel, '') AS payment_channel
                            FROM sales_payments
                            WHERE DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS b
                        ON a.sales_payments_id = b.id
                        
                        LEFT JOIN
                        (SELECT id, username, role FROM users) AS c
                        ON c.id=a.sold_by
                                            

                        UNION ALL

                        SELECT  'debtor' AS transaction_type, IFNULL(b.name, '') AS customer, a.transaction_code, IFNULL(a.total, 0) AS transaction_total, '-' AS payment_channel,
                                0 AS cash, 0 AS mpesa, 0 AS bank, IFNULL(r.reversed, 0) AS reversed,
                                c.username AS sold_by,
                                a.profit,
                                a.date_sold AS date_sold
                                
                        FROM
                        (SELECT customer_id, SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code, (SUM(unit_quantity*selling_price)-SUM(unit_quantity*buying_price)) AS profit, sold_by FROM sold_stock_debtors
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY customer_id, date_sold, transaction_id, transaction_code, sold_by) AS a
                            
                        LEFT JOIN
                        (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        
                        LEFT JOIN
                        (SELECT transaction_code, SUM(quantity_returned*selling_price) AS reversed FROM sold_stock_reversed WHERE location_id='{$location_id}' AND sale_type='debtor' GROUP BY transaction_code) AS r
                        ON r.transaction_code=a.transaction_code
                        
                        LEFT JOIN
                        (SELECT id, role, username FROM users) AS c
                        ON c.id=a.sold_by
                            
                        UNION ALL
                            
                        SELECT 'reversal' AS transaction_type, IFNULL(b.name, '') AS customer, a.transaction_code, 0 AS transaction_total, '-' AS payment_channel,
                        0 AS cash, 0 AS mpesa, 0 AS bank, IFNULL(a.reversed, 0) AS reversed,
                            c.username AS sold_by,
                            0-a.profit AS profit,
                            a.date_returned AS date_sold
                            
                        FROM
                        (SELECT id, transaction_code, SUM(quantity_returned*selling_price) AS reversed, SUM((selling_price-buying_price) * quantity_returned) AS profit, date_returned, sold_by, customer_id FROM sold_stock_reversed
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY id, transaction_code, date_returned, sold_by, customer_id) AS a
                            
                        LEFT JOIN
                        (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        
                        LEFT JOIN
                        (SELECT id, role, username FROM users) AS c
                        ON c.id=a.sold_by
                            
                        UNION ALL
                        
                        SELECT 'transfer' AS transaction_type, IFNULL(b.name, '') AS customer, a.transaction_code, IFNULL(a.total, 0) AS transaction_total, '-' AS payment_channel,
                        0 AS cash, 0 AS mpesa, 0 AS bank, 0 AS reversed,
                            c.username AS sold_by,
                            0 AS profit,
                            a.date_transfered AS date_sold
                        FROM
                        (SELECT customer_id, SUM(unit_quantity*buying_price) AS total, date_transfered, transaction_id, transaction_code, sold_by FROM sold_stock_transfers
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                            AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                            GROUP BY customer_id, date_transfered, transaction_id, transaction_code, sold_by) AS a
                            
                        LEFT JOIN (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        
                        LEFT JOIN
                        (SELECT id, role, username FROM users) AS c
                        ON c.id=a.sold_by
                        
                        LEFT JOIN
                        (SELECT transaction_code, SUM(quantity_returned*selling_price) AS reversed FROM sold_stock_reversed WHERE location_id='{$location_id}' AND sale_type='transfer' GROUP BY transaction_code) AS r
                        ON r.transaction_code=a.transaction_code
                            
                        UNION ALL
                        
                        SELECT 'debtor payment' AS transaction_type, IFNULL(b.name, '') AS customer, 'debtor payment' AS transaction_code, a.cash+a.mpesa+a.bank AS transaction_total, '-' AS payment_channel,
                                a.cash, a.mpesa, a.bank, 0 AS reversed,
                                c.username AS sold_by,
                                0 AS profit,
                                a.date_created AS date_sold
                        FROM 
                        
                        (SELECT id, customer_id, cash, mpesa, bank, amount_credited, date_created, added_by FROM debtors_payments WHERE cash+mpesa+bank > 0 AND DATE_FORMAT(date_created, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_created, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS a
                        
                        LEFT JOIN
                        (SELECT id, name FROM customers) AS b
                        ON a.customer_id = b.id
                        
                        LEFT JOIN
                        (SELECT id, role, username FROM users) AS c
                        ON c.id=a.added_by
                        
                        INNER JOIN user_location_rights lr
                        ON c.role='admin' OR (lr.user_id=c.id AND lr.location_id='{$location_id}')
                        
                        ) AS a


                        GROUP BY a.transaction_type, a.customer, a.transaction_code, a.transaction_total, a.payment_channel, a.cash, a.mpesa, a.bank, a.reversed, a.sold_by, a.profit, a.date_sold
                        
                        ORDER BY a.date_sold DESC";

            }

            $result = run_query($conn, $sql);

            $sales=[];
            $cr=0;


            $total_sales = 0;
            $total_cash = 0;
            $total_mpesa = 0;
            $total_bank = 0;
            $total_reversed = 0;
            $total_profit = 0;
            $total_debtors = 0;

            $reversed_cash = 0;
            $reversed_multiple_cash = 0;
            $reversed_mpesa = 0;
            $reversed_multiple_mpesa = 0;
            $reversed_bank = 0;
            $reversed_multiple_bank = 0;

            $reversed_debtor = 0;
            $reversed_transfer = 0;

            $paid_debtors_cash = 0;
            $paid_debtors_mpesa = 0;
            $paid_debtors_bank = 0;


            while($row = mysqli_fetch_assoc($result)) {
                
                // $user = get_user_record($row['sold_by']);

                $sales[$cr]['transaction_type'] = $row['transaction_type'];
                $sales[$cr]['customer'] = $row['customer'];
                $sales[$cr]['transaction_code'] = $row['transaction_code'];
                $sales[$cr]['transaction_total'] = $row['transaction_total'];
                $sales[$cr]['payment_channel'] = $row['payment_channel'];
                $sales[$cr]['cash'] = $row['cash'];
                $sales[$cr]['mpesa'] = $row['mpesa'];
                $sales[$cr]['bank'] = $row['bank'];
                $sales[$cr]['reversed'] = $row['reversed'];
                $sales[$cr]['sold_by'] = $row['sold_by'];
                $sales[$cr]['profit'] = $row['profit'];
                $sales[$cr]['date_sold'] = $row['date_sold'];

                $total_sales += $row['transaction_total'];
                $total_cash += $row['cash'];
                $total_mpesa += $row['mpesa'];
                $total_bank += $row['bank'];
                $total_reversed += $row['reversed'];
                $total_profit += $row['profit'];




                if($row['payment_channel'] == 'multiple'&&
                    $row['cash'] > 0 &&
                    $row['reversed'] > 0) {
                    $reversed_multiple_cash += $row['reversed'];
                }

                if($row['payment_channel'] == 'multiple'&&
                    $row['mpesa'] > 0 &&
                    $row['reversed'] > 0) {
                    $reversed_multiple_mpesa += $row['reversed'];
                }

                if($row['payment_channel'] == 'multiple'&&
                    $row['bank'] > 0 &&
                    $row['reversed'] > 0) {
                    $reversed_multiple_bank += $row['reversed'];
                }

                if($row['payment_channel'] == 'cash'&&
                    $row['reversed'] > 0) {
                    $reversed_cash += $row['reversed'];
                }

                if($row['payment_channel'] == 'mpesa'&&
                    $row['reversed'] > 0) {
                    $reversed_mpesa += $row['reversed'];
                }

                if($row['payment_channel'] == 'bank'&&
                    $row['reversed'] > 0) {
                    $reversed_bank += $row['reversed'];
                }

                if($row['transaction_type'] == 'debtor'&&
                    $row['reversed'] < 1) {
                    $total_debtors += $row['transaction_total'];
                }

                if($row['transaction_type'] == 'debtor'&&
                    $row['reversed'] > 0) {
                    $reversed_debtor += $row['reversed'];
                }

                if($row['transaction_type'] == 'transfer'&&
                    $row['reversed'] > 0) {
                    $reversed_transfer += $row['reversed'];
                }

                if($row['transaction_type'] == 'debtor payment'&&
                    $row['cash'] > 0) {
                    $paid_debtors_cash += $row['cash'];
                    $total_cash -= $row['cash'];
                }

                if($row['transaction_type'] == 'debtor payment'&&
                    $row['mpesa'] > 0) {
                    $paid_debtors_mpesa += $row['mpesa'];
                    $total_mpesa -= $row['mpesa'];
                }

                if($row['transaction_type'] == 'debtor payment'&&
                    $row['bank'] > 0) {
                    $paid_debtors_bank += $row['bank'];
                    $total_bank -= $row['bank'];
                }



               
                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "sales" => $sales,
                "total_cash" => $total_cash,
                "paid_debtors_cash" => $paid_debtors_cash,
                "reversed_cash" => $reversed_cash,
                "reversed_multiple_cash" => $reversed_multiple_cash,
                "total_mpesa" => $total_mpesa,
                "paid_debtors_mpesa" => $paid_debtors_mpesa,
                "reversed_mpesa" => $reversed_mpesa,
                "reversed_multiple_mpesa" => $reversed_multiple_mpesa,
                "total_bank" => $total_bank,
                "paid_debtors_bank" => $paid_debtors_bank,
                "reversed_bank" => $reversed_bank,
                "reversed_multiple_bank" => $reversed_multiple_bank,
                "total_debtors" => $total_debtors,
                "reversed_debtor" => $reversed_debtor,
                "total_reversed" => $total_reversed,
                "total_profit" => $total_profit
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get_user_period_cash_transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        // $today = $date;

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT a.transaction_code, IFNULL(a.total, 0) AS total,
                        a.date_sold AS date_sold, IFNULL(b.customer_name, '') AS customer,
                        a.sales_payments_id AS payment_id,
                        IFNULL(b.cash, 0) AS cash, IFNULL(b.mpesa, 0) AS mpesa,
                        IFNULL(b.bank, 0) AS bank,
                        IFNULL(b.amount_reversed, 0) AS reversed,
                        b.payment_channel AS payment_channel
                    FROM
                    (SELECT SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code, sales_payments_id FROM sold_stock
                        WHERE sold_by='{$user_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY date_sold, transaction_id, transaction_code, sales_payments_id) AS a
                    LEFT JOIN
                    (SELECT id, IFNULL(customer_name, '') AS customer_name, IFNULL(cash, 0) AS cash, IFNULL(mpesa, 0) AS mpesa, IFNULL(bank, 0) AS bank, IFNULL(amount_reversed, 0) AS amount_reversed, IFNULL(payment_channel, '') AS payment_channel
                        FROM sales_payments
                        WHERE DATE_FORMAT(date_paid, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_paid, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS b
                    ON a.sales_payments_id = b.id
                    ORDER BY a.transaction_id DESC";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $customer['id'] = encrypt_data('0');
                $customer['name'] = $row['customer'];

                $payment['id'] = encrypt_data($row['payment_id']);
                $payment['payment_channel'] = $row['payment_channel'];
                $payment['cash'] = $row['cash'];
                $payment['mpesa'] = $row['mpesa'];
                $payment['bank'] = $row['bank'];
                $payment['reversed'] = $row['reversed'];

                $total = $row['total'] - $row['reversed'];

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['customer'] = $customer;
                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['items'] = [];
                $transactions[$cr]['payment'] = $payment;

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-period-profit-report') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));

        if(check_device_mac($conn, $mac)) {

            $sql = "";

            if($location_id=='0') {

                $sql = "SELECT (IFNULL(sales.sales, 0) + IFNULL(debtor_sold.sales, 0) - IFNULL(reversed.reversed, 0) - IFNULL(reversed2.reversed, 0)) AS gross_sales,
                                (IFNULL(sales.profit, 0) + IFNULL(debtor_sold.profit, 0) - IFNULL(reversed.reversed_profit, 0) - IFNULL(reversed2.reversed_profit, 0)) AS gross_profit,
                                IFNULL(expenses.expenses, 0) AS expenses, (IFNULL(sales_tax.input_tax, 0) - IFNULL(reversed_tax.input_tax, 0)) AS input_tax, (IFNULL(sales_tax.output_tax, 0) - IFNULL(reversed_tax.output_tax, 0)) AS output_tax  FROM

                            (SELECT SUM(unit_quantity*selling_price) AS sales, SUM((selling_price-buying_price)*unit_quantity) AS profit FROM `sold_stock` WHERE DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS sales
                            LEFT JOIN
                            (SELECT SUM(unit_quantity*selling_price) AS sales, SUM((selling_price-buying_price)*unit_quantity) AS profit FROM `sold_stock_debtors` WHERE DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS debtor_sold
                            ON
                            debtor_sold.sales>=0
                            LEFT JOIN
                            (SELECT SUM(cash+mpesa+bank) AS sales FROM debtors_payments WHERE DATE_FORMAT(date_of_payment, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_of_payment, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS debtor_sales
                            ON debtor_sales.sales>=0
                        
                        LEFT JOIN
                        (SELECT IFNULL(SUM(quantity_returned*selling_price), 0) AS reversed, IFNULL(SUM((selling_price-buying_price)*quantity_returned), 0) AS reversed_profit FROM sold_stock_reversed WHERE sale_type='cash' AND DATE_FORMAT(date_returned, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS reversed
                        ON reversed.reversed >=0

                        LEFT JOIN
                        (SELECT IFNULL(SUM(quantity_returned*selling_price), 0) AS reversed, IFNULL(SUM((selling_price-buying_price)*quantity_returned), 0) AS reversed_profit FROM sold_stock_reversed WHERE sale_type='debtor' AND DATE_FORMAT(date_returned, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS reversed2
                        ON reversed2.reversed >=0
                        
                        LEFT JOIN
                        (SELECT IFNULL(SUM(amount), 0) AS expenses FROM expenses WHERE DATE_FORMAT(date_created, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_created, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS expenses
                        ON expenses.expenses >=0

                        LEFT JOIN
                        (SELECT 0 AS input_tax,
                                0 AS output_tax
                        FROM sold_stock WHERE DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS sales_tax
                        ON sales_tax.input_tax>=0
                                    
                        LEFT JOIN
                        (SELECT 0 AS input_tax,
                                0 AS output_tax
                                FROM sold_stock_reversed WHERE transaction_code LIKE 'CT%' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS reversed_tax
                        ON reversed_tax.input_tax >= 0";

            } else {

                $sql = "SELECT (IFNULL(sales.sales, 0) + IFNULL(debtor_sold.sales, 0) - IFNULL(reversed.reversed, 0) - IFNULL(reversed2.reversed, 0)) AS gross_sales,
                                (IFNULL(sales.profit, 0) + IFNULL(debtor_sold.profit, 0) - IFNULL(reversed.reversed_profit, 0) - IFNULL(reversed2.reversed_profit, 0)) AS gross_profit,
                                IFNULL(expenses.expenses, 0) AS expenses,
                                (IFNULL(sales_tax.input_tax, 0) - IFNULL(reversed_tax.input_tax, 0)) AS input_tax,
                                (IFNULL(sales_tax.output_tax, 0) - IFNULL(reversed_tax.output_tax, 0)) AS output_tax 
                                FROM

                            (SELECT SUM(unit_quantity*selling_price) AS sales, SUM((selling_price-buying_price)*unit_quantity) AS profit FROM `sold_stock`
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS sales
                            LEFT JOIN
                            (SELECT SUM(unit_quantity*selling_price) AS sales, SUM((selling_price-buying_price)*unit_quantity) AS profit FROM `sold_stock_debtors`
                            WHERE location_id='{$location_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS debtor_sold
                            ON
                            debtor_sold.sales>=0
                            LEFT JOIN
                            (SELECT SUM(cash+mpesa+bank) AS sales FROM debtors_payments WHERE DATE_FORMAT(date_of_payment, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_of_payment, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS debtor_sales
                            ON debtor_sales.sales>=0
                        
                        LEFT JOIN
                        (SELECT IFNULL(SUM(quantity_returned*selling_price), 0) AS reversed, IFNULL(SUM((selling_price-buying_price)*quantity_returned), 0) AS reversed_profit FROM sold_stock_reversed
                        WHERE location_id='{$location_id}' AND sale_type='cash' AND DATE_FORMAT(date_returned, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS reversed
                        ON reversed.reversed >=0

                        LEFT JOIN
                        (SELECT IFNULL(SUM(quantity_returned*selling_price), 0) AS reversed, IFNULL(SUM((selling_price-buying_price)*quantity_returned), 0) AS reversed_profit FROM sold_stock_reversed
                        WHERE location_id='{$location_id}' AND sale_type='debtor' AND DATE_FORMAT(date_returned, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS reversed2
                        ON reversed2.reversed >=0
                        
                        LEFT JOIN
                        (SELECT IFNULL(SUM(amount), 0) AS expenses FROM expenses
                        WHERE added_by IN (SELECT user_id FROM user_location_rights
                        WHERE location_id='{$location_id}') AND
                        DATE_FORMAT(date_created, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_created, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')) AS expenses
                        ON expenses.expenses >=0

                        LEFT JOIN
                        (SELECT 0 AS input_tax,
                                0 AS output_tax FROM sold_stock
                                WHERE location_id='{$location_id}' AND DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS sales_tax
                        ON sales_tax.input_tax>=0
                                    
                        LEFT JOIN
                        (SELECT 0 AS input_tax,
                                0 AS output_tax
                                FROM sold_stock_reversed
                                WHERE location_id='{$location_id}' AND transaction_code LIKE 'CT%' AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')) AS reversed_tax
                        ON reversed_tax.input_tax >= 0";

            }

            $result = run_query($conn, $sql);

            $record = mysqli_fetch_assoc($result);

            $gross_sales = $record['gross_sales'];
            $gross_profit = $record['gross_profit'];
            $expenses = $record['expenses'];
            $input_tax = $record['input_tax'];
            $output_tax = $record['output_tax'];

            $top_products = get_top_selling_products_period($conn, $from, $to, $location_id);

            $sales = get_period_sales_profit($conn, $from, $to, $location_id);
            $allExpenses = get_period_expenses($conn, $from, $to, $location_id);


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "gross_sales" => $gross_sales,
                "gross_profit" => $gross_profit,
                "expenses" => $expenses,
                "input_tax" => $input_tax,
                "output_tax" => $output_tax,
                "top_products" => $top_products,
                "sales" => $sales,
                "allExpenses" => $allExpenses
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get_reversal_record') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $code = mysqli_escape_string($conn, decrypt_data($request->code));
        
        // $today = $date;

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM sold_stock_reversed WHERE
                    transaction_code='{$code}'
                    ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr = 0;

            while($row = mysqli_fetch_assoc($result)) {

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $transactions[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $transactions[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);
                $transactions[$cr]['qty_returned'] =  $row['quantity_returned'];
                $transactions[$cr]['selling_price'] =  $row['selling_price'];
                $transactions[$cr]['total'] =  $row['selling_price'] * $row['quantity_returned'];
                $transactions[$cr]['date_of_sale'] =  $row['date_returned'];

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_user_period_sale_type_reversed_transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        $type = mysqli_escape_string($conn, decrypt_data($request->type));
        
        // $today = $date;

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM sold_stock_reversed WHERE
                    sold_by='{$user_id}'
                    AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                    AND transaction_code IN (SELECT transaction_code FROM sales_payments WHERE payment_channel='{$type}')
                    ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr = 0;

            while($row = mysqli_fetch_assoc($result)) {

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $transactions[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $transactions[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);
                $transactions[$cr]['qty_returned'] =  $row['quantity_returned'];
                $transactions[$cr]['selling_price'] =  $row['selling_price'];
                $transactions[$cr]['total'] =  $row['selling_price'] * $row['quantity_returned'];
                $transactions[$cr]['date_of_sale'] =  $row['date_returned'];

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_user_period_debtor_transfer_reversed_transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        $type = mysqli_escape_string($conn, decrypt_data($request->type));
        
        // $today = $date;

        if(check_device_mac($conn, $mac)) {

            // $table = $type==='debtor' ? 'sold_stock_debtors' : 'sold_stock_transfers';
            // $column = $type==='debtor' ? 'date_sold' : 'date_transfered';
            

            $sql = "SELECT * FROM sold_stock_reversed WHERE
                    sold_by='{$user_id}' AND sale_type='{$type}'
                    AND DATE_FORMAT(date_returned, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                    AND DATE_FORMAT(date_returned, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                    ";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $transactions[$cr]['customer'] = get_debtor_record($conn, $row['customer_id'])['name'];
                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $transactions[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $transactions[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);
                $transactions[$cr]['qty_returned'] =  $row['quantity_returned'];
                $transactions[$cr]['selling_price'] =  $row['selling_price'];
                $transactions[$cr]['total'] =  $row['selling_price'] * $row['quantity_returned'];
                $transactions[$cr]['date_of_sale'] =  $row['date_returned'];

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-all-users-period-expenses') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        
        if(check_device_mac($conn, $mac)) {

            // $today = $date;


            $sql = "SELECT * FROM expenses WHERE
                    DATE_FORMAT(date_incurred, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                    DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $expenses = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {

                
                $user_name = get_user_record($conn, $row['added_by'])['name'];

                $expenses[$cr]['id'] = encrypt_data($row['id']);
                $expenses[$cr]['purpose'] = $row['purpose'];
                $expenses[$cr]['paid_to'] = $row['paid_to'];
                $expenses[$cr]['channel'] = $row['payment_channel'];
                $expenses[$cr]['amount'] = $row['amount'];
                $expenses[$cr]['date_created'] = $row['date_created'];
                $expenses[$cr]['date_incurred'] = $row['date_incurred'];
                $expenses[$cr]['added_by'] = $user_name;

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "expenses" => $expenses
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-user-period-expenses') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        
        if(check_device_mac($conn, $mac)) {

            // $today = $date;

            $user_name = get_user_record($conn, $user_id)['name'];

            $sql = "SELECT * FROM expenses WHERE added_by='{$user_id}' AND
                    DATE_FORMAT(date_incurred, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                    DATE_FORMAT(date_incurred, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d') ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $expenses = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $expenses[$cr]['id'] = encrypt_data($row['id']);
                $expenses[$cr]['purpose'] = $row['purpose'];
                $expenses[$cr]['paid_to'] = $row['paid_to'];
                $expenses[$cr]['channel'] = $row['payment_channel'];
                $expenses[$cr]['amount'] = $row['amount'];
                $expenses[$cr]['date_created'] = $row['date_created'];
                $expenses[$cr]['date_incurred'] = $row['date_incurred'];
                $expenses[$cr]['added_by'] = $user_name;

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "expenses" => $expenses
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_user_period_debtors_transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        // $today = $date;

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total,
                        a.date_sold AS date_sold, IFNULL(b.name, '') AS customer
                    FROM
                    (SELECT customer_id, SUM(unit_quantity*selling_price) AS total, date_sold, transaction_id, transaction_code FROM sold_stock_debtors
                        WHERE sold_by='{$user_id}' AND
                        DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY date_sold, transaction_id, transaction_code, customer_id) AS a
                    INNER JOIN
                    (SELECT id, name FROM customers) AS b
                    ON a.customer_id = b.id
                    ORDER BY a.transaction_id DESC";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $customer['id'] = encrypt_data($row['customer_id']);
                $customer['name'] = $row['customer'];

                $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                $total = $row['total'] - $reversed_amount;

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['customer'] = $customer;
                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['items'] = [];
                $transactions[$cr]['reversed'] = $reversed_amount;

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_user_period_transfers_transactions') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        // $today = $date;

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT a.customer_id, a.transaction_code, IFNULL(a.total, 0) AS total, a.date_transfered AS date_sold, IFNULL(b.name, '') AS customer
                    FROM
                    (SELECT customer_id, SUM(unit_quantity*buying_price) AS total, date_transfered, transaction_id, transaction_code FROM sold_stock_transfers
                        WHERE sold_by='{$user_id}'
                        AND DATE_FORMAT(date_transfered, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d')
                        AND DATE_FORMAT(date_transfered, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                        GROUP BY customer_id, date_transfered, transaction_id, transaction_code) AS a
                    INNER JOIN (SELECT id, name FROM customers) AS b
                    ON a.customer_id = b.id
                    ORDER BY a.transaction_id DESC";
            $result = run_query($conn, $sql);

            $transactions=[];
            $cr=0;

            while($row = mysqli_fetch_assoc($result)) {

                $customer['id'] = encrypt_data($row['customer_id']);
                $customer['name'] = $row['customer'];

                $reversed_amount =  get_transaction_code_debtor_reversed_total($conn, $row['transaction_code']);

                $total = $row['total'] - $reversed_amount;

                $transactions[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $transactions[$cr]['transaction_total'] = $total;
                $transactions[$cr]['customer'] = $customer;
                $transactions[$cr]['date_of_sale'] = $row['date_sold'];
                $transactions[$cr]['items'] = [];
                $transactions[$cr]['reversed'] = $reversed_amount;

                $cr++;

            }


            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "transactions" => $transactions
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-user-period-cash-discount-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        
        if(check_device_mac($conn, $mac)) {

            // $today = $date;

            $user_name = get_user_record($conn, $user_id)['name'];

            $sql = "SELECT * FROM sold_stock WHERE selling_price<recom_selling_price AND sold_by='{$user_id}' AND
                    DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                    ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $items[$cr]['customer'] = get_payment_record($conn, $row['sales_payments_id'])['customer_name'];

                $items[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $items[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $items[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $items[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);

                
                $items[$cr]['unit_qty'] = $row['unit_quantity'];
                $items[$cr]['recom_selling_price'] = $row['recom_selling_price'];
                $items[$cr]['selling_price'] = $row['selling_price'];
                $items[$cr]['discount'] = ($row['recom_selling_price'] - $row['selling_price']) * $row['unit_quantity'];
                $items[$cr]['date_sold'] = $row['date_sold'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-user-period-debtor-discount-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        if(check_device_mac($conn, $mac)) {

            // $today = $date;

            $user_name = get_user_record($conn, $user_id)['name'];

            $sql = "SELECT * FROM sold_stock_debtors WHERE selling_price<recom_selling_price AND sold_by='{$user_id}' AND
                    DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                    ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $items[$cr]['customer'] = get_debtor_record($conn, $row['customer_id'])['name'];

                $items[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $items[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $items[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $items[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);

                
                $items[$cr]['unit_qty'] = $row['unit_quantity'];
                $items[$cr]['recom_selling_price'] = $row['recom_selling_price'];
                $items[$cr]['selling_price'] = $row['selling_price'];
                $items[$cr]['discount'] = ($row['recom_selling_price'] - $row['selling_price']) * $row['unit_quantity'];
                $items[$cr]['date_sold'] = $row['date_sold'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-user-period-cash-overcharged-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        
        if(check_device_mac($conn, $mac)) {

            // $today = $date;

            $user_name = get_user_record($conn, $user_id)['name'];

            $sql = "SELECT * FROM sold_stock WHERE selling_price>recom_selling_price AND sold_by='{$user_id}' AND
                    DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                    ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $items[$cr]['customer'] = get_payment_record($conn, $row['sales_payments_id'])['customer_name'];

                $items[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $items[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $items[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $items[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);

                
                $items[$cr]['unit_qty'] = $row['unit_quantity'];
                $items[$cr]['recom_selling_price'] = $row['recom_selling_price'];
                $items[$cr]['selling_price'] = $row['selling_price'];
                $items[$cr]['overcharge'] = ($row['selling_price'] - $row['recom_selling_price']) * $row['unit_quantity'];
                $items[$cr]['date_sold'] = $row['date_sold'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-user-period-debtor-overcharged-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        
        
        if(check_device_mac($conn, $mac)) {

            // $today = $date;

            $user_name = get_user_record($conn, $user_id)['name'];

            $sql = "SELECT * FROM sold_stock_debtors WHERE selling_price>recom_selling_price AND sold_by='{$user_id}' AND
                    DATE_FORMAT(date_sold, '%Y-%m-%d') >= DATE_FORMAT('{$from}', '%Y-%m-%d') AND DATE_FORMAT(date_sold, '%Y-%m-%d') <= DATE_FORMAT('{$to}', '%Y-%m-%d')
                    ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $items = [];
            $cr=0;

            while($row=mysqli_fetch_assoc($result)) {
                $items[$cr]['customer'] = get_debtor_record($conn, $row['customer_id'])['name'];

                $items[$cr]['transaction_code'] = encrypt_data($row['transaction_code']);
                $items[$cr]['item'] = get_item_details($conn, $row['item_id']);
                $items[$cr]['unit'] = get_unit_record($conn, $row['unit_id']);
                $items[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);

                
                $items[$cr]['unit_qty'] = $row['unit_quantity'];
                $items[$cr]['recom_selling_price'] = $row['recom_selling_price'];
                $items[$cr]['selling_price'] = $row['selling_price'];
                $items[$cr]['overcharge'] = ($row['selling_price'] - $row['recom_selling_price']) * $row['unit_quantity'];
                $items[$cr]['date_sold'] = $row['date_sold'];

                $cr++;
            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


// get data for stock taking
if($action === 'get-shop-info') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            $shop_details = get_shop_details($conn, $added_by);

            $shop_details['tcode'] = encrypt_data('Stock taking');

            $value = get_stock_value($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "Shop" => $shop_details,
                "Value" => $value['total']
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }




    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-completed-stock-taking-dates') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        
        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {

                $today = get_today();

                $ongoing = check_today_stock_taking($conn);

                $sql = "";

                $today_taking = 0;

                if($ongoing) {

                    $sql = "SELECT * FROM stock_taking_dates
                            WHERE completed=1 OR (DATE_FORMAT(date_started, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') AND completed=0)
                            ORDER BY id DESC";

                    $today_taking = 1;
                    
                } else {
                    $sql = "SELECT * FROM stock_taking_dates WHERE completed=1 ORDER BY id DESC";
                }

                $result = run_query($conn, $sql);

                $dates=[];
                $cr=0;

                while($row=mysqli_fetch_assoc($result)) {

                    $items = get_stock_taking_items($conn, $row['id']);
                    $dates[$cr]['id'] = encrypt_data($row['id']);
                    $dates[$cr]['location'] = get_shop_location_record($conn, $row['location_id']);
                    $dates[$cr]['items'] = $items['items'];
                    $dates[$cr]['date_started'] = $row['date_started'];
                    $dates[$cr]['completed'] = $row['completed'];
                    $dates[$cr]['completion_time'] = $row['completion_time'];
                    $dates[$cr]['done_by'] = get_user_record($conn, $row['started_by'])['name'];
                    $dates[$cr]['today'] = $today_taking;
                    $dates[$cr]['missing'] = $items['missing'];
                    $dates[$cr]['excess'] = $items['excess'];
                    $dates[$cr]['stable'] = $items['stable'];
                    $dates[$cr]['updated'] = $row['updated_stock'];

                    $cr++;

                }

                $locations = get_shop_locations($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "dates" => $dates,
                    "locations" => $locations
                );

                echo json_encode($data_insert);
                mysqli_close($conn);

            }

        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-stock-taking-items') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $date_id = mysqli_escape_string($conn, decrypt_data($request->date_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->user_id));
        $date_started = mysqli_escape_string($conn, decrypt_data($request->date_started));
        $completion_time = mysqli_escape_string($conn, decrypt_data($request->completion_time));


        if(check_device_mac($conn, $mac)) {

            $items = get_stock_taking_items($conn, $date_id);

            $date['id'] = encrypt_data($date_id);
            $date['location'] = get_shop_location_record($conn, $location_id);
            $date['items'] = $items['items'];
            $date['date_started'] = $date_started;
            $date['completion_time'] = $completion_time;
            $date['done_by'] = get_user_record($conn, $user_id)['name'];
            $date['today'] = 0;
            $date['missing'] = $items['missing'];
            $date['excess'] = $items['excess'];
            $date['stable'] = $items['stable'];

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "date" => $date
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
            
        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'check-stock-staking-today') {
    

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        
        if(check_device_mac($conn, $mac)) {
            $ongoing = check_today_stock_taking($conn);

            if($ongoing) {

                $data_insert=array(
                    "status" => "error",
                    "message" => "Stock taking in progress!"
                );
        
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {

                $data_insert=array(
                    "status" => "success",
                    "message" => "success"
                );
        
                echo json_encode($data_insert);
                mysqli_close($conn);

            }
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-stock-taking-items-and-stock') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $date_id = mysqli_escape_string($conn, decrypt_data($request->date_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));


        if(check_device_mac($conn, $mac)) {


            // get all stock
            $location_items = get_location_items($conn, $location_id, $date_id);
            $stock =  $location_items['stock'];


            // get stock taking items for this date
            $items = get_stock_taking_items($conn, $date_id);

            
            $date['id'] = encrypt_data($date_id);
            $date['location'] = get_shop_location_record($conn, $location_id);
            $date['items'] =  $items['items'];
            $date['date_started'] = get_today();
            $date['completion_time'] = '';
            $date['done_by'] = encrypt_data($user_id);
            $date['today'] = 1;
            $date['missing'] = $items['missing'];
            $date['excess'] = $items['excess'];
            $date['stable'] = $items['stable'];

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "stock" => $stock,
                "date" => $date,
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-all-users') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {
            
            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {

                $sql = "SELECT * FROM users WHERE deleted=0";
                $result = run_query($conn, $sql);

                $users=[];
                $cr=0;

                while($row=mysqli_fetch_assoc($result)) {

                    $users[$cr]['id'] = encrypt_data($row['id']);
                    $users[$cr]['username'] = $row['username'];
                    $users[$cr]['password'] = $row['password'];
                    $users[$cr]['mobile'] = $row['mobile'];
                    $users[$cr]['role'] = $row['role'];
                    $users[$cr]['disabled'] = $row['disabled'];
                    $users[$cr]['image_url'] = $row['image_url'];

                    $roles = get_shop_user_roles($conn, $row['id']);
                    $users[$cr]['roles'] = $roles;

                    $cr++;

                }

                $locations = get_shop_locations($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "users" => $users,
                    "locations" => $locations
                );

                echo json_encode($data_insert);
                mysqli_close($conn);
            }
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get-user-location-rights') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->user_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        
        if(check_device_mac($conn, $mac)) {
            $rights = get_user_location_rights($conn, $user_id, $location_id);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "rights" => $rights
            );

            echo json_encode($data_insert);
            mysqli_close($conn);
        }


    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-business-profile') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        
        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {

                $profile = get_shop_profile_details($conn);


                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "Profile" => $profile
                );

                echo json_encode($data_insert);
                mysqli_close($conn);
            }
        }




    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



// current sales
if($action === 'get-user-today-sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        
        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $user_id);

            if(backupDatabase($conn)) {
        
                $today = get_today();

                // get gross sales, gross profit, expenses,
                // debtors, transfers, cash, bank, mpesa,
                // reversed amounts
                // opening float per user
                $sql = "SELECT a.id, a.username,
                        IFNULL(cash_sales.gross_sales, 0) AS cash_sales,
                        IFNULL(cash_sales.gross_profit, 0) AS cash_profit,
                        IFNULL(reversed_cash.sales_reversed, 0) AS cash_reversed,
                        IFNULL(reversed_cash.reversed_profit, 0) AS reversed_cash_profit,
                        
                        IFNULL(debtor_sales.gross_sales, 0) AS debtor_sales,
                        IFNULL(debtor_sales.gross_profit, 0) AS debtor_profit,
                        IFNULL(reversed_debtor.sales_reversed, 0) AS debtor_reversed,
                        IFNULL(reversed_debtor.reversed_profit, 0) AS reversed_debtor_profit,
                        
                        IFNULL(transfer_sales.gross_sales, 0) AS transfer_sales,
                        IFNULL(reversed_transfer.sales_reversed, 0) AS transfer_reversed,
                        
                        IFNULL(expenses.expense, 0) AS expenses,
                        IFNULL(float_amount.cash_float, 0) AS cash_float,
                        
                        IFNULL(banked_amount.cash, 0) AS cash_banked,
                        IFNULL(banked_amount.mpesa, 0) AS mpesa_banked,
                        IFNULL(banked_amount.bank, 0) AS bank_banked,
                        
                        IFNULL(banked_reversed_cash_only.reversed_cash_amount, 0) AS reversed_cash_only,
                        IFNULL(banked_reversed_mpesa_only.reversed_mpesa_amount, 0) AS reversed_mpesa_only,
                        IFNULL(banked_reversed_bank_only.reversed_bank_amount, 0) AS reversed_bank_only,
                        IFNULL(banked_reversed_multiple.reversed_multiple, 0) AS reversed_multiple,

                        IFNULL(cash_discounts.discounts, 0) AS cash_discounts,
                        IFNULL(debtor_discounts.discounts, 0) AS debtor_discounts,

                        IFNULL(cash_overcharges.overcharges, 0) AS cash_overcharges,
                        IFNULL(debtor_overcharges.overcharges, 0) AS debtor_overcharges
                        
                        FROM 
                        
                        (SELECT id, username FROM users WHERE id='{$user_id}') AS a
                        
                        LEFT JOIN
                        (SELECT sold_by, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit FROM sold_stock WHERE DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS cash_sales
                        ON cash_sales.sold_by=a.id
                        
                        LEFT JOIN
                        (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit FROM sold_stock_reversed
                        WHERE DATE_FORMAT(date_returned, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')
                        AND sale_type='cash' GROUP BY sold_by) AS reversed_cash
                        ON reversed_cash.sold_by=a.id
                        
                        LEFT JOIN
                        (SELECT sold_by, SUM(unit_quantity * selling_price) AS gross_sales, SUM((selling_price-buying_price) * unit_quantity) AS gross_profit FROM sold_stock_debtors WHERE DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS debtor_sales
                        ON debtor_sales.sold_by=a.id
                        
                        LEFT JOIN
                        (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed, SUM((selling_price-buying_price)*quantity_returned) AS reversed_profit FROM sold_stock_reversed
                            WHERE DATE_FORMAT(date_returned, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')
                        AND sale_type='debtor' GROUP BY sold_by) AS reversed_debtor
                        ON reversed_debtor.sold_by=a.id
                        
                        LEFT JOIN
                        (SELECT sold_by, SUM(unit_quantity * buying_price) AS gross_sales FROM sold_stock_transfers WHERE DATE_FORMAT(date_transfered, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS transfer_sales
                        ON transfer_sales.sold_by=a.id
                        
                        LEFT JOIN
                        (SELECT sold_by, SUM(quantity_returned*selling_price) AS sales_reversed FROM sold_stock_reversed
                        WHERE DATE_FORMAT(date_returned, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')
                        AND sale_type='transfer' GROUP BY sold_by) AS reversed_transfer
                        ON reversed_transfer.sold_by=a.id
                        
                        LEFT JOIN
                        (SELECT added_by, SUM(amount) AS expense FROM expenses WHERE DATE_FORMAT(date_incurred, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY added_by) AS expenses
                        ON expenses.added_by=a.id
                        
                        LEFT JOIN
                        (SELECT added_by, SUM(float_amount) AS cash_float FROM opening_float WHERE DATE_FORMAT(date_created, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY added_by) AS float_amount
                        ON float_amount.added_by=a.id
                        
                        LEFT JOIN
                        (SELECT sold_by, SUM(cash) AS cash, SUM(mpesa) AS mpesa, SUM(bank) AS bank FROM sales_payments WHERE DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS banked_amount
                        ON banked_amount.sold_by=a.id
                        
                        LEFT JOIN
                        (SELECT sold_by, SUM(amount_reversed) AS reversed_cash_amount FROM sales_payments WHERE cash > 0 AND mpesa < 1 AND bank < 1 AND DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS banked_reversed_cash_only
                        ON banked_reversed_cash_only.sold_by=a.id
                        
                        LEFT JOIN
                        (SELECT sold_by, SUM(amount_reversed) AS reversed_mpesa_amount FROM sales_payments WHERE cash < 1 AND mpesa > 0 AND bank < 1 AND DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS banked_reversed_mpesa_only
                        ON banked_reversed_mpesa_only.sold_by=a.id
                        
                        LEFT JOIN
                        (SELECT sold_by, SUM(amount_reversed) AS reversed_bank_amount FROM sales_payments WHERE cash < 1 AND mpesa < 1 AND bank > 0 AND DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS banked_reversed_bank_only
                        ON banked_reversed_bank_only.sold_by=a.id
                        
                        LEFT JOIN
                        (SELECT sold_by, SUM(amount_reversed) AS reversed_multiple FROM sales_payments
                            WHERE DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d')
                            AND ((cash > 0 AND mpesa > 0) OR (cash>0 AND bank>0) OR (mpesa>0 AND bank > 0)) GROUP BY sold_by) AS banked_reversed_multiple
                        ON banked_reversed_multiple.sold_by=a.id

                        LEFT JOIN
                        (SELECT sold_by, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock WHERE selling_price<recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS cash_discounts
                        ON cash_discounts.sold_by=a.id

                        LEFT JOIN
                        (SELECT sold_by, SUM((recom_selling_price-selling_price) * unit_quantity) AS discounts FROM sold_stock_debtors WHERE selling_price<recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS debtor_discounts
                        ON debtor_discounts.sold_by=a.id

                        LEFT JOIN
                        (SELECT sold_by, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock WHERE selling_price>recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS cash_overcharges
                        ON cash_overcharges.sold_by=a.id

                        LEFT JOIN
                        (SELECT sold_by, SUM((selling_price-recom_selling_price) * unit_quantity) AS overcharges FROM sold_stock_debtors WHERE selling_price>recom_selling_price AND DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') GROUP BY sold_by) AS debtor_overcharges
                        ON debtor_overcharges.sold_by=a.id
                        
                        
                        GROUP BY a.id";

                $result = run_query($conn, $sql);

                $sales=null;


                while($row = mysqli_fetch_assoc($result)) {
                    
                    // $user = get_user_record($row['sold_by']);

                    $sales['user_id'] = encrypt_data($row['id']);
                    $sales['user_name'] = $row['username'];


                    $cash_sales = $row['cash_sales'];
                    $debtor_sales = $row['debtor_sales'];
                    $transfer_sales = $row['transfer_sales'];

                    $cash_sales_reversed = $row['cash_reversed'];
                    $debtor_sales_reversed = $row['debtor_reversed'];
                    $transfer_sales_reversed = $row['transfer_reversed'];

                    
                    $gross_sales = $cash_sales + $debtor_sales - $cash_sales_reversed - $debtor_sales_reversed;
                    $gross_transfer_sales = $transfer_sales - $transfer_sales_reversed;

                    $sales['gross_sales'] = $gross_sales;
                    $sales['transfer_sales'] = $gross_transfer_sales;
                    $sales['debtor_sales'] = $debtor_sales;
                    $sales['expenses'] = $row['expenses'];

                    $sales['debtor_reversed'] = $debtor_sales_reversed;
                    $sales['transfer_reversed'] = $transfer_sales_reversed;

                    $cash_profit = $row['cash_profit'];
                    $reversed_cash_profit = $row['reversed_cash_profit'];
                    $debtor_profit = $row['debtor_profit'];
                    $reversed_debtor_profit = $row['reversed_debtor_profit'];

                    $gross_profit = ($cash_profit - $reversed_cash_profit) + ($debtor_profit - $reversed_debtor_profit);
                    $sales['gross_profit'] = $gross_profit;


                    $cash_banked = $row['cash_banked'];
                    $reversed_banked_cash = $row['reversed_cash_only'];
                    $sales['cash_banked'] = $cash_banked;
                    $sales['cash_reversed'] = $reversed_banked_cash;

                    $float = $row['cash_float'];
                    $sales['float'] = $float;

                    $mpesa_banked = $row['mpesa_banked'];
                    $reversed_banked_mpesa = $row['reversed_mpesa_only'];
                    $sales['mpesa_banked'] = $mpesa_banked;
                    $sales['mpesa_reversed'] = $reversed_banked_mpesa;

                    $bank_banked = $row['bank_banked'];
                    $reversed_banked_bank = $row['reversed_bank_only'];
                    $sales['bank_banked'] = $bank_banked;
                    $sales['bank_reversed'] = $reversed_banked_bank;


                    $sales['reversed_multiple'] = $row['reversed_multiple'];


                    // get any added stock for this user
                    $user_added_stock = get_date_user_added_stock($conn, $row['id'], $today);
                    
                    $sales['added_stock'] = $user_added_stock;

                    // get any paid debtors
                    $user_paid_debtors = get_date_user_paid_debtors($conn, $user_id, $today);

                    $sales['paid_debtors'] = $user_paid_debtors;

                    $sales['cash_discounts'] = $row['cash_discounts'];
                    $sales['debtor_discounts'] = $row['debtor_discounts'];
                    
                    $sales['cash_overcharges'] = $row['cash_overcharges'];
                    $sales['debtor_overcharges'] = $row['debtor_overcharges'];


                }

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "userSales" => $sales,
                    "date" => encrypt_data($today)
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            }

        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


// dashboard
if($action === 'get-dashboard-report') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            if(backupDatabase($conn)) {

                // get sales for last 7
                $weekly_sales = get_week_sales($conn);
                $monthly_sales = get_months_sales($conn);

                $top_selling_products=get_top_selling_products($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "weekly_sales" => $weekly_sales,
                    "monthly_sales" => $monthly_sales,
                    "top_selling_products" => $top_selling_products,
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            }
        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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



if($action === 'get-user-today-excel-sales-report') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        
        if(check_device_mac($conn, $mac)) {

            $today = date('Y-m-d H-i-s');

            $sql = "SELECT * FROM sold_stock WHERE DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') ORDER BY id ASC";
            $result = run_query($conn, $sql);

            $items = [];
            $cr=0;

            $code1 = '';
            $code2 = '';

            

            while($row = mysqli_fetch_assoc($result)) {
                
                
                if($cr==0) {
                    $code1=$row['transaction_code'];
                    $code2=$row['transaction_code'];

                    $item_name = get_item_details($conn, $row['item_id'])['name'];
                    $unit = get_unit_record($conn, $row['unit_id']);

                    $payment = get_payment_record($conn, $row['sales_payments_id']);

                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $payment['customer_name'] === '' ? 'Cash customer' : $payment['customer_name'];
                    $items[$cr]['Item'] = $item_name;
                    $items[$cr]['Unit'] = $unit['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    if($unit['unit_quantity']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                    }
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = get_user_record($conn, $row['sold_by'])['name'];

                    $items[$cr]['Sold by'] = $sold_by;

                } else {

                    $code2 = $row['transaction_code'];

                    if($code1 != $code2) {


                        $items[$cr]['Receipt number'] = '';
                        $items[$cr]['Customer name'] = '';
                        $items[$cr]['Item'] = '';
                        $items[$cr]['Unit'] = '';
                        $items[$cr]['Quantity'] = '';
                        $items[$cr]['Unit buying price'] = '';
                        $items[$cr]['Unit selling price'] = '';
                        $items[$cr]['Total selling price'] = '';
                        $items[$cr]['Reversed amount'] = '';
                        $items[$cr]['Profit'] = '';
                        $items[$cr]['Sold by'] = '';

                        $code1 = $code2;

                        
                        $cr++;

                    }

                    $item_name = get_item_details($conn, $row['item_id'])['name'];
                    $unit = get_unit_record($conn, $row['unit_id']);

                    $payment = get_payment_record($conn, $row['sales_payments_id']);

                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $payment['customer_name'] === '' ? 'Cash customer' : $payment['customer_name'];
                    $items[$cr]['Item'] = $item_name;
                    $items[$cr]['Unit'] = $unit['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    if($unit['unit_quantity']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;
                    }
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = get_user_record($conn, $row['sold_by'])['name'];


                    $items[$cr]['Sold by'] = $sold_by;


                }

                $cr++;

            }


            $sql = "SELECT * FROM sold_stock_debtors WHERE DATE_FORMAT(date_sold, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') ORDER BY id ASC";
            $result = run_query($conn, $sql);

            $code1 = '';
            $code2 = '';

            

            while($row = mysqli_fetch_assoc($result)) {

                if($cr==0) {
                    $code1=$row['transaction_code'];
                    $code2=$row['transaction_code'];

                    $item_name = get_item_details($conn, $row['item_id'])['name'];
                    $unit = get_unit_record($conn, $row['unit_id']);

                    $customer = get_debtor_record($conn, $row['customer_id'])['name'];

                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $customer;
                    $items[$cr]['Item'] = $item_name;
                    $items[$cr]['Unit'] = $unit['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    if($unit['unit_quantity']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;
                    }
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = get_user_record($conn, $row['sold_by'])['name'];

                    $items[$cr]['Sold by'] = $sold_by;

                } else {

                    $code2 = $row['transaction_code'];

                    if($code1 != $code2) {


                        $items[$cr]['Receipt number'] = '';
                        $items[$cr]['Customer name'] = '';
                        $items[$cr]['Item'] = '';
                        $items[$cr]['Unit'] = '';
                        $items[$cr]['Quantity'] = '';
                        $items[$cr]['Unit buying price'] = '';
                        $items[$cr]['Unit selling price'] = '';
                        $items[$cr]['Total selling price'] = '';
                        $items[$cr]['Reversed amount'] = '';
                        $items[$cr]['Profit'] = '';
                        $items[$cr]['Sold by'] = '';

                        $code1 = $code2;

                        
                        $cr++;

                    }

                    $item_name = get_item_details($conn, $row['item_id'])['name'];
                    $unit = get_unit_record($conn, $row['unit_id']);

                    $customer = get_debtor_record($conn, $row['customer_id'])['name'];

                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $customer;
                    $items[$cr]['Item'] = $item_name;
                    $items[$cr]['Unit'] = $unit['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    if($unit['unit_quantity']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;
                    }
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = get_user_record($conn, $row['sold_by'])['name'];


                    $items[$cr]['Sold by'] = $sold_by;


                }

                $cr++;

            }

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items,
                "date" => $today
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-period-excel-sales-report') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        
        
        if(check_device_mac($conn, $mac)) {

            $today = date('Y-m-d H-i-s');

            $sql = "SELECT ss.date_sold, ss.id, ss.unit_id, su.unit_quantity AS unit_qty, ss.item_id, ss.transaction_code, sp.customer_name, st.name, su.unit_name, ss.unit_quantity, ss.buying_price, ss.selling_price, us.username AS sold_by

                    FROM
                    
                    (SELECT * FROM sold_stock WHERE
                                        DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                                        DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                                        ORDER BY id DESC) AS ss
                    LEFT JOIN
                    (SELECT * FROM stock) AS st
                    ON st.id=ss.item_id
                    
                    LEFT JOIN
                    (SELECT * FROM stock_units) AS su
                    ON su.id=ss.unit_id
                    
                    LEFT JOIN
                    (SELECT * FROM sales_payments) AS sp
                    ON sp.id=ss.sales_payments_id
                    
                    LEFT JOIN
                    (SELECT * FROM users) AS us
                    ON us.id=ss.sold_by
                    
                    ORDER BY ss.id DESC limit 200
                    ";
                    
            $result = run_query($conn, $sql);

            $items = [];
            $cr=0;

            $code1 = '';
            $code2 = '';
            
           
            while($row = mysqli_fetch_assoc($result)) {

                if($cr==0) {
                    
                    $code1=$row['transaction_code'];
                    $code2=$row['transaction_code'];

                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $row['customer_name'] === '' ? 'Cash customer' : $row['customer_name'];
                    $items[$cr]['Item'] = utf8_encode($row['name']);
                    $items[$cr]['Unit'] = $row['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    //$pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    // $unit = get_unit_record($conn, $row['unit_id']);
                    $pieces_reversed = 0;
                    $amount_reversed = 0;
                    $profit_reversed = 0;
                    
                    if($row['unit_qty']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($row['unit_qty'])) * $pieces_reversed;

                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($row['unit_qty'])) * $pieces_reversed;
                    }
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = $row['sold_by'];

                    $items[$cr]['Sold by'] = $sold_by;

                    $items[$cr]['Date'] = $row['date_sold'];
                    $items[$cr]['Time'] = $row['date_sold'];
                    

                } else {

                    $code2 = $row['transaction_code'];

                    if($code1 != $code2) {

                        $items[$cr]['Receipt number'] = '';
                        $items[$cr]['Customer name'] = '';
                        $items[$cr]['Item'] = '';
                        $items[$cr]['Unit'] = '';
                        $items[$cr]['Quantity'] = '';
                        $items[$cr]['Unit buying price'] = '';
                        $items[$cr]['Unit selling price'] = '';
                        $items[$cr]['Total selling price'] = '';
                        $items[$cr]['Reversed amount'] = '';
                        $items[$cr]['Profit'] = '';
                        $items[$cr]['Sold by'] = '';
                        $items[$cr]['Date'] = '';
                        $items[$cr]['Time'] = '';

                        $code1 = $code2;
                        
                        $cr++;
                        
                    }
                    
                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $row['customer_name'] === '' ? 'Cash customer' : $row['customer_name'];
                    $items[$cr]['Item'] = utf8_encode($row['name']);
                    $items[$cr]['Unit'] = $row['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    //$pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    //$unit = get_unit_record($conn, $row['unit_id']);
                    
                    $pieces_reversed = 0;

                    $amount_reversed = 0;
                    $profit_reversed = 0;
                    if($row['unit_qty']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($row['unit_qty'])) * $pieces_reversed;
                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($row['unit_qty'])) * $pieces_reversed;
                    }
                    
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = $row['sold_by'];


                    $items[$cr]['Sold by'] = $sold_by;
                    $items[$cr]['Date'] = $row['date_sold'];
                    $items[$cr]['Time'] = $row['date_sold'];
                    
                    
                }

                $cr++;

            }


            $sql = "SELECT * FROM sold_stock_debtors WHERE
                    DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                    DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                    ORDER BY id ASC";
            $result = run_query($conn, $sql);

            $code1 = '';
            $code2 = '';

            

            while($row = mysqli_fetch_assoc($result)) {

                if($cr==0) {
                    $code1=$row['transaction_code'];
                    $code2=$row['transaction_code'];

                    $item_name = get_item_details($conn, $row['item_id'])['name'];
                    $unit = get_unit_record($conn, $row['unit_id']);

                    $customer = get_debtor_record($conn, $row['customer_id'])['name'];

                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $customer;
                    $items[$cr]['Item'] = utf8_encode($item_name);
                    $items[$cr]['Unit'] = $unit['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    if($unit['unit_quantity']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;
                    }
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = get_user_record($conn, $row['sold_by'])['name'];

                    $items[$cr]['Sold by'] = $sold_by;
                    $items[$cr]['Date'] = $row['date_sold'];
                    $items[$cr]['Time'] = $row['date_sold'];

                } else {

                    $code2 = $row['transaction_code'];

                    if($code1 != $code2) {


                        $items[$cr]['Receipt number'] = '';
                        $items[$cr]['Customer name'] = '';
                        $items[$cr]['Item'] = '';
                        $items[$cr]['Unit'] = '';
                        $items[$cr]['Quantity'] = '';
                        $items[$cr]['Unit buying price'] = '';
                        $items[$cr]['Unit selling price'] = '';
                        $items[$cr]['Total selling price'] = '';
                        $items[$cr]['Reversed amount'] = '';
                        $items[$cr]['Profit'] = '';
                        $items[$cr]['Sold by'] = '';
                        $items[$cr]['Date'] = '';
                        $items[$cr]['Time'] = '';

                        $code1 = $code2;

                        
                        $cr++;

                    }

                    $item_name = get_item_details($conn, $row['item_id'])['name'];
                    $unit = get_unit_record($conn, $row['unit_id']);

                    $customer = get_debtor_record($conn, $row['customer_id'])['name'];

                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $customer;
                    $items[$cr]['Item'] = utf8_encode($item_name);
                    $items[$cr]['Unit'] = $unit['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    if($unit['unit_quantity']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                    }
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = get_user_record($conn, $row['sold_by'])['name'];

                    $items[$cr]['Sold by'] = $sold_by;
                    $items[$cr]['Date'] = $row['date_sold'];
                    $items[$cr]['Time'] = $row['date_sold'];

                }

                $cr++;

            }


            $nonEmptyArray = [];
            $emptyArray = [];

            foreach ($items as $value) {
                if ($value['Date']=='') {
                    $emptyArray[] = $value;
                } else {
                    $nonEmptyArray[] = $value;
                }
            }

            usort($nonEmptyArray, function($a, $b) {

                if (empty($a['Date']) && empty($b['Date'])) {
                    return 0; // Both are empty, no change in order
                } elseif (empty($a['Date'])) {
                    return 1; // $a is empty, move it to the end
                } elseif (empty($b['Date'])) {
                    return -1; // $b is empty, move it to the end
                }

                $dateA = new DateTime($a['Date']);
                $dateB = new DateTime($b['Date']);
                return $dateB <=> $dateA;
            });



            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $nonEmptyArray,
                "date" => $today
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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




if($action === 'get-period-excel-sales-report-old') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));
        
        if(check_device_mac($conn, $mac)) {

            $today = date('Y-m-d H-i-s');

            $sql = "SELECT * FROM sold_stock WHERE 
                    DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                    DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                    ORDER BY id DESC";
            $result = run_query($conn, $sql);

            $items = [];
            $cr=0;

            $code1 = '';
            $code2 = '';

            

            while($row = mysqli_fetch_assoc($result)) {
                
                
                if($cr==0) {
                    $code1=$row['transaction_code'];
                    $code2=$row['transaction_code'];

                    $item_name = get_item_details($conn, $row['item_id'])['name'];
                    $unit = get_unit_record($conn, $row['unit_id']);

                    $payment = get_payment_record($conn, $row['sales_payments_id']);

                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $payment['customer_name'] === '' ? 'Cash customer' : $payment['customer_name'];
                    $items[$cr]['Item'] = $item_name;
                    $items[$cr]['Unit'] = $unit['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    if($unit['unit_quantity']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;
                    }
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = get_user_record($conn, $row['sold_by'])['name'];

                    $items[$cr]['Sold by'] = $sold_by;

                    $items[$cr]['Date'] = $row['date_sold'];
                    $items[$cr]['Time'] = $row['date_sold'];

                } else {

                    $code2 = $row['transaction_code'];

                    if($code1 != $code2) {


                        $items[$cr]['Receipt number'] = '';
                        $items[$cr]['Customer name'] = '';
                        $items[$cr]['Item'] = '';
                        $items[$cr]['Unit'] = '';
                        $items[$cr]['Quantity'] = '';
                        $items[$cr]['Unit buying price'] = '';
                        $items[$cr]['Unit selling price'] = '';
                        $items[$cr]['Total selling price'] = '';
                        $items[$cr]['Reversed amount'] = '';
                        $items[$cr]['Profit'] = '';
                        $items[$cr]['Sold by'] = '';
                        $items[$cr]['Date'] = '';
                        $items[$cr]['Time'] = '';

                        $code1 = $code2;

                        
                        $cr++;

                    }

                    $item_name = get_item_details($conn, $row['item_id'])['name'];
                    $unit = get_unit_record($conn, $row['unit_id']);

                    $payment = get_payment_record($conn, $row['sales_payments_id']);

                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $payment['customer_name'] === '' ? 'Cash customer' : $payment['customer_name'];
                    $items[$cr]['Item'] = $item_name;
                    $items[$cr]['Unit'] = $unit['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    if($unit['unit_quantity']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;
                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;
                    }
                    
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = get_user_record($conn, $row['sold_by'])['name'];


                    $items[$cr]['Sold by'] = $sold_by;
                    $items[$cr]['Date'] = $row['date_sold'];
                    $items[$cr]['Time'] = $row['date_sold'];


                }

                $cr++;

            }


            $sql = "SELECT * FROM sold_stock_debtors WHERE
                    DATE_FORMAT(date_sold, '%Y-%m-%d')>=DATE_FORMAT('{$from}', '%Y-%m-%d') AND
                    DATE_FORMAT(date_sold, '%Y-%m-%d')<=DATE_FORMAT('{$to}', '%Y-%m-%d')
                    ORDER BY id ASC";
            $result = run_query($conn, $sql);

            $code1 = '';
            $code2 = '';

            

            while($row = mysqli_fetch_assoc($result)) {

                if($cr==0) {
                    $code1=$row['transaction_code'];
                    $code2=$row['transaction_code'];

                    $item_name = get_item_details($conn, $row['item_id'])['name'];
                    $unit = get_unit_record($conn, $row['unit_id']);

                    $customer = get_debtor_record($conn, $row['customer_id'])['name'];

                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $customer;
                    $items[$cr]['Item'] = $item_name;
                    $items[$cr]['Unit'] = $unit['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    if($unit['unit_quantity']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;
                    }
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = get_user_record($conn, $row['sold_by'])['name'];

                    $items[$cr]['Sold by'] = $sold_by;
                    $items[$cr]['Date'] = $row['date_sold'];
                    $items[$cr]['Time'] = $row['date_sold'];

                } else {

                    $code2 = $row['transaction_code'];

                    if($code1 != $code2) {


                        $items[$cr]['Receipt number'] = '';
                        $items[$cr]['Customer name'] = '';
                        $items[$cr]['Item'] = '';
                        $items[$cr]['Unit'] = '';
                        $items[$cr]['Quantity'] = '';
                        $items[$cr]['Unit buying price'] = '';
                        $items[$cr]['Unit selling price'] = '';
                        $items[$cr]['Total selling price'] = '';
                        $items[$cr]['Reversed amount'] = '';
                        $items[$cr]['Profit'] = '';
                        $items[$cr]['Sold by'] = '';
                        $items[$cr]['Date'] = '';
                        $items[$cr]['Time'] = '';

                        $code1 = $code2;

                        
                        $cr++;

                    }

                    $item_name = get_item_details($conn, $row['item_id'])['name'];
                    $unit = get_unit_record($conn, $row['unit_id']);

                    $customer = get_debtor_record($conn, $row['customer_id'])['name'];

                    $items[$cr]['Receipt number'] = $row['transaction_code'];
                    $items[$cr]['Customer name'] = $customer;
                    $items[$cr]['Item'] = $item_name;
                    $items[$cr]['Unit'] = $unit['unit_name'];
                    $items[$cr]['Quantity'] = $row['unit_quantity'];
                    $items[$cr]['Unit buying price'] = number_format($row['buying_price'], 2);
                    $items[$cr]['Unit selling price'] = number_format($row['selling_price'], 2);
                    $items[$cr]['Total selling price'] = number_format($row['selling_price'] * $row['unit_quantity'], 2);

                    $pieces_reversed = get_transaction_code_item_reversed_pieces($conn, $row['transaction_code'], $row['item_id'], $row['id']);

                    if($unit['unit_quantity']==0) {
                        $amount_reversed = 0;
                        $profit_reversed = 0;
                    } else {
                        $amount_reversed = (floatval($row['selling_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                        $profit_reversed = $amount_reversed - (floatval($row['buying_price'])/floatval($unit['unit_quantity'])) * $pieces_reversed;

                    }
                    
                    $items[$cr]['Reversed amount'] = number_format($amount_reversed, 2);

                    $items[$cr]['Profit'] = number_format(((floatval($row['selling_price'])-floatval($row['buying_price']))*$row['unit_quantity'])-$profit_reversed, 2);

                    $sold_by = get_user_record($conn, $row['sold_by'])['name'];

                    $items[$cr]['Sold by'] = $sold_by;
                    $items[$cr]['Date'] = $row['date_sold'];
                    $items[$cr]['Time'] = $row['date_sold'];

                }

                $cr++;

            }

            $nonEmptyArray = [];
            $emptyArray = [];

            foreach ($items as $value) {
                if ($value['Date']=='') {
                    $emptyArray[] = $value;
                } else {
                    $nonEmptyArray[] = $value;
                }
            }

            usort($nonEmptyArray, function($a, $b) {

                if (empty($a['Date']) && empty($b['Date'])) {
                    return 0; // Both are empty, no change in order
                } elseif (empty($a['Date'])) {
                    return 1; // $a is empty, move it to the end
                } elseif (empty($b['Date'])) {
                    return -1; // $b is empty, move it to the end
                }

                $dateA = new DateTime($a['Date']);
                $dateB = new DateTime($b['Date']);
                return $dateB <=> $dateA;
            });



            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $nonEmptyArray,
                "date" => $today
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'get-stock-vat') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        
        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT SUM(((a1.buying_price-(a1.buying_price/a1.vat_percentage)) * a1.total_quantity)) AS total  FROM

                    (SELECT a.buying_price as buying_price, (1+(v.vat_percent/100)) AS vat_percentage, IFNULL(l.total_quantity,0) AS total_quantity FROM
                    
                    (SELECT id, buying_price, vat_id FROM stock WHERE deleted=0) AS a
                    LEFT JOIN
                    (SELECT id, vat_percent FROM vat_records WHERE vat_percent>0) AS v
                    ON v.id=a.vat_id
                    
                    LEFT JOIN
                    (SELECT item_id, SUM(location_quantity) AS total_quantity FROM stock_locations WHERE deleted=0 GROUP BY item_id) AS l
                    ON l.item_id=a.id) AS a1";
            $result = run_query($conn, $sql);

            $record = mysqli_fetch_assoc($result);

            $total = $record['total'];

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "total" => $total
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }

        
    } catch (Exception $e) {
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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




if($action === 'get_customer_invoices') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $invoices = get_all_customer_invoices($conn, $customer_id);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "Invoices" => $invoices
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



if($action === 'get_shop_details') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        // Access is granted. Add code of the operation here
        $shop = get_shop_details($conn, 1);

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "shop" => $shop
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





if($action === 'get_cash_customer_sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));

        $sql = "SELECT a.date_sold, a.total, IFNULL(b.total, 0) AS reversed
                FROM
                (SELECT DATE(date_sold) AS date_sold,
                        SUM(unit_quantity*selling_price) AS total
                        FROM sold_stock
                WHERE customer_id='{$customer_id}'
                GROUP BY DATE(date_sold)) AS a
                LEFT JOIN
                (SELECT DATE(date_returned) AS date_sold, SUM(quantity_returned*selling_price) AS total
                        FROM sold_stock_reversed
                    WHERE sale_id IN (SELECT id FROM sold_stock WHERE customer_id='{$customer_id}')
                    GROUP BY DATE(date_returned)) AS b
                ON b.date_sold=a.date_sold
                
                ORDER BY date_sold DESC";
        $result = run_query($conn, $sql);

        $sales = [];

        $total = 0;
        while($row=mysqli_fetch_assoc($result)) {
            $sales[] = [
                'date' => $row['date_sold'],
                'sales' => $row['total'],
                'reversed' => $row['reversed']
            ];

            $total += $row['total'];
        }

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "sales" => $sales,
            "total" => $total
        );
        echo json_encode($data_insert);
        mysqli_close($conn);

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_cash_customer_periodic_sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));
        $from = mysqli_escape_string($conn, decrypt_data($request->from));
        $to = mysqli_escape_string($conn, decrypt_data($request->to));

        $sql = "SELECT a.date_sold, a.total, IFNULL(b.total, 0) AS reversed
                FROM
                (SELECT DATE(date_sold) AS date_sold,
                        SUM(unit_quantity*selling_price) AS total
                        FROM sold_stock
                WHERE customer_id='{$customer_id}'
                AND DATE(date_sold) >= DATE('{$from}')
                AND DATE(date_sold) <= DATE('{$to}')
                GROUP BY DATE(date_sold)) AS a
                LEFT JOIN
                (SELECT DATE(date_returned) AS date_sold,
                        SUM(quantity_returned*selling_price) AS total
                        FROM sold_stock_reversed
                WHERE customer_id='{$customer_id}'
                AND DATE(date_returned) >= DATE('{$from}')
                AND DATE(date_returned) <= DATE('{$to}')
                GROUP BY DATE(date_returned)) AS b
                ON b.date_sold=a.date_sold";
        $result = run_query($conn, $sql);

        $sales = [];

        $total = 0;
        while($row=mysqli_fetch_assoc($result)) {
            $sales[] = [
                'date' => $row['date_sold'],
                'sales' => $row['total'],
                'reversed' => $row['reversed']
            ];

            $total += $row['total'];
        }

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "sales" => $sales,
            "total" => $total
        );
        echo json_encode($data_insert);
        mysqli_close($conn);

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'get_cash_customer_date_sales') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));
        $date = mysqli_escape_string($conn, decrypt_data($request->date));
        
        $sql = "SELECT a.transaction_code, users.username,
                        a.total, IFNULL(c.total, 0) AS reversed
                FROM
                (SELECT transaction_code, sold_by,
                        SUM(unit_quantity*selling_price) AS total
                        FROM sold_stock
                WHERE customer_id='{$customer_id}'
                AND DATE(date_sold) = DATE('{$date}')
                GROUP BY transaction_code) AS a
                LEFT JOIN users ON users.id=a.sold_by
                LEFT JOIN
                (SELECT transaction_code, SUM(quantity_returned*selling_price) AS total
                        FROM sold_stock_reversed WHERE sale_type='cash'
                    GROUP BY transaction_code) AS c
                ON c.transaction_code=a.transaction_code";
        $result = run_query($conn, $sql);

        $sales = [];

        while($row=mysqli_fetch_assoc($result)) {
            $sales[] = [
                'transaction_code' => $row['transaction_code'],
                'total' => $row['total'],
                'reversed' => $row['reversed'],
                'sold_by' => $row['username']
            ];
        }

        $data_insert=array(
            "status" => "success",
            "message" => "success",
            "sales" => $sales
        );
        echo json_encode($data_insert);
        mysqli_close($conn);

    } catch (Exception $e) {
        
        $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        unlock_all_user_locked_items($conn, $uid);
        
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
