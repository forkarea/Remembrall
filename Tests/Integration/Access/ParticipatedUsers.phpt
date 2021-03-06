<?php
declare(strict_types = 1);
/**
 * @testCase
 * @phpVersion > 7.1
 */
namespace Remembrall\Integration\Access;

use Klapuch\Access;
use Klapuch\Encryption;
use Remembrall\Misc;
use Remembrall\Model;
use Remembrall\TestCase;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class ParticipatedUsers extends \Tester\TestCase {
	use TestCase\Database;

	public function testRegisteringWithoutTransferring() {
		(new Misc\SampleParticipant($this->database, ['subscription' => 1]))->try();
		(new Misc\SampleSubscription($this->database))->try();
		(new Model\Access\ParticipatedUsers(
			new Access\UniqueUsers($this->database, new Encryption\FakeCipher()),
			$this->database
		))->register('me@participant.cz', '123', 'member');
		(new Misc\TableCount($this->database, 'participants', 1))->assert();
		(new Misc\TableCount($this->database, 'invitation_attempts', 1))->assert();
		(new Misc\TableCount($this->database, 'users', 1))->assert();
		(new Misc\TableCount($this->database, 'subscriptions', 1))->assert();
	}

	public function testTransferringInheritSubscriptions() {
		$this->database->exec(
			"INSERT INTO subscriptions (user_id, part_id, interval, last_update, snapshot) VALUES
			(3, 3, 'PT10S', '2000-01-01', 'abc'),
			(3, 4, 'PT20S', '2001-01-01', 'def'),
			(3, 5, 'PT30S', '2002-01-01', 'ghi')"
		);
		$novice = 'me@participant.cz';
		(new Misc\SampleParticipant($this->database, ['email' => $novice, 'subscription' => 1, 'accepted' => true]))->try();
		(new Misc\SampleParticipant($this->database, ['email' => $novice, 'subscription' => 2, 'accepted' => false]))->try();
		(new Misc\SampleParticipant($this->database, ['email' => $novice, 'subscription' => 3, 'accepted' => true]))->try();
		(new Misc\SampleParticipant($this->database, ['subscription' => 3, 'accepted' => true]))->try();
		$user = (new Model\Access\ParticipatedUsers(
			new Access\UniqueUsers($this->database, new Encryption\FakeCipher()),
			$this->database
		))->register($novice, '123', 'member');
		(new Misc\TableCount($this->database, 'participants', 2))->assert();
		(new Misc\TableCount($this->database, 'invitation_attempts', 2))->assert();
		(new Misc\TableCount($this->database, 'users', 1))->assert();
		(new Misc\TableCount($this->database, 'subscriptions', 5))->assert();
		$subscriptions = $this->database->query('SELECT * FROM subscriptions ORDER BY id')->fetchAll();
		Assert::same(
			[$user->id()],
			array_unique([
				(string) $subscriptions[3]['user_id'],
				(string) $subscriptions[4]['user_id'],
			])
		);
		Assert::same(3, $subscriptions[3]['part_id']);
		Assert::same(5, $subscriptions[4]['part_id']);

		Assert::same('PT10S', $subscriptions[3]['interval']);
		Assert::same('PT30S', $subscriptions[4]['interval']);

		Assert::contains('2000-01-01 00:00:00', $subscriptions[3]['last_update']);
		Assert::contains('2002-01-01 00:00:00', $subscriptions[4]['last_update']);

		Assert::same('abc', $subscriptions[3]['snapshot']);
		Assert::same('ghi', $subscriptions[4]['snapshot']);
	}

	public function testTransferringWithCaseInsensitiveEmail() {
		(new Misc\SampleParticipant(
			$this->database,
			['email' => 'ME@participant.cz', 'subscription' => 1, 'accepted' => true]
		))->try();
		(new Misc\SampleSubscription($this->database))->try();
		(new Model\Access\ParticipatedUsers(
			new Access\UniqueUsers($this->database, new Encryption\FakeCipher()),
			$this->database
		))->register('me@participant.cz', '123', 'member');
		(new Misc\TableCount($this->database, 'participants', 0))->assert();
		(new Misc\TableCount($this->database, 'invitation_attempts', 0))->assert();
		(new Misc\TableCount($this->database, 'subscriptions', 2))->assert();
		$this->clear();
		(new Misc\SampleParticipant(
			$this->database,
			['email' => 'me@participant.cz', 'subscription' => 1, 'accepted' => true]
		))->try();
		(new Misc\SampleSubscription($this->database))->try();
		(new Model\Access\ParticipatedUsers(
			new Access\UniqueUsers($this->database, new Encryption\FakeCipher()),
			$this->database
		))->register('ME@participant.cz', '123', 'member');
		(new Misc\TableCount($this->database, 'participants', 0))->assert();
		(new Misc\TableCount($this->database, 'invitation_attempts', 0))->assert();
		(new Misc\TableCount($this->database, 'subscriptions', 2))->assert();
	}
}

(new ParticipatedUsers)->run();