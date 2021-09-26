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

class huawei4g extends eqLogic {
    /*     * *************************Attributs****************************** */
	public static $_widgetPossibility = array('custom' => true);


    /*     * ***********************Methode static*************************** */
	public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = jeedom::getTmpFolder('huawei4g') . '/dependance';
		//if (exec(system::getCmdSudo() . system::get('cmd_check') . ' -E "python3\-huawei\-lte\-api" | wc -l') >= 1) {
		if (exec(system::getCmdSudo() . ' python3 -c "import huawei_lte_api"; echo $?') == 0) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		
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
				$rtr->postSave();
			} catch (Exception $e) {
				log::add('huawei4g', 'error', $e->getMessage());
			}
		}
	}


	public static function deamon_info() {
        $return = array();
        $return['log'] = __CLASS__;
        $return['state'] = 'nok';
        $return['launchable'] = 'ok';

        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file)) {
            if (@posix_getsid(trim(file_get_contents($pid_file)))) {
                $return['state'] = 'ok';
            } else {
                shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
            }
        }

        return $return;
    }

    public static function deamon_start() {
        self::deamon_stop();

        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        $deamon_path = realpath(__DIR__ . '/../../resources/huawei4gd');
        $cmd = '/usr/bin/python3 ' . $deamon_path . '/huawei4gd.py';
        $cmd .= ' --socketport ' . config::byKey('socketport', __CLASS__);
        $cmd .= ' --cycle ' . config::byKey('cycle', __CLASS__);
        $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
        $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
        $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/' . __CLASS__ . '/core/php/huawei4g.php';
        log::add(__CLASS__, 'info', 'Lancement démon ' . __CLASS__ . ' : ' . $cmd);
        $result = exec($cmd . ' >> ' . log::getPathToLog(__CLASS__) . ' 2>&1 &');

        $i = 0;
        while ($i < 30) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }

            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add(__CLASS__, 'error', 'Impossible de lancer le démon ' . __CLASS__ .', vérifiez les paramètres', 'unableStartDeamon');
            return false;
        }

        message::removeAll(__CLASS__, 'unableStartDeamon');
        return true;
    }

    public static function deamon_stop() {
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file)) {
            $pid = intval(trim(file_get_contents($pid_file)));
            system::kill($pid);
        }

        system::kill('huawei4gd.py');
        system::fuserk(config::byKey('socketport', __CLASS__));

        sleep(1);
    }

	public function preUpdate() {
		if ($this->getConfiguration('ip') == '') {
			throw new Exception(__('Le champs IP ne peut pas être vide', __FILE__));
		}
	}
	
	private function getLastSMSReceived($json) {
		$values = array();
		$DateSms;
		$values['Number'] = "N/A";
		$values['Text'] = "N/A";

		if(strpos($json, '[')=== FALSE) {
			$obj = json_decode($json);
			log::add('huawei4g', 'debug', 'single sms '.$obj);
			log::add('huawei4g', 'debug', 'key SMS 1');
			log::add('huawei4g', 'debug', 'value Phone '.$obj->Phone);
			log::add('huawei4g', 'debug', 'value Content '.$obj->Content);
			if($value->Smstat == 0) {
				log::add('huawei4g', 'debug', 'value Sms reçu');
				$values['Number'] = $obj->Phone;
				$values['Text'] = $obj->Content;
			}
		} else {
			$obj = json_decode($json);
			log::add('huawei4g', 'debug', 'multi sms '.$obj);

			foreach($obj as $key => $value) {
				$NewDate = DateTime::createFromFormat('Y-m-d H:i:s', $value->Date);
				log::add('huawei4g', 'debug', 'key SMS '.$key);
				log::add('huawei4g', 'debug', 'value Phone '.$value->Phone);
				log::add('huawei4g', 'debug', 'value Content '.$value->Content);
				if($value->Smstat == 0) {
					log::add('huawei4g', 'debug', 'value Sms reçu');
					if(empty($DateSms)) {
						log::add('huawei4g', 'debug', 'value Date empty, setting date '.$NewDate->format('Y-m-d'));
						$DateSms = $NewDate;
					}
					log::add('huawei4g', 'debug', 'date sms '.$DateSms->format('Y-m-d H:i:s'));
					log::add('huawei4g', 'debug', 'new date '.$NewDate->format('Y-m-d H:i:s'));

					if($DateSms <= $NewDate) {
						log::add('huawei4g', 'debug', 'value Date not empty, comparing dates');

						$DateSms = DateTime::createFromFormat('Y-m-d H:i:s', $value->Date);
						$values['Number'] = $value->Phone;
						$values['Text'] = $value->Content;
					}
				}
			}
		}
		return $values;
	}
	
	public function reboot() {
		$sendData['action'] = "reboot";
		$this->sendDeamon($sendData);
		log::add('huawei4g', 'debug', 'send reboot to deamon: '.$sendData);
	}
	
	public function enableData() {
		$sendData['action'] = "enabledata";
		$this->sendDeamon($sendData);
		log::add('huawei4g', 'debug', 'send enabledata to deamon: '.$sendData);
	}
	
	public function disableData() {
		$sendData['action'] = "disabledata";
		$this->sendDeamon($sendData);
		log::add('huawei4g', 'debug', 'send disabledata to deamon: '.$sendData);
	}	
	
	public function delSMS($arr) {
		if(empty($arr['smsid'])) {
			log::add('huawei4g', 'debug', 'delSMS smsid empty');
		} else {
			$index = $arr['smsid'];
			$sendData['index'] = $index;
			$sendData['action'] = "delsms";
			$this->sendDeamon($sendData);
			log::add('huawei4g', 'debug', 'send enabledata to deamon: '.$sendData);
		}
	}
	public function sendSMSDeamon($_options = array()) {
		$texteMode = $this->getConfiguration('texteMode');
		$check = TRUE;
		
		if($texteMode == 1) {
			$messageSMS = $this->cleanSMS($_options['message']);
		} else {
			$messageSMS = $_options['message'];
		}
		
		log::add('huawei4g', 'debug', 'SMS numerotel: '.$_options['numerotel']);
		log::add('huawei4g', 'debug', 'SMS title: '.$_options['title']);
		log::add('huawei4g', 'debug', 'SMS message: '.$messageSMS);
		
		// téléphone rempli dans un scenario
		if(!empty($_options['title']) && empty($arr['numerotel'])) {
			if(preg_match('/^[0-9]{10}+$/', $_options['title'])) {
				$numbers = explode(';', $_options['title']);
			} else {
				$numbers = explode(';', $this->getConfiguration('phonenumber'));
			}
			$check = FALSE;
			log::add('huawei4g', 'debug', 'SMS par scenario');
		}
		
		// téléphone rempli dans le widget
		if(empty($_options['title']) && !empty($_options['numerotel'])) {
			$numbers = explode(';', $_options['numerotel']);
			$check = FALSE;
			log::add('huawei4g', 'debug', 'SMS par widget');
		}
		
		// default
        if($check) {
            $numbers = explode(';', $this->getConfiguration('phonenumber'));
			log::add('huawei4g', 'debug', 'SMS default phonenumber: '.$numbers);
        }

		if(!empty($numbers)) {
			$message = trim($_options['message']);
			$sendData['message'] = $messageSMS;
			$sendData['numbers'] = $numbers;
			$sendData['action'] = "sendsms";
			$this->sendDeamon($sendData);
			log::add('huawei4g', 'debug', 'send SMS to deamon: '.$sendData);
		} else {
			log::add('huawei4g', 'debug', 'SMS numbers missing');
		}
    }
	
	// send data to deamon
	public function sendDeamon($dataToSend) {
		$dataToSend['apikey'] = jeedom::getApiKey('huawei4g');
		
        if ($this->getConfiguration('ip') != null) {
            $data = json_encode($dataToSend);

            $socket = socket_create(AF_INET, SOCK_STREAM, 0);
            socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'huawei4g'));
            socket_write($socket, $data, strlen($data));
            socket_close($socket);
        }
    }
	
	private function cleanSMS($message) {
		$caracteres = array(
				'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ä' => 'a', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', '@' => 'a',
				'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', '€' => 'e',
				'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
				'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Ö' => 'o', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
				'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'µ' => 'u',
				'Œ' => 'oe', 'œ' => 'oe',
				'$' => 's');
		return preg_replace('#[^A-Za-z0-9 \n\.\'=\*:]+#', '', strtr($message, $caracteres));
	}
	
	
		/*     * *********************Methode d'instance************************* */
	public function preSave() {
		//$this->setDisplay('height','900');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-power');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-time');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-upload');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-download');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-perf');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-perf');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-upload');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-download');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-time');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setUnite( 's' );
			$RouteurCmd->setOrder('10');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'CurrentMonthUpload');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'CurrentMonthUpload');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Mois Upload', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('CurrentMonthUpload');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-upload');
			$RouteurCmd->setSubType('numeric');
			//$RouteurCmd->setUnite( 'o' );
			$RouteurCmd->setOrder('14');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'CurrentMonthDownload');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'CurrentMonthDownload');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Mois Download', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('CurrentMonthDownload');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-download');
			$RouteurCmd->setSubType('numeric');
			//$RouteurCmd->setUnite( 'o' );
			$RouteurCmd->setOrder('13');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-linux');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('15');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-linux');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('16');
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
			$RouteurCmd->setTemplate('dashboard','huawei4g-linux');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('17');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'Imei');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'Imei');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('IMEI', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('Imei');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('18');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'WanIPAddress');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'WanIPAddress');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('WAN IPv4', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('WanIPAddress');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('19');
			$RouteurCmd->save();
		}
		
			$RouteurCmd = $this->getCmd(null, 'WanIPv6Address');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'WanIPv6Address');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('WAN IPv6', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('WanIPv6Address');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('20');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'workmode');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'workmode');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Mode', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('workmode');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('21');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'cell_id');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'cell_id');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Cell ID', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('cell_id');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('22');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'rsrp');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'rsrp');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('RSRP', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('rsrp');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setUnite( 'dBm' );
			$RouteurCmd->setOrder('23');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'rssi');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'rssi');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('RSSI', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('rssi');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setUnite( 'dBm' );
			$RouteurCmd->setOrder('24');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'mrf');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'mrf');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Marge RF', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('mrf');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setUnite( 'dBm' );
			$RouteurCmd->setOrder('25');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'sinr');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'sinr');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('SINR', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('sinr');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setUnite( 'dB' );
			$RouteurCmd->setOrder('26');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'Msisdn');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'Msisdn');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Numéro', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('Msisdn');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('27');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'frq');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'frq');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Fréquence', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('frq');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('28');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'fdl');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'fdl');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('FDL', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('fdl');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setOrder('29');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'ful');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'ful');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('FUL', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('ful');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-antenna');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setOrder('30');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'dataswitch');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'dataswitch');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Mobile data', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('dataswitch');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-data-status');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setOrder('31');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'Count');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'Count');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('SMS Stockés', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('Count');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-sms');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setOrder('32');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'LocalUnread');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'LocalUnread');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('SMS Non Lu', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('LocalUnread');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-sms');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setOrder('33');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'LocalInbox');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'LocalInbox');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('SMS Reçus', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('LocalInbox');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-sms');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setOrder('34');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'LocalOutbox');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'LocalOutbox');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('SMS Envoyés', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('LocalOutbox');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-sms');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setOrder('35');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'LocalDeleted');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'LocalDeleted');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('SMS Supprimés', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('LocalDeleted');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-sms');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setOrder('36');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'Messages');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'Messages');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('SMS', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('Messages');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-smstxt');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('37');
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
			//$RouteurCmd->setTemplate('dashboard','huawei4g-btn');
			$RouteurCmd->setSubType('other');
			//$RouteurCmd->setOrder('30');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'disabledata');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'disabledata');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Désactiver Data', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('disabledata');
			$RouteurCmd->setType('action');
			//$RouteurCmd->setTemplate('dashboard','huawei4g-btn');
			$RouteurCmd->setSubType('other');
			//$RouteurCmd->setOrder('30');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'enabledata');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'enabledata');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Activer Data', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('enabledata');
			$RouteurCmd->setType('action');
			//$RouteurCmd->setTemplate('dashboard','huawei4g-btn');
			$RouteurCmd->setSubType('other');
			//$RouteurCmd->setOrder('30');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'sendsms');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'sendsms');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Envoyer SMS', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('sendsms');
			$RouteurCmd->setType('action');
			$RouteurCmd->setTemplate('dashboard','huawei4g-sendsms');
			$RouteurCmd->setSubType('message');
			$RouteurCmd->setOrder('38');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'delsms');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'delsms');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Supprimer SMS', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('delsms');
			$RouteurCmd->setType('action');
			$RouteurCmd->setTemplate('dashboard','huawei4g-delsms');
			$RouteurCmd->setSubType('other');
			$RouteurCmd->setOrder('39');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'Radio24');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'Radio24');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Radio 2.4 GHz', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('Radio24');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-wifi-status');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setOrder('40');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'Radio5');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'Radio5');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Radio 5 GHz', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('Radio5');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-wifi-status');
			$RouteurCmd->setSubType('numeric');
			$RouteurCmd->setOrder('41');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'Ssid');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'Ssid');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('SSID', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('Ssid');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-ssid');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('42');
			$RouteurCmd->save();
		}
		
		$RouteurCmd = $this->getCmd(null, 'LastNumber');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'LastNumber');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Dernier Numéro', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('LastNumber');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-sms');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('43');
			$RouteurCmd->save();
		}

		$RouteurCmd = $this->getCmd(null, 'LastSMS');
		if (!is_object($RouteurCmd)) {
			log::add('huawei4g', 'debug', 'LastSMS');
			$RouteurCmd = new huawei4gCmd();
			$RouteurCmd->setName(__('Dernier Message', __FILE__));
			$RouteurCmd->setEqLogic_id($this->getId());
			$RouteurCmd->setLogicalId('LastSMS');
			$RouteurCmd->setType('info');
			$RouteurCmd->setTemplate('dashboard','huawei4g-sms');
			$RouteurCmd->setSubType('string');
			$RouteurCmd->setOrder('44');
			$RouteurCmd->save();
		}
	}
	
	public function postUpdate() {		
		/*$cmd = $this->getCmd(null, 'refresh');
		if (is_object($cmd)) { 
			$cmd->execCmd();
		}*/
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
				
			case "sendsms":
				$eqLogic->sendSmsDeamon($_options);
				log::add('huawei4g','debug','sendsms ' . $this->getHumanName());
				break;
	
			case "delsms":
				$eqLogic->delSMS($_options);
				log::add('huawei4g','debug','delsms ' . $this->getHumanName());
				break;
			
			case "enabledata":
				$eqLogic->enableData($_options);
				log::add('huawei4g','debug','enabledata ' . $this->getHumanName());
				break;
			
			case "disabledata":
				$eqLogic->disableData($_options);
				log::add('huawei4g','debug','disabledata ' . $this->getHumanName());
				break;
				
			default:
				log::add('huawei4g','debug','execute non reconnu ' . $this->getLogicalId());
 		}
		return true;
	}

    /*     * **********************Getteur Setteur*************************** */
}

?>