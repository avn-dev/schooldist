
// draws a chart with plotkit
function drawBarChart(strCanvas, arrInput, strStyle, arrYTicks) {

	var arrXTicks 	= new Array();
	var arrValues 	= new Array();
	var arrColors 	= new Array();
	var intCounter 	= 0;

	arrInput.each(function(objItem){
		arrXTicks.push({v:intCounter, label:objItem.label});
		arrValues.push([intCounter, objItem.value]);
		if(!objItem.color) {
			objItem.color = MochiKit.Color.Color.fromHexString('#29527A');
		} else {
			objItem.color = MochiKit.Color.Color.fromHexString(objItem.color);
		}
		arrColors.push(objItem.color);
		intCounter++;
	});

	var intPaddingLeft = 70;
	if(strStyle == 'vertical') {
		//intPaddingLeft = 30;
	}

	var objPlotkitOptions = {
	   	"IECanvasHTC": 		"/admin/js/plotkit/iecanvas.htc",
		"colorScheme":		arrColors,
		"backgroundColor":	MochiKit.Color.Color.fromHexString('#e6e6e6'),	
	   	"padding": 			{left: intPaddingLeft, right: 10, top: 10, bottom: 20},
	   	"xTicks": 			arrXTicks, 
	   	"yTicks": 			arrYTicks, 
	   	"axisLabelColor": 	MochiKit.Color.Color.fromHexString('#666666'),
	   	"axisLineWidth": 	0,
	   	"axisLineColor": 	MochiKit.Color.Color.fromHexString('#ffffff'),
		"drawXAxis":		true,
		"drawYAxis":		true, 
		"yTickPrecision":	0,
		"barOrientation":	strStyle,
		"enableEvents": 	false
	};

    var objLayout = new PlotKit.Layout("bar", objPlotkitOptions);
	objLayout.addDataset("data1", arrValues);
    objLayout.evaluate();

	//var objCanvas = MochiKit.DOM.getElement(strCanvas);
	var objCanvas = document.getElementById(strCanvas);

    var objPlotter = new PlotKit.SweetCanvasRenderer(objCanvas, objLayout, objPlotkitOptions);
    objPlotter.render();	
    
}

function drawPieChart(strCanvas, arrInput) {

	var arrColorValues 	= [ 
								'#cc0033',
								'#eb99ad',
								'#9cd7f6',
								'#bae3f9',
								'#d7effb',
								'#ddb98e',
								'#F5BE01',
								'#416100',
								'#97AA00',
								'#59BDEF',
								'#144C99',
								'#5E3466',
								'#C06D67',
								'#CC6901',
								'#C0CC66',
								'#686262',
								'#db4d71',
								'#A37F65',
								'#9B9190',
								'#EE7900'
							];

	var arrXTicks 		= [];
	var arrHits 		= [];
	var arrColorScheme	= [];
	var intCounter		= 0;
	var intColorCounter = 0;

	arrInput.each(function(objItem){

		arrXTicks.push({v:intCounter, label:''});
		arrHits.push([intCounter, +objItem.value]);
		arrColorScheme.push(Color.fromHexString(arrColorValues[intColorCounter]));
		
		if(intColorCounter == 15){
			intColorCounter = 0;
		}
		
		intCounter++;
		intColorCounter++;
	});

	var objPlotkitOptions = {
	   	"IECanvasHTC": 		"/admin/js/plotkit/iecanvas.htc",
		"colorScheme":		arrColorScheme,
		"backgroundColor":	MochiKit.Color.Color.fromHexString('#f7f7f7'),
	   	"padding": 			{left: 70, right: 30, top: 50, bottom: 30},
	   	"xTicks": 			arrXTicks, 
		"drawYAxis":		false, 
		"pieRadius": 		0.60
	};

    var objLayout = new PlotKit.Layout("pie", objPlotkitOptions);
	objLayout.addDataset("sqrt", arrHits);
    objLayout.evaluate();
	
	var strCanvasId = strCanvas;
	var objCanvas = MochiKit.DOM.getElement(strCanvasId);
	var objPlotter = new PlotKit.SweetCanvasRenderer(objCanvas, objLayout, objPlotkitOptions);
    objPlotter.render();

	/**
	 * 	after all that render the legend - this is gonna be cruel
	 */
	var strCanvasLegend	 	= strCanvas+'Legend';
	var objLegendContainer 	= $(strCanvasLegend);
	var strLegendText 		= '<ul>';
	var intCounter			= 0;

	arrInput.each(function(objItem){
		strLegendText += '<li style="color:'+arrColorValues[intCounter]+';font-size:18px;"><span style="vertical-align:middle;color:#000;font-size:10px;">'+objItem.label+' ('+objItem.value+')</span></li>';
		intCounter++;
	});
	strLegendText += '</ul>';
	objLegendContainer.innerHTML = strLegendText;

	return true;

}
