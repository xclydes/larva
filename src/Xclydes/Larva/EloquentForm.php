<?php
namespace Xclydes\Larva;

use Kris\LaravelFormBuilder\Form;
use Illuminate\Support\Facades\Schema;
use Xclydes\Larva\Contracts\IFormEloquent;
use Xclydes\Larva\Helpers\LarvaHelper;
use Xclydes\Larva\Metadata\TableColumn;
use Xclydes\Larva\Metadata\TableData;

class EloquentForm extends Form {
	
	const FIELDTYPE_HIDDEN = 'hidden';
	const FIELDTYPE_STATIC = 'static';
		
	private $tblData;
	
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
		//Generate the cancel button
        $cancelRoute = $this->getFormOption('route_prefix', false);
		$this->createCancelButton( $cancelRoute );
		//Generate the submit button
		$this->createSubmitButton();
	}

    /**
     * Register any custom fields before the form
     * is generated.
     */
    protected function registerCustomFields() {
        $this->addCustomField('boolean', 'Xclydes\Larva\Fields\BooleanType');
    }

    /**
     * Create the form cancel buttons on the form with the route
     * provided.
     * @param $cancelRoute The route to be used.
     */
    protected function createCancelButton($cancelRoute )
    {
        //Is the cancel button enabled
        if ( xclydes_larva_config('edit.footer.cancel', false )
            && $cancelRoute) {
            $this->add('footer_cancel', 'static', [
                'tag' => 'a',
                'wrapper' => false,
                'label' => false,
                'attr' => [
                    'href' => route($cancelRoute . '.index'),
                    'class' => $this->getFormOption('btn.cancel.class', 'btn btn-danger')
                ],
                'value' => trans(_XCLYDESLARVA_NS_RESOURCES_ . '::buttons.cancel'),
            ]);
        }
    }

    /**
     * Create the form submit button.
     */
    protected function createSubmitButton() {
        //Add the save/submit button
        $this->add('submit', 'submit', [
            'label' => trans(_XCLYDESLARVA_NS_RESOURCES_ . '::buttons.save'),
            'attr' => [
                'class' => $this->getFormOption('btn.save.class', 'btn btn-success pull-right')
            ]
        ]);
    }

	/**
	 * {@inheritDoc}
	 * @see \Kris\LaravelFormBuilder\Form::setupFieldOptions()
	 */
	protected function setupFieldOptions($name, &$options) {
	    //Calcuate the column size
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

	public function setFormOptions(array $formOptions)
    {
        //If no column count is set
        if( !isset( $formOptions['field_column_count'] ) ) {
            //Add the default columns, if none is set
            $formOptions['field_column_count'] = xclydes_larva_config('edit.column.count', 1);
        }
        //Process normally
        return parent::setFormOptions($formOptions);
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
                $fKeys = $fieldData->foreignKeys;
                $fKey = array_shift( array_values( $fKeys ) );
                if( count( $fKey->ownerColumns ) == 1
                    && $fKey->ownerTableName ) {
                    $remoteTableName = $fKey->ownerTableName;
                    $remoteColumnName = array_shift( array_values( $fKey->ownerColumns ) );
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