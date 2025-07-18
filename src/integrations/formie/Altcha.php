<?php
namespace jalendport\altcha\integrations\formie;

use Craft;
use craft\helpers\UrlHelper;
use jalendport\altcha\Altcha as AltchaPlugin;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

class Altcha extends \verbb\formie\base\Captcha
{

	public ?string $handle = 'altcha';


	public function getName(): string
	{
		return Craft::t('altcha', 'Altcha');
	}


	/**
	 * @throws SyntaxError
	 * @throws Exception
	 * @throws RuntimeError
	 * @throws LoaderError
	 */
	public function getIconUrl(): string
	{

		$view = Craft::$app->getView();
		$oldTemplatesPath = $view->getTemplatesPath();
		$templatePath = Craft::getAlias('@jalendport/altcha');
		$view->setTemplatesPath($templatePath);

		$svg = Craft::$app->getView()->renderTemplate('icon.svg');

		// Restore the original templates path
		$view->setTemplatesPath($oldTemplatesPath);

		return 'data:image/svg+xml;base64,' . base64_encode($svg);

	}


	public function getDescription(): string
	{
		return Craft::t('altcha', 'ALTCHA provides privacy-first spam protection with a globally compliant and accessible alternative to Captchas. Find out more via [Altcha](https://altcha.org/).');
	}


	public function getSettingsHtml(): string
	{
		return '<div class="readable"><blockquote class="note tip">Altcha Settings can be updated on the <a href="' . UrlHelper::cpUrl('settings/altcha') . '">Altcha plugin settings page</a>.</blockquote></div>';
	}


	/**
	 * @throws SyntaxError
	 * @throws RuntimeError
	 * @throws Exception
	 * @throws LoaderError
	 */
	public function getFrontEndHtml(\verbb\formie\elements\Form $form, $page = null): string
	{
		return AltchaPlugin::getInstance()->altchaService->renderWidget();
	}


	public function validateSubmission(\verbb\formie\elements\Submission $submission): bool
	{

		$payload = Craft::$app->getRequest()->post('altcha');

		// If the payload is empty, we can't validate
		if (empty($payload)) {
			return false;
		}

		// Verify the solution
		$verified = AltchaPlugin::getInstance()->altchaService->verifySolution($payload);

		if (!$verified) {
			$this->spamReason = Craft::t('altcha', 'Submission failed Altcha verification.');
			return false;
		}

		return true;

	}

}
