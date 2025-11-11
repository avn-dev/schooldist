<?php

namespace Form\Service;

abstract class Frontend implements \FideloSoftware\Spam\Contracts\Form {

	/**
	 * @var \Form\Entity\Init
	 */
	protected $oForm;

	/**
	 * @var \Form\Entity\Page 
	 */
	protected $oPage;
	
	protected $aErrors = [];
	
	protected $aAllocations = [];
	
	protected $aAttachments = [];
	
	protected $aFields = [];
	
	protected $aFieldProxies = [];
	
	protected $bSuccess = false;

	protected $aElementData = [];
	
	protected $sInstanceHash;
	
	protected $aFieldsToPages = [];
	
	protected $aFieldValues = [];

	protected $oFirstPage = null;
	protected $oLastPage = null;
	protected $oPreviousPage = null;
	
	protected $aCaptcha = [];
	
	/**
	 * @var \FideloSoftware\Spam\SpamShield
	 */
	protected $spamShield;
	
	static public function getSessionInstance($sInstanceHash) {
		
		$sKey = self::getSessionKey($sInstanceHash);
		
		$oSession = \Core\Handler\SessionHandler::getInstance();
		
		if($oSession->has($sKey) === true) {
			$oInstance = $oSession->get($sKey);
			return $oInstance;
		}

	}
	
	static protected function getSessionKey($sInstanceHash) {
		$sKey = 'form_instance_'.$sInstanceHash;
		return $sKey;
	}


	/**
	 * @param \Form\Entity\Init $oForm
	 * @param \MVC_Request $oRequest
	 */
	public function __construct($sInstanceHash) {
		
		$this->sInstanceHash = $sInstanceHash;

	}
	
	public function enableCaptcha(string $sReCaptchaSecret, string $sReCaptchaKey) {
		
		if(
			empty($sReCaptchaSecret) ||
			empty($sReCaptchaKey)
		) {
			throw new \InvalidArgumentException('Missing configuration for reCAPTCHA!');
		}
		
		$this->aCaptcha = [
			'secret' => $sReCaptchaSecret,
			'key' => $sReCaptchaKey
		];
	}

	public function hasCaptcha() {
		return !empty($this->aCaptcha);
	}

	public function setForm(\Form\Entity\Init $oForm) {
		$this->oForm = $oForm;
		
		if($oForm->use_captcha) {
			$this->enableCaptcha($oForm->captcha_secret, $oForm->captcha_key);
		}
	}
	
	public function __sleep() {
		
		$aReturn = [
			'sInstanceHash',
			'aFieldValues',
			'aFieldProxies',
			'aFieldsToPages',
			'spamShield'
		];
	
		return $aReturn;
	}

	public function __wakeup() {

		$this->bSuccess = false;

	}
	
	public function destroySession() {
		
		$sKey = self::getSessionKey($this->sInstanceHash);
		
		$oSession = \Core\Handler\SessionHandler::getInstance();
		
		if($oSession->has($sKey) === true) {
			$oSession->remove($sKey);
		}

		$this->sInstanceHash = null;

	}
	
	public function saveInSession() {
		
		if($this->sInstanceHash !== null) {
			
			$sKey = self::getSessionKey($this->sInstanceHash);
		
			$oSession = \Core\Handler\SessionHandler::getInstance();
		
			$oSession->set($sKey, $this);
			
		}
		
	}
	
	public function setMessage($sMessage) {

	}
	
	public function setElementData(array $aElementData) {
		$this->aElementData = $aElementData;
	}
	
	/**
	 * @param \Form\Entity\Page $oPage
	 */
	public function setPage(\Form\Entity\Page $oPage) {
		
		$this->oPage = $oPage;
		
		// Felder der Seite laden, falls noch nicht geschehen
		if(!isset($this->aFields[$oPage->id])) {

			$oOptionRepository = \Form\Entity\Option::getRepository();
		
			$this->aFields[$oPage->id] = $oOptionRepository->getFields($this->oForm, $oPage);

			foreach($this->aFields[$oPage->id] as $oField) {
				$this->aFieldProxies[$oPage->id][$oField->id] = new \Form\Proxy\Field($oField);
				$this->aFieldsToPages[$oField->id] = $oPage->id;
			}

		}

	}
	
