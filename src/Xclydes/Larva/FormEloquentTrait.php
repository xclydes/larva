<?php
namespace Xclydes\Larva;

use Xclydes\Larva\Contracts\IFormEloquent;
use Xclydes\Larva\Metadata\ForeignKey;
use Xclydes\Larva\Metadata\TableColumn;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

trait FormEloquentTrait {
	
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
     * Indicates whether or not the field specified
     * should be added as field within the form.
     * @param TableColumn $fieldData Details of the column
     * in question.
     * @return boolean
     */
	public function isDisplayedInForm( $fieldData ){
		$fieldName = $fieldData->name;
		//Assume the form is to be displayed
		return !$this->isGuarded( $fieldName )
			&& !in_array($fieldName, $this->getProtectedFields());
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
		$isInternalField = in_array($fieldName, $this->getProtectedFields());
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
		    $firstFKey = array_shift( array_values( $fieldData->foreignKeys ) );
		    if( $firstFKey != null ) {
                //Get the property field
                $propField = array_shift( array_values( $firstFKey->ownerColumns ) );
                //Resolve the class name
                $foreignTableName = $firstFKey->ownerTableName;
                //echo 'Foreign Table: ' . $foreignTableName . '<br />';
                $fqN = self::getForeignModel( $foreignTableName );
                //Add the entity class
                $opts['class'] = $fqN;
                $opts['property_key'] = $propField;
                $nameField = $propField;
                $clsChain = class_implements( $fqN );
                if( in_array( 'Xclydes\Larva\Contracts\IFormEloquent', $clsChain ) ) {
                    $nameField = $fqN::getDescriptionField();
                }
                $opts['property'] = $nameField;
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
		//Return null to use defaul option
		$prefType = null;
		//If the field data has foreign key information
		if( count( $fieldData->foreignKeys ) > 0 ) {
			$foreignTableName = $fieldData->foreignKeys[0]->ownerTableName;
			//echo 'Foreign Table: ' . $foreignTableName . '<br />';
			$fqN = self::getForeignModel( $foreignTableName );
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
	 * @param unknown $foreignTableName
	 * @return Ambigous <NULL, string, mixed>
	 */
	private static function getForeignModel( $foreignTableName ) {
		$fqClsName = null;
		if( $foreignTableName ) {
			$fqClsName = array_get(self::$tableModels, $foreignTableName);
			if( !$fqClsName ){			
				//Get the name space to check
				$reflector = new \ReflectionClass( get_called_class() );
				$nameSpace = $reflector->getNamespaceName();
				//Get the expected class name
				$className = Str::ucfirst( Str::singular( Str::camel( $foreignTableName ) ) );
				$fqClsName = "\\{$nameSpace}\\{$className}";
				//echo 'Possible Class Name: ' . $className . '<br />';
				//If the class exists
				if( class_exists( $fqClsName ) ) {
					//Add this to the mapping
					self::$tableModels[$foreignTableName] = $fqClsName;
				}
			}
		}
		return $fqClsName;
	}
} 
