<?php

if (!defined("IN_MYBB")) {
    die("Nice try but wrong place, smartass. Be a good boy and use navigation.");
}

$plugins->add_hook('member_login', 'isango_bridge');
$plugins->add_hook('admin_settings_print_peekers', 'isango_settingspeekers');
$plugins->add_hook('global_start', 'isango_buttons');
//$plugins->add_hook('member_login_end', 'isango_buttons');

function isango_info()
{
    return array(
        'name' => 'Isango',
        'description' => 'Simple Social Login / Register Using oAuth 2.0',
        'website' => 'https://github.com/mybbgroup/Isango',
        'author' => 'effone</a> of <a href="https://mybb.group">MyBBGroup</a>',
        'authorsite' => 'https://eff.one',
        'version' => '1.0.0',
        'compatibility' => '18*',
        'codename' => 'isango',
    );
}

function isango_activate()
{
    global $db, $lang;
    $lang->load('isango');

	$stylesheet = @file_get_contents(MYBB_ROOT.'inc/plugins/isango/isango.css');
	$attachedto = '';
	$name = 'isango.css';
	$css = array(
		'name' => $name,
		'tid' => 1,
		'attachedto' => $db->escape_string($attachedto),
		'stylesheet' => $db->escape_string($stylesheet),
		'cachefile' => $name,
		'lastmodified' => TIME_NOW,
	);
	$db->update_query('themestylesheets', array(
		"attachedto" => $attachedto
	), "name='{$name}'");
	$query = $db->simple_select('themestylesheets', 'sid', "tid='1' AND name='{$name}'");
	$sid = (int) $db->fetch_field($query, 'sid');
	if ($sid) {
		$db->update_query('themestylesheets', $css, "sid='{$sid}'");
	} else {
		$sid = $db->insert_query('themestylesheets', $css);
		$css['sid'] = (int) $sid;
	}
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	if (!cache_stylesheet(1, $css['cachefile'], $stylesheet))
	{
		$db->update_query("themestylesheets", array('cachefile' => "css.php?stylesheet={$sid}"), "sid='{$sid}'", 1);
	}
    update_theme_stylesheet_list(1, false, true);    

    $gid = (int) ($db->fetch_field($db->simple_select("settinggroups", "gid", "name='isango'"), "gid"));
    $isango_opts = array();
    $disporder = 0;
    $available_gates = array();
    $query = $db->simple_select("settings", "name, disporder", "name LIKE 'isango_%_enabled'");
    while ($entry = $db->fetch_array($query)) {
        $gate = explode('_', $entry['name']);
        $available_gates[] = $gate[1];
        if ((int) $entry['disporder'] > $disporder) {
            $disporder = (int) $entry['disporder'] + 2;
        }
    }
    $supported_gates = isango_config();
    $required_gates = array_diff($supported_gates, $available_gates);
    $dropable_gates = array_diff($available_gates, $supported_gates);

    foreach ($required_gates as $gateway) {
        $isango_opts[] = array(
            'name' => 'isango_' . $gateway . '_enabled',
            'title' => $lang->sprintf($lang->isango_gateway_enabled_title, ucfirst($gateway)),
            'description' => $lang->sprintf($lang->isango_gateway_enabled_desc, ucfirst($gateway)),
            'optionscode' => 'onoff',
            'value' => '',
            'disporder' => ++$disporder,
            'gid' => intval($gid),
        );

        foreach (array('ID', 'Secret') as $key) {
            $isango_opts[] = array(
                'name' => 'isango_' . $gateway . '_' . strtolower($key),
                'title' => $lang->sprintf($lang->isango_gateway_key_title, ucfirst($gateway), $key),
                'description' => $lang->sprintf($lang->isango_gateway_key_desc, ucfirst($gateway), $key),
                'optionscode' => 'text',
                'value' => '',
                'disporder' => ++$disporder,
                'gid' => intval($gid),
            );
        }
    }

    foreach ($isango_opts as $isango_opt) {
        $db->insert_query("settings", $isango_opt);
    }

    foreach ($dropable_gates as $gate) {
        $db->delete_query("settings", "name LIKE '%isango_{$gate}%'");
    }

    rebuild_settings();

    require MYBB_ROOT . "inc/adminfunctions_templates.php";
    foreach(['header_welcomeblock_guest','member_login','member_register'] as $tpl){
        find_replace_templatesets($tpl, '#<\/form>#', '</form><!-- isango -->{$isango_buttons}<!-- /isango -->');
    }
}

