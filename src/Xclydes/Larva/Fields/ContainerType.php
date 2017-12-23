<?php

namespace Kris\LaravelFormBuilder\Fields;

class ContainerType extends ParentType
{


    /**
     * @inheritdoc
     */
    protected function getTemplate()
    {
        return 'field_container';
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
}
