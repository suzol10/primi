<?php

declare(strict_types=1);

namespace Smuuf\Primi\Tasks;

use \Smuuf\Primi\Context;
use \Smuuf\Primi\Helpers\Func;
use \Smuuf\Primi\Helpers\Stats;
use \Smuuf\Primi\Helpers\Traits\StrictObject;

class TaskQueue {

	use StrictObject;

	/**
	 * Run queued tasks after this time interval passes (in seconds).
	 * @var int|float
	 */
	public static $interval = 0.1;

	/** @var Context */
	private $context;

	/** @var float Measuring time. */
	private $timer;

	/** @var array FIFO queue for scheduling tasks. */
	private $queue = [];

	/**
	 * Random ID for this queue instance (safer than spl_object_id() or similar
	 * for checking uniqueness).
	 *
	 * @var string
	 */
	private $id;

	public function __construct(Context $ctx) {
		$this->id = Func::unique_id();
		$this->context = $ctx;
		$this->timer = Func::monotime();
	}

	public function getId(): string {
		return $this->id;
	}

	public function addTask(TaskInterface $task, float $delay = 0): void {

		$eta = Func::monotime() + $delay;
		$this->queue[] = [$task, $eta];

	}

	public function tick(): void {

		Stats::add('taskqueue_ticks_tried');
		if (!$this->queue) {
			return;
		}

		if ((Func::monotime() - $this->timer) < self::$interval) {
			return;
		}

		Stats::add('taskqueue_ticks_actual');
		$this->timer = Func::monotime();
		$this->executeQueued();

	}

	/**
	 * Run all remaining tasks.
	 *
	 * Tasks with ETA in the future are skipped and kept in the queue..
	 */
	public function deplete(): void {
		$this->executeQueued();
	}

	private function executeQueued(): void {

		// Because asynchronous events (e.g. signals) could modify (add tasks to)
		// the $queue property while we're iterating through it, and
		// the same (adding more tasks) could be done by any tasks we're now
		// actually going to execute now, we need to handle these edge-case
		// situations.

		// Create a copy of the queue and empty the main queue, so that any
		// new entries are added to the empty queue.
		[$queue, $this->queue] = [$this->queue, []];

		$skipped = [];
		foreach ($queue as [$task, $eta]) {

			if ($eta > Func::monotime()) {
				$skipped[] = [$task, $eta];
				continue;
			}

			$this->executeTask($task);

		}

		// If there were any tasks skipped, add it to the end of the "cleared"
		// queue (but which may now already have new tasks in it. See above.)
		if ($skipped) {
			$this->queue = array_merge($this->queue, $skipped);
		}

	}

	private function executeTask(TaskInterface $task) {
		$task->execute($this->context);
	}

}