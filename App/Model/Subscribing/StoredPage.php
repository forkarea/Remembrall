<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use Klapuch\Storage;
use Klapuch\Uri;

/**
 * Page stored in the database
 */
final class StoredPage implements Page {
	private $origin;
	private $url;
	private $database;

	public function __construct(Page $origin, Uri\Uri $url, \PDO $database) {
		$this->origin = $origin;
		$this->url = $url;
		$this->database = $database;
	}

	public function content(): \DOMDocument {
		$content = new DOM();
		$content->loadHTML(
			(new Storage\ParameterizedQuery(
				$this->database,
				'SELECT content
				FROM pages
				WHERE url IS NOT DISTINCT FROM ?',
				[$this->url->reference()]
			))->field()
		);
		return $content;
	}

	public function refresh(): Page {
		$refreshedPage = $this->origin->refresh();
		(new Storage\ParameterizedQuery(
			$this->database,
			'UPDATE pages
			SET content = ?
			WHERE url IS NOT DISTINCT FROM ?',
			[$refreshedPage->content()->saveHTML(), $this->url->reference()]
		))->execute();
		return $this;
	}
}