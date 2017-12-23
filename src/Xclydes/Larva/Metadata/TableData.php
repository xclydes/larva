<?php
/**
 * Created by PhpStorm.
 * User: Xclyd
 * Date: 12/21/2017
 * Time: 3:35 PM
 */
namespace Xclydes\Larva\Metadata;

use \Cache;

class TableData
{
    /**
     * @var $name string
     */
    public $name;
    /**
     * @var $columns TableColumn[]
     */
    private $columns;

    /**
     * @return TableColumn[]
     */
    public function _getColumns() {
        return $this->columns;
    }

    /**
     * Gets the table columns wrapped in a
     * collection.
     * @return mixed
     */
    public function getColumns() {
        return collect( $this->_getColumns() );
    }

    /**
     * Removes the table specified from the cache.
     * @param $tblName The name of the table to be
     * removed.
     */
    public static function forgetTable($tblName ) {
        Cache::forget( self::generateCacheKey( $tblName ) );
    }

    private static function performAnalysis( $tblName ) {
        $columns = array();
        //Create a table data with the columns
        $tblData = new self();
       //Get the schema manager
        $schemaManager = \DB::connection()->getDoctrineSchemaManager();
        $table = $schemaManager->listTableDetails( $tblName );
        //If the table is valid
        if( $table ) {
            $tblData->name = $table->getName();
            //Process the columns
            $columns = self::processColumns( $table );
            //Process the foreign keys
            $fKeys = self::processForeignKeys( $table );
            //Associate the fkeys and columns
            foreach( $fKeys as $fKey ) {
                /** @var $fKey ForeignKey */
                $keyColNames = array_values( $fKey->localColumns );
                $colName = array_shift( $keyColNames );
                /** @var $col  TableColumn*/
                $col = array_get($columns, $colName, false);
                if( $col !== false  ) {
                    //Set the keys on the column
                    array_push($col->foreignKeys, $col);
                }
            }
        }
        $tblData->columns = $columns;
        return $tblData;
    }

    /**
     * Generate foreign key data from the table specified.
     * @param $table Doctrine\DBAL\Schema\Table The table
     * to be checked.
     * @return ForeignKey[] The foriegn key data collected.
     */
    private static function processForeignKeys($table ) {
        //Get the foreign keys
        $fKeys = $table->getForeignKeys();
        //Process the foreign key data
        $fKeyData = array();
        //Get the column type
        foreach($fKeys as $fKey) {
            //Create the key data
            $keyData = new ForeignKey();
            //Get the local names
            $keyData->localColumns = $fKey->getLocalColumns();
            $keyData->ownerColumns = $fKey->getForeignColumns();
            $keyData->ownerTableName = $fKey->getForeignTableName( );
            //Add these details as a foreign key
            array_push($fKeyData, $keyData);
        }
        return $fKeyData;
    }

    /**
     * Generate column data for the table supplied
     * @param $table Doctrine\DBAL\Schema\Table The table
     * to be checked.
     * @return TableColumn[] The column data collected.
     */
    private static function processColumns($table ) {
        $columns = array();
        $tblCols = $table->getColumns();
        //Get the columns
        foreach($tblCols as $column) {
            //var_dump( $column );
            //Create a new table column
            $tblCol = new TableColumn();
            //Get the column name
            $tblCol->name = $column->getName();
            $colType = $column->getType();
            $tblCol->type = $colType;
            $tblCol->isIncluded = true;
            $tblCol->isDisplayed = true;
            $tblCol->length = $column->getLength();
            $tblCol->notNull = $column->getNotnull();
            $tblCol->isInteger = $colType instanceof SmallIntType
                || $colType instanceof IntegerType;

            $tblCol->isNumeric = $colType instanceof SmallIntType
                || $colType instanceof IntegerType
                || $colType instanceof FloatType
                || $colType instanceof DecimalType
                || $colType instanceof BigIntType;

            $tblCol->isBoolean = $colType instanceof BooleanType;
            $tblCol->isText = $colType instanceof StringType;
            $tblCol->isDate = $colType instanceof DateTimeType
                || $colType instanceof TimeType
                || $colType instanceof DateType;
            //Store for reference
            $columns[ $tblCol->name ] = $tblCol;
        }
        return $columns;
    }

    private static function tableFromCache( $tblName ) {
        return Cache::get( self::generateCacheKey( $tblName ) );
    }

    private static function generateCacheKey( $tblName ) {
        return _XCLYDESLARVA_NS_CLASSES_ . '::' . $tblName;
    }

    /**
     * Performs an analysis on the table specified.
     * @param $tblName The table to be analyzed.
     * @param bool $force Force the analysis to be re-run
     * ignoring the cache.
     * @return TableData The table data generated.
     */
    public static function analyzeTable($tblName, $force = false ) {
        //Get the cached version
        $tbl = false;
        if( $force ) {
            self::forgetTable( $tblName );
        } else {
            $tbl = self::tableFromCache($tblName);
        }
        //If the the table is not valid
        if( !$tbl ) {
            //Perform an analysis
            $tbl = self::performAnalysis( $tblName );
            //Store it to the cache
            Cache::forever( self::generateCacheKey( $tblName ),  $tbl );
        }
        return $tbl;
     }
}