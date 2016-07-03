<?php
/**
 * @testCase
 * @phpVersion > 7.0.0
 */
namespace Remembrall\Integration\Access;

use Remembrall\Model\{
	Access, Security
};
use Remembrall\TestCase;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class MySqlSubscribers extends TestCase\Database {
	public function testRegisteringBrandNewSubscriber() {
		(new Access\MySqlSubscribers(
			$this->database,
			new Security\FakeCipher()
		))->register('foo@bar.cz', 'passw0rt');
		$subscribers = $this->database->fetchAll(
			'SELECT email, `password` FROM subscribers'
		);
		Assert::count(1, $subscribers);
		$subscriber = current($subscribers);
		Assert::same('foo@bar.cz', $subscriber['email']);
		Assert::same('secret', $subscriber['password']);
	}

	public function testRegistrationWithDuplicatedEmail() {
		$this->database->query(
			'INSERT INTO subscribers (email, `password`) VALUES
			("foo@bar.cz", "secret")'
		);
		Assert::exception(
			function() {
				(new Access\MySqlSubscribers(
					$this->database,
					new Security\FakeCipher()
				))->register('foo@bar.cz', 'passw0rt');
			},
			\Remembrall\Exception\DuplicateException::class,
			'Email "foo@bar.cz" already exists'
		);
	}

    protected function prepareDatabase() {
        $this->database->query('TRUNCATE subscribers');
    }
}

(new MySqlSubscribers)->run();