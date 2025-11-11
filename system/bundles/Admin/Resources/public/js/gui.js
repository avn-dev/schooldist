
function similar_text(first, second, percent) {

	if (first === null || second === null || typeof first === 'undefined' || typeof second === 'undefined') {
	  return 0;
	}

	  first = first.toLowerCase();
	  second = second.toLowerCase();

	  first += '';
	  second += '';

	var pos1 = 0,
	  pos2 = 0,
	  max = 0,
	  firstLength = first.length,
	  secondLength = second.length,
	  p, q, l, sum;

	max = 0;

	for (p = 0; p < firstLength; p++) {
		for (q = 0; q < secondLength; q++) {
			for (l = 0;
			  (p + l < firstLength) && (q + l < secondLength) && (first.charAt(p + l) === second.charAt(q + l)); l++)
			;
			if (l > max) {
				max = l;
				pos1 = p;
				pos2 = q;
			}
		}
	}

	sum = max;

	if (sum) {
		if (pos1 && pos2) {
			sum += this.similar_text(first.substr(0, pos1), second.substr(0, pos2));
		}

		if ((pos1 + max < firstLength) && (pos2 + max < secondLength)) {
			sum += this.similar_text(first.substr(pos1 + max, firstLength - pos1 - max), second.substr(pos2 + max,
			  secondLength - pos2 - max));
		}
	}

	if (!percent) {
		return sum;
	} else {
		return (sum * 200) / (firstLength + secondLength);
	}
}

function toggleNavItem(sSearch, oItem) {

	var bShow = false;

	if(oItem.find('ul.treeview-menu')) {
		oItem.find('ul.treeview-menu').children('').each(function() {
			bThisShow = toggleNavItem(sSearch, $(this));
			if(bThisShow) {
				bShow = true;
			}
		});
	}

	var sText = $.trim(oItem.children('a').text());

	var iPercent;
	if(
		!sSearch.length ||
		sSearch.length === 0
	) {
		iPercent = 100;
	} else {
		var iMatch = similar_text(sText, sSearch);
		iPercent = (iMatch/sSearch.length)*100;
	}

	if(
		bShow ||
		iPercent >= 85
	) {

		oItem.show();

		if(
			oItem.has('i.fa-angle-left').length > 0 &&
			oItem.has('ul.treeview-menu').is('.menu-open') == false
		) {
			openNavItem(oItem);
		}

		bShow = true;
		
	} else {
		
		oItem.hide();
		
		if(
			oItem.has('i.fa-angle-left').length > 0 &&
			oItem.has('ul.treeview-menu').is('.menu-open') == true
		) {
			closeNavItem(oItem);
		}
		
	}
	
	return bShow;
}


function closeNavItem(checkElement) {

	if(!checkElement.is('li')) {
		console.error('Element not valid (closeNavItem)', checkElement);
		return;
	}

	if(!checkElement.hasClass('menu-open')) {
		return;
	}
	
	checkElement.removeClass('menu-open');
	
	//Close the menu
	checkElement.children('.treeview-menu').hide();
	checkElement.removeClass("active");
}

function openNavItem(checkElement) {

	if(!checkElement.is('li')) {
		console.error('Element not valid (openNavItem)', checkElement);
		return;
	}

	if(checkElement.hasClass('menu-open')) {
		return;
	}

	checkElement.addClass('menu-open');
		
	//Open the target menu and add the menu-open class
	checkElement.children('.treeview-menu').show();

}

function loadContentByUrl(sKey, sLabel, sUrl, bFocus, bClosable) {

	var sContent = '<iframe id="content-frame" src="'+sUrl+'" style="width:100%;">';

	if(!sKey) {
		sKey = sUrl.toLowerCase().replace(/[^a-z0-9]/g, '-');
	}

	var aData = {
		key: sKey,
		content: sContent,
		title: sLabel,
		scroll: false,
		type: 'url',
		value: sUrl
	};
	
	oTabHandler.prepareContentTab(aData, undefined, bFocus, bClosable);

}

