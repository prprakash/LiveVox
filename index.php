<?php
include 'Livevox.php';

$clientName = '';
$username = '';
$password = '';

$lv = new Livevox($clientName, $username, $password);
$callCenters = $lv->getCallCenters();
print_r($callCenters);
?>
