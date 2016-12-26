<?php
/**
 * @testCase
 * @phpVersion > 7.1
 */
namespace Remembrall\Integration\Subscribing;

use Remembrall\Model\Subscribing;
use Remembrall\TestCase;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class PostgresPart extends TestCase\Database {
	public function testContent() {
		Assert::same(
			'facedown content',
			(new Subscribing\PostgresPart(
				new Subscribing\FakePart(),
				1,
				$this->database
			))->content()
		);
	}

	public function testSnapshot() {
		Assert::same(
			'face snap',
			(new Subscribing\PostgresPart(
				new Subscribing\FakePart(),
				1,
				$this->database
			))->snapshot()
		);
	}

	public function testRefreshingWithNewContent() {
		(new Subscribing\PostgresPart(
			new Subscribing\FakePart('NEW_CONTENT', null, 'NEW_SNAP'),
			1,
			$this->database
		))->refresh();
		$statement = $this->database->prepare('SELECT * FROM parts WHERE id = 1');
		$statement->execute();
		$part = $statement->fetch();
		Assert::same('NEW_CONTENT', $part['content']);
		Assert::same('NEW_SNAP', $part['snapshot']);
	}

	public function testRefreshingWithRecordedVisitation() {
		$this->purge(['part_visits']);
		(new Subscribing\PostgresPart(
			new Subscribing\FakePart('NEW_CONTENT', null, 'NEW_SNAP'),
			1,
			$this->database
		))->refresh();
		$statement = $this->database->prepare(
			"SELECT *
			FROM part_visits
			WHERE visited_at >= NOW() - INTERVAL '1 MINUTE'"
		);
		$statement->execute();
		Assert::count(1, $statement->fetchAll());
	}

	public function testRefreshingWithoutAffectingOthers() {
		(new Subscribing\PostgresPart(
			new Subscribing\FakePart('NEW_CONTENT', null, 'NEW_SNAP'),
			1,
			$this->database
		))->refresh();
		$statement = $this->database->prepare('SELECT * FROM parts');
		$statement->execute();
		$parts = $statement->fetchAll();
		Assert::count(2, $parts);
		Assert::same(2, $parts[0]['id']);
		Assert::same('google content', $parts[0]['content']);
		Assert::same('google snap', $parts[0]['snapshot']);
		Assert::same(1, $parts[1]['id']);
		Assert::same('NEW_CONTENT', $parts[1]['content']);
		Assert::same('NEW_SNAP', $parts[1]['snapshot']);
	}

	protected function prepareDatabase() {
		$this->purge(['parts']);
		$this->database->exec(
			"INSERT INTO parts (page_url, expression, content, snapshot) VALUES
			('www.facedown.cz', '//facedown', 'facedown content', 'face snap'),
			('www.google.com', '//google', 'google content', 'google snap')"
		);
	}
}

(new PostgresPart)->run();