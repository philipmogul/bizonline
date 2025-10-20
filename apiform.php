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

// Include the api.php file
require_once("api.php");

require_once("JWT/autoload.php");
use \Firebase\JWT\JWT;

date_default_timezone_set('Africa/Nairobi');


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





function run_query_form($conn1, $sql) {
    $result = mysqli_query($conn1, $sql);

    if($result) {

        return $result;

    } else {
        throw new Exception("Query error ".mysqli_error($conn1));
        exit();
    }
}


function item_log($conn1, $done_by, $item_id, $operation, $mac) {
    $time = get_today();
    $sql = "INSERT INTO item_operations (item_id, operation, date_done, done_by, mac)
            VALUES ('{$item_id}', '{$operation}', '{$time}', '{$done_by}', '{$mac}')";
    
    return run_query_form($conn1, $sql);
}


function get_user_details($conn1, $username, $password) {

    $sql="SELECT * FROM users WHERE (mobile='{$username}' OR username='{$username}') AND password='{$password}' AND deleted=0 LIMIT 1";
    $result=run_query_form($conn1, $sql);

    $user = null;
    if(mysqli_num_rows($result) > 0) {
        while($record = mysqli_fetch_assoc($result)) {
            $user['id'] = encrypt_data($record['id']);
            $user['username'] = encrypt_data($record['username']);
            $user['password'] = encrypt_data($record['password']);
            $user['mobile'] = encrypt_data($record['mobile']);
            $user['role'] = encrypt_data($record['role']);
            $user['disabled'] = encrypt_data($record['disabled']);
            $user['date_created'] = encrypt_data($record['date_created']);
            $user['image_url'] = encrypt_data($record['image_url']);
        }
    
        return $user;
    } else {
        $data_insert = array(
            "status" => "error",
            "message" => "User record does not exist!",
            "error" => mysqli_error($conn1)
        );
        // return the error
        echo json_encode($data_insert);
        // close connection
        mysqli_close($conn1);

        exit();
    }

    

}


function get_last_insert_id($conn1) {
    $sql = "SELECT LAST_INSERT_ID() AS id";
    $result = run_query_form($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    return $record['id'];
}



function get_unit_pieces($conn1, $unit_id) {
    $sql = "SELECT * FROM stock_units WHERE id='{$unit_id}'";
    $result = run_query_form($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    return $record['unit_quantity'];
}

function get_vat_record($conn1, $vat_id) {
    $sql = "SELECT * FROM vat_records WHERE id='{$vat_id}'";
    $result = run_query_form($conn1, $sql);

    $row = mysqli_fetch_assoc($result);

    return $row['vat_percent'];
}

function reduce_item_location_qty($conn1, $item_id, $location_id, $qty) {
    
    $sql = "SELECT location_quantity FROM stock_locations WHERE item_id='{$item_id}' AND location_id='{$location_id}' LIMIT 1";
    $result = run_query_form($conn1, $sql);

    $row = mysqli_fetch_assoc($result);

    $current_qty = $row['location_quantity'];

    $new_qty = $current_qty - $qty;

    if($new_qty < 0) {
        $new_qty == 0;
    }

    $sql2 = "UPDATE stock_locations SET location_quantity='{$new_qty}'
            WHERE item_id='{$item_id}' AND location_id='{$location_id}'";
    run_query_form($conn1, $sql2);
}

function increase_item_location_qty($conn1, $item_id, $location_id, $qty) {
    $sql = "SELECT * FROM stock_locations WHERE item_id='{$item_id}' AND location_id='{$location_id}' LIMIT 1";
    $result = run_query_form($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {

        while($row = mysqli_fetch_assoc($result)) {
            $current_qty = $row['location_quantity'];

            $new_qty = $current_qty + $qty;
        
            if($new_qty < 0) {
                $new_qty == 0;
            }
        
            $sql2 = "UPDATE stock_locations SET location_quantity='{$new_qty}'
                    WHERE item_id='{$item_id}' AND location_id='{$location_id}'";
            $result2 = run_query_form($conn1, $sql2);
        }

    } else {

        $today = get_today();

        $sql = "INSERT INTO stock_locations (item_id, location_id, location_quantity, date_created)
                VALUES ('{$item_id}', '{$location_id}', '{$qty}', '{$today}')";
        $result2 = run_query_form($conn1, $sql);   
    }
}






function get_temp_transactions($conn1, $user_id) {

    $sql = "SELECT * FROM temp_transactions WHERE sold_by='{$user_id}' AND customer_id=0 ORDER BY id DESC";
    $result = run_query_form($conn1, $sql);

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


function get_total_temp($conn1, $user_id) {

    $sql = "SELECT IFNULL(SUM(unit_quantity * selling_price), 0) AS total FROM temp_transactions
    WHERE sold_by='{$user_id}' AND customer_id=0";

    $result = run_query_form($conn1, $sql);

    $row=mysqli_fetch_assoc($result);

    return $row['total'];
}

function get_temp_transactions_cash_customer($conn1, $user_id, $customer_id) {

    $sql = "SELECT * FROM temp_transactions
            WHERE customer_id='{$customer_id}'
            AND sold_by='{$user_id}'
            ORDER BY id DESC";
    $result = run_query_form($conn1, $sql);

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



function get_total_temp_customer($conn1, $user_id, $customer_id) {

    $sql = "SELECT IFNULL(SUM(unit_quantity * selling_price), 0) AS total FROM temp_transactions
    WHERE customer_id='{$customer_id}'
    AND sold_by='{$user_id}'";

 $result = run_query_form($conn1, $sql);

    $row=mysqli_fetch_assoc($result);

    return $row['total'];
}



function get_temp_waiting_customer_items($conn1, $user_id, $customer) {

    $sql = "SELECT * FROM temp_waiting_customers WHERE sold_by='{$user_id}' AND customer_name='{$customer}' ORDER BY id DESC";
    $result = run_query_form($conn1, $sql);

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


function get_total_temp_waiting($conn1, $user_id, $customer) {
    $sql = "SELECT IFNULL(SUM(unit_quantity * selling_price), 0) AS total FROM temp_waiting_customers WHERE sold_by='{$user_id}' AND customer_name='{$customer}'";
    $result = run_query_form($conn1, $sql);

    $row=mysqli_fetch_assoc($result);

    return $row['total'];
}




function table_is_locked_by_user($conn1, $user_id, $table_name) {
    $sql = "SELECT * FROM table_locks WHERE table_name='{$table_name}' ORDER BY id DESC LIMIT 1";
    $result = run_query_form($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {
        $record = mysqli_fetch_assoc($result);

        if($record['user_id']===$user_id) {

            if($record['locked']==1) {

                // table is locked by this user
                return true;

            } else {

                // lock the table
                $today = get_today();
                $sql = "INSERT INTO table_locks(user_id, table_name, locked, lock_time)
                        VALUES ('{$user_id}', '{$table_name}', 1, '{$today}')";
                $result = run_query_form($conn1, $sql);

                return true;

            }

        } else {

            if($record['locked']==1) {
                // table is locked by different user
                $user = get_user_record($conn1, $record['user_id']);

                $data_insert=array(
                    "status" => "lock",
                    "message" => "Sales are locked by ".$user['name'].". Complete the sale after 3 seconds."
                );
                echo json_encode($data_insert);
                mysqli_close($conn1);

                exit();

            } else {

                // lock table  by this user
                $today = get_today();
                $sql = "INSERT INTO table_locks(user_id, table_name, locked, lock_time)
                        VALUES ('{$user_id}', '{$table_name}', 1, '{$today}')";
                $result = run_query_form($conn1, $sql);

                return true;
            }

        }

    } else {
        // record for this table does not exist
        // lock table  by this user
        $today = get_today();
        $sql = "INSERT INTO table_locks(user_id, table_name, locked, lock_time)
                VALUES ('{$user_id}', '{$table_name}', 1, '{$today}')";
        $result = run_query_form($conn1, $sql);

        return true;

    }
}


function unlock_table($conn1, $table_name, $user_id) {
    $today = get_today();
    $sql = "UPDATE table_locks SET locked=0, unlock_time='{$today}' WHERE table_name='{$table_name}' AND user_id='{$user_id}' AND locked=1";
    $result = run_query_form($conn1, $sql);
}



function get_sold_stock_max_transation_id($conn1) {
    $sql = "SELECT IFNULL(max(transaction_id), 0) as max_id FROM sold_stock";
    $result = run_query_form($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    return $record['max_id'];
}

function get_inter_location_max_transation_id($conn1) {
    $sql = "SELECT IFNULL(max(id), 0) as max_id FROM stock_location_transfer";
    $result = run_query_form($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    return $record['max_id'];
}

function get_sold_stock_debtor_max_transation_id($conn1) {
    $sql = "SELECT IFNULL(max(transaction_id), 0) as max_id FROM sold_stock_debtors";
    $result = run_query_form($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    return $record['max_id'];
}

function get_sold_stock_transfer_max_transation_id($conn1) {
    $sql = "SELECT IFNULL(max(transaction_id), 0) as max_id FROM sold_stock_transfers";
    $result = run_query_form($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    return $record['max_id'];
}

function insert_sales_payment_method($conn1, $customer, $transaction_code, $payment_method, $cash_amount, $mpesa_amount, $bank_amount, $date_of_sale, $sold_by) {
    
    $TODAY = $date_of_sale;

    $sql = "INSERT INTO sales_payments
            (transaction_code, cash, mpesa, bank, payment_channel, date_paid, customer_name, sold_by)
            VALUES
            ('{$transaction_code}', '{$cash_amount}', '{$mpesa_amount}', '{$bank_amount}', '{$payment_method}', '{$TODAY}', '{$customer}', '{$sold_by}')";
    
    $result = run_query_form($conn1, $sql);

    $sales_payment_id = mysqli_insert_id($conn1);

    return $sales_payment_id;
}

function insert_sold_stock($conn1, $customer_id, $max_id, $transaction_code, $item_id, $location_id, $location_old_quantity, $total_old_quantity, $unit_id, $unit_quantity, $buying_price, $min_sell_price, $recom_sell_price, $selling_price, $sales_payment_id, $sold_by, $TODAY) {
    $sql = "INSERT INTO sold_stock
            (customer_id, transaction_id, transaction_code,
                item_id, location_id, location_old_quantity,
                total_old_quantity, unit_id, unit_quantity,
                buying_price, min_selling_price, recom_selling_price,
                selling_price, date_sold, sales_payments_id,
                sold_by)
            VALUES ('{$customer_id}','{$max_id}', '{$transaction_code}', '{$item_id}',
                    '{$location_id}', '{$location_old_quantity}',
                    '{$total_old_quantity}', '{$unit_id}',
                    '{$unit_quantity}', '{$buying_price}',
                    '{$min_sell_price}', '{$recom_sell_price}',
                    '{$selling_price}', '{$TODAY}',
                    '{$sales_payment_id}', '{$sold_by}')";
    run_query_form($conn1, $sql);
}

function insert_sold_stock_debtor($conn1, $max_id, $customer_id, $transaction_code, $item_id, $location_id, $location_old_quantity, $total_old_quantity, $unit_id, $unit_quantity, $buying_price, $min_sell_price, $recom_sell_price, $selling_price, $sold_by, $TODAY) {
    $sql = "INSERT INTO sold_stock_debtors
            (customer_id,
            transaction_id,
            transaction_code,
            item_id,
            location_id,
            location_old_quantity,
            total_old_quantity,
            unit_id,
            unit_quantity,
            buying_price,
            min_selling_price,
            recom_selling_price,
            selling_price,
            date_sold,
            sold_by)
            VALUES ('{$customer_id}', '{$max_id}', '{$transaction_code}',
                    '{$item_id}', '{$location_id}',
                    '{$location_old_quantity}',
                    '{$total_old_quantity}', '{$unit_id}',
                    '{$unit_quantity}', '{$buying_price}',
                    '{$min_sell_price}', '{$recom_sell_price}',
                    '{$selling_price}', '{$TODAY}', '{$sold_by}')";
    $result = run_query_form($conn1, $sql);
}

function insert_sold_stock_transfer($conn1, $max_id, $customer_id, $transaction_code, $item_id, $location_id, $location_old_quantity, $total_old_quantity, $unit_id, $unit_quantity, $buying_price, $sold_by, $TODAY) {
    $sql = "INSERT INTO sold_stock_transfers
            (customer_id,
            transaction_id,
            transaction_code,
            item_id,
            location_id,
            location_old_quantity,
            total_old_quantity,
            unit_id,
            unit_quantity,
            buying_price,
            date_transfered,
            sold_by)
            VALUES ('{$customer_id}', '{$max_id}', '{$transaction_code}',
                    '{$item_id}', '{$location_id}',
                    '{$location_old_quantity}',
                    '{$total_old_quantity}', '{$unit_id}',
                    '{$unit_quantity}', '{$buying_price}',
                    '{$TODAY}', '{$sold_by}')";
    $result = run_query_form($conn1, $sql);
}

function insert_inter_location($conn1, $transaction_code, $item_id, $location_from_id, $location_to_id, $location_from_old_qty, $location_to_old_qty, $unit_id, $unit_quantity, $sold_by, $TODAY) {
    
    $sql = "INSERT INTO stock_location_transfer
            (transaction_code, item_id, location_1_id,
            location_1_old_quantity, unit_id, unit_quantity,
            location_2_id, location_2_old_quantity,
            transfered_by,
            date_transfered)
            VALUES
            ('{$transaction_code}', '{$item_id}',
            '{$location_from_id}', '{$location_from_old_qty}',
            '{$unit_id}', '{$unit_quantity}', '{$location_to_id}',
            '{$location_to_old_qty}', '{$sold_by}', '{$TODAY}')";

    $result = run_query_form($conn1, $sql);
}

function insert_supplier_payment_record($conn1, $supplier_id, $amount, $channel, $today, $paid_by, $added_by, $paid_to) {
    $sql = "INSERT INTO supplier_payments(supplier_id, amount_paid, payment_channel, date_paid, paid_by, user_id, paid_to)
            VALUES ('{$supplier_id}', '{$amount}', '{$channel}', '{$today}', '{$paid_by}', '{$added_by}', '{$paid_to}')";
    $result = run_query_form($conn1, $sql);
}


function get_unupdated_stock_taking_items($conn1, $date_id) {
    $sql = "SELECT * FROM stock_taking_items WHERE stock_taking_date_id='{$date_id}' AND updated=0";
    $result = run_query_form($conn1, $sql);

    $items=[];
    $cr=0;

    while($row=mysqli_fetch_assoc($result)) {

        $items[$cr]['id'] = $row['id'];
        $items[$cr]['item_id'] = $row['item_id'];
        $items[$cr]['location_id'] = $row['location_id'];
        $items[$cr]['current_qty'] = $row['current_quantity'];
        $items[$cr]['confirmed_qty'] = $row['quantity_confirmed'];

        $cr++;

    }


    return $items;
}

function update_stock_taking_location_quantity($conn1, $item_id, $location_id, $qty) {
    $sql = "UPDATE stock_locations
            SET location_quantity='{$qty}'
            WHERE item_id='{$item_id}' AND location_id='{$location_id}'";
    $result = run_query_form($conn1, $sql);
}

function update_stock_taking_item($conn1, $id) {
    $sql3 = "UPDATE stock_taking_items SET updated=1
            WHERE id='{$id}'";
    $result3 = run_query_form($conn1, $sql3);
}

function delete_unsaved_sales_payments($conn1) {
    $sql = "DELETE FROM sales_payments WHERE id NOT IN (SELECT sales_payments_id FROM sold_stock)";
    $result = run_query_form($conn1, $sql);


    // DELETE DUPLICATE sales
    $sql="SELECT * FROM sales_payments sp1 WHERE EXISTS
        (SELECT `transaction_code`, COUNT(*) AS total FROM `sales_payments` sp2 WHERE sp1.transaction_code=sp2.transaction_code GROUP BY transaction_code HAVING total>1)
        ORDER BY id DESC";

    $result=run_query_form($conn1, $sql);

    if(mysqli_num_rows($result) > 0) {

        $deleted_codes = array();

        while($row = mysqli_fetch_assoc($result)) {

            $tcode=$row['transaction_code'];
            $id=$row['id'];

            if(in_array($tcode, $deleted_codes, true)) {

                // record was already deleted

            } else {
                // delete that record
                $sql3 = "DELETE FROM sales_payments WHERE id='{$id}'";
                run_query_form($conn1, $sql3);

                usleep(1);

                // delete the sold_stock
                $sql4 = "DELETE FROM sold_stock WHERE sales_payments_id='{$id}'";
                run_query_form($conn1, $sql4);

                usleep(1);

                array_push($deleted_codes, $tcode);
            }
            
        }

    }
}

function delete_duplicate_cart_items($conn1, $user_id) {
    
    $sql = "DELETE t1
                FROM temp_transactions t1
                INNER JOIN
                (SELECT MIN(id) AS id, DATE_FORMAT(date_sold, '%Y-%m-%d %H:%i:%s') AS date_sold,
                    item_id, unit_id, unit_quantity, location_id, buying_price, selling_price, customer_id,
                    COUNT(*) AS total 
                FROM temp_transactions WHERE sold_by='{$user_id}'
                GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d %H:%i:%s'), item_id, unit_id, unit_quantity, location_id, buying_price, selling_price, customer_id
                HAVING total>1) AS t2
                ON DATE_FORMAT(t1.date_sold, '%Y-%m-%d %H:%i:%s')=DATE_FORMAT(t2.date_sold, '%Y-%m-%d %H:%i:%s')
                    AND t1.item_id=t2.item_id
                    AND t1.unit_id=t2.unit_id
                    AND t1.unit_quantity=t2.unit_quantity
                    AND t1.location_id=t2.location_id
                    AND t1.buying_price=t2.buying_price
                    AND t1.selling_price=t2.selling_price
                    AND t1.customer_id=t2.customer_id
                WHERE t1.id>t2.id";

        run_query_form($conn1, $sql);
}

function delete_duplicate_cart_items_waiting($conn1, $user_id) {
    // delete the duplicates
    $sql = "DELETE t1
            FROM temp_waiting_customers t1
            INNER JOIN
            (SELECT MIN(id) AS id, DATE_FORMAT(date_sold, '%Y-%m-%d %H:%i:%s') AS date_sold,
                customer_name, item_id, unit_id, unit_quantity, location_id, buying_price, selling_price,
                COUNT(*) AS total
            FROM temp_waiting_customers WHERE sold_by='{$user_id}'
            GROUP BY DATE_FORMAT(date_sold, '%Y-%m-%d %H:%i:%s'), customer_name, item_id, unit_id, unit_quantity, location_id, buying_price, selling_price
            HAVING total>1) AS t2
            ON DATE_FORMAT(t1.date_sold, '%Y-%m-%d %H:%i:%s')=DATE_FORMAT(t2.date_sold, '%Y-%m-%d %H:%i:%s')
                AND t1.item_id=t2.item_id
                AND t1.unit_id=t2.unit_id
                AND t1.unit_quantity=t2.unit_quantity
                AND t1.location_id=t2.location_id
                AND t1.buying_price=t2.buying_price
                AND t1.selling_price=t2.selling_price
                AND t1.customer_name=t2.customer_name
            WHERE t1.id>t2.id
                AND t1.sold_by='{$user_id}'";

    run_query_form($conn1, $sql);   
}

function delete_duplicate_cart_items_iterlocation($conn1, $user_id) {
    // delete the duplicates
    $sql = "DELETE t1
            FROM temp_inter_location t1
            INNER JOIN
            (SELECT MIN(id) AS id,
                item_id, location_1_id, location_2_id, unit_id, unit_quantity,
                COUNT(*) AS total
            FROM temp_inter_location WHERE transfered_by='{$user_id}'
            GROUP BY item_id, location_1_id, location_2_id, unit_id, unit_quantity
            HAVING total>1) AS t2
            ON t1.item_id=t2.item_id
                AND t1.unit_id=t2.unit_id
                AND t1.unit_quantity=t2.unit_quantity
                AND t1.location_1_id=t2.location_1_id
                AND t1.location_2_id=t2.location_2_id
            WHERE t1.id>t2.id
                AND t1.transfered_by='{$user_id}'";

    run_query_form($conn1, $sql);
}








function uwazii_authorization_code() {
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://restapi.uwaziimobile.com/v1/authorize',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>'{
        "username":"Liquor_Store",
        "password":"Mutua*123#"
        }',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
      ),
    ));
    
    $response = curl_exec($curl);
    curl_close($curl);
    
    return $response;
}

function save_access_token_uwazii($conn1) {

    $auth_code = uwazii_authorization_code();
    
    $resp = json_decode($auth_code, true);
    
    $auth_code = $resp['data']['authorization_code'];
    
    $url = "https://restapi.uwaziimobile.com/v1/accesstoken";
    $curl = curl_init($url); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); 
    curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode(array(
        'authorization_code' => $auth_code))
    );
    $headers = array();
    $headers[] = "Content-Type: application/json"; 
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


    $response = curl_exec($curl);

    curl_close($curl);
    
    $result = json_decode($response,true);
    $access_token = $result['data']['access_token'];

    $today = date('Y-m-d H:i:s', time());

    $sql = "INSERT INTO sms_access_tokens (token, date_created) VALUES ('{$access_token}', '{$today}')";
    $result = mysqli_query($conn1, $sql);
    if($result) {
        return $access_token;
    } else {
        return $access_token;
    }

}

function get_access_token_uwazii($conn1) {
    $sql = "SELECT * FROM sms_access_tokens ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn1, $sql);
    if($result) {
        if(mysqli_num_rows($result) > 0) {
            $record = mysqli_fetch_assoc($result);

            return $record['token'];
        } else {
            return save_access_token_uwazii($conn1);
        }
    } else {
        return '';
    }
}

function send_uwazii_new($number, $conn1) {

    $token = get_access_token_uwazii($conn1);
    
    $data = json_encode(array($number));
    $url = "https://restapi.uwaziimobile.com/v1/send";
    $curl = curl_init($url); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); 
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    $headers = array();
    $headers[] = "Content-Type: application/json";
    $headers[] = "X-Access-Token: ".$token;  
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


    $response = curl_exec($curl);

    curl_close($curl);
    
    $res = json_decode($response);
    
    
    if($res->status == true) {
        return 0;
    } else {
        return 1;
    }

}

function get_debtor_balance($conn1, $customer_id) {
    $sql = "SELECT IFNULL(sales.sales, 0) AS sales, IFNULL(payments.payments, 0) AS payments, IFNULL(payments.credit_note, 0) AS credit_note
                FROM
                (SELECT customer_id, SUM(unit_quantity*selling_price) AS sales FROM sold_stock_debtors WHERE customer_id='{$customer_id}') AS sales
                LEFT JOIN
                (SELECT customer_id, SUM(cash+mpesa+bank) AS payments, SUM(amount_credited) AS credit_note FROM debtors_payments WHERE customer_id='{$customer_id}') AS payments
                ON payments.customer_id=sales.customer_id";
    $result = run_query($conn1, $sql);

    $balance = 0;
    while($row=mysqli_fetch_assoc($result)) {
        $balance = $row['sales'] - $row['credit_note'] - $row['payments'];
    }

    return $balance;
}

function get_latest_offline_transaction($conn1) {
    $sql = "SELECT MAX(date_sold) AS date_sold FROM

            (SELECT MAX(date_sold) AS date_sold FROM sold_stock
            UNION
            SELECT MAX(date_sold) AS date_sold FROM sold_stock_debtors
            UNION
            SELECT MAX(date_returned) AS date_sold FROM sold_stock_reversed
            UNION
            SELECT MAX(date_transfered) AS date_sold FROM sold_stock_transfers
            UNION
            SELECT MAX(date_created) AS date_sold FROM debtors_payments
            UNION
            SELECT MAX(date_created) AS date_sold FROM expenses
            UNION
            SELECT MAX(date_created) AS date_sold FROM opening_float
            UNION
            SELECT MAX(date_paid) AS date_sold FROM sales_payments
            UNION
            SELECT MAX(date_created) AS date_sold FROM stock
            UNION
            SELECT MAX(date_created) AS date_sold FROM stock_locations
            UNION
            SELECT MAX(date_transfered) AS date_sold FROM stock_location_transfer
            UNION
            SELECT MAX(date_removed) AS date_sold FROM stock_negative
            UNION
            SELECT MAX(date_added) AS date_sold FROM stock_positive
            UNION
            SELECT MAX(date_returned) AS date_sold FROM stock_returns
            UNION
            SELECT MAX(date_started) AS date_sold FROM stock_taking_dates
            UNION
            SELECT MAX(date_created) AS date_sold FROM stock_units
            UNION
            SELECT MAX(date_created) AS date_sold FROM suppliers
            UNION
            SELECT MAX(date_created) AS date_sold FROM supplier_invoices
            UNION
            SELECT MAX(date_added) AS date_sold FROM supplier_invoice_items
            UNION
            SELECT MAX(date_paid) AS date_sold FROM supplier_payments
            UNION
            SELECT MAX(date_paid) AS date_sold FROM supplier_payments
            UNION
            SELECT MAX(date_sold) AS date_sold FROM temp_transactions
            UNION
            SELECT MAX(date_sold) AS date_sold FROM temp_waiting_customers
            UNION
            SELECT MAX(date_paid) AS date_sold FROM transfer_payments
            UNION
            SELECT MAX(date_created) AS date_sold FROM users
            UNION
            SELECT MAX(date_created) AS date_sold FROM user_rights
            UNION
            SELECT MAX(date_created) AS date_sold FROM vat_records
            UNION
            SELECT MAX(date_done) AS date_sold FROM item_operations) AS a
        ";

    $result = run_query_form($conn1, $sql);

    $record = mysqli_fetch_assoc($result);

    $offline_date = $record['date_sold'];

    return $offline_date;
}

function get_latest_online_transaction($conn1) {

    $server_url = 'https://fedhamart.co.ke/NaoApi/database_restore.php';

    // create a curl handle
    $ch = curl_init($server_url);
    
    // define the post data
    $postData = array(
        "action" => "get_latest_online_transaction_date"
    );

    $postData = json_encode($postData);
    
    // set curl options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // execute the request
    $res = curl_exec($ch);

    if(curl_errno($ch)) {

        $data_insert = array(
            "status" => "error",
            "message" => "Could not communicate online ".curl_error($ch)
        );
        // return the error
        echo json_encode($data_insert);
        mysqli_close($conn1);

        exit();

    } else {

        $resp = json_decode($res);

        if($resp->status === 'success') {

            $latest_upload_date = $resp->upload_date;
            $latest_transaction_date = $resp->date;

            $return = null;
            $return['latest_upload_date'] = $latest_upload_date;
            $return['latest_transaction_date'] = $latest_transaction_date;
            
            return $return;
        } else {

            $data_insert = array(
                "status" => "error",
                "message" => "Error from online server ".$resp->message
            );
            // return the error
            echo json_encode($data_insert);
            
            mysqli_close($conn1);

        }

    }

}






function restore_local_db_from_online_db_new($db, $user_id) {
    // temp path to store the downloaded file

    global $dbname;
    $localDownLoadPath = $dbname.'.sql';
    
    $server_url = 'https://fedhamart.co.ke/NaoApi/database_restore.php';
    
    // Initialize the curl session
    $ch = curl_init($server_url);

    // define the post data
    $postData = array(
        "action" => "download-to-local",
        "uid" => $user_id
    );

    $postData = json_encode($postData);
    
    // curt options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // execute the curl resp (downloaded db file) and save it to a local file
    $downloadedDbFile = curl_exec($ch);

    // Check if the response is empty or contains an error message
    if (empty($downloadedDbFile) || strpos($downloadedDbFile, 'Error:') !== false) {
        if(empty($downloadedDbFile)) {
            echo 'No file received';
            exit();
        }

        echo 'Error receiving file';
        exit();
    } 

    file_put_contents($localDownLoadPath, $downloadedDbFile);
    
    // close the curl session
    curl_close($ch);
    
    



    // $filePath = 'fedhamar_bizonline.sql';
    $filePath = $localDownLoadPath;

    // Connect & select the database
    //$db = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName); 

    // Temporary variable, used to store current query
    $templine = '';
    
    // Read in entire file
    $lines = file($filePath);
    
    $error = '';
    
    // Loop through each line
    foreach ($lines as $line){
        // Skip it if it's a comment
        if(substr($line, 0, 2) == '--' || $line == ''){
            continue;
        }
        
        // Add this line to the current segment
        $templine .= $line;
        
        // If it has a semicolon at the end, it's the end of the query
        if (substr(trim($line), -1, 1) == ';'){
            // Perform the query
            if(!$db->query($templine)){
                $error .= 'Error performing query "<b>' . $templine . '</b>": ' . $db->error . '<br /><br />';
            }
            
            // Reset temp variable to empty
            $templine = '';
        }
    }

    if(!empty($error)) {

        $data_insert = array(
            "status" => "error",
            "message" => "Could not restore db localhost ".$error. "\n"
        );
        // return the error
        echo json_encode($data_insert);

        mysqli_close($db);

    } else {

        unlink($localDownLoadPath);

        $data_insert = array(
            "status" => "success",
            "message" => "success"
        );
        // return the error
        echo json_encode($data_insert);

        mysqli_close($db);
    }



}




function restore_local_db_from_online_db($user_id) {
    // temp path to store the downloaded file
    $localDownLoadPath = 'fedhamart.sql';
    
    $server_url = 'https://dev.liquorstore.ke/must/database_restore.php';
    
    // Initialize the curl session
    $ch = curl_init($server_url);

    // define the post data
    $postData = array(
        "action" => "download-to-local",
        "uid" => $user_id
    );

    $postData = json_encode($postData);
    
    // curt options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // execute the curl resp (downloaded db file) and save it to a local file
    $downloadedDbFile = curl_exec($ch);

    // Check if the response is empty or contains an error message
    if (empty($downloadedDbFile) || strpos($downloadedDbFile, 'Error:') !== false) {
        if(empty($downloadedDbFile)) {
            echo 'No file received';
            exit();
        }

        echo 'Error receiving file';
        exit();
    } 

    file_put_contents($localDownLoadPath, $downloadedDbFile);
    
    // close the curl session
    curl_close($ch);
    
    // restore the local DB using the downloaded DB file
    $command = "C:\xampp\mysql\bin\mysql -h localhost -u root -psteve254 fedhamart < C:\xampp\htdocs\NaoApi\fedhamart.sql";

    global $servername;
    global $username;
    global $password;
    global $dbname;

    //$cmd = "C:\xampp\mysql\bin\mysql -h localhost -u root -psteve254 fedhamart < $localDownLoadPath";
    
    $command = "C:\xampp\mysql\bin\mysql -u $username -p$password -h localhost -port 3306 $dbname < C:\xampp\htdocs\NaoApi\fedhamart.sql 2> error_log";
        
    
    // $command = "C:\xampp\mysql\bin\mysql -u root -p steve254 < fedhamart.sql";
    exec($command, $output, $returnCode);
    
    if($returnCode==0) {

       // unlink($localDownLoadPath);

        $data_insert = array(
            "status" => "success",
            "message" => "success"
        );
        // return the error
        echo json_encode($data_insert);
    } else {

       // unlink($localDownLoadPath);

        $data_insert = array(
            "status" => "error",
            "message" => "Could not restore db localhost ".implode("\n", $output). "\n"
        );
        // return the error
        echo json_encode($data_insert);
    }
}






































 



$action = decrypt_data($request->action);


if($action === 'add-device') {

    $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
    $name = mysqli_escape_string($conn, decrypt_data($request->name));
    $usernamedb = mysqli_escape_string($conn, decrypt_data($request->mobile));
    $passworddb = mysqli_escape_string($conn, decrypt_data($request->password));


    try {
        
        mysqli_begin_transaction($conn);



        $user_details = get_user_details($conn, $usernamedb, $passworddb);

        $uid = decrypt_data($user_details['id']);

        unlock_all_user_locked_items($conn, $uid);    

        $today = get_today();

        $sql = "INSERT INTO registered_devices (mac, device_name, date_registered, registered_by)
                VALUES ('{$mac}', '{$name}', '{$today}', '{$uid}')";

        $result = run_query_form($conn, $sql);

        // record the user action
        user_log($conn, $uid, 'Registered device', $mac);

        
        mysqli_commit($conn);

        // // backupDatabase($conn);

        $data_insert=array(
            "status" => "success",
            "message" => "This computer is now registered!"
        );
        echo json_encode($data_insert);
        mysqli_close($conn);


    } catch (Exception $e) {
        //throw $th;
        mysqli_rollback($conn);
        $data_insert=array(
            "status" => "error",
            "message" => $e->getMessage()
        );
        echo json_encode($data_insert);
        mysqli_close($conn);
    }


}



if($action === 'backup-db') {

    $mac = mysqli_escape_string($conn, decrypt_data($request->mac));

    backupDatabase($conn);
}

if($action === 'add_category') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $category = mysqli_escape_string($conn, decrypt_data($request->category));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        try {
        
            mysqli_begin_transaction($conn);
        
        
            if(check_device_mac($conn, $mac)) {
                
                $sql = "SELECT * FROM stock_categories WHERE category_name='{$category}' AND deleted=0";
                $result = run_query_form($conn, $sql);

                if(mysqli_num_rows($result) < 1) {

                    $sql = "INSERT INTO stock_categories (category_name, added_by)
                                VALUES ('{$category}', '{$added_by}')";
                    $result = run_query_form($conn, $sql);

                    $records = get_all_categories($conn);

                    user_log($conn, $added_by, 'Added the category '.$category, $mac);


                    mysqli_commit($conn);

                    // // backupDatabase($conn);


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
                        "message" => "That category name exists!"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);
                }

            }


        } catch (Exception $e) {
            //throw $th;
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage()
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

if($action === 'update_category') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $category = mysqli_escape_string($conn, decrypt_data($request->category));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        
        if(check_device_mac($conn, $mac)) {
            
            $sql = "SELECT * FROM stock_categories WHERE category_name='{$category}' AND deleted=0 AND id!='{$id}'";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) < 1) {

                $sql = "UPDATE stock_categories
                        SET category_name='{$category}'
                        WHERE id='{$id}'";
                $result = run_query_form($conn, $sql);

                $records = get_all_categories($conn);

                user_log($conn, $added_by, 'Updated the category '.$category, $mac);


                $data_insert=array(
                    "status" => "success",
                    "message" => "success"
                );
                echo json_encode($data_insert);
                mysqli_close($conn);


            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "That category name exists!"
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

if($action === 'delete_category') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $category_id = mysqli_escape_string($conn, decrypt_data($request->category));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {
            
            $today = get_today();

            $sql = "SELECT * FROM stock_categories WHERE id='{$category_id}' AND deleted=0";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) > 0) {

                $record = mysqli_fetch_assoc($result);
                $name = $record['category_name'];

                $sql="DELETE FROM stock_categories WHERE id='{$category_id}'";
                $result = run_query_form($conn, $sql);

                $records = get_all_categories($conn);

                user_log($conn, $added_by, 'Deleted the category '.$name, $mac);


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
                    "message" => "That vat does not exist!"
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

if($action === 'add_vat_record') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $percent = mysqli_escape_string($conn, decrypt_data($request->percent));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        
        if(check_device_mac($conn, $mac)) { 
            
            $today = get_today();

            $sql = "SELECT * FROM vat_records WHERE vat_percent='{$percent}' AND deleted=0";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) < 1) {

                $sql="INSERT INTO vat_records (vat_percent, date_created, added_by)
                        VALUES ('{$percent}', '{$today}', '{$added_by}')";
                $result = run_query_form($conn, $sql);

                $records = get_vat_records($conn);

                
                user_log($conn, $added_by, 'Added the VAT percent - '.$percent, $mac);


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
                    "message" => "That vat percentage exists!"
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

if($action === 'delete_vat_record') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $vid = mysqli_escape_string($conn, decrypt_data($request->vid));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {
            
            $today = get_today();

            $sql = "SELECT * FROM vat_records WHERE id='{$vid}' AND deleted=0";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) > 0) {

                $record = mysqli_fetch_assoc($result);
                $vat = $record['vat_percent'];

                $sql="UPDATE vat_records SET deleted=1 WHERE id='{$vid}'";
                $result = run_query_form($conn, $sql);

                user_log($conn, $added_by, 'Deleted the VAT percent - '.$vat, $mac);


                $data_insert=array(
                    "status" => "success",
                    "message" => "success"
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "That vat does not exist!"
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


if($action === 'add_stock_item') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_name = mysqli_escape_string($conn, decrypt_data($request->name));
        $item_category = mysqli_escape_string($conn, decrypt_data($request->category_id));
        $vat_id = mysqli_escape_string($conn, decrypt_data($request->vat_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {
            
            $sql = "SELECT * FROM stock WHERE name='{$item_name}' AND deleted=0";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) < 1) {

                $today = get_today();

                $sql="INSERT INTO stock (name, category_id, vat_id, date_created, last_edited, edited_by)
                        VALUES ('{$item_name}', '{$item_category}', '{$vat_id}', '{$today}', '{$today}', '{$added_by}')";
                $result = run_query_form($conn, $sql);


                $item_id= get_last_insert_id($conn);


                // insert stock location with value of zero
                $locations = get_shop_locations($conn);
                $location_id = decrypt_data($locations[0]['id']);

                $sql = "INSERT INTO stock_locations (item_id, location_id, location_quantity, date_created)
                        VALUES ('{$item_id}', '{$location_id}', 0, '{$today}')";
                $result = run_query_form($conn, $sql);
                

                item_log($conn, $added_by, $item_id, 'Item was created', $mac);

                user_log($conn, $added_by, 'Added a new item '.$item_name, $mac);

                // // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "sucsess",
                    "id" => encrypt_data($item_id)
                );
                echo json_encode($data_insert);
                mysqli_close($conn);



            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "An item with that name exists!"
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


if($action === 'delete_stock_item') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $name = mysqli_escape_string($conn, decrypt_data($request->name));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
       
        
        if(check_device_mac($conn, $mac)) {
            $sql = "UPDATE stock SET deleted=1 WHERE id='{$item_id}'";
            $result = run_query_form($conn, $sql);

            item_log($conn, $added_by, $item_id, 'Item was deleted', $mac);

            user_log($conn, $added_by, 'Deleted the item - '.$name, $mac);

            // // backupDatabase($conn);

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

if($action === 'update_stock_item') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->id));
        $item_name = mysqli_escape_string($conn, decrypt_data($request->name));
        $item_name1 = mysqli_escape_string($conn, decrypt_data($request->name1));
        $category_id = mysqli_escape_string($conn, decrypt_data($request->category_id));
        $category_id1 = mysqli_escape_string($conn, decrypt_data($request->category_id1));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {
            
            $sql = "SELECT * FROM stock WHERE name='{$item_name}' AND category_id='{$category_id}' AND id != '{$item_id}' AND deleted=0";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) < 1) {

                $today = get_today();

                $sql="UPDATE stock SET name='{$item_name}', category_id='{$category_id}' WHERE id='{$item_id}'";
                $result = run_query_form($conn, $sql);


                item_log($conn, $added_by, $item_id, 'Item was edited', $mac);

                user_log($conn, $added_by, 'Edited the item '.$item_name, $mac);

                // // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "sucsess"
                );
                echo json_encode($data_insert);
                mysqli_close($conn);



            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "An item with that name exists!"
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

if($action === 'update_item_bp') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $item_name = mysqli_escape_string($conn, decrypt_data($request->item_name));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->bp));
        $vat_id = mysqli_escape_string($conn, decrypt_data($request->vat));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        

        $sql = "UPDATE stock SET buying_price='{$buying_price}', vat_id='{$vat_id}' WHERE id='{$item_id}'";
        $result = run_query_form($conn, $sql);

        item_log($conn, $added_by, $item_id, 'Buying price was edited', $mac);

        user_log($conn, $added_by, 'Updated buying price for the item '.$item_name, $mac);

        // backupDatabase($conn);

        $data_insert=array(
            "status" => "success",
            "message" => "sucsess"
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





if($action === 'update_item_stock_alert_level') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $min_quantity = mysqli_escape_string($conn, decrypt_data($request->min_quantity));
        
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        $today = get_today();

        
        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM min_stock_items WHERE item_id='{$item_id}'";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) > 0) {
                $sql = "UPDATE min_stock_items SET unit_id='{$unit_id}', min_quantity='{$min_quantity}'
                        WHERE item_id='{$item_id}'";
                $result = run_query_form($conn, $sql);

            } else {
                // insert record
                $sql = "INSERT INTO min_stock_items
                        (item_id, unit_id, min_quantity)
                        VALUES ('{$item_id}', '{$unit_id}', '{$min_quantity}')";
                $result = run_query_form($conn, $sql);
            }


            item_log($conn, $added_by, $item_id, 'Stock alert level updated', $mac);

            user_log($conn, $added_by, 'Updated item stock alert level', $mac);


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


if($action === 'add_item_selling_unit') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit = mysqli_escape_string($conn, decrypt_data($request->unit));
        $unit_quantity = mysqli_escape_string($conn, decrypt_data($request->unit_quantity));
        $unit_min_selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_min_selling_price));
        $unit_recom_selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_recom_selling_price));
        $markup = mysqli_escape_string($conn, decrypt_data($request->markup));
        $markup_discount = mysqli_escape_string($conn, decrypt_data($request->markup_discount));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        $today = get_today();

        
        if(check_device_mac($conn, $mac)) {
            
            $sql = "SELECT * FROM stock_units WHERE item_id='{$item_id}' AND unit_name='{$unit}' AND unit_quantity='{$unit_quantity}' AND deleted=0";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) < 1) {

                $today = get_today();

                $sql = "INSERT INTO stock_units (item_id, unit_name, unit_quantity, unit_min_selling_price, unit_recom_selling_price, markup, markup_discount, date_created, created_by)
                        VALUES ('{$item_id}', '{$unit}', '{$unit_quantity}', '{$unit_min_selling_price}', '{$unit_recom_selling_price}', '{$markup}', '{$markup_discount}', '{$today}', '{$added_by}')";
                $result = run_query_form($conn, $sql);

                $item_id = get_last_insert_id($conn);
                
                item_log($conn, $added_by, $item_id, 'A new item selling unit was added', $mac);

                user_log($conn, $added_by, 'Added a new item unit - '.$unit, $mac);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "id" => encrypt_data($item_id)
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "A unit with that name or quantity exists for that item!"
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



if($action === 'update_item_selling_unit') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_name = mysqli_escape_string($conn, decrypt_data($request->unit));
        $unit_quantity = mysqli_escape_string($conn, decrypt_data($request->unit_quantity));
        $unit_min_selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_min_selling_price));
        $unit_recom_selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_recom_selling_price));
        $markup = mysqli_escape_string($conn, decrypt_data($request->markup));
        $markup_discount = mysqli_escape_string($conn, decrypt_data($request->markup_discount));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        $today = get_today();

        if(check_device_mac($conn, $mac)) {
            
            $sql = "SELECT * FROM stock_units WHERE unit_name='{$unit_name}' AND unit_quantity='{$unit_quantity}' AND deleted=0 AND item_id='{$item_id}' AND id!='{$unit_id}'";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) < 1) {

                $today = get_today();

                $sql = "UPDATE stock_units
                        SET unit_name='{$unit_name}',
                        unit_quantity='{$unit_quantity}',
                        unit_min_selling_price='{$unit_min_selling_price}',
                        unit_recom_selling_price='{$unit_recom_selling_price}',
                        markup='{$markup}',
                        markup_discount='{$markup_discount}'

                        WHERE id='{$unit_id}'";

                $result = run_query_form($conn, $sql);

                item_log($conn, $added_by, $item_id, 'Item selling unit was updated', $mac);

                user_log($conn, $added_by, 'Updated the item unit - '.$unit_name, $mac);

                // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success"
                );
                echo json_encode($data_insert);
                mysqli_close($conn);



            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "A unit with that name or quantity exists for that item!"
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

