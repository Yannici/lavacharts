<?php namespace Khill\Lavacharts\Configs;

/**
 * DataTable Object
 *
 * The DataTable object is used to hold the data passed into a visualization.
 * A DataTable is a basic two-dimensional table. All data in each column must
 * have the same data type. Each column has a descriptor that includes its data
 * type, a label for that column (which might be displayed by a visualization),
 * and an ID, which can be used to refer to a specific column (as an alternative
 * to using column indexes). The DataTable object also supports a map of
 * arbitrary properties assigned to a specific value, a row, a column, or the
 * whole DataTable. Visualizations can use these to support additional features;
 * for example, the Table visualization uses custom properties to let you assign
 * arbitrary class names or styles to individual cells.
 *
 *
 * @package    Lavacharts
 * @subpackage Configs
 * @author     Kevin Hill <kevinkhill@gmail.com>
 * @copyright  (c) 2015, KHill Designs
 * @link       http://github.com/kevinkhill/lavacharts GitHub Repository Page
 * @link       http://lavacharts.com                   Official Docs Site
 * @license    http://opensource.org/licenses/MIT MIT
 */

use Carbon\Carbon;
use Khill\Lavacharts\Utils;
use Khill\Lavacharts\Formats\Format;
use Khill\Lavacharts\Exceptions\InvalidDate;
use Khill\Lavacharts\Exceptions\InvalidCellCount;
use Khill\Lavacharts\Exceptions\InvalidConfigValue;
use Khill\Lavacharts\Exceptions\InvalidConfigProperty;
use Khill\Lavacharts\Exceptions\InvalidColumnDefinition;
use Khill\Lavacharts\Exceptions\InvalidColumnIndex;
use Khill\Lavacharts\Exceptions\InvalidRowDefinition;
use Khill\Lavacharts\Exceptions\InvalidRowProperty;

class DataTable
{
    /**
     * Timezone for dealing with datetime and Carbon objects
     *
     * @var string
     */
    public $timezone;

    /**
     * Timezone for dealing with datetime and Carbon objects
     *
     * @var string
     */
    public $dateTimeFormat;

    /**
     * Holds the information defining the columns.
     *
     * @var array
     */
    private $cols = array();

    /**
     * Holds the information defining each row.
     *
     * @var array
     */
    private $rows = array();

    /**
     * Holds the formatting information for each column.
     *
     * @var array
     */
    private $formats = array();

    /**
     * Valid column types.
     *
     * @var array
     */
    private $colCellTypes = array(
        'string',
        'number',
        'bool',
        'date',
        'datetime',
        'timeofday'
    );

    /**
     * Valid column descriptions
     *
     * @var array
     */
    private $colCellDesc = array(
        'type',
        'label',
        'id',
        'role',
        'pattern'
    );

    public function __construct($timezone = null)
    {
        $this->setTimezone($timezone);
    }

    public function setTimezone($timezone)
    {
        // get PHP.ini setting
        $default_timezone = date_default_timezone_get();

        if (Utils::nonEmptyString($timezone)) {
            $this->timezone = $timezone;
        } elseif (Utils::nonEmptyString($default_timezone)) {
            $this->timezone = $default_timezone;
        } else {
            $this->timezone = 'America/Los_Angeles';
        }

        date_default_timezone_set($this->timezone);

        return $this;
    }

    /**
     * Sets the format to be used by Carbon::createFromFormat()
     *
     * This method is used to set the format to be used to parse a string
     * passed to a cell in a date column, that was parsed incorrectly by Carbon::parse()
     */
    public function setDateTimeFormat($dateTimeFormat)
    {
        if (Utils::nonEmptyString($dateTimeFormat)) {
            $this->dateTimeFormat = $dateTimeFormat;
        } else {
            throw new InvalidConfigValue(
                __FUNCTION__,
                'string'
            );
        }

        return $this;
    }