	public function getFields() {
		return $this->aFields[$this->oPage->id];
	}
	
	public function getAllFields() {
		return $this->aFields;
	}

	public function getFieldProxies() {
		return $this->aFieldProxies[$this->oPage->id];
	}

	public function getAllFieldProxies() {
		return $this->aFieldProxies;
	}
	
	/**
	 * @todo Double-Opt-In
	 */
	public function handleNewsletter() {
		
		$aNewsletter = $aAllocations;
		if ($aAllocations['sex'] == "Herr" || $aAllocations['sex'] == "Mr" || $aAllocations['sex'] == "Mr") {
			$aNewsletter['sex'] = 1;
		} elseif ($aAllocations['sex'] == "Frau" || $aAllocations['sex'] == "Mrs" || $aAllocations['sex'] == "Mrs") {
			$aNewsletter['sex'] = 2;
		} else {
			$aNewsletter['sex'] = 0;
		}
		if (preg_match("/^([a-z0-9\._-]*)@([a-z0-9\.-]{2,66})\.([a-z]{2,6})$/i", $aNewsletter['email'])) {
			$sQuery = "
				SELECT
					*
				FROM
					`newsletter2_recipients`
				WHERE
					`email` LIKE '".\DB::escapeQueryString($aNewsletter['email'])."' AND
					`idList` = '".\DB::escapeQueryString($aNewsletter['newsletter'])."'
			";
			$aRecipients = DB::getQueryRows($sQuery);
			if (empty($aRecipients)) {
				$sQuery = "
					INSERT INTO
						`newsletter2_recipients`
					SET
						`idList` = '".\DB::escapeQueryString($aNewsletter['newsletter'])."',
						`sex` = '".\DB::escapeQueryString($aNewsletter['sex'])."',
						`name` = '".\DB::escapeQueryString($aNewsletter['name'])."',
						`firstname` = '".\DB::escapeQueryString($aNewsletter['firstname'])."',
						`email` = '".\DB::escapeQueryString($aNewsletter['email'])."',
						`active` = 1
				";
				\DB::executeQuery($sQuery);
			}
		}
	}

	public function sendVisitorEmail($sConfirmEmail) {
		
		\DB::insertData('form_mailing', [
			'form_id' => $this->oForm->getId(),
			'mail_from' => \System::d('admin_email'),
			'mail_to' => $sConfirmEmail,
			'subject' => $this->oForm->cmail_title,
			'content' => $this->getMailContent($this->oForm->cmail_text),
			'html' => (int) $this->oForm->html
		]);
		
		return true;
	}
	
	public function getField($iFieldId) {
		
		$iPageId = $this->aFieldsToPages[$iFieldId];
		
		if(isset($this->aFields[$iPageId][$iFieldId])) {
			return $this->aFields[$iPageId][$iFieldId];
		}

	}
	
	public function getFieldProxy($iFieldId) {

		$iPageId = $this->aFieldsToPages[$iFieldId];

		if(isset($this->aFieldProxies[$iPageId][$iFieldId])) {
			return $this->aFieldProxies[$iPageId][$iFieldId];
		}
		
	}
	
	public function getPage() {
		return $this->oPage;
	}
	
