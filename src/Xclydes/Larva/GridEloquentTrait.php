<?php
namespace Xclydes\Larva;

use Illuminate\Support\Facades\Input;
use ViewComponents\Grids\Component\ColumnSortingControl;
use ViewComponents\ViewComponents\Base\ComponentInterface;
use ViewComponents\ViewComponents\Component\Control\PageSizeSelectControl;
use ViewComponents\ViewComponents\Component\Control\PaginationControl;
use ViewComponents\ViewComponents\Input\InputOption;
use ViewComponents\ViewComponents\Input\InputSource;
use Xclydes\Larva\Metadata\TableColumn;

trait GridEloquentTrait {

    protected function getGridProtectedFields() {
        $protFields = [
            Model::CREATED_AT,
            Model::UPDATED_AT,
            IFormEloquent::FIELD_DELETED_AT,
            IFormEloquent::FIELD_CREATED_BY,
            IFormEloquent::FIELD_UPDATED_BY,
            IFormEloquent::FIELD_DELETED_BY,
        ];
        //If there are hidden fields
        if( property_exists($this, 'hidden')
            && is_array( $this->hidden ) ) {
            $protFields = array_merge( $protFields, $this->hidden);
        }
        return $protFields;
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
        if( method_exists($this, 'getGridProtectedFields') ) {
            $isProtected = in_array($fieldName, $this->getGridProtectedFields());
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
        $extComps = [];
        $inputs = new InputSource( Input::all() );
        $defPageSize = xclydes_larva_config('list.page.size', 10);
        array_push($extComps,
            new PaginationControl($inputs->option('page', 1), $defPageSize)
        );
        array_push($extComps,
            new PageSizeSelectControl(
                $inputs->option('page_size', $defPageSize),
                xclydes_larva_config('list.page.steps', [5, 10, 20, 50, 100])
            )
        );
        return $extComps;
    }

    /**
     * @return mixed
     */
    public function getGridProviderOptions() {
        return [];
    }
}
