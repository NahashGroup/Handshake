<?php

class everyone
{
  protected $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function clean_text($data)
  {
    $data = $this->db->escapeString($data);
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
  }

  public function add_to_logs($type, $log)
  {
    $log_statement = 'INSERT INTO "logs" ("log_type","log_data", "log_timestamp") VALUES (:log_type, :log_data, :log_timestamp)';
    $params[':log_type'] = $type; $types[':log_type'] = SQLITE3_TEXT;
    $params[':log_data'] = $log; $types[':log_data'] = SQLITE3_TEXT;
    $params[':log_timestamp'] = date("d-m-Y H:i:s"); $types[':log_timestamp'] = SQLITE3_TEXT;
    $log_statement = $this->execute_query($log_statement, $params, $types, false);
  }

  public function execute_query($query, $params = [], $types = [], $rollback = false)
  {
    $statement = $this->db->prepare($query);

    if ($statement) {
      if (isset($params) && isset($types)) {
        foreach ($params as $key => $value) {
          $type = isset($types[$key]) ? $types[$key] : SQLITE3_TEXT;
          $statement->bindValue($key, $value, $type);
        }
      }

      $result = $statement->execute();

      if ($result) {
        $params = [];
        $types = [];
        return $result;
      } else {
        $debug = debug_backtrace()[0];
        $log = "Query execution error in file" . $debug['file'] . " at line " . $debug['line'] . " : " . $this->db->lastErrorMsg();
        if ($rollback === true) {
          $this->db->exec('ROLLBACK');
        }
        $this->add_to_logs("fatal", $log);
      }
    } else {
      $debug = debug_backtrace()[0];
      $log = "Query preparation error in file " . $debug['file'] . " at line " . $debug['line'] . " : " . $this->db->lastErrorMsg();
      if ($rollback === true) {
        $this->db->exec('ROLLBACK');
      }
      $this->add_to_logs("fatal", $log);
    }

  }

