<?php

namespace jalendport\altcha\models;

use Craft;
use craft\base\Model;

/**
 * Altcha settings
 */
class Settings extends Model
{

	public string $verificationMethod = 'custom';

	public string $hmacKey = '';

	public string $sentinelEndpointUrl = '';
	public string $sentinelApiKey = '';
	public string $sentinelApiSecret = '';

	public bool $registerWidgetJs = true;

	public int $complexity = 50000;


	public function rules(): array
	{

		return [
			['hmacKey',
				'required',
				'when' => function($model) {
					return $model->verificationMethod === 'custom';
				},
				'message' => Craft::t('altcha', 'HMAC Key cannot be blank.')
			],
			['hmacKey', 'string'],
			['hmacKey', 'default', 'value' => ''],
			['sentinelEndpointUrl',
				'required',
				'when' => function($model) {
					return $model->verificationMethod === 'sentinel';
				},
				'message' => Craft::t('altcha', 'Sentinel Endpoint URL cannot be blank.')
			],
			['sentinelEndpointUrl', 'string'],
			['sentinelEndpointUrl', 'default', 'value' => ''],
			['sentinelApiKey',
				'required',
				'when' => function($model) {
					return $model->verificationMethod === 'sentinel';
				},
				'message' => Craft::t('altcha', 'Sentinel API Key cannot be blank.')
			],
			['sentinelApiKey', 'string'],
			['sentinelApiKey', 'default', 'value' => ''],
			['sentinelApiSecret',
				'required',
				'when' => function($model) {
					return $model->verificationMethod === 'sentinel';
				},
				'message' => Craft::t('altcha', 'Sentinel API Secret cannot be blank.')
			],
			['sentinelApiSecret', 'string'],
			['sentinelApiSecret', 'default', 'value' => ''],
		];

	}

}
