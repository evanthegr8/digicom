<?php
/**
 * @package		DigiCom
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die;

jimport ("joomla.application.component.view");

class DigiComViewCustomers extends JViewLegacy {

	function display ($tpl =  null )
	{
		// Access check.
		if (!JFactory::getUser()->authorise('digicom.customers', 'com_digicom'))
		{
			return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
		}

		$layout = JRequest::getVar('layout','');
		if($layout){
			$this->setLayout($layout);
		}
		
		$customers = $this->get('Items');
		$pagination = $this->get('Pagination');

		$this->custs = $customers;
		$this->pagination = $pagination;

		$prd = JRequest::getVar("prd", 0, "request");
		$this->assign("prd", $prd);

		$keyword = JRequest::getVar("keyword", "", "request");
		$this->assign ("keyword", $keyword);
		
		//set toolber
		$this->addToolbar();
		
		DigiComHelperDigiCom::addSubmenu('customers');
		$this->sidebar = DigiComHelperDigiCom::renderSidebar();
		
		parent::display($tpl);

	}

	function settypeform($tpl = null){
		$id = JRequest::getVar("id", "0");
		if($id == "0"){
			JToolBarHelper::title(JText::_('VIEWLICCUSTOMER').":<small>[".trim(JText::_("DIGI_NEW"))."]</small>");
		}
		else{
			JToolBarHelper::title(JText::_('VIEWLICCUSTOMER').":<small>[".trim(JText::_("DIGI_EDIT"))."]</small>");
		}

		JToolBarHelper::custom('next','forward.png','forward_f2.png','Next',false);
		JToolBarHelper::cancel();
		parent::display($tpl);
	}

	
	
	/**
	 * Add the page title and toolbar.
		*
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		JToolBarHelper::title(JText::_('COM_DIGICOM_CUSTOMERS_TOOLBAR_TITLE'), 'generic.png');

		$bar = JToolBar::getInstance('toolbar');
		// Instantiate a new JLayoutFile instance and render the layout
		$layout = new JLayoutFile('toolbar.title');
		$title=array(
			'title' => JText::_( 'COM_DIGICOM_CUSTOMERS_TOOLBAR_TITLE' ),
			'class' => 'title'
		);
		$bar->appendButton('Custom', $layout->render($title), 'title');
		
		$layout = new JLayoutFile('toolbar.settings');
		$bar->appendButton('Custom', $layout->render(array()), 'settings');
		

	}
	
	
}
