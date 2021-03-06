<?php
namespace Xclydes\Larva;

use Xclydes\Larva\Contracts\IFormEloquent;
use Xclydes\Larva\Helpers\LarvaHelper;
use Xclydes\Larva\Metadata\ForeignKey;
use Xclydes\Larva\Metadata\TableColumn;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Xclydes\Larva\Metadata\TableData;

trait FormEloquentTrait {

    /**
     * @return string[]
     */
    protected function getFormProtectedFields() {
        $protFields = [
            Model::CREATED_AT,
            Model::UPDATED_AT,
            IFormEloquent::FIELD_DELETED_AT,
            IFormEloquent::FIELD_CREATED_BY,
            IFormEloquent::FIELD_UPDATED_BY,
            IFormEloquent::FIELD_DELETED_BY,
        ];
        return $protFields;
    }

    /**
     * Indicates whether or not the field specified
     * should be added as field within the form.
     * @param TableColumn $fieldData Details of the column
     * in question.
     * @return boolean
     */
    public function isDisplayedInForm( $fieldData ){
        $fieldName = $fieldData->name;
        $isProtected = false;
        if( method_exists($this, 'getFormProtectedFields')
            && is_array( $this->getFormProtectedFields() ) ) {
            $isProtected = in_array($fieldName, $this->getFormProtectedFields());
        }
        //Assume the form is to be displayed
        return !$this->isGuarded( $fieldName )
            && !$isProtected;
    }

    /**
     * Whether or not the field should be visible in the form.
     * This does not mean it should be otherwise included.
     * @param TableColumn $fieldData TableColumn Details of the column
     * in question.
     * @return boolean
     */
    public function isIncludedInForm($fieldData ){
        $fieldName = $fieldData->name;
        $inc = false;
        $isFillable = empty( $this->fillable ) || in_array($fieldName, $this->fillable);
        $isInternalField = in_array($fieldName, $this->getFormProtectedFields());
        $inc = $fieldName && !$this->isGuarded( $fieldName ) && $isFillable && !$isInternalField;
        return $inc;
    }

    /**
     * Gets the initial list of options to be used
     * for instantiating the field object.
     * @param string $formFieldType The type of field that
     * will be rendered.
     * @param TableColumn $fieldData The field to be checked.
     * @return mixed
     */
    public function getFieldInitOptions($formFieldType, $fieldData) {
        $opts = array();
        //If the field type says entity
        if( $formFieldType == 'entity' ) {
            /** @var $firstFKey  ForeignKey */
            $fKeys = array_values( $fieldData->foreignKeys );
            $firstFKey = array_shift( $fKeys );
            if( $firstFKey != null ) {
                //Get the related table
                $ownerTable = $firstFKey->ownerTableName;
                //Take the first property
                $remoteCols = array_values( $firstFKey->ownerColumns );
                $firstProp= array_shift( $remoteCols );
                //Get the table data
                $ownerTableData = TableData::analyzeTable( $ownerTable );
                //If the table data is valid
                if( $ownerTableData ) {
                    //Get the list of possible classes
                    $clsLst = $ownerTableData->getClasses();
                    //If the list is not empty
                    if( $clsLst->isNotEmpty() ) {
                        //Use the first model
                        $fqN = $clsLst->first();
                        //Add the entity class
                        $opts['class'] = $fqN;
                        $opts['property_key'] = $firstProp;
                        $nameField = $firstProp;
                        $clsChain = class_implements( $fqN );
                        logger()->debug("FQN : '{$fqN}, Class chain ", $clsChain);
                        if( in_array( 'Xclydes\\Larva\\Contracts\\IFormEloquent', $clsChain ) ) {
                            $nameField = $fqN::getDescriptionField();
                        } else {
                            //Use the first text column
                            foreach($ownerTableData->getColumns() as $ownerCol) {
                                logger()->debug('Seeking Text Column...' . print_r($ownerCol, true));
                                /** @var $ownerCol TableColumn */
                                if( $ownerCol->isText ) {
                                    //Use this field
                                    $nameField = $ownerCol->name;
                                    break;
                                }
                            }
                        }
                        $opts['property'] = $nameField;
                        logger()->debug("FQN : '{$fqN}, Key : '{$firstProp}', Name : '{$nameField}'", $opts);
                    }
                }
            }
        }
        return $opts;
    }

    /**
     * Gets the preferred field type for the column
     * specified.
     * @param TableColumn $fieldData The data for the field
     * being processed.
     * @return string
     */
    public function getPreferredFieldType( $fieldData ) {
        //Return null to use default option
        $prefType = null;
        //If the field data has foreign key information
        if( count( $fieldData->foreignKeys ) > 0 ) {
            //Get the list of classes
            $firstEntry = $fieldData->foreignKeys[0];
            $foreignTableName = $firstEntry->ownerTableName;
            $fqN = LarvaHelper::getForeignModel( $foreignTableName );
            //If the FQN exists
            if( class_exists( $fqN ) ) {
                //Use the entity type
                $prefType = 'entity';
            } else {
                //This has to be a select type field
                $prefType = 'select';
            }
        } else {
            $fieldName = $fieldData->name;
            //If the dates attribute is set
            if( property_exists($this, 'dates')
                && is_array( $this->dates )
                && in_array( $fieldName, $this->dates ) ) {
                //Treat it as a date
                $prefType = 'date';
            }
            //If a cast list is defined
            if( property_exists($this, 'casts')
                && is_array( $this->casts ) ) {
                //Get the cast to type
                $castTo = array_get($this->casts, $fieldName, false);
                switch( $castTo ) {
                    case 'boolean':
                        $prefType = 'boolean';
                        break;
                }
            }
        }
        return $prefType;
    }


    /**
     * Gets an array of entries from the model table.
     * @param bool $id The field to be used as the ID. If not set the id field
     * will be determined from the primary key.
     * @param bool $descField The field to be used as the description. If not set,
     * the getDescriptionField method will be called.
     * @param bool $filterClosre A closure to which the query will be supplied
     * for configuring the Builder.
     * @return array The entries found keyed on the ID.
     */
    public static function getDropdownList($id = false, $descField = false, $filterClosre = false ) {
        $items = [];
        $fqn = get_called_class();
        //Get the class
        $ins = new $fqn;
        //If it implements the form eloquent interface
        if( $ins instanceof IFormEloquent ) {
            if( !$descField ) {
                $descField = $fqn::getDescriptionField();
            }
            $qry = $ins::whereNotNull($descField);
            if( $filterClosre instanceof \Closure ) {
                $filterClosre( $qry );
            }
            //If no ID is specified
            if( !$id ) {
                //Get the table data
                $tblData = TableData::analyzeTable( $ins->getTable() );
                $id = $tblData->getKeys()->first()->name;
            }
            $items = $qry->pluck($descField, $id);
        }
        return $items;
    }
} 
