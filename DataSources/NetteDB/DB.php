<?php

namespace DataGrid\DataSources\NetteDB;

use \DataGrid\DataSources\IDataSource,
    \DataGrid\DataSources,
    \Nette\Database\Table,
    \Nette;

/**
 * 
 * @author Dušan Jakub, FIT VUT Brno
 */
class DB extends DataSources\Mapped {

        /**
         * @var Table\Selection
         */
        private $selection;

        /**
         * @var array Fetched data
         */
        private $data;

        /**
         * @var int Total data count
         */
        private $count;

        /**
         * Store given selection
         * @param \DibiFluent
         * @return IDataSource
         */
        public function __construct(Table\Selection $sel) {
                $this->selection = $sel;
        }

        public function setMapping(array $mapping) {
                parent::setMapping($mapping);
                foreach ($mapping as $k => $m) {
                        $this->selection->select("$m AS `$k`");
                }
        }

        public function test() {
                foreach ($this->selection as $row) {
                        dump($row->toArray());
                }
                exit;
        }

        /**
         * Add filtering onto specified column
         * @param string column name
         * @param string filter
         * @param string|array operation mode
         * @param string chain type (if third argument is array)
         * @throws \InvalidArgumentException
         * @return IDataSource
         */
        public function filter($column, $operation = IDataSource::EQUAL, $value = NULL, $chainType = NULL) {
        $col = $column;
        if ($this->hasColumn($column)) {
            $col = $this->mapping[$column];
                }

                if (is_array($operation)) {
                        if ($chainType !== self::CHAIN_AND && $chainType !== self::CHAIN_OR) {
                                throw new \InvalidArgumentException('Invalid chain operation type.');
                        }
        } else {
                        $operation = array($operation);
                }

                if (empty($operation)) {
                        throw new \InvalidArgumentException('Operation cannot be empty.');
                }



                $conds = array();
                $values = array();
                foreach ($operation as $o) {
                        $this->validateFilterOperation($o);

            $c = "$col $o";
                        if ($o !== self::IS_NULL && $o !== self::IS_NOT_NULL) {
                                $c .= " ?";

                                $values[] = ($o === self::LIKE || $o === self::NOT_LIKE) ? DataSources\Utils\WildcardHelper::formatLikeStatementWildcards($value) : $value;
                                ;
                        }

                        $conds[] = $c;
                }

                $conds = implode(" ( $chainType ) ", $conds); // "(cond1) OR (cond2) ..."  -- outer braces missing for now
                $this->selection->where("( $conds )", $values);

                return $this;
        }

        /**
         * Adds ordering to specified column
         * @param string column name
         * @param string one of ordering types
         * @throws \InvalidArgumentException
         * @return IDataSource
         */
        public function sort($column, $order = IDataSource::ASCENDING) {
                if (!$this->hasColumn($column)) {
            $this->selection->order($column . " " . ($order === self::ASCENDING ? 'ASC' : 'DESC'));
        } else {

                $this->selection->order($this->mapping[$column] . " " . ($order === self::ASCENDING ? 'ASC' : 'DESC'));
        }
                return $this;
        }

        /**
         * Reduce the result starting from $start to have $count rows
         * @param int the number of results to obtain
         * @param int the offset
         * @throws \OutOfRangeException
         * @return IDataSource
         */
        public function reduce($count, $start = 0) {
                // Delibearately skipping check agains count($this)

                if ($count === NULL)
                        $count = 0;
                if ($start === NULL)
                        $start = 0;

                if ($start < 0 || $count < 0)
                        throw new \OutOfRangeException;

                $this->selection->limit($count, $start);
                return $this;
        }

        /**
         * Get iterator over data source items
         * @return \ArrayIterator
         */
        public function getIterator() {
                return $this->selection;
        }

        /**
         * Fetches and returns the result data.
         * @return array
         */
        public function fetch() {
                throw $this->selection->fetch();
                //return $this->data = $this->df->fetchAll();
        }

        /**
         * Count items in data source
         * @return integer
         */
        public function count() {
                $query = clone $this->selection;
                $this->count = $query->count('*');

                return $this->count;
        }

        /**
         * Return distinct values for a selectbox filter
         * @param string Column name
         * @return array
         */
        public function getFilterItems($column) {
        $query = clone $this->selection;
        return $query->select($column)->group($column)->fetchPairs($column, $column);
        }

        /**
         * Clone dibi fluent instance
         * @return void
         */
        public function __clone() {
                $this->selection = clone $this->selection;
        }

}