if($action === 'delete_item_selling_unit') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $unit = mysqli_escape_string($conn, decrypt_data($request->unit));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $unit_name = mysqli_escape_string($conn, decrypt_data($request->unit_name));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->itid));

        if(check_device_mac($conn, $mac)) {

            $sql = "UPDATE stock_units SET deleted=1 WHERE id='{$unit}'";
            $result = run_query_form($conn, $sql);

            item_log($conn, $added_by, $item_id, 'The selling unit '.$unit_name.' was deleted', $mac);
            user_log($conn, $added_by, 'Deleted item unit - '.$unit_name, $mac);

            // backupDatabase($conn);

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

if($action === 'add_shop_location') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $name = mysqli_escape_string($conn, decrypt_data($request->location));
        $color = mysqli_escape_string($conn, decrypt_data($request->color));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {
            
            $sql = "SELECT * FROM shop_locations WHERE name='{$name}' AND deleted=0";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) < 1) {

                $sql = "SELECT * FROM shop_locations WHERE color='{$color}' AND deleted=0";
                $result = run_query_form($conn, $sql);

                if(mysqli_num_rows($result) < 1) {

                    $today = get_today();

                    $sql = "INSERT INTO shop_locations (name, color, date_created, created_by)
                            VALUES ('{$name}', '{$color}', '{$today}', $added_by)";
                    $result = run_query_form($conn, $sql);


                    $location_id = encrypt_data(get_last_insert_id($conn));

                    user_log($conn, $added_by, 'Added new stock location - '.$name, $mac);


                    $data_insert=array(
                        "status" => "success",
                        "message" => "success",
                        "id" => $location_id
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);


                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "A location with that color exists!"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);
                }

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "A location with that name exists!"
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


if($action === 'update_shop_location') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $name = mysqli_escape_string($conn, decrypt_data($request->location));
        $color = mysqli_escape_string($conn, decrypt_data($request->color));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {
            
            $sql = "SELECT * FROM shop_locations WHERE name='{$name}' AND deleted=0 AND id!='{$id}'";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) < 1) {

                $sql = "SELECT * FROM shop_locations WHERE color='{$color}' AND deleted=0 AND id!='{$id}'";
                $result = run_query_form($conn, $sql);

                if(mysqli_num_rows($result) < 1) {

                    $today = get_today();

                    $sql = "UPDATE shop_locations  SET name='{$name}', color='{$color}' WHERE id='{$id}'";
                    $result = run_query_form($conn, $sql);


                    user_log($conn, $added_by, 'Updated the stock location - '.$name, $mac);


                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);


                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "A location with that color exists!"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);
                }

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "A location with that name exists!"
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

