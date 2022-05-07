<?php
/**
 * One-click Payments plugin for Craft CMS 3.x
 *
 * Payments plugin for single-click checkouts using Formie and Stripe
 *
 * @link      https://github.com/madebyraygun
 * @copyright Copyright (c) 2022 MadeByRaygun
 */

namespace madebyraygun\oneclickpayments;

use Craft;
use madebyraygun\oneclickpayments\models\Settings;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\events\SubmissionEvent;
use verbb\formie\elements\Submission;
use madebyraygun\oneclickpayments\helpers\StripePaymentsHelper;
use madebyraygun\oneclickpayments\helpers\FormConfig;
use madebyraygun\oneclickpayments\records\PaymentsFormStatus;
use craft\helpers\UrlHelper;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    MadeByRaygun
 * @package   OneclickPayments
 * @since     1.0.0
 *
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class OneclickPayments extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * OneclickPayments::$plugin
     *
     * @var OneclickPayments
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = false;

    // Public Methods
    // =========================================================================
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['webhooks/stripe-payments'] = 'one-click-payments/default';
                $event->rules['webhooks/cancel-subscription'] = 'one-click-payments/cancel';
            }
        );

        $this->hookFormieEvents();
    }

    protected function createSettingsModel() {
        return new Settings();
    }

    private function hookFormieEvents() {
        Event::on(Submission::class, Submission::EVENT_AFTER_SAVE, function(Event $event) {
            $submission = $event->sender;
            $formConfig = new FormConfig($submission->getForm()->handle);
            if ($formConfig->isValid() && $event->isNew) {
                $token = Craft::$app->security->generateRandomString(32);
                $values = $this->mapValues($submission);
                $link = $this->getPaymentLink($formConfig, $values);
                $status = new PaymentsFormStatus([
                    'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
                    'submissionId' => $submission->id,
                    'linkId' => $link->id,
                    'linkUrl' => $link->url,
                    'token' => $token,
                ]);
                $status->save(false);
                if ($values->type == 'recurring') {
                    $this->generateCancelLink($submission, $token);
                }
            }
        });

        Event::on(SubmissionsController::class, SubmissionsController::EVENT_AFTER_SUBMISSION_REQUEST, function(SubmissionEvent $event) {
            $submission = $event->submission;
            $success = $event->success;
            $formConfig = new FormConfig($submission->getForm()->handle);
            if ($formConfig->isValid() && $success) {
                $status = PaymentsFormStatus::find()->where(['submissionId' => $submission->id])->one();
                if (!empty($status->linkUrl)) {
                    Craft::$app
                    ->getResponse()
                    ->redirect($status->linkUrl)
                    ->send();
                }
            }
        });
    }

    private function generateCancelLink($submission, $token) {
        $cancelUrl = UrlHelper::siteUrl('/webhooks/cancel-subscription', [
            'id' => $token,
        ]);
        $submission->setFieldValue('cancelSubscriptionUrl', $cancelUrl);
        Craft::$app->getElements()->saveElement($submission, false);
    }

    private function getPaymentLink($formConfig, $values) {
        $callbackUrl = UrlHelper::siteUrl($formConfig->thankyouLanding);
        $stripe = StripePaymentsHelper::GetInstance($formConfig);
        return $stripe->createPaymentLink($values->type, $values->amount, $callbackUrl);
    }

    private function getFieldValue($submission, $field) {
        $value = $submission->getFieldValue($field);
        if (empty($value)) {
            return $submission
                ->getForm()
                ->getFieldByHandle('paymentType')
                ->defaultValue;
        }
        return $value->value ?? $value;
    }

    private function mapValues($submission) {
        $total = $this->getFieldValue($submission, 'paymentTotal');
        $type = $this->getFieldValue($submission, 'paymentType');
        return (object)[
            'id' => $submission->uid,
            'type' => $type,
            'amount' => floatval($total) * 100 // as cents
        ];
    }
}
