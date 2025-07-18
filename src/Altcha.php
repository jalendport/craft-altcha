<?php

namespace jalendport\altcha;

use Craft;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;
use craft\web\View;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use craft\base\Event;
use craft\base\Model;
use craft\base\Plugin;
use craft\log\MonologTarget;
use craft\web\twig\variables\CraftVariable;
use jalendport\altcha\controllers\console\AltchaConsoleController;
use jalendport\altcha\models\Settings;
use jalendport\altcha\services\AltchaService as AltchaService;
use jalendport\altcha\services\AltchaVariable;
use jalendport\altcha\services\Integrations;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\web\View as YiiView;

/**
 * Altcha plugin
 *
 * @method static Altcha getInstance()
 * @method Settings getSettings()
 * @author Jalen Davenport <hello@jalendport.com>
 * @copyright Jalen Davenport
 * @license MIT
 * @property-read AltchaService $altchaService
 * @property-read AltchaVariable $altchaVariable
 * @property-read Integrations $integrations
 */
class Altcha extends Plugin
{

    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;


    public static function config(): array
    {
        return [
            'components' => [
				'altchaService' => AltchaService::class,
				'altchaVariable' => AltchaVariable::class,
    			'integrations' => Integrations::class
			],
        ];
    }


    public function init(): void
    {

		Craft::setAlias('@altcha', __DIR__);

		if (Craft::$app->request->isConsoleRequest)
		{
			$this->controllerNamespace = 'jalendport\\altcha\\null';
			Craft::$app->controllerMap['altcha'] = AltchaConsoleController::class;
		}
		else
		{
			$this->controllerNamespace = 'jalendport\\altcha\\controllers\\web';
		}

		parent::init();

		$this->_attachEventHandlers();
		$this->_registerCpRoutes();
		$this->_registerLogTarget();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            $this->integrations->addAll();
        });

	}


	/**
	 * @throws InvalidConfigException
	 */
	protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }


	/**
	 * @throws InvalidRouteException
	 */
	public function getSettingsResponse(): \craft\web\Response
	{
		return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('settings/altcha'));
    }


	public function getSettingsNavItems(): array {
		return [
			'general' => ['title' => Craft::t('altcha', 'General Settings')],
			'integrations' => ['title' => Craft::t('altcha', 'Integrations')],
			'widget' => ['title' => Craft::t('altcha', 'Widget Options')],
		];
	}


    private function _attachEventHandlers(): void
    {

		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			[$this->altchaService, 'craftVariableInitEventHandler']
		);

		Event::on(
			View::class,
			YiiView::EVENT_BEGIN_BODY,
			[$this->altchaService, 'beginBodyEventHandler']
		);

    }


	private function _registerCpRoutes(): void
	{

		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			function(RegisterUrlRulesEvent $event) {
				$event->rules['settings/altcha'] = 'altcha/settings/index';
				$event->rules['settings/altcha/general'] = 'altcha/settings/general';
				$event->rules['settings/altcha/integrations'] = 'altcha/settings/integrations';
				$event->rules['settings/altcha/widget'] = 'altcha/settings/widget';
			}
		);

	}


	/**
	 * Logs an informational message to our custom log target.
	 */
	public static function info(string $message): void
	{
		Craft::info($message, 'altcha');
	}


	/**
	 * Logs an error message to our custom log target.
	 */
	public static function error(string $message): void
	{
		Craft::error($message, 'altcha');
	}


	/**
	 * Sets up custom logger so module-specific logs end up in their own file
	 */
	private function _registerLogTarget(): void
	{

		Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
			'name' => 'altcha',
			'categories' => ['altcha'],
			'level' => LogLevel::INFO,
			'logContext' => false,
			'allowLineBreaks' => false,
			'formatter' => new LineFormatter(
				format: "%datetime% %message%\n",
				dateFormat: 'Y-m-d H:i:s',
			),
		]);

	}

}
