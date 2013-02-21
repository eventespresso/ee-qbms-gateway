<?php

function event_espresso_qbms_payment_settings() {
	global $espresso_premium, $active_gateways;
	if (!$espresso_premium)
		return;
	if (isset($_POST['update_qbms'])) {
		$qbms_settings['qbms_description'] = $_POST['qbms_description'];
		$qbms_settings['qbms_title'] = $_POST['qbms_title'];
		$qbms_settings['qbms_app_login'] = $_POST['qbms_app_login'];
		$qbms_settings['qbms_conn_ticket'] = $_POST['qbms_conn_ticket'];
		$qbms_settings['qbms_app_id'] = $_POST['qbms_app_id'];
		$qbms_settings['qbms_sandbox'] = $_POST['qbms_sandbox'];
		$qbms_settings['qbms_liveurl'] = 'https://webmerchantaccount.quickbooks.com/j/AppGateway';
		$qbms_settings['qbms_testurl'] = 'https://webmerchantaccount.ptc.quickbooks.com/j/AppGateway';
		$qbms_settings['qbms_logpath'] = $_POST['qbms_logpath'];
		$qbms_settings['qbms_log'] = $_POST['qbms_log'];
		$qbms_settings['qbms_force_ssl_return'] = $_POST['qbms_force_ssl_return'];
		update_option('event_espresso_qbms_settings', $qbms_settings);
		echo '<div id="message" class="updated fade"><p><strong>' . __('qbms settings saved.', 'event_espresso') . '</strong></p></div>';
	}
	$qbms_settings = get_option('event_espresso_qbms_settings');
	if (empty($qbms_settings)) {
		if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/qbms/qbms-logo.png")) {
			$button_url = EVENT_ESPRESSO_GATEWAY_URL . "/qbms/qbms-logo.png";
		} else {
			$button_url = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/qbms/qbms-logo.png";
		}
		$qbms_settings['qbms_title'] = '';
		$qbms_settings['qbms_app_login'] = '';
		$qbms_settings['qbms_conn_ticket'] = '';
		$qbms_settings['qbms_app_id'] = '';
		$qbms_settings['qbms_sandbox'] = '';
		$qbms_settings['qbms_liveurl'] = 'https://webmerchantaccount.quickbooks.com/j/AppGateway';
		$qbms_settings['qbms_testurl'] = 'https://webmerchantaccount.ptc.quickbooks.com/j/AppGateway';
		$default_logdir = wp_upload_dir();
		$qbms_settings['qbms_logpath'] = $default_logdir['basedir'].'/espresso/gateways/qbms/';
		$qbms_settings['qbms_log'] = 'off';
		$qbms_settings['qbms_force_ssl_return'] = 'FALSE';
		if (add_option('event_espresso_qbms_settings', $qbms_settings, '', 'no') == false) {
			update_option('event_espresso_qbms_settings', $qbms_settings);
		}
	}

	//Open or close the postbox div
	if (empty($_REQUEST['deactivate_qbms'])
		&& (!empty($_REQUEST['activate_qbms'])
			|| array_key_exists('qbms', $active_gateways))) {
		$postbox_style = '';
} else {
	$postbox_style = 'closed';
}
?>

<div class="metabox-holder">
	<div class="postbox <?php echo $postbox_style; ?>">
		<div title="Click to toggle" class="handlediv"><br /></div>
		<h3 class="hndle">
			<?php _e('qbms Settings', 'event_espresso'); ?>
		</h3>
		<div class="inside">
			<div class="padding">
				<?php
				if (!empty($_REQUEST['activate_qbms'])) {
					$active_gateways['qbms'] = dirname(__FILE__);
					update_option('event_espresso_active_gateways', $active_gateways);
				}
				if (!empty($_REQUEST['deactivate_qbms'])) {
					unset($active_gateways['qbms']);
					update_option('event_espresso_active_gateways', $active_gateways);
				}
				echo '<ul>';
				if (array_key_exists('qbms', $active_gateways)) {
					echo '<li id="deactivate_qbms" style="width:30%;" onclick="location.href=\'' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=payment_gateways&deactivate_qbms=true\';" class="red_alert pointer"><strong>' . __('Deactivate qbms IPN?', 'event_espresso') . '</strong></li>';
					event_espresso_display_qbms_settings();
				} else {
					echo '<li id="activate_qbms" style="width:30%;" onclick="location.href=\'' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=payment_gateways&activate_qbms=true\';" class="green_alert pointer"><strong>' . __('Activate qbms IPN?', 'event_espresso') . '</strong></li>';
				}
				echo '</ul>';
				?>
			</div>
		</div>
	</div>
</div>
<?php
}