function loadContentByView(sKey, sView, bInteractive, bFocus, sParameter, bClosable) {

	if(typeof bInteractive === 'undefined') {
        bInteractive = true;
    }

	if(typeof sParameter === 'undefined') {
        sParameter = '';
    }

	if(!sKey) {
		sKey = sView.toLowerCase().replace(/[^a-z0-9]/g, '-');
	}
	
	var aData = {
		key: sKey,
		type: 'view',
		value: sView,
		data: sParameter
	};

	oTabHandler.prepareContentTab(aData, bInteractive, bFocus, bClosable);

}

function closeContentByKey(sKey) {
	var oTab = $('#content-tabs ul').find('li[data-key="'+sKey+'"]');
	if (oTab && oTab.length > 0) {
		oTabHandler.closeTab(oTab[0]);
	}
}

function loadDashboard() {
	loadContentByView('admin-dashboard', '/admin/dashboard');
}

function showFeedback(aData) {
	if(
		aData.success &&
		aData.success === true
	) {
		toastr.success(aData.message);
	} else {
		toastr.error(aData.message);
	}
}

function initAjaxForms() {

	$('.ajax_submit').each(function() {
		
		$(this).unbind('submit');
		
		$(this).submit(function(oEvent) {

			var sAction = this.action;
			var sValues = $( this ).serialize()

			$.ajax({
				type: "POST",
				url: sAction,
				data: sValues,
				success: $.proxy(handleAjaxFormResponse, this),
				dataType: 'json'
			});

			oEvent.preventDefault();

		});
	})
	
}

function handleAjaxFormResponse(oResponse) {
	
	$(this).find('.form-group').removeClass('has-error');
	$(this).find('.alert-success').css('display', 'none')
	$(this).find('.alert-danger').css('display', 'none');

	if(
		oResponse.errors &&
		Object.keys(oResponse.errors).length > 0
	) {

		Object.keys(oResponse.errors).forEach(function(sKey) {

			var oFormGroup = $('#'+sKey).closest('.form-group');
			oFormGroup.addClass('has-error');
			$(this).find('.alert-danger').css('display', '');

			if(typeof oResponse.errors[sKey] === 'string') {
				oFormGroup.find('.help-block').text(oResponse.errors[sKey]);								
			}
						
		}.bind(this));

	} else {
		$(this).find('.alert-success').css('display', '');

        $(this).trigger("dashboardReload");

		$('.tab-pane').animate({
			scrollTop: $(this).first('.alert-success').offset().top
		}, 500);

	}
	
	
}

function dashboardReloadFunction() {

    loadContentByView('admin-dashboard', '/admin/dashboard', false, false);

}

/**
 * Start oTabHandler
 */
var oTabHandler = {};

oTabHandler.aCurrentTabs = [];

oTabHandler.prepareContentTab = function(aCurrentContentData, bInteractive, bFocus, bClosable) {

	if(typeof bInteractive === 'undefined') {
        bInteractive = true;
    }

	if($('#content-'+aCurrentContentData.key).length > 0) {

		if(!bInteractive) {

            if(aCurrentContentData.type === 'url') {
                oTabHandler.openContentTab(aCurrentContentData, bFocus);
            } else {
                oTabHandler.loadContentTab(aCurrentContentData, bFocus);
            }

		} else {

			// Open new or show current
			$('#load-content-modal').modal('show');

			$('#load-content-modal .open-tab').unbind('click');
			$('#load-content-modal .open-tab').click(function() {
				$('#content-link-'+aCurrentContentData.key).tab('show');
				$('#load-content-modal').modal('hide');
			});

			// Tab wechseln per Leertaste
			$('#load-content-modal').on('shown.bs.modal', function(event) {
				$('#load-content-modal .open-tab').focus();
			});
			
			$('#load-content-modal .reload-tab').unbind('click');
			$('#load-content-modal .reload-tab').click(function() {
				if(aCurrentContentData.type === 'url') {
					oTabHandler.openContentTab(aCurrentContentData, bFocus);
				} else {
					oTabHandler.loadContentTab(aCurrentContentData, bFocus);
				}
				$('#load-content-modal').modal('hide');
			});
			
			$('#load-content-modal .new-tab').unbind('click');
			$('#load-content-modal .new-tab').click(function() {
				oTabHandler.createContentTab(aCurrentContentData, bFocus);
				$('#load-content-modal').modal('hide');
			});

        }

	} else {

		oTabHandler.createContentTab(aCurrentContentData, bFocus, bClosable);

	}

}

