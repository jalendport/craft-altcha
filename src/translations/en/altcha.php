<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * English translation messages.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

return [
    // Plugin
    'Altcha' => 'Altcha',

    // Settings navigation
    'General Settings' => 'General Settings',
    'Integrations' => 'Integrations',
    'Widget Options' => 'Widget Options',

    // Settings — layout
    'These settings are read-only because administrative changes are disallowed in this environment.' => 'These settings are read-only because administrative changes are disallowed in this environment.',

    // Settings — general
    'HMAC key' => 'HMAC key',
    'The secret key used to sign and verify challenges. Run `php craft altcha/generate-hmac-key` to generate one.' => 'The secret key used to sign and verify challenges. Run `php craft altcha/generate-hmac-key` to generate one.',
    'Complexity' => 'Complexity',
    'How much work a visitor’s browser spends solving a challenge. Higher settings cost bots more, but also slow real visitors down on older devices.' => 'How much work a visitor’s browser spends solving a challenge. Higher settings cost bots more, but also slow real visitors down on older devices.',
    'Low' => 'Low',
    'Medium' => 'Medium',
    'High' => 'High',
    'Challenge expiry' => 'Challenge expiry',
    'How long an issued challenge stays valid. Anything from 20 to 60 minutes suits most forms.' => 'How long an issued challenge stays valid. Anything from 20 to 60 minutes suits most forms.',
    'minutes' => 'minutes',

    // Settings — integrations
    'This integration stays dormant until the plugin it protects is installed and enabled.' => 'This integration stays dormant until the plugin it protects is installed and enabled.',
    'Comments' => 'Comments',
    'Requires a solved challenge on front-end comments submitted through the Comments plugin.' => 'Requires a solved challenge on front-end comments submitted through the Comments plugin.',
    'Contact Form' => 'Contact Form',
    'Requires a solved challenge on messages sent through the Contact Form plugin.' => 'Requires a solved challenge on messages sent through the Contact Form plugin.',
    'Guest Entries' => 'Guest Entries',
    'Requires a solved challenge on entries submitted through the Guest Entries plugin.' => 'Requires a solved challenge on entries submitted through the Guest Entries plugin.',
    'User registration' => 'User registration',
    'Requires a solved challenge on Craft’s own front-end registration form.' => 'Requires a solved challenge on Craft’s own front-end registration form.',
    'Protect the login form' => 'Protect the login form',
    'Also requires a solved challenge on Craft’s front-end login form.' => 'Also requires a solved challenge on Craft’s front-end login form.',
    'Protect the forgot-password form' => 'Protect the forgot-password form',
    'Also requires a solved challenge on Craft’s front-end password reset form.' => 'Also requires a solved challenge on Craft’s front-end password reset form.',
    'Formie forms are protected from Formie’s own captcha settings, not here.' => 'Formie forms are protected from Formie’s own captcha settings, not here.',
    'Blanket mode' => 'Blanket mode',
    'Requires a solved challenge on any front-end POST to the action paths you list below. Use this for form plugins with no integration of their own, such as Freeform and Campaign.' => 'Requires a solved challenge on any front-end POST to the action paths you list below. Use this for form plugins with no integration of their own, such as Freeform and Campaign.',
    'Allowed action paths' => 'Allowed action paths',
    'One controller action path per row, such as `freeform/submit`. End a path with `*` to match everything beneath it, such as `campaign/forms/*`. Altcha’s own action paths are never enforced.' => 'One controller action path per row, such as `freeform/submit`. End a path with `*` to match everything beneath it, such as `campaign/forms/*`. Altcha’s own action paths are never enforced.',
    'Action path' => 'Action path',
    'Add an action path' => 'Add an action path',

    // Settings — widget
    'Display mode' => 'Display mode',
    'How the widget appears in your forms. “Invisible” solves the challenge in the background with no visible checkbox, so pair it with an auto-solve mode.' => 'How the widget appears in your forms. “Invisible” solves the challenge in the background with no visible checkbox, so pair it with an auto-solve mode.',
    'Standard' => 'Standard',
    'Bar' => 'Bar',
    'Floating' => 'Floating',
    'Overlay' => 'Overlay',
    'Invisible' => 'Invisible',
    'Auto-solve' => 'Auto-solve',
    'When the widget starts solving its challenge. Leave this off to have visitors start it themselves by checking the box.' => 'When the widget starts solving its challenge. Leave this off to have visitors start it themselves by checking the box.',
    'Off' => 'Off',
    'On focus' => 'On focus',
    'On load' => 'On load',
    'On submit' => 'On submit',
    'Theme' => 'Theme',
    'The widget’s color scheme. “Automatic” follows the visitor’s system preference.' => 'The widget’s color scheme. “Automatic” follows the visitor’s system preference.',
    'Automatic' => 'Automatic',
    'Light' => 'Light',
    'Dark' => 'Dark',
    'Hide the ALTCHA logo' => 'Hide the ALTCHA logo',
    'Hides the ALTCHA logo inside the widget.' => 'Hides the ALTCHA logo inside the widget.',
    'Hide the widget footer' => 'Hide the widget footer',
    'Hides the “Protected by ALTCHA” footer beneath the widget.' => 'Hides the “Protected by ALTCHA” footer beneath the widget.',
    'Register the widget script' => 'Register the widget script',
    'Loads the bundled ALTCHA script on any page that renders the widget. Turn this off if you bundle the ALTCHA script into your own front-end JavaScript.' => 'Loads the bundled ALTCHA script on any page that renders the widget. Turn this off if you bundle the ALTCHA script into your own front-end JavaScript.',

    // Formie captcha
    'ALTCHA provides privacy-first spam protection with a globally compliant and accessible alternative to Captchas. Find out more via [Altcha](https://altcha.org/).' => 'ALTCHA provides privacy-first spam protection with a globally compliant and accessible alternative to Captchas. Find out more via [Altcha](https://altcha.org/).',
    'Altcha plugin settings page' => 'Altcha plugin settings page',
    'Altcha settings can be updated on the {link}.' => 'Altcha settings can be updated on the {link}.',
    'Submission was missing an Altcha solution.' => 'Submission was missing an Altcha solution.',
    'Submission failed Altcha verification.' => 'Submission failed Altcha verification.',

    // Front-end verification
    'Altcha verification failed. Please try again.' => 'Altcha verification failed. Please try again.',
];
