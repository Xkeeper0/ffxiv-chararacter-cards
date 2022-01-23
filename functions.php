<?php


	/**
	 * Enables an error handler that will throws warnings as RuntimeExceptions
	 * Used to (sigh) catch certain warnings that should be exceptions...
	 */
	function throw_warnings($enable = true) {
		static $is_enabled = false;

		if ($enable && !$is_enabled) {
			$enabled = true;
			set_error_handler(
				function ($severity, $message, $file, $line) {
					throw new \RuntimeException($message);
				},
				E_WARNING | E_USER_WARNING
			);
			return true;
		} elseif ($enable && $is_enabled) {
			$enabled = false;
			restore_error_handler();
			return false;
		}
	}
