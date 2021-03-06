<?php
/**
 * @package		DigiCom
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die;

class DigiComViewDiscounts extends JViewLegacy
{

	function display ($tpl =  null )
	{
		// Access check.
		if (!JFactory::getUser()->authorise('digicom.discounts', 'com_digicom'))
		{
			return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
		}
        
		$condition = JRequest::getVar("condition", '1');
		$this->assign ("condition", $condition);

		$status = JRequest::getVar("status", '');
		$this->assign ("status", $status);

		$this->promos = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->configs = $this->get('configs');

		
		//set toolber
		$this->addToolbar();
		
		DigiComHelperDigiCom::addSubmenu('discounts');
		$this->sidebar = DigiComHelperDigiCom::renderSidebar();
		
		parent::display($tpl);

	}

	/**
	 * Add the page title and toolbar.
		*
	 * @since   1.6
	*/
	protected function addToolbar()
	{
		JToolBarHelper::title(JText::_('COM_DIGICOM_DISCOUNTS_TOOLBAR_TITLE'), 'generic.png');

		$bar = JToolBar::getInstance('toolbar');
		// Instantiate a new JLayoutFile instance and render the layout
		$layout = new JLayoutFile('toolbar.title');
		$title=array(
			'title' => JText::_( 'COM_DIGICOM_DISCOUNTS_TOOLBAR_TITLE' ),
			'class' => 'title'
		);
		$bar->appendButton('Custom', $layout->render($title), 'title');
		
		$layout = new JLayoutFile('toolbar.settings');
		$bar->appendButton('Custom', $layout->render(array()), 'settings');
		
		JToolBarHelper::addNew('discount.add');
		JToolBarHelper::divider();
		JToolBarHelper::publishList('discounts.publish');
		JToolBarHelper::unpublishList('discounts.unpublish');

		JToolBarHelper::divider();

		JToolBarHelper::deleteList(JText::_('COM_DIGICOM_DISCOUNTS_DELETE_CONFIRMATION'),'discounts.delete');
	}
}
