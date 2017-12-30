<?php
/**
 * Created by PhpStorm.
 * User: Xclyd
 * Date: 12/21/2017
 * Time: 3:35 PM
 */
namespace Xclydes\Larva\Metadata;

use Cache;
use DirectoryIterator;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TimeType;
use ReflectionClass;

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
     * @var $keys TableColumn[]
     */
    private $keys;

    /**
     * @return TableColumn[]
     */
    public function _getColumns() {
        return $this->columns;
    }

    /**
     * Gets the table columns wrapped in a
     * collection.
     * @return \Illuminate\Support\Collection
     */
    public function getColumns() {
        return collect( $this->_getColumns() );
    }

    /**
     * @return TableColumn[]
     */
    public function _getKeys()
    {
        return $this->keys;
    }

    /**
     * Gets the key columns wrapped in a
     * collection.
     * @return \Illuminate\Support\Collection
     */
    public function getKeys()
    {
        return  collect( $this->keys );
    }

    /**
     * Gets te
     * @return \Illuminate\Support\Collection
     */
    public function getClasses() {
        //Gather the class names
        $classes = [];
        //Get the class map
        $clsMap = self::getClassMap();
        foreach($clsMap as $cls=>$tbl) {
            //If the table name matches
            if( $tbl == $this->name ) {
                //Add this class
                array_push($classes, $cls);
            }
        }
        return collect( $classes );
    }

    public static function getClassMap( $reload = false ) {
        $mapCacheKey = _XCLYDESLARVA_NS_CLASSES_ . '::classes';
        if( $reload ) {
            Cache::forget( $mapCacheKey );
        }
        //Get the map from the cache
        $map = Cache::get( $mapCacheKey );
        //If nothing was found
        if( !$map ) {
            //Get the app path
            self::loadClasses( app_path(), true);
            //Get the list of loaded classes
            $declaredClasses = get_declared_classes();
            $clsMap = [];
            //Generate a new map
            foreach($declaredClasses as $clsName) {
                //If it has the app prefix
                if( starts_with( $clsName, 'App\\') ) {
                    //Is it a model?
                    if( is_subclass_of($clsName, '\\Illuminate\\Database\\Eloquent\\Model') ) {
                        //Get the table from it
                        try {
                            //Load using reflection
                            $reflected  = new ReflectionClass( $clsName );
                            //If the class can be instantiated
                            if( $reflected->isInstantiable() ) {
                                logger()->debug("Getting table for model {$clsName}...");
                                /** @var $inst \Illuminate\Database\Eloquent\Model*/
                                $inst = $reflected->newInstance();
                                //Get the table name
                                $tblName = $inst->getTable();
                                logger()->debug("Model {$clsName} => Table {$tblName}.");
                                //Add to the map
                                $clsMap[$clsName] = $tblName;
                            }
                        } catch (\Exception $err) {
                            logger()->error($err->getMessage() . "\r\n" . $err->getTraceAsString() );
                        }
                    }
                }
            }
            //Cache it for future reference
            Cache::forever($mapCacheKey, $clsMap);
            //Return the class map
            $map = $clsMap;
        }
        return $map;
    }

    public static function loadClasses( $path,  $subDir = false) {
        //Convert the slashes
        $path = str_replace('\\', '/', $path);
        //Finish the path
        $path = str_finish( $path, '/' );
        //Get all php files
        $phpFiles = glob("$path*.php");
        logger()->debug('Files in  ' . $path, $phpFiles);
        foreach($phpFiles as $phpFile) {
            try {
                logger()->debug('Including ' . $phpFile);
                include_once $phpFile;
            }catch (\Exception $err) {
                logger()->error($err->getMessage() . "\r\n" . $err->getTraceAsString() );
            }
        }
        if( $subDir ) {
            //Process the subdirectories
            foreach (new DirectoryIterator( $path ) as $fileInfo) {
                if(!$fileInfo->isDot()
                   && $fileInfo->isDir() ) {
                   self::loadClasses( $fileInfo->getPathname(), true );
                }
            }
        }
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
        logger()->debug('Analyze Table ' . $tblName);
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
            $pKeyCols = [];
            //Gather the primary keys
            foreach($columns as $column){
                if( $column->isPrimary ) {
                    array_push($pKeyCols, $column);
                }
            }
            //Set these on the table
            $tblData->keys = $pKeyCols;
            //Process the foreign keys
            $fKeys = self::processForeignKeys( $table );
            logger()->debug('FKeys ' . $tblName, $fKeys);
            //Associate the fkeys and columns
            foreach( $fKeys as $fKey ) {
                /** @var $fKey ForeignKey */
                $keyColNames = array_values( $fKey->localColumns );
                logger()->debug($tblName . ' Column Names ', $keyColNames);
                $colName = array_shift( $keyColNames );
                logger()->debug($tblName . ' First Column Name ' . $colName);
                /** @var $col  TableColumn*/
                $col = array_get($columns, $colName, false);
                if( $col !== false  ) {
                    logger()->debug( ' FKey Belongs to  ' . $tblName, ['column'=>$col, 'fkey' => $fKey]);
                    //Set the keys on the column
                    array_push($col->foreignKeys, $fKey);
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
    private static function processForeignKeys( $table ) {
        //Get the foreign keys
        $fKeys = $table->getForeignKeys();
        //Process the foreign key data
        $fKeyData = array();
        //Get the column type
        foreach($fKeys as $fKey) {
            //Create the key data
            $keyData = new ForeignKey();
            //Get the local names
            $keyData->localTableName = $table->getName();
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
     * @param $table \Doctrine\DBAL\Schema\Table The table
     * to be checked.
     * @return TableColumn[] The column data collected.
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function processColumns($table ) {
        $columns = array();
        $tblCols = $table->getColumns();
        //Get the primary key column
        $pKeyCols = $table->getPrimaryKeyColumns();
        //Get the columns
        /** @var  $column */
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
            //Is it a primary key
            $tblCol->isPrimary = is_array( $pKeyCols )
                && in_array($column->getName(), $pKeyCols);
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
        }
        $tbl = self::tableFromCache($tblName);
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