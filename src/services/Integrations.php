<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Registers the plugin's third-party integrations.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\services;

use Craft;
use craft\base\Element;
use craft\contactform\events\SendEvent;
use craft\contactform\Mailer;
use craft\controllers\UsersController;
use craft\events\ModelEvent;
use craft\guestentries\controllers\SaveController;
use craft\guestentries\events\SaveEvent;
use jalendport\altcha\Altcha as AltchaPlugin;
use jalendport\altcha\integrations\BlanketMode;
use jalendport\altcha\integrations\Comments;
use jalendport\altcha\integrations\ContactForm;
use jalendport\altcha\integrations\formie\Altcha as FormieIntegration;
use jalendport\altcha\integrations\GuestEntries;
use jalendport\altcha\integrations\Users;
use verbb\comments\elements\Comment;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations as FormieIntegrations;
use yii\base\ActionEvent;
use yii\base\Component;
use yii\base\Controller;
use yii\base\Event;
use yii\base\Module;

/**
 * Wires up every integration the plugin ships, each behind both a `class_exists`
 * check for the host plugin and — where the integration changes how an existing
 * form behaves — its own settings toggle, so installing this plugin is inert
 * until it's switched on.
 *
 * Formie is the exception: it registers Altcha as an available captcha, which
 * only takes effect once it's enabled per form in Formie itself.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class Integrations extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Registers all available integrations.
     *
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function addAll(): void
    {
        $this->_addFormieIntegration();
        $this->_addCommentsIntegration();
        $this->_addContactFormIntegration();
        $this->_addGuestEntriesIntegration();
        $this->_addUsersIntegration();
        $this->_addBlanketMode();
    }

    // Private Methods
    // =========================================================================

    /**
     * Registers blanket-POST mode across every allowlisted action.
     *
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _addBlanketMode(): void
    {
        $settings = AltchaPlugin::$plugin->getSettings();

        if (!$settings->enableBlanketMode) {
            return;
        }

        if ($settings->blanketActionAllowlist === []) {
            return;
        }

        // The application is the outermost module in every request, so its
        // before-action event covers actions in every controller.
        Craft::$app->on(
            Module::EVENT_BEFORE_ACTION,
            static function(ActionEvent $event): void {
                BlanketMode::beforeAction($event);
            }
        );
    }

    /**
     * Registers the Verbb Comments integration.
     *
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _addCommentsIntegration(): void
    {
        if (!AltchaPlugin::$plugin->getSettings()->enableComments) {
            return;
        }

        if (!class_exists(Comment::class)) {
            return;
        }

        Event::on(
            Comment::class,
            Element::EVENT_BEFORE_SAVE,
            static function(ModelEvent $event): void {
                Comments::beforeSaveComment($event);
            }
        );
    }

    /**
     * Registers the Contact Form integration.
     *
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _addContactFormIntegration(): void
    {
        if (!AltchaPlugin::$plugin->getSettings()->enableContactForm) {
            return;
        }

        if (!class_exists(Mailer::class)) {
            return;
        }

        Event::on(
            Mailer::class,
            Mailer::EVENT_BEFORE_SEND,
            static function(SendEvent $event): void {
                ContactForm::beforeSend($event);
            }
        );
    }

    /**
     * Registers Altcha as an available Formie captcha.
     *
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _addFormieIntegration(): void
    {
        if (!class_exists(FormieIntegrations::class)) {
            return;
        }

        Event::on(
            FormieIntegrations::class,
            FormieIntegrations::EVENT_REGISTER_INTEGRATIONS,
            static function(RegisterIntegrationsEvent $event): void {
                $event->captchas[] = FormieIntegration::class;
            }
        );
    }

    /**
     * Registers the Guest Entries integration.
     *
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _addGuestEntriesIntegration(): void
    {
        if (!AltchaPlugin::$plugin->getSettings()->enableGuestEntries) {
            return;
        }

        if (!class_exists(SaveController::class)) {
            return;
        }

        Event::on(
            SaveController::class,
            SaveController::EVENT_BEFORE_SAVE_ENTRY,
            static function(SaveEvent $event): void {
                GuestEntries::beforeSaveEntry($event);
            }
        );
    }

    /**
     * Registers the native user forms integration.
     *
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _addUsersIntegration(): void
    {
        $settings = AltchaPlugin::$plugin->getSettings();

        if (!$settings->enableUserRegistration && !$settings->protectLogin && !$settings->protectForgotPassword) {
            return;
        }

        Event::on(
            UsersController::class,
            Controller::EVENT_BEFORE_ACTION,
            static function(ActionEvent $event): void {
                Users::beforeAction($event);
            }
        );
    }
}
