<?php

namespace madebyraygun\oneclickpayments\helpers;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use Craft;

class FormieHelper {
  public static function SetSubmissionStatus($submissionId, $status) {
    $submission = self::GetSubmission($submissionId);
    $status = Formie::$plugin->getStatuses()->getStatusByHandle($status);
    if (!empty($status)) {
      $submission->setStatus($status);
      Craft::$app->getElements()->saveElement($submission, false);
      Formie::$plugin->getSubmissions()->sendNotifications($submission);
      Craft::$app->getQueue()->run();
    }
  }

  public static function GetSubmission($id) {
    return Submission::find()
      ->id($id)
      ->one();
  }

  public static function SetSubmissionField($submission, $fieldHandle, $value) {
    $field = Formie::$plugin->getFields()->getFieldByHandle($fieldHandle);
    if (!empty($field)) {
      $submission->setFieldValue($field, $value);
      Craft::$app->getElements()->saveElement($submission, false);
    }
  }
}
