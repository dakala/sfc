/**
 * @file
 */

(function ($) {

  Drupal.behaviors.simpleFullcalendar = {
    attach: function (context, settings) {
      var $url = settings.path.baseUrl;
      $('#calendar').fullCalendar({
        header: {
          left: 'prev,next today',
          center: 'title',
          right: 'month,basicWeek,basicDay'
        },
        editable: true,
        eventLimit: true,
        events: $url + 'calendar/calendar.json'
      });
    }
  };

})(jQuery);
