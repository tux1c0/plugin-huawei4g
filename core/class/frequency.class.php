<?php

class Frequency {
    private $name = '';
	private $band;
	private $fdl = 0;
	private $ful = 0;
	private $ndl = 0;
	private $nul = 0;
	private $earfcn;
	private $frqArray;
	private $jsonFile = 'frequency.json';
	private $jsonKey;

	public function setEarfcn($e) {
		$this->earfcn = $e;
		$split = explode(" ", $e);
		$this->setNdl(explode(':', $split[0])[1]);
		$this->setNul(explode(':', $split[1])[1]);
		log::add('huawei4g', 'debug', 'freq ndl: '.$this->getNdl());
		log::add('huawei4g', 'debug', 'freq nul: '.$this->getNul());
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
		log::add('huawei4g', 'debug', 'freq band: '.$b);
		$this->setName();
	}
	
	public function getBand() {
		return $this->band;
	}

    private function setName() {
		$this->jsonKey = $this->searchArray('band', $this->getBand());
		log::add('huawei4g', 'debug', 'jsonKey: '.$this->jsonKey);
		
		$this->name = $this->frqArray[$this->jsonKey]["bandType"].' '.$this->frqArray[$this->jsonKey]["name"];
		log::add('huawei4g', 'debug', 'Freq Name: '.$this->name);
    }
	
	public function getName() {
		return $this->name;
	}

	function __construct() {
        $this->getJSON();
    }

	public function calculator() {
		$NDL_Off = $this->frqArray[$this->jsonKey]["NDL_Min"];
		log::add('huawei4g', 'debug', 'NDL_Off: '.$NDL_Off);
		
		$FDL_Low = $this->frqArray[$this->jsonKey]["FDL_Low"];
		log::add('huawei4g', 'debug', 'FDL_Low: '.$FDL_Low);
		
		$NUL_Off = $this->frqArray[$this->jsonKey]["NUL_Min"];
		log::add('huawei4g', 'debug', 'NUL_Off: '.$NUL_Off);
		
		$FUL_Low = $this->frqArray[$this->jsonKey]["FUL_Low"];
		log::add('huawei4g', 'debug', 'FUL_Low: '.$FUL_Low);
		
		$this->setFdl($FDL_Low+(0.1*($this->getNdl()-$NDL_Off)));
		$this->setFul($FUL_Low+(0.1*($this->getNul()-$NUL_Off)));
	}
	
	private function getJSON() {
		try {
			// Read JSON file
			$json = file_get_contents(dirname(__FILE__) . '/../../resources/'.$this->jsonFile);

			//Decode JSON
			$this->frqArray = json_decode($json,true);
			log::add('huawei4g', 'debug', 'tableau from JSON: '.$this->frqArray);
		} catch (Exception $e) {
			log::add('huawei4g', 'error', $e->getMessage());
		}
		
	}
	
	private function searchArray($key, $value) {
		$list = array_column($this->frqArray, $key);
		log::add('huawei4g', 'debug', 'liste: '.$list);
		$foundKey = array_search($value, $list);
		log::add('huawei4g', 'debug', 'key: '.$foundKey);
		
		return $foundKey;
	}


}
?>