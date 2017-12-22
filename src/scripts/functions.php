<?php
/**
 * Created by PhpStorm.
 * User: Xclyd
 * Date: 12/22/2017
 * Time: 11:29 AM
 */

if( !function_exists('xclydes_larva_resouce') ) {
    function xclydes_larva_resouce( $rsc )
    {
        return _XCLYDESLARVA_NS_RESOURCES_ . '::' . $rsc;
    }
}

if( !function_exists('xclydes_larva_config') ) {
    function xclydes_larva_config( $key, $def) {
        return config( xclydes_larva_resouce( $key ), $def );
    }
}
