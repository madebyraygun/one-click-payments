<?php
/**
 * One-click Payments plugin for Craft CMS 3.x
 *
 * Payments plugin for single-click checkouts using Formie and Stripe
 *
 * @link      https://github.com/madebyraygun
 * @copyright Copyright (c) 2022 MadeByRaygun
 */

namespace madebyraygun\oneclickpayments\models;

use madebyraygun\oneclickpayments\OneclickPayments;

use Craft;
use craft\base\Model;

/**
 * OneclickPayments Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    MadeByRaygun
 * @package   OneclickPayments
 * @since     1.0.0
 */
class Settings extends Model {
    // Public Properties
    // =========================================================================

    /**
     * Some field model attribute
     *
     * @var string
     */
    public $stripeSecretKey = '';
    public $stripeHookSecretKey = '';
    public $forms = [];

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules() {
        return [
            ['stripeSecretKey', 'string'],
            ['stripeSecretKey', 'default', 'value' => ''],
            ['stripePublishableKey', 'string'],
            ['stripePublishableKey', 'default', 'value' => ''],
            ['forms', 'array'],
            ['forms', 'default', 'value' => []],
        ];
    }
}