function isango_deactivate()
{
    global $db;
    
	// Find the master and any children
	$query = $db->simple_select('themestylesheets', 'tid,name', "name='isango.css'");
	// Delete them all from the server
	while ($styleSheet = $db->fetch_array($query)) {
		@unlink(MYBB_ROOT."cache/themes/{$styleSheet['tid']}_{$styleSheet['name']}");
		@unlink(MYBB_ROOT."cache/themes/theme{$styleSheet['tid']}/{$styleSheet['name']}");
	}
	// Then delete them from the database
	$db->delete_query('themestylesheets', "name='isango.css'");
	// Now remove them from the CSS file list
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    update_theme_stylesheet_list(1, false, true);
    
    require MYBB_ROOT . "inc/adminfunctions_templates.php";
    foreach(['header_welcomeblock_guest','member_login','member_register'] as $tpl){
        find_replace_templatesets($tpl, '#\<!--\sisango\s--\>(.+)\<!--\s\/isango\s--\>#is', '', 0);
    }
}

function isango_install()
{
    global $db, $lang;
    $lang->load('isango');

    $isango_group = array(
        'name' => 'isango',
        'title' => $lang->isango_title,
        'description' => $lang->isango_description,
        'disporder' => '3',
        'isdefault' => '0',
    );
    $db->insert_query("settinggroups", $isango_group);

    rebuild_settings();
}

function isango_is_installed()
{
    global $db;
    $query = $db->simple_select("settinggroups", "gid", "name='isango'");
    $gid = $db->fetch_field($query, "gid");

    return !empty($gid);
}

function isango_uninstall()
{
    global $db;

    $db->delete_query("settings", "name LIKE '%isango_%'");
    $db->delete_query("settinggroups", "name='isango'");

    rebuild_settings();
}

function isango_settingspeekers(&$peekers)
{
    foreach (isango_config() as $gateway) {
        foreach (array('ID', 'Secret') as $key) {
            $peekers[] = 'new Peeker($(".setting_isango_' . $gateway . '_enabled"), $("#row_setting_isango_' . $gateway . '_' . strtolower($key) . '"),/1/,true)';
        }

    }
}

function isango_bridge()
{
    global $mybb, $errors;
    if (isset($mybb->input['gateway'])) { // oAuth call

        if (isset($mybb->input['code']) && isset($mybb->input['state'])) { // Verification return call from the gateway
            global $lang;
            $lang->load('isango');
            if (isset($mybb->cookies['isango_state']) && $mybb->cookies['isango_state'] == $mybb->input['state']) {
                my_unsetcookie('isango_state'); // Verified, destroy cookie
                try {
                    // Get the access token
                    $params = array('code' => $mybb->input['code'], 'state' => $mybb->input['state']);
                    $data = isango_curl($params, $mybb->input['gateway'], 'token');

                    // Get user information
                    $params = array('code' => $data['access_token']);
                    $user = isango_curl($params, $mybb->input['gateway']);
                } catch (Exception $e) {
                    $errors = $e->getMessage();
                }

                if (empty($errors)) {
                    $errors = !empty($user) ? isango_login($user, $mybb->input['gateway']) : $lang->no_user_data;
                }

            } else {
                my_unsetcookie('isango_state');
                $errors = $lang->auth_state_mismatch;
            }

        } else { // Initialization call by the user
            $gateway = strtolower($mybb->get_input('gateway'));
            $errors = isango_gateway_error($gateway);

            if (!$errors) {
                $conf = isango_config($gateway, 'auth');
                $state = hash('sha256', microtime(true) . rand() . $_SERVER['REMOTE_ADDR']);
                my_setcookie("isango_state", $state, '', true, "lax"); // Set a cookie to verify response

                $params = array_merge(array(
                    'client_id' => $mybb->settings['isango_' . $gateway . '_id'],
                    'redirect_uri' => $mybb->settings['bburl'] . '/member.php?action=login&gateway=' . $gateway,
                    'state' => $state,
                ), $conf['params']);

                // Redirect the user to authorization page
                header('Location: ' . $conf['url'] . '?' . http_build_query($params));
                die();
            }
        }
    }
}

