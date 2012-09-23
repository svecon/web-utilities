<?php

namespace Svecon\Nette\Base;

use Nette\Application\UI\Form;

class Form extends Form {

	/** @var string */
	public $title;

	public function __construct($title = '') {
		parent::__construct();

		$this->title = $title;

		$this->addProtection('Time limit expired. Please send this form again.');

		foreach ($this->formatTemplateFiles() as $file) {
			if (is_file($file)) {
				$this->renderer = new FormRenderer($file);
				break;
			}
		}
	}

	/**
	 * Vraci pole souboru, ktere mohou obsahovat sablonu formulare
	 * @return array
	 */
	protected function formatTemplateFiles() {
		$dir = dirname($this->reflection->getFilename());
		return array
			(
			"$dir/{$this->reflection->name}.latte",
		);
	}

	protected function attached($presenter) {
		parent::attached($presenter);

		if (empty($this->title))
			$this->title = $this->name;

		$this->getElementPrototype()->class[] = $this->name;

		$this->setTranslator($presenter->context->translator);
	}

}