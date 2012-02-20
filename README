Handelsbanken API wrapper
===

Basic usage:

	<?php
	include('handelsbanken.php');
	
	$handelsbanken = new Handelsbanken();
	$auth = $handelsbanken->login($username, $pin);
	?>

The variable `$auth` will then be an object with the property `token` and `cookie` which are used to communicate with the API. Like fetching your accounts...

	$accounts = $handelsbanken->get_accounts($auth->token, $auth->cookie);

Or listing transactions for a specific account…

	$transactions = $handelsbanken->get_transactions($auth->token, $auth->cookie, $id);

Available methods
---
 - login(`$username`, `$pin`)
 - get_accounts(`$token`, `$cookie`)
 - get_transactions(`$token`, `$cookie`, `$id`)
 - interests()
 - more to come...


Kudos
---

Big thanks to Björn Sållarp ([@bjornsallarp][]) for making this possible through his research: http://blog.sallarp.com/handelsbanken-api.html

[@bjornsallarp]: http://twitter.com/bjornsallarp "Björn Sållarp"