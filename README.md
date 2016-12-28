# SweetCLI

A framework for creating awesome CLI applications using PHP.

## Installation

Add this to `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:adeptoas/sweet-cli"
    }
],

"require": {
	"adeptoas/sweet-cli": "^1.0.0"
}
```

Make sure to merge your `require`-blocks!

## Examples

For examples have a look in `/example`. There you'll find a step-by-step tutorial on how to create a CLI application.

## Concept

Your CLI application will consist of the main entry point and your subcommands. Each subcommand can have options and arguments and is free to do whatever it needs to do. Commands look like this:

- `cli subcommand -o -n 2 --long-opt --long-opt-string Example`
- `cli subcommand -on 2 --long-opt "Some Argument"`
- `cli -vv subcommand -on 2`
- …

## How to Build an Application

1. Create a new file you want to use as your "binary" to be run. I recommend `cli`.
2. Put this in the top of your file (*nothing* before it!):

   ```php
   #!/usr/bin/env php
   <?php
   ```
3. Include the composer autoloader:

   ```php
   require 'vendor/autoload.php';
   ```
4. Subclass `Adepto\SweetCLI\Base\APIApplication` and override these methods:
   - `public static function getTitle(): string`
   - `public static function getShortTitle(): string`
   - `public static function getConfigPath(): string`
5. Create an instance of your newly created class (empty constructor).
6. Subclass `Adepto\SweetCLI\Subcommands\SubCommand` for every subcommand you need and override these methods:
   - `public static function getOptions(): array` (refer to the options syntax for this)
   - `public static function getCommand(): string`
   - `public static function getDescription(): string`
7. Add your newly created subcommand classes to your application (do *not* create instances!):

   ```php
   $app->addSubCommand(SomeSubCommand::class);
   $app->addSubCommand(SomeOtherSubCommand::class);
   ```
8. Call `$app->run($argv)`.
9. Make your script executable: `chmod +x cli`
10. You can now run it: `./cli --help`

## Options Syntax

When creating a subcommand you have to override the `getOptions(): array` method. This has to return an array of available options for that subcommand. The key of each item has to be the option specification with the value being another array with modifiers.

**This is the option specification:**

- `o` → -o [boolean]
- `option` → --option [boolean]
- `o|option` → -o and --option [boolean]
- `o:` → -o [boolean, required]
- `o+` → -o value [string values]
- `o:+` → -o value -o value2 [string values, required]

So in short:
- `:` → required
- `+` → values allowed
- `|` → alternative

**This is a list of available modifiers:**
- `"desc" => "Some Description"` → description for the help page. Leave this empty to make the option invisible.
- `"default" => "Some Value"` → default to "Some Value" if option is not given
- `"validValues" => [1, 2, 3]` → only allow 1, 2 or 3 for the option
- `"type" => "number"` → only allow numbers

## Usage

### SubCommand

**This is only a description of the public API that you will use to do stuff in your subcommand.**

```php
checkConflictingOptions()
```

See if `hasConflictingOptions()` returns `true` and if so throw an exception.

```php
hasDuplicateBoolean(bool $bool, array $values): bool
```

Returns `true` if `$bool` occurs more than once in `$values`

```php
hasOptions(): bool
```

See if this subcommand has been given options.

```php
hasOption(string $option): bool
```

See if this subcommand has been given a specific `$option`.

```php
getOption(string $option): mixed
```

Get the value of a specific option or `null` if the option wasn't set.


```php
setOption(string $option, mixed $value)
```

Set the value of an option. Use this with caution!

```php
requireOption(string $option, string $message)
```

Check if `$option` is set and throw an exception containing `$message` if it isn't set.
