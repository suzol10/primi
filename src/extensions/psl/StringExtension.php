<?php

declare(strict_types=1);

namespace Smuuf\Primi\Psl;

use \Smuuf\Primi\Extension;
use \Smuuf\Primi\ErrorException;
use \Smuuf\Primi\Helpers\Common;
use \Smuuf\Primi\Structures\Value;
use \Smuuf\Primi\Structures\BoolValue;
use \Smuuf\Primi\Structures\ArrayValue;
use \Smuuf\Primi\Structures\RegexValue;
use \Smuuf\Primi\Structures\StringValue;
use \Smuuf\Primi\Structures\NumberValue;

class StringExtension extends Extension {

	public static function string_shuffle(StringValue $str): StringValue {

		// str_shuffle() doesn't work with unicode, so let's do this ourselves.
		$original = $str->value;
		$length = \mb_strlen($original);
		$indices = \range(0, $length - 1);
		\shuffle($indices);
		$result = "";

		while (($i = \array_pop($indices)) !== \null) {
			$result .= \mb_substr($original, $i, 1);
		}

		return new StringValue($result);

	}

	public static function string_format(StringValue $str, Value ...$items): StringValue {

		// Extract PHP values from passed in value objects, because later we
		// will pass the values to sprintf().
		\array_walk($items, function(&$i) {
			$i = $i->value;
		});

		$passedCount = \count($items);
		$expectedCount = 0;
		$indexedMode = \null;

		// Convert {} syntax to a something sprintf() understands.
		// {} will be converted to "%s", positional {456} will be converted to
		// "%456$s".
		$prepared = \preg_replace_callback("#\{(\d+)?\}#", function($m) use (
			$passedCount,
			&$indexedMode,
			&$expectedCount
		) {

			if (isset($m[1])) {

				if ($indexedMode === \false) {
					// A positional placeholder was used when a non-positional
					// one is already present.
					throw new ErrorException(
						\sprintf("Cannot combine positional and non-positional placeholders.")
					);
				}

				$indexedMode = \true;
				$index = $m[1];

				if ($index < 0) {
					throw new ErrorException(
						\sprintf("Position (%s) cannot be less than 0.", $index)
					);
				}

				if ($index > $passedCount) {
					throw new ErrorException(
						\sprintf("Position (%s) does not match the number of parameters (%s).", $index, $passedCount)
					);
				}

				$converted = "%{$index}\$s";

			} else {

				if ($indexedMode === \true) {
					// A non-positional placeholder was used when a positional
					// one is already present.
					throw new ErrorException(
						sprintf("Cannot combine positional and non-positional placeholders.")
					);
				}

				$indexedMode = \false;
				$converted = "%s";

			}

			$expectedCount++;
			return $converted;

		}, $str->value);

		// If there are more args expected than passed, throw error.
		if ($expectedCount > $passedCount) {
			throw new ErrorException(
				\sprintf(
					"Not enough arguments passed (expected %s, got %s).",
					$expectedCount,
					$passedCount
				)
			);
		}

		return new StringValue(\sprintf($prepared, ...$items));

	}

	public static function string_replace(StringValue $self, Value $search, StringValue $replace = \null): StringValue {

		// Replacing using array of search-replace pairs.
		if ($search instanceof ArrayValue) {

			$from = \array_keys($search->value);

			// Values in ArrayValues are stored as Value objects,
			// so we need to extract the real PHP values from it.
			$to = \array_values(\array_map(function($item) {
				return $item->value;
			}, $search->value));

			return new StringValue(\str_replace($from, $to, $self->value));

		}

		if ($replace === \null) {
			throw new \ArgumentCountError;
		}

		if ($search instanceof StringValue || $search instanceof NumberValue) {
			// Handle both string/number values the same way.
			return new StringValue(
				\str_replace(
					(string) $search->value, $replace->value, $self->value
				)
			);
		} elseif ($search instanceof RegexValue) {
			return new StringValue(
				\preg_replace(
					$search->value, $replace->value, $self->value
				)
			);
		} else {
			throw new \TypeError;
		}

	}

	public static function string_reverse(StringValue $self): StringValue {

		// strrev() does not support multibyte.
		// Let's do it ourselves then!

		$result = '';
		$len = \mb_strlen($self->value);

		for ($i = $len; $i-- > 0;) {
			$result .= \mb_substr($self->value, $i, 1);
		}

		return new StringValue($result);

	}

	public static function string_split(StringValue $self, Value $delimiter): ArrayValue {

		// Allow only some value types.
		Common::allowTypes($delimiter, StringValue::class, RegexValue::class);

		if ($delimiter instanceof RegexValue) {
			$splat = \preg_split($delimiter->value, $self->value);
		}

		if ($delimiter instanceof StringValue) {
			$splat = \explode($delimiter->value, $self->value);
		}

		return new ArrayValue(array_map(function($part) {
			return new StringValue($part);
		}, $splat ?? []));

	}

	public static function string_contains(StringValue $self, Value $needle): BoolValue {

		// Allow only some value types.
		Common::allowTypes(
			$needle, StringValue::class, NumberValue::class, RegexValue::class
		);

		if ($needle instanceof RegexValue) {
			return new BoolValue(
				(bool) \preg_match((string) $needle->value, (string) $self->value)
			);
		}

		// Let's search the $needle object in $arr's value (array of objects).
		return new BoolValue(
			\mb_strpos((string) $self->value, (string) $needle->value) !== \false
		);

	}

	public static function string_number_of(StringValue $self, Value $needle): NumberValue {

		// Allow only some value types.
		Common::allowTypes($needle, StringValue::class, NumberValue::class);

		return new NumberValue(
			(string) \mb_substr_count(
				(string) $self->value, (string) $needle->value
			)
		);

	}

	public static function string_find_first(StringValue $self, Value $needle): Value {

		// Allow only some value types.
		Common::allowTypes($needle, StringValue::class, NumberValue::class);

		$pos = \mb_strpos($self->value, (string) $needle->value);
		if ($pos !== \false) {
			return new NumberValue((string) $pos);
		} else {
			return new BoolValue(\false);
		}

	}

	public static function string_find_last(StringValue $self, Value $needle): Value {

		// Allow only some value types.
		Common::allowTypes($needle, StringValue::class, NumberValue::class);

		$pos = \mb_strrpos($self->value, (string) $needle->value);
		if ($pos !== \false) {
			return new NumberValue((string) $pos);
		} else {
			return new BoolValue(\false);
		}

	}

	public static function string_join(
		StringValue $self,
		ArrayValue $array
	): StringValue {

		$prepared = \array_map(function($item) use ($self) {

			// Common::allowTypes($item, StringValue::class, NumberValue::class,
			// 	BoolValue::class, ArrayValue::class);

			switch (\true) {
				case $item instanceof ArrayValue:
					return self::string_join($self, $item)->value;
				default:
					return $item->getStringValue();
			}

		}, $array->value);

		return new StringValue(\implode($self->value, $prepared));

	}

}
