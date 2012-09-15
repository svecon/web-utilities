<?php

class Currencies_UpdateController extends Zend_Controller_Action {

    public function init() {
        // vypnout šablony
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /**
     * Aktualizuje počet položek v exchange_rates a zároveň najde datum poslední položky
     */
    public function updateRecordsCounts() {
        $mExchangeRates = new Currencies_Model_DbTable_ExchangeRates;
        $mCurrencies = new Currencies_Model_DbTable_Currencies;
        
        $sql1 = "UPDATE `{$mCurrencies->getTableName()}` SET `record_count` = (SELECT COUNT(1) FROM `{$mExchangeRates->getTableName()}` WHERE `currency` = `currencies`.`code`)";
        $sql2 = "UPDATE `{$mCurrencies->getTableName()}` SET `record_latest` = (SELECT `date` FROM `{$mExchangeRates->getTableName()}` WHERE `currency` = `currencies`.`code` ORDER BY `date` DESC LIMIT 1) WHERE `record_count` > 0;";

        $statement = $mExchangeRates->getAdapter()->query($sql1)->execute();
        $statement = $mExchangeRates->getAdapter()->query($sql2)->execute();
    }

    /**
     * http://www.ecb.int/stats/exchange/eurofxref/html/index.en.html
     * http://en.wikipedia.org/wiki/List_of_circulating_currencies
     */
    public function indexAction() {
        $monthAgo = date("Y-m-d", strtotime("-30 day"));
        
        $mExchangeRates = new Currencies_Model_DbTable_ExchangeRates;
        $query = $mExchangeRates->getAdapter()->query("SELECT DISTINCT(date) FROM `{$mExchangeRates->getTableName()}` WHERE date >= \"{$monthAgo}\" ORDER BY `date` DESC")->fetchAll();

        $weekDaysCount = 0;
        for ($i = 30; $i >= 0; $i--) {
            $date = date("N", strtotime("-{$i} day"));
            if ($date <= 5)
                $weekDaysCount++;
        }

        echo "Za posledních <b>{$weekDaysCount}</b> pracovních dní byla aktualizace provedena <b>" . count($query) . "x</b>.";
        echo "<br />";
        echo "Poslední aktualizace proběhla: <b>" . $query[0]['date'] . "</b> (" . date('l', strtotime($query[0]['date'])) . ")";

        echo "<hr />";
        $query = $mExchangeRates->getAdapter()->query("SELECT * FROM `{$mExchangeRates->getTableName()}` WHERE `currency` = 'CZK' AND `date` >= \"{$monthAgo}\" ORDER BY `date` DESC")->fetchAll();
        foreach ($query as $data) {
            echo $data['date'] . " # 1 EUR = " . number_format($data['ratio'], 3) . " CZK<br />";
        }
    }

    /**
     * Stáhne informace z posledního možného výpisu (jeden den)
     */
    public function lastdayAction() {
        $xml = file_get_contents("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");
        $sxml = new SimpleXMLElement($xml);

        $date = $sxml->Cube->Cube->attributes()->time;

        $updateRecordCounts = false;
        $i = 0;
        foreach ($sxml->Cube->Cube->Cube as $row) {
            $attribs = $row->attributes();
            $arr[$i]['ratio'] = (float) $attribs->rate;
            $arr[$i]['currency'] = (string) $attribs->currency;
            $arr[$i]['date'] = (string) $date;
            $i++;
        }

        foreach ($arr as $item) {
            $mExchange = new Currencies_Model_DbTable_ExchangeRates;
            $select = $mExchange->select();
            $select
                    ->from($mExchange, array('COUNT(1) AS count'))
                    ->where('currency = ?', $item['currency'])
                    ->where('date = ?', $item['date']);

            $rows = $mExchange->fetchAll($select);
            if (!$rows[0]->count) {
                $mExchange->insert($item);
                $updateRecordCounts = true;
                var_dump($item);
            }
        }

        if ($updateRecordCounts)
            $this->updateRecordsCounts();
    }

    /**
     * Stáhne informace z 90-denního výpisu
     */
    public function lastmonthsAction() {
        $xml = file_get_contents("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-hist-90d.xml");
        $sxml = new SimpleXMLElement($xml);

        $updateRecordCounts = false;
        foreach ($sxml->Cube->Cube as $cube) {

            $date = $cube->attributes()->time;

            $mExchange = new Currencies_Model_DbTable_ExchangeRates;
            $select = $mExchange->select();
            $select
                    ->from($mExchange, array('COUNT(1) AS count'))
                    ->where('date = ?', $date);

            $rows = $mExchange->fetchAll($select);
            if ($rows[0]->count == count($cube->Cube)) {
                echo $date . " -> OK<br />";
                continue;
            }
            
            $i = 0;
            foreach ($cube->Cube as $row) {
                $attribs = $row->attributes();
                $arr[$i]['ratio'] = (float) $attribs->rate;
                $arr[$i]['currency'] = (string) $attribs->currency;
                $arr[$i]['date'] = (string) $date;
                $i++;
            }

            foreach ($arr as $item) {
                $mExchange = new Currencies_Model_DbTable_ExchangeRates;
                $select = $mExchange->select();
                $select
                        ->from($mExchange, array('COUNT(1) AS count'))
                        ->where('currency = ?', $item['currency'])
                        ->where('date = ?', $item['date']);

                $rows = $mExchange->fetchAll($select);
                if (!$rows[0]->count) {
                    $mExchange->insert($item);
                    echo $date . " -> UPDATED<br />";
                    $updateRecordCounts = true;
                }
            }
        }

        if ($updateRecordCounts)
            $this->updateRecordsCounts();
    }

    /**
     * Funkce, která rozparsuje tabulku zemí a měn z wikipedie
     * 
     * http://en.wikipedia.org/wiki/List_of_circulating_currencies
     * 
     * Používá ořezaný soubor: currencies_list.html
     */
    public function parseWikiCurrencies() {
        require APPLICATION_PATH . "/../library/External/SimpleHtmlDom.php";
        $html = file_get_html($_SERVER['DOCUMENT_ROOT'] . "/currencies_list.html");

        $table = $html->find("table[class=wikitable]");

        $table = $table[0]; // actual table

        $i = 0;
        foreach ($table->find('tr') as $tr) {
            $bool = false;
            $td = $tr->find('td');
            if (count($td) == 6) {

                $country = str_replace('&nbsp;', '', $td[0]->plaintext);

                $imgSrc = $td[0]->find('img');
                $imgSrc = $imgSrc[0]->src;

                $currency = preg_replace("/\[[A-Z]\]/", "", $td[1]->plaintext);
                $sign = (($td[2]->innertext));

                $iso = $td[3]->plaintext;
                $unit = preg_replace("/\[[A-Z]\]/", "", $td[4]->plaintext);
                $numberToBasic = $td[5]->plaintext;

                $prevCountry = $country;
                $prevSrc = $imgSrc;
                $bool = true;
            } elseif (count($td) == 5) {
                $country = $prevCountry;
                $imgSrc = $prevSrc;

                $currency = preg_replace("/\[[A-Z]\]/", "", $td[0]->plaintext);
                $sign = (($td[1]->innertext));

                $iso = $td[2]->plaintext;
                $unit = preg_replace("/\[[A-Z]\]/", "", $td[3]->plaintext);
                $numberToBasic = $td[4]->plaintext;
                $bool = true;
            }

            if ($bool) {
                $arr[$i]['currency'] = $currency;
                $arr[$i]['country'] = $country;
                $arr[$i]['code'] = $iso;
                $arr[$i]['sign'] = $sign;
                $arr[$i]['fractional_unit'] = $unit;
                $arr[$i]['number_to_basic'] = $numberToBasic;

                $i++;
            }
        }

        $model = new Currencies_Model_DbTable_Currencies;

        foreach ($arr as $one)
            $model->insert($one);

        $model = new Currencies_Model_DbTable_Countries;
        $all = $model->fetchAll();
        foreach ($all as $row) {
            echo trim(preg_replace("[\(The\)|\(the\)]", "", $row->name)) . "<br />";
            $update['name'] = trim(preg_replace("[\(The\)|\(the\)]", "", $row->name));
            $model->update($update, 'id = ' . $row->id);
        }
    }

    /**
     * Parsuje XML z ECB, která má data od 1999.
     * 
     * XML obsahuje ~110.000 položek (operace trvá ~8 minut)
     */
    public function parseEcbHistory() {
        die("nejdříve stáhněte nový výpis: http://www.ecb.europa.eu/stats/eurofxref/eurofxref-sdmx.xml");
        $file = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/eurofxref-sdmxorig.xml');
        $sXML = new SimpleXMLElement($file);

        $i = 0;
        foreach ($sXML->children()->DataSet->Series as $serie) {
            $currency = (string) $serie->attributes()->CURRENCY;

            foreach ($serie->children() as $data) {
                $arr[$i]['currency'] = $currency;
                $arr[$i]['date'] = (string) $data->attributes()->TIME_PERIOD;
                $arr[$i]['ratio'] = (string) $data->attributes()->OBS_VALUE;

                $i++;
            }
        }

        $model = new Currencies_Model_DbTable_ExchangeRates;
        foreach ($arr as $row) {
            try {
                $model->insert($row);
            } catch (Exception $e) {
                $mCur = new Currencies_Model_DbTable_Currencies;
                $newCurr['code'] = $row['currency'];
                $mCur->insert($newCurr);
                $model->insert($row);
                echo $row['currency'] . "<br />";
            }
        }
    }

}