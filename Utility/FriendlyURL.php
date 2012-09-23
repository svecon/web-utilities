<?php

namespace Svecon\Utility;

class FriendlyURL {

	/** Vytvoření přátelského URL
	 * @param string řetězec v kódování UTF-8, ze kterého se má vytvořit URL
	 * @return string řetězec obsahující pouze čísla, znaky bez diakritiky, podtržítko a pomlčku
	 * @copyright Jakub Vrána, http://php.vrana.cz/
	 * @link http://php.vrana.cz/vytvoreni-pratelskeho-url.php
	 */
	public static function friendlyURL($nadpis) {
		$url = $nadpis;
		$url = preg_replace('~[^\\pL0-9_]+~u', '-', $url);
		$url = trim($url, "-");
		$url = iconv("utf-8", "us-ascii//TRANSLIT", $url);
		$url = strtolower($url);
		$url = preg_replace('~[^-a-z0-9_]+~', '', $url);
		return $url;
	}

	public static function usageExample() {
		$sampleData = "http://svecon.cz/nějaký článek/O Masožravcích";

		echo "json_encode() output:\n", $sampleData;

		$pretty = self::friendlyURL($sampleData);
		echo "\n\nFormatted:\n", $pretty;
	}

}
