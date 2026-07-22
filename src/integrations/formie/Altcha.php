<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Formie captcha integration.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\integrations\formie;

use Craft;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use jalendport\altcha\Altcha as AltchaPlugin;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Registers Altcha as a captcha Formie can enable per form.
 *
 * Formie owns the settings UI, the spam log, and where the widget lands in the
 * form; all this class does is render the widget and hand the posted payload to
 * the plugin's verification service.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class Altcha extends Captcha
{
    // Public Properties
    // =========================================================================

    /**
     * @var string|null the captcha's handle within Formie
     * @since 1.0.0
     */
    public ?string $handle = 'altcha';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function getDescription(): string
    {
        return Craft::t('altcha', 'ALTCHA provides privacy-first spam protection with a globally compliant and accessible alternative to Captchas. Find out more via [Altcha](https://altcha.org/).');
    }

    /**
     * @inheritdoc
     * @throws LoaderError if the widget template can't be loaded
     * @throws RuntimeError if rendering the widget template fails
     * @throws SyntaxError if the widget template has a syntax error
     * @throws Exception if the templates path can't be resolved
     * @throws InvalidConfigException if the widget asset bundle can't be
     * registered
     * @since 1.0.0
     */
    public function getFrontEndHtml(Form $form, ?FieldLayoutPage $page = null): string
    {
        // The widget's `name` defaults to `altcha`, which is what
        // getCaptchaValue() reads back on submission.
        return (string)AltchaPlugin::$plugin->altcha->renderWidget();
    }

    /**
     * @inheritdoc
     *
     * Formie's default publishes an icon from its own asset bundle, which has
     * nothing under this plugin's handle — so inline our own instead.
     *
     * @since 1.0.0
     */
    public function getIconUrl(): string
    {
        $path = Craft::getAlias('@jalendport/altcha/icon.svg');
        $svg = is_string($path) ? @file_get_contents($path) : false;

        if ($svg === false) {
            return parent::getIconUrl();
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function getName(): string
    {
        return Craft::t('altcha', 'Altcha');
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function getSettingsHtml(): string
    {
        $link = Html::a(Craft::t('altcha', 'Altcha plugin settings page'), UrlHelper::cpUrl('settings/altcha'));
        $note = Craft::t('altcha', 'Altcha settings can be updated on the {link}.', ['link' => $link]);

        return Html::tag('div', Html::tag('blockquote', $note, ['class' => 'note tip']), ['class' => 'readable']);
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function validateSubmission(Submission $submission): bool
    {
        // Read through Formie's helper rather than the POST params directly, so
        // GraphQL submissions — which carry captcha data on the submission —
        // verify too.
        $payload = (string)$this->getCaptchaValue($submission, 'altcha');

        if ($payload === '') {
            $this->spamReason = Craft::t('altcha', 'Submission was missing an Altcha solution.');
            return false;
        }

        if (!AltchaPlugin::$plugin->altcha->verifySolution($payload)) {
            $this->spamReason = Craft::t('altcha', 'Submission failed Altcha verification.');
            return false;
        }

        return true;
    }
}
