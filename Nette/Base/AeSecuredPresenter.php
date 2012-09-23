<?php
namespace Svecon\Nette\Base;

use BasePresenter;
use Navigation\Navigation;
use Nette\Security\User;

/**
 * Authentification secured presenter. Verify if user is loggen in.
 *
 * @author     John Doe
 * @package    MyApplication
 */
abstract class AeSecuredPresenter extends BasePresenter
{

	public function startup()
	{
		parent::startup();

		$user = $this->getUser();

		if (!$user->isLoggedIn())
		{
			if ($user->getLogoutReason() === User::INACTIVITY)
			{
				$this->flashMessage('You have been signed out for your inactivism.', 'warning');
			}

			$backlink = $this->getApplication()->storeRequest();
			$this->redirect('Sign:in', array('backlink' => $backlink));

		}
	}

	protected function createComponentNavigation() {

		$menu_items = array(
			array(
				'label' => 'Reservations',
				'presenter' => 'Reservation',
			),
			array(
				'label' => 'Guests',
				'presenter' => 'Guest',
				'action' => 'database',
				'children' => array(
					array(
						'label' => 'Database',
					),
					array(
						'label' => 'Companies',
						'action' => 'company',
					),
				),
			),
			array(
				'label' => 'Invoices',
				'presenter' => 'Invoice',
			),
			array(
				'label' => 'Settings',
				'presenter' => 'Setting',
				'action' => 'pricelist',
				'children' => array(
					array(
						'label' => 'Pricelists',
					),
					array(
						'label' => 'Guest types',
						'action' => 'guestType',
					),
					array(
						'label' => 'Partners',
						'action' => 'partner',
					),
					array(
						'label' => 'Order sources',
						'action' => 'orderSource',
					),
					array(
						'label' => 'Rooms',
						'action' => 'room',
					),
					array(
						'label' => 'Users',
						'action' => 'user',
					),
					array(
						'label' => 'Flags',
						'action' => 'flag',
					),
					array(
						'label' => 'Reservation states',
						'action' => 'reservationRoomState',
					),
				),
			),
		);

		$nav = new Navigation;

		$user = $this->getUser();

		foreach ($menu_items as $mi)
		{

			$mi['action'] = (isset($mi['action']) ? $mi['action'] : '');

			// kontrola prav
			if (!$user->isAllowed($mi['presenter'], $mi['action']))
				continue;

			// pridej polozky
			$item = $nav->add($mi['label'], $this->link($mi['presenter'] . ':' . $mi['action']));
			if ($this->isLinkCurrent($mi['presenter'] . ':*'))
				$item->setCurrent(TRUE);

			if (isset($mi['children']))
			{
				foreach ($mi['children'] as $child)
				{
					// pokud neco neni definovane, ziskej to od rodice
					$label = (isset($child['label']) ? $child['label'] : $mi['label']);
					$presenter = (isset($child['presenter']) ? $child['presenter'] : $mi['presenter']);
					$action = (isset($child['action']) ? $child['action'] : $mi['action']);

					// kontrola prav
					if (!$user->isAllowed($presenter, $action))
						continue;

					// pridej polozku
					$item->add($label, $this->link($presenter . ':' . $action));
					if ($this->isLinkCurrent($presenter . ':*'))
						$item->setCurrent(TRUE);
				}
			}
		}

		return $nav;
	}

}