oTabHandler.createContentTab = function(aCurrentContentData, bFocus, bClosable) {

	if(typeof bClosable === 'undefined') {
		bClosable = true;
	}

	var aData = {};
	aData.key = aCurrentContentData.key;
	aData.title = aCurrentContentData.title;
	aData.content = aCurrentContentData.content;
	aData.scroll = aCurrentContentData.scroll;
	aData.type = aCurrentContentData.type;
	aData.value = aCurrentContentData.value;

	var iCount = 0;

	// Search for next ID
	do {

		if(iCount > 0) {
			aData.key = aCurrentContentData.key+'_'+iCount;
		}

		iCount++;

	} while($('#content-'+aData.key).length !== 0);


	var sClass = 'content-tab';
	if(aData.scroll === true) {
		sClass = 'content-tab-scroll';
	}

	var sHtml = '<a href="#content-'+aData.key+'" class="content-link" id="content-link-'+aData.key+'" aria-controls="content-'+aData.key+'" role="tab" data-toggle="tab" data-key="'+aData.key+'" data-type="'+aData.type+'" data-value="'+aData.value+'" data-title="'+aData.title+'"></a>'
	if (bClosable) {
		sHtml += ' <i id="content-close-'+aData.key+'" class="fa fa-circle-o-notch fa-spin fa-fw"></i>';
	}

	$('#content-tabs ul.nav').append('<li role="presentation" data-key="'+aData.key+'">'+sHtml+'</li>');
	$('#content-wrapper').append('<div role="tabpanel" class="tab-pane '+sClass+'" id="content-'+aData.key+'">...</div>');	

	// Next step
	if(aData.type === 'url') {
		this.openContentTab(aData, bFocus);
	} else {
		this.loadContentTab(aData, bFocus);
	}

	this.updateTabScrolling();

	$("#content-tabs .nav-tabs").sortable({
		axis: "x"
	});
    $("#content-tabs .nav-tabs").disableSelection();

}

oTabHandler.loadContentTab = function(aData, bFocus) {

	if(typeof aData.data === 'undefined') {
		aData.data = '';
	}

	$.post(
		aData.value,
		aData.data,
		$.proxy(
			function(aData, oResponse) {
				
				if(oResponse.title) {
					aData.title = oResponse.title;
				}
				if(oResponse.content) {
					aData.content = oResponse.content;
				}
				if(oResponse.scroll) {
					aData.scroll = oResponse.scroll;
				}

				this.openContentTab(aData, bFocus);
			},
			this,
			aData
		),
		'json'
	);

}

oTabHandler.openContentTab = function(aCurrentContentData, bFocus) {

	if(typeof bFocus === 'undefined') {
        bFocus = true;
	}

	var aData = {};
	aData.key = aCurrentContentData.key;
	aData.title = aCurrentContentData.title;
	aData.content = aCurrentContentData.content;
	aData.scroll = aCurrentContentData.scroll;
	aData.type = aCurrentContentData.type;
	aData.value = aCurrentContentData.value;

	var oTab = $('#content-'+aData.key);
	var oLink = $('#content-link-'+aData.key);
	var oClose = $('#content-close-'+aData.key);

	oLink.text(aData.title);
	oTab.html(aData.content);

	if (oClose) {
		oClose.attr('class', 'fa fa-fw fa-close');
	}

	var sClass = 'content-tab';
	if(aData.scroll === true) {
		sClass = 'content-tab-scroll';
	}

	$(oTab).removeClass('content-tab content-tab-scroll');
	$(oTab).addClass(sClass);
	
	this.updateContentTabsEvents(aData.key);

	if(bFocus === true) {
		$('#content-link-'+aData.key).tab('show');
	}

	this.resizeContentTabs();
	
	$('.box').boxWidget();

	initAjaxForms();
	initPasswordInputs();

	this.updateTabScrolling();

}

oTabHandler.getTabIndex = function(oTab) {

	var iTabIndex = this.aCurrentTabs.findIndex(function(oCurrentTab, iIndex, aTabs) {

		if(this == oCurrentTab.key) {
			return true;
		}

	}, $(oTab).data('key'));
	
	return iTabIndex;
}

