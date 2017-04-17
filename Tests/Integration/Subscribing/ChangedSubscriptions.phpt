<?php
declare(strict_types = 1);
/**
 * @testCase
 * @phpVersion > 7.1
 */
namespace Remembrall\Integration\Subscribing;

use Gajus\Dindent;
use Klapuch\Dataset;
use Nette\Mail;
use Remembrall\Model\Subscribing;
use Remembrall\Model\Web;
use Remembrall\TestCase;
use Tester\Assert;
use Texy;

require __DIR__ . '/../../bootstrap.php';

final class ChangedSubscriptions extends TestCase\Database {
	public function testChangedSnapshotAndPastDate() {
		$this->database->exec(
			"INSERT INTO parts (page_url, expression, content, snapshot) VALUES 
			('a', '//a', 'ac', 'as'),
			('www.matched.com', '//matched', 'bc', 'bs'),
			('c', '//c', 'cc', 'cs'),
			('d', '//d', 'dc', 'ds'),
			('e', '//e', 'ec', 'es')"
		);
		$this->database->exec(
			"INSERT INTO subscriptions (user_id, part_id, interval, last_update, snapshot) VALUES 
			(1, 1, 'PT10S', '2000-01-01', 'as'),
			(2, 2, 'PT10S', '2002-01-01', 'changed'),
			(3, 3, 'PT10S', NOW(), 'changed but time is recent'),
			(4, 4, 'PT10S', NOW(), 'ds'),
			(5, 5, 'PT10S', '2001-01-01', 'es')"
		);
		$this->database->exec(
			"INSERT INTO users (id, email, password) VALUES 
			(1, 'a@a.cz', 'a'),
			(2, 'b@b.cz', 'b'),
			(3, 'c@c.cz', 'c'),
			(4, 'd@d.cz', 'd'),
			(5, 'e@e.cz', 'e')"
		);
		$subscriptions = (new Subscribing\ChangedSubscriptions(
			new Subscribing\FakeSubscriptions(),
			new Mail\SendmailMailer(),
			$this->database,
			new Texy\Texy(),
			new Dindent\Indenter()
		))->all(new Dataset\FakeSelection(''));
		$subscription = $subscriptions->current();
		Assert::equal(
			new Subscribing\EmailSubscription(
				new Subscribing\StoredSubscription(2, $this->database),
				new Mail\SendmailMailer(),
				'b@b.cz',
				new Web\FormattedPart(
					new Web\ConstantPart(
						new Web\FakePart(),
						'bc',
						'bs',
						[
							'url' => 'www.matched.com',
							'expression' => '//matched',
							'content' => 'bc',
						]
					),
					new Texy\Texy(),
					new Dindent\Indenter()
				)
			),
			$subscription
		);
		$subscriptions->next();
		Assert::null($subscriptions->current());
	}

	public function testTemplateFields() {
		$this->database->exec(
			"INSERT INTO parts (page_url, expression, content, snapshot) VALUES 
			('www.matched.com', '//matched', 'bc', 'bs')"
		);
		$this->database->exec(
			"INSERT INTO subscriptions (user_id, part_id, interval, last_update, snapshot) VALUES 
			(1, 1, 'PT10S', '2000-01-01', 'as')"
		);
		$this->database->exec(
			"INSERT INTO users (id, email, password) VALUES 
			(1, 'a@a.cz', 'a')"
		);
		$subscriptions = (new Subscribing\ChangedSubscriptions(
			new Subscribing\FakeSubscriptions(),
			new class implements Mail\IMailer {
				public function send(Mail\Message $message) {
					printf(
						'To: %s',
						implode(array_keys($message->getHeader('To')))
					);
					printf('Subject: %s', $message->getSubject());
					printf('Body: %s', $message->getHtmlBody());
				}
			},
			$this->database,
			new Texy\Texy(),
			new Dindent\Indenter()
		))->all(new Dataset\FakeSelection(''));
		$subscription = $subscriptions->current();
		ob_start();
		$subscription->notify();
		$output = ob_get_clean();
		Assert::contains('www.matched.com', $output);
		Assert::contains('//matched', $output);
		Assert::contains('bc', $output);
	}

	public function testEmptyIterating() {
		$subscriptions = (new Subscribing\ChangedSubscriptions(
			new Subscribing\FakeSubscriptions(),
			new Mail\SendmailMailer(),
			$this->database,
			new Texy\Texy(),
			new Dindent\Indenter()
		))->all(new Dataset\FakeSelection(''));
		Assert::null($subscriptions->current());
	}

	protected function prepareDatabase(): void {
		$this->purge(['parts', 'subscriptions', 'users', 'part_visits']);
	}
}

(new ChangedSubscriptions)->run();