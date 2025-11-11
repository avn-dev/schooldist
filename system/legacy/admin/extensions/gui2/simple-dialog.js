/* Utility functions */
function bindEventHandler(element, eventName, handler) {
	if (element.addEventListener) {
		// The standard way
		element.addEventListener(eventName, handler, false);
	} else if (element.attachEvent) {
		// The Microsoft way
		element.attachEvent('on' + eventName, handler);
	}
}

/**
 * The modal dialog class
 * @constructor
 */
function Dialog(options) {

	this.options = {
		width: 800,
		height: 600,
		left: 0,
		top: 0,
		level: 1,
		openOnCreate: true,
		destroyOnClose: true,
		escHandler: this.close,
		draggable: true,
		buttons: {'OK': this.close},
		closeIconEvent: false,
		settingsBtn: false
	};

	// Overwrite the default options
	for (var option in options) {
		this.options[option] = options[option];
	}

	// Create dialog dom
	this._makeNodes();

	if (this.options.openOnCreate) {
		this.open();
	}
}

Dialog.prototype = {
	/* handles to the dom nodes */
	container: null,
	header: null,
	body: null,
	content: null,
	actions: null,
	_overlay: null,
	_wrapper: null,
	_zIndex: 0,
	_escHandler: null,

	/**
	 * Shows the dialog
	 */
	open: function(zIndexNoReset) {
		
		if(zIndexNoReset)
		{
			// Do nothing
		}
		else
		{
			// Set new zIndex
			this._makeTop();
		}

		var ws = this._wrapper.style;

		this._overlay.style.display = 'block';
		ws.display = 'block';
		//this._wrapper.focus();

		if (this.options.focus) {
			var input = document.getElementById(this.options.focus);
			if (input) {
				input.focus();
			}
		}

		this.position();

	},

	/**
	 * Closes the dialog
	 */
	close: function() {
		if (this.options.destroyOnClose) {
			this._destroy();
		} else {
			this._overlay.style.display = 'none';
			this._wrapper.style.display = 'none';
		}
	},

	/**
	 * Add buttons to the dialog actions panel after creation
	 * @param {object} buttons Object with property name as button text and value as click handler
	 * @param {boolean} prepend If true, buttons will be prepended to the panel instead of being appended
	 */
	addButtons: function(buttons, prepend) {
		var actions = this.actions;
		var buttonArray = this._makeButtons(buttons);
		var first = null;
		if (prepend && (first = actions.firstChild) != null) {
			buttonArray.each(function(button) {
				actions.insertBefore(buttonArray[i], first);
			});
		} else {
			buttonArray.each(function(button) {
				actions.appendChild(button);
			});
		}
	},

	replaceButtons: function(buttons) {
		
		this.actions.innerHTML = '';
		this.addButtons(buttons);
		
	},

	/**
	 * Change (or set) title after creation
	 * @param {string} title The dialog title
	 */
	setTitle: function(title) {
		if (!this.header) {
			var header = document.createElement('div');
			header.className = 'dialog-header modal-header';
			this.container.insertBefore(header, this.body);
			/*new Draggable(this.container);*/
			this.header = header;
		}

		this.header.innerHTML = title;
	},

	/**
	 * Makes the dom tree for the dialog
	 */
	_makeNodes: function() {
		if (this._overlay || this._wrapper) {
			return; // Avoid duplicate invocation
		}

		// Prevent overlay from becoming too dark
		var opacity = this.options.level === 1 ? 'opacity-80' : 'opacity-30';

		// Make overlay
		this._overlay = document.createElement('div');
		this._overlay.className = `bg-gray-900 dark:bg-gray-950 ${opacity} transition-opacity dialog-overlay modal fade in`;
		document.body.appendChild(this._overlay);

		/*if (typeof this.options.title == 'string' && this.options.title != '') {
			var header = document.createElement('div');
			header.className = 'dialog-header';
			//this.container.addClassName('test_dennis2');
			//new Draggable(this.container);
			header.innerHTML = this.options.title;
			this.header = header;
		}*/

		var header = document.createElement('div');
		header.className = 'dialog-header modal-header';
		this.header = header;

		var divtitle = new Element('h3', {
			'class': 'GUIDialogTitle modal-title',
			'id': "title_" + this.options.gui_dialog_id + '_' + this.options.gui_dialog_hash
		}).update(this.options.sTitle);

		this.header.append(divtitle);

		var tools = new Element('div', {
			'class': 'flex-none'
		})
		var sCloseBtnId = 'close_' + this.options.gui_dialog_id + '_' + this.options.gui_dialog_hash + '';
		var imgclose = '<button type="button" id="' + sCloseBtnId + '" class="close" data-dismiss="modal" aria-label="Close"><i class="fas fa-xs fa-times"></i></button>';
		Element.insert(tools, {'bottom': imgclose});
		this.header.append(tools);

		if (this.options.settingsBtn === true) {
			var sSettingsBtnId = 'settings_' + this.options.gui_dialog_id + '_' + this.options.gui_dialog_hash + '';
			var imgsettings = '<button type="button" id="' + sSettingsBtnId + '" class="close dialog-settings"><i class="fas fa-xs fa-cog"></i>&nbsp;';
			Element.insert(this.header, {'bottom': imgsettings});
		}


		// {begin dialog body
		var content = document.createElement('div');
		content.className = 'dialog-content';
		content.innerHTML = this.options.content;
		this.content = content;

		//   {begin actions panel
		var footer = document.createElement('div');
		footer.className = 'dialog-actions modal-footer';

		var oDivActionButtons = document.createElement('div');
		oDivActionButtons.className = 'buttons form-inline';

		var buttons = this._makeButtons(this.options.buttons);
		if (buttons.length > 0) {
			buttons.each(function (button) {
				oDivActionButtons.appendChild(button);
			});
		} else {
			footer.hide();
		}

		footer.appendChild(oDivActionButtons);

		this.actions = oDivActionButtons;
		//   }end actions panel

		var body = document.createElement('div');
		body.className = 'dialog-body modal-body';
		body.appendChild(content);
		//body.appendChild();
		this.body = body;
		// }end dialog body

		var container = document.createElement('div');
		container.className = 'dialog modal-content';

		if (this.header) {
			var divColor = new Element('div', {
				'class': 'header-line h-1 rounded-full'
			})

			container.appendChild(divColor);
			container.appendChild(header);
		}
		container.appendChild(body);
		container.appendChild(footer);
		this.container = container;

		var wrapper = document.createElement('div');
		wrapper.className = 'dialog-wrapper modal-dialog';
		wrapper.id = 'dialog_wrapper_' + this.options.gui_dialog_id + '_' + this.options.gui_dialog_hash;
		var ws = wrapper.style;
		ws.position = 'absolute';

		/**
		 * Neues Drag'n'Drop
		 */
		if (this.options.draggable) {
			$j(wrapper).draggable({
				handle: divtitle,
				zIndex: 9000
			});

			/*new Draggable(wrapper,
				{
					handle: divtitle,
					zindex: 9000
				}
			);*/
		}

		ws.width = this.options.width + 'px';
		ws.height = this.options.height + 'px';

		ws.display = 'none';
		ws.outline = 'none';
		wrapper.appendChild(container);
		// register keydown event
		if (this.options.escHandler) {
			wrapper.tabIndex = -1;
			this._onKeydown = this._makeHandler(function (e) {
				if (!e) {
					e = window.event;
				}
				if (e.keyCode && e.keyCode == 27) {
					this.options.escHandler.apply(this);
				}
			}, this);
			bindEventHandler(wrapper, 'keydown', this._onKeydown);
		}
		this._wrapper = document.body.appendChild(wrapper);

		if (Dialog.needIEFix) {
			this._fixIE();
		}

		if (this.options.closeIconEvent === true) {
			bindEventHandler($(sCloseBtnId), 'click', this._makeHandler(function () {
				this.close();
			}, this));
		}

	},

	position: function () {

		var de = document.documentElement;
		var w = self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
		var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;

		this._wrapper.style.width = this.options.width + 'px';
		this._wrapper.style.height = this.options.height + 'px';

		if(this._wrapper.offsetWidth > w - 40) {
			if(!this.options.left || this.options.left < 0){
					this._wrapper.style.left = "20px";
				} else {
					this._wrapper.style.left = parseInt(this.options.left)+'px';
				}
				this._wrapper.style.width = w - 40 + 'px';
		} else {
			if(!this.options.left || this.options.left < 0){
				this._wrapper.style.left = (((w - this._wrapper.offsetWidth)/2)-5)+"px";
			} else {
				this._wrapper.style.left = parseInt(this.options.left)+'px';
			}
		}

		if(this._wrapper.offsetHeight > (h - 60)) {

			if(!this.options.top || this.options.top < 0){
				this._wrapper.style.top = '25px';
			} else {
				this._wrapper.style.top = parseInt(this.options.top)+'px';
			}
			this._wrapper.style.height = h-60 + 'px';

		} else {
			if(!this.options.top || this.options.top < 0){
				this._wrapper.style.top = (((h - this._wrapper.offsetHeight)/2)-5)+'px';
			} else {
				this._wrapper.style.top = parseInt(this.options.top)+'px';
			}
		}

	},

	getPageSize: function(){
      var de = document.documentElement;
      var w = self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
      var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;

      arrayPageSize = new Array(w,h)
      return arrayPageSize;
   },
   
   getPageScrollTop: function(){
      var yScrolltop;
      if (self.pageYOffset) {
         yScrolltop = self.pageYOffset;
      } else if (document.documentElement && document.documentElement.scrollTop){    // Explorer 6 Strict
         yScrolltop = document.documentElement.scrollTop;
      } else if (document.body) {// all other Explorers
         yScrolltop = document.body.scrollTop;
      }
      arrayPageScroll = new Array('',yScrolltop)
      return arrayPageScroll;
   },
	/**
	 * Removes the nodes from document
	 * @param {object} buttons Object with property name as button text and value as click handler
	 * @return {Array} Array of buttons as dom nodes
	 */
	_makeButtons: function(buttons) {
		var buttonArray = new Array();
		for (var buttonText in buttons) {
			var button = document.createElement('button');
			button.className = 'dialog-button btn';
			
			button.innerHTML = buttonText;

			if(typeof buttons[buttonText] == 'function') {
				bindEventHandler(button, 'click', this._makeHandler(buttons[buttonText], this));
			} else {
				if(buttons[buttonText]['id']) {
					button.id = buttons[buttonText]['id'];
				}
				if(buttons[buttonText]['style']) {
					button.style = buttons[buttonText]['style'];
				}
				if(buttons[buttonText]['default']) {
					button.className += ' btn-default';
				} else {
					button.className += ' btn-primary';
				}
				bindEventHandler(button, 'click', this._makeHandler(buttons[buttonText]['function'], this));
			}

			buttonArray.push(button);
		}
		return buttonArray;
	},

	/** A helper function used by makeButtons */
	_makeHandler: function(method, obj) {
		return function(e) {
			method.call(obj, e);
		}
	},

	/** A helper function used by open */
	_makeTop: function() {
		if (this._zIndex < Dialog.Manager.currentZIndex) {
			this._overlay.style.zIndex = Dialog.Manager.newZIndex();
			this._zIndex = this._wrapper.style.zIndex = Dialog.Manager.newZIndex();
		}
	},

	_fixIE: function() {
		var width = document.documentElement["scrollWidth"] + 'px';
		var height = document.documentElement["scrollHeight"] + 'px';
		var os = this._overlay.style;
		os.position = 'absolute';
		os.width = width;
		os.height = height;

		var iframe = document.createElement('iframe');
		iframe.className = 'iefix';
		iframe.style.width = width;
		iframe.style.height = height;
		this._wrapper.appendChild(iframe);
	},

	/**
	 * Removes the nodes from document
	 */
	_destroy: function() {
		document.body.removeChild(this._wrapper);
		document.body.removeChild(this._overlay);
		this.container = null;
		this.header = null;
		this.body = null;
		this.content = null;
		this.actions = null;
		this._overlay = null;
		this._wrapper = null;
	}
};

Dialog.needIEFix = (function () {
	var userAgent = navigator.userAgent.toLowerCase();
	return /msie/.test(userAgent) && !/opera/.test(userAgent) && !window.XMLHttpRequest;
})();

if(typeof(_iSimpleDialogZIndex) == 'undefined') {
	_iSimpleDialogZIndex = 3000;
} else {
	_iSimpleDialogZIndex++;
}


Dialog.Manager = {
	currentZIndex: _iSimpleDialogZIndex,
	newZIndex: function() {
		_iSimpleDialogZIndex++;
		this.currentZIndex = _iSimpleDialogZIndex;
		return _iSimpleDialogZIndex;
	}
}

/** This simple object manages the z indices */
/*Dialog.Manager = {
	currentZIndex: 3000,
	newZIndex: function() {
		return ++this.currentZIndex;
	}
};*/