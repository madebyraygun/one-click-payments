<?php

namespace madebyraygun\oneclickpayments\helpers;
use Craft;
use Stripe\StripeClient;
use madebyraygun\oneclickpayments\OneclickPayments;

class StripePaymentsHelper {

  private $currency;
  private $stripe;

  public function __construct($secretKey, $formConfig) {
    $this->stripe = new StripeClient($secretKey);
    $this->formConfig = $formConfig;
    $this->currency = 'usd';
  }

  public static function GetInstance($formConfig) {
    $key = OneclickPayments::$plugin->getSettings()->stripeSecretKey;
    return new StripePaymentsHelper($key, $formConfig);
  }

  /**
   * @param string $type The price type "one_time" or "recurring"
   * @param int $amount The price amount in cents
   */
  public function getPrice($type, $amount) {
    $stripe = $this->stripe;
    $productId = $this->getProductId($type);
    $results = $stripe->prices->search([
      'query' => "
        type:'{$type}' AND
        active:'true' AND
        currency:'{$this->currency}' AND
        product:'{$productId}'
      "
    ]);
    if (!empty($results->data)) {
      foreach ($results->data as $price) {
        if ($price->unit_amount == $amount) {
          return $price;
        }
      }
    }
    return null;
  }

  public function cancelSubscription($subscriptionId) {
    $stripe = $this->stripe;
    $stripe->subscriptions->cancel($subscriptionId);
  }

  /**
   * @param string $type The price type "one_time" or "recurring"
   * @param int $amount The price amount in cents
   */
  public function createPrice($type, $amount) {
    $productId = $this->getProductId($type);
    $stripe = $this->stripe;
    $price = [
      'unit_amount' => $amount,
      'currency' => $this->currency,
      'product' => $productId,
    ];
    if ($type == 'recurring') {
      $price['recurring'] = ['interval' => 'month'];
    }
    return $stripe->prices->create($price);
  }

  /**
   * Deactivates a Payment Link
   * @param string $paymentId The payment link ID
   */
  public function deactivatePaymentLink($paymentId) {
    $this->stripe
      ->paymentLinks
      ->update($paymentId, [
        'active' => false
      ]);
  }

  public function retrievePaymentIntent($paymentId) {
    return $this->stripe
      ->paymentIntents
      ->retrieve($paymentId);
  }

  public function retrievePaymentLink($paymentId) {
    return $this->stripe
      ->paymentLinks
      ->retrieve($paymentId, [
        'expand' => ['line_items'],
      ]);
  }

  /**
   * Creates a Payment Link with given params
   * @param string $paymentsType The payments type "one_time" or "recurring"
   * @param int $amount The price amount in cents
   * @param string $callbackUrl URL to redirect after payment completion
   */
  public function createPaymentLink($paymentsType, $amount, $callbackUrl) {
    $price = $this->getPrice($paymentsType, $amount);
    if (empty($price)) {
      $price = $this->createPrice($paymentsType, $amount);
    }
    if (!empty($price)) {
      $paymentIntent = [
        ['line_items' => [['price' => $price->id, 'quantity' => 1]]],
        'after_completion' => [
          'type' => 'redirect',
          'redirect' => ['url' => $callbackUrl],
        ],
      ];
      return $this->stripe->paymentLinks->create($paymentIntent);
    }
    return null;
  }

  public function getProductId($type) {
    try {
      $recurringId = $this->formConfig->stripeSubscriptionProductId;
      $oneTimeId = $this->formConfig->stripeOneTimeProductId;
      return $type == 'recurring' ? $recurringId : $oneTimeId;
    } catch (\Exception $e) {
      Craft::error($e->getMessage(), __METHOD__);
    }
  }
}
