<?php

if (!function_exists('getVatNumberFieldLabel')) {
    /**
     * Restituisce l'etichetta del campo Partita IVA in base al paese.
     *
     * @param string $country
     * @return string
     */
    function getVatNumberFieldLabel($country)
    {
        switch ($country) {
            case 'IT':
                return "Partita Iva";
            case 'DE':
                return "USt-IdNr";
            case 'FR':
                return "Numéro de TVA Intracommunautaire";
            case 'ES':
                return "NIF/NIE";
            case 'AU':
                return "UID-Nummer";
            case 'SW':
                return "Momsregistreringsnummer";
            case 'CH':
                return "MWST-Nummer";
            case 'NL':
            case 'BE':
                return "BTW-nummer";
            default:
                return "VAT Number";
        }
    }
}