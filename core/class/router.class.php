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
	private function toXml($response, array $config = [])
    {
        $disableEntities = libxml_disable_entity_loader(true);
        $internalErrors = libxml_use_internal_errors(true);
        try {
            // Allow XML to be retrieved even if there is no response body
            $xml = new SimpleXMLElement(
                (string) $this->getBody() ?: '<root />',
                isset($config['libxml_options']) ? $config['libxml_options'] : LIBXML_NONET,
                false,
                isset($config['ns']) ? $config['ns'] : '',
                isset($config['ns_is_prefix']) ? $config['ns_is_prefix'] : false
            );
            libxml_disable_entity_loader($disableEntities);
            libxml_use_internal_errors($internalErrors);
        } catch (\Exception $e) {
            libxml_disable_entity_loader($disableEntities);
            libxml_use_internal_errors($internalErrors);
            throw new XmlParseException(
                'Unable to parse response body into XML: ' . $e->getMessage(),
                $this,
                $e,
                (libxml_get_last_error()) ?: null
            );
        }
        return $xml;
    }
	
	/*
	Functions for HTTP sessions
	*/
	public function setHttpSession() {
		$this->client = new GuzzleHttp\Client(['base_uri' => $this->getAddress(), 'timeout' => 5.0]);
	}
	
	
	private function getXML($api) {
		try {
			$response = $this->client->get($api);
		} catch (RequestException $e) {
			log::add('huawei4g', 'error', $e->getRequest());
			if ($e->hasResponse()) {
				log::add('huawei4g', 'error', $e->getResponse());
			}
		}
			//if($response->getStatusCode() == 200) {
				$xml = $this->toXml($response->getBody(), []);
			//}
			
        return $xml;
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