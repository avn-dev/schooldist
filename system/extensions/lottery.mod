<?php

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);
$aFalse = $aEntries = array();
/////////////// functions ///////////////
function checkAnswer($id){
	$aSql = array('id' => $id);
	$sSql = "
		SELECT
			*
		FROM
			`lottery_answers`
		WHERE
			`id` = :id
		AND
			`right_one` = 1
		LIMIT 1
	";
	$aResult = DB::getPreparedQueryData($sSql, $aSql);
	if(count($aResult) == 1){
		return true;
	} else {
		return false;
	}
}
/////////////// functions end ///////////////
if(isset($oConfig->lottery_id)) {
	if(isset($_VARS['question_submit'])){
		if(checkAnswer($_VARS['radio']) == false){
			$sAnswer = false;
			$_SESSION['lottery'] = false;
		} elseif(checkAnswer($_VARS['radio']) == true){
			$sAnswer = true;
			$_SESSION['lottery'] = true;
		}
	}
	/////////////// functions etc for answer and question ///////////////
	$id = $oConfig->lottery_id;
		// get Question => $sQuestion
	$aSql = array('id' => $id);
	$sSql = "
			SELECT
				`question`
			FROM
				`lotteries`
			WHERE
				`id` = :id 
	";
	$aResult = DB::getPreparedQueryData($sSql, $aSql);
	$sQuestion = $aResult[0]['question'];

	// get Answers
	// foreach($aAnswers as $key => $value) // $value['answer']
	$aSql = array('id' => $id);
	$sSql = "
			SELECT
				*
			FROM
				`lottery_answers`
			WHERE
				`lottery_id` = :id
			ORDER BY
				`id`
	";
	$aAnswers = DB::getPreparedQueryData($sSql, $aSql);
	///////////////
	/////////////// proof and send data after enter in form ///////////////
	if(isset($_VARS['send'])){
			// captcha
		$objCaptcha = new \Cms\Service\Captcha();
		
		if ($_VARS['sex'] == 'female'){
			$_VARS['sex'] = 2;
		} else if ($_VARS['sex'] == 'male'){
			$_VARS['sex'] = 1;
		}

			// fill variables with form entries
		$aEntries = array(
			'name'			=> $_VARS['name'],
			'firstname'		=> $_VARS['firstname'],
			'email'			=> $_VARS['email'],
			'tel'			=> $_VARS['tel'],
			'sex'			=> $_VARS['sex'],
			'lottery_id' 	=> $oConfig->lottery_id
		);
			
			// check newsletter
		if(isset($_VARS['newsletter'])){
			$aEntries['newsletter'] = 1;
		} else {
			$aEntries['newsletter'] = 0;
		}
		
		
			// check form entrys
		if(trim($_VARS['name']) == ""){
			$aFalse['noentry_name'] = 1;
		}
		if(trim($_VARS['firstname']) == ""){
			$aFalse['noentry_byname'] = 1;
		}
		if(trim($_VARS['email']) == ""){
			$aFalse['noentry_email'] = 1;
		}
		
		
			// email check
		if(trim($_VARS['email']) != ""){
			if(checkEmailMx($_VARS['email']) == false){
				$aFalse['email'] = 1;
			}
		}
		
		
			// save or error
		if(empty($aFalse)){
			$bolOther = true;
		} else {
			$sErrormessage = true;
		}
		if(isset($aFalse['email'])){
			$sErrorEmail = true;
		}
		
		
			// proof captcha code
		if(isset($_VARS['ccode'])) {
			$bolCheck = $objCaptcha->checkCaptcha($_VARS['ccode']);
		}
		
		if($bolCheck && $bolOther){
			
			$aSql = array(
				'lottery_id' => $aEntries['lottery_id'],
				'email' => $_VARS['email']
			);
			
			$sSql = "
				SELECT
					*
				FROM
					`lottery_results`
				WHERE
					`lottery_id` = :lottery_id
				AND
					`email` = :email
			";
			$aDouble = DB::getPreparedQueryData($sSql, $aSql);
			
			if(count($aDouble) >= 1){
				
				$sErrorDouble = true;
				
			} else {
			
				$sSql = "
					INSERT INTO
						`lottery_results`
						
						(`sex`, 
						 `firstname`, 
						 `lastname`, 
						 `telephone`, 
						 `email`, 
						 `newsletter`, 
						 `lottery_id`)
	
					VALUES
						(:sex,
						 :firstname,
						 :name,
						 :tel,
						 :email,
						 :newsletter,
						 :lottery_id
						 )
				";
				DB::executePreparedQuery($sSql, $aEntries);
				
				if($aEntries['newsletter'] == 1){
					$aSql = array(
						'lottery_id' => $oConfig->lottery_id
					);
					
					$sSql = "
						SELECT
							`newsletter_id`
						FROM
							`lotteries`
						WHERE
							`id` = :lottery_id
					";
					
					$aNewsletter = DB::getPreparedQueryData($sSql, $aSql);
					
					$aSql = array(
						'newsletter_list'	=>	$aNewsletter[0]['newsletter_id'],
						'name'				=>	$aEntries['name'],
						'firstname'			=>	$aEntries['firstname'],
						'email'				=>	$aEntries['email'],
						'sex'				=>	$aEntries['sex'],
					);
					
					$sSql = "
						INSERT INTO
							`newsletter2_recipients`
						SET
							`idList` 	= 	:newsletter_list,
							`name`		= 	:name,
							`firstname`	=	:firstname,
							`email`		=	:email,
							`sex`		=	:sex,
							`active`	=	1
					";
					
					DB::executePreparedQuery($sSql, $aSql);
				}
				
				
				$sEntry_add = true;
				unset($_SESSION['lottery']);
			}
			
		}
			
	}
	
	
	
		//captcha
	$oCaptcha = new \Cms\Service\Captcha();
	
		// smarty
	$oSmarty = new \Cms\Service\Smarty();
	$oSmarty->assign('bolCheck', $bolCheck);
	$oSmarty->assign('oCaptcha', $oCaptcha);
	$oSmarty->assign('sEntry_add', $sEntry_add);
	$oSmarty->assign('sQuestion', $sQuestion);
	$oSmarty->assign('sAnswer', $sAnswer);
	$oSmarty->assign('aAnswers', $aAnswers);
	$oSmarty->assign('sErrormessage', $sErrormessage);
	$oSmarty->assign('sErrorEmail', $sErrorEmail);
	$oSmarty->assign('sErrorDouble', $sErrorDouble);
	$oSmarty->assign('aFalse', $aFalse);
	$oSmarty->assign('aEntries', $aEntries);
	$oSmarty->displayExtension($element_data);
}
	///////////////
?>