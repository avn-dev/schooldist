<?php

namespace Tc\Traits\Gui2;

trait ImageTab
{

	protected function writeImageTabHTML()
	{
		$smarty = new \SmartyWrapper();
		$dir = \Factory::executeStatic(\Ext_TC_Upload::class, 'getUploadDir', [false, false]);
		$url = \System::d('domain').'/media'.$dir;

		$images = \Factory::executeStatic(\Ext_TC_Upload::class, 'getImages');

		$smarty->assign('lang_image', $this->_oGui->t('Bild'));
		$smarty->assign('lang_link', $this->_oGui->t('Link'));
		$smarty->assign('link', $url);

		$imageFormat = \Factory::getObject(\Ext_TC_Gui2_Format_Image::class, [$dir, $this->_oGui->t('Vorschau'), 180]);

		foreach($images as &$image) {
			$image['preview'] = $imageFormat->format($image['filename']);
		}

		$smarty->assign('aImages', $images);

		$code = $smarty->fetch(\Util::getDocumentRoot().'system/bundles/Communication/Resources/views/email_layout_pics.tpl');

		$code = str_replace(array("\n", "\t"), '', $code);

		return $code;
	}

}