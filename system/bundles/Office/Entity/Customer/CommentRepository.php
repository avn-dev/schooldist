<?php

namespace Office\Entity\Customer;

class CommentRepository extends \WDBasic_Repository {
	
	public function findByCustomerOrdered($iCustomerId, $bASC = false){
		$sSql = '
			SELECT
				*
			FROM
				#tablename
			WHERE
				customer_id = :customerId AND
				active = true
			ORDER BY
				position 
		';
		
		if($bASC){
			$sSql .= "ASC";
		} else {
			$sSql .= "DESC";
		}

		$sSql .= ";";

		$aSql = array(
			'tablename' => $this->_sTableName,
			'customerId' => $iCustomerId
		);

		// Query ausführen
		$aResults = \DB::getQueryRows($sSql, $aSql);

		// Hole Array aus Server-Entitäten
		if(!empty($aResults)) {
			$aComments = $this->_getEntities($aResults);
			return $aComments;
		}

	}
	
	/**
	 * Gibt alle Kommentare zurück deren Position größer gleich sind.
	 * 
	 * @param int $iPosition Die Position
	 * @return array Ein <b>Array</b>, das ggf. mit <i>Kommentar-Entitäten
	 * (WDBasic-Objekten)</i> gefüllt ist.
	 */
	public function findByPositionBiggerOrEqual($iPosition) {

		$sSql = '
			SELECT
				*
			FROM
				#tablename
			WHERE
				position >= :position AND
				active = true
			;
		';

		$aSql = array(
			'tablename' => $this->_sTableName,
			'position' => $iPosition
		);

		// Query ausführen
		$aResults = \DB::getQueryRows($sSql, $aSql);

		// Hole Array aus Server-Entitäten
		if(!empty($aResults)) {
			$aComments = $this->_getEntities($aResults);
			return $aComments;
		}

	}

	/**
	 * Gibt die maximalste Position zurück
	 * 
	 * @return int <p>
	 * Die höchste Position.
	 * </p>
	 */
	public function getMaxPosition() {

		$sSql = '
			SELECT
				MAX(position) AS position
			FROM
				#tablename
			WHERE
				active = true
			;
		';

		$aSql = array('tablename' => $this->_sTableName);
	
		// Query ausführen
		$aResults = \DB::getQueryRows($sSql, $aSql);
		if(empty($aResults)){
			$iMaxPosition = 0;
		} else {
			$iMaxPosition = (int) $aResults[0]["position"];
		}
		return $iMaxPosition;
	}

	
	public function increasePositions($iPosition) {

		$sSql = '
			UPDATE
				#tablename
			SET
				position = position + 1
			WHERE
				active = true AND
				position >= :position
			;
		';

		$aSql = array(
			'tablename' => $this->_sTableName,
			'position' => $iPosition
		);
	
		// Query ausführen
		\DB::getQueryRows($sSql, $aSql);
	}

	public function decreasePositions($iPosition) {

		$sSql = '
			UPDATE
				#tablename
			SET
				position = position - 1
			WHERE
				active = true AND
				position >= :position
			;
		';

		$aSql = array(
			'tablename' => $this->_sTableName,
			'position' => $iPosition
		);
	
		// Query ausführen
		\DB::getQueryRows($sSql, $aSql);
	}
	
	public function decreasePositionsBetween($iPositionMin, $iPositionMax) {

		$sSql = '
			UPDATE
				#tablename
			SET
				position = position - 1
			WHERE
				active = true AND
				position >= :positionMin AND
				position <= :positionMax
			;
		';

		$aSql = array(
			'tablename' => $this->_sTableName,
			'positionMin' => $iPositionMin,
			'positionMax' => $iPositionMax
		);
	
		// Query ausführen
		\DB::getQueryRows($sSql, $aSql);
	}
	
	public function increasePositionsBetween($iPositionMin, $iPositionMax) {

		$sSql = '
			UPDATE
				#tablename
			SET
				position = position + 1
			WHERE
				active = true AND
				position >= :positionMin AND
				position <= :positionMax
			;
		';

		$aSql = array(
			'tablename' => $this->_sTableName,
			'positionMin' => $iPositionMin,
			'positionMax' => $iPositionMax
		);
	
		// Query ausführen
		\DB::getQueryRows($sSql, $aSql);
	}
}