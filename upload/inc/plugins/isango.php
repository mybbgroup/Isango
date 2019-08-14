<?php

if (!defined("IN_MYBB")) {
    die("Nice try but wrong place, smartass. Be a good boy and use navigation.");
}

$plugins->add_hook('global_start', 'isango_buttons');
$plugins->add_hook('error', 'isango_buttons_nopermit');
$plugins->add_hook('member_login', 'isango_bridge');
$plugins->add_hook('usercp_menu', 'isango_ucpnav', 25);
$plugins->add_hook('usercp_start', 'isango_connections');
$plugins->add_hook('admin_settings_print_peekers', 'isango_settingspeekers');

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

    $stylesheet = @file_get_contents(MYBB_ROOT . 'inc/plugins/isango/isango.css');
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
        "attachedto" => $attachedto,
    ), "name='{$name}'");
    $query = $db->simple_select('themestylesheets', 'sid', "tid='1' AND name='{$name}'");
    $sid = (int) $db->fetch_field($query, 'sid');
    if ($sid) {
        $db->update_query('themestylesheets', $css, "sid='{$sid}'");
    } else {
        $sid = $db->insert_query('themestylesheets', $css);
        $css['sid'] = (int) $sid;
    }
    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
    if (!cache_stylesheet(1, $css['cachefile'], $stylesheet)) {
        $db->update_query("themestylesheets", array('cachefile' => "css.php?stylesheet={$sid}"), "sid='{$sid}'", 1);
    }
    update_theme_stylesheet_list(1, false, true);

    $gid = (int) ($db->fetch_field($db->simple_select("settinggroups", "gid", "name='isango'"), "gid"));
    $isango_opts = array();
    $disporder = 1; // 0 is reserved for common settings
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
    foreach (['header_welcomeblock_guest', 'member_login', 'member_register'] as $tpl) {
        find_replace_templatesets($tpl, '#<\/form>#', '</form><!-- isango -->{$isango_buttons}<!-- /isango -->');
    }
}

function isango_deactivate()
{
    global $db;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(MYBB_ROOT . 'cache/themes')) as $file) {
        if (stripos($file, 'isango') !== false) {
            @unlink($file);
        }

    }
    $db->delete_query('themestylesheets', "name='isango.css'");
    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
    update_theme_stylesheet_list(1, false, true);

    require MYBB_ROOT . "inc/adminfunctions_templates.php";
    foreach (['header_welcomeblock_guest', 'member_login', 'member_register'] as $tpl) {
        find_replace_templatesets($tpl, '#\<!--\sisango\s--\>(.+)\<!--\s\/isango\s--\>#is', '', 0);
    }
}

