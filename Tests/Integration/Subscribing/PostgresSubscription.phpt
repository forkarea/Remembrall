<?php
/**
 * @testCase
 * @phpVersion > 7.1
 */
namespace Remembrall\Integration\Subscribing;

use Klapuch\Time;
use Remembrall\Model\Subscribing;
use Remembrall\TestCase;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class PostgresSubscription extends TestCase\Database {
	public function testCancelingWithoutAffectingOthers() {
		(new Subscribing\PostgresSubscription(1, $this->database))->cancel();
		$statement = $this->database->prepare('SELECT * FROM subscriptions');
		$statement->execute();
		$subscriptions = $statement->fetchAll();
		Assert::count(1, $subscriptions);
		Assert::same(2, $subscriptions[0]['id']);
	}

	public function testCancelingUnknownWithoutEffect() {
		$statement = $this->database->prepare('SELECT * FROM subscriptions');
		$statement->execute();
		$before = $statement->fetchAll();
		(new Subscribing\PostgresSubscription(666, $this->database))->cancel();
		$statement->execute();
		$after = $statement->fetchAll();
		Assert::same($before, $after);
	}


	public function testEditingIntervalWithoutChangingLastUpdate() {
		$id = 1;
		(new Subscribing\PostgresSubscription(
			$id,
			$this->database
		))->edit(new Time\FakeInterval(null, null, 'PT10M'));
		$statement = $this->database->prepare('SELECT * FROM subscriptions WHERE id = ?');
		$statement->execute([$id]);
		$subscription = $statement->fetch();
		Assert::same('PT10M', $subscription['interval']);
		Assert::same('2000-01-01 00:00:00', $subscription['last_update']);
	}

	public function testNotifying() {
		$id = 1;
		(new Subscribing\PostgresSubscription(
			$id,
			$this->database
		))->notify();
		$statement = $this->database->prepare('SELECT * FROM notifications');
		$statement->execute();
		$notifications = $statement->fetchAll();
		Assert::count(1, $notifications);
		Assert::same($id, $notifications[0]['subscription_id']);
	}

	public function testNotifyingWithUpdatedSnapshot() {
		$id = 1;
		(new Subscribing\PostgresSubscription(
			$id,
			$this->database
		))->notify();
		$statement = $this->database->prepare('SELECT * FROM subscriptions WHERE id = ?');
		$statement->execute([$id]);
		Assert::same('facedown snap', $statement->fetch()['snapshot']);
	}

	protected function prepareDatabase() {
		$this->purge(['subscriptions', 'notifications', 'parts']);
		$this->database->exec(
			"INSERT INTO subscriptions (id, user_id, part_id, interval, last_update, snapshot) VALUES
			(1, 111, 3, 'PT2M', '2000-01-01', ''),
			(2, 666, 4, 'PT3M', '2000-01-01', '')"
		);
		$this->database->exec(
			"INSERT INTO parts (id, page_url, expression, content, snapshot) VALUES
			(3, 'www.facedown.cz', '//p', 'facedown content', 'facedown snap'),
			(4, 'www.google.com', '//p', 'google content', 'google snap')"
		);
	}
}

(new PostgresSubscription)->run();