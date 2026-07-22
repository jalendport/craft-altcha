<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Native user forms integration: verifies registration, login, and
 * forgot-password submissions.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\integrations;

use Craft;
use jalendport\altcha\Altcha;
use yii\base\ActionEvent;
use yii\web\BadRequestHttpException;

/**
 * Enforces Altcha on Craft's own front-end user forms: registration, login, and
 * the forgot-password form, each behind its own setting.
 *
 * Because a before-action hook runs before any model exists, there's nothing to
 * attach a validation error to; a failed check throws a 400 instead, which is
 * the established pattern for captcha plugins protecting core controllers.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class Users
{
    // Static Methods
    // =========================================================================

    /**
     * Verifies the posted Altcha solution before a protected user action runs.
     *
     * @param ActionEvent $event the controller's before-action event
     * @return void
     * @throws BadRequestHttpException if the solution is missing or invalid
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public static function beforeAction(ActionEvent $event): void
    {
        $request = Craft::$app->getRequest();

        // Console requests have no post data at all, so a console command
        // touching users would otherwise blow up reading the payload.
        if ($request->getIsConsoleRequest()) {
            return;
        }

        if (!$request->getIsSiteRequest()) {
            return;
        }

        if (!$request->getIsPost()) {
            return;
        }

        if (!self::_isProtected($event->action->id)) {
            return;
        }

        $payload = (string)$request->post('altcha');

        if ($payload === '' || !Altcha::$plugin->altcha->verifySolution($payload)) {
            throw new BadRequestHttpException(Craft::t('altcha', 'Altcha verification failed. Please try again.'));
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns whether the given user action is one this site protects.
     *
     * `save-user` covers both public registration and a logged-in user editing
     * their own account; only the former should be held to a challenge. Craft
     * decides new-vs-edit from a falsy `userId` (`$isNewUser = !$userId` in its
     * own controller), so mirror that exactly — a strict `=== null` check would
     * miss a registration posted with an empty or `0` `userId` and skip
     * verification on it.
     *
     * @param string $actionId the action being run
     * @return bool whether the action requires a solution
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private static function _isProtected(string $actionId): bool
    {
        $settings = Altcha::$plugin->altcha->getSettings();
        $request = Craft::$app->getRequest();

        return match ($actionId) {
            'save-user' => $settings->enableUserRegistration && !$request->post('userId'),
            'login' => $settings->protectLogin,
            'send-password-reset-email' => $settings->protectForgotPassword,
            default => false,
        };
    }
}