	public function validatePage(\MVC_Request $oRequest, $bSubmit = true) {

		$this->aErrors = [];
		$this->aAllocations = [];

		$aFields = array();

		$lastPageSubmit = false;
		
		if(
			$bSubmit &&
			$this->oPage->id === $this->oLastPage->id
		) {
			
			// captcha auf der letzten Seite validieren - falls eingeschaltet	
			$this->validateCaptcha($oRequest);
			
			$lastPageSubmit = true;
			
		}
		
		$aFiles = $oRequest->getFilesData();
		
		foreach($this->aFields[$this->oPage->id] as $oField) {

			$aDisplayConditions = $oField->display_conditions;

			if(!empty($aDisplayConditions)) {

				foreach($aDisplayConditions as $aDisplayCondition) {

					$sConditionValue = (array)$oRequest->input('option_'.$aDisplayCondition['field']);

					if((string)$sConditionValue != (string)$aDisplayCondition['value']) {
						$oField->check = false;
						$oField->validation = false;	
					}

				}
			}

			if(
				$oField->type === 'file' &&
				!empty($aFiles["option_".$oField->id]['name'])
			) {
				$mValue = $aFiles["option_".$oField->id]['name'];
			} else {
				$mValue = $oRequest->input('option_'.$oField->id);
			}

			// Wert muss geprüft werden.
			if ($oField->check == 1) {
				if (is_array($mValue)) {
					$sCheck = implode('', $mValue);
				} else {
					$sCheck = $mValue;
				}

				if(
					$bSubmit && 
					empty($sCheck)
				) {
					$this->aErrors[$oField->id] = 1;
				}
			}
			
			switch($oField->validation) {
				case "date":
					
					if(!empty($mValue)) {

						$oDateFormat = new \Ext_Gui2_View_Format_Date;
						$sDate = $oDateFormat->convert($mValue);
						
						$oValidate = new \WDValidate();
						$oValidate->value = $sDate;
						$oValidate->check = 'DATE';
						$bValidate = $oValidate->execute();

						if($bValidate === false) {
							$this->aErrors[$oField->id] = "date";
						}
					}
					break;
				case "plz":
					if (!empty($mValue) && !preg_match("/^([0-9]{4,5})$/i", $mValue)) {
						$this->aErrors[$oField->id] = "plz";
					}
					break;
				case "email":
					if (!empty($mValue) && !\Util::checkEmailMx($mValue)) {
						$this->aErrors[$oField->id] = "email";
					}
					break;
				case "numbers":
					if (!empty($mValue) && !preg_match("/^\s*[0-9]+\s*[.,]*\s*[0-9]*\s*$/", $mValue)) {
						$this->aErrors[$oField->id] = "numbers";
					}
					break;
				case "currency":
					if (!empty($mValue) && !preg_match("/^\s*[0-9]+\s*[.,]*\s*[0-9]*\s*[a-z€$]{0,3}$/i", $mValue)) {
						$this->aErrors[$oField->id] = "currency";
					}
					break;
			}

			if ($oField->allocation) {
				if (is_array($mValue)) {
					$this->aAllocations[$oField->allocation] = $mValue[0];
				} else {
					$this->aAllocations[$oField->allocation] = $mValue;
				}
			}
			
			if(!empty($mValue)) {

				// Wenn Dateiupload.
				if (
					$oField->type === 'file' &&
					is_array($aFiles["option_".$oField->id]) && 
					is_file($aFiles["option_".$oField->id]['tmp_name'])
				) {

					$sFileName = $aFiles["option_".$oField->id]['name'];
					$sFileName = \Util::getCleanFileName($sFileName);
					
					// Dateien speichern, wenn das Verzeichnis noch nicht besteht.
					$sTargetDir = \Util::getDocumentRoot().'storage/form/';
					\Util::checkDir($sTargetDir);

					move_uploaded_file($aFiles["option_".$oField->id]['tmp_name'], $sTargetDir.$sFileName);
					
					$this->aAttachments[$oField->id][] = $sFileName;
					
				} elseif (is_array($mValue)) {
					$mValue = array_map('strip_tags', $mValue);
				} else {
					$mValue = strip_tags($mValue);
				}

				$oFieldProxy = $this->getFieldProxy($oField->id);
				$oFieldProxy->setValue($mValue);

				$this->aFieldValues[$oField->id] = $mValue;

			}

		}

		if($lastPageSubmit) {
			
			try {
				$this->spamShield->detect($this, $oRequest);
			} catch(\FideloSoftware\Spam\Exceptions\BannedException $e) {
				$this->aErrors[] = $e->getMessage();
			} catch(\FideloSoftware\Spam\Exceptions\SpamDetectionException $e) {
				$this->aErrors[] = $e->getMessage();
			}

		}
		
		$this->updateErrors();
			
		if(empty($this->aErrors)) {
			return true;
		}
		
		return false;
	}
	
