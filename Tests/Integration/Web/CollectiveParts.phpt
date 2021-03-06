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
		(new Misc\SamplePart($this->database, ['content' => 'a']))->try();
		(new Misc\SamplePart($this->database, ['content' => 'b']))->try();
		(new Misc\SamplePart($this->database, ['content' => 'c']))->try();
		(new Misc\SampleSubscription($this->database, ['part' => 1]))->try();
		(new Misc\SampleSubscription($this->database, ['part' => 1]))->try();
		(new Misc\SampleSubscription($this->database, ['part' => 2]))->try();
		(new Misc\SampleSubscription($this->database, ['part' => 4]))->try();
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
		(new Misc\SamplePart($this->database))->try();
		(new Misc\SamplePart($this->database))->try();
		(new Misc\SamplePart($this->database))->try();
		Assert::same(3, (new Web\CollectiveParts($this->database))->count());
	}

	public function testIteratingPrinting() {
		$parts = (new Web\CollectiveParts(
			$this->database
		))->all(new Dataset\FakeSelection(''));
		Assert::null($parts->current());
	}
}

(new CollectiveParts)->run();