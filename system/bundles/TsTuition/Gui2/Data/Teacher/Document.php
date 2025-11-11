<?php

namespace TsTuition\Gui2\Data\Teacher;

use Illuminate\Support\Arr;

class Document extends \Ext_Thebing_Document_Gui2
{
	public function getSelectedObject(int $iObjectId = null): \Ts\Interfaces\Entity\DocumentRelation
	{
		if ($iObjectId !== null) {
			return $this->_getParentGui()->getWDBasic($iObjectId);
		}

		$parentGuiId = Arr::first($this->request->input('parent_gui_id'));
		/** @var \Ext_Thebing_Teacher $teacher */
		$teacher = $this->_getParentGui()->getWDBasic($parentGuiId);

		return $teacher;
	}
}