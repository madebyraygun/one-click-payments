<?php

namespace madebyraygun\oneclickpayments\controllers;

use Craft;
use madebyraygun\oneclickpayments\OneclickPayments;
use madebyraygun\oneclickpayments\helpers\FormConfig;
use madebyraygun\oneclickpayments\helpers\FormieHelper;
use madebyraygun\oneclickpayments\helpers\StripePaymentsHelper;
use madebyraygun\oneclickpayments\records\PaymentsFormStatus;
use craft\web\Controller;
use Stripe\Webhook;

class DefaultController extends Controller {
    protected $allowAnonymous = ['index'];

    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
        $this->enableCsrfValidation = false;
    }

    public function actionIndex() {
        $this->requirePostRequest();
        $payload = Craft::$app->getRequest()->getRawBody();
        $signature = Craft::$app->getRequest()->getHeaders()->get('Stripe-Signature');
        $secret = OneclickPayments::$plugin->getSettings()->stripeHookSecretKey;
        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
            Craft::info($event->type, __METHOD__);
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event);
                    break;
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event);
                    break;
                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event);
                    break;
            }
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }
    }

    private function handleCheckoutSessionCompleted($event) {
        $session = $event->data->object;
        $linkId = $session->payment_link;
        $intentId = $session->payment_intent;
        $subscription = $session->subscription;
        $status = PaymentsFormStatus::find()->where(['linkId' => $linkId])->one();
        if ($status) {
            FormieHelper::SetSubmissionStatus($status->submissionId, 'processing');
            $status->setAttribute('intentId', $intentId);
            $status->setAttribute('subscriptionId', $subscription);
            $status->save(false);
            if ($session->payment_status == "paid") {
                $formHandle = FormieHelper::GetSubmission($status->submissionId)->form->handle;
                $formConfig = new FormConfig($formHandle);
                FormieHelper::SetSubmissionStatus($status->submissionId, 'confirmed');
                $stripe = StripePaymentsHelper::GetInstance($formConfig);
                $stripe->deactivatePaymentLink($linkId);
            }
        }
    }

    private function handlePaymentIntentSucceeded($event) {
        $session = $event->data->object;
        $intentId = $session->id;
        $status = PaymentsFormStatus::find()->where(['intentId' => $intentId])->one();
        if ($status) {
            FormieHelper::SetSubmissionStatus($status->submissionId, 'confirmed');
        }
    }
}