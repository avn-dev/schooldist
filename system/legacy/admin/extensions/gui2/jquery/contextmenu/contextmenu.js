/**
 * jquery.contextmenu.js
 * jQuery Plugin for Context Menus
 * http://www.JavascriptToolbox.com/lib/contextmenu/
 *
 * Copyright (c) 2008 Matt Kruse (javascripttoolbox.com)
 * Dual licensed under the MIT and GPL licenses. 
 *
 * @version 1.1
 * @history 1.1 2010-01-25 Fixed a problem with 1.4 which caused undesired show/hide animations
 * @history 1.0 2008-10-20 Initial Release
 * @todo slideUp doesn't work in IE - because of iframe?
 * @todo Hide all other menus when contextmenu is shown?
 * @todo More themes
 * @todo Nested context menus
 */
(function($){
	$.contextMenu = {
		shadow:true,
		shadowOffset:0,
		shadowOffsetX:5,
		shadowOffsetY:5,
		shadowWidthAdjust:-3,
		shadowHeightAdjust:-3,
		shadowOpacity:.2,
		shadowClass:'context-menu-shadow',
		shadowColor:'black',

		offsetX:0,
		offsetY:0,
		appendTo:'body',
		direction:'down',
		constrainToScreen:true,
        
		showTransition:'show',
		hideTransition:'hide',
		showSpeed:null,
		hideSpeed:null,
		showCallback:null,
		hideCallback:null,
    
		className:'context-menu',
		itemClassName:'context-menu-item',
		itemHoverClassName:'context-menu-item-hover sc_gradient',
		disabledItemClassName:'context-menu-item-disabled',
		disabledItemHoverClassName:'context-menu-item-disabled-hover',
		separatorClassName:'context-menu-separator',
		innerDivClassName:'context-menu-item-inner',
		themePrefix:'context-menu-theme-',
		theme:'default',

		separator:'context-menu-separator',
		target:null,
		menu:null,
		shadowObj:null,
		bgiframe:null,
		shown:false,
		useIframe: false,
    
		create: function(menu,opts) {
			var cmenu = $.extend({},this,opts);
      
			if (typeof menu=="string") {
				cmenu.menu = $(menu);
			} 
	  
			else if (typeof menu=="function") {
				cmenu.menuFunction = menu;
			}

			else {
				cmenu.menu = cmenu.createMenu(menu,cmenu);
			}
			if (cmenu.menu) {
				cmenu.menu.css({
					display:'none'
				});
				$(cmenu.appendTo).append(cmenu.menu);
			}
      

			if (cmenu.shadow) {
				cmenu.createShadow(cmenu);
				if (cmenu.shadowOffset) {
					cmenu.shadowOffsetX = cmenu.shadowOffsetY = cmenu.shadowOffset;
				}
			}
			$('body').bind('contextmenu',function(){
				cmenu.hide();
			});
			return cmenu;
		},
    
		createIframe: function() {
			return $('<iframe frameborder="0" tabindex="-1" src="javascript:false" style="display:block;position:absolute;z-index:-1;filter:Alpha(Opacity=0);"/>');
		},
    
		createMenu: function(menu,cmenu) {
			var className = cmenu.className;
			$.each(cmenu.theme.split(","),function(i,n){
				className+=' '+cmenu.themePrefix+n
				});
			var $t = $('<table cellspacing=0 cellpadding=0></table>').click(function(){
				cmenu.hide();
				return false;
			});
			var $tr = $('<tr></tr>');
			var $td = $('<td></td>');
			var $div = $('<div class="'+className+'"></div>');
      
			for (var i=0; i<menu.length; i++) {
				var m = menu[i];
				if (m==$.contextMenu.separator) {
					$div.append(cmenu.createSeparator());
				}
				else {
					for (var opt in menu[i]) {
						$div.append(cmenu.createMenuItem(opt,menu[i][opt]));
					}
				}
			}
			if ( cmenu.useIframe ) {
				$td.append(cmenu.createIframe());
			}
			$t.append($tr.append($td.append($div)))
			return $t;
		},
    
		createMenuItem: function(label,obj) {
			var cmenu = this;
			if (typeof obj=="function") {
				obj={
					onclick:obj
				};			
			}
			var o = $.extend({
				onclick:function() { },
				className:'',
				hoverClassName:cmenu.itemHoverClassName,
				icon:'',
				disabled:false,
				title:'',
				hoverItem:cmenu.hoverItem,
				hoverItemOut:cmenu.hoverItemOut,
				colorIcon: null
			},obj);

			var iconStyle = (o.icon)?'background-image:url('+o.icon+');':'';
			var $div = $('<div class="'+cmenu.itemClassName+' '+o.className+((o.disabled)?' '+cmenu.disabledItemClassName:'')+'" title="'+o.title+'"></div>')
				.click(function(e) {
						if(cmenu.isItemDisabled(this)) {
							return false;
						} else {
							return o.onclick.call(cmenu.target,this,cmenu,e)
						}
					})
				.hover(function() {
					var sClass = cmenu.isItemDisabled(this) ? cmenu.disabledItemHoverClassName : o.hoverClassName;
					o.hoverItem.call(this, sClass, o);
				}, function() {
					var sClass = cmenu.isItemDisabled(this) ? cmenu.disabledItemHoverClassName : o.hoverClassName;
					o.hoverItemOut.call(this, sClass, o);
				}
			);

			var $idivAdditionalStyle = '';
			if(o.colorIcon != null) {
				$idivAdditionalStyle = ' context-menu-item-inner-coloricon';
			}

			var $idiv = $('<div class="' + cmenu.innerDivClassName + $idivAdditionalStyle + '" style="'+iconStyle+'">'+label+'</div>');

			if(o.colorIcon != null) {
				var oColorIconDiv = $('<div class="context-menu-item-coloricon" style="background-color: '+o.colorIcon+'"></div>');
				var oClearDiv = $('<div style="clear: left"></div>');
				$div.append(oColorIconDiv);
			}

			$div.append($idiv);
			if(typeof oClearDiv != 'undefined') {
				$div.append(oClearDiv);
			}

			return $div;

		},

		createSeparator: function() {
			return $('<div class="'+this.separatorClassName+'"></div>');
		},

		isItemDisabled: function(item) {
			return $(item).is('.'+this.disabledItemClassName);
		},

		hoverItem: function(c, oElement) {
			$(this).addClass(c);
			
			if(
				typeof oElement.colorIcon != 'undefined' && 
				oElement.colorIcon != null
			) {
				$(this.children[0]).addClass('context-menu-item-coloricon-hover');
			}
		},

		hoverItemOut: function(c, oElement) {
			$(this).removeClass(c);
			
			if(
				typeof oElement.colorIcon != 'undefined' && 
				oElement.colorIcon != null
			) {
				$(this.children[0]).removeClass('context-menu-item-coloricon-hover');
			}
		},

		createShadow: function(cmenu) {
			cmenu.shadowObj = $('<div class="'+cmenu.shadowClass+'"></div>').css( {
				display:'none',
				position:"absolute", 
				zIndex:9998, 
				opacity:cmenu.shadowOpacity, 
				backgroundColor:cmenu.shadowColor
			} );
			$(cmenu.appendTo).append(cmenu.shadowObj);
		},

		showShadow: function(x,y,e) {
			var cmenu = this;
			if (cmenu.shadow) {
				cmenu.shadowObj.css( {
					width:(cmenu.menu.width()+cmenu.shadowWidthAdjust)+"px", 
					height:(cmenu.menu.height()+cmenu.shadowHeightAdjust)+"px", 
					top:(y+cmenu.shadowOffsetY)+"px", 
					left:(x+cmenu.shadowOffsetX)+"px"
					}).addClass(cmenu.shadowClass)[cmenu.showTransition](cmenu.showSpeed);
			}
		},

		beforeShow: function() {
			return true;
		},

		show: function(t,e) {
			var cmenu=this, x=e.pageX, y=e.pageY;
			cmenu.target = t;
			if (cmenu.beforeShow()!==false) {
				if (cmenu.menuFunction) {
					if (cmenu.menu) {
						$(cmenu.menu).remove();
					}
					cmenu.menu = cmenu.createMenu(cmenu.menuFunction(cmenu,t),cmenu);
					cmenu.menu.css({
						display:'none'
					});
					$(cmenu.appendTo).append(cmenu.menu);
				}
				var $c = cmenu.menu;
				x+=cmenu.offsetX;
				y+=cmenu.offsetY;
				var pos = cmenu.getPosition(x,y,cmenu,e);
				cmenu.showShadow(pos.x,pos.y,e);
				// Resize the iframe if needed
				if (cmenu.useIframe) {
					$c.find('iframe').css({
						width:$c.width()+cmenu.shadowOffsetX+cmenu.shadowWidthAdjust,
						height:$c.height()+cmenu.shadowOffsetY+cmenu.shadowHeightAdjust
						});
				}
				$c.css( {
					top:pos.y+"px", 
					left:pos.x+"px", 
					position:"absolute",
					zIndex:9999
				} )[cmenu.showTransition](cmenu.showSpeed,((cmenu.showCallback)?function(){
					cmenu.showCallback.call(cmenu);
				}:null));
				cmenu.shown=true;
				$(document).one('click',null,function(){
					cmenu.hide()
					});
			}
		},

		getPosition: function(clickX,clickY,cmenu,e) {
			var x = clickX+cmenu.offsetX;
			var y = clickY+cmenu.offsetY
			var h = $(cmenu.menu).height();
			var w = $(cmenu.menu).width();
			var dir = cmenu.direction;
			if (cmenu.constrainToScreen) {
				var $w = $(window);
				var wh = $w.height();
				var ww = $w.width();
				if (dir=="down" && (y+h-$w.scrollTop() > wh)) {
					dir = "up";
				}
				var maxRight = x+w-$w.scrollLeft();
				if (maxRight > ww) {
					x -= (maxRight-ww);
				}
			}
			if (dir=="up") {
				y -= h;
			}
			return {
				'x':x,
				'y':y
			};
		},

		hide: function() {
			var cmenu=this;
			if (cmenu.shown) {
				if (cmenu.iframe) {
					$(cmenu.iframe).hide();
				}
				if (cmenu.menu) {
					cmenu.menu[cmenu.hideTransition](cmenu.hideSpeed,((cmenu.hideCallback)?function(){
						cmenu.hideCallback.call(cmenu);
					}:null));
				}
				if (cmenu.shadow) {
					cmenu.shadowObj[cmenu.hideTransition](cmenu.hideSpeed);
				}
			}
			cmenu.shown = false;
		}
	};

	$.fn.contextMenu = function(menu,options) {
		var cmenu = $.contextMenu.create(menu,options);
		return this.each(function(){
			$(this).bind('contextmenu',function(e){
				cmenu.show(this,e);
				return false;
			});
		});
	};
	
})(jQuery);