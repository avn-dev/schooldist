<?

class Counter {
	
	function Counter() {
		
	}
	
	function updateCounter() {
		
		// checken, oder der aktuelle tag schon in der DB steht
		$strSql = "SELECT 
						`id`
					FROM
						`counter`
					WHERE	
						`day` = CURRENT_DATE
					";
		$arrCount = DB::getQueryData($strSql);
		if(is_array($arrCount)) {
			$intCurrentDay = $arrCount[0]['id'];
		}
		
		if(!session_id()) {
			session_start();
		}

		// check if is first access in session
		$bolFirst = 1;
		if(isset($_SESSION['counter_visit']) && $_SESSION['counter_visit'] == 1) {
			$bolFirst = 0;
		}

		if(isset($intCurrentDay)) {
			if($bolFirst) {
				$strSql = "
						UPDATE 
							`counter`
						SET
							`views` = `views` + 1,
							`visits` = `visits` + 1
						WHERE	
							`id` = :intCurrentDay
						";
			} else {
				$strSql = "
						UPDATE 
							`counter`
						SET
							`views` = `views` + 1
						WHERE	
							`id` = :intCurrentDay
						";
			}
			$arrTransfer = array('intCurrentDay'=>$intCurrentDay);
			DB::executePreparedQuery($strSql, $arrTransfer);
		} else {
			$strSql = "
					INSERT INTO 
						`counter`
					SET
						`created` = NOW(),
						`day` = CURRENT_DATE,
						`views` = 1,
						`visits` = 1
					";
			DB::executeQuery($strSql);
		}

		$_SESSION['counter_visit'] = 1;
		
	}
	
	function getTotalVisitCount() {
		
		$strSql = "SELECT 
						SUM(visits) c
					FROM
						counter
					WHERE	
						1
					";
		$arrCount = DB::getQueryData($strSql);
		$intCount = $arrCount[0]['c'];

		return $intCount;

	}
	
}

?>