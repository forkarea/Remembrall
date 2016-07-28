<?php
/**
 * @testCase
 * @phpVersion > 7.0.0
 */
namespace Remembrall\Integration\Subscribing;

use Remembrall\Model\{
	Subscribing, Access, Http
};
use Remembrall\TestCase;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class OwnedSubscriptions extends TestCase\Database {
    public function testSubscribingBrandNew() {
		$this->database->query(
			'INSERT INTO part_visits (part_id, visited_at) VALUES
			(1, "2000-01-01 01:01:01")'
		);
		$this->database->query(
			'INSERT INTO parts (page_url, expression, content) VALUES
			("www.google.com", "//p", "a")'
		);
        (new Subscribing\OwnedSubscriptions(
			new Access\FakeSubscriber(666),
            $this->database
        ))->subscribe(
			'www.google.com',
			'//p',
            new Subscribing\FakeInterval(
                new \DateTimeImmutable('01:01'),
                null,
                new \DateInterval('PT158M')
            )
        );
		$parts = $this->database->fetchAll(
			'SELECT parts.id, page_url, expression, interval 
			FROM parts
			INNER JOIN subscriptions ON subscriptions.part_id = parts.id'
		);
		Assert::count(1, $parts);
		$part = current($parts);
		Assert::same(1, $part['id']);
		Assert::same('www.google.com', $part['page_url']);
		Assert::same('//p', $part['expression']);
		Assert::same('PT158M', $part['interval']);
		$partVisits = $this->database->fetchAll('SELECT part_id, visited_at FROM part_visits');
		Assert::count(1, $partVisits);
		$partVisit = current($partVisits);
		Assert::same(1, $partVisit['part_id']);
		Assert::same('2000-01-01 01:01:01', (string)$partVisit['visited_at']);
    }

	public function testSubscribingDuplicateWithRollback() {
		$this->database->query(
			'INSERT INTO parts (page_url, expression, content) VALUES
			("www.google.com", "//p", "a")'
		);
		$this->database->query(
			'INSERT INTO part_visits (part_id, visited_at) VALUES
			(1, "2000-01-01 01:01:01")'
		);
		$parts = new Subscribing\OwnedSubscriptions(
			new Access\FakeSubscriber(666),
			$this->database
		);
		$parts->subscribe(
			'www.google.com',
			'//p',
			new Subscribing\FakeInterval(
				new \DateTimeImmutable('01:01'),
				null,
				new \DateInterval('PT158M')
			)
		);
		Assert::exception(function() use($parts) {
			$parts->subscribe(
				'www.google.com',
				'//p',
				new Subscribing\FakeInterval(
					new \DateTimeImmutable('01:01'),
					null,
					new \DateInterval('PT158M')
				)
			);
		}, 'Remembrall\Exception\DuplicateException');
		Assert::count(1, $this->database->fetchAll('SELECT id FROM parts'));
		Assert::count(1, $this->database->fetchAll('SELECT id FROM part_visits'));
	}

	public function testIteratingOwnedSubscriptions() {
		$this->database->query(
			'INSERT INTO parts (page_url, expression, content) VALUES
			("www.google.com", "//a", "a"),
			("www.facedown.cz", "//b", "b"),
			("www.facedown.cz", "//c", "c"),
			("www.google.com", "//d", "d")'
		);
		$this->database->query(
			'INSERT INTO subscriptions (part_id, subscriber_id, interval) VALUES
			(1, 1, "PT1M"),
			(2, 2, "PT2M"),
			(3, 1, "PT3M"),
			(4, 1, "PT4M")'
		);
		$this->database->query(
			'INSERT INTO part_visits (part_id, visited_at) VALUES
			(1, "2000-01-01 01:01:01"),
			(1, "2008-01-01 01:01:01"),
			(2, "2001-01-01 01:01:01"),
			(3, "2002-01-01 01:01:01"),
			(4, "2003-01-01 01:01:01")'
		);
		$parts = (new Subscribing\OwnedSubscriptions(
			new Access\FakeSubscriber(1),
			$this->database
		))->iterate();
		Assert::count(3, $parts);
		Assert::same('//d', (string)$parts[0]->print()['expression']);
		Assert::same('//a', (string)$parts[1]->print()['expression']);
		Assert::same('2008', $parts[1]->print()['interval']->start()->format('Y'));
		Assert::same('//c', (string)$parts[2]->print()['expression']);
	}

    protected function prepareDatabase() {
		$this->truncate(['parts', 'part_visits', 'pages', 'subscriptions']);
		$this->restartSequence(['parts', 'part_visits', 'subscriptions']);
		$this->database->query(
			'INSERT INTO pages (url, content) VALUES
			("www.google.com", "<p>google</p>"),
			("www.facedown.cz", "<p>facedown</p>")'
		);
    }
}

(new OwnedSubscriptions)->run();
