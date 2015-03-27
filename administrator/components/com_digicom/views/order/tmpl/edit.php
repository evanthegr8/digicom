<?php
/**
 * @package		DigiCom
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die;

$document = JFactory::getDocument();

$k = 0;
$n = count ($this->item->products);
//Log::debug($n);
$configs = $this->configs;
$order = $this->item;
$refunds = DigiComModelOrder::getRefunds($order->id);
$chargebacks = DigiComModelOrder::getChargebacks($order->id);
$deleted = DigiComModelOrder::getDeleted($order->id);
$date = date( $configs->get('time_format','d M Y'), $order->order_date);

	?>

<?php if (!empty( $this->sidebar)) : ?>
<div id="j-sidebar-container" class="">
	<?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="">
<?php else : ?>
<div id="j-main-container" class="">
<?php endif;?>
<form id="adminForm" action="index.php" name="adminForm" method="post">

<div id="contentpane" >
<p class="alert alert-info">
	<?php echo JText::sprintf('COM_DIGICOM_ORDER_DETAILS_HEADER_NOTICE',$order->id,$date,$order->status); ?>
</p>
	
<h2><?php echo JText::_('COM_DIGICOM_ORDER_DETAILS_HEADER_TITLE'); ?></h2>
	<table class="adminlist table table-striped">
		<thead>
			<tr>
				<th class="sectiontableheader">#</th>
				<th class="sectiontableheader"  >
					<?php echo JText::_('COM_DIGICOM_PRODUCT');?>
				</th>
				
				<th class="sectiontableheader"  >
					<?php echo JText::_('COM_DIGICOM_ORDER_DETAILS_TOTAL_PRODUCT');?>
				</th>

				<th class="sectiontableheader"  >
					<?php echo JText::_('COM_DIGICOM_PRICE');?>
				</th>

			</tr>
		</thead>

			<tbody>

			<?php 
			$oll_courses_total = 0;
			//for ($i = 0; $i < $n; $i++):
			$i = 0;
			foreach ($order->products as $key=>$prod):
				if(!isset($prod->id)) break;
				//print_r($prod);die;
				$id = $order->id;
				
				if (!isset($prod->currency)) {
					$prod->currency = $configs->get('currency','USD');
				}
				
				$licenseid = $prod->id;
				//print_r($prod);die;
				$refund = DigiComModelOrder::getRefunds($order->id, $prod->id);
				$chargeback = DigiComModelOrder::getChargebacks($order->id, $prod->id);
				$cancelled = DigiComModelOrder::isLicenseDeleted($prod->id);?>
				<tr class="row<?php echo $k;?> sectiontableentry<?php echo ($i%2 + 1);?>">
					<td style="<?php echo $cancelled == 3 ? 'text-decoration: line-through;' : '';?>"><?php echo $i+1; ?></td>
					<td style="<?php echo $cancelled == 3 ? 'text-decoration: line-through;' : '';?>">
						<?php echo $prod->name;?>
					</td>
					<td style="<?php echo $cancelled == 3 ? 'text-decoration: line-through;' : '';?>">
						<?php echo $prod->quantity;?>
					</td>
					<td style="<?php echo $cancelled == 3 ? 'text-decoration: line-through;' : '';?>"><?php
						$price = $prod->price - $refund - $chargeback;
						echo DigiComHelperDigiCom::format_price($prod->price, $prod->currency, true, $configs);
						$oll_courses_total += $price;
						if ($refund > 0)
						{
							echo '&nbsp;<span style="color:#ff0000;"><em>('.JText::_("LICENSE_REFUND")." - ".DigiComHelperDigiCom::format_price($refund, $prod->currency, true, $configs).')</em></span>';
						}
						if ($chargeback > 0)
						{
							echo '&nbsp;<span style="color:#ff0000;"><em>('.JText::_("LICENSE_CHARGEBACK")." - ".DigiComHelperDigiCom::format_price($chargeback, $prod->currency, true, $configs).')</em></span>';
						} ?>
					</td>
					
				</tr><?php
				$k = 1 - $k;
				$i++;
			endforeach; ?>

			<tr style="border-style:none;"><td style="border-style:none;" colspan="4"><hr /></td></tr>
			<tr><td colspan="2" ></td>
				<td style="font-weight:bold"><?php echo JText::_("COM_DIGICOM_SUBTOTAL");?></td>
				<td>
					<?php 
						echo DigiComHelperDigiCom::format_price($oll_courses_total, $order->currency, true, $configs);
					?>
				</td>
			</tr>
			
			<tr><td colspan="2"></td>
				<td style="font-weight:bold"><?php echo JText::_("COM_DIGICOM_DISCOUNT");?> <strong><?php echo $order->promocode; ?></strong></td>
				<td><?php echo DigiComHelperDigiCom::format_price($order->promocodediscount, $order->currency, true, $configs);?></td></tr>
			<?php if ($refunds > 0):?>
			<tr>
				<td colspan="2"></td>
				<td style="font-weight:bold;color:#ff0000;"><?php echo JText::_("LICENSE_REFUNDS");?></td>
				<td style="color:#ff0000;"><?php echo DigiComHelperDigiCom::format_price($refunds, $order->currency, true, $configs); ?></td>
			</tr>
			<?php endif;?>
			<?php if ($chargebacks > 0):?>
			<tr>
				<td colspan="2"></td>
				<td style="font-weight:bold;color:#ff0000;"><?php echo JText::_("LICENSE_CHARGEBACKS");?></td>
				<td style="color:#ff0000;"><?php echo DigiComHelperDigiCom::format_price($chargebacks, $order->currency, true, $configs); ?></td>
			</tr>
			<?php endif;?>
			<?php if ($deleted > 0):?>
			<tr>
				<td colspan="2"></td>
				<td style="font-weight:bold;color:#ff0000;"><?php echo JText::_("DELETED_LICENSES");?></td>
				<td style="color:#ff0000;"><?php echo DigiComHelperDigiCom::format_price($deleted, $order->currency, true, $configs); ?></td>
			</tr>
			<?php endif;?>
			<tr><td colspan="2"></td>
					<td style="font-weight:bold"><?php echo JText::_("COM_DIGICOM_TOTAL");?></td>
				<td>
					<?php
						$value = $order->amount_paid;
						if($value == "-1"){
							$value = $order->amount;
						}
						$value = $value - $refunds - $chargebacks;
						echo DigiComHelperDigiCom::format_price($value, $order->currency, true, $configs);
					?>
				</td>
			</tr>
			</tbody>


		</table>

	</div>

	<input type="hidden" name="option" value="com_digicom" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="view" value="order" />
	<?php echo JHtml::_('form.token'); ?>
</form>
</div>

<div class="dg-footer">
	<?php echo JText::_('COM_DIGICOM_CREDITS'); ?>
</div>
