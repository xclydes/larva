<?php
/**
 * Created by PhpStorm.
 * User: Xclyd
 * Date: 12/29/2017
 * Time: 5:08 PM
 */

namespace Xclydes\Larva\Contracts;


use ViewComponents\Grids\Component\Column;
use Xclydes\Larva\Metadata\TableColumn;

interface IGridEloquent
{
    /**
     * Whether or not the field should be visible in the grid.
     * @param TableColumn $fieldData TableColumn Details of the column
     * in question.
     * @return boolean
     */
    function isDisplayedInGrid($fieldData);

    /**
     * @param TableColumn $fieldData TableColumn Details of the column
     * in question.
     * @return bool
     */
    function isSortedInGrid($fieldData );

    /**
     * Gets the preferred value formatter for the column
     * specified.
     * @param TableColumn $fieldData The data for the field
     * being processed.
     * @return string
     */
    function getGridValueFormatter( $fieldData );

    /**
     * Gives the implementation an opportunity to
     * customize the column being used for the grid.
     * @param TableColumn $fieldData TableColumn Details of the column
     * in question.
     * @return Column The column instance to be rendered.
     */
    function getGridColumnComponents( $fieldData );

    /**
     * Gets the grid components to be added, if any.
     * @return mixed The array of components to be
     * added.
     */
    function getExtraGridComponents();

    /**
     * @return mixed
     */
    function getGridProviderOptions();
}