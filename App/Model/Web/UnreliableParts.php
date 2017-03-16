<?php
declare(strict_types = 1);
namespace Remembrall\Model\Web;

use Klapuch\Dataset;
use Klapuch\Http;
use Klapuch\Storage;
use Klapuch\Uri;

/**
 * All the parts which are no longer trusted as reliable and need to be reloaded
 */
final class UnreliableParts implements Parts {
	private $origin;
	private $database;

	public function __construct(Parts $origin, \PDO $database) {
		$this->origin = $origin;
		$this->database = $database;
	}

	public function add(Part $part, Uri\Uri $uri, string $expression): void {
		$this->origin->add($part, $uri, $expression);
	}

	public function iterate(Dataset\Selection $selection): \Traversable {
		$parts = (new Storage\ParameterizedQuery(
			$this->database,
			$selection->expression(
				"SELECT page_url AS url, expression, parts.id, content, snapshot,
				occurrences
				FROM parts
				RIGHT JOIN (
					SELECT MIN(SUBSTRING(interval FROM '[0-9]+')::INT) AS interval,
					part_id, COUNT(*) AS occurrences
					FROM subscriptions
					GROUP BY part_id
				) AS subscriptions ON subscriptions.part_id = parts.id 
				LEFT JOIN (
					SELECT MAX(visited_at) AS visited_at, part_id
					FROM part_visits
					GROUP BY part_id
				) AS part_visits ON part_visits.part_id = parts.id
				WHERE visited_at + INTERVAL '1 SECOND' * interval < NOW()
				ORDER BY visited_at ASC"
			)
		))->rows();
		foreach ($parts as $part) {
			$url = new Uri\ValidUrl($part['url']);
			$page = new FrugalPage(
				$url,
				new StoredPage(
					new HtmlWebPage(
						new Http\BasicRequest('GET', new Uri\ReachableUrl($url))
					),
					$url,
					$this->database
				),
				$this->database
			);
			yield new ConstantPart(
				new StoredPart(
					new HtmlPart(
						new MatchingExpression(
							new XPathExpression($page, $part['expression'])
						),
						$page
					),
					$part['id'],
					$this->database
				),
				$part['content'],
				$part['snapshot'],
				$part
			);
		}
	}

	public function count(): int {
		return (new Storage\ParameterizedQuery(
			$this->database,
			"SELECT COUNT(*)
			FROM parts
			RIGHT JOIN (
				SELECT MIN(SUBSTRING(interval FROM '[0-9]+')::INT) AS interval,
				part_id
				FROM subscriptions
				GROUP BY part_id
			) AS subscriptions ON subscriptions.part_id = parts.id 
			LEFT JOIN (
				SELECT MAX(visited_at) AS visited_at, part_id
				FROM part_visits
				GROUP BY part_id
			) AS part_visits ON part_visits.part_id = parts.id
			WHERE visited_at + INTERVAL '1 SECOND' * interval < NOW()"
		))->field();
	}
}