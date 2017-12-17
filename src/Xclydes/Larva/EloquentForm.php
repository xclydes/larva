<?php
namespace Xclydes\Larva;

use Kris\LaravelFormBuilder\Form;
use Illuminate\Support\Facades\Schema;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\TimeType;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Schema\Table;
use Xclydes\Larva\Contracts\IFormEloquent;
use Xclydes\Larva\Helpers\LarvaHelper;

class EloquentForm extends Form {
	
	const FIELDTYPE_HIDDEN = 'hidden';
	const FIELDTYPE_STATIC = 'static';
		
	private $tblData;
	
	/**
	 * @return \App\Xclydes\Larva\mixed
	 */
	public function getTableData() {
		return $this->tblData;
	}
	
	/**
	 * Get the list of fields which should be
	 * displatyed in the form.
	 * @return mixed
	 */
	public function getDisplayedFields() {
		$displayedColNames = array();
		$inst = $this->getModel();
		$isFormEloquent = $inst instanceof IFormEloquent;
		//If the model is a form eloquent
		if( $isFormEloquent ) {
			//Ensure the build process was already run
			foreach($this->getTableData() as $col) {
				if( $inst->isDisplayedInForm( $col ) ) {
					array_push($displayedColNames, $col['name']);
				}
			}		
		} else {
			$displayedColNames = array_keys( $this->getTableData() );
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
		$this->addCustomField('boolean', 'Xclydes\Larva\Fields\BooleanType');
		//Use the model instance
		$inst = $this->getModel();
		//Get the table name
		$tblName = $inst->getTable();
		//Analyse the table
		$this->tblData = self::analyzeTable( $tblName );
		//Get the field names
		$fieldNames = array_keys( $this->tblData );
		//Process the field names
		foreach( $fieldNames as $fieldName ) {
			//Get the field type from 
			$fieldData = $this->tblData[$fieldName];
			//Assume the type is to be resolved
			$formFieldType = null;
			//Assume displayed
			$displayed = true;
			//Assume included
			$included = true;
			//Does the form support the advanced features?
			$formSupport = $inst instanceof IFormEloquent;
			//If the model is a form eloquent
			if( $formSupport ) {
				$displayed = $inst->isDisplayedInForm( $fieldData );
				$included = $inst->isIncludedInForm( $fieldData );
				//Update the table data
				$fieldData['displayed'] = $displayed;
				$fieldData['included'] = $included;
				//echo "{$fieldName} => Displayed? {$displayed}, Included? {$included}<br />";
				if( !$displayed
					&& !$included ) {					
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
			$this->add($fieldName, $formFieldType, $options);
		}
		//Is the cancel button enabled
        $cancelRoute = $this->getFormOption('route_prefix', false);
        if( config( 'larva.edit.footer.cancel')
            && $cancelRoute ) {
		    $this->add('footer_cancel', 'static', [
		        'tag' => 'a',
                'wrapper' => false,
                'label' => false,
                'attr' => ['href' => route($cancelRoute . '.index'), 'class'  => $this->getFormOption('btn.cancel.class', 'btn btn-danger')],
                'value' => trans(_XCLYDESLARVA_NS_RESOURCES_ . '::buttons.cancel'),
            ]);
        }
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
		/*//If the realname was not already set
		if( !isset( $options['real_name'] ) ) {
			//Resolve and set it now
			$options['real_name'] = LarvaHelper::resolveColumnName( $this->getModel(), $name );
		}*/
	}
	
	/**
	 * Analyzes the table specified.
	 * @param string $tblName The table to be analyzed.
	 * @return mixed A multi-dimensional array containing the
	 * column details. Each column contains:-
	 *    name - 
	 *    type - The Doctrine DBAL type
	 *    is_int
	 *    is_boolean
	 *    is_text
	 *    is_date
	 *    fkeys - A simplified array of foreign keys. Values are
	 *    	table - Foreign Table
	 *    	columns - Array of foreign columns
	 */
	public static function analyzeTable( $tblName ) {
		$columns = array();
		//Get the schema manager
		$schemaManager = \DB::connection()->getDoctrineSchemaManager();
		$table = $schemaManager->listTableDetails( $tblName );
		//Get the foreign keys
		$fKeys = $table->getForeignKeys();
		//Get the columns
		foreach($table->getColumns() as $column) {
			//var_dump( $column );
			//Get the column name
			$colName = $column->getName();
			$colType = $column->getType();
			$colArr = array(
				'name' => $colName,
				'type' => $colType,
				'included' => true,
				'displayed' => true,
				'length' => $column->getLength(),
				'not_null' => $column->getNotnull(),
				'is_int' => $colType instanceof SmallIntType
					|| $colType instanceof IntegerType,
				'is_numeric' => $colType instanceof SmallIntType
					|| $colType instanceof IntegerType
					|| $colType instanceof FloatType
					|| $colType instanceof DecimalType
					|| $colType instanceof BigIntType,
				'is_boolean' => $colType instanceof BooleanType,
				'is_text' => $colType instanceof StringType,
				'is_date' => $colType instanceof DateTimeType 
					|| $colType instanceof TimeType
					|| $colType instanceof DateType,
			);
			
			//Process the foreign key data
			$fKeyData = array();
			//Get the column type
			foreach($fKeys as $fKey) {
				//Get the local names
				$colNames = $fKey->getLocalColumns();
				if( in_array( $colName, $colNames ) ) {
					$keyData =  array(
						'table' => $fKey->getForeignTableName( ),
						'columns' => $fKey->getForeignColumns( )
					);
					//Add these details as a foreign key
					array_push( $fKeyData, $keyData );
				}
			}
			$colArr['fkeys'] = $fKeyData;
			$columns[$colName] = $colArr;
		}
		return $columns;
	}
	
	/**
	 * Generates the validation rules to be applied to this field
	 * @param string $fieldName The field being processed. 
	 * @param mixed $fieldData The database field data.
	 * @param boolean $displayed Should the field displayed.
	 * @param boolean $included Should the field be included
	 * as part of the form's submission.
	 * @return string The rules to be used, if any. 
	 */
	protected function resolveValidationRules($fieldData) {
		$included = $fieldData['included'];
		$ruleParts = array();
		//Process only if the field is included
		if( $included ) {
			//var_dump($fieldData);
			//TODO Possible use the 'in' rule
			//If the table data is a text field with a length
			if( $fieldData['is_text'] && intval( $fieldData['length'] ) > 0 ) {
				//Set a maximum
				array_push($ruleParts, "max:{$fieldData['length']}");
			} else if( $fieldData['is_boolean'] ) {
				//The value must be true of false
				array_push($ruleParts, "boolean");
			} else if( $fieldData['is_numeric'] ) {
				//if this is an integer type
				if( $fieldData['is_int'] ) {
					//It must be an integer
					array_push($ruleParts, "integer");
				} else {
					//The value must be numeric
					array_push($ruleParts, "numeric");
				}
			} else if( $fieldData['is_boolean'] ) {
				//The value must be true of false
				array_push($ruleParts, "boolean");
			}
			//If the field it not null
			if( $fieldData['not_null']
				&& !$fieldData['is_boolean']  ) {
				array_push($ruleParts, "required");
			}
			//If the field has a single foreign key
			if( count( $fieldData['fkeys'] ) == 1
				&& count( $fieldData['fkeys'][0]['columns'] ) == 1
				&& $fieldData['fkeys'][0]['table'] ) {
				//The value must exist
				array_push($ruleParts, "exists:{$fieldData['fkeys'][0]['table']},{$fieldData['fkeys'][0]['columns']['0']}");
			}								
		}
		return empty( $ruleParts ) ? null : implode('|', $ruleParts);
	}
	
	/**
	 * Determine the most appropriate form element
	 * for the field being processed.
	 * @param string $fieldName The field being processed. 
	 * @param mixed $fieldData The database field data.
	 * @param boolean $displayed Should the field displayed.
	 * @param boolean $included Should the field be included
	 * as part of the form's submission.
	 * @return string The field type to be used.
	 */
	protected function resolveFieldType($fieldData) {
		$included = $fieldData['included'];
		$displayed = $fieldData['displayed'];
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
			if( $fieldData['is_date'] ) {
				//Is this date time?
				//Else use a date selection field
				$fType = 'text';
			} else if( $fieldData['is_boolean'] ) {
				$fType = 'boolean';
			} else if( $fieldData['is_int'] ) {
				$fType = 'number';
			} else {//Assume it is text
				//TODO Get the text area threshold from the config
				$txtSizeLimit = $this->getFormOption('textarea_minlen', 60);
				//How big does it need to be
				if( intval( $fieldData['length'] ) > $txtSizeLimit ) {
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