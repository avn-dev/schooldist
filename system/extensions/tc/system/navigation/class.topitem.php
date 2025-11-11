<?php
class Ext_TC_System_Navigation_TopItem extends Ext_TC_System_Navigation_Item_Abstract {
	
	/**
	 * The Startstring for the L10N
	 * @var String
	 */
	protected $_sL10NStart = "Thebing Core » ";
	
	/**
	 *
	 * @var array[int]Ext_TC_System_Navigation_LeftItem
	 */
	protected $_aChilds = array();
	
	public $sId				= null;

	public $sName			= "";
	public $mAccess			= "";
	public $sTitle			= "";
	public $sL10NAddon		= "";
	public $iExtension		= 0;
	public $iLoadContent	= 0;
	public $iPosition		= 101;
	public $sAction			= '';
	public $sUrl = '';
	public $sIcon = null;
	public $sKey = "";
	public $sType = 'url';

	/**
	 * add a Child Navigation to Current Navigation
	 * @param Ext_TC_System_Navigation_LeftItem $oChild 
	 */
	public function addChild($oChild) {
		
		if($oChild instanceof Ext_TC_System_Navigation_LeftItem) {

			$oChild->setL10NStart($this->_sL10NStart);
			
			/**
			 * An dieser Stelle gibt es ein Problem: Wenn der obere Menüpunkt noch die Rechte
			 * der Untermenüpunkte hat, dann wird dieser eingeblendet, auch wenn der User 
			 * das eigentliche Recht des oberen Menüpunktes gar nicht besitzt
			 */
			if($this->mAccess == '') {
				if(is_array($this->mAccess)){
					if(
						(
							is_string($oChild->mAccess) &&
							!in_array($oChild->mAccess, $this->mAccess)
						) ||
						!is_string($oChild->mAccess)
					){
						$this->mAccess[] = $oChild->mAccess;
					}
				} else {
					$this->mAccess = array($this->mAccess, $oChild->mAccess);
				}

				foreach((array)$this->mAccess as $iKey => $mAccess){
					if(empty($mAccess)){
						unset($this->mAccess[$iKey]);
					}
				}
			}
			
			$this->_aChilds[] = $oChild;
		} else {
			throw new Exception('You need a Ext_TC_System_Navigation_LeftItem Object as Child for Ext_TC_System_Navigation_TopItem');
		}
		
	}
	
	/**
	 * get the Navigation Infos as Array for CMS Navigation
	 * @return array 
	 */
	public function getArray(){

		// TODO id, name, key...auf eins reduzieren
		$aBack = array();
		$aBack['id']			= $this->sId;
		$aBack['name']			= $this->sName;
		$aBack['right']			= $this->mAccess;
		$aBack['title']			= $this->generateTitle();
		$aBack['extension']		= $this->iExtension;
		$aBack['load_content']	= $this->iLoadContent;
		$aBack['action']		= $this->sAction;
		$aBack['url'] = $this->sUrl;
		$aBack['icon'] = $this->sIcon;
		$aBack['type'] = $this->sType;
		$aBack['key'] = !empty($this->sKey) ? $this->sKey : $this->sName;

		return $aBack;
	}
	
	/**
	 * Return a List of Child Objects
	 * @return Ext_TC_System_Navigation_LeftItem[]
	 */
	public function getChilds(){
		return $this->_aChilds;
	}
	
	public function setChilds(array $aChilds) {
		$this->_aChilds = $aChilds;
	}
	
	
	/**
	 * Return all Child Elements of the Navigation
	 * @return Array $aBack
	 */
	public function getChildArray(){
		$aBack = array();
		foreach((array)$this->_aChilds as $oChild){
			$aBack[] = $oChild->getArray();
		}
		return $aBack;
	}
}
