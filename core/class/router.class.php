<?php
use GuzzleHttp\Client;

class Router {
    private $httpSession = null; 
    private $routerAddress = 'http://192.168.1.1';
	private $client;


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
	
	/*
	Functions for HTTP sessions
	*/
	public function setHttpSession() {
		$this->client = new GuzzleHttp\Client(['base_uri' => $this->getAddress(), 'timeout' => 3.0]);
	}
	
	
	private function getXML($api) {
        $response = $this->client->request('GET', $api);

        // Si un retour erreur <code>
        //if (property_exists($xml, 'code')) {
			//log::add('huawei4g', 'error', 'Erreur API '.$response);
        //}

        return $response;
    }
	
	
	/*
	Functions w/o login needed
	*/
	public function getTrafficStatistics() {
		$res = $this->getXML('api/monitoring/traffic-statistics');
		//$xml = new SimpleXMLElement($res);
		return $res;
	}
	
	/*
	Functions w/ login needed
	*/


}
?>