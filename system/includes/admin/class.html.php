<?php

/**
 * 
 */
class Admin_Html {

	public static function loadAdminHeader($mixOptions="", $frameset=0, $strBody="", $charset="UTF-8", $bSubmitStatus=1, $bolXhtml=1, $tailwind = false) {
		global $page_data, $objWebDynamics, $system_data;

		\System::wd()->executeHook('admin_header', $mixOptions);

		if(is_array($mixOptions)) {
			if(isset($mixOptions['additional'])) {
				$strAdditional	= $mixOptions['additional'];
			}
			if(isset($mixOptions['body'])) {
				$strBody 		= $mixOptions['body'];
			}
			if(isset($mixOptions['xhtml'])) {
				$bolXhtml 		= $mixOptions['xhtml'];
			}
		} else {
			$strAdditional = $mixOptions;
			$mixOptions = array();
		}

		if(!$charset) {
			$charset = "UTF-8";
		}

		if(!isset($mixOptions['left_frame'])) {
			$mixOptions['left_frame'] = 1;
		}

		
	?>
	<!DOCTYPE html>
	<html>
		
		<head>

		<meta http-equiv="Content-Type" content="text/html; charset=<?=$charset?>"/>

<?php
	$oSmarty = new SmartyWrapper();
	$oSmarty->display(Factory::executeStatic('\Admin_Html', 'getHeadIncludeFile', [$tailwind]));
?>
		
		<link rel="stylesheet" href="/admin/css/admin.css"/>

	<?
		echo (string)($mixOptions['additional_top'] ?? '');

		if(isset($mixOptions['jslib']) && $mixOptions['jslib'] == 'jquery_1_10') {
	?>

		<link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
		<script src="//code.jquery.com/jquery-1.10.2.js"></script>
		<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
		
		<?php
		self::loadTinyMCEJS();
		?>
		
		<script src="/admin/js/admin_jquery.js"></script>
		
		<script type="text/javascript" src="/admin/js/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>
		<script type="text/javascript" src="/admin/js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
		<link rel="stylesheet" type="text/css" href="/admin/js/fancybox/jquery.fancybox-1.3.4.css" media="screen" />

	<?
		if($bSubmitStatus) {
	?>
		<script type="text/javascript">
			$(window).on('beforeunload', function() {
				_doUnload();
			});
		</script>
	<?
			}

		} elseif(isset($mixOptions['jslib']) && $mixOptions['jslib'] == 'jquery') {
	?>

		<script type="text/javascript" src="/admin/js/jquery/jquery.js"></script>
		<script type="text/javascript" src="/admin/js/jquery/jquery.autocomplete.js"></script>
		<script type="text/javascript" src="/admin/js/jquery/jquery.date_input.js"></script>
		<script type="text/javascript" src="/admin/js/jquery/jquery.dimensions.js"></script>

	<?
		if($bSubmitStatus) {
	?>
		<script type="text/javascript">
			$(window).on('beforeunload', function() {
				_doUnload();
			});
		</script>
	<?
			}

		} else {
	?>

		<script type="text/javascript" src="/admin/js/prototype/prototype.js"></script>
		<script type="text/javascript" src="/admin/js/litbox/litbox.js"></script>
		<script type="text/javascript" src="/admin/js/control/control.tabs.js"></script>

		<script type="text/javascript" src="/admin/js/gui/gui.js"></script>
		<script type="text/javascript" src="/admin/js/gui/template.js"></script>
		<script type="text/javascript" src="/admin/js/gui/table.js"></script>

		<?php
		self::loadTinyMCEJS();
		?>

		<script type="text/javascript" src="/admin/js/admin.js"></script>
		<script type="text/javascript" src="/admin/js/hook.js"></script>

		<link rel="stylesheet" href="/admin/css/litbox.css"/>

		<script type="text/javascript">

			var iLeftFrameWidth = <?=(int)$system_data['frame_width']?>;

	<?
	
		if($bSubmitStatus) {
	?>
			Event.observe(window, 'beforeunload', _doUnload);

	<?
			}
	?>
		</script>

	<?
		}
	?>

		<style><?=System::getSystemColorStyles()?></style>

		<?=$strAdditional?>
		<?= (string)($mixOptions['additional_bottom'] ?? ''); ?>
		<title><?=$page_data['htmltitle']??''?></title>

	</head>
	<?
		if($frameset == 0) {
	?>
	<body class="skin-blue" <?=$strBody?> style="background: rgb(236, 240, 245) none repeat scroll 0% 0%; position: relative; height: auto; min-height: 100%;">
		<div class="page-loader">
			<div class="pl-cube1 pl-cube"></div>
			<div class="pl-cube2 pl-cube"></div>
			<div class="pl-cube4 pl-cube"></div>
			<div class="pl-cube3 pl-cube"></div>
		</div>
	<?
		}

	}

