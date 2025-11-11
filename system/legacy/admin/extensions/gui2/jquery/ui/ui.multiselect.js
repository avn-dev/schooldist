/*
 * jQuery UI Multiselect
 *
 * Authors:
 *  Michael Aufreiter (quasipartikel.at)
 *  Yanick Rochon (yanick.rochon[at]gmail[dot]com)
 * 
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 * 
 * http://www.quasipartikel.at/multiselect/
 *
 * 
 * Depends:
 *	ui.core.js
 *	ui.sortable.js
 *
 * Optional:
 * localization (http://plugins.jquery.com/project/localisation)
 * scrollTo (http://plugins.jquery.com/project/ScrollTo)
 * 
 * Todo:
 *  Make batch actions faster
 *  Implement dynamic insertion through remote calls
 */

(function($) {

$.widget("ui.multiselect", {
	options: {
		sortable: true,
		searchable: true,
		animated: 'fast',
		show: 'slideDown',
		hide: 'slideUp',
		dividerLocation: 0.5,
		selected_values: new Array(),
		locale: {
			addAll:'Add all',
			removeAll:'Remove all',
			itemsCount:'items selected'
		},
		readonly: false
	},

	_adjustWidthFirefox: function() {
		if (navigator.userAgent.indexOf("Firefox") === -1) {
			return;
		}
		if (this.selectedList[0].scrollHeight > this.selectedList[0].clientHeight) {
			this.selectedList.find('.ui-icon').css("margin-right", "10px");
		} else {
			this.selectedList.find('.ui-icon').css("margin-right", "0px");
		}

		if (this.availableList[0].scrollHeight > this.availableList[0].clientHeight) {
			this.availableList.find('.ui-icon').css("margin-right", "10px");
		} else {
			this.availableList.find('.ui-icon').css("margin-right", "0px");
		}
	},

	_init: function() {

		var sRand = 10000000 + parseInt(Math.random() * (99999999 - 10000000 + 1));

		this.randomN = sRand;
		this.bFirstCall = true;

		var sContainerClass = '';
		if(this.element[0].disabled) {
			this.options.readonly = true;
			sContainerClass += ' readonly';
		}

		this.element.hide();
		this.id = this.element.attr("id");
		this.searchString = '';
		this.container = $('<div class="ui-multiselect ui-helper-clearfix ui-widget ui-multi_' + sRand + ''+sContainerClass+'" id="ui-multi_' + sRand + '"></div>').insertAfter(this.element);
		this.count = 0; // number of currently selected options
		this.selectedContainer = $('<div class="selected"></div>').appendTo(this.container);
		this.availableContainer = $('<div class="available"></div>').appendTo(this.container);
		this.selectedActions = $('<div class="actions ui-widget-header ui-helper-clearfix"><span class="count">0 '+this.options.locale.itemsCount+'</span><a href="#" class="remove-all rand_' + sRand + '">'+this.options.locale.removeAll+'</a></div>').appendTo(this.selectedContainer);
		this.availableActions = $('<div class="actions ui-widget-header ui-helper-clearfix"><input type="text" class="txt search empty ui-widget-content"/><a href="#" class="add-all rand_' + sRand + '">'+this.options.locale.addAll+'</a></div>').appendTo(this.availableContainer);
		this.selectedList = $('<ul class="selected connected-list selected_' + sRand + '" id="selected_' + sRand + '"><li class="ui-helper-hidden-accessible"></li></ul>').bind('selectstart', function(){return false;}).appendTo(this.selectedContainer);
		this.availableList = $('<ul class="available connected-list available_' + sRand + '" id="available_' + sRand + '"><li class="ui-helper-hidden-accessible"></li></ul>').bind('selectstart', function(){return false;}).appendTo(this.availableContainer);

		var that = this;

		// Wenn eine Höhe per Style definiert ist, diese nehmen	
		if(
			this.element[0] &&
			this.element[0].style &&
			this.element[0].style.height
		) {
			iElementHeight = this.element[0].style.height;
			iElementHeight = parseInt(iElementHeight);
		// Wenn eine Anzahl von Elementen angegeben ist, diese zur Berechnung der Höhe verwenden
		} else if(
			this.element[0] &&
			this.element[0].size
		) {
			iElementHeight = this.element[0].size * 16;
		} else {
			iElementHeight = 100;
		}

		var iElementWidth = '450px';

		if(
			this.element[0] && 
			this.element[0].style && 
			this.element[0].style.width
		) {
			iElementWidth = this.element[0].style.width;
			if(iElementWidth.indexOf('%') == -1) {
				iElementWidth = (parseInt(iElementWidth)+2)+'px';
			}
		}

		if(iElementHeight < 1) {
			iElementHeight = 100;
		}

		// set dimensions
		$("#ui-multi_" + sRand).get(0).style.width = iElementWidth;
		this.selectedContainer.width((this.options.dividerLocation*100)+'%');
		this.availableContainer.width(((1-this.options.dividerLocation)*100)+'%');
		
		// fix list height to match <option> depending on their individual header's heights
		$("#selected_" + sRand).get(0).style.height = (iElementHeight)+'px';
		$("#available_" + sRand).get(0).style.height = (iElementHeight)+'px';

		if ( !this.options.animated ) {
			this.options.show = 'show';
			this.options.hide = 'hide';
		}

		// init lists
		this._populateLists(this.element.children());
		this.bFirstCall = false;

		if(!this.options.readonly) {
			// make selection sortable
			if (this.options.sortable) {
				$("ul.selected_" + this.randomN).sortable({
					placeholder: 'ui-state-highlight',
					axis: 'y',
					update: function(event, ui) {
						// apply the new sort order to the original selectbox
						that.selectedList.find('li').each(function() {
							if ($(this).data('optionLink')) {
								if (that.element.data('keepData')) {
									$(this).data('optionLink').detach().appendTo(that.element);
								} else {
									$(this).data('optionLink').remove().appendTo(that.element);
								}
							}
						});
					},
					receive: function(event, ui) {
						ui.item.data('optionLink').get(0).selected = true;
						// increment count
						that.count += 1;
						that._updateCount();
						// workaround, because there's no way to reference
						// the new element, see http://dev.jqueryui.com/ticket/4303
						that.selectedList.children('.ui-draggable').each(function() {
							$(this).removeClass('ui-draggable');
							$(this).data('optionLink', ui.item.data('optionLink'));
							$(this).data('idx', ui.item.data('idx'));
							that._applyItemState($(this), true);
						});

						// workaround according to http://dev.jqueryui.com/ticket/4088
						setTimeout(function() { ui.item.remove(); }, 1);
					}
				});
			}

			// set up livesearch
			if (this.options.searchable) {
				this._registerSearchEvents(this.availableContainer.find('input.search'));
			} else {
				this.availableContainer.find('input.search').hide();
			}

			// batch actions
			$('.remove-all.rand_' + sRand).click(function() {
				that.removeAllOptions();
				return false;
			});

			$('.add-all.rand_' + sRand).click(function() {
				that.addAllOptions();
				return false;
			});
		}
		this._adjustWidthFirefox();
	},

	addAllOptions: function() {
		var aOptions;
		if(this.availableContainer.find('input.search').val() != '') {

			var aOptionCache = $(this.availableList.children('li').map(function() {
				if(this.style.display != 'none') {
					return this.title;
				}
			}));

			aOptions = $(this.element.children().map(function() {
				if(
					this.selected ||
					jQuery.inArray(this.text, aOptionCache) > -1
				) {
					this.selected = true;
					return this;
				}
			}));

		} else {
			aOptions = $(this.element.children().map(function() {
				this.selected = true;
				return this;
			}));
		}

		this._populateLists(this.element.children());

		if(this.availableContainer.find('input.search').val() != '') {
			this._filter.apply(this.availableContainer.find('input.search'), [this.availableList]);
		}

	},

	removeAllOptions: function() {
		this._populateLists(this.element.children().prop('selected', false));
	},

	destroy: function() {

		this.element.show();
		this.container.remove();

		//$.widget.prototype.destroy.apply(this, arguments);
		$.Widget.prototype.destroy.call(this, arguments);
	},

	_populateLists: function(options) {

		this.count = 0;

		var that = this;

		// Erster Aufruf. Berücksichtige die selected values in der richtigen Reihenfolge
		if(	
			this.options.selected_values &&
			this.options.selected_values.length > 0 &&
			this.options.sortable //wenn sortierbar
		) {

			this.selectedList.children('.ui-element').remove();
			this.availableList.children('.ui-element').remove();
	
			// todo: eine Andere Lösung finden, um die Reihenfolge bei selektierten zu behalten wegen Performance
			var z = 0;

			// Erste schleife NUR für "selected values" in der Reihenfolge, wie sie unter position gespeichert ist
			for(var n = 0; n < this.options.selected_values.length; n++)
			{
				var iID = this.options.selected_values[n];
	
				var items = $(options.map(function(i)
				{
					if(this.selected && this.value == iID)
					{
						var item = that._getOptionNode(this).appendTo(this.selected ? that.selectedList : that.availableList).show();
	
						if (this.selected)
						{
							that.count += 1;
						}
	
						that._applyItemState(item, this.selected);
						item.data('idx', z++);
	
						that.selectedList.find('li').each(function() {
							if(item.data('optionLink'))
								item.data('optionLink').remove().appendTo(that.element);
						});
	
						return item[0];
					}
		    	}));
			}

			// Zweite schleife für nicht "selected values"
			var items = $(options.map(function(i)
			{
			    if(!this.selected || that.options.selected_values.length == 0)
				{
					var item = that._getOptionNode(this).appendTo(this.selected ? that.selectedList : that.availableList).show();
	
					if (this.selected)
					{
						that.count += 1;
					}
	
					that._applyItemState(item, this.selected);
					item.data('idx', z++);
	
					return item[0];
				}
	    	}));
	
			// Unset selected valus
			this.options.selected_values = new Array();

		} else {

			this.selectedList.children('.ui-element').remove();
			this.availableList.children('.ui-element').remove();

			// Standard Aufruf
			var items = $(options.map(function(i) {
				var item = that._getOptionNode(this).appendTo(this.selected ? that.selectedList : that.availableList).show();
				if(this.selected) {
					that.count += 1;
				}
				that._applyItemState(item, this.selected);
				item.data('idx', i);
				return item[0];
		    }));

		}

		// update count
		this._updateCount(this.bFirstCall);

	},

	refreshOptions: function() {

		var options = this.element.find('option').removeAttr('selected');

		this.selectedList.children('.ui-element').remove();
		this.availableList.children('.ui-element').remove();
		this.count = 0;

		var that = this;

		// Standard Aufruf
		var items = $(options.map(function(i) {
			var item = that._getOptionNode(this).appendTo(this.selected ? that.selectedList : that.availableList).show();
			if (this.selected) that.count += 1;
			that._applyItemState(item, this.selected);
			item.data('idx', i);
			return item[0];
		}));

		this.selectedContainer.find('span.count').text(this.count+" "+this.options.locale.itemsCount);
		this._adjustWidthFirefox();
	},

	/**
	 * Reload the Select Options
	 */
	reloadOptions: function() {

		var options = this.element.find('option');
		this.selectedList.children('.ui-element').remove();
		this.availableList.children('.ui-element').remove();
		this.count = 0;
		var that = this;

		// Standard Aufruf
		var items = $(options.map(function(i) {
			var item = that._getOptionNode(this).appendTo(this.selected ? that.selectedList : that.availableList).show();
			if (this.selected) that.count += 1;
			that._applyItemState(item, this.selected);
			item.data('idx', i);
			return item[0];
		}));

		this.selectedContainer.find('span.count').text(this.count+" "+this.options.locale.itemsCount);
		this.availableContainer.find('input.search').text(this.searchString);
		if(this.availableContainer.find('input.search').val() != '') {
			$(this.availableContainer.find('input.search')).keyup();
		}
		this._adjustWidthFirefox();
	},

	_updateCount: function(bFirstCall) {

		if(
			this.element[0] &&
			!bFirstCall
		) {
			this._fireEvent('change', this.element[0]);
		}

		this.selectedContainer.find('span.count').text(this.count+" "+this.options.locale.itemsCount);
	},

	_getOptionNode: function(option) {
		option = $(option);
		var sText;
		sText = option.text();
		var sTitle = sText.replace(/"/g, "'");

		var node = $('<li class="ui-state-default ui-element firefoxscrollcorrect" title="'+sTitle+'"><span class="ui-icon"></span>'+sText+'<a href="#" class="action"><span class="ui-corner-all ui-icon"></span></a></li>').hide();
		node.data('optionLink', option);
		return node;
	},

	// clones an item with associated data
	// didn't find a smarter away around this
	_cloneWithData: function(clonee) {
		var clone = clonee.clone();
		clone.data('optionLink', clonee.data('optionLink'));
		var data = clonee.data();
		$.each(data, function(indexname, value) {
			clone.data(indexname, value);
		})
		return clone;
	},

	_setSelected: function(item, selected) {

		// Option selektieren / deselektieren
		item.data('optionLink').get(0).selected = selected;

		if (selected) {

			var selectedItem = this._cloneWithData(item);
			item[this.options.hide](this.options.animated, function() { $(this).remove(); });
			selectedItem.appendTo(this.selectedList).hide()[this.options.show](this.options.animated);

			this._applyItemState(selectedItem, true);
			return selectedItem;
		} else {
			// look for successor based on initial option index
			var items = this.availableList.find('li');
			//var comparator = this.options.nodeComparator;
			var succ = null, i = item.data('idx');
			//var direction = this.options.nodeComparator(item, $(items[i]));

			succ = false;
			//succ = items[i];
			//falls das nicht klappt, Mehmet finden
			this.availableList.find('li').each(function() {
				if($(this).data('idx')>i){
					succ = $(this);
					return false;
				}
			});

			var availableItem = this._cloneWithData(item);
			succ ? availableItem.insertBefore($(succ)) : availableItem.appendTo(this.availableList);
			item[this.options.hide](this.options.animated, function() { $(this).remove(); });
			availableItem.hide()[this.options.show](this.options.animated);

			this._applyItemState(availableItem, false);
			return availableItem;
		}
	},

	_applyItemState: function(item, selected) {

		if (selected) {

			if (this.options.sortable) {
				item.children('span').addClass('ui-icon-arrowthick-2-n-s').removeClass('ui-helper-hidden').addClass('ui-icon');
			} else {
				item.children('span').removeClass('ui-icon-arrowthick-2-n-s').addClass('ui-helper-hidden').removeClass('ui-icon');
			}

			item.find('a.action span').addClass('ui-icon-minus').removeClass('ui-icon-plus');
			this._registerRemoveEvents(item.find('a.action'));

		} else {
			item.children('span').removeClass('ui-icon-arrowthick-2-n-s').addClass('ui-helper-hidden').removeClass('ui-icon');
			item.find('a.action span').addClass('ui-icon-plus').removeClass('ui-icon-minus');
			this._registerAddEvents(item.find('a.action'));
		}

		this._registerHoverEvents(item);
	},

	// taken from John Resig's liveUpdate script
	_filter: function(list) {
		var input = $(this);
		var rows = list.children('li'),
		cache = rows.map(function(){
			let searchString = $(this).text().toLowerCase();
			if ($(this).data('optionLink')) {
				let searchDataAttribute = $(this).data('optionLink').data('search');
				if (searchDataAttribute) {
					searchString += '///' + searchDataAttribute;
				}
			}
			return searchString;
		});

		var term = $.trim(input.val().toLowerCase()), scores = [];

		if (!term) {
			rows.show();
		} else {
			rows.hide();

			cache.each(function(i) {
				if (this.indexOf(term)>-1) {
					scores.push(i);
				}
			});

			$.each(scores, function() {
				$(rows[this]).show();
			});
		}
		this.searchString = $(this).text().toLowerCase();
	},

	_registerHoverEvents: function(elements) {
		if(!this.options.readonly) {
			elements.removeClass('ui-state-hover');
			elements.mouseover(function() {
				$(this).addClass('ui-state-hover');
			});
			elements.mouseout(function() {
				$(this).removeClass('ui-state-hover');
			});
		}
	},

	_registerAddEvents: function(elements) {

		if(!this.options.readonly) {

			var that = this;

			elements.click(function() {

				var item = that._setSelected($(this).parent(), true);
				that.count += 1;
				that._updateCount();

				return false;
			});

		}
	},

	_registerRemoveEvents: function(elements) {
		if(!this.options.readonly) {
			var that = this;
			elements.click(function() {
				that._setSelected($(this).parent(), false);
				that.count -= 1;
				that._updateCount();
				return false;
			});
		}
 	},
	_registerSearchEvents: function(input) {
		var that = this;

		input.focus(function() {
			$(this).addClass('ui-state-active');
		})
		.blur(function() {
			$(this).removeClass('ui-state-active');
		})
		.keypress(function(e) {
			if (e.keyCode == 13)
				return false;
		})
		.keyup(function() {
			that._filter.apply(this, [that.availableList]);
		});
	},
	
	_fireEvent : function(eventType, element) {
		if (document.createEvent) {
			var evt = document.createEvent("Events");
			evt.initEvent(eventType, true, true);
			element.dispatchEvent(evt);
		} else if (document.createEventObject) {
			var evt = document.createEventObject();
			element.fireEvent("on" + eventType, evt);
		}
	}
});

$.extend($.ui.multiselect, {
	defaults: {
		sortable: true,
		searchable: true,
		animated: 'fast',
		show: 'slideDown',
		hide: 'slideUp',
		dividerLocation: 0.5
	},
	locale: {
		addAll:'Add all',
		removeAll:'Remove all',
		itemsCount:'items selected'
	}
});

})(jQuery);
