<?php

namespace jalendport\altcha\controllers\console;

use Craft;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use Random\RandomException;
use yii\console\ExitCode;
use yii\helpers\BaseConsole;

/**
 * Altcha console controller
 */
class AltchaConsoleController extends BaseConsoleController
{

	/**
	 * @throws RandomException
	 */
    public function actionGenerateHmacKey(): int
    {

		$this->stdout('Generating an HMAC key ... ', BaseConsole::FG_YELLOW);
		$key = base64_encode(random_bytes(32));
		if (!$this->_setEnvVar('ALTCHA_HMAC_KEY', $key)) {
			return ExitCode::UNSPECIFIED_ERROR;
		}
		$this->stdout("done ($key)" . PHP_EOL, BaseConsole::FG_YELLOW);
        return ExitCode::OK;

    }


	/**
	 * Sets an environment variable value in the project’s `.env` file.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 */
	private function _setEnvVar(string $name, mixed $value): bool
	{
		$configService = Craft::$app->getConfig();
		$path = $configService->getDotEnvPath();

		if (!file_exists($path)) {
			if (!$this->interactive || $this->confirm(PHP_EOL . "A .env file doesn't exist at $path. Would you like to create one?", true)) {
				try {
					FileHelper::writeToFile($path, '');
				} catch (\Throwable $e) {
					$this->stderr("Unable to create $path: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
					return false;
				}

				$this->stdout("$path created. Note you still need to set up PHP dotenv for its values to take effect." . PHP_EOL, Console::FG_YELLOW);
			} else {
				$this->stdout(PHP_EOL . 'Action aborted.' . PHP_EOL, Console::FG_YELLOW);
				return false;
			}
		}

		try {
			$configService->setDotEnvVar($name, $value ?? '');
		} catch (\Throwable $e) {
			$this->stderr("Unable to set $name on $path: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
			return false;
		}

		return true;
	}


}
