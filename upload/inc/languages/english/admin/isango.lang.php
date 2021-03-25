<?php
/**
 * @Package: Isango : Admic CP Languages
 * @description: Simple Social Login / Register for MyBB Using oAuth 2.0
 * @version: 2.0.0
 * @author: MyBB Group Developers (effone)
 * @authorsite: https://mybb.group
 * @update: 16-Jan-2021
 */

$l['isango_title'] = 'Isango';
$l['isango_description'] = 'Simple Social Login / Register Using oAuth 2.0';
$l['isango_active_title'] = 'Enable Isango';
$l['isango_active_desc'] = 'Use this switch to enable / disable the functionality of Isango without actually disabling the required background processes.';
$l['isango_gateway_enabled_title'] = 'Enable Logging in with {1}?';
$l['isango_gateway_enabled_desc'] = 'Allow users to Login / Register using {1} authentication API.';
$l['isango_gateway_key_title'] = '{1} Client {2}:';
$l['isango_gateway_key_desc'] = 'Enter your App {2} obtained from {1}';
$l['isango_default_gid_title'] = 'Default Usergroup';
$l['isango_default_gid_desc'] = 'Define a usergroup ID where users registered by Isango will be placed. (Fallback group is "Registered")';
$l['isango_allow_register_title'] = 'Allow Registration';
$l['isango_allow_register_desc'] = 'Allowing this will auto create new account if the data does not match with existing user.';
$l['isango_notify_registered_title'] = 'Notify New User';
$l['isango_notify_registered_desc'] = 'Send a PM to newly registered user with the random password used to create account.';
$l['isango_single_connection_title'] = 'Single Connection Per Service';
$l['isango_single_connection_desc'] = 'Allow users to add and login through only one connection per service.';
$l['isango_input_mode_title'] = 'Input Mode';
$l['isango_input_mode_desc'] = 'Choose the default input mode for initiation. However; all the modes are globally available and usable (refer Isango Wiki).';
$l['isango_input_mode_max'] = 'Big size buttons with text';
$l['isango_input_mode_min'] = 'Small buttons with icon only';
$l['isango_input_mode_pop'] = 'A select dropdown';

$l['isango_uninstall'] = 'Isango Uninstallation';
$l['isango_uninstall_message'] = 'Do you wish to drop ALL plugin data from the database? Selecting \"No\" will leave untouched:<ul><li>Linked accounts for users.</li>\n\n</ul>\nSelecting \"No\" will <em>not</em>, however, prevent the removal of:<ul><li>Plugin settings (including any saved settings).</li>\n<li>The plugin\'s stylesheet, \"isango.css\" (including any changes you\'ve made to it), accessible for each theme via the ACP\'s Templates & Style -> <a href=\"index.php?module=style-themes\">Themes</a> module.</li>\n<li>The plugin\'s templates (including any changes you\'ve made to them), accessible  under each template set via the ACP\'s Templates & Style -> <a href=\"index.php?module=style-templates\">Templates</a> module.</li>\n</ul>';
