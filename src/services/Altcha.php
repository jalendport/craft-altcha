<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * The plugin's core service: challenge issuance, solution verification, replay
 * protection, and widget rendering.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\services;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha as AltchaClient;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\VerifySolutionOptions;
use Craft;
use craft\helpers\App;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use InvalidArgumentException;
use jalendport\altcha\Altcha as AltchaPlugin;
use jalendport\altcha\models\Settings;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\web\View;

/**
 * The Altcha service holds all of the challenge and verification logic. The
 * controllers, integrations, and Twig variable are thin wrappers that delegate
 * here.
 *
 * Its two dependencies on a booted Craft app — the plugin settings and the
 * cache — are resolved through overridable getters ({@see getSettings()} and
 * {@see getCache()}), so unit tests can inject a {@see Settings} model and a
 * {@see \yii\caching\ArrayCache} and exercise verification without an
 * application.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class Altcha extends Component
{
    // Private Properties
    // =========================================================================

    /**
     * @var CacheInterface|null the injected cache, or null to fall back to
     * Craft's cache
     * @see getCache()
     * @since 1.0.0
     */
    private ?CacheInterface $_cache = null;

    /**
     * @var Settings|null the injected settings, or null to fall back to the
     * plugin's settings
     * @see getSettings()
     * @since 1.0.0
     */
    private ?Settings $_settings = null;

    // Public Methods
    // =========================================================================

    /**
     * Handles the {@see \yii\web\View::EVENT_BEGIN_BODY} event, registering the
     * widget script on front-end requests when the setting is enabled.
     *
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function beginBodyEventHandler(): void
    {
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return;
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        if (!$this->getSettings()->registerWidgetJs) {
            return;
        }

        $this->_registerWidgetScript();
    }

    /**
     * Issues a fresh challenge for the widget to solve, signed with the
     * configured HMAC key.
     *
     * Fails closed: if the key is empty or its environment reference can't be
     * resolved, this returns null rather than issuing an unverifiable challenge.
     *
     * @return Challenge|null the challenge, or null when the HMAC key is
     * misconfigured
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function createChallenge(): ?Challenge
    {
        $hmacKey = $this->_getHmacKey();

        if ($hmacKey === '') {
            return null;
        }

        $settings = $this->getSettings();
        $client = new AltchaClient(hmacSignatureSecret: $hmacKey);

        return $client->createChallenge(new CreateChallengeOptions(
            algorithm: new Pbkdf2(),
            cost: $settings->complexity,
            expiresAt: time() + $settings->challengeExpiryMinutes * 60,
        ));
    }

    /**
     * Returns the cache used for replay tracking, defaulting to Craft's cache.
     *
     * @return CacheInterface the cache
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function getCache(): CacheInterface
    {
        if ($this->_cache !== null) {
            return $this->_cache;
        }

        return Craft::$app->getCache();
    }

    /**
     * Returns the URL the widget fetches challenges from.
     *
     * @return string the challenge URL
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function getChallengeUrl(): string
    {
        return UrlHelper::actionUrl('altcha/challenge');
    }

    /**
     * Returns the plugin settings, defaulting to the shared plugin instance's.
     *
     * @return Settings the settings
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function getSettings(): Settings
    {
        if ($this->_settings !== null) {
            return $this->_settings;
        }

        /** @var Settings $settings */
        $settings = AltchaPlugin::$plugin->getSettings();

        return $settings;
    }

    /**
     * Renders the Altcha widget, merging the challenge URL with the given
     * options.
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
        $view = Craft::$app->getView();
        $oldTemplatesPath = $view->getTemplatesPath();
        $templatePath = Craft::getAlias('@jalendport/altcha/templates');
        $view->setTemplatesPath($templatePath);

        $options = array_merge([
            'challengeurl' => $this->getChallengeUrl(),
        ], $options);

        try {
            $widgetHtml = $view->renderTemplate('_widget', [
                'options' => $options,
            ]);
        } finally {
            // Always restore the path so an exception here doesn't break Twig
            // for the rest of the request.
            $view->setTemplatesPath($oldTemplatesPath);
        }

        return Template::raw($widgetHtml);
    }

    /**
     * Injects the cache used for replay tracking. Intended for tests.
     *
     * @param CacheInterface $cache the cache to use
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function setCache(CacheInterface $cache): void
    {
        $this->_cache = $cache;
    }

    /**
     * Injects the settings model. Intended for tests.
     *
     * @param Settings $settings the settings to use
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function setSettings(Settings $settings): void
    {
        $this->_settings = $settings;
    }

    /**
     * Verifies a solution payload posted by the widget.
     *
     * Fails closed on a misconfigured HMAC key, a malformed payload, and an
     * already-consumed payload (replay). A payload is only ever accepted once:
     * on the first successful verification it is registered in the cache, and
     * any later attempt with the same payload is rejected as a replay.
     *
     * @param string $payload the base64-encoded payload from the widget
     * @return bool whether the solution is valid and unused
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function verifySolution(string $payload): bool
    {
        $hmacKey = $this->_getHmacKey();

        if ($hmacKey === '') {
            AltchaPlugin::error('Altcha verification aborted: the HMAC key is empty or its environment reference could not be resolved.');
            return false;
        }

        $client = new AltchaClient(hmacSignatureSecret: $hmacKey);

        try {
            $result = $client->verifySolution(new VerifySolutionOptions(
                payload: $payload,
                algorithm: new Pbkdf2(),
            ));
        } catch (InvalidArgumentException) {
            // The payload couldn't be parsed (bad base64, invalid JSON, or
            // missing fields) — treat it as a failed verification.
            return false;
        }

        if (!$result->verified) {
            return false;
        }

        return $this->_registerConsumed($payload);
    }

    // Private Methods
    // =========================================================================

    /**
     * Resolves the configured HMAC key, parsing any environment reference.
     *
     * Returns an empty string when the key is unset or its environment
     * reference is unresolved, so callers can fail closed.
     *
     * @return string the resolved HMAC key, or an empty string when unusable
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _getHmacKey(): string
    {
        $key = App::parseEnv($this->getSettings()->hmacKey);

        // parseEnv returns null/false for an unresolved env reference; a value
        // still starting with `$` means an env variable that wasn't set.
        if (!is_string($key) || $key === '' || str_starts_with($key, '$')) {
            return '';
        }

        return $key;
    }

    /**
     * Registers a verified payload as consumed for replay protection.
     *
     * {@see \yii\caching\CacheInterface::add()} returns false when the key
     * already exists, which means this exact solution was already accepted.
     *
     * @param string $payload the verified payload
     * @return bool whether the payload was newly registered (false on replay)
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _registerConsumed(string $payload): bool
    {
        $cacheKey = 'altcha:consumed:' . hash('sha256', $payload);
        // Outlive the challenge's own expiry by a margin so a replay can't slip
        // through between expiry and cache eviction.
        $duration = $this->getSettings()->challengeExpiryMinutes * 60 + 300;

        if (!$this->getCache()->add($cacheKey, 1, $duration)) {
            AltchaPlugin::info('Altcha rejected a replayed solution.');
            return false;
        }

        return true;
    }

    /**
     * Registers the Altcha widget script on the current front-end request.
     *
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _registerWidgetScript(): void
    {
        try {
            Craft::$app->getView()->registerJsFile(
                'https://cdn.jsdelivr.net/gh/altcha-org/altcha/dist/altcha.min.js',
                [
                    'async' => true,
                    'defer' => true,
                    'type' => 'module',
                    'position' => View::POS_HEAD,
                ]
            );
        } catch (InvalidConfigException $e) {
            AltchaPlugin::error($e->getMessage());
        }
    }
}
