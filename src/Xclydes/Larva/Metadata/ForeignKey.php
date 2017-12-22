<?php
/**
 * Created by PhpStorm.
 * User: Xclyd
 * Date: 12/21/2017
 * Time: 3:51 PM
 */

namespace Xclydes\Larva\Metadata;


class ForeignKey
{
    /**
     * @var $ownerTableName string
     */
    public $ownerTableName;
    /**
     * @var $ownerColumns string[]
     */
    public $ownerColumns;
    /**
     * @var $localTableName string
     */
    public $localTableName;
    /**
     * @var $localColumns string[]
     */
    public $localColumns;
}