/*
* Timeliner.js
* @version		1.6.1
* @copyright	Tarek Anandan (http://www.technotarek.com)
*/
;(function($) {

        $.timeliner = function(options) {
            if ($.timeliners == null) {
                $.timeliners = { options: [] };
                $.timeliners.options.push(options);
            }
            else {
                $.timeliners.options.push(options);
            }
            $(document).ready(function() {
                for (var i=0; i<$.timeliners.options.length; i++) {
                    startTimeliner($.timeliners.options[i]);
                }
            });
        }

        function startTimeliner(options){
            var settings = {
                timelineContainer: options['timelineContainer'] || '#timelineContainer', // value: selector of the main element holding the timeline's content, default to #timelineContainer
                startState: options['startState'] || 'closed', // value: closed | open,
                // default to closed; sets whether the timeline is
                // initially collapsed or fully expanded
                startOpen: options['startOpen'] || [], // value: array of IDs of
                // single timelineEvents, default to empty; sets
                // the minor events that you want to display open
                // by default on page load
                baseSpeed: options['baseSpeed'] || 200, // value: numeric, default to
                // 200; sets the base speed for animation of the
                // event marker
                speed: options['speed'] || 4, // value: numeric, defalut to 4; a
                // multiplier applied to the base speed that sets
                // the speed at which an event's conents are
                // displayed and hidden
                fontOpen: options['fontOpen'] || '1.2em', // value: any valid CSS
                // font-size value, defaults to 1em; sets the font
                // size of an event after it is opened
                fontClosed: options['fontClosed'] || '1em', // value: any valid CSS
                // font-size value, defaults to 1em; sets the font
                // size of an event after it is closed
                expandAllText: options ['expandAllText'] || '+ expand all', // value:
                // string, sets the text of the expandAll selector
                // after the timeline is fully collapsed
                collapseAllText: options['collapseAllText'] || '- collapse all' // // value:
                // string, sets the text of the expandAll selector
                // after the timeline is fully expanded
            };

            function openEvent(eventHeading,eventBody) {
                $(eventHeading)
                    .removeClass('closed')
                    .addClass('open')
                    .animate({ fontSize: settings.fontOpen }, settings.baseSpeed);
                $(eventBody).show(settings.speed*settings.baseSpeed);
            }

            function closeEvent(eventHeading,eventBody) {
                $(eventHeading)
                    .animate({ fontSize: settings.fontClosed }, 0)
                    .removeClass('open')
                    .addClass('closed');
                $(eventBody).hide(settings.speed*settings.baseSpeed);
            }


            if ($(settings.timelineContainer).data('started')) {
                return;                 // we already initialized this timelineContainer
            } else {
                $(settings.timelineContainer).data('started', true);
                $(settings.timelineContainer+" "+".expandAll").html(settings.expandAllText);
                $(settings.timelineContainer+" "+".collapseAll").html(settings.collapseAllText);

			// If startState option is set to closed, hide all the events; else, show fully expanded upon load
			if(settings.startState==='closed')
			{
				// Close all items
                                $(settings.timelineContainer+" "+".timelineEvent").hide();

				// show startOpen events
				$.each($(settings.startOpen), function(index, value) {
                                    openEvent($(value).parent(settings.timelineContainer+" "+".timelineMinor").find("dt a"),$(value));
				});

			}else{

				// Open all items
                                openEvent($(settings.timelineContainer+" "+".timelineMinor dt a"),$(settings.timelineContainer+" "+".timelineEvent"));

			}

			// Minor Event Click
			/*
			$(settings.timelineContainer).on("click",".timelineMinor dt",function(){
				console.log("timelineMinor dt")
				var currentId = $(this).attr('id');

				// if the event is currently open
				if($(this).find('a').is('.open'))
				{

					closeEvent($("a",this),$("#"+currentId+"EX"))

				} else{ // if the event is currently closed

					openEvent($("a", this),$("#"+currentId+"EX"));

				}

			});
			*/
			$(settings.timelineContainer).on("click",".timelineMinor dt",function(){
				console.log("timelineMinor dt")
				var numDDs = $(this).nextUntil('dt').length
				console.log("num:"+numDDs)
				if(numDDs == 0){
					return
				}
				var fe = $(this).nextUntil('dt')[0]
				// if the event is currently open
				if($(fe).is('.open'))
				{
					closeEvent($(this).nextUntil('dt'),$(this).nextUntil('dt'))

				} else{ // if the event is currently closed

					openEvent($(this).nextUntil('dt'),$(this).nextUntil('dt'));

				}

			});
			// Major Marker Click
			/*
			$(settings.timelineContainer).on("click",".timelineMajorMarker",function()
			{
				console.log("timelineMajorMarker")
				// number of minor events under this major event
				var numEvents = $(this).parents(".timelineMajor").find("dl.timelineMinor").length;

				// number of minor events already open
				var numOpen = $(this).parents(".timelineMajor").find('.open').length;
				console.log("numEvents:"+numEvents+" numOpen:"+numOpen)
				if(numEvents > numOpen )
				{

					openEvent($(this).parents(".timelineMajor").find("dt a","dl.timelineMinor"),$(this).parents(".timelineMajor").find(".timelineEvent"));
					//openEvent($(this).parents(".timelineMajor").find("dl.timelineMinor"),$(this).parents(".timelineMajor").find("dl.timelineMinor"));

				} else{

					closeEvent($(this).parents(".timelineMajor").find("dl.timelineMinor a"),$(this).parents(".timelineMajor").find(".timelineEvent"));
					//closeEvent($(this).parents(".timelineMajor").find("dl.timelineMinor","dl.timelineMinor a"),$(this).parents(".timelineMajor").find("dl.timelineMinor a","dl.timelineMinor"));

				}
			});
*/
			
			$(settings.timelineContainer).on("click",".timelineMajorMarker",function()
			{
				// number of minor events under this major event
				//var numEvents = $(this).parents(".timelineMajor").find("dl.timelineMinor").length;
				
				var numEvents = $(this).next().find("dt").length;
				if(numEvents = 0){
					return
				}	
				//fe = $(this).parents(".timelineMajor").find("dl.timelineMinor")[0]
				fe = $(this).next()
				if($(fe).is('.open'))
				{
					//closeEvent($(this).parents(".timelineMajor").find("dl.timelineMinor"),$(this).parents(".timelineMajor").find("dl.timelineMinor"));
					closeEvent($(this).next(),$(this).next());
				} else{
					//openEvent($(this).parents(".timelineMajor").find("dl.timelineMinor"),$(this).parents(".timelineMajor").find("dl.timelineMinor"));
					openEvent($(this).next(),$(this).next());
				}
			});
			// All Markers/Events
                        $(settings.timelineContainer+" "+".expandAll").click(function()
			{
				if($(this).hasClass('expanded'))
				{

					closeEvent($(this).parents(settings.timelineContainer).find("dt a","dl.timelineMinor"),$(this).parents(settings.timelineContainer).find(".timelineEvent"));
					$(this).removeClass('expanded').html(settings.expandAllText);

				} else{

					openEvent($(this).parents(settings.timelineContainer).find("dt a","dl.timelineMinor"),$(this).parents(settings.timelineContainer).find(".timelineEvent"));
					$(this).addClass('expanded').html(settings.collapseAllText);

				}
			});
                }
	};
})(jQuery);
