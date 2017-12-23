<?php
namespace Xclydes\Larva;

use Kris\LaravelFormBuilder\Fields\ContainerType;
use Kris\LaravelFormBuilder\Fields\FormField;
use Kris\LaravelFormBuilder\Form;
use Xclydes\Larva\Contracts\IFormEloquent;
use Xclydes\Larva\Helpers\LarvaHelper;
use Xclydes\Larva\Metadata\TableColumn;
use Xclydes\Larva\Metadata\TableData;

class EloquentForm extends Form {

    const FIELDTYPE_HIDDEN = 'hidden';
    const FIELDTYPE_STATIC = 'static';

    private $tblData;

    /**
     * @var $headerActionContainer ContainerType
     */
    private $headerActionContainer;

    /**
     * @var $footerActionContainer ContainerType
     */
    private $footerActionContainer;

    /**
     * Gets the TableData this form represents.
     * @return TableData
     */
    public function getTableData() {
        return $this->tblData;
    }

    /**
     * Get the list of fields which should be
     * displayed in the form.
     * @return mixed
     */
    public function getDisplayedFields() {
        $displayedColNames = array();
        $inst = $this->getModel();
        $isFormEloquent = $inst instanceof IFormEloquent;
        //If the model is a form eloquent
        if( $isFormEloquent ) {
            //Ensure the build process was already run
            /** @var $col TableColumn */
            foreach($this->getTableData()->getColumns() as $col) {
                if( $inst->isDisplayedInForm( $col ) ) {
                    array_push($displayedColNames, $col->name);
                }
            }
        } else {
            $displayedColNames = array_keys( $this->getTableData()->_getColumns() );
        }
        $displayedCols = array();
        foreach ($displayedColNames as $displayedColName) {
            $displayedCols[$displayedColName] = LarvaHelper::resolveColumnName( $inst, $displayedColName );
        }
        return $displayedCols;
    }

    /* (non-PHPdoc)
     * @see \Kris\LaravelFormBuilder\Form::buildForm()
     */
    public function buildForm()
    {
        $this->registerCustomFields();
        //Use the model instance
        $inst = $this->getModel();
        //Get the table name
        $tblName = $inst->getTable();
        //Analyse the table
        $this->tblData = TableData::analyzeTable( $tblName );
        //Get the columns
        $tblColumns = $this->tblData->getColumns();
        //Process the field names
        /** @var $fieldData TableColumn */
        foreach( $tblColumns as $fieldData ) {
            //Assume the type is to be resolved
            $formFieldType = null;
            //Does the form support the advanced features?
            $formSupport = $inst instanceof IFormEloquent;
            //If the model is a form eloquent
            if( $formSupport ) {
                //Update the table data
                $fieldData->isDisplayed = $inst->isDisplayedInForm( $fieldData );
                $fieldData->isIncluded = $inst->isIncludedInForm( $fieldData );
                //echo "{$fieldName} => Displayed? {$displayed}, Included? {$included}<br />";
                if( !$fieldData->isDisplayed
                    && !$fieldData->isIncluded ) {
                    //echo "Skip {$fieldName}<br />";
                    //Skip it
                    continue;
                }
                //Mark the type for resolution
                $formFieldType = $inst->getPreferredFieldType( $fieldData );
            }
            //If no field type was specified
            if( !$formFieldType ) {
                //Resolve the type to be used
                $formFieldType = $this->resolveFieldType( $fieldData );
            }
            $options = array();
            //Determine the validation rules to apply
            $validationRules = $this->resolveValidationRules( $fieldData );
            //If rules are specified
            if( $validationRules ) {
                //Set them as part of the options
                $options['rules'] = $validationRules;
            }
            //If the form options are supported
            if( $formSupport ) {
                //Merge any custom options
                $options = array_merge($inst->getFieldInitOptions($formFieldType, $fieldData), $options);
            }
            //echo "Add {$fieldName} => {$formFieldType}. Options: " . print_r($options, true)."<br />";
            //Add the field
            $this->add($fieldData->name, $formFieldType, $options);
        }
        //Sort the list
        uasort ($this->fields, [$this, 'compareFields']);
    }

