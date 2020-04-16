<?php

class Router {
	private $client;
	private $session;
	private $statut;
	private $login;
	private $password;
	private $ip;
	private $output;
	private $outputSMS;
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
	
	private function encodeToUtf8($string) {
		return mb_convert_encoding($string, "UTF-8", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	}

	
	public function getStatus() {
		$state = $this->getState();
		log::add('huawei4g', 'debug', 'State: '.$state['State']);
		
		if(empty($state['State'])) {
			if(intval($state['State']) == self::LOGGED_IN) {
				$this->statut = "Up";
				log::add('huawei4g', 'debug', 'Up');
			} else {
				$this->statut = "Down";
				log::add('huawei4g', 'debug', 'Down - no data');
			}
		} else {
			$this->statut = "Down";
			log::add('huawei4g', 'debug', 'Down');
		}
		
		return $this->statut;
	}


	/*
	Functions for sessions
	*/

	public function setSession($login, $pwd, $action) {
		$this->setLogin($login);
		$this->setPassword($pwd);
		
		switch($action) {
			case "get":
				$this->setInfo($this->getInfoPython());
				break;
			
			case "sms":
				$this->setInfoSMS($this->getSMSPython());
				break;
				
			default:
				break;
		}
		
	}
	
	private function setInfo($out) {
		log::add('huawei4g', 'debug', 'PreOutput: '.$out);
		
		// removing Python bracket list
		$tmp = substr(trim($out), 2, -2);
		// splitting json outputs
		$this->output = explode('}\', \'{', $tmp);
		log::add('huawei4g', 'debug', 'PostOutput: '.$this->output);
		foreach($this->output as $key => $value) {
			if($value[0] != '{') {
				$this->output[$key] = substr_replace($value,'{',0,0);
			}
			if(substr($this->output[$key], -1) != '}') {
				$this->output[$key] = $this->output[$key].'}';
			}
						
			$this->output[$key] = str_replace("\\'", "'", $this->output[$key]);
			$this->output[$key] = str_replace(array("\r\n", "\n", "\r"), "", $this->output[$key]);
			log::add('huawei4g', 'debug', $key.': '.$this->output[$key]);
			$this->output[$key] = json_decode($this->output[$key], true);
			
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					log::add('huawei4g', 'debug', ' - Aucune erreur');
				break;
				case JSON_ERROR_DEPTH:
					log::add('huawei4g', 'debug', ' - Profondeur maximale atteinte');
				break;
				case JSON_ERROR_STATE_MISMATCH:
					log::add('huawei4g', 'debug', ' - Inadéquation des modes ou underflow');
				break;
				case JSON_ERROR_CTRL_CHAR:
					log::add('huawei4g', 'debug', ' - Erreur lors du contrôle des caractères');
				break;
				case JSON_ERROR_SYNTAX:
					log::add('huawei4g', 'debug', ' - Erreur de syntaxe ; JSON malformé');
				break;
				case JSON_ERROR_UTF8:
					log::add('huawei4g', 'debug', ' - Caractères UTF-8 malformés, probablement une erreur d\'encodage');
				break;
				default:
					log::add('huawei4g', 'debug', ' - Erreur inconnue');
				break;
			}
		}
	}
	
