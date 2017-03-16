<?php

namespace Municipio\Theme;

/**
 * Set theme fonts with constant THEME_FONTS. Eg: 'Roboto,Helvetica,Arial'
 * Use web font with constant WEB_FONT
 */

class Font
{
    public $api_url = 'https://www.googleapis.com/webfonts/v1/webfonts';

    public function __construct()
    {
        if (defined('WEB_FONT')) {
            add_action('admin_init', array($this, 'checkFont'), 10);
            add_action('admin_init', array($this, 'refreshWebFont'), 5);

            add_action('wp_head', array($this, 'renderFontVar'), 5);
            add_action('wp_head', array($this, 'renderFontJS'), 5);
        }
        if (defined('THEME_FONTS')) {
            add_action('wp_head', array($this, 'addFontFamilies'), 200);
        }
    }

    /**
     * Print settings from database about the font face.
     * @return void
     */
    public function renderFontVar()
    {
        echo '<script type="text/javascript">';
        echo '/* <![CDATA[ */ ';

        if (is_multisite()) {
            echo 'var webFont = '. json_encode(array(
                'fontFamily' => get_site_option('theme_font_family'),
                'md5'        => get_site_option('theme_font_md5'),
                'fontFile'   => get_template_directory_uri() . get_site_option('theme_font_file')
            )) . ';';
        } else {
            echo 'var webFont = '. json_encode(array(
                'fontFamily' => get_option('theme_font_family'),
                'md5'        => get_option('theme_font_md5'),
                'fontFile'   => get_template_directory_uri() . get_option('theme_font_file')
            )) . ';';
        }

        echo ' /* ]]> */';
        echo '</script>';
    }

    /**
     * Print js-function in header
     * @param  string $fontFamily Font family to save
     * @return void
     */
    public function renderFontJS()
    {
        if (file_exists(MUNICIPIO_PATH . '/assets/dist/js/font.min.js')) {
            echo '<script type="text/javascript">';
            echo '/* <![CDATA[ */ ';
            readfile(MUNICIPIO_PATH . '/assets/dist/js/font.min.js');
            echo ' /* ]]> */';
            echo '</script>';
        }
    }

    /**
     * Print styling element
     * @return string
     */
    public function addFontFamilies()
    {
        echo '<style> body { font-family: ' . THEME_FONTS . '; } </style>';
    }

    /**
     * Trigger webfont inital save.
     * @return void
     */
    public function checkFont()
    {
        if (!is_multisite() && WEB_FONT != get_option('theme_font_family')) {
            $this->saveFont(WEB_FONT);
        }

        if (is_multisite() && WEB_FONT != get_site_option('theme_font_family')) {
            $this->saveFont(WEB_FONT);
        }
    }

    /**
     * Force refresh of the webfont. Access ?refreshWebFont in admin interface.
     * @return void
     */
    public function refreshWebFont()
    {
        if (isset($_GET['refreshWebFont'])) {

            //Remove file
            unlink(MUNICIPIO_PATH . 'assets/source/fonts/' . str_replace(' ', '_', strtolower(WEB_FONT)) . '.json');

            //Remove options
            if (is_multisite()) {
                delete_site_option('theme_font_md5');
                delete_site_option('theme_font_family');
                delete_site_option('theme_font_file');
            } else {
                delete_option('theme_font_md5');
                delete_option('theme_font_family');
                delete_option('theme_font_file');
            }

            //Die (Tell us that it has been done)
            wp_die("The font settings cache has been trashed.");
        }
    }

