<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>{$sName}</title>

	<link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet"> 
	<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <style>
		html, body {
			padding: 0;
			margin: 0;
			height: 100%; overflow: hidden;
			font-family: Roboto, sans-serif;
		}
		header {
			background-color:{$sColor};
			position: absolute;
			width: 100%;
			height: 80px;
			top: 0;
			color: #ffffff;
		}
		#content {
			overflow: hidden;
			width: 100%;
			display: block;
			width: 100%;
			height: calc(100vh - 120px);
			margin-top: 80px;
		}
		footer {
			background-color:{$sColor};
			position: absolute;
			width: 100%;
			height: 40px;
			bottom: 0;
			text-align: center;
			z-index: 10;
		}
		#updated {
			position: absolute;
			width: 100%;
			height: 12px;
			bottom: 0;
			text-align: right;
			z-index: 20;
			line-height: 12px;
			font-size: 10px;
			color: #ffffff;
		}
		#logo {
			padding: 10px 20px;
		}
		#logo img {
			height: 60px;
		}
		#clock {
			position: absolute;
			right: 0;
			top: 0;
			width: 320px;
			padding: 0 20px;
		}
		#clock .day {
			font-size: 90px;
			width: 163px;
			float: left;
			line-height: 83px;
			text-align: right;
		}
		#clock .time {
			font-size: 49px;
			text-align: right;
			line-height: 50px;
		}
		#clock .date {
			font-size: 24px;
			text-align: right;
		}
		.marquee-style {
			font-size: 49px;
			line-height: 80px;
		}
		.marquee {
			position: absolute;
			left: 320px;
			right: 360px;
			top: 0;
		}
		table.students {
			width: 100%;
		}
		
		table.students{
			width:100%; 
			border-collapse:collapse; 
			border-left: 5px solid #999;
			border-right: 5px solid #999;
			table-layout: fixed;
		}
		table.students td,
		table.students th{ 
			font-size: 22px;
			line-height: 33px;
			padding:2px; border:#999 1px solid;
			overflow: hidden;
			white-space: nowrap;
			text-overflow: ellipsis;				
		}
		/* provide some minimal visual accomodation for IE8 and below */
		table.students tr{
			background: #cccccc;
		}
		/*  Define the background color for all the ODD background rows  */
		table.students tr:nth-child(odd){ 
			background: #f7f7f7;
		}
		/*  Define the background color for all the EVEN background rows  */
		table.students tr:nth-child(even){
			background: #dedede;
		}
		table.schedule{
			width:100%; 
			border-collapse:collapse; 
			border-left: 5px solid #999;
			border-right: 5px solid #999;
			table-layout: fixed;
		}
		table.schedule td,
		table.schedule th{ 
			font-size: 20px;
			line-height: 24px;
			padding:2px; border:#999 1px solid;
			overflow: hidden;
			white-space: nowrap;
			text-overflow: ellipsis;				
		}
		/* provide some minimal visual accomodation for IE8 and below */
		table.schedule tr{
			background: #cccccc;
		}
		/*  Define the background color for all the ODD background rows  */
		table.schedule tr:nth-child(odd){ 
			background: #f7f7f7;
		}
		/*  Define the background color for all the EVEN background rows  */
		table.schedule tr:nth-child(even){
			background: #dedede;
		}
		
		.slick-dots {
			display: flex;
			justify-content: center;
			margin: 0;
			padding: 8px 0;

			list-style-type: none;

		}
		
		.slick-dots li {
			margin: 0 0.25rem;
		}

		.slick-dots button {
			display: block;
			width: 24px;
			height: 24px;
			padding: 0;

			border: none;
			border-radius: 100%;
			background-color: #999;

		}

		.slick-dots li.slick-active button {
			background-color: white;
		}
		.room_container {
			position: 	relative; 
			background: url(/admin/extensions/thebing/images/class_scheduling_bg.gif);
			border: 1px solid #f4f4f4;
		}
		.room_content {
			position: absolute;
			top: 0px;
			left: 1px;
			width: {\System::d('ts_scheduling_block_width', 120)-7}px;
			background: #f7f7f7;
			background-color: rgb(247, 247, 247);
			z-index: 1;
			font-size: 12px;
			line-height: 12px;
			overflow: hidden;
			border: 1px solid #ccc;
			border-radius: 3px;
			padding: 1px;
		}
		.room_container .room_content .room_content_padding,
		.room_container .room_content_inactive .room_content_padding {
			padding: 4px;
		}
		
		#tablePlanification {
			
		}
		
		#tablePlanification thead th {
			background: #999;
			color: #fff;
		}
		
		#tablePlanification th,
		#tablePlanification td {
			vertical-align: top;
			overflow: hidden;
		} 
		
		#tablePlanification th {
			white-space: nowrap;
			text-overflow: ellipsis;
			font-size: 14px;
		}
		
		.thRowLabel {
			background-color:#EEEEEE;
			border-bottom:1px solid #CCCCCC;
			border-right:1px solid #CCCCCC;
			color:#333;
			font-weight:bold;
			vertical-align:middle;
			line-height: 13px;
			height: 15px;
			padding: 3px; 
			text-align: right;
			margin-right: -1px;
			font-size: 13px;
			text-align: left;
		}
		
		{$sCss}
		
	</style>
	</head>
	<body>

		<header>
			<div id="logo"><img src="/media/screens/{$sLogo}"></div>
			<div class="marquee marquee-style">{$sTicker}</div>
			<div id="clock"><div class="day"></div><div class="time"></div><div class="date"></div></div>
		</header>

		<div id="content">

		</div>
			
		<footer></footer>
		<div id="updated"></div>
	
		<script
  src="https://code.jquery.com/jquery-3.6.0.min.js"
  integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
  crossorigin="anonymous"></script>
		<script type="text/javascript" src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

		<script>
	(function($) {
        $.fn.textWidth = function(){
            var calc = '<span style="display:none" class="marquee-style">' + $(this).text() + '</span>';
            $('body').append(calc);
            var width = $('body').find('span:last').width();
            $('body').find('span:last').remove();
            return width;
        };
        
        $.fn.marquee = function(args) {

			if(args == 'destroy') {
				if($(this).attr('timeout')) {
					clearTimeout($(this).attr('timeout'));
				}
				return;
			}

			
            var that = $(this);
            var textWidth = that.textWidth(),
                offset = that.width(),
                width = offset,
                css = {
                    'text-indent' : that.css('text-indent'),
                    'overflow' : that.css('overflow'),
                    'white-space' : that.css('white-space')
                },
                marqueeCss = {
                    'text-indent' : width,
                    'overflow' : 'hidden',
                    'white-space' : 'nowrap'
                },
                args = $.extend(true, { count: -1, speed: 1e1, leftToRight: false }, args),
                i = 0,
                stop = textWidth*-1,
                dfd = $.Deferred();
            
			that.css('text-indent', '0px');
			
            function go() {
                if(!that.length) return dfd.reject();
                if(width <= stop) {
                    i++;
                    if(i == args.count) {
                        that.css(css);
                        return dfd.resolve();
                    }
                    if(args.leftToRight) {
                        width = textWidth*-1;
                    } else {
                        width = offset;
                    }
                }
                that.css('text-indent', width + 'px');
                if(args.leftToRight) {
                    width++;
                } else {
                    width--;
                }
                that.attr('timeout', setTimeout(go, args.speed));
            };
			
            if(args.leftToRight) {
                width = textWidth*-1;
                width++;
                stop = offset;
            } else {
                width--;            
            }
			
            that.css(marqueeCss);
            go();

            return dfd.promise();
        };
    })(jQuery); 
	
	{literal}

	function addZero(x) {
		if (x < 10) {
		  return x = '0' + x;
		} else {
		  return x;
		}
	}

	function updateClock() {
		var date = new Date();

		var h = addZero(date.getHours());
		var m = addZero(date.getMinutes());
		var s = addZero(date.getSeconds());
		var D = addZero(date.getDate());
		var M = addZero(date.getMonth()+1);
		var Y = addZero(date.getYear()+1900);
		var d = addZero(date.toLocaleString(undefined, { weekday: "short" }));

		$('.day').text(d);
		$('.time').text(h + ':' + m);
		$('.date').text(D+'/'+M+'/'+Y);

	}
	
	function updateUpdated() {
		
		var date = new Date();

		var h = addZero(date.getHours());
		var m = addZero(date.getMinutes());
		var s = addZero(date.getSeconds());
		var D = addZero(date.getDate());
		var M = addZero(date.getMonth()+1);
		var Y = addZero(date.getYear()+1900);

		$('#updated').text(D+'/'+M+'/'+Y+' '+h + ':' + m);

	}
	
	{/literal}
		
	var iChecksum = 0;
		
	function updateContent() {

		$.get('{route name='TsScreen.ts_screens_update' sKey=$sKey}?checksum='+iChecksum, function( data ) {
			
			if($('.marquee').text() != data.sTicker) {
				$('.marquee').marquee('destroy');
				$('.marquee').text(data.sTicker);
				$('.marquee').marquee();
			}
			
			if(data.oData) {
				updateUpdated();
			}
			
			if(
				data.sElement == 'roomplan' &&
				data.oData
			) {
		
				var html = '';
		
				if(data.oData.error) {
					html += '<div style="margin-top: 10%;text-align: center;font-size: 30px;">'+data.oData.error+'</div>';
				} else {
					
					rows = Math.floor((window.innerHeight-120) / 38);
					
					var i,j, lists = [], chunk = rows-1;
					for (i = 0,j = data.oData.students.length; i < j; i += chunk) {
						lists.push(data.oData.students.slice(i, i + chunk));
						// do whatever
					}
					
					html += '<div id="list-container">';
					
					lists.forEach(function(list){
						
						html += '<div><table class="students">';
						html += '<thead>';
						html += '<tr>';
						html += '<th style="width: 40%;">'+data.oData.translations.name+'</th>';
						html += '<th style="width: 25%;">'+data.oData.translations.class+'</th>';
						html += '<th style="width: 10%;">'+data.oData.translations.time+'</th>';
						html += '<th style="width: 25%;">'+data.oData.translations.room+'</th>';
						html += '</tr>';
						html += '</thead>';
						html += '<tbody>';
						
						list.forEach(function(student){
							html += '<tr>';
							html += '<td>'+student.lastname+', '+student.firstname+'</td>';
							html += '<td>'+student.class+'</td>';
							html += '<td>'+student.from.substring(0,5)+'</td>';
							html += '<td>'+student.room;
							if(student.floor) {
								html += ', '+student.floor;
							}
							
							html += '</td>';
							html += '</tr>';
						});
						
						html += '</tbody>';
						html += '</table></div>';
						
					});
					
					html += '</div>';
					
				}

				$('#list-container').slick('unslick');

				$('#content').html(html);
				iChecksum = data.iChecksum;

				$('#list-container').slick({
					slidesToShow: 2,
					slidesToScroll: 1,
					autoplay: true,
					autoplaySpeed: (data.oData.autoplay_speed * 1000),
					arrows: false,
					dots: true,
					appendDots: $('footer')
				});

			} else if(
				data.sElement == 'schedule' &&
				data.oData
			) {
		
				$('#content').html(data.oData.schedule);
				iChecksum = data.iChecksum;
		
				tableHeight = $('#tablePlanification').height();
				availableHeight = window.innerHeight-120;
		
				ratio = Math.floor((availableHeight / tableHeight)*100) / 100;
		
				$('#tablePlanification').css('transform', 'scale('+ratio+')');
				$('#tablePlanification').css('transform-origin', '0% 0% 0px');
		
			} else if(
				data.sElement == 'editor' &&
				data.oData
			) {
		
				$('#content').html(data.oData.html);
				iChecksum = data.iChecksum;
		
			} else if(
				data.sElement === null
			) {
				$('#content').html('');
				iChecksum = -1;
			}
			
		});
	}
	
	$(document).ready(function() {
		
		updateClock();
		// Jede Sekunde
		setInterval(updateClock, 1000);
		
		updateContent();
		// Alle 2 Minuten
		setInterval(updateContent, 60000);
		
		$('.marquee').marquee();
		
	});	
	
	</script>
	
  </body>
</html>