oTabHandler.updateContentTabsEvents = function(sKey) {

	$('#content-link-'+sKey).on('shown.bs.tab', $.proxy(function (e) {

		if(e.target) {

			oTab = $(e.target).parent();
			sKey = oTab.data('key');
			
			var iNewIndex = this.getTabIndex(oTab);

			// Schon im Index -> enfernen
			if(iNewIndex !== -1) {
				this.aCurrentTabs.splice(iNewIndex, 1);
			}

			this.aCurrentTabs.push({
				key: sKey,
			});

			// Tab in Navi als aktiv markieren
			this.activateNavigation(oTab);

		}

	}, this));

	// Close icon
	$('#content-close-'+sKey+'').click($.proxy(this.closeTab, this));

}

oTabHandler.activateNavigation = function(oTab) {
	// Alles deaktivieren und einklappen
	$('#nav-container li').removeClass('active');
	
	if($('#nav-container li.menu-open').length > 0) {
		closeNavItem($('#nav-container li.menu-open'));
	}

	var sKey = $(oTab).data('key');

	sKey = sKey.replace(/_[0-9]+$/, '');
	
	var oNavItem = $('#nav'+sKey);

	while(oNavItem.length > 0) {
		oNavItem.addClass('active');
		openNavItem(oNavItem);
		oNavItem = oNavItem.parent('ul').parent('li');
	}
	
}

oTabHandler.activateCurrentTab = function() {

	if(this.aCurrentTabs.length > 0) {
		var oCurrentTab = this.aCurrentTabs[this.aCurrentTabs.length-1];
		this.activateNavigation($('#content-link-'+oCurrentTab.key));
	}
	
}

oTabHandler.closeTab = function(oEvent) {

	if (oEvent.target) {
		if($(oEvent.target).has('.fa')) {
			oTab = $(oEvent.target).parent();
		} else {
			oTab = oEvent.target;
		}
	} else {
		oTab = oEvent;
	}

	var sTarget = $(oTab).children('a').attr('aria-controls');

	iTabIndex = this.getTabIndex(oTab);

	if(iTabIndex !== -1) {
		this.aCurrentTabs.splice(iTabIndex, 1);
	}

	oTab.remove();
	$('#'+sTarget).remove();

	if(this.aCurrentTabs.length > 0) {
		var oLastTab = this.aCurrentTabs[this.aCurrentTabs.length-1];
		$('#content-link-'+oLastTab.key).tab('show');
	}
	
	this.updateTabScrolling();

}

oTabHandler.resizeContentTabs = function() {
	
	var iHeight = $(window).height() - 80;

	$('div.tab-pane[role="tabpanel"]').each(function() {
		$(this).css('height', iHeight);
		if($(this).find('iframe').length > 0) {
			$(this).find('iframe').css('height', iHeight);
		}
		$(this).trigger('admin:resize-content-tab');
	});

	this.updateTabScrolling();

}

oTabHandler.saveTabs = function() {

	var aData = [];

	var aTabLinks = $('.content-link');

	aTabLinks.each(function() {

		aData.push({
			type: $(this).data('type'),
			key: $(this).data('key'),
			value: $(this).data('value'),
			title: $(this).data('title')
		});

	})

	$.post(
		'/admin/save-tabs',	
		JSON.stringify(aData),
		function(aData) {
			showFeedback(aData);
		},
		'json'
	);

}

oTabHandler.loadDefaultTabs = function() {
	
	$.post(        
		'/admin/get-tabs',
		$.proxy(this.loadDefaultTabsCallback, this),
		'json'
	);
	
}

oTabHandler.loadDefaultTabsCallback = function(oResponse) {

	if (!oResponse.bAllowSaving) {
		$('#content-tabs-save').hide();
	}

	oResponse.aTabs.forEach(function(aTab, iTab) {

		if (Object.keys(aTab).indexOf('active') === -1) {
			aTab.active = false;
		}

		if (Object.keys(aTab).indexOf('closable') === -1) {
			aTab.closable = true;
		}

		switch(aTab.type) {
			case 'view':
				loadContentByView(aTab.key, aTab.value, undefined, aTab.active, aTab.closable);
				break;
			case 'url':
				loadContentByUrl(aTab.key, aTab.title, aTab.value, aTab.active, aTab.closable);
				break;
		}

	}, this);

}

