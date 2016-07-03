<?php
/**
 * @testCase
 * @phpVersion > 7.0.0
 */
namespace Remembrall\Integration\Http;

use Remembrall\Model\Storage;
use Tester;
use Dibi;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class Transaction extends Tester\TestCase {
	/** @var Storage\Transaction */
	private $transaction;

	/** @var Dibi\Connection */
	private $database;

	public function setUp() {
		parent::setUp();
		Tester\Environment::lock('transaction', __DIR__ . '/../../Temporary');
		$credentials = parse_ini_file(__DIR__ . '/.database.ini');
		$this->database = new Dibi\Connection($credentials);
		$this->transaction = new Storage\Transaction($this->database);
		$this->database->query('TRUNCATE test');
	}

	public function testSuccessfulTransactionWithReturnedValue() {
		$lastId = $this->transaction->start(
			function() {
				$this->database->query(
					'INSERT INTO test (name) VALUES ("foo")'
				);
				$this->database->query(
					'INSERT INTO test (name) VALUES ("foo2")'
				);
				$foo2Id = $this->database->fetchSingle(
					'SELECT LAST_INSERT_ID()'
				);
				$this->database->query('DELETE FROM test WHERE name = "foo2"');
				return $foo2Id;
			}
		);
		Assert::same(2, $lastId);
		Assert::equal(
			[new Dibi\Row(['ID' => 1, 'name' => 'foo'])],
			$this->database->fetchAll('SELECT * FROM test')
		);
	}

	public function testForcedDriverExceptionWithRollback() {
		$exception = Assert::exception(
			function() {
				$this->transaction->start(
					function() {
						$this->database->query(
							'INSERT INTO test (name) VALUES ("foo")'
						);
						$this->database->query(
							'INSERT INTO test (name) VALUES ("foo2")'
						);
						$this->database->query(
							'SOMETHING STRANGE TO DATABASE!'
						);
					}
				);
			},
			'\RuntimeException',
			'Error on the database side. Rolled back.'
		);
		Assert::type('Dibi\DriverException', $exception->getPrevious());
		Assert::equal(
			[],
			$this->database->fetchAll('SELECT * FROM test')
		);
	}

	public function testForcedGeneralExceptionWithRollback() {
		Assert::exception(
			function() {
				$this->transaction->start(
					function() {
						$this->database->query(
							'INSERT INTO test (name) VALUES ("foo")'
						);
						$this->database->query(
							'INSERT INTO test (name) VALUES ("foo2")'
						);
						throw new \RuntimeException('Forced exception');
					}
				);
			},
			'\RuntimeException',
			'Forced exception'
		);
		Assert::equal(
			[],
			$this->database->fetchAll('SELECT * FROM test')
		);
	}
}

(new Transaction())->run();