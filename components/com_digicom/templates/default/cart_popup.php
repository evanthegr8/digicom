<?php
/**
 * @package		DigiCom
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die;

// TODO : Remvoe JRequest and cleanup code, naming convention
JHtml::_('jquery.framework');
JHTML::_('behavior.modal');
$user = JFactory::getUser();
$document=JFactory::getDocument();
$configs = $this->configs;
$agreeterms = JRequest::getVar("agreeterms", "");
$processor = JRequest::getVar("processor", "");
$Itemid = JRequest::getInt("Itemid", 0);
$items = $this->items;
?>
<div id="digicom" class="digicom-wrapper com_digicom cart">
<?php
$button_value = "COM_DIGICOM_CHECKOUT";
$onclick = "document.getElementById('returnpage').value='checkout'; document.getElementById('type_button').value='checkout';";

if($user->id == 0 || $this->customer->_customer->country == "")
{
	$button_value = "DSSAVEPROFILE";
	$onclick = "document.getElementById(\'returnpage\').value=\'login_register\'; document.getElementById(\'type_button\').value=\'checkout\';";
}

if($configs->get('askterms',0) == '1')
{
	$onclick= "if(document.cart_form.agreeterms.checked != true){ alert(\'".JText::_("COM_DIGICOM_CART_ACCEPT_TERMS_CONDITIONS_REQUIRED_NOTICE")."\'); return false; }".$onclick;
}

$url="index.php?option=com_digicom&controller=cart&task=gethtml&tmpl=component&format=raw&processor=";

$total = 0;//$this->total;//0;
$discount = $this->discount;//0;
$cat_url = $this->cat_url;
$totalfields = 0;
$shippingexists = 0;
$from = JRequest::getVar("from", "");
$nr_columns = 4;
?>

<?php 
	$formlink = JRoute::_("index.php?option=com_digicom&view=cart&Itemid=".$Itemid);
	$currency = $configs->get('currency','USD');
?>

<form name="cart_form" method="post" action="<?php echo $formlink?>" onSubmit="return cartformsubmit();">
	<table class="table table-hover table-striped">
	<tbody><?php
	$k = 0;
	foreach($items as $itemnum => $item){
		if($itemnum < 0){
			continue;
		}
	?>
		<tr>
			<!-- Product image -->
			<td width="70">
				<img height="100" width="200" title="<?php echo $item->name; ?>" src="<?php echo $item->images; ?>" alt="<?php echo $item->name; ?>"/>
			</td>
			<!-- /End Product image -->

			<!-- Product name -->
			<td style="text-align:left;" class="digicom_product_name">
				<?php 
					echo $item->name; 
				?>
			</td>
			<!-- /End Product name -->

			<!-- Price -->
			<td align="right" style="vertical-align:top;text-align:right;">
				<?php 
					echo DigiComSiteHelperDigiCom::format_price2($item->price, $item->currency, true, $configs);
					$currency = $item->currency;
				?>
			</td>
			<!-- /End Price -->

			<!-- Remove -->
			<td align="center" style="vertical-align:top;width:80px;text-align:right;">
				<a href="#" onclick="javascript:deleteFromCart(<?php echo $item->cid; ?>);"><i class="icon-remove"></i></a>
			</td>
			<!-- /End Remove -->
		</tr>
	<?php
		$total += $item->subtotal;
		$k++;
	}
	?>
		</tbody>
		<tfoot>
		<tr class="info">
			<td></td>
			<td>
				<b><?php
					$text = "COM_DIGICOM_ITEM_IN_CART";
					if($k > 1){
						$text = "COM_DIGICOM_ITEMS_IN_CART";
					}
					echo $k." ".JText::_($text); 
				?></b>
			</td>
			<td><b><?php echo JText::_("COM_DIGICOM_SUBTOTAL");?></b></td>
			<td>
				<b><?php echo DigiComSiteHelperDigiCom::format_price2($total, $currency, true, $configs); ?></b>
			</td>
		</tr>
		</tfoot>
	</table>

	<input name="controller" type="hidden" id="controller" value="Cart">
	<input name="task" type="hidden" id="task" value="updateCart">
	<input name="returnpage" type="hidden" id="returnpage" value="">
	<input name="Itemid" type="hidden" value="<?php global $Itemid; echo $Itemid; ?>">
	<input name="promocode" type="hidden" value="" />
	<input type="hidden" name="processor" id="processor" value="paypaypal">
</form>
</div>
<?php JFactory::getApplication()->close(); ?>
