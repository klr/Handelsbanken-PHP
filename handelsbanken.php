<?php
class Handelsbanken
{
	/**
	 * URL
	 */
	public $url = 'https://m.handelsbanken.se/app//';
	
	/**
	 * Call
	 *
	 * @param string	GET or POST
	 * @param string
	 * @param array		Optional.
	 * @param array		Optional.
	 * @return object
	 */
	public function call($type, $method, $params = array(), $additional_headers = array()){
		$headers = array(
			'User-Agent: Mobilbank/1.2 CFNetwork/548.0.3 Darwin/11.0.0',
			'X-SHB-DEVICE-MODEL: IOS-5.0,1.2,iPhone3.1',
			'X-SHB-DEVICE-CLASS: APP',
			'X-SHB-APP-VERSION: 1.0'
		);
		
		if($type == 'POST'){
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
		}
		
		foreach($additional_headers as $header){
			$headers[] = $header;
		}
		
		$query_string = '';
		
		if($type == 'GET' && count($params) !== 0){
			$query_string = '?' . http_build_query($params);
		}
		
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $this->url . $method . $query_string);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
		
		if($type == 'POST'){
			curl_setopt($curl, CURLOPT_POST, 1);
			
			if(count($params) !== 0){
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
			}
		}
		
		$data = curl_exec($curl);
		
		if(curl_errno($curl)){
			throw new Exception('Curl error: ' . curl_error($curl)); 
		} 
		
		curl_close($curl); 
		
		list($header, $body) = explode("\r\n\r\n", $data, 2);
		return array('header' => $header, 'xml' => simplexml_load_string($body));
	}
	
	/**
	 * Login
	 *
	 * @param string
	 * @param string
	 * @return object|bool
	 */
	public function login($username, $pin){
		extract($this->call('POST', 'login', array(
			'username' => $username,
			'pin' => $pin,
			'deviceid' => 'f8280cf34708c7b5a8bd2ed93dcd3c814800000'
		)));
		
		$header = explode('Set-Cookie: ', $header);
		$header = explode(';', $header[1]);
		$cookie = $header[0];
		
		if(isset($xml->authToken)){
			return (object) array(
				'token' => (string) $xml->authToken,
				'cookie' => $cookie
			);
		}
		
		return false;
	}
	
	/**
	 * Logout
	 *
	 * @param object
	 * @return bool
	 */
	public function logout($auth){
		extract($this->call('GET', 'logout', array(
			'authToken' => $auth->token
		), array(
			'Cookie: ' . $auth->cookie
		)));
		
		return true;
	}
	
	/**
	 * Get accounts
	 *
	 * @param object
	 * @return array
	 */
	public function get_accounts($auth){
		extract($this->call('GET', 'accounts', array(
			'authToken' => $auth->token
		), array(
			'Cookie: ' . $auth->cookie
		)));
		
		$accounts = array();
		
		foreach($xml->accounts->account as $account){
			$accounts[] = (object) array(
				'id' => (int) $account->accountId,
				'name' => utf8_decode($account->accountName),
				'number' => (int) $account->accountNumber,
				'number_modified' => (string) $account->accountNumberModified,
				'type' => (int) $account->type,
				'amount' => (double) $account->accountAmount,
				'balance' => (double) $account->accountBalance
			);
		}
		
		return $accounts;
	}
	
	/**
	 * Get transactions
	 *
	 * @param string
	 * @param string
	 * @param integer
	 */
	public function get_transactions($auth, $id){
		extract($this->call('GET', 'transactions', array(
			'authToken' => $auth->token,
			'type' => 1,
			'account' => $id,
			'accountType' => 1
		), array(
			'Cookie: ' . $auth->cookie
		)));
		
		$transactions = array();
		
		foreach($xml->transactions->transaction as $transaction){
			$transactions[] = (object) array(
				'date' => (string) $transaction->transactionDate,
				'timestamp' => strtotime($transaction->transactionDate),
				'amount' => (float) str_replace(' ', '', $transaction->transactionAmount),
				'description' => utf8_decode($transaction->transactionDescription),
				'type' => (int) $transaction->transactionType
			);
		}
		
		return $transactions;
	}
	
	/**
	 * Get transfer
	 *
	 * @param object
	 * @return array
	 */
	public function get_transfer($auth){
		extract($this->call('GET', 'transfer', array(
			'authToken' => $auth->token
		), array(
			'Cookie: ' . $auth->cookie
		)));
		
		$accounts = (object) array('from' => array(), 'to' => array());
		
		foreach($xml->fromAccounts->accounts->account as $account){
			$accounts->from[] = (object) array(
				'id' => (int) $account->accountId,
				'name' => utf8_decode($account->accountName),
				'number' => (int) $account->accountNumber,
				'number_modified' => (string) $account->accountNumberModified,
				'type' => (int) $account->typeAccount,
				'amount' => (float) str_replace(' ', '', $account->accountAmount)
			);
		}
		
		foreach($xml->toAccounts->accounts->account as $account){
			$accounts->to[] = (object) array(
				'id' => (int) $account->accountId,
				'name' => utf8_decode($account->accountName),
				'number' => (int) $account->accountNumber,
				'number_modified' => (string) $account->accountNumberModified,
				'type' => (int) $account->typeAccount,
				'amount' => null
			);
		}
		
		return $accounts;
	}
	
	/**
	 * Transfer
	 *
	 * @param object
	 * @param float
	 * @param integer
	 * @param integer
	 * @param string
	 * @param string
	 * @return bool
	 */
	public function transfer($auth, $amount, $account_from, $account_to, $annotation, $message){
		extract($this->call('POST', 'transfer-status', array(
			'authToken' => $auth->token,
			'annotation' => $annotation,
			'accountFromNr' => $account_from,
			'message' => $message,
			'amount' => $amount,
			'accountToNr' => $account_to
		), array(
			'Cookie: ' . $auth->cookie
		)));
		
		if(isset($xml->commit)){
			extract($this->call('GET', 'transfer-result', array(
				'authToken' => $auth->token
			), array(
				'Cookie: ' . $auth->cookie
			)));
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get interests
	 *
	 * @return array
	 */
	public function get_interests(){
		extract($this->call('GET', 'interests'));
		
		$rates = array();
		
		foreach($xml->rates->rate as $rate){
			$rates[] = (object) array(
				'period' => utf8_decode($rate->attributes()->period),
				'value' => (string) $rate->attributes()->value
			);
		}
		
		return $rates;
	}
}