if($action === 'delete_shop_location') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->id));
        $name = mysqli_escape_string($conn, decrypt_data($request->name));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        

        if(check_device_mac($conn, $mac)) {

            $sql1 = "SELECT * FROM shop_locations WHERE id='{$location_id}' AND name='Shop' AND deleted=0";
            $result1 = run_query_form($conn, $sql1);

            if(mysqli_num_rows($result1) > 0) {

                $data_insert=array(
                    "status" => "error",
                    "message" => "Shop location cannot be deleted!"
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {

                $sql = "UPDATE shop_locations SET deleted=1 WHERE id='{$location_id}'";
                $result = run_query_form($conn, $sql);

                user_log($conn, $added_by, 'Deleted the stock location - '.$name, $mac);

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



if($action === 'add_new_supplier_invoice') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $supplier = mysqli_escape_string($conn, decrypt_data($request->supplier));
        $name = mysqli_escape_string($conn, decrypt_data($request->name));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM supplier_invoices WHERE supplier_id='{$supplier}' AND invoice_number='{$name}'";
            $result = run_query_form($conn, $sql);

            $today = get_today();

            if(mysqli_num_rows($result) < 1) {

                $sql = "INSERT INTO supplier_invoices (supplier_id, invoice_number, date_supplied, date_created, user_id)
                        VALUES ('{$supplier}', '{$name}', '{$today}', '{$today}', '{$added_by}')";
                $result = run_query_form($conn, $sql);

                $invoice_id = get_last_insert_id($conn);


                user_log($conn, $added_by, 'Added new supplier invoice - '.$name, $mac);

                // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "id" => encrypt_data($invoice_id)
                );
                echo json_encode($data_insert);
                mysqli_close($conn);


            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Invoice number exists!"
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

if($action === 'add_invoice_item') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $invoice_id = mysqli_escape_string($conn, decrypt_data($request->invoice));
        $invoiceno = mysqli_escape_string($conn, decrypt_data($request->invoiceno));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $item_name = mysqli_escape_string($conn, decrypt_data($request->item_nm));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit));
        $unit_name = mysqli_escape_string($conn, decrypt_data($request->unitnm));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $total_qty = mysqli_escape_string($conn, decrypt_data($request->total_qty));
        $old_bp = mysqli_escape_string($conn, decrypt_data($request->old_bp));
        $old_sp = mysqli_escape_string($conn, decrypt_data($request->old_sp));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->buying_price));
        $buying_price_with_vat = mysqli_escape_string($conn, decrypt_data($request->buying_price_vat));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->selling_price));
        $vat_id = mysqli_escape_string($conn, decrypt_data($request->vat));
        $update_old_bp = mysqli_escape_string($conn, decrypt_data($request->update_old_bp));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));


        if(check_device_mac($conn, $mac)) {

            // get the item location quantity
            $item_old_quantity = get_item_location_quantity($conn, $item_id, $location_id);

            $location_old_qty = $item_old_quantity['location_quantity'];
            $total_old_qty = $item_old_quantity['total_quantity'];


            // get the total quantity to be added in pieces
            $unit_pieces = get_unit_pieces($conn, $unit_id);
            $total_pieces_to_add = $unit_pieces * $unit_qty;


            // unit bp
            $unit_bp = $buying_price_with_vat * $unit_pieces;

            // insert into supplier invoice items
            $today = get_today();

            $sql = "INSERT INTO supplier_invoice_items
                    (invoice_id,
                    item_id,
                    location_id,
                    location_old_quantity,
                    total_old_quantity,
                    unit_id,
                    unit_quantity,
                    total_quantity_added,
                    unit_buying_price,
                    old_buying_price,
                    new_buying_price,
                    old_selling_price,
                    new_selling_price,
                    vat_id,
                    update_old_buying_price,
                    date_added,
                    added_by)
                    VALUES ('{$invoice_id}', '{$item_id}', '{$location_id}',
                            '{$location_old_qty}', '{$total_old_qty}',
                            '{$unit_id}', '{$unit_qty}', '{$total_pieces_to_add}', '{$unit_bp}',
                            '{$old_bp}', '{$buying_price_with_vat}', '{$old_sp}',
                            '{$selling_price}', '{$vat_id}', '{$update_old_bp}',
                            '{$today}', '$added_by')";

            $result = run_query_form($conn, $sql);

            $id = get_last_insert_id($conn);

            // log the item operation
            item_log($conn, $added_by, $item_id, 'Item was added to the invoice - '.$invoiceno.' with unit of - '.$unit_qty.' '.$unit_name, $mac);

            // log user operation
            user_log($conn, $added_by, 'Added the item '.$item_name.' to invoice no '.$invoiceno, $mac);

            // unlock the item in stock locations
            unlock_item($conn, $item_id, $location_id, $added_by);

            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "id" => encrypt_data($id)
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


if($action === 'delete_invoice_item') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $invoice_id = mysqli_escape_string($conn, decrypt_data($request->invoice));
        $invoiceno = mysqli_escape_string($conn, decrypt_data($request->invoiceno));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $item_name = mysqli_escape_string($conn, decrypt_data($request->item_nm));
        $unit_name = mysqli_escape_string($conn, decrypt_data($request->unitnm));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));

        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        $sql = "DELETE FROM supplier_invoice_items WHERE id='{$id}' AND item_id='{$item_id}' AND invoice_id='{$invoice_id}'";
        $result = run_query_form($conn, $sql);


        // log the item operation
        item_log($conn, $added_by, $item_id, 'Item was deleted from the invoice - '.$invoiceno, $mac);

        // log user operation
        user_log($conn, $added_by, 'Deleted the item '.$item_name.' from invoice no '.$invoiceno.' with quantity of '.$unit_qty.' '.$unit_name, $mac);


        // backupDatabase($conn);

        $data_insert=array(
            "status" => "success",
            "message" => "success"
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


if($action === 'delete_invoice') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $invoice_id = mysqli_escape_string($conn, decrypt_data($request->id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        $sql = "DELETE FROM supplier_invoice_items WHERE invoice_id='{$invoice_id}'";
        $result = run_query_form($conn, $sql);

        $sql2 = "DELETE FROM supplier_invoices WHERE id='{$invoice_id}'";
        $result2 = run_query_form($conn, $sql2);


        // log user operation
        user_log($conn, $added_by, 'Deleted an invoice', $mac);

        // backupDatabase($conn);

        $data_insert=array(
            "status" => "success",
            "message" => "success"
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



if($action === 'approve_invoice') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $invoice_id = mysqli_escape_string($conn, decrypt_data($request->invoice));
        $invoiceno = mysqli_escape_string($conn, decrypt_data($request->invoiceno));

        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        $today = get_today();

        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM supplier_invoice_items WHERE invoice_id='{$invoice_id}'";
            $result = run_query_form($conn, $sql);

            while($row=mysqli_fetch_assoc($result)) {

                $item_id = $row['item_id'];
                $unit_id = $row['unit_id'];
                $unit_quantity = $row['unit_quantity'];
                $old_sp = $row['old_selling_price'];
                $old_bp = $row['old_buying_price'];
                $buying_price = $row['new_buying_price'];
                $selling_price = $row['new_selling_price'];
                $update_old_bp = $row['update_old_buying_price'];

                $location_id = $row['location_id'];

                $vat_id = $row['vat_id'];

                $unit_pieces = get_unit_pieces($conn, $unit_id);

                $total_pieces = $unit_quantity * $unit_pieces;

                // update stock locations (location quantity)
                // if not present insert the record
                $sql = "SELECT * FROM stock_locations WHERE item_id='{$item_id}' AND location_id='{$location_id}'";
                $result2 = run_query_form($conn, $sql);

                if(mysqli_num_rows($result2) > 0) {
                    $record = mysqli_fetch_assoc($result2);
                    $item_old_quantity = $record['location_quantity'];
                    // update the location quantity
                    // $item_old_quantity = get_item_location_quantity($conn, $item_id, $location_id);

                    $location_new_quantity = $item_old_quantity + $total_pieces;

                    $sql = "UPDATE stock_locations SET location_quantity='{$location_new_quantity}' WHERE item_id='{$item_id}' AND location_id='{$location_id}'";
                    run_query_form($conn, $sql);

                } else {

                    // insert the record
                    $sql = "INSERT INTO stock_locations
                            (item_id, location_id, location_quantity, date_created)
                            VALUES ('{$item_id}', '{$location_id}', '{$total_pieces}', '{$today}')";
                    run_query_form($conn, $sql);

                }

                // update stock (buying price and vat)
                if($update_old_bp == 1) {
                    $sql = "UPDATE stock SET buying_price='{$buying_price}', vat_id='{$vat_id}', last_edited='{$today}', edited_by='{$added_by}' WHERE id='{$item_id}'";
                    run_query_form($conn, $sql);

                    // update the selling prices
                    $sql = "UPDATE stock_units
                            SET
                            unit_recom_selling_price =  ROUND(((1 + markup/100) * ({$buying_price}*unit_quantity)) / 5) * 5,
                            unit_min_selling_price =  ROUND(((1 + markup_discount/100) * ({$buying_price}*unit_quantity)) / 5) * 5
                            WHERE item_id='{$item_id}'";
                    run_query_form($conn, $sql);

                }

                // update stock selling units (min and recommended selling prices)
                // if($old_sp != $selling_price) {
                //     $sql = "UPDATE stock_units SET unit_recom_selling_price='{$selling_price}' WHERE item_id='{$item_id}' AND id='{$unit_id}'";
                //     $result5 = run_query_form($conn, $sql);
                // }

            }

            $sql = "UPDATE supplier_invoices SET approved=1, date_approved='{$today}' WHERE id='{$invoice_id}' AND invoice_number='{$invoiceno}'";
            $result = run_query_form($conn, $sql);

            // log user operation
            user_log($conn, $added_by, 'Approved the invoice - '.$invoiceno, $mac);

            $shop_details = get_shop_details($conn, $added_by);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
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


if($action === 'add_stock_positive_item') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {
            $sql="SELECT * FROM stock WHERE id='{$item_id}'";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) > 0) {

                $unit_pieces = get_unit_pieces($conn, $unit_id);
    
                $pieces_to_add = $unit_pieces * $unit_qty;

                $today = get_today();

                // check if item has a stock location record
                $sql="SELECT * FROM stock_locations WHERE item_id='{$item_id}' AND location_id='{$location_id}'";
                $result = run_query_form($conn, $sql);

                if(mysqli_num_rows($result) > 0) {

                    $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                    $item_old_qty = $location_old_qty['location_quantity'];


                    $item_new_qty = floatval($pieces_to_add) + floatval($item_old_qty);

                    $item_new_qty = intval($item_new_qty);


                    $sql="UPDATE stock_locations SET location_quantity='{$item_new_qty}'
                            WHERE item_id='{$item_id}' AND location_id='{$location_id}'";
                    $result = run_query_form($conn, $sql);

                    $buying_price_per_piece = get_item_details($conn, $item_id)['buying_price'];


                    // get the item total old quantity first
                   // $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                    //$item_location_old_qty = $location_old_qty['location_quantity'];
                    $item_total_old_qty = $location_old_qty['total_quantity'];



                    // insert stock positive
                    $sql = "INSERT INTO stock_positive(
                                item_id, location_id, location_old_quantity, unit_id,
                                unit_quantity, total_old_quantity,
                                total_quantity_added, buying_price_per_piece, date_added, added_by)
                            VALUES
                            ('{$item_id}', '{$location_id}', '{$item_old_qty}', '{$unit_id}',
                            '{$unit_qty}', '{$item_total_old_qty}',
                            '{$pieces_to_add}', '{$buying_price_per_piece}', '{$today}', '{$added_by}')";
                    $result = run_query_form($conn, $sql);

                    // log the item operation
                    item_log($conn, $added_by, $item_id, 'A stock positive was added of - '.$pieces_to_add.' pieces', $mac);

                    // log user operation
                    user_log($conn, $added_by, 'Added stock positive of '.$pieces_to_add.' pieces', $mac);

                    // back up the database
                    // backupDatabase($conn);

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {


                    
                    // insert the location record
                    $sql = "INSERT INTO stock_locations (item_id, location_id, location_quantity, date_created)
                            VALUES ('{$item_id}', '{$location_id}', '{$pieces_to_add}', '{$today}')";
                    $result = run_query_form($conn, $sql);

                    $buying_price_per_piece = get_item_details($conn, $item_id)['buying_price'];

                    // get the item total old quantity first
                    $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                    $item_location_old_qty = $location_old_qty['location_quantity'];
                    $item_total_old_qty = $location_old_qty['total_quantity'];



                    // insert stock positive
                    $sql = "INSERT INTO stock_positive(
                                item_id, location_id, location_old_quantity, unit_id,
                                unit_quantity, total_old_quantity,
                                total_quantity_added, buying_price_per_piece, date_added, added_by)
                            VALUES
                            ('{$item_id}', '{$location_id}', 0, '{$unit_id}', '{$unit_qty}',
                            '{$item_total_old_qty}',
                            '{$pieces_to_add}', '{$buying_price_per_piece}', '{$today}', '{$added_by}')";
                    $result = run_query_form($conn, $sql);


                    // log the item operation
                    item_log($conn, $added_by, $item_id, 'A stock positive was added of - '.$pieces_to_add.' pieces', $mac);

                    // log user operation
                    user_log($conn, $added_by, 'Added stock positive of '.$pieces_to_add.' pieces', $mac);

                    // back up the database
                    // backupDatabase($conn);

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);


                }


            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Item does not exist in stock!"
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

if($action === 'add_stock_negative_item') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {
            $sql="SELECT * FROM stock WHERE id='{$item_id}'";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) > 0) {

                $unit_pieces = get_unit_pieces($conn, $unit_id);

                $pieces_to_remove = $unit_pieces * $unit_qty;

                // check if item has a stock location record
                $sql="SELECT * FROM stock_locations WHERE item_id='{$item_id}' AND location_id='{$location_id}'";
                $result = run_query_form($conn, $sql);


                if(mysqli_num_rows($result) > 0) {

                    $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                    $item_old_qty = $location_old_qty['location_quantity'];

                    $item_new_qty = floatval($item_old_qty) - floatval($pieces_to_remove);

                    if($item_new_qty < 0) {
                        $item_new_qty = 0;
                    }

                    $today = get_today();


                    $sql="UPDATE stock_locations SET location_quantity='{$item_new_qty}'
                            WHERE item_id='{$item_id}' AND location_id='{$location_id}'";
                    $result = run_query_form($conn, $sql);
                    
                    $buying_price_per_piece = get_item_details($conn, $item_id)['buying_price'];


                    // get the item total old quantity first
                   // $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                   // $item_location_old_qty = $location_old_qty['location_quantity'];
                    $item_total_old_qty = $location_old_qty['total_quantity'];



                    // insert stock negative
                    $sql = "INSERT INTO stock_negative(
                                item_id, location_id, location_old_quantity, unit_id, unit_quantity, total_old_quantity,
                                total_quantity_removed, buying_price_per_piece, date_removed, removed_by)
                            VALUES
                            ('{$item_id}', '{$location_id}', '{$item_old_qty}', '{$unit_id}', '{$unit_qty}', '{$item_total_old_qty}',
                            '{$pieces_to_remove}', '{$buying_price_per_piece}', '{$today}', '{$added_by}')";
                    $result = run_query_form($conn, $sql);

                    // log the item operation
                    item_log($conn, $added_by, $item_id, 'A stock negative was added of - '.$pieces_to_remove.' pieces', $mac);

                    // log user operation
                    user_log($conn, $added_by, 'Added stock negative of '.$pieces_to_remove.' pieces', $mac);


                    // back up the database
                    // backupDatabase($conn);


                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {

                    $today = get_today();
                    
                    // insert the location record
                    $sql = "INSERT INTO stock_locations (item_id, location_id, location_quantity, date_created)
                            VALUES ('{$item_id}', '{$location_id}', 0, '{$today}')";
                    $result = run_query_form($conn, $sql);

                    $buying_price_per_piece = get_item_details($conn, $item_id)['buying_price'];

                    // get the item total old quantity first
                    $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                    $item_location_old_qty = $location_old_qty['location_quantity'];
                    $item_total_old_qty = $location_old_qty['total_quantity'];



                    // insert stock negative
                    $sql = "INSERT INTO stock_negative(
                                item_id, location_id, location_old_quantity, total_old_quantity,
                                total_quantity_removed, buying_price_per_piece, date_removed, removed_by)
                            VALUES
                            ('{$item_id}', '{$location_id}', 0, '{$item_total_old_qty}',
                            '{$pieces_to_remove}', '{$buying_price_per_piece}', '{$today}', '{$added_by}')";
                    $result = run_query_form($conn, $sql);



                    // log the item operation
                    item_log($conn, $added_by, $item_id, 'A stock negative was added of - '.$pieces_to_remove.' pieces', $mac);

                    // log user operation
                    user_log($conn, $added_by, 'Added stock negative of '.$pieces_to_remove.' pieces', $mac);

                    // back up the database
                    // backupDatabase($conn);

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);


                }


            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Item does not exist in stock!"
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



