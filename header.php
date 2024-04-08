<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php if (isset($site_name)) { echo encode_html($site_name); } ?> | <?php if (isset($page_name)) { echo encode_html($page_name); } ?>
    </title>
    <meta name="description" content="<?php if (isset($site_description)) { echo encode_html($site_description); } ?>">
    <meta name="keywords" content="<?php if (isset($site_keywords)) { echo encode_html($site_keywords); } echo ", " . date("Y"); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="design/img/icon.png">
    <link rel="stylesheet" href="design/style.css" />
</head>

<body>
    <div class="topnav">
        <a <?php if (isset($location) && $location === "index") { echo 'class="active"'; } ?> href="?location=index">Home <img src="design/img/home.png" height="15" width="15"></a>
        <?php if (!isset($_SESSION['auth_token'])) { ?>
            <a <?php if (isset($location) && $location === "add_escrow") { echo 'class="active"'; } ?> href="?location=add_escrow">Escrow Request <img src="design/img/add_escrow.png" height="15" width="15"></a>
            <a <?php if (isset($location) && $location === "login") { echo 'class="active"'; } ?> href="?location=login">Escrow Access <img src="design/img/access_escrow.png" height="15" width="15"></a>
        <?php } ?>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === "admin") { ?>
            <a <?php if (isset($location) && $location === "control_panel") { echo 'class="active"'; } ?> href="?location=control_panel">Control Panel <img src="design/img/control_panel.png" height="15" width="15"></a>
        <?php } ?>

        <?php if (isset($_SESSION['auth_token'])) { ?>
            <a <?php if (isset($location) && $location === "panel") { echo 'class="active"'; } ?> href="?location=panel&exchange=<?= encode_html($_SESSION['exchange']); ?>">Escrow Panel <img src="design/img/panel.png" height="15" width="15"></a>
            <a href="?location=disconnect">Disconnect <img src="design/img/disconnect.png" height="15" width="15"></a>
        <?php } ?>
    </div>
    </div>

    <div class="center">
        <h1><?php if (isset($site_name)) { echo encode_html($site_name); } ?></h1>
        <h3><?php if (isset($site_description)) { echo encode_html($site_description); } ?></h3>
        <strong><h2><?php if (isset($page_name) && isset($location) && $location !== "index") { echo encode_html($page_name); } ?></h2></strong>
    </div>