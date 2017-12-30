<?php
/**
 * Created by PhpStorm.
 * User: Xclyd
 * Date: 12/29/2017
 * Time: 4:55 PM
 */

namespace Xclydes\Larva;

use Illuminate\Support\Facades\Lang;
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
            if( is_array( $provOpts ) ) {
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
            //Create a list of column components
            $colComps = [];
            //If the model is a grid eloquent
            if( $gridSupport ) {
                //Update the table data
                $fieldData->isDisplayed = $inst->isDisplayedInGrid( $fieldData );
                //echo "{$fieldName} => Displayed? {$displayed}, Included? {$included}<br />";
                if( !$fieldData->isDisplayed ) {
                    //Skip it
                    continue;
                }
                $colComps = $inst->getGridColumnComponents( $fieldData );
            } else {
                //Add the column components
                $colComps = self::getGridColumnComponents( $inst, $fieldData );
            }
            //Get the components to be added
            if( is_array( $colComps ) ) {
                //Add the instance components
                $components = array_merge($components, $colComps);
            }
        }
        //If the model is a grid eloquent
        if( $gridSupport ) {
            //Get the components to be added
            $xtraComps = $inst->getExtraGridComponents();
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

    public static function getGridColumnComponents( $model, $fieldData ) {
        $components = [];
        //Get the field
        $fieldName = $fieldData->name;
        $langBundle = LarvaHelper::resolveBundle( $model );
        //Determine the translation key to be be used
        $transKeyBase =  $langBundle . '.' . strtolower( $fieldName );
        $colTitle = null;
        if( Lang::has( $transKeyBase ) ) {
            $colTitle = trans($transKeyBase );
        }
        $column = new Column($fieldName, $colTitle);
        //Generate a value formatter based on the type
        $valueFormatter = self::createValueFormatter( $fieldData );
        if( $valueFormatter instanceof \Closure ) {
            $column->setValueFormatter( $valueFormatter );
        }
        //Store the column
        array_push($components, $column);

        return $components;
    }

    /**
     * @param TableColumn $fieldData
     * @return null|\Closure
     */
    public static function createValueFormatter( $model, $fieldData ) {
        $closure = null;
        $langBundle = LarvaHelper::resolveBundle( $model );
        if( $fieldData->isBoolean ) {
            $closure = function( $value ) use( $langBundle ) {
                //Return the text true or false
                $transKey = "{$langBundle}." . ($value ? 'true' : 'false');
                if( Lang::has( $transKey ) ) {
                    $txt = trans( $transKey );
                } else {
                    $txt = $value ? 'true' : 'false';
                }
                return $txt;
            };
        } else if( $fieldData->isDate && !$fieldData->isTime  ) {
            //This is a date only
        } else if( $fieldData->isDate ) {
            //This is date and time
        } else if( $fieldData->isTime ) {
            //This is a time only
        }
        return $closure;
    }
}