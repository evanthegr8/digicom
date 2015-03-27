<?php
/**
* @package			DigiCom Joomla Extension
 * @author			themexpert.com
 * @version			$Revision: 398 $
 * @lastmodified	$LastChangedDate: 2013-11-04 05:07:10 +0100 (Mon, 04 Nov 2013) $
 * @copyright		Copyright (C) 2013 themexpert.com. All rights reserved.
* @license


*/

defined ('_JEXEC') or die ("Go away.");

class DigiComControllerDownloads extends JControllerLegacy
{

	var $_model = null;
	var $_config = null;
	var $_order = null;
	var $_customer = null;

	function __construct () {
		global $Itemid;
		parent::__construct();

		$this->_model = $this->getModel("Downloads");
		$this->_config = $this->getModel("Config");
		$this->_order = $this->getModel("Order");
		$this->_customers_model = $this->getModel("Customer");

		$this->log_link = JRoute::_("index.php?option=com_digicom&view=profile&layout=login&returnpage=downloads&Itemid=".$Itemid, false);
		$this->_customer = new DigiComSiteHelperSession();;
	}
	
	function makeDownload()
	{
		global $Itemid;
		
		if($this->_customer->_user->id < 1)
		{
			$this->setRedirect(JRoute::_($this->log_link, false));
			return;
		}
		
		//require_once( JPATH_COMPONENT_SITE . DS . 'helpers' . DS . 'downloadfileclass.inc' );
		$fileInfo = $this->_model->getfileinfo();
		
		DigiComSiteHelperDigiCom::checkUserAccessToFile($fileInfo,$this->_customer->_user->id);
		
		if(empty($fileInfo->url)){
			$itemid = JFactory::getApplication()->input->get('itemid',0);
			$msg = JText::sprintf('COM_DIGICOM_FILE_DONT_EXIST_DETAILS',$fileInfo->name);
			JFactory::getApplication()->redirect('index.php?option=com_digicom&view=downloads&Itemid='.$itemid,$msg);
		}
		
		$parsed = parse_url($fileInfo->url);
		if (empty($parsed['scheme'])) {
			$fileLink = JPATH_BASE.DS.$fileInfo->url;
		}else{
			$fileLink = $fileInfo->url;
		}
		
		//update hits
		$files =   JTable::getInstance('Files', 'Table');
		$files->load($fileInfo->id);
		$files->hits = $files->hits+1;
		$files->store();
		
		//$downloadfile = new DOWNLOADFILE($fileLink);
		$downloadfile = new DigiComSiteHelperDownloadFile($fileLink);
		if (!$downloadfile->df_download()){
			$itemid = JFactory::getApplication()->input->get('itemid',0);
			$msg = JText::_sprintf("COM_DIGICOM_FILE_DOWNLOAD_FAILED",$fileInfo->name);
			//$msg = JText::sprintf('COM_DIGICOM_FILE_DONT_EXIST_DETAILS',$fileInfo->name);
			JFactory::getApplication()->redirect('index.php?option=com_digicom&view=downloads&Itemid='.$itemid,$msg);
		}			
		
	}
	
}
