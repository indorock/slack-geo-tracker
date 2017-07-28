<?php

require("./vendor/autoload.php");
require('./lib/xpath.query.php');
require("./lib/class.slackconnector.php");

if(!isset($_POST['token']))
    die();

$token = $POST['token'];
$sc = new SlackConnector();

