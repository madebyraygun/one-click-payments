<?php
namespace madebyraygun\oneclickpayments\records;

use Craft;
use craft\db\ActiveRecord;

class PaymentsFormStatus extends ActiveRecord {
    public static function tableName() {
        return '{{%oneclickpayments_status}}';
    }
}