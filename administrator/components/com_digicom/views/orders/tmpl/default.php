<?php
/**
 * @package		DigiCom
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die;

JHtml::_('behavior.multiselect');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

$invisible = 'style="display:none;"';

$k = 0;
$n = count( $this->orders );
$configs = JComponentHelper::getComponent('com_digicom')->params;
$f = $configs->get('time_format','DD-MM-YYYY');
$f = str_replace( "-", "-%", $f );
$f = "%" . $f;
?>
<script language="javascript" type="text/javascript">
Joomla.submitbutton = function (pressbutton) {
	var form = document.adminForm;
	if (pressbutton == 'remove')
	{
		if (confirm("<?php echo JText::_("CONFIRM_ORDER_DELETE");?>"))
		{
			Joomla.submitform(pressbutton);
		}
		return;
	}

	Joomla.submitform(pressbutton);
}
</script>

<?php if (!empty( $this->sidebar)) : ?>
<div id="j-sidebar-container" class="">
	<?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="">
<?php else : ?>
<div id="j-main-container" class="">
<?php endif;?>

	<div class="dg-alert dg-alert-with-icon">
		<span class="icon-support"></span><?php echo JText::_("COM_DIGICOM_ORDERS_HEADER_NOTICE"); ?>
	</div>
	<form id="adminForm" action="<?php echo JRoute::_('index.php?option=com_digicom&view=orders'); ?>" method="post" name="adminForm" autocomplete="off" class="form-validate form-horizontal">
		<div class="js-stools">
			<div class="clearfix">
				<div class="btn-wrapper input-append">
					<input type="text" id="filter_search" name="keyword" placeholder="<?php echo JText::_('DSKEYWORD'); ?>" value="<?php echo (strlen( trim( $this->keyword ) ) > 0 ? $this->keyword : ""); ?>" class="input-medium" />		
					<button type="submit" class="btn hasTooltip" title="" data-original-title="Search">
						<i class="icon-search"></i>
					</button>
					<button type="button" class="btn hasTooltip js-stools-btn-clear" onclick="document.id('filter_search').value='';this.form.submit();">
						<i class="icon-remove"></i>	
					</button>
				</div>
				<div class="btn-wrapper input-append input-prepend pull-right">
					<label class="add-on"><?php echo JText::_( "DSFROM" ); ?>:</label>
					<?php echo JHTML::_( "calendar", $this->startdate > 0 ? date( $configs->get('time_format','DD-MM-YYYY'), $this->startdate ) : "", 'startdate', 'startdate', $f, array('class'=>'input-medium'), array('class'=>'span2'), array('class'=>'span2')); ?>&nbsp;
				
					<label class="add-on"><?php echo JText::_( "DSTO" ); ?>:</label>
					<?php echo JHTML::_( "calendar", $this->enddate > 0 ? date( $configs->get('time_format','DD-MM-YYYY'), $this->enddate ) : "", 'enddate', 'enddate', $f , array('class'=>'input-medium')); ?>

					<input type="submit" name="go" value="<?php echo JText::_( "DSGO" ); ?>" class="btn" />
					<button type="button" class="btn hasTooltip js-stools-btn-clear" onclick="document.id('startdate').value='';document.id('enddate').value='';this.form.submit();">
						<i class="icon-remove"></i>	
					</button>
				</div>

			</div>
		</div>
		<br>


		
		<table class="adminlist table table-striped">
			<thead>
				<tr>
					<th width="5">
						<?php echo JHtml::_('grid.checkall'); ?>
					</th>
					<th width="20">
						<?php echo JText::_( 'JGRID_HEADING_ID' ); ?>
					</th>

					<th>
						<?php echo JText::_( 'COM_DIGICOM_DATE' ); ?>
					</th>
					<th  <?php if ( $configs->get('showolics',0) == 0 ) echo $invisible; ?>>
						<?php echo JText::_( 'VIEWORDERSNOL' ); ?>
					</th>
					<th>
						<?php echo JText::_( 'COM_DIGICOM_PRICE' ); ?>
					</th>
					<th>
						<?php echo JText::_( 'COM_DIGICOM_AMOUNT_PAID' ); ?>
					</th>
					<th>
						<?php echo JText::_( 'COM_DIGICOM_USER_NAME' ); ?>
					</th>
					<th>
						<?php echo JText::_( 'COM_DIGICOM_CUSTOMER' ); ?>
					</th>
					<th>
						<?php echo JText::_( 'JSTATUS' ); ?>
					</th>
					<th>
						<?php echo JText::_( 'COM_DIGICOM_CUSTOMER_PAYMENT_METHOD' ); ?>
					</th>
				</tr>
			</thead>

			<tbody>
				<?php if($n > 0):  ?>
				<?php
				$z = 0;
				for ( $i = 0; $i < $n; $i++ ):
					++$z;
					$order =  $this->orders[$i];

					$id = $order->id;
					$checked = JHTML::_( 'grid.id', $i, $id );
					$olink = JRoute::_( "index.php?option=com_digicom&view=order&task=order.edit&id=" . $id );
					$customerlink = JRoute::_( "index.php?option=com_digicom&view=customer&task=customer.edit&id=" . $order->userid );
					$order->published = 1;
					$published = JHTML::_( 'grid.published', $order, $i );
					$orderstatuslink = JRoute::_( "index.php?option=com_digicom&view=orders&task=orders.cycleStatus&id=" . $id );
					$userlink = "index.php?option=com_users&view=users&filter_search=".$order->username;

				?>
					<tr class="row<?php echo $k; ?>">
						<td align="center">
							<?php echo $checked; ?>
						</td>
						<td align="center">
							<a href="<?php echo $olink; ?>"><?php echo $id; ?></a>
						</td>
						<td align="center">
							<?php echo date( $configs->get('time_format','DD-MM-YYYY'), $order->order_date ); ?>
						</td>
						<td align="center" <?php if ( $configs->get('showolics',0) == 0 )
								echo $invisible; ?>>
							<a href="<?php echo $link; ?>" ><?php echo $order->licensenum; ?></a>
						</td>
						<td align="center">
							<?php 
								echo DigiComHelperDigiCom::format_price($order->amount, $configs->get('currency','USD'), true, $configs); 
							?>
						</td>
						<td align="center">
							<?php 
								$refunds = DigiComHelperDigiCom::getRefunds($order->id);
								$chargebacks = DigiComHelperDigiCom::getChargebacks($order->id);
								$order->amount_paid = $order->amount_paid - $refunds - $chargebacks;
								echo DigiComHelperDigiCom::format_price($order->amount_paid, $configs->get('currency','USD'), true, $configs); 
							?>
						</td>
						<td align="center">
							<?php echo ($order->username); ?>
						</td>
						<td align="center">
							<a href="<?php echo $customerlink; ?>" ><?php echo ($order->firstname . " " . $order->lastname); ?></a>
						</td>
						<td align="center">
							<?php
								$class = 'badge badge-success';
								if($order->status == "Pending"){
									$class = 'badge badge-warning';
								}
							?>
							<span class="<?php echo $class; ?>">
								<?php echo (trim( $order->status ) != "in_progres" ? $order->status : "Active"); ?>
							</span>
							<a href="<?php echo $orderstatuslink; ?>" title="<?php echo JText::_('COM_DIGICOM_ORDER_STATUS_CHANGE_FROM_'.strtoupper($order->status));?>" class="hasTooltip">
								<i class="icon-refresh"></i>
							</a>
							
						</td>
						<td align="center">
							<?php echo $order->processor; ?>
						</td>
						
					</tr>
					<?php
					$k = 1 - $k;
				endfor;
					?>
				<?php else: ?>
					<tr>
						<td colspan="9">
							<?php echo  JText::_('COM_DIGICOM_ORDERS_NOTICE_NO_ORDER_FOUND'); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="9">
						<?php
							$total_pag = $this->pagination->get("pages.total", "0");
							$pag_start = $this->pagination->get("pages.start", "1");
							if($total_pag > ($pag_start + 9)){
								$this->pagination->set("pages.stop", ($pag_start + 9));
							}
							else{
								$this->pagination->set("pages.stop", $total_pag);
							}
							echo $this->pagination->getListFooter();
						?>
					</td>
				</tr>
			</tfoot>
		</table>

		<input type="hidden" name="option" value="com_digicom" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="view" value="orders" />
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>

<div class="dg-footer">
	<?php echo JText::_('COM_DIGICOM_CREDITS'); ?>
</div>
