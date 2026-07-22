<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * The plugin's settings model.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\models;

use craft\base\Model;

/**
 * Altcha settings.
 *
 * Backs the control panel settings pages and Craft's standard
 * `config/altcha.php` plugin-config mechanism, which merges into this model at
 * boot and makes every setting multi-environment aware for free.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string[] the action paths that blanket-POST mode enforces Altcha on,
     * each an exact `controller/action` path or a trailing-`*` wildcard
     * @since 1.0.0
     */
    public array $blanketActionAllowlist = [];

    /**
     * @var int how many minutes an issued challenge stays valid before it
     * expires
     * @since 1.0.0
     */
    public int $challengeExpiryMinutes = 20;

    /**
     * @var int the proof-of-work cost the widget must spend per solution attempt
     * @since 1.0.0
     */
    public int $complexity = 50000;

    /**
     * @var bool whether blanket-POST mode enforces Altcha across allowlisted
     * action paths
     * @since 1.0.0
     */
    public bool $enableBlanketMode = false;

    /**
     * @var bool whether the Verbb Comments integration is enabled
     * @since 1.0.0
     */
    public bool $enableComments = false;

    /**
     * @var bool whether the Contact Form integration is enabled
     * @since 1.0.0
     */
    public bool $enableContactForm = false;

    /**
     * @var bool whether the Guest Entries integration is enabled
     * @since 1.0.0
     */
    public bool $enableGuestEntries = false;

    /**
     * @var bool whether native user-registration forms are protected
     * @since 1.0.0
     */
    public bool $enableUserRegistration = false;

    /**
     * @var string the HMAC key used to sign and verify challenges — set this to
     * an environment variable (e.g. `$ALTCHA_HMAC_KEY`)
     * @since 1.0.0
     */
    public string $hmacKey = '';

    /**
     * @var bool whether the native forgot-password form is protected
     * @since 1.0.0
     */
    public bool $protectForgotPassword = false;

    /**
     * @var bool whether the native login form is protected
     * @since 1.0.0
     */
    public bool $protectLogin = false;

    /**
     * @var bool whether the plugin registers the Altcha widget script on the
     * front end — disable if you bundle the script yourself
     * @since 1.0.0
     */
    public bool $registerWidgetJs = true;

    /**
     * @var string the widget's auto-solve behavior (`off`, `onfocus`, `onload`,
     * or `onsubmit`)
     * @since 1.0.0
     */
    public string $widgetAuto = 'off';

    /**
     * @var string the widget's display mode (`standard`, `bar`, `floating`,
     * `overlay`, or `invisible`)
     * @since 1.0.0
     */
    public string $widgetDisplay = 'standard';

    /**
     * @var bool whether the widget's footer is hidden
     * @since 1.0.0
     */
    public bool $widgetHideFooter = true;

    /**
     * @var bool whether the widget's ALTCHA logo is hidden
     * @since 1.0.0
     */
    public bool $widgetHideLogo = true;

    /**
     * @var string the widget's color theme (`light`, `dark`, or empty to follow
     * the page)
     * @since 1.0.0
     */
    public string $widgetTheme = '';

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = ['hmacKey', 'required'];
        $rules[] = ['hmacKey', 'string'];

        $rules[] = ['challengeExpiryMinutes', 'integer', 'min' => 1, 'max' => 1440];

        $rules[] = ['complexity', 'integer'];
        $rules[] = ['complexity', 'in', 'range' => [1000, 50000, 100000]];

        $rules[] = ['widgetDisplay', 'in', 'range' => ['standard', 'bar', 'floating', 'overlay', 'invisible']];
        $rules[] = ['widgetAuto', 'in', 'range' => ['off', 'onfocus', 'onload', 'onsubmit']];
        $rules[] = ['widgetTheme', 'string'];

        $rules[] = [
            [
                'registerWidgetJs',
                'widgetHideLogo',
                'widgetHideFooter',
                'enableComments',
                'enableContactForm',
                'enableUserRegistration',
                'protectLogin',
                'protectForgotPassword',
                'enableGuestEntries',
                'enableBlanketMode',
            ],
            'boolean',
        ];

        $rules[] = ['blanketActionAllowlist', 'safe'];

        return $rules;
    }
}
