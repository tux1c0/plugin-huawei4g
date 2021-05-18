<?php

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
require_once dirname(__FILE__) . '/../class/frequency.class.php';

if (!jeedom::apiAccess(init('apikey'), 'huawei4g')) {
    echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
    die();
}

if (init('test') != '') {
    echo 'OK';
    die();
}

if (init('deviceslist') != '') {
    $data = [];
    foreach (eqLogic::byType('huawei4g') as $eqLogic) {
        if ($eqLogic->getIsEnable() != 1) {
            continue;
        }

        $data[$eqLogic->getId()] = array(
            'ip' => $eqLogic->getConfiguration('ip'),
            'username' => $eqLogic->getConfiguration('username'),
            'password' => $eqLogic->getConfiguration('password')
        );
    }
    die(json_encode($data));
}

$result = json_decode(file_get_contents("php://input"), true);
if (!is_array($result)) {
    die();
}

// get all Huawei4g equipments
$eqLogics = eqLogic::byType('huawei4g');
if (count($eqLogics) < 1) {
    die();
}

// clean the info
function cleanInfo($cle, $valeur) {
	$key = trim(secureXSS($cle));
	$value = trim(secureXSS($valeur));
	$out = array();
	// init, default is the key/cmd
	$out[0] = $key;
	
	if(strpos(strval($value), 'dB') === true) {
		$this->infos[$key] = str_replace('dB', '', $value);
	} elseif (strpos(strval($value), 'dBm') === true) {
		$this->infos[$key] = str_replace('dBm', '', $value);
	} else {	
		switch($key) {
		case "lte_bandinfo": 
			$out[0] = 'band';
			$out[1] = $value;
			break;
		case "dataswitch": 
			$out[1] = intval($value);
			break;
		case "DeviceName": 
			$out[0] = 'devicename';
			$out[1] = $value;
			break;
		default:
			$out[1] = $value;
		}
	}
	
	log::add('huawei4g', 'debug', 'function cleanInfo key:'.$out[0].' value: '.$out[1]);
	return $out;
}

// update a single cmd
function updateInfo($eqLogicToUpdate, $cmdToUpdate, $valueToUpdate) {
	try {
		$cmd = $eqLogicToUpdate->getCmd(null, $cmdToUpdate);
		if (is_object($cmd)) {
			$cmd->event($valueToUpdate);
		}
		log::add('huawei4g', 'debug', 'updateInfo cmd '.$cmdToUpdate. ' valeur '.$valueToUpdate);
	} catch (Exception $e) {
		log::add('huawei4g', 'error', 'Impossible de mettre à jour le champs '.$cmdToUpdate);
	}
}

// deal with signal API output
function frequency($eqLogicToUpdate, $arr) {
	$Frequency = new Frequency();
	
	// calculating frequencies
	if(isset($arr['band']) and isset($arr['earfcn'])) {
		$Frequency->setBand($arr['band']);
		$Frequency->setEarfcn($arr['earfcn']);
		$Frequency->calculator();
		$frq = $Frequency->getName();
		updateInfo($eqLogicToUpdate, 'frq', $frq);
		$fdl = $Frequency->getFdl();
		updateInfo($eqLogicToUpdate, 'fdl', $fdl);
		$ful = $Frequency->getFul();
		updateInfo($eqLogicToUpdate, 'ful', $ful);
		// calcul Marge RF
		$mrf = $arr['rssi'] - $arr['rsrp'];
		updateInfo($eqLogicToUpdate, 'mrf', $mrf);
	}
	
	foreach($arr as $key => $data) {
		log::add('huawei4g', 'debug', 'update key '.$key.' - data '.$data);
		//clean info
		$res = cleanInfo($key, $data);
		updateInfo($eqLogicToUpdate, $res[0], $res[1]);
	}	
}

