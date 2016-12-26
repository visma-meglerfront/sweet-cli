<?php
	namespace Adepto\SweetCLI\Example;

	class PizzaSubCommand extends \Adepto\SweetCLI\Subcommands\SubCommand {

		public function run() {
			$this->printHeadingLine('Your pizza has:');
			$this->println(json_encode($this->options, JSON_PRETTY_PRINT));
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

				"n|number"	=>	[
					"desc"			=>	"How many pizzas?",
					"type"			=>	"number",
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
