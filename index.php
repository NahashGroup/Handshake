<?php

require('config/config.php');
require('config/functions.php');
require('config/class.php');

AntiInjectionSQL();

$everyone = new everyone($db);

if (isset($_SESSION['auth_token'])) {
  $user = new user($db);
  if (isset($_SESSION['role']) && $_SESSION['role'] === "admin") {
    $control = new control($db);
  }
}

$location = $_GET["location"] ?? "index";

$locations = array(
  "index" => "Home",
  "add_escrow" => "Escrow Request",
  "login" => "Escrow Access",
  "panel" => "Escrow Panel",
  "control_panel" => "Control Panel",
  "logs" => "Logs",
  "eye_of_providence" => "Statistics",
  "disconnect" => "Disconnect"
);


if (array_key_exists($location, $locations)) {

  $page_name = $locations[$location];

  require('header.php');

  $token = (string) filter_input(INPUT_POST, "token");

  if ($location === "index") {
    require('gears/place.php');
  }
  if ($location === "add_escrow") {
    if (!isset($_SESSION['auth_token'])) {
      $everyone->add_escrow($token);
      require('gears/add_escrow.php');
    } else {
      header("Location: ?location=index");
    }
  }
  if ($location === "login") {
    if (!isset($_SESSION['auth_token'])) {
      $everyone->login($token);
      require('gears/login.php');
    } else {
      header("Location: ?location=index");
    }
  }
  if ($location === "panel") {
    if ((isset($_SESSION['exchange']) && isset($_GET["exchange"])) && $_SESSION['exchange'] === $_GET["exchange"] || $_SESSION['role'] === "admin") {
      if ($exchange_data = $everyone->get_exchange_data($_GET["exchange"])) {
        require('gears/panel.php');
      }
    } else {
      error_or_success_popup("error", "You do not have access to this exchange.", "?location=index");
    }
  }
  if ($location === "control_panel") {
    if (isset($_SESSION['role']) && $_SESSION['role'] === "admin") {
      $control->modify_site_data($token);
      $control->check_and_update_exchanges($token);
      $control->close_escrow($token);
      $control->complete_escrow($token);
      $control->change_escrow_status($token);
      $control->enable_or_disable_add_escrow_case($token);
      $control->modify_cryptos_fees($token);
      require('gears/control_panel.php');
    } else {
      header("Location: ?location=index");
    }
  }
  if ($location === "logs") {
    if (isset($_SESSION['role']) && $_SESSION['role'] === "admin") {
      require('gears/logs.php');
      $control->logs_methods($token);
    } else {
      header("Location: ?location=index");
    }
  }
  if ($location === "eye_of_providence") {
    if (isset($_SESSION['role']) && $_SESSION['role'] === "admin") {
      require('gears/eye_of_providence.php');
    } else {
      header("Location: ?location=index");
    }
  }
  if ($location === "disconnect") {
    if (isset($_SESSION['auth_token'])) {
      $user->disconnect();
    } else {
      header("Location: ?location=index");
    }
  }

  require('footer.php');

} else {
  header("Location: ?location=index");
}

?>