    /**
     * Adds a column to the DataTable
     *
     * First signature has the following parameters:
     * type - A string with the data type of the values of the column.
     * The type can be one of the following: 'string' 'number' 'bool' 'date'
     * 'datetime' 'timeofday'.
     *
     * optLabel - [Optional] A string with the label of the column. The column
     * label is typically displayed as part of the visualization, for example as
     *  a column header in a table, or as a legend label in a pie chart. If not
     * value is specified, an empty string is assigned.
     * optId - [Optional] A string with a unique identifier for the column. If
     * not value is specified, an empty string is assigned.
     *
     *
     * @param  string|array          Column type or an array describing the column.
     * @param  string                A label for the column. (Optional)
     * @param  string                An ID for the column. (Optional)
     * @param  Format                A column formatter object. (Optional)
     * @throws InvalidConfigValue
     * @throws InvalidConfigProperty
     * @return DataTable
     */
    public function addColumn($typeOrDescArr, $optLabel = '', $optId = '', Format $formatter = null)
    {
        if (is_array($typeOrDescArr)) {
            $this->addColumnFromArray($typeOrDescArr);
        } elseif (is_string($typeOrDescArr)) {
            $this->addColumnFromStrings($typeOrDescArr, $optLabel, $optId, $formatter);
        } else {
            throw new InvalidConfigValue(
                __FUNCTION__,
                'string or array'
            );
        }

        return $this;
    }

    /**
     * Adds multiple columns to the DataTable
     *
     * @param  array              $arrOfCols Array of columns to batch add to the DataTable.
     * @throws InvalidConfigValue
     * @return DataTable
     */
    public function addColumns($arrOfCols)
    {
        if (Utils::arrayIsMulti($arrOfCols)) {
            foreach ($arrOfCols as $col) {
                $this->addColumnFromArray($col);
            }
        } else {
            throw new InvalidConfigValue(
                __FUNCTION__,
                'array of arrays'
            );
        }

        return $this;
    }

    /**
     * Supplemental function to add a string column with less params.
     *
     * @param  string                A label for the column.
     * @param  Format                A column formatter object. (Optional)
     * @throws InvalidConfigValue
     * @throws InvalidConfigProperty
     * @return DataTable
     */
    public function addStringColumn($optLabel, Format $formatter = null)
    {
        return $this->addColumn('string', $optLabel, 'col_' . count($this->cols) + 1, $formatter);
    }

    /**
     * Supplemental function to add a date column with less params.
     *
     * @param  string                A label for the column.
     * @param  Format                A column formatter object. (Optional)
     * @throws InvalidConfigValue
     * @throws InvalidConfigProperty
     * @return DataTable
     */
    public function addDateColumn($optLabel, Format $formatter = null)
    {
        return $this->addColumn('date', $optLabel, 'col_' . count($this->cols) + 1, $formatter);
    }

    /**
     * Supplemental function to add a number column with less params.
     *
     * @param  string                A label for the column.
     * @param  Format                A column formatter object. (Optional)
     * @throws InvalidConfigValue
     * @throws InvalidConfigProperty
     * @return DataTable
     */
    public function addNumberColumn($optLabel, Format $formatter = null)
    {
        return $this->addColumn('number', $optLabel, 'col_' . count($this->cols) + 1, $formatter);
    }

    /**
     * Supplemental function to add columns from an array.
     *
     * @param  array                   $colDefArray
     * @throws InvalidColumnDefinition
     * @return DataTable
     */
    private function addColumnFromArray($colDefArray)
    {
        if (Utils::arrayValuesCheck($colDefArray, 'string') && Utils::between(1, count($colDefArray), 4, true)) {
            call_user_func_array(array($this, 'addColumnFromStrings'), $colDefArray);
        } else {
            throw new InvalidColumnDefinition($colDefArray);
        }

        return $this;
    }

