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
 * Stores `allDay` in the values of always all day event date fields.
 *
 * Event date values didn't store whether they're all day, since it came from the field setting.
 * Now that a field can make all day optional, values without `allDay` are treated as not all day,
 * so this keeps existing all day events all day if their field is switched to optional.
 */
class m261009_000000_store_all_day extends Migration
{
    public function safeUp(): bool
    {
        $layoutElementUids = [];

        foreach (Craft::$app->getFields()->getAllLayouts() as $layout) {
            foreach ($layout->getCustomFields() as $field) {
                if ($field instanceof EventDate && $field->allDayMode === EventDate::ALL_DAY_ALWAYS) {
                    $layoutElementUids[] = $field->layoutElement->uid;
                }
            }
        }

        if ($layoutElementUids === []) {
            return true;
        }

        $qb = $this->db->getQueryBuilder();
        $query = (new Query)
            ->select(['id', 'content'])
            ->from(Table::ELEMENTS_SITES)
            ->where([
                'or',
                ...array_map(fn (string $uid): string => $qb->jsonExtract('content', [$uid]).' IS NOT NULL', $layoutElementUids),
            ]);

        foreach (Db::each($query) as $row) {
            $content = Json::decode($row['content']);

            foreach ($layoutElementUids as $uid) {
                if (isset($content[$uid])) {
                    $content[$uid]['allDay'] = true;
                }
            }

            Db::update(
                Table::ELEMENTS_SITES,
                ['content' => $content],
                ['id' => $row['id']],
                updateTimestamp: false,
                db: $this->db,
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261009_000000_store_all_day cannot be reverted.\n";

        return false;
    }
}
