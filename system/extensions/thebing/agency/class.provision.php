<?PHP

class Ext_Thebing_Agency_Provision extends Ext_Thebing_Basic {

	protected $idSaison;
	protected $oSchool;
	protected $agency_id;

	public function __construct($agency_id,$oSchool,$idSaison) {

		$this->idSaison = $idSaison;
		$this->oSchool = $oSchool;
		$this->agency_id = $agency_id;

	}

	public function getCourseProvision($iCourseId): ?\Ts\Dto\Commission {

		$oCourse = Ext_Thebing_Tuition_Course::getInstance($iCourseId);
		$oCategory = $oCourse->getCategory();

		$aData = array();
		$aData['type']			= 'course';
		$aData['type_id']		= $iCourseId;
		$aData['category_id']	= $oCategory->id;

		return $this->_getProvision($aData);

	}

	public function getActivityCommission(TsActivities\Entity\Activity $activity): ?\Ts\Dto\Commission {
		
		$aData = array();
		$aData['type']			= 'activity';
		$aData['type_id']		= $activity->id;

		return $this->_getProvision($aData);
	}
	
	public function getAccommodationProvision($iAccommdation,$iRoom,$iMeal): ?\Ts\Dto\Commission {

		$aData = array();
		$aData['type']			= 'accommodation';
		$aData['type_id']		= $iMeal;
		$aData['additional_id']	= $iRoom;
		$aData['category_id']	= $iAccommdation;

		return $this->_getProvision($aData);
	}

	public function getAdditionalProvision($iId, $iParentId = 0, $sType = ''): ?\Ts\Dto\Commission {

		$aData = array();
		$aData['type']			= 'additional_'.$sType;
		$aData['type_id']		= $iParentId;
		$aData['additional_id']	= 0;
		$aData['category_id']	= $iId;

		return $this->_getProvision($aData);
	}

	public function getExtraPositionProvision($iId): ?\Ts\Dto\Commission {

		$aData = array();
		$aData['type']			= 'extra_position';
		$aData['type_id']		= $iId;
		$aData['additional_id']	= 0;
		$aData['category_id']	= 0;

		return $this->_getProvision($aData);
	}

	public function getGeneralProvision($iId): ?\Ts\Dto\Commission {

		$aData = array();
		$aData['type']			= 'general';
		$aData['type_id']		= $iId;
		$aData['additional_id']	= 0;
		$aData['category_id']	= 0;

		return $this->_getProvision($aData);
	}

	public function getExtraNightProvision($iAccommdation, $iRoom, $iMeal): ?\Ts\Dto\Commission {

		$aData = array();
		$aData['type']			= 'extra_night';
		$aData['type_id']		= $iMeal;
		$aData['additional_id']	= $iRoom;
		$aData['category_id']	= $iAccommdation;

		return $this->_getProvision($aData);
	}

	public function getTransferProvision($oTransfer, $bTwoWay): ?\Ts\Dto\Commission {

		if(
			$oTransfer->transfer_type == 1 ||
			$oTransfer->transfer_type == 2 ||
			$bTwoWay
		) {

			// Anreise bzw. Abreise			
			if($bTwoWay){
				$iTypeId = 0;
			}else{
				$iTypeId = $oTransfer->transfer_type;
			}
			
			$aData = array();
			$aData['type']			= 'transfer';
			$aData['type_id']		= (int)$iTypeId;
			$aData['additional_id']	= 0;
			$aData['category_id']	= 0;

			return $this->_getProvision($aData);
		}

		return null;
	}

