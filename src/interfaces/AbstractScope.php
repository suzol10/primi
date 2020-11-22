<?php

namespace Smuuf\Primi;

use \Smuuf\Primi\Ex\EngineError;
use \Smuuf\Primi\Structures\Value;

/**
 * Abstract base class for "variable scope" structure. Allows for custom
 * mechanisms of storing/fetching variables.
 *
 * For example, some scope implementations can simply use memory to store
 * and/or fetch variables. Other scope implementations can use more exotic
 * ways of storing/fetching variables.
 */
abstract class AbstractScope extends StrictObject {

	//use WatchLifecycle;

	/** @var AbstractScope|null Parent scope, if any. */
	protected $parent = null;

	final public function setParent(self $parent): void {

		if ($this === $parent) {
			throw new EngineError("Scope cannot have itself as parent");
		}

		$this->parent = $parent;

	}

	/**
	 * Get a variable by its name.
	 *
	 * If the variable is missing in current scope, look the variable up in the
	 * parent scope, if there's any.
	 */
	final public function getVariable(string $name): ?Value {

		return $this->fetchVariable($name)
			// Recursively up, if there's a parent scope..
			?? ($this->parent ? $this->parent->getVariable($name) : \null);

	}

	/**
	 * Returns a dictionary [var_name => Value] of all variables present in
	 * current scope.
	 *
	 * If the `$includeParents` argument is `true`, variables from parent scopes
	 * will be included too (variables in child scopes have priority over those
	 * from parent scopes).
	 */
	public function getVariables(bool $includeParents = \false): array {

		$fromParents = ($includeParents && $this->parent)
			// Recursively up, if there's a parent scope.
			? $this->parent->getVariables($includeParents)
			: [];

		return $this->fetchVariables() + $fromParents;

	}

	/**
	 * Implementation-specific internal way of getting a variable from a scope.
	 */
	abstract protected function fetchVariable(string $name): ?Value;

	/**
	 * Implementation-specific internal way of getting all variables from a
	 * scope.
	 */
	abstract protected function fetchVariables(): array;

	/**
	 * Implementation-specific internal way of setting a variable into a scope.
	 */
	abstract public function setVariable(string $name, Value $value);

	/**
	 * Implementation-specific internal way of setting multiple variables into
	 * a scope.
	 */
	abstract public function setVariables(array $pairs);

}