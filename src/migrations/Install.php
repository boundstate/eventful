<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace boundstate\eventful\migrations;

use boundstate\eventful\db\Table;
use craft\db\Migration;
use craft\db\Table as CraftTable;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable(Table::METADATA, [
            'id' => $this->integer()->notNull(),
            'iCalendarSequence' => $this->integer()->unsigned()->defaultValue(0)->notNull(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->addForeignKey(
            null,
            Table::METADATA,
            'id',
            CraftTable::ELEMENTS,
            'id',
            'CASCADE',
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropAllForeignKeysToTable(Table::METADATA);
        $this->dropTableIfExists(Table::METADATA);

        return true;
    }
}
