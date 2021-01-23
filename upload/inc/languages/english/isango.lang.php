<?php

/**
 * @Package: Isango : Frontend Languages
 * @description: Simple Social Login / Register for MyBB Using oAuth 2.0
 * @version: 2.0.0
 * @author: MyBB Group Developers (effone)
 * @authorsite: https://mybb.group
 * @update: 23-Jan-2020
 */

$l['gateway_not_supported'] = "Login with '{1}' is not supported.";
$l['gateway_not_enabled'] = "Login with '{1}' is disabled by the Administrator.";
$l['gateway_not_configured'] = "Login with '{1}' is not configured properly. Please contact Administrator.";
$l['gateway_response_error'] = "Failed to receive {1} data from '{2}' (status code: {3}). Please contact Administrator.";
$l['no_user_data'] = "No user data received from remote gateway.";
$l['undetermined_username'] = "No usable username could be determined from the available authenticated data.";
$l['auth_email_banned'] = "Authenticated email address is banned here. Please try other login method.";
$l['auth_state_mismatch'] = "Authorization state mismatch. Please don't refresh page while logging in.";
$l['auth_success_registered_redirect'] = "Welcome. Successfully logged in using '{1}'.";
$l['auth_already_connected_redirect'] = "The profile from '{1}' which you are trying to connect is already registered with your account.";
$l['auth_success_connected_redirect'] = "Gateway '{1}' is successfully connected to your account.";
$l['isango_gateway'] = "Gateway";
$l['isango_regmail'] = "Associated email";
$l['isango_identifier'] = "Identifier";
$l['isango_regdate'] = "Registered On";
$l['isango_state'] = "State";
$l['isango_stateoffline'] = "Inactive Connection";
$l['isango_stateonline'] = "Active Connection";
$l['isango_nav_connections'] = "Connections";
$l['isango_connect_new'] = "Connect New Authentication";
$l['isango_connect_title'] = "Connected Authentications";
$l['isango_selectallconn'] = "Select All Connections";
$l['isango_confirmconndel'] = "Are you sure you want to delete selected connections?";
$l['isango_noconnselected'] = "Please select some connections before attempting to delete.";
$l['isango_delete_button'] = "Delete Selected Connections";
$l['isango_no_service'] = "No service is available for you to connect at this moment.";
$l['isango_no_connection'] = "No connection detail is available for your account at this moment. You can add connections from below.";
$l['isango_existing_connection'] = "The authentication you are trying to connect is already registered with some other account.";
$l['isango_unverified_title'] = "Unverified User Data";
$l['isango_unverified_data'] = "Returned data from the provider is not verified. Please pass verification of your account data at provider's end and try back.";
$l['isango_regrestrict_title'] = "New Registration Restricted";
$l['isango_registration_restricted'] = "Sorry, obtained data doesn't match with any existing account and the administrator decided not to allow fresh account creation using third party authentication service.";
$l['isango_invalid_data'] = "Service '{1}' failed to return acceptable {2}. Check your account settings or try different service gateway.";
$l['isango_connect_error_title'] = "Authentication Connection Error!!!";
$l['isango_single_connection_error'] = "You already have a connection established using {1} gateyay. You can add only one connection per service.";
$l['isango_pmnotify_subject'] = "Welcome to {1}";
$l['isango_pmnotify_matter'] = "Welcome {1},\r\n\r\nYour new account has been created by {2} authintation using the following random password:\r\n\r\n{3}\r\n\r\nHowever we encourage you to change the password immediately for security reasons.\r\n\r\nHave a pleasant stay.\r\n\r\nWishes,\r\n- {4} Team";
