<?php

use boundstate\eventful\db\OccurrenceQuery;
use boundstate\eventful\models\EventDate;
use boundstate\eventful\models\Occurrence;
use craft\base\ElementInterface;
use craft\db\Paginator;
use craft\elements\db\ElementQueryInterface;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

/**
 * Returns an occurrence query over the given events, without a database.
 *
 * @param  array<string, EventDate>  $events  event dates by element title
 */
function occurrenceQuery(array $events, array $config = []): OccurrenceQuery
{
    $test = test();

    $query = new class(['elementQuery' => $test->createStub(ElementQueryInterface::class), 'field' => 'eventDate', ...$config]) extends OccurrenceQuery
    {
        public array $events = [];

        public int $findCount = 0;

        protected function findEvents(DateTime $from, DateTime $to): array
        {
            $this->findCount++;

            return $this->events;
        }
    };

    foreach ($events as $title => $date) {
        $element = $test->createStub(ElementInterface::class);
        $element->method('__toString')->willReturn($title);
        $query->events[] = [$element, $date];
    }

    return $query;
}

function describeOccurrences(array $occurrences): array
{
    return array_map(
        fn (Occurrence $o): string => "$o->element {$o->getStart()->format('Y-m-d H:i')}",
        $occurrences,
    );
}

/**
 * Tuesdays 7-9PM UTC, forever, starting Tuesday, January 6, 2026.
 */
function trivia(): EventDate
{
    return new EventDate([
        'start' => '2026-01-06 19:00:00',
        'end' => '2026-01-06 21:00:00',
        'timezone' => 'UTC',
        'repeat' => true,
        'freq' => EventDate::FREQ_WEEKLY,
        'byDay' => ['TU'],
        'allowNeverEnding' => true,
    ]);
}

function oneOff(string $start): EventDate
{
    return new EventDate([
        'start' => $start,
        'end' => (new DateTime($start))->modify('+1 hour')->format('Y-m-d H:i:s'),
        'timezone' => 'UTC',
    ]);
}

$range = ['from' => '2026-03-01 00:00:00', 'to' => '2026-03-31 23:59:59'];

it('returns occurrences of all events in range, sorted by start', function () use ($range): void {
    $query = occurrenceQuery([
        'Trivia' => trivia(),
        'Concert' => oneOff('2026-03-12 20:00:00'),
        'Past' => oneOff('2026-02-12 20:00:00'),
    ], $range);

    expect(describeOccurrences($query->all()))->toBe([
        'Trivia 2026-03-03 19:00',
        'Trivia 2026-03-10 19:00',
        'Concert 2026-03-12 20:00',
        'Trivia 2026-03-17 19:00',
        'Trivia 2026-03-24 19:00',
        'Trivia 2026-03-31 19:00',
    ]);
});

it('includes the element and its event date', function () use ($range): void {
    $date = trivia();
    $occurrence = occurrenceQuery(['Trivia' => $date], $range)->one();

    expect($occurrence)->toBeInstanceOf(Occurrence::class)
        ->and((string) $occurrence->element)->toBe('Trivia')
        ->and($occurrence->eventDate)->toBe($date)
        ->and($occurrence->getEnd()->format('Y-m-d H:i'))->toBe('2026-03-03 21:00');
});

it('sorts by start descending', function () use ($range): void {
    $query = occurrenceQuery([
        'Trivia' => trivia(),
        'Concert' => oneOff('2026-03-12 20:00:00'),
    ], $range)->orderBy(['start' => SORT_DESC])->limit(2);

    expect(describeOccurrences($query->all()))->toBe([
        'Trivia 2026-03-31 19:00',
        'Trivia 2026-03-24 19:00',
    ]);
});

it('applies limit and offset, and counts all occurrences', function () use ($range): void {
    $query = occurrenceQuery(['Trivia' => trivia()], $range)->offset(1)->limit(2);

    expect(describeOccurrences($query->all()))->toBe([
        'Trivia 2026-03-10 19:00',
        'Trivia 2026-03-17 19:00',
    ])
        ->and($query->count())->toBe(5)
        ->and($query->exists())->toBeTrue();
});

it('finds occurrences of long-running events', function (): void {
    $query = occurrenceQuery(['Trivia' => trivia()], [
        'from' => '2046-03-01 00:00:00',
        'to' => '2046-03-07 23:59:59',
    ]);

    expect(describeOccurrences($query->all()))->toBe(['Trivia 2046-03-06 19:00']);
});

it('works with the paginator', function () use ($range): void {
    $query = occurrenceQuery(['Trivia' => trivia()], $range);

    $paginator = new Paginator($query, ['pageSize' => 2, 'currentPage' => 3]);

    expect($paginator->getTotalPages())->toBe(3)
        ->and(describeOccurrences($paginator->getPageResults()))->toBe(['Trivia 2026-03-31 19:00'])
        ->and($query->findCount)->toBe(1);
});

it('finds events again when the range changes', function () use ($range): void {
    $query = occurrenceQuery(['Trivia' => trivia()], $range);
    $query->count();
    $query->to('2026-03-15');

    expect($query->count())->toBe(2)
        ->and($query->findCount)->toBe(2);
});

it('requires a range', function (): void {
    occurrenceQuery(['Trivia' => trivia()], ['from' => 'now'])->all();
})->throws(InvalidConfigException::class);

it('cannot be filtered with where()', function () use ($range): void {
    occurrenceQuery([], $range)->where(['id' => 1]);
})->throws(NotSupportedException::class);

it('can only be ordered by start or end', function () use ($range): void {
    occurrenceQuery([], $range)->orderBy('title')->all();
})->throws(NotSupportedException::class);
