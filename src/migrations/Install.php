<?php
namespace madebyraygun\oneclickpayments\migrations;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

class Install extends Migration {
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    public function safeUp() {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }
        return true;
    }

    public function safeDown() {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();
        return true;
    }

    protected function createTables() {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%oneclickpayments_status}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%oneclickpayments_status}}',
                [
                    'id'          => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid'         => $this->uid(),
                    'siteId'      => $this->integer()->notNull(),
                    'submissionId'=> $this->integer()->notNull(),
                    'token'       => $this->string(255),
                    'intentId'    => $this->string(255),
                    'linkUrl'     => $this->string(255),
                    'linkId'      => $this->string(255),
                    'subscriptionId' => $this->string(255),
                ]
            );
        }
        return $tablesCreated;
    }

    protected function createIndexes() {
        $this->createIndex(null, '{{%oneclickpayments_status}}', 'formId', true);
    }

    protected function addForeignKeys() {
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%oneclickpayments_status}}', 'siteId'),
            '{{%oneclickpayments_status}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    protected function insertDefaultData() {
    }

    protected function removeTables() {
        $this->dropTableIfExists('{{%oneclickpayments_status}}');
    }
}