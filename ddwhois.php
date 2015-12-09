<?php

if( !defined( 'WHMCS' )){
	die( 'This file cannot be accessed directly.' );
}

define( 'WHOIS_SERVERS_FILE', __DIR__ . '/../../../includes/whoisservers.php' );
define( 'WHOIS_SERVERS_BACKUP', __DIR__ . '/whoisservers_backup.txt' );
define( 'SETTINGS_FILE', __DIR__ . '/config.json' );

/**
 * Retrieve configuration for the module.
 * @return Array
 */
function ddwhois_config()
{
	$config = array(
		'name' => 'DonDominio Whois Addon',
		'description' => 'Adds the capability to perform Whois queries using the DonDominio/MrDomain API through WHMCS',
		'version' => '1.0',
		'author' => 'DonDominio',
		'language' => 'English',
		'fields' => array()
	);
	
	return $config;
}

/**
 * Main output for the module.
 * @param Array $vars Arguments passed by WHMCS
 */
function ddwhois_output( $vars )
{
	$module_link = $vars['modulelink'];
	$version = $vars['version'];
	$lang = $vars['_lang'];
	
	switch( $_REQUEST['action'] ){
	case 'import':
		ddwhois_import( $vars );
		break;
	case 'import_process':
		ddwhois_import_process( $vars );
		
		break;
	case 'export':
		ddwhois_export( $vars );
		break;
	case 'switch':
		if( !empty( $_GET['tld'] )){
			ddwhois_setup( $vars, $_GET['tld'] );
		}
		break;
	case 'save_settings':
		ddwhois_save_settings(
			array(
				'apiuser' => $_POST['username'],
				'apipasswd' => $_POST['password'],
				'domain' => $_POST['domain']
			)
		);
	case 'settings':
		ddwhois_settings( $vars );
		
		break;
	case 'new_tld':
		ddwhois_new_tld( $vars );
		break;
	default:	
		if( array_key_exists( 'message', $_GET )){
			if( $_GET['message'] == 'new-tld-error-permissions' ){
				echo "<div class='errorbox'><span class='title'>" . $lang['new-tld-error-permissions'] . "</span></div>";
			}
			
			if( $_GET['message'] == 'new-tld-ok' ){
				echo "<div class='successbox'><span class='title'>" . $lang['new-tld-ok'] . "</span></div>";
			}
			
			if( $_GET['message'] == 'new-tld-error' ){
				echo "<div class='errorbox'><span class='title'>" . $lang['new-tld-error'] . "</span></div>";
			}
			
			if( $_GET['message'] == 'import-ok' ){
				echo "<div class='successbox'><span class='title'>" . $lang['import-ok'] . "</span></div>";
			}
			
			if( $_GET['message'] == 'import-error' ){
				echo "<div class='errorbox'><span class='title'>" . $lang['import-error'] . "</span></div>";
			}
			
			if( $_GET['message'] == 'settings-error' ){
				echo "<div class='errorbox'><span class='title'>" . $lang['settings-error'] . "</span></div>";
			}
			
			if( $_GET['message'] == 'settings-ok' ){
				echo "<div class='successbox'><span class='title'>" . $lang['settings-ok'] . "</span></div>";
			}			
		}
		
		echo "
		<script>
		$( \"a[href^='#tab']\" ).click( function() {
			var tabID = $(this).attr('href').substr(4);
			var tabToHide = $('#tab' + tabID);
			if(tabToHide.hasClass('active')) {
				tabToHide.removeClass('active');
			}  else {
				tabToHide.addClass('active')
			}
		});
		</script>
		
		<a class='btn btn-default btn-sm' href='addonmodules.php?module=ddwhois&action=settings'>" . $lang['config_settings'] . "</a>
		<a class='btn btn-default btn-sm' href='addonmodules.php?module=ddwhois&action=export'>" . $lang['servers_export'] . "</a>
		<a class='btn btn-default btn-sm' href='addonmodules.php?module=ddwhois&action=import'>" . $lang['servers_import'] . "</a>
		
		<div class='contexthelp'>
			<img src='images/icons/reports.png' border='0' align='absmiddle'>&nbsp;
			<a href='https://dev.dondominio.com/whmcs/docs/whois/'>
				" . $lang['info_path_moreinfo'] . "
			</a>
		</div>
		
		<p>
		<ul class='nav nav-tabs admin-tabs' role='tablist'>
			<li>
				<a href='#tab1' role='tab' data-toggle='tab' id='tabLink1'>
					" . $lang['new_tld'] . "
				</a>
			</li>
		</ul>
		
		<div class='tab-content admin-tabs'>
			<div class='tab-pane' id='tab1'>
				<form method='get' action='addonmodules.php'>
					<input type='hidden' name='module' value='ddwhois' />
					<input type='hidden' name='action' value='switch' />
					
					<table class='form' width='100%' border='0' cellspacing='2' cellpadding='3'>
						<tbody>
							<tr>
								<td width='30%' class='fieldlabel'>
									" . $lang['new_tld_tld'] . "
								</td>
								
								<td class='fieldarea'>
									<input type='text' name='tld' size='30' value='' required='required' />
								</td>
							</tr>
						</tbody>
					</table>
					
					<div class='btn-container'>
						<input type='submit' id='search-clients' value='" . $lang['new_tld_add'] . "' class='button btn btn-default'>
					</div>
				</form>
			</div>
		</div>
		";
		
		if( !is_writable( WHOIS_SERVERS_FILE )){
			echo "<div class='infobox'><span class='title'>" . $lang['error_servers_no_writable'] . "</span></div>";
			
			echo "
			<p>
				" . $lang['info_path_whois'] . ": <strong>" . realpath( WHOIS_SERVERS_FILE ) . "</strong>
			</p>
			
			<p>
				
			</p>
			";
			
			return false;
		}
		
		$whois_database = @file( WHOIS_SERVERS_FILE );
		
		echo "
		<p>
		
		<div class='tab-pane active' id='tab1'>
			<form method='post' action=''>
				<table class='datatable' width='100%' border='0' cellspacing='1' cellpadding='3' id='domainpricing'>
					<thead>
						<tr>
							<th width='50%'>
								TLD
							</th>
							
							<th width='50%'>
								Server
							</th>
							
							<th width='1'>
								&nbsp;
							</th>
						</tr>
					</thead>
					
					<tbody>
		";
		
		foreach( $whois_database as $entry_id=>$entry ){
			$components = explode( '|', $entry );
			
			$tld = $components[0];
			$server = $components[1];
			$match = trim( $components[2] );
			
				
			echo "
						<tr id='dp-1'>
							<td>
								$tld
							</td>
							
							<td>
								$server
							</td>
							
							<td>
			"; 
			
			if( is_writable( WHOIS_SERVERS_FILE ) && $match != 'HTTPREQUEST-DDAVAILABLE' ){
				echo "
								<a href='addonmodules.php?module=ddwhois&action=switch&tld=" . $tld . "' class='btn btn-default btn-sm'>
									" . $lang['config_switch'] . "
								</a>
				";
			}else{
				echo "
								&nbsp;
				";
			}
			
			echo "
							</td>
						</tr>
			";
		}
		
		echo "
					</tbody>
				</table>
			</form>
		</div>
		";
		
		break;
	}
}