    /**
     * Save new font
     * @param  string $fontFamily Font family to save
     * @return void
     */
    public function saveFont($fontFamily)
    {
        $fontFile = str_replace(' ', '_', strtolower($fontFamily)) . '.json';
        $allowed_styles = array('regular', '600', '700', 'italic', '600italic', '700italic',);
        $md5 = '';

        if (file_exists(MUNICIPIO_PATH . 'assets/source/fonts/' . $fontFile)) {

            $file_content   = file_get_contents(MUNICIPIO_PATH . 'assets/source/fonts/' . $fontFile);
            $file_object    = json_decode($file_content);
            $md5            = $file_object->md5;

        } else {

            $url        = (defined('GOOGLE_FONT_KEY')) ? $this->api_url . '?key=' . GOOGLE_FONT_KEY : null;
            $fonts_json = $this->getFontList($url);

            //Valid json
            if ($fonts_json) {

                //Parsing of font file
                $font_array = json_decode($fonts_json, true);
                $font_key   = array_search($fontFamily, array_column($font_array['items'], 'family'));
                $font       = $font_array['items'][$font_key];

                if (! empty($font)) {
                    $font_string = '';

                    // Filter allowed styles/weights
                    $styles = array_intersect_key($font['files'], array_flip($allowed_styles));
                    foreach ((array) $styles as $key => $file_url) {
                        $font_string .= $this->getFontString($font['family'], $key, $file_url);
                    }
                    $md5 = md5($font_string);

                    // Complete json string
                    $json_string    = '{"md5":"' . $md5 . '","value":"' . $font_string . '"}';
                    $json_file      = fopen(MUNICIPIO_PATH . 'assets/source/fonts/' . $fontFile, 'w');

                    // Write to file
                    fwrite($json_file, $json_string);
                    fclose($json_file);
                }
            }
        }

        $this->updateFontOptions($md5, $fontFamily, $fontFile);
    }

    /**
     * Update database with the new font settings
     * @param  string $md5         Font hash
     * @param  string $fontFamily  Font style name
     * @param  string $fontFile    Font path
     * @return void
     */

    public function updateFontOptions($md5, $fontFamily, $fontFile)
    {
        if (is_multisite()) {
            update_site_option('theme_font_md5', $md5);
            update_site_option('theme_font_family', $fontFamily);
            update_site_option('theme_font_file', '/assets/source/fonts/' . $fontFile);
        } else {
            update_option('theme_font_md5', $md5);
            update_option('theme_font_family', $fontFamily);
            update_option('theme_font_file', '/assets/source/fonts/' . $fontFile);
        }
    }

    /**
     * Download font and convert to base64 encoded string
     * @param  string $fontFamily Font family name
     * @param  string $key         Font style name
     * @param  string $url         External font url
     * @return string
     */
    public function getFontString($fontFamily, $key, $url)
    {
        // Download font from url
        $file = file_get_contents($url);
        if ($file === false) {
            return '';
        }

        $font_style  = 'normal';
        $font_weight = 'normal';
        switch ($key) {
            case (is_numeric($key) ? true : false) :
                $font_weight = $key;
                break;
            case (ctype_alpha($key) ? true : false) :
                $font_style = $key;
                break;
            case (ctype_alnum($key) ? true : false) :
                preg_match('/[a-z]+/', $key, $match);
                $font_style = $match[0];
                preg_match('/[0-9]+/', $key, $match);
                $font_weight = $match[0];
                break;
            default:
                break;
        }

        // Base64 encode font file
        $base64 = 'data:application/x-font-woff' . ';base64,' . base64_encode($file);
        $font_string .= '@font-face {\n  font-family: \'' . $fontFamily . '\';\n  font-style: ' . $font_style . ';\n  font-weight: ' . $font_weight . ';\n  src: local(\'' . $fontFamily . '\'), local(\'' . $fontFamily . '-'. ucfirst($key) . '\'), url(' . $base64 . ') format(\'woff\');\n}\n';

        return $font_string;
    }

    /**
     * Get Google font list as json. Update existing list if possible
     * @param  string $url Google font url with api key
     * @return string      Json file content
     */
    public function getFontList($url = null)
    {
        $font_list  = MUNICIPIO_PATH . 'assets/source/fonts/google_fonts.json';
        $fonts_json = null;

        if (function_exists('wp_remote_get') && $url != null) {
            $response = wp_remote_get($url);
            if (isset($response['body']) && $response['body']) {
                // Save new font list to file
                if (strpos($response['body'], 'error') === false) {
                    $fonts_json = $response['body'];
                    file_put_contents($font_list, $fonts_json);
                }
            }
        }

        // Get local file
        if (!$fonts_json) {
            $fonts_json = file_get_contents($font_list);
        }

        return $fonts_json;
    }
}
