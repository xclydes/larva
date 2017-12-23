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

    protected function getDefaults()
    {
        $opts = [
            'is_group' => true,
            'wrapper' => [
                'class' => 'row'
            ]
        ];
        return $opts;
    }
}
