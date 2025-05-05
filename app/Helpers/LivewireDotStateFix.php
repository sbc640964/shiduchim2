<?php

namespace App\Helpers;

class LivewireDotStateFix
{
    public static function fix($state)
    {
        if (blank($state)) {
            return $state;
        }

        $fixed = $state;

        foreach ($state as $key => $value) {
            // Check if the key has a dot and the corresponding array exists
            if (str_contains($key, '.')) {
                [$baseKey, $subKey] = explode('.', $key, 2);

                // If the base key is an array and contains the subkey, overwrite the dotted key
                if (isset($state[$baseKey]) && is_array($state[$baseKey]) && array_key_exists($subKey, $state[$baseKey])) {
                    $fixed[$key] = $state[$baseKey][$subKey];
                    unset($fixed[$baseKey]);
                }
            }
        }

        return $fixed;
    }
}
