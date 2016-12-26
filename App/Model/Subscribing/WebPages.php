<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use Klapuch\{
	Storage, Uri
};

final class WebPages implements Pages {
	private $database;

	public function __construct(\PDO $database) {
		$this->database = $database;
	}

	public function add(Uri\Uri $url, Page $page): Page {
		(new Storage\ParameterizedQuery(
			$this->database,
			'INSERT INTO pages (url, content)
			VALUES (:url, :content)
			ON CONFLICT (url) DO UPDATE
			SET content = :content',
			[
				'url' => $url->reference(),
				'content' => $page->content()->saveHTML(),
			]
		))->execute();
		return new PostgresPage($page, $url, $this->database);
	}
}