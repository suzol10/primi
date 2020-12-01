<?php

namespace Smuuf\Primi\Handlers\Types;

use \Smuuf\Primi\Context;
use \Smuuf\Primi\Handlers\HandlerFactory;
use \Smuuf\Primi\Helpers\Func;
use \Smuuf\Primi\Handlers\SimpleHandler;
use \Smuuf\Primi\Values\NullValue;

class Program extends SimpleHandler {

	protected static function handle(array $node, Context $context) {

		foreach ($node['stmts'] as $sub) {
			$handler = HandlerFactory::getFor($sub['name']);
			$returnValue = $handler::run($sub, $context);
		}

		return $returnValue ?? new NullValue;

	}

	public static function reduce(array &$node): void {

		// Make sure the list of statements has proper form.
		if (isset($node['stmts'])) {
			$node['stmts'] = Func::ensure_indexed($node['stmts']);
		} else {
			// ... even if there are no statements at all.
			$node['stmts'] = [];
		}

	}

}