function isango_login($user, $gateway)
{
    global $db, $lang;
    $lang->load('isango');

    switch ($gateway) {
        case 'google':
            $name = $db->escape_string($user['name']);
            $email = $db->escape_string($user['email']);
            //$avatar = $user['picture']; // Check for size, remote allowance
            break;
        
        case 'microsoft':
            $name = $db->escape_string($user['name']);
            $email = $db->escape_string($user['emails']['account']);
            break;
        
        default:
            break;
    }
    
    // Check availability of username by email
    $query = $db->simple_select("users", "uid, loginkey", "email='{$email}'");
    $user_info = $db->fetch_array($query);

    if (!$user_info) { // User not found, need to register a fresh account
        // Check for banned email
        if (is_banned_email($email, true)) {
            return $lang->auth_email_banned;
        }
        // Accumulate all possible usernames based on available data
        $possible_usernames = array();
        $temp1 = explode('@', $email);
        $possible_usernames[] = isango_purename($temp1[0]);
        $temp2 = explode(' ', $name);
        $possible_usernames[] = isango_purename($temp2[0]);
        $possible_usernames[] = isango_purename($email);
        $possible_usernames[] = isango_purename($temp1[0] . '@' . $gateway);
        if (count($temp2) > 1) {
            $possible_usernames[] = isango_purename(implode('_', $temp2));
        }
        $possible_usernames = array_values(array_filter($possible_usernames));

        $i = 0;
        do {
            $username = $possible_usernames[$i++];
            if (get_user_by_username(trim($username), ['exists' => true])) {
                $username = '';
            } else {
                $i = count($possible_usernames);
            }
        } while ($i < count($possible_usernames));

        if (empty($username)) {
            // Loop over, we don't have a username to use, OMG!
            return $lang->undetermined_username; // Improve the situation!!!!!!!
        } else {
            // Walla!!! got a name. Use it for new user registration
            require_once MYBB_ROOT . "inc/datahandlers/user.php";
            $userhandler = new UserDataHandler("insert");
            // Set the data for the new user.
            $userhandler->set_data(array(
                "username" => $username,
                "password" => base64_encode(random_bytes(10)).'aZ9', // Randpm pass, PHP 7+
                "email" => $email,
                "email2" => $email,
                "usergroup" => 2,
                "regip" => $session->packedip,
                "registration" => true,
            ));
            if (!$userhandler->validate_user()) {
                return $userhandler->get_friendly_errors();
            }
        }
        $user_info = $userhandler->insert_user();
    }
    // We have the user with us, let's log the user in
    my_setcookie("mybbuser", $user_info['uid'] . "_" . $user_info['loginkey'], null, true, "lax");
    redirect("index.php", $lang->sprintf($lang->auth_success_redirect, ucfirst($gateway)));
}

function isango_purename($username)
{
    global $mybb;
    require_once MYBB_ROOT . 'inc/functions_user.php';

    // Check and remove unwanted characters?
    $username = preg_replace("#\s{2,}#", " ", trim_blank_chrs(trim($username)));
    $username = preg_replace('~[\\\\/:*?"<>;,|]~', '', $username);
    $username = str_replace(array(unichr(160), unichr(173), unichr(0xCA), dec_to_utf8(8238), dec_to_utf8(8237), dec_to_utf8(8203)), array(" ", "-", "", "", "", ""), $username);

    // Check for banned / invalid usernames
    if (is_banned_username($username, true)
        || !validate_utf8_string($username, false, false)
        || ($mybb->settings['maxnamelength'] != 0 && my_strlen($username) > $mybb->settings['maxnamelength'])
        || ($mybb->settings['minnamelength'] != 0 && my_strlen($username) < $mybb->settings['minnamelength'])) {
        return false;
    }
    return $username;
}

