<?php

class Configuration
{
    const DEFAULT_LANGUAGE = 1;

    /**
     * @param string $key
     * @return null|int
     */
    public static function get($key)
    {
        if ($key === 'PS_LANG_DEFAULT') {
            return self::DEFAULT_LANGUAGE;
        }

        return null;
    }

}
