<?php

namespace DataGrid\Columns;

use Nette,
    Nette\Utils\Html,
    Datagrid,
    DataGrid\Filters;

/**
 * Base class that implements the basic common functionality to data grid columns.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nette.org/datagrid
 * @package    Nette\Extras\DataGrid
 */
abstract class Column extends Nette\ComponentModel\Container implements IColumn {

        /** @var Html  table header element template */
        protected $header;

        /** @var Html  table cell element template */
        protected $cell;

        /** @var string */
        protected $caption;

        /** @var int */
        protected $maxLength = 100;

        /** @var array  of arrays('pattern' => 'replacement') */
        public $replacement;

        /** @var array  of callback functions */
        public $formatCallback = array();

        /** @var bool */
        public $orderable = TRUE;

        /** @var string */
        public static $ajaxClass = 'datagrid-ajax';

        /** @var string or DataGrid\Renderes\Column* */
        protected $renderer = "Column";

        /**
         * Data grid column constructor.
         * @param  string  textual caption of column
         * @param  int     maximum number of dislayed characters
         */
        public function __construct($caption = NULL, $maxLength = NULL) {
                parent::__construct();
                $this->addComponent(new Nette\ComponentModel\Container, 'filters');
                $this->header = Html::el();
                $this->cell = Html::el();
                $this->caption = $caption;
                if ($maxLength !== NULL)
                        $this->maxLength = $maxLength;
                $this->monitor('DataGrid\DataGrid');
        }

        /**
         * This method will be called when the component (or component's parent)
         * becomes attached to a monitored object. Do not call this method yourself.
         * @param  Nette\ComponentModel\IComponent
         * @return void
         */
        protected function attached($component) {
                if ($component instanceof Datagrid\DataGrid) {
                        $this->setParent($component);

                        if ($this->caption === NULL) {
                                $this->caption = $this->getName();
                        }
                }
        }

        /**
         * Returns DataGrid.
         * @param  bool   throw exception if form doesn't exist?
         * @return Datagrid\DataGrid
         */
        public function getDataGrid($need = TRUE) {
                return $this->lookup('DataGrid\DataGrid', $need);
        }

        /*         * ******************* Html objects getters ******************** */

        /**
         * Returns headers's HTML element template.
         * @return Html
         */
        public function getHeaderPrototype() {
                return $this->header;
        }

        /**
         * Returns table's cell HTML element template.
         * @return Html
         */
        public function getCellPrototype() {
                return $this->cell;
        }

        /**
         * Setter / property method.
         * @return string
         */
        public function getCaption() {
                if ($this->caption instanceof Html && $this->caption->title) {
                        return $this->caption->title($this->getDataGrid()->translate($this->caption->title));
                }
                else {
                        return $this->getDataGrid()->translate($this->caption);
                }
        }

        /*         * ******************* interface DataGrid\Columns\IColumn ******************** */

        /**
         * Is column orderable?
         * @return bool
         */
        public function isOrderable() {
                return $this->orderable;
        }

        /**
         * Gets header link (order signal)
         * @param  string  direction of sorting (a|d|NULL)
         * @return string
         */
        public function getOrderLink($dir = NULL) {
                return $this->getDataGrid()->link('order', array('by' => $this->getName(), 'dir' => $dir));
        }

        /**
         * Has column filter box?
         * @return bool
         */
        public function hasFilter() {
                return $this->getFilter(FALSE) instanceof Filters\IColumnFilter;
        }

        /**
         * Returns column's filter.
         * @param  bool   throw exception if component doesn't exist?
         * @return Filters\IColumnFilter|NULL
         */
        public function getFilter($need = TRUE) {
                return $this->getComponent('filters')->getComponent($this->getName(), $need);
        }

        /**
         * Formats cell's content. Descendant can override this method to customize formating.
         * @param  mixed
         * @param  DibiRow|array
         * @return string
         */
        public function formatContent($value, $data = NULL) {
                return (string) $value;
        }

        /**
         * Filters data source. Descendant can override this method to customize filtering.
         * @param  mixed
         * @return void
         */
        public function applyFilter($value) {
                return;
        }

        /**
         * Return a renderer.
         * It might be a renderer ID which is then used to get the actual 
         * renderer, or a renderer itself
         * @return string or DataGrid\Renderers\Column
         */
        public function getRenderer() {
                return $this->renderer;
        }

