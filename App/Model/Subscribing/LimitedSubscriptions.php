<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use Klapuch\{
	Storage, Uri, Time
};
use Remembrall\Model\Access;

/**
 * Disallow subscribing after more than X subscriptions
 */
final class LimitedSubscriptions implements Subscriptions {
	const LIMIT = 5;
	private $database;
	private $subscriber;
	private $origin;

	public function __construct(
		Storage\Database $database,
		Access\Subscriber $subscriber,
		Subscriptions $origin
	) {
		$this->database = $database;
		$this->subscriber = $subscriber;
		$this->origin = $origin;
	}

	public function subscribe(
		Uri\Uri $uri,
		string $expression,
		Time\Interval $interval
	) {
		if($this->overstepped()) {
			throw new \OverflowException(
				sprintf(
					'You have reached the limit of %d subscribed parts',
					self::LIMIT
				)
			);
		}
		$this->origin->subscribe($uri, $expression, $interval);
	}

	public function iterate(): array {
		return $this->origin->iterate();
	}

	/**
	 * Has the subscriber subscribed more than X parts and overstepped the limit?
	 * @return bool
	 */
	private function overstepped(): bool {
		return (bool)$this->database->fetchColumn(
			'SELECT 1
			FROM parts
			INNER JOIN subscriptions ON subscriptions.part_id = parts.id 
			WHERE subscriber_id IS NOT DISTINCT FROM ?
			HAVING COUNT(parts.id) >= ?',
			[$this->subscriber->id(), self::LIMIT]
		);
	}
}