	public static function getHeadIncludeFile($tailwind = false) {

        if ($tailwind) {
			return 'system/bundles/Admin/Resources/views/head.css.inc.tpl';
		}

		return 'system/bundles/AdminLte/Resources/views/head.css.inc.tpl';		
	}
	
	private static function loadTinyMCEJS() {
		$oAccess = Access::getInstance();
		if(
			$oAccess instanceof Access_Backend &&
			$oAccess->checkValidAccess()
		) {
			if(System::d('debugmode') == 2) {
				echo '<script type="text/javascript" src="/tinymce/resource/basic/tinymce.js"></script>';	
			} else {
				echo '<script type="text/javascript" src="/tinymce/resource/basic/tinymce.min.js"></script>';
			}
		}
	}
	
	public static function loadAdminFooter($sAdditional="", $bFrameset=false) {

		$sCode = "";

		if($bFrameset === false) {
			
			ob_start();

			$oSmarty = new SmartyWrapper();
			$oSmarty->assign('bJqueryNoConflict', true);
			$oSmarty->display('system/bundles/AdminLte/Resources/views/footer.js.inc.tpl');

			// TODO Wofür wird hier ein Output-Buffer benötigt, wenn es $oSmarty->fetch() gibt?
			$sCode .= ob_get_clean();

			$sCode .= $sAdditional."\n";

			$sCode .= "</body>\n";

		}

		$sCode .= "</html>\n";

		echo $sCode;

	}
	
	public static function stripTagsContent($sText, $sTags = '', $bInvert = FALSE) { 

		$aTags = array();
		preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($sTags), $aTags); 
		$aTags = array_unique($aTags[1]); 

		if(
			is_array($aTags) && 
			count($aTags) > 0
		) { 
			if($bInvert == FALSE) { 
				return preg_replace('@<(?!(?:'. implode('|', $aTags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $sText); 
			} else { 
				return preg_replace('@<('. implode('|', $aTags) .')\b.*?>.*?</\1>@si', '', $sText); 
			} 
		} elseif($bInvert == FALSE) { 
			return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $sText); 
		} 

