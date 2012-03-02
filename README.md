Handelsbanken API wrapper
===

Basic usage:

	<?php
	include('handelsbanken.php');
	
	$handelsbanken = new Handelsbanken();
	$auth = $handelsbanken->login($username, $pin);
	?>

The variable `$auth` will then be an object with the property `token` and `cookie` which are used to communicate with the API. Like fetching your accounts...

	$accounts = $handelsbanken->get_accounts($auth);

Or listing transactions for a specific account…

	$transactions = $handelsbanken->get_transactions($auth, $id);

Or why not transferring money between your accounts?

	$handelsbanken->transfer($auth, $amount, $from_account, $to_account, $annotation, $message);

Available methods
---
 - login(`$username`, `$pin`)
 - logout(`$auth`)
 - get_accounts(`$auth`)
 - get_transactions(`$auth`, `$id`)
 - get_transfer(`$auth`)
 - transfer(`$auth`, `$amount`, `$account_from`, `$account_to`, `$annotation`, `$message`)
 - get_interests()
 - more to come...


Kudos
---

Big thanks to Björn Sållarp ([@bjornsallarp][]) for making this possible through his research: http://blog.sallarp.com/handelsbanken-api.html

[@bjornsallarp]: http://twitter.com/bjornsallarp "Björn Sållarp"