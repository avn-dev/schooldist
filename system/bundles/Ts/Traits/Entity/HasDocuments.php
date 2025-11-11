<?php

namespace Ts\Traits\Entity;

trait HasDocuments
{
	/**
	 * @param $sDocumentType
	 * @param $bRelational
	 * @return \Ext_Thebing_Inquiry_Document
	 * @throws \Exception
	 */
	public function newDocument(string $sDocumentType = 'brutto', bool $bRelational = true): \Ext_Thebing_Inquiry_Document {

		$this->completeJoinedObjectConfig();

		if ($bRelational) {
			/** @var \Ext_Thebing_Inquiry_Document $oDocument */
			$oDocument = $this->getJoinedObjectChild('documents');
			$oDocument->type = $sDocumentType;
			return $oDocument;
		}

		// static_key_field kann mit getJoinedObjectChild() nicht mehr geändert werden (was auch korrekt ist), daher new
		$oDocument = new \Ext_Thebing_Inquiry_Document();
		$oDocument->entity = $this::class;
		$oDocument->entity_id = $this->id;
		$oDocument->type = $sDocumentType;

		return $oDocument;
	}

	private function completeJoinedObjectConfig(): void
	{
		if (isset($this->_aJoinedObjects['documents'])) {
			return;
		}

		$this->_aJoinedObjects['documents'] = [
			'class' => \Ext_Thebing_Inquiry_Document::class,
			'key' => 'entity_id',
			'static_key_fields'=> ['entity' => $this::class],
			'type' => 'child',
			'check_active' => true,
			'query' => false,
//			'bidirectional' => true,
			'on_delete' => 'cascade', // Wird in delete() ggf. überschrieben
			'cloneable' => false
		];

	}

	/**
	 * @param array|string $documentTypes
	 * @return \Ext_Thebing_Inquiry_Document[]
	 * @throws \Exception
	 */
	public function getDocumentsOfTypes(array|string $documentTypes): array
	{
		$search = new \Ext_Thebing_Inquiry_Document_Search($this->getId());
		$search->setType($documentTypes);
		$search->setObjectType($this::class);

		return $search->searchDocument();
	}

}