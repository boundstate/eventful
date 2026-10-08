<?php

namespace boundstate\eventful\models;

use boundstate\eventful\enums\IcsStatus;
use boundstate\eventful\helpers\DateHelper;
use boundstate\eventful\helpers\UrlHelper;
use craft\base\Model;
use craft\helpers\MailerHelper;
use DateTime;
use Recurr\Rule;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

class IcsEvent extends Model
{
    public static ?int $now = null;

    private readonly VEvent $_doc;

    private ?DateTime $_start = null;

    private ?DateTime $_end = null;

    public function __construct(VCalendar $doc, array $config = [])
    {
        /** @var VEvent $event */
        $event = $doc->add('VEVENT', [
            'DTSTAMP' => gmdate('Ymd\\THis\\Z', self::$now),
        ]);

        $this->_doc = $event;

        parent::__construct($config);
    }

    public function setUid(string $uid): static
    {
        $this->_doc->UID = sprintf('%s@%s', $uid, UrlHelper::hostname());

        return $this;
    }

    public function setSequence(int $sequence): static
    {
        $this->_doc->SEQUENCE = $sequence;

        return $this;
    }

    public function setStart(DateTime $start): static
    {
        $this->_doc->DTSTART = $start;
        $this->_start = $start;

        return $this;
    }

    public function getStart(): ?DateTime
    {
        return $this->_start;
    }

    public function setEnd(DateTime $end): static
    {
        $this->_doc->DTEND = $end;
        $this->_end = $end;

        return $this;
    }

    public function getEnd(): ?DateTime
    {
        return $this->_end;
    }

    public function setStatus(?IcsStatus $status): static
    {
        if (! $status instanceof IcsStatus) {
            $this->_doc->remove('STATUS');
        } else {
            $this->_doc->STATUS = $status->value;
        }

        return $this;
    }

    public function setSummary(string $summary): static
    {
        $this->_doc->SUMMARY = $summary;

        return $this;
    }

    public function setDescription(string $description): static
    {
        $this->_doc->DESCRIPTION = $description;

        return $this;
    }

    public function setLocation(string $location): static
    {
        $this->_doc->LOCATION = $location;

        return $this;
    }

    /**
     * Sets the repeating rule for this event.
     * NOTE: the rule end date will be ignored; to set the event end date use {@link setEnd()}.
     */
    public function setRule(Rule $rule): static
    {
        // DTEND, EXDATE, & RDATE should not be part of the RRULE
        $this->_doc->RRULE = (clone $rule)
            ->setEndDate(null)
            ->setExDates([])
            ->setRDates([])
            ->getString(Rule::TZ_FIXED);

        // add EXDATE & RDATE dates directly to VEVENT, and include time,
        // otherwise they won't be parsed correctly by calendars

        foreach ($rule->getExDates() as $exDate) {
            $this->_doc->add(
                'EXDATE',
                DateHelper::setTime($exDate->date, $rule->getStartDate()),
            );
        }

        foreach ($rule->getRDates() as $inDate) {
            $this->_doc->add(
                'RDATE',
                DateHelper::setTime($inDate->date, $this->getStart()),
            );
        }

        return $this;
    }

    public function setOrganizers(mixed $users): static
    {
        $this->_doc->remove('ORGANIZER');
        $this->addOrganizers($users);

        return $this;
    }

    public function addOrganizers(mixed $users): static
    {
        foreach ($this->normalizeEmails($users) as $email => $name) {
            $this->_doc->add('ORGANIZER', "MAILTO:{$email}", array_filter([
                'CN' => $name,
            ]));
        }

        return $this;
    }

    public function setAttendees(mixed $users, ?bool $accepted = null): static
    {
        $this->_doc->remove('ATTENDEE');
        $this->addAttendees($users, $accepted);

        return $this;
    }

    public function addAttendees(mixed $users, ?bool $accepted = null): static
    {
        foreach ($this->normalizeEmails($users) as $email => $name) {
            $props = array_filter([
                'CN' => $name,
                'ROLE' => 'REQ-PARTICIPANT',
            ]);
            if ($accepted) {
                $props['PARTSTAT'] = 'ACCEPTED';
                $props['RSVP'] = 'TRUE';
            }
            $this->_doc->add('ATTENDEE', "MAILTO:{$email}", $props);
        }

        return $this;
    }

    /**
     * Normalizes emails to `email => name` pairs,
     * since {@link MailerHelper::normalizeEmails()} uses numeric keys for emails without names.
     *
     * @return array<string, ?string>
     */
    private function normalizeEmails(mixed $users): array
    {
        $normalized = [];

        foreach (MailerHelper::normalizeEmails($users) as $key => $value) {
            if (is_int($key)) {
                $normalized[$value] = null;
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
