<?php

namespace DataGrid\Renderers;

use Nette,
    DataGrid,
    Nette\Utils\Html,
    DataGrid\Columns,
    DataGrid\Action;

/**
 * Converts a data grid into the HTML output.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nette.org/datagrid
 * @package    Nette\Extras\DataGrid
 */
class Conventional extends Nette\Object implements IRenderer {

	/** @var array  of HTML tags */
	public $wrappers = array(
		'datagrid' => array(
			'container' => 'table class=datagrid',
		),
		'form' => array(
			'.class' => 'datagrid',
		),
		'error' => array(
			'container' => 'ul class=error',
			'item' => 'li',
		),
		'row.header' => array(
			'container' => 'tr class=header',
			'cell' => array(
				'container' => 'th', // .checker, .action
				'.active' => 'active',
			),
		),
		'row.filter' => array(
			'container' => 'tr class=filters',
			'cell' => array(
				'container' => 'td', // .action
			),
			'control' => array(
				'.input' => 'text',
				'.select' => 'select',
				'.submit' => 'button',
			),
		),
		'row.content' => array(
			'container' => 'tr', // .even, .selected
			'.even' => 'even',
			'cell' => array(
				'container' => 'td', // .checker, .action
			),
		),
		'row.footer' => array(
			'container' => 'tr class=footer',
			'cell' => array(
				'container' => 'td',
			),
		),
		'paginator' => array(
			'container' => 'span class=paginator',
			'button' => array(
				'first' => 'span class="paginator-first"',
				'prev' => 'span class="paginator-prev"',
				'next' => 'span class="paginator-next"',
				'last' => 'span class="paginator-last"',
			),
			'controls' => array(
				'container' => 'span class=paginator-controls',
			),
		),
		'operations' => array(
			'container' => 'span class=operations',
		),
		'info' => array(
			'container' => 'span class=grid-info',
		)
	);

	/** @var string */
	public $footerFormat = '%operations% %paginator% %info%';

	/** @var string */
	public $paginatorFormat = '%label% %input% of %count%';

	/** @var string */
	public $infoFormat = 'Items %from% - %to% of %count% | Display: %selectbox% | %reset%';

	/** @var string  template file */
	public $file;

	/** @var DataGrid\DataGrid */
	protected $dataGrid;

	/** @var array  of function(Nette\Web\Html $row, DibiRow $data) */
	public $onRowRender;

	/** @var array  of function(Nette\Web\Html $cell, string $column, mixed $value) */
	public $onCellRender;

	/** @var array  of function(Nette\Web\Html $action, DibiRow $data) */
	public $onActionRender;
	private $columnRenderers = array();

	/**
	 * Data grid renderer constructor.
	 * @return void
	 */
	public function __construct() {
		$this->file = __DIR__ . '/grid.phtml';
	}

	/**
	 * Provides complete datagrid rendering.
	 * @param  DataGrid\DataGrid
	 * @param  string
	 * @return string
	 */
	public function render(DataGrid\DataGrid $dataGrid, $mode = NULL) {
		if ($this->dataGrid !== $dataGrid) {
			$this->dataGrid = $dataGrid;
		}

		if (!$dataGrid->dataSource instanceof DataGrid\DataSources\IDataSource) {
			throw new Nette\InvalidStateException('Data source is not instance of IDataSource. ' . gettype($this->dataSource) . ' given.');
		}

		if ($mode !== NULL) {
			return call_user_func_array(array($this, 'render' . $mode), array());
		}

		$template = $this->dataGrid->getTemplate();
		$template->setFile($this->file);
		return $template->__toString(TRUE);
	}

	/**
	 * Renders datagrid form begin.
	 * @return string
	 */
	public function renderBegin() {
		$form = $this->dataGrid->getForm(TRUE);
		$form->getElementPrototype()->addClass($this->getValue('form .class'));
		return $form->getRenderer()->render($form, 'begin');
	}

	/**
	 * Renders datagrid form end.
	 * @return string
	 */
	public function renderEnd() {
		$form = $this->dataGrid->getForm(TRUE);
		return $form->getRenderer()->render($form, 'end');
	}

