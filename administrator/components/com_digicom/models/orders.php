<?php
/**
 * @package		DigiCom
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die;

class DigiComModelOrders extends JModelList{

	protected $_context = 'com_digicom.order';
	protected $_orders;
	protected $_order;
	protected $_id = null;
	protected $_total = 0;
	protected $_pagination = null;
	protected $_statusList = array("Active", "Pending");

	public function __construct(){
		parent::__construct();
		$cids = JRequest::getVar( 'cid', 0, '', 'array' );

		$this->setId( (int) $cids[0] );
	}

	function populateState($ordering = NULL, $direction = NULL){
		$app = JFactory::getApplication('administrator');
		$this->setState('list.start', $app->getUserStateFromRequest($this->_context . '.list.start', 'limitstart', 0, 'int'));
		$this->setState('list.limit', $app->getUserStateFromRequest($this->_context . '.list.limit', 'limit', $app->getCfg('list_limit', 25) , 'int'));
		$this->setState('selected', JRequest::getVar('cid', array()));
	}

	function getPagination(){
		$pagination=parent::getPagination();
		$pagination->total=$this->total;
		if($pagination->total%$pagination->limit>0){
			$nr_pages=intval($pagination->total/$pagination->limit)+1;
		}
		else{ 
			$nr_pages=intval($pagination->total/$pagination->limit);
		}
		$pagination->set('pages.total',$nr_pages);
		$pagination->set('pages.stop',$nr_pages);
		return $pagination;
	}

	function getPromocodeByCode($code){
		$sql = "SELECT id FROM #__digicom_promocodes WHERE code = '" . $code . "'";
		$this->_db->setQuery( $sql );
		$promocode_id = $this->_db->loadResult();

		if ( $promocode_id ) {
			return $promocode_id;
		} else {
			return "0";
		}

	}

	function getExpireDate($plan_id, $purchase_date_int){
		$sql = "select `duration_count`, `duration_type` from #__digicom_plans where id=".intval($plan_id);
		$this->_db->setQuery($sql);
		$this->_db->query();
		$plan_values = $this->_db->loadAssocList();

		$time = "";
		$expires_date = "";
		switch($plan_values["0"]["duration_type"]){
			case "1" :
					$time = "hour";
					break;
			case "2" :
					$time = "day";
					break;
			case "3" :
					$time = "month";
					break;
			case "4" :
					$time = "year";
					break;
		}

		if($plan_values["0"]["duration_count"] != "-1"){
			$expires_date_int = strtotime("+".$plan_values["0"]["duration_count"]." ".$time, $purchase_date_int);
			$expires_date_string = date("Y-m-d H:i:s", $expires_date_int);
			$expires_date = $expires_date_string;
		}
		else{
			$expires_date = "0000-00-00 00:00:00";
		}
		return $expires_date;
	}

	function saveorder(){
		$post = JRequest::get('post');
		//print_r($post);die;
		$userid = $post['userid'];
		$table = $this->getTable('Customer');
		$table->loadCustommer($userid);
		
		if(empty($table->id) or $table->id < 0){
			$user = JFactory::getUser($userid);
			$name = explode(' ',$user->name);
			
			$cust = new stdClass();
			$cust->id = $user->id;
			$cust->firstname = $name[0];
			$cust->lastname =  (!empty($name[1]) ? $name[1] : '');
			$table->bind($cust);
			$table->store();
		}
		
		//print_r($post);die;
		$config = JFactory::getConfig();
		$tzoffset = $config->get('offset');
		
		if(isset($post['order_date'])&& $post['order_date']){
			$date = JFactory::getDate($post['order_date']);
			$purchase_date = $date->toSql();
			$order_date = $date->toUNIX();
		} else{
			$purchase_date = date('Y-m-d H:i:s', time() + $tzoffset);
			$date = JFactory::getDate();
			$order_date = $date->toUNIX();
		}
		
		$order = array();
		$order['userid'] = $post['userid'];
		$order['order_date'] = $order_date;
		$order['processor'] = $post['processor'];
		$order['promocode'] = $post['promocode'];
		$order['promocodediscount'] = $post['discount'];
		$order['promocodeid'] = $this->getPromocodeByCode( $order['promocode'] );
		$order['number_of_products'] = count( $post['product_id'] );
		$order['currency'] = $post['currency'];
		$order['status'] = $post['status'];
		$order['discount'] = $post['discount'];
		$order['amount'] = $post['amount'];
		$order['amount_paid'] = trim($post['amount_paid']) != "" ? trim($post['amount_paid']) : '0';
		$order['published'] = '1';
		$order_table = $this->getTable( 'Order' );

		if(!$order_table->bind($order)){
			return false;
		}

		if(!$order_table->check()){
			return false;
		}

		if(!$order_table->store()){
			return false;
		}
		$now = time();
		//we have to add orderdetails now;
		$this->addOrderDetails($post['product_id'], $order_table->id, $now, $post['userid'], $post['status']);
		/*
		TODO:: email submit 
		require_once(JPATH_SITE.DS."components".DS."com_digicom".DS."helpers".DS."cronjobs.php");
		submitEmailFromBackend($order, $license);
		*/
		
		return true;
	}
	
	function addOrderDetails($items, $orderid, $now, $customer, $status = "Active")
	{
		$license = array();
		if($status != "Pending")
			$published = 1;
		else
			$published = 0;

		$database = JFactory::getDBO();
		$license_index = 0;
		$jconfig = JFactory::getConfig();
		
		$user_id = $customer;

		if($user_id == 0){
			return false;
		}
		
		//print_r($items);die;
		$table = $this->getTable('Product');
		// start foreach
		foreach($items as $key=>$item)
		{
			if($key >= 0)
			{
				
				$product = $table->load($item);
				$price = $product->price;
				$date = JFactory::getDate();
				$purchase_date = $date->toSql();
				$expire_string = "0000-00-00 00:00:00";
				$package_type = (!empty($product->bundle_source) ? $product->bundle_source : 'reguler');
				$sql = "insert into #__digicom_orders_details(userid, productid,quantity,price, orderid, amount_paid, published, package_type, purchase_date, expires) "
						. "values ('{$user_id}', '{$item}', '1','{$price}','".$orderid."', '0', ".$published.", '".$package_type."', '".$purchase_date."', '".$expire_string."')";
				$database->setQuery($sql);
				$database->query();

				$site_config = JFactory::getConfig();
				$tzoffset = $site_config->get('offset');
				$buy_date = date('Y-m-d H:i:s', time() + $tzoffset);
				$sql = "insert into #__digicom_logs (`userid`, `productid`, `buy_date`, `buy_type`)
						values (".$user_id.", ". $item .", '".$buy_date."', 'new')";
				$database->setQuery($sql);
				$database->query();
				
				
				$sql = "update #__digicom_products set used=used+1 where id = '" . $item . "'";
				$database->setQuery( $sql );
				$database->query();
				
			}
		}
		// end foreach
		
		return true;
	}

	function calcPrice($req){
		$configs = JComponentHelper::getComponent('com_digicom')->params;

		$result = array();
		$amount_subtotal = 0;
		$amount = 0;
		$taxvalue = 0;

		//--------------------------------------------------------
		// Promo code
		//--------------------------------------------------------

		$promovalue = 0;
		$addPromo = false;
		$ontotal = false;
		$onProduct = false;
		if($req->promocode !='none'){

			$q = "select * from #__digicom_promocodes where code = '".trim($req->promocode)."'";
			$this->_db->setQuery($q);
			$promo = $this->_db->loadObject();
			//print_r($promo->discount_enable_range);die;
			if($promo->id > 0){
				//we got real promocode
				$promoid = $promo->id;
				$promocode = $promo->code;

				//validate promocode
				if(!($promo->codelimit <= $promo->used && $promo->codelimit > 0)){
					$addPromo = true;
					//we can use it, it has limit
					if($promo->discount_enable_range==1){
						// for entire cart
						$ontotal = true;
					}else{
						$onProduct = true;
					}
				}
			}
		}

		//echo $ontotal;die;

		//$cust_id = $req->customer_id;
		if(isset($req)){
			foreach($req->pids as $item ) {
				if (!empty($item[0])) {
					
					$sql = "SELECT price FROM #__digicom_products WHERE id = '" . $item[0] . "'";
					$this->_db->setQuery( $sql );
					$plan = $this->_db->loadObject();

					$product_id = $item[0];
					$price = $plan->price;

					$amount_subtotal += $price;
					$amount += $price;
					//$taxvalue += $this->getTax( $product_id, $cust_id, $price );
					$taxvalue += 0;

					//check promocode on product apply
					if($addPromo && $onProduct){
						//TODO: Apply Product promo
						// Get product restrictions
						$sql = "SELECT p.`productid` FROM `#__digicom_promocodes_products` AS p WHERE p.`promoid`=" . $promo->id ." and p.`productid`=".$item[0];
						$this->_db->setQuery( $sql );
						$promo->product = $this->_db->loadObject();

						if (count($promo->product) && $promo->aftertax == '0')
						{
							//promo discount should be applied before taxation
							//we get product to calculate discount
							
							if ($promo->promotype == '0')
							{
								// Use absolute values
								$promovalue += $promo->amount;
							}
							else
							{
								// Use percentage
								$promovalue += $price * $promo->amount / 100;
							}

							$sql = "update #__digicom_promocodes set used=used+1 where id = '" . $promo->id . "'";
							$this->_db->setQuery( $sql );
							$this->_db->query();
											
						}
					} // end if for: product promo check
				} //end if for empty if check
			} //end foreach for products
		}

		//add tax to total
		$amount = $amount + $taxvalue;

		if($addPromo && $onProduct){
			$amount -= $promovalue;
		}

		//--------------------------------------------------------
		// Promo code on cart
		//--------------------------------------------------------
		if($addPromo && $ontotal){
			//echo 'apply promo on cart';die;
			//now lets apply promo discounts if there are any
			if($promo->promotype == '0'){//use absolute values
				$amount -= $promo->amount;
				$promovalue = $promo->amount;
			}
			else{ //use percentage
				$promovalue = $amount * $promo->amount / 100;
				$amount *= 1 - $promo->amount / 100;
			}

			$sql = "update #__digicom_promocodes set used=used+1 where id = '" . $promo->id . "'";
			$this->_db->setQuery( $sql );
			$this->_db->query();
		}
		
		//echo $promovalue;die;
		//--------------------------------------------------------
		$amount_subtotal = $amount_subtotal < 0 ? "0.00" : $amount_subtotal;
		$amount = $amount < 0 ? "0.00" : $amount;

		$result['amount'] = trim( DigiComHelperDigiCom::format_price( $amount_subtotal, $configs->get('currency','USD'), true, $configs ) );
		$result['amount_value'] = trim( DigiComHelperDigiCom::format_price( $amount_subtotal, $configs->get('currency','USD'), false, $configs ) );
		$result['tax_value'] = trim( DigiComHelperDigiCom::format_price( $taxvalue, $configs->get('currency','USD'), false, $configs ) );
		$result['tax'] = trim( DigiComHelperDigiCom::format_price( $taxvalue, $configs->get('currency','USD'), true, $configs ) );;
		$result['discount_sign'] = trim( DigiComHelperDigiCom::format_price( $promovalue, $configs->get('currency','USD'), true, $configs ) );
		$result['discount'] = trim( DigiComHelperDigiCom::format_price( $promovalue, $configs->get('currency','USD'), false, $configs ) );
		$result['total'] = trim( DigiComHelperDigiCom::format_price( $amount, $configs->get('currency','USD'), true, $configs ) );
		$result['total_value'] = trim( DigiComHelperDigiCom::format_price( $amount, $configs->get('currency','USD'), false, $configs ) );
		$result['currency'] = $configs->get('currency','USD');
		$result['shipping'] = 0;

		return $result;
	}

	function setId( $id )
	{
		$this->_id = $id;
		$this->_order = null;
	}

	protected function getListQuery(){
		$db = JFactory::getDBO();
		$c = $this->getInstance( "Config", "DigiComModel" );
		$configs = $c->getConfigs();

		$startdate = JRequest::getVar("startdate", "", "request");
		$startdate = strtotime($startdate);

		$enddate = JRequest::getVar("enddate", "", "request");
		$enddate = strtotime($enddate);

		$keyword = JRequest::getVar( "keyword", "");
		//echo $keyword ;die;
		$keyword_where = "(u.username like '%" . $keyword . "%' 
							or c.firstname like '%" . $keyword . "%' 
							or c.lastname like '%" . $keyword . "%'
							or o.id like '%" . $keyword . "%')";

		$sql = "SELECT o.*, u.username, c.firstname, c.lastname
				FROM #__digicom_orders o
						INNER JOIN 
					#__users u ON u.id=o.userid
						LEFT JOIN 
					#__digicom_customers c ON u.id=c.id ";
					
		$where = array();
		if($startdate > 0) 
			$where[]=" o.order_date > " . $startdate . " ";
		if($enddate > 0) 
			$where[]=" o.order_date < " . $enddate . " ";
		if(strlen( trim( $keyword ) ) > 0)
			$where[]=$keyword_where;
		$where_clause = (count($where))? ' WHERE '. implode(' AND ',$where) : '';
		$sql .= $where_clause. " ORDER BY o.id DESC";
		
		//echo $sql;die;
		
		return $sql;
	}

	function getItems(){
		$config = JFactory::getConfig();
		$app = JFactory::getApplication('administrator');
		$limistart = $app->getUserStateFromRequest($this->context.'.list.start', 'limitstart');
		$limit = $app->getUserStateFromRequest($this->context.'.list.limit', 'limit', $config->get('list_limit'));
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query = $this->getListQuery();

		$db->setQuery($query);
		$db->query();
		$result	= $db->loadObjectList();
		$this->total=count($result);
		$db->setQuery($query, $limistart, $limit);
		$db->query();
		$result	= $db->loadObjectList();

		return $result;
	}

	public static function getChargebacks($order)
	{
		$db = JFactory::getDBO();
		$sql = "SELECT SUM(`cancelled_amount`)
				FROM `#__digicom_orders_details`
				WHERE `cancelled`=1
				  AND `orderid`=" . (int) $order;
		$db->setQuery($sql);
		return $db->loadResult();
	}

	public static function getRefunds($order)
	{
		$db = JFactory::getDBO();
		$sql = "SELECT SUM(`cancelled_amount`)
				FROM `#__digicom_orders_details`
				WHERE `cancelled`=2
				  AND `orderid`=" . (int) $order;
		$db->setQuery($sql);
		return $db->loadResult();
	}

	public static function getDeleted($order, $license=0)
	{
		$db = JFactory::getDBO();
		$sql = "SELECT SUM(`amount_paid`)
				FROM `#__digicom_orders_details`
				WHERE `cancelled`=3
				  AND `orderid`=" . (int) $order;
		$db->setQuery($sql);
		return $db->loadResult();
	}

	public static function isLicenseDeleted($id)
	{
		$db = JFactory::getDBO();
		$sql = "SELECT `cancelled`
				FROM `#__digicom_orders_details`
				WHERE `id`='" . $id . "'";
		$db->setQuery($sql);
		return $db->loadResult();
	}

	
	function getOrder($id = 0){
		if(empty($this->_order)){
			
			$db = JFactory::getDBO();
			if ($id > 0) $this->_id = $id;
			else $id = $this->_id;
			
			$sql = "SELECT o.*"
					." FROM #__digicom_orders o"
					." WHERE o.id='".intval($id)."' AND o.published='1'"
			;
			$db->setQuery($sql);
			$this->_order = $db->loadObject();
			
			$sql = "SELECT p.id, p.name, p.price,p.catid, od.package_type,od.quantity, od.amount_paid FROM #__digicom_products as p, #__digicom_orders_details as od WHERE p.id=od.productid AND od.orderid='". $this->_order->id ."'";
			$db->setQuery($sql);
			$prods = $db->loadObjectList();
			
			$this->_order->products = $prods;
		}
		return $this->_order;
	}

	

	function delete()
	{
		$db = JFactory::getDBO();
		$cids = JRequest::getVar( 'cid', array(0), 'post', 'array' );
		$item = $this->getTable( 'Order' );

		foreach ($cids as $cid)
		{
			if (!$item->delete($cid))
			{
				$this->setError($item->getErrorMsg());
				return false;
			}
		}

		// delete licenses
		$db->setQuery('delete from #__digicom_orders_details where orderid in ('.implode(',', $cids).')');

		if (!$db->query())
		{
			$this->setError($db->getErrorMsg());
			return false;
		}

		return true;
	}

	
	function cycleStatus(){
		$db = JFactory::getDBO();
		$cids = JRequest::getVar( 'id');
		$res = true;

		$sql = "select status from #__digicom_orders where id ='" . $cids . "'";
		$db->setQuery( $sql );

		$status = $db->loadResult();
		$max_status = count( $this->_statusList ) - 1;
		$statid = $max_status;
		foreach ( $this->_statusList as $i => $stat){
			if ( $stat == $status ) {
				$statid = $i;
				break;
			}
		}
		$statid++;
		if ( $statid > $max_status )
			$statid = 0;
		$status = $this->_statusList[$statid];

		$table = $this->getTable('order');
		$table->load($cids);
		$table->status = $status;
		$table->amount_paid = $table->amount;
		if(!$table->store()){
			$res = false;
		}

		if($res && $status == "Pending"){
			$sql = "update #__digicom_orders_details set published=0 where orderid in ('".$cids."')";
			$type = 'process_order';
		}
		elseif($status == "Active"){
			$sql = "update #__digicom_orders_details set published=1 where orderid in ('" . $cids  . "')";
			$type = 'complete_order';
		}
		
		$db->setQuery($sql);
		if(!$db->query()){
			$res = false;
		}

		$this->sendApprovedEmail($cids, $type, $status);

		return $res;
	}


	/*
	* $type = process_order, new_order, cancel_order;
	*/
	function sendApprovedEmail( $cid = 0 , $type = 'complete_order', $status = 'Active', $paid = '')
	{
		if ( $cid < 1 )
			return;
		$db = JFactory::getDBO();
		$order = $this->getTable( "Order" );
		$order->load( $cid );

		$configs = JComponentHelper::getComponent('com_digicom')->params;

		$cust_info = $this->getTable( "Customer" );
		$cust_info->load( $order->userid );

		$my = $cust_info;

		$emailinfo = $configs->get('email');
		$message = $emailinfo->$type->body;
		$subject = $emailinfo->$type->subject;

		$mes = new stdClass();

		$promo = new stdClass(); //$cart->get_promo($cust_info);
		$promo->id = $order->promocodeid;
		$promo->code = $order->promocode;
		if ( $promo->id > 0 ) {
			$promoid = $promo->id;
			$promocode = $promo->code;
		} else {
			$promoid = '0';
			$promocode = '0';
		}

		$amount = DigiComHelperDigiCom::format_price( ($paid ? $paid : $order->amount), $configs->get('currency','USD'), true, $configs );

		$timestamp = time();

		$app = JFactory::getApplication('administrator');
		$sitename = (trim( $configs->get('store_name','DigiCom Store') ) != '') ? $configs->get('store_name','DigiCom Store') : $app->getCfg( 'sitename' );
		$siteurl = (trim( $configs->get('store_url',JURI::root()) ) != '') ? $configs->get('store_url',JURI::root()) : JURI::root();

		$message = str_replace( "[SITENAME]", $sitename, $message );

		$message = str_replace( "../%5BSITEURL%5D", $siteurl, $message );
		$message = str_replace( "%5BSITEURL%5D", $siteurl, $message );
		$message = str_replace( "[SITEURL]", $siteurl, $message );


		$message = str_replace( "[CUSTOMER_USER_NAME]", $my->username, $message );
		$message = str_replace( "[CUSTOMER_FIRST_NAME]", $my->firstname, $message );
		$message = str_replace( "[CUSTOMER_LAST_NAME]", $my->lastname, $message );
		$message = str_replace( "[CUSTOMER_EMAIL]", $my->email, $message );

		$message = str_replace( "[ORDER_DATE]", date( $configs->get('time_format','DD-MM-YYYY'), $timestamp ), $message );
		$message = str_replace( "[ORDER_ID]", $cid, $message );
		$message = str_replace( "[ORDER_AMOUNT]", $amount, $message );
		$message = str_replace( "[NUMBER_OF_PRODUCTS]", $order->number_of_products, $message );
		$message = str_replace( "[DISCOUNT_AMOUNT]", $order->promocodediscount, $message );
		$message = str_replace( "[ORDER_STATUS]", $status, $message );

		$displayed = array();
		$product_list = '';

		$sql = "select od.*, p.name from #__digicom_orders_details od, #__digicom_products p where od.productid=p.id and od.orderid=" . $cid;
		$db->setQuery( $sql );
		$items = $db->loadObjectList();

		$product_list = "";
		foreach ( $items as $item ) {
			$product_list .= $item->quantity . " - " . $item->name . '<br />';
		}

		$message = str_replace( "[PRODUCTS]", $product_list, $message );

		//subject
		$subject = str_replace( "[SITENAME]", $sitename, $subject );
		$subject = str_replace( "../%5BSITEURL%5D", $siteurl, $subject );
		$subject = str_replace( "%5BSITEURL%5D", $siteurl, $subject );
		$subject = str_replace( "[SITEURL]", $siteurl, $subject );

		$subject = str_replace( "[CUSTOMER_USER_NAME]", $my->username, $subject );
		$subject = str_replace( "[CUSTOMER_FIRST_NAME]", $my->firstname, $subject );
		$subject = str_replace( "[CUSTOMER_LAST_NAME]", $my->lastname, $subject );
		$subject = str_replace( "[CUSTOMER_EMAIL]", $my->email, $subject );


		$subject = str_replace( "[ORDER_DATE]", date( $configs->get('time_format','DD-MM-YYYY'), $timestamp ), $subject );
		$subject = str_replace( "[ORDER_ID]", $cid, $subject );
		$subject = str_replace( "[ORDER_AMOUNT]", $amount, $subject );
		$subject = str_replace( "[NUMBER_OF_PRODUCTS]", $order->number_of_products, $subject );
		$subject = str_replace( "[DISCOUNT_AMOUNT]", $order->promocodediscount, $subject );
		$subject = str_replace( "[ORDER_STATUS]", $status, $subject );

		$subject = str_replace( "[PRODUCTS]", $product_list, $subject );

		$subject = html_entity_decode( $subject, ENT_QUOTES );

		$message = html_entity_decode( $message, ENT_QUOTES );

		// Send email to user
		//global $mosConfig_mailfrom, $mosConfig_fromname, $configs;

		$mosConfig_mailfrom = $app->getCfg( "mailfrom" );
		$mosConfig_fromname = $app->getCfg( "fromname" );
		if ( $configs->get('usestoremail',0) == '1' && strlen( trim( $configs->get('store_name','DigiCom Store') ) ) > 0 && strlen( trim( $configs->get('store_email',JFactory::getConfig()->get('mailfrom')) ) ) > 0 ) {
			$adminName2 = $configs->get('store_name','DigiCom Store');
			$adminEmail2 = $configs->get('store_email',JFactory::getConfig()->get('mailfrom'));
		} else if ( $mosConfig_mailfrom != "" && $mosConfig_fromname != "" ) {
			$adminName2 = $mosConfig_fromname;
			$adminEmail2 = $mosConfig_mailfrom;
		} else {

			$query = "SELECT name, email"
			. "\n FROM #__users"
			. "\n WHERE LOWER( usertype ) = 'superadministrator'"
			. "\n OR LOWER( usertype ) = 'super administrator'"
			;
			$database->setQuery( $query );
			$rows = $database->loadObjectList();
			$row2 = $rows[0];
			$adminName2 = $row2->name;
			$adminEmail2 = $row2->email;
		}


		$mailSender = JFactory::getMailer();
		$mailSender->IsHTML( true );
		$mailSender->addRecipient( $my->email );
		$mailSender->setSender( array($adminEmail2, $adminName2) );
		$mailSender->setSubject( $subject );
		$mailSender->setBody( $message );

		if ( !$mailSender->Send() ) {

			//<Your error code management>
		}
		//	mosMail( $adminEmail2, $adminName2, $my->email, $subject, $message, 1 ); // Send mail
		if ( $configs->get('sendmailtoadmin',0) != 0 ) {

			$mailSender = JFactory::getMailer();
			$mailSender->IsHTML( true );
			$mailSender->addRecipient( $adminEmail2 );
			$mailSender->setSender( array($adminEmail2, $adminName2) );
			$mailSender->setSubject( $subject );
			$mailSender->setBody( $message );
			if ( !$mailSender->Send() ) {
				//<Your error code management>
			}
			//mosMail( $adminEmail2, $adminName2, $adminEmail2, $subject, $message, 1 ); // Send mail
		}

		return true;

	}


	
	/**
	 * Method to get a form object.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since	3.2
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_digicom.order', 'order', array('control' => 'jform', 'load_data' => $loadData));
		
		if (empty($form))
		{
			return false;
		}
		
		return $form;
	}

}
