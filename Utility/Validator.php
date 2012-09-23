<?php

/**
 * Třída obsahuje funkce pro kontrolu ISVS prvků (IČ, RČ)
 * <br />
 * (ISVS = informacni systemy verejne spravy)
 * <br />
 * Kontroly těchto čísel se nevyplatí, protože při vzniku čísel byly výjimky a překlepy.
 *
 * @author Ondřej Švec
 */
class App_KontrolaISVS {

    /**
     * Zkontroluje Identifikační číslo (IČ)
     * <br />
     * <b>Nefunguje pro například pro 72343976</b>
     * @author David Grudl
     * @link http://latrine.dgx.cz/jak-overit-platne-ic-a-rodne-cislo
     * @param string $ic
     * @return bool 
     */
    public static function verifyIC($ic) {
        // "be liberal in what you receive"
        $ic = preg_replace('#\s+#', '', $ic);

        // má požadovaný tvar?
        if (!preg_match('#^\d{8}$#', $ic)) {
            return FALSE;
        }

        // kontrolní součet
        $a = 0;
        for ($i = 0; $i < 7; $i++) {
            $a += $ic[$i] * (8 - $i);
        }

        $a = $a % 11;

        if ($a === 0)
            $c = 1;
        elseif ($a === 10)
            $c = 1;
        elseif ($a === 1)
            $c = 0;
        else
            $c = 11 - $a;

        return (int) $ic[7] === $c;
    }

    /**
     * Zkontroluje Rodné Číslo (RČ)
     * @author David Grudl
     * @link http://latrine.dgx.cz/jak-overit-platne-ic-a-rodne-cislo
     * @param string $rc (######/####)
     * @return bool 
     */
    public static function verifyRC($rc) {
        // "be liberal in what you receive"
        if (!preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $rc, $matches)) {
            return FALSE;
        }

        list(, $year, $month, $day, $ext, $c) = $matches;

        // do roku 1954 přidělovaná devítimístná RČ nelze ověřit
        if ($c === '') {
            return $year < 54;
        }

        // kontrolní číslice
        $mod = ($year . $month . $day . $ext) % 11;
        if ($mod === 10)
            $mod = 0;
        if ($mod !== (int) $c) {
            return FALSE;
        }

        // kontrola data
        $year += $year < 54 ? 2000 : 1900;

        // k měsíci může být připočteno 20, 50 nebo 70
        if ($month > 70 && $year > 2003)
            $month -= 70;
        elseif ($month > 50)
            $month -= 50;
        elseif ($month > 20 && $year > 2003)
            $month -= 20;

        if (!checkdate($month, $day, $year)) {
            return FALSE;
        }

        // cislo je OK
        return TRUE;
    }

    /**
     * Zkontroluje Kód plátce daně (DIČ)
     * <br />
     * Funkce propustí všechny cizince!
     * @author Filip Oščádal
     * @link http://latrine.dgx.cz/jak-overit-platne-ic-a-rodne-cislo#comment-12097
     * @param string $dic (CZ-#########)
     * @return bool
     */
    public static function verifyDIC($dic) {
        $id = strtoupper(substr($dic, 0, 2));
        $x = substr($dic, 2);
        if (in_array($id, array('CZ', 'SK'))) {
            $xlen = strlen($x);
            if ($xlen < 8) {
                return false;
            } elseif ($xlen > 11) {
                return false;
            } elseif ($xlen == 8) {
                return self::verifyIC($x);
            } else {
                return self::verifyRC($x);
            }
        }
        return true;
    }

}