  public function get_value_from_column_query($value, $column_name, $table_name)
  {
    $statement = 'SELECT $column_name FROM $table_name WHERE $column_name = :value';

    $params[':value'] = $value; $types[':value'] = SQLITE3_TEXT;

    $result = $this->execute_query($statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

    if ($result) {
      return $result[$column_name];
    }

    return false;
  }

  public function ratelimit($action, $ratelimit_seconds, $main_redirect)
  {
    $session_key = $action . '_last_time';
    if (isset($_SESSION[$session_key])) {
      $time_since_last_action = time() - $_SESSION[$session_key];
      if ($time_since_last_action < $ratelimit_seconds) {
        $remaining_time = $ratelimit_seconds - $time_since_last_action;
        if ($remaining_time > 0) {
          $ratelimit_message = "Please wait " . $remaining_time . " seconds before performing this action again.";
          if ($main_redirect === true) {
            error_or_success_popup("error", $ratelimit_message, "?location=index");
          } else {
            error_or_success_popup("error", $ratelimit_message, false);
          }
        }
      }
    }
    $_SESSION[$session_key] = time();
  }

  public function global_exchanges_stats()
  {

    $statuses = [
      'waiting' => 'Waiting',
      'in_progress' => 'In Progress',
      'disputed' => 'In Dispute',
      'completed' => 'Completed',
      'closed' => 'Closed'
    ];

    $results = "";

    foreach ($statuses as $status => $translated_status) {
      $count_status_statement = "SELECT COUNT(*) as count FROM exchanges WHERE status = :status OR (status = 'delivered' AND :status = 'in_progress')";
      $params[':status'] = $status; $types[':status'] = SQLITE3_TEXT;
      $count_status_statement = $this->execute_query($count_status_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

      $results .= " | ";

      if ($count_status_statement) {
        $count = $count_status_statement['count'];
        $results .= "$translated_status : $count";
      } else {
        $results .= "";
      }

    }

    $results .= " | ";

    return $results;

  }


  public function call_electrum_rpc($method, $params = [])
  {

    global $electrum_rpc_user, $electrum_rpc_password, $electrum_rpc_url, $electrum_rpc_port;

    $url = "http://$electrum_rpc_user:$electrum_rpc_password@$electrum_rpc_url:$electrum_rpc_port";

    $data = json_encode([
      'id' => 'curltext',
      'method' => $method,
      'params' => $params,
    ]);

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($curl);

    $error = curl_error($curl);

    curl_close($curl);

    if ($error) {
      die("Connection error with Electrum RPC: $error");
    }

    return json_decode($response, true);
  }


  public function call_monero_rpc($method, $params = [])
  {

    global $monero_rpc_user, $monero_rpc_password, $monero_rpc_url, $monero_rpc_port;

    $url = "http://$monero_rpc_user:$monero_rpc_password@$monero_rpc_url:$monero_rpc_port/json_rpc";

    $data = json_encode([
      'jsonrpc' => '2.0',
      'id' => '0',
      'method' => $method,
      'params' => $params
    ]);

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);

    $response = curl_exec($curl);

    $error = curl_error($curl);

    curl_close($curl);

    if ($error) {
      die("Connection error with Monero RPC: $error");
    }

    return json_decode($response, true);
  }


  private function add_new_auth_token_and_deal_with_exchange($role, $exchange, $sum, $cryptocurrency, $refund_address)
  {

    if (!is_null($exchange)) {
      $exchange = $this->clean_text($exchange);
    }

    $new_auth_token = generate_random_token(64);

    if ($role === "vendor") {

      $exchange = generate_random_exchange(32);

    } elseif ($role === "customer") {

      $database_exchange_data = $this->get_exchange_data($exchange);

      if ($database_exchange_data["associated"] !== "yes") {

        if ($cryptocurrency === $database_exchange_data["cryptocurrency"]) {

          $converted_sum = add_percentage_and_convert($database_exchange_data["sum"], $database_exchange_data["cryptocurrency"]);

          if (is_numeric($converted_sum)) {

            $exchange = $database_exchange_data["exchange"];

          } else {
            error_or_success_popup("error", "An error has occurred while retrieving cryptocurrency rates. Please try again later. We apologise for any inconvenience.", false);
          }

        } else {
          error_or_success_popup("error", "An error has occurred.", false);
        }

      } else {
        error_or_success_popup("error", "The exchange is already associated.", "?location=index");
      }
    } else {
      die();
    }

    $this->db->exec('BEGIN TRANSACTION');

    $new_auth_token_statement = 'INSERT INTO "auth_tokens" ("auth_token", "exchange", "role", "refund_address") VALUES (:auth_token, :exchange, :role, :refund_address)';

    $params[':auth_token'] = $new_auth_token; $types[':auth_token'] = SQLITE3_TEXT;
    $params[':exchange'] = $exchange; $types[':exchange'] = SQLITE3_TEXT;
    $params[':role'] = $role; $types[':role'] = SQLITE3_TEXT;
    $params[':refund_address'] = $refund_address; $types[':refund_address'] = SQLITE3_TEXT;

    $new_auth_token_statement = $this->execute_query($new_auth_token_statement, $params, $types, true);

    if ($new_auth_token_statement) {

      if ($role === "vendor") {

        if ($cryptocurrency === "bitcoin") {

          $deposit_address_array = $this->call_electrum_rpc('createnewaddress');
          $deposit_address = $deposit_address_array['result'];

          $new_exchange_statement = 'INSERT INTO "exchanges" ("exchange", "timestamp", "sum", "cryptocurrency", "deposit_address", "status") VALUES (:exchange, :timestamp, :sum, :cryptocurrency, :deposit_address, :status)';

        } elseif ($cryptocurrency === "monero") {

          $account_array = $this->call_monero_rpc('create_account', ['label' => $exchange]);
          $deposit_address_array = $this->call_monero_rpc('create_address', ['account_index' => $account_array['result']['account_index']]);
          $deposit_address = $deposit_address_array['result']['address'];

          $new_exchange_statement = 'INSERT INTO "exchanges" ("exchange", "timestamp", "sum", "cryptocurrency", "deposit_address", "account_index", "status") VALUES (:exchange, :timestamp, :sum, :cryptocurrency, :deposit_address, :account_index, :status)';

          $params[':account_index'] = $account_array['result']['account_index']; $types[':account_index'] = SQLITE3_INTEGER;

        } else {
          die();
        }

        $params[':exchange'] = $exchange; $types[':exchange'] = SQLITE3_TEXT;
        $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
        $params[':sum'] = $sum; $types[':sum'] = SQLITE3_INTEGER;
        $params[':cryptocurrency'] = $cryptocurrency; $types[':cryptocurrency'] = SQLITE3_TEXT;
        $params[':deposit_address'] = $deposit_address; $types[':deposit_address'] = SQLITE3_TEXT;
        $params[':status'] = "waiting"; $types[':status'] = SQLITE3_TEXT;

        $new_exchange_statement = $this->execute_query($new_exchange_statement, $params, $types, true);

        if ($new_exchange_statement) {

          $this->db->exec('COMMIT');

          $_SESSION['auth_token'] = $new_auth_token;
          $_SESSION['role'] = $role;
          $_SESSION['exchange'] = $exchange;
          $_SESSION['refund_address'] = $refund_address;

          error_or_success_popup("success", "Welcome to your seller's area.", "?location=panel&exchange=$exchange");

        }

      } elseif ($role === "customer") {

        $update_exchange_associated_statement = 'UPDATE exchanges SET associated = "yes", converted_sum = :converted_sum WHERE exchange = :exchange';

        $params[':exchange'] = $exchange; $types[':exchange'] = SQLITE3_TEXT;
        $params[':converted_sum'] = $converted_sum; $types[':converted_sum'] = SQLITE3_NUM;

        $update_exchange_associated_statement = $this->execute_query($update_exchange_associated_statement, $params, $types, true);

        if ($update_exchange_associated_statement) {

          $this->db->exec('COMMIT');

          $_SESSION['auth_token'] = $new_auth_token;
          $_SESSION['role'] = $role;
          $_SESSION['exchange'] = $exchange;
          $_SESSION['refund_address'] = $refund_address;

          error_or_success_popup("success", "Welcome to your customer area.", "?location=panel&exchange=$exchange");

        }

      } else {
        die();
      }
    }

  }

  public function get_exchange_data($exchange)
  {
    if (validate_numeric_string($exchange, 32, 32)) {

      $get_exchange_data_statement = 'SELECT exchange, timestamp, sum, converted_sum, cryptocurrency, deposit_address, account_index, status, completed_message, associated FROM exchanges WHERE exchange = :exchange';
      $params[':exchange'] = $exchange; $types[':exchange'] = SQLITE3_TEXT;
      $get_exchange_data_statement = $this->execute_query($get_exchange_data_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

      if ($get_exchange_data_statement) {

        return $get_exchange_data_statement;

      } else {
        error_or_success_popup("error", "No exchange was found.", "?location=index");
      }
    } else {
      error_or_success_popup("error", "The exchange identifier is invalid.", "?location=index");
    }
  }


  public function add_escrow($token)
  {
    global $add_escrow_case;
    if ($add_escrow_case === "enabled") {

      if (isset($_POST["add"]) && sha1(session_id()) === $token) {

        if ($_SESSION["code"] === strtolower($_POST["code"])) {

          $sum = (string) filter_input(INPUT_POST, "sum");
          $cryptocurrency = (string) filter_input(INPUT_POST, "cryptocurrency");
          $refund_address = (string) filter_input(INPUT_POST, "refund_address");

          $this->add_escrow_gears("vendor", null, $sum, $cryptocurrency, $refund_address);

        } else {
          error_or_success_popup("error", "The captcha is invalid.", false);
        }

      }
      if (isset($_POST["join"]) && sha1(session_id()) === $token) {

        $exchange = (string) filter_input(INPUT_POST, "exchange");
        $cryptocurrency = (string) filter_input(INPUT_POST, "cryptocurrency");
        $refund_address = (string) filter_input(INPUT_POST, "refund_address");

        $this->add_escrow_gears("customer", $exchange, null, $cryptocurrency, $refund_address);

      }
    }
  }


  private function add_escrow_gears($role, $exchange, $sum, $cryptocurrency, $refund_address)
  {
    $this->ratelimit('add_escrow', 60, false);
    if ($role === "vendor") {
      if ($cryptocurrency === "bitcoin") {
        if (validate_numeric_value($sum, 25, 50000)) {
          if (is_valid_cryptocurrency_address($cryptocurrency, $refund_address)) {
            $this->add_new_auth_token_and_deal_with_exchange("vendor", null, $sum, $cryptocurrency, $refund_address);
          } else {
            error_or_success_popup("error", "The refund address does not correspond to a Bitcoin address.", false);
          }
        } else {
          error_or_success_popup("error", "The amount is invalid, it must be between €25 and €50,000 (for bitcoin) and it must be in digital format.", false);
        }
      } elseif ($cryptocurrency === "monero") {
        if (validate_numeric_value($sum, 10, 50000)) {
          if (is_valid_cryptocurrency_address($cryptocurrency, $refund_address)) {
            $this->add_new_auth_token_and_deal_with_exchange("vendor", null, $sum, $cryptocurrency, $refund_address);
          } else {
            error_or_success_popup("error", "The refund address does not correspond to a Monero address.", false);
          }
        } else {
          error_or_success_popup("error", "The amount is invalid, must be between €10 and €50,000 (for monero) and must be in digital format.", false);
        }
      } else {
        error_or_success_popup("error", "A problem has occurred.", false);
      }
    } elseif ($role === "customer") {
      if (validate_numeric_string($exchange, 32, 32)) {
        if (is_valid_cryptocurrency_address($cryptocurrency, $refund_address)) {
          $this->add_new_auth_token_and_deal_with_exchange("customer", $exchange, null, $cryptocurrency, $refund_address);
        } else {
          error_or_success_popup("error", "The refund address does not match the cryptocurrency chosen by the seller. Read more carefully.", false);
        }
      } else {
        error_or_success_popup("error", "The exchange identifier is invalid.", false);
      }
    } else {
      die();
    }


  }

  public function login($token)
  {

    if (isset($_POST["login"]) && sha1(session_id()) === $token) {

      if ($_SESSION["code"] === strtolower($_POST["code"])) {
        $auth_token = (string) filter_input(INPUT_POST, "auth_token");
        $this->login_gears($auth_token);
      } else {
        error_or_success_popup("error", "The captcha is invalid.", false);
      }
    }

  }

  private function login_gears($auth_token)
  {
    if (is_valid_token($auth_token)) {

      $login_statement = 'SELECT auth_token, role, exchange, refund_address FROM auth_tokens WHERE auth_token = :auth_token';
      $params[':auth_token'] = $auth_token; $types[':auth_token'] = SQLITE3_TEXT;
      $login_statement = $this->execute_query($login_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

      if ($login_statement) {

        $_SESSION['auth_token'] = $login_statement["auth_token"];
        $_SESSION['role'] = $login_statement["role"];
        $_SESSION['exchange'] = $login_statement["exchange"];
        $_SESSION['refund_address'] = $login_statement["refund_address"];

        error_or_success_popup("success", "Hello, you are logged in to your account.", "?location=panel&exchange=" . $_SESSION['exchange']);
      } else {
        error_or_success_popup("error", "No Escrow request exists for this token.", false);
      }

    } else {
      error_or_success_popup("error", "The token is not in a valid format.", false);
    }
  }




}

class user extends everyone
{
  public function __construct($db)
  {
    parent::__construct($db);
    if (!isset($_SESSION['auth_token'])) {
      die();
    }
  }

  public function disconnect()
  {
    session_unset();
    session_destroy();
    header("Location: ?location=index");
    die();
  }


  public function get_refund_address($role, $exchange)
  {
    if ($this->get_exchange_data($exchange)) {

      if (in_array($role, array("vendor", "customer"))) {

        $get_refund_address_statement = 'SELECT refund_address FROM auth_tokens WHERE exchange = :exchange AND role = :role';

        $params[':exchange'] = $exchange; $types[':exchange'] = SQLITE3_TEXT;
        $params[':role'] = $role; $types[':role'] = SQLITE3_TEXT;

        $get_refund_address_statement = $this->execute_query($get_refund_address_statement, $params, $types, false)->fetchArray(SQLITE3_ASSOC);

        if ($get_refund_address_statement) {
          return $get_refund_address_statement['refund_address'];
        } else {
          return false;
        }
      }
    }
  }

  public function check_crypto_balance($type, $address, $account_index)
  {
    switch ($type) {
      case 'bitcoin':
        return $this->check_bitcoin_balance($address);

      case 'monero':
        return $this->check_monero_balance($account_index);

      default:
        die("Type of cryptocurrency not supported : $type");
    }
  }

  private function check_bitcoin_balance($address)
  {

    if (is_valid_cryptocurrency_address("bitcoin", $address)) {

      $response = $this->call_electrum_rpc('getaddressbalance', [$address]);

      if (isset($response['error'])) {
        die("Error obtaining Bitcoin balance : " . $response['error']['message']);
      }

      $confirmed_balance = $response['result']['confirmed'];

      if ($confirmed_balance > 0) {
        return $confirmed_balance;
      } else {
        return 0;
      }
    }
  }

  private function check_monero_balance($account_index)
  {
    if (is_integer($account_index)) {

      $response = $this->call_monero_rpc('get_balance', ['account_index' => $account_index]);

      if (isset($response['error'])) {
        die("Error obtaining Monero balance : " . $response['error']['message']);
      }

      $unlocked_balance = $response['result']['unlocked_balance'] / 1e12;

      if ($unlocked_balance > 0) {
        return $unlocked_balance;
      } else {
        return 0;
      }
    }
  }

  public function send_crypto($type, $source_address, $account_index, $destination_address, $amount)
  {
    switch ($type) {
      case 'bitcoin':
        return $this->send_bitcoin($source_address, $destination_address, $amount);

      case 'monero':
        return $this->send_monero($account_index, $destination_address, $amount);

      default:
        die("Type of cryptocurrency not supported : $type");
    }
  }

  private function send_bitcoin($source_address, $destination_address, $amount)
  {

    if (is_valid_cryptocurrency_address("bitcoin", $source_address) && is_valid_cryptocurrency_address("bitcoin", $destination_address) && is_numeric($amount)) {

      global $electrum_rpc_password;

      $balance = $this->check_bitcoin_balance($source_address);
      if ($balance < $amount) {
        die("Insufficient balance to complete Bitcoin transaction.");
      }


      #$fee_params = ['fee_rate' => '30'];
      #$fee_response = $this->call_electrum_rpc('setfeerate', $fee_params);

      #if (isset($fee_response['error'])) {
      #die("Error when defining the Bitcoin fee rate : " . json_encode($fee_response['error']));
      #}

      $tx_params = [
        'destination' => $destination_address,
        'amount' => $amount,
        'from_addr' => $source_address
      ];

      $tx_response = $this->call_electrum_rpc('payto', $tx_params);

      if (isset($tx_response['error'])) {
        die("Error preparing Bitcoin transaction : " . json_encode($tx_response['error']));
      }

      $sign_params = [
        'tx' => $tx_response['result'],
        'password' => $electrum_rpc_password
      ];

      $sign_response = $this->call_electrum_rpc('signtransaction', $sign_params);

      if (isset($sign_response['error'])) {
        die("Error signing Bitcoin transaction : " . json_encode($sign_response['error']));
      }

      $send_params = [
        'tx' => $sign_response['result']
      ];

      $send_response = $this->call_electrum_rpc('broadcast', $send_params);

      if (isset($send_response['error'])) {
        die("Error sending Bitcoin transaction : " . json_encode($send_response['error']));
      }

      if ($send_response['result']) {
        return $send_response['result'];
      } else {
        return false;
      }
    }
  }

  private function send_monero($account_index, $destination_address, $amount)
  {

    if (is_integer($account_index) && is_valid_cryptocurrency_address("monero", $destination_address) && is_numeric($amount)) {

      global $monero_fee;

      $balance = $this->check_monero_balance($account_index);

      if ($balance < $amount) {
        die("Insufficient balance to complete Monero transaction.");
      }

      $atomic_amount = convert_to_atomic_units($amount);

      $priority = $monero_fee;

      $params = [
        'destinations' => [['amount' => $atomic_amount, 'address' => $destination_address]],
        'account_index' => $account_index,
        'priority' => $priority,
        'ring_size' => 7,
        'unlock_time' => 0,
        'get_tx_keys' => true,
        'do_not_relay' => false,
        'get_tx_hex' => true
      ];

      $response = $this->call_monero_rpc('transfer', $params);

      if (isset($response['error'])) {
        die("Error sending Monero transaction : " . $response['error']['message']);
      }

      if ($response['result']) {
        return $response['result'];
      } else {
        return false;
      }
    }
  }

  public function confirm_delivery($token, $exchange)
  {
    if (isset($_POST["confirm_delivery"]) && sha1(session_id()) === $token) {

      $this->confirm_delivery_gears($exchange);

    }
  }

  private function confirm_delivery_gears($exchange)
  {
    if ($this->get_exchange_data($exchange)) {

      $update_status_statement = 'UPDATE exchanges SET status = "delivered", timestamp = :timestamp WHERE exchange = :exchange';

      $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
      $params[':exchange'] = $exchange; $types[':exchange'] = SQLITE3_TEXT;

      $update_status_statement = $this->execute_query($update_status_statement, $params, $types, false);

      if ($update_status_statement) {
        error_or_success_popup("success", "The exchange has been correctly marked as delivered.", false);
      }

    }
  }

  public function confirm_receipt($token, $exchange)
  {

    if (isset($_POST["confirm_receipt"]) && sha1(session_id()) === $token) {

      $this->confirm_receipt_gears($exchange);

    }
  }

  private function confirm_receipt_gears($exchange)
  {
    if ($this->get_exchange_data($exchange)) {

      $update_status_statement = 'UPDATE exchanges SET status = "completed", timestamp = :timestamp WHERE exchange = :exchange';

      $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
      $params[':exchange'] = $exchange; $types[':exchange'] = SQLITE3_TEXT;

      $update_status_statement = $this->execute_query($update_status_statement, $params, $types, false);

      if ($update_status_statement) {
        error_or_success_popup("success", "The exchange has been correctly marked as completed, thank you!", false);
      }

    }
  }

  public function report_dispute($token, $exchange)
  {
    if (isset($_POST["report_dispute"]) && sha1(session_id()) === $token) {

      $this->report_dispute_gears($exchange);

    }
  }

  private function report_dispute_gears($exchange)
  {
    if ($this->get_exchange_data($exchange)) {

      $update_status_statement = 'UPDATE exchanges SET status = "disputed", timestamp = :timestamp WHERE exchange = :exchange';

      $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
      $params[':exchange'] = $exchange; $types[':exchange'] = SQLITE3_TEXT;

      $update_status_statement = $this->execute_query($update_status_statement, $params, $types, false);

      if ($update_status_statement) {
        error_or_success_popup("success", "The exchange was correctly marked as contentious.", false);
      }
    }
  }


  public function send_dispute_message($token, $sender_token, $exchange)
  {
    if (isset($_POST["send_dispute_message"]) && sha1(session_id()) === $token) {

      $message = (string) filter_input(INPUT_POST, "message");

      $this->send_dispute_message_gears($sender_token, $exchange, $message);

    }
  }


  private function send_dispute_message_gears($sender_token, $exchange, $message)
  {

    if ($dispute = $this->get_exchange_data($exchange)) {
      if ($dispute["status"] === "disputed" && ($dispute["exchange"] === $_SESSION['exchange'] || $_SESSION['role'] === "admin")) {

        if ($sender_token === $_SESSION['auth_token']) {

          if (validate_post($message, 1, 600)) {

            // $message = $this->clean_text($message);

            $send_dispute_message_statement = 'INSERT INTO "messages" ("sender_token", "exchange", "message", "timestamp") VALUES (:sender_token, :exchange, :message, :timestamp)';

            $params[':sender_token'] = $sender_token; $types[':sender_token'] = SQLITE3_TEXT;
            $params[':exchange'] = $exchange; $types[':exchange'] = SQLITE3_TEXT;
            $params[':message'] = $message; $types[':message'] = SQLITE3_TEXT;
            $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;

            $send_dispute_message_statement = $this->execute_query($send_dispute_message_statement, $params, $types, false);

            if ($send_dispute_message_statement) {
              error_or_success_popup("success", "Your message has been sent successfully.", false);
            } else {
              error_or_success_popup("error", "An error has occurred while sending your message.", false);
            }

          } else {
            error_or_success_popup("error", "Your message must be between 1 and 600 characters long.", false);
          }

        } else {
          error_or_success_popup("error", "You don't send the message with your token, which is very strange.", false);
        }

      } else {
        error_or_success_popup("error", "The exchange is not marked as contentious.", "?location=index");
      }
    }

  }

  public function display_messages($exchange)
  {
    return $this->display_messages_gears($exchange);
  }

  private function display_messages_gears($exchange)
  {
    if ($dispute = $this->get_exchange_data($exchange)) {

      if ($dispute["status"] === "disputed" && ($dispute["exchange"] === $_SESSION['exchange'] || $_SESSION['role'] === "admin")) {

        $display_messages_statement = 'SELECT m.*, a.role AS sender_role 
                                               FROM messages m
                                               INNER JOIN auth_tokens a ON m.sender_token = a.auth_token
                                               WHERE m.exchange = :exchange 
                                               ORDER BY m.timestamp ASC';

        $params[':exchange'] = $exchange; $types[':exchange'] = SQLITE3_TEXT;

        $display_messages_statement = $this->execute_query($display_messages_statement, $params, $types, false);

        $display_messages = '<div class="display_messages">';

        if ($display_messages_statement && $display_messages_statement->fetchArray(SQLITE3_ASSOC)) {


          $display_messages .= '<div class="message_container">';

          $role_names = [
            'customer' => 'Customer',
            'vendor' => 'Seller',
            'admin' => 'Administrator'
          ];

          while ($message = $display_messages_statement->fetchArray(SQLITE3_ASSOC)) {

            $sender_role = $message['sender_role'];
            $is_current_user = $message['sender_token'] === $_SESSION['auth_token'];

            if ($is_current_user) {
              $display_role = 'Your message';
              $message_class = 'message-sender';
            } else {
              $display_role = array_key_exists($sender_role, $role_names) ? $role_names[$sender_role] : $sender_role;
              $message_class = ($sender_role === 'admin') ? 'message-admin' : 'message-receiver';
            }

            $display_messages .= '<div class="' . encode_html($message_class) . '">'
              . '<strong>'
              . encode_html($display_role)
              . '</strong><br>'
              . encode_html($message['message'])
              . '<span class="message-timestamp">'
              . encode_html(convert_date($message['timestamp']))
              . '</span></div>';
          }



          $display_messages .= '</div>';

          $display_messages .= '<form action="" method="POST">'
            . '<p>Message:</p>'
            . '<textarea maxlength="600" placeholder="You can only send 600 characters per message." name="message"></textarea>'
            . '<input type="hidden" name="token" value="' . encode_html(sha1(session_id())) . '">'
            . '<p><button type="submit" name="send_dispute_message">Send message</button></p>'
            . '</form>';

        } else {

          $display_messages .= '<p>Aucun message trouvé pour cet échange. Vous pouvez, si vous le souhaitez, envoyer le premier message.</p>'
            . '<form action="" method="POST">'
            . '<p>Message:</p>'
            . '<textarea maxlength="600" placeholder="You can only send 600 characters per message." name="message"></textarea>'
            . '<input type="hidden" name="token" value="' . encode_html(sha1(session_id())) . '">'
            . '<p><button type="submit" name="send_dispute_message">Send message</button></p>'
            . '</form>';
        }

        $display_messages .= '</div>';

        return $display_messages;

      } else {
        error_or_success_popup("error", "The exchange is not marked as contentious.", "?location=index");
      }
    }
  }


}

class control extends user
{
  public function __construct($db)
  {
    parent::__construct($db);
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
      die();
    }
  }

  public function modify_site_data($token)
  {
    if (isset($_POST["modify_site_data"]) && sha1(session_id()) === $token) {
      $new_site_name = (string) filter_input(INPUT_POST, "site_name");
      $new_site_description = (string) filter_input(INPUT_POST, "site_description");
      $new_site_keywords = (string) filter_input(INPUT_POST, "site_keywords");
      $this->modify_site_data_gears($new_site_name, $new_site_description, $new_site_keywords);
    }

  }

  private function modify_site_data_gears($new_site_name, $new_site_description, $new_site_keywords)
  {

    $set_clause = array();
    $params = array();

    if (validate_post($new_site_name, 1, 20)) {
      $new_site_name = $this->clean_text($new_site_name);
      $set_clause[] = 'name = :new_site_name';
      $params[":new_site_name"] = $new_site_name;
    }


    if (validate_post($new_site_description, 1, 200)) {
      $new_site_description = $this->clean_text($new_site_description);
      $set_clause[] = 'description = :new_site_description';
      $params[":new_site_description"] = $new_site_description;
    }

    if (validate_post($new_site_keywords, 1, 200)) {
      $new_site_keywords = $this->clean_text($new_site_keywords);
      $set_clause[] = 'keywords = :new_site_keywords';
      $params[":new_site_keywords"] = $new_site_keywords;
    }


    if (empty($set_clause)) {
      error_or_success_popup("error", "Please change at least one value.", false);
    }

    $set_clause_str = implode(", ", $set_clause);

    $modify_site_data_statement = "UPDATE site SET $set_clause_str";

    $modify_site_data_statement = $this->execute_query($modify_site_data_statement, $params);

    if ($modify_site_data_statement) {
      error_or_success_popup("success", "The site information has been successfully modified.", false);
    }
  }

  public function check_and_update_exchanges($token)
  {
    if (isset($_POST["check_and_update_exchanges"]) && sha1(session_id()) === $token) {

      $this->check_and_update_exchanges_gears();

    }

  }

  private function check_and_update_exchanges_gears()
  {


    $get_status_exchanges_statement = 'SELECT exchange, timestamp, converted_sum, cryptocurrency, deposit_address, account_index, status, associated, completed_message FROM exchanges';
    $params = $types = [];
    $get_status_exchanges_statement = $this->execute_query($get_status_exchanges_statement, $params, $types, false);

    while ($exchange = $get_status_exchanges_statement->fetchArray(SQLITE3_ASSOC)) {

      if ($exchange['status'] === "waiting") {

        if ($this->check_crypto_balance($exchange['cryptocurrency'], $exchange['deposit_address'], $exchange['account_index']) >= $exchange['converted_sum'] && $exchange['converted_sum'] > 0) {

          $update_status_statement = 'UPDATE exchanges SET status = "in_progress", timestamp = :timestamp WHERE exchange = :exchange';
          $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
          $params[':exchange'] = $exchange['exchange']; $types[':exchange'] = SQLITE3_TEXT;

          $this->execute_query($update_status_statement, $params, $types, false);

        } elseif (has_time_elapsed($exchange['timestamp'], "7")) {

          $update_status_statement = 'UPDATE exchanges SET status = "closed", timestamp = :timestamp WHERE exchange = :exchange';
          $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
          $params[':exchange'] = $exchange['exchange']; $types[':exchange'] = SQLITE3_TEXT;

          $this->execute_query($update_status_statement, $params, $types, false);

        }


      }
      if ($exchange['status'] === "in_progress") {

        if (has_time_elapsed($exchange['timestamp'], "7")) {

          if ($this->check_crypto_balance($exchange['cryptocurrency'], $exchange['deposit_address'], $exchange['account_index']) >= $exchange['converted_sum'] && $exchange['converted_sum'] > 0) {

            if ($refund_address = $this->get_refund_address("customer", $exchange['exchange'])) {

              if ($exchange['cryptocurrency'] === "bitcoin") {
                $new_amount = $exchange['converted_sum'] / 1.15;
              } elseif ($exchange['cryptocurrency'] === "monero") {
                $new_amount = $exchange['converted_sum'] / 1.06;
              } else {
                die();
              }

              $amount = round($new_amount, 8);

              if ($this->send_crypto($exchange['cryptocurrency'], $exchange['deposit_address'], $exchange['account_index'], $refund_address, $amount)) {

                $update_status_statement = 'UPDATE exchanges SET status = "closed", timestamp = :timestamp WHERE exchange = :exchange';

                $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
                $params[':exchange'] = $exchange['exchange']; $types[':exchange'] = SQLITE3_TEXT;

                $this->execute_query($update_status_statement, $params, $types, false);

              }

            }
          } else {


            $update_status_statement = 'UPDATE exchanges SET status = "closed", timestamp = :timestamp WHERE exchange = :exchange';

            $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
            $params[':exchange'] = $exchange['exchange']; $types[':exchange'] = SQLITE3_TEXT;

            $this->execute_query($update_status_statement, $params, $types, false);

          }

        }

      }
      if ($exchange['status'] === "delivered") {

        if (has_time_elapsed($exchange['timestamp'], "14")) {

          if ($this->check_crypto_balance($exchange['cryptocurrency'], $exchange['deposit_address'], $exchange['account_index']) >= $exchange['converted_sum'] && $exchange['converted_sum'] > 0) {

            if ($refund_address = $this->get_refund_address("vendor", $exchange['exchange'])) {

              if ($exchange['cryptocurrency'] === "bitcoin") {
                $new_amount = $exchange['converted_sum'] / 1.15;
              } elseif ($exchange['cryptocurrency'] === "monero") {
                $new_amount = $exchange['converted_sum'] / 1.06;
              } else {
                die();
              }

              $amount = round($new_amount, 8);

              if ($this->send_crypto($exchange['cryptocurrency'], $exchange['deposit_address'], $exchange['account_index'], $refund_address, $amount)) {

                $update_status_statement = 'UPDATE exchanges SET status = "closed", timestamp = :timestamp WHERE exchange = :exchange';

                $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
                $params[':exchange'] = $exchange['exchange']; $types[':exchange'] = SQLITE3_TEXT;

                $this->execute_query($update_status_statement, $params, $types, false);

              }

            }
          } else {

            $update_status_statement = 'UPDATE exchanges SET status = "closed", timestamp = :timestamp WHERE exchange = :exchange';

            $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
            $params[':exchange'] = $exchange['exchange']; $types[':exchange'] = SQLITE3_TEXT;

            $this->execute_query($update_status_statement, $params, $types, false);

          }

        }

      }
      if ($exchange['status'] === "completed" && is_null($exchange['completed_message'])) {

        if ($this->check_crypto_balance($exchange['cryptocurrency'], $exchange['deposit_address'], $exchange['account_index']) >= $exchange['converted_sum'] && $exchange['converted_sum'] > 0) {

          if ($refund_address = $this->get_refund_address("vendor", $exchange['exchange'])) {

            if ($exchange['cryptocurrency'] === "bitcoin") {
              $new_amount = $exchange['converted_sum'] / 1.15;
            } elseif ($exchange['cryptocurrency'] === "monero") {
              $new_amount = $exchange['converted_sum'] / 1.06;
            } else {
              die();
            }

            $amount = round($new_amount, 8);

            if ($this->send_crypto($exchange['cryptocurrency'], $exchange['deposit_address'], $exchange['account_index'], $refund_address, $amount)) {

              $update_status_statement = 'UPDATE exchanges SET completed_message = "Pas de litige.", timestamp = :timestamp WHERE exchange = :exchange';

              $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
              $params[':exchange'] = $exchange['exchange']; $types[':exchange'] = SQLITE3_TEXT;

              $this->execute_query($update_status_statement, $params, $types, false);

            }

          }


        }


      }

    }
  }


  public function close_escrow($token)
  {
    if (isset($_POST["close_escrow"]) && sha1(session_id()) === $token) {
      $exchange = (string) filter_input(INPUT_POST, "exchange");
      $this->close_escrow_gears($exchange);
    }

  }

  private function close_escrow_gears($exchange)
  {

    if ($database_exchange = $this->get_exchange_data($exchange)) {
      $close_escrow_statement = 'UPDATE exchanges SET status = "closed", timestamp = :timestamp WHERE exchange = :exchange';
      $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
      $params[':exchange'] = $database_exchange["exchange"]; $types[':exchange'] = SQLITE3_TEXT;
      $close_escrow_statement = $this->execute_query($close_escrow_statement, $params, $types, false);
      if ($close_escrow_statement) {
        error_or_success_popup("success", "The exchange has been closed correctly.", false);
      }
    }
  }

  public function complete_escrow($token)
  {
    if (isset($_POST["complete_escrow"]) && sha1(session_id()) === $token) {
      $exchange = (string) filter_input(INPUT_POST, "exchange");
      $completed_message = (string) filter_input(INPUT_POST, "completed_message");
      $role = (string) filter_input(INPUT_POST, "role");
      $this->complete_escrow_gears($exchange, $completed_message, $role);
    }

  }

  private function complete_escrow_gears($exchange, $completed_message, $role)
  {

    if ($database_exchange = $this->get_exchange_data($exchange)) {

      if (in_array($role, array("vendor", "customer", "admin"))) {

        if (validate_post($completed_message, 1, 600)) {

          // $completed_message = $this->clean_text($completed_message);

          if ($role !== "admin") {

            if ($refund_address = $this->get_refund_address($role, $database_exchange['exchange'])) {

              if ($database_exchange['cryptocurrency'] === "bitcoin") {
                $new_amount = $database_exchange['converted_sum'] / 1.15;
              } elseif ($database_exchange['cryptocurrency'] === "monero") {
                $new_amount = $database_exchange['converted_sum'] / 1.06;
              } else {
                die();
              }

              $amount = round($new_amount, 8);

              if ($this->send_crypto($database_exchange['cryptocurrency'], $database_exchange['deposit_address'], $database_exchange['account_index'], $refund_address, $amount)) {

                $complete_escrow_statement = 'UPDATE exchanges SET status = "completed", completed_message = :completed_message, timestamp = :timestamp WHERE exchange = :exchange';

                $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
                $params[':exchange'] = $database_exchange['exchange']; $types[':exchange'] = SQLITE3_TEXT;
                $params[':completed_message'] = $completed_message; $types[':completed_message'] = SQLITE3_TEXT;

                $complete_escrow_statement = $this->execute_query($complete_escrow_statement, $params, $types, false);

                if ($complete_escrow_statement) {
                  error_or_success_popup("success", "The exchange is now completed.", "?location=control_panel");
                }

              }

            }

          } else {

            $complete_escrow_statement = 'UPDATE exchanges SET status = "completed", completed_message = :completed_message, timestamp = :timestamp WHERE exchange = :exchange';

            $params[':completed_message'] = $completed_message; $types[':completed_message'] = SQLITE3_TEXT;
            $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
            $params[':exchange'] = $database_exchange['exchange']; $types[':exchange'] = SQLITE3_TEXT;

            $complete_escrow_statement = $this->execute_query($complete_escrow_statement, $params, $types, false);

            if ($complete_escrow_statement) {
              error_or_success_popup("success", "The exchange is now completed.", false);
            }


          }
        } else {
          error_or_success_popup("error", "Your message must be between 1 and 600 characters long.", false);
        }

      } else {
        error_or_success_popup("error", "Please choose an appropriate role.", false);
      }

    }
  }

  public function change_escrow_status($token)
  {
    if (isset($_POST["change_escrow_status"]) && sha1(session_id()) === $token) {
      $exchange = (string) filter_input(INPUT_POST, "exchange");
      $completed_message = (string) filter_input(INPUT_POST, "completed_message");
      $escrow_status = (string) filter_input(INPUT_POST, "escrow_status");
      $this->change_escrow_status_gears($exchange, $completed_message, $escrow_status);
    }

  }

  private function change_escrow_status_gears($exchange, $completed_message, $escrow_status)
  {
    if ($database_exchange = $this->get_exchange_data($exchange)) {
      if (in_array($escrow_status, array("waiting", "in_progress", "delivered", "disputed", "completed"))) {

        if (validate_post($completed_message, 0, 600)) {

          $change_escrow_status_statement = 'UPDATE exchanges SET status = :status, completed_message = :completed_message, timestamp = :timestamp WHERE exchange = :exchange';
          $params[':status'] = $escrow_status; $types[':status'] = SQLITE3_TEXT;

          if (empty($completed_message)) {
            $params[':completed_message'] = null; $types[':completed_message'] = SQLITE3_NULL;
          } else {
            $params[':completed_message'] = $completed_message; $types[':completed_message'] = SQLITE3_TEXT;
          }

          $params[':completed_message'] = $completed_message; $types[':completed_message'] = SQLITE3_TEXT;
          $params[':timestamp'] = date("Y-m-d H:i:s"); $types[':timestamp'] = SQLITE3_TEXT;
          $params[':exchange'] = $database_exchange['exchange']; $types[':exchange'] = SQLITE3_TEXT;
          $change_escrow_status_statement = $this->execute_query($change_escrow_status_statement, $params, $types, false);

          if ($change_escrow_status_statement) {
            error_or_success_popup("success", "The status of the exchange has been successfully changed.", false);
          }
        } else {
          error_or_success_popup("error", "Your message must be between 0 and 600 characters long.", "?location=control_panel");
        }
      } else {
        error_or_success_popup("error", "Please select an appropriate status.", false);
      }

    }
  }

  public function enable_or_disable_add_escrow_case($token)
  {
    if (isset($_POST["enable_or_disable_add_escrow_case"]) && sha1(session_id()) === $token) {
      $add_escrow_case = (string) filter_input(INPUT_POST, "add_escrow_case");
      $this->enable_or_disable_add_escrow_case_gears($add_escrow_case);
    }

  }

  private function enable_or_disable_add_escrow_case_gears($add_escrow_case)
  {
    if (in_array($add_escrow_case, array("enabled", "disabled"))) {
      $enable_or_disable_add_escrow_case_statement = 'UPDATE site SET add_escrow_case = :add_escrow_case';
      $params[':add_escrow_case'] = $add_escrow_case; $types[':add_escrow_case'] = SQLITE3_TEXT;
      $enable_or_disable_add_escrow_case_statement = $this->execute_query($enable_or_disable_add_escrow_case_statement, $params, $types, false);

      if ($enable_or_disable_add_escrow_case_statement) {
        error_or_success_popup("success", "The status of Escrow Request page has been successfully changed.", false);
      }
    } else {
      error_or_success_popup("error", "Please select a correct status.", false);
    }

  }

  public function modify_cryptos_fees($token)
  {
    if (isset($_POST["modify_cryptos_fees"]) && sha1(session_id()) === $token) {
      $new_bitcoin_fee = (string) filter_input(INPUT_POST, "bitcoin_fee");
      $new_monero_fee = (string) filter_input(INPUT_POST, "monero_fee");
      $this->modify_cryptos_fees_gears($new_bitcoin_fee, $new_monero_fee);
    }

  }

  private function modify_cryptos_fees_gears($new_bitcoin_fee, $new_monero_fee)
  {

    global $monero_fee, $bitcoin_fee;

    $set_clause = array();
    $params = array();

    if (validate_numeric_value($new_bitcoin_fee, 1, 100000) && $new_bitcoin_fee != $bitcoin_fee) {

      $command = "electrum setconfig fee_per_kb $new_bitcoin_fee 2>&1";

      $output = shell_exec($command);

      if (trim($output) == "true") {
        $set_clause[] = 'bitcoin_fee = :new_bitcoin_fee';
        $params[":new_bitcoin_fee"] = $new_bitcoin_fee;
      } else {
        error_or_success_popup("error", "The modification for Bitcoin fees failed or did not return 'true'. If a parallel Monero modification was to take place, it may also have failed.", false);
      }
    }

    if (validate_numeric_value($new_monero_fee, 0, 3) && $new_monero_fee != $monero_fee) {

      $set_clause[] = 'monero_fee = :new_monero_fee';
      $params[":new_monero_fee"] = $new_monero_fee;

    }


    if (empty($set_clause)) {
      error_or_success_popup("error", "Please change at least one value.", false);
    }

    $set_clause_str = implode(", ", $set_clause);

    $modify_cryptos_fees_statement = "UPDATE site SET $set_clause_str";

    $modify_cryptos_fees_statement = $this->execute_query($modify_cryptos_fees_statement, $params);

    if ($modify_cryptos_fees_statement) {
      error_or_success_popup("success", "The site crypto fees have been successfully changed.", false);
    }

  }


  public function show_logs()
  {
    $this->show_logs_gears();
  }

  private function show_logs_gears()
  {

    $show_logs_statement = 'SELECT * FROM logs ORDER BY log_timestamp DESC';
    $params = $types = [];
    $show_logs_statement = $this->execute_query($show_logs_statement, $params, $types, false);

    if ($show_logs_statement) {
      while ($logs_array = $show_logs_statement->fetchArray(SQLITE3_ASSOC)) {
        if ($logs_array['log_type'] == "fatal") {
          echo '<div class="log-message log-fatal">[' . encode_html(convert_date($logs_array['log_timestamp'])) . '] ' . encode_html($logs_array['log_type']) . ': ' . encode_html($logs_array['log_data']) . '</div>';
        }
        if ($logs_array['log_type'] == "error") {
          echo '<div class="log-message log-error">[' . encode_html(convert_date($logs_array['log_timestamp'])) . '] ' . encode_html($logs_array['log_type']) . ': ' . encode_html($logs_array['log_data']) . '</div>';
        }
        if ($logs_array['log_type'] == "success") {
          echo '<div class="log-message log-success">[' . encode_html(convert_date($logs_array['log_timestamp'])) . '] ' . encode_html($logs_array['log_type']) . ': ' . encode_html($logs_array['log_data']) . '</div>';
        }
      }
    }

  }

  public function logs_methods($token)
  {

    if (isset($_POST["clear_logs"]) && sha1(session_id()) === $token) {

      $this->clear_logs();

    }

    /*
    if (isset($_POST["save_logs"]) && sha1(session_id()) === $token) {

        $this->save_logs();

    }
    */

  }

  private function clear_logs()
  {
    $clear_logs_statement = 'DELETE FROM logs';
    $params = $types = [];
    $clear_logs_statement = $this->execute_query($clear_logs_statement, $params, $types, false);
    if ($clear_logs_statement) {
      error_or_success_popup("success", "Site log cleanup successfully completed.", false);
    }
  }

  /*
  private function save_logs()
  {
      $save_logs_statement = 'SELECT * FROM logs';
      $params = $types = [];
      $save_logs_statement = $this->execute_query($save_logs_statement, $params, $types, false);
      $saved_logs = fopen('saved_logs.csv', 'w');
      $column_names = array('log_id', 'log_type', 'log_data', 'log_timestamp');
      fputcsv($saved_logs, $column_names);
      while ($row = $save_logs_statement->fetchArray(SQLITE3_ASSOC)) {
          fputcsv($saved_logs, $row);
      }
      fclose($saved_logs);

      if ($save_logs_statement && file_exists('saved_logs.csv') && filesize('saved_logs.csv') > 0) {
          error_or_success_popup("success", "Log backup successfully completed.", false);
      }
  }
  */


  public function eye_of_providence($type)
  {

    switch ($type) {
      case 'exchanges':
        return $this->exchanges_stats_gears();

      case 'wallets':
        return $this->wallets_stats_gears();

      default:
        die("Type not supported.");
    }
  }

  private function exchanges_stats_gears()
  {

    $exchanges_stats_statement = 'SELECT exchange, timestamp, sum, converted_sum, cryptocurrency, deposit_address, status, completed_message FROM exchanges ORDER BY status DESC';
    $params = $types = [];
    $exchanges_stats_statement = $this->execute_query($exchanges_stats_statement, $params, $types, false);

    if ($exchanges_stats_statement) {

      while ($row = $exchanges_stats_statement->fetchArray(SQLITE3_ASSOC)) {

        $status_class = '';

        switch ($row['status']) {
          case 'disputed':
            $status_class = 'status-disputed';
            break;
          case 'closed':
            $status_class = 'status-closed';
            break;
          case 'completed':
            $status_class = 'status-completed';
            break;
          default:
            $status_class = 'status-other';
            break;
        }

        echo "<tr class='" . encode_html($status_class) . "'>";
        echo "<td><a href='?location=panel&exchange=" . encode_html($row['exchange']) . "' target='_blank'>" . encode_html($row['exchange']) . "</a></td>";
        echo "<td>" . encode_html(convert_date($row['timestamp'])) . "</td>";
        echo "<td>" . encode_html($row['sum']) . "</td>";
        echo "<td>" . encode_html($row['converted_sum']) . "</td>";
        echo "<td>" . encode_html($row['cryptocurrency']) . "</td>";
        echo "<td>" . encode_html($row['deposit_address']) . "</td>";
        echo "<td>" . encode_html($row['status']) . "</td>";
        echo "<td>" . encode_html($row['completed_message']) . "</td>";
        echo "</tr>";
      }
    }

  }

  private function wallets_stats_gears()
  {

    // Electrum Balance
    $electrum_balance_info = $this->call_electrum_rpc('getbalance');
    $electrum_total_balance = $electrum_balance_info['result']['confirmed'];
    echo "<tr><td>Electrum Total Balance</td><td></td><td>" . encode_html($electrum_total_balance) . " BTC</td></tr>";

    // Electrum Used Addresses
    $electrum_addresses = $this->call_electrum_rpc('listaddresses', ['funded' => true]);
    foreach ($electrum_addresses['result'] as $address) {
      $address_balance = $this->call_electrum_rpc('getaddressbalance', ['address' => $address]);
      echo "<tr><td>Electrum</td><td>" . encode_html($address) . "</td><td>" . encode_html($address_balance['result']['confirmed']) . " BTC</td></tr>";
    }

    // Monero Balance
    $monero_balance_info = $this->call_monero_rpc('get_balance', ['all_accounts' => true]);
    $monero_total_balance = $monero_balance_info['result']['balance'] / 1e12;
    echo "<tr><td>Monero Total Balance</td><td></td><td>" . encode_html($monero_total_balance) . " XMR</td></tr>";

    // Monero Used Addresses
    if (isset($monero_balance_info['result']['per_subaddress'])) {
      foreach ($monero_balance_info['result']['per_subaddress'] as $sub_address_info) {
        $address = $sub_address_info['address'];
        $balance = $sub_address_info['balance'] / 1e12;
        echo "<tr><td>Monero</td><td>" . encode_html($address) . "</td><td>" . encode_html($balance) . " XMR</td></tr>";
      }
    }
  }

}

?>