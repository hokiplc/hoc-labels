<?php
/**
 * Uninstall handler for House of Coffee Brother Labels.
 *
 * Removes plugin options on uninstall. Does not touch order data.
 *
 * @package HOC\BrotherLabels
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'hoc_brother_labels_settings' );
