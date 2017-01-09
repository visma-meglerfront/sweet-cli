<?php
	namespace Adepto\SweetCLI\Subcommands;

	use GetOptionKit\OptionCollection;
	use GetOptionKit\Option;
	use GetOptionKit\OptionPrinter\OptionPrinter;

	use Colors\Color;

	class SubCommandOptionPrinter implements OptionPrinter {
		public static $screenWidth = 78;

		/**
		 * Render readable spec
		 */
		public function renderOption(Option $opt) {
			if ($opt->getId() == 'help') return null;

			$c = new Color();
			$c->setTheme([
				'option'	=>	[]
			]);

			$lines = [];
			$optionDefinition = '';

			if ($opt->short && $opt->long) {
				$optionDefinition = sprintf('-%s, --%s', $opt->short, $opt->long);
			} else if ($opt->short) {
				$optionDefinition = sprintf('-%s', $opt->short);
			} else if ($opt->long) {
				$optionDefinition = sprintf('--%s', $opt->long);
			}

			if ($opt->isRequired()) {
				$c->setTheme([
					'option'	=>	['bold']
				]);

				$optionDefinition .= ' *';
			}

			if ($opt->defaultValue) {
				$lines[] = "    " . $c($optionDefinition)->option . ' ' . $c('[= ' . $opt->defaultValue . ']')->dark;
			} else {
				$lines[] = "    " . $c($optionDefinition)->option;
			}

			$lines[] = $c(wordwrap("        " . str_replace("\n", "\n        ", $opt->desc), self::$screenWidth, "\n        "))->dark;
			$lines[] = "";

			return implode("\n", $lines);
		}


		/**
		 * Render option descriptions
		 *
		 * @return string output
		 */
		public function render(OptionCollection $options) {
			$c = new Color();

			$lines = [];

			if (count($options) > 1) { // --help is always included
				foreach ($options as $option) {
					$rendered = $this->renderOption($option);

					if ($rendered !== null) {
						$lines = array_merge($lines, [ $rendered ]);
					}
				}
			} else {
				$lines[] = $c('No options.')->dark;
			}

			$lines[] = '';

			return implode("\n", $lines);
		}
	}
