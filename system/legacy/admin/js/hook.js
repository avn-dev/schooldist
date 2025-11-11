
var oWdHooks  = new Object();

oWdHooks.aHooks = new Object();

oWdHooks.addHook = function (sHook, sExtension) {
	if(!this.aHooks[sHook]) {
		this.aHooks[sHook] = new Array();
	}
	this.aHooks[sHook][this.aHooks[sHook].length] = sExtension;
}

oWdHooks.executeHook = function (sHook, mInput, mData) {
	if (this.aHooks[sHook]) {
		this.aHooks[sHook].each(function(sKey){
			var sCode = 'var executeHook = new Function("sHook", "mInput", "mData", "mInput = executeHook_' + sKey + '_' + sHook + '(sHook, mInput, mData); return mInput;");';
			eval(sCode);
			mInput = executeHook(sHook, mInput, mData);
		});
	}
	return mInput;
}