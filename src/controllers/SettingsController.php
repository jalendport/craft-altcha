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

use craft\web\Controller;
use craft\web\View;
use jalendport\altcha\Altcha;
use yii\web\ForbiddenHttpException;
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
     * @throws ForbiddenHttpException if the user isn't an admin
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // The HMAC key lives on these pages, so admins only. Passing false
        // keeps them viewable (read-only) where admin changes are disallowed.
        $this->requireAdmin(false);

        $this->_variables = [
            'plugin' => Altcha::$plugin,
            'settings' => Altcha::$plugin->getSettings(),
            'navItems' => Altcha::$plugin->getSettingsNavItems(),
            'overrides' => Altcha::$plugin->getConfigOverrides(),
        ];

        return true;
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
}
