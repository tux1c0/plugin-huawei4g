<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/router.class.php';

class huawei4g extends eqLogic {
    /*     * *************************Attributs****************************** */
	public static $_widgetPossibility = array('custom' => true);
	const ERROR_SYSTEM_UNKNOWN = '100001';
	const ERROR_SYSTEM_NO_SUPPORT = '100002';
	const ERROR_SYSTEM_NO_RIGHTS = '100003';
	const ERROR_SYSTEM_BUSY = '100004';
	const ERROR_SYSTEM_PARAMETER = '100006';
	const ERROR_SYSTEM_CSRF = '125002';
	
    /*     * ***********************Methode static*************************** */
	public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = jeedom::getTmpFolder('huawei4g') . '/dependance';
		if (exec(system::getCmdSudo() . system::get('cmd_check') . '-E "php\-guzzlehttp" | wc -l') >= 1) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		
		$return['state'] = 'ok';
		return $return;
	}
	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array('script' => dirname(__FILE__) . '/../../resources/install.sh ' . jeedom::getTmpFolder('huawei4g') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

	public static function update($_eqLogic_id = null) {
		if ($_eqLogic_id == null) {
			$eqLogics = eqLogic::byType('huawei4g');
		} else {
			$eqLogics = array(eqLogic::byId($_eqLogic_id));
		}
		foreach ($eqLogics as $rtr) {
			try {
				$rtr->getRouteurInfo();
			} catch (Exception $e) {
				log::add('huawei4g', 'error', $e->getMessage());
			}
		}
	}
	
	public static function cron15() {
		foreach (self::byType('huawei4g') as $rtr) {
			if ($rtr->getIsEnable() == 1) {
				$cmd = $rtr->getCmd(null, 'refresh');
				if (!is_object($cmd)) {
					continue; 
				}
				$cmd->execCmd();
			}
		}
    }
	
	public function preUpdate() {
		if ($this->getConfiguration('ip') == '') {
			throw new Exception(__('Le champs IP ne peut pas être vide', __FILE__));
		}
		if ($this->getConfiguration('username') == '') {
			throw new Exception(__("Le champs SSH Nom d'utilisateur ne peut pas être vide", __FILE__));
		}
		if ($this->getConfiguration('password') == '') {
			throw new Exception(__('Le champs SSH Mot de passe ne peut pas être vide', __FILE__));
		}
	}

	public function getRouteurInfo() {
		// getting configuration
		$IPaddress = $this->getConfiguration('ip');
		$login = $this->getConfiguration('username');
		$pwd = $this->getConfiguration('password');
		$RtrName = $this->getName();
		
		$this->infos = array();
		
		// setting the router session
		$Router = new Router();
		$Router->setAddress($IPaddress);

		try {
			$Router->setHttpSession($login, $pwd);
			$this->infos['status'] = $Router->getStatus();
			
			if($this->infos['status'] == "Up") {
				$this->setInfo($Router->getTrafficStatistics());
				$this->setInfo($Router->getPublicLandMobileNetwork());
				$this->setInfo($Router->getDeviceBasicInfo());
				$this->setInfo($Router->getCellInfo());
			}
		} catch (Exception $e) {
			log::add('huawei4g', 'error', $e);
		}
		
		
		$this->updateInfo();
	}
	
	// fill the info array
	private function setInfo($infoTab) {
		// workaround PHP < 7
		if (!function_exists('array_key_first')) {
			function array_key_first(array $arr) {
				foreach($arr as $key => $unused) {
					return $key;
				}
				return NULL;
			}
		}
		
		if(array_key_first($infoTab) == 'code') {
			log::add('huawei4g', 'error', $this->errorInfo($infoTab['code']));
		} else {
			foreach($infoTab as $key => $value) {
				log::add('huawei4g', 'debug', 'key:'.$key.' value:'.$value);
				$this->infos[$key] = $value;
			}
		}
	}
	
	// manage API errors
	private function errorInfo($code) {
		switch($code) {
			case ERROR_SYSTEM_BUSY: 
				$e = "System busy";
				break;
			case ERROR_SYSTEM_CSRF: 
				$e = "Token error";
				break;
			case ERROR_SYSTEM_NO_RIGHTS: 
				$e = "You don't have rights";
				break;
			case ERROR_SYSTEM_NO_SUPPORT: 
				$e = "API not supported";
				break;
			case ERROR_SYSTEM_PARAMETER: 
				$e = "Wrong parameter";
				break;
			case ERROR_SYSTEM_UNKNOWN: 
				$e = "Unknown API error";
				break;	
			default:
				$e = "UNKNOWN ERROR";
		}
		
		return $e;
	}
	
	// update HTML
	public function updateInfo() {
		foreach ($this->getCmd('info') as $cmd) {
			try {
				$key = $cmd->getLogicalId();
				$value = $this->infos[$key];
				$this->checkAndUpdateCmd($cmd, $value);
				log::add('huawei4g', 'debug', 'key '.$key. ' valeur '.$value);
			} catch (Exception $e) {
				log::add('huawei4g', 'error', 'Impossible de mettre à jour le champs '.$key);
			}
		}
	}

	
		/*     * *********************Methode d'instance************************* */
	public function preSave() {
		$this->setDisplay('height','800');
		//$this->setDisplay('width','200');
    }
	
	public function postSave() {
		
		$RouteurCmd = $this->getCmd(null, 'status');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'status');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Statut', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('status');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','power');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('2');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'FullName');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'FullName');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Réseau mobile', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('FullName');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','antenna');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('1');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'CurrentConnectTime');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'CurrentConnectTime');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Current Connect Time', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('CurrentConnectTime');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','time');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setUnite( 's' );
			$RouteurCmd->setOrder('3');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'CurrentUpload');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'CurrentUpload');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Current Upload', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('CurrentUpload');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','upload');
			$RouteurCmd->setSubType('numeric');
			//$RouteurCmd->setUnite( 'o' );
			$RouteurCmd->setOrder('7');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'CurrentDownload');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'CurrentDownload');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Current Download', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('CurrentDownload');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','download');
			$RouteurCmd->setSubType('numeric');
			//$RouteurCmd->setUnite( 'o' );
			$RouteurCmd->setOrder('6');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'CurrentDownloadRate');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'CurrentDownloadRate');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Current Download Rate', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('CurrentDownloadRate');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','perf');
			$RouteurCmd->setSubType('numeric');
			//$RouteurCmd->setUnite( 'ko/s' );
			$RouteurCmd->setOrder('4');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'CurrentUploadRate');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'CurrentUploadRate');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Current Upload Rate', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('CurrentUploadRate');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','perf');
			$RouteurCmd->setSubType('numeric');
			//$RouteurCmd->setUnite( 'ko/s' );
			$RouteurCmd->setOrder('5');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'TotalUpload');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'TotalUpload');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Total Upload', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('TotalUpload');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','upload');
			$RouteurCmd->setSubType('numeric');
			//$RouteurCmd->setUnite( 'o' );
			$RouteurCmd->setOrder('12');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'TotalDownload');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'TotalDownload');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Total Download', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('TotalDownload');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','download');
			$RouteurCmd->setSubType('numeric');
			//$RouteurCmd->setUnite( 'o' );
			$RouteurCmd->setOrder('11');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'TotalConnectTime');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'TotalConnectTime');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Total Connect Time', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('TotalConnectTime');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','time');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setUnite( 's' );
			$RouteurCmd->setOrder('10');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'refresh');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'refresh');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Rafraîchir', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('refresh');
			$RouteurCmd->setType('action');
			$RouteurCmd->setSubType('other');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'devicename');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'devicename');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Modèle', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('devicename');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','linux');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('13');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'SoftwareVersion');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'SoftwareVersion');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Software', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('SoftwareVersion');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','linux');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('14');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'WebUIVersion');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'WebUIVersion');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('WebUI', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('WebUIVersion');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','linux');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('15');
			$RouteurCmd->save();
		}
		
		/*
		$RouteurCmd = $this->getCmd(null, 'reboot');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'reboot');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Redémarrer', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('reboot');
			$RouteurCmd->setType('action');
			$RouteurCmd->setSubType('other');
			$RouteurCmd->setOrder('16');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'poweroff');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'poweroff');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Arrêter', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('poweroff');
			$RouteurCmd->setType('action');
			$RouteurCmd->setSubType('other');
			$RouteurCmd->setOrder('17');
			$RouteurCmd->save();
		}
		*/
	}
	
	public function postUpdate() {		
		$cmd = $this->getCmd(null, 'refresh');
		if (is_object($cmd)) { 
			 $cmd->execCmd();
		}
    }
	
}

class huawei4gCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */


    public function execute($_options = null) {
		$eqLogic = $this->getEqLogic();
		switch ($this->getLogicalId()) {
			case "reboot":
				$eqLogic->reboot();
				log::add('huawei4g','debug','reboot ' . $this->getHumanName());
				break;
			case "poweroff":
				$eqLogic->halt();
				log::add('huawei4g','debug','poweroff ' . $this->getHumanName());
				break;
			case "refresh":
				$eqLogic->getRouteurInfo();
				log::add('huawei4g','debug','refresh ' . $this->getHumanName());
				break;
 		}
		return true;
	}

    /*     * **********************Getteur Setteur*************************** */
}

?>