if($action === 'add-item-to-cart-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->unit_bp));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_price));
        $min_sell_price = mysqli_escape_string($conn, decrypt_data($request->min_sp));
        $recom_sell_price = mysqli_escape_string($conn, decrypt_data($request->recom_sp));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {
            $locked_status = check_item_lock_status($conn, $item_id, $location_id, $user_id);
            
            $today = get_today();

            if($locked_status) {

                $sql = "INSERT INTO temp_transactions
                        (item_id, location_id, unit_id, unit_quantity, buying_price, min_selling_price, recom_selling_price, selling_price, sold_by, date_sold)
                    VALUES ('{$item_id}', '{$location_id}', '{$unit_id}', '{$unit_qty}', '{$buying_price}', '{$min_sell_price}', '{$recom_sell_price}', '{$selling_price}', '{$user_id}', '{$today}')";
            
                $result = run_query_form($conn, $sql);

                // delete any duplicate cart entires
                delete_duplicate_cart_items($conn, $user_id);

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



if($action === 'update-item-cart') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $cart_id = mysqli_escape_string($conn, decrypt_data($request->cart_id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->unit_bp));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_price));
        $min_sell_price = mysqli_escape_string($conn, decrypt_data($request->min_sp));
        $recom_sell_price = mysqli_escape_string($conn, decrypt_data($request->recom_sp));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            // get the total pieces in the current cart quantity
            $sql = "SELECT * FROM temp_transactions WHERE id='{$cart_id}' AND sold_by='{$user_id}'";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) > 0) {

                $sql = "UPDATE temp_transactions
                        SET 
                        location_id='{$location_id}',
                        unit_id='{$unit_id}',
                        unit_quantity='{$unit_qty}',
                        buying_price='{$buying_price}',
                        min_selling_price='{$min_sell_price}',
                        recom_selling_price='{$recom_sell_price}',
                        selling_price='{$selling_price}',
                        date_sold='{$today}'
                        WHERE
                        id='{$cart_id}' AND sold_by='{$user_id}'";
                
                run_query_form($conn, $sql);

                // delete any duplicate cart entires
                delete_duplicate_cart_items($conn, $user_id);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success"
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Item is not in the list!",
                    "id" => $cart_id
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



if($action === 'update-item-cart-waiting') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $cart_id = mysqli_escape_string($conn, decrypt_data($request->cart_id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->unit_bp));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_price));
        $min_sell_price = mysqli_escape_string($conn, decrypt_data($request->min_sp));
        $recom_sell_price = mysqli_escape_string($conn, decrypt_data($request->recom_sp));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {
            
            $today = get_today();

            // get the total pieces in the current cart quantity
            $sql = "SELECT * FROM temp_waiting_customers WHERE id='{$cart_id}' AND sold_by='{$user_id}'";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) > 0) {

                $row = mysqli_fetch_assoc($result);

                // update the record
                $sql = "UPDATE temp_waiting_customers
                        SET 
                        location_id='{$location_id}',
                        unit_id='{$unit_id}',
                        unit_quantity='{$unit_qty}',
                        buying_price='{$buying_price}',
                        min_selling_price='{$min_sell_price}',
                        recom_selling_price='{$recom_sell_price}',
                        selling_price='{$selling_price}',
                        date_sold='{$today}'
                        WHERE
                        id='{$cart_id}' AND sold_by='{$user_id}'";
            
                run_query_form($conn, $sql);

                // delete any duplicate cart entires
                delete_duplicate_cart_items_waiting($conn, $user_id);

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
            
            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Item is not in the list!",
                    "id" => $cart_id
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




if($action === 'add-item-to-cart-cash-customer') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->unit_bp));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_price));
        $min_sell_price = mysqli_escape_string($conn, decrypt_data($request->min_sp));
        $recom_sell_price = mysqli_escape_string($conn, decrypt_data($request->recom_sp));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        try {
            //code...
        
            mysqli_begin_transaction($conn);

            if(check_device_mac($conn, $mac)) {

                $locked_status = check_item_lock_status($conn, $item_id, $location_id, $user_id);
                
                
                $today = get_today();

                if($locked_status) {

                    $sql = "INSERT INTO temp_transactions
                            (item_id, customer_id,
                            location_id,
                            unit_id,
                            unit_quantity, buying_price,
                            min_selling_price,
                            recom_selling_price, selling_price,
                            sold_by, date_sold)
                        VALUES ('{$item_id}', '{$customer_id}', '{$location_id}', '{$unit_id}', '{$unit_qty}', '{$buying_price}', '{$min_sell_price}', '{$recom_sell_price}', '{$selling_price}', '{$user_id}', '{$today}')";
                
                    $result = run_query_form($conn, $sql);

                    // reduce the qty from stock locations
                    $result1 = reduce_item_location_qty($conn, $item_id, $location_id, $total_pieces);


                    // unclock the item
                    unlock_item($conn, $item_id, $location_id, $user_id);


                    $user_name = get_user_record($conn, $user_id)['name'];

                    // log the item operation
                    item_log($conn, $user_id, $item_id, 'Item was added to cart with a quantity of - '.$unit_qty.' by '.$user_name, $mac);

                    // delete any duplicate cart entires
                    delete_duplicate_cart_items($conn, $user_id);


                    $items = get_stock_and_cart_items($conn, $user_id);

                    mysqli_commit($conn);

                    // backupDatabase($conn);

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
            //throw $th;
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage(),
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

if($action === 'add-item-to-print-list') {
    
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->unit_bp));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_price));
        $min_sell_price = mysqli_escape_string($conn, decrypt_data($request->min_sp));
        $recom_sell_price = mysqli_escape_string($conn, decrypt_data($request->recom_sp));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        
        if(check_device_mac($conn, $mac)) {

            
            $today = get_today();


            $sql = "INSERT INTO print_items
                    (item_id,
                    location_id,
                    unit_id,
                    unit_quantity, selling_price,
                    sold_by)
                VALUES ('{$item_id}', '{$location_id}',
                        '{$unit_id}', '{$unit_qty}',
                        '{$selling_price}', '{$user_id}')";
        
            $result = run_query_form($conn, $sql);

            // reduce the qty from stock locations
            // $result1 = reduce_item_location_qty($conn, $item_id, $location_id, $total_pieces);


            // unclock the item
            unlock_item($conn, $item_id, $location_id, $user_id);


            // $user_name = get_user_record($conn, $user_id)['name'];

            // log the item operation
            // item_log($conn, $user_id, $item_id, 'Item was added to cart with a quantity of - '.$unit_qty.' by '.$user_name, $mac);


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


if($action === 'delete-item-from-cart') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        try {

            mysqli_begin_transaction($conn);
        
            if(check_device_mac($conn, $mac)) {

                $locked_status = check_item_lock_status($conn, $item_id, $location_id, $added_by);

                $today = get_today();

                if($locked_status) {

                    $sql = "DELETE FROM temp_transactions WHERE id='{$id}' AND item_id='{$item_id}' AND location_id='{$location_id}' AND sold_by='{$added_by}'";
                    $result = run_query_form($conn, $sql);

                    // increase the qty in stock locations
                    increase_item_location_qty($conn, $item_id, $location_id, $total_pieces);

                    // unclock the item
                    unlock_item($conn, $item_id, $location_id, $added_by);

                    $user_name = get_user_record($conn, $added_by)['name'];

                    // log the item operation
                    item_log($conn, $added_by, $item_id, 'Item was deleted from cart by '.$user_name, $mac);

                    $items = get_stock_and_cart_items($conn, $added_by);

                    mysqli_commit($conn);

                    // backupDatabase($conn);

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
            //throw $th;
            //throw $th;
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage(),
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

if($action === 'delete-item-from-cart-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {

            $locked_status = check_item_lock_status($conn, $item_id, $location_id, $added_by);

            $today = get_today();

            if($locked_status) {

                $sql = "DELETE FROM temp_transactions WHERE id='{$id}' AND item_id='{$item_id}' AND location_id='{$location_id}' AND sold_by='{$added_by}'";
                $result = run_query_form($conn, $sql);

                // increase the qty in stock locations
                // increase_item_location_qty($conn, $item_id, $location_id, $total_pieces);

                // unclock the item
                // unlock_item($conn, $item_id, $location_id, $added_by);

                // $user_name = get_user_record($conn, $added_by)['name'];

                // log the item operation
                // item_log($conn, $added_by, $item_id, 'Item was deleted from cart by '.$user_name, $mac);

                // $items = get_stock_and_cart_items($conn, $added_by);


                // backupDatabase($conn);

                $items = get_stock_and_cart_items_new($conn, $added_by);
        
                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "stock" => $items['stock'],
                    "locations" => $items['shop_locations'],
                    "cartItems" => $items['cart_items'],
                    "waiting" => $items['waiting'],
                    "myCustomers" => $items['my_customers'],
                    "print_list" => $items['print_items']
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

if($action === 'delete-item-from-print-list') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();


            $sql = "DELETE FROM print_items WHERE id='{$id}' AND item_id='{$item_id}' AND location_id='{$location_id}' AND sold_by='{$added_by}'";
            $result = run_query_form($conn, $sql);

            // increase the qty in stock locations
            // increase_item_location_qty($conn, $item_id, $location_id, $total_pieces);

            // unclock the item
            unlock_item($conn, $item_id, $location_id, $added_by);

            // $user_name = get_user_record($conn, $added_by)['name'];

            // log the item operation
            // item_log($conn, $added_by, $item_id, 'Item was deleted from cart by '.$user_name, $mac);

            $items = get_stock_and_cart_items_new($conn, $added_by);


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

if($action === 'complete-cash-payment') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $payment_method = mysqli_escape_string($conn, decrypt_data($request->payment_method));
        $cash_amount = mysqli_escape_string($conn, decrypt_data($request->cash_amount));
        $mpesa_amount = mysqli_escape_string($conn, decrypt_data($request->mpesa_amount));
        $bank_amount = mysqli_escape_string($conn, decrypt_data($request->bank_amount));
        $date_of_sale = mysqli_escape_string($conn, decrypt_data($request->date_of_sale));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer = mysqli_escape_string($conn, decrypt_data($request->customer));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            // check if table is locked
            if(table_is_locked_by_user($conn, $added_by, 'sold_stock')) {
                
                $total = get_total_temp($conn, $added_by);

                if($total != (floatval($cash_amount) + floatval($mpesa_amount) + floatval($bank_amount))) {

                    $data_insert=array(

                        "status" => "error",
                        "message" => "Please complete the sale again!",
                        "paid" => (floatval($cash_amount) + floatval($mpesa_amount) + floatval($bank_amount)),
                        "total" => $total
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {

                    // check if there are items with more quantity than available quantity
                    $sql = "SELECT temp.item_id, temp.location_id,
                            SUM(temp.unit_quantity*su.unit_quantity) AS unit_quantity,
                            sl.location_quantity
                            FROM
                            temp_transactions AS temp
                            INNER JOIN
                            stock_units su
                            ON su.id=temp.unit_id AND su.item_id=temp.item_id

                            INNER JOIN
                            stock_locations sl
                            ON sl.item_id=temp.item_id AND sl.location_id=temp.location_id

                            GROUP BY item_id, location_id

                            HAVING unit_quantity>location_quantity";
                    
                    $result = run_query_form($conn, $sql);

                    if(mysqli_num_rows($result) < 1) {

                        // continue with the processing
                        $temp_items = get_temp_transactions($conn, $added_by);

                        if(count($temp_items) > 0) {
                            if(table_is_locked_by_user($conn, $added_by, 'sold_stock')) {

                                $max_id = get_sold_stock_max_transation_id($conn);
                                $max_id++;
                                $transaction_code = 'CT'.$max_id;

                                $today = get_today();

                                $sales_payment_id = insert_sales_payment_method($conn, $customer, $transaction_code, $payment_method, $cash_amount, $mpesa_amount, $bank_amount, $today, $added_by);

                                usleep(1);

                                $total_items = count($temp_items);

                                $total_inserted = 0;

                                while($total_items>0 && $total_inserted<$total) {

                                    $row = $temp_items[0];

                                    $id = decrypt_data($row['id']);

                                    $item_id = $row['item_id'];
                                    $location_id = $row['location_id'];
                                    $unit_id = $row['unit_id'];

                                    $unit_qty = $row['unit_qty'];
                                    $unit_bp = $row['unit_bp'];
                                    $min_sp = $row['min_sp'];
                                    $recom_sp = $row['recom_sp'];
                                    $unit_sp = $row['sp'];
                                    
                                    $unit = get_unit_record($conn, $unit_id);

                                    $total_sold_pieces = $unit['unit_quantity'] * $unit_qty;

                                    $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                                    $item_location_old_qty = $location_old_qty['location_quantity'];
                                    $item_total_old_qty = $location_old_qty['total_quantity'];

                                    $location_old_pieces = $item_location_old_qty;
                                    $total_old_pieces = $item_total_old_qty;

                                    // insert into sold stock
                                    insert_sold_stock($conn, 0, $max_id, $transaction_code, $item_id, $location_id, $location_old_pieces, $total_old_pieces, $unit_id, $unit_qty, $unit_bp, $min_sp, $recom_sp, $unit_sp, $sales_payment_id, $added_by, $date_of_sale);
                                    
                                    usleep(1);

                                    // delete the item from cart
                                    $sql3 = "DELETE FROM temp_transactions WHERE id='{$id}'";
                                    run_query_form($conn, $sql3);
    
                                    usleep(1);

                                    // reduce the qty from stock locations
                                    reduce_item_location_qty($conn, $item_id, $location_id, $total_sold_pieces);

                                    usleep(1);

                                    // log the item operation
                                    item_log($conn, $user_id, $item_id, 'Item cash sale with quantity of - '.$unit_qty.' '.$unit['unit_name'].' transaction_code '.$transaction_code.' by '.$user_name, $mac);

                                    usleep(1);

                                    // repeat the process with a new list
                                    $temp_items = get_temp_transactions($conn, $added_by);
                                    $total_items = count($temp_items);

                                    $total_inserted += (floatval($unit_sp) * floatval($unit_qty));

                                }


                                // get the shop name and description for printing
                                $shop_details = get_shop_details($conn, $added_by);

                                $shop_details['tcode'] = encrypt_data($transaction_code);


                                // log user operation
                                user_log($conn, $added_by, 'Completed a cash sale transaction with the code '.$transaction_code, $mac);

                                // delete any unsaved payments
                                delete_unsaved_sales_payments($conn);

                                // mysqli_commit($conn);

                                // backupDatabase($conn);

                                unlock_table($conn, 'sold_stock', $added_by);

                                $data_insert=array(
                                    "status" => "success",
                                    "message" => "success",
                                    "Shop" => $shop_details
                                );

                                echo json_encode($data_insert);
                                mysqli_close($conn);

                            }
                        }

                    } else {

                        // notify user of the less stock
                        $cart_items = [];
                        $cr = 0;

                        while($row=mysqli_fetch_assoc($result)) {

                            $item = get_item_details($conn, $row['item_id']);
                            $location = get_shop_location_record($conn, $row['location_id']);
                            
                            $cart_items[$cr]['id'] = encrypt_data($row['id']);
                            $cart_items[$cr]['item'] = $item;
                            $cart_items[$cr]['location'] = $location;
                            $cart_items[$cr]['qty_ordered'] = $row['unit_quantity'];
                            $cart_items[$cr]['curr_quantity'] = $row['location_quantity'];

                            $cr++;
                        }

                        $data_insert=array(
                            "status" => "error",
                            "message" => "Quantity less!",
                            "item" => $cart_items
                        );
                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    }
                    
                }

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



if($action === 'complete-cash-payment-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $payment_method = mysqli_escape_string($conn, decrypt_data($request->payment_method));
        $cash_amount = mysqli_escape_string($conn, decrypt_data($request->cash_amount));
        $mpesa_amount = mysqli_escape_string($conn, decrypt_data($request->mpesa_amount));
        $bank_amount = mysqli_escape_string($conn, decrypt_data($request->bank_amount));
        $date_of_sale = mysqli_escape_string($conn, decrypt_data($request->date_of_sale));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer = mysqli_escape_string($conn, decrypt_data($request->customer));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            // check if table is locked
            if(table_is_locked_by_user($conn, $added_by, 'sold_stock')) {
                
                $total = get_total_temp($conn, $added_by);

                if($total != (floatval($cash_amount) + floatval($mpesa_amount) + floatval($bank_amount))) {

                    unlock_table($conn, 'sold_stock', $added_by);

                    $data_insert=array(

                        "status" => "error",
                        "message" => "Please complete the sale again!",
                        "paid" => (floatval($cash_amount) + floatval($mpesa_amount) + floatval($bank_amount)),
                        "total" => $total
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {

                    // check if there are items with more quantity than available quantity
                    $sql = "SELECT temp.item_id, temp.location_id,
                            SUM(temp.unit_quantity*su.unit_quantity) AS unit_quantity,
                            sl.location_quantity
                            FROM
                            temp_transactions AS temp
                            INNER JOIN
                            stock_units su
                            ON su.id=temp.unit_id AND su.item_id=temp.item_id

                            INNER JOIN
                            stock_locations sl
                            ON sl.item_id=temp.item_id AND sl.location_id=temp.location_id

                            WHERE temp.customer_id=0 AND temp.sold_by='{$added_by}'
                            GROUP BY item_id, location_id

                            HAVING unit_quantity>location_quantity";
                    
                    $result = run_query_form($conn, $sql);

                    if(mysqli_num_rows($result) < 1) {

                        $user_name = get_user_record($conn, $added_by)['name'];
                        $today = get_today();

                        $temp_items = get_temp_transactions($conn, $added_by);

                        if(count($temp_items) > 0) {

                            // if(table_is_locked_by_user($conn, $added_by, 'sold_stock')) {

                                // continue with the processing
                                mysqli_begin_transaction($conn);

                                try {

                                    $max_id = get_sold_stock_max_transation_id($conn);
                                    $max_id++;
                                    $transaction_code = 'CT'.$max_id;

                                    $today = get_today();

                                    $sales_payment_id = insert_sales_payment_method($conn, $customer, $transaction_code, $payment_method, $cash_amount, $mpesa_amount, $bank_amount, $today, $added_by);


                                    $sold_stock_values = [];
                                    $temp_transaction_ids_to_delete = [];
                                    $stock_location_updates = [];
                                    $item_log_values = [];

                                    foreach ($temp_items as $row) {
                                        $id = decrypt_data($row['id']);
                                        $item_id = $row['item_id'];
                                        $location_id = $row['location_id'];
                                        $unit_id = $row['unit_id'];
                                        $unit_qty = $row['unit_qty'];
                                        $unit_bp = $row['unit_bp'];
                                        $unit_sp = $row['sp'];
                                        $min_sp = $row['min_sp'];
                                        $recom_sp = $row['recom_sp'];

                                        // get the unit details
                                        $unit = get_unit_record($conn, $unit_id);
                                        $total_sold_pieces = $unit['unit_quantity'] * $unit_qty;

                                        // get current stock quantity
                                        $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);
                                        $item_location_old_qty = $location_old_qty['location_quantity'];
                                        $item_total_old_qty = $location_old_qty['total_quantity'];
                                        $location_old_pieces = $item_location_old_qty;
                                        $total_old_pieces = $item_total_old_qty;


                                        // prepare data for batch inserts/updates
                                        $sold_stock_values[] = "({$customer_id}, {$max_id}, '{$transaction_code}', {$item_id},
                                                                {$location_id}, {$location_old_pieces}, {$total_old_pieces},
                                                                {$unit_id}, {$unit_qty}, {$unit_bp}, {$min_sp}, {$recom_sp},
                                                                {$unit_sp}, {$sales_payment_id}, {$added_by}, '{$date_of_sale}')";
                                        
                                        $temp_transaction_ids_to_delete[] = $id;


                                        if(!isset($stock_location_updates[$item_id . '_' . $location_id])) {
                                            $stock_location_updates[$item_id . '_' . $location_id] = 0;
                                        }

                                        $stock_location_updates[$item_id . '_' . $location_id] += $total_sold_pieces;


                                        $item_log_values[] = "({$item_id},
                                                            'Item cash sale with quantity of - {$unit_qty} {$unit['unit_name']} transaction_code {$transaction_code} by {$user_name}', 
                                                            '{$today}', {$added_by}, '{$mac}')";

                                    }


                                    // perform the batch inserts sold_stock
                                    if(!empty($sold_stock_values)) {
                                        $sql_sold_stock = "INSERT INTO sold_stock
                                                            (customer_id, transaction_id, transaction_code,
                                                                item_id, location_id, location_old_quantity,
                                                                total_old_quantity, unit_id, unit_quantity,
                                                                buying_price, min_selling_price, recom_selling_price,
                                                                selling_price, sales_payments_id,
                                                                sold_by, date_sold)
                                                            VALUES " .implode(",", $sold_stock_values);
                                        run_query_form($conn, $sql_sold_stock);
                                    }


                                    // perform batch delete temp_transactions
                                    if(!empty($temp_transaction_ids_to_delete)) {
                                        $sql_delete = "DELETE FROM temp_transactions
                                                        WHERE id IN
                                                        (" . implode(",", array_map(function($id) {return "'" . $id . "'"; }, $temp_transaction_ids_to_delete)) . ")";
                                        run_query_form($conn, $sql_delete);
                                    }


                                    // perform updates on stock_locations
                                    if(!empty($stock_location_updates)) {
                                        foreach ($stock_location_updates as $key => $qty_to_deduct) {
                                            list($item_id, $location_id) = explode('_', $key);

                                            reduce_item_location_qty($conn, $item_id, $location_id, $qty_to_deduct);
                                        }
                                    }


                                    // insert item logs
                                    if(!empty($item_log_values)) {
                                        $sql_logs = "INSERT INTO item_operations
                                                    (item_id, operation, date_done, done_by, mac)
                                                    VALUES " . implode(",", $item_log_values);
                                        run_query_form($conn, $sql_logs);
                                    }


                                    // get the shop name and description for printing
                                    $shop_details = get_shop_details($conn, $added_by);

                                    $shop_details['tcode'] = encrypt_data($transaction_code);


                                    // log user operation
                                    user_log($conn, $added_by, 'Completed a cash sale transaction with the code '.$transaction_code, $mac);

                                    // delete any unsaved payments
                                    delete_unsaved_sales_payments($conn);

                                    unlock_table($conn, 'sold_stock', $added_by);

                                    mysqli_commit($conn);

                                    $data_insert=array(
                                        "status" => "success",
                                        "message" => "success",
                                        "Shop" => $shop_details
                                    );

                                    echo json_encode($data_insert);
                                    mysqli_close($conn);
                                    
                                } catch (Exception $e) {
                                    mysqli_rollback($conn);

                                    unlock_table($conn, 'sold_stock', $added_by);

                                    $data_insert=array(
                                        "status" => "error",
                                        "message" => "Transaction failed: " . $e->getMessage()
                                    );

                                    echo json_encode($data_insert);
                                    mysqli_close($conn);
                                }

                            // }

                        } else {

                            unlock_table($conn, 'sold_stock', $added_by);

                            $data_insert=array(
                                "status" => "error",
                                "message" => "No items in cart",
                            );
                            echo json_encode($data_insert);
                            mysqli_close($conn);

                        }

                    } else {

                        // notify user of the less stock
                        $cart_items = [];

                        while($row=mysqli_fetch_assoc($result)) {

                            $item = get_item_details($conn, $row['item_id']);
                            $location = get_shop_location_record($conn, $row['location_id']);
                            
                            $cart_items[] = [
                                'item' => $item,
                                'location' => $location,
                                'qty_ordered' => $row['unit_quantity'],
                                'curr_quantity' => $row['location_quantity']
                            ];
                        }

                        unlock_table($conn, 'sold_stock', $added_by);

                        $data_insert=array(
                            "status" => "error",
                            "message" => "Quantity less!",
                            "items" => $cart_items
                        );
                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    }
                    
                }

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


if($action === 'unlock-user-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $uid = mysqli_escape_string($conn, decrypt_data($request->uid));
        
        unlock_all_user_locked_items($conn, $uid);
        
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


if($action === 'complete-print-list') {
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
         

            // delete the item from cart
            $sql3 = "DELETE FROM print_items WHERE sold_by='{$added_by}'";
            $result3 = run_query_form($conn, $sql3);



            // get the shop name and description for printing
            $shop_details = get_shop_details($conn, $added_by);

            $shop_details['tcode'] = encrypt_data('INV00');


            // log user operation
            // user_log($conn, $added_by, 'Completed a cash sale transaction with the code '.$transaction_code, $mac);


            $data_insert=array(
                "status" => "success",
                "message" => "success",
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




if($action === 'complete-cash-payment-cash-customer') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $payment_method = mysqli_escape_string($conn, decrypt_data($request->payment_method));
        $cash_amount = mysqli_escape_string($conn, decrypt_data($request->cash_amount));
        $mpesa_amount = mysqli_escape_string($conn, decrypt_data($request->mpesa_amount));
        $bank_amount = mysqli_escape_string($conn, decrypt_data($request->bank_amount));
        $date_of_sale = mysqli_escape_string($conn, decrypt_data($request->date_of_sale));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $name = mysqli_escape_string($conn, decrypt_data($request->name));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            // check if table is locked
            if(table_is_locked_by_user($conn, $added_by, 'sold_stock')) {
                
                $total = get_total_temp_customer($conn, $added_by, $customer_id);

                if($total != (floatval($cash_amount) + floatval($mpesa_amount) + floatval($bank_amount))) {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Please complete the sale again!",
                        "paid" => (floatval($cash_amount) + floatval($mpesa_amount) + floatval($bank_amount)),
                        "total" => $total
                    );

                    echo json_encode($data_insert);
                    mysqli_close($conn);
                } else {
                    
                    $temp_items = get_temp_transactions_cash_customer($conn, $added_by, $customer_id);

                    if(count($temp_items) > 0) {

                        if(table_is_locked_by_user($conn, $added_by, 'sold_stock')) {
                        
                            $max_id = get_sold_stock_max_transation_id($conn);
                            $max_id++;
                            $transaction_code = 'CT'.$max_id;

                            $today = get_today();

                            $sales_payment_id = insert_sales_payment_method($conn, $name, $transaction_code, $payment_method, $cash_amount, $mpesa_amount, $bank_amount, $today, $added_by);

                            usleep(1);

                            $total_items = count($temp_items);

                            $total_inserted = 0;

                            while($total_items>0 && $total_inserted<$total) {

                                $row = $temp_items[0];

                                $id = decrypt_data($row['id']);

                                // $customer_id = $row['customer_id'];

                                $item_id = $row['item_id'];
                                $location_id = $row['location_id'];
                                $unit_id = $row['unit_id'];

                                $unit_qty = $row['unit_qty'];
                                $unit_bp = $row['unit_bp'];
                                $min_sp = $row['min_sp'];
                                $recom_sp = $row['recom_sp'];
                                $unit_sp = $row['sp'];
                                
                                $unit = get_unit_record($conn, $unit_id);

                                $total_sold_pieces = $unit['unit_quantity'] * $unit_qty;


                                $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                                $item_location_old_qty = $location_old_qty['location_quantity'];
                                $item_total_old_qty = $location_old_qty['total_quantity'];

                                $location_old_pieces = $total_sold_pieces + $item_location_old_qty;
                                $total_old_pieces = $total_sold_pieces + $item_total_old_qty;


                                // insert into sold stock
                                insert_sold_stock($conn, $customer_id, $max_id, $transaction_code, $item_id, $location_id, $location_old_pieces, $total_old_pieces, $unit_id, $unit_qty, $unit_bp, $min_sp, $recom_sp, $unit_sp, $sales_payment_id, $added_by, $date_of_sale);

                                usleep(1);

                                // delete the item from cart
                                $sql3 = "DELETE FROM temp_transactions WHERE id='{$id}'";
                                $result3 = run_query_form($conn, $sql3);

                                usleep(1);

                                // repeat the process with a new list
                                $temp_items = get_temp_transactions_cash_customer($conn, $added_by, $customer_id);
                                $total_items = count($temp_items);

                                $total_inserted += (floatval($unit_sp) * floatval($unit_qty));

                            }

                            // get the shop name and description for printing
                            $shop_details = get_shop_details($conn, $added_by);

                            $shop_details['tcode'] = encrypt_data($transaction_code);


                            // log user operation
                            user_log($conn, $added_by, 'Completed a cash sale transaction with the code '.$transaction_code, $mac);

                            // delete any unsaved payments
                            delete_unsaved_sales_payments($conn);

                            // mysqli_commit($conn);

                            // backupDatabase($conn);

                            unlock_table($conn, 'sold_stock', $added_by);

                            $data_insert=array(
                                "status" => "success",
                                "message" => "success",
                                "Shop" => $shop_details
                            );

                            echo json_encode($data_insert);
                            mysqli_close($conn);

                        }

                    } else {
                        $data_insert=array(
                            "status" => "error",
                            "message" => "No items in list"
                        );

                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    }

                }
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


if($action === 'add-waiting-customer') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer = mysqli_escape_string($conn, decrypt_data($request->customer));

        if(check_device_mac($conn, $mac)) {

            $temp_items = get_temp_transactions($conn, $added_by);

            if(count($temp_items) > 0) {

                $total_items = count($temp_items);

                while($total_items>0) {

                    $row = $temp_items[0];

                    $id = decrypt_data($row['id']);

                    $item_id = $row['item_id'];
                    $location_id = $row['location_id'];
                    $unit_id = $row['unit_id'];

                    $unit_qty = $row['unit_qty'];
                    $unit_bp = $row['unit_bp'];
                    $min_sp = $row['min_sp'];
                    $recom_sp = $row['recom_sp'];
                    $unit_sp = $row['sp'];

                    $today = new DateTime();

                    $today = $today->format('Y-m-d H:i:s.u');

                    // insert the record in waiting list
                    $sql = "INSERT INTO temp_waiting_customers
                            (customer_name, item_id, location_id,
                            unit_id, unit_quantity, buying_price,
                            min_selling_price, recom_selling_price,
                            selling_price, sold_by, date_Sold)
                            VALUES
                            ('{$customer}', '{$item_id}', '{$location_id}',
                            '{$unit_id}', '{$unit_qty}', '{$unit_bp}',
                            '{$min_sp}', '{$recom_sp}', '{$unit_sp}',
                            '{$added_by}', '{$today}')";

                    run_query_form($conn, $sql);


                    // delete the item from cart
                    $sql3 = "DELETE FROM temp_transactions WHERE id='{$id}'";
                    run_query_form($conn, $sql3);

                    // repeat the process with a new list
                    $temp_items = get_temp_transactions($conn, $added_by);
                    $total_items = count($temp_items);

                    usleep(1);
                }


                // log user operation
                user_log($conn, $added_by, 'Added the customer '.$customer.' to waiting', $mac);

                // delete any duplicate cart entires
                delete_duplicate_cart_items_waiting($conn, $added_by);

                // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success"
                );

                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "No items to add!"
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



if($action === 'add-item-to-cart-waiting') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $customer = mysqli_escape_string($conn, decrypt_data($request->customer));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->unit_bp));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_price));
        $min_sell_price = mysqli_escape_string($conn, decrypt_data($request->min_sp));
        $recom_sell_price = mysqli_escape_string($conn, decrypt_data($request->recom_sp));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        try {

            mysqli_begin_transaction($conn);
        
            if(check_device_mac($conn, $mac)) {

                $locked_status = check_item_lock_status($conn, $item_id, $location_id, $user_id);
                
                
                $today = get_today();

                if($locked_status) {

                    $sql = "INSERT INTO temp_waiting_customers
                            (customer_name, item_id, location_id, unit_id, unit_quantity, buying_price, min_selling_price, recom_selling_price, selling_price, sold_by, date_sold)
                        VALUES ('{$customer}', '{$item_id}', '{$location_id}', '{$unit_id}', '{$unit_qty}', '{$buying_price}', '{$min_sell_price}', '{$recom_sell_price}', '{$selling_price}', '{$user_id}', '{$today}')";
                
                    $result = run_query_form($conn, $sql);

                    // reduce the qty from stock locations
                    $result1 = reduce_item_location_qty($conn, $item_id, $location_id, $total_pieces);


                    // unclock the item
                    unlock_item($conn, $item_id, $location_id, $user_id);


                    $user_name = get_user_record($conn, $user_id)['name'];

                    // log the item operation
                    item_log($conn, $user_id, $item_id, 'Item was added to waiting cart with a quantity of - '.$unit_qty.' by '.$user_name, $mac);

                    
                    // delete any duplicate cart entires
                    delete_duplicate_cart_items_waiting($conn, $user_id);
                    

                    $items = get_stock_and_cart_items($conn, $user_id);

                    mysqli_commit($conn);

                    // backupDatabase($conn);

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
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage(),
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



if($action === 'add-item-to-cart-waiting-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $customer = mysqli_escape_string($conn, decrypt_data($request->customer));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->unit_bp));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_price));
        $min_sell_price = mysqli_escape_string($conn, decrypt_data($request->min_sp));
        $recom_sell_price = mysqli_escape_string($conn, decrypt_data($request->recom_sp));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            $locked_status = check_item_lock_status($conn, $item_id, $location_id, $user_id);
            
            $today = get_today();

            if($locked_status) {

                $sql = "INSERT INTO temp_waiting_customers
                        (customer_name, item_id, location_id, unit_id, unit_quantity, buying_price, min_selling_price, recom_selling_price, selling_price, sold_by, date_sold)
                    VALUES ('{$customer}', '{$item_id}', '{$location_id}', '{$unit_id}', '{$unit_qty}', '{$buying_price}', '{$min_sell_price}', '{$recom_sell_price}', '{$selling_price}', '{$user_id}', '{$today}')";
            
                $result = run_query_form($conn, $sql);

                // reduce the qty from stock locations
                // $result1 = reduce_item_location_qty($conn, $item_id, $location_id, $total_pieces);

                // unclock the item
                // unlock_item($conn, $item_id, $location_id, $user_id);

                // $user_name = get_user_record($conn, $user_id)['name'];

                // log the item operation
                // item_log($conn, $user_id, $item_id, 'Item was added to waiting cart with a quantity of - '.$unit_qty.' by '.$user_name, $mac);

                
                // delete any duplicate cart entires
                delete_duplicate_cart_items_waiting($conn, $user_id);
                

                // $items = get_stock_and_cart_items($conn, $user_id);


                // backupDatabase($conn);

                // $items = get_stock_and_cart_items($conn, $user_id);
                $items = get_stock_and_cart_items_new($conn, $user_id);

                // // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "stock" => $items['stock'],
                    "locations" => $items['shop_locations'],
                    "cartItems" => $items['cart_items'],
                    "waiting" => $items['waiting'],
                    "myCustomers" => $items['my_customers'],
                    "print_list" => $items['print_items']
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

if($action === 'delete-item-from-cart-waiting') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $customer = mysqli_escape_string($conn, decrypt_data($request->customer));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        try {
            mysqli_begin_transaction($conn);
        
            if(check_device_mac($conn, $mac)) {

                $locked_status = check_item_lock_status($conn, $item_id, $location_id, $added_by);

                $today = get_today();

                if($locked_status) {

                    $sql = "DELETE FROM temp_waiting_customers WHERE id='{$id}' AND item_id='{$item_id}' AND location_id='{$location_id}' AND sold_by='{$added_by}'";
                    $result = run_query_form($conn, $sql);

                    // increase the qty in stock locations
                    increase_item_location_qty($conn, $item_id, $location_id, $total_pieces);

                    // unclock the item
                    unlock_item($conn, $item_id, $location_id, $added_by);

                    $user_name = get_user_record($conn, $added_by)['name'];

                    // log the item operation
                    item_log($conn, $added_by, $item_id, 'Item was deleted from waiting cart by '.$user_name, $mac);
                    

                    $items = get_stock_and_cart_items($conn, $added_by);

                    mysqli_commit($conn);

                    // backupDatabase($conn);

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
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage(),
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


if($action === 'delete-item-from-cart-waiting-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $customer = mysqli_escape_string($conn, decrypt_data($request->customer));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        
        if(check_device_mac($conn, $mac)) {

            $locked_status = check_item_lock_status($conn, $item_id, $location_id, $added_by);

            $today = get_today();

            if($locked_status) {

                $sql = "DELETE FROM temp_waiting_customers WHERE id='{$id}' AND item_id='{$item_id}' AND location_id='{$location_id}' AND sold_by='{$added_by}'";
                $result = run_query_form($conn, $sql);

                // increase the qty in stock locations
                // increase_item_location_qty($conn, $item_id, $location_id, $total_pieces);

                // unclock the item
                // unlock_item($conn, $item_id, $location_id, $added_by);

                // $user_name = get_user_record($conn, $added_by)['name'];

                // log the item operation
                // item_log($conn, $added_by, $item_id, 'Item was deleted from waiting cart by '.$user_name, $mac);

                $items = get_stock_and_cart_items_new($conn, $added_by);

                // mysqli_commit($conn);

                // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "stock" => $items['stock'],
                    "locations" => $items['shop_locations'],
                    "cartItems" => $items['cart_items'],
                    "waiting" => $items['waiting'],
                    "myCustomers" => $items['my_customers'],
                    "print_list" => $items['print_items']
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


if($action === 'delete-item-from-cart-debtor-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $customer = mysqli_escape_string($conn, decrypt_data($request->customer));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {

            $locked_status = check_item_lock_status($conn, $item_id, $location_id, $added_by);

            $today = get_today();

            if($locked_status) {

                $sql = "DELETE FROM temp_transactions WHERE customer_id='{$customer}' AND id='{$id}' AND item_id='{$item_id}' AND location_id='{$location_id}' AND sold_by='{$added_by}'";
                $result = run_query_form($conn, $sql);

                // increase the qty in stock locations
                // increase_item_location_qty($conn, $item_id, $location_id, $total_pieces);

                // unclock the item
                // unlock_item($conn, $item_id, $location_id, $added_by);

                // $user_name = get_user_record($conn, $added_by)['name'];

                // log the item operation
                // item_log($conn, $added_by, $item_id, 'Item was deleted from debtor cart by '.$user_name, $mac);

                $items = get_stock_and_cart_items_new($conn, $added_by);

                // backupDatabase($conn);

                $sql = "SELECT * FROM temp_transactions WHERE sold_by='{$added_by}' AND customer_id='{$customer}' ORDER BY id DESC";
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



if($action === 'complete-cash-payment-waiting') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $payment_method = mysqli_escape_string($conn, decrypt_data($request->payment_method));
        $cash_amount = mysqli_escape_string($conn, decrypt_data($request->cash_amount));
        $mpesa_amount = mysqli_escape_string($conn, decrypt_data($request->mpesa_amount));
        $bank_amount = mysqli_escape_string($conn, decrypt_data($request->bank_amount));
        $date_of_sale = mysqli_escape_string($conn, decrypt_data($request->date_of_sale));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $customer = mysqli_escape_string($conn, decrypt_data($request->customer));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            // check if table is locked
            if(table_is_locked_by_user($conn, $added_by, 'sold_stock')) {
            
                $total = get_total_temp_waiting($conn, $added_by, $customer);

                if($total != (floatval($cash_amount) + floatval($mpesa_amount) + floatval($bank_amount))) {

                    unlock_table($conn, 'sold_stock', $added_by);

                    $data_insert=array(
                        "status" => "error",
                        "message" => "Please complete the sale again!",
                        "paid" => (floatval($cash_amount) + floatval($mpesa_amount) + floatval($bank_amount)),
                        "total" => $total
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {

                    // check if there are items with more quantity than available quantity
                    $sql = "SELECT temp.item_id, temp.location_id,
                            SUM(temp.unit_quantity*su.unit_quantity) AS unit_quantity,
                            sl.location_quantity
                            FROM
                            temp_waiting_customers AS temp
                            INNER JOIN
                            stock_units su
                            ON su.id=temp.unit_id AND su.item_id=temp.item_id AND temp.customer_name='{$customer}' AND temp.sold_by='{$added_by}'

                            INNER JOIN
                            stock_locations sl
                            ON sl.item_id=temp.item_id AND sl.location_id=temp.location_id

                            GROUP BY item_id, location_id

                            HAVING unit_quantity>location_quantity";

                    $result = run_query_form($conn, $sql);

                    if(mysqli_num_rows($result) < 1) {

                        $user_name = get_user_record($conn, $added_by)['name'];
                        $today = get_today();

                        $temp_items = get_temp_waiting_customer_items($conn, $added_by, $customer);

                        if(count($temp_items) > 0) {

                            // if(table_is_locked_by_user($conn, $added_by, 'sold_stock')) {

                                // continue with the processing
                                mysqli_begin_transaction($conn);

                                try {

                                    $max_id = get_sold_stock_max_transation_id($conn);
                                    $max_id++;
                                    $transaction_code = 'CT'.$max_id;

                                    $today = get_today();

                                    $sales_payment_id = insert_sales_payment_method($conn, $customer, $transaction_code, $payment_method, $cash_amount, $mpesa_amount, $bank_amount, $today, $added_by);

                                    $sold_stock_values = [];
                                    $temp_transaction_ids_to_delete = [];
                                    $stock_location_updates = [];
                                    $item_log_values = [];

                                    foreach ($temp_items as $row) {

                                        $id = decrypt_data($row['id']);
                                        $item_id = $row['item_id'];
                                        $location_id = $row['location_id'];
                                        $unit_id = $row['unit_id'];
                                        $unit_qty = $row['unit_qty'];
                                        $unit_bp = $row['unit_bp'];
                                        $unit_sp = $row['sp'];
                                        $min_sp = $row['min_sp'];
                                        $recom_sp = $row['recom_sp'];

                                        // get the unit details
                                        $unit = get_unit_record($conn, $unit_id);
                                        $total_sold_pieces = $unit['unit_quantity'] * $unit_qty;

                                        // get current stock quantity
                                        $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);
                                        $item_location_old_qty = $location_old_qty['location_quantity'];
                                        $item_total_old_qty = $location_old_qty['total_quantity'];
                                        $location_old_pieces = $item_location_old_qty;
                                        $total_old_pieces = $item_total_old_qty;


                                        // prepare data for batch inserts/updates
                                        $sold_stock_values[] = "({$customer_id}, {$max_id}, '{$transaction_code}', {$item_id},
                                                                {$location_id}, {$location_old_pieces}, {$total_old_pieces},
                                                                {$unit_id}, {$unit_qty}, {$unit_bp}, {$min_sp}, {$recom_sp},
                                                                {$unit_sp}, {$sales_payment_id}, {$added_by}, '{$date_of_sale}')";
                                        
                                        $temp_transaction_ids_to_delete[] = $id;


                                        if(!isset($stock_location_updates[$item_id . '_' . $location_id])) {
                                            $stock_location_updates[$item_id . '_' . $location_id] = 0;
                                        }

                                        $stock_location_updates[$item_id . '_' . $location_id] += $total_sold_pieces;


                                        $item_log_values[] = "({$item_id},
                                                            'Item cash sale with quantity of - {$unit_qty} {$unit['unit_name']} transaction_code {$transaction_code} by {$user_name}', 
                                                            '{$today}', {$added_by}, '{$mac}')";

                                    }

                                    // perform the batch inserts sold_stock
                                    if(!empty($sold_stock_values)) {
                                        $sql_sold_stock = "INSERT INTO sold_stock
                                                            (customer_id, transaction_id, transaction_code,
                                                                item_id, location_id, location_old_quantity,
                                                                total_old_quantity, unit_id, unit_quantity,
                                                                buying_price, min_selling_price, recom_selling_price,
                                                                selling_price, sales_payments_id,
                                                                sold_by, date_sold)
                                                            VALUES " .implode(",", $sold_stock_values);
                                        run_query_form($conn, $sql_sold_stock);
                                    }

                                    // perform batch delete temp_transactions_waiting
                                    if(!empty($temp_transaction_ids_to_delete)) {
                                        $sql_delete = "DELETE FROM temp_waiting_customers
                                                        WHERE id IN
                                                        (" . implode(",", array_map(function($id) {return "'" . $id . "'"; }, $temp_transaction_ids_to_delete)) . ")";
                                        run_query_form($conn, $sql_delete);
                                    }

                                    // perform updates on stock_locations
                                    if(!empty($stock_location_updates)) {
                                        foreach ($stock_location_updates as $key => $qty_to_deduct) {
                                            list($item_id, $location_id) = explode('_', $key);

                                            reduce_item_location_qty($conn, $item_id, $location_id, $qty_to_deduct);
                                        }
                                    }

                                    // insert item logs
                                    if(!empty($item_log_values)) {
                                        $sql_logs = "INSERT INTO item_operations
                                                    (item_id, operation, date_done, done_by, mac)
                                                    VALUES " . implode(",", $item_log_values);
                                        run_query_form($conn, $sql_logs);
                                    }

                                    // get the shop name and description for printing
                                    $shop_details = get_shop_details($conn, $added_by);

                                    $shop_details['tcode'] = encrypt_data($transaction_code);

                                    // log user operation
                                    user_log($conn, $added_by, 'Completed a cash sale transaction with the code '.$transaction_code, $mac);

                                    // delete any unsaved payments
                                    delete_unsaved_sales_payments($conn);

                                    unlock_table($conn, 'sold_stock', $added_by);

                                    mysqli_commit($conn);

                                    $data_insert=array(
                                        "status" => "success",
                                        "message" => "success",
                                        "Shop" => $shop_details
                                    );

                                    echo json_encode($data_insert);
                                    mysqli_close($conn);


                                } catch (Exception $e) {

                                    mysqli_rollback($conn);

                                    unlock_table($conn, 'sold_stock', $added_by);

                                    $data_insert=array(
                                        "status" => "error",
                                        "message" => "Transaction failed: " . $e->getMessage()
                                    );

                                    echo json_encode($data_insert);
                                    mysqli_close($conn);
                                }

                            // }
                        }

                    } else {

                        // notify user of the less stock
                        $cart_items = [];

                        while($row=mysqli_fetch_assoc($result)) {

                            $item = get_item_details($conn, $row['item_id']);
                            $location = get_shop_location_record($conn, $row['location_id']);
                            
                            $cart_items[] = [
                                'item' => $item,
                                'location' => $location,
                                'qty_ordered' => $row['unit_quantity'],
                                'curr_quantity' => $row['location_quantity']
                            ];
                        }

                        unlock_table($conn, 'sold_stock', $added_by);

                        $data_insert=array(
                            "status" => "error",
                            "message" => "Quantity less!",
                            "items" => $cart_items
                        );
                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    
                    }

                }

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


if($action === 'add-item-to-cart-debtor') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->unit_bp));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_price));
        $min_sell_price = mysqli_escape_string($conn, decrypt_data($request->min_sp));
        $recom_sell_price = mysqli_escape_string($conn, decrypt_data($request->recom_sp));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        try {

            mysqli_begin_transaction($conn);
        
            if(check_device_mac($conn, $mac)) {

                $locked_status = check_item_lock_status($conn, $item_id, $location_id, $user_id);
                
                
                $today = get_today();

                if($locked_status) {

                    $sql = "INSERT INTO temp_transactions
                            (customer_id, item_id, location_id, unit_id, unit_quantity, buying_price, min_selling_price, recom_selling_price, selling_price, sold_by, date_sold)
                        VALUES ('{$customer_id}', '{$item_id}', '{$location_id}', '{$unit_id}', '{$unit_qty}', '{$buying_price}', '{$min_sell_price}', '{$recom_sell_price}', '{$selling_price}', '{$user_id}', '{$today}')";
                
                    $result = run_query_form($conn, $sql);

                    // reduce the qty from stock locations
                    $result1 = reduce_item_location_qty($conn, $item_id, $location_id, $total_pieces);


                    // unclock the item
                    unlock_item($conn, $item_id, $location_id, $user_id);


                    $user_name = get_user_record($conn, $user_id)['name'];

                    // log the item operation
                    item_log($conn, $user_id, $item_id, 'Item was added to cart with a quantity of - '.$unit_qty.' by '.$user_name, $mac);

                    // delete any duplicate cart entires
                    delete_duplicate_cart_items($conn, $user_id);

                    mysqli_commit($conn);
                    
                    // backupDatabase($conn);

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                }

            }

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage(),
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


if($action === 'add-item-to-cart-debtor-new') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->unit_bp));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_price));
        $min_sell_price = mysqli_escape_string($conn, decrypt_data($request->min_sp));
        $recom_sell_price = mysqli_escape_string($conn, decrypt_data($request->recom_sp));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            $locked_status = check_item_lock_status($conn, $item_id, $location_id, $user_id);
            
            
            $today = get_today();

            if($locked_status) {

                $sql = "INSERT INTO temp_transactions
                        (customer_id, item_id, location_id, unit_id, unit_quantity, buying_price, min_selling_price, recom_selling_price, selling_price, sold_by, date_sold)
                    VALUES ('{$customer_id}', '{$item_id}', '{$location_id}', '{$unit_id}', '{$unit_qty}', '{$buying_price}', '{$min_sell_price}', '{$recom_sell_price}', '{$selling_price}', '{$user_id}', '{$today}')";
            
                $result = run_query_form($conn, $sql);

                // reduce the qty from stock locations
                // $result1 = reduce_item_location_qty($conn, $item_id, $location_id, $total_pieces);

                // unclock the item
                // unlock_item($conn, $item_id, $location_id, $user_id);


                // $user_name = get_user_record($conn, $user_id)['name'];

                // log the item operation
                // item_log($conn, $user_id, $item_id, 'Item was added to cart with a quantity of - '.$unit_qty.' by '.$user_name, $mac);

                // delete any duplicate cart entires
                delete_duplicate_cart_items($conn, $user_id);

                // backupDatabase($conn);

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


if($action === 'complete-debtor-sale') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));
        $date_of_sale = mysqli_escape_string($conn, decrypt_data($request->date_of_sale));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $total_amount = mysqli_escape_string($conn, decrypt_data($request->total));

        if(check_device_mac($conn, $mac)) {

            // check if table is locked
            if(table_is_locked_by_user($conn, $added_by, 'sold_stock_debtors')) {
            
                $temp_items = get_temp_debtor_transactions($conn, $added_by, $customer_id);

                if(count($temp_items) > 0) {

                    // check if there are items with more quantity than available quantity
                    $sql = "SELECT temp.item_id, temp.location_id,
                            SUM(temp.unit_quantity*su.unit_quantity) AS unit_quantity,
                            sl.location_quantity
                            FROM
                            temp_transactions AS temp
                            INNER JOIN
                            stock_units su
                            ON su.id=temp.unit_id AND su.item_id=temp.item_id
                            AND temp.customer_id='{$customer_id}' AND temp.sold_by='{$added_by}'

                            INNER JOIN
                            stock_locations sl
                            ON sl.item_id=temp.item_id AND sl.location_id=temp.location_id

                            GROUP BY item_id, location_id

                            HAVING unit_quantity>location_quantity";
                    
                    $result = run_query_form($conn, $sql);

                    if(mysqli_num_rows($result) < 1) {

                        $user_name = get_user_record($conn, $added_by)['name'];
                        $today = get_today();

                        // if(table_is_locked_by_user($conn, $added_by, 'sold_stock_debtors')) {

                            // continue with the processing
                            mysqli_begin_transaction($conn);

                            try {

                                $balance_bf = get_debtor_balance($conn, $customer_id);
                        
                                $max_id = get_sold_stock_debtor_max_transation_id($conn);
                                $max_id++;
                                $transaction_code = 'DT'.$max_id;

                                $today = get_today();

                                $sold_stock_values = [];
                                $temp_transaction_ids_to_delete = [];
                                $stock_location_updates = [];
                                $item_log_values = [];

                                foreach ($temp_items as $row) {

                                        $id = decrypt_data($row['id']);
                                        $item_id = $row['item_id'];
                                        $location_id = $row['location_id'];
                                        $unit_id = $row['unit_id'];
                                        $unit_qty = $row['unit_qty'];
                                        $unit_bp = $row['unit_bp'];
                                        $unit_sp = $row['sp'];
                                        $min_sp = $row['min_sp'];
                                        $recom_sp = $row['recom_sp'];

                                        // get the unit details
                                        $unit = get_unit_record($conn, $unit_id);
                                        $total_sold_pieces = $unit['unit_quantity'] * $unit_qty;

                                        // get current stock quantity
                                        $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);
                                        $item_location_old_qty = $location_old_qty['location_quantity'];
                                        $item_total_old_qty = $location_old_qty['total_quantity'];
                                        $location_old_pieces = $item_location_old_qty;
                                        $total_old_pieces = $item_total_old_qty;


                                        // prepare data for batch inserts/updates
                                        $sold_stock_values[] = "({$customer_id}, {$max_id}, '{$transaction_code}', {$item_id},
                                                                {$location_id}, {$location_old_pieces}, {$total_old_pieces},
                                                                {$unit_id}, {$unit_qty}, {$unit_bp}, {$min_sp}, {$recom_sp},
                                                                {$unit_sp}, {$added_by}, '{$date_of_sale}')";
                                        
                                        $temp_transaction_ids_to_delete[] = $id;


                                        if(!isset($stock_location_updates[$item_id . '_' . $location_id])) {
                                            $stock_location_updates[$item_id . '_' . $location_id] = 0;
                                        }

                                        $stock_location_updates[$item_id . '_' . $location_id] += $total_sold_pieces;


                                        $item_log_values[] = "({$item_id},
                                                            'Item debtor sale with quantity of - {$unit_qty} {$unit['unit_name']} transaction_code {$transaction_code} by {$user_name}', 
                                                            '{$today}', {$added_by}, '{$mac}')";

                                }


                                // perform the batch inserts sold_stock
                                if(!empty($sold_stock_values)) {
                                    $sql_sold_stock = "INSERT INTO sold_stock_debtors
                                                        (customer_id, transaction_id, transaction_code,
                                                            item_id, location_id, location_old_quantity,
                                                            total_old_quantity, unit_id, unit_quantity,
                                                            buying_price, min_selling_price, recom_selling_price,
                                                            selling_price,
                                                            sold_by, date_sold)
                                                        VALUES " .implode(",", $sold_stock_values);
                                    run_query_form($conn, $sql_sold_stock);
                                }

                                // perform batch delete temp_transactions_waiting
                                if(!empty($temp_transaction_ids_to_delete)) {
                                    $sql_delete = "DELETE FROM temp_transactions
                                                    WHERE id IN
                                                    (" . implode(",", array_map(function($id) {return "'" . $id . "'"; }, $temp_transaction_ids_to_delete)) . ")";
                                    run_query_form($conn, $sql_delete);
                                }

                                // perform updates on stock_locations
                                if(!empty($stock_location_updates)) {
                                    foreach ($stock_location_updates as $key => $qty_to_deduct) {
                                        list($item_id, $location_id) = explode('_', $key);

                                        reduce_item_location_qty($conn, $item_id, $location_id, $qty_to_deduct);
                                    }
                                }

                                // insert item logs
                                if(!empty($item_log_values)) {
                                    $sql_logs = "INSERT INTO item_operations
                                                (item_id, operation, date_done, done_by, mac)
                                                VALUES " . implode(",", $item_log_values);
                                    run_query_form($conn, $sql_logs);
                                }


                                // find if the debtor has a payment record
                                // if no payment method insert the first with 0 payment to capture the balance before
                                $sql = "SELECT * FROM debtors_payments WHERE customer_id='{$customer_id}'";
                                $result = run_query_form($conn, $sql);

                                if(mysqli_num_rows($result) < 1) {
                                    $sql = "INSERT INTO debtors_payments
                                            (customer_id,
                                            balance_before,
                                            cash,
                                            mpesa,
                                            bank,
                                            amount_credited,
                                            date_of_payment,
                                            date_created,
                                            added_by)
                                            VALUES
                                            ('{$customer_id}', '{$total_amount}', 0, 0, 0, 0, '{$today}', '{$today}', '{$added_by}')";
                                    $result = run_query_form($conn, $sql);
                                }


                                // get the shop name and description for printing
                                $shop_details = get_shop_details($conn, $added_by);

                                $shop_details['tcode'] = encrypt_data($transaction_code);

                                // log user operation
                                user_log($conn, $added_by, 'Completed a debtor sale transaction with the code '.$transaction_code, $mac);

                                // delete any unsaved payments
                                delete_unsaved_sales_payments($conn);

                                unlock_table($conn, 'sold_stock_debtors', $added_by);

                                mysqli_commit($conn);

                                $shop_details['balance_bf'] = $balance_bf;
                                
                                $data_insert=array(
                                    "status" => "success",
                                    "message" => "success",
                                    "Shop" => $shop_details,
                                    "balance_bf" => $balance_bf
                                );

                                echo json_encode($data_insert);
                                mysqli_close($conn);

                            } catch (Exception $e) {
                                mysqli_rollback($conn);

                                unlock_table($conn, 'sold_stock_debtors', $added_by);

                                $data_insert=array(
                                    "status" => "error",
                                    "message" => "Transaction failed: " . $e->getMessage()
                                );

                                echo json_encode($data_insert);
                                mysqli_close($conn);
                            }

                        // }

                    } else {

                        // notify user of the less stock
                        $cart_items = [];

                        while($row=mysqli_fetch_assoc($result)) {

                            $item = get_item_details($conn, $row['item_id']);
                            $location = get_shop_location_record($conn, $row['location_id']);
                            
                            $cart_items[] = [
                                'item' => $item,
                                'location' => $location,
                                'qty_ordered' => $row['unit_quantity'],
                                'curr_quantity' => $row['location_quantity']
                            ];
                        }

                        unlock_table($conn, 'sold_stock_debtors', $added_by);

                        $data_insert=array(
                            "status" => "error",
                            "message" => "Quantity less!",
                            "items" => $cart_items
                        );
                        echo json_encode($data_insert);
                        mysqli_close($conn);

                    }

                } else {
                    unlock_table($conn, 'sold_stock_debtors', $added_by);

                    $data_insert=array(
                        "status" => "error",
                        "message" => "No items in cart",
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);
                }
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



if($action === 'add-item-to-cart-transfer') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_qty = mysqli_escape_string($conn, decrypt_data($request->unit_qty));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->unit_bp));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->unit_price));
        $min_sell_price = mysqli_escape_string($conn, decrypt_data($request->min_sp));
        $recom_sell_price = mysqli_escape_string($conn, decrypt_data($request->recom_sp));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        try {

            mysqli_begin_transaction($conn);
            
            if(check_device_mac($conn, $mac)) {

                $locked_status = check_item_lock_status($conn, $item_id, $location_id, $user_id);
                
                
                $today = get_today();

                if($locked_status) {

                    $sql = "INSERT INTO temp_transactions
                            (customer_id, item_id, location_id, unit_id, unit_quantity, buying_price, min_selling_price, recom_selling_price, selling_price, sold_by, date_sold)
                        VALUES ('{$customer_id}', '{$item_id}', '{$location_id}', '{$unit_id}', '{$unit_qty}', '{$buying_price}', '{$min_sell_price}', '{$recom_sell_price}', '{$selling_price}', '{$user_id}', '{$today}')";
                
                    $result = run_query_form($conn, $sql);

                    // reduce the qty from stock locations
                    // $result1 = reduce_item_location_qty($conn, $item_id, $location_id, $total_pieces);


                    // unclock the item
                    // unlock_item($conn, $item_id, $location_id, $user_id);


                    // $user_name = get_user_record($conn, $user_id)['name'];

                    // log the item operation
                    // item_log($conn, $user_id, $item_id, 'Item was added to cart with a quantity of - '.$unit_qty.' by '.$user_name, $mac);

                    // delete any duplicate cart entires
                    delete_duplicate_cart_items($conn, $user_id);

                    mysqli_commit($conn);

                    // backupDatabase($conn);

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                }

            }

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage(),
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

if($action === 'complete-transfer-sale') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));
        $date_of_sale = mysqli_escape_string($conn, decrypt_data($request->date_of_sale));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $total_amount = mysqli_escape_string($conn, decrypt_data($request->total));

        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);

            // check if table is locked
            if(table_is_locked_by_user($conn, $added_by, 'sold_stock_transfers')) {

                $temp_items = get_temp_debtor_transactions($conn, $added_by, $customer_id);

                if(count($temp_items) > 0) {

                    // check if there are items with more quantity than available quantity
                    $sql = "SELECT temp.item_id, temp.location_id,
                            SUM(temp.unit_quantity*su.unit_quantity) AS unit_quantity,
                            sl.location_quantity
                            FROM
                            temp_transactions AS temp
                            INNER JOIN
                            stock_units su
                            ON su.id=temp.unit_id AND su.item_id=temp.item_id
                            AND temp.customer_id='{$customer_id}' AND temp.sold_by='{$added_by}'

                            INNER JOIN
                            stock_locations sl
                            ON sl.item_id=temp.item_id AND sl.location_id=temp.location_id

                            GROUP BY item_id, location_id

                            HAVING unit_quantity>location_quantity";

                    $result = run_query_form($conn, $sql);

                    if(mysqli_num_rows($result) < 1) {

                        $user_name = get_user_record($conn, $added_by)['name'];
                        $today = get_today();

                        // if(table_is_locked_by_user($conn, $added_by, 'sold_stock_transfers')) {

                            // continue with the processing
                            mysqli_begin_transaction($conn);

                            try {

                                $max_id = get_sold_stock_transfer_max_transation_id($conn);
                                $max_id++;
                                $transaction_code = 'TT'.$max_id;

                                $today = get_today();


                                $sold_stock_values = [];
                                $temp_transaction_ids_to_delete = [];
                                $stock_location_updates = [];
                                $item_log_values = [];

                                foreach ($temp_items as $row) {

                                        $id = decrypt_data($row['id']);
                                        $item_id = $row['item_id'];
                                        $location_id = $row['location_id'];
                                        $unit_id = $row['unit_id'];
                                        $unit_qty = $row['unit_qty'];
                                        $unit_bp = $row['unit_bp'];
                                        $unit_sp = $row['sp'];
                                        $min_sp = $row['min_sp'];
                                        $recom_sp = $row['recom_sp'];

                                        // get the unit details
                                        $unit = get_unit_record($conn, $unit_id);
                                        $total_sold_pieces = $unit['unit_quantity'] * $unit_qty;

                                        // get current stock quantity
                                        $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);
                                        $item_location_old_qty = $location_old_qty['location_quantity'];
                                        $item_total_old_qty = $location_old_qty['total_quantity'];
                                        $location_old_pieces = $item_location_old_qty;
                                        $total_old_pieces = $item_total_old_qty;


                                        // prepare data for batch inserts/updates
                                        $sold_stock_values[] = "({$customer_id}, {$max_id}, '{$transaction_code}', {$item_id},
                                                                {$location_id}, {$location_old_pieces}, {$total_old_pieces},
                                                                {$unit_id}, {$unit_qty}, {$unit_bp},
                                                                {$added_by}, '{$date_of_sale}')";
                                        
                                        $temp_transaction_ids_to_delete[] = $id;


                                        if(!isset($stock_location_updates[$item_id . '_' . $location_id])) {
                                            $stock_location_updates[$item_id . '_' . $location_id] = 0;
                                        }

                                        $stock_location_updates[$item_id . '_' . $location_id] += $total_sold_pieces;


                                        $item_log_values[] = "({$item_id},
                                                            'Item transfer sale with quantity of - {$unit_qty} {$unit['unit_name']} transaction_code {$transaction_code} by {$user_name}', 
                                                            '{$today}', {$added_by}, '{$mac}')";

                                }


                                // perform the batch inserts sold_stock
                                if(!empty($sold_stock_values)) {

                                    $sql_sold_stock = "INSERT INTO sold_stock_transfers
                                                        (customer_id, transaction_id, transaction_code,
                                                            item_id, location_id, location_old_quantity,
                                                            total_old_quantity, unit_id, unit_quantity,
                                                            buying_price,
                                                            sold_by, date_transfered)
                                                        VALUES " .implode(",", $sold_stock_values);
                                    run_query_form($conn, $sql_sold_stock);
                                }

                                // perform batch delete temp_transactions_waiting
                                if(!empty($temp_transaction_ids_to_delete)) {
                                    $sql_delete = "DELETE FROM temp_transactions
                                                    WHERE id IN
                                                    (" . implode(",", array_map(function($id) {return "'" . $id . "'"; }, $temp_transaction_ids_to_delete)) . ")";
                                    run_query_form($conn, $sql_delete);
                                }

                                // perform updates on stock_locations
                                if(!empty($stock_location_updates)) {
                                    foreach ($stock_location_updates as $key => $qty_to_deduct) {
                                        list($item_id, $location_id) = explode('_', $key);

                                        reduce_item_location_qty($conn, $item_id, $location_id, $qty_to_deduct);
                                    }
                                }

                                // insert item logs
                                if(!empty($item_log_values)) {
                                    $sql_logs = "INSERT INTO item_operations
                                                (item_id, operation, date_done, done_by, mac)
                                                VALUES " . implode(",", $item_log_values);
                                    run_query_form($conn, $sql_logs);
                                }


                                // get the shop name and description for printing
                                $shop_details = get_shop_details($conn, $added_by);

                                $shop_details['tcode'] = encrypt_data($transaction_code);


                                // log user operation
                                user_log($conn, $added_by, 'Completed a transfer sale transaction with the code '.$transaction_code, $mac);

                                unlock_table($conn, 'sold_stock_transfers', $added_by);

                                mysqli_commit($conn);
                                
                                $data_insert=array(
                                    "status" => "success",
                                    "message" => "success",
                                    "Shop" => $shop_details
                                );

                                echo json_encode($data_insert);
                                mysqli_close($conn);

                            } catch (Exception $e) {
                                mysqli_rollback($conn);

                                unlock_table($conn, 'sold_stock_transfers', $added_by);

                                $data_insert=array(
                                    "status" => "error",
                                    "message" => "Transaction failed: " . $e->getMessage()
                                );

                                echo json_encode($data_insert);
                                mysqli_close($conn);
                            }

                        // }

                    } else {
                        // notify user of the less stock
                        $cart_items = [];

                        while($row=mysqli_fetch_assoc($result)) {

                            $item = get_item_details($conn, $row['item_id']);
                            $location = get_shop_location_record($conn, $row['location_id']);
                            
                            $cart_items[] = [
                                'item' => $item,
                                'location' => $location,
                                'qty_ordered' => $row['unit_quantity'],
                                'curr_quantity' => $row['location_quantity']
                            ];
                        }

                        unlock_table($conn, 'sold_stock_transfers', $added_by);

                        $data_insert=array(
                            "status" => "error",
                            "message" => "Quantity less!",
                            "items" => $cart_items
                        );
                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    }

                } else {
                    unlock_table($conn, 'sold_stock_transfers', $added_by);

                    $data_insert=array(
                        "status" => "error",
                        "message" => "No items in cart",
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);
                }

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


if($action === 'reverse-cash-sale') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $quantity = mysqli_escape_string($conn, decrypt_data($request->quantity));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        try {

            mysqli_begin_transaction($conn);

            if(check_device_mac($conn, $mac)) {

                $locked_status = check_item_lock_status($conn, $item_id, $location_id, $added_by);
                
                // get the record
                $sql = "SELECT * FROM sold_stock WHERE id='{$id}' AND item_id='{$item_id}' AND location_id='{$location_id}'";
                $result = run_query_form($conn, $sql);

                if(mysqli_num_rows($result) > 0) {

                    $record = mysqli_fetch_assoc($result);

                    $transaction_code = $record['transaction_code'];
                    $selling_price = $record['selling_price'];
                    $buying_price = $record['buying_price'];

                    $sales_payment_id = $record['sales_payments_id'];

                    
                    // calculate the selling price and buying_price for the unit returned if the unit returned is not same as that sold
                    $unit_bp = $buying_price;
                    $unit_sp = $selling_price;
                    if($unit_id != $record['unit_id']) {

                        $unit_sold = get_unit_record($conn, $record['unit_id']);
                        $unit_sold_qty = $unit_sold['unit_quantity'];

                        $unit_bp_per_piece = $buying_price/$unit_sold_qty;
                        $unit_sp_per_piece = $selling_price/$unit_sold_qty;

                        $unit_to_reverse = get_unit_record($conn, $unit_id);

                        $unit_bp = $unit_bp_per_piece * $unit_to_reverse['unit_quantity'];
                        $unit_sp = $unit_sp_per_piece * $unit_to_reverse['unit_quantity'];

                    }

                    $today = get_today();


                    // get the item total old quantity first
                    $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                    $item_location_old_qty = $location_old_qty['location_quantity'];
                    $item_total_old_qty = $location_old_qty['total_quantity'];




                    $sql = "INSERT INTO sold_stock_reversed
                            (sale_type,
                            sale_id,
                            transaction_code,
                            item_id,
                            unit_id,
                            location_id,
                            quantity_returned,
                            total_old_quantity,
                            customer_id,
                            buying_price,
                            selling_price,
                            date_returned,
                            sold_by)
                            VALUES
                            ('cash', '{$id}', '{$transaction_code}',
                            '{$item_id}', '{$unit_id}', '{$location_id}',
                            '{$quantity}', '{$item_location_old_qty}', 0, '{$unit_bp}', '{$unit_sp}',
                            '{$today}', '{$added_by}')";

                    $result = run_query_form($conn, $sql);

                    // increase the quantity in stock locations

                    // get the unit record to reverse
                    $unit_record = get_unit_record($conn, $unit_id);

                    $total_pieces_to_increase = floatval($unit_record['unit_quantity']) * floatval($quantity);

                    increase_item_location_qty($conn, $item_id, $location_id, $total_pieces_to_increase);


                    //  update the sales payment amount reversed
                    $amount_to_reversed = $unit_sp * $quantity;

                    $sql = "SELECT * FROM sales_payments WHERE id='{$sales_payment_id}'";
                    $result = run_query_form($conn, $sql);

                    $record = mysqli_fetch_assoc($result);

                    $total_reverse = $record['amount_reversed'] + $amount_to_reversed;

                    $sql = "UPDATE sales_payments SET amount_reversed='{$total_reverse}' WHERE id='{$sales_payment_id}'";
                    $result = run_query_form($conn, $sql);



                    // unclock the item
                    unlock_item($conn, $item_id, $location_id, $added_by);


                    $user_name = get_user_record($conn, $added_by)['name'];

                    // log the item operation
                    item_log($conn, $added_by, $item_id, 'Item sale was reversed with a quantity of - '.$quantity.' '.$unit_record['unit_name'].' by '.$user_name, $mac);

                    mysqli_commit($conn);

                    // backupDatabase($conn);

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Transaction record does not exist!"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);
                }

            }

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage(),
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    }
    catch (Exception $e) {
        
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


if($action === 'reverse-debtor-sale') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $quantity = mysqli_escape_string($conn, decrypt_data($request->quantity));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        try {

            mysqli_begin_transaction($conn);

            if(check_device_mac($conn, $mac)) {

                
                $locked_status = check_item_lock_status($conn, $item_id, $location_id, $added_by);
                
                // get the record
                $sql = "SELECT * FROM sold_stock_debtors WHERE customer_id='{$customer_id}' AND id='{$id}' AND item_id='{$item_id}' AND location_id='{$location_id}'";
                $result = run_query_form($conn, $sql);

                if(mysqli_num_rows($result) > 0) {

                    $record = mysqli_fetch_assoc($result);

                    $transaction_code = $record['transaction_code'];
                    $selling_price = $record['selling_price'];
                    $buying_price = $record['buying_price'];

                    
                    // calculate the selling and buying price for the unit returned if the unit returned is not same as that sold
                    $unit_bp = $buying_price;
                    $unit_sp = $selling_price;
                    if($unit_id != $record['unit_id']) {

                        $unit_sold = get_unit_record($conn, $record['unit_id']);
                        $unit_sold_qty = $unit_sold['unit_quantity'];

                        $unit_bp_per_piece = $buying_price/$unit_sold_qty;
                        $unit_sp_per_piece = $selling_price/$unit_sold_qty;

                        $unit_to_reverse = get_unit_record($conn, $unit_id);

                        $unit_bp = $unit_bp_per_piece * $unit_to_reverse['unit_quantity'];
                        $unit_sp = $unit_sp_per_piece * $unit_to_reverse['unit_quantity'];

                    }

                    $today = get_today();

                    // get the item total old quantity first
                    $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                    $item_location_old_qty = $location_old_qty['location_quantity'];
                    $item_total_old_qty = $location_old_qty['total_quantity'];




                    $sql = "INSERT INTO sold_stock_reversed
                            (sale_type,
                            sale_id,
                            transaction_code,
                            item_id,
                            unit_id,
                            location_id,
                            quantity_returned,
                            total_old_quantity,
                            customer_id,
                            buying_price,
                            selling_price,
                            date_returned,
                            sold_by)
                            VALUES
                            ('debtor', '{$id}', '{$transaction_code}',
                            '{$item_id}', '{$unit_id}', '{$location_id}',
                            '{$quantity}', '{$item_total_old_qty}', '{$customer_id}', '{$unit_bp}',
                            '{$unit_sp}', '{$today}', '{$added_by}')";

                    $result = run_query_form($conn, $sql);

                    // increase the quantity in stock locations

                    // get the unit record to reverse
                    $unit_record = get_unit_record($conn, $unit_id);

                    $total_pieces_to_increase = floatval($unit_record['unit_quantity']) * floatval($quantity);

                    increase_item_location_qty($conn, $item_id, $location_id, $total_pieces_to_increase);


                    // insert the total as amount credited for debtor payments
                    $sql="SELECT * FROM debtors_payments WHERE customer_id='{$customer_id}' AND
                            DATE_FORMAT(date_created, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') AND
                            added_by='{$added_by}' AND
                            amount_credited>0 AND cash<1 AND bank<1 AND mpesa<1";
                    $result = run_query_form($conn, $sql);

                    if(mysqli_num_rows($result) > 0) {
                        $record = mysqli_fetch_assoc($result);

                        $id = $record['id'];
                        $amount_credited = $record['amount_credited'];

                        $new_amount = $amount_credited + ($unit_sp * $quantity);
                        $sql = "UPDATE debtors_payments SET amount_credited='{$new_amount}' WHERE id='{$id}'";
                        $result = run_query_form($conn, $sql);

                    } else {

                        $credit_amount = $unit_sp * $quantity;

                        $sql = "INSERT INTO debtors_payments
                                (customer_id,
                                balance_before,
                                cash,
                                mpesa,
                                bank,
                                amount_credited,
                                date_of_payment,
                                date_created,
                                added_by)
                                VALUES
                                ('{$customer_id}', 0, 0, 0, 0, '{$credit_amount}', '{$today}', '{$today}', '{$added_by}')";
                        $result = run_query_form($conn, $sql);

                    }


                    // unclock the item
                    unlock_item($conn, $item_id, $location_id, $added_by);


                    $user_name = get_user_record($conn, $added_by)['name'];

                    // log the item operation
                    item_log($conn, $added_by, $item_id, 'Item debtor sale was reversed with a quantity of - '.$quantity.' '.$unit_record['unit_name'].' by '.$user_name, $mac);

                    mysqli_commit($conn);

                    // backupDatabase($conn);

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Transaction record does not exist!"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);
                }


            }

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage(),
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }



    }
    catch (Exception $e) {
        
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

if($action === 'reverse-transfer-sale') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->cid));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $quantity = mysqli_escape_string($conn, decrypt_data($request->quantity));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        try {

            mysqli_begin_transaction($conn);

            if(check_device_mac($conn, $mac)) {

                
                $locked_status = check_item_lock_status($conn, $item_id, $location_id, $added_by);
                
                // get the record
                $sql = "SELECT * FROM sold_stock_transfers WHERE customer_id='{$customer_id}' AND id='{$id}' AND item_id='{$item_id}' AND location_id='{$location_id}'";
                $result = run_query_form($conn, $sql);

                if(mysqli_num_rows($result) > 0) {

                    $record = mysqli_fetch_assoc($result);

                    $transaction_code = $record['transaction_code'];
                    $selling_price = $record['buying_price'];
                    $buying_price = $record['buying_price'];
                    
                    // calculate the selling and buying price for the unit returned if the unit returned is not same as that sold
                    $unit_bp = $buying_price;
                    $unit_sp = $selling_price;
                    if($unit_id != $record['unit_id']) {

                        $unit_sold = get_unit_record($conn, $record['unit_id']);
                        $unit_sold_qty = $unit_sold['unit_quantity'];

                        $unit_bp_per_piece = $buying_price/$unit_sold_qty;
                        $unit_sp_per_piece = $selling_price/$unit_sold_qty;

                        $unit_to_reverse = get_unit_record($conn, $unit_id);

                        $unit_bp = $unit_bp_per_piece * $unit_to_reverse['unit_quantity'];
                        $unit_sp = $unit_sp_per_piece * $unit_to_reverse['unit_quantity'];

                    }

                    $today = get_today();

                    // get the item total old quantity first
                    $location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                    $item_location_old_qty = $location_old_qty['location_quantity'];
                    $item_total_old_qty = $location_old_qty['total_quantity'];



                    $sql = "INSERT INTO sold_stock_reversed
                            (sale_type,
                            sale_id,
                            transaction_code,
                            item_id,
                            unit_id,
                            location_id,
                            quantity_returned,
                            total_old_quantity,
                            customer_id,
                            buying_price,
                            selling_price,
                            date_returned,
                            sold_by)
                            VALUES
                            ('transfer', '{$id}', '{$transaction_code}',
                            '{$item_id}', '{$unit_id}', '{$location_id}',
                            '{$quantity}', '{$item_total_old_qty}', '{$customer_id}', '{$unit_bp}', '{$unit_sp}',
                            '{$today}', '{$added_by}')";

                    $result = run_query_form($conn, $sql);

                    // increase the quantity in stock locations

                    // get the unit record to reverse
                    $unit_record = get_unit_record($conn, $unit_id);

                    $total_pieces_to_increase = floatval($unit_record['unit_quantity']) * floatval($quantity);

                    increase_item_location_qty($conn, $item_id, $location_id, $total_pieces_to_increase);



                    // insert the total as amount credited for transfer payments
                    $sql="SELECT * FROM transfer_payments WHERE customer_id='{$customer_id}' AND
                            DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') AND
                            added_by='{$added_by}' AND
                            amount_credited>0 AND cash<1 AND mpesa<1 AND bank<1";
                    $result = run_query_form($conn, $sql);

                    if(mysqli_num_rows($result) > 0) {
                        $record = mysqli_fetch_assoc($result);

                        $id = $record['id'];
                        $amount_credited = $record['amount_credited'];

                        $new_amount = $amount_credited + ($unit_sp * $quantity);
                        $sql = "UPDATE transfer_payments SET amount_credited='{$new_amount}' WHERE id='{$id}'";
                        $result = run_query_form($conn, $sql);

                    } else {

                        $credit_amount = $unit_sp * $quantity;

                        $sql = "INSERT INTO transfer_payments
                                (customer_id, payment_channel, cash, mpesa, bank, amount_credited, added_by, date_paid)
                                VALUES
                                ('{$customer_id}', '', 0, 0, 0, '{$credit_amount}', '{$added_by}', '{$today}')";
                        $result = run_query_form($conn, $sql);

                    }



                    // unclock the item
                    unlock_item($conn, $item_id, $location_id, $added_by);

                    $user_name = get_user_record($conn, $added_by)['name'];

                    // log the item operation
                    item_log($conn, $added_by, $item_id, 'Item transfer sale was reversed with a quantity of - '.$quantity.' '.$unit_record['unit_name'].' by '.$user_name, $mac);

                    mysqli_commit($conn);

                    // backupDatabase($conn);

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Transaction record does not exist!"
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);
                }


            }

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage(),
            );
            echo json_encode($data_insert);
            mysqli_close($conn);
        }

    }
    catch (Exception $e) {
        
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

if($action === 'add-float') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $float = mysqli_escape_string($conn, decrypt_data($request->float));

        if(check_device_mac($conn, $mac)) {
            $today = get_today();

            $sql = "INSERT INTO opening_float (float_amount, date_created, added_by)
                    VALUES ('{$float}', '{$today}', '{$user_id}')";
            $result = run_query_form($conn, $sql);

            $id = get_last_insert_id($conn);


            // record the user action
            user_log($conn, $user_id, 'Added float', $mac);

            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "id" => encrypt_data($id)
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    }
    catch (Exception $e) {
        
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

if($action === 'delete-float') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));

        if(check_device_mac($conn, $mac)) {
            $today = get_today();

            $sql = "DELETE FROM opening_float WHERE id='{$id}' AND added_by='{$user_id}'";
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $user_id, 'Deleted expense', $mac);

            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    }
    catch (Exception $e) {
        
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


if($action === 'add-expense') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $purpose = mysqli_escape_string($conn, decrypt_data($request->purpose));
        $paid_to = mysqli_escape_string($conn, decrypt_data($request->paid_to));
        $channel = mysqli_escape_string($conn, decrypt_data($request->channel));
        $amount = mysqli_escape_string($conn, decrypt_data($request->amount));
        $date_incurred = mysqli_escape_string($conn, decrypt_data($request->date_incurred));

        if(check_device_mac($conn, $mac)) {
            $today = get_today();

            $sql = "INSERT INTO expenses(purpose, paid_to, payment_channel, amount, date_created, date_incurred, added_by)
                    VALUES ('{$purpose}', '{$paid_to}', '{$channel}', '{$amount}', '{$today}', '{$date_incurred}', '{$user_id}')";
            $result = run_query_form($conn, $sql);


            // record the user action
            user_log($conn, $user_id, 'Added expense', $mac);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    }
    catch (Exception $e) {
        
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

if($action === 'delete-expense') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));

        if(check_device_mac($conn, $mac)) {
            $today = get_today();

            $sql = "DELETE FROM expenses WHERE id='{$id}' AND added_by='{$user_id}'";
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $user_id, 'Deleted expense', $mac);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    }
    catch (Exception $e) {
        
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


if($action === 'add_new_supplier') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));
        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $name = mysqli_escape_string($conn, decrypt_data($request->name));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        

        
        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM suppliers WHERE supplier_name='{$name}' AND deleted=0";
            $result = run_query_form($conn, $sql);

            $today = get_today();

            if(mysqli_num_rows($result) < 1) {

                $sql = "INSERT INTO suppliers (supplier_name, date_created, last_edited, edited_by)
                        VALUES ('{$name}', '{$today}', '{$today}', '{$added_by}')";
                $result = run_query_form($conn, $sql);

                $supplier_id = get_last_insert_id($conn);


                user_log($conn, $added_by, 'Added new supplier - '.$name, $mac);

                // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "id" => encrypt_data($supplier_id)
                );
                echo json_encode($data_insert);
                mysqli_close($conn);


            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Supplier name exists!"
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

