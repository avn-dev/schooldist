<?php

namespace Notices\Http\Controller;

use Core\Handler\SessionHandler;
use Notices\Entity\Notice;

class InterfaceController extends \MVC_Abstract_Controller {

	/**
	 *
	 */
	public function ViewAction(\MVC_Request $request) {

		$sClass = $request->get('entity');
		$iId = (int)$request->get('id');
		$sNotice = $request->get('notice');

		$aTransfer = [];

		if(
			!empty($sNotice) &&
			\Access_Backend::getInstance()->hasRight(['core_communication_notes', 'new'])
		) {

			$notice = new Notice();
			$notice->entity = $sClass;
			$notice->entity_id = $iId;
			$latestVersion = $notice->getJoinedObjectChild('versions');
			$latestVersion->notice = $sNotice;
			$notice->save();

			SessionHandler::getInstance()->getFlashBag()->add('success', \L10N::t('Die Notiz wurde erfolgreich gespeichert!'));
			\Log::add(Notice::LOG_NOTICE_CREATED, $notice->id, get_class($notice));
		}
		
		$aTransfer['sClass'] = $sClass;
		$aTransfer['iId'] = $iId;

		$oEntity = $this->getEntity($sClass, $iId);
		$aTransfer['oEntity'] = $oEntity;

		$oRepo = Notice::getRepository();

		if(
			$oEntity instanceof \WDBasic &&
			$oEntity->id > 0
		) {
			$aNotices = $oRepo->getByEntity($oEntity);
		} else {
			$aNotices = $oRepo->getByEntity();
		}

		$aTransfer['aNotices'] = $aNotices;
		$aTransfer['oController'] = $this;
		$aTransfer['session'] = SessionHandler::getInstance();

		return response()->view('notices', $aTransfer);
	}
		
	public function getEntity($sClass, $iId) {
		
		if(class_exists($sClass)) {
			
			$oReflection = new \ReflectionClass($sClass);
			$bEntityClass = $oReflection->isSubclassOf('WDBasic');
		
			if($bEntityClass === true) {
				$oEntity = $sClass::getInstance($iId);
				return $oEntity;
			}
			
		}
		
	}

	public function delete(\MVC_Request $request, $sClass, $iId) {

		$noticeId = (int)$request->get('noticeId');

		$notice = Notice::getInstance($noticeId);

		if(
			$notice->entity == $sClass &&
			$notice->entity_id == $iId &&
			\Access_Backend::getInstance()->hasRight(['core_communication_notes', 'delete'])
		) {
			$notice->delete();
			SessionHandler::getInstance()->getFlashBag()->add('success', \L10N::t('Die Notiz wurde gelöscht!'));
			\Log::add(Notice::LOG_NOTICE_DELETED, $notice->id, get_class($notice));
		} else {
			SessionHandler::getInstance()->getFlashBag()->add('error', \L10N::t('Die Notiz wurde nicht gelöscht!'));
		}

		$this->redirectUrl('/notices/interface/view?entity='.$sClass.'&id='.$iId, false);
	}

	public function save(\MVC_Request $request, $sClass, $iId) {

		$noticeIdAndMessage = $request->input('versions');
		$noticeId = array_key_first($noticeIdAndMessage);
		$noticeMessage = $noticeIdAndMessage[$noticeId];

		$notice = Notice::getInstance($noticeId);

		if(
			$notice->entity == $sClass &&
			$notice->entity_id == $iId &&
			!empty($noticeMessage) &&
			\Access_Backend::getInstance()->hasRight(['core_communication_notes', 'edit'])
		) {
			$latestVersion = $notice->getJoinedObjectChild('versions');
			$latestVersion->notice = $noticeMessage;
			$latestVersion->save();
			SessionHandler::getInstance()->getFlashBag()->add('success', \L10N::t('Die Notiz wurde erfolgreich editiert!'));
			\Log::add(Notice::LOG_NOTICE_UPDATED, $notice->id, get_class($notice));
		} else {
			SessionHandler::getInstance()->getFlashBag()->add('error', \L10N::t('Die Notiz wurde nicht editiert!'));
		}

		$this->redirectUrl('/notices/interface/view?entity='.$sClass.'&id='.$iId, false);
	}
	
}