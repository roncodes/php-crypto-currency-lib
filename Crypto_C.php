<?php defined('BASEPATH') OR exit('No direct script access allowed');

/*******************************************************************************
 * Codeigniter Crypto Currency Library
 * by Ronald A. Richardson
 * www.ronaldarichardson.com
 * Licensed under WTFPL
 *
 *            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
 *                   Version 2, December 2004
 *
 * Copyright (C) 2004 Sam Hocevar <sam@hocevar.net>
 *
 * Everyone is permitted to copy and distribute verbatim or modified
 * copies of this license document, and changing it is allowed as long
 * as the name is changed.
 *
 *           DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
 * TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION
 *
 * 0. You just DO WHAT THE FUCK YOU WANT TO.
 *******************************************************************************/

class Crypto_C {

	private $mtgox_api;
	private $cryptsy_api;
	private $dogecharge_api;

	public function __construct() {
	    $this->mtgox_api = 'http://data.mtgox.com/api/1/';
	    $this->cryptsy_api = 'http://pubapi.cryptsy.com/api.php';
	    $this->dogecharge_api = 'https://api.dogecharge.com/v1/';
	}

	public function __call($method, $args) {
		if($method == 'get' || $method == 'post') {
			return $this->_request($method, $args[0], (isset($args[1])) ? $args[1] : array());
		}
		return null;
	}

	public function _request($method = 'post', $path = '', $query = array()) {
		$response = new stdClass;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $path . (($query && $method == 'get') ? '?' . http_build_query($query, NULL, '&') : ''));
		if($method == 'post') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query, NULL, '&'));
		}
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$response = curl_exec($ch);
		$response = (json_decode($response)) ? json_decode($response) : $response;
		if(is_object($response)) {
			$response->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		}
		curl_close ($ch);
		return $response;
	}

	public function create_dogecoin_pay_address($yourwallet = null, $timeout = 10) {
		if($yourwallet === null) return;
		return trim($this->get($this->dogecharge_api . "redirecting/create?out=$yourwallet&hours=$timeout"));
	}

	public function check_for_dogecoin_payment($address = null) {
		if($address === null) return;
		return $this->get($this->dogecharge_api . "redirecting/check?in=$address");
	}

	public function single_bitcoin_price_in_usd() {
		$data = $this->get($this->mtgox_api . 'BTCUSD/ticker_fast');
		return $data->return->buy->value;
	}

	public function single_doge_price_in_btc() {
		$data = $this->get($this->cryptsy_api . '?method=singlemarketdata&marketid=132');
		return $data->return->markets->DOGE->lasttradeprice;
	}

	public function usd_to_dogecoin($usd = 1) {
		return ($usd / $this->single_doge_price_in_btc()) / $this->single_bitcoin_price_in_usd();
	}

	public function usd_to_bitcoin($usd = 1) {
		return ($usd / $this->single_bitcoin_price_in_usd());
	}

	public function btc_to_usd($btc = 1) {
		return $btc * $this->single_bitcoin_price_in_usd();
	}

	public function btc_to_dogecoin($btc = 1) {
		return $btc / $this->single_doge_price_in_btc();
	}

	public function dogecoin_to_bitcoin($doge = 1) {
		return $doge * $this->single_doge_price_in_btc();
	}

	public function dogecoin_to_usd($doge = 1) {
		return ($doge * $this->single_doge_price_in_btc()) * $this->single_bitcoin_price_in_usd();
	}

	public function to_pennies($input) {
	  return (int) preg_replace("/[^0-9]/", "", $input);
	}

	public function conversion_test() {
		
		echo '<pre>';
		echo '<strong>$20 USD to BTC:</strong><br>';
		echo $this->usd_to_bitcoin(20).' BTC<br><br>';
		echo '<strong>$20 USD to DOGECOIN:</strong><br>';
		echo $this->usd_to_dogecoin(20).' DOGE<br><br>';
		echo '<strong>5 BTC to DOGECOIN:</strong><br>';
		echo $this->btc_to_dogecoin(5).' DOGE<br><br>';
		echo '<strong>5 BTC to USD:</strong><br>';
		echo '$'.$this->btc_to_usd(5).'<br><br>';
		echo '<strong>1500 DOGE to BTC:</strong><br>';
		echo $this->dogecoin_to_bitcoin(1500).' BTC<br><br>';
		echo '<strong>1500 DOGE to USD:</strong><br>';
		echo '$'.$this->dogecoin_to_usd(1500).'<br><br>';
		echo '</pre>';
	}

}