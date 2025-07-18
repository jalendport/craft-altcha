<?php

namespace jalendport\altcha\services;

use AltchaOrg\Altcha\Altcha as AltchaClient;
use Craft;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use jalendport\altcha\Altcha;
use jalendport\altcha\Altcha as AltchaPlugin;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;
use yii\base\Component;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\View;

/**
 * Altcha service
 */
class AltchaService extends Component
{

	/**
	 * @throws InvalidConfigException
	 */
	public function craftVariableInitEventHandler(Event $e): void
	{

		/** @var CraftVariable $variable */
		$variable = $e->sender;

		// Attach the AltchaVariable service
		$variable->set('altcha', AltchaVariable::class);

	}


	public function beginBodyEventHandler(): void
	{

		if (!Craft::$app->getRequest()->getIsCpRequest() &&
			!Craft::$app->getRequest()->getIsConsoleRequest() &&
			AltchaPlugin::getInstance()->getSettings()->registerWidgetJs)
		{
			$this->registerAltchaWidgetScript();
		}

	}


	private function registerAltchaWidgetScript(): void
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


	/**
	 * @throws SyntaxError
	 * @throws RuntimeError
	 * @throws Exception
	 * @throws LoaderError
	 */
	public function renderWidget(array $options = []): Markup
	{

		$view = Craft::$app->getView();
		$oldTemplatesPath = $view->getTemplatesPath();
		$templatePath = Craft::getAlias('@jalendport/altcha/templates');
		$view->setTemplatesPath($templatePath);

		// Merge provided options with defaults
		$options = array_merge([
			'challengeurl' => $this->getChallengeUrl(),
		], $options);

		$widgetHtml = $view->renderTemplate('_widget', [
			'options' => $options,
		]);

		// Restore the original templates path
		$view->setTemplatesPath($oldTemplatesPath);

		return Template::raw($widgetHtml);

	}


	public function getChallengeUrl(): string
	{

		$settings = AltchaPlugin::getInstance()->getSettings();

		if ($settings->verificationMethod === 'sentinel') {
			return $settings->sentinelEndpointUrl . '?apiKey=' . $settings->sentinelApiKey;
		}

		return UrlHelper::actionUrl('altcha/challenge');

	}


	public function verifySolution($payload): bool
	{

		$altcha = new AltchaClient(Altcha::getInstance()->getSettings()->hmacKey);

		return $altcha->verifySolution(
			$payload,
			true
		);

	}

}
