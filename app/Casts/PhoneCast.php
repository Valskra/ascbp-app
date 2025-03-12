<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class PhoneCast implements CastsAttributes
{
    /**
     * Convertir la valeur brute depuis la BDD vers la vue
     */
    public function get($model, string $key, $value, array $attributes)
    {
        // Nettoyage/formatage de la valeur en sortie, si besoin
        // Par exemple : "0612345678" → "06.12.34.56.78"
        $digits = preg_replace('/\D/', '', $value ?? '');
        if (strlen($digits) === 10) {
            return substr($digits, 0, 2) . ' ' .
                substr($digits, 2, 2) . ' ' .
                substr($digits, 4, 2) . ' ' .
                substr($digits, 6, 2) . ' ' .
                substr($digits, 8, 2);
        }
        // Retourne la valeur brute si 10 chiffres non détectés
        return $value;
    }

    /**
     * Convertir la valeur quand on l’enregistre dans la BDD
     */
    public function set($model, string $key, $value, array $attributes)
    {
        // Nettoyage de la valeur pour ne stocker que les chiffres
        return preg_replace('/\D/', '', $value ?? '');
    }
}
