<?php

namespace Ts\Interfaces\Entity;

/*
 * Interface für den Dokumenten-Dialog (Ext_Thebing_Document)
 */
interface DocumentRelation {

	public function getSchool();

	public function getCurrency();

	public function getDocumentLanguage();

	public function newDocument(string $sDocumentType = 'brutto', bool $bRelational = true);

	public function getTypeForNumberrange($sDocumentType, $mTemplateType = null);
}
