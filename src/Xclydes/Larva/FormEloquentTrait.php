<?php
namespace Xclydes\Larva;

use Xclydes\Larva\Contracts\IFormEloquent;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

trait FormEloquentTrait {
	
	private static $tableModels = [];
	
	/**
	 * @return multitype:string 
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
	
	/* (non-PHPdoc)
	 * @see \App\Xclydes\Larva\Contracts\IFormEloquent::isDisplayedInForm()
	 */
	public function isDisplayedInForm( $fieldData ){
		$fieldName = $fieldData['name'];
		//Assume the form is to be displayed
		return !$this->isGuarded( $fieldName )
			&& !in_array($fieldName, $this->getProtectedFields());
	}
	
	/* (non-PHPdoc)
	 * @see \App\Xclydes\Larva\Contracts\IFormEloquent::isIncludedInForm()
	 */
	public function isIncludedInForm($fieldData ){
		$fieldName = $fieldData['name'];
		$inc = false;
		$isFillable = empty( $this->fillable ) || in_array($fieldName, $this->fillable);
		$isInternalField = in_array($fieldName, $this->getProtectedFields());
		$inc = $fieldName && !$this->isGuarded( $fieldName ) && $isFillable && !$isInternalField;
		return $inc;
	}
	
	/* (non-PHPdoc)
	 * @see \App\Xclydes\Larva\Contracts\IFormEloquent::getFieldInitOptions()
	 */
	public function getFieldInitOptions($formFieldType, $fieldData) {
		$opts = array();
		//If the field type says entity
		if( $formFieldType == 'entity' ) {
			//Resolve the class name
			$foreignTableName = $fieldData['fkeys'][0]['table'];
			//echo 'Foreign Table: ' . $foreignTableName . '<br />';
			$fqN = self::getForeignModel( $foreignTableName );
			//Add the entity class
			$opts['class'] = $fqN;
		}
		return $opts;
	}	
	
	/* (non-PHPdoc)
	 * @see \App\Xclydes\Larva\Contracts\IFormEloquent::getPreferredFieldType()
	 */
	public function getPreferredFieldType( $fieldData ) {
		//Return null to use defaul option
		$prefType = null;
		//If the field data has foreign key information
		if( count( $fieldData['fkeys'] ) > 0 ) {
			$foreignTableName = $fieldData['fkeys'][0]['table'];
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