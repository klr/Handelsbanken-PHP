<?php
include('handelsbanken.php');

$handelsbanken = new Handelsbanken();

try{
	$auth = $handelsbanken->login('', '');
	$accounts = $handelsbanken->get_accounts($auth);
	
	$id = $accounts[0]->id;
	
	$transactions = $handelsbanken->get_transactions($auth, $id);
	
	echo '<pre>' . print_r($transactions, true) . '</pre>';
}
catch(Exception $e){
	echo '<h1>Exception: ' . $e->getMessage() . '</h1>';
}