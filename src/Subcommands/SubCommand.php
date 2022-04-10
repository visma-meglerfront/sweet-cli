<?php
	namespace Adepto\SweetCLI\Subcommands;

	use Colors\Color;

	use GetOptionKit\Exception\RequireValueException;
	use GetOptionKit\OptionCollection;

	use Adepto\SweetCLI\Base\{
		ColoredLogger,
		CLIApplication
	};

	use Adepto\SweetCLI\Exceptions\{
		ConflictException
	};
	
	use GetOptionKit\OptionPrinter\OptionPrinter;
	use stdClass;
	
	/**
	 * SubCommand
	 * A subcommand in the command line interface.
	 *
	 * @author  bluefirex
	 * @version 1.1
	 * @package as.adepto.sweet-cli.base
	 */
	abstract class SubCommand extends ColoredLogger {
		const COMMAND = '';

		protected CLIApplication $app;
		protected stdClass $options;
		protected array $arguments;

		public function __construct(CLIApplication $app, stdClass $options, array $arguments = []) {
			parent::__construct();

			$this->app = $app;
			$this->options = $options;
			$this->arguments = $arguments;
		}

		/**
		 * Does this subcommand have conflicting options?
		 * You have to override this to return a different boolean if you have
		 * your own checks. Default is false.
		 *
		 * @return boolean
		 */
		public function hasConflictingOptions(): bool {
			return false;
		}

		/**
		 * Check if this subcommand has conflicting options and throw an exception if it has.
		 *
		 * @throws ConflictException If conflicting options are present. hasConflictingOptions() is being used for this.
		 */
		protected function checkConflictingOptions() {
			if ($this->hasConflictingOptions()) {
				throw new ConflictException('Conflicting options.');
			}
		}

		/**
		 * Does $values contain a duplicate boolean $bool?
		 *
		 * @param boolean $bool   Bool to check
		 * @param array   $values Array to search in
		 *
		 * @return bool
		 */
		protected function hasDuplicateBoolean(bool $bool, array $values): bool {
			return (array_count_values(array_map('strval', $values))[strval($bool)] ?? 0) > 1;
		}

		/**
		 * Does this subcommand have options?
		 *
		 * @return bool
		 */
		public function hasOptions(): bool {
			return count((array) $this->options) > 0;
		}

		/**
		 * Does this subcommand have option $option set?
		 *
		 * @param string  $option Option
		 *
		 * @return bool
		 */
		public function hasOption(string $option): bool {
			return property_exists($this->options, $option);
		}

		/**
		 * Get the value of $option.
		 * Caution: For flag options, this returns true.
		 *
		 * @param string $option Option
		 *
		 * @return mixed
		 */
		public function getOption(string $option) {
			return $this->options->$option ?? null;
		}

		/**
		 * Set an option.
		 *
		 * @param string $option Option
		 * @param mixed  $value  Value
		 */
		public function setOption(string $option, $value): SubCommand {
			$this->options->$option = $value;

			return $this;
		}
		
		/**
		 * Require an option to be set.
		 *
		 * @param string $option  Option to be required
		 * @param string $message Exception message if $option isn't set
		 *
		 * @throws RequireValueException
		 */
		protected function requireOption(string $option, string $message) {
			if (!$this->hasOption($option)) {
				throw new RequireValueException($message);
			}
		}

		/**
		 * Get the app this subcommand has been called from
		 *
		 * @return CLIApplication
		 */
		public function getApp(): CLIApplication {
			return $this->app;
		}
		
		/**
		 * Callback to be run before the main function runs.
		 *
		 * @throws ConflictException
		 */
		public function onBeforeRun() {
			$this->checkConflictingOptions();
		}

		/**
		 * Run this subcommand!
		 * This will be called automatically by the CLIApplication that instantiated this.
		 */
		public abstract function run();

		/**
		 * Does this subcommand allow being run without any options?
		 *
		 * @return boolean
		 */
		public static function allowsEmptyOptions(): bool {
			return true;
		}

		public static function printHelp(array $config, OptionPrinter $printer) {
			$c = new Color();
			$specs = self::getOptionKitSpecs();

			if (!empty(static::COMMAND)) {
				echo $c($config['title_short'] . ': ')->cyan;
				echo $c(static::COMMAND)->green->underline . PHP_EOL;
			} else {
				echo $c($config['title_long'])->cyan . PHP_EOL;
			}

			if (static::getDescription()) {
				echo $c(wordwrap(static::getDescription(), SubCommandOptionPrinter::$screenWidth, "\n")) . PHP_EOL;

				if (count($specs) > 1) {
					echo PHP_EOL;
				}
			}

			echo $printer->render($specs);
		}

		/**
		 * Get the specifications for this subcommand used by the CLIApplication
		 * for parsing correctly.
		 *
		 * @return array
		 */
		public static function getSpecs(): array {
			return [
				'desc'		=>	static::getDescription(),
				'options'	=>	static::getOptions(),
				'hidden'	=>	empty(static::getDescription()),
				'class'		=>	static::class
			];
		}

		/**
		 * Get the specifications for this subcommand as an OptionCollection
		 * for use with GetOptionKit.
		 *
		 * @return OptionCollection
		 */
		public static function getOptionKitSpecs(): OptionCollection {
			$spec = new OptionCollection();

			foreach (static::getOptions() as $opt => $flags) {
				$flags = (object) $flags;
				$specOpt = $spec->add($opt, $flags->desc ?? 'no description provided');

				if (isset($flags->default)) {
					$specOpt->defaultValue($flags->default);
				}

				if (isset($flags->type)) {
					$specOpt->isa($flags->type);
				}

				if (isset($flags->validValues) && is_array($flags->validValues)) {
					$specOpt->validValues($flags->validValues);
				}
			}

			// Every subcommand has a help page
			$spec->add('h|help', 'Print Help Page');

			return $spec;
		}

		/**
		 * Get valid options for this subcommand.
		 * Format:
		 * 'name'  =>  [
		 *     'desc'           =>  'Some Description what this option does',
		 *     'default'        =>  'some_default_value',
		 *     'type'           =>  'boolean|string|…',
		 *     'validValues'    =>  [
		 *         'value_1',
		 *         'value_2'
		 *     ]
		 * ]
		 *
		 * Valid name formats:
		 *     name     =>  --name
		 *     name:    =>  --name value (required)
		 *     name+    =>  --name value1 --name value2 (multiple values)
		 *     name+:   =>  --name value1 --name value2 (multiple values, required)
		 *     name?    =>  --name || '' (optional)
		 *     n|name   =>  --name || -n (shortopt)
		 *
		 *     …and permutations of these notations…
		 *
		 * @return array
		 */
		public abstract static function getOptions(): array;

		/**
		 * Get the name of the subcommand.
		 *
		 * @return string
		 */
		public abstract static function getCommand(): string;

		/**
		 * Get the description of this subcommand.
		 * If empty this subcommand doesn't have a description.
		 *
		 * @return string
		 */
		public abstract static function getDescription(): string;
	}