	protected function _getProvision($aData): ?\Ts\Dto\Commission {

		$sType			= $aData['type'];
		$iTypeId		= (int)$aData['type_id'];
		$iCategoryId	= (int)$aData['category_id'];
		$iAdditionalId	= (int)$aData['additional_id']; // room_id

		if($this->agency_id > 0) {
			
			$oAgency = Ext_Thebing_Agency::getInstance($this->agency_id);
			
			// Provisionsgruppen
			$aProvisionGroups = $oAgency->getProvisionGroups();

			/** @var \Ext_Thebing_Agency_Provision_Group $oGroup */
			$oGroup = NULL;
			// Prüfen ob eine Provisionsgruppe passt
			foreach((array)$aProvisionGroups as $provisionGroup) {

				$from = \Illuminate\Support\Carbon::parse($provisionGroup->valid_from)
					->startOfDay();

				$until = (
						$provisionGroup->valid_until && 
						$provisionGroup->valid_until !== '0000-00-00'
					)
					? \Illuminate\Support\Carbon::parse($provisionGroup->valid_until)->endOfDay()
					: null;

				$now = now();

				if(
					$from->lessThanOrEqualTo($now) &&
					(
						is_null($until) ||
						$until->greaterThanOrEqualTo($now)
					) &&
					(
						$provisionGroup->school_id === null ||
						$provisionGroup->school_id == $this->oSchool->id
					)
				) {
					$oGroup = $provisionGroup;
					break;
				}
			}

			// Wenn Gruppe gefunden
			if($oGroup) {

				$commissionCategory = $oGroup->getProvisionGroup();
				
				if($commissionCategory->old_structure) {

					// Provision suchen
					$where = '';
					
					$aSql = array();
					$aSql['group_id']		= (int)$oGroup->group_id;
					$aSql['school_id']		= (int)$this->oSchool->id;
					$aSql['season_id']		= (int)$this->idSaison;
					$aSql['type_id']		= (int)$iTypeId;
					$aSql['additional_id']	= (int)$iAdditionalId;
					$aSql['type']			= (string)$sType;
					// Bei alter Struktur und Kurs darf die Kategorie nicht mitabgefragt werden, da sie nicht immer korrekt ist und unnötig.
					if($aData['type'] !== 'course') {
						$aSql['category_id']	= (int)$iCategoryId;
						$where .= " AND
								`category_id` = :category_id ";
					}

					$sSql = "
							SELECT
								*
							FROM
								`ts_commission_categories_values_old`
							WHERE
								`active`		= 1 AND
								`group_id`		= :group_id AND
								`school_id`		= :school_id AND
								`season_id`		= :season_id AND
								`type_id`		= :type_id AND
								`additional_id`	= :additional_id AND
								`type`			= :type
								".$where."
							LIMIT 1
							";
					
					$aProvision = DB::getQueryRow($sSql, $aSql);

					if(is_array($aProvision)){
						return new \Ts\Dto\Commission((float)$aProvision['provision'], \Ts\Enums\CommissionType::PERCENT);
					}
					
				} else {

					$ratesIndex = $commissionCategory->getRatesIndex();
					
					$type = $parentType = null;
			
					$possibleParentType = null;
					
					$settingKey = null;
					// Ein Provisionssatz für diesen Typ
					$setting = 3;
					
					$typeId = $iTypeId;
					$parentTypeId = $iCategoryId;

					switch($sType) {
						case 'insurance':
							$type = 'insurance';
							$settingKey = 'commission_insurance';
							break;
						case 'general':
							$type = 'additional_general';
							$settingKey = 'commission_additional_general';
							$typeId = $iCategoryId;
							$parentTypeId = $iTypeId;
							break;
						case 'course':
							$type = 'course';
							$settingKey = 'commission_course';
							$possibleParentType = 'course_category';
							break;
						case 'activity':
							$type = 'activity';
							$settingKey = 'commission_activity';
							break;
						case 'accommodation':
							$type = 'accommodation';
							$settingKey = 'commission_accommodation';
							$possibleParentType = 'accommodation_category';
							break;
						case 'extra_night':
							$type = 'accommodation';
							$settingKey = 'commission_accommodation';
							break;
						case 'transfer':
							$type = 'insurance';
							
							break;
						case 'extra_position':
							$type = 'extra_position';
							
							break;
						case 'additional_course':
							$type = 'additional_course';
							$settingKey = 'commission_additional_course';
							$typeId = $iCategoryId;
							$parentTypeId = $iTypeId;
							break;
						case 'additional_accommodation':
							$type = 'additional_accommodation';
							$settingKey = 'commission_additional_accommodation';
							$typeId = $iCategoryId;
							$parentTypeId = $iTypeId;
							break;

					}

					if($settingKey) {
						$setting = $commissionCategory->$settingKey;
					}

					switch($setting) {
						case 1:
							$parentType = '';
							$parentTypeId = 0;
							break;
						case 2:
							$typeId = 0;
							$parentType = $possibleParentType;
							break;
						case 3:
							$typeId = 0;
							$parentType = '';
							$parentTypeId = 0;
							break;
					}

					if (isset($ratesIndex[$type][$typeId][$parentType][$parentTypeId])) {
						[$fProvision, $sProvisionType] = $ratesIndex[$type][$typeId][$parentType][$parentTypeId];

						return new \Ts\Dto\Commission(
							(float)$fProvision,
							\Ts\Enums\CommissionType::from($sProvisionType)
						);
					}

				}
				
			}
			
		}

		return null;
	}

}