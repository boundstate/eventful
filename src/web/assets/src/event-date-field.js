import { DateTime } from 'luxon';

import './event-date-field.css';

Craft.Eventful ??= {};

(($) => {
  Craft.Eventful.Input = Garnish.Base.extend({
    inputId: null,
    inputName: null,
    locale: null,
    previousStartDate: null,
    previousStartTime: null,
    previousEndTime: null,
    defaultTimezone: null,

    $container: null,
    $startDateInput: null,
    $startTimeInput: null,
    $allDayCheckbox: null,
    $endTimeInput: null,
    $endDateInput: null,
    $untilDateInput: null,
    $countInput: null,
    $intervalInput: null,
    $frequencyInput: null,
    $weekdayCheckboxes: null,
    $monthRepeatSelect: null,
    $actionBtn: null,
    $actionMenu: null,
    $chips: null,
    $timezoneSelectize: null,

    init(inputId, inputName, locale) {
      this.inputId = inputId;
      this.inputName = inputName;
      this.locale = locale;
      // the user's preferred timezone (or the system timezone), like the rest of the control panel
      this.defaultTimezone = Craft.timezone;

      this.$container = $('#' + Craft.namespaceId(inputId));
      this.$startDateInput = $('#' + Craft.namespaceId('start-date', inputId));
      this.$startTimeInput = $('#' + Craft.namespaceId('start-time', inputId));
      this.$endTimeInput = $('#' + Craft.namespaceId('end-time', inputId));
      this.$endDateInput = $('#' + Craft.namespaceId('end-date', inputId));
      this.$allDayCheckbox = $('#' + Craft.namespaceId('all-day', inputId));
      this.$timezoneSelectize = $(
        '#' + Craft.namespaceId('timezone', inputId),
      ).data('selectize');
      this.configureTimezoneField();
      this.$untilDateInput = $('#' + Craft.namespaceId('until-date', inputId));
      this.$countInput = $('#' + Craft.namespaceId('count', inputId));
      this.$intervalInput = $('#' + Craft.namespaceId('interval', inputId));
      this.$frequencyInput = $('#' + Craft.namespaceId('freq', inputId));
      this.$weekdayCheckboxes = $(
        '#' + Craft.namespaceId('weekdays', inputId) + ' :checkbox',
      );
      this.$monthRepeatSelect = $(
        '#' + Craft.namespaceId('month-repeat', inputId),
      );
      this.$actionBtn = $('#' + Craft.namespaceId('action-btn', inputId));
      this.$actionMenu = $('#' + Craft.namespaceId('action-menu', inputId));
      this.$chips = $('#' + Craft.namespaceId(inputId) + ' .chips');

      this.addListener(this.$frequencyInput, 'change', 'updateRepeatValue');
      this.addListener(this.$weekdayCheckboxes, 'change', 'updateRepeatValue');
      this.addListener(this.$monthRepeatSelect, 'change', 'updateRepeatValue');
      this.addListener(this.$countInput, 'change', 'updateCountLabel');
      this.addListener(this.$intervalInput, 'change', 'updateFrequencyLabels');
      this.addListener(this.$startDateInput, 'change', 'onStartDateChange');
      this.addListener(this.$endDateInput, 'change', 'onEndDateChange');
      this.addListener(this.$allDayCheckbox, 'change', 'onAllDayChange');
      this.addListener(this.$chips.find('.add input'), 'change', 'addDate');
      this.addListener(this.$chips.find('.delete'), 'click', 'removeDate');

      this.addListener(this.$startTimeInput, 'change', 'onStartTimeChange');
      this.addListener(this.$endTimeInput, 'change', 'onEndTimeChange');
      this.addListener(
        this.$timezoneSelectize.$input,
        'change',
        'updateTimezone',
      );

      this.$actionMenu.find('.menu-item').each((index, element) => {
        let $menuItem = $(element);
        let $container = $('#' + $menuItem.data('target'));

        $menuItem
          .fieldtoggle()
          .data('fieldtoggle')
          .on('toggleChange', () => {
            $menuItem.closest('li').remove();

            this.$actionMenu.data('disclosureMenu').hide();

            if (!this.$actionMenu.find('li').length) {
              this.$actionBtn.remove();
              this.$actionMenu.remove();
            }

            setTimeout(() => {
              $container.find('input').focus();
            }, 200);
          });
      });

      // the times of all day events are only placeholders, so clear them
      // to be filled like a new event's if "All day" is unchecked
      if (this.$container.hasClass('all-day')) {
        this.$startTimeInput.val('');
        this.$endTimeInput.val('');
      }

      this.$startDateInput.trigger('change');

      // only listen after the initial change,
      // since the timezone options are already rendered for the initial start date
      this.addListener(this.$startDateInput, 'change', 'updateTimezoneOptions');

      this.$startTimeInput.trigger('change');

      if (!this.$timezoneSelectize.getValue() && this.defaultTimezone) {
        this.$timezoneSelectize.setValue(this.defaultTimezone);
      } else {
        this.$timezoneSelectize.$input.trigger('change');
      }

      this.$container.removeClass('loading');
    },

    onAllDayChange() {
      this.$container.toggleClass(
        'all-day',
        this.$allDayCheckbox.prop('checked'),
      );
    },

    configureTimezoneField() {
      // include the city when rendering the selected item, since
      // timezones can share an offset and abbreviation (e.g. Vancouver & Los Angeles in summer)
      this.$timezoneSelectize.settings.render.item = ({ text = '', hint }) => {
        let label = hint ? `${text} - ${hint}` : text;
        return `<div class="item" title="${Craft.escapeHtml(label)}"><span>${Craft.escapeHtml(label)}</span></div>`;
      };
      this.$timezoneSelectize.clearCache('item');

      // re-render selected option
      let value = this.$timezoneSelectize.getValue();
      if (value) {
        this.$timezoneSelectize.updateOption(
          value,
          this.$timezoneSelectize.options[value],
        );
      }
    },

    /**
     * Returns the next half hour in the event's timezone, rather than the browser's,
     * as a time for the time inputs (which use the browser's).
     */
    getNextHalfHour() {
      let now = DateTime.now().setZone(
        this.$timezoneSelectize.getValue() || this.defaultTimezone,
      );
      return DateTime.fromObject({ hour: now.hour })
        .plus({ minutes: Math.ceil(now.minute / 30) * 30 })
        .toJSDate();
    },

    updateTimezone() {
      let timezone = this.$timezoneSelectize.getValue() || this.defaultTimezone;
      if (timezone) {
        this.$container.find('input[name$="[timezone]"]').val(timezone);
      }
    },

    updateCountLabel() {
      let count = parseInt(this.$countInput.val());
      this.$countInput
        .closest('.field')
        .find('.label')
        .text(count === 1 ? 'occurrence' : 'occurrences');
    },

    updateFrequencyLabels() {
      let interval = parseInt(this.$intervalInput.val());

      this.$frequencyInput.find('option').each((index, element) => {
        let label =
          $(element).text().replace(/s$/, '') + (interval !== 1 ? 's' : '');
        $(element).text(label);
      });
    },

    updateRepeatValue() {
      let byDayName = Craft.namespaceInputName('byDay[]', this.inputName);
      let byMonthDayName = Craft.namespaceInputName(
        'byMonthDay[]',
        this.inputName,
      );

      this.$container.find('input[name="' + byDayName + '"]').remove();
      this.$container.find('input[name="' + byMonthDayName + '"]').remove();

      let inputs = [];

      switch (this.$frequencyInput.val()) {
        case 'WEEKLY':
          this.getSelectedWeekdays().forEach((weekday) => {
            inputs.push({ name: byDayName, value: weekday });
          });
          break;
        case 'MONTHLY':
          let value = this.$monthRepeatSelect.val();
          if (/^\d+$/.test(value)) {
            // month number
            inputs.push({ name: byMonthDayName, value: value });
          } else {
            // nth weekday of month (e.g. 2MO or -1FR)
            inputs.push({ name: byDayName, value: value });
          }
          break;
      }

      this.$container.append(
        inputs.map((input) =>
          $('<input>', {
            name: input.name,
            type: 'hidden',
            value: input.value,
          }),
        ),
      );
    },

    onStartDateChange() {
      let date = getDateInputVal(this.$startDateInput);

      if (date) {
        // always fill the start time once there's a date,
        // so what's autosaved in a draft is what the user sees
        if (!getTimeInputVal(this.$startTimeInput)) {
          setTimeInputVal(this.$startTimeInput, this.getNextHalfHour());
          this.onStartTimeChange();
        }

        this.$untilDateInput.datepicker('option', 'minDate', date);
        this.updateEndDate(date);

        if (
          (this.previousStartDate &&
            date.getTime() !== this.previousStartDate.getTime()) ||
          !this.isAnyWeekdaySelected()
        ) {
          this.selectWeekday(date.getDay());
        }

        this.updateMonthRepeatOptions(date);
      }

      this.previousStartDate = date;
    },

    async updateTimezoneOptions() {
      // the offsets and abbreviations in the labels change during the year
      let date = getDateInputVal(this.$startDateInput);
      if (!date) {
        return;
      }

      let response = await Craft.sendActionRequest(
        'GET',
        'eventful/default/timezone-options',
        { params: { date: formatISODate(date) } },
      );

      // ignore a response for a date that's since been changed again
      if (getDateInputVal(this.$startDateInput)?.getTime() !== date.getTime()) {
        return;
      }

      // update the options in place, so the selected one keeps its value,
      // and reorder them since they're sorted by offset (keeping the empty one first)
      response.data.forEach((option, index) => {
        this.$timezoneSelectize.updateOption(option.value, {
          ...option,
          $order: index + 2,
        });
      });
    },

    updateEndDate(startDate) {
      if (this.$endDateInput.prop('disabled')) {
        return;
      }

      this.$endDateInput.datepicker('option', 'minDate', startDate);

      // keep the same number of days when the start date changes
      let endDate = getDateInputVal(this.$endDateInput);
      if (endDate && this.previousStartDate) {
        endDate = addDays(
          endDate,
          getDaysBetween(this.previousStartDate, startDate),
        );
      }
      if (!endDate) {
        endDate = startDate;
      }
      setDateInputVal(this.$endDateInput, endDate);
    },

    onEndDateChange() {
      let startDate = getDateInputVal(this.$startDateInput);

      // don't allow the end date to be cleared while there's a start date
      if (startDate && !getDateInputVal(this.$endDateInput)) {
        setDateInputVal(this.$endDateInput, startDate);
      }
    },

    onStartTimeChange() {
      let startTime = getTimeInputVal(this.$startTimeInput);

      // don't allow the start time to be cleared while there's a date
      if (!startTime && getDateInputVal(this.$startDateInput)) {
        startTime = this.previousStartTime || this.getNextHalfHour();
        setTimeInputVal(this.$startTimeInput, startTime);
      }

      this.$endTimeInput.timepicker('option', {
        minTime: startTime,
        showDuration: true,
      });

      if (startTime) {
        let endTime = getTimeInputVal(this.$endTimeInput);
        if (!endTime) {
          endTime = addMinutes(startTime, 60);
        } else if (this.previousStartTime) {
          let changeInMs =
            startTime.getTime() - this.previousStartTime.getTime();
          endTime = addMinutes(endTime, changeInMs / 60000);
        }
        setTimeInputVal(this.$endTimeInput, endTime);
        this.previousEndTime = endTime;
      }

      this.previousStartTime = startTime;
    },

    onEndTimeChange() {
      let endTime = getTimeInputVal(this.$endTimeInput);

      // don't allow the end time to be cleared while there's a start time
      if (!endTime) {
        let startTime = getTimeInputVal(this.$startTimeInput);
        if (startTime) {
          endTime = this.previousEndTime || addMinutes(startTime, 60);
          setTimeInputVal(this.$endTimeInput, endTime);
        }
      }

      this.previousEndTime = endTime;
    },

    isAnyWeekdaySelected() {
      return this.$weekdayCheckboxes.filter(':checked').length > 0;
    },

    selectWeekday(dayOfWeek) {
      this.$weekdayCheckboxes.each((index, element) => {
        $(element).prop('checked', index === dayOfWeek);
      });
      // fire event on first checkbox just to trigger change handler
      $(this.$weekdayCheckboxes[0]).change();
    },

    getSelectedWeekdays() {
      return this.$weekdayCheckboxes
        .filter(':checked')
        .map((index, element) => $(element).val())
        .get();
    },

    updateMonthRepeatOptions(date) {
      date = DateTime.fromJSDate(date);
      let dayOfMonth = date.day;
      let weekDayName = date.toLocaleString({ weekday: 'long' });
      // RRULE weekdays (e.g. "MO"), rather than the localized name
      let weekDay = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'][
        date.weekday - 1
      ];
      let weekOfMonth = Math.ceil(dayOfMonth / 7);
      let isLastWeekOfMonth = dayOfMonth + 7 > date.daysInMonth;

      let currentValue = this.$monthRepeatSelect.val();

      let options = [
        {
          value: dayOfMonth,
          label: 'on day ' + dayOfMonth,
        },
        {
          value: weekOfMonth + weekDay,
          label:
            'on the ' +
            weekOfMonth +
            getOrdinal(weekOfMonth) +
            ' ' +
            weekDayName,
        },
      ];

      if (isLastWeekOfMonth) {
        options.push({
          value: '-1' + weekDay,
          label: 'on the last ' + weekDayName,
        });
      }

      this.$monthRepeatSelect
        .html('')
        .append(
          options.map((option) =>
            $('<option>', {
              value: option.value,
              text: option.label,
            }),
          ),
        )
        .closest('.field')
        .removeClass('hidden');

      if (options.some((option) => option.value === currentValue)) {
        this.$monthRepeatSelect.val(currentValue);
      } else {
        this.$monthRepeatSelect.val(options[0].value);
      }
    },

    addDate(event) {
      let date = getDateInputVal($(event.target));
      if (date) {
        let $chips = $(event.target).closest('.chips');
        let text =
          date.toLocaleDateString(this.locale || undefined, {
            weekday: 'short',
          }) +
          ' ' +
          Craft.formatDate(date);
        let inputName = Craft.namespaceInputName(
          $chips.data('inputName'),
          this.inputName,
        );
        let inputValue = formatISODate(date);

        let $deleteBtn = $('<button>', {
          type: 'button',
          class: 'btn action-btn delete icon',
        });

        this.addListener($deleteBtn, 'click', 'removeDate');

        $chips.find('li:last').before(
          $('<li>').append(
            $('<div>', { class: 'chip small removable' }).append(
              $('<div>', { class: 'chip-content' }).append(
                $('<input>', {
                  type: 'hidden',
                  name: inputName,
                  value: inputValue,
                }),
                $('<div>', { class: 'label', text: text }),
                $('<div>', { class: 'chip-actions' }).append($deleteBtn),
              ),
            ),
          ),
        );

        $(event.target).val(null);
        $(event.target).blur();
      }
    },

    removeDate(event) {
      $(event.target).closest('li').remove();
    },
  });
})(jQuery);

