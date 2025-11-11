<?php

namespace Ts\Gui2\Selection\Numberrange;

use Illuminate\Support\Arr;

class Companies extends \Ext_Gui2_View_Selection_Abstract {
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
	
		$companies = [];
		if($this->oJoinedObject instanceof \Ext_TS_NumberRange_Allocation_Set) {
			/** @var \Ext_Thebing_School $school */
			foreach($oWDBasic->getJoinTableObjects('schools') as $school) {

				$companyEntities = \TsAccounting\Entity\Company::query()
					->select('ts_com.*')
					->join('ts_accounting_companies_combinations as ts_com_c', 'ts_com_c.company_id', '=', 'ts_com.id')
					->join('ts_accounting_companies_combinations_to_schools as ts_accts', 'ts_com_c.id', '=', 'ts_accts.company_combination_id')
					->where('ts_accts.school_id', '=', $school->id)
					->get();

				$schoolCompanies = Arr::pluck($companyEntities, 'name', 'id');
					
				if(empty($companies)) {
					$companies = $schoolCompanies;
				}
				
				$companies = array_intersect_assoc($schoolCompanies, $companies);
				
			}
		}
		
		return $companies;		
	}		
	
}
