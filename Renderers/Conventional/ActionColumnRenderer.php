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
class ActionColumnRenderer extends ColumnRenderer {

        public function generateHeaderCell(\DataGrid\Columns\IColumn $column) {
                $cell = parent::generateHeaderCell($column);
                $cell->addClass('actions');
                return $cell;
        }

        public function generateFilterCell(\DataGrid\Columns\IColumn $column) {
                $cell = $this->gridRenderer->getWrapper('row.filter cell container');

                // TODO: set on filters too?
                $cell->attrs = $column->getCellPrototype()->attrs;

                $value = (string) $this->gridRenderer->getSubmitControl();
                $cell->addClass('actions');
                $cell->setHtml($value);

                return $cell;
        }

        public function generateContentCell(\DataGrid\Columns\IColumn $column, $data, $primary) {
                $cell = $this->gridRenderer->getWrapper('row.content cell container');
                $cell->attrs = $column->getCellPrototype()->attrs;

                $value = '';
                foreach ($this->gridRenderer->getDataGrid()->getActions() as $action) {
                        $value .= $this->generateAction($action, $data, $primary);
                }
                $cell->addClass('actions');

                $cell->setHtml((string) $value);
                return $cell;
        }

        protected function generateAction($action, $data, $primary) {
                if (!is_callable($action->ifDisableCallback) || !callback($action->ifDisableCallback)->invokeArgs(array($data))) {
                        $html = $action->getHtml();
                        $html->title($this->gridRenderer->getDataGrid()->translate($html->title));
                        $action->generateLink(array($primary => $data[$primary]));
                        $this->gridRenderer->onActionRender($html, $data);
                        return $html->render() . ' ';
                } else
                        return Html::el('span')->setText($this->gridRenderer->getDataGrid()->translate($action->getHtml()->title))->render() . ' ';
        }

}

?>
