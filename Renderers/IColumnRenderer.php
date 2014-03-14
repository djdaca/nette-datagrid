<?php

namespace DataGrid\Renderers\Column;

/**
 * Defines method that must implement data grid rendered.
 *
 * @author     Dusan Jakub
 * @license    New BSD License
 * @package    Nette\Extras\DataGrid
 */
interface IColumnRenderer {

        /**
         * 
         * @param  DataGrid\Datagrid
         * @param  DataGrid\Columns\IColumn
         * @return string
         */
        public function generateHeaderCell(\DataGrid\Columns\IColumn $column);

        /**
         * 
         * @param  DataGrid\Datagrid
         * @param  DataGrid\Columns\IColumn
         * @return string
         */
        public function generateFilterCell(\DataGrid\Columns\IColumn $column);

        /**
         * 
         * @param  DataGrid\Datagrid
         * @param  DataGrid\Columns\IColumn
         * @return string
         */
        public function generateContentCell(\DataGrid\Columns\IColumn $column, $data, $primary);
}