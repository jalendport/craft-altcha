<?php

namespace jalendport\altcha\controllers\web;

use AltchaOrg\Altcha\Altcha as AltchaClient;
use AltchaOrg\Altcha\ChallengeOptions;
use craft\web\Controller;
use jalendport\altcha\Altcha;
use yii\web\Response;

/**
 * Altcha web controller
 */
class ChallengeController extends Controller
{

    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = true;


	public function actionIndex(): Response
    {

		$altcha = new AltchaClient(Altcha::getInstance()->getSettings()->hmacKey);

		$options = new ChallengeOptions(
			maxNumber: Altcha::getInstance()->getSettings()->complexity,
			expires: (new \DateTimeImmutable())->add(new \DateInterval('PT10S')),
		);

		$challenge = $altcha->createChallenge($options);

		$payload = [
			'algorithm' => $challenge->algorithm,
			'challenge' => $challenge->challenge,
			'maxnumber' => $challenge->maxNumber,
			'salt'      => $challenge->salt,
			'signature' => $challenge->signature,
		];

		return $this->asJson($payload);

    }

}
