<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Event fired before a front-end comment is verified against Altcha.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\events;

use verbb\comments\elements\Comment;
use yii\base\Event;

/**
 * Event fired before the Comments integration verifies a comment's Altcha
 * solution, so a listener can wave through comments posted from a form that
 * doesn't carry a widget (a members-only form, say).
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class VerifyCommentEvent extends Event
{
    // Public Properties
    // =========================================================================

    /**
     * @var Comment|null the comment about to be verified
     * @since 1.0.0
     */
    public ?Comment $comment = null;

    /**
     * @var bool whether to skip verification and save the comment as-is
     * @since 1.0.0
     */
    public bool $skipVerification = false;
}
