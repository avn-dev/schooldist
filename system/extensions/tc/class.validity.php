<?php

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Spatie\Period\Period;

abstract class Ext_TC_Validity extends Ext_TC_Basic {

	public $sParentColumn	= 'parent_id';
	public $sItemColumn		= 'item_id';
	public $sDependencyColumn = '';
	public $bCheckItemId	= true;

	protected $_sTable = 'tc_validity';

	protected $_bSkipValidateChecks = false;

	/**
	 * @param bool $bIncludeSelf
	 * @param bool $bWithEndDate
	 * @param int $iDependencyId
	 * @return array
	 */
	public function getLatestEntry($bIncludeSelf = false, $bWithEndDate = false, $iDependencyId = null) {
		$sWhere = '';

		if(!$bIncludeSelf) {
			$sWhere .= " AND `".$this->_sTableAlias."`.`id` != :id";
		}

		if($bWithEndDate === false) {
			$sWhere .= " AND `".$this->_sTableAlias."`.`valid_until` = '0000-00-00'";
		} elseif($bWithEndDate === true) {
			$sWhere .= " AND `".$this->_sTableAlias."`.`valid_until` != '0000-00-00'";
		}

		$sParentColumn	= $this->sParentColumn;
	
		$aSql = array(
			'table'			=> $this->_sTable,
			'id'			=> $this->id,
			'parent_column'	=> $sParentColumn,
			'parent_id'		=> (int) $this->$sParentColumn
		);

		$sFrom = '';
		
		$this->manipulateFromPart($sFrom);
		
		$sSql = "
			SELECT
				`".$this->_sTableAlias."`.`id`
			FROM
				#table `".$this->_sTableAlias."`
				".$sFrom."
			WHERE
				`".$this->_sTableAlias."`.#parent_column = :parent_id 
		";
	
		if($this->bCheckItemId === true) {
			$sSql .= " AND `".$this->_sTableAlias."`.#item_column = :item_id ";
			
			$sItemColumn			= $this->sItemColumn;
			$aSql['item_column']	= $sItemColumn;
			$aSql['item_id']		= (int)$this->$sItemColumn;
		}

		if(!empty($this->sDependencyColumn)) {
			$sSql .= " AND `{$this->_sTableAlias}`.`{$this->sDependencyColumn}` = :dependency_id ";
			$aSql['dependency_id'] = $iDependencyId;
		}

		if($this->hasActiveField()) {
			$sWhere .= " AND `".$this->_sTableAlias."`.`active` = 1 ";
		}

		$this->manipulateWherePart($sWhere);
		
		$sSql .= " 
			".$sWhere."
			ORDER BY
				`".$this->_sTableAlias."`.`valid_from` DESC
			LIMIT 1 
		";
		
		$iLastEntry = DB::getQueryOne($sSql, $aSql);

		return $iLastEntry;
	}

	/**
	 * 
	 * @param boolean $bIncludeSelf
	 * @param boolean $bWithEndDate
	 * @return int 
	 */
	public function getFirstEntry($bIncludeSelf = false, $bWithEndDate = false) {
		$sWhere = '';
		
		if(!$bIncludeSelf) {
			$sWhere .= " AND `".$this->_sTableAlias."`.`id` != :id";
		}

		if($bWithEndDate === false) {
			$sWhere .= " AND `".$this->_sTableAlias."`.`valid_until` = '0000-00-00'";
		} elseif($bWithEndDate === true) {
			$sWhere .= " AND `".$this->_sTableAlias."`.`valid_until` != '0000-00-00'";
		}

		$sParentColumn	= $this->sParentColumn;
	
		$aSql = array(
			'table'			=> $this->_sTable,
			'id'			=> $this->id,
			'parent_column'	=> $sParentColumn,
			'parent_id'		=> (int) $this->$sParentColumn
		);

		$sFrom = '';
		
		$this->manipulateFromPart($sFrom);
		
		$sSql = "
			SELECT
				`".$this->_sTableAlias."`.`id`
			FROM
				#table `".$this->_sTableAlias."`
				".$sFrom."
			WHERE
				`".$this->_sTableAlias."`.#parent_column	= :parent_id 
		";

		if($this->bCheckItemId === true) {
			$sSql .= " AND `".$this->_sTableAlias."`.#item_column	= :item_id ";
			
			$sItemColumn			= $this->sItemColumn;
			$aSql['item_column']	= $sItemColumn;
			$aSql['item_id']		= (int)$this->$sItemColumn;
		}

		if($this->hasActiveField()) {
			$sWhere .= " AND `".$this->_sTableAlias."`.`active` = 1 ";
		}

		$this->manipulateWherePart($sWhere);
		
		$sSql .= " 
			".$sWhere."
			ORDER BY
				`".$this->_sTableAlias."`.`valid_from` ASC
			LIMIT 1 
		";

		$iFirstEntry = DB::getQueryOne($sSql, $aSql);

		return $iFirstEntry;
	}	
	
