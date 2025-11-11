<?php

use Core\Entity\ParallelProcessing\Stack;

class GlobalChecks {
		
	protected $_aFormErrors = array();
	
	protected $_aCheck = array();
	
	protected $_sClassName = '';

	/** @var static */
	protected $_oUsedClass = null;
	
	protected $_iCheck = 0;

	protected $_oLogger;
	
	protected $oSession;
		
	public function __construct($bCheckClassName = true, $iCheck = 0){

		$this->oSession = \Core\Handler\SessionHandler::getInstance();
		
		$bNeeded = false;
		$bBlocking = $this->isBlocking();

		$this->_iCheck = $iCheck;

		while(
			$bNeeded === false &&
			$bBlocking === true
		) {

			$this->_oUsedClass = null;

			$this->_aCheck = $this->getCheck();

			if(empty($this->_aCheck)) {
				break;
			}
			$this->setData($bCheckClassName);
			$bNeeded = $this->_oUsedClass->isNeeded();
			//$bBlocking = $this->_oUsedClass->isBlocking();

			$bCheckClassName = true;
			$this->_iCheck++;

		}

		if($this->_oUsedClass instanceof GlobalChecks) {
			$this->_oUsedClass->modifyCheckData();
		}

	}
	
	public function getUsedClass() {
		return $this->_oUsedClass;
	}
	
	protected function setData($bCheckClassName = true){
		// Set Classname
		$this->_sClassName = $this->_aCheck['class_name'];

		// If Classname is set
		if(!empty($this->_sClassName) && $bCheckClassName) {
			$bCheckClassName = false;
			$this->_oUsedClass = new $this->_sClassName($bCheckClassName, $this->_iCheck);
		} else {
			$this->_oUsedClass = $this;
		}

	}

	public function isNeeded(){
		return true;
	}

