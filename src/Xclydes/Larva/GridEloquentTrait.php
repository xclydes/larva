<?php
namespace Xclydes\Larva;

use Illuminate\Database\Eloquent\Model;
use Xclydes\Larva\Contracts\IFormEloquent;
use Xclydes\Larva\Metadata\TableColumn;

trait GridEloquentTrait {

    private static $tableModels = [];

    /**
     * @return string[]
     */
    protected function getProtectedFields() {
        return [
            Model::CREATED_AT,
            Model::UPDATED_AT,
            IFormEloquent::FIELD_DELETED_AT,
            IFormEloquent::FIELD_CREATED_BY,
            IFormEloquent::FIELD_UPDATED_BY,
            IFormEloquent::FIELD_DELETED_BY,
        ];
    }

    /**
     * Gets the preferred value formatter for the column
     * specified.
     * @param TableColumn $fieldData The data for the field
     * being processed.
     * @return string
     */
    public function getGridValueFormatter( $fieldData ) {
        return null;
    }

    /**
     * Gives the implementation an opportunity to
     * customize the column being used for the grid.
     * @param TableColumn $fieldData TableColumn Details of the column
     * in question.
     * @return Column The column instance to be rendered.
     */
    function getGridColumn( $fieldData ){
        return null;
    }

    /**
     * Indicates whether or not the field specified
     * should be added as field within the form.
     * @param TableColumn $fieldData Details of the column
     * in question.
     * @return boolean
     */
    public function isDisplayedInGrid( $fieldData ){
        $fieldName = $fieldData->name;
        //Assume the form is to be displayed
        return !$this->isGuarded( $fieldName )
            && !in_array($fieldName, $this->getProtectedFields());
    }

    /**
     * Gets the grid components to be added, if any.
     * @return mixed The array of components to be
     * added.
     */
    public function getGridComponents(){
        return null;
    }

    /**
     * @return mixed
     */
    public function getGridProviderOptions(){
        return null;
    }
}
