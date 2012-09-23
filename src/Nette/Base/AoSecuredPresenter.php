<?php

namespace Svecon\Nette\Base;

/**
 * Authorization secured presenter.
 *
 * @author     John Doe
 * @package    MyApplication
 */
abstract class AoSecuredPresenter extends AeSecuredPresenter {

	/** @var array */
	protected $controlFactory;

	public function startup() {
		parent::startup();

		$user = $this->getUser();
		if (!$user->isAllowed($this->name, $this->action)) {
			$this->flashMessage('You do not have permitions to visit this section!', 'warning');
			$this->redirect('Sign:in');
		}
	}

	public function beforeRender() {
		$this->template->controlFactory = $this->controlFactory;
	}

//	public function handleMakeControl($controlName, $controlParams = array()) {
//		$this->invalidateControl('singleReservationFormComponent-singleReservationForm');
//		//$this->invalidateControl('controlFactory');
//		$this->controlFactory['name'] = $controlName;
//		$this->controlFactory['params'] = $controlParams;
//	}

	public function handleInvalidateControl($snippet_data = array()) {
		// nastav parametry
		foreach ($snippet_data as $control_snippet => $data) {
			$controls = explode('-', $control_snippet);
			$control = array_shift($controls);
			$snippet = join('-', $controls);

			if (!empty($data) && $data != 'null') {
				foreach ($data as $var => $value)
					$this[$control_snippet]->$var = $value;
			}

			if (empty($snippet))
				$this[$control]->invalidateControl();
			else
				$this[$control]->invalidateControl($snippet);
		}
	}

//	public function handleAutocomplete($input_name, $form_id, $term)
//	{
//		Nette\Forms\Controls\Autocomplete::handleAutocomplete($this, $input_name, $form_id, $term);
//	}
}
