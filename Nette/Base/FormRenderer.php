<?php

namespace Svecon\Nette\Base;

use Nette\Forms\Form;
use Nette\Forms\IFormRenderer;
use Nette\InvalidStateException;
use Nette\Latte\Engine;
use Nette\Object;
use Nette\Templating\FileTemplate;
use Nette\Templating\ITemplate;
use Nette\Templating\Template;
use Nette\UnexpectedValueException;

class FormRenderer extends Object implements IFormRenderer {

	/** @var ITemplate */
	protected $template;

	public function __construct($templateOrFile = NULL) {
		if ($templateOrFile instanceof Template) {
			$this->template = $templateOrFile;
		} elseif (is_file($templateOrFile)) {
			$this->template = new FileTemplate($templateOrFile);
		} elseif (isset($templateOrFile)) {
			throw new UnexpectedValueException('Argument musí být soubor nebo instance template!');
		}
	}

	public function render(Form $form) {
		$template = $this->getTemplate();
		$template->setTranslator($form->getTranslator());
		$template->onPrepareFilters[] = function($template) {
					$template->registerFilter(new Engine);
				};

		$template->_control = $form->parent;
		$template->form = $form->name;
		$template->render();
	}

	public function getTemplate() {
		if ($this->template instanceof Template) {
			return $this->template;
		} else {
			throw new InvalidStateException("Není nastavena šablona!");
		}
	}

	public function setTemplate(Template $template) {
		$this->template = $template;
	}

}
