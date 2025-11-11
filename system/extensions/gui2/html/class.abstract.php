<?php


abstract class Ext_Gui2_Html_Abstract implements Ext_Gui2_Html_Interface {

	protected $sStartTag		= ''; 
	protected $sEndTag			= '';
	public $bAllowReadOnly	= false;

	protected $aElements	= array();
	protected $aOptions		= array();

	protected $aMultipleValueOptions = array(
		'class'
	);

	public $bReadOnly			= null;
	public $bDisabledByReadonly	= true;

	protected $label = '';

	/**
	 * @param string $sOption
	 * @param mixed $mValue
	 */
	public function __set($sOption, $mValue) {

		if(empty($sOption)) {
			return;
		}

		$this->addAttributeValue($sOption, $mValue);

	}

	public function __get($sOption){
		return $this->aOptions[$sOption] ?? null;
	}

	/**
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function setDataAttribute($sName, $mValue) {
		if(!is_scalar($mValue)) {
			$mValue = htmlspecialchars(json_encode($mValue));
		}
		$this->__set('data-'.$sName, $mValue);
	}

	/**
	 * Entfernt einen Wert aus einem Attribut
	 * @author Mark Koopmann
	 * @param <type> $sAttribute
	 * @param <type> $sValue 
	 */
	public function removeAttributeValue($sAttribute, $sValue) {

		if(isset($this->aOptions[$sAttribute])) {
			$aValues = explode(' ', $this->aOptions[$sAttribute]);
			$iKey = array_search($sValue, $aValues);
			if($iKey !== false) {
				unset($aValues[$iKey]);
			}
			$this->aOptions[$sAttribute] = implode(' ', $aValues);

			if(empty($this->aOptions[$sAttribute])) {
				unset($this->aOptions[$sAttribute]);
			}
		}

	}

	public function addAttributeValue($sAttribute, $sValue) {

		if(!isset($this->aOptions[$sAttribute])) {
			$this->aOptions[$sAttribute] = '';
		}

		if(in_array($sAttribute, $this->aMultipleValueOptions) === true) {

			$aValues = explode(' ', $this->aOptions[$sAttribute]);

			$aValues[] = $sValue;

			$aValues = array_unique($aValues);
			$aValues = array_filter($aValues);

			$this->aOptions[$sAttribute] = implode(' ', $aValues);

		} else {
			
			// Felder, die es nicht mit leerem Wert geben darf
			if(
				in_array($sAttribute, ['id']) &&
				isset($this->aOptions[$sAttribute]) &&
				(
					$sValue === null ||
					$sValue === ''
				)
			) {
				unset($this->aOptions[$sAttribute]);
			} else {
				$this->aOptions[$sAttribute] = $sValue;	
			}
			
		}

	}

	/**
	 * Entfernt ein Attribut
	 *
	 * @param string $sAttribute
	 */
	public function removeAttribute($sAttribute) {

		if(isset($this->aOptions[$sAttribute])) {
			unset($this->aOptions[$sAttribute]);
		}

	}

	public function setElement($mElement){

		if(
			!$mElement instanceof Ext_Gui2_Html_Interface &&
			!is_string($mElement)
		) {
			throw new InvalidArgumentException('Please use Ext_Gui2_Html_Interface or string (type was '.gettype($mElement).')');
		}

		if($mElement instanceof Ext_Gui2_Html_Label) {
			$this->label = reset($mElement->aElements);
		} elseif(is_string($mElement)) {
			$this->label = $mElement;
		}
		
		$this->aElements[] = $mElement;

		return $this;
	}

	public function clearElements(){
		$this->aElements = array();
	}

	/**
	 * Liefert alle gesetzten Elemente
	 */
	public function getElements(){
		return $this->aElements;
	}

	/**
	 * Liefert alle gesetzten Elemente
	 */
	public function hasElements() {
		
		if(count($this->aElements) > 0) {
			return true;
		}
		
		return false;
	}

	/**
	 * Erzeugt HTML aus den gesetzten Elementen
	 *
	 * @param bool $bReadOnly
	 * @return string
	 */
	public function generateHTML($bReadOnly = false){

		$sHTML = '';
		
		if($this->bReadOnly !== null){
			$bReadOnly = $this->bReadOnly;
		}

		// TODO Das sollte generell entfernt werden (warum wird Boolean nicht direkt als Attribut gesetzt?)
		if(
			$this->bAllowReadOnly &&
			$bReadOnly
		){
			$this->addAttributeValue('readonly', "readonly");
			if($this->bDisabledByReadonly){
				$this->addAttributeValue('disabled', "disabled");
			}
			
			// Nur setzen wenn noch nicht vorhanden, ansonsten wird readonly zu oft gesetzt
			// TODO Klasse sollte mit der AdminLTE-Umstellung ohnehin nicht mehr benötigt werden
			if(strpos($this->class, 'readonly') === false) {
				$this->addAttributeValue('class', "readonly");
			}
		} elseif($this->bAllowReadOnly) {
			$this->removeAttribute('readonly');
			$this->removeAttribute('disabled');
			$this->removeAttributeValue('class', "readonly");
		}

		$sHTML .= $this->sStartTag;

		$this->addAttributes($sHTML);

		foreach((array)$this->aElements as $mElement){
			
			// Wenn das Value ein Object ist erzeuge zuerst das HTML
			if($mElement instanceof Ext_Gui2_Html_Interface){
				$sHTML .= $mElement->generateHTML($bReadOnly);
			} else if(is_string($mElement)){
				$sHTML .= $mElement;
			} else {
				throw new Exception("Please use Ext_Gui2_Html_Interface or String");
			}
			
		}

		$sHTML .= $this->sEndTag;

		return $sHTML;

	}

	protected function addAttributes(&$sHtml) {

		foreach($this->aOptions as $sOption=>$mValue) {

			// Wenn das Value ein Object ist erzeuge zuerst das HTML
			if($mValue instanceof Ext_Gui2_Html_Interface){
				$mValue = $mValue->generateHTML();
			}

			if(is_object($mValue)) {
				continue;
			}

			$sTemp = ' '.$sOption.'="'.$mValue.'"';

			if(strpos($sHtml, '/>') !== false){
				$sTemp .= '/>';
				// Setzte die Option ans ende des Tags
				$sHtml = str_replace('/>', $sTemp, $sHtml);

			} else {
				$sTemp .= '>';
				// Setzte die Option ans ende des Tags
				$sHtml = str_replace('>', $sTemp, $sHtml);
			}

		}
	}

	/**
	 * Rekursiv nach einem Element in aElements suchen
	 *
	 * Das übergebene Argument in der Closure kann alles mögliche sein!
	 *
	 * @param Closure $cFilterClosure
	 * @return Generator|self[]
	 */
	public function filterElements(\Closure $cFilterClosure) {

		$cFilter = function($mElement) use (&$cFilter, $cFilterClosure) {

			if($cFilterClosure($mElement)) {
				yield $mElement;
			}

			if($mElement instanceof self) {
				foreach($mElement->aElements as $oChildElement) {
					yield from $cFilter($oChildElement);
				}
			}

		};

		yield from $cFilter($this);

	}

	public function getLabel() {
		return $this->label;
	}
	
}
