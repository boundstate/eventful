<?php

namespace boundstate\eventful\migrations;

use boundstate\eventful\db\Table;
use craft\db\Migration;
use craft\db\Table as CraftTable;

/**
 * Makes the metadata table's `id` a plain integer, since it's the ID of the element and was never generated.
 */
class m261018_000000_metadata_id_without_auto_increment extends Migration
{
    public function safeUp(): bool
    {
        if ($this->db->getIsPgsql()) {
            $sequence = $this->db->createCommand('SELECT pg_get_serial_sequence(:table, :column)', [
                ':table' => $this->db->getSchema()->getRawTableName(Table::METADATA),
                ':column' => 'id',
            ])->queryScalar();
        }

        // MySQL can't change a column used in a foreign key
        $this->dropForeignKeyIfExists(Table::METADATA, 'id');

        // also drops the default on PostgreSQL, which used the sequence
        $this->alterColumn(Table::METADATA, 'id', $this->integer()->notNull());

        $this->addForeignKey(
            null,
            Table::METADATA,
            'id',
            CraftTable::ELEMENTS,
            'id',
            'CASCADE',
        );

        if (! empty($sequence)) {
            $this->execute("DROP SEQUENCE $sequence");
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261018_000000_metadata_id_without_auto_increment cannot be reverted.\n";

        return false;
    }
}
