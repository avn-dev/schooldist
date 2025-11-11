/*
*
* Copyright (c) 2006 Andrew Tetlaw 
* http://tetlaw.id.au/view/blog/table-sorting-with-prototype/
* 
* Permission is hereby granted, free of charge, to any person
* obtaining a copy of this software and associated documentation
* files (the "Software"), to deal in the Software without
* restriction, including without limitation the rights to use, copy,
* modify, merge, publish, distribute, sublicense, and/or sell copies
* of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
* ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
* CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
* * 
*/

var TuitionScrollableTable = {
	init : function(elm, o){
		var table = $(elm);
		if(table.tagName != "TABLE") return;
		if(!table.id) table.id = "scrollable-table-" + TuitionScrollableTable._count++;
		Object.extend(TuitionScrollableTable.options, o || {} );
		var doscroll = 1;
		var sortFirst;
		if(doscroll) TuitionScrollableTable.initScroll(table);
	},
	initScroll : function(elm){
		var table = $(elm);
		
		if(table.tagName != "TABLE") return;
		
		if(table.hasClassName('scrolltable-ready')) {
			return;
		}

		table.addClassName('scrolltable-ready');

		table.addClassName(TuitionScrollableTable.options.tableScrollClass);
		var w = table.getDimensions().width;
		w = w - 16;

		table.setStyle({
			'border-spacing': '0',
			'table-layout': 'fixed',
			width: w + 'px'
		});
		
		var hcolgroup = $(document.createElement('colgroup'));
		
		var cells = TuitionScrollableTable.getHeaderCells(table);
		var cellwidth = [];
		cells.each(function(c,i){
			c = $(c);
			var cw = c.getDimensions().width;
			var hcol = $(document.createElement('col'));
			hcol.setStyle({width: cw + 'px'});
			hcolgroup.appendChild(hcol);
			cellwidth[i] = cw;
		});
		
		// Fixed Head
		var head = (table.tHead && table.tHead.rows.length > 0) ? table.tHead : table.rows[0];
		var hclone = head.cloneNode(true);

		var hdiv = $(document.createElement('div'));
		hdiv.id = table.id + '-head';
		table.parentNode.insertBefore(hdiv, table);
		hdiv.setStyle({
			position: 'relative',
			width: w + 'px'
		});
		var htbl = $(document.createElement('table'));
		htbl.setStyle({
			borderSpacing: '0',
			tableLayout: 'fixed',
			width: w + 'px'
		});
		htbl.cellPadding = 0;
		htbl.cellSpacing = 0;
		htbl.addClassName('');
		hdiv.appendChild(htbl);
		hdiv.addClassName('scroll-table-head');
		
		if(head.parentNode == table) {
			table.removeChild(head);
		}

		htbl.appendChild(hcolgroup);
		htbl.appendChild(hclone);
		
		// Table Body
		var cdiv = $(document.createElement('div'));
		cdiv.id = table.id + '-body';
		table.parentNode.insertBefore(cdiv, table);
		cdiv.setStyle({
			overflowY: 'scroll',
			overflowX: 'scroll'
		});
		cdiv.appendChild(table);
		cdiv.addClassName('scroll-table-body');
		
		hdiv.scrollLeft = 0;
		cdiv.scrollLeft = 0;

		Event.observe(cdiv, 'scroll', TuitionScrollableTable._scroll.bindAsEventListener(table), false);

		if(table.offsetHeight - cdiv.offsetHeight > 0){
			cdiv.setStyle({width:(cdiv.getDimensions().width + 16) + 'px'})
		}

		/*
		var colgroup = table.down('colgroup');

		cellwidth.each(function(item, i) {
			colgroup.down('col',i).setStyle({width: item + 'px'});
			hcolgroup.down('col',i).setStyle({width: item + 'px'});
		});
		*/
		
	},

	getHeaderCells : function(table, cell) {
		if(!table) table = $(cell).up('table');
		return $A((table.tHead && table.tHead.rows.length > 0) ? table.tHead.rows[table.tHead.rows.length-1].cells : table.rows[0].cells);
	},

	getCellIndex : function(cell) {
		return $A(cell.parentNode.cells).indexOf(cell);
	},

	_scroll: function(){

		if(this.id.indexOf('other') != -1) {
			var oDivLabels = $('divPlanificationLabelsOther');
		} else {
			var oDivLabels = $('divPlanificationLabels');
		}

		$(this.id + '-head').style.left  = ($(this.id + '-body').scrollLeft * -1)+'px';
        oDivLabels.style.left = $(this.id + '-body').scrollLeft+'px';

    },

	setup : function(o) {
		Object.extend(TuitionScrollableTable.options, o || {} );
		 //in case the user added more types/detectors in the setup options, we read them out and then erase them
		 // this is so setup can be called multiple times to inject new types/detectors
		Object.extend(TuitionScrollableTable.types, TuitionScrollableTable.options.types || {});
		TuitionScrollableTable.options.types = {};
		if(TuitionScrollableTable.options.detectors) {
			TuitionScrollableTable.detectors = $A(TuitionScrollableTable.options.detectors).concat(TuitionScrollableTable.detectors);
			TuitionScrollableTable.options.detectors = [];
		}
	},

	options : {
		autoLoad : true,
		tableSelector : ['table.scroll'],
		tableScrollClass : 'scroll'
	},

	_count : 0,
	load : function() {
		if(TuitionScrollableTable.options.autoLoad) {
			$A(TuitionScrollableTable.options.tableSelector).each(function(s){
				$$(s).each(function(t) {
					TuitionScrollableTable.init(t);
				});
			});
		}
	}
};
