<?php namespace Xclydes\Larva\Fields;

use Kris\LaravelFormBuilder\Fields\CheckableType;

class BooleanType extends CheckableType {

    protected function getTemplate()
    {
        return xclydes_larva_resouce('field_boolean' );
    }
    
}