	/**
	 * Renders validation errors.
	 * @return string
	 */
	public function renderErrors() {
		$form = $this->dataGrid->getForm(TRUE);

		$errors = $form->getErrors();
		if (count($errors)) {
			$ul = $this->getWrapper('error container');
			$li = $this->getWrapper('error item');

			foreach ($errors as $error) {
				$item = clone $li;
				if ($error instanceof Html) {
					$item->add($error);
				}
				else {
					$item->setText($error);
				}
				$ul->add($item);
			}
			return "\n" . $ul->render(0);
		}
	}

	/**
	 * Renders data grid body.
	 * @return string
	 */
	public function renderBody() {
		$container = $this->getWrapper('datagrid container');

		// headers
		$header = Html::el($container->getName() == 'table' ? 'thead' : NULL);
		$header->add($this->generateHeaderRow());

		if ($this->dataGrid->hasFilters()) {
			$header->add($this->generateFilterRow());
		}

		// footer
		$footer = Html::el($container->getName() == 'table' ? 'tfoot' : NULL);
		$footer->add($this->generateFooterRow());

		// body
		$body = Html::el($container->getName() == 'table' ? 'tbody' : NULL);

		if ($this->dataGrid->paginator->itemCount) {
			$iterator = new Nette\Iterators\CachingIterator($this->dataGrid->getRows());
			foreach ($iterator as $data) {
				$row = $this->generateContentRow($data);
				$row->addClass($iterator->isEven() ? $this->getValue('row.content .even') : NULL);
				$body->add($row);
			}
		}
		else {
			$size = count($this->dataGrid->getColumns());
			$row = $this->getWrapper('row.content container');
			$cell = $this->getWrapper('row.content cell container');
			$cell->colspan = $size;
			$cell->style = 'text-align:center';
			$cell->add(Html::el('div')->setText($this->dataGrid->translate('No data were found')));
			$row->add($cell);
			$body->add($row);
		}

		if ($container->getName() == 'table') {
			$container->add($header);
			$container->add($footer);
			$container->add($body);
		}
		else {
			$container->add($header);
			$container->add($body);
			$container->add($footer);
		}

		return $container->render(0);
	}

	/**
	 * Renders data grid paginator.
	 * @return string
	 */
	public function renderPaginator() {
		$paginator = $this->dataGrid->paginator;
		if ($paginator->pageCount <= 1)
			return '';

		$container = $this->getWrapper('paginator container');
		$translator = $this->dataGrid->getTranslator();

		$a = Html::el('a');
		$a->addClass(Action::$ajaxClass);

		// to-first button
		$first = $this->getWrapper('paginator button first');
		$title = $this->dataGrid->translate('First');
		$link = clone $a->href($this->dataGrid->link('page', 1));
		if ($first instanceof Html) {
			if ($paginator->isFirst())
				$first->addClass('inactive');
			else
				$first = $link->add($first);
			$first->title($title);
		} else {
			$first = $link->setText($title);
		}
		$container->add($first);

		// previous button
		$prev = $this->getWrapper('paginator button prev');
		$title = $this->dataGrid->translate('Previous');
		$link = clone $a->href($this->dataGrid->link('page', $paginator->page - 1));
		if ($prev instanceof Html) {
			if ($paginator->isFirst())
				$prev->addClass('inactive');
			else
				$prev = $link->add($prev);
			$prev->title($title);
		} else {
			$prev = $link->setText($title);
		}
		$container->add($prev);

		// page input
		$controls = $this->getWrapper('paginator controls container');
		$form = $this->dataGrid->getForm(TRUE);
		$format = $this->dataGrid->translate($this->paginatorFormat);
		$html = str_replace(
			array('%label%', '%input%', '%count%'), array($form['page']->label, $form['page']->control, $paginator->pageCount), $format
		);
		$controls->add(Html::el()->setHtml($html));
		$container->add($controls);

		// next button
		$next = $this->getWrapper('paginator button next');
		$title = $this->dataGrid->translate('Next');
		$link = clone $a->href($this->dataGrid->link('page', $paginator->page + 1));
		if ($next instanceof Html) {
			if ($paginator->isLast())
				$next->addClass('inactive');
			else
				$next = $link->add($next);
			$next->title($title);
		} else {
			$next = $link->setText($title);
		}
		$container->add($next);

		// to-last button
		$last = $this->getWrapper('paginator button last');
		$title = $this->dataGrid->translate('Last');
		$link = clone $a->href($this->dataGrid->link('page', $paginator->pageCount));
		if ($last instanceof Html) {
			if ($paginator->isLast())
				$last->addClass('inactive');
			else
				$last = $link->add($last);
			$last->title($title);
		} else {
			$last = $link->setText($title);
		}
		$container->add($last);

		// page change submit
		$control = $form['pageSubmit']->control;
		$control->title = $control->value;
		$container->add($control);

		unset($first, $prev, $next, $last, $button, $paginator, $link, $a, $form);
		return $container->render();
	}

