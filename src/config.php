<?php
/**
 * One-click Payment plugin for Craft CMS 3.x
 *
 * Payments plugin for single-click checkouts using Formie and Stripe
 *
 * @link      https://github.com/madebyraygun
 * @copyright Copyright (c) 2022 MadeByRaygun
 */

/**
 * One-click Payment config.php
 *
 * This file exists only as a template for the One-click Payment settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'one-click-payments.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    "stripeSecretKey" => "",
    "stripeHookSecretKey" => "",
    "stripeSubscriptionProductId" => "",
    "stripeOneTimeProductId" => "",
    "forms" => [],
];