oTabHandler.updateTabScrolling = function() {
	
	var oTabsDiv = $('#content-tabs');
	if(oTabsDiv) {

		var oTabsList = $('#content-tabs ul.nav');

		$('#tabs_scroll_left').remove();
		$('#tabs_scroll_right').remove();

		oTabsList.css('left', 0);
		
		if(oTabsDiv.width() < oTabsList.width()) {

			var iTabsScrollDiff = oTabsList.width() - oTabsDiv.width() + 14;
			var iTabsScrollDuration = iTabsScrollDiff / 100 * 1000;

			var oLeftScrollDiv = document.createElement('div');
			oLeftScrollDiv.id = 'tabs_scroll_left';
			oLeftScrollDiv.className = 'tabs_scroll_left';
			var oLeftScrollIcon = document.createElement('span');
			oLeftScrollIcon.className = 'fa fa-caret-left';

			var oRightScrollDiv = document.createElement('div');
			oRightScrollDiv.id = 'tabs_scroll_right';
			oRightScrollDiv.className = 'tabs_scroll_right';
			var oRightScrollIcon = document.createElement('span');
			oRightScrollIcon.className = 'fa fa-caret-right';

			oLeftScrollDiv.append(oLeftScrollIcon);
			oRightScrollDiv.append(oRightScrollIcon);

			oTabsDiv.append(oLeftScrollDiv);
			oTabsDiv.append(oRightScrollDiv);

			$(oLeftScrollDiv).hover(
				function() {
					$(oTabsList).animate({left: 0}, iTabsScrollDuration);
				},
				function() {
					$(oTabsList).stop();
				}
			);
			$(oRightScrollDiv).hover(
				function() {
					$(oTabsList).animate({left: iTabsScrollDiff*-1}, iTabsScrollDuration);
				},
				function() {
					$(oTabsList).stop();
				}
			);

		}
	}
}

/*
 * END TabHandler
 */

function printInfo(oImg) { 
	var oWin = window.open('', 'printWindow', 'location=no,status=no,width=700,height=500');
	var oDiv = $(oImg).closest('.box').get(0);

	var sHTML = '<html><head>';
	sHTML += '<link type=\"text/css\" rel=\"stylesheet\" href=\"/admin/css/admin.css\" media=\"\" />';
	sHTML += '<title></title>';
	sHTML += '</head><body><br /><div class=\"infoBox\">';
	oWin.document.writeln(sHTML);
	oWin.document.write(oDiv.innerHTML);
	oWin.document.writeln('</div></body></html>');
	oWin.print();
}

$( document ).ready(function() {

	// Toastr Optionen
	toastr.options.progressBar = true;
	toastr.options.closeButton = true;
	toastr.options.newestOnTop = true;
	toastr.options.preventDuplicates = true;

	const navSearchInput = $('#nav-search');
	const navSearchBtn = $('#nav-search-btn');

	navSearchInput.keyup(function() {

		var sSearch = $(this).val();

		if(
			!sSearch.length ||
			sSearch.length === 0
		) {
			$(this).find(':hidden').show();
			$('#nav-container').find('li').each(function() {
				$(this).show();
				closeNavItem($(this));
			});

			oTabHandler.activateCurrentTab();
			navSearchBtn.find('i.fa').removeClass('fa-times').addClass('fa-search');
		} else {
			$('#nav-container').children('').each(function() {
				toggleNavItem(sSearch, $(this));
			});
			navSearchBtn.find('i.fa').removeClass('fa-search').addClass('fa-times');
		}

	});

	navSearchBtn.click(() => navSearchInput.val('').trigger('keyup'));

	$('#content-tabs-save').click(function() {
		
		oTabHandler.saveTabs();

	});

	$(window).resize(function(e) {
		oTabHandler.resizeContentTabs();
		window.__FIDELO__.oFrame.oEmitter.emit('window:resize', e);
	});

	oTabHandler.loadDefaultTabs();

	setInterval(function() {
		window.__FIDELO__.oFrame.executePeriodicalRequest();
	}, 10 * 1000);

});