function isango_curl(array $params, string $gateway, string $mode = 'api')
{
    global $mybb;
    $conf = isango_config($gateway, $mode);
    // Yahoo needs a GUID to access API
    if ($gateway == 'yahoo' && $mode == 'api') {
        $conf['url'] = sprintf($conf['url'], $params['guid']);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $conf['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($mode == 'token') {
        $query = array(
            'client_id' => $mybb->settings['isango_' . $gateway . '_id'],
            'client_secret' => $mybb->settings['isango_' . $gateway . '_secret'],
            'redirect_uri' => $mybb->settings['bburl'] . '/member.php?action=login&gateway=' . $gateway,
            'code' => $params['code'],
            'state' => $params['state'],
            'grant_type' => 'authorization_code',
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
    }

    // Set header
    $header = array('Accept' => 'application/json'); // Default, override with config
    if ($mode == 'api') {
        $header['Authorization'] = 'Bearer ' . $params['code'];
    }

    if (isset($conf['header']) && is_array($conf['header'])) {
        $header = array_merge($header, $conf['header']);
    }

    $compiled_header = array();
    foreach ($header as $key => $val) {
        $compiled_header[] = $key . ": " . $val;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $compiled_header);

    $data = json_decode(curl_exec($ch), true);

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200) {
        global $lang;
        $lang->load('isango');
        throw new Exception($lang->sprintf($lang->gateway_response_error, $mode, ucfirst($gateway), $http_code));
    }

    return $data;
}

function isango_buttons()
{
    global $isango_buttons;
    $isango_buttons = "";
    foreach (isango_config() as $gateway) {
        if (!isango_gateway_error($gateway)) {
            $isango_buttons .= '<a class="button isango_' . $gateway . '" href="member.php?action=login&gateway=' . $gateway . '"><span>' . ucfirst($gateway) . '</span></a>';
        }
    }
    if(!empty($isango_buttons)){
        $isango_buttons = "<div style='text-align: center; margin-top: 10px;'>" . $isango_buttons . "</div>";
    }
}

function isango_gateway_error(string $gateway)
{
    global $lang;
    $lang->load('isango');

    if (!in_array($gateway, isango_config())) {
        return $lang->sprintf($lang->gateway_not_supported, ucfirst($gateway));
    }

    global $mybb;

    if (empty($mybb->settings['isango_' . $gateway . '_enabled'])) {
        return $lang->sprintf($lang->gateway_not_enabled, ucfirst($gateway));
    }

    if (empty(trim($mybb->settings['isango_' . $gateway . '_id'])) || empty(trim($mybb->settings['isango_' . $gateway . '_secret']))) {
        return $lang->sprintf($lang->gateway_not_configured, ucfirst($gateway));
    }

    return false;
}

// Core configurations of supported gateways, also returns list of supported gateways
function isango_config(string $gateway = "", string $mode = "")
{
    $path = MYBB_ROOT . 'inc/plugins/isango/%s.ini';
    $gateways = array();

    foreach (glob(sprintf($path, '*')) as $gate) {
        $gateways[] = strtolower(basename($gate, '.ini'));
    }

    if (empty($gateway)) {
        return $gateways;
    }

    $gateway = strtolower($gateway);
    if (in_array($gateway, $gateways)) {
        $conf = parse_ini_file(sprintf($path, $gateway), true);

        if (!empty($mode)) {
            if (array_key_exists($mode, $conf)) {
                return $conf[$mode];
            }
            return false;
        }
        return $conf;
    }
    return false;
}