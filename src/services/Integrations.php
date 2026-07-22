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

use craft\base\Element;
use craft\events\ModelEvent;
use jalendport\altcha\Altcha as AltchaPlugin;
use jalendport\altcha\integrations\Comments;
use jalendport\altcha\integrations\formie\Altcha as FormieIntegration;
use verbb\comments\elements\Comment;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations as FormieIntegrations;
use yii\base\Component;
use yii\base\Event;

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
    }

    // Private Methods
    // =========================================================================

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
}
