<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use Dibi;
use Remembrall\Model\Access;
use Remembrall\Exception;

final class OwnedPart implements Part {
	private $origin;
	private $url;
	private $expression;
	private $database;
	private $owner;

	public function __construct(
		Part $origin,
		string $url,
		Expression $expression,
		Dibi\Connection $database,
		Access\Subscriber $owner
	) {
		$this->origin = $origin;
		$this->url = $url;
		$this->expression = $expression;
		$this->database = $database;
		$this->owner = $owner;
	}

	public function content(): string {
		if(!$this->owned())
			throw new Exception\NotFoundException('You do not own this part');
		return $this->database->fetchSingle(
			'SELECT content
			FROM parts
			INNER JOIN subscribed_parts ON subscribed_parts.part_id = parts.id
			WHERE subscriber_id = ?
			AND expression = ?
			AND page_url = ?',
			$this->owner->id(),
			(string)$this->expression,
			$this->url
		);
	}

	public function refresh(): Part {
		if(!$this->owned())
			throw new Exception\NotFoundException('You do not own this part');
		return $this->origin->refresh();
	}

	public function print(): array {
		return $this->origin->print() + [
			'expression' => $this->expression,
			'url' => $this->url,
			'subscriber' => $this->owner,
		];
	}

	public function equals(Part $part): bool {
		return $this->content() === $part->content();
	}

	/**
	 * Is the part owned by the given owner?
	 * @return bool
	 */
	private function owned(): bool {
		return (bool)$this->database->fetchSingle(
			'SELECT 1
			FROM subscribed_parts
			WHERE subscriber_id = ?
			AND part_id = (
				SELECT id
				FROM parts
				WHERE expression = ? AND page_url = ?
			)',
			$this->owner->id(),
			(string)$this->expression,
			$this->url
		);
	}
}