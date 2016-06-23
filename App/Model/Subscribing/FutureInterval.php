<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

/**
 * Only interval which points to the future
 * Past intervals are not allowed
 */
final class FutureInterval implements Interval {
	private $origin;

	public function __construct(Interval $origin) {
		$this->origin = $origin;
	}

	public function start(): \DateTimeInterface {
		return $this->origin->start();
	}

	public function next(): \DateTimeInterface {
		if($this->origin->next() > $this->start())
			return $this->origin->next();
		throw new \OutOfRangeException('Interval must points to the future');
	}

	public function step(): \DateInterval {
		if($this->origin->step()->invert === 0)
			return $this->origin->step();
		throw new \OutOfRangeException('Interval must points to the future');
	}
}