	private function getMailContent($sTemplate, array $aAdditionalPlaceholders=[]) {

		$sLineBreak = "\n";
		if ($this->oForm->html) {
			$sLineBreak = "<br>";
		}

		// Standardtext
		if (empty($sTemplate)) {
			
			if ($this->oForm->html) {
				$sTemplate = "<style>body, p, th, td {font-family: sans-serif; text-align: left; } th {background-color: #dedede;}</style>
Folgende Daten wurden uebermittelt am ".strftime("%x %X", time()).":
<br><br>
<table>
	<#fields#>
	<tr>
		<th><#field_name#></th>
		<td><#field_data#></td>
	</tr>
	<#/fields#>
</table>
<br><br>";
				
				if(!empty($aAdditionalPlaceholders)) {
					$sTemplate .= "
<dl>
  <dt>IP</dt>
  <dd><#ip#></dd>
  <dt>Referrer</dt>
  <dd><#referrer#></dd>
  <dt>Spy</dt>
  <dd><#spy#></dd>
</dl>
					";
				}
			} else {
				$sTemplate = "
Folgende Daten wurden uebermittelt am ".strftime("%x %X", time()).":
						
<#fields#>
<#field_name#>: <#field_data#><#/fields#>
";

				if(!empty($aAdditionalPlaceholders)) {
					$sTemplate .= "
IP: <#ip#>
Referrer: <#referrer#>
Spy: <#spy#>

					";
				}
			}

		}

		$buffer_admin = \Cms\Service\PageParser::checkForBlock($sTemplate, "fields");
		if ($buffer_admin) {
			$sContent = "";
			$aEmailFields = array();
			foreach($this->aFieldProxies as $iPageId=>$aPageFields) {
				foreach($aPageFields as $oFieldProxy) {
					if (
						$oFieldProxy->getProperty('type') != "onlytext" && 
						$oFieldProxy->getProperty('type') != "onlytitle"
					) {
						
						$sFieldKey = trim($oFieldProxy->getProperty('name'));
						
						if(empty($sFieldKey)) {
							$sFieldKey = '#'.$oFieldProxy->getProperty('id');
						}

						$aEmailFields[] = [
							'label' => $sFieldKey,
							'value' => $oFieldProxy->getValue(true)
						];

					}
				}
			}
			
			foreach ($aEmailFields as $val) {

				$buffer_admin_loop = $buffer_admin;
				$buffer_admin_loop = str_replace("<#field_name#>", $val['label'], $buffer_admin_loop);
				$buffer_admin_loop = str_replace("<#field_data#>", $val['value'], $buffer_admin_loop);
				$buffer_admin_output .= $buffer_admin_loop;

			}
			
			$sContent .= \Cms\Service\PageParser::replaceBlock($sTemplate, "fields", $buffer_admin_output);
		} else {
			$sContent = $sTemplate;
		}

		if(!empty($sTemplate)) {
			foreach($this->aFieldProxies as $iPageId=>$aPageFields) {
				foreach($aPageFields as $oFieldProxy) {
					$sContent = str_replace("<#field_option_".$oFieldProxy->getProperty('id')."#>", $oFieldProxy->getValue(true), $sContent);
				}
			}
		}

		// Ersetzen der zugeordneten Felder.
		foreach ($this->aAllocations as $k => $v) {
			$sContent = str_replace("<#field_".$k."#>", $v, $sContent);
		}

		if(!empty($aAdditionalPlaceholders)) {
			foreach($aAdditionalPlaceholders as $sKey=>$sValue) {
				$sContent = str_replace("<#".$sKey."#>", $sValue, $sContent);
			}
		}

		$sContent = preg_replace("/\r?\n/","\n", $sContent);
		
		return $sContent;
	}

