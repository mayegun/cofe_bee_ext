(function ($) {

// Define our objects.
  Drupal.batCalendar = Drupal.batCalendar || {};
  Drupal.batCalendar.Modal = Drupal.batCalendar.Modal || {};

  var ajax = undefined;

  Drupal.behaviors.bat_event = {
    attach: function (context) {

      var calendars = [];
      for (id in drupalSettings.batCalendar) {
        calendars[id] = new Array('#' + drupalSettings.batCalendar[id]['id']);
      }

      // Refresh the event once the modal is closed.
      $(window).on('dialog:beforeclose', function (e, dialog, $element) {
        $.each(calendars, function (key, value) {
          $(value[0]).fullCalendar('refetchEvents');
        });
      });

      $.each(calendars, function (key, value) {
        $(value[0]).once().fullCalendar({
          schedulerLicenseKey: drupalSettings.batCalendar[key].schedulerLicenseKey,
          themeSystem: drupalSettings.batCalendar[key].themeSystem,
          locale: drupalSettings.batCalendar[key].locale,
          slotWidth: drupalSettings.batCalendar[key].slotWidth,
          height: drupalSettings.batCalendar[key].calendarHeight,
          editable: drupalSettings.batCalendar[key].editable,
          selectable: drupalSettings.batCalendar[key].selectable,
          displayEventTime: false,
          eventStartEditable: drupalSettings.batCalendar[key].eventStartEditable,
          eventDurationEditable: drupalSettings.batCalendar[key].eventDurationEditable,
          dayNamesShort: [Drupal.t('Sun'), Drupal.t('Mon'), Drupal.t('Tue'), Drupal.t('Wed'), Drupal.t('Thu'), Drupal.t('Fri'), Drupal.t('Sat')],
          monthNames: [Drupal.t('January'), Drupal.t('February'), Drupal.t('March'), Drupal.t('April'), Drupal.t('May'), Drupal.t('June'), Drupal.t('July'), Drupal.t('August'), Drupal.t('September'), Drupal.t('October'), Drupal.t('November'), Drupal.t('December')],
          header: {
            left: drupalSettings.batCalendar[key].headerLeft,
            center: drupalSettings.batCalendar[key].headerCenter,
            right: drupalSettings.batCalendar[key].headerRight,
          },
          allDayDefault: drupalSettings.batCalendar[key].allDayDefault,
          businessHours: drupalSettings.batCalendar[key].businessHours,
          defaultView: drupalSettings.batCalendar[key].defaultView,
          selectConstraint: (drupalSettings.batCalendar[key].selectConstraint == null) ? undefined : drupalSettings.batCalendar[key].selectConstraint,
          minTime: drupalSettings.batCalendar[key].minTime,
          maxTime: drupalSettings.batCalendar[key].maxTime,
          hiddenDays: drupalSettings.batCalendar[key].hiddenDays,
          validRange: drupalSettings.batCalendar[key].validRange,
          defaultDate: $.fullCalendar.moment(drupalSettings.batCalendar[key].defaultDate),
          views: {
            timelineDay: {
              buttonText: drupalSettings.batCalendar[key].viewsTimelineDayButtonText,
              slotDuration: drupalSettings.batCalendar[key].viewsTimelineDaySlotDuration,
            },
            timelineSevenDay: {
              buttonText: drupalSettings.batCalendar[key].viewsTimelineSevenDayButtonText,
              duration: drupalSettings.batCalendar[key].viewsTimelineSevenDayDuration,
              slotDuration: drupalSettings.batCalendar[key].viewsTimelineSevenDaySlotDuration,
              type: 'timeline',
            },
            timelineTenDay: {
              buttonText: drupalSettings.batCalendar[key].viewsTimelineTenDayButtonText,
              duration: drupalSettings.batCalendar[key].viewsTimelineTenDayDuration,
              slotDuration: drupalSettings.batCalendar[key].viewsTimelineTenDaySlotDuration,
              type: 'timeline',
            },
            timelineThirtyDay: {
              buttonText: drupalSettings.batCalendar[key].viewsTimelineThirtyDayButtonText,
              duration: drupalSettings.batCalendar[key].viewsTimelineThirtyDayDuration,
              slotDuration: drupalSettings.batCalendar[key].viewsTimelineThirtyDaySlotDuration,
              type: 'timeline',
            },
            timeline365Day: {
              buttonText: drupalSettings.batCalendar[key].viewsTimeline365DayButtonText,
              duration: drupalSettings.batCalendar[key].viewsTimeline365DaySlotDuration,
              type: 'timeline',
            }
          },
          groupByResource: drupalSettings.batCalendar[key].groupByResource,
          groupByDateAndResource: drupalSettings.batCalendar[key].groupByDateAndResource,
          allDaySlot: drupalSettings.batCalendar[key].allDaySlot,
          firstDay: drupalSettings.batCalendar[key].firstDay,
          defaultTimedEventDuration: drupalSettings.batCalendar[key].defaultTimedEventDuration,
          customButtons: drupalSettings.batCalendar[key].customButtons,
          eventOrder: drupalSettings.batCalendar[key].eventOrder,
          titleFormat: drupalSettings.batCalendar[key].titleFormat,
          slotLabelFormat: drupalSettings.batCalendar[key].slotLabelFormat,
          resourceAreaWidth: drupalSettings.batCalendar[key].resourceAreaWidth,
          resourceLabelText: drupalSettings.batCalendar[key].resourceLabelText,
          resources: Drupal.url('bat_api/calendar-units?_format=json&types=' + drupalSettings.batCalendar[key].unitType + '&ids=' + drupalSettings.batCalendar[key].unitIds + '&event_type=' + drupalSettings.batCalendar[key].eventType),
          selectOverlap: function (event) {
            // Allow selections over background events, but not any other types of events.
            return event.rendering === 'background';
          },
          events: Drupal.url('bat_api/calendar-events?_format=json&unit_types=' + drupalSettings.batCalendar[key].unitType + '&unit_ids=' + drupalSettings.batCalendar[key].unitIds + '&event_types=' + drupalSettings.batCalendar[key].eventType + '&background=' + drupalSettings.batCalendar[key].background),
          windowResize: function (view) {
            $(this).fullCalendar('refetchEvents');
          },
          select: async function (start, end, jsEvent, view, resource) {
            const nid = drupalSettings.batCalendar[0].nid;
            const duration = 1;
            let startDate = Drupal.batCalendar.SetDate(start._i);
            let endDate = Drupal.batCalendar.SetDate(new Date(startDate).setDate(new Date(startDate).getDate() + parseInt(duration)));

            const checkAvail = await $.ajax({
                                  url: `/cofe-ext-bee/check-availability/${nid}/${startDate}/${endDate}`,
                                  type: "GET"
                                }); 

            if (checkAvail.status === 200) {
              Drupal.batCalendar.Modal(this, nid, startDate);
            }
          },
          selectOverlap: function (event) {
            return event;
          },
          eventOverlap: function (stillEvent, movingEvent) {
            // Prevent events from being drug over blocking events.
            return !stillEvent.blocking && (stillEvent.type == movingEvent.type);
          },
          eventResize: function (event, delta, revertFunc) {
            if (event.editable) {
              saveBatEvent(event, revertFunc, calendars, key);
            } else {
              revertFunc();
            }
          },
          eventAfterRender: function (event, element, view) {
            // Append event title when rendering as background.
            if (event.rendering == 'background' && event.fixed == 0) {
              if ((view.type == 'timelineThirtyDay' || view.type == 'timelineMonth' || view.type == 'timelineYear') && drupalSettings.batCalendar[key].repeatEventTitle) {
                var start = event.start.clone();
                start.subtract(start.hour(), 'hours').subtract(start.minute(), 'minutes');

                if (event.end === null) {
                  var end = event.start.clone();
                } else {
                  var end = event.end.clone();
                }

                var index = 0;

                // Event width.
                var width = element.width()
                // Event colspan number.
                var colspan = element.get(0).colSpan;

                if (event.end != null) {
                  end.add(1, 'minute');
                  // Single cell width.
                  var cell_width = width / (end.diff(start, 'days'));

                  while (start < end) {
                    element.append('<span class="fc-title" style="position:absolute; top:8px; left:' + (index * cell_width + 3) + 'px;">' + (event.title || '&nbsp;') + '</span>');
                    start = start.add(1, 'day');
                    index++;
                  }
                } else {
                  element.append('<span class="fc-title" style="position:absolute; top:8px; left:3px;">' + (event.title || '&nbsp;') + '</span>');
                  start = start.add(1, 'day');
                }
              } else {
                element.append('<span class="fc-title" style="position:absolute; top:8px; left:3px;">' + (event.title || '&nbsp;') + '</span>');
              }
            }
          }
        });
      });
    }
  };

  Drupal.batCalendar.SetDate = function (timestamp) {
    let date = new Date(timestamp);
    let sYear = date.getFullYear();
    let sMonth = (date.getMonth() + 1).toString().padStart(2, '0');
    let sDay = date.getDate().toString().padStart(2, '0');
    return sYear + '-' + sMonth + '-' + sDay;
  },

    /**
     * Initialize the modal box.
     */
    Drupal.batCalendar.Modal = function (element, nid, sd, ed) {
      var url;

      // To make all calendars trigger correctly the getResponse event we need to
      // initialize the ajax instance with the global calendar table element.
      var calendars_table = $(element.el).closest('.calendar-set').get();

      if (drupalSettings.batCalendar[0] !== undefined) {
        if (drupalSettings.batCalendar[0].user_info !== undefined) {
          url = `/cofe-ext-bee/calendar-reservation-form/${nid}/${sd}/${drupalSettings.batCalendar[0].user_info}`;
        }
      }

      // Create a drupal ajax object that points to the event form.
      var element_settings = {
        url: url,
        event: 'getResponse',
        progress: {type: 'throbber'},
        selector: '#drupal-modal'
      };

      var response = {
        selector: '#drupal-modal',
        dialogOptions: drupalSettings.batCalendar[0].dialogOptions,
      };

      if (ajax == undefined) {
        ajax = new Drupal.Ajax(element_settings.url, calendars_table, element_settings);
      } else {
        ajax.url = url;
        ajax.options.url = url + '?' + Drupal.ajax.WRAPPER_FORMAT + '=drupal_ajax';
        ajax.element_settings.url = url;
      }

      Drupal.AjaxCommands.prototype.openDialog(ajax, response, 0);

      $('#drupal-modal').html(drupalSettings.batCalendar[0].dialogOptions.loading);

      // We need to trigger the AJAX getResponse manually because the
      // fullcalendar select event is not recognized by Drupal's AJAX.
      $(calendars_table).trigger('getResponse');
    };
})(jQuery);