    /**
     * Supplemental function to add columns from strings.
     *
     * @param  array               $type
     * @param  array               $label
     * @param  array               $id
     * @param  array               $format
     * @throws InvalidConfigValue
     * @return DataTable
     */
    private function addColumnFromStrings($type, $label = '', $id = '', $format = null)
    {
        $colIndex = $this->getNumberOfColumns();

        if (in_array($type, $this->colCellTypes)) {
            if (Utils::nonEmptyString($type)) {
                $descArray['type'] = $type;
            } else {
                throw new InvalidConfigValue(
                    __FUNCTION__,
                    'string'
                );
            }

            if (Utils::nonEmptyString($label)) {
                $descArray['label'] = $label;
            }

            if (Utils::nonEmptyString($id)) {
                $descArray['id'] = $id;
            }

            if (! is_null($format)) {
                $this->formats[$colIndex] = $format;
            }
        } else {
            throw new InvalidConfigProperty(
                __FUNCTION__,
                'string',
                Utils::arrayToPipedString($this->colCellTypes)
            );
        }

        $this->cols[$colIndex] = $descArray;
    }

    /**
     * Sets the format of the column.
     *
     * @param  int                $colIndex
     * @param  Format             $formatter
     * @throws InvalidColumnIndex
     * @return DataTable
     */
    public function formatColumn($colIndex, Format $formatter)
    {
        if (is_int($colIndex) && $colIndex > 0) {
            $this->formats[$colIndex] = $formatter->toArray();
        } else {
            throw new InvalidColumnIndex($colIndex);
        }

        return $this;
    }

    /**
     * Sets the format of multiple columns.
     *
     * @param  array     $colFormatArr
     * @return DataTable
     */
    public function formatColumns($colFormatArr)
    {
        if (is_array($colFormatArr) && ! empty($colFormatArr)) {
            foreach ($colFormatArr as $index => $format) {
                if (is_subclass_of($format, 'Format')) {
                    $this->formats[$colIndex] = $format->toArray();
                }
            }
        }

        return $this;
    }

    /**
     * Add a row to the DataTable
     *
     * Each cell in the table is described by an array with the following properties:
     *
     * v [Optional] The cell value. The data type should match the column data type.
     * If null, the whole object should be empty and have neither v nor f properties.
     *
     * f [Optional] A string version of the v value, formatted for display. The
     * values should match, so if you specify Date(2008, 0, 1) for v, you should
     * specify "January 1, 2008" or some such string for this property. This value
     * is not checked against the v value. The visualization will not use this value
     * for calculation, only as a label for display. If omitted, a string version
     * of v will be used.
     *
     * p [Optional] An object that is a map of custom values applied to the cell.
     * These values can be of any JavaScript type. If your visualization supports
     * any cell-level properties, it will describe them; otherwise, this property
     * will be ignored. Example: p:{style: 'border: 1px solid green;'}.
     *
     *
     * Cells in the row array should be in the same order as their column descriptions
     * in cols. To indicate a null cell, you can specify null, leave a blank for
     * a cell in an array, or omit trailing array members. So, to indicate a row
     * with null for the first two cells, you would specify [null, null, {cell_val}].
     *
     * @param  mixed $optCellArray Array of values or DataCells.
     *
     * @throws InvalidCellCount
     *
     * @return DataTable
     */
    public function addRow($optCellArray = null)
    {
        $props = array(
            'v',
            'f',
            'p'
        );

        if (is_null($optCellArray)) {
            for ($a = 0; $a < count($this->cols); $a++) {
                $tmp[] = array('v' => null);
            }

            $this->rows[] = array('c' => $tmp);
        } else {
            if (is_array($optCellArray)) {
                if (Utils::arrayIsMulti($optCellArray)) {
                    foreach ($optCellArray as $prop => $value) {
                        if (in_array($value, $props)) {
                            $rowVals[] = array($prop => $value);
                        } else {
                            throw new InvalidRowProperty;
                        }
                    }

                    $this->rows[] = array('c' => $rowVals);
                } else {
                    if (count($optCellArray) <= count($this->cols)) {
                        for ($b = 0; $b < count($this->cols); $b++) {
                            if (isset($optCellArray[$b])) {
                                if ($this->cols[$b]['type'] == 'date') {
                                    $rowVals[] = array('v' => $this->parseDate($optCellArray[$b]));
                                } else {
                                    $rowVals[] = array('v' => $optCellArray[$b]);
                                }
                            } else {
                                $rowVals[] = array('v' => null);
                            }
                        }
                        $this->rows[] = array('c' => $rowVals);
                    } else {
                        throw new InvalidCellCount(count($optCellArray), count($this->cols));
                    }
                }
            } else {
                throw new InvalidRowDefinition($optCellArray);
            }
        }

        return $this;
    }

