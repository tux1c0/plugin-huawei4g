<?php
use GuzzleHttp\Client;

class Router {
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
		$this->client = new GuzzleHttp\Client(['base_uri' => $this->getAddress(), 'timeout' => 5.0]);
	}
	
	
	private function getXML($api) {
        $response = $this->client->request('GET', $api);

        return $response->getBody();
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