<?php
	namespace Adepto\SweetCLI\Base;

	use Colors\Color;

	/**
	 * ColoredLogger
	 * Class for logging stuff to the CLI in various standardized formats.
	 *
	 * @author  bluefirex
	 * @version 1.0
	 * @package as.adepto.sweet-cli.base
	 */
	class ColoredLogger {
		protected $c;

		public function __construct() {
			$this->c = new Color();
		}

		/**
		 * Wrapper for kevinlebrun's color printing function/class.
		 *
		 * @param string $str Message to print
		 *
		 * @return Color       Instance of {@link Colors\Color}
		 */
		protected function c($str) {
			return $this->c->__invoke($str);
		}

		protected function invokePrint(array $messages, $printFunction, $indent = 1) {
			foreach ($messages as $message) {
				$this->$printFunction($message, $indent);
			}
		}

		/**
		 * Print an error line including a cross emoji.
		 *
		 * @param string  $message Message to print
		 * @param integer $indent  Indenting Level, default = 1
		 * @param string  $emoji   A custom emoji to use or empty, to use none
		 */
		protected function printErrorLine($message, $indent = 1, $emoji = '❌') {
			$this->println($this->c((!empty($emoji) ? $emoji . '  ' : '') . $message)->red, $indent);
		}

		/**
		 * Print a success line including a checkmark emoji.
		 *
		 * @param string  $message Message to print
		 * @param integer $indent  Indenting Level, default = 1
		 * @param string  $emoji   A custom emoji to use or empty, to use none
		 */
		protected function printSuccessLine($message, $indent = 1, $emoji = '✅') {
			$this->println($this->c((!empty($emoji) ? $emoji . '  ' : '') . $message)->green, $indent);
		}

		/**
		 * Print an info line including an info emoji.
		 *
		 * @param string  $message Message to print
		 * @param integer $indent  Indenting Level, default = 1
		 * @param string  $color   Color of the message, default = 'dark' | Uses the colors of kevinlebrun/Colors
		 * @param string  $emoji   A custom emoji to use or empty, to use none
		 */
		protected function printInfoLine($message, $indent = 1, $color = 'dark', $emoji = '❕') {
			$this->println($this->c((!empty($emoji) ? $emoji . '  ' : '') . $message)->$color, $indent);
		}

		/**
		 * Print a warning line including a warning emoji.
		 *
		 * @param string  $message Message to print
		 * @param integer $indent  Indenting Level, default = 1
		 */
		protected function printWarningLine($message, $indent = 1) {
			$this->println($this->c($message)->yellow, $indent);
		}

		/**
		 * Print a detail line.
		 * This is used to print out detail information for humans.
		 *
		 * @param string  $message Message to print
		 * @param integer $indent  Indenting Level, default = 1
		 */
		protected function printDetailLine($message, $indent = 1) {
			$this->println($this->c($message)->dark, $indent);
		}

		/**
		 * Print a success line including a reload/update emoji.
		 *
		 * @param string  $message Message to print
		 * @param integer $indent  Indenting Level, default = 1
		 */
		protected function printUpdateLine($message, $indent = 1) {
			$this->println($this->c('🔄  ' . $message)->blue, $indent);
		}

		/**
		 * Print a heading.
		 *
		 * @param string $message Message to pront
		 */
		protected function printHeadingLine($message) {
			$this->println($this->c('=== ' . $message)->bold->green);
		}

		/**
		 * Print a subheading.
		 *
		 * @param string $message Message to print
		 */
		protected function printSubHeadingLine($message) {
			$this->println($this->c('--- ' . $message)->cyan);
		}

		/**
		 * Print a spinner.
		 * Note that in order for it spin you have to call it multiple times.
		 * If you want to hide it, call it with $reset = true.
		 *
		 * Example:
		 * 
		 *     while (doSomethingHeav()) {
		 *         printSpinner()
		 *     }
		 *
		 * printSpinner(false)
		 *
		 * @param  bool|boolean $reset  Pass true to hide id, false to show/update it
		 * @param  string|null  $label  Label to show beside the spinner icon
		 * @param  int|integer  $indent How many levels to indent, defaults to 1
		 */
		protected function printSpinner(bool $reset = false, string $label = null, int $indent = 1) {
			static $spinner = '⠏⠇⠧⠦⠴⠼⠸⠹⠙⠋';
			static $iteration = 0;

			if ($reset) {
				$iteration = 0;
				echo "\r" . str_repeat(' ', mb_strlen($label) + $indent * 4 + 1 + 1) . "\r";

				return;
			}

			echo "\r" . str_repeat(' ', $indent * 4) . mb_substr($spinner, $iteration++ % 10, 1) . ' ' . $label . ' ';
		}

		/**
		 * Print a divider.
		 *
		 * @param bool|boolean $pad  Whether or not to print empty lines before and after the divider
		 * @param string       $char Char to be used, defaults to '-' (also if passed string is empty)
		 */
		protected function printDivider(bool $pad = false, string $char = '-') {
			if ($pad) $this->println();

			if (empty($char)) {
				$char = '-';
			}

			$this->println($this->c(str_repeat($char, 24))->dark);

			if ($pad) $this->println();
		}

		/**
		 * Print a list item.
		 *
		 * @param string      $message Message to print
		 * @param int|integer $indent  Indenting Level, default = 1
		 * @param string      $color   Color of the message, default = 'dark' | Uses the colors of kevinlebrun\Colors
		 * @param string      $char    Char to be used as the list character. If empty, '·' is used.
		 *
		 * @return [type]               [description]
		 */
		protected function printListItem($message, int $indent = 1, string $color = 'dark', string $char = '·') {
			if (empty($char)) {
				$char = '·';
			}

			$this->println($this->c(str_repeat(' ', $indent * 4 - 2) . $char . ' ' . $message)->$color, 0);
		}

		/**
		 * Print something followed by a line break.
		 * Same arguments as {@see print}.
		 */
		protected function println(...$args) {
			$this->print(...$args);
			echo PHP_EOL;
		}

		/**
		 * Print something.
		 *
		 * @param string  $message Message to print
		 * @param integer $indent  Indenting Level, default = 0
		 */
		protected function print($message = '', $indent = 0) {
			if (php_sapi_name() != 'cli') return;

			echo str_repeat(' ', $indent * 4) . $message;
		}
	}