function isango_install()
{
    global $db, $lang;
    $lang->load('isango');

    // Install Isango templates
    foreach (glob(MYBB_ROOT . 'inc/plugins/isango/*.htm') as $template) {
        $db->insert_query('templates', array(
            'title' => $db->escape_string(strtolower(basename($template, '.htm'))),
            'template' => $db->escape_string(@file_get_contents($template)),
            'sid' => -2,
            'version' => 100,
            'dateline' => TIME_NOW,
        ));
    }

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "isango (
		cid int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		uid int(10) UNSIGNED NOT NULL,
		gateway varchar(30) NOT NULL DEFAULT 'unknown',
		cuid varchar(160),
		email varchar(220) NOT NULL DEFAULT '',
		name varchar(220) NOT NULL DEFAULT '',
		dateline int(10) UNSIGNED NOT NULL,
		PRIMARY KEY (`cid`),
		INDEX (`cuid`, `uid`)
		);"
    );

    $isango_group = array(
        'name' => 'isango',
        'title' => $lang->isango_title,
        'description' => $lang->isango_description,
        'disporder' => '3',
        'isdefault' => '0',
    );
    $db->insert_query("settinggroups", $isango_group);
    $gid = $db->insert_id();

    // Commom settings
    $isango_opts = array();
    $isango_opts[] = array(
        'name' => 'isango_default_gid',
        'title' => $lang->isango_default_gid_title,
        'description' => $lang->isango_default_gid_desc,
        'optionscode' => 'groupselectsingle',
        'value' => 2,
        'disporder' => 0,
        'gid' => intval($gid),
    );

    foreach ($isango_opts as $isango_opt) {
        $db->insert_query("settings", $isango_opt);
    }

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
    // $db->query("drop TABLE ".TABLE_PREFIX."isango");

    foreach (glob(MYBB_ROOT . 'inc/plugins/isango/*.htm') as $template) {
        $db->delete_query('templates', 'title = "' . strtolower(basename($template, '.htm')) . '"');
    }

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
                    $user = array();
                    $conf = isango_config($mybb->input['gateway'], 'api');
                    if (is_string($conf['url'])) {
                        $conf['url'] = (array) $conf['url'];
                    }

                    foreach ($conf['url'] as $url) {
                        $params = array('code' => $data['access_token'], 'url' => $url);
                        $response = isango_curl($params, $mybb->input['gateway']);
                        $user = array_merge($user, $response);
                    }

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
                    'response_type' => 'code',
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
    global $db, $lang, $mybb;
    $lang->load('isango');
    $errors = array();
    $udata = isango_fetchinfo($user, $gateway);

    foreach ($udata as $key => $val) {
        $udata[$key] = $val = filter_var($db->escape_string($val), FILTER_SANITIZE_STRING);
        if (empty($val) || ($val == "email" && !filter_var($val, FILTER_VALIDATE_EMAIL))) {
            $errors[] = "'" . ucfirst($key) . "'";
        }
    }

    if (!empty($errors)) {
        $errors = implode(', ', $errors);
        error($lang->sprintf($lang->isango_invalid_data, ucfirst($gateway), $errors), $lang->isango_connect_error_title);
    } else {
        extract($udata);
    }

    // Check for banned email
    if (is_banned_email($email, true)) {
        return $lang->auth_email_banned;
    }

    // Check availability of username by email
    $query = $db->query("
        SELECT u.uid, u.loginkey
        FROM " . TABLE_PREFIX . "users u
        LEFT JOIN " . TABLE_PREFIX . "isango i ON (i.uid=u.uid)
        WHERE u.email='{$email}'
        OR (i.gateway='{$gateway}' AND i.email='{$email}')
        OR (i.gateway='{$gateway}' AND i.cuid='{$id}')
    ");
    $user_info = $db->fetch_array($query);

    // We need to reconfirm about the gateway entry
    $connected = 0;
    $logged_in = $mybb->user['uid'];
    if ($user_info) {
        $connected = $db->fetch_array($db->simple_select('isango', 'uid', "(gateway='{$gateway}' AND email='{$email}') OR (gateway='{$gateway}' AND cuid='{$id}')"));
    }

    if (!$logged_in) {
        if (!$user_info) { // User not found, need to register a fresh account
            // Accumulate all possible usernames based on available data
            $possible_usernames = array();
            $temp1 = explode('@', $email);
            $possible_usernames[] = isango_purename($temp1[0]);
            $temp2 = explode(' ', $name);
            $possible_usernames[] = isango_purename($temp2[0]);
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

                global $cache;
                $usergroups = array();
                foreach ($cache->read('usergroups') as $group) {
                    $usergroups[] = $group['gid'];
                }
                $gid = intval($mybb->settings['isango_default_gid']);
                if (!in_array($gid, $usergroups)) {
                    $gid = 2; // Reset to registered usergroup
                }

                // Set the data for the new user.
                $userhandler->set_data(array(
                    "username" => $username,
                    "password" => base64_encode(random_bytes(10)) . 'aZ9', // Randpm pass, PHP 7+
                    "email" => $email,
                    "email2" => $email,
                    "usergroup" => $gid,
                    "regip" => $session->packedip,
                    "registration" => true,
                ));
                if (!$userhandler->validate_user()) {
                    return $userhandler->get_friendly_errors();
                }
            }
            $user_info = $userhandler->insert_user();
        }

        $redirect_url = 'index.php';
        $redirect_message = $lang->sprintf($lang->auth_success_registered_redirect, ucfirst($gateway));

        // We have the user with us, let's log the user in
        my_setcookie("mybbuser", $user_info['uid'] . "_" . $user_info['loginkey'], null, true, "lax");
    } else { // User already logged in
        $redirect_url = 'usercp.php?action=connections';
        if ($connected) {
            if ($connected['uid'] !== $mybb->user['uid']) {
                error($lang->isango_existing_connection, $lang->isango_connect_error_title);
            }
            $redirect_message = $lang->sprintf($lang->auth_already_connected_redirect, ucfirst($gateway));
        } else {
            $redirect_message = $lang->sprintf($lang->auth_success_connected_redirect, ucfirst($gateway));
        }
        $user_info['uid'] = $mybb->user['uid'];
    }

    // Make the connection entry
    if (!$connected) {
        $connected = array(
            'uid' => $user_info['uid'],
            'gateway' => $gateway,
            'cuid' => $id,
            'email' => $email,
            'name' => $name,
            'dateline' => TIME_NOW,
        );
        $db->insert_query("isango", $connected);
    }

    redirect($redirect_url, $redirect_message);
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

