<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Serves signed Altcha challenges to the widget.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\controllers;

use craft\web\Controller;
use jalendport\altcha\Altcha;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * The challenge endpoint the widget's `challenge` URL points at.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class ChallengeController extends Controller
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $defaultAction = 'index';

    /**
     * @inheritdoc
     */
    protected array|int|bool $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * Issues a fresh signed challenge as JSON.
     *
     * @return Response the challenge payload
     * @throws ServerErrorHttpException if the HMAC key is misconfigured
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function actionIndex(): Response
    {
        $challenge = Altcha::$plugin->altcha->createChallenge();

        if ($challenge === null) {
            throw new ServerErrorHttpException(
                'Altcha is misconfigured: the HMAC key is empty or its environment reference could not be resolved. Set a valid key in the Altcha plugin settings.'
            );
        }

        return $this->asJson($challenge->toArray());
    }
}
