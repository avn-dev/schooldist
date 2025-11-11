function ThebingFormCombination(oForm, oMapping, oThebingHelper) {
	this.oForm = oForm;
	this.oMapping = oMapping;
	this.oThebingHelper = oThebingHelper;
}

ThebingFormCombination.prototype = {
	
	spreadCombination : function(oField, oCombination) {
		
		if(!oCombination) {
			oCombination = {};
		}
		
		var oFieldMapping = this.getFieldMapping(oField);
		
		if(oFieldMapping.hasOwnProperty('dependencies')) {

			var mValue = oField.value;

			var sCombinationIndex = mValue;
			if(
				!oCombination[sCombinationIndex] &&
				oCombination.hasOwnProperty('*')
			) {
				sCombinationIndex = '*';
			}

			for(var i = 0; i <= oFieldMapping.dependencies.length; ++i) {

				var sDependencyIdentifier = oFieldMapping.dependencies[i];			
				var oChildCombination = null;
				var bPrepareMany = false;		

				if(
					oCombination[sCombinationIndex] &&
					oCombination[sCombinationIndex][sDependencyIdentifier]
				) {
					oChildCombination = oCombination[sCombinationIndex][sDependencyIdentifier];
				}

				if(oFieldMapping.hasOwnProperty('prepare_many')) {
					bPrepareMany = oFieldMapping.prepare_many;
				} else if(oFieldMapping.hasOwnProperty('dependency_requirements')) {
					var oDependencyRequirements = oFieldMapping['dependency_requirements'];
					if(
						oDependencyRequirements.hasOwnProperty(sDependencyIdentifier) &&
						oDependencyRequirements[sDependencyIdentifier].hasOwnProperty('prepare_many')
					) {
						bPrepareMany = oDependencyRequirements[sDependencyIdentifier]['prepare_many'];
					}
				}

				if(!oChildCombination) {
					oChildCombination = {};
				}

				if(bPrepareMany) {

					var aElements = this.oThebingHelper.getElementsByClass(sDependencyIdentifier, this.oForm);

					for(var j = 0; j < aElements.length; ++j) {
						var oDependencyField = aElements[j];
						this.bindCombinationToField(oDependencyField, oChildCombination, oField);
					}

				} else {

					var oContainer = this.oThebingHelper.getParentElementByClass(oField, 'thebing-dependency-container');

					if(oContainer) {
						var aDependencyField = this.oThebingHelper.getElementsByClass(sDependencyIdentifier, oContainer);
						var oDependencyField = aDependencyField[0];

						if(oDependencyField) {
							this.bindCombinationToField(oDependencyField, oChildCombination, oField);
						}
					}
				}

			}

		}			
		
	},
	
	bindCombinationToField : function(oField, oCombination, oParentField) {
		
		var oFieldMapping = this.getFieldMapping(oField);
		
		this.setCombinationValues(oField, oCombination, oParentField);
		console.debug(oField, oCombination, oParentField);
		
		this.spreadCombination(oField, oCombination);
	},
	
	setCombinationValues : function(oField, oCombination, oParentField) {
		
		var oFieldMapping = this.getFieldMapping(oField);
		
		if(oField) {
			var aSelectOptions = new Array();
			if(oCombination) {			
				aSelectOptions = this.buildSelectOptionsFromCombination(oCombination);			
			}
			
			if(oField.nodeName === 'SELECT') {
				
			}
		}
		
	},
	
	getMappingIdentifier : function(oField) {
		if(
			oField &&
			oField.className
		) {
			var aClassNames = oField.className.split(' ');
			var sPrefix = 'thebing_';
			
			for(var i = 0; i < aClassNames.length; ++i) {
				if(aClassNames[i].substring(0, sPrefix.length) === sPrefix) {
					return aClassNames[i];
				}
			}
		}

		return null;
	},
	
	getFieldMapping : function(oField) {
		
		var sIdentifier = this.getMappingIdentifier(oField);

		if(
			sIdentifier === null ||
			!this.oMapping.hasOwnProperty(sIdentifier)
		) {
			throw "Unable to find field mapping"; 
		}
		
		return this.oMapping[sIdentifier];
	}
	
}