function isango_fetchinfo($u, $gateway)
{
    global $db;
    $info = array();
    $conf = isango_config($gateway, 'info');
    foreach ($conf as $key => $val) {
        eval("\$val = \"" . $val . "\";");
        $info[$key] = $db->escape_string($val);
    }
    return $info;
}

function isango_curl(array $params, string $gateway, string $mode = 'api')
{
    global $mybb;
    $conf = isango_config($gateway, $mode);
    $url = isset($params['url']) ? $params['url'] : $conf['url'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
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

function isango_buttons($return = false)
{
    global $isango_buttons;
    $isango_buttons = "";
    foreach (isango_config() as $gateway) {
        if (!isango_gateway_error($gateway)) {
            $isango_buttons .= '<a class="button isango_' . $gateway . '" href="member.php?action=login&gateway=' . $gateway . '"><span>' . ucfirst($gateway) . '</span></a>';
        }
    }
    if (!empty($isango_buttons)) {
        $isango_buttons = "<div style='text-align: center; margin-top: 10px;'>" . $isango_buttons . "</div>";
    }
    if ($return) {
        return $isango_buttons;
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

function isango_ucpnav()
{
    global $usercpmenu;
    $usercpmenu = substr_replace($usercpmenu, '<tr><td class="trow1 smalltext"><a href="usercp.php?action=connections" class="usercp_nav_item usercp_nav_connections">Connections</a></td></tr></tbody>', strrpos($usercpmenu, '</tbody>'), strlen('</tbody>'));
}

function isango_connections()
{
    global $mybb, $lang, $header, $footer, $headerinclude, $templates, $theme, $usercpnav, $db;
    $lang->load('isango');
    add_breadcrumb($lang->nav_usercp, "usercp.php");

    if ($mybb->input['action'] == "delete_connections" && $mybb->request_method == "post") {
        verify_post_check($mybb->get_input('my_post_key'));

        if ($_POST['cid']) {
            $cids = implode(',', array_map('intval', $_POST['cid']));
            $db->delete_query("isango", 'uid="' . $mybb->user['uid'] . '" AND cid IN (' . $cids . ')');
        }

        $mybb->input['action'] = "connections";
    }

    if ($mybb->input['action'] == "connections") {
        add_breadcrumb($lang->isango_nav_connections);
        $connections = '';
        $query = $db->simple_select('isango', '*', 'uid="' . $mybb->user['uid'] . '"');
        while ($conn = $db->fetch_array($query)) {
            $alt_row = alt_trow();
            $conn['gateway'] = ucfirst($conn['gateway']);
            $conn['dateline'] = my_date($mybb->settings['dateformat'], $conn['dateline']);
            eval("\$connections .= \"" . $templates->get("usercp_connections_connection") . "\";");
        }

        if (empty($connections)) {
            eval("\$connections = \"" . $templates->get("usercp_connections_none") . "\";");
        }

        $isango_buttons = isango_buttons(true);
        if (empty($isango_buttons)) {
            $isango_buttons = $lang->isango_no_service;
        }

        eval("\$connect_page = \"" . $templates->get("usercp_connections") . "\";");
        output_page($connect_page);
    }
}
