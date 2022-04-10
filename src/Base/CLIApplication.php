<?php
	namespace Adepto\SweetCLI\Base;

	// GetOptionKit
	use Adepto\SweetCLI\Subcommands\SubCommand;
	use Exception;
	use GetOptionKit\OptionCollection;
	use GetOptionKit\ContinuousOptionParser;

	// Colors
	use Colors\Color;

	use Adepto\SweetCLI\Subcommands\SubCommandOptionPrinter;
	use Adepto\SweetCLI\Exceptions\ConflictException;
	use GetOptionKit\OptionResult;
	use Throwable;
	
	/**
	 * CLIApplication
	 * An application running in CLI. Provides all the logic
	 * as well as parsing bindings. Works with {@see \Adepto\SweetCLI\Subcommands\SubCommand}.
	 *
	 * @author  bluefirex
	 * @version 2.0
	 * @package as.adepto.sweet-cli.base
	 *
	 * @method static getTitle()
	 * @method static getShortTitle()
	 */
	abstract class CLIApplication {
		protected ContinuousOptionParser $parser;
		protected ?string $appPath;
		protected array $subCommandClasses;
		protected array $aliases;
		protected Color $c;

		/**
		 * Create a CLI application.
		 *
		 * @param string|null  $appPath          The path to the file that is being run as the script
		 * @param bool         $handleExceptions Whether to use the built-in exception handler
		 */
		public function __construct(?string $appPath = null, bool $handleExceptions = true) {
			$this->subCommandClasses = [];
			$this->aliases = [];
			$this->appPath = $appPath;
			$this->parser = new ContinuousOptionParser($this->getAppSpecs());
			$this->c = new Color();

			// Hijack exception handling
			if ($handleExceptions) {
				set_exception_handler([$this, '__handleException']);
			}
		}

		protected function c(...$args) {
			return $this->c->__invoke(...$args);
		}

		/**
		 * Handle an exception/error that has happened.
		 *
		 * @param Throwable $t Error or Exception
		 */
		public function __handleException(Throwable $t): void {
			echo $this->c('*** ' . get_class($t) . ':')->red . PHP_EOL;
			echo $this->c(wordwrap('    ' . $t->getMessage(), SubCommandOptionPrinter::$screenWidth, "\n    "))->red . PHP_EOL;
			
			if (!$t instanceof ConflictException) {
				echo PHP_EOL;

				echo $this->c('Stacktrace:')->dark . PHP_EOL;
				echo implode("\n", array_map([$this, 'highlightTraceLine'], explode("\n", $t->getTraceAsString()))) . PHP_EOL;
			}
		}

		protected function highlightTraceLine(string $line): string {
			return preg_replace_callback('~^(#[0-9]+) ({main}|([\S ]+)\(([0-9]+)\): ([\S\s]+))~', function($m) {
				$stack = $m[1];
				
				if ($m[2] == '{main}') {
					$line = 0;
					$file = '{main}';
					$command = '';
				} else {
					$file = $m[3];
					$line = $m[4];
					$command = $m[5];
				}
				
				return $this->c($stack)->dark . ' ' .
					   $this->c($file)->dark .
					   ($command ? 
					   		PHP_EOL .
					   		$this->c('   ' . $command) .
					   		$this->c(' on line ' . $line)->dark .
					   		PHP_EOL
					   	: '');
			}, $line);
		}
		
		/**
		 * Add a subcommand class name.
		 * You are responsible for loading it yourself!
		 *
		 * @param string $class Class Name, including any namespaces
		 *
		 * @return CLIApplication        Convenience for chaining
		 *
		 * @throws Exception If $class does not extend SubCommand
		 */
		public function addSubCommand(string $class): CLIApplication {
			if (!class_exists($class)) {
				throw new Exception('Cannot find subcommand class: ' . $class, 1);
			}

			$baseClassName = 'Adepto\\SweetCLI\\Subcommands\\SubCommand';

			if (!is_subclass_of($class, $baseClassName)) {
				throw new Exception('Class "' . $class . '" does not extend "' . $baseClassName . '"');
			}

			$this->subCommandClasses[] = $class;
			$this->subCommandClasses = array_unique($this->subCommandClasses);

			return $this;
		}

		/**
		 * Add an alias or override, if it exists already
		 *
		 * @param string      $alias Alias, i.e. `composer`
		 * @param string|null $path  Actual binary path, i.e. `/usr/local/bin/composer` or `composer` (if you want to rely on the user's PATH)
		 *                           Leave null to map directly to the user's PATH.
		 */
		public function addAlias(string $alias, string $path = null) {
			$this->aliases[$alias] = $path ?? $alias;
		}

		/**
		 * Get the subcommand definitions in an easy to walk through array
		 *
		 * @return array
		 */
		protected function getSubCommandDefinitions(): array {
			$def = [];

			foreach ($this->subCommandClasses as $class) {
				$def[$class::getCommand()] = $class::getSpecs();
			}

			return $def;
		}

		/**
		 * Get the subcommand specs in a format for GetOptionKit.
		 *
		 * @return array
		 */
		protected function getSubCommandSpecs(): array {
			$subCommands = $this->getSubCommandDefinitions();
			ksort($subCommands);

			foreach ($subCommands as $command => $config) {
				/** @noinspection PhpArrayAccessCanBeReplacedWithForeachValueInspection */
				$spec = &$subCommands[$command]['options'];
				$spec = $config['class']::getOptionKitSpecs();
			}

			return $subCommands;
		}

		/**
		 * Get global app options for GetOptionKit
		 *
		 * @return OptionCollection
		 */
		protected function getAppSpecs(): OptionCollection {
			$appSpecs = new OptionCollection();
			$appSpecs->add('v|verbose', 'Verbose Mode · supply multiple times to increase verbosity level (i.e. -vv)')->incremental();
			$appSpecs->add('h|help', 'Print Help Page');

			return $appSpecs;
		}
		
		/**
		 * Parse global app options out of an argv array
		 *
		 * @param array $argv argv as array
		 *
		 * @return OptionResult
		 *
		 * @throws Exception If parsing failed
		 */
		protected function parseAppOptions(array $argv): OptionResult {
			return $this->parser->parse($argv);
		}

		/**
		 * Set up the application before running it.
		 */
		public function setup(): void {
			// Nothing by default
		}
		
		/**
		 * Run the application with the given $argv.
		 * This function will always exit the script with an appropriate status code.
		 *
		 * @param array $argv arguments as array
		 *
		 * @throws Exception If parsing failed
		 */
		public function run(array $argv) {
			if (php_sapi_name() !== 'cli') {
				throw new Exception('Unsupported operation.');
			}

			// Set up first
			$this->setup();

			// Load subcommand definitions
			$subCommands = $this->getSubCommandSpecs();

			// Parse!
			$arguments = [];
			$subCommandOptions = [];

			try {
				$appOptions = $this->parseAppOptions($argv);
			} catch (Exception $e) {
				echo $this->c($e->getMessage())->red . PHP_EOL;

				exit(1);
			}

			// Set Verbosity
			if (isset($appOptions['verbose'])) {
				CLIFunctions::setVerbosityLevel(abs($appOptions['verbose']->getValue()));
			}

			// Parse subcommands and options
			while (!$this->parser->isEnd()) {
				// Get the current argument
				$currentArg = $this->parser->getCurrentArgument();

				// If the argument is a registered subcommand, parse it
				if (in_array($currentArg, array_keys($subCommands))) {
					$this->parser->advance();

					try {
						$this->parser->setSpecs($subCommands[$currentArg]['options']);
						$subCommandOptions[$currentArg] = $this->parser->continueParse();
					} catch (Exception $e) {
						echo $this->c($currentArg . ': ' . $e->getMessage())->red . PHP_EOL;
						exit;
					}
				} else {
					$arguments[] = $this->parser->advance();
				}
			}

			// Handle aliases
			if (array_key_exists($arguments[0] ?? '__undefined', $this->aliases)) {
				$invoke = $arguments[0];

				CLIFunctions::verboseOnLevel(1, 'Using alias: ' . $arguments[0] . ' → ' . $this->aliases[$invoke]);

				// Change directory, if app path is present
				if ($this->appPath) {
					chdir(dirname($this->appPath));
				}

				$invoke = $this->aliases[$invoke];
				$extraArgs = array_slice($arguments, 1, null);

				// Run!
				system($invoke . ' ' . implode(' ', $extraArgs) . ' > `tty`');

				// Exit because aliases have priority
				exit(0);
			}

			// Handle help
			if (count($subCommandOptions) == 0 || (count($arguments) && $arguments[0] == 'help') || isset($appOptions['help'])) {
				$printer = new SubCommandOptionPrinter();

				echo $this->c(static::getTitle())->cyan . PHP_EOL;
				echo $this->c('Available subcommands:') . PHP_EOL . PHP_EOL;

				if (count($subCommands)) {
					foreach ($subCommands as $subCommand => $config) {
						if ($config['hidden']) continue;

						echo $this->c('    ')->green;
						echo $this->c($subCommand)->green->underline . PHP_EOL;

						if (isset($config['desc'])) {
							echo $this->c(wordwrap('    ' . $config['desc'], SubCommandOptionPrinter::$screenWidth, "\n    ")) . PHP_EOL;

							if (count($config['options']) > 1) { // more than just help
								echo PHP_EOL;
							}
						}

						$printerOutput = explode("\n", $printer->render($config['options']));
						$printerOutput = array_map(function($val) {
							return '    ' . $val;
						}, $printerOutput);

						echo implode("\n", $printerOutput);
						echo PHP_EOL;
					}
				} else {
					echo $this->c('    No commands available.')->dark . PHP_EOL;
				}

				// echo PHP_EOL;
				echo $this->c('Available global options:') . PHP_EOL . PHP_EOL;
				echo $printer->render($this->getAppSpecs());

				if (count($this->aliases)) {
					echo $this->c('Available aliases:') . PHP_EOL;

					$longestKeyLength = max(array_map('mb_strlen', array_keys($this->aliases)));

					foreach ($this->aliases as $alias => $path) {
						echo '    ' . $this->c->bold($alias) .
						              str_repeat(' ', $longestKeyLength - mb_strlen($alias)) .
						              ' → ' .
						              $path .
						              PHP_EOL;
					}

					echo PHP_EOL;
				}

				echo $this->c('Legend: ') . PHP_EOL;
				echo '    ' . $this->c->bold('*') . '    ' . $this->c(' → required') . PHP_EOL;
				echo '    ' . $this->c->dark('[= …]') . $this->c(' → default value') . PHP_EOL;

				// Exit because help has priority
				exit(0);
			}

			// Fetch options
			foreach ($subCommandOptions as $subCommand => $options) {
				$optionsArray = [];

				foreach ($options as $option) {
					$optionsArray[$option->getId()] = $option->getValue();
				}
				
				/**
				 * This is the class the subcommand is based on.
				 *
				 * @var class-string<SubCommand> $class
				 */
				$class = $subCommands[$subCommand]['class'];

				// Log some stuff:
				// Class Name
				CLIFunctions::verboseOnLevel(1, 'Class: ' . $class);

				// Options
				if (count($optionsArray)) {
					CLIFunctions::verboseOnLevel(2, 'Options:');
					
					if (extension_loaded('json')) {
						CLIFunctions::verboseOnLevel(2, json_encode($optionsArray, JSON_PRETTY_PRINT), SubCommandOptionPrinter::$screenWidth, false);
					} else {
						CLIFunctions::verboseOnLevel(2, 'Install php-json to view detailed information.');
					}
				}

				// arguments
				if (count($arguments)) {
					CLIFunctions::verboseOnLevel(2, 'Arguments:');
					
					if (extension_loaded('json')) {
						CLIFunctions::verboseOnLevel(2, json_encode($arguments, JSON_PRETTY_PRINT), SubCommandOptionPrinter::$screenWidth, false);
					} else {
						CLIFunctions::verboseOnLevel(2, 'Install php-json to view detailed information.');
					}
				}

				/*
					If '--help' is set, print help page and do nothing else.
					(You have to set your priorities, yo.)
				 */
				if (isset($options['help']) || (!$class::allowsEmptyOptions() && count($optionsArray) == 0)) {
					$class::printHelp([
						'title_short'       =>  static::getShortTitle(),
						'title_long'        =>  static::getTitle()
					], new SubCommandOptionPrinter());
					
					exit(0);
				}

				/*
					Else continue.
				 */
				try {
					/**
					 * Actual subcommand object.
					 *
					 * @var SubCommand $subCmdObj
					 */
					$subCmdObj = new $class($this, (object) $optionsArray, $arguments);
					$subCmdObj->onBeforeRun();
					$subCmdObj->run();

					exit(0);
				} catch (\Exception $e) {
					echo $this->c($subCommand . ': ' . $e->getMessage())->red . PHP_EOL;
					exit(1);
				}
			}
		}

		public static function PATH() {
			return realpath(__DIR__ . '/../../');
		}
	}
