<?php

namespace Municipio\Helper;

class Styleguide
{
    private static $_uri = '//helsingborg-stad.github.io/styleguide-web/dist';
    private static $_devUri = '//hbgprime.dev/dist';

    /**
     * Returns true if development mode is enabled.
     *
     * @return bool
     */
    private static function _isDevMode()
    {
        return (
            (defined('DEV_MODE') && DEV_MODE === true) ||
            (isset($_GET['DEV_MODE']) && $_GET['DEV_MODE'] === 'true')
        );
    }

    /**
     * Returns the base URI of the styleguide.
     *
     * @return string
     */
    private static function _getBaseUri()
    {
        if (self::_isDevMode()) {
            return self::$_devUri;
        }

        if (defined('MUNICIPIO_STYLEGUIDE_URI') && MUNICIPIO_STYLEGUIDE_URI != "") {
            $uri = MUNICIPIO_STYLEGUIDE_URI;
        } else {
            $uri = self::$_uri;
        }

        $uri = rtrim(apply_filters('Municipio/theme/styleguide_uri', $uri), '/');

        if (defined('STYLEGUIDE_VERSION') && STYLEGUIDE_VERSION != "") {
            $uri .= '/' . STYLEGUIDE_VERSION;
        }

        return $uri;
    }

    /**
     * Returns the complete path to a file.
     *
     * @param string $path The path to append.
     *
     * @return string
     */
    public static function getPath($path)
    {
        return self::_getBaseUri() . '/'. $path;
    }

    /**
     * Returns the complete style path.
     *
     * @param bool $isBem Set to get a BEM theme.
     *
     * @return string
     */
    public static function getStylePath($isBem = false)
    {
        $directory = $isBem ? '/css-bem' : '/css';
        $extension = self::_isDevMode() ? 'dev' : 'min';
        $field = get_field('color_scheme', 'option');
        $theme = apply_filters('Municipio/theme/key', $field);

        return self::getPath("$directory/hbg-prime-$theme.$extension.css");
    }

    /**
     * Returns the complete script path.
     *
     * @return string
     */
    public static function getScriptPath()
    {
        $extension = self::_isDevMode() ? 'dev' : 'min';

        return self::getPath("js/hbg-prime.$extension.js");
    }
}