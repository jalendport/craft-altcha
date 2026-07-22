<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * The `craft.altcha` Twig variable.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\variables;

use jalendport\altcha\Altcha;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;
use yii\base\Exception;

/**
 * Exposes the Altcha service to templates as `craft.altcha`. Every method is a
 * thin wrapper that delegates to the service.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class AltchaVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the URL the widget fetches challenges from.
     *
     * @return string the challenge URL
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function getChallengeUrl(): string
    {
        return Altcha::$plugin->altcha->getChallengeUrl();
    }

    /**
     * Renders the Altcha widget.
     *
     * @param array<string, mixed> $options the widget attribute overrides
     * @return Markup the rendered widget
     * @throws LoaderError if the widget template can't be loaded
     * @throws RuntimeError if rendering the widget template fails
     * @throws SyntaxError if the widget template has a syntax error
     * @throws Exception if the templates path can't be resolved
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function renderWidget(array $options = []): Markup
    {
        return Altcha::$plugin->altcha->renderWidget($options);
    }
}
