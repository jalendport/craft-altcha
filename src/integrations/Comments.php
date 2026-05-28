<?php
namespace jalendport\altcha\integrations;

use Craft;
use craft\base\Component;
use craft\events\CancelableEvent;
use craft\events\ModelEvent;
use jalendport\altcha\Altcha;
use verbb\comments\elements\Comment;
use yii\base\Event;

class Comments extends Component
{

	public const EVENT_BEFORE_VERIFY_COMMENT = 'beforeVerifyComment';

	public static function beforeSaveComment(ModelEvent $event): void
	{

		/** @var Comment $comment */
		$comment = $event->sender;
		$currentScenario = $comment->scenario;

		// Prevent Altcha from validating in the CP
		if ($currentScenario !== Comment::SCENARIO_FRONT_END) {
			return;
		}

		// Allow listeners to bypass verification (e.g. non-public comment forms)
		$verifyEvent = new CancelableEvent(['sender' => $comment]);
		Event::trigger(self::class, self::EVENT_BEFORE_VERIFY_COMMENT, $verifyEvent);

		if (!$verifyEvent->isValid) {
			return;
		}

		$payload = Craft::$app->getRequest()->post('altcha');

		// If the payload is empty, we can't validate
		if (empty($payload)) {
			$event->isValid = false;
			$comment->addError('altcha', Craft::t('altcha', 'Please complete the Altcha challenge.'));
			return;
		}

		// Verify the solution
		$verified = Altcha::getInstance()->altchaService->verifySolution($payload);

		if (!$verified) {
			$comment->status = Comment::STATUS_SPAM;
		}

	}

}
