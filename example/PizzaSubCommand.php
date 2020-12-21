<?php
	namespace Adepto\SweetCLI\Example;

	class PizzaSubCommand extends \Adepto\SweetCLI\Subcommands\SubCommand {

		public function run() {
			$this->printHeadingLine('Your pizza has:');
			$this->println(json_encode($this->options, JSON_PRETTY_PRINT));
			
			$this->println();
			$result = $this->printTimeoutMessage('Pizza ready for baking.', 1, 3, 'c');

			if (!$result) {
				$this->printInfoLine('Baking cancelled.');
				return;
			}
			
			$this->printSubHeadingLine('Baking…');

			// Show off spinner
			for ($i = 0; $i < 100; $i++) {
				$this->printSpinner(false, 'Preparing…');
				usleep(50000);
			}

			$this->printSpinner(true);

			// Show off progress bar
			$total = 16;

			for ($i = 0; $i < $total; $i++) {
				$this->printProgressBar($i + 1, $total, sprintf('%d', $i + 1), sprintf('%d seconds', $total));
				
				if ($i != $total) sleep(1);
			}

			echo $this->printSuccessLine('Done');
		}

		public static function getOptions(): array {
			return [
				"a|add:+"	=>	[
					"desc"		=>	"Add some topping",
				],

				"t|type"	=>	[
					"desc"			=>	"Type of the pizza, defaults to italian",
					"default"		=>	"italian",
					"validValues"	=>	[ "italian", "american" ]
				],

				"n|number:"	=>	[
					"desc"			=>	"How many pizzas?",
					"type"			=>	"Number",
					"default"		=>	1
				]
			];
		}

		public static function getCommand(): string {
			return 'pizza';
		}

		public static function getDescription(): string {
			return 'Make a pizza';
		}
	}
