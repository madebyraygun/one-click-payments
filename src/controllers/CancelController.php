<?php

namespace madebyraygun\oneclickpayments\controllers;

use Craft;
use madebyraygun\oneclickpayments\helpers\FormieHelper;
use madebyraygun\oneclickpayments\helpers\FormConfig;
use madebyraygun\oneclickpayments\helpers\StripePaymentsHelper;
use madebyraygun\oneclickpayments\records\PaymentsFormStatus;
use craft\web\Controller;
use craft\helpers\UrlHelper;

class CancelController extends Controller {
    protected array|int|bool $allowAnonymous = ['index'];

    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
        $this->enableCsrfValidation = false;
    }

    public function actionIndex() {
      $params = $this->getParams();
      $status = PaymentsFormStatus::find()->where(['token' => $params->id])->one();
      if ($status && !empty($status->subscriptionId)) {
        $formHandle = FormieHelper::GetSubmission($status->submissionId)->form->handle;
        $formConfig = new FormConfig($formHandle);
        $stripe = StripePaymentsHelper::GetInstance($formConfig);
        $stripe->cancelSubscription($status->subscriptionId);
        FormieHelper::SetSubmissionStatus($status->submissionId, 'cancelled');
        $this->redirectTo($formConfig->cancelLanding);
      } else {
        $this->redirectTo('/');
      }
    }

    private function redirectTo($siteUrl) {
      $url = UrlHelper::siteUrl($siteUrl);
      Craft::$app
        ->getResponse()
        ->redirect($url)
        ->send();
    }

    private function getParams() {
      $req = Craft::$app->getRequest();
      return (object)[
        'id' => $req->getParam('id') ?? '',
      ];
    }
}