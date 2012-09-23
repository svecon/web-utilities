<?php

class FriendlyURL {

	/** Takes a string a creates cool URL using dashes
	 * @param string $string
	 * @return string
	 * @copyright http://htmlblog.net/seo-friendly-url-in-php/
	 */
	public static function friendlyURL($string) {
		$string = preg_replace("`\[.*\]`U", "", $string);
		$string = preg_replace('`&(amp;)?#?[a-z0-9]+;`i', '-', $string);
		$string = htmlentities($string, ENT_COMPAT, 'utf-8');
		$string = preg_replace("`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);`i", "\\1", $string);
		$string = preg_replace(array("`[^a-z0-9]`i", "`[-]+`"), "-", $string);
		return strtolower(trim($string, '-'));
	}

	/** Vytvoření přátelského URL
	 * @param string řetězec v kódování UTF-8, ze kterého se má vytvořit URL
	 * @return string řetězec obsahující pouze čísla, znaky bez diakritiky, podtržítko a pomlčku
	 * @copyright Jakub Vrána, http://php.vrana.cz/
	 */
	public static function friendly_url($nadpis) {
		$url = $nadpis;
		$url = preg_replace('~[^\\pL0-9_]+~u', '-', $url);
		$url = trim($url, "-");
		$url = iconv("utf-8", "us-ascii//TRANSLIT", $url);
		$url = strtolower($url);
		$url = preg_replace('~[^-a-z0-9_]+~', '', $url);
		return $url;
	}

}
