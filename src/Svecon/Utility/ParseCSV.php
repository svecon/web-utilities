<?php

namespace Svecon\Utility;

/**
 * Description of CSV
 *
 * @author svec
 */
class ParseCsv {

	public static function parseCSV($headers = true) {
		$text = $this->text;
		$lines = explode("\r\n", $text);

		if ($headers) {
			$h = explode(';', $lines[0]);
		}

		$data = array();
		for ($i = $headers ? 1 : 0; $i < count($lines); $i++) {
			$item = explode(';', $lines[$i]);

			for ($j = 0; $j < count($item); $j++) {
				if ($headers) {
					if (in_array('id', $h))
						$data[$item[array_search('id', $h)]][$h[$j]] = $item[$j];
					else
						$data[$i - 1][$h[$j]] = $item[$j];
				}
				else {
					$data[$i][$j] = $item[$j];
				}
			}
		}

		return $data;
	}

	public function parseCSVFile($filename, $headers = true) {
		return self::parseCSV(file_get_contents($filename), $headers);
	}

	public static function usageExample() {
		$text = "Name,Art,History,Math,English,Science
Vito Corleone,87,85,72,65,70
Michael Corleone,68,82,90,70,96
Santino Corleone,93,80,81,87,89
Fredo Corleone,62,80,62,62,99
Tom Hagen,77,62,70,83,85";

		$parsed = self::parseCSV($text);
	}

}

?>
