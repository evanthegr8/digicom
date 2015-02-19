<?php
/**
* @package			DigiCom Joomla Extension
 * @author			themexpert.com
 * @version			$Revision: 341 $
 * @lastmodified	$LastChangedDate: 2013-10-10 14:28:28 +0200 (Thu, 10 Oct 2013) $
 * @copyright		Copyright (C) 2013 themexpert.com. All rights reserved.
* @license			GNU/GPLv3
*/

defined ('_JEXEC') or die ("Go away.");

JHtml::_('behavior.multiselect');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHTML::_('behavior.tooltip');
$configs = $this->configs;
$document = JFactory::getDocument();

if(isset($document->_scripts)){
	$temp_array = array();
	foreach($document->_scripts as $path=>$type){
		if(strpos($path, "plugins/system/mtupgrade/mootools.js") !== FALSE){
			$temp = str_replace("plugins/system/mtupgrade/mootools.js", "media/system/js/mootools.js", $path);
			$temp_array[$temp] = $type;
		}
		else{
			$temp_array[$path] = $type;
		}
	}
	if(isset($temp_array) && count($temp_array) > 0){
		$document->_scripts = $temp_array;
	}
}
$ajax = <<<EOD

	window.addEvent('domready', function(){

		$('buttonaddproduct').addEvent('click', function(e) {
			e.stop()||new Event(e).stop();

			var url = "index.php?option=com_digicom&controller=orders&task=productitem&no_html=1&tmpl=component";

			 var req = new Request.HTML({
				method: 'get',
				url: url,
				data: { 'do' : '1' },
				onComplete: function(response){
					$('product_items').adopt(response);
					$$('a.modal').each(function(el) {
						el.addEvent('click', function(e) {
							new Event(e).stop();
							SqueezeBox.fromElement(el);
						});
					});
				}
			}).send();
		});
	});

	function grayBoxiJoomla(link_element, width, height){
		SqueezeBox.open(link_element, {
			handler: 'iframe',
			size: {x: width, y: height}
		});
	}

	function changePlain(castid) {

		var product_ids = [];

		var inputs = Array.prototype.slice.call(document.getElementsByTagName('input'));
		for(i=0; i<inputs.length; i++){
			el = inputs[i];
			if(el.name.indexOf('product_id[') == 0){
				var tid = el.getAttribute('id').substr('product_id'.length, el.getAttribute('id').length);
				var tproduct = el.value;

				var tmp = [];
				tmp.push(tproduct);
				product_ids.push(tmp);
			}
		}
		var tprocessor = $('processor').value;
		var tpromocode = $('promocode').value;
		var tamount_paid = $('amount_paid').value;

		//var jsonString = JSON.encode({pids: product_ids, customer_id: castid, processor: tprocessor, promocode: tpromocode, amount_paid: tamount_paid});
		var jsonString = JSON.encode({pids: product_ids, processor: tprocessor, promocode: tpromocode, amount_paid: tamount_paid});
		var url = "index.php?option=com_digicom&controller=orders&task=calc&no_html=1&jsonString="+jsonString;
		console.log(url);
		var req = new Request.HTML({
			method: 'get',
			url: url,
			data: { 'do' : '1' },
			postBody: jsonString,
			onComplete: function(req){
				$("from_ajax_div").empty().adopt(req);
				var encoded_string = $("from_ajax_div").innerHTML;

				var resp = JSON.decode(encoded_string);

				var processor_select = $("processor");
				var CountPayments = processor_select.options.length;
				for(payindex = 0; payindex < CountPayments; payindex++) {
					if (processor_select.options[payindex].value == resp.processor) {
						processor_select.options[payindex].selected = true;
					}
				}

				var promocode_select = $("promocode");
				var CountPromocodes = promocode_select.options.length;
				for(payindex = 0; payindex < CountPromocodes; payindex++) {
					if (promocode_select.options[payindex].value == resp.promocode) {
						promocode_select.options[payindex].selected = true;
					}
				}

				$("amount").innerHTML = resp.amount;
				$("amount_value").value = resp.amount_value;
				$("tax").innerHTML = resp.tax;
				$("tax_value").value = resp.tax_value;
				$("discount").value = resp.discount;
				$("discount_sign").innerHTML = resp.discount_sign;
				$("total").innerHTML = resp.total;
				$("amount_paid").value = resp.total_value;
				$("currency_amount_paid").innerHTML = resp.currency;
				$("currency_value").value = resp.currency;
			}
		}).send();

	}

	function remove_product(id){
		var complete_id = 'product_item_'+id;
		var par = document.getElementById(complete_id);
		var parent_element = par.parentNode;
		parent_element.removeChild(par);
		changePlain();
	}

	function checkSubscriptionPlan(id){
		if(document.getElementById('subscr_plan_select'+id).style.display == 'none'){
			document.getElementById('subscr_plan_'+id).style.display = 'none';
		}
		else{
			document.getElementById('subscr_plan_'+id).style.display = '';
		}
	}

	function show_attribute_product(id) {
		//document.getElementById('subscr_type_'+id).style.display = '';
		//setTimeout(function(){checkSubscriptionPlan(id)}, 2000);
		//document.getElementById('subscr_plan_'+id).style.display = '';
		//show_licences_renew(id);
		changePlain(id);
	}

	function show_licences_renew(id) {

		var type = "";
		var pid = document.getElementById('product_id'+id).value;

		// Plan
		var url = "index.php?option=com_digicom&controller=plans&task=planitem&hid="+id+"&pid="+pid+"&type="+type+"&no_html=1";
		var req = new Request.HTML({
			method: 'get',
			url: url,
			data: { 'do' : '1' },
			onComplete: function(response){
				if(document.getElementById('subscr_plan_select'+id)){
					document.getElementById('subscr_plan_select'+id).parentNode.empty().adopt(response);
				}
				changePlain();
			}
		}).send();
		
	}

