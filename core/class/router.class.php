<?php

class Router {
	private $client;
	private $session;
	private $statut;
	private $login;
	private $password;
	private $ip;
	const LOGGED_IN = '0';
	const LOGGED_OUT = '-1';


	private function setLogin($l) {
		$this->login = $l;
	}
	
	private function getLogin() {
		return $this->login;
	}
	
	private function setPassword($p) {
		$this->password = $p;
	}
	
	private function getPassword() {
		return $this->password;
	}
	
	private function setIP($ip) {
		$this->ip = $ip;
	}
	
	private function getIP() {
		return $this->ip;
	}

    public function setAddress($address) {
		$this->ip = $address;
    }
	
	public function getAddress() {
		return $this->routerAddress;
	}

	public function getStatus() {
		$state = $this->getState();
		if($state['State'] == LOGGED_IN) {
			$this->statut = "Up";
		} else {
			$this->statut = "Down";
		}
		
		return $this->statut;
	}

	/*
	Functions for sessions
	*/

	public function setSession($login, $pwd) {
		$this->setLogin($login);
		$this->setPassword($pwd);
	}
	
	// get the info
	private function getInfoPython($api) {
		$command = dirname(__FILE__) . '/../../resources/scripts/poller.py '.$this->getIP().' '.$this->getLogin().' '.$this->getPassword().' '.$api;
		try{
			$json = shell_exec('python3 '.$command);
		} catch (Exception $e){
			log::add('huawei4g', 'debug', $e);
		}
		log::add('huawei4g', 'debug', $json);
		return json_decode($json, true);		
	}
	
	// SMS
	private function setSMSPython($tel, $msg) {
		$command = dirname(__FILE__) . '/../../resources/scripts/sender.py '.$this->getIP().' '.$this->getLogin().' '.$this->getPassword().' '.$msg.' '.$tel;
		try{
			$json = shell_exec('python3 '.$command);
		} catch (Exception $e){
			log::add('huawei4g', 'debug', $e);
		}
		log::add('huawei4g', 'debug', $json);
		return json_decode($json, true);		
	}
	
		
	/*
	Functions w/o login needed
	*/
	public function getTrafficStatistics() {
		return $this->getInfoPython('api/monitoring/traffic-statistics');
	}
	
	public function getPublicLandMobileNetwork() {
		return $this->getInfoPython('api/net/current-plmn');
	}
	
	public function getDeviceBasicInfo() {
		return $this->getInfoPython('api/device/basic_information');
	}

	/*
	Functions w/ login needed
	*/
	public function getCellInfo() {
		return $this->getInfoPython('api/net/cell-info');
	}
	
	public function getSignal() {
		return $this->getInfoPython('api/device/signal');
	}
	
	public function getSMS() {
		return $this->getInfoPython('api/sms/sms-count');
	}
	
	public function setReboot() {
		return $this->getInfoPython('api/device/control');
	}
	
	public function getState() {
		return $this->getInfoPython('api/user/state-login');
	}
	
	public function sendSMS($phone, $message) {
		return $this->setSMSPython($phone, $message);
	}

}
?>