// update all cmd
if (isset($result['cmd']) and isset($result['data'])) {
	log::add('huawei4g', 'debug', 'result update data '.$result['data']);
	
	switch($result['cmd']) {
		case "update":
			foreach($result['data'] as $key => $data) {
				log::add('huawei4g', 'debug', 'update key '.$key.' - data '.$data);
				//clean info
				$res = cleanInfo($key, $data);
				//only first eqLogics, pending support of multi eqlogics
				updateInfo($eqLogics[0], $res[0], $res[1]);
			}
			break;
			
		case "status": 
			log::add('huawei4g', 'debug', 'status '.$result['data']);
			//only first eqLogics, pending support of multi eqlogics
			updateInfo($eqLogics[0], "status", trim(secureXSS($result['data'])));
			break;
			
		case "signal": 
			log::add('huawei4g', 'debug', 'signal '.$result['data']);
			frequency($eqLogics[0], $result['data']);
			break;
		
		case "smsList": 
			log::add('huawei4g', 'debug', 'smsList '.$result['data']);
			$outputSMS = str_replace("\\'", "'", $result['data']['Message']);
			$outputSMS = str_replace(array("\r\n", "\n", "\r"), "", $outputSMS);
			//only first eqLogics, pending support of multi eqlogics
			updateInfo($eqLogics[0], "Messages", trim(json_encode($outputSMS)));
			break;

		case "ssid": 
			log::add('huawei4g', 'debug', 'ssid '.$result['data']);
			//only first eqLogics, pending support of multi eqlogics
			updateInfo($eqLogics[0], "Ssid", trim(json_encode($result['data']['Ssid'])));
			break;
			
		case "count": 
			log::add('huawei4g', 'debug', 'count '.$result['data']);
			//only first eqLogics, pending support of multi eqlogics
			updateInfo($eqLogics[0], "Count", trim(secureXSS($result['data'])));
			break;

		case "radio": 
			$tmp = $result['data']['radio'];
			log::add('huawei4g', 'debug', 'radio '.$result['data']);
			foreach($tmp as $key => $value) {
				if(isset($tmp[$key]['ID'])) {
					if($tmp[$key]['ID'] == "InternetGatewayDevice.X_Config.Wifi.Radio.1."){
						$return["Radio24"] = $tmp[$key]['wifienable'];
						updateInfo($eqLogics[0], "Radio24", trim(secureXSS($tmp[$key]['wifienable'])));
					}
					if($tmp[$key]['ID'] == "InternetGatewayDevice.X_Config.Wifi.Radio.2."){
						updateInfo($eqLogics[0], "Radio5", trim(secureXSS($tmp[$key]['wifienable'])));
					}
				}
			}

			break;

		default:
			break;
		}
}


if (isset($result['cmd']) and isset($result['message'])) {
    foreach ($eqLogics as $eqLogic) {
        $cmd = $eqLogic->getCmd(null, $result['cmd']);
        if (is_object($cmd)) {
            $cmd->event($result['message']);
        }
    }
    die();
}

if (isset($result['messages'])) {
    foreach ($result['messages'] as $key => $datas) {
        $message = trim(secureXSS($datas['message']));
        $sender = trim(secureXSS($datas['sender']));

        if (empty($message) or empty($sender)) {
            continue;
        }

        $smsOk = false;
        foreach ($eqLogics as $eqLogic) {
            foreach ($eqLogic->getCmd() as $eqLogicCmd) {
                if (strpos($eqLogicCmd->getConfiguration('phonenumber'), $sender) === false) {
                    continue;
                }

                $smsOk = true;
                log::add('huawei4g', 'info', __('Message de ', __FILE__) . $sender . ' : ' . $message);

                // Prise en charge de la commande ask
                if ($eqLogicCmd->askResponse($message)) {
                    continue(3);
                }

                // Gestion des interactions
                $params = array('plugin', 'huawei4g');
                if ($eqLogicCmd->getConfiguration('user') != '') {
                    $user = user::byId($eqLogicCmd->getConfiguration('user'));
                    if (is_object($user)) {
                        $params['profile'] = $user->getLogin();
                    }
                }
                $params['reply_cmd'] = $eqLogicCmd;
                $reply = interactQuery::tryToReply(trim($message), $params);
                if (trim($reply['reply']) != '') {
                    $eqLogicCmd->execute(array('message' => $reply['reply'], 'numbers' => array($sender)));
                    log::add('huawei4g', 'info', __('Réponse : ', __FILE__) . $reply['reply']);
                }

                $cmd = $eqLogicCmd->getEqlogic()->getCmd('info', 'smsLastMessage');
                $cmd->event($message);

                $cmd = $eqLogicCmd->getEqlogic()->getCmd('info', 'smsLastSender');
                $cmd->event($sender);
                break;
            }
        }

        if (!$smsOk) {
            log::add('huawei4g', 'info', __('Message d\'un numéro non autorisé ', __FILE__) . secureXSS($sender) . ' : ' . secureXSS($message));
        }
    }
}
