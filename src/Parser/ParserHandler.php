<?php

namespace Smuuf\Primi\Parser;

use \Smuuf\Primi\Ex\SyntaxError;
use \Smuuf\Primi\Helpers\Func;
use \Smuuf\Primi\Helpers\Timer;
use \Smuuf\Primi\Handlers\HandlerFactory;

class ParserHandler extends CompiledParser {

	/** @var string Primi source code that is to be parsed and executed. */
	private $source;

	/** @var array<string, scalar> Parser statistics. */
	private $stats = [];

	public function __construct($source) {

		$source = self::sanitizeSource($source);

		parent::__construct($source);
		$this->source = $source;

	}

	/**
	 * Return parser stats.
	 */
	public function getStats(): array {
		return $this->stats;
	}

	public function run(): array {

		$t = (new Timer)->start();
		$result = $this->match_Program();
		$this->stats['parsing'] = $t->get();

		if ($result['text'] !== $this->source) {

			// $this->pos is an internal PEG Parser position counter and
			// we will use it to determine the line and position in the source.
			$this->syntaxError($this->pos, $this->source);

		}

		$t = (new Timer)->start();
		$processed = $this->processAST($result, $this->source);
		$this->stats['ast_postprocess'] = $t->get();

		return $processed;

	}

	private function syntaxError(int $position, string $source) {

		$line = \false;
		$pos = \false;

		if ($position !== \false) {
			[$line, $pos] = Func::get_position_estimate(
				$this->source,
				$position
			);
		}

		// Show a bit of code where the syntax error occured.
		$excerpt = \mb_substr($source, $position, 20);

		throw new SyntaxError((int) $line, (int) $pos, $excerpt);

	}

	private static function sanitizeSource(string $s) {

		// Unify new-lines.
		$s = \str_replace("\r\n", "\n", $s);

		// Ensure newline at the end (parser needs this to be able to correctly
		// parse comments in one line source codes.)
		return \rtrim($s) . "\n";

	}

	private function processAST(array $ast, string $source): array {

		$t = (new Timer)->start();
		self::preprocessNode($ast);
		$this->stats['ast_postprocess_preprocess_nodes'] = $t->get();

		$t = (new Timer)->start();
		self::reduceNode($ast);
		$this->stats['ast_postprocess_reduce_nodes'] = $t->get();

		$t = (new Timer)->start();
		self::addPositions($ast, $source);
		$this->stats['ast_postprocess_add_position'] = $t->get();

		return $ast;

	}
	/**
	 * Go recursively through each of the nodes and strip unecessary data
	 * in the abstract syntax tree.
	 */
	private static function preprocessNode(array &$node): void {

		// If node has "skip" node defined, replace the whole node with the
		// "skip" subnode.
		unset($node['_matchrule']);
		foreach ($node as &$item) {

			while ($inner = ($item['skip'] ?? \false)) {
				$item = $inner;
			}

			if (\is_array($item)) {
				self::preprocessNode($item);
			}

		}

	}

	/**
	 * Go recursively through each of the nodes and strip unnecessary data
	 * in the abstract syntax tree.
	 */
	private static function reduceNode(array &$node): void {

		foreach ($node as &$item) {
			if (\is_array($item)) {
				self::reduceNode($item);
			}
		}

		if (!isset($node['name'])) {
			return;
		}

		if (!$handler = HandlerFactory::getFor($node['name'], \false)) {
			return;
		}

		// Remove text from nodes that don't need it.
		if (!$handler::NODE_NEEDS_TEXT) {
			unset($node['text']);
		}

		// If a handler knows how to reduce its node, let it.
		$handler::reduce($node);

	}

	/**
	 * Recursively iterate the node and its children and add information about
	 * the node's offset (line & position) for later (e.g. error messages).
	 */
	private static function addPositions(array &$node, string $source): void {

		if (isset($node['offset'])) {

			[$line, $pos] = Func::get_position_estimate(
				$source, $node['offset']
			);

			$node['_l'] = $line;
			$node['_p'] = $pos;

			// Offset no longer necessary.
			unset($node['offset']);

		}

		foreach ($node as &$item) {
			if (\is_array($item)) {
				self::addPositions($item, $source);
			}
		}

	}

}