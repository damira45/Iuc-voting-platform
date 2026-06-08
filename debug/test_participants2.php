<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'admin';
$_SESSION['user_name'] = 'Admin';

require_once 'config/config.php';
require_once 'pages/participants.php';
