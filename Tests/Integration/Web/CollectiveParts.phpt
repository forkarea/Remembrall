<?php
declare(strict_types = 1);
/**
 * @testCase
 * @phpVersion > 7.1
 */
namespace Remembrall\Integration\Web;

use Klapuch\Dataset;
use Klapuch\Output;
use Klapuch\Uri;
use Remembrall\Misc;
use Remembrall\Model\Web;
use Remembrall\TestCase;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class CollectiveParts extends \Tester\TestCase {
	use TestCase\Database;

	public function testAddingBrandNewOne() {
		(new Web\CollectiveParts(
			$this->database
		))->add(
			new Web\FakePart('google content', null, 'google snap'),
			new Uri\FakeUri('www.google.com'),
			'//p',
			'xpath'
		);
		(new Misc\TableCount($this->database, 'parts', 1))->assert();
		$parts = $this->database->query('SELECT * FROM parts')->fetch();
		Assert::same('www.google.com', $parts['page_url']);
		Assert::same('google content', $parts['content']);
		Assert::same('google snap', $parts['snapshot']);
		Assert::same('(//p,xpath)', $parts['expression']);
	}

	public function testAddingToOthers() {
		$parts = new Web\CollectiveParts($this->database);
		$parts->add(
			new Web\FakePart('google content', null, 'google snap'),
			new Uri\FakeUri('www.google.com'),
			'//google',
			'xpath'
		);
		$parts->add(
			new Web\FakePart('facedown content', null, 'facedown snap'),
			new Uri\FakeUri('www.facedown.cz'),
			'//facedown',
			'css'
		);
		(new Misc\TableCount($this->database, 'parts', 2))->assert();
		$parts = $this->database->query('SELECT * FROM parts')->fetchAll();
		Assert::same('www.google.com', $parts[0]['page_url']);
		Assert::same('google content', $parts[0]['content']);
		Assert::same('google snap', $parts[0]['snapshot']);
		Assert::same('(//google,xpath)', $parts[0]['expression']);
		Assert::same('www.facedown.cz', $parts[1]['page_url']);
		Assert::same('facedown content', $parts[1]['content']);
		Assert::same('facedown snap', $parts[1]['snapshot']);
		Assert::same('(//facedown,css)', $parts[1]['expression']);
	}

	public function testAddingWithRecordedVisitation() {
		$this->truncate(['part_visits']);
		(new Web\CollectiveParts(
			$this->database
		))->add(
			new Web\FakePart('<p>Content</p>', null, ''),
			new Uri\FakeUri('www.google.com'),
			'//p',
			'xpath'
		);
		(new Misc\TableCount($this->database, 'part_visits', 1))->assert();
	}

	public function testUpdatingDuplicationForSameLanguage() {
		$oldPart = new Web\FakePart('Content', null, 'OLD_SNAP');
		$newPart = new Web\FakePart('NEW_CONTENT', null, 'NEW_SNAP');
		$parts = new Web\CollectiveParts($this->database);
		$parts->add($oldPart, new Uri\FakeUri('www.google.com'), '//p', 'xpath');
		$parts->add($newPart, new Uri\FakeUri('www.google.com'), '//p', 'xpath');
		$parts->add(new Web\FakePart('CSS', null, 'CSS_SNAP'), new Uri\FakeUri('www.google.com'), '//p', 'css');
		$parts = $this->database->query('SELECT * FROM parts')->fetchAll();
		(new Misc\TableCount($this->database, 'parts', 2))->assert();
		Assert::same('NEW_CONTENT', $parts[0]['content']);
		Assert::same('NEW_SNAP', $parts[0]['snapshot']);
		Assert::contains('xpath', $parts[0]['expression']);
		Assert::same('CSS', $parts[1]['content']);
		Assert::same('CSS_SNAP', $parts[1]['snapshot']);
		Assert::contains('css', $parts[1]['expression']);
	}

	public function testUpdatingDuplicationWithAllRecordedVisitation() {
		$this->truncate(['part_visits']);
		$oldPart = new Web\FakePart('Content', null, 'OLD_SNAP');
		$newPart = new Web\FakePart('NEW_CONTENT', null, 'NEW_SNAP');
		$parts = new Web\CollectiveParts($this->database);
		$parts->add($oldPart, new Uri\FakeUri('www.google.com'), '//p', 'xpath');
		$parts->add($newPart, new Uri\FakeUri('www.google.com'), '//p', 'xpath');
		(new Misc\TableCount($this->database, 'part_visits', 2))->assert();
	}

	public function testIterating() {
		$this->database->exec(
			"INSERT INTO parts (page_url, expression, content, snapshot) VALUES
			('www.google.com', ROW('//a', 'xpath'), 'a', ''),
			('www.seznam.cz', ROW('//b', 'xpath'), 'b', ''),
			('www.facedown.cz', ROW('//c', 'xpath'), 'c', '')"
		);
		$this->database->exec(
			"INSERT INTO subscriptions (user_id, part_id, interval, last_update, snapshot) VALUES
			(1, 1, 'PT1M', NOW(), md5(random()::text)),
			(2, 1, 'PT1M', NOW(), md5(random()::text)),
			(3, 2, 'PT1M', NOW(), md5(random()::text)),
			(4, 4, 'PT1M', NOW(), md5(random()::text))"
		);
		$parts = (new Web\CollectiveParts(
			$this->database
		))->all(new Dataset\FakeSelection(''));
		$part = $parts->current();
		Assert::same('a', $part->content());
		Assert::contains('|occurrences|2|', $part->print(new Output\FakeFormat())->serialization());
		$parts->next();
		$part = $parts->current();
		Assert::same('b', $part->content());
		Assert::contains('|occurrences|1|', $part->print(new Output\FakeFormat())->serialization());
		$parts->next();
		$part = $parts->current();
		Assert::same('c', $part->content());
		Assert::contains('|occurrences|0|', $part->print(new Output\FakeFormat())->serialization());
		$parts->next();
		Assert::null($parts->current());
	}

	public function testCounting() {
		$this->database->exec(
			"INSERT INTO parts (page_url, expression, content, snapshot) VALUES
			('www.google.com', ROW('//a', 'xpath'), 'a', ''),
			('www.seznam.cz', ROW('//b', 'xpath'), 'b', ''),
			('www.facedown.cz', ROW('//c', 'xpath'), 'c', '')"
		);
		$parts = new Web\CollectiveParts($this->database);
		Assert::same(3, $parts->count());
	}

	public function testIteratingPrinting() {
		$parts = (new Web\CollectiveParts(
			$this->database
		))->all(new Dataset\FakeSelection(''));
		Assert::null($parts->current());
	}
}

(new CollectiveParts)->run();