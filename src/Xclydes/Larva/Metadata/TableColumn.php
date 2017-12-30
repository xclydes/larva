<?php
/**
 * Created by PhpStorm.
 * User: Xclyd
 * Date: 12/21/2017
 * Time: 3:51 PM
 */

namespace Xclydes\Larva\Metadata;


class TableColumn
{
    /**
     * @var $name string
     */
    public $name;
    public $type;
    /**
     * @var $foreignKeys ForeignKey[]
     */
    public $isIncluded;
    /**
     * @var $isDisplayed boolean
     */
    public $isDisplayed;
    /**
     * @var $length integer
     */
    public $length;
    /**
     * @var $notNull boolean
     */
    public $notNull;
    /**
     * @var $isInteger boolean
     */
    public $isInteger;
    /**
     * @var $isNumeric boolean
     */
    public $isNumeric;
    /**
     * @var $isBoolean boolean
     */
    public $isBoolean;
    /**
     * @var $isText boolean
     */
    public $isText;
    /**
     * @var $isDate boolean
     */
    public $isDate;
    /**
     * @var $isTime boolean
     */
    public $isTime;
    /**
     * @var $isPrimary boolean
     */
    public $isPrimary;
    /**
     * @var $foreignKeys ForeignKey[]
     */
    public $foreignKeys = [];
}