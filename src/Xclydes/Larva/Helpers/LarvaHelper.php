<?php
namespace Xclydes\Larva\Helpers;

use Xclydes\Larva\EloquentForm;

class LarvaHelper {
	
	public static function resolveBundle( $inst ) {
		$modelLangFileName = str_replace ( '\\', '_', strtolower ( get_class ( $inst ) ) );
		return _XCLYDESLARVA_NS_RESOURCES_ . '::' . $modelLangFileName;
	}
	
	/**
	 * @param unknown $inst
	 * @param unknown $fieldName
	 * @return string
	 */
	public static function resolveForDisplay($inst, $fieldName, EloquentForm $form = null) {
		$txt = '';
		if( is_object( $inst ) ) {
			$txt = $inst->{$fieldName};
			//Determine the translation key to be be used
			$transKeyBase = self::resolveBundle( $inst ) . '.' . strtolower( $fieldName );
			//Append the value of the field
			$transKey = strtolower( "{$transKeyBase}.{$txt}" );
			//Attempt to translate it
			$translated = trans( $transKey );
			$transValid = $translated
				&& $translated != $transKey;
			//If a valid translation was not found but a form is set
			 if( !$transValid && $form ) {
				//Get the table data
				$tblData = $form->getTableData();
				//If the field is set
				if( is_array( $tblData )
					&& isset( $tblData[ $fieldName ] ) ) {
					$fieldData = $tblData[ $fieldName ];
					//If this a boolean field
					if( is_array( $fieldData ) 
						&& isset( $fieldData['is_boolean' ] ) ) {
						//Convert the value and try again
						$propKey = $txt ? 'true' : 'false';
						$transKey = strtolower( "{$transKeyBase}.{$propKey}" );
						//Attempt to translate it
						$translated = trans( $transKey );
						$transValid = $translated && $translated != $transKey;
					}
				}
			}
			//If it was translated correctly
			if( $transValid ) {
				//Use this translation
				$txt = $translated;
			}
		}
		return $txt;
	}
	
	/**
	 * Gets the name to be displayed for the field. If a langauage file
	 * is defined, then that translation is applied to that file.
	 * The langauage files should have be named after the fully qualified class name,
	 * slashes should be converted to underscores.
	 * Otherwise name is split on underscores and the first letters uppercased.
	 * @param unknown $displayedColName The name to be resolved.
	 * @return string The name to be displayed.
	 */
	public static function resolveColumnName( $inst, $displayedColName ) {
		$transKey = self::resolveBundle( $inst ) . '.' . strtolower( $displayedColName );
		//Get the name from the language.
		$displayName = trans( $transKey );
		//If no language name is defined
		if( !$displayName
			|| $displayName == $transKey ) {
			//Generate one
			$displayName = ucfirst(str_replace('_', ' ', $displayedColName));
		}
		return $displayName;
	}
}
