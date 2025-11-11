<?php

/**
 * @property $id
 * @property $journey_id
 * @property $traveller_id
 * @property $type
 * @property $value
 */
class Ext_TS_Inquiry_Journey_Traveller extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'ts_journeys_travellers_detail';

	protected $_aFormat = array( 
								'journey_id' => array(
									'required'=>true,
									'validate'=>'INT_POSITIVE',
									'not_changeable' => true
									),
								'traveller_id' => array(
									'required'=>true,
									'validate'=>'INT_POSITIVE',
									'not_changeable' => true // @TODO Vielleicht muss das wegen R-#5047 entfernt werden?
									),
								'type' => array(
									'required'=>true,
									)
							);
	
	protected $_aJoinedObjects = array(
		'journey'			=> array(
			'class'				=> 'Ext_TS_Inquiry_Journey',
			'key'				=> 'journey_id',
		),
	);

	public static $aCache = array();
    
    public $bChange = false;


    /**
	 * Festhalten ob durch Änderung eines Flags Rechnungspositionen aktualisiert werden müssen
	 *  
	 * @var type 
	 */
	protected $_aItemChanges = array();

	public static function getDetailByJourneyAndTraveller($iJourneyId, $iTravellerId, $sTypeValue){
		
		$iJourneyId		= (int)$iJourneyId;
		$iTravellerId	= (int)$iTravellerId;
		$sTypeValue		= (string)$sTypeValue;

		if(
			empty($iJourneyId) ||
			empty($iTravellerId) ||
			empty($sTypeValue)
		){
			return null;
		}

		$sCacheKey = $iJourneyId.'_'.$iTravellerId.'_'.$sTypeValue;

		if(
			isset(self::$aCache[$sCacheKey])
		)
		{
			return self::$aCache[$sCacheKey];
		}

		$oSelf = new self;

		$sSql = "
			SELECT
				`value`
			FROM
				#table
			WHERE
				`journey_id` = :journey_id AND
				`traveller_id` = :traveller_id AND
				`type` = :type
		";

		$aSql = array(
			'table'			=> $oSelf->_sTable,
			'journey_id'	=> $iJourneyId,
			'traveller_id'	=> $iTravellerId,
			'type'			=> $sTypeValue
		);

		$mValue = (string)DB::getQueryOne($sSql,$aSql);

		self::$aCache[$sCacheKey] = $mValue;

		return $mValue;
	}
	
	public function save($bLog = true)
	{		
		$this->_checkItemChanges();
		
		$mReturn = parent::save($bLog);
		
		$this->_saveItemChanges();

		return $mReturn;
	}
	
	protected function _checkItemChanges()
	{
		$this->_aItemChanges = array();
		
		$sType		= $this->type;
		
        $this->bChange = $this->isChanged('value');

        if($this->bChange)
        {
            $oJourney	= $this->getJourney();

            $oInquiry	= $oJourney->getInquiry();

            $oLastDoc	= $oInquiry->getLastDocument('invoice_without_storno');

            if($oLastDoc)
            {
                $oLastVersion = $oLastDoc->getLastVersion();

                if(
                    is_object($oLastVersion) &&
                    $oLastVersion instanceof Ext_Thebing_Inquiry_Document_Version
                )
                {
                    $aItems = $oLastVersion->getItemObjects(true);

                    $this->_searchInItems($aItems, $sType, $oLastDoc, $oInquiry);
                }
            }
        }
	}
	
	protected function _searchInItems(array $aItems, $sType, Ext_Thebing_Inquiry_Document $oDoc, Ext_TS_Inquiry $oInquiry)
	{
		if($sType == 'free_all' || $sType == 'guide')
		{
			$aTypes = array(
				'free_accommodation',
				'free_accommodation_fee',
				'free_course',
				'free_course_fee',
				'free_transfer',
			);
			
			foreach($aTypes as $sType)
			{
				$this->_addItemChanges($aItems, $sType, $oDoc, $oInquiry);
			}
			
			return true;
		}
		
		$this->_addItemChanges($aItems, $sType, $oDoc, $oInquiry);
	}
	
	protected function _addItemChanges(array $aItems, $sType, Ext_Thebing_Inquiry_Document $oDoc, Ext_TS_Inquiry $oInquiry)
	{
		$sTypeInDocument = $this->_getTypeInDocument($sType);
		
		foreach($aItems as $oItem)
		{
			if($oItem->type == $sTypeInDocument)
			{                
				$this->_aItemChanges[] = array(
					'type_id'		=> $oItem->type_id,
					'type'			=> $oItem->type,
					'parent_id'		=> $oItem->parent_booking_id,
					'inquiry_id'	=> $oInquiry->id,
					'document_id'	=> $oDoc->id,
					'status'		=> 'edit',
				);
			}
		}
	}


	protected function _getTypeInDocument($sType)
	{
		switch($sType)
		{
			case 'free_accommodation':
				$sTypeInDocument = 'accommodation';
				break;
			case 'free_accommodation_fee':
				$sTypeInDocument = 'additional_accommodation';
				break;
			case 'free_course':
				$sTypeInDocument = 'course';
				break;
			case 'free_course_fee':
				$sTypeInDocument = 'additional_course';
				break;
			case 'free_transfer':
				$sTypeInDocument = 'transfer';
				break;
			default:
				throw new Exception('"' . $sType . '" is not supported as journey traveller detail!');
		}
		
		return $sTypeInDocument;
	}


	/**
	 * 
	 * @return Ext_TS_Inquiry_Journey
	 */
	public function getJourney()
	{
		$oJourney = $this->getJoinedObject('journey');
		
		return $oJourney;
	}
	
	/**
	 * Gefundene Änderungen abspeichern in der item_changes Tabelle
	 */
	protected function _saveItemChanges()
	{
		foreach($this->_aItemChanges as $aChangeData)
		{
			Ext_Thebing_Inquiry_Document_Version::setChange(
					$aChangeData['inquiry_id'], 
					$aChangeData['type_id'], 
					$aChangeData['type'], 
					$aChangeData['status'],
					$aChangeData['parent_id']
			);
		}
	}
}