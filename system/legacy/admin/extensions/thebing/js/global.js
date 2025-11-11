
function showLBTitle(sMessage, bSuccess) {

	if($('LB_title')){

		if(bSuccess) {
			$('LB_title').update('<span style="color: green;">'+sMessage+'</span>');
		} else {
			$('LB_title').update('<span style="color: red;">'+sMessage+'</span>');
		}
		clearLBTitle();

	}

}

function clearLBTitle(){
	
	new PeriodicalExecuter(function(pe) {
	  	if($('LB_title')){
	  		$('LB_title').update("");
	  	}
	    pe.stop();
	}, 5);
	
}