		return $sText; 
	}
	
	static public function generateHiddenField($sName, $mValue="", $sParameter="") {

		return '<input type="hidden" name="'.$sName.'" value="'.\Util::convertHtmlEntities($mValue).'" '.$sParameter.' />';

	}
	
	static function printFormText($title, $name, $value="", $parameter="", $idHelp=0, $mError=null) {
?>
			<div class="form-group<?=((!empty($mError))?' has-error':'')?>">
				<label for="<?=Util::getCleanFilename($name)?>" class="col-sm-2 control-label"><?=$title?> <?=printHint($idHelp)?></label>
				<div class="col-sm-10">
					<input type="text" id="<?=Util::getCleanFilename($name)?>" name="<?=$name?>" class="txt form-control" value="<?=\Util::convertHtmlEntities($value)?>" <?=$parameter?>>
					<?php
					if(is_string($mError)) {
					?>
					<span class="help-block"><?=$mError?></span>
					<?php
					}
					?>
				</div>
			</div>
<?
	}

	static public function printFormTextarea($title, $name, $value="", $rows = 5, $parameter="", $onerow=1, $idHelp=0) {
?>
			<div class="form-group">
				<label for="<?=Util::getCleanFilename($name)?>" class="col-sm-2 control-label"><?=$title?> <?=printHint($idHelp)?></label>
				<div class="col-sm-10">
					<textarea id="<?=Util::getCleanFilename($name)?>" name="<?=$name?>" class="txt form-control" rows="<?=$rows?>" <?=$parameter?>><?=\Util::convertHtmlEntities($value)?></textarea>
				</div>
			</div>
<?
	}

	static public function printFormHTMLarea($title, $name, $value="", $sToolbar="Basic", $onerow=1, $idHelp=0, $iWidth='100%', $iHeight='200') {
?>
			<div class="form-group">
				<label for="<?=Util::getCleanFilename($name)?>" class="col-sm-2 control-label"><?=$title?> <?=printHint($idHelp)?></label>
				<div class="col-sm-10">
					<textarea id="<?=Util::getCleanFilename($name)?>" name="<?=$name?>" class="txt form-control tinymce" rows="<?=$rows?>" <?=$parameter?> style="height: <?=$iHeight?>;width: <?=$iWidth?>"><?=\Util::convertHtmlEntities($value)?></textarea>
				</div>
			</div>
<?
	}

	static public function printFormCheckbox($title, $name, $value="1", $checked=false, $onerow=1, $idHelp=0, $iWidth="30", $parameter="") {
?>
			<div class="form-group">
				<label for="<?=Util::getCleanFilename($name)?>" class="col-sm-2 control-label"><?=$title?> <?=printHint($idHelp)?></label>
				<div class="col-sm-10">
					<input type="hidden" name="<?=$name?>" value="0"><input type="checkbox" name="<?=$name?>" id="<?=Util::getCleanFilename($name)?>" value="<?=\Util::convertHtmlEntities($value)?>" <?=(($checked)?"checked":"")?> <?=$parameter?> />
				</div>
			</div>
<?
	}

	static public function printFormSelect($title, $name, $values, $value=-1, $parameter="", $onerow=1, $idHelp=0, $iWidth="30") {
?>
			<div class="form-group">
				<label for="<?=Util::getCleanFilename($name)?>" class="col-sm-2 control-label"><?=$title?> <?=printHint($idHelp)?></label>
				<div class="col-sm-10">
					<select id="<?=Util::getCleanFilename($name)?>" name="<?=$name?>" class="txt form-control" <?=$parameter?>>
						<?
						foreach($values as $key=>$val) {
							echo "<option value=\"".\Util::convertHtmlEntities($key)."\" ".(($key==$value)?"selected":"").">".$val."</option>";
						}
						?>
					</select>
				</div>
			</div>
<?
	}

	static public function printFormPageSelect($title, $name, $value="", $mixOptions=0, $extra="", $bIdValue=0, $sWhere="", $iWidth="30", $strLanguageCode=false, $intSiteId=false, $sRight=false) {

		if(!is_array($mixOptions)) {
			$mixTmpOptions = $mixOptions;
			$mixOptions = array();
			$mixOptions['empty'] 		= $mixTmpOptions;
			$mixOptions['extra'] 		= $extra;
			$mixOptions['value_type'] 	= $bIdValue;
			$mixOptions['where'] 		= $sWhere;
			$mixOptions['language'] 	= $strLanguageCode;
			$mixOptions['site_id'] 		= $intSiteId;
			$mixOptions['right'] 		= $sRight;
		}
?>
			<div class="form-group">
				<label for="<?=Util::getCleanFilename($name)?>" class="col-sm-2 control-label"><?=$title?></label>
				<div class="col-sm-10">
					<?=printPageSelect($name, $value, $mixOptions)?>
				</div>
			</div>
<?
	}

	static public function printFormMultiSelect($title, $name, $values, $value=[], $parameter="", $onerow=1, $idHelp=0, $iWidth="30") {
?>
			<div class="form-group">
				<label for="<?=Util::getCleanFilename($name)?>" class="col-sm-2 control-label"><?=$title?> <?=printHint($idHelp)?></label>
				<div class="col-sm-10">
					<select id="<?=Util::getCleanFilename($name)?>" name="<?=$name?>[]" class="txt form-control" <?=$parameter?> multiple>
						<?
						foreach($values as $key=>$val) {
							echo "<option value=\"".\Util::convertHtmlEntities($key)."\" ".((in_array($key, $value))?"selected":"").">".$val."</option>";
						}
						?>
					</select>
				</div>
			</div>
<?
	}

	static public function printFormDate($title, $name, $value="", $parameter="", $onerow=1, $idHelp=0, $sFormat="%x %X") {
		if($value>0)
			$value = strftime($sFormat,$value);
		else
			$value = "";
	?>
			<div class="form-group">
				<label for="<?=Util::getCleanFilename($name)?>" class="col-sm-2 control-label"><?=$title?> <?=printHint($idHelp)?></label>
				<div class="col-sm-10">
					<input type="text" name="<?=$name?>" class="txt form-control w300 datepicker" value="<?=$value?>" <?=$parameter?>>
				</div>
			</div>
	<?
	}

	static public function printFormDateTime($title, $name, $value="", $parameter="", $onerow=1, $idHelp=0, $sFormat="%x %X") {

		if($value>0)
			$value = strftime($sFormat,$value);
		else
			$value = "";
	?>
			<div class="form-group">
				<label for="<?=Util::getCleanFilename($name)?>" class="col-sm-2 control-label"><?=$title?> <?=printHint($idHelp)?></label>
				<div class="col-sm-10">
					<input type="text" name="<?=$name?>" class="txt form-control w300 datetimepicker" value="<?=$value?>" <?=$parameter?>>
				</div>
			</div>
	<?
	}

}
