<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Integrate Altcha's privacy-first spam protection into your forms.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha;

use Craft;
use craft\base\Model;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use jalendport\altcha\models\Settings;
use jalendport\altcha\services\Altcha as AltchaService;
use jalendport\altcha\services\Integrations;
use jalendport\altcha\variables\AltchaVariable;
use jalendport\base\Plugin;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\web\Response;

/**
 * Altcha plugin.
 *
 * The main class is deliberately lean: components live in the static
 * {@see config()} method, and {@see init()} is a table of contents of private
 * `_registerXxx()` methods. Per-plugin file logging, the `@altcha` alias, and
 * the shared settings template root come from {@see \jalendport\base\Plugin}.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 *
 * @property-read AltchaService $altcha
 * @property-read Integrations $integrations
 * @method Settings getSettings()
 */
class Altcha extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Altcha the plugin instance
     * @since 1.0.0
     */
    public static Altcha $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var bool whether the plugin has a settings page in the control panel
     * @since 1.0.0
     */
    public bool $hasCpSettings = true;

    /**
     * @var string the plugin's schema version
     * @since 1.0.0
     */
    public string $schemaVersion = '1.0.0';

    // Static Methods
    // =========================================================================

    /**
     * Registers the plugin's components per the Craft 5 plugin spec.
     *
     * @return array the component configuration
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public static function config(): array
    {
        return [
            'components' => [
                'altcha' => AltchaService::class,
                'integrations' => Integrations::class,
            ],
        ];
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerVariable();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
        }

        // Element queries, Twig, and other plugins aren't ready during boot, so
        // defer integration wiring until Craft is fully initialized.
        Craft::$app->onInit(function() {
            $this->integrations->addAll();
        });
    }

    /**
     * Returns the settings sub-navigation shown on the plugin's settings pages.
     *
     * @return array<string, array<string, string>> the nav items, keyed by handle
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function getSettingsNavItems(): array
    {
        return [
            'general' => ['title' => Craft::t('altcha', 'General Settings')],
            'integrations' => ['title' => Craft::t('altcha', 'Integrations')],
            'widget' => ['title' => Craft::t('altcha', 'Widget Options')],
        ];
    }

    /**
     * @inheritdoc
     * @throws InvalidRouteException if the settings route can't be resolved
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function getSettingsResponse(): Response
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('settings/altcha'));
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws InvalidConfigException if the settings model can't be created
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    // Private Methods
    // =========================================================================

    /**
     * Registers the plugin's control panel settings routes.
     *
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            static function(RegisterUrlRulesEvent $event): void {
                $event->rules['settings/altcha'] = 'altcha/settings/index';
                $event->rules['settings/altcha/general'] = 'altcha/settings/general';
                $event->rules['settings/altcha/integrations'] = 'altcha/settings/integrations';
                $event->rules['settings/altcha/widget'] = 'altcha/settings/widget';
            }
        );
    }

    /**
     * Registers the `craft.altcha` Twig variable.
     *
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _registerVariable(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function(Event $event): void {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('altcha', AltchaVariable::class);
            }
        );
    }
}
