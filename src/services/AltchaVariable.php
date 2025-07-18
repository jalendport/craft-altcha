<?php

namespace jalendport\altcha\services;

use Craft;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;
use yii\base\Component;
use yii\base\Exception;

/**
 * Altcha Variable service
 */
class AltchaVariable extends Component
{

	/**
	 * @throws SyntaxError
	 * @throws RuntimeError
	 * @throws Exception
	 * @throws LoaderError
	 */
	public static function renderWidget(array $options = []): Markup
	{
		return \jalendport\altcha\Altcha::getInstance()->altchaService->renderWidget($options);
	}

	public static function getChallengeUrl(): string
	{
		return \jalendport\altcha\Altcha::getInstance()->altchaService->getChallengeUrl();
	}

}