	/**
	 * 
	 * @param boolean $bIncludeSelf
	 * @param boolean $bWithEndDate
	 * @return array 
	 */
	public function getEntries($bIncludeSelf = false, $bWithEndDate = false) {
		$sWhere = '';

		if(!$bIncludeSelf) {
			$sWhere .= " AND `".$this->_sTableAlias."`.`id` != :id";
		}

		$sParentColumn	= $this->sParentColumn;
	
		$aSql = array(
			'table'			=> $this->_sTable,
			'id'			=> $this->id,
			'parent_column'	=> $this->sParentColumn,
			'parent_id'		=> (int) $this->$sParentColumn
		);

		$sFrom = '';
		
		$this->manipulateFromPart($sFrom);
		
		$sSql = "
			SELECT
				`".$this->_sTableAlias."`.`id`
			FROM
				#table `".$this->_sTableAlias."`
				".$sFrom."
			WHERE
				`".$this->_sTableAlias."`.#parent_column	= :parent_id 
		";

		if($this->bCheckItemId === true) {
			$sSql .= " AND `".$this->_sTableAlias."`.#item_column	= :item_id ";
			
			$sItemColumn			= $this->sItemColumn;
			$aSql['item_column']	= $sItemColumn;
			$aSql['item_id']		= (int)$this->$sItemColumn;			
		}

		if($this->hasActiveField()) {
			$sWhere .= " AND `".$this->_sTableAlias."`.`active` = 1 ";
		}

		$this->manipulateWherePart($sWhere);
		
		$sSql .= " 
			".$sWhere."
			ORDER BY
				`".$this->_sTableAlias."`.`valid_from` DESC 
		";
		
		$aEntries = DB::getQueryCol($sSql, $aSql);

		return (array)$aEntries;
	}
	
	protected function manipulateFromPart(&$sFromPart) {}
	
	protected function manipulateWherePart(&$sWherePart) {}
	
