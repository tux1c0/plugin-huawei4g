<?php

class Frequency {
    private $name;
	private $band;
	private $fdl;
	private $ful;
	private $ndl;
	private $nul;
	private $earfcn;
	private $frqArray;
	private $jsonFile = 'frequency.json';

	public function setEarfcn($e) {
		$this->earfcn = $e;
		$split = explode(" ", $e);
		$this->setNdl(explode(':', $split[0])[1]);
		$this->setNul(explode(':', $split[1])[1]);
	}
	
	public function getEarfcn() {
		return $this->earfcn;
	}
	
	public function setFul($f) {
		$this->ful = $f;
	}
	
	public function getFul() {
		return $this->ful;
	}
	
	public function setFdl($f) {
		$this->fdl = $f;
	}
	
	public function getFdl() {
		return $this->fdl;
	}
	
	public function setNul($f) {
		$this->nul = $f;
	}
	
	public function getNul() {
		return $this->nul;
	}
	
	public function setNdl($f) {
		$this->ndl = $f;
	}
	
	public function getNdl() {
		return $this->ndl;
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
			log::add('huawei4g', 'debug', $this->frqArray);
		} catch (Exception $e) {
			log::add('huawei4g', 'error', $e->getMessage());
		}
		
	}
	


}
?>