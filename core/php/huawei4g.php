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
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (!jeedom::apiAccess(init('apikey'), 'huawei4g')) {
    echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
    die();
}

if (init('test') != '') {
    echo 'OK';
    die();
}

$result = json_decode(file_get_contents("php://input"), true);
if (!is_array($result)) {
    die();
}

$eqLogics = eqLogic::byType('huawei4g');
if (count($eqLogics) < 1) {
    die();
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
