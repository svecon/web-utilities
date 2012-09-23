<?php

namespace Svecon\Nette\Base;

use Nette\ArrayHash;
use Nette\Database\Connection;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\InvalidArgumentException;
use Nette\Object;
use Svecon\Base\DbTableInterface;

class DbTable extends Object implements DbTableInterface {

	/**
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 *
	 * @var String
	 */
	protected $name;

	public function __construct(Connection $connection) {
		$childInstance = get_class($this);
		$this->name = $childInstance::NAME;
		if ($this->name === null)
			throw new InvalidArgumentException('You have to specify table name.');

		$this->connection = $connection;
	}

	/**
	 * Vrací celou tabulku z databáze
	 * @return Selection
	 */
	private function prepareSelection() {
		return $this->connection->table($this->name);
	}

	/**
	 *
	 * @param Array $columnsArray
	 * @param Bool $returnImploded
	 * @return Array|String
	 */
	protected function buildColumns($columnsArray, $returnImploded = true) {
		$buildArray = array();

		foreach ($columnsArray as $tableName => $columns) {
			foreach ($columns as $column) {
				$buildArray[] = "$tableName.$column";
			}
		}
		return $returnImploded ? implode(', ', $buildArray) : $buildArray;
	}

	/**
	 * Filtruje data (z formulare) pro danou tabulku.
	 * @param string
	 * @param ArrayHash
	 * @return array|null
	 */
	protected function filterData($table, ArrayHash $data) {
		$var_name = 'columns_' . $table;
		if (!isset($this->$var_name))
			return NULL;

		$return = array();

		foreach ($this->$var_name as $col) {
			if (!property_exists($data, $col))
				continue;

			$return[$col] = $data[$col];
		}

		return $return;
	}

	public function findAll() {
		return $this->prepareSelection();
	}

	public function findAllBy($condition, $parameters = array()) {
		return $this->prepareSelection()->where($condition, $parameters);
	}

	public function findOneBy($condition, $parameters = array()) {
		return $this->findAllBy($condition, $parameters)->limit(1);
	}

	public function getOne($id) {
		return $this->prepareSelection()->get($id);
	}

	public function insert($data) {
		return $this->prepareSelection()->insert($data);
	}

	public function update($data) {
		$primary_key = $this->prepareSelection()->primary;

		// zde nesmi byt getOne, protoze kdyz by bylo ID NULL, tak by se upravovala defaultni polozka
		$item = $this->prepareSelection()->get($data[$primary_key]);
		$item->update($data);
		return $item;
	}

	public function delete($id) {
		// zde nesmi byt getOne, protoze kdyz by bylo ID NULL, tak by se smazala defaultni polozka
		return $this->prepareSelection()->get($id)->delete();
	}

}
