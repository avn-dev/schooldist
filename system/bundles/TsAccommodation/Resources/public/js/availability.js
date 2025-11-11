
var aGUI = {};

var bExport = false;

$(function() {

	//$('#till')[0].selectedIndex = $('#from')[0].selectedIndex + 7;

	$( window ).resize(function() {
		setWindowSizes();
	});

	$('#from').change(function() {
		loadTable();
	});

	$('#till').change(function() {
		loadTable();
	});

	$('#school').change(function() {
		loadTable();
	});

	$('#category').change(function() {
		loadTable();
	});

	$('#view').change(function() {
		loadTable();
	});
	
	setWindowSizes();
	loadTable();
	
});

/* ==================================================================================================== */

function setWindowSizes() {

	topMargin = $('#blocksScroller').offset().top;
	
	bottomMargin = parseInt($('.box').css('margin-bottom'));
	
	$('#blocksScroller').height(($( document ).height() - topMargin - bottomMargin) + 'px');

}

/* ==================================================================================================== */

function loadTable(asExport = false)
{
	if($('#category').value == '') {
		return false;
	}

	var sParams = 'action=load_table';

	sParams += '&from=' + $('#from').val();
	sParams += '&till=' + $('#till').val();
	sParams += '&view=' + $('#view').val();
	sParams += '&category=' + $('#category').val();

	var oSchool = document.getElementById('school');
	if (oSchool) {
		sParams += '&school=' + $(oSchool).val();
	}

	if (asExport) {
		sParams += '&export=1';
		$.ajax({
			url: '/admin/ts/accommodation/availability/results',
			method: 'POST',
			data: sParams,
			xhrFields: {
				responseType: 'blob'
			},
			success: function (data) {
				const blobUrl = URL.createObjectURL(data);
				const a = document.createElement('a');
				a.href = blobUrl;
				a.download = 'availabiltiy.xlsx';
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				URL.revokeObjectURL(blobUrl);
			},
			error: function (xhr, status, error) {
				console.error('Error downloading the file:', error);
			}
		});
	} else {

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$('#loading_indicator').show();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$.ajax({
			type: "POST",
			url: '/admin/ts/accommodation/availability/results',
			data: sParams,
			success: loadTableCallback
		});
	}
	
}

/* ==================================================================================================== */

function loadTableCallback(result) {
	$('#blocksContainer').empty();

	$('#blocksContainer').html(result);

	$('#loading_indicator').hide();
}
