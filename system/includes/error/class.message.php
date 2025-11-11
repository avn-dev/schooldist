<?php

/**
 * Klasse stellt eine Fehlermeldung dar 
 */
class Error_Message {
	
	/**
	 * Die Fehlermeldung
	 * @var string 
	 */
	protected $_sMessage;
	
	/**
	 * Pfad für L10N
	 * @var string 
	 */
	protected $_sL10NPath = 'Thebing » Errors';
	
	/**
	 * Variablen für die Platzhalter
	 * @var array 
	 */
	protected $_aVariables;
	
	/**
	 * Information zu einem Eingabefeld, falls sich der Fehler auf eins bezieht
	 * array(
	 *	'dbcolumn'=>
	 *	'dbalias'=>
	 *	'id'=> (alternativ)
	 * )
	 * @var array
	 */
	protected $_aInput;
	
	/**
	 * Fehlertyp error/hint
	 * 
	 * @var string 
	 */
	protected $_sType = 'error';
	
	/**
	 * Gibt an wie Platzhalter in der Message ersetzt werden können
	 * @var string (sprintf/associative)
	 */
	protected $_sVariableMode = 'sprintf';
	
	/**
	 * Generiert ein Array für die Verwendung in der gui2.js
	 * 
	 * @param string $sDefaultL10NPath 
	 */
	public function generateGuiArray($sL10NPath=null) {		
		
		$sErrorMessage = $this->getMessage(true, $sL10NPath);

		$aError = array(
			'message' => $sErrorMessage,
			'type' => $this->_sType
		);
		
		// Input hinzufügen, falls vorhanden
		if(!empty($this->_aInput)) {
			$aError['input'] = $this->_aInput;
		}

		return $aError;
		
	}
	
	/**
	 * liefert die Fehlermeldung
	 * @param boolean $bReplaceVariables
	 * @return string
	 */
	public function getMessage($bReplaceVariables = true, $sL10NPath = null) {
		$sMessage = $this->_sMessage;
		
		if($sL10NPath === null) {
			$sL10NPath = $this->_sL10NPath;
		}

		// Nachricht übersetzen
		$sMessage = L10N::t($sMessage, $sL10NPath);

		// Platzhalter ersetzen
		if(
			$bReplaceVariables &&
			!empty($this->_aVariables)
		) {
			
			if($this->_sVariableMode == 'sprintf') {
				
				$aParameters = array($sMessage);
				$aParameters = array_merge($aParameters, $this->_aVariables);

				$sMessage = call_user_func_array("sprintf", $aParameters);
				
			} else {
				
				foreach($this->_aVariables as $sKey=>$sValue) {

					$sMessage = str_replace('{'.$sKey.'}', $sValue, $sMessage);

				}
				
			}

		}
		
		return $sMessage;
	}
	
	/**
	 * Setz den L10N-Pfad
	 * @param string $sL10NPath 
	 */
	public function setL10NPath($sL10NPath) {
		$this->_sL10NPath = $sL10NPath;
	}
	
	/**
	 * Setzt die unformatierte Message (mit Platzhaltern)
	 * 
	 * @param string $sMessage 
	 */
	public function setMessage($sMessage) {

		if(
			!is_string($sMessage)
		) {
			throw new Exception('Only strings are allowed as error message!');
		}
		
		if(
			empty($sMessage)
		) {
			throw new Exception('Empty error messages are not allowed!');
		}
		
		$this->_sMessage = (string)$sMessage;

	}
	
	/**
	 * Setzt Variablen für die Ersetzung von Platzhaltern in der Message
	 * 
	 * Mögliche Parameter:
	 * Einzelner String: 'test'
	 * Mehrere Strings: 'test1', 'test2'
	 * Array von Strings: array('test1', 'test2')
	 * Assoziatives Array: array('test1'=>'Test 1', 'test2'=>'Test 2')
	 * 
	 * @throws Exception 
	 */
	public function setVariables() {
		
		$this->_aVariables = array();
		
		$aArguments = func_get_args();
		
		if(count($aArguments) > 1) {
			foreach($aArguments as $mArgument) {
			
				if(!is_scalar($mArgument)) {
					throw new Exception('Only scalar values are allowed if you pass more than one argument!');
				}
				
				$this->_aVariables[] = $mArgument;

			}
			
			$this->_sVariableMode = 'sprintf';
			
		} else {
			
			$mArgument = reset($aArguments);
			
			if(is_scalar($mArgument)) {
				
				$this->_aVariables[] = $mArgument;
				
				$this->_sVariableMode = 'sprintf';
				
			} else {

				$mFirstArgument = reset($mArgument);
				$mFirstKey = key($mArgument);

				if(is_numeric($mFirstKey)) {
					$this->_aVariables = $mArgument;
				
					$this->_sVariableMode = 'sprintf';
				} elseif(is_string($mFirstKey)) {
					$this->_aVariables = $mArgument;
				
					$this->_sVariableMode = 'associative';
				} else {
					throw new Exception('Multidimensional arrays are not allowed!');
				}
				
			}
			
		}

	}
	
}