	public function sendFormEmail() {
		
		$oSession = \Core\Handler\SessionHandler::getInstance();
		
		$aAdditionalPlaceholders = [
			'ip' => $_SERVER['REMOTE_ADDR'],
			'referrer' => $oSession->get('frontend_referrer'),
			'spy' => $oSession->get('frontend_spy')
		];

		$sText = $this->getMailContent($this->oForm->text, $aAdditionalPlaceholders);

		$aMailInsert = [];
		$aMailInsert['form_id'] = $this->oForm->getId();
		$aMailInsert['content'] = $sText;
		$aMailInsert['mail_from'] = \System::d('admin_email');
		
		// Wenn eine E-Mail gesetzt ist, soll diese als Absender angegeben werden.
		if ($this->aAllocations['email']) {
			if ($this->oForm->allocate) {	
				$aMailInsert['mail_from'] = $this->aAllocations['email'];
			} else {
				$aMailInsert['reply_to'] = $this->aAllocations['email'];
			}
		} else {
			$aMailInsert['reply_to'] = \System::d('admin_email');
		}

		// only send mail if address is set
		if ($this->oForm->email) {

			$aMailInsert['mail_to'] = $this->oForm->email;
			
			if($this->oForm->cc) {
				$aMailInsert['cc'] = $this->oForm->cc;
			}
			if($this->oForm->bcc) {
				$aMailInsert['bcc'] = $this->oForm->bcc;
			}

			$aMailInsert['subject'] = $this->oForm->subject;
			$aMailInsert['html'] = (int) $this->oForm->html;
			if(!empty($this->aAttachments)) {
				$aMailInsert['attachments'] = json_encode($this->aAttachments);
			}
			\DB::insertData('form_mailing', $aMailInsert);
		}

		return true;
	}
	
	public function getErrors() {
		return $this->aErrors;
	}
	
	public function hasAllocation($sKey) {
		
		if(isset($this->aAllocations[$sKey])) {
			return true;
		}
		
		return false;
	}
	
	public function getAllocation($sKey) {
		
		if(isset($this->aAllocations[$sKey])) {
			return $this->aAllocations[$sKey];
		}
		
	}
	
	public function getConditionService() {
		
		$oConditionService = new Frontend\Conditions($this);
		
		return $oConditionService;
	}
	
	public function getForm() {
		return $this->oForm;
	}

	
	public function handleSuccess() {
		
		$this->bSuccess = $this->sendFormEmail();
		
		if($this->bSuccess !== true) {
			return false;
		}
		
		// Optionales Newsletter eintragen.
		if ($this->getAllocation('newsletter') > 0) {
			$this->handleNewsletter();	
		}

		// Bestätigungsemail an Absender nach Bedarf mit Platzhaltern.
		if (!empty($this->oForm->confirm)) {

			$sConfirmEmail = $this->getAllocation('email');

			if(
				!empty($sConfirmEmail) &&
				\Util::checkEmailMx($sConfirmEmail) === true
			) {
				$this->sendVisitorEmail($sConfirmEmail);
			}

		}

		// Eintrag der Daten in die Datenbank.
		$aInsert = [];
		$aInsert['date'] = date('Y-m-d H:i:s');
		$aInsert['ip'] = $_SERVER['REMOTE_ADDR'];

		foreach($this->getAllFieldProxies() as $aFieldProxies) {
			foreach($aFieldProxies as $oFieldProxy) {
				$aInsert['field_'.$oFieldProxy->getProperty('id')] = $oFieldProxy->getValue(true);
			}
		}

		$iDataId = \DB::insertData('form_data_'.(int)$this->oForm->id, $aInsert);

		$aTransfer = array($iDataId, $this->aAllocations, $this->aFieldValues, $this->oForm);
		\System::wd()->executeHook('form_'.$this->aElementData['content_id'], $aTransfer);
		unset($aTransfer);

		// Session zurücksetzen
		$this->destroySession();
		
	}
	
	public function handleRequest(\MVC_Request $oRequest) {

		$aPages	= (array)$this->oForm->getJoinedObjectChilds('pages', true);

		$this->oLastPage = end($aPages);
		$this->oFirstPage = reset($aPages);
		
		$bSubmit = ($oRequest->method() === 'GET')
				? false
				: true;
		
		// Values auch per $_GET erlauben
		$bValidate = $this->validatePage($oRequest, $bSubmit);
		
		// Formular abgeschickt?
		if (
			$oRequest->get('fo_action') == 'send' || 
			$oRequest->get('fo_action_'.$this->aElementData['content_id']) == 'send'
		) {
			
			// Aktuelle Seite validiert?
			if($bValidate === true) {
				
				reset($aPages);
				
				while($oPage = current($aPages)) {
					if($oPage->id == $this->oPage->id) {
						break;
					}
					next($aPages);
				}
				
				$oNextPage = next($aPages);

				// War das die letzte Seite?
				if($oNextPage) {

					$this->setPage($oNextPage);

				} else {					
					// Formulardaten speichern und Mails verschicken
					$this->handleSuccess();

					$bValidate = $this->bSuccess;					
					
				}
				
			} 
			
			if($bValidate !== true) {
	
				$this->handleErrors();
		
			}

		}
		
		reset($aPages);
		while($oPage = current($aPages)) {
			if($oPage->id == $this->oPage->id) {
				break;
			}
			$this->oPreviousPage = $oPage;
			next($aPages);
		}
		
	}
	
