<?php

class Frequency {
    private $name;
	private $band;
	private $fdl;
	private $ful;
	private $earfcn;
	private $frqArray;
	private $jsonFile = 'frequency.json';

	public function setEarfcn($e) {
		$this->earfcn = $e;
	}
	
	public function getEarfcn() {
		return $this->earfcn;
	}
	
	public function setBand($b) {
		$this->band = $b;
	}
	
	public function getBand() {
		return $this->band;
	}

    private function setName() {
		$this->name = 'a';
    }
	
	public function getName() {
		return $this->name;
	}

	function __construct() {
        $this->getJSON();
    }

	public function calculator() {
		
		
	}
	
	private function getJSON() {
		try {
			// Read JSON file
			$json = file_get_contents(dirname(__FILE__) . '/../../resources/'.$this->jsonFile);

			//Decode JSON
			$this->frqArray = json_decode($json,true);
		} catch (Exception $e) {
			log::add('huawei4g', 'error', $e->getMessage());
		}
		
	}
	


}
?>