function ddwhois_get( $key )
{
	$config_file = @file_get_contents( SETTINGS_FILE );
	
	$config = json_decode( $config_file, true );
	
	return $config[$key];
}

function ddwhois_set( $key, $value )
{
	$config_file = @file_get_contents( SETTINGS_FILE );
	
	$config = json_decode( $config_file, true );
	
	$config[ $key ] = $value;
	
	$config_json = json_encode( $config );
	
	return @file_put_contents( SETTINGS_FILE, $config_json );
}

function ddwhois_export( array $vars )
{	
	$lang = $vars['_lang'];
	
	header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename( WHOIS_SERVERS_FILE ).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize( WHOIS_SERVERS_FILE ));
    readfile( WHOIS_SERVERS_FILE );
}

function ddwhois_import( array $vars )
{
	$lang = $vars['_lang'];
	
	echo "
	<form action='addonmodules.php' method='post' enctype='multipart/form-data'>
		<input type='hidden' name='module' value='ddwhois' />
		<input type='hidden' name='action' value='import_process' />
		
		<input type='file' name='whoisservers' />
		
		<div class='btn-container'>
			<input id='saveChanges' type='submit' value='" . $lang['import_btn'] . "' class='btn btn-primary' />
			<a class='btn btn-default' href='addonmodules.php?module=ddwhois'>" . $lang['config_cancel'] . "</a>
		</div>
	</form>
	";
}

function ddwhois_import_process( array $vars )
{
	$lang = $vars['_lang'];
	
	//Backing up, just in case...
	ddwhois_backup();
	
	$file_contents = @file( $_FILES['whoisservers']['tmp_name'] );
	
	$error = false;
	
	//Checking if the file has the correct format
	foreach( $file_contents as $line ){
		$components = explode( '|', $line );
		
		if( count( $components ) != 3 ){
			$error = true;
			break;
		}
		
		if( substr( $components[0], 0, 1 ) != '.' ){
			$error = true;
			break;
		}
	}
	
	if( !$error ){
		move_uploaded_file( $_FILES['whoisservers']['tmp_name'], WHOIS_SERVERS_FILE );
		
		header( 'Location: addonmodules.php?module=ddwhois&message=import-ok' );
		exit();
	}
	
	header( 'Location: addonmodules.php?module=ddwhois&message=import-error' );
	exit();
}

/**
 * Setup a TLD to use DD API for Whois.
 * @param string $tld The TLD to configure
 * @return bool
 */
