<?php
/**
 * @package		DigiCom
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die;

defined( 'DS' ) or define( 'DS', DIRECTORY_SEPARATOR);

jimport( 'joomla.plugin.plugin' );

require_once(dirname(__FILE__) . '/paypal/helper.php');

class  plgDigiCom_PayPaypal extends JPlugin
{
	public $responseStatus = array (
				'Completed' 		=> 'C',
				'Pending' 			=> 'P',
				'Failed' 			=> 'E',
				'Denied' 			=> 'D',
				'Refunded'			=> 'RF',
				'Canceled_Reversal' => 'CRV',
				'Reversed'			=> 'RV'
			);
	function __construct($subject, $config)
	{
		parent::__construct($subject, $config);
		//Set the language in the class
		//$config = JFactory::getConfig();

		//Define Payment Status codes in Paypal  And Respective Alias in Framework
		$this->responseStatus= array (
				'Completed' 		=> 'C',
				'Pending' 			=> 'P',
				'Failed' 			=> 'E',
				'Denied' 			=> 'D',
				'Refunded'			=> 'RF',
				'Canceled_Reversal' => 'CRV',
				'Reversed'			=> 'RV'
			);
	}

	/* Internal use functions */
	function buildLayoutPath($layout) {
		jimport('joomla.filesystem.file');
		$app = JFactory::getApplication();
		$core_file 	= dirname(__FILE__) . '/' . $this->_name . '/tmpl/default.php';
		$override	= JPATH_BASE . '/templates/' . $app->getTemplate() . '/html/plugins/' . $this->_type . '/' . $this->_name . '/' . $layout . '.php';

		if(JFile::exists($override))
		{
			return $override;
		}
		else
		{
	  	return  $core_file;
	}
	}

	//Builds the layout to be shown, along with hidden fields.
	function buildLayout($vars, $layout = 'default' )
	{
		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath($layout);
		include($layout);
		$html = ob_get_contents(); 
		ob_end_clean();
		return $html;
	}

	// Used to Build List of Payment Gateway in the respective Components
	function onTP_GetInfo($config)
	{

		if(!in_array($this->_name,$config))
		return;
		$obj 		= new stdClass;
		$obj->name 	=$this->params->get( 'plugin_name' );
		$obj->id	= $this->_name;
		return $obj;
	}

	//Constructs the Payment form in case of On Site Payment gateways like Auth.net & constructs the Submit button in case of offsite ones like Paypal
	function onTP_GetHTML($vars)
	{
		$params 		= $this->params;
		$secure_post 	= $params->get('secure_post');
		$sandbox 		= $params->get('sandbox');
		$vars->action_url = plgDigiCom_PayPaypalHelper::buildPaypalUrl($secure_post , $sandbox);

		//Take this receiver email address from plugin if component not provided it
		if(empty($vars->business))
		$vars->business = $this->params->get('business');
		$html = $this->buildLayout($vars);
		return $html;
	}



	function onTP_Processpayment($data) 
	{
		$params 		= $this->params;
		$secure_post 	= $params->get('secure_post');
		$sandbox 		= $params->get('sandbox');
		$paypal_url 	= plgDigiCom_PayPaypalHelper::buildPaypalUrl($secure_post , $sandbox);

		$verify 		= plgDigiCom_PayPaypalHelper::validateIPN($data);

		if (!$verify) { return false; }

		$payment_status = $this->translateResponse( $data['payment_status'] );

		$result = array(
			'order_id'=>$data['custom'],
			'transaction_id'=>$data['txn_id'],
			'buyer_email'=>$data['payer_email'],
			'status'=>$payment_status,
			'txn_type'=>$data['txn_type'],
			'total_paid_amt'=>$data['mc_gross'],
			'raw_data'=>$data,
			'processor'=>'paypal'
		);
		return $result;
	}

	function translateResponse($payment_status){
			if(array_key_exists($payment_status,$this->responseStatus)){
				return $this->responseStatus[$payment_status];
			}
	}
	function onTP_Storelog($data)
	{
			$log = plgDigiCom_PayPaypalHelper::Storelog($this->_name,$data);

	}
}
