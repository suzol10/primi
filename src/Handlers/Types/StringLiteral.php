<?php

declare(strict_types=1);

namespace Smuuf\Primi\Handlers\Types;

use \Smuuf\Primi\Context;
use \Smuuf\Primi\Handlers\SimpleHandler;
use \Smuuf\Primi\Helpers\StringEscaping;
use \Smuuf\Primi\Values\StringValue;

class StringLiteral extends SimpleHandler {

	const NODE_NEEDS_TEXT = \true;

	protected static function handle(array $node, Context $context) {
		return StringValue::build(StringEscaping::unescapeString($node['text']));
	}

	public static function reduce(array &$node): void {

		// Trim quotes from the start and the end using substr().
		// Using trim("\"'", ...) would make "abc'" into abc instead of abc',
		// so do this a little more directly.
		$node['text'] = \mb_substr(
			$node['text'], 1, \mb_strlen($node['text']) - 2
		);
		unset($node['quote']);
		unset($node['core']);

	}

}
