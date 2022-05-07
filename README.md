# One-click Payments plugin for Craft CMS 3.x + Formie 1.5.x

Payments plugin for Craft CMS using Formie and Stripe Checkout.

## Overview

This plugin allows you to support payments on your Craft CMS site by using [Stripe](https://stripe.com/) and [Formie](https://verbb.io/craft-plugins/formie/features) to enable Stripe Checkout. This plugin is designed for simple cases in which you want to:

- Avoid creating a new UI/UX to capture credit card details on your site.
- Skip the configuration process for a shopping cart.
- Use a simple, fast and secure payment flow that supports one-time and recurring payments.
- Avoid user login/creation to complete payments.

## Requirements

This plugin requires Craft CMS 3.0.0 or later and Formie 1.5.x or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require madebyraygun/one-click-payments

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for One-click Payments.

## Configuring One-click Payments

Before using the plugin you will need to set a configuration file under: `/craft/config/one-click-payments.php` which looks like this:

```php
<?php

use craft\helpers\App;

return [
  "stripeSecretKey" => App::env('STRIPE_SECRET_KEY'),
  "stripeHookSecretKey" => App::env('STRIPE_HOOK_SECRET'),
  'forms' => [
    'formHandleOne' => [
      'thankyouLanding' => '/path/to/thank-you',
      'cancelLanding' => '/path/to/cancel-confirmation',
      'stripeSubscriptionProductId' => App::env('STRIPE_SUB_PRODUCT'),
      'stripeOneTimeProductId' => App::env('STRIPE_OT_PRODUCT'),
    ],
    'formHandleTwo' => [
      'thankyouLanding' => '/path/to/thank-you',
      'stripeOneTimeProductId' => App::env('STRIPE_OT_PRODUCT'),
    ]
  ],
];
```

As a best practice you should store any sensitive values in your .env file.

## Configure Stripe Account and configure a Webhook
First make sure you have a [Stripe](https://stripe.com/) account and make sure to put it in [Test Mode](https://stripe.com/docs/testing). Then you can grab the `stripeSecretKey` [Secret Key](https://stripe.com/docs/keys) from stripe dashboard and add it to your .env file.

Next you'll need to configure a webhook to receive events of your payments, to do this:
1. Log into your Stripe dashboard.
2. Click on `Developers` at the top right.
3. Click on the `Webhooks` tab.
4. Click on `Add endpoint` and point the url at your dev URL
`https://example.com/webhooks/stripe-payments`

You'll most likely be developing on a local environment so you'll need to expose your local dev server to the public so Stripe can reach you. You can use [ngrok](https://ngrok.com/) or a similar tool to do this. In case you are using [Nitro](https://craftcms.com/docs/nitro/2.x/) you can configure ngrok like this:
```
./ngrok http -host-header=rewrite yoursite.nitro:80
```

5. For the “Events to send” setting, select “receive all events” or enter the following:
- `checkout.session.completed`
- `payment_intent.payment_failed`
- `payment_intent.succeeded`
6. Click `Add endpoint`.
7. Grab the `Signing secret` of your new endpoint and use it to configure the `stripeHookSecretKey` of your config.

## Create your Stripe Products
Each form requires at least one product, (One Time or Recurring), but can support both simultaneously. Create the products and grab their IDs to configure them to be either `One Time` or `Recurring` under price. You can optionally specify a price for the product, but the price field will be automatically calculated from your form and added to the product if needed. (This allows for custom values in both one-time and subscription products, perfect for donation or "pay what you want" forms.)

Add the ID for each product inside the array matching the form handle in your config, as either a `stripeOneTimeProductId`, `stripeSubscriptionProductId`, or both.

## Create your Formie Forms
Internally the plugin will take Formie handles `formHandleOne` and `formHandleTwo` from the sample config and try getting the values to configure the `paymentTotal` and purchasable `paymentType` for the checkout. To do that we need to add the following fields to the form:

1) A field with a handle `paymentTotal` to store the amount for the checkout. This can be a Radio Button, Dropdown, Calculation, Hidden Field or Number field. This field should have a number in USD as currency as the value.
2) A field with a handle `paymentType` to have `one_time` or `recurring` as values. This can be a Radio Button, Dropdowns or a Hidden Field.
3) If you want to support subscriptions (`recurring`) payments you'll need an aditional Hidden field with the handle `cancelSubscriptionUrl` to store a generated "Magic" URL to let customers cancel a subscription which is usually given by email.

For Hidden fields you need to make sure to set the value as a `Default Value`.

## Configure your Formie Statuses

You need to have the following statuses configured in your payment forms:
  - submitted (set this as default for new payment forms)
  - processing
  - confirmed
  - cancelled

## Configure your notifications

You can set up unique notifications for each form, conditional based on the status of the submission. Once the Stripe payment is confirmed and the webhook is fired, the submission status should change to `confirmed`. This would be a good opportunity to send a confirmation email with a summary of the transaction including the total. 

For subscription products, you should also include a link to your magic `cancelSubscriptionUrl` in the confirmation so users can easily cancel their subscription. When a subscription is cancelled, the webhook is fired and the submission status will be changed to `cancelled`, this is another opportunity to send a confirmation email.

## Configure your Landing Pages

These are common pages for the user to be redirected to after the checkout is completed or once the subscription is been cancelled. You can set unique pages for each form or create a generic set of pages for all forms.

## Using One-click Payments

Once you've configured Stripe you can start testing by embedding the form in any page and test the checkout flow. Make sure you set your live Stripe keys in the .env for your production server and you should be ready to make a payments. 

## One-click Payments Roadmap

* Update for Craft 4 and Formie 2.0. (planned for 1.0 milestone)
* Store more data about the transaction from the Stripe webhook.
* Reference form values in the confirmation pages.
* Automate the creation of required field to simplify setup.

Brought to you by [Raygun](https://madebyraygun.com)