    /**
     * Render the form.
     *
     * @param array $options
     * @param string $fields
     * @param bool $showStart
     * @param bool $showFields
     * @param bool $showEnd
     * @return string
     */
    protected function render($options, $fields, $showStart, $showFields, $showEnd)
    {
        $formOptions = $this->formHelper->mergeOptions($this->formOptions, $options);

        $this->setupNamedModel();

        return $this->formHelper->getView()
            ->make($this->getTemplate())
            ->with(compact('showStart', 'showFields', 'showEnd'))
            ->with( $this->getRenderData() )
            ->with('formOptions', $formOptions)
            ->with('fields', $fields)
            ->with('model', $this->getModel())
            ->with('exclude', $this->exclude)
            ->with('form', $this)
            ->render();
    }

    /**
     * @return ContainerType
     */
    public function getFooterActionContainer() {
        if( !$this->footerActionContainer ) {
            //Create the footer container
            $this->footerActionContainer = $this->makeField('footer_action_container', 'container', []);
            //Generate the cancel button
            $cancelRoute = $this->getFormOption('route_prefix', false);
            $this->createCancelButton( $this->footerActionContainer, $cancelRoute );
            //Generate the submit button
            $this->createSubmitButton( $this->footerActionContainer );
        }
        return $this->footerActionContainer;
    }

    /**
     * @return ContainerType
     */
    public function getHeaderActionContainer()
    {
        if( !$this->headerActionContainer ) {
            //Create the header container
            $this->headerActionContainer = $this->makeField('header_action_container', 'container', []);
        }
        return $this->headerActionContainer;
    }

    /**
     * Sorts the list of fields provided.
     * @param FormField $fieldA The first field to be compared.
     * @param FormField $fieldB The second field to be compared.
     * @return int The result of the comparison.
     */
    protected function compareFields( $fieldA, $fieldB ) {
        $diff = 0;
        //If both are form fields
        if( $fieldA instanceof FormField
            && $fieldB instanceof FormField  ) {
            //Compare the types
            $diff = $this->getFieldWeight( $fieldA ) - $this->getFieldWeight( $fieldB );
        }
        return $diff;
    }

    /**
     * Determines the weight of the field specified.
     * Fields to be shown first should have lower values
     * than fields to be shown last.
     * @param FormField $field The field to be weighed.
     * @return int The weight assigned.
     */
    protected function getFieldWeight( $field ) {
        //Get the weight from the config
        $weightsArr = xclydes_larva_config('edit.fields.weight', []);
        //Get the default weight
        $defWeight = array_get($weightsArr, '*', 999);
        //Get the weight of the field
        $fieldWeight = ($field instanceof FormField) && $field->getType() ?
            array_get($weightsArr, $field->getType(), $defWeight) :
            $defWeight;
        return $fieldWeight;
    }

    /**
     * Register any custom fields before the form
     * is generated.
     */
    protected function registerCustomFields() {
        $this->addCustomField('boolean', 'Xclydes\Larva\Fields\BooleanType');
        $this->addCustomField('container', 'Xclydes\Larva\Fields\ContainerType');
    }

    /**
     * Create the form cancel buttons on the form with the route
     * provided.
     * @param $appendTo ContainerType The container to which the button
     * should be added.
     * @param $cancelRoute The route to be used.
     */
    protected function createCancelButton( $appendTo, $cancelRoute )
    {
        //Is the cancel button enabled
        if ( xclydes_larva_config('edit.footer.cancel', false )
            && $cancelRoute) {
            $footerCancel = $this->makeField('footer_cancel', 'static', [
                'tag' => 'a',
                'wrapper' => false,
                'label' => false,
                'attr' => [
                    'href' => route($cancelRoute . '.index'),
                    'class' => $this->getFormOption('btn.cancel.class', 'btn btn-danger')
                ],
                'value' => trans( xclydes_larva_resouce('buttons.cancel') ),
            ]);
            //Add it to the container
            $appendTo->appendChild( $footerCancel );
        }
    }

    /**
     * Create the form submit button.
     * @param $appendTo ContainerType The container to append to.
     */
    protected function createSubmitButton( $appendTo ) {
        //Add the save/submit button
        $submitButton = $this->makeField('submit', 'submit', [
            'label' => trans( xclydes_larva_resouce('buttons.save') ),
            'attr' => [
                'class' => $this->getFormOption('btn.save.class', 'btn btn-success pull-right')
            ]
        ]);
        //Append the field
        $appendTo->appendChild( $submitButton );
    }

