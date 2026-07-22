<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Renders the plugin's control panel settings pages.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\controllers;

use Craft;
use craft\web\Controller;
use craft\web\View;
use jalendport\altcha\Altcha;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

/**
 * Serves the Altcha settings tabs (general, integrations, widget) in the
 * control panel.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class SettingsController extends Controller
{
    // Private Properties
    // =========================================================================

    /**
     * @var array<string, mixed> the variables shared with every settings tab
     * @since 1.0.0
     */
    private array $_variables = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $this->_variables = [
            'plugin' => Altcha::$plugin,
            'settings' => Altcha::$plugin->getSettings(),
            'navItems' => Altcha::$plugin->getSettingsNavItems(),
        ];

        return parent::beforeAction($action);
    }

    /**
     * Renders the settings landing page.
     *
     * @return Response the rendered page
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function actionIndex(): Response
    {
        $variables = $this->_variables;

        return $this->renderTemplate('altcha/_settings/index', compact('variables'), View::TEMPLATE_MODE_CP);
    }

    /**
     * Renders the general settings tab.
     *
     * @return Response the rendered tab
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function actionGeneral(): Response
    {
        $variables = $this->_variables;

        return $this->renderTemplate('altcha/_settings/general', compact('variables'), View::TEMPLATE_MODE_CP);
    }

    /**
     * Renders the integrations settings tab.
     *
     * @return Response the rendered tab
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function actionIntegrations(): Response
    {
        $variables = $this->_variables;

        return $this->renderTemplate('altcha/_settings/integrations', compact('variables'), View::TEMPLATE_MODE_CP);
    }

    /**
     * Renders the widget options settings tab.
     *
     * @return Response the rendered tab
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function actionWidget(): Response
    {
        $variables = $this->_variables;

        return $this->renderTemplate('altcha/_settings/widget', compact('variables'), View::TEMPLATE_MODE_CP);
    }

    /**
     * Saves the plugin settings.
     *
     * @return Response|null a redirect on success, or null to re-render with errors
     * @throws MethodNotAllowedHttpException if the request isn't a POST
     * @throws BadRequestHttpException if the request is malformed
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $request = $this->request;

        $settings = Altcha::$plugin->getSettings();
        $settings->setAttributes($request->getParam('settings'), false);

        if (!$settings->validate()) {
            $this->setFailFlash(Craft::t('altcha', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(Altcha::$plugin, $settings->toArray());

        if (!$pluginSettingsSaved) {
            $this->setFailFlash(Craft::t('altcha', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('altcha', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }
}