/* ]]> */
EOD;
$doc = JFactory::getDocument();
$doc->addScriptDeclaration( $ajax );
?>
<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="">
	<?php else : ?>
	<div id="j-main-container" class="">
	<?php endif;?>
	
<div id="returnJSON"></div>
<fieldset class="adminform">
	<legend><?php echo JText::_( 'New Order' ); ?></legend>

	<p class="alert alert-info"> <?php echo JText::_("HEADER_ORDERS_ADD"); ?> </p>


<form id="adminForm" action="index.php" method="post" name="adminForm">
	<table width="100%">
		<tr>
			<td width="30%">Username</td>
			<td><?php echo $this->form->getInput('userid'); ?></td>
			<td>
				
			</td>
		</tr>
		<tr>
			<td colspan="3" style="background:#ccc;">
				<h3><?php echo JText::_( 'Add Product(s) to this order' ); ?></h3>
			</td>
		</tr>
		<tr>
			<td colspan="3" id="product_items">
<!-- Products -->
<div id="product_item_1">
</div>
<!-- /Products -->
			</td>
		</tr>
		<!-- Add Products -->
		<tr>
			<td style="border-top:1px solid #ccc;padding-top:5px;"></td>
			<td style="border-top:1px solid #ccc;padding-top:5px;">
				<!-- a href="#" id="buttonaddproduct"><?php echo JText::_( 'Add Product' ); ?></a -->
				<input class="inputbox btn btn-small" type="button" id="buttonaddproduct" name="add_product_button" value="<?php echo JText::_( 'Add Product' ); ?>"/>
			</td>
			<td style="border-top:1px solid #ccc;padding-top:5px;"></td>
		</tr>
		<!-- Common info  -->
		<tr>
			<td style="border-top:1px solid #ccc;padding-top:5px;"><?php echo JText::_( 'COM_DIGICOM_FIELD_ORDER_STATUS_LABEL' ); ?></td>
			<td style="border-top:1px solid #ccc;padding-top:5px;" ><?php echo $this->form->getInput('status'); ?></td>
			<td style="border-top:1px solid #ccc;padding-top:5px;" ></td>
		</tr>
		
		<tr>
			<td><?php echo JText::_( 'Payment method' ); ?></td>
			<td id="payment_method">
				<?php // echo $this->plugins; ?>
				<select id="processor" name="processor" class="inputbox" size="1">
					<?php
					$db = JFactory::getDBO();
					$condtion = array(0 => '\'payment\'');
					$condtionatype = join(',',$condtion);
					if(JVERSION >= '1.6.0')
					{
						$query = "SELECT extension_id as id,name,element,enabled as published
								  FROM #__extensions
								  WHERE folder in ($condtionatype) AND enabled=1";
					}
					else
					{
						$query = "SELECT id,name,element,published
								  FROM #__plugins
								  WHERE folder in ($condtionatype) AND published=1";
					}
					$db->setQuery($query);
					$gatewayplugin = $db->loadobjectList();

					$lang = JFactory::getLanguage();
					$options = array();
					$options[] = JHTML::_('select.option', '', 'Select payment gateway');
					foreach($gatewayplugin as $gateway)
					{
						$gatewayname = strtoupper(str_replace('plugpayment', '',$gateway->element));
						$lang->load('plg_payment_' . strtolower($gatewayname), JPATH_ADMINISTRATOR);
						echo '<option value="' . $gateway->element . '" ' . ($configs->get('default_payment','paypal') == $gateway->element ? "selected" : "") . '>' . JText::_($gatewayname) . '</option>';
					} ?>
				</select>
				<?php
					echo JHTML::tooltip(JText::_("COM_DIGICOM_ORDERPAYMETHOD_TIP"), '', '',  "<img src=".JURI::root()."administrator/components/com_digicom/assets/images/tooltip.png />", '', '', 'hasTip');
				?>
			</td>
			<td></td>
		</tr>
		<tr>
			<td><?php echo JText::_( 'Promocode' ); ?></td>
			<td>
				<?php echo $this->promocode; ?>
				<?php
					echo JHTML::tooltip(JText::_("COM_DIGICOM_ORDERPROMOCODE_TIP"), '', '',  "<img src=".JURI::root()."administrator/components/com_digicom/assets/images/tooltip.png />", '', '', 'hasTip');
				?>
			</td>
			<td></td>
		</tr>
		<tr>
			<td><?php echo JText::_( 'Amount' ); ?></td>
			<td id="amount" width="10%"></td>
			<td>
			<?php
				echo JHTML::tooltip(JText::_("COM_DIGICOM_ORDERAMOUNT_TIP"), '', '',  "<img src=".JURI::root()."administrator/components/com_digicom/assets/images/tooltip.png />", '', '', 'hasTip');
			?>
			</td>
		</tr>
		<tr style="display:none">
			<td><?php echo JText::_( 'Tax' ); ?></td>
			<td id="tax"></td>
			<td>
			<?php
				echo JHTML::tooltip(JText::_("COM_DIGICOM_ORDERTAX_TIP"), '', '',  "<img src=".JURI::root()."administrator/components/com_digicom/assets/images/tooltip.png />", '', '', 'hasTip');
			?>
			</td>
		</tr>
		<tr>
			<td><?php echo JText::_( 'Discount' ); ?></td>
			<td id="discount_sign"></td>
			<td></td>
		</tr>
		<tr>
			<td><?php echo JText::_( 'Total' ); ?></td>
			<td id="total"></td>
			<td></td>
		</tr>
		<tr>
			<td><?php echo JText::_( 'Amount paid' ); ?></td>
			<td><span id="currency_amount_paid"></span><input id="amount_paid" name="amount_paid" type="text" value=""/></td>
			<td>
			<?php
				echo JHTML::tooltip(JText::_("COM_DIGICOM_ORDERAMOUNTPAID_TIP"), '', '',  "<img src=".JURI::root()."administrator/components/com_digicom/assets/images/tooltip.png />", '', '', 'hasTip');
			?>
			</td>
		</tr>
		
		<tr>
			<td><?php echo JText::_( 'Order Date' ); ?></td>
			<td>
				<?php echo $this->form->getInput('order_date'); ?>
			</td>
			<td>
			</td>
		</tr>
		<!-- /Common info  -->
	</table>


		<input type="hidden" name="option" value="com_digicom"/>
		<input type="hidden" name="controller" value="orders"/>
		<input type="hidden" id="tax_value" name="tax" value="0"/>
		<input type="hidden" name="shipping" value="0"/>
		<input type="hidden" id="amount_value" name="amount" value="0"/>
		<input type="hidden" id="discount" name="discount" value="0"/>
		<input type="hidden" id="currency_value" name="currency" value=""/>
		<input type="hidden" name="task" value=""/>
</form>


<div style="border-top:1px solid #ccc;padding-top:5px;">
	<input onclick="javascript: submitbutton('saveorder')" type="button" name="task" value="Save" class="btn btn-success" />
	<div id="from_ajax_div" style="display:none;"></div>
</div>

</fieldset>

<div>