<?php
/**
 * Created by PhpStorm.
 * User: Xclyd
 * Date: 12/29/2017
 * Time: 4:55 PM
 */

namespace Xclydes\Larva;

use ViewComponents\Eloquent\EloquentDataProvider;
use ViewComponents\Grids\Component\Column;
use ViewComponents\Grids\Grid;
use Xclydes\Larva\Contracts\IGridEloquent;
use Xclydes\Larva\Helpers\LarvaHelper;
use Xclydes\Larva\Metadata\TableColumn;
use Xclydes\Larva\Metadata\TableData;

class EloquentGrid extends Grid
{

    private $instance;
    private $tblData;

    public function __construct( $inst )
    {
        //Set the instance
        $this->instance = $inst;
        //Pass on to the parent
        parent::__construct($this->createDataProvider(), $this->createComponents());
    }

    protected function createDataProvider() {
        $opts =[];
        //Use the model instance
        $inst = $this->getModel();
        //Does the model support the advanced features?
        if( $inst instanceof IGridEloquent ) {
            //Get the provider options
            $provOpts = $inst->getGridProviderOptions();
            if( is_a( $provOpts ) ) {
                $opts = array_merge($provOpts, $opts);
            }
        }
        return new EloquentDataProvider(get_class( $inst), $opts);
    }

    protected function createComponents() {
        $components = [];
        //Use the model instance
        $inst = $this->getModel();
        //Get the table name
        $tblName = $inst->getTable();
        //Analyse the table
        $this->tblData = TableData::analyzeTable( $tblName );
        //Get the columns
        $tblColumns = $this->tblData->getColumns();
        //Does the model support the advanced features?
        $gridSupport = $inst instanceof IGridEloquent;
        //Process the field names
        /** @var $fieldData TableColumn */
        foreach( $tblColumns as $fieldData ) {
            //Assume the type is to be resolved
            $gridColumn = null;
            $gridValueFormatter = null;
            //If the model is a grid eloquent
            if( $gridSupport ) {
                //Update the table data
                $fieldData->isDisplayed = $inst->isDisplayedInGrid( $fieldData );
                //echo "{$fieldName} => Displayed? {$displayed}, Included? {$included}<br />";
                if( !$fieldData->isDisplayed ) {
                    //Skip it
                    continue;
                }
                //Get the column instance to be rendered
                $gridColumn = $inst->getGridColumn( $fieldData );
                //Get the value formatter to use
                $gridValueFormatter = $inst->getGridValueFormatter( $fieldData );
            } else {
                //Get the value formatter to use
                $gridValueFormatter = $this->createValueFormatter(  $fieldData );
            }
            //If the column is not valid
            if( !$gridColumn ) {
                $gridColumn = $this->createGridColumn( $fieldData );
            }
            //If a formatter is set
            if( $gridValueFormatter ) {
                //Set the value formatter on the column
                $gridColumn->setValueFormatter( $gridValueFormatter );
            }
            //Add this component
            array_push($components, $gridColumn);
        }
        //If the model is a grid eloquent
        if( $gridSupport ) {
            //Get the components to be added
            $xtraComps = $inst->getGridComponents();
            //If it is an array
            if( is_array( $xtraComps ) ) {
                //Add them to return
                $components = array_merge($components, $xtraComps);
            }
        }
        return $components;
    }

    public function getModel() {
        return $this->instance;
    }

    protected function createGridColumn( $fieldData ) {
        //Get the field
        $fieldName = $fieldData->name;
        //Get the name
        $colTitle = LarvaHelper::resolveForDisplay($this->getModel(), $fieldName);
        return new Column($fieldName, $colTitle);
    }

    /**
     * @param TableColumn $fieldData
     * @return null|\Closure
     */
    protected function createValueFormatter($fieldData ) {
        return null;
    }
}