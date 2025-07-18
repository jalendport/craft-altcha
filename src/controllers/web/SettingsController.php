<?php
namespace jalendport\altcha\controllers\web;

use Craft;
use craft\web\Controller;
use craft\web\View;
use jalendport\altcha\Altcha;
use jalendport\altcha\models\Settings;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class SettingsController extends Controller
{

	private array $_variables = [];


	public function beforeAction($action): bool
	{
		$settings = Altcha::getInstance()->getSettings();

		$this->_variables = [
			'plugin' => Altcha::getInstance(),
			'settings' => $settings,
			'navItems' => Altcha::getInstance()->getSettingsNavItems()
		];

		return parent::beforeAction($action);
	}


	public function actionIndex(): Response
	{

		$variables = $this->_variables;
		return $this->renderTemplate('altcha/_settings/index', compact('variables'), View::TEMPLATE_MODE_CP);

	}


	public function actionGeneral(): Response
	{

		$variables = $this->_variables;
		return $this->renderTemplate('altcha/_settings/general', compact('variables'), View::TEMPLATE_MODE_CP);

	}


	public function actionIntegrations(): Response
	{

		$variables = $this->_variables;
		return $this->renderTemplate('altcha/_settings/integrations', compact('variables'), View::TEMPLATE_MODE_CP);

	}


	public function actionWidget(): Response
	{

		$variables = $this->_variables;
		return $this->renderTemplate('altcha/_settings/widget', compact('variables'), View::TEMPLATE_MODE_CP);

	}


	/**
	 * @throws MethodNotAllowedHttpException
	 * @throws BadRequestHttpException
	 */
	public function actionSaveSettings(): ?Response
	{

		$this->requirePostRequest();

		$request = $this->request;

		$settings = Altcha::getInstance()->getSettings();
		$settings->setAttributes($request->getParam('settings'), false);

		if (!$settings->validate()) {
			$this->setFailFlash(Craft::t('altcha', 'Couldn’t save settings.'));

			Craft::$app->getUrlManager()->setRouteParams([
				'settings' => $settings,
			]);

			return null;
		}

		$pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(Altcha::getInstance(), $settings->toArray());

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
