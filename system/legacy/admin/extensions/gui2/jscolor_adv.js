/*
 * jscolorXXS, JavaScript Color Picker
 *
 * @version 0.9
 * @license GNU Lesser General Public License, http://www.gnu.org/copyleft/lesser.html
 * @author  Peter Dematt�, http://dematte.at
 * @created 2010-08-03
 * @updated 2010-08-13
 * @status  not finished (~300 lines)
 * @link    http://dematte.at/jcolorXS
 * based on the idea of jscolor.com
 * inspired by jondyer.name, quirksmode.com
 */

var jscolor = {
	pickerMode : 'HSV',
	pickerBorderColor : '#fff #a0a0a0 #a0a0a0 #fff',
	pickerInsetColor : '#a0a0a0 #fff #fff #a0a0a0',
	pickerFaceColor : '#f0f0f0',
	pickerBorder : 1,
	pickerInset : 1,
	pickerFace : 10,
	hash : true,
	caps : true,
	bindClass : 'color',
	changeFaceColor : false, // some extra
	closeTimeout : false,
	values: {},

	init : function() {
		jscolor.pM = jscolor.pickerMode;
		var objs = document.getElementsByTagName('input');
		for (var n=0, m=0; n<objs.length; n++) if (objs[n].color = objs[n].className.match(RegExp('(^|\\s)('+jscolor.bindClass+')\\s*(\\{[^}]*\\})?', 'i'))) {
			if (objs[n].color[3]) objs[n].color = eval('('+objs[n].color[3]+')');
			objs[n].addClassName(m++); // renaming to get className for jscolor later (with className.replace())
			jscolor.addEvent(objs[n], 'click', jscolor.togglePicker);
			objs[n].style.backgroundColor = objs[n].value != '' ? (objs[n].value.indexOf('#') != -1 ? '' : '#') + objs[n].value : '#FFFFFF';
			//if (objs[n].value == '') objs[n].value = '#FFFFFF';
			//objs[n].style.color = jscolor.getBrightness(jscolor.HEX2RGB(objs[n].value)) > 128 ? '#000' : '#FFF';
			objs[n].style.color = '#000';

			// Erste Werte speichern, damit im Fehlerfall darauf zurückgegriffen werden kann
			if(
				!(objs[n].id in jscolor.values) &&
				objs[n].value != ''
			) {
				jscolor.values[objs[n].id] = objs[n].value;
			}

		} // else objs.splice(n,1);

		if (!objs[0]) return;
		$('myBody').appendChild(jscolor.build());
		jscolor.addEvents();
	},

	build : function() {
		var newCSS = document.createElement('style'), div = document.createElement('div'), divColl, objs = document.getElementsByTagName('input'), oc,
		ver = navigator.userAgent.toLowerCase(), IE = document.all,
		IE = IE ? ver.indexOf("msie 5") !=-1 || ver.indexOf("msie 6") !=-1 : false,
		cpMS = (jscolor.pickerMode == 'HVS'),
		// twf = 'progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'tw.png\', sizingMethod=\'scale\')',
		// tbf = 'progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'tb.png\', sizingMethod=\'scale\')',
		db1 = 'iVBORw0KGgoAAAANSUhEUgAAA',
		db2 = 'AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAgY0hSTQAAeiYAAICEAAD6AAAAgOgAAHUwAADqYAAAOpgAABdwnLpRPAAAA',
		db3 = db1+'AEAAABlCAYAAACMcInF'+db2+'N',
		db4 = 'R0lGODlhDwAPAPEAAAAAAP///',
		hsv = db1+'LUAAAABCAIAAAAXanid'+db2+'EtJREFUKFNj/M/AwMDDwMAGRhAGMomfi1UjTMtfToZfDCD0BYNEEyRDDQ5HU+BciLexOppy54KDgGLH4YwiZobvSGFMRnBCogiLPwFPaF0BRtROKwAAAABJRU5ErkJggg==',
		tb  = db3+'dJREFUKFMVxE1EA2AAANCvHyVJMpPJyEhEIpGImB0SHbJDOmSH2SEiOkyHDumQDukQERExosN0SIdEOiQmEpFIJBlFibKUUu/wXgghVP0L1apRrepUrwY1qknNalFEUbUqpjbF1a6EOtSpLnWrR73qU78GNKghJZXSsEY0qjGlNa4JTSqjrHKa0rRmNKu85jSvBS1qScta0arWtK4NbWpL2ypoR7sqak/7OtChjnSsE53qTCWd60KXutK1bnSrO93rQY8q60nPetGr3vSuD1X0qS9960e/f/WiMhwclNgJAAAAAElFTkSuQmCC',
		tw  = db3+'hJREFUKFMVxEFEQwEAANC10iRJMpnEjEQkEokRs8OMHabDdEiH6RARHdKhQzqkQzpERESM2CEd0iGRDolEIhKJJFGUKGVpv947vFAQBDWh/8KqVZ3qFVGDGtWkZrWoVVG1KaZ2dSiuhDrVpW71qFd96teABpXUkFJKK6OscsprWAWNaFRjKmpcE5rUlKY1o1nNaV4LWtSSlrWiVa1pXRva1JZK2lZZO9rVnvZ1oEMd6VgnOtWZznWhS13pWje61Z3u9aBHPelZL3rVm971oU996VsV/ehX1T9OtF/8xZgdKAAAAABJRU5ErkJggg==',
		cross = db4+'////////yH5BAEAAAIALAAAAAAPAA8AAAIklB8Qx53b4otSUWcvyiz4/4AeQJbmKY4p1HHapBlwPL/uVRsFADs=',
		arrow = db4+'6g8eKg8eCH5BAEAAAIALAAAAAAHAAsAAAITTIQYcLnsgGxvijrxqdQq6DRJAQA7',
		jsF = jscolor.pickerFace, jsI = jscolor.pickerInset, jsB = jscolor.pickerBorder, jsIC = jscolor.pickerInsetColor,
		jsW = 211+jsI*4+jsF*2, jsH = 101+jsI*2+jsF*2,
		addQ = jsI*2,
		CSSLR = 'position:absolute;top:'+jsF+'px;height:101px !important;height:'+(101+addQ)+'px;border:'+jsI+'px solid #fff;border-color:'+jsIC+';',
		CSS = '.jscolor{position:relative;width:'+jsW+'px !important;width:'+(jsW+jsB*2)+'px;height:'+jsH+'px !important;height:'+(jsH+jsB*2)+'px;border:'+jsB+'px solid #fff;border-color:'+jscolor.pickerBorderColor+';background-color:'+jscolor.pickerFaceColor+'}'+
		'.jscolor .left{'+CSSLR+'left:'+jsF+'px;width:181px !important;width:'+(181+addQ)+'px;background-image:url('+(IE?'hsv.png':'data:image/png;base64,'+hsv+'')+')}'+
		'.jscolor .left .layer, .jscolor .left .mousepad{position:absolute;width:181px;height:101px}'+
		'.jscolor .left .mousepad{cursor:crosshair}'+
		'.jscolor .left .cursor{position:absolute;top:-7px;left:-7px;width:15px;height:15px}'+
		'.jscolor .right{'+CSSLR+'right:'+jsF+'px;width:16px !important;width:'+(16+addQ)+'px;background-color:#f00}'+
		'.jscolor .right .mousepad{position:absolute;right:0px;width:26px;height:100px;cursor:hand;cursor:pointer}'+
		'.jscolor .right .cursor{position:absolute;right:'+(jsI+17)+'px;top:-5px;width:7px;height:11px}'+
		'.cross{background-image:url('+(IE?'cross.gif':'data:image/gif;base64,'+cross+'')+')}'+
		'.arrow{background-image:url('+(IE?'arrow.gif':'data:image/gif;base64,'+arrow+'')+')}'+
		'.tw{background-image:url('+(IE?'tw.png':'data:image/png;base64,'+tw+'')+')}'+
		'.tb{background-image:url('+(IE?'tb.png':'data:image/png;base64,'+tb+'')+')}';
		
		// the customizable part of the CSS;
		for (var n=0, m=0; n<objs.length; n++) if (objs[n].color) {
			oc = objs[n].color;
			if (oc.pickerBorderColor || oc.pickerFaceColor || oc.pickerInsetColor || oc.pickerFace || oc.pickerInset || oc.pickerBorder) {
				pBC = oc.pickerBorderColor || jscolor.pickerBorderColor;
				pFC = oc.pickerFaceColor || jscolor.pickerFaceColor;
				pIC = oc.pickerInsetColor || jscolor.pickerInsetColor;
				jsF = (typeof oc.pickerFace == 'number') ? oc.pickerFace : jscolor.pickerFace;
				jsI = (typeof oc.pickerInset == 'number') ? oc.pickerInset : jscolor.pickerInset;
				jsB = (typeof oc.pickerBorder == 'number') ? oc.pickerBorder : jscolor.pickerBorder;
				jsW = 211+jsI*4+jsF*2;jsH = 101+jsI*2+jsF*2;
				addQ = jsI*2;
	
				var CSSLRC = 'top:'+jsF+'px;height:'+(101+addQ)+'px;border:'+jsI+'px solid #fff;border-color:'+pIC+';',
				CSSC = '.c_'+m+'{width:'+jsW+'px !important;width:'+(jsW+jsB*2)+'px;height:'+jsH+'px !important;height:'+(jsH+jsB*2)+'px;border:'+jsB+'px solid #fff;border-color:'+pBC+';background-color:'+pFC+'}'+
				'.c_'+m+' .left{'+CSSLRC+'left:'+jsF+'px;width:'+(181+addQ)+'px}'+
				'.c_'+m+' .right{'+CSSLRC+'right:'+jsF+'px;width:'+(16+addQ)+'px}'+
				'.c_'+(m++)+' .right .cursor{right:'+(jsI+17)+'px}';
				CSS += CSSC;
			} else m++;
		}
		newCSS.setAttribute('type','text/css');
		if (newCSS.styleSheet) newCSS.styleSheet.cssText = '<!--\n'+CSS+'\n-->';
		else newCSS.appendChild(document.createTextNode('<!--\n'+CSS+'\n-->'));
		document.getElementsByTagName('head')[0].appendChild(newCSS);
		
		div.className = 'jscolor';
		div.innerHTML = '<div class="left"><div class="layer t'+(cpMS?'b':'w')+'"></div><div class="cursor cross"></div><div class="mousepad"></div></div>'+
		'<div class="right t'+(cpMS?'w':'b')+'"><div class="cursor arrow"></div><div class="mousepad"></div></div>';
		divColl = div.getElementsByTagName('div');
		for (var n=0; n<divColl.length; n++) jscolor[['jcL','layL','curL','padL','jcR','curR','padR'][n]]=divColl[n];
		div.style.position = 'absolute';div.style.display = 'none';
		div.style.zIndex = 4001;
		jscolor.jscolor = div;

		Event.observe(jscolor.jscolor, 'mousemove', function() {
			if(jscolor.closeTimeout) {
				window.clearTimeout(jscolor.closeTimeout);
			}
			//jscolor.closeTimeout = window.setTimeout(function() {jscolor.jscolor.hide()}.bind(jscolor), 2000);
		}.bind(jscolor));

		return div;
	},
		
	addEvents : function() {
		jscolor.padL.onmousedown = jscolor.padR.onmousedown = function(e){
			var  e = e ? e : window.event, obj = e.target || e.srcElement;
			obj.xyNow = jscolor.getOrigin(obj);
			jscolor.doDrag(e);
			jscolor.addEvent(document.body, 'mousemove', jscolor.doDrag);
			return false;
		}
		jscolor.addEvent(document.body, 'mouseup', function(){
			jscolor.removeEvent(document.body, 'mousemove');
			jscolor.padL.xyNow = jscolor.padR.xyNow = false;
			return false;
		});
	},
	
	doDrag : function(e,mouseNow,init) {
		var mouseNow = mouseNow ? mouseNow : jscolor.getMousePos((e ? e : window.event), jscolor.padL.xyNow || jscolor.padR.xyNow),
		curL = jscolor.curL.style, curR = jscolor.curR.style,
		cpMS = (jscolor.pickerMode == 'HVS'),
		val = '', rgb;
		mouseNow[1] = mouseNow[1] < 0 ? 0 : mouseNow[1] > 100 ? 100 : mouseNow[1];
		mouseNow[0] = mouseNow[0] < 0 ? 0 : mouseNow[0] > 180 ? 180 : mouseNow[0];
		var Hc = mouseNow[0]/180*255, Sc = Vc = 255-(mouseNow[1]/100*255), // c = coordinate whise
				Sr = jscolor.color.S/100*255, Vr = jscolor.color.V/100*255;    // r = real
		
		if (jscolor.padL.xyNow || init == jscolor.padL) { // left picker
			curL.left = mouseNow[0]-7+'px';
			curL.top = mouseNow[1]-7+'px';
			jscolor.color.putHSV([mouseNow[0]*2, cpMS?100-mouseNow[1]:jscolor.color.S, cpMS?jscolor.color.V:100-mouseNow[1]]);
			jscolor.jcR.style.backgroundColor = 'rgb('+jscolor.HSV2RGB(Hc, cpMS?Sc:255, cpMS?255:Vc)+')';
		} else { // right picker
			curR.top = mouseNow[1]-5+'px';
			jscolor.color.putHSV([jscolor.color.H, cpMS?jscolor.color.S:100-mouseNow[1], cpMS?100-mouseNow[1]:jscolor.color.V]);
		}
		
		if (init) return false;
		if (jscolor.curObj) jo = jscolor.curObj;
		rgb = [jscolor.color.R,jscolor.color.G,jscolor.color.B];
		jo.style.backgroundColor = 'rgb('+rgb+')';
		//jo.style.color = jscolor.getBrightness(rgb) > 128 ? '#000' : '#FFF';
		jo.style.color = '#000';
		val = jscolor.RGB2HEX(rgb);
		jo.value = ((typeof jo.color.hash == 'boolean' ? jo.color.hash : jscolor.hash) ? '#':'') + 
							 ((typeof jo.color.caps == 'boolean' ? jo.color.caps : jscolor.caps) ? val : val.toLowerCase());
		if (jo.color.changeFaceColor) jscolor.jscolor.style.backgroundColor = 'rgb('+rgb+')';
		return false;
	},
	
	color : {
		H:0,S:100,V:100,R:0,G:0,B:0,
		putRGB : function (rgb) {this.R=rgb[0];this.G=rgb[1];this.B=rgb[2]},
		putHSV : function (hsv) {
			this.H=hsv[0];this.S=hsv[1];this.V=hsv[2];
			var rgb = jscolor.HSV2RGB(this.H/360*255,this.S/100*255,this.V/100*255);
			this.R=rgb[0];this.G=rgb[1];this.B=rgb[2]
		}
	},
	
	togglePicker : function(e) {

		e = e ? e : window.event;
		var obj = e.target || e.srcElement;

		// Wenn Wert Fehler aufweist, dann Standardwert nehmen, sonst kommt #NANNANNAN
		var rgb = jscolor.HEX2RGB(obj.value);
		for(var i = 0; i < rgb.length; i++) {
			if(isNaN(rgb[i])) {
				if(obj.id in jscolor.values) {
					rgb = jscolor.HEX2RGB(jscolor.values[obj.id]);
				} else {
					// Fallback: Wenn keine Farbe da ist, muss dieser Picker trotzdem eine Farbe bekommen
					rgb = jscolor.HEX2RGB('#FFFFFF');
				}
				break;
			}
		}

		var jcs = jscolor.jscolor.style,
		HSVxy = jscolor.RGB2HSV(rgb),
		pm = obj.color.pickerMode || jscolor.pM,
		cpMS = (pm == 'HVS');
		jscolor.pickerMode = pm;
		
		if (jcs.display != 'none' && jscolor.curObj == obj) {
			jcs.display = 'none';
			if(jscolor.closeTimeout) {
				window.clearTimeout(jscolor.closeTimeout);
			}
		} else {

			//jscolor.closeTimeout = window.setTimeout(function() {jscolor.jscolor.hide()}.bind(jscolor), 2000);

			jcs.display = 'block';
			jscolor.jscolor.clonePosition(obj, {setLeft: true, setTop: true, offsetTop: 17});
			jscolor.doDrag(null,[null,100-HSVxy[(cpMS?1:2)]],jscolor.padR);
			jscolor.doDrag(null,[HSVxy[0]/2,100-HSVxy[(cpMS?2:1)]],jscolor.padL);
			
			jscolor.jscolor.className = 'jscolor '+obj.className.replace('color ','c_');
			jscolor.layL.className = 'layer t'+(cpMS?'b':'w');jscolor.jcR.className = 'right t'+(cpMS?'w':'b');
			jscolor.jscolor.style.backgroundColor = obj.color.pickerFaceColor || jscolor.pickerFaceColor;
			obj.style.backgroundColor = (obj.value.indexOf('#') != -1 ? '':'#')+obj.value;
			obj.onblur = function(){
				jscolor.jscolor.hide();
			}
		}
		jscolor.curObj = obj;
	},
	
	getBrightness : function(rgb) {return Math.sqrt(rgb[0]*rgb[0]*.241+rgb[1]*rgb[1]*.691+rgb[2]*rgb[2]*.068)},

	RGB2HEX : function (rgb) {
		return((rgb[0]<16?'0':'')+rgb[0].toString(16)+
					 (rgb[1]<16?'0':'')+rgb[1].toString(16)+
					 (rgb[2]<16?'0':'')+rgb[2].toString(16)).toUpperCase();
	},
	
	HEX2RGB : function (hex) {
		hex = hex.replace('#','');
		return [parseInt(hex.substr(0,2),16), parseInt(hex.substr(2,2),16), parseInt(hex.substr(4,2),16)];
	},
	
	HSV2RGB : function(x,y,z) {
		var r=g=b=c=0,d=(100-z/255*100)/100,i=y/255,j=y*(255-z)/255;cPHue=z;
		
		if (x<42.5){r=y;g=x*6*i;g+=(y-g)*d;b=j;}
		else if (x>=42.5&&x< 85){c=42.5;r=(255-(x-c)*6)*i;r+=(y-r)*d;g=y;b=j;}
		else if (x>=85&&x<127.5){c=85;r=j;g=y;b=(x-c)*6*i;b+=(y-b)*d;}
		else if (x>=127.5&&x<170){c=127.5;r=j;g=(255-(x-c)*6)*i;g+=(y-g)*d;b=y;}
		else if (x>=170&&x<212.5){c=170;r=(x-c)*6*i;r+=(y-r)*d;g=j;b=y;}
		else if (x>=212.5){c=212.5;r=y;g=j;b=(255-(x-c)*6)*i;b+=(y-b)*d;}
		return [Math.round(r),Math.round(g),Math.round(b)];
	},
	
	RGB2HSV : function(r, g, b) {
		if (typeof r == 'object') {var b=r[2],g=r[1];r=r[0]}
		var n = Math.min(Math.min(r,g),b), v = Math.max(Math.max(r,g),b), m = v - n;
		if(m === 0) return [0, 0, v/255*100];
		var h = r===n ? 3+(b-g)/m : (g===n ? 5+(r-b)/m : 1+(g-r)/m);
		return [h===6?0:h*60, m/v*100, v/255*100];
	},

	getStyle : function (prop,obj) {
		if (obj.currentStyle)	return obj.currentStyle[prop];
		else if (window.getComputedStyle) return document.defaultView.getComputedStyle(obj,null).getPropertyValue(prop);
	},

	getOrigin : function(obj) {
		var parent=box=null, pos=[];
		

		if (obj.parentNode === null || jscolor.getStyle('display', obj) == 'none') return false;
		
		if (obj.getBoundingClientRect) { // IE
			 box = obj.getBoundingClientRect();
			 var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
			 var scrollLeft = document.documentElement.scrollLeft || document.body.scrollLeft;
			 return [Math.round(box.left) + scrollLeft, Math.round(box.top) + scrollTop];
		}	else if (document.getBoxObjectFor) { // gecko
			 box = document.getBoxObjectFor(obj);
			 pos = [box.x, box.y];
		}	else { // safari/opera
			 pos = [obj.offsetLeft, obj.offsetTop];
			 parent = obj.offsetParent;
			 if (parent != obj) {
					while (parent) {
						 pos[0] += parent.offsetLeft;
						 pos[1] += parent.offsetTop;
						 parent = parent.offsetParent;
					}
			 }
			 var ua = navigator.userAgent.toLowerCase();
			 if (ua.indexOf('opera') != -1  || (ua.indexOf('safari') != -1 && jscolor.getStyle('position', obj) == 'absolute')) {
					pos[1] -= document.body.offsetTop;
			 }
		}
		if (obj.parentNode) parent = obj.parentNode;
		else parent = null;
		while (parent && parent.tagName != 'BODY' && parent.tagName != 'HTML') {
			 pos[0] -= parent.scrollLeft;
			 pos[1] -= parent.scrollTop;
			 if (parent.parentNode) parent = parent.parentNode;
			 else parent = null;
		}
		return pos;
	},

	getMousePos : function(e,relC) {
		var  e = e ? e : window.event;
		if(typeof e.pageX === 'number') return [e.pageX - relC[0], e.pageY - relC[1]];
		else return [e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft - relC[0],
								 e.clientY + document.body.scrollTop  + document.documentElement.scrollTop - relC[1]];
	},
	
	addEvent : function(obj, type, func) {
		if (!obj || !type || !func) return false;
		if (obj == window && type == 'load') {jscolor.addWinEvent(obj, type, func);return false;}
		if (!obj.funcCache) obj.funcCache = [];
		obj.funcCache[type] = func;
		if (obj.addEventListener) obj.addEventListener(type, func, false);
		else obj.attachEvent("on" + type, func);
	},
	
	addWinEvent : function(obj, type, func) {
		if(typeof window.addEventListener != 'undefined') window.addEventListener(type, func, false);
		else if(typeof document.addEventListener != 'undefined') document.addEventListener(type, func, false); //.. opera 
		else if(typeof window.attachEvent != 'undefined') window.attachEvent('on' + type, func);
		else {
			if(typeof window.onload == 'function') {
					var existing = onload;
					window.onload = function() {
							existing();
							func();
					};
			} else window.onload = func;
		}
	},
	
	removeEvent : function(obj, type, func) {
		if (!obj || !type) return false;
		if (!func && !obj.funcCache[type]) return false;
		if (obj.removeEventListener) obj.removeEventListener(type, func||obj.funcCache[type], false);
		else obj.detachEvent("on" + type, func||obj.funcCache[type]);
	}
}

jscolor.addEvent(window, 'load', jscolor.init);