<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use Dibi;
use Remembrall\Exception;
use Remembrall\Model\{
	Access, Storage
};

/**
 * Parts which are owned by the given subscriber
 */
final class OwnedParts implements Parts {
	private $origin;
	private $database;
	private $myself;

	public function __construct(
		Parts $origin,
		Dibi\Connection $database,
		Access\Subscriber $myself
	) {
		$this->origin = $origin;
		$this->database = $database;
		$this->myself = $myself;
	}

	public function subscribe(
		Part $part,
		string $url,
		string $expression,
		Interval $interval
	): Part {
		try {
			(new Storage\Transaction($this->database))->start(
				function() use ($part, $url, $expression, $interval) {
					$this->origin->subscribe(
						$part,
						$url,
						$expression,
						$interval
					);
					$this->database->query(
						'INSERT INTO subscribed_parts
						(part_id, subscriber_id, interval) VALUES
						((SELECT id FROM parts WHERE expression = ? AND page_url = ?), ?, ?)',
						$expression,
						$url,
						$this->myself->id(),
						sprintf('PT%dM', $interval->step()->i)
					);
				}
			);
			return $part;
		} catch(Dibi\UniqueConstraintViolationException $ex) {
			throw new Exception\DuplicateException(
				sprintf(
					'"%s" expression on the "%s" page is already subscribed by you',
					$expression,
					$url
				),
				(int)$ex->getCode(),
				$ex
			);
		}
	}

	public function remove(string $url, string $expression) {
		if(!$this->owned($url, $expression))
			throw new Exception\NotFoundException('You do not own this part');
		$this->database->query(
			'DELETE FROM subscribed_parts
			WHERE subscriber_id = ?
			AND part_id = (SELECT id FROM parts WHERE expression = ? AND page_url = ?)',
			$this->myself->id(),
			$expression,
			$url
		);
	}

	public function iterate(): array {
		return (array)array_reduce(
			$this->database->fetchAll(
				'SELECT parts.content AS part_content, expression, url,
				pages.content AS page_content, interval, (
					SELECT MAX(visited_at)
					FROM part_visits
					WHERE part_id = parts.id
				) AS visited_at
				FROM parts
				INNER JOIN subscribed_parts ON subscribed_parts.part_id = parts.id  
				LEFT JOIN pages ON pages.url = parts.page_url
				WHERE subscriber_id = ?',
				$this->myself->id()
			),
			function($previous, Dibi\Row $row) {
				$previous[] = new ConstantPart(
					new HtmlPart(
						new XPathExpression(
							new ConstantPage($row['page_content']),
							$row['expression']
						),
						new ConstantPage($row['page_content'])
					),
					$row['part_content'],
					$row['url'],
					new DateTimeInterval(
						new \DateTimeImmutable((string)$row['visited_at']),
						new \DateInterval($row['interval'])
					)
				);
				return $previous;
			}
		);
	}

	/**
	 * Checks whether the subscriber really owns the given part
	 * @param string $url
	 * @param string $expression
	 * @return bool
	 */
	private function owned(string $url, string $expression): bool {
		return (bool)$this->database->fetchSingle(
			'SELECT 1
			FROM parts
			INNER JOIN subscribed_parts ON subscribed_parts.part_id = parts.id
			WHERE subscriber_id = ? AND page_url = ? AND expression = ?',
			$this->myself->id(),
			$url,
			$expression
		);
	}
}
