<?php

function AntiInjectionSQL()
{
  $injection = 'INSERT|UNION|SELECT|NULL|COUNT|FROM|LIKE|DROP|TABLE|WHERE|COUNT|COLUMN|TABLES|INFORMATION_SCHEMA|OR|UPDATE|TRUNCATE|DELETE';
  foreach ($_GET as $getSearchs) {
    $getSearch = explode(" ", $getSearchs);
    foreach ($getSearch as $k => $v) {
      if (in_array(strtoupper(trim($v)), explode('|', $injection))) {
        die();
      }
    }
  }
}


function encode_html($str)
{
  return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function decode_html($str)
{
  return html_entity_decode($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function convert_date($date)
{
  $date = DateTime::createFromFormat('Y-m-d H:i:s', $date);
  return $date->format('d-m-Y H:i:s');
}

function validate_post($post, $min_length, $max_length)
{

  if (!is_string($post) || !mb_check_encoding($post, 'UTF-8')) {
    return false;
  }

  $post = preg_replace('/\p{C}+/u', '', $post);
  $post = stripslashes($post);
  $post = trim($post);

  $length = mb_strlen($post, 'UTF-8');
  if ($length < $min_length || $length > $max_length) {
    return false;
  }

  return true;
}

function validate_numeric_string($value, $min_length, $max_length)
{
  return ctype_digit($value) && strlen($value) >= $min_length && strlen($value) <= $max_length;
}

function validate_numeric_value($value, $min_value, $max_value)
{
  return is_numeric($value) && $value >= $min_value && $value <= $max_value;
}

function convert_to_atomic_units($amount)
{
  $atomic_units = $amount * 1e12;
  $last_digit = $atomic_units % 10;
  $atomic_units = $atomic_units - $last_digit + 1;
  return $atomic_units;
}

function generate_random_exchange($length = 32)
{
  $result = '';

  for ($i = 0; $i < $length; $i++) {
    $result .= random_int(0, 9);
  }

  return $result;
}

function generate_random_token($length = 64)
{
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

  $characters_length = strlen($characters);

  $result = '';

  for ($i = 0; $i < $length; $i++) {
    $result .= $characters[rand(0, $characters_length - 1)];
  }

  return $result;
}

function is_valid_token($token, $expected_length = 64)
{
  $valid_characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

  if (strlen($token) != $expected_length) {
    return false;
  }

  for ($i = 0; $i < $expected_length; $i++) {
    if (strpos($valid_characters, $token[$i]) === false) {
      return false;
    }
  }

  return true;
}

function is_valid_cryptocurrency_address($type, $address)
{
  if ($type === 'bitcoin') {
    // (P2PKH, P2SH, Bech32)
    return preg_match('/^1[1-9A-HJ-NP-Za-km-z]{25,34}$/', $address) ||
      preg_match('/^3[1-9A-HJ-NP-Za-km-z]{25,34}$/', $address) ||
      preg_match('/^bc1[qpzry9x8gf2tvdw0s3jn54khce6mua7l]{39,59}$/', $address);
  } elseif ($type === 'monero') {
    // (Standard, Subaddress, Integrated)
    return preg_match('/^4[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]{94}$/', $address) ||
      preg_match('/^8[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]{94}$/', $address) ||
      preg_match('/^4[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]{106}$/', $address);
  } else {
    return false;
  }
}


function has_time_elapsed($date_string, $days)
{
  $date_to_check = new DateTime($date_string);
  $now = new DateTime();

  $diff = $now->diff($date_to_check)->days;

  return $diff >= $days;
}



function get_cryptocurrency_price($cryptocurrency)
{
  $url = "https://api.coingecko.com/api/v3/simple/price?ids=" . $cryptocurrency . "&vs_currencies=eur";

  $curl = curl_init();

  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

  $response = curl_exec($curl);

  curl_close($curl);

  $data = json_decode($response, true);

  return $data[$cryptocurrency]['eur'];
}

function add_percentage_and_convert($amount, $cryptocurrency)
{

  global $bitcoin_fee;

  $cryptocurrency_price = get_cryptocurrency_price($cryptocurrency);

  if ($cryptocurrency === "bitcoin") {

    $average_transaction_size = 250; // bits
    $fees_in_satoshis = ($bitcoin_fee / 1000) * $average_transaction_size; // to satoshis
    $fee_in_eur = ($fees_in_satoshis / 100000000) * $cryptocurrency_price; // satoshis to eur

    $new_amount = $amount + ($amount * 0.15) + $fee_in_eur;
  } elseif ($cryptocurrency === "monero") {
    $new_amount = $amount + ($amount * 0.06);
  } else {
    die();
  }

  $converted_amount = $new_amount / $cryptocurrency_price;

  return round($converted_amount, 8);
}


function error_or_success_popup($type, $text, $redirect)
{
  if ($type === "error") {
    $_SESSION['error_log'] = $text;
    if ($redirect !== false) {
      header("Location: $redirect#popup_error");
      die();
    } else {
      header("Location: #popup_error");
      die();
    }
  }
  if ($type === "success") {
    $_SESSION['success_log'] = $text;
    if ($redirect !== false) {
      header("Location: $redirect#popup_success");
      die();
    } else {
      header("Location: #popup_success");
      die();
    }

  }
}

function generate_pagination($total_pages, $page, $location = '', $variables = array())
{
  $range = 2;
  $query_string = '';

  if (!empty($variables)) {
    foreach ($variables as $key => $value) {
      if (!empty($value)) {
        $query_string .= urlencode($key) . '=' . urlencode($value) . '&';
      }
    }
  }

  echo '<div class="data-pagination-container">';
  echo '<nav data-pagination>';
  echo '<a href="' . (encode_html($location) ? '?location=' . encode_html($location) . '&' : '?') . encode_html($query_string) . 'page=' . ($page == 0 ? 0 : $page - 1) . '" ' . ($page <= 0 ? 'disabled' : '') . '><img src="design/img/left.png"></i></a>';
  echo '<ul>';
  echo '<li' . ($page == 0 ? ' class="current"' : '') . '><a href="' . (encode_html($location) ? '?location=' . encode_html($location) . '&' : '?') . encode_html($query_string) . 'page=0">0</a></li>';
  if ($page - $range > 2) {
    echo '<li><a href="#">...</a></li>';
  }
  for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++) {
    if ($i == $page) {
      echo '<li class="current"><a href="#">' . $i . '</a></li>';
    } else {
      echo '<li><a href="' . (encode_html($location) ? '?location=' . encode_html($location) . '&' : '?') . encode_html($query_string) . 'page=' . $i . '">' . $i . '</a></li>';
    }
  }
  if ($page + $range < $total_pages - 1) {
    echo '<li><a href="#">...</a></li>';
    echo '<li><a href="' . (encode_html($location) ? '?location=' . encode_html($location) . '&' : '?') . encode_html($query_string) . 'page=' . $total_pages . '">' . $total_pages . '</a></li>';
  }
  echo '</ul>';
  echo '<a href="' . (encode_html($location) ? '?location=' . encode_html($location) . '&' : '?') . encode_html($query_string) . 'page=' . ($page == $total_pages ? $page : $page + 1) . '" ' . ($page == $total_pages ? 'disabled' : '') . '><img src="design/img/right.png"></i></a>';
  echo '</nav>';
  echo '</div>';
}



?>