	/**
	 * Renders data grid operation controls.
	 * @return string
	 */
	public function renderOperations() {
		if (!$this->dataGrid->hasOperations())
			return '';

		$container = $this->getWrapper('operations container');
		$form = $this->dataGrid->getForm(TRUE);
		$container->add($form['operations']->label);
		$container->add($form['operations']->control);
		$container->add($form['operationSubmit']->control->title($form['operationSubmit']->control->value));

		return $container->render();
	}

	/**
	 * Renders info about data grid.
	 * @return string
	 */
	public function renderInfo() {
		$container = $this->getWrapper('info container');
		$paginator = $this->dataGrid->paginator;
		$form = $this->dataGrid->getForm(TRUE);

		$stateSubmit = $form['resetSubmit']->control;
		$stateSubmit->title($stateSubmit->value);

		$this->infoFormat = $this->dataGrid->translate($this->infoFormat);
		$html = str_replace(
			array(
		    '%from%',
		    '%to%',
		    '%count%',
		    '%selectbox%',
		    '%reset%'
			), array(
		    $paginator->itemCount != 0 ? $paginator->offset + 1 : $paginator->offset,
		    $paginator->offset + $paginator->length,
		    $paginator->itemCount,
		    $form['items']->control . $form['itemsSubmit']->control->title($form['itemsSubmit']->control->value),
		    ($this->dataGrid->rememberState ? $stateSubmit : ''),
			), $this->infoFormat
		);

		$container->setHtml(trim($html, ' | '));
		return $container->render();
	}

	/**
	 * Generates datagrid headrer.
	 * @return Html
	 */
	protected function generateHeaderRow() {
		$row = $this->getWrapper('row.header container');

		// checker
		if ($this->dataGrid->hasOperations()) {
			$cell = $this->getWrapper('row.header cell container');
			$cell->addClass('checker');

			if ($this->dataGrid->hasFilters()) {
				$cell->rowspan(2);
			}
			$row->add($cell);
		}

		// headers
		foreach ($this->dataGrid->getColumns() as $column) {
			$renderer = $this->getColumnRenderer($column->getRenderer());
			$cell = $renderer->generateHeaderCell($column);

			$row->add($cell);
		}

		return $row;
	}

	public function getSubmitControl() {
		$form = $this->dataGrid->getForm(TRUE);

		$submitControl = $form['filterSubmit']->control;
		$submitControl->addClass($this->getValue('row.filter control .submit'));
		$submitControl->title = $submitControl->value;

		return $submitControl;
	}

	/**
	 * Generates datagrid filter.
	 * @return Html
	 */
	protected function generateFilterRow() {
		$row = $this->getWrapper('row.filter container');


		foreach ($this->dataGrid->getColumns() as $column) {
			$renderer = $this->getColumnRenderer($column->getRenderer());
			$cell = $renderer->generateFilterCell($column);
			$row->add($cell);
		}

		if (!$this->dataGrid->hasActions()) {
			$submitControl = $this->getSubmitControl();
			$submitControl->addStyle('display: none');
			$row->add($submitControl);
		}

		return $row;
	}