    /**
     * Adds multiple rows to the DataTable.
     *
     * @see   addRow()
     * @param array Multi-dimensional array of rows.
     *
     * @return DataTable
     */
    public function addRows($arrayOfRows)
    {
        if (Utils::arrayIsMulti($arrayOfRows)) {
            foreach ($arrayOfRows as $row) {
                $this->addRow($row);
            }
        } else {
            throw new InvalidConfigValue(
                __FUNCTION__,
                'array of arrays'
            );
        }

        return $this;
    }

    /**
     * Returns the number of columns in the DataTable
     *
     * @return int
     */
    public function getNumberOfColumns()
    {
        return count($this->cols);
    }

    /**
     * Returns the number of rows in the DataTable
     *
     * @return int
     */
    public function getNumberOfRows()
    {
        return count($this->rows);
    }

    /**
     * Returns the column array from the DataTable
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->cols;
    }

    /**
     * Returns the rows array from the DataTable
     *
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Returns the formats array from the DataTable
     *
     * @return array
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * Boolean value if there are defined formatters
     *
     * @return bool
     */
    public function hasFormats()
    {
        return count($this->formats) > 0 ? true : false;
    }

    /**
     * Convert the DataTable to JSON
     *
     * @return string JSON representation of the DataTable.
     */
    public function toJson()
    {
        $json = json_encode(array(
            'cols' => $this->cols,
            'rows' => $this->rows,
        ));

        // Removes the "" from Date() so it is treated
        // as a JS new Date() instead of a string.
        //return preg_replace('/"(Date\([0-9,]+\))"/', 'new $1', $json);
        return $json;
    }

    /**
     * Either passes the Carbon instance or parses a datetime string.
     *
     * @param  Carbon|string $date
     * @return string Javscript date declaration
     */
    private function parseDate($date)
    {
        if (is_a($date, 'Carbon\Carbon')) {
            $carbonDate = $date;
        } elseif (Utils::nonEmptyString($date)) {
            try {
                if (Utils::nonEmptyString($this->dateTimeFormat)) {
                    $carbonDate = Carbon::createFromFormat($this->dateTimeFormat, $date);
                } else {
                    $carbonDate = Carbon::parse($date);
                }
            } catch (\Exception $e) {
                throw new InvalidDate;
            }
        } else {
            throw new InvalidDate;
        }

        return $this->carbonToJsString($carbonDate);
        //return $carbonDate->toIso8601String();
    }

    /**
     * Outputs the Carbon object as a valid javascript Date string.
     *
     * @return string Javscript date declaration
     */
    private function _carbonToJsString(Carbon $c)
    {
        // Javascript
        //Wed Apr 15 2015 16:01:22 GMT-0700 (PDT)
        //Sun Oct 19 2014  0:00:00 UTC+0000

        // Carbon
        //"Wed, 01 Oct 2014 00:00:00 +0000"

        return $c->format('D M j Y G:i:s TO');
        //return $c->toRfc2822String();
    }

    /**
     * Outputs the Carbon object as a valid javascript Date string.
     *
     * @return string Javscript date declaration
     */
    private function carbonToJsString(Carbon $c)
    {
        return sprintf(
            'Date(%d,%d,%d,%d,%d,%d)',
            isset($c->year)   ? $c->year      : 'null',
            isset($c->month)  ? $c->month - 1 : 'null', //silly javascript
            isset($c->day)    ? $c->day       : 'null',
            isset($c->hour)   ? $c->hour      : 'null',
            isset($c->minute) ? $c->minute    : 'null',
            isset($c->second) ? $c->second    : 'null'
        );
    }

}