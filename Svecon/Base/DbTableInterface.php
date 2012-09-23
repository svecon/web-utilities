<?php

namespace Svecon\Base;

interface DbTableInterface {

	/**
	 * Vrací všechny záznamy z databáze
	 * @return Selection
	 */
	public function findAll();

	/**
	 * Vrací vyfiltrované záznamy na základě vstupního pole
	 * (pole array('name' => 'David') se převede na část SQL dotazu WHERE name = 'David')
	 * @param array $by
	 * @return Selection
	 */
	public function findAllBy($condition, $parameters = array());

	/**
	 * To samé jako findBy akorát vrací vždy jen jeden záznam
	 * @return Selection
	 */
	public function findOneBy($condition, $parameters = array());

	/**
	 * Vraci radek dle primarniho klice.
	 * @param mixed Hodnota primarniho klice
	 * @return false|ActiveRow
	 */
	public function getOne($primaryId);

	/**
	 * Inserts row in a table.
	 * @param array Data pro vlozeni
	 * @return false|int|ActiveRow
	 */
	public function insert($data);

	/**
	 *
	 * @param array Data upravovaneho radku
	 * @return FALSE|ActiveRow
	 */
	public function update($data);

	/**
	 * Smaze radek v tabulce
	 * @param mixed Hodnota primarniho klice mazaneho radku.
	 * @return int|FALSE Number of affected rows or FALSE in case of an error
	 */
	public function delete($primaryId);
}