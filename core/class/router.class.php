<?php

class Router {
	private $client;
	private $session;
	private $statut;
	private $login;
	private $password;
	private $ip;
	private $output;
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
		log::add('huawei4g', 'debug', 'State: '.$state['State']);
		if(intval($state['State']) == self::LOGGED_IN) {
			$this->statut = "Up";
			log::add('huawei4g', 'debug', 'Up');
		} else {
			$this->statut = "Down";
			log::add('huawei4g', 'debug', 'Down');
		}

		return $this->statut;
	}


	/*
	Functions for sessions
	*/

	public function setSession($login, $pwd) {
		$this->setLogin($login);
		$this->setPassword($pwd);
		$out = $this->getInfoPython();
		log::add('huawei4g', 'debug', 'PreOutput: '.$this->output);
		
		// removing Python bracket list
		$tmp = substr(trim($out), 2, -2);
		// splitting json outputs
		$this->output = explode('}\', \'{', $tmp);
		log::add('huawei4g', 'debug', 'PostOutput:');
		foreach($this->output as $key => $value) {
			if($value[0] != '{') {
				$this->output[$key] = substr_replace($value,'{',0,0);
			}
			if(substr($this->output[$key], -1) != '}') {
				$this->output[$key] = $this->output[$key].'}';
			}
						
			$this->output[$key] = str_replace("\\'", "'", $this->output[$key]);
			$this->output[$key] = preg_replace( "/\r|\n/", "", $this->output[$key]);
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
		$command = dirname(__FILE__) . '/../../resources/scripts/sender.py '.$this->getIP().' '.$this->getLogin().' '.$this->getPassword().' '.$tel.' '.escapeshellarg($msg);
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
		return $this->output[1];
	}
	
	public function getPublicLandMobileNetwork() {
		return $this->output[2];
	}
	
	public function getDeviceBasicInfo() {
		return $this->output[3];
	}

	/*
	Functions w/ login needed
	*/
	public function getCellInfo() {
		return $this->output[4];
	}
	
	public function getSignal() {
		return $this->output[5];
	}
	
	public function getSMS() {
		return $this->output[6];
	}
	
	public function setReboot() {
		return $this->reboot();
	}
	
	public function getState() {
		return $this->output[0];
	}
	
	public function sendSMS($phone, $message) {
		return $this->setSMSPython($phone, $message);
	}

}
?>