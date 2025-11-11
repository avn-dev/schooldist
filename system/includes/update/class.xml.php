<?php

class Update_Xml {
	
	protected $_aUpdates = array();
	protected $_iLastVersion;
	protected $_sLastName = array();
	
	protected $fCurrentVersion;
	
	public function __construct($fVersion=null) {
		
		if($fVersion === null) {
			$this->fCurrentVersion = System::d('version');
		} else {
			$this->fCurrentVersion = $fVersion;
		}
	
	}
	
	/**
	 * Parsed das Update-XML
	 * 
	 * @param string $sXml
	 * @return array
	 */
	public function parse($sXml) {

		$this->_aUpdates = array();
		
		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, array($this, "startElement"), array($this, "endElement"));
		xml_set_character_data_handler($xml_parser, array($this, "handleElement"));
		xml_set_default_handler($xml_parser, array($this, "defaultElement"));
		xml_parse($xml_parser, $sXml);
		xml_parser_free($xml_parser);

		return $this->_aUpdates;
		
	}
	
	public function handleElement($parser, $data) {
		
		if(version_compare($this->_iLastVersion, $this->fCurrentVersion, '>')) {

			if(preg_match("/[a-z0-9]/i", $data)){
				if($this->_sLastName == "MESSAGE"){
					$this->_aUpdates[$this->_iLastVersion][$this->_sLastName] = $data;
				}
				if($this->_sLastName == "SQL"){
					$this->_aUpdates[$this->_iLastVersion]['QUERIES'][] = $data;
				}
				if($this->_sLastName == "FILE"){
					$this->_aUpdates[$this->_iLastVersion]['FILES'][] = $data;
				}
				if($this->_sLastName == "REQUIREMENT"){
					$this->_aUpdates[$this->_iLastVersion]['REQUIREMENTS'][] = $data;
				}
				if($this->_sLastName == "CHECK"){
					$this->_aUpdates[$this->_iLastVersion]['CHECKS'][] = $data;
				}
				if($this->_sLastName == "UPDATE_TABLES"){
					$this->_aUpdates[$this->_iLastVersion]['UPDATE_TABLES'] = $data;
				}
				if($this->_sLastName == "LICENSE"){
					$this->_aUpdates[$this->_iLastVersion]['LICENSE'] = (int) $data;
				}
			}

			if($this->_sLastName == "DATE1" && !isset($this->_aUpdates[$this->_iLastVersion]['DATE'])){ 
				$this->_aUpdates[$this->_iLastVersion]['DATE'] = $data; 
			}
		}
	}

	public function startElement($parser, $name, $attrs) {

		if(
			isset($attrs['VERSION']) &&
			version_compare($attrs['VERSION'], $this->fCurrentVersion, '>')
		) {
			$this->_iLastVersion = $attrs['VERSION'];
		}

		$this->_sLastName = $name;

	}

	public function endElement($parser, $name) {

	}

	public function defaultElement($parser, $name) {

	}

}