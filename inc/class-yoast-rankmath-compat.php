<?php

class Easy_XML_Sitemap_SEO_Compat {

    public static function detect() {
        return [
            'yoast'     => defined( 'WPSEO_VERSION' ),
            'rankmath'  => defined( 'RANK_MATH_VERSION' ),
        ];
    }

    public static function has_conflict() {
        $detected = self::detect();
        return $detected['yoast'] || $detected['rankmath'];
    }

    public static function admin_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! self::has_conflict() ) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo '<strong>Easy XML Sitemap:</strong> Outro plugin de SEO ativo também gera sitemaps. ';
        echo 'Recomendamos usar apenas um sitemap para evitar confusão nos motores de busca.';
        echo '</p></div>';
    }
}