function addDays(date, days) {
  return DateTime.fromJSDate(date).plus({ days }).toJSDate();
}

function getDaysBetween(startDate, endDate) {
  // round, since a day isn't 24 hours when the clocks change
  // (and starts after midnight if they change at midnight)
  return Math.round(
    DateTime.fromJSDate(endDate)
      .startOf('day')
      .diff(DateTime.fromJSDate(startDate).startOf('day'), 'days').days,
  );
}

function addMinutes(date, minutes) {
  return DateTime.fromJSDate(date).plus({ minutes }).toJSDate();
}

function formatISODate(date) {
  return DateTime.fromJSDate(date).toISODate();
}

function getOrdinal(i) {
  let j = i % 10,
    k = i % 100;
  if (j === 1 && k !== 11) {
    return 'st';
  } else if (j === 2 && k !== 12) {
    return 'nd';
  } else if (j === 3 && k !== 13) {
    return 'rd';
  } else {
    return 'th';
  }
}

function getDateInputVal($input) {
  if ($input.hasClass('hasDatepicker')) {
    return $input.datepicker('getDate');
  } else {
    let val = $input.val();
    return val ? DateTime.fromISO(val).toJSDate() : null;
  }
}

function setDateInputVal($input, date) {
  if ($input.hasClass('hasDatepicker')) {
    $input.datepicker('setDate', date);
  } else {
    $input.val(formatISODate(date));
  }
}

function getTimeInputVal($input) {
  if ($input.hasClass('ui-timepicker-input')) {
    return $input.timepicker('getTime');
  } else {
    let val = $input.val();
    if (!val) {
      return null;
    } else {
      return DateTime.fromISO(val).toJSDate();
    }
  }
}

function setTimeInputVal($input, date) {
  if ($input.hasClass('ui-timepicker-input')) {
    $input.timepicker('setTime', date);
  } else {
    $input.val(DateTime.fromJSDate(date).toFormat('HH:mm'));
  }
}
