<?php
/**
 * Altcha config template
 *
 * Don't edit this file — copy it to your project's config/ folder as
 * altcha.php, then override any of the settings below. Like any Craft plugin
 * config file, it's multi-environment aware: nest a setting under environment
 * keys (`'*'`, `'production'`, …) to vary it per environment.
 *
 * Settings defined here take precedence over the control panel; a value set
 * here is locked in the CP and flagged as overridden.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

return [
    // The HMAC key used to sign and verify challenges. Set this to an
    // environment variable, e.g. '$ALTCHA_HMAC_KEY'. Generate one with
    // `php craft altcha/generate-hmac-key`.
    'hmacKey' => '$ALTCHA_HMAC_KEY',

    // How many minutes an issued challenge stays valid before it expires
    // (min 1, max 1440). ALTCHA recommends 20–60 minutes.
    'challengeExpiryMinutes' => 20,

    // The proof-of-work cost the widget must spend per solution attempt.
    // One of: 1000 (low), 50000 (medium), 100000 (high).
    'complexity' => 50000,

    // Whether the plugin registers the Altcha widget script on the front end.
    // Disable if you bundle the Altcha script into your own front-end JS.
    'registerWidgetJs' => true,

    // Integration toggles. Each is off by default so installing the plugin is
    // inert until you opt a form host in.
    'enableComments' => false,
    'enableContactForm' => false,
    'enableUserRegistration' => false,
    'protectLogin' => false,
    'protectForgotPassword' => false,
    'enableGuestEntries' => false,
    'enableBlanketMode' => false,

    // The action paths blanket-POST mode enforces Altcha on. Each entry is an
    // exact 'controller/action' path or a trailing-'*' wildcard, e.g.
    // ['freeform/submit', 'campaign/forms/*'].
    'blanketActionAllowlist' => [],

    // Widget display mode: 'standard', 'bar', 'floating', 'overlay', or
    // 'invisible'.
    'widgetDisplay' => 'standard',

    // Widget auto-solve behavior: 'off', 'onfocus', 'onload', or 'onsubmit'.
    'widgetAuto' => 'off',

    // Widget color theme: 'light', 'dark', or '' to follow the page.
    'widgetTheme' => '',

    // Whether the widget's ALTCHA logo is hidden.
    'widgetHideLogo' => true,

    // Whether the widget's footer is hidden.
    'widgetHideFooter' => true,
];