	public function isBlocking(){
		if(
			$this->oSession->has('system_checks_execute_checks') &&
			$this->oSession->get('system_checks_execute_checks') == 1
		) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * check if an check is defined
	 * @return bol
	 */
	public function checkForCheck() {
		
		if(
			empty($this->_oUsedClass->_aCheck) ||
			(
				$this->oSession->has('system_checks_skip_global_checks') &&
				$this->oSession->get('system_checks_skip_global_checks') == true
			)
		) {
			return false;
		}

		return true;
	}
	
	/**
	 * funktion to Modify Check Data
	 * @return 
	 */
	protected function modifyCheckData(){
		
		//$this->_aCheck = ....
		
	}
	
	

	/**
	 * Select the oldest, active Check in the system_global_checks table
	 * @return Array CheckData
	 */
	public function getCheck() {

		$sSql = " SELECT
						*,
						UNIX_TIMESTAMP(`created`) `created`,
						UNIX_TIMESTAMP(`changed`) `changed`
					FROM
						`system_global_checks`
					WHERE
						`active` = 1
					ORDER BY
						`changed` ASC";
		$aResult = DB::getQueryData($sSql);

		// Wenn keine aktiven Checks mehr vorhanden sind, Tabelle leeren
		if(empty($aResult)) {
			
			$sSql = " 
					SELECT
						*
					FROM
						`system_global_checks`
					";
			$aResult = DB::getQueryRows($sSql);
			
			if(!empty($aResult)) {

				\Update::endUpdate();

				$sSql = "TRUNCATE TABLE `system_global_checks`";
				DB::executeQuery($sSql);

			}

		}

		$aReturn = $aResult[$this->_iCheck];

		return $aReturn;

	}
	
	public function getNextCheck(){

		$sSql = " SELECT
						*,
						UNIX_TIMESTAMP(`created`) `created`,
						UNIX_TIMESTAMP(`changed`) `changed`
					FROM
						`system_global_checks`
					WHERE
						`active` = 1
					ORDER BY
						`changed` ASC";
		$aResult = DB::getQueryData($sSql);

		return $aResult[1];

	}
	
	public static function getChecks() {

		$sSql = " SELECT
						*,
						UNIX_TIMESTAMP(`created`) `created`,
						UNIX_TIMESTAMP(`changed`) `changed`
					FROM
						`system_global_checks`
					WHERE
						`active` = 1
					ORDER BY
						`changed` ASC";
		$aResult = DB::getQueryData($sSql);

		return $aResult;

	}
	
	/**
	 * Set the Check to active = 0
	 */
	public function updateCheck(){

		$sSql = " UPDATE
						`system_global_checks`
					SET
						`active` = 0
					WHERE 
						`id` = :id";
		$aSql = array('id'=>$this->_aCheck['id']);

		DB::executePreparedQuery($sSql, $aSql);

	}

	/**
	 * Prepare the ExecuteCheck
	 * Check if a Check exist and if this the correct one ( e.g if 2 users send it at the same time
	 * @return bool
	 * @throws Exception
	 */
	protected function prepareExecuteCheck(){
		global $system_data, $_VARS;
		// If no Check found, dont try to execute
		// If you land on this Method and it dosn´t exist an Check ist must give an Error in your Script!
		if(empty($this->_oUsedClass->_aCheck)){
			$this->_oUsedClass->_aFormErrors[] = L10N::t('No Check Found!', 'System » Update');
			return false;
		}

		// if after sending the Form the Check is has Changed trow an Error
		// ( e.g 2 Users make the check to the same time an it give an second Check )
		if($_VARS['check_id'] != $this->_oUsedClass->_aCheck['id']){
			$this->_oUsedClass->_aFormErrors[] = L10N::t('Sie haben Daten für einen anderen Check abgesendet. Bitte gehen Sie einen Schritt zurück!', 'System » Update');
			return false;
		}

		try {
			DB::updateData('system_global_checks', ['locked' => 1], ['id' => (int)$this->_oUsedClass->_aCheck['id']]);
			$bReturn = $this->_oUsedClass->executeCheck();

			Log::logUpdateAction($system_data['version'], 'CHECK', get_class($this->_oUsedClass), $bReturn);
		} catch(Exception $e) {
			Log::logUpdateAction($system_data['version'], 'CHECK', get_class($this->_oUsedClass), false, $e);
			throw $e;
		}

		DB::updateData('system_global_checks', ['locked' => 0], ['id' => (int)$this->_oUsedClass->_aCheck['id']]);

		return $bReturn;
	}
	
	/**
	 * Her you can make your own Check
	 * If it Return true, the Global Check is Success also it Failed and the Script do not update the DB
	 * @return bol
	 */
	public function executeCheck(){
		return true;
	}

	/**
	 * Führt einen Prozess aus (ParallelProcessing)
	 * @param array $aData
	 * @return bool
	 */
	public function executeProcess(array $aData) {
		return true;
	}

	/**
	 * Prozess eintragen für ParallelProcessing
	 * @param array $aData
	 * @param int $iPrio
	 * @throws Exception
	 */
	protected function addProcess(array $aData, int $iPrio = 100) {

		$aData['check'] = get_class($this);

		$oStackRepository = Stack::getRepository();
		$oStackRepository->writeToStack('core/check-handler', $aData, $iPrio);

	}

	public function generateHtml(){
		
		// If no Check found, disable generate html
		if(empty($this->_oUsedClass->_aCheck)){
			return false;
		}

		//wraper to switch to your own Class if set
		$this->_oUsedClass->_generateHtml();
		
		return true;

	}
	
	/**
	 * Generate the HTML of the Global Check
	 * It calls the printFormContent who print the innerHTML of the Form
	 * and set the default hidden input "task" and "check_id"
	 * @output HTML 
	 */
	protected function _generateHtml(){
		global $_VARS;

		Admin_Html::loadAdminHeader();
		
		// Beim Update darf der Check nicht im äußeren Fenster geöffnet werden
		if(!$this->oSession->get('system_checks_no_top_global_checks')) {
?>
<script>
	if (self != top) top.location = self.location;
</script>
<?php
		}
?>
<section class="content-header">
	<h1><?=L10N::t('System checks', 'System » Update')?></h1>
</section>

<section class="content">
	<div class="box">
		<div class="box-body">
<?
		// Durch ?task=update und dem Hidden Input ist das hier ein Array
		if(
			$_VARS['task'] == 'execute_check' || (
				is_array($_VARS['task']) &&
				in_array('execute_check', $_VARS['task'])
			)
		) {
			$bSuccess = $this->_oUsedClass->prepareExecuteCheck();
			if($bSuccess){
				$this->_oUsedClass->updateCheck();
?>
				<div class="alert alert-success">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
					<h4><i class="icon fa fa-check"></i> <?=L10N::t('Der Check wurde erfolgreich ausgeführt', 'System » Update')?>!</h4>
					<?=L10N::t('Sie werden in zwei Sekunden weitergeleitet!', 'System » Update')?>
				</div>

				<script language ="JavaScript">
				<!--
					setTimeout("reloadFrame()",2000);
					function reloadFrame() {
<?
if(!$this->oSession->get('system_checks_no_top_global_checks')) {
?>
						top.location.href = '/admin/';
<?
} else {
?>
						go('<?=$_SERVER['PHP_SELF']?>');
<?
}
?>
					}
					
				// -->
				</script>
<?
			} else {
				
?>
				
				<div class="alert alert-danger">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
					<h4><i class="icon fa fa-ban"></i> <?=L10N::t('An error has occurred!', 'System » Update')?></h4>
					<ul>
<?
					foreach($this->_oUsedClass->_aFormErrors as $sError){
?>
						<li><?=$sError?></li>
<?
					}
?>					
					</ul>
				</div>

				<p>
					<form action="" method="post" class="form-horizontal">
						<input type="hidden" value="" name="task" id="task" />
						<input type="submit" class="btn btn-primary pull-right" value="<?=L10N::t('Zurück', 'System » Update')?>" />
					</form>
				</p>
<?
			}
		} else {
			
			if(!empty($this->_oUsedClass->_aFormErrors)){
?>
				<div class="alert alert-danger">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
					<h4><i class="icon fa fa-ban"></i> <?=L10N::t('An error has occurred!', 'System » Update')?></h4>
					<ul>
<?
					foreach($this->_oUsedClass->_aFormErrors as $sError){
?>
						<li><?=$sError?></li>
<?
					}
?>					
					</ul>
				</div>

<?
			} else {

				if(empty($this->_oUsedClass->_aCheck['title'])) {
					$this->_oUsedClass->_aCheck['title'] = $this->getTitle();
				}
				if(empty($this->_oUsedClass->_aCheck['description'])) {
					$this->_oUsedClass->_aCheck['description'] = $this->getDescription();
				}
				
?>
				<h3><?=$this->_oUsedClass->_aCheck['title']?></h3>
				<p><?=nl2br($this->_oUsedClass->_aCheck['description'])?></p>
				<form action="" method="post" class="form-horizontal">
					<input type="hidden" value="execute_check" name="task" id="task" />
					<input type="hidden" value="<?=$this->_oUsedClass->_aCheck['id']?>" name="check_id" id="check_id" />
<?
					$this->_oUsedClass->printFormContent();
?>
				</form>
<?
			}
			

		}
?>
		</div>
	</div>
</section>
<?
		Admin_Html::loadAdminFooter();
		die();
		
	}
	
	/**
	 * Print the Content of the Form
	 * @output HTML 
	 */
	public function printFormContent() {
?>			
		<input type="submit" value="<?=L10N::t('Ausführen', 'System » Update')?>" class="btn btn-primary pull-right" />
<?
	}
	
	public function getTitle() {
		$sTitle = 'Title';
		return $sTitle;		
	}
	
	public function getDescription() {
		$sDescription = 'Description';
		return $sDescription;		
	}
	
	public static function listChecks($aChecks) {
		
		echo '<ul>';
		
		foreach($aChecks as $aCheck) {
			
			$oCheck = new $aCheck['class_name'];
			echo '<li>'.$oCheck->getTitle().'</li>';
			
		}
		
		echo '</ul>';
		
	}

	/**
	 * Loggt mit dem Level INFO
	 * @param $sMessage
	 * @param array $mOptional
	 */
	public function logInfo($sMessage, $mOptional = array()) {
		$this->_log('info', $sMessage, $mOptional);
	}

	/**
	 * Loggt mit dem Level ERROR
	 * @param $sMessage
	 * @param array $mOptional
	 */
	public function logError($sMessage, $mOptional = array()) {
		$this->_log('error', $sMessage, $mOptional);
	}

	/**
	 * Generelles Loggen für Checks
	 * @param $sType
	 * @param $sMessage
	 * @param array $mOptional
	 */
	protected function _log($sType, $sMessage, $mOptional = array()) {

		// Ohne Cache der Monolog Instanz ist Log::getLogger() ziemlich langsam bei vielen Aufrufen
		if($this->_oLogger === null) {
			$this->_oLogger = Log::getLogger('check');
		}

		$sClass = get_class($this);

		$sMessage = $sClass.': '.$sMessage;

		if(!is_array($mOptional)) {
			$mOptional = array($mOptional);
		}

		if($sType === 'error') {
			$this->_oLogger->addError($sMessage, $mOptional);
		} else {
			$this->_oLogger->addInfo($sMessage, $mOptional);
		}
	}

}
