<?php

namespace Smuuf\Primi\Handlers\Kinds;

use \Smuuf\Primi\Context;
use \Smuuf\Primi\Ex\ReturnException;
use \Smuuf\Primi\Handlers\SimpleHandler;
use \Smuuf\Primi\Handlers\HandlerFactory;

class ReturnStatement extends SimpleHandler {

	protected static function handle(array $node, Context $context) {

		if (!isset($node['subject'])) {
			throw new ReturnException;
		}

		throw new ReturnException(
			HandlerFactory::runNode($node['subject'], $context)
		);

	}

}