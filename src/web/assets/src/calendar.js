import dayGridPlugin from '@fullcalendar/daygrid';
import iCalendarPlugin from '@fullcalendar/icalendar';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import luxonPlugin, { toLuxonDateTime } from '@fullcalendar/luxon3';
import multiMonthPlugin from '@fullcalendar/multimonth';
import timeGridPlugin from '@fullcalendar/timegrid';
import { Calendar } from '@fullcalendar/core';
import { DateTime } from 'luxon';

import './calendar.css';

Craft.Eventful ??= {};

(($) => {
  Craft.Eventful.Calendar = Garnish.Base.extend({
    settingsKey: 'eventful.settings',

    views: {
      day: 'timeGridDay',
      week: 'timeGridWeek',
      month: 'dayGridMonth',
      year: 'multiMonthYear',
      schedule: 'listMonth',
    },

    calendarOptions: null,

    $contentHeading: null,
    $sidebarToggle: null,

    $title: null,
    $calendar: null,
    $timezoneNotice: null,
    $contextTrigger: null,
    $contextActions: null,
    contextMenu: null,
    $form: null,
    $todayBtn: null,
    $previousBtn: null,
    $nextBtn: null,
    $viewBtn: null,
    $subscribeBtn: null,
    $deleteSubmitBtn: null,
    confirmDeleteModal: null,

    init() {
      this.setSettings(Craft.getLocalStorage(this.settingsKey, {}));

      this.$sidebarToggle = $('#sidebar-trigger');
      this.$contentHeading = $('#content-heading');

      this.$title = $('#calendar-title');
      this.$calendar = $('#calendar');
      this.$timezoneNotice = $('#calendar-timezone-notice');
      this.calendarOptions = this.$calendar.data();
      this.$contextTrigger = $('#calendar-context-menu-trigger');
      this.$contextActions = $('#calendar-context-menu a');
      this.$form = $('#calendar-form');
      this.$todayBtn = $('#calendar-today-btn');
      this.$previousBtn = $('#calendar-prev-btn');
      this.$nextBtn = $('#calendar-next-btn');
      this.$viewBtn = $('#calendar-view-btn');
      this.$subscribeBtn = $('#calendar-subscribe-btn');

      this.initForm();
      this.initTimezoneNotice();
      this.initCalendar();

      if (this.$contextTrigger.length) {
        this.contextMenu = new Garnish.DisclosureMenu(this.$contextTrigger);

        this.addListener(this.$contextTrigger, 'click', () => {
          this.contextMenu.show();
        });

        this.addListener(this.$contextActions, 'click', 'createEvent');
      }

      this.addListener(this.$form.find('input'), 'change', 'onFormChange');

      this.addListener(this.$todayBtn, 'click', () => {
        this.calendar.today();
      });

      this.addListener(this.$previousBtn, 'click', () => {
        this.calendar.prev();
      });

      this.addListener(this.$nextBtn, 'click', () => {
        this.calendar.next();
      });

      this.addListener($('[data-calendar-view]'), 'click', (event) => {
        let view = this.views[$(event.target).data('calendarView')];
        $(event.target)
          .closest('[data-disclosure-menu]')
          .data('disclosureMenu')
          .hide();
        this.calendar.changeView(view);
      });

      this.addListener(this.$sidebarToggle, 'open', () => {
        this.calendar.updateSize();
      });
      this.addListener(this.$sidebarToggle, 'close', () => {
        this.calendar.updateSize();
      });

      this.addListener(this.$subscribeBtn, 'click', 'copySubscribeUrl');

      window.addEventListener('popstate', (event) => {
        if (event.state.date) {
          this.calendar.gotoDate(event.state.date);
        }
      });
    },

    initCalendar() {
      this.calendar = new Calendar(this.$calendar[0], {
        plugins: [
          dayGridPlugin,
          timeGridPlugin,
          multiMonthPlugin,
          listPlugin,
          interactionPlugin,
          iCalendarPlugin,
          luxonPlugin,
        ],
        // like the rest of the control panel, show dates in the user's preferred timezone
        // (or the system timezone), rather than the browser's
        timeZone: Craft.timezone,
        locale: this.calendarOptions.locale,
        firstDay: this.calendarOptions.firstDay,
        initialView:
          this.views[this.calendarOptions.initialView] ||
          this.settings.view ||
          'dayGridMonth',
        initialDate: this.calendarOptions.initialDate,
        dayMaxEventRows: true,
        headerToolbar: false,
        listDayFormat: {
          weekday: 'long',
          month: 'short',
          day: 'numeric',
        },
        listDaySideFormat: false,
        eventSources: [
          this.loadEvents.bind(this),
          ...this.calendarOptions.extraEventSources,
        ],
        dayHeaderContent: this.getDayHeaderContent.bind(this),
        dayCellContent: this.getDayCellContent.bind(this),
        moreLinkContent: this.getMoreLinkContent.bind(this),
        datesSet: this.onSetDates.bind(this),
        dayHeaderDidMount: this.onDayHeaderMounted.bind(this),
        dateClick: this.showContextMenu.bind(this),
        eventClick: this.showHud.bind(this),
        moreLinkClick: this.onClickMoreLink.bind(this),
        windowResize: this.onResize.bind(this),
      });
      this.calendar.render();
      this.onResize();
    },

    initTimezoneNotice() {
      // only if they show different times at some point
      let browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      if (!browserTimezone || hasSameOffsets(browserTimezone, Craft.timezone)) {
        return;
      }

      // e.g. "Eastern Time", with the cities if both timezones have the same name
      // (e.g. "Pacific Time – Los Angeles" and "Pacific Time – Vancouver")
      let locale = this.calendarOptions.locale || undefined;
      let name = getTimezoneName(Craft.timezone, locale);
      let browserName = getTimezoneName(browserTimezone, locale);
      if (name === browserName) {
        name += ` – ${getTimezoneCity(Craft.timezone)}`;
        browserName += ` – ${getTimezoneCity(browserTimezone)}`;
      }

      // Craft's timezone is the system one if the user hasn't chosen one
      let hasPreference = this.$timezoneNotice.data('hasPreference');

      this.$timezoneNotice
        .append(
          hasPreference
            ? `Times are in your preferred time zone (${name}) instead of your browser’s (${browserName}). `
            : `Times are in the system time zone (${name}) instead of your browser’s (${browserName}). `,
          $('<a>', {
            href: Craft.getCpUrl('myaccount/preferences'),
            text: 'Preferences',
          }),
        )
        .removeClass('hidden');
    },

    /**
     * Returns a date from FullCalendar in the calendar's timezone,
     * since its `Date` objects are in the browser's.
     */
    toCalendarDateTime(date) {
      return toLuxonDateTime(date, this.calendar);
    },

    loadEvents(info, success) {
      $.get(
        this.calendarOptions.sourceUrl,
        {
          start: info.start.toISOString(),
          end: info.end.toISOString(),
          ...this.settings,
        },
        success,
      );
    },

    getDayHeaderContent(info) {
      if (
        info.view.type === 'timeGridDay' ||
        info.view.type === 'timeGridWeek'
      ) {
        let weekday = info.text.split(' ')[0];
        return {
          domNodes: $('<div>', { text: weekday }).append(
            $('<div>', {
              class: 'day-number',
              text: this.toCalendarDateTime(info.date).day,
            }),
          ),
        };
      } else {
        return true;
      }
    },

    getDayCellContent(info) {
      if (
        info.view.type === 'dayGridMonth' &&
        info.dayNumberText &&
        !info.isToday &&
        this.toCalendarDateTime(info.date).day === 1
      ) {
        let month = this.calendar.formatDate(info.date, { month: 'short' });
        return info.dayNumberText + ' ' + month;
      } else {
        return true; // default
      }
    },

    getMoreLinkContent(info) {
      if (info.view.type === 'multiMonthYear') {
        return info.shortText;
      } else {
        return true; // default
      }
    },

    onSetDates(dateInfo) {
      this.setSettings({ view: this.calendar.view.type });
      Craft.setLocalStorage(this.settingsKey, this.settings);

      let title = dateInfo.view.title;
      let date = dateInfo.view.currentStart;
      let calendarDate = this.toCalendarDateTime(date);

      this.$title.text(title);

      if (!this.$contentHeading.length) {
        this.$contentHeading = $('<span>', { id: 'content-heading' });
        this.$contentHeading.appendTo('.screen-title');
      }
      this.$contentHeading.text(title);

      let view = Object.keys(this.views).find(
        (key) => this.views[key] === this.calendar.view.type,
      );

      this.$viewBtn.text(upperFirst(view));

      this.scrolledListToNow = false;

      const url = new URL(window.location.href);
      url.pathname = [
        ...url.pathname.split('/').slice(0, 3),
        view,
        calendarDate.year,
        calendarDate.month,
        calendarDate.day,
      ].join('/');

      window.history.pushState({ date }, '', url);
    },

    onDayHeaderMounted(info) {
      if (
        info.view.type.startsWith('list') &&
        !info.isPast &&
        !this.scrolledListToNow
      ) {
        if (!$(info.el).is(':first-child')) {
          let scroller = $(info.el).closest('.fc-scroller')[0];
          let scrollerTop = scroller.getBoundingClientRect().top;
          let itemTop = info.el.getBoundingClientRect().top;

          scroller.scrollTo({ top: itemTop - scrollerTop });
        }
        this.scrolledListToNow = true;
      }
    },

    onClickMoreLink() {
      this.hideAllHuds();

      // display a panel with a full list of events for that day
      // https://fullcalendar.io/docs/moreLinkClick
      return 'popover';
    },

    onResize() {
      if (this.contextMenu) {
        this.contextMenu.hide();
      }
      this.hideAllHuds();

      let rect = this.calendar.el.getBoundingClientRect();

      this.calendar.setOption(
        'height',
        'calc(100vh - ' + rect.top + 'px - var(--l))',
      );
    },

    editEvent(jsEvent) {
      jsEvent.preventDefault();

      this.hideAllHuds();

      let element = jsEvent.data.event.extendedProps.element;
      Craft.createElementEditor(element.type, element).on('submit', () => {
        this.calendar.refetchEvents();
      });
    },

    showContextMenu(info) {
      if (!this.contextMenu) {
        return;
      }

      this.hideAllHuds();

      let $alignmentElement = $('<div>', {
        css: {
          position: 'absolute',
          left: info.jsEvent.clientX + 'px',
          top: info.jsEvent.clientY + 'px',
        },
      }).appendTo('body');

      this.selection = info;
      this.contextMenu.$alignmentElement = $alignmentElement;
      this.contextMenu.show();

      setTimeout(() => {
        $alignmentElement.remove();
      }, 500);
    },

    initForm() {
      if (!this.$form.length) {
        this.onFormChange();
        return;
      }

      this.$form.find(':checkbox[data-color]').each((index, input) => {
        $(input).parent().css('--color', $(input).data('color'));
      });

      this.$form.find('input').each((index, input) => {
        switch (input.name) {
          case 'organizers[]':
            input.checked =
              !this.settings.organizers ||
              this.settings.organizers.includes(input.value);
            break;
          case 'sources[]':
            input.checked =
              !this.settings.sources ||
              this.settings.sources.includes(input.value);
            break;
        }
      });
    },

    onFormChange() {
      this.setSettings({
        sources: this.$form
          .find('input[name^="sources"]:checked')
          .map((index, input) => input.value)
          .get(),
        organizers: this.$form
          .find('input[name^="organizers"]:checked')
          .map((index, input) => input.value)
          .get(),
      });
      Craft.setLocalStorage(this.settingsKey, this.settings);

      this.calendar?.refetchEvents();
    },

    createEvent(event) {
      if (!this.selection) {
        return;
      }

      event.preventDefault();

      if (this.contextMenu) {
        this.contextMenu.hide();
      }

      // events created on a day start at the next half hour
      let start = this.selection.allDay
        ? setTime(this.toCalendarDateTime(this.selection.date), 30)
        : this.selection.date;

      let dateFieldHandle = $(event.target).data('dateFieldHandle');

      Craft.sendActionRequest('POST', $(event.target).attr('href'), {
        data: {
          [dateFieldHandle]: {
            start: start.toISOString(),
            timezone: Craft.timezone,
          },
        },
      }).then(({ data }) => {
        Craft.createElementEditor(data.modelClass, {
          elementId: data.modelId,
          params: { fresh: 1 },
        }).on('submit', () => {
          this.calendar.refetchEvents();
        });
      });
    },

    showHud(info) {
      info.jsEvent.preventDefault();

      // in case the event was clicked on from the "more" popover,
      // remove the popover manually
      // $(info.jsEvent.target).closest('.fc-popover').remove();

      let $eventEl = $(info.el);
      let $content = this.buildHudContent(info);

      let hud = $eventEl.data('hud');
      if (!hud) {
        hud = new Garnish.HUD(info.el, $content, {
          hudClass: 'hud event-hud',
          orientations: ['left', 'right', 'top', 'bottom'],
          withShade: false,
        });
        $eventEl.data('hud', hud);
      } else {
        hud.show();
      }
    },

    buildHudContent(info) {
      let extra = info.event.extendedProps;

      let $footer = $('<div>', {
        class: 'hud-footer flex flex-justify',
      }).append(
        $('<div>', { class: 'event-hud-type' }).append(
          $('<div>', {
            class: 'event-hud-type-circle',
            css: { background: info.event.backgroundColor },
          }),
          $('<div>', { text: extra.sourceLabel }),
        ),
      );

      let $footerActions = $('<div>', { class: 'flex' });

      if (extra.element.canEdit) {
        let $editBtn = $('<a>', {
          class: 'btn',
          href: info.el.href,
          'data-icon': 'pencil',
          text: 'Edit',
        });

        this.addListener($editBtn, 'click', info, 'editEvent');

        $footerActions.append($editBtn);
      } else {
        let $viewBtn = $('<a>', {
          class: 'btn',
          href: info.el.href,
          text: 'View',
        });

        this.addListener($viewBtn, 'click', info, 'editEvent');

        $footerActions.append($viewBtn);
      }

      if (extra.element.canDelete) {
        let $deleteBtn = $('<button>', {
          type: 'button',
          class: 'btn',
          'data-icon': 'trash',
          title: 'Delete',
        });

        this.addListener($deleteBtn, 'click', info, 'confirmDeleteEvent');

        $footerActions.append($deleteBtn);
      }

      $footer.append($footerActions);

      let $closeBtn = $('<button>', {
        type: 'button',
        class: 'btn event-hud-close-btn',
        'data-icon': 'remove',
      });

      this.addListener($closeBtn, 'click', 'hideHud');

      let $body = $('<div>', { class: 'event-hud-body' }).append(
        $('<h2>', { text: info.event.title }),
        $('<div>', { class: 'event-hud-date' }).append(
          $('<div>', { text: extra.dateDescription }),
          $('<div>', {
            class: 'event-hud-repeat-description',
            text: extra.repeatDescription,
          }),
        ),
      );

      if (extra.location) {
        $body.append($('<div>', { html: extra.location }));
      }

      if (extra.description) {
        $body.append($('<div>', { html: extra.description }));
      }

      if (extra.zoomMeeting) {
        $body.append(
          $('<a>', {
            class: 'btn submit secondary',
            href: extra.zoomMeeting,
            target: '_blank',
            text: 'Start Zoom meeting',
          }),
        );
      }

      return $('<div>').append($closeBtn, $body, $footer);
    },

    hideHud(jsEvent) {
      let $eventEl = $(jsEvent.target).closest('.hud');
      let hud = $eventEl.data('hud');

      if (hud) {
        hud.hide();
      }
    },

    hideAllHuds() {
      for (let hudID in Garnish.HUD.activeHUDs) {
        if (!Garnish.HUD.activeHUDs.hasOwnProperty(hudID)) {
          continue;
        }
        Garnish.HUD.activeHUDs[hudID].hide();
      }
    },

    confirmDeleteEvent(jsEvent) {
      let extra = jsEvent.data.event.extendedProps;
      let repeats = !!extra.repeatDescription;

      let $cancelBtn = $('<button>', {
        type: 'button',
        class: 'btn',
        text: 'Cancel',
      });

      this.$deleteSubmitBtn = Craft.ui.createSubmitButton({
        label: 'Delete',
        spinner: true,
      });

      let $body = $('<div>', { class: 'body' }).append(
        $('<h3>', {
          text: repeats ? 'Delete recurring event' : 'Delete event',
        }),
      );

      if (repeats) {
        $body.append(
          $('<div>').append(
            $('<label>', { class: 'flex flex-center' }).append(
              $('<input>', {
                type: 'radio',
                name: 'delete',
                value: 'occurrence',
                checked: true,
              }),
              'This event',
            ),
          ),
          $('<div>').append(
            $('<label>', { class: 'flex flex-center' }).append(
              $('<input>', { type: 'radio', name: 'delete', value: 'all' }),
              'All events',
            ),
          ),
        );
      } else {
        $body.append(
          $('<div>', { text: 'Are you sure you want to delete this event?' }),
        );
      }

      $body.append(
        $('<div>', { class: 'buttons right' }).append(
          $cancelBtn,
          this.$deleteSubmitBtn,
        ),
      );

      let $form = $('<form>', {
        id: 'confirmdeletemodal',
        class: 'modal fitted',
        method: 'post',
      }).append($body);

      this.$deleteActionRadios = $form.find('input');

      this.confirmDeleteModal = new Garnish.Modal($form);

      this.addListener($cancelBtn, 'click', () => {
        this.confirmDeleteModal.hide();
      });

      this.addListener($form, 'submit', jsEvent.data, 'deleteEvent');
    },

    deleteEvent(jsEvent) {
      jsEvent.preventDefault();

      this.$deleteSubmitBtn.addClass('loading');
      this.disable();

      let extra = jsEvent.data.event.extendedProps;

      let deleteOccurrence =
        this.$deleteActionRadios.filter(':checked').val() === 'occurrence';
      let action = deleteOccurrence
        ? 'eventful/default/delete'
        : 'elements/delete';
      let data = extra.element;
      if (deleteOccurrence) {
        data.date = extra.occurrenceDate;
      }

      this.$deleteSubmitBtn.removeClass('loading');

      Craft.sendActionRequest('POST', action, { data: data }).then(() => {
        this.enable();
        this.confirmDeleteModal.hide();
        this.hideAllHuds();
        this.calendar.refetchEvents();

        Craft.cp.displaySuccess('Event deleted.');
      });
    },

    copySubscribeUrl(event) {
      event.preventDefault();

      Craft.ui.createCopyTextPrompt({
        label: 'Secret iCal address',
        value: $(event.target).closest('a').attr('href'),
      });
    },
  });
})(jQuery);

