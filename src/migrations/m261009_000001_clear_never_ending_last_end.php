<?php

namespace boundstate\eventful\migrations;

use boundstate\eventful\fields\EventDate;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;

/**
 * Recalculates the denormalized `lastEnd` of repeating event date values.
 *
 * Events that repeat forever were mistaken for ending (since `count` keeps its default when unused),
 * so they were saved with the end of the last occurrence Recurr generates instead of no last end date.
 */
class m261009_000001_clear_never_ending_last_end extends Migration
{
    public function safeUp(): bool
    {
        /** @var array<string, EventDate> $fields */
        $fields = [];

        foreach (Craft::$app->getFields()->getAllLayouts() as $layout) {
            foreach ($layout->getCustomFields() as $field) {
                if ($field instanceof EventDate) {
                    $fields[$field->layoutElement->uid] = $field;
                }
            }
        }

        if ($fields === []) {
            return true;
        }

        $qb = $this->db->getQueryBuilder();
        $query = (new Query)
            ->select(['id', 'content'])
            ->from(Table::ELEMENTS_SITES)
            ->where([
                'or',
                ...array_map(fn (string $uid): string => $qb->jsonExtract('content', [$uid, 'freq']).' IS NOT NULL', array_keys($fields)),
            ]);

        foreach (Db::each($query) as $row) {
            $content = Json::decode($row['content']);
            $changed = false;

            foreach ($fields as $uid => $field) {
                if (! is_array($content[$uid] ?? null) || empty($content[$uid]['freq'])) {
                    continue;
                }

                $lastEnd = $this->lastEnd($field, $content[$uid]);
                if ($lastEnd !== ($content[$uid]['lastEnd'] ?? null)) {
                    $content[$uid]['lastEnd'] = $lastEnd;
                    $changed = true;
                }
            }

            if ($changed) {
                Db::update(
                    Table::ELEMENTS_SITES,
                    ['content' => $content],
                    ['id' => $row['id']],
                    updateTimestamp: false,
                    db: $this->db,
                );
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261009_000001_clear_never_ending_last_end cannot be reverted.\n";

        return false;
    }

    /**
     * Returns the `lastEnd` a stored value should have, as it would be serialized when saved.
     */
    private function lastEnd(EventDate $field, array $value): ?string
    {
        return $field->serializeValue($field->normalizeValue($value, null), null)['lastEnd'] ?? null;
    }
}
