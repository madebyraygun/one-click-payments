<?php

namespace madebyraygun\oneclickpayments\helpers;
use madebyraygun\oneclickpayments\OneclickPayments;
use Craft;

class FormConfig {
  private $handle;
  private $form;

  public function __construct($handle) {
    $this->handle = $handle;
    $this->form = $this->getForm($handle);
  }

  private function getForm($handle) {
    $settings = OneclickPayments::$plugin->getSettings();
    if (array_key_exists($handle, $settings->forms)) {
      return (object)$settings->forms[$handle];
    }
  }

  public function __get($property) {
    if ($this->isValid() && property_exists($this->form, $property)) {
      return $this->form->$property;
    }
  }

  public function isValid() {
    return $this->form !== null;
  }
}
