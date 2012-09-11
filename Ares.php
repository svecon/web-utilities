<?php

/**
 * Třída pošle dotaz na databázi ekonomických subjektů a pracuje s údaji v XML
 * <br />
 * ARES = Administrativní registr ekonomických subjektů 
 *
 * @author Švec
 * @link http://wwwinfo.mfcr.cz/ares/ares_xml.html.cz#k3
 */
class App_AresXml {

    private $_url = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi';
    private $_response;

    public function search($ico) {
        if (!is_numeric($ico) or strlen($ico) < 7 or strlen($ico) > 8) {
            throw new Exception('IČ musí být číslo o délce 7-8 znaků', 1);
        }

        $client = new Zend_Http_Client($this->_url);
        $client->setParameterGet(
                array(
                    'ico' => $ico
                )
        );

        $this->_response = $client->request('GET');

        if ($this->_response->getStatus() != 200) {
            throw new Exception('HTTP request failed', $this->_response->getStatus());
        }
    }

    public function parseData() {
        if (!($this->_response instanceof Zend_Http_Response)) {
            throw new Exception('HTTP request has not been sent yet', 1);
        }
        $xml = new SimpleXMLElement($this->_response->getBody());

        $ns = $xml->getDocNamespaces();
        $res = $xml->children($ns['are'])->children($ns['D']);

        if (isset($res->E)) {
            throw new Exception('Error: ' . $res->E->ET, 404);
        }

        $sub = $res->VBAS;
        $sub_addr = $sub->AA;

        $data['company'] = trim(strval($sub->OF));
        $data['ic'] = trim(strval($sub->ICO));
        $data['ic_duplicate'] = $data['ic'];
        $data['dic'] = trim(strval($sub->DIC));
        $data['city'] = trim(strval($sub_addr->N));
        $data['country'] = trim(strval($sub_addr->NS));
        $data['zip'] = trim(strval($sub_addr->PSC));
        $data['street'] = trim(strval($sub_addr->NU)) . ' ' . trim(strval($sub_addr->CD));
        if (isset($sub_addr->CO))
            $data['street'] .= '/' . trim(strval($sub_addr->CO));
        
        /**
         * @link http://wwwinfo.mfcr.cz/ares/aresPrFor.html.cz
         */
        $cisloPravniFormy = $sub->PF->KPF;
        if($cisloPravniFormy <= 110) {
            $data['record'] = 'Záznam podnikatele v živnostenském rejstříku vede: ' . $sub->RRZ->ZU->NZU;
        }
        else {
            $data['record'] = 'Záznam společnosti vede: ' . $sub->ROR->SZ->SD->T . ', spisová značka ' . $sub->ROR->SZ->OV;
        }

        return $data;
    }

}