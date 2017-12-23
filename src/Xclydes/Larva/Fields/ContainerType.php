<?php
namespace Xclydes\Larva\Fields;

use Kris\LaravelFormBuilder\Fields\ParentType;

class ContainerType extends ParentType
{


    /**
     * @inheritdoc
     */
    protected function getTemplate()
    {
        return xclydes_larva_resouce('field_container' );
    }

    public function appendChild( $newField ) {
        array_push($this->children, $newField);
        return $this;
    }

    /**
     * @return mixed|void
     */
    protected function createChildren()
    {
        $this->children = [];
    }

    /**
     * @inheritdoc
     */
    protected function getRenderData() {
        $data = parent::getRenderData();
        $data['children'] = $this->children;
        return $data;
    }

    public function setOptions($options)
    {
        //Add the wrapper options
        $options['wrapper'] = [
            'class' => 'row'
        ];
        return parent::setOptions($options);
    }
}