    /**
     * {@inheritDoc}
     * @see \Kris\LaravelFormBuilder\Form::setupFieldOptions()
     */
    protected function setupFieldOptions($name, &$options) {
        parent::setupFieldOptions($name, $options);
        //If this is not a group
        if( !array_get($options, 'is_group', false) ) {
            //Calculate the column size
            $columns = $this->getFormOption('field_column_count', 1);
            $maxCols = xclydes_larva_config('edit.columns.max', 12);
            //Calculate the column ration
            $colRatio = ($maxCols / $columns);
            //Set the wrapper option
            $wrapperOptsArr = array_get($options, 'wrapper', []);
            //Get the class option
            $wrapperClass = array_get($wrapperOptsArr, 'class', '');
            //Append the column span
            $wrapperClass .= " col-md-{$colRatio}";
            $wrapperOptsArr['class'] = $wrapperClass;
            //Update the wrapper attribute
            $options['wrapper'] = $wrapperOptsArr;
        }
    }

    protected function getRenderData()
    {
        $formOptions = [];
        //Add the default columns, if none is set
        $formOptions['field_column_count'] = xclydes_larva_config('edit.columns.count', 1);
        //Add the row start
        $formOptions['field_row_open'] = xclydes_larva_config('edit.rows.wrapper.open');
        //Add the row end
        $formOptions['field_row_close'] = xclydes_larva_config('edit.rows.wrapper.close');
        return $formOptions;
    }

    /**
     * Generates the validation rules to be applied to this field.
     * @param \Xclydes\Larva\Metadata\TableColumn $fieldData Description of the field
     * @return string The rules to be used, if any.
     */
    protected function resolveValidationRules( $fieldData ) {
        $included = $fieldData->isIncluded;
        $ruleParts = array();
        //Process only if the field is included
        if( $included ) {
            //var_dump($fieldData);
            //TODO Possible use the 'in' rule
            //If the table data is a text field with a length
            if( $fieldData->isText && intval( $fieldData->length ) > 0 ) {
                //Set a maximum
                array_push($ruleParts, "max:{$fieldData->length}");
            } else if( $fieldData->isBoolean ) {
                //The value must be true of false
                array_push($ruleParts, "boolean");
            } else if( $fieldData->isNumeric ) {
                //if this is an integer type
                if( $fieldData->isInteger ) {
                    //It must be an integer
                    array_push($ruleParts, "integer");
                } else {
                    //The value must be numeric
                    array_push($ruleParts, "numeric");
                }
            } else if( $fieldData->isBoolean ) {
                //The value must be true of false
                array_push($ruleParts, "boolean");
            }
            //If the field it not null
            if( $fieldData->notNull ) {
                //Mark it as required
                array_push($ruleParts, "required");
            } else {
                array_push($ruleParts, "nullable");
            }

            //If the field has a single foreign key
            if( count( $fieldData->foreignKeys ) >= 1 ) {
                $fKeys = array_values( $fieldData->foreignKeys );
                $fKey = array_shift( $fKeys );
                if( count( $fKey->ownerColumns ) == 1
                    && $fKey->ownerTableName ) {
                    $remoteTableName = $fKey->ownerTableName;
                    $ownerColumns = array_values( $fKey->ownerColumns );
                    $remoteColumnName = array_shift( $ownerColumns );
                    //The value must exist
                    array_push($ruleParts, "exists:{$remoteTableName},{$remoteColumnName}");
                }
            }
        }
        return empty( $ruleParts ) ? null : implode('|', $ruleParts);
    }

    /**
     * Determines the input type best suited for the
     * field specified.
     * @param \Xclydes\Larva\Metadata\TableColumn $fieldData Data describing
     * the field.
     * @return string The recommended input type.
     */
    protected function resolveFieldType( $fieldData ) {
        $included = $fieldData->isIncluded;
        $displayed = $fieldData->isDisplayed;
        $fType = 'text';
        //TODO Handle special conditions
        //If there is a relation
        if( $displayed && !$included ) {
            //Show as a label
            $fType = self::FIELDTYPE_STATIC;
        } else if( !$displayed && $included ) {
            //Add as a hidden field
            $fType = self::FIELDTYPE_HIDDEN;
        } else {
            //Is this a date field?
            if( $fieldData->isDate ) {
                //Is this date time?
                //Else use a date selection field
                $fType = 'text';
            } else if( $fieldData->isBoolean ) {
                $fType = 'boolean';
            } else if( $fieldData->isInteger ) {
                $fType = 'number';
            } else {//Assume it is text
                //TODO Get the text area threshold from the config
                $txtSizeLimit = $this->getFormOption('textarea_minlen', 60);
                //How big does it need to be
                if( intval( $fieldData->length ) > $txtSizeLimit ) {
                    //Use a text area
                    $fType = 'textarea';
                } else {
                    $fType = 'text';
                }
            }
        }
        return $fType;
    }
} 