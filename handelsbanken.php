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
		
		#echo '<pre>' . print_r(curl_getinfo($curl), true) . '</pre>';
		#echo '<pre>' . print_r(htmlentities($data), true) . '</pre>';
		
		curl_close($curl); 
		
		list($header, $body) = explode("\r\n\r\n", $data, 2);
		return array('header' => $header, 'xml' => simplexml_load_string($body));
	}
	
	/**
	 * Auth
	 *
	 * @param string
	 * @param string
	 * @return string|bool
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
	 * Get accounts
	 *
	 * @param string
	 * @param string
	 * @return array
	 */
	public function get_accounts($token, $cookie){
		extract($this->call('GET', 'accounts', array(
			'authToken' => $token
		), array(
			'Cookie: ' . $cookie
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
	public function get_transactions($token, $cookie, $id){
		extract($this->call('GET', 'transactions', array(
			'authToken' => $token,
			'type' => 1,
			'account' => $id,
			'accountType' => 1
		), array(
			'Cookie: ' . $cookie
		)));
		
		$transactions = array();
		
		foreach($xml->transactions->transaction as $transaction){
			$transactions[] = (object) array(
				'date' => (string) $transaction->transactionDate,
				'timestamp' => strtotime($transaction->transactionDate),
				'description' => utf8_decode($transaction->transactionDescription),
				'type' => (int) $transaction->transactionType
			);
		}
		
		return $transactions;
	}
	
	/**
	 * Interests
	 *
	 * @return array
	 */
	public function interests(){
		$xml = $this->call('GET', 'interests');
		
		$rates = array();
		
		foreach($xml->rates->rate as $rate){
			$rates[] = array(
				'period' => (string) utf8_decode($rate->attributes()->period),
				'value' => (string) $rate->attributes()->value
			);
		}
		
		return $rates;
	}
}