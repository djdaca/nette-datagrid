<?php

namespace DataGrid\Renderers\Column;

use Nette,
    DataGrid,
    Nette\Utils\Html,
    DataGrid\Columns,
    DataGrid\Action,
    DataGrid\Renderers;

/**
 * Description of ColumnRenderer
 *
 * @author rodney2
 */
class ColumnRenderer implements IColumnRenderer {

        protected $gridRenderer;

        public function __construct(Renderers\Conventional $gridRenderer) {
                $this->gridRenderer = $gridRenderer;
        }

        public function generateHeaderCell(\DataGrid\Columns\IColumn $column) {
                $value = $text = $column->caption;

                if ($column->isOrderable()) {
                        $i = 1;
                        parse_str($this->gridRenderer->getDataGrid()->order, $list);
                        foreach ($list as $field => $dir) {
                                $list[$field] = array($dir, $i++);
                        }

                        if (isset($list[$column->getName()])) {
                                $a = $list[$column->getName()][0] === 'a';
                                $d = $list[$column->getName()][0] === 'd';
                        }
                        else {
                                $a = $d = FALSE;
                        }

                        if (count($list) > 1 && isset($list[$column->getName()])) {
                                $text .= Html::el('span')->setHtml($list[$column->getName()][1]);
                        }

                        $up = clone $down = Html::el('a')->addClass(Columns\Column::$ajaxClass);
                        $up->addClass($a ? 'active' : '')->href($column->getOrderLink('a'))
                                ->add(Html::el('span')->class('up'));
                        $down->addClass($d ? 'active' : '')->href($column->getOrderLink('d'))
                                ->add(Html::el('span')->class('down'));
                        $positioner = Html::el('span')->class('positioner')->add($up)->add($down);
                        $active = $a || $d;

                        $value = (string) Html::el('a')->href($column->getOrderLink())
                                        ->addClass(Columns\Column::$ajaxClass)->setHtml($text) . $positioner;
                }
                else {
                        $value = (string) Html::el('p')->setText($value);
                }

                $cell = $this->gridRenderer->getWrapper('row.header cell container')->setHtml($value);
                $cell->attrs = $column->getHeaderPrototype()->attrs;
        		$cell->addClass(isset($active) && $active == TRUE ? $this->gridRenderer->getValue('row.header cell .active') : NULL);

                return $cell;
        }

        public function generateFilterCell(\DataGrid\Columns\IColumn $column) {
                $cell = $this->gridRenderer->getWrapper('row.filter cell container');

                // TODO: set on filters too?
                $cell->attrs = $column->getCellPrototype()->attrs;


                if ($column->hasFilter()) {
                        $filter = $column->getFilter();
                        if ($filter instanceof Filters\SelectboxFilter) {
                                $class = $this->gridRenderer->getValue('row.filter control .select');
                        }
                        else {
                                $class = $this->gridRenderer->getValue('row.filter control .input');
                        }
                        $control = $filter->getFormControl()->control;
                        $control->addClass($class);
                        $value = (string) $control;
                }
                else {
                        $value = '';
                }

                $cell->setHtml($value);

                return $cell;
        }

        public function generateContentCell(\DataGrid\Columns\IColumn $column, $data, $primary) {
                $cell = $this->gridRenderer->getWrapper('row.content cell container');
                $cell->attrs = $column->getCellPrototype()->attrs;

                $value = $column->formatContent($data[$column->getName()], $data);

                $cell->setHtml((string) $value);
                return $cell;
        }

}

?>
