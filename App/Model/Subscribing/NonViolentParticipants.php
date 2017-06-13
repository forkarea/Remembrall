<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use Klapuch\Access;
use Klapuch\Output;
use Klapuch\Storage;

/**
 * All the non-violent participants
 */
final class NonViolentParticipants implements Participants {
	private const ATTEMPTS = 5,
		RELEASE = 12; // five attempts in last 12 hours
	private $author;
	private $database;

	public function __construct(Access\User $author, \PDO $database) {
		$this->author = $author;
		$this->database = $database;
	}

	public function invite(int $subscription, string $email): Invitation {
		if ($this->harassed($subscription, $email)) {
			throw new \OutOfRangeException(
				sprintf(
					'"%s" declined your invitation too many times',
					$email
				)
			);
		}
		return new ParticipantInvitation(
			(new Storage\ParameterizedQuery(
				$this->database,
				'INSERT INTO participants (email, subscription_id, code, invited_at, accepted, decided_at) VALUES
				(?, ?, ?, NOW(), FALSE, NULL)
				ON CONFLICT (email, subscription_id)
				DO UPDATE SET invited_at = NOW()
				RETURNING code',
				[$email, $subscription, bin2hex(random_bytes(32))]
			))->field(),
			$this->database
		);
	}

	public function kick(int $subscription, string $email): void {
		(new Storage\ParameterizedQuery(
			$this->database,
			'DELETE FROM participants
			WHERE email = ?
			AND subscription_id = ?',
			[$email, $subscription]
		))->execute();
	}

	public function all(): \Iterator {
		$participants = (new Storage\ParameterizedQuery(
			$this->database,
			'SELECT participants.id, participants.email, subscription_id, invited_at, accepted, decided_at
			FROM participants
			INNER JOIN subscriptions ON subscriptions.id = participants.subscription_id
			INNER JOIN users ON users.id = subscriptions.user_id
			WHERE user_id = ?
			ORDER BY decided_at DESC',
			[$this->author->id()]
		))->execute();
		foreach ($participants as $participant) {
			yield new class(
				[
					'harassed' => $this->harassed(
						$participant['subscription_id'],
						$participant['email']
					),
				] + $participant
			) implements Participant {
				private $participant;

				public function __construct(array $participant) {
					$this->participant = $participant;
				}

				public function print(Output\Format $format): Output\Format {
					return new Output\FilledFormat($format, $this->participant);
				}
			};
		}
	}

	/**
	 * Was the participant asked too many times for invitation?
	 * @param int $subscription
	 * @param string $email
	 * @return bool
	 */
	private function harassed(int $subscription, string $email): bool {
		return (bool) (new Storage\ParameterizedQuery(
			$this->database,
			"SELECT 1
			FROM invitation_attempts
			WHERE participant_id = (
				SELECT id
				FROM participants
				WHERE subscription_id = ?
				AND email = ?
			)
			AND attempt_at + INTERVAL '1 HOUR' * ? > NOW()
			HAVING COUNT(*) >= ?",
			[$subscription, $email, self::RELEASE, self::ATTEMPTS]
		))->field();
	}
}