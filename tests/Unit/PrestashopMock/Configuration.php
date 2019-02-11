<?php

class Configuration
{
    /**
     * @param string $key
     * @return null|int
     */
    public static function get($key)
    {
        if ($key === 'PS_LANG_DEFAULT') {
            return 1;
        }

        return null;
    }

}