        public function setRenderer($r) {
                if (!is_string($r) && !($r instanceof \DataGrid\Renderers\Column\IColumnRenderer))
                        throw new \Nette\InvalidArgumentException("Renderer can only be string id or renderer object itself");
                $this->renderer = $r;

                return $this;
        }

        /*         * ******************* Default sorting and filtering ******************** */

        /**
         * Adds default sorting to data grid.
         * @param string
         * @return Column  provides a fluent interface
         */
        public function addDefaultSorting($order = 'ASC') {
                $orders = array('ASC', 'DESC', 'asc', 'desc', 'A', 'D', 'a', 'd');
                if (!in_array($order, $orders)) {
                        throw new \InvalidArgumentException("Order must be in '" . implode(', ', $orders) . "', '$order' given.");
                }

                parse_str($this->getDataGrid()->defaultOrder, $list);
                $list[$this->getName()] = strtolower($order[0]);
                $this->getDataGrid()->defaultOrder = http_build_query($list, '', '&');

                return $this;
        }

        /**
         * Adds default filtering to data grid.
         * @param string
         * @return Column  provides a fluent interface
         */
        public function addDefaultFiltering($value) {
                parse_str($this->getDataGrid()->defaultFilters, $list);
                $list[$this->getName()] = $value;
                $this->getDataGrid()->defaultFilters = http_build_query($list, '', '&');

                return $this;
        }

        /**
         * Removes data grid's default sorting.
         * @return Column  provides a fluent interface
         */
        public function removeDefaultSorting() {
                parse_str($this->getDataGrid()->defaultOrder, $list);
                if (isset($list[$this->getName()]))
                        unset($list[$this->getName()]);
                $this->getDataGrid()->defaultOrder = http_build_query($list, '', '&');

                return $this;
        }

        /**
         * Removes data grid's default filtering.
         * @return Column  provides a fluent interface
         */
        public function removeDefaultFiltering() {
                parse_str($this->getDataGrid()->defaultFilters, $list);
                if (isset($list[$this->getName()]))
                        unset($list[$this->getName()]);
                $this->getDataGrid()->defaultFilters = http_build_query($list, '', '&');

                return $this;
        }

        /*         * ******************* filter factories ******************** */

        /**
         * Alias for method addTextFilter().
         * @return Filters\IColumnFilter
         */
        public function addFilter() {
                return $this->addTextFilter();
        }

        /**
         * Adds single-line text filter input to data grid.
         * @return Filters\IColumnFilter
         * @throws \InvalidArgumentException
         */
        public function addTextFilter() {
                $this->_addFilter(new Filters\TextFilter);
                return $this->getFilter();
        }

        /**
         * Adds single-line text date filter input to data grid.
         * Optional dependency on DatePicker class (@link http://nettephp.com/extras/datepicker)
         * @return Filters\IColumnFilter
         * @throws \InvalidArgumentException
         */
        public function addDateFilter() {
                $this->_addFilter(new Filters\DateFilter);
                return $this->getFilter();
        }

        /**
         * Adds check box filter input to data grid.
         * @return Filters\IColumnFilter
         * @throws \InvalidArgumentException
         */
        public function addCheckboxFilter() {
                $this->_addFilter(new Filters\CheckboxFilter);
                return $this->getFilter();
        }

        /**
         * Adds select box filter input to data grid.
         * @param  array   items from which to choose
         * @param  bool    add empty first item to selectbox?
         * @param  bool    translate all items in selectbox?
         * @return Filters\IColumnFilter
         * @throws \InvalidArgumentException
         */
        public function addSelectboxFilter($items = NULL, $firstEmpty = TRUE, $translateItems = TRUE) {
                $this->_addFilter(new Filters\SelectboxFilter($items, $firstEmpty));
                return $this->getFilter()->translateItems($translateItems);
        }

        /**
         * Internal filter adding routine.
         * @param  Filters\IColumnFilter $filter
         * @return void
         */
        private function _addFilter(Filters\IColumnFilter $filter) {
                if ($this->hasFilter()) {
                        $this->getComponent('filters')->removeComponent($this->getFilter());
                }
                $this->getComponent('filters')->addComponent($filter, $this->getName());
        }

}