if($action === 'add-supplier-payment') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));
        $amount = mysqli_escape_string($conn, decrypt_data($request->amount));
        $channel = mysqli_escape_string($conn, decrypt_data($request->channel));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $paid_to = mysqli_escape_string($conn, decrypt_data($request->paid_to));
        $paid_by = mysqli_escape_string($conn, decrypt_data($request->paid_by));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $date_of_sale = mysqli_escape_string($conn, decrypt_data($request->date_of_sale));

        if(check_device_mac($conn, $mac)) {
            $today = get_today();

            insert_supplier_payment_record($conn, $supplier_id, $amount, $channel, $date_of_sale, $paid_by, $added_by, $paid_to);

            // record the user action
            user_log($conn, $user_id, 'Added a supplier payment record', $mac);

            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }

    }

    catch (Exception $e) {
        
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

if($action === 'delete-supplier-payment') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {
            $today = get_today();

            $sql = "DELETE FROM supplier_payments WHERE id='{$id}' AND supplier_id='{$supplier_id}'";
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $user_id, 'Deleted a supplier payment record', $mac);

            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }

    }

    catch (Exception $e) {
        
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

if($action === 'delete-supplier') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {
            $today = get_today();

            $sql = "UPDATE suppliers SET deleted=1 WHERE id='{$supplier_id}'";
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $user_id, 'Deleted a supplier', $mac);

            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    }

    catch (Exception $e) {
        
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


if($action === 'add_new_cash_customer') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $name = mysqli_escape_string($conn, decrypt_data($request->name));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        
        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM customers WHERE name='{$name}' AND deleted=0 AND customer_type='cash'";
            $result = run_query_form($conn, $sql);

            $today = get_today();

            if(mysqli_num_rows($result) < 1) {

                $sql = "INSERT INTO customers (name, customer_type, date_added, added_by)
                        VALUES ('{$name}', 'cash', '{$today}', '{$added_by}')";
                $result = run_query_form($conn, $sql);

                $debtor_id = get_last_insert_id($conn);


                user_log($conn, $added_by, 'Added new cash customer - '.$name, $mac);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "id" => encrypt_data($debtor_id)
                );
                echo json_encode($data_insert);
                mysqli_close($conn);


            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Customer name or mobile number exists!"
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





if($action === 'add_new_debtor') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $name = mysqli_escape_string($conn, decrypt_data($request->name));
        $mobile_number = mysqli_escape_string($conn, decrypt_data($request->mobile_number));
        $sales_limit = mysqli_escape_string($conn, decrypt_data($request->sales_limit));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        
        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM customers WHERE (name='{$name}' OR mobile_number='{$mobile_number}') AND deleted=0 AND customer_type='debtor'";
            $result = run_query_form($conn, $sql);

            $today = get_today();

            if(mysqli_num_rows($result) < 1) {

                $sql = "INSERT INTO customers (name, mobile_number, customer_type, sales_limit, date_added, added_by)
                        VALUES ('{$name}', '{$mobile_number}', 'debtor', '{$sales_limit}', '{$today}', '{$added_by}')";
                $result = run_query_form($conn, $sql);

                $debtor_id = get_last_insert_id($conn);


                user_log($conn, $added_by, 'Added new debtor - '.$name, $mac);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "id" => encrypt_data($debtor_id)
                );
                echo json_encode($data_insert);
                mysqli_close($conn);


            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Customer name or mobile number exists!"
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



if($action === 'add-debtor-payment') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $balance_before = mysqli_escape_string($conn, decrypt_data($request->balance));
        $cash = mysqli_escape_string($conn, decrypt_data($request->cash));
        $mpesa = mysqli_escape_string($conn, decrypt_data($request->mpesa));
        $bank = mysqli_escape_string($conn, decrypt_data($request->bank));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $sql = "INSERT INTO debtors_payments
                    (customer_id, balance_before, cash, mpesa, bank, amount_credited, date_of_payment, date_created, added_by)
                    VALUES
                    ('{$customer_id}', '{$balance_before}', '{$cash}', '{$mpesa}', '{$bank}', 0, '{$today}', '{$today}', '{$added_by}')";
            
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $added_by, 'Added a debtor payment record', $mac);

            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    }

    catch (Exception $e) {
        
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

if($action === 'delete-debtor-payment') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {
            $today = get_today();

            $sql = "DELETE FROM debtors_payments WHERE id='{$id}' AND customer_id='{$customer_id}'";
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $user_id, 'Deleted a debtor payment record', $mac);

            // backupDatabase($conn);

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

if($action === 'change-debtor-limit') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $amount = mysqli_escape_string($conn, decrypt_data($request->amount));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $sql = "UPDATE customers SET sales_limit='{$amount}' WHERE id='{$customer_id}'";
            
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $added_by, 'Changed debtor sales limit', $mac);

            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    }

    catch (Exception $e) {
        
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

if($action === 'delete-debtor') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $sql = "UPDATE customers SET deleted=1 WHERE id='{$customer_id}'";
            
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $added_by, 'Deleted a debtor', $mac);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    }

    catch (Exception $e) {
        
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

if($action === 'delete-cash-customer') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $sql = "UPDATE customers SET deleted=1 WHERE id='{$customer_id}'";
            
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $added_by, 'Deleted a daily customer', $mac);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }

    }

    catch (Exception $e) {
        
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



if($action === 'add_new_transfer_customer') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $name = mysqli_escape_string($conn, decrypt_data($request->name));
        $mobile_number = mysqli_escape_string($conn, decrypt_data($request->mobile_number));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
         
        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM customers WHERE (name='{$name}' OR mobile_number='{$mobile_number}') AND deleted=0 AND customer_type='transfer'";
            $result = run_query_form($conn, $sql);

            $today = get_today();

            if(mysqli_num_rows($result) < 1) {

                $sql = "INSERT INTO customers (name, mobile_number, customer_type, date_added, added_by)
                        VALUES ('{$name}', '{$mobile_number}', 'transfer', '{$today}', '{$added_by}')";
                $result = run_query_form($conn, $sql);

                $debtor_id = get_last_insert_id($conn);

                user_log($conn, $added_by, 'Added a new transfer customer - '.$name, $mac);

                // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "id" => encrypt_data($debtor_id)
                );
                echo json_encode($data_insert);
                mysqli_close($conn);


            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "Customer name or mobile number exists!"
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

if($action === 'add-transfer-payment') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $payment_channel = mysqli_escape_string($conn, decrypt_data($request->payment_channel));
        $cash = mysqli_escape_string($conn, decrypt_data($request->cash));
        $mpesa = mysqli_escape_string($conn, decrypt_data($request->mpesa));
        $bank = mysqli_escape_string($conn, decrypt_data($request->bank));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $sql = "INSERT INTO transfer_payments
                    (customer_id, payment_channel, cash, mpesa, bank, amount_credited, added_by, date_paid)
                    VALUES
                    ('{$customer_id}', '{$payment_channel}', '{$cash}', '{$mpesa}', '{$bank}', 0, '{$added_by}', '{$today}')";
            
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $added_by, 'Added a transfer payment record', $mac);
            
            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);


        }


    }

    catch (Exception $e) {
        
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

if($action === 'delete-transfer-payment') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {
            $today = get_today();

            $sql = "DELETE FROM transfer_payments WHERE id='{$id}' AND customer_id='{$customer_id}'";
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $user_id, 'Deleted a transfer payment record', $mac);
            
            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }


    }

    catch (Exception $e) {
        
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

if($action === 'delete-transfer') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $sql = "UPDATE customers SET deleted=1 WHERE id='{$customer_id}'";
            
            $result = run_query_form($conn, $sql);

            // record the user action
            user_log($conn, $added_by, 'Deleted a transfer customer', $mac);
            
            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    }
    catch (Exception $e) {
        
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


if($action === 'add-item-to-temp-inter-location') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_quantity = mysqli_escape_string($conn, decrypt_data($request->unit_quantity));
        $location_from_id = mysqli_escape_string($conn, decrypt_data($request->from));
        $location_to_id = mysqli_escape_string($conn, decrypt_data($request->to));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            $locked_status = check_item_lock_status($conn, $item_id, $location_from_id, $user_id);

            $today = get_today();

            if($locked_status) {
                
                // get location from current quantity for this item
                $item_location_from_current_qty = get_item_location_quantity($conn, $item_id, $location_from_id);
                
                $location_from_old_qty = $item_location_from_current_qty['location_quantity'];

                // get total quantity to transfer in pieces
                $unit_pieces = get_unit_pieces($conn, $unit_id);
                $total_pieces_to_transfer = $unit_pieces * $unit_quantity;



                $sql = "INSERT INTO temp_inter_location
                        (item_id, unit_id, unit_quantity, location_1_id, location_2_id, location_1_old_quantity, transfered_by)
                        VALUES
                        ('{$item_id}', '{$unit_id}', '{$unit_quantity}', '{$location_from_id}', '{$location_to_id}', '{$location_from_old_qty}', '{$user_id}')";
                
                $result = run_query_form($conn, $sql);

                // reduce the qty from stock locations
                reduce_item_location_qty($conn, $item_id, $location_from_id, $total_pieces_to_transfer);
                
                // unclock the item
                unlock_item($conn, $item_id, $location_from_id, $user_id);

                $user_name = get_user_record($conn, $user_id)['name'];

                // log the item operation
                item_log($conn, $user_id, $item_id, 'Item was added to location cart with a quantity of - '.$unit_quantity.' by '.$user_name, $mac);

                // delete any duplicate cart entires
                delete_duplicate_cart_items_iterlocation($conn, $user_id);


                $items = get_stock_and_cart_items_inter_location($conn, $user_id);

                // backupDatabase($conn);

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


if($action === 'add-item-to-temp-inter-location-new') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_quantity = mysqli_escape_string($conn, decrypt_data($request->unit_quantity));
        $location_from_id = mysqli_escape_string($conn, decrypt_data($request->from));
        $location_to_id = mysqli_escape_string($conn, decrypt_data($request->to));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        if(check_device_mac($conn, $mac)) {

            // $locked_status = check_item_lock_status($conn, $item_id, $location_from_id, $user_id);

            $today = get_today();

            // if($locked_status) {
                
                // get location from current quantity for this item
                // $item_location_from_current_qty = get_item_location_quantity($conn, $item_id, $location_from_id);
                
                // $location_from_old_qty = $item_location_from_current_qty['location_quantity'];

                // get total quantity to transfer in pieces
                // $unit_pieces = get_unit_pieces($conn, $unit_id);
                // $total_pieces_to_transfer = $unit_pieces * $unit_quantity;



                $sql = "INSERT INTO temp_inter_location
                        (item_id, unit_id, unit_quantity, location_1_id, location_2_id, transfered_by)
                        VALUES
                        ('{$item_id}', '{$unit_id}', '{$unit_quantity}', '{$location_from_id}', '{$location_to_id}', '{$user_id}')";
                
                $result = run_query_form($conn, $sql);

                // reduce the qty from stock locations
                // reduce_item_location_qty($conn, $item_id, $location_from_id, $total_pieces_to_transfer);
                
                // unclock the item
                // unlock_item($conn, $item_id, $location_from_id, $user_id);

                // $user_name = get_user_record($conn, $user_id)['name'];

                // log the item operation
                // item_log($conn, $user_id, $item_id, 'Item was added to location cart with a quantity of - '.$unit_quantity.' by '.$user_name, $mac);

                // delete any duplicate cart entires
                delete_duplicate_cart_items_iterlocation($conn, $user_id);


                $items = get_stock_and_cart_items_inter_location_new($conn, $user_id);

                // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "stock" => $items['stock'],
                    "locations" => $items['locations'],
                    "cartItems" => $items['cartItems']
                );
                echo json_encode($data_insert);
                mysqli_close($conn);
            // }

        }

    } catch (Exception $e) {
        
        // $uid = mysqli_escape_string($conn, decrypt_data($request->atu));
        // unlock_all_user_locked_items($conn, $uid);
        
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

if($action === 'delete-item-from-temp-inter-location') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $total_pieces = mysqli_escape_string($conn, decrypt_data($request->tt_qty));
        $location_from_id = mysqli_escape_string($conn, decrypt_data($request->location_from));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        
        if(check_device_mac($conn, $mac)) {

            // $locked_status = check_item_lock_status($conn, $item_id, $location_from_id, $added_by);


            $sql = "DELETE FROM temp_inter_location WHERE id='{$id}' AND item_id='{$item_id}' AND location_1_id='{$location_from_id}' AND transfered_by='{$added_by}'";
            $result = run_query_form($conn, $sql);

            // increase the qty in stock locations
            // increase_item_location_qty($conn, $item_id, $location_from_id, $total_pieces);

            // unclock the item
            // unlock_item($conn, $item_id, $location_from_id, $added_by);

            // $user_name = get_user_record($conn, $added_by)['name'];

            // log the item operation
            // item_log($conn, $added_by, $item_id, 'Item was deleted from location cart by '.$user_name, $mac);

            // backupDatabase($conn);

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

if($action === 'complete-inter-location') {
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

            $user_name = get_user_record($conn, $added_by)['name'];
            $temp_items = get_temp_inter_locations($conn, $added_by);

            if(count($temp_items) > 0) {

                // check if there are items with more quantity than available quantity
                $sql = "SELECT temp.item_id, temp.location_1_id,
                        SUM(temp.unit_quantity*su.unit_quantity) AS unit_quantity,
                        sl.location_quantity
                        FROM
                        temp_inter_location AS temp
                        INNER JOIN
                        stock_units su
                        ON su.id=temp.unit_id AND su.item_id=temp.item_id

                        INNER JOIN
                        stock_locations sl
                        ON sl.item_id=temp.item_id AND sl.location_id=temp.location_1_id

                        WHERE temp.transfered_by='{$added_by}'
                        GROUP BY item_id, location_1_id

                        HAVING unit_quantity>location_quantity";

                $result = run_query_form($conn, $sql);

                if(mysqli_num_rows($result) < 1) {

                    // continue with the processing
                    mysqli_begin_transaction($conn);

                    try {

                        $max_id = get_inter_location_max_transation_id($conn);

                        $max_id++;
                        $transaction_code = 'LT'.$max_id;

                        $today = get_today();


                        $sold_stock_values = [];
                        $temp_transaction_ids_to_delete = [];
                        $stock_location_1_updates = [];
                        $stock_location_2_updates = [];
                        $item_log_values = [];

                        foreach ($temp_items as $row) {

                                $id = decrypt_data($row['id']);
                                $item_id = $row['item_id'];
                                $location_1_id = $row['location_1_id'];
                                $location_2_id = $row['location_2_id'];
                                $unit_id = $row['unit_id'];
                                $unit_qty = $row['unit_quantity'];

                                // get the unit details
                                $unit = get_unit_record($conn, $unit_id);
                                $total_sold_pieces = $unit['unit_quantity'] * $unit_qty;

                                // get current stock quantity
                                $location_1_old_qty = get_item_location_quantity($conn, $item_id, $location_1_id);
                                $location_2_old_qty = get_item_location_quantity($conn, $item_id, $location_2_id);

                                $item_location_1_old_qty = $location_1_old_qty['location_quantity'];
                                $item_total_old_qty_1 = $location_1_old_qty['total_quantity'];
                                $location_1_old_pieces = $item_location_1_old_qty;
                                $total_old_pieces_1 = $item_total_old_qty_1;


                                $item_location_2_old_qty = $location_2_old_qty['location_quantity'];
                                $item_total_old_qty_2 = $location_2_old_qty['total_quantity'];
                                $location_2_old_pieces = $item_location_2_old_qty;
                                $total_old_pieces_2 = $item_total_old_qty_2;



                                // prepare data for batch inserts/updates
                                $sold_stock_values[] = "('{$transaction_code}', {$item_id},
                                                        {$location_1_id}, {$location_1_old_pieces},
                                                        {$unit_id}, {$unit_qty}, {$location_2_id},
                                                        {$location_2_old_pieces}, {$added_by}, '{$today}')";
                                
                                $temp_transaction_ids_to_delete[] = $id;


                                if(!isset($stock_location_1_updates[$item_id . '_' . $location_1_id])) {
                                    $stock_location_1_updates[$item_id . '_' . $location_1_id] = 0;
                                }

                                $stock_location_1_updates[$item_id . '_' . $location_1_id] += $total_sold_pieces;
                                
                                if(!isset($stock_location_2_updates[$item_id . '_' . $location_2_id])) {
                                    $stock_location_2_updates[$item_id . '_' . $location_2_id] = 0;
                                }

                                $stock_location_2_updates[$item_id . '_' . $location_2_id] += $total_sold_pieces;


                                $item_log_values[] = "({$item_id},
                                                    'Item location transfer with quantity of - {$unit_qty} {$unit['unit_name']} transaction_code {$transaction_code} by {$user_name}', 
                                                    '{$today}', {$added_by}, '{$mac}')";

                        }


                        // perform the batch inserts sold_stock
                        if(!empty($sold_stock_values)) {

                            $sql_sold_stock = "INSERT INTO stock_location_transfer
                                                (transaction_code, item_id, location_1_id,
                                                location_1_old_quantity, unit_id, unit_quantity,
                                                location_2_id, location_2_old_quantity,
                                                transfered_by,
                                                date_transfered)
                                                VALUES " .implode(",", $sold_stock_values);
                            run_query_form($conn, $sql_sold_stock);
                        }

                        // perform batch delete temp_transactions_waiting
                        if(!empty($temp_transaction_ids_to_delete)) {
                            $sql_delete = "DELETE FROM temp_inter_location
                                            WHERE id IN
                                            (" . implode(",", array_map(function($id) {return "'" . $id . "'"; }, $temp_transaction_ids_to_delete)) . ")";
                            run_query_form($conn, $sql_delete);
                        }

                        // perform updates on location from
                        if(!empty($stock_location_1_updates)) {
                            foreach ($stock_location_1_updates as $key => $qty_to_deduct) {
                                list($item_id, $location_1_id) = explode('_', $key);

                                reduce_item_location_qty($conn, $item_id, $location_1_id, $qty_to_deduct);
                            }
                        }

                        // perform updates on location to
                        if(!empty($stock_location_2_updates)) {
                            foreach ($stock_location_2_updates as $key => $qty_to_increase) {
                                list($item_id, $location_2_id) = explode('_', $key);

                                increase_item_location_qty($conn, $item_id, $location_2_id, $qty_to_increase);
                            }
                        }

                        // insert item logs
                        if(!empty($item_log_values)) {
                            $sql_logs = "INSERT INTO item_operations
                                        (item_id, operation, date_done, done_by, mac)
                                        VALUES " . implode(",", $item_log_values);
                            run_query_form($conn, $sql_logs);
                        }


                        // get the shop name and description for printing
                        $shop_details = get_shop_details($conn, $added_by);

                        $shop_details['tcode'] = encrypt_data($transaction_code);


                        // log user operation
                        user_log($conn, $added_by, 'Completed stock location to location transaction '.$transaction_code, $mac);

                        mysqli_commit($conn);

                        $data_insert=array(
                            "status" => "success",
                            "message" => "success",
                            "Shop" => $shop_details
                        );

                        echo json_encode($data_insert);
                        mysqli_close($conn);


                    } catch (Exception $e) {
                        mysqli_rollback($conn);

                        $data_insert=array(
                            "status" => "error",
                            "message" => "Transaction failed: " . $e->getMessage()
                        );

                        echo json_encode($data_insert);
                        mysqli_close($conn);
                    }

                } else {
                    // notify user of the less stock
                    $cart_items = [];

                    while($row=mysqli_fetch_assoc($result)) {

                        $item = get_item_details($conn, $row['item_id']);
                        $location = get_shop_location_record($conn, $row['location_1_id']);
                        
                        $cart_items[] = [
                            'item' => $item,
                            'location' => $location,
                            'qty_ordered' => $row['unit_quantity'],
                            'curr_quantity' => $row['location_quantity']
                        ];
                    }

                    $data_insert=array(
                        "status" => "error",
                        "message" => "Quantity less!",
                        "items" => $cart_items
                    );
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                }

            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "No items in cart!"
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



// stock taking
if($action === 'start-stock-taking') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));

        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $sql = "INSERT INTO stock_taking_dates(location_id, date_started, started_by)
                    VALUES ('{$location_id}', '{$today}', '{$added_by}')";
            
            $result = run_query_form($conn, $sql);

            $date_id = get_last_insert_id($conn);

            // log user operation
            user_log($conn, $added_by, 'Started stock taking date '.$today, $mac);

            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "date_id" => encrypt_data($date_id)
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

