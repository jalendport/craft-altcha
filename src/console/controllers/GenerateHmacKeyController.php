<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Console command that generates and stores an Altcha HMAC key.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use Random\RandomException;
use Throwable;
use yii\console\ExitCode;

/**
 * Generates a random HMAC key and writes it to the project's `.env` file as
 * `ALTCHA_HMAC_KEY`, ready to reference from the plugin's `hmacKey` setting as
 * `$ALTCHA_HMAC_KEY`.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class GenerateHmacKeyController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Generates an HMAC key and stores it in the `.env` file.
     *
     * @return int the exit code
     * @throws RandomException if a source of randomness can't be found
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function actionIndex(): int
    {
        $this->stdout('Generating an HMAC key ... ', Console::FG_YELLOW);

        $key = base64_encode(random_bytes(32));

        if (!$this->_setEnvVar('ALTCHA_HMAC_KEY', $key)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("done ($key)" . PHP_EOL, Console::FG_YELLOW);

        return ExitCode::OK;
    }

    // Private Methods
    // =========================================================================

    /**
     * Sets an environment variable value in the project's `.env` file, creating
     * the file first if it doesn't exist yet.
     *
     * @param string $name the environment variable name
     * @param string $value the environment variable value
     * @return bool whether the value was written
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _setEnvVar(string $name, string $value): bool
    {
        $configService = Craft::$app->getConfig();
        $path = $configService->getDotEnvPath();

        if (!file_exists($path)) {
            if (!$this->interactive || $this->confirm(PHP_EOL . "A .env file doesn't exist at $path. Would you like to create one?", true)) {
                try {
                    FileHelper::writeToFile($path, '');
                } catch (Throwable $e) {
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
            $configService->setDotEnvVar($name, $value);
        } catch (Throwable $e) {
            $this->stderr("Unable to set $name on $path: {$e->getMessage()}" . PHP_EOL, Console::FG_RED);
            return false;
        }

        return true;
    }
}
