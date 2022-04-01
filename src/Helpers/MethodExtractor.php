<?php

namespace Smuuf\Primi\Helpers;

use \Smuuf\StrictObject;
use \Smuuf\Primi\Values\FuncValue;
use \Smuuf\Primi\Structures\FnContainer;

use \Smuuf\DocBlockParser\Parser as DocBlockParser;
use \Smuuf\Primi\MagicStrings;
use \Smuuf\Primi\Ex\EngineError;

abstract class MethodExtractor {

	use StrictObject;

	/**
	 * @return array<string, FuncValue> Dict array of public methods as mapping
	 * `[<method name> => FuncValue]` that are present in an object.
	 */
	public static function extractMethods(object $obj): array {

		$result = [];
		$extRef = new \ReflectionClass($obj);

		//
		// Extract functions.
		//

		$methods = $extRef->getMethods(\ReflectionMethod::IS_PUBLIC);
		foreach ($methods as $ref) {

			$name = $ref->getName();

			// Skip PHP magic "__methods", but allow Primi magic "__methods__".
			if (\str_starts_with($name, '__') && !\str_ends_with($name, '__')) {
				continue;
			}

			$doc = $ref->getDocComment() ?: '';
			$db = DocBlockParser::parse($doc);

			$fnFlags = [];
			if ($fnTag = $db->getTags(MagicStrings::NATFUN_TAG_FUNCTION)->getFirst()) {

				if ($fnTag->hasArg(MagicStrings::NATFUN_NOSTACK)) {
					$fnFlags[] = FnContainer::FLAG_NO_STACK;
				}

				if ($arg = $fnTag->getArg(MagicStrings::NATFUN_CALLCONV)) {
					if ($arg->getValue() === MagicStrings::CALLCONV_CALLARGS) {
						$fnFlags[] = FnContainer::FLAG_CALLCONV_CALLARGS;
					} else {
						throw new EngineError(sprintf(
							"Invalid value for argument '%s'",
							MagicStrings::NATFUN_CALLCONV
						));
					}
				}

				$result[$name] = new FuncValue(
					FnContainer::buildFromClosure([$obj, $name], $fnFlags)
				);

			}

		}

		return $result;

	}

}
