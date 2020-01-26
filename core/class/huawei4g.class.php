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
	
    /*     * ***********************Methode static*************************** */
	public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = jeedom::getTmpFolder('huawei4g') . '/dependance';
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
		
		$this->infos = array(
			'status'	=> ''
		);
		
		//The router class is the main entry point for interaction.
		$Router = new Router();
		$Router->setAddress($IPaddress);

		try {
			$Router->setHttpSession();
			$stats = $Router->getTrafficStatistics();
			foreach($stats as $stat => $value) {
				log::add('huawei4g', 'debug', 'stat:'.$stats.' value:'.$value);
			}

		} catch (Exception $e) {
				log::add('huawei4g', 'error', $e);
		}
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
			$RouteurCmd->setOrder('15');
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