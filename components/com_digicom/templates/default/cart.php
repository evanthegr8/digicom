<?php
/**
 * @package		DigiCom
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die;

// TODO : Remove JReqeust and need a lot cleaup
JHtml::_('jquery.framework');
JHTML::_('behavior.modal');
JHtml::_('formbehavior.chosen', 'select');

$user = JFactory::getUser();
$document=JFactory::getDocument();
$configs = $this->configs;
$agreeterms = JRequest::getVar("agreeterms", "");
$processor = JRequest::getVar("processor", "");
$Itemid = JRequest::getInt("Itemid", 0);
$items = $this->items;
$button_value = "COM_DIGICOM_CHECKOUT";
$onclick = "document.getElementById('returnpage').value='checkout'; document.getElementById('type_button').value='checkout';";

if($user->id == 0 || $this->customer->_customer->country == "")
{
	$button_value = "COM_DIGICOM_CONTINUE";
}

if($configs->get('askterms',0) == '1')
{
	$onclick= "if(document.cart_form.agreeterms.checked != true){ alert(\'".JText::_("ACCEPT_TERMS_CONDITIONS")."\'); return false; }".$onclick;
}

$url="index.php?option=com_digicom&view=cart&task=cart.gethtml&tmpl=component&format=raw&processor=";

$total = 0;//$this->total;//0;
$discount = $this->discount;//0;
$cat_url = $this->cat_url;
$shippingexists = 0;
$from = JRequest::getVar("from", "");
$nr_columns = 4;
$invisible = 'style="display:none;"';
$formlink = JRoute::_("index.php?option=com_digicom&view=cart&Itemid=".$Itemid);
$tax = $this->tax; 
?>
<div id="digicom">

	<?php if($configs->get('show_steps',1) == 1){ ?>
	<div class="pagination pagination-centered">
		<ul>
			<li class="active"><span><?php echo JText::_("COM_DIGICOM_BUYING_PROCESS_STEP_ONE"); ?></span></li>
			<li><span><?php echo JText::_("COM_DIGICOM_BUYING_PROCESS_STEP_TWO"); ?></span></li>
			<li><span><?php echo JText::_("COM_DIGICOM_BUYING_PROCESS_STEP_THREE"); ?></span></li>
		</ul>
	</div>
	<?php } ?>

	<?php if(count($items) == 0): ?>
	<div class="alert alert-warning">
		<?php echo JText::_("COM_DIGICOM_CART_IS_EMPTY_NOTICE"); ?>
	</div>
	<?php else: ?>

	<div class="digi-cart">
		<form id="cart_form" name="cart_form" method="post" action="<?php echo $formlink?>" onSubmit="return cartformsubmit(<?php echo $user->id; ?>,<?php echo $configs->get('askterms',0); ?>);">
			<?php if($user->id != "0"){ ?>
			<div class="row-fluid">
				<div class="span12" style="text-align:right;vertical-align:bottom;">
					<?php echo JText::sprintf("COM_DIGICOM_CART_LOGGED_IN_AS",$user->name); ?>
				</div>
			</div>
			<?php } ?>

			<table id="digicomcarttable" class="table table-striped table-bordered" width="100%">
				<thead>
				<tr valign="top">
					<th width="30%">
						<?php echo JText::_("COM_DIGICOM_PRODUCT");?>
					</th>
					<th>
						<?php echo JText::_("COM_DIGICOM_PRICE_PLAN");?>
					</th>
					
					<th>
						<?php echo JText::_("COM_DIGICOM_QUANTITY"); ?>
					</th>

					<?php if ($tax['discount_calculated']){?>
					<th>
						<?php echo JText::_("COM_DIGICOM_PROMO_DISCOUNT"); ?>
					</th>
					<?php } ?>

					<th><?php echo JText::_("COM_DIGICOM_SUBTOTAL");?></th>

					<th><?php echo JText::_("COM_DIGICOM_CART_REMOVE_ITEM");?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($items as $itemnum => $item ):
					if($itemnum < 0){
						continue;
					}
					$item_link = JRoute::_(DigiComHelperRoute::getProductRoute($item->id, $item->catid, $item->language));
					?>
					<tr>
						<td>
							<a href="<?php echo $item_link; ?>" target="blank"><?php echo $item->name; ?></a>
						</td>

						<td nowrap="nowrap">
							<?php echo DigiComSiteHelperDigiCom::format_price2($item->price, $item->currency, true, $configs); ?>
						</td>

						<td align="center" nowrap="nowrap">
							<span class="digicom_details">
								<strong>
									<?php if($configs->get('show_quantity',0) == "1") { ?>								
										<input id="quantity<?php echo $item->cid; ?>" type="number" onchange="update_cart(<?php echo $item->cid; ?>);" name="quantity[<?php echo $item->cid; ?>]" min="1" class="input-small" value="<?php echo $item->quantity; ?>" size="2" placeholder="<?php echo JText::_('COM_DIGICOM_QUANTITY'); ?>">
									<?php } else {
										echo $item->quantity;
									} ?>
								</strong>
							</span>
						</td>

						<?php if($tax['discount_calculated']) : ?>
						<td style="text-align:center;" nowrap="nowrap">
							<span id="cart_item_discount<?php echo $item->cid; ?>" class="digi_cart_amount">
								<?php
								$value_discount = 0;
								if ( $item->discount > 0)
								{
									$value_discount = $item->discount;
								}
								elseif ( isset($item->percent_discount) && $item->percent_discount > 0)
								{
									$value_discount = ($item->price * $item->percent_discount) / 100;
								}
								echo (isset($item->percent_discount) && $item->percent_discount > 0) ? $item->percent_discount : DigiComSiteHelperDigiCom::format_price2($item->discount, $item->currency, true, $configs);;?>
							</span>
						</td>
						<?php endif; ?>

						<td nowrap>
							<span id="cart_item_total<?php echo $item->cid; ?>" class="digi_cart_amount"><?php
								echo DigiComSiteHelperDigiCom::format_price2($item->subtotal-(isset($value_discount) ? $value_discount : 0), $item->currency, true, $configs); ?>
							</span>
						</td>

						<td nowrap="nowrap">
							<a href="javascript:void();" class="btn btn-small btn-danger" onclick="RemoveFromCart(<?php echo $item->cid;?>);"><i class="icon-trash icon-white"></i></a>
						</td>
					</tr>
					<?php
					$total += $item->subtotal;
				endforeach;
				?>
			</tbody>
		</table>

		<table id="digicomcartpromo" width="100%">
			<tr valign="top">
				<td class="general_text" colspan="<?php echo $nr_columns - 1; ?>" valign="bottom">
					<?php echo JText::_("COM_DIGICOM_CART_IF_PROMOCODE_LABEL"); ?>
				</td>
				<td nowrap="nowrap">
					<ul class="unstyled">
						<?php if ($configs->get('tax_summary',0) == 1) { ?>
							<?php if ($tax['promo'] > 0 && $tax['promoaftertax'] == '0'): ?>
							<li class="digi_cart_total"><?php echo JText::_("DSPROMODISCOUNT"); ?></li>
							<?php endif; ?>
			
							<?php  if (($tax['value'] > 0) || ($configs->get('tax_zero',1) == 1) && ($this->customer->_user->id > 0)) : ?>
							<li class="digi_cart_total"><?php echo $tax['type']; ?></li>
							<?php endif; ?>
			
							<?php  if ($tax['shipping'] > 0 && $this->customer->_user->id > 0): ?>
							<li class="digi_cart_total"><?php echo JText::_("DSSHIPING"); ?></li>
							<?php endif; ?>
			
							<?php if ($tax['promo'] > 0 && $tax['promoaftertax'] == '1'): ?>
							<li class="digi_cart_total"><?php echo JText::_("DSPROMOCODEDISCOUNT"); ?></li>
							<?php endif; ?>
		
						<?php }	?>
					</ul>
				</td>
				
				<?php if ($configs->get('tax_summary',0) == 1) { ?>
				<td nowrap="nowrap" style="text-align: center; padding-top:15px;">
					<ul class="unstyled">

						<?php if ($tax['promo'] > 0 && $tax['promoaftertax'] == '0') : ?>
						<li class="digi_cart_amount" style="text-align:right;" id="digicom_cart_discount"><?php echo DigiComSiteHelperDigiCom::format_price2($tax['promo'], $tax['currency'], true, $configs) ?></li>
						<?php endif;?>

						<?php if (($tax['value'] > 0 || $configs->get('tax_zero',1) == 1) && $this->customer->_user->id > 0) : ?>
						<li class="digi_cart_amount" style="text-align:right;" id="digicom_cart_tax"><?php echo DigiComSiteHelperDigiCom::format_price2($tax['value'], $tax['currency'], true, $configs); ?></li>
						<?php endif; ?>

						<?php if ($tax['shipping'] > 0 && $this->customer->_user->id > 0) : ?>
						<li class="digi_cart_amount" style="text-align:right;"><?php echo DigiComSiteHelperDigiCom::format_price2($tax['shipping'], $tax['currency'], true, $configs); ?></li>
						<?php endif; ?>

						<?php if ($tax['promo'] > 0 && $tax['promoaftertax'] == '1') : ?>
							<li class="digi_cart_amount" style="text-align:right;"><?php echo DigiComSiteHelperDigiCom::format_price2($tax['promo'], $tax['currency'], true, $configs); ?></li>
						<?php endif; ?>
					</ul>
				</td>
				<?php } else { ?>
					<td>&nbsp;</td>
				<?php } ?>
			</tr>
				
			<tr valign="top">
				<td colspan="<?php echo $nr_columns - 1; ?>" >
					<div class="input-append">
						<input type="text" id="promocode" name="promocode" size="15" value="<?php echo $this->promocode; ?>" />
						<button type="submit" class="btn" onclick="document.getElementById('task').value='cart.updateCart'; document.getElementById('type_button').value='recalculate';"><i class="ico-gift"></i> <?php echo JText::_("COM_DIGICOM_CART_PROMOCODE_APPLY"); ?></button>
					</div>
					<?php if(!empty($this->promoerror) or ($tax['promo'] <= 0 && $this->promocode != '')): ?>
						<div class="digi_error alert alert-warning">
							<?php echo $this->promoerror; ?>
							<?php if($tax['promo'] <= 0 && $this->promocode != ''):?>
								<?php echo JText::_('DIGI_PROMO_NO_ACCESS');?>
							<?php endif;?>
						</div>
					<?php endif;?>
				</td>
				<td nowrap="nowrap" style="text-align: center;">
					<ul style="margin: 0; padding: 0;list-style-type: none;">
						<li class="digi_cart_total" style="font-weight: bold;font-size: 18px;text-align:right;"><?php echo JText::_("COM_DIGICOM_TOTAL");?></li>
					</ul>
				</td>
				<td nowrap="nowrap" style="text-align: center;">
					<ul style="margin: 0; padding: 0;list-style-type: none;">
						<li class="digi_cart_amount" id="cart_total" style="font-weight: bold;font-size: 18px;text-align:right;"><?php echo DigiComSiteHelperDigiCom::format_price2($tax['taxed'], $tax['currency'], true, $configs); ?></li>
					</ul>
				</td>
			</tr>
		</table>

		<?php if($configs->get('askterms',0) == '1'):?>
			<div class="accept-terms">
				<input type="checkbox" name="agreeterms" id="agreeterms" style="margin-top: 0;"/><?php
				$db = JFactory::getDBO();
				$sql = "select `alias`, `catid`, `introtext`
								from #__content
								where id=".intval($configs->get('termsid'));
				$db->setQuery($sql);
				$db->query();
				$result = $db->loadAssocList();
				$terms_content = $result["0"]["introtext"];
				$alias = $result["0"]["alias"];
				$catid = $result["0"]["catid"]; ?>
				<a href="javascript:;" onclick="jQuery('#myModalTerms').modal('show');"><?php echo JText::_("COM_DIGICOM_CART_AGREE_TERMS"); ?></a>
			</div>
		<?php endif;?>

		<?php 
		if($configs->get('showccont',0) == 1){ ?>
			<div id="digicomcartcontinue" class="row-fluid continue-shopping">
				<div class="span8">
					<?php
					echo JText::_("DIGI_PAYMENT_METHOD").": ".$this->plugins;
					$onclick = "document.getElementById('returnpage').value='checkout'; document.getElementById('type_button').value='checkout';";
					?>
					<input type="submit" name="Submit" class="btn btn-warning" value="<?php echo JText::_("COM_DIGICOM_CHECKOUT");?>" onClick="<?php echo $onclick; ?>">
				</div>
				<div class="span4" <?php if ($discount!=1) echo 'style="display:none"'?>>&nbsp;</div>
			</div>
		<?php } else { ?>
			<div id="digicomcartcontinue" class="row-fluid continue-shopping">
				<div class="span8" style="margin-bottom:10px;">
					<!--<a href="<?php echo $cat_url; ?>" class="btn"><i class="icon-cart"></i> <?php echo JText::_("DSCONTINUESHOPING")?></a>-->
				</div>
				<div class="span4" style="margin-top: -34px;margin-bottom: 10px;">
				<p><strong><?php echo JText::_('COM_DIGICOM_PAYMENT_METHOD'); ?></strong></p>
					<?php
					$button_value = "COM_DIGICOM_CHECKOUT";
					$onclick = "if(jQuery('#processor').val() == ''){ ShowPaymentAlert(); return false; }";
					$onclick.= "jQuery('#returnpage').val('checkout'); jQuery('#type_button').val('checkout');";

					if($user->id == 0 || $this->customer->_customer->country == "")
					{
						$button_value = "COM_DIGICOM_CONTINUE";
					}

					if($configs->get('askterms',0) == '1')
					{
						$onclick.= "if(ShowTermsAlert()) {" . $onclick . " jQuery('#cart_form').submit(); }else{ return false; }";
					}
					else
					{
						$onclick.= "jQuery('#cart_form').submit();";
					} ?>

					<?php echo DigiComSiteHelperDigicom::getPaymentPlugins($configs); ?>
					
					<div id="html-container"></div>
					<button type="button" class="btn btn-warning" style="float:right;margin-top:10px;" onclick="<?php echo $onclick; ?> "><?php echo JText::_($button_value);?> <i class="ico-ok-sign"></i></button>
				</div>
			</div>
		<?php } ?>


			<input name="view" type="hidden" id="view" value="cart">
			<input name="task" type="hidden" id="task" value="cart.checkout">
			<input name="returnpage" type="hidden" id="returnpage" value="">
			<input name="type_button" type="hidden" id="type_button" value="">
			<input name="Itemid" type="hidden" value="<?php echo $Itemid; ?>">
		</form>
	</div>
	<?php if(isset($tax) && $tax['promo_error'] != ''):?>
		<div id="digicart_login" style="width:350px;left:50%;top:30%;position:fixed;z-index:1000;background:#eee;margin-left:-175px;">
			<div id="cart_header" style="background-color: rgb(204, 204, 204);">
				<table width="100%" style="font-size:12px;">
					<tbody>
					<tr>
						<td width="80%" align="left">
							&nbsp;<?php echo JText::_("Login");?>
						</td>
						<td align="right">
							<a onclick="javascript:closePopupLogin('digicart_login'); return false;" class="close_btn" href="#">&nbsp;</a>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
			<div id="cart_body">
				<?php if(!empty($tax['promo_error'])): ?>
					<div class="digi_error alert alert-warning"><?php echo $tax['promo_error']; ?></div>
				<?php endif; ?>
				<form id="dslogin" name="dslogin" method="post" action="index.php">
					<table width="100%" id="digilistitems" style="font-size:12px;">
						<tbody>
						<tr style="padding-bottom:3px;">
							<td style="padding-left:5px;">
								<?php echo JText::_("Username");?>
							</td>
							<td style="padding-left:5px; width:150px; text-align:left;" class="digicom_product_name">
								<input type="text" id="dsusername" name="username" style="width:150px;" />
							</td>
						</tr>
						 <tr style="padding-bottom:3px;">
							<td style="padding-left:5px;">
								<?php echo JText::_("Password");?>
							</td>
							<td style="padding-left:5px; width:150px; text-align:left;" class="digicom_product_name">
								<input type="password" id="dspassword" name="password" style="width:150px;" />
							</td>
						</tr>
						<tr>
							<td></td>
							<td>
								<a href="<?php echo JRoute::_('index.php?option=com_users&view=reset'); ?>">
									<?php echo JText::_('LOGIN_FORGOT_YOUR_PASSWORD'); ?></a>
								<br />
								<a href="<?php echo JRoute::_('index.php?option=com_users&view=remind'); ?>">
									<?php echo JText::_('LOGIN_FORGOT_YOUR_USERNAME'); ?></a>
							</td>
						</tr>
						</tbody>
					</table>

					<input type="hidden" name="option" value="com_users"/>
					<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt('Itemid', 0);?>"/>
					<input type="hidden" name="task" value="user.login"/>
					<input type="hidden" name="return"
						   value="<?php echo base64_encode('index.php?option=com_digicom&view=cart&task=cart.showCart&Itemid=' . JRequest::getInt('Itemid', 0)); ?>"/>
					<?php echo JHTML::_('form.token'); ?>
				</form>
			</div>
			<div id="cart_futter" style="background-color: rgb(204, 204, 204);">
				<table width="100%">
					<tbody>
					<tr>
						<td width="100%">
							<table width="100%">
								<tbody>
								<tr>
									<td width="60%" align="left"><input type="button" class="btn" onclick="javascript:closePopupLogin('digicart_login'); return false;" value=" Cancel " name="Submit1" style="padding:0px !important;"></td>
									<td width="40%" align="right"><input type="button" class="btn btn-warning" onclick="document.dslogin.submit();" value="Login" name="Submit"></td>
								</tr>
								</tbody>
							</table>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif;?>

	<div id="myModal" class="modal" style="display:none;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3 id="myModalLabel" style="line-height: 1;">...</h3>
		</div>
		<div id="myModalBody" class="modal-body">

		</div>
		<div id="myModalFooter" class="modal-footer">
			<button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_("COM_DIGICOM_CLOSE");?></button>
		</div>
	</div>

	<?php if($configs->get('askterms',0) == '1'):?>
	<div id="myModalTerms" class="modal" style="display:none;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 style="line-height: 1;"><?php echo JText::_("COM_DIGICOM_TERMS");?></h3>
		</div>
		<div class="modal-body">
			<?php echo $terms_content;?>
		</div>
		<div class="modal-footer">
			<button class="action-agree btn btn-success" data-dismiss="modal" aria-hidden="true"><?php echo JText::_("COM_DIGICOM_CART_AGREE_TERMS_BUTTON");?></button>
			<button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_("COM_DIGICOM_CLOSE");?></button>
		</div>
	</div>
	<?php endif;?>

	<script>
		jQuery('.action-agree').click(function() {
		    jQuery('input[name="agreeterms"]').attr('checked', 'checked');
		});

		<?php
		if ($agreeterms != '')
		{
			echo 'jQuery("#agreeterms").attr("checked","checked");';
		}
		if ($processor != '')
		{
			echo 'jQuery("#processor").val("' . $processor . '");';
		}
		?>
		function ShowTermsAlert()
		{
			if (document.cart_form.agreeterms.checked != true)
			{
				jQuery('#myModalLabel').html("<?php echo JText::_("COM_DIGICOM_WARNING");?>");
				jQuery('#myModalBody').html("<p><?php echo JText::_("COM_DIGICOM_CART_ACCEPT_TERMS_CONDITIONS_REQUIRED_NOTICE");?></p>");
				jQuery('#myModal').modal('show');
				return false;
			}
			else
			{
				return true;
			}
		}
		function ShowPaymentAlert()
		{
			jQuery('#myModalLabel').html("<?php echo JText::_("COM_DIGICOM_WARNING");?>");
			jQuery('#myModalBody').html("<p><?php echo JText::_("COM_DIGICOM_CART_PAYMENT_METHOD_REQUIRED_NOTICE");?></p>");
			jQuery('#myModal').modal('show');
		}

		function RemoveFromCart(CARTID)
		{
			window.location = "<?php echo JURI::root();?>index.php?option=com_digicom&view=cart&task=cart.deleteFromCart&cartid="+CARTID+"<?php echo (isset($item->discount1)?('&discount=1&noupdate='.(isset($item->noupdate)?$item->noupdate:'').'&qty='.$item->quantity ):"" )."&Itemid=".$Itemid;?>&processor="+jQuery("#processor").val()+"&agreeterms="+jQuery("#agreeterms").val();
		}

		if(jQuery(window).width() > jQuery("#digicomcarttable").width() && jQuery(window).width() < 550)
		{
			jQuery(".digicom table select").css("width", (jQuery("#digicomcarttable").width()-30)+"px");
		}
	</script>
<?php endif; ?>

</div>
