<?php

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

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
            'password' => $eqLogic->getConfiguration('password'),
            'frequence' => $eqLogic->getConfiguration('frequence'),
            'texteMode' => $eqLogic->getConfiguration('texteMode')
        );
    }
    die(json_encode($data));
}

$result = json_decode(file_get_contents("php://input"), true);
if (!is_array($result)) {
    die();
}

$eqLogics = eqLogic::byType('huawei4g');
if (count($eqLogics) < 1) {
    die();
}

// update a cmd
function updateInfo($eqLogicToUpdate, $cmdToUpdate, $valueToUpdate) {
	try {
		$cmd = $eqLogicToUpdate->getCmd(null, $cmdToUpdate);
		if (is_object($cmd)) {
			$cmd->checkAndUpdateCmd($cmd, $valueToUpdate);
		}
		log::add('huawei4g', 'debug', 'updateInfo cmd '.$cmdToUpdate. ' valeur '.$valueToUpdate);
	} catch (Exception $e) {
		log::add('huawei4g', 'error', 'Impossible de mettre à jour le champs '.$cmdToUpdate);
	}
}

// update all cmd
if (isset($result['cmd']) and isset($result['data'])) {
	log::add('huawei4g', 'debug', 'result update data '.$result['data']);
	if($result['cmd'] == "update") {
		foreach($result['data'] as $data) {
			log::add('huawei4g', 'debug', 'update data '.$data);
			//updateInfo($eqLogics[0], $data, $value);
		}
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
