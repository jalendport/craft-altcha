<?php

namespace jalendport\altcha\services;

use Craft;
use craft\base\Element;
use craft\base\Event;
use jalendport\altcha\integrations\Comments;
use jalendport\altcha\integrations\formie\Altcha as FormieIntegration;
use yii\base\Component;

/**
 * Integrations service
 */
class Integrations extends Component
{

	public function addAll(): void
	{
		$this->addFormieIntegration();
		$this->addCommentsIntegration();
	}


	private function addFormieIntegration(): void
	{

		if (!class_exists(\verbb\formie\services\Integrations::class)) {
			return;
		}

		Event::on(
			\verbb\formie\services\Integrations::class,
			\verbb\formie\services\Integrations::EVENT_REGISTER_INTEGRATIONS,
			function (\verbb\formie\events\RegisterIntegrationsEvent $event) {
				$event->captchas[] = FormieIntegration::class;
			}
		);

	}


	private function addCommentsIntegration(): void
	{

		if (!class_exists(\verbb\comments\elements\Comment::class)) {
			return;
		}

		Event::on(
			\verbb\comments\elements\Comment::class,
			Element::EVENT_BEFORE_SAVE,
			function (\craft\events\ModelEvent $event) {
				Comments::beforeSaveComment($event);
			}
		);

	}

}
