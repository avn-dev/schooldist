<?php

namespace Ts\Hook;

use Core\Exception\Entity\ValidationException;

/**
 * Verwendung in den Formularen überprüfen
 *
 * @see \Ext_TC_Flexibility::HOOK_VALIDATE
 */
class FlexFieldValidate extends \Core\Service\Hook\AbstractHook
{
	public function run(mixed &$validate, \Ext_TC_Flexibility $field): void
	{
		if (
			$validate === true && (
				$field->active != $field->getOriginalData('active') ||
				$field->type != $field->getOriginalData('type')
			)
		) {
			$result = \DB::table('kolumbus_forms_pages_blocks_settings', 'kfpbs')
				->select(['kf.title', 'kfp.position'])
				->join('kolumbus_forms_pages_blocks as kfpb', 'kfpb.id', 'kfpbs.block_id')
				->join('kolumbus_forms_pages as kfp', 'kfp.id', 'kfpb.page_id')
				->join('kolumbus_forms as kf', 'kf.id', 'kfp.form_id')
				->where('setting', 'type')
				->where('value', 'flex_'.$field->id)
				->whereIn('kfpb.block_id', \Ext_Thebing_Form_Page_Block::TYPES_INPUTS)
				->where('kfpb.active', 1)
				->where('kfp.active', 1)
				->where('kf.active', 1)
				->first();

			if ($result !== null) {
				$label = str_replace(['{form}', '{page}'], [$result['title'], $result['position']], \L10N::t("Formular '{form}' (Seite {page})", \Ext_Thebing_Form_Gui2::$sDescription));
				throw (new ValidationException('FIELD_IN_USE'))->setAdditional(['label' => $label]);
			}
		}
	}
}