/**
 * Returns the date at the current time (in its timezone), rounded up to the given number of minutes.
 */
function setTime(date, step) {
  let now = DateTime.now().setZone(date.zone);
  return date
    .set({ hour: now.hour, minute: 0 })
    .plus({ minutes: Math.ceil(now.minute / step) * step })
    .toJSDate();
}

/**
 * Returns the localized generic name of a timezone (e.g. "Pacific Time"), or its identifier if there isn't one.
 */
function getTimezoneName(timezone, locale) {
  return (
    new Intl.DateTimeFormat(locale, {
      timeZone: timezone,
      timeZoneName: 'longGeneric',
    })
      .formatToParts(new Date())
      .find((part) => part.type === 'timeZoneName')?.value ?? timezone
  );
}

/**
 * Returns the city of a timezone identifier (e.g. "Los Angeles" for "America/Los_Angeles").
 */
function getTimezoneCity(timezone) {
  return timezone.split('/').pop().replaceAll('_', ' ');
}

/**
 * Returns whether two timezones have the same offsets from a year ago to a year from now,
 * so they differ if one stops or starts observing DST (e.g. Vancouver from November 2026).
 */
function hasSameOffsets(timezone, otherTimezone) {
  let start = DateTime.now().startOf('month').minus({ years: 1 });
  for (let months = 0; months <= 24; months++) {
    let date = start.plus({ months });
    if (date.setZone(timezone).offset !== date.setZone(otherTimezone).offset) {
      return false;
    }
  }
  return true;
}

function upperFirst(val) {
  return String(val).charAt(0).toUpperCase() + String(val).slice(1);
}
