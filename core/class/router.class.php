<?php
use GuzzleHttp\Client;

class Router {
    private $routerAddress = 'http://192.168.1.1';
	private $client;
	private $token;
	private $session;


    public function setAddress($address) {
        $address = rtrim($address, '/');
        if (strpos($address, 'http') !== 0) {
            $address = 'http://'.$address;
        }

        $this->routerAddress = $address.'/';
    }
	
	public function getAddress() {
		return $this->routerAddress;
	}

	// Build the full API URL
    private function getUrl($apiAddress) {
        return $this->routerAddress.'/'.$apiAddress;
    }
	
	// convert string to XML
	private function toXml($response) {
        libxml_disable_entity_loader(true);
        libxml_use_internal_errors(true);
        log::add('huawei4g', 'debug', 'Body to convert: '.$response);
		$xml = simplexml_load_string((string)$response, "SimpleXMLElement", LIBXML_NOCDATA);

		if(!$xml) {
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				log::add('huawei4g', 'error', 'Parse XML: '.$error);
			}
			libxml_clear_errors();
			return null;
		} else {
			return $xml;
		}
    }
	
	private function setToken($infoTab) {
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
			log::add('huawei4g', 'error', 'Impossible de récupérer le token');
		} else {
			$this->token = $infoTab['TokInfo'];
			$this->session = $infoTab['SesInfo'];
			log::add('huawei4g', 'debug', 'token:'.$this->token);
			log::add('huawei4g', 'debug', 'session:'.$this->session);
		}
	}
	
	/*
	Functions for HTTP sessions
	*/
	public function setHttpSession() {
		$this->client = new GuzzleHttp\Client(['base_uri' => $this->getAddress(), 'timeout' => 5.0]);
		$this->setToken(getToken());
	}
	
	// get the info
	private function getInfo($api) {
		$xml = $this->getXML($api);
		return $this->xmlToArray($xml);		
	}
	
	// retrieve the XML response
	/*
	ERROR_SYSTEM_UNKNOWN = 100001
    ERROR_SYSTEM_NO_SUPPORT = 100002
    ERROR_SYSTEM_NO_RIGHTS = 100003
    ERROR_SYSTEM_BUSY = 100004
	ERROR_SYSTEM_PARAMETER = 100006
    ERROR_SYSTEM_CSRF = 125002 (token)
	*/
	private function getXML($api) {
		try {
			$response = $this->client->get($api);
		} catch (RequestException $e) {
			log::add('huawei4g', 'error', 'Erreur de connexion au routeur');
			log::add('huawei4g', 'debug', $e->getRequest());
			if ($e->hasResponse()) {
				log::add('huawei4g', 'error', $e->getResponse());
			}
		}

		$xml = $this->toXml($response->getBody());
		log::add('huawei4g', 'debug', $api.', '.$xml->asXML());
        return $xml;
    }
	
	// transform XML into array
	private function xmlToArray($xml) {
		$json = json_encode($xml);
		return json_decode($json,TRUE);
	}
	
	/*
	Functions w/o login needed
	net/net-feature-switch
	webserver/token
	webserver/SesTokInfo
	wlan/wifi-feature-switch
	wlan/basic-settings
	wlan/multi-switch-settings
	wlan/wps-pbc
	wlan/status-switch-settings
	global/module-switch
	language/current-language
	cradle/status-info
	monitoring/converged-status
	monitoring/check-notifications
	monitoring/start_date
	monitoring/month_statistics
	dialup/mobile-dataswitch
	dialup/connection
	dialup/dialup-feature-switch
	time/timeout
	redirection/homepage
	pin/status
	pin/simlock
	online-update/status
	online-update/configuration
	online-update/autoupdate-config
	
	*/
	public function getTrafficStatistics() {
		return $this->getInfo('api/monitoring/traffic-statistics');
	}
	
	public function getPublicLandMobileNetwork() {
		return $this->getInfo('api/net/current-plmn');
	}
	
	public function getToken() {
		return $this->getInfo('api/webserver/SesTokInfo');
	}

	/*
	Functions w/ login needed
	*/
	public function getCellInfo() {
		return $this->getInfo('api/net/cell-info');
	}

	/*
	Undefined
	net/network
	
	
	*/

	/*
	ERROR 125002
	net/net-mode
	net/register
	net/net-mode-list
	net/plmn-list
	net/cell-info
	net/csps_state
	usbstorage/fsstatus
	usbstorage/usbaccount
	voice/featureswitch
	voice/sipaccount
	voice/sipadvance
	voice/sipserver
	voice/speeddial
	voice/functioncode
	voice/voiceadvance
	vpn/feature-switch
	vpn/br_list
	vpn/ipsec_settings
	vpn/l2tp_settings
	vpn/pptp_settings
	lan/HostInfo
	led/nightmode
	log/loginfo
	dhcp/settings
	dhcp/feature-switch
	cradle/feature-switch
	cradle/basic-info
	cradle/factory-mac
	cradle/mac-info
	cwmp/basic-info
	ddns/ddns-list
	ddns/status
	monitoring/status
	monitoring/start_date_wlan
	monitoring/month_statistics_wlan
	ntwk/lan_upnp_portmapping
	dialup/profiles
	usbprinter/printerlist
	sntp/sntpswitch
	sntp/timeinfo
	sntp/serverinfo
	pb/pb-match
	online-update/check-new-version
	online-update/check-new-version
	online-update/ack-newversion
	sdcard/dlna-setting
	sdcard/sdcardsamba
	sdcard/printerlist
	
	
	
	ERROR 100002
	vsim/operateswitch-vsim
	wlan/handover-setting
	ntwk/celllock
	statistic/feature-roam-statistic
	sdcard/sdcard
	
	
	ERROR 100006
	webserver/publickey
	
	
	ERROR 100003
	webserver/white_list_switch
	wlan/station-information
	wlan/security-settings
	wlan/multi-security-settings
	wlan/multi-security-settings-ex
	wlan/multi-basic-settings
	wlan/multi-macfilter-settings
	wlan/multi-macfilter-settings-ex
	wlan/mac-filter
	wlan/oled-showpassword
	wlan/wps
	wlan/wps-appin
	wlan/wps-switch
	dialup/auto-apn
	time/timerule
	syslog/querylog
	sntp/settings
	ota/status
	sdcard/share-account
	
	ERROR 100001
	online-update/url-list
	
	*/

}
?>