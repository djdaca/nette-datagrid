<?php

namespace DataGrid\Renderers\Extended;

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
class ActionColumnRenderer extends Renderers\Column\ActionColumnRenderer {

        public function generateHeaderCell(\DataGrid\Columns\IColumn $column) {
                $cell = parent::generateHeaderCell($column);

                if (!$this->gridRenderer->getDataGrid()->hasFilters()) {
                        $actions = Html::el("span")->setHtml(
                                $this->generateActions($column, null, null, Action::WITHOUT_KEY));
                        $cell->setHtml($actions . $cell->getHtml());
                }

                return $cell;
        }

        public function generateFilterCell(\DataGrid\Columns\IColumn $column) {
                $cell = $this->gridRenderer->getWrapper('row.filter cell container');

                // TODO: set on filters too?
                $cell->attrs = $column->getCellPrototype()->attrs;

                $submit = $this->gridRenderer->getSubmitControl();
                $submit->value = "";

                $value = (string) $submit;
                $value .= Html::el("span")->setHtml(
                        $this->generateActions($column, null, null, Action::WITHOUT_KEY));

                $cell->addClass('actions');
                $cell->setHtml($value);

                return $cell;
        }

        public function generateContentCell(\DataGrid\Columns\IColumn $column, $data, $primary) {
                $cell = $this->gridRenderer->getWrapper('row.content cell container');
                $cell->attrs = $column->getCellPrototype()->attrs;

                $value = $this->generateActions($column, $data, $primary, Action::WITH_KEY);
                $cell->addClass('actions');

                $cell->setHtml(Html::el("span")->setHtml((string) $value));
                return $cell;
        }

        protected function generateActions(\DataGrid\Columns\IColumn $column, $data, $primary, $key) {
                $value = '';
                foreach ($this->gridRenderer->getDataGrid()->getActions() as $action) {
                        if ($action->getKey() == $key)
                                $value .= $this->generateAction($action, $data, $primary);
                }
                return $value;
        }

}

?>
