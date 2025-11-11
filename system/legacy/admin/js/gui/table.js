
GUI_Table.prototype             = new GUI(); 
GUI_Table.prototype.constructor = GUI_Table; 
GUI_Table.superClass            = GUI.prototype;

function GUI_Table() {

	this.strItem = 'table';
	this.arrTemplate = new Array();
	
}

GUI_Table.prototype.writeTable = function() {

	this.arrTemplate = this.getTemplate();
	console.debug(this.arrTemplate);

}

 
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

var ScrollableTable = {
	init : function(elm, o){
		var table = $(elm);
		if(table.tagName != "TABLE") return;
		if(!table.id) table.id = "scrollable-table-" + ScrollableTable._count++;
		Object.extend(ScrollableTable.options, o || {} );
		var doscroll = 1;
		var sortFirst;
		if(doscroll) ScrollableTable.initScroll(table);
	},
	initScroll : function(elm){
		var table = $(elm);
		if(table.tagName != "TABLE") return;
		
		if(table.hasClassName('scrolltable-ready')) {
			return;
		}

		table.addClassName('scrolltable-ready');

		table.addClassName(ScrollableTable.options.tableScrollClass);
		var w = table.getDimensions().width;
		w = w - 16;

		table.setStyle({
			'border-spacing': '0',
			'table-layout': 'fixed',
			width: w + 'px'
		});
		
		var hcolgroup = $(document.createElement('colgroup'));
		
		var cells = ScrollableTable.getHeaderCells(table);
		var cellwidth = new Array();
		cells.each(function(c,i){
			c = $(c);
			var cw = c.getDimensions().width;
			var hcol = $(document.createElement('col'));
			hcol.setStyle({width: cw + 'px'});
			hcolgroup.appendChild(hcol);
			cellwidth[i] = cw;
		})
		
		// Fixed Head
		var head = (table.tHead && table.tHead.rows.length > 0) ? table.tHead : table.rows[0];
		var hclone = head.cloneNode(true);
		
		var hdiv = $(document.createElement('div'));
		hdiv.id = table.id + '-head';
		table.parentNode.insertBefore(hdiv, table);
		hdiv.setStyle({
			overflow: 'hidden'
		});
		var htbl = $(document.createElement('table'));
		htbl.setStyle({
			borderSpacing: '0',
			tableLayout: 'fixed',
			width: w + 'px',
			marginBottom: 0
		});
		htbl.cellPadding = 0;
		htbl.cellSpacing = 0;
		htbl.addClassName('table');
		htbl.id = table.id + '-head-table';
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
			overflowX: 'hidden',
			backgroundColor: '#ffffff'
		});
		var colgroupBody = hcolgroup.cloneNode(true);
		// Colgroup koppieren , da falls keine breite im HTML steht werden für den Head bereich pixel breiten ausgerechnet für den Body jedoch nicht!

		table.down('colgroup').replace(colgroupBody);
		
		cdiv.appendChild(table);
		
		
		
		cdiv.addClassName('scroll-table-body');
		
		hdiv.scrollLeft = 0;
		cdiv.scrollLeft = 0;

		Event.observe(cdiv, 'scroll', ScrollableTable._scroll.bindAsEventListener(table), false);

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
        $(this.id + '-head').scrollLeft  = $(this.id + '-body').scrollLeft;
    },
	setup : function(o) {
		Object.extend(ScrollableTable.options, o || {} )
		 //in case the user added more types/detectors in the setup options, we read them out and then erase them
		 // this is so setup can be called multiple times to inject new types/detectors
		Object.extend(ScrollableTable.types, ScrollableTable.options.types || {})
		ScrollableTable.options.types = {};
		if(ScrollableTable.options.detectors) {
			ScrollableTable.detectors = $A(ScrollableTable.options.detectors).concat(ScrollableTable.detectors);
			ScrollableTable.options.detectors = [];
		}
	},
	options : {
		autoLoad : true,
		tableSelector : ['table.scroll'],
		tableScrollClass : 'scroll'
	},
	_count : 0,
	load : function() {
		if(ScrollableTable.options.autoLoad) {
			$A(ScrollableTable.options.tableSelector).each(function(s){
				$$(s).each(function(t) {
					ScrollableTable.init(t);
				});
			});
		}
	}
}