if($action === 'add-stock-taking-item') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $date_id = mysqli_escape_string($conn, decrypt_data($request->date_id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->buying_price));
        $quantity_confirmed = mysqli_escape_string($conn, decrypt_data($request->quantity_confirmed));

        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            // get the location quantity
            $item_loc_qty = get_item_location_quantity($conn, $item_id, $location_id)['location_quantity'];

            $sql = "INSERT INTO stock_taking_items(stock_taking_date_id, item_id, buying_price, location_id, current_quantity, quantity_confirmed, confirmed_by)
                    VALUES ('{$date_id}', '{$item_id}', '{$buying_price}', '{$location_id}', '{$item_loc_qty}', '{$quantity_confirmed}', '{$added_by}')";
            $result = run_query_form($conn, $sql);

            // backupDatabase($conn);

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

if($action === 'delete-stock-taking-item') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $date_id = mysqli_escape_string($conn, decrypt_data($request->date_id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));

        
        if(check_device_mac($conn, $mac)) {


            $sql = "DELETE FROM stock_taking_items WHERE id='{$id}' AND item_id='{$item_id}' AND location_id='{$location_id}'";
            $result = run_query_form($conn, $sql);

            // backupDatabase($conn);
            
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

if($action === 'complete-stock-taking') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $date_id = mysqli_escape_string($conn, decrypt_data($request->date_id));
        $loc_id = mysqli_escape_string($conn, decrypt_data($request->loc_id));

        
        if(check_device_mac($conn, $mac)) {

            unlock_all_user_locked_items($conn, $added_by);
         

            $today = get_today();

            $sql = "UPDATE stock_taking_dates
                    SET completed=1, completion_time='{$today}'
                    WHERE id='{$date_id}'";
            
            $result = run_query_form($conn, $sql);

            // log user operation
            user_log($conn, $added_by, 'Completed stock taking date '.$today, $mac);

            // backupDatabase($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "date_id" => encrypt_data($date_id)
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

if($action === 'update-stock-taking-items') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $date_id = mysqli_escape_string($conn, decrypt_data($request->date_id));
        $loc_id = mysqli_escape_string($conn, decrypt_data($request->loc_id));

        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $temp_items = get_unupdated_stock_taking_items($conn, $date_id);

            if(count($temp_items) > 0) {

                $total_items = count($temp_items);

                while($total_items>0) {

                    $row = $temp_items[0];

                    $id = $row['id'];
                    $qty = $row['confirmed_qty'];
                    $item_id = $row['item_id'];
                    $location_id = $row['location_id'];

                    update_stock_taking_location_quantity($conn, $item_id, $location_id, $qty);

                    update_stock_taking_item($conn, $id);                    

                    // repeat the process with a new list
                    $temp_items = get_unupdated_stock_taking_items($conn, $date_id);
                    $total_items = count($temp_items);

                }

            }

            $sql = "UPDATE stock_taking_dates SET updated_stock=1 WHERE id='{$date_id}'";
            $result = run_query_form($conn, $sql);


            // log user operation
            user_log($conn, $added_by, 'Updated stock after completing stock taking '.$today, $mac);

            // backupDatabase($conn);

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

if($action === 'update-user-details') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $username = mysqli_escape_string($conn, decrypt_data($request->username));
        $password = mysqli_escape_string($conn, decrypt_data($request->password));
        $mobile = mysqli_escape_string($conn, decrypt_data($request->mobile));

        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $sql = "SELECT * FROM users WHERE id!='{$user_id}' AND (username='{$username}' OR mobile='{$mobile}') AND deleted=0";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) < 1) {

                $sql = "UPDATE users SET username='{$username}', password='{$password}', mobile='{$mobile}'
                        WHERE id='{$user_id}'";
                $result = run_query_form($conn, $sql);

                // log user operation
                user_log($conn, $added_by, 'Updated user login details', $mac);

                // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success"
                );
    
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {

                $data_insert=array(
                    "status" => "error",
                    "message" => "Username or mobile number exists!"
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

if($action === 'update-user-roles') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $roles = $request->roles;
        
        if(check_device_mac($conn, $mac)) {

            $sql1 = "SELECT * FROM user_rights WHERE user_id='{$user_id}'";
            $result1 = run_query_form($conn, $sql1);

            if(mysqli_num_rows($result1) < 1) {
                $sql = "INSERT INTO user_rights (user_id) VALUES ('{$user_id}')";
                run_query_form($conn, $sql);
            }

            foreach($roles as $key => $value) {

                $value = decrypt_data($value);

                $sql = "UPDATE user_rights SET $key='{$value}' WHERE user_id='{$user_id}'";
                $result = run_query_form($conn, $sql);

            }


            // log user operation
            user_log($conn, $added_by, 'Updated user rights', $mac);

            // backupDatabase($conn);

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

if($action === 'update-user-location-rights') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $roles = $request->roles;
        
        if(check_device_mac($conn, $mac)) {

            $sql1 = "SELECT * FROM user_location_rights WHERE user_id='{$user_id}' AND location_id='{$location_id}'";
            $result1 = run_query_form($conn, $sql1);

            if(mysqli_num_rows($result1) < 1) {
                $sql = "INSERT INTO user_location_rights (user_id, location_id)
                        VALUES ('{$user_id}', '{$location_id}')";
                run_query_form($conn, $sql);
            }

            foreach($roles as $key => $value) {

                $value = decrypt_data($value);

                $sql = "UPDATE user_location_rights SET $key='{$value}' WHERE user_id='{$user_id}' AND location_id='{$location_id}'";
                $result = run_query_form($conn, $sql);

            }

            // log user operation
            user_log($conn, $added_by, 'Updated user location rights', $mac);
            
            // backupDatabase($conn);

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

if($action === 'disable-user-account') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $sql = "UPDATE users SET disabled=1
                    WHERE id='{$user_id}'";
            $result = run_query_form($conn, $sql);

            // log user operation
            user_log($conn, $added_by, 'Deactivated user account', $mac);

            // backupDatabase($conn);

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

if($action === 'enable-user-account') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $sql = "UPDATE users SET disabled=0
                    WHERE id='{$user_id}'";
            $result = run_query_form($conn, $sql);

            // log user operation
            user_log($conn, $added_by, 'Activated user account', $mac);

            // backupDatabase($conn);

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


if($action === 'create-user-account') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $username = mysqli_escape_string($conn, decrypt_data($request->username));
        $mobile = mysqli_escape_string($conn, decrypt_data($request->mobile));
        $password = mysqli_escape_string($conn, decrypt_data($request->password));
        $roles = $request->roles;
        
        try {

            mysqli_begin_transaction($conn);

            if(check_device_mac($conn, $mac)) {

                $sql = "SELECT * FROM users WHERE (mobile='{$mobile}' OR username='{$username}') AND deleted=0";
                $result = run_query_form($conn, $sql);

                if(mysqli_num_rows($result) < 1) {

                    $sql = "INSERT INTO users (username, password, mobile, role)
                            VALUES ('{$username}', '{$password}', '{$mobile}', 'employee')";
                    $result = run_query_form($conn, $sql);

                    $user_id = get_last_insert_id($conn);


                    // insert the roles
                    $sql1 = "INSERT INTO user_rights (user_id) VALUES ('{$user_id}')";
                    run_query_form($conn, $sql1);
        
                    foreach($roles as $key => $value) {
        
                        $value = decrypt_data($value);
        
                        $sql = "UPDATE user_rights SET $key='{$value}' WHERE user_id='{$user_id}'";
                        $result = run_query_form($conn, $sql);
        
                    }

                    // log user operation
                    user_log($conn, $added_by, 'Created new user account for '.$username, $mac);

                    mysqli_commit($conn);

                    // backupDatabase($conn);

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success"
                    );
        
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {
                    $data_insert=array(
                        "status" => "error",
                        "message" => "Username or mobile number exists!"
                    );
        
                    echo json_encode($data_insert);
                    mysqli_close($conn);
                }
                
            }

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage(),
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

if($action === 'delete-user-account') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        
        if(check_device_mac($conn, $mac)) {

            $today = get_today();

            $sql = "UPDATE users SET deleted=1
                    WHERE id='{$user_id}'";
            $result = run_query_form($conn, $sql);

            // log user operation
            user_log($conn, $added_by, 'Deleted a user account ', $mac);

            // backupDatabase($conn);

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


// business profile
if ($action === 'save_profile') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $name = mysqli_escape_string($conn, decrypt_data($request->name));
        $description = mysqli_escape_string($conn, decrypt_data($request->description));
        $location = mysqli_escape_string($conn, decrypt_data($request->location));
        $till = mysqli_escape_string($conn, decrypt_data($request->till));
        $paybill = mysqli_escape_string($conn, decrypt_data($request->paybill));
        $paybill_account = mysqli_escape_string($conn, decrypt_data($request->paybill_account));
        $mpesa_bname = mysqli_escape_string($conn, decrypt_data($request->mpesa_bname));
        $footer = mysqli_escape_string($conn, decrypt_data($request->footer));

        if(check_device_mac($conn, $mac)) {
        
            $sql2 = "UPDATE shop SET name='{$name}', description='{$description}', location='{$location}'";
            $result2 = run_query_form($conn, $sql2);

            $sql3 = "UPDATE shop_receipt_options SET paybill='{$paybill}', paybill_account_name='{$paybill_account}', till='{$till}', mpesa_business_name='{$mpesa_bname}', footer_message='{$footer}'";
            $result3 = run_query_form($conn, $sql3);

            // log user operation
            user_log($conn, $added_by, 'Updated shop profile ', $mac);

            // backupDatabase($conn);
            

            $data_insert = array(
                "status" => "success",
                "message" => "success"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
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

if ($action === 'save_number') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $number = mysqli_escape_string($conn, decrypt_data($request->number));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {
            $sql = "SELECT * FROM shop_contacts WHERE contact='{$number}'";
            $result=run_query_form($conn, $sql);

            if (mysqli_num_rows($result) < 1) {

                $sql2 = "INSERT INTO shop_contacts (contact) VALUES ('{$number}')";
                $result=run_query_form($conn, $sql2);
                
                // log user operation
                user_log($conn, $added_by, 'Added the contact number '.$number, $mac);

                $data_insert = array(
                    "status" => "success",
                    "message" => "success"
                );
                // return the error
                echo json_encode($data_insert);
                // close connection
                mysqli_close($conn);


            } else {

                $data_insert = array(
                    "status" => "error",
                    "message" => "Contact number already exists!"
                );
                // return the error
                echo json_encode($data_insert);
                // close connection
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

if ($action === 'delete_number') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $number = mysqli_escape_string($conn, decrypt_data($request->number));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {
            $sql = "SELECT * FROM shop_contacts WHERE contact='{$number}'";
            $result=run_query_form($conn, $sql);

            if (mysqli_num_rows($result) > 0) {

                $sql2 = "DELETE FROM shop_contacts WHERE contact='{$number}'";
                $result=run_query_form($conn, $sql2);

                // log user operation
                user_log($conn, $added_by, 'Deleted the contact number '.$number, $mac);

                $data_insert = array(
                    "status" => "success",
                    "message" => "success"
                );
                // return the error
                echo json_encode($data_insert);
                // close connection
                mysqli_close($conn);


            } else {
                $data_insert = array(
                    "status" => "error",
                    "message" => "Contact number does not exist!"
                );
                // return the error
                echo json_encode($data_insert);
                // close connection
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

if ($action === 'delete-business-image') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $image_name = mysqli_escape_string($conn, decrypt_data($request->image_name));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {
            $upload_dir = 'uploads/shopLogos/';

            // delete fro licenses table
            $sql="UPDATE shop_receipt_options SET image_url=''";
            $result = run_query_form($conn, $sql);

            // log user operation
            user_log($conn, $added_by, 'Deleted business image', $mac);



            if (file_exists($upload_dir.$image_name)) {

                unlink($upload_dir.$image_name);

                $data_insert = array(
                    "status" => "success",
                    "message" => "Image deleted successful!"
                );
                // return the error
                echo json_encode($data_insert);
                // close connection
                mysqli_close($conn);

            } else {

                $data_insert = array(
                    "status" => "success",
                    "message" => "Image deleted successful!"
                );
                // return the error
                echo json_encode($data_insert);
                // close connection
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

if ($action === 'update_business_image') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $imageUrl = mysqli_escape_string($conn, decrypt_data($request->imageUrl));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        if(check_device_mac($conn, $mac)) {
            $sql="UPDATE shop_receipt_options SET image_url='{$imageUrl}'";
            $result=run_query_form($conn, $sql);

            // log user operation
            user_log($conn, $added_by, 'Updated business image', $mac);

            // backupDatabase($conn);

            $data_insert = array(
                "status" => "success",
                "message" => "success!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
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


if ($action === 'delete_profile_image') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $image_name = mysqli_escape_string($conn, decrypt_data($request->img));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));

        $upload_dir = 'uploads/';

        // delete fro licenses table
        $sql="UPDATE users SET image_url='profile.png' WHERE id='{$user_id}'";
        $result = run_query_form($conn, $sql);

        // log user operation
        user_log($conn, $user_id, 'Deleted profile image', $mac);


        if (file_exists($upload_dir.$image_name)) {

            unlink($upload_dir.$image_name);

            $data_insert = array(
                "status" => "success",
                "message" => "Image deleted successful!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn);

        } else {
            $data_insert = array(
                "status" => "success",
                "message" => "Image deleted successful!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
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

if ($action === 'update_user_image') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $user_id = mysqli_escape_string($conn, decrypt_data($request->uid));
        $imagename = mysqli_escape_string($conn, decrypt_data($request->imagename));

        $sql="UPDATE users SET image_url='{$imagename}' WHERE id='{$user_id}'";
        $result=mysqli_query($conn, $sql);

        if ($result) {

            // log user operation
            user_log($conn, $user_id, 'Updated profile image', $mac);


            $data_insert = array(
                "status" => "success",
                "message" => "success!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
            mysqli_close($conn);

        } else {
            $data_insert = array(
                "status" => "error",
                "message" => "Something bad happend!"
            );
            // return the error
            echo json_encode($data_insert);
            // close connection
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


// unlock any locked items by a user
if ($action === 'unlock_all_user_locked_items') {

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


if ($action === 'add_stock_return') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

   try {

        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));
        $invoice_item_id = mysqli_escape_string($conn, decrypt_data($request->invoice_item_id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit_id));
        $unit_quantity = mysqli_escape_string($conn, decrypt_data($request->unit_quantity));
        $location_id = mysqli_escape_string($conn, decrypt_data($request->location_id));
        $buying_price = mysqli_escape_string($conn, decrypt_data($request->buying_price));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));

        try {

            mysqli_begin_transaction($conn);

            if(check_device_mac($conn, $mac)) {

                // get location quantity
                $item_location_old_qty = get_item_location_quantity($conn, $item_id, $location_id);

                $location_old_qty = $item_location_old_qty['location_quantity'];
                $total_old_qty = $item_location_old_qty['total_quantity'];

                $unit_pieces = get_unit_pieces($conn, $unit_id);
                $total_qty = $unit_pieces * floatval($unit_quantity);

                $today = get_today();

                $sql = "INSERT INTO stock_returns(supplier_id, invoice_item_id, item_id, unit_id, unit_quantity, buying_price, location_id, location_old_quantity, total_old_quantity, date_returned, added_by)
                        VALUES ('{$supplier_id}', '{$invoice_item_id}', '{$item_id}', '{$unit_id}', '{$unit_quantity}', '{$buying_price}', '{$location_id}', '{$location_old_qty}', '{$total_old_qty}', '{$today}', '{$added_by}')";
                
                $result = run_query_form($conn, $sql);

                // add supplier payment
                $sql = "SELECT * FROM supplier_payments WHERE supplier_id='{$supplier_id}' AND DATE_FORMAT(date_paid, '%Y-%m-%d')=DATE_FORMAT('{$today}', '%Y-%m-%d') AND payment_channel='Stock return'";
                $result = run_query_form($conn, $sql);

                if(mysqli_num_rows($result) > 0) {

                    // update the payment
                    $record = mysqli_fetch_assoc($result);

                    $id = $record['id'];

                    $new_amount = $record['amount_paid'] + (floatval($buying_price) * floatval($unit_quantity));

                    $sql = "UPDATE supplier_payments SET amount_paid='{$new_amount}' WHERE id='{$id}'";
                    $result = run_query_form($conn, $sql);

                    // reduce item location quantity
                    reduce_item_location_qty($conn, $item_id, $location_id, $total_qty);

                    mysqli_commit($conn);


                } else {

                    $total = floatval($buying_price) * floatval($unit_quantity);

                    insert_supplier_payment_record($conn, $supplier_id, $total, 'Stock return', $today, 'Supplier', $added_by, 'Supplier');

                    // reduce item quantity
                    // reduce item location quantity
                    $unit_pieces = get_unit_pieces($conn, $unit_id);
                    $total_qty = $unit_pieces * floatval($unit_quantity);

                    reduce_item_location_qty($conn, $item_id, $location_id, $total_qty);

                    mysqli_commit($conn);

                }

                $items = get_supplier_invoice_items($conn, $supplier_id);
                $temp_items = get_supplier_incomplete_returns($conn, $supplier_id, $added_by);
                


                $data_insert = array(
                    "status" => "success",
                    "message" => "success",
                    "items" => $items,
                    "temp_items" => $temp_items
                );
                // return the error
                echo json_encode($data_insert);
                // close connection
                mysqli_close($conn);

            }

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $data_insert=array(
                "status" => "error",
                "message" => $e->getMessage(),
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

if ($action === 'delete_stock_return_item') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->uid));
        
        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM stock_returns WHERE id='{$id}' AND added_by='{$added_by}' AND transaction_code=''";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) > 0) {

                $row = mysqli_fetch_assoc($result);

                // increase the stock quantity
                $unit_pieces = get_unit_pieces($conn, $row['unit_id']);
                $total_qty = $unit_pieces * floatval($row['unit_quantity']);

                increase_item_location_qty($conn, $row['item_id'], $row['location_id'], $total_qty);

                // delete the item
                $sql = "DELETE FROM stock_returns WHERE id='{$id}'";
                $result = run_query_form($conn, $sql);

                $items = get_supplier_invoice_items($conn, $row['supplier_id']);
                $temp_items = get_supplier_incomplete_returns($conn, $row['supplier_id'], $added_by);
                
                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "items" => $items,
                    "temp_items" => $temp_items
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

if ($action === 'complete_return') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->uid));
        $supplier_id = mysqli_escape_string($conn, decrypt_data($request->supplier_id));
        
        if(check_device_mac($conn, $mac)) {

            $sql = "SELECT * FROM stock_returns WHERE added_by='{$added_by}' AND supplier_id='{$supplier_id}' AND transaction_code=''";
            $result = run_query_form($conn, $sql);

            if(mysqli_num_rows($result) > 0) {

                $sql = "SELECT IFNULL(MAX(id), 0) AS id FROM stock_returns";
                $result = run_query_form($conn, $sql);

                $record=mysqli_fetch_assoc($result);

                $transaction_code = 'SR'.$record['id'];


                $sql = "UPDATE stock_returns SET transaction_code='{$transaction_code}' WHERE added_by='{$added_by}' AND supplier_id='{$supplier_id}' AND transaction_code=''";
                $result = run_query_form($conn, $sql);

                $items = get_supplier_invoice_items($conn, $supplier_id);
                $temp_items = get_supplier_incomplete_returns($conn, $supplier_id, $added_by);
            

                $shop_details = get_shop_details($conn, $added_by);
                $shop_details['tcode'] = encrypt_data($transaction_code);


                // backupDatabase($conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success",
                    "items" => $items,
                    "temp_items" => $temp_items,
                    "Shop" => $shop_details
                );

                echo json_encode($data_insert);
                mysqli_close($conn);


            } else {
                $data_insert=array(
                    "status" => "error",
                    "message" => "No items selected!",
                    "id" => $supplier_id,
                    "id2" => $added_by
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

if ($action === 'reprint_stock_return') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));

        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->uid));
        $code = mysqli_escape_string($conn, decrypt_data($request->code));
        
        if(check_device_mac($conn, $mac)) {

            $items = get_supplier_return_code_items($conn1, $code);
            $shop_details = get_shop_details($conn, $added_by);
            $shop_details['tcode'] = encrypt_data($transaction_code);

           
            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items,
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


if($action === 'update_customer_invoice') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        $id = mysqli_escape_string($conn, decrypt_data($request->id));
        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $invoice_number = mysqli_escape_string($conn, decrypt_data($request->invoice_number));
        $title = mysqli_escape_string($conn, decrypt_data($request->title));
        $invoice_date = mysqli_escape_string($conn, decrypt_data($request->invoice_date));
        $created_by = mysqli_escape_string($conn, decrypt_data($request->created_by));


        $sql = "SELECT * FROM customer_invoices WHERE id!='{$id}' AND inv_number='{$invoice_number}' AND customer_id='{$customer_id}'";
        $result = mysqli_query($conn, $sql);
        if($result) {

            if(mysqli_num_rows($result) == 0) {

                $sql = "UPDATE customer_invoices SET inv_number='{$invoice_number}', title='{$title}', invoice_date='{$invoice_date}'
                        WHERE id='{$id}'";
                $result = mysqli_query($conn, $sql);

                if($result) {

                    $invoices = get_all_customer_invoices($conn, $customer_id);

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success",
                        "Invoices" => $invoices
                    );
            
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {

                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not check invoice number!"
                    );
            
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                }

            } else {

                $data_insert=array(
                    "status" => "error",
                    "message" => "Invoice number exists for this customer!"
                );

                echo json_encode($data_insert);
                mysqli_close($conn);

            }

        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Could not check invoice number!"
            );

            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        http_response_code(401);
        $data_insert=array(
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}




if($action === 'add_new_customer_invoice') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        $customer_id = mysqli_escape_string($conn, decrypt_data($request->customer_id));
        $invoice_number = mysqli_escape_string($conn, decrypt_data($request->invoice_number));
        $title = mysqli_escape_string($conn, decrypt_data($request->title));
        $invoice_date = mysqli_escape_string($conn, decrypt_data($request->invoice_date));
        $created_by = mysqli_escape_string($conn, decrypt_data($request->created_by));


        $sql = "SELECT * FROM customer_invoices WHERE inv_number='{$invoice_number}' AND customer_id='{$customer_id}'";
        $result = mysqli_query($conn, $sql);

        if($result) {

            if(mysqli_num_rows($result) < 1) {

                $today = date('Y-m-d H:i:s', time());

                $sql="INSERT INTO customer_invoices (title, inv_number, customer_id, invoice_date, date_created, created_by)
                        VALUES ('{$title}', '{$invoice_number}', '{$customer_id}', '{$invoice_date}', '{$today}', '{$created_by}')";
                $result = mysqli_query($conn, $sql);

                if($result) {

                    $invoice;

                    $id = mysqli_insert_id($conn);

                    $invoice['id'] = encrypt_data($id);
                    $invoice['title'] = $title;
                    $invoice['number'] = $invoice_number;
                    $invoice['date'] = $invoice_date;
                    $invoice['date_created'] = date('Y-m-d', time());
                    $invoice['created_by'] = get_user_record($conn, $created_by)['name'];
                    $invoice['items'] = get_customer_invoice_items($conn, $id);

                    $data_insert=array(
                        "status" => "success",
                        "message" => "success",
                        "invoice" => $invoice
                    );
            
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                } else {

                    $data_insert=array(
                        "status" => "error",
                        "message" => "Could not add invoice!"
                    );
            
                    echo json_encode($data_insert);
                    mysqli_close($conn);

                }


            } else {

                $data_insert=array(
                    "status" => "error",
                    "message" => "Invoice number exists for this customer. Provide a different invoice number!"
                );
        
                echo json_encode($data_insert);
                mysqli_close($conn);
            }

        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Could not check invoice number!"
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        http_response_code(401);
        $data_insert=array(
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if($action === 'add_customer_invoice_item') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        $invoice_id = mysqli_escape_string($conn, decrypt_data($request->invoice_id));
        $item_id = mysqli_escape_string($conn, decrypt_data($request->item_id));
        $unit_id = mysqli_escape_string($conn, decrypt_data($request->unit));
        $unit_quantity = mysqli_escape_string($conn, decrypt_data($request->unit_quantity));
        $selling_price = mysqli_escape_string($conn, decrypt_data($request->item_price));
        
        $sql="INSERT INTO customer_invoice_items (invoice_id, item_id, unit_id, unit_quantity, selling_price)
                VALUES ('{$invoice_id}', '{$item_id}', '{$unit_id}', '{$unit_quantity}', '{$selling_price}')";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $items = get_customer_invoice_items($conn, $invoice_id)['items'];

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "items" => $items
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Could not add item!"
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        http_response_code(401);
        $data_insert=array(
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}


if($action === 'delete_customer_invoice_item') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        $invoice_id = mysqli_escape_string($conn, decrypt_data($request->invoice_id));
        $id = mysqli_escape_string($conn, decrypt_data($request->item_id));

        $sql = "DELETE FROM customer_invoice_items WHERE id='{$id}' AND invoice_id='{$invoice_id}'";
        $result = mysqli_query($conn, $sql);

        if($result) {

            $data_insert=array(
                "status" => "success",
                "message" => "success"
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);

        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Could not delete invoice item!"
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        http_response_code(401);
        $data_insert=array(
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}



if($action === 'delete_customer_invoice') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {

        $invoice_id = mysqli_escape_string($conn, decrypt_data($request->invoice_id));

        $sql = "DELETE FROM customer_invoices WHERE id='{$invoice_id}'";
        $sql2 = "DELETE FROM customer_invoice_items WHERE invoice_id='{$invoice_id}'";
        $result = mysqli_query($conn, $sql);
        $result2 = mysqli_query($conn, $sql2);

        if($result) {

            if($result2) {

                $data_insert=array(
                    "status" => "success",
                    "message" => "success"
                );
        
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {

                $data_insert=array(
                    "status" => "error",
                    "message" => "Could not delete invoice items!"
                );
        
                echo json_encode($data_insert);
                mysqli_close($conn);

            }


        } else {

            $data_insert=array(
                "status" => "error",
                "message" => "Could not delete invoice!"
            );
    
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } catch (Exception $e) {
        http_response_code(401);
        $data_insert=array(
        "jwt" => $jwt,
        "status" => "error2",
        "message" => $e->getMessage()
        );

        echo json_encode($data_insert);
        mysqli_close($conn);
    }
}


if($action==='send_sms') {

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));
        
        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $mobile = mysqli_escape_string($conn, decrypt_data($request->mobile));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));
        $sms = decrypt_data($request->message);
        
        
        if(check_device_mac($conn, $mac)) {

            if($mobile === '254724547623') {

                $business = get_shop_details($conn, $added_by);
                $message = strtoupper(decrypt_data($business['name'])). "\n\n";

                $number = array(
                    "number" => $mobile,
                    "senderID" => "BIZONLINE",
                    "text" => $message.$sms,
                    "type" => "sms",
                    "beginDate" => date('Y-m-d', time()),
                    "beginTime" => date('h:i', time()),
                    "lifetime" => 555,
                    "delivery" => false
                );

                send_uwazii_new($number, $conn);

                $data_insert=array(
                    "status" => "success",
                    "message" => "success"
                );
                echo json_encode($data_insert);
                mysqli_close($conn);

            } else {

                $data_insert=array(
                    "status" => "error",
                    "message" => "You can only send message to +254724547623"
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



if($action === 'upload-backup-from-local') {

    $user_id = mysqli_escape_string($conn, decrypt_data($request->added_by));
    
    
    // build the command
    // $command = "mysqldump -h localhost -u root -psteve254 nao_shop";

    global $dbname;
    $backupFile = $dbname.'.sql';
    
    $command = ('C:\xampp\mysql\bin\mysqldump.exe --user=root --password=steve254 --host=localhost fedhamart > '.$backupFile);

    // exec the command and capture the output
    exec($command, $output, $returnCode);
    
    if($returnCode==0) {
        
        // capture the sql content
        $sqlBackup = implode("\n", $output);
        
        $server_url = 'https://fedhamart.co.ke/NaoApi/database_restore.php';
        
        // create a curl handle
        $ch = curl_init($server_url);
        
        // define the post data
        $postData = array(
            "action" => 'backup_online',
            "backup_file" => new CURLFile($backupFile),
            "uid" => $user_id
        );
        
        // set curl options
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // execute the request
        $res = curl_exec($ch);
        
        // check for any errors
        if(curl_errno($ch)) {
            $data_insert = array(
                "status" => "error",
                "message" => "Could not send backup ".curl_error($ch)
            );
            // return the error
            echo json_encode($data_insert);
            mysqli_close($conn);
        } else {
            
            $resp = json_decode($res);
            
            if($resp->status === 'success') {

                // insert the upload time

                
                $data_insert = array(
                    "status" => "success",
                    "message" => "success"
                );
                // return the error
                echo json_encode($data_insert);
                mysqli_close($conn);
            } else {
                $data_insert = array(
                    "status" => "error",
                    "message" => "Error from online server \n".$resp->message
                );
                // return the error
                echo json_encode($data_insert);
                mysqli_close($conn);
            }
            
        }

        unlink($backupFile);
        
    
    } else {
        $data_insert = array(
            "status" => "error",
            "message" => "Could not create backup"
        );
        // return the error
        echo json_encode($data_insert);
        mysqli_close($conn);
    }

}


if($action === 'restore-locally') {

    $user_id = mysqli_escape_string($conn, decrypt_data($request->added_by));


    $offline_trans_date = get_latest_offline_transaction($conn);

    // get the latest online transaction
    $online_date = get_latest_online_transaction($conn);

    $online_trans_date = $online_date['latest_transaction_date'];
    
    $last_upload_date = $online_date['latest_upload_date'];

    $offline_trans_date = new DateTime($offline_trans_date);
    $online_trans_date = new DateTime($online_trans_date);
    $last_upload_date = new DateTime($last_upload_date);

    $current_download_time = get_today();

    // check any transactions btn prev upload time and current download time
    
    if($offline_trans_date > $last_upload_date) {
        // changes were made offline
        
        // check if there were any changes made online
        if($online_trans_date > $last_upload_date) {
            // changes were also made online

            // offline data will be overwritten and this is not allowed
            // so ask user to upload again and admin to make changes again

            $data_insert = array(
                "status" => "error",
                "message" => "You are about to lose transactions made offline after ".$last_upload_date->format('d/m/Y g:i A'),
                "online" => $online_trans_date,
                "offline" => $offline_trans_date,
                "upload" => $last_upload_date
            );
            // return the error
            echo json_encode($data_insert);
            mysqli_close($conn);
        
        } else {

            // changes were only made offline

            // do not restore the database
            // inform user no changes were made by admin online

            $data_insert = array(
                "status" => "success",
                "message" => "success2"
            );
            // return the error
            echo json_encode($data_insert);
            mysqli_close($conn);

        }

    } else if ($offline_trans_date < $last_upload_date) {

        // no changes have been made offline

         // check if there were any changes made online
         if($online_trans_date > $last_upload_date) {
            // changes were only made online

            // start the restore process

            restore_local_db_from_online_db_new($conn, $user_id);

            //restore_local_db_from_online_db($user_id);

         } else {

            // no changes were made either locally or online

            
            // restore_local_db_from_online_db_new($conn, $user_id);

           // restore_local_db_from_online_db($user_id);

            // return success

            $data_insert = array(
                "status" => "success",
                "message" => "success1"
            );
            // return the error
            echo json_encode($data_insert);
            mysqli_close($conn);

         }

    } else {
        // both offline date, online date and upload date are the same
        // this scenario is not possible

        // do nothing

        // return success
        $data_insert = array(
            "status" => "success",
            "message" => "success3"
        );
        // return the error
        echo json_encode($data_insert);
        mysqli_close($conn);

    }

}


if ($action === 'restore-locally-confirmed') {

    $user_id = mysqli_escape_string($conn, decrypt_data($request->added_by));

    restore_local_db_from_online_db_new($conn, $user_id);

}



if($action === 'update_min_quantity') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    $temp_header = explode(" ",$authHeader);
    $jwt = decrypt_data($request->at);

    try {
        JWT::$leeway = 10;
        $decoded = JWT::decode($jwt, SECRET_KEY, array(ALGORITHM));
        
        $mac = mysqli_escape_string($conn, decrypt_data($request->mac));
        $min_qty = mysqli_escape_string($conn, decrypt_data($request->min_qty));
        $added_by = mysqli_escape_string($conn, decrypt_data($request->added_by));


        if(check_device_mac($conn, $mac)) {

            $sql = "UPDATE min_stock_items SET min_quantity='{$min_qty}'";
            $result = run_query_form($conn, $sql);

            $min_items = get_min_stock_items($conn);

            $data_insert=array(
                "status" => "success",
                "message" => "success",
                "data" => $min_items
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



