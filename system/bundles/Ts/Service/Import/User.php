<?php

namespace Ts\Service\Import;

use Tc\Exception\Import\ImportRowException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class User extends AbstractImport {
	
	protected $sEntity = \Ext_Thebing_User::class;
	
	public function getFields() {

		$aFields = [];
		$aFields[0] = ['field'=> 'Vorname', 'target' => 'firstname'];
		$aFields[1] = ['field'=> 'Nachname', 'target' => 'lastname'];
		$aFields[2] = ['field'=> 'E-Mail', 'target' => 'email'];
		$aFields[3] = ['field'=> 'Passwort', 'target' => 'password'];

		return $aFields;
	}
	
	protected function getBackupTables() {
		return [];
	}
	
	protected function getCheckItemFields(array $aPreparedData) {

	}

	/**
	 * @see \Ext_TS_Enquiry_Gui2_Icon_Visible
	 */
	protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData=null) {

		try {

			$aData = [];
			\Ext_Thebing_Import::processItems($this->aFields, $aItem, $aData);

			$oUser = new \Ext_Thebing_User();
			$oUser->status = 1;

			foreach($aData as $sField => $mValue) {
				$oUser->$sField = $mValue;
			}

			$mValidate = $oUser->validate(false);

			if (is_array($mValidate)) {
				foreach ($mValidate as $sField => $mErrors) {
					foreach (Arr::wrap($mErrors) as $sError) {
						throw (new ImportRowException(\Ext_Thebing_User_Gui2_Data::convertErrorKeyToMessage($sError)))
							->pointer("", $iItem, array_search(Str::afterLast($sField, '.'), array_keys($aData)));
					}
				}
			}

			$firstRole = \Ext_Thebing_Admin_Usergroup::query()->pluck('name')->first();

			$oUser->updateRoles([$firstRole], false);
			$oUser->save();

			$this->aReport['insert']++;

			return $oUser->getId();

		} catch(\Exception $e) {

			if ($e instanceof ImportRowException && $e->hasPointer()) {
				$this->aErrors[$iItem] = [['message' => $e->getMessage(), 'pointer' => $e->getPointer()]];
			} else {
				$this->aErrors[$iItem] = [['message' => $e->getMessage(), 'pointer' => new ErrorPointer("", $iItem)]];
			}

			$this->aReport['error']++;
			
			if(empty($this->aSettings['skip_errors'])) {
				throw new \Exception('Terminate import');
			}
	
		}
		
	}

}
