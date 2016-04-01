<?php
namespace Xclydes\Larva\Contracts;

use Kris\LaravelFormBuilder\Fields\FormField;

interface IFormEloquent {
	
	const FIELD_CREATED_BY = 'created_by';
	const FIELD_UPDATED_BY = 'updated_by';
	const FIELD_DELETED_AT = 'deleted_at';
	const FIELD_DELETED_BY = 'deleted_by';
	
	/**
	 * Indicates whether or not the field specified
	 * should be added as field within the form.
	 * @param string $fieldName The field to be checked.
	 */
	function isIncludedInForm($fieldData);
	
	/**
	 * Whether or not the field should be visible in the form.
	 * This does not mean it should be otherwise included.
	 * @param string $fieldName The field to be checked.
	 */
	function isDisplayedInForm($fieldData);	

	/**
	 * Gets the initial list of options to be used
	 * for instantiating the field object.
	 * @param string $fieldType The type of field that
	 * will be rendered.
	 * @param string $fieldName The field to be checked.
	 */
	function getFieldInitOptions($formFieldType, $fieldData);
	
	/**
	 * Gets the preferred field type for the column
	 * specified.
	 * @param mixed $fieldData The data for the field
	 * being processed.
	 */
	function getPreferredFieldType( $fieldData );
}