//qbms Settings Form
function event_espresso_display_qbms_settings() {
	$qbms_settings = get_option('event_espresso_qbms_settings');
	$default_logdir = wp_upload_dir();
	$default_logdir = $default_logdir['basedir'].'/espresso/gateways/qbms/';
	?>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
		<table width="99%" border="0" cellspacing="5" cellpadding="5">

			<tr>
				<td class="titledesc"><a href="#" tip="<?php _e('This controls the title which the user sees during checkout.','event_espresso') ?>" class="tips" tabindex="99"></a><?php _e('Method Title', 'event_espresso') ?>:</td>
				<td class="forminp">
					<input class="input-text" type="text" name="qbms_title" id="qbms_title" style="min-width:50px;" value="<?php if ($value = $qbms_settings['qbms_title']) echo $value; else echo 'QBMS'; ?>" />
				</td>
			</tr>
			<tr>
				<td class="titledesc"><a href="#" tip="<?php _e('This controls the description which the user sees during checkout.','event_espresso') ?>" class="tips" tabindex="99"></a><?php _e('Description', 'event_espresso') ?>:</td>
				<td class="forminp">
					<input class="input-text wide-input" type="text" name="qbms_description" id="qbms_description" style="min-width:50px;" value="<?php if ($value = $qbms_settings['qbms_description']) echo $value; ?>" />
				</td>
			</tr>
			<tr>
				<td class="titledesc"><a href="#" tip="<?php _e('Please enter your QBMS Application Login; this is needed in order to take payment!','event_espresso') ?>" class="tips" tabindex="99"></a><?php _e('QBMS Application Login', 'event_espresso') ?>:</td>
				<td class="forminp">
					<input class="input-text" type="text" name="qbms_app_login" id="qbms_app_login" style="min-width:50px;" value="<?php if ($value = $qbms_settings['qbms_app_login']) echo $value; ?>" />
				</td>
			</tr>
			<tr>
				<td class="titledesc"><a href="#" tip="<?php _e('Please enter your QBMS App ID; this is needed in order to take payment!','event_espresso') ?>" class="tips" tabindex="99"></a><?php _e('QBMS App ID', 'event_espresso') ?>:</td>
				<td class="forminp">
					<input class="input-text" type="text" name="qbms_app_id" id="qbms_app_id" style="min-width:50px;" value="<?php if ($value = $qbms_settings['qbms_app_id']) echo $value; ?>" />
				</td>
			</tr>
			<tr>
				<td class="titledesc"><a href="#" tip="<?php _e('Please enter your QBMS Connection Ticket; this is needed in order to take payment!','event_espresso') ?>" class="tips" tabindex="99"></a><?php _e('QBMS Connection Ticket', 'event_espresso') ?>:</td>
				<td class="forminp">
					<input class="input-text" type="text" name="qbms_conn_ticket" id="qbms_conn_ticket" style="min-width:50px;" value="<?php if ($value = $qbms_settings['qbms_conn_ticket']) echo $value; ?>" />
				</td>
			</tr>
			<tr>
				<td class="titledesc"><a href="#" tip="<?php _e('Please enter your QBMS Sandbox; this is needed in order to take payment!','event_espresso') ?>" class="tips" tabindex="99"></a><?php _e('QBMS Sandbox', 'event_espresso') ?>:</td>
				<td class="forminp">
					<select name="qbms_sandbox" id="qbms_sandbox" style="min-width:100px;">
						<option value="yes" <?php if ($qbms_settings['qbms_sandbox'] == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'event_espresso'); ?></option>
						<option value="no" <?php if ($qbms_settings['qbms_sandbox'] == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'event_espresso'); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="titledesc"><a href="#" tip="<?php _e('Path to debug log','event_espresso') ?>" class="tips" tabindex="99"></a><?php _e('Log Path', 'event_espresso') ?>(Default: <?php echo $default_logdir; ?>) :</td>
				<td class="forminp">
					<input class="input-text wide-input" type="text" name="qbms_logpath" id="qbms_logpath" style="min-width:50px;" value="<?php if ( $value = $qbms_settings['qbms_logpath'] ) { echo $value; } ?>" />
				</td>
			</tr>
			<tr>
				<td class="titledesc"><a href="#" tip="<?php _e('Loging','event_espresso') ?>" class="tips" tabindex="99"></a><?php _e('Loging', 'event_espresso') ?>:</td>
				<td class="forminp">
					<select name="qbms_log" id="qbms_log" style="min-width:100px;">
						<option value="off" <?php if ($qbms_settings['qbms_log'] == 'off') echo 'selected="selected"'; ?>> <?php _e('Off', 'event_espresso'); ?></option>
						<option value="e_only" <?php if ($qbms_settings['qbms_log'] == 'e_only') echo 'selected="selected"'; ?>><?php _e('Errors Only', 'event_espresso'); ?></option>
						<option value="all" <?php if ($qbms_settings['qbms_log'] == 'all') echo 'selected="selected"'; ?>><?php _e('All', 'event_espresso'); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="titledesc"><a href="#" tip="<?php _e('Force SSL Return','event_espresso') ?>" class="tips" tabindex="99"></a><?php _e('Force SSL Return', 'event_espresso') ?>:</td>
				<td class="forminp">
					<select name="qbms_force_ssl_return" id="qbms_force_ssl_return" style="min-width:100px;">
						<option value="TRUE" <?php if ($qbms_settings['qbms_force_ssl_return'] == 'TRUE') echo 'selected="selected"'; ?>> <?php _e('YES', 'event_espresso'); ?></option>
						<option value="FALSE" <?php if ($qbms_settings['qbms_force_ssl_return'] == 'FALSE') echo 'selected="selected"'; ?>><?php _e('NO', 'event_espresso'); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<p>
			<input type="hidden" name="update_qbms" value="update_qbms">
			<input class="button-primary" type="submit" name="Submit" value="<?php _e('Update QBMS Settings', 'event_espresso') ?>" id="save_qbms_settings" />
		</p>
	</form>
	<?php
}

add_action('action_hook_espresso_display_gateway_settings', 'event_espresso_qbms_payment_settings');