	/**
	 * 
	 * @param boolean $bThrowExceptions
	 * @return boolean|string 
	 */
	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);

		if($this->_bSkipValidateChecks === false) {
		
			if($aErrors === true) {

				$aErrors = array();

				$oHelper = new Ext_TC_Validity_Helper_Error($this);			
				$aErrors = $oHelper->validate();
				
				if(empty($aErrors)) {
					return true;
				}
			}
		
		}

		return $aErrors;
	}

	/**
	 * @param boolean $bLog
	 * @return Ext_TC_Validity 
	 */
	public function save($bLog = true) {

		parent::save();

		if(
			$this->_bSkipValidateChecks === false &&
			$this->id > 0
		) {

			$iDependencyId = null;
			if(!empty($this->sDependencyColumn)) {
				$iDependencyId = $this->{$this->sDependencyColumn};
			}

			// Gültig bis Datum bei dem letzten Eintrag anpassen
			$iLastEntry = $this->getLatestEntry(false, false, $iDependencyId);

			if($iLastEntry > 0) {
				
				$oDate = new WDDate($this->valid_from, WDDate::DB_DATE);
				$oDate->sub(1, WDDate::DAY);

				$oLastEntry = static::getInstance($iLastEntry);
				
				/**
				 * Datum nur setzen wenn letzter Eintrag vor dem aktuellen liegt
				 * und valid_until des letzten Eintrages  = 0 ist
				 */				
				$iCompare = $oDate->compare($oLastEntry->valid_from, WDDate::DB_DATE);

				if(
					$iCompare >= 0 &&
					$oLastEntry->valid_until == '0000-00-00'
				) {
					$oLastEntry->valid_until = $oDate->get(WDDate::DB_DATE);
					$oLastEntry->save();
				}
			}

		}
		
		return $this;
	}

	/**
	 * löscht einen Eintrag
	 * @return array|boolean 
	 */
	public function delete() {

		// Letzten Eintrag inklusive dem aktuellen
		$iLastEntryCheck = (int)$this->getLatestEntry(true, null);
		// Letzten Eintrag ohne den aktuellen mit Enddatum
		$iLastEntry = (int)$this->getLatestEntry(false, true);

		$mDelete = parent::delete();

		// Wenn der zu löschende Eintrag nicht dem letzten entspricht
		if(
			$mDelete === true &&
			$this->id == $iLastEntryCheck &&
			$iLastEntry > 0
		) {
			$oLastEntry = static::getInstance($iLastEntry);
			$oLastEntry->valid_until = '0000-00-00';
			$oLastEntry->save();
		}

		return $mDelete;
	}
	
	/**
	 * Achtung! Das wird nur in der SalesMaske für Boa benutzt. Das ist nur ein Workaround um
	 * nicht die Zusatzleistungen umbauen zu müssen
	 * 
	 * @return mixed
	 */
	public function deleteRestricted() {
		return parent::delete();
	}

	static public function getValidEntry(WDBasic $oEntity, DateTime $dFrom, DateTime $dUntil=null, int $iDependencyId=null) {
		
		$oBlankEntity = new static;
		
		$sParentColumn	= $oBlankEntity->sParentColumn;
	
		$aSql = array(
			'table'			=> $oBlankEntity->getTableName(),
			'parent_column'	=> $sParentColumn,
			'parent_id'		=> (int)$oEntity->id,
			'from' => $dFrom->format('Y-m-d')
		);

		$sWhere = '';
		$sFrom = '';
		
		$oBlankEntity->manipulateFromPart($sFrom);
		
		$sSql = "
			SELECT
				`".$oBlankEntity->getTableAlias()."`.*
			FROM
				#table `".$oBlankEntity->getTableAlias()."`
				".$sFrom."
			WHERE
				`".$oBlankEntity->getTableAlias()."`.#parent_column	= :parent_id AND "; 
		
		if($dUntil === null) {
			$sSql .= "(
				`".$oBlankEntity->getTableAlias()."`.`valid_from` <= :from AND `".$oBlankEntity->getTableAlias()."`.`valid_until` = '0000-00-00' OR
				:from BETWEEN `".$oBlankEntity->getTableAlias()."`.`valid_from` AND `".$oBlankEntity->getTableAlias()."`.`valid_until`
			)
			";
		} else {
			$aSql['until'] = $dUntil->format('Y-m-d');
			$sSql .= "(
				(
					:until >= `".$oBlankEntity->getTableAlias()."`.`valid_from` AND 
					`".$oBlankEntity->getTableAlias()."`.`valid_until` = '0000-00-00' 
				) OR
				(
					:from <= `".$oBlankEntity->getTableAlias()."`.`valid_until` AND 
					:until >= `".$oBlankEntity->getTableAlias()."`.`valid_from`
				)
			)
			";
		}

		if($oBlankEntity->hasActiveField()) {
			$sWhere .= " AND `".$oBlankEntity->getTableAlias()."`.`active` = 1 ";
		}

		if(
			!empty($oBlankEntity->sDependencyColumn) &&
			!empty($iDependencyId)
		) {
			$sSql .= " AND `".$oBlankEntity->getTableAlias()."`.`{$oBlankEntity->sDependencyColumn}` = :dependency_id ";
			$aSql['dependency_id'] = $iDependencyId;
		}

		$oBlankEntity->manipulateWherePart($sWhere);
		
		$sSql .= " 
			".$sWhere."
			ORDER BY
				`".$oBlankEntity->getTableAlias()."`.`valid_from` ASC
			LIMIT 1 
		";

		$aEntity = DB::getQueryRow($sSql, $aSql);

		if(!empty($aEntity)) {
			$oEntity = static::getObjectFromArray($aEntity);
			
			return $oEntity;
		}
		
	}

	public function getPeriod(): Period {

		if (
			!\Core\Helper\DateTime::isDate($this->valid_from, 'Y-m-d') &&
			!\Core\Helper\DateTime::isDate($this->valid_until, 'Y-m-d')
		) {
			throw new LogicException(sprintf('Neither valid valid_from nor valid_until available. (%s/%d)', $this->_sTable, $this->id));
		}

		// Achtung: Periode sollte wegen dem hier nur zum Vergleichen und NICHT zum Iterieren verwendet werden
		$from  = CarbonImmutable::startOfTime();
		$until = CarbonImmutable::endOfTime();

		if (\Core\Helper\DateTime::isDate($this->valid_from, 'Y-m-d')) {
			$from = CarbonImmutable::parse($this->valid_from);
		}

		if (\Core\Helper\DateTime::isDate($this->valid_until, 'Y-m-d')) {
			$until = CarbonImmutable::parse($this->valid_until);
		}

		return Period::make($from, $until);
	}
	
}