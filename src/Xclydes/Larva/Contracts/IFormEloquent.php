<?php
namespace Xclydes\Larva\Contracts;

use Xclydes\Larva\Metadata\TableColumn;
use Xclydes\Larva\Metadata\TableData;

interface IFormEloquent {
	
	const FIELD_CREATED_BY = 'created_by';
	const FIELD_UPDATED_BY = 'updated_by';
	const FIELD_DELETED_AT = 'deleted_at';
	const FIELD_DELETED_BY = 'deleted_by';

    /**
     * Indicates whether or not the field specified
     * should be added as field within the form.
     * @param TableColumn $fieldData Details of the column
     * in question.
     * @return boolean
     */
	function isIncludedInForm($fieldData);
	
	/**
	 * Whether or not the field should be visible in the form.
	 * This does not mean it should be otherwise included.
	 * @param TableColumn $fieldData TableColumn Details of the column
     * in question.
     * @return boolean
	 */
	function isDisplayedInForm($fieldData);

	/**
	 * Gets the initial list of options to be used
	 * for instantiating the field object.
	 * @param string $formFieldType The type of field that
	 * will be rendered.
	 * @param TableColumn $fieldData The field to be checked.
     * @return mixed
	 */
	function getFieldInitOptions($formFieldType, $fieldData);
	
	/**
	 * Gets the preferred field type for the column
	 * specified.
	 * @param TableColumn $fieldData The data for the field
	 * being processed.
     * @return string
	 */
	function getPreferredFieldType( $fieldData );
	
	/**
	 * Gets sql string to be used as the description of
	 * entity.
     * @return string
	 */
	static function getDescriptionField();
}