function ddwhois_setup( array $vars, $new_tld )
{
	$lang = $vars['_lang'];
	
	if( empty( trim( $new_tld ))){
		header( 'Location: addonmodules.php?module=ddwhois&message=new-tld-error' );
		exit();
	}
	
	//Backup
	ddwhois_backup();
	
	//Load file
	$whois_database = @file( WHOIS_SERVERS_FILE );
	
	//Flag for found TLDs
	$found = false;
	
	//Get URL
	$url = $_SERVER['REQUEST_URI'];
	$admin_section = strpos( $url, '/admin' );
	$route = substr( $url, 0, $admin_section );
	
	//Looking for the TLD in the file
	foreach( $whois_database as $whois_id=>$whois_entry ){
		$components = explode( '|', $whois_entry );
		
		$tld = $components[0];
		$server = $components[1];
		$match = $components[2];
		
		//TLD found; modify its settings
		if( $tld == $new_tld ){
			$whois_database[$whois_id] = $tld . '|http://' . ddwhois_get( 'domain' ) . $route . '/modules/addons/ddwhois/whoisproxy.php?domain=|HTTPREQUEST-DDAVAILABLE' . "\r\n";
			
			$found = true;
		}
	}
	
	//TLD not found in current file; add it to the bottom
	if( !$found ){
		$whois_database[] = $new_tld . '|http://' . ddwhois_get( 'domain' ) . $route . '/modules/addons/ddwhois/whoisproxy.php?domain=|HTTPREQUEST-DDAVAILABLE' . "\r\n";
	}
	
	//Save the resulting file
	$result = @file_put_contents( WHOIS_SERVERS_FILE, implode( "", $whois_database ));
	
	//Default confirmation
	$message = 'new-tld-ok';
	
	//Lookup for errors while saving the file
	if( !$result ){
		$message = 'new-tld-error-permissions';
	}
	
	//Redirect
	header( 'Location: addonmodules.php?module=ddwhois&message=' . $message );
}

/**
 * Make a backup of the original whois servers file
 * Creates a backup on the local directory of the original whois servers file for restoring it
 * later, if needed.
 * @return bool
 */
function ddwhois_backup()
{	
	//Do not overwrite the backup if it already exists
	if( !file_exists( WHOIS_SERVERS_BACKUP )){
		copy( WHOIS_SERVERS_FILE, WHOIS_SERVERS_BACKUP );
		
		return true;
	}
	
	return false;
}

/**
 * Save the settings & redirect
 * This function is called whenever the settings screen submits the form via POST. Settings are
 * pulled from the POST array and passed in the $settings array. After saving the settings, sets
 * a redirect header and exits script execution.
 * @param array $settings Settings to save
 */
function ddwhois_save_settings( array $settings )
{
	$username = ddwhois_set( 'apiUsername', trim($settings['apiuser']) );
	$password = ddwhois_set( 'apiPassword', trim($settings['apipasswd']) );
	$domain = ddwhois_set( 'domain', trim( $settings['domain'] ));
	
	//¿Error saving? Redirect w/ error
	if( !$username || !$password ){
		header( 'Location: addonmodules.php?module=ddwhois&message=settings-error' );
		exit();
	}
	
	//Redirect
	header( 'Location: addonmodules.php?module=ddwhois&message=settings-ok' );
	
	exit();
}

/**
 * Settings screen.
 * @param array $vars Parameters from WHMCS
 */
function ddwhois_settings( array $vars )
{
	$lang = $vars['_lang'];
	
	echo "
		<div class='tab-pane active' id='tab1'>
			<form method='post' action='addonmodules.php'>
				<input type='hidden' name='module' value='ddwhois' />
				<input type='hidden' name='action' value='save_settings' />
				
				<table class='form' width='100%' border='0' cellspacing='2' cellpadding='3'>
					<tbody>
						<tr>
							<td class='fieldlabel' width='200'>
								" . $lang['config_username'] . "
							</td>
							
							<td class='fieldarea'>
								<input type='text' name='username' value='" . ddwhois_get( 'apiUsername' ) . "' size='35'>
							</td>
						</tr>
						
						<tr>
							<td class='fieldlabel' width='200'>
								" . $lang['config_password'] . "
							</td>
							
							<td class='fieldarea'>
								<input type='password' name='password' value='" . ddwhois_get( 'apiPassword' ) . "' size='35'>
							</td>
						</tr>
						
						<tr>
							<td class='fieldlabel' width='200'>
								" . $lang['config_domain'] . "
							</td>
							
							<td class='fieldarea'>
								<input type='text' name='domain' value='" . ddwhois_get( 'domain' ) . "' size='35'>
							</td>
						</tr>
					</tbody>
				</table>
				
				<div class='btn-container'>
					<input id='saveChanges' type='submit' value='" . $lang['config_save'] . "' class='btn btn-primary' />
					<a class='btn btn-default' href='addonmodules.php?module=ddwhois'>" . $lang['config_cancel'] . "</a>
				</div>
			</form>
		</div>
	";
}

?>