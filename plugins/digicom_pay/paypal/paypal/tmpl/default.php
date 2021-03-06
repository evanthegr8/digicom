<?php
/**
 * @package		DigiCom
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die; 
?>
<div class="akeeba-bootstrap">
	<form action="<?php echo $vars->action_url ?>" class="form-horizontal" method="post">
		<input type="hidden" name="business" value="<?php echo $vars->business ?>" />
		<input type="hidden" name="custom" value="<?php echo $vars->order_id ?>" />
		<input type="hidden" name="item_name" value="<?php echo $vars->item_name ?>" />
		<input type="hidden" name="return" value="<?php echo $vars->url ?>" />
		<input type="hidden" name="cancel_return" value="<?php echo $vars->cancel_return ?>" />
		<input type="hidden" name="notify_url" value="<?php echo $vars->notify_url ?>" />
		<input type="hidden" name="currency_code" value="<?php echo $vars->currency_code ?>" />
		<input type="hidden" name="no_note" value="1" />
		<input type="hidden" name="rm" value="2" />
		<input type="hidden" name="amount" value="<?php echo $vars->amount ?>" />
		<input type="hidden" name="cmd" value="_xclick" />
		<div class="form-actions">
			<input type="submit" class="btn btn-success btn-large" src="https://www.paypal.com/en_US/i/btn/x-click-but02.gif" border="0"  value="<?php echo JText::_('SUBMIT'); ?>" alt="Make payments with PayPal - it's fast, free and secure!" />
		</div>
	</form>
</div>