	private function setInfoSMS($out) {
		log::add('huawei4g', 'debug', 'PreOutputSMS: '.$out);
		
		// removing Python bracket list
		$tmp = substr(trim($out), 2, -2);
		// splitting json outputs
		$this->outputSMS = explode('}\', \'{', $tmp);
		log::add('huawei4g', 'debug', 'PostOutputSMS: '.$this->outputSMS);
		foreach($this->outputSMS as $key => $value) {
			if($value[0] != '{') {
				$this->outputSMS[$key] = substr_replace($value,'{',0,0);
			}
			if(substr($this->outputSMS[$key], -1) != '}') {
				$this->outputSMS[$key] = $this->outputSMS[$key].'}';
			}
						
			$this->outputSMS[$key] = str_replace("\\'", "'", $this->outputSMS[$key]);
			$this->outputSMS[$key] = str_replace(array("\r\n", "\n", "\r"), "", $this->outputSMS[$key]);
			log::add('huawei4g', 'debug', $key.': '.$this->outputSMS[$key]);
			$this->outputSMS[$key] = json_decode($this->outputSMS[$key], true);
			
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					log::add('huawei4g', 'debug', ' - Aucune erreur');
				break;
				case JSON_ERROR_DEPTH:
					log::add('huawei4g', 'debug', ' - Profondeur maximale atteinte');
				break;
				case JSON_ERROR_STATE_MISMATCH:
					log::add('huawei4g', 'debug', ' - Inadéquation des modes ou underflow');
				break;
				case JSON_ERROR_CTRL_CHAR:
					log::add('huawei4g', 'debug', ' - Erreur lors du contrôle des caractères');
				break;
				case JSON_ERROR_SYNTAX:
					log::add('huawei4g', 'debug', ' - Erreur de syntaxe ; JSON malformé');
				break;
				case JSON_ERROR_UTF8:
					log::add('huawei4g', 'debug', ' - Caractères UTF-8 malformés, probablement une erreur d\'encodage');
				break;
				default:
					log::add('huawei4g', 'debug', ' - Erreur inconnue');
				break;
			}
		}
	}
	
	// get the info
	private function getInfoPython() {
		$command = dirname(__FILE__) . '/../../resources/scripts/poller.py '.$this->getIP().' '.$this->getLogin().' '.$this->getPassword();
		try{
			$json = shell_exec('python3 '.$command);
		} catch (Exception $e){
			log::add('huawei4g', 'debug', $e);
		}
		log::add('huawei4g', 'debug', $json);
		return $json;		
	}
	
	// SMS
	private function setSMSPython($tel, $msg) {
		$escapedArg = "'".str_replace("'", "'\\''", $msg)."'";
		$command = dirname(__FILE__) . '/../../resources/scripts/sender.py '.$this->getIP().' '.$this->getLogin().' '.$this->getPassword().' '.$tel.' '.$this->encodeToUtf8($escapedArg);
		try{
			$json = shell_exec('python3 '.$command);
		} catch (Exception $e){
			log::add('huawei4g', 'debug', $e);
		}
		log::add('huawei4g', 'debug', $json);
		return json_decode($json, true);		
	}
	
	private function getSMSPython() {
		$command = dirname(__FILE__) . '/../../resources/scripts/getsms.py '.$this->getIP().' '.$this->getLogin().' '.$this->getPassword();
		try{
			$json = shell_exec('python3 '.$command);
		} catch (Exception $e){
			log::add('huawei4g', 'debug', $e);
		}
		log::add('huawei4g', 'debug', $json);
		return $json;		
	}
	
	private function delSMSPython($ind) {
		$command = dirname(__FILE__) . '/../../resources/scripts/delsms.py '.$this->getIP().' '.$this->getLogin().' '.$this->getPassword().' '.$ind;
		try{
			$json = shell_exec('python3 '.$command);
		} catch (Exception $e){
			log::add('huawei4g', 'debug', $e);
		}
		log::add('huawei4g', 'debug', $json);
		return json_decode($json, true);		
	}
	
	// Reboot
	private function reboot() {
		$command = dirname(__FILE__) . '/../../resources/scripts/reboot.py '.$this->getIP().' '.$this->getLogin().' '.$this->getPassword();
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
		return $this->output[2];
	}
	
	public function getPublicLandMobileNetwork() {
		return $this->output[3];
	}
	
	public function getDeviceBasicInfo() {
		return $this->output[4];
	}

	/*
	Functions w/ login needed
	*/
	public function getCellInfo() {
		return $this->output[5];
	}
	
	public function getSignal() {
		return $this->output[6];
	}
	
	public function getMonthStats() {
		return $this->output[7];
	}
	
	public function getWifiInfo() {
		$tmp = $this->output[8];
		$return = array();
		log::add('huawei4g', 'debug', "Cleaning WiFi Radio");
		foreach($tmp as $key => $value) {
			log::add('huawei4g', 'debug', "Radio ".$tmp[$key]->ID);
			if(isset($tmp[$key]->ID)) {
				if($tmp[$key]->ID == "InternetGatewayDevice.X_Config.Wifi.Radio.1"){
					$return["Radio 2.4 GHz"] = $tmp[$key]->wifienable;
				}
				if($tmp[$key]->ID == "InternetGatewayDevice.X_Config.Wifi.Radio.2"){
					$return["Radio 5 GHz"] = $tmp[$key]->wifienable;
				}
			}
		}
		return $return;
	}
	
	public function getWifiDetails() {
		return $this->output[9];
	}
	
	public function getSMSCount() {
		return $this->outputSMS[2];
	}
	
	/* toujours garder en dernier dans le tableau */
	public function getSMS() {
		return $this->outputSMS[3];
	}
	
	public function setReboot() {
		return $this->reboot();
	}
	
	public function getState() {
		if(empty($this->outputSMS[1])) {
			return $this->output[1];
		} else {
			return $this->outputSMS[1];
		}
	}
	
	public function sendSMS($phone, $message) {
		return $this->setSMSPython($phone, $message);
	}
	
	public function delSMS($index) {
		return $this->delSMSPython($index);
	}
}
?>