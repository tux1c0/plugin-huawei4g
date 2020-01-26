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
	
	/*
	Functions for HTTP sessions
	*/
	public function setHttpSession() {
		$this->client = new GuzzleHttp\Client(['base_uri' => $this->getAddress(), 'timeout' => 5.0]);
	}
	
	// get the info
	private function getInfo($api) {
		$xml = $this->getXML($api);
		return $this->xmlToArray($xml);		
	}
	
	// retrieve the XML response
	private function getXML($api) {
		try {
			$response = $this->client->get($api);
		} catch (RequestException $e) {
			log::add('huawei4g', 'error', $e->getRequest());
			if ($e->hasResponse()) {
				log::add('huawei4g', 'error', $e->getResponse());
			}
		}

		$xml = $this->toXml($response->getBody());
		log::add('huawei4g', 'debug', $xml->asXML());
        return $xml;
    }
	
	// transform XML into array
	private function xmlToArray($xml) {
		$json = json_encode($xml);
		return json_decode($json,TRUE);
	}
	
	/*
	Functions w/o login needed
	*/
	public function getTrafficStatistics() {
		$res = $this->getInfo('api/monitoring/traffic-statistics');

		return $res;
	}
	
	/*
	Functions w/ login needed
	*/


}
?>