	public function handleErrors() {
		
	}

	public function updateErrors() {

		foreach($this->aFieldProxies[$this->oPage->id] as $oFieldProxy) {
			if(isset($this->aErrors[$oFieldProxy->getProperty('id')])) {
				$oFieldProxy->setError($this->aErrors[$oFieldProxy->getProperty('id')]);
			} else {
				$oFieldProxy->unsetError();
			}
		}

	}
	
	public function getContentId() {
		return $this->aElementData['content_id'];
	}
	
	public function getFormCaptchaHtml() {
		if(!$this->hasCaptcha()) {
			return '';
		}
					
		return '<input type="hidden" name="recaptcha_response" id="recaptchaResponse">';
	}
	
	public function getFormCaptchaJavaScript() {
		if(!$this->hasCaptcha()) {
			return '';
		}
			
		$sJs = ' 
			<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?render={site_key}"></script>
			<script>
				grecaptcha.ready(function () {
					grecaptcha.execute("{site_key}", { action: "{action}" }).then(function (token) {
						var recaptchaResponse = document.getElementById("recaptchaResponse");
						recaptchaResponse.value = token;
					});
					
					setInterval(function () {
						grecaptcha.execute("{site_key}", { action: "{action}" }).then(function (token) {
							var recaptchaResponse = document.getElementById("recaptchaResponse");
							recaptchaResponse.value = token;								
							//console.log("set new token to existing input >> token = " + token);
						});
					}, 60000);
				});
			</script>
		';
		
		return str_replace(['{site_key}', '{action}'], [$this->aCaptcha['key'], 'form_'.$this->oForm->getId()], $sJs);
	}
	
	public function validateCaptcha(\MVC_Request $oRequest) {

		if(!$this->hasCaptcha()) {
			return true;
		}
		
		$oReCaptcha = new \ReCaptcha\ReCaptcha($this->aCaptcha['secret']);
		
		$oResponse = $oReCaptcha->setExpectedHostname($oRequest->getHttpHost())
                  ->setExpectedAction('form_'.$this->oForm->getId())
                  ->setScoreThreshold(0.5)
                  ->verify($oRequest->input('recaptcha_response'), $oRequest->ip());

		if($oResponse->isSuccess()) {
			return true;
		} else {
			\Log::getLogger('form', 'recaptcha')->info('Failed', $oResponse->toArray());
		}
			
		// Captcha schlug fehl
		
		$this->aErrors['captcha'] = "invalid";
		
		return false;
	}
	
	abstract public function parse();
	
	/**
	 * @inheritDoc
	 */
	public function getUid(): string {
		
		return $this->oForm->id;
		
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldValues(array $fieldNames = []): array {
		
		$values = $this->aFieldValues;

		if (!empty($fieldNames)) {
			$values = array_intersect_key($values, array_flip($fieldNames));
		}
		
		return $values;
	}

	public function initSpamShield(\MVC_Request $request) {
		
		// Wird in Session gespeichert
		if($this->spamShield !== null) {
			return;
		}

		$store = new \Core\Service\Cache\LaravelStore();

		$this->spamShield = new \FideloSoftware\Spam\SpamShield(
			[
				new \FideloSoftware\Spam\Strategies\HoneypotStrategy(3),
				new \FideloSoftware\Spam\Strategies\TimestampStrategy($store, 3),
				#new \FideloSoftware\Spam\Strategies\LinkStrategy(0),
			],
			$store,
			\Log::getLogger('form', 'spam')
		);
		
		// Form initialization 
		$this->spamShield->onload($this, $request);
		
	}
	
}
