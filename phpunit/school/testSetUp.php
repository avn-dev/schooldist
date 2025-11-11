<?php

include_once \Util::getDocumentRoot().'phpunit/core/testSetup.php';

/**
 * Dieses Setup kopiert die Datenbank und erstellt ein paar Standarddaten wie z.B. Client,
 * außerdem werden hier viele Methoden bereitgestellt um Objekte zu erzeugen
 * 
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class schoolTestSetup extends coreTestSetup
{	
	/**
	 *
	 * @var Ext_Thebing_Client
	 */
	protected $_oClient;
	
	/**
	 *
	 * @var Ext_Thebing_Currency
	 */
	protected $_oCurrency;

	/**
	 * Schuldatenbank kopieren mit leeren Daten und ein paar Daten wie client etc füllen
	 */
	public function setUp()
    {
		global $db_data;

		//Lokale Connection aufbauen
		try
		{
			$oConnectionLocal = DB::getConnection('local');
		}
		catch(DB_QueryFailedException $e)
		{
			//Lokale connection existiert nicht, aufbauen
			$oConnectionLocal = DB::createConnection('local', $db_data['host'], $db_data['username'], $db_data['password'], 'ts_local');
		}
		catch(Exception $e)
		{
			//Lokale connection existiert nicht, aufbauen
			$oConnectionLocal = DB::createConnection('local', $db_data['host'], $db_data['username'], $db_data['password'], 'ts_local');
		}

		$aSql = array(
			'unittest_db' => $db_data['system'],
		);
		
		//Unit-test DB löschen
		$sSql = "DROP DATABASE #unittest_db";
		
		try 
		{
			DB::executePreparedQuery($sSql, $aSql);
		}
		catch(Exception $e)
		{ 
			__pout($e);
		}
		
		//Unit-test DB erstellen
		$sSql = "CREATE DATABASE #unittest_db";
		DB::executePreparedQuery($sSql, $aSql);

		//Datenbank nach drop wieder selektieren, da sonst nachfolgende queries nicht mehr funktionieren würden
		$sSql = "USE #unittest_db";
		DB::executePreparedQuery($sSql, $aSql);
		
		//Alle Tabellen der lokalen Datenbank durchgehen
		$sSql = 'SHOW TABLES FROM ts_local';

		$aTables = DB::getQueryRows($sSql);
		
		$aFailed = array();

		foreach($aTables as $aTable)
		{
			$sTable = reset($aTable);
			
			//Tabelle löschen aus der lokalen Datenbank
			try
			{				
				$sSql = "
					SHOW CREATE TABLE
						".$sTable."
				";

				//Create Befehl aus der lokalen Datenbank bekommen
				$aCreate	= $oConnectionLocal->queryRow($sSql);

				$sCreate	= $aCreate['Create Table'];
				
				//Create Table Befehl ausführen
				$rRes = DB::executeQuery($sCreate);
			
			}
			catch(DB_QueryFailedException $e)
			{
				__pout($e); 
			}
			catch(Exception $e)
			{
				__pout($e);
			}
		}

		//Client anlegen
		$aData = array(
			'name'		=> 'TestClient Unittest',
		);
		
		$oClient = $this->_createWDBasicObject('Ext_Thebing_Client', $aData);
		/* @var $oClient Ext_Thebing_Client */
		
		$oLicense = new stdClass();
		$oLicense->included_schools = 1;

		//Lizenz faken
		$oClient->setLicense($oLicense);

		$this->_oClient = $oClient;
		
		//Währung anlegen
		$aData = array(
			'name'			=> 'Euro',
			'iso4217'		=> 'EUR',
			'iso4217_num'	=> '978',
			'sign'			=> '€',
		);
		
		$this->_oCurrency = $this->_createWDBasicObject('Ext_Thebing_Currency', $aData);
		
		//Instance caches müssen unbedingt geleert werden, darum auch parent setup aufrufen
		parent::setUp();

	}
	
	/**
	 * Schule nach Preis-Struktur generieren
	 * 
	 * @param string $sPriceStructure
	 * @param array $aData
	 * @param bool $bSave
	 * @return Ext_Thebing_School 
	 */
	protected function _createDefaultSchoolByPriceStructrue($sPriceStructure, array $aData=array())
	{
		if($sPriceStructure == 'week'){
			
			//Preis pro Woche
			$iPriceStructureWeek = 1;
			
		}elseif($sPriceStructure == 'normal'){
			
			//Normale Preisstruktur
			$iPriceStructureWeek = 0;
			
		}else{
			Throw new Exception('Invalid Price Structure ' . $sPriceStructure);
		}
		
		$aData['price_structure_week'] = $iPriceStructureWeek;
		
		$oSchool = $this->_createDefaultSchool($aData);
		
		return $oSchool;
	}
	
	/**
	 * Standard Schule erstellen befüllt mit client_id,email,währung & name
	 * 
	 * @param array $aData
	 * @return Ext_Thebing_School
	 */
	protected function _createDefaultSchool(array $aData=array())
	{
		//Pflichtfelder befüllen wenn nicht definiert
		if(!isset($aData['ext_1']))
		{
			$aData['ext_1'] = 'Default School';
		}
		
		if(!isset($aData['idClient']))
		{
			$aData['idClient'] = $this->_oClient->id;
		}
		
		if(!isset($aData['email']))
		{
			$aData['email'] = 'defaultschool@mail.de';
		}
		
		$aData['client'] = $this->_oClient;
		
		$aData['aCurrencies'] = array($this->_oCurrency->id);
		
		$aData['currency'] = $this->_oCurrency->id;
		
		$oSchool = $this->_createWDBasicObject('Ext_Thebing_School', $aData);
		
		return $oSchool;
	}
	
	/**
	 *
	 * @param Ext_TS_School $oSchool
	 * @return Ext_TS_Inquiry 
	 */
	protected function _createDefaultInquiry(Ext_Thebing_School $oSchool)
	{
		//Pflichtfelder befüllen
		$aData = array(
			'currency_id'		=> $this->_oCurrency->id,
			'payment_method'	=> 0,
		);

		$oInquiry	= $this->_createWDBasicObject('Ext_TS_Inquiry', $aData);

		//Bei der getJourney Methode wird die InquiryId schon befüllt, falls Objekt noch nicht vorhanden ist
		$oJourney					= $oInquiry->getJourney();
		//In der Schulsoftware, hat jede Schule nur eine Produktlinie im Moment
		$oJourney->productline_id	= $oSchool->getProductLineId();
		//Schule setzen
		$oJourney->school_id		= $oSchool->id;
		
		$mValidate = $oJourney->validate(true);
		
		if($mValidate === true)
		{
			//Journey abspeichern
			$oJourney->save(false);
		}
		
		return $oInquiry;
	}
	
	/**
	 *
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param array $aData
	 * @return Ext_TS_Inquiry_Journey_Accommodation
	 */
	protected function _createJourneyAccommodation(Ext_TS_Inquiry $oInquiry, array $aData)
	{
		$oJourney				= $oInquiry->getJourney();
		
		$aData['journey_id']	= $oJourney->id;
		
		$oJourneyAccommodation	= $this->_createWDBasicObject('Ext_TS_Inquiry_Journey_Accommodation', $aData);
		
		return $oJourneyAccommodation;
	}
	
	/**
	 *
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param array $aData
	 * @return Ext_TS_Inquiry_Journey_Accommodation
	 */
	protected function _createJourneyCourse(Ext_TS_Inquiry $oInquiry, array $aData)
	{
		$oJourney				= $oInquiry->getJourney();
		
		$aData['journey_id']	= $oJourney->id;
		
		$oJourneyCourse	= $this->_createWDBasicObject('Ext_TS_Inquiry_Journey_Course', $aData);
		
		return $oJourneyCourse;
	}
	
	/**
	 * Preiswoche erstellen
	 * 
	 * @param Ext_Thebing_School $oSchool
	 * @param array $aData
	 * @return Ext_Thebing_School_Week
	 */
	protected function _createWeek(Ext_Thebing_School $oSchool, array $aData)
	{
		$aData['idClient']	= $this->_oClient->id;
		
		$aData['idSchool']	= $oSchool->id;
		
		if(
			!isset($aData['title']) && 
			isset($aData['start_week'])
		)
		{
			$aData['title'] = 'Week ' . $aData['start_week'];
			
			if(isset($aData['week']))
			{
				$iWeekUntil = $aData['start_week'] + $aData['week'];
				
				$aData['title'] .= ' - ' . $iWeekUntil;
			}
		}
		
		$oWeek = $this->_createWDBasicObject('Ext_Thebing_School_Week', $aData);
		
		return $oWeek;
	}
	
	/**
	 * 
	 * @var Ext_Thebing_School $oSchool
	 * 
	 * @return Ext_Thebing_Accommodation_Category
	 */
	protected function _createDefaultAccommodationCategory(Ext_Thebing_School $oSchool, array $aData=array())
	{
		//Alle Wochen die vorhanden sind
		if(!isset($aData['weeks']))
		{
			$aWeeks				= (array)$oSchool->getWeekList(true);
			$aData['weeks']		= array_keys($aWeeks);
		}
		
		$aData['school_id'] = $oSchool->id;
		$aData['type_id']	= 0;
		$aData['name_en']	= 'Default Accommodation Category';
		$aData['short_en']	= 'DAC';
		$aData['idClient']	= $this->_oClient->id;
		
		$oAccommodationCategory = $this->_createWDBasicObject('Ext_Thebing_Accommodation_Category', $aData);
		
		return $oAccommodationCategory;
	}
	
	/**
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @return Ext_Thebing_Accommodation_Roomtype
	 */
	protected function _createDefaultRoomType(Ext_Thebing_School $oSchool)
	{
		$aData = array(
			'school_id' => $oSchool->id,
			'name_en'	=> 'Default RoomType',
			'short_en'	=> 'DR',
			'idClient'	=> $this->_oClient->id,
		);
		
		$oRoomType = $this->_createWDBasicObject('Ext_Thebing_Accommodation_Roomtype', $aData);
		
		return $oRoomType;
	}
	
	/**
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @return Ext_Thebing_Accommodation_Meal
	 */
	protected function _createDefaultMeal(Ext_Thebing_School $oSchool)
	{
		$aData = array(
			'school_id' => $oSchool->id,
			'name_en'	=> 'Default Meal',
			'short_en'	=> 'DM',
			'idClient'	=> $this->_oClient->id,
		);
		
		$oMeal = $this->_createWDBasicObject('Ext_Thebing_Accommodation_Meal', $aData);
		
		return $oMeal;
	}
	
	/**
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @param array $aData
	 * @return Ext_Thebing_Marketing_Saison
	 */
	protected function _createSeason(Ext_Thebing_School $oSchool, array $aData)
	{
		if(
			!isset($aData['title_en']) &&
			isset($aData['valid_from']) &&
			isset($aData['valid_until'])
		)
		{
			$aData['title_en'] = 'Season ' . $aData['valid_from'] . ' / ' .$aData['valid_until'];
		}
		
		$aData['idPartnerschool']	= $oSchool->id;
		
		$aData['idClient']			= $this->_oClient->id;
		
		$oSaison = $this->_createWDBasicObject('Ext_Thebing_Marketing_Saison', $aData);
		
		return $oSaison;
	}
	
	/**
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_Thebing_Marketing_Saison $oSeason
	 * @param array $aData
	 * @return schoolTestSetup 
	 */
	protected function _createPrice(Ext_Thebing_School $oSchool, Ext_Thebing_Marketing_Saison $oSeason, array $aData)
	{
		if(
			!isset($aData['week_id']) || 
			!isset($aData['parent_type']) ||
			!isset($aData['value'])
		)
		{
			Throw new Exception('Missing information, you have to define week_id,parent_type and value!');
		}
		
		$aInsert = array(
			'idClient'		=> $this->_oClient->id,
			'idSchool'		=> $oSchool->id,
			'idSaison'		=> $oSeason->id,
			'idCurrency'	=> $oSchool->getCurrency(),
			'idWeek'		=> $aData['week_id'],
			'typeParent'	=> $aData['parent_type'],
			'value'			=> $aData['value'],
		);
		
		$rRes = DB::insertData('kolumbus_prices_new', $aInsert);
		
		if(!$rRes)
		{
			Throw new Exception('Insert for Price failed: ' . print_r($aInsert, 1));
		}
		
		return $this;
	}

	/**
	 * Ein WDBasic Objekt erzeugen
	 * 
	 * @param string $sModel | Name der WDBasic Klasse
	 * @param array $aData | Array an Daten die befüllt werden sollen
	 * @param bool $bSave | wenn auf true steht, wird das Objekt in der Datenbank gespeichert
	 * 
	 * @return WDBasic 
	 */
	protected function _createWDBasicObject($sModel, array $aData)
	{
		if(class_exists($sModel))
		{			
			//0 wurde hier bewusst übergeben, weil das Schulobjekt bei keinem Parameter aus der Instance das Objekt holt
			$oModel = new $sModel(0);
			
			if($oModel instanceof WDBasic)
			{
				foreach($aData as $sKey => $mValue)
				{
					//Ins Objekt setzen, die WDBasic überprüft schon im setter ob gesetzt werden kann
					$oModel->$sKey = $mValue;
				}
				
				//Immer aktiv setzen wenn vorhanden
				$aFields = $oModel->getArray();
				
				if(isset($aFields['active']))
				{
					$oModel->active = 1;
				}
				
				// Falls validate nicht klappt, Exceptions schmeißen
				$mValidate = $oModel->validate(true);

				if($mValidate === true)
				{
					//nicht loggen, da nicht nötig
					$oModel->save(false);
				}
				else
				{
					__out($mValidate); 
				}
				
				return $oModel;
			}
			else
			{
				Throw new Exception('Object '.get_class($oModel).' is not an isntanceof WDBasic!');
			}
				
		} else {
			Throw new Exception('Class '.$sModel.' not found!');
		}

	}

}