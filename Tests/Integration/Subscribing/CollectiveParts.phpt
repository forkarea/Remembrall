<?php
/**
 * @testCase
 * @phpVersion > 7.1
 */
namespace Remembrall\Integration\Subscribing;

use Klapuch\Uri;
use Remembrall\Model\Subscribing;
use Remembrall\TestCase;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class CollectiveParts extends TestCase\Database {
	public function testAddingBrandNew() {
		(new Subscribing\CollectiveParts(
			$this->database
		))->add(
			new Subscribing\FakePart('google content', null, 'google snap'),
			new Uri\FakeUri('www.google.com'),
			'//p'
		);
		$statement = $this->database->prepare('SELECT * FROM parts');
		$statement->execute();
		$parts = $statement->fetchAll();
		Assert::count(1, $parts);
		Assert::same('www.google.com', $parts[0]['page_url']);
		Assert::same('google content', $parts[0]['content']);
		Assert::same('google snap', $parts[0]['snapshot']);
		Assert::same('//p', $parts[0]['expression']);
	}

	public function testAddingToOthers() {
		$this->database->exec(
			"INSERT INTO parts (page_url, expression, content, snapshot) VALUES
			('www.google.com', '//google', 'google content', 'google snap')"
		);
		(new Subscribing\CollectiveParts(
			$this->database
		))->add(
			new Subscribing\FakePart('facedown content', null, 'facedown snap'),
			new Uri\FakeUri('www.facedown.cz'),
			'//facedown'
		);
		$statement = $this->database->prepare('SELECT * FROM parts');
		$statement->execute();
		$parts = $statement->fetchAll();
		Assert::count(2, $parts);
		Assert::same('www.google.com', $parts[0]['page_url']);
		Assert::same('google content', $parts[0]['content']);
		Assert::same('google snap', $parts[0]['snapshot']);
		Assert::same('//google', $parts[0]['expression']);
		Assert::same('www.facedown.cz', $parts[1]['page_url']);
		Assert::same('facedown content', $parts[1]['content']);
		Assert::same('facedown snap', $parts[1]['snapshot']);
		Assert::same('//facedown', $parts[1]['expression']);
	}

	public function testAddingWithRecordedVisitation() {
		$this->truncate(['part_visits']);
		(new Subscribing\CollectiveParts(
			$this->database
		))->add(
			new Subscribing\FakePart('<p>Content</p>', null, ''),
			new Uri\FakeUri('www.google.com'),
			'//p'
		);
		$statement = $this->database->prepare(
			"SELECT *
			FROM part_visits
			WHERE visited_at >= NOW() - INTERVAL '1 MINUTE'"
		);
		$statement->execute();
		Assert::count(1, $statement->fetchAll());
	}

	public function testUpdatingAsDuplication() {
		$oldPart = new Subscribing\FakePart('Content', null, 'OLD_SNAP');
		(new Subscribing\CollectiveParts(
			$this->database
		))->add($oldPart, new Uri\FakeUri('www.google.com'), '//p');
		$newPart = new Subscribing\FakePart('NEW_CONTENT', null, 'NEW_SNAP');
		(new Subscribing\CollectiveParts(
			$this->database
		))->add($newPart, new Uri\FakeUri('www.google.com'), '//p');
		$statement = $this->database->prepare('SELECT * FROM parts');
		$statement->execute();
		$parts = $statement->fetchAll();
		Assert::count(1, $parts);
		Assert::same('NEW_CONTENT', $parts[0]['content']);
		Assert::same('NEW_SNAP', $parts[0]['snapshot']);
	}

	public function testUpdatingAsDuplicationWithAllRecordedVisitation() {
		$this->truncate(['part_visits']);
		$part = new Subscribing\FakePart('<p>Content</p>', null, 'snap');
		(new Subscribing\CollectiveParts(
			$this->database
		))->add($part, new Uri\FakeUri('www.google.com'), '//p');
		(new Subscribing\CollectiveParts(
			$this->database
		))->add($part, new Uri\FakeUri('www.google.com'), '//p');
		$statement = $this->database->prepare('SELECT * FROM part_visits');
		$statement->execute();
		Assert::count(2, $statement->fetchAll());
	}

	public function testIterating() {
		$this->database->exec(
			"INSERT INTO parts (page_url, expression, content, snapshot) VALUES
			('www.google.com', '//a', 'a', ''),
			('www.facedown.cz', '//c', 'c', '')"
		);
		$parts = (new Subscribing\CollectiveParts($this->database))->iterate();
		$part = $parts->current();
		Assert::equal('a', $part->content());
		$parts->next();
		$part = $parts->current();
		Assert::equal('c', $part->content());
		$parts->next();
		Assert::null($parts->current());
	}

	public function testIteratingEmptyParts() {
		$parts = (new Subscribing\CollectiveParts($this->database))->iterate();
		Assert::null($parts->current());
	}

	protected function prepareDatabase() {
		$this->purge(['parts']);
	}
}

(new CollectiveParts)->run();