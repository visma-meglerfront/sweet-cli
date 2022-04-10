<?php
	namespace Adepto\SweetCLI\Base;

	use Colors\Color;

	/**
	 * CLIFunctions
	 * Common functions to be used in CLI context
	 *
	 * @author  bluefirex
	 * @version 1.0
	 * @package as.adepto.sweet-cli.base
	 */
	abstract class CLIFunctions {
		private static int $verbosityLevel = 0;
		private static int $verbosityLevelBackup = 0;

		/**
		 * Set the verbosity level required for any output
		 *
		 * @param int $level
		 */
		public static function setVerbosityLevel(int $level): void {
			if (!is_numeric($level)) {
				self::$verbosityLevel = 0;
				self::$verbosityLevelBackup = 0;
			} else {
				self::$verbosityLevel = $level;
				self::$verbosityLevelBackup = $level;
			}
		}

		/**
		 * Disable all verbosity.
		 */
		public static function disableVerbosity(): void {
			self::$verbosityLevel = 0;
		}

		/**
		 * Re-enable last verbosity level.
		 */
		public static function enableVerbosity(): void {
			self::$verbosityLevel = self::$verbosityLevelBackup;
		}

		/**
		 * Get the current verbosity level.
		 *
		 * @return int
		 */
		public static function getVerbosityLevel(): int {
			return self::$verbosityLevel;
		}

		/**
		 * Log a verbose message on a specific level to stdOut
		 *
		 * @param int     $level       Verbosity Level
		 * @param string  $message     Message
		 * @param boolean $wrap        Whether to wrap the message at maximum screen width
		 * @param boolean $printPrefix Whether to print the verbosity prefix, i.e. "-vv" for $level 2
		 */
		public static function verboseOnLevel(int $level, string $message, bool $wrap = false, bool $printPrefix = true) {
			$c = new Color();

			if (self::getVerbosityLevel() >= $level) {
				$maxLevel = self::getVerbosityLevel();

				// Prefix: "-v:" , "-vv:"
				$prefix = '-' . str_repeat('v', $level) . ':';

				// Whitespace between the prefix and the actual message
				// MAX_LEVEL + OVERHEAD - STRLEN(PREFIX) + 1 (SPACE)
				$prefixWhitespace = str_repeat(' ', $maxLevel + 2 - strlen($prefix) + 1);
				$prefixWithOverheadAsWhitespace = str_repeat(' ', strlen($prefix . $prefixWhitespace));

				// Clean Linebreaks
				$message = str_replace("\n", "\n" . $prefixWithOverheadAsWhitespace, $message);

				// Prefix Whitespace
				$message = $prefixWhitespace . $message;

				if ($printPrefix) {
					$message = $prefix . $message;
				} else {
					$message = str_repeat(' ', strlen($prefix)) . $message;
				}

				// Wrapping
				if ($wrap) {
					$message = wordwrap($message, $wrap, "\n" . $prefixWithOverheadAsWhitespace);
				}

				// Print the actual message
				echo $c($message)->dark . PHP_EOL;
			}
		}

		/**
		 * Check if the current OS is Darwin (macOS).
		 * Warning: Since this uses PHP_OS it might return the OS
		 * this installation of PHP was compiled on.
		 *
		 * @return bool
		 */
		public static function isDarwin(): bool {
			return strtolower(PHP_OS) == 'darwin';
		}

		/**
		 * Alias for {@see isDarwin()}.
		 *
		 * @return bool
		 */
		public static function isOSX(): bool {
			return self::isDarwin();
		}

		/**
		 * Check if the current OS is Windows.
		 * Warning: Since this uses PHP_OS it might return the OS
		 * this installation of PHP was compiled on.
		 *
		 * @return bool
		 */
		public static function isWindows(): bool {
			return substr(strtolower(PHP_OS), 0, 3) == 'WIN';
		}
	}
