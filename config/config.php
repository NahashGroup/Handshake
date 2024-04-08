<?php

date_default_timezone_set('Europe/Paris');
session_set_cookie_params(10800);
session_start();

#Electrum 

$electrum_rpc_user = 'user';
$electrum_rpc_password = 'password';
$electrum_rpc_url = '127.0.0.1';
$electrum_rpc_port = '7777';


#Monero

$monero_rpc_user = 'user';
$monero_rpc_password = 'password';
$monero_rpc_url = '127.0.0.1';
$monero_rpc_port = '18080';

#Database

$db = new SQLite3("config/db.sqlite");

if (!isset($site_gears)) {

  $site_data = $db->prepare('SELECT name, description, keywords, add_escrow_case, bitcoin_fee, monero_fee FROM site');
  $site_data = $site_data->execute()->fetchArray(SQLITE3_ASSOC);

  $site_name = $site_data["name"];

  $site_description = $site_data["description"];

  $site_keywords = $site_data["keywords"];

  $add_escrow_case = $site_data["add_escrow_case"];

  $bitcoin_fee = $site_data["bitcoin_fee"];

  $monero_fee = $site_data["monero_fee"];

  $site_gears = true;

}

?>