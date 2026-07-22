<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Verbb Comments integration: verifies front-end comment submissions.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\integrations;

use Craft;
use craft\events\ModelEvent;
use jalendport\altcha\Altcha;
use jalendport\altcha\events\VerifyCommentEvent;
use verbb\comments\elements\Comment;
use yii\base\Event;

/**
 * Enforces Altcha on new comments posted through Verbb Comments' front-end
 * form. Everything else a visitor can do to a comment — editing it, trashing
 * it, voting, flagging — passes through untouched, as does anything saved from
 * the control panel or a console command.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class Comments
{
    // Constants
    // =========================================================================

    /**
     * @event VerifyCommentEvent The event that is triggered before a comment's
     * Altcha solution is verified, allowing listeners to skip verification.
     * @since 1.0.0
     */
    public const EVENT_BEFORE_VERIFY_COMMENT = 'beforeVerifyComment';

    // Static Methods
    // =========================================================================

    /**
     * Verifies the posted Altcha solution before a new front-end comment saves,
     * and blocks the save with a validation error when it doesn't check out.
     *
     * @param ModelEvent $event the comment's before-save event
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public static function beforeSaveComment(ModelEvent $event): void
    {
        $request = Craft::$app->getRequest();

        // Console requests have no post data at all, so a programmatic save
        // would otherwise blow up reading the payload.
        if ($request->getIsConsoleRequest()) {
            return;
        }

        /** @var Comment $comment */
        $comment = $event->sender;

        if ($comment->scenario !== Comment::SCENARIO_FRONT_END) {
            return;
        }

        // Only brand-new comments carry a widget. Edits keep their id, and the
        // front-end trash action saves through this same event with a delete
        // action — neither should be held to a challenge.
        if ($comment->id) {
            return;
        }

        if ($comment->getAction() !== Comment::ACTION_SAVE) {
            return;
        }

        $verifyEvent = new VerifyCommentEvent(['comment' => $comment]);
        Event::trigger(self::class, self::EVENT_BEFORE_VERIFY_COMMENT, $verifyEvent);

        if ($verifyEvent->skipVerification) {
            return;
        }

        $payload = (string)$request->post('altcha');

        if ($payload === '' || !Altcha::$plugin->altcha->verifySolution($payload)) {
            $event->isValid = false;
            $comment->addError('altcha', Craft::t('altcha', 'Altcha verification failed. Please try again.'));
        }
    }
}
