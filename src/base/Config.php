<?php
	namespace Adepto\SweetCLI\Base;

	use \Symfony\Component\Yaml\Yaml;

	/**
	 * Configuration Wrapper
	 *
	 * @author  bluefirex
	 * @package as.adepto.sweet-cli.base
	 * @version 1.0
	 */
	class Config {
		/**
		 * File Type: JSON
		 *
		 * @var string
		 */
		const TYPE_JSON = 'json';

		/**
		 * File Type: YAML
		 *
		 * @var string
		 */
		const TYPE_YAML = 'yaml';

		const TYPES_MAP = [
			'json'		=>	self::TYPE_JSON,
			'yml'		=>	self::TYPE_YAML,
			'yaml'		=>	self::TYPE_YAML
		];

		protected static $instance;

		protected $file;
		protected $type;
		protected $values = [];

		public function __construct($file, $type = null) {
			$values = [];
			$contents = is_file($file) ? file_get_contents($file) : '';

			// Try to detect type
			if ($type === null) {
				$type = self::TYPES_MAP[(new \SplFileInfo($file))->getExtension()] ?? null;
			}

			// Load!
			switch ($type) {
				case self::TYPE_JSON:
					$values = @json_decode($contents, true);
					break;

				case self::TYPE_YAML:
					$values = Yaml::parse($contents);
					break;

				default:
					throw new \Exception('Unknown type.');
			}

			// If empty, make it an empty array
			if (!is_array($values)) {
				$values = [];
			}

			$this->file = $file;
			$this->type = $type;
			$this->values = $values;
		}

		/**
		 * On destruction save all values to disk
		 */
		public function __destruct() {
			switch ($this->type) {
				case self::TYPE_JSON:
					file_put_contents($this->file, json_encode($this->values));
					break;

				case self::TYPE_YAML:
					file_put_contents($this->file, Yaml::dump($this->values));

					break;
			}
		}

		protected function setInNestedArray(array &$arr, $key, $value) {
			$keys = explode('.', $key);
			$firstKey = $keys[0];

			if (count($keys) == 1) {
				$arr[$firstKey] = $value;
			} else {
				unset($keys[0]);

				if (!array_key_exists($firstKey, $arr)) {
					$arr[$firstKey] = [];
				}

				if (!is_array($arr[$firstKey])) {
					throw new \TypeError('Item at key "' . $key . '" is neither an array nor unset.');
				}

				$this->setInNestedArray($arr[$firstKey], implode('.', $keys), $value);
			}
		}

		protected function getFromArray(array $arr, $key) {
			$keys = explode('.', $key);
			$firstKey = $keys[0];

			if (count($keys) == 1) {
				return $arr[$firstKey];
			} else {
				unset($keys[0]);

				if (!array_key_exists($firstKey, $arr)) {
					return null;
				}

				return $this->getFromArray($arr[$firstKey], implode('.', $keys));
			}
		}

		protected function unsetInArray(array &$arr, string $key) {
			$keys = explode('.', $key);
			$firstKey = $keys[0];

			if (count($keys) == 1) {
				unset($arr[$firstKey]);

				return true;
			} else {
				unset($keys[0]);

				if (array_key_exists($firstKey, $arr)) {
					return $this->unsetInArray($arr[$firstKey], implode('.', $keys));
				}

				return false;
			}
		}

		/**
		 * Set a value in $key.
		 *
		 * @param string $key   Dot-seperated key, i.e. "some.key"
		 * @param mixed  $value Value
		 */
		public function setValue(string $key, $value) {
			$this->setInNestedArray($this->values, $key, $value);
		}

		/**
		 * Get a value from $key
		 *
		 * @param string $key          Dot-seperated key, i.e. "some.key"
		 * @param mixed  $defaultValue Default Value if value doesn't exist
		 *
		 * @return mixed
		 */
		public function getValue(string $key, $defaultValue = null) {
			return $this->getFromArray($this->values, $key) ?? $defaultValue;
		}

		/**
		 * Unset a value "$key"
		 *
		 * @param string $key Dot-seperated key, i.e. "some.key"
		 *
		 * @return bool        true if removal was successful
		 */
		public function unsetValue(string $key): bool {
			return $this->unsetInArray($this->values, $key);
		}

		/**
		 * Load a file to be used for the singleton
		 *
		 * @param string $file File to loader
		 */
		public static function loadFile($file) {
			self::$instance = new self($file);
		}

		/**
		 * Get the currently loaded instance
		 *
		 * @return Config
		 */
		public static function getInstance() {
			if (!self::$instance) {
				throw new \Exception('No file loaded.');
			}

			return self::$instance;
		}

		/**
		 * Get a value in the currently loaded configuration.
		 * {@see Config::getValue}
		 *
		 * @param string $key          Dot-seperated key
		 * @param mixed  $defaultValue Default value if $key doesn't exist
		 *
		 * @return mixed
		 */
		public static function get(string $key, $defaultValue = null) {
			return self::getInstance()->getValue($key, $defaultValue);
		}

		/**
		 * Set a value in the currently loaded configuration.
		 *
		 * @param string $key   Dot-seperated key
		 * @param mixed  $value Value
		 */
		public static function set(string $key, $value) {
			return self::getInstance()->setValue($key, $value);
		}

		/**
		 * Unset a value in the currently loaded configuration
		 *
		 * @param string $key Dot-seperated key
		 */
		public static function unset(string $key) {
			return self::getInstance()->unsetValue($key);
		}
	}
