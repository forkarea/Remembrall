<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use Klapuch\Output;
use Klapuch\Storage;
use Klapuch\Time;

/**
 * Subscription stored in the database
 */
final class StoredSubscription implements Subscription {
	private $id;
	private $database;

	public function __construct(int $id, \PDO $database) {
		$this->id = $id;
		$this->database = $database;
	}

	public function cancel(): void {
		(new Storage\ParameterizedQuery(
			$this->database,
			'DELETE FROM subscriptions
			WHERE id IS NOT DISTINCT FROM ?',
			[$this->id]
		))->execute();
	}

	public function edit(Time\Interval $interval): void {
		(new Storage\ParameterizedQuery(
			$this->database,
			'UPDATE subscriptions
			SET interval = ?
			WHERE id IS NOT DISTINCT FROM ?',
			[$interval->iso(), $this->id]
		))->execute();
	}

	public function notify(): void {
		(new Storage\ParameterizedQuery(
			$this->database,
			'UPDATE subscriptions
			SET snapshot = (
				SELECT snapshot
				FROM parts
				WHERE id = (
					SELECT part_id
					FROM subscriptions
					WHERE id = :id
				)
			)
			WHERE id IS NOT DISTINCT FROM :id',
			['id' => $this->id]
		))->execute();
	}

	public function print(Output\Format $format): Output\Format {
		$subscription = (new Storage\ParameterizedQuery(
			$this->database,
			'SELECT readable_subscriptions.id, interval_seconds / 60 AS interval, page_url AS url, expression
			FROM readable_subscriptions
			LEFT JOIN parts ON readable_subscriptions.part_id = parts.id
			WHERE readable_subscriptions.id IS NOT DISTINCT FROM ?',
			[$this->id]
		))->row();
		return new Output\FilledFormat($format, $subscription);
	}
}