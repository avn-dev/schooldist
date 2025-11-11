<?php

namespace Tc\Controller;

class UploaderController extends \MVC_Abstract_Controller {

	public function request() {

		$_VARS = $this->_oRequest->getAll();
		
		if($_VARS['task'] == 'init_uploader'){

			$sNamespace = $_VARS['namespace'];
			$iNamespace = (int)$_VARS['namespace_id'];
			$iSelected	= (int)$_VARS['selected_id'];
			$bMultiple  = (bool)$_VARS['multiple'];
			$bDrop      = (bool)$_VARS['drop'];

			$oBuilder = new \Ext_TC_Uploader_Builder($iSelected, $iNamespace, $sNamespace);
			$oBuilder->setMultiple($bMultiple);
			$oBuilder->setDragDrop($bDrop);
			$oBuilder->setAllFileTypes();
			$sHtml = $oBuilder->generateHTML();
			echo $sHtml;
			die();

		} else if($_VARS['task'] == 'remove_uploader'){

			$oHander = new \Ext_TC_Uploader_Handler($_VARS['tc_upload_id']);
			if($_VARS['delete_files']){
				$oHander->deleteFiles();
			}
			$oHander->remove();

		}  else if($_VARS['task'] == 'remove_files'){

			$oHander = new \Ext_TC_Uploader_Handler($_VARS['tc_upload_id']);
			$oHander->deleteFiles();
			echo \L10N::t('Dateien erfolgreich gelöscht');
			die();

		} else if($_VARS['task'] == 'start_tc_upload'){

			$oHander = new \Ext_TC_Uploader_Handler($_VARS['tc_upload_id']);
			$oHander->upload();
			$sResponse = $oHander->getResponse();
			echo $sResponse;
			die();

		}
		
	}
	
}
