<?php
namespace Xclydes\Larva;

use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;
use ViewComponents\Grids\Component\ColumnSortingControl;
use ViewComponents\ViewComponents\Base\ComponentInterface;
use ViewComponents\ViewComponents\Input\InputOption;
use Xclydes\Larva\Contracts\IFormEloquent;
use Xclydes\Larva\Metadata\TableColumn;

trait GridEloquentTrait {

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
     * @return ComponentInterface[] The array of components to be added.
     */
    public function getGridColumnComponents( $fieldData ) {
        //Get the default components
        $defComps = EloquentGrid::getGridColumnComponents($this, $fieldData);
        //If this is sortable
        if( $this->isSortedInGrid( $fieldData ) ) {
            $inputOption = new InputOption('sort', Input::all(), null);
            $sortControl = new ColumnSortingControl($fieldData->name, $inputOption);
            //Add a sort component
            array_push($defComps, $sortControl);
        }
        return $defComps;
    }

    /**
     * Indicates whether or not the field specified
     * should be added as field within the form.
     * @param TableColumn $fieldData Details of the column
     * in question.
     * @return boolean
     */
    public function isDisplayedInGrid( $fieldData ) {
        $fieldName = $fieldData->name;
        $isProtected = false;
        if( method_exists($this, 'getProtectedFields') ) {
            $isProtected = in_array($fieldName, $this->getProtectedFields());
        }
        //Assume the form is to be displayed
        return !$this->isGuarded( $fieldName )
            && !$isProtected;
    }

    /**
     * @param TableColumn $fieldData
     * @return bool
     */
    public function isSortedInGrid( $fieldData ){
        return true;
    }

    /**
     * Gets the grid components to be added, if any.
     * @return ComponentInterface[] The array of components to be
     * added.
     */
    public function getExtraGridComponents() {
        //TODO Add an actions column
        return [];
    }

    /**
     * @return mixed
     */
    public function getGridProviderOptions() {
        return [];
    }
}