	/**
	 * Generates datagrid row content.
	 * @param  \Traversable|array data
	 * @return Html
	 */
	protected function generateContentRow($data) {
		$form = $this->dataGrid->getForm(TRUE);
		$row = $this->getWrapper('row.content container');

		$primary = $this->dataGrid->keyName;
		if ($this->dataGrid->hasOperations() || $this->dataGrid->hasActions()) {
			if (!isset($data[$primary])) {
				throw new \InvalidArgumentException("Invalid name of key for group operations or actions. Column '" . $primary . "' does not exist in data source.");
			}
		}

		// checker
		if ($this->dataGrid->hasOperations()) {
			$value = $form['checker'][$data[$primary]]->getControl();
			$cell = $this->getWrapper('row.content cell container')->setHtml((string) $value);
			$cell->addClass('checker');
			$row->add($cell);
		}

		// content
		foreach ($this->dataGrid->getColumns() as $column) {
			$renderer = $this->getColumnRenderer($column->getRenderer());
			$cell = $renderer->generateContentCell($column, $data, $primary);
			$this->onCellRender($cell, $column->getName(), !($column instanceof Columns\ActionColumn) ? $data[$column->getName()] : $data);
			$row->add($cell);
		}
		unset($form, $primary, $cell, $value, $action);
		$this->onRowRender($row, $data);
		return $row;
	}

	/**
	 * Generates datagrid footer.
	 * @return Html
	 */
	protected function generateFooterRow() {
		$form = $this->dataGrid->getForm(TRUE);
		$paginator = $this->dataGrid->paginator;
		$row = $this->getWrapper('row.footer container');

		$count = count($this->dataGrid->getColumns());
		if ($this->dataGrid->hasOperations())
			$count++;

		$cell = $this->getWrapper('row.footer cell container');
		$cell->colspan($count);

		$this->footerFormat = $this->dataGrid->translate($this->footerFormat);
		$html = str_replace(
			array(
		    '%operations%',
		    '%paginator%',
		    '%info%',
			), array(
		    $this->renderOperations(),
		    $this->renderPaginator(),
		    $this->renderInfo(),
			), $this->footerFormat
		);
		$cell->setHtml($html);
		$row->add($cell);

		return $row;
	}

	/**
	 * Return the column renderer according to the id
	 * and cache it, so the next call with the same id will 
	 * return the same object
	 * If istance of IColumnRenderer is given instead of the id,
	 * simply pass it through
	 * @param type $id
	 * @return type 
	 */
	protected function getColumnRenderer($id) {
		if ($id instanceof Column\IColumnRenderer)
			return $id;

		if (!is_string($id))
			throw new \Nette\InvalidArgumentException("Renderer id must be a string");

		// in columnRenderers array may be strings as well as renderer instances
		if (!isset($this->columnRenderers[$id])) {
			$this->columnRenderers[$id] = "\\DataGrid\\Renderers\\Column\\" . $id . "Renderer";
		}

		if (is_string($this->columnRenderers[$id])) {
			//try {
			$this->columnRenderers[$id] = new $this->columnRenderers[$id]($this);
//			}
//			catch (...) {
//				$this->columnRenderers[$id] = new ColumnRenderer;
//			}
		}

		return $this->columnRenderers[$id];
	}

	public function setColumnRenderer($id, $renderer) {
		if (!is_string($renderer) && !($renderer instanceof \DataGrid\Renderers\Column\IColumnRenderer))
			throw new \Nette\InvalidArgumentException("Renderer can only be string renderer classname or renderer object itself.");
		$this->columnRenderers[$id] = $renderer;
		return $this;
	}

	/**
	 * @param  string
	 * @return Html
	 */
	// TODO: move to column renderer?
	public function getWrapper($name) {
		$data = $this->getValue($name);
		return $data instanceof Html ? clone $data : Html::el($data);
	}

	/**
	 * @param  string
	 * @return string
	 */
	// TODO: move to column renderer?
	public function getValue($name) {
		$name = explode(' ', $name);
		if (count($name) == 3) {
			$data = & $this->wrappers[$name[0]][$name[1]][$name[2]];
		}
		else {
			$data = & $this->wrappers[$name[0]][$name[1]];
		}
		return $data;
	}

	/**
	 * Returns DataGrid.
	 * @return DataGrid\DataGrid
	 */
	public function getDataGrid() {
		return $this->dataGrid;
	}

}