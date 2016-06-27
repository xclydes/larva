<?php namespace Xclydes\Larva\Fields;

use Kris\LaravelFormBuilder\Fields\CheckableType;

class BooleanType extends CheckableType {

    protected function getTemplate()
    {
        return _XCLYDESLARVA_NS_RESOURCES_ . '::field_boolean';
    }
    
}
