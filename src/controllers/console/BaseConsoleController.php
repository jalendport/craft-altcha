<?php

namespace jalendport\altcha\controllers\console;

use craft\console\Controller;
use yii\helpers\BaseConsole;

abstract class BaseConsoleController extends Controller
{

	/**
	 * Writes an error to the console
	 * @param string $msg
	 */
	protected function _writeError(string $msg): void
	{

		$this->stderr('Error: ', BaseConsole::BOLD, BaseConsole::FG_RED);
		$this->stderr(print_r($msg, true) . PHP_EOL);

	}


	/**
	 * Writes a line to the console
	 * @param string|null $msg
	 */
	protected function _writeLine(string $msg = null): void
	{
		$this->stdout(print_r($msg, true) . PHP_EOL);
	}


	/**
	 * Dumps a var to the console
	 * @param mixed $var
	 */
	protected function _dump(mixed $var): void
	{
		$this->stdout(print_r('Dump:', true) . PHP_EOL, BaseConsole::BOLD);
		$this->stdout(print_r($var, true) . PHP_EOL, BaseConsole::FG_GREEN);
	}

}
