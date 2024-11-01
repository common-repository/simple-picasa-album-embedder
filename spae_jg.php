<?php
/*
Plugin Name: Simple Picasa Album Embedder
Plugin URI: http://www.caekcraft.co.uk/simple-picasa-album-embedder/
Description: A simple way to embed the pictures of a picasa album. You supply your e-mail address and password. The plugin then gets all your albums (the private ones as well), allows you to select which page you want to embed your full album, and voilá. It puts the gallery before the actual content.

Version: 1.0.7
Author: CaekCraft
Author URI: http://www.caekcraft.co.uk
License: GPL2
*/
add_action('admin_init', array('spae_jg','init'));
add_action('admin_menu',array('spae_jg','admin_menus'));
add_action('wp_head',array('spae_jg','add_css'));
add_filter('the_content', array('spae_jg','insert_gallery'));
add_action('admin_print_styles', array('spae_jg','add_css'));


class spae_jg {

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: set_defaults()
	 * purpose: When the plugin is first installed, populate the database with initial data to avoid calling the validation function twice on first submit.
	 * called from: init()
	 * calls: default_values()
	 * returns: none
	 */
	function set_defaults() {
		global $spae_vars;
		$spae_vars = get_option('spae_DB');
		if( false === $spae_vars) {
			$def_options = self::default_values();
			update_option('spae_DB',$def_options);
		}
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: default_values()
	 * purpose: Populate an array with default values, before user inputs anything.
	 * called from: set_defaults()
	 * calls: none
	 * returns: $options (array)
	 */
	function default_values() {
		$options = array(
			'gmail' => '',
			'pass' => '',
			'album' => '',
			'key' => '',
			'pageid' => '');
		return $options;
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: include_zend()
	 * purpose: The plugin needs certain Zend classes to work (to be able to connect to google's api and user content). This checks whether these classes are present, and if not, tries to include them, then checks again, and provides feedback.
	 * called from: init()
	 * returns: true upon success (classes are included and ready to use), false (otherwise)
	 */
	function include_zend()
	{
		$zclassesincluded = 0; //I use this to check whether all classes are present
		$paths = explode(PATH_SEPARATOR, get_include_path()); //get the include paths to search for Zend. The Zend gdata interfaces plugin adds an include path, so it will show up here
		foreach ($paths as $path) {
			if(file_exists($path.'/Zend/Loader.php')) {
				require_once($path.'/Zend/Loader.php'); //if the file is found, include it
				$zend_classes = array('Zend_Gdata','Zend_Gdata_ClientLogin','Zend_Gdata_Photos','Zend_Http_Client','Zend_Gdata_Photos_UserQuery','Zend_Gdata_Feed','Zend_Uri_Http'); // populate with all the classes the plugin needs
				foreach ($zend_classes as $class_to_include) { // iterate
					Zend_Loader::loadClass($class_to_include); // include
					if(class_exists($class_to_include)) {
						$zclassesincluded++; } // check if include successful, and increase the variable
				}
				break; // if found once in the include paths, stop. We don't want to include it twice
			}
		}
		if($zclassesincluded == 7) { // if all classes are included, the value should be 7. If it is not 7, some or all classes did not get included => problem
			return true; // green light for the rest of the plugin
		} else {
			return false; // red light for the rest of the plugin
		}
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: google_login()
	 * purpose: Checks whether credentials supplied are valid or not
	 * called from: init(), insert_gallery()
	 * calls: various Zend classes that connect to google
	 * returns: true on success, false on failure
	 */
	function google_login($jg_gmail, $password) {
		$svc = Zend_Gdata_Photos::AUTH_SERVICE_NAME;
		try {
			$client = Zend_Gdata_ClientLogin::getHttpClient($jg_gmail, $password, $svc);
			return true;
		}
		catch (Zend_Gdata_App_AuthException $e)
		{
			$gerror = "error: ". $e->getMessage();
			return false;
		}
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: decrypt_all()
	 * purpose: decrypts everything, and stores them in constants
	 * called from: init(), insert_gallery()
	 * calls: Encryption::decrypt
	 * returns: constants
	 */
	function decrypt_all() {
		$spae_vars = get_option('spae_DB');
		if(is_array($spae_vars) && $spae_vars['gmail'] != '') {
			$remove = array('key');
			foreach ($spae_vars as $key => $value) {
				if($value == '') {
					array_push($remove, $key);
				}
			}
			$fremove = array_flip($remove);
			$to_decrypt = array_diff_key($spae_vars, $fremove);

			foreach($to_decrypt as $key => $value) {
				$decrypted[$key] = Encryption::decrypt($value, $spae_vars['key']);
			}
			$key_to_vars = array('key' => $spae_vars['key']);
			$to_define = array_merge($decrypted, $key_to_vars);
		}
		if (isset($to_define) && array_key_exists('gmail',$to_define)) { define('email',$to_define['gmail'], true); } else { define('email',''); }
		if (isset($to_define) && array_key_exists('pass',$to_define)) { define('password', $to_define['pass'], true); } else { define('password',''); }
		if (isset($to_define) && array_key_exists('key',$to_define)) { define('keyphrase', $to_define['key'], true); } else { define('keyphrase',''); }
		if (isset($to_define) && array_key_exists('album',$to_define)) { define('albumid', $to_define['album'], true); } else { define('albumid',''); }
		if (isset($to_define) && array_key_exists('pageid',$to_define)) { define('pageid', $to_define['pageid'], true); } else { define('pageid',''); }
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: embed_paypal()
	 * purpose: to embed a donation button to me
	 * called from: SPAE_form()
	 * calls: none
	 * returns: html
	 */
	function embed_paypal() {
		?>
		<div id="paypalbutton">
			<h3>Keep me fed!</h3>
			<p>If this plugin helped you in any way, don't shy away from clicking the Donate button. I will be grateful. You could buy me ink for the printer, paper for the printer, coffee to keep me alive, and so on.</p>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" name="paypal" target="_blank">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="hosted_button_id" value="4WEWF3GDRR3RC">
				<input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
				<img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_GB/i/scr/pixel.gif" width="1" height="1">
			</form>
		</div>
		<?php
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: init()
	 * purpose: Builds up the necessary framework for the options. Sets the section, field, checks whether all prerequisites are present, and if not, notifies the user.
	 * called from: add_action() -> "admin_init" hook (line 10)
	 * calls: various functions that display the sections and fields.
	 * returns: html
	 */
	function init(){
		self::set_defaults(); // checks if first install, populates DB, otherwise nothing
		self::decrypt_all(); // gets data from DB, decrypts them and makes them ready for further use
		$check_zend = self::include_zend(); // checks whether Zend is present or not
		if($check_zend) {
			$check_google_login = self::google_login(email,password); // checks if credentials are good to make a connection or not. Also needed to embed this here, because PHP will die if zend is not present (as google_login uses zend)
		} else {
			$check_google_login = false; // sets google_login to false if zend is not present (thus cannot even check)
		}

		register_setting(
			'spae_main', //the settings group name
			'spae_DB', //the var to store the array in the DB (key)
			array('spae_jg','validate')); //  'plugin_options_validate'
		add_settings_section(
			'spae_section', //the section id
			'Account Data', // the section title
			array('spae_jg','HTML_SECTION'), //the callback function
			'spae_settings'); //the page id (options-general.php?page=XXX <- this)

		/*	these are the fields from now on */
		add_settings_field(
			'spae_email', //the field ID
			'Your e-mail address', // the field title
			array('spae_jg', 'HTML_email'), //the callback function
			'spae_settings', // the page id (options-general.php?page=XXX <- this)
			'spae_section'); // the section ID
		add_settings_field(
			'spae_pwd', //the field ID
			'Password', // the field title
			array('spae_jg', 'HTML_pwd'), //the callback function
			'spae_settings', // the page id (options-general.php?page=XXX <- this)
			'spae_section'); // the section ID
		add_settings_field(
			'spae_key', // the field ID
			'Key', // field title (cuz it's hidden)
			array('spae_jg', 'HTML_key'), // the callback function
			'spae_settings', // the page id (options-general.php?page=XXX <- this)
			'spae_section'); // the section ID

		if($check_zend) {
			if($check_google_login) {
				//these are only run if Zend is present, and credentials are good

				add_settings_field(
					'spae_album', //the field ID
					'Which album would you like to embed?', // the field title
					array('spae_jg', 'HTML_album'), //the callback function
					'spae_settings', // the page id (options-general.php?page=XXX <- this)
					'spae_section'); // the section ID
				add_settings_field(
					'spae_pageid', //the field ID
					'ID of the target page', // the field title
					array('spae_jg', 'HTML_pageid'), //the callback function
					'spae_settings', // the page id (options-general.php?page=XXX <- this)
					'spae_section'); // the section ID
			} else {
				// this is run when Zend is present, but google credentials are not okay

				add_settings_field(
					'spae_nogoogle', //the field ID
					'Google Login Unsuccessful', // the field title
					array('spae_jg', 'HTML_nogoogle'), //the callback function
					'spae_settings', // the page id (options-general.php?page=XXX <- this)
					'spae_section'); // the section ID
			}
		} else {
			// this is run when Zend is not present, even though inclusion has been tried.

			add_settings_field(
				'spae_nozend',
				'Ooops, Zend is borked',
				array('spae_jg', 'HTML_nozend'),
				'spae_settings',
				'spae_section');
		}
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: admin_menus()
	 * purpose: Adds capability and means for the user to interact with the plugin (which means adds a menu under the "Settings" section, and a page where all the settings can be tweaked).
	 * called from: add_action -> "admin_menu" hook (line 11)
	 * calls: add_options_page() -> SPAE_form() : to render the option page
	 * returns: html
	 */
	function admin_menus() {
		if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
			return;
		}
		if (function_exists('add_options_page')) {
			add_options_page(
				'SPAE Settings', // Title of the page
				'SPAE Settings menu', // menu text
				'manage_options',  // credentials necessary to display the menu
				'spae_settings',   // the page id (options-general.php?page=XXX <- this)
				array('spae_jg', 'SPAE_form')); // calls the function that actually displays the settings page for it. (important, as without the settings fields and do functions, jack shit will be visible.
		}
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: SPAE_form()
	 * purpose: Renders the initial html content on the options page
	 * called from: admin_menus
	 * calls: the html functions as set out in the add_settings_field() and add_settings_section() functions within init()
	 * returns:
	 */
	function SPAE_form() {
		$spae_vars = get_option('spae_DB');
		?>
		<div id="spae_wrap">
			<?php screen_icon("options-general"); ?>
			<h2>Simple Picasa Album Embedder Settingz</h2>
			<form name="spae" action="options.php" method="post">
				<?php settings_fields('spae_main'); // the group id is needed (register settings) ?>
				<?php do_settings_sections('spae_settings');  // the page id (options-general.php?page=XXX <- this)?>
				<p class="submit">
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
				</p>
			</form>
			<?php self::embed_paypal(); ?>

		</div>
		<?php
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: Overview()
	 * purpose: display optional html content at the beginning of the settings section
	 * called from: spae_jg::init::add_settings_section
	 * calls: void
	 * returns: html
	 */
	function HTML_SECTION() {

	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: HTML_email
	 * purpose: displays the form field for one of the sections
	 * called from: spae_jg::init::add_settings_field()
	 * calls: void
	 * returns: html
	 */
	function HTML_email() { //the html for the email input field
		$spae_vars = get_option('spae_DB'); //the DB key is needed here
		?>
		<input
			id="gmail"
			name="spae_DB[gmail]"
			class="regular-text"
			value="<?php echo email; ?>"
		/>
		<?php
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: HTML_pwd
	 * purpose: displays the password field for the settings section
	 * called from: spae_jg::init::add_settings_field()
	 * calls: void
	 * returns: html
	 */
	function HTML_pwd() { //the html for the password input field
		$spae_vars = get_option('spae_DB'); //the DB key is needed here
		?>
		<input
			id="pass"
			type="password"
			name="spae_DB[pass]"
			class="regular-text"
			value="<?php echo password; ?>"
		/>
		<?php
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: HTML_key
	 * purpose: displays the hidden field for the keyphrase
	 * called from:
	 * calls:
	 * returns:
	 */

	function HTML_key() { //the html for the password input field
		$spae_vars = get_option('spae_DB'); //the DB key is needed here
		if(isset($spae_vars['key']) && strlen($spae_vars['key']) == 15) {
			$keyhelper = $spae_vars['key'];
		} else {
			$keyhelper = self::random_string();
		}
		define('keyphrase2', $keyhelper, true);
?>
		<input
			id="pass"
			type="hidden"
			name="spae_DB[key]"
			class="regular-text"
			value="<?php echo keyphrase2; ?>"
		/>
		<?php
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: HTML_album
	 * purpose: does logic based on whether the plugin can connect to the user's google account or not. If not, displays warning messages, and informs the user about the next steps. If it can, displays a dropdown list of the albums that the user has on her picasa web albums.
	 * called from: spae_jg::init::add_settings_field()
	 * calls: TBC
	 * returns: TBC
	 */
	function HTML_album() { //the html for selecting the album (will only work, if there is a connection made, and the list is fetched successfully)
		$spae_vars = get_option('spae_DB'); //the DB key is needed here
		$svc = Zend_Gdata_Photos::AUTH_SERVICE_NAME;
		$client = Zend_Gdata_ClientLogin::getHttpClient(email, password, $svc);
		$gphoto = new Zend_Gdata_Photos($client);
		$query = new Zend_Gdata_Photos_UserQuery();
		$query->setType("entry");
		try {
			$userFeed = $gphoto->getUserFeed(null, $query);
			echo "<select id='album' name='spae_DB[album]' >";
			foreach ($userFeed as $user_entry) {
				$albumid = $user_entry->id->text;
				$albumid_array = explode("/",$albumid);
				$rev_albumid_array = array_reverse($albumid_array);
				$clean_albumid = $rev_albumid_array[0];
				$selected = (albumid == $clean_albumid) ? ' selected="selected" ' : '';

				$option = '<option value="'.$clean_albumid.'"'. $selected . '/>';
				$option .= $user_entry->title->text;
				$option .= '</option>';
				echo $option;
			}
			echo "</select>";
		} catch (Zend_Gdata_App_HttpException $e) {
			echo "Error: ". $e->getMessage() . "<br />\n";
			if( $e->getReponse() != null) {
				echo "Body: <br />\n" . $e->getResponse()->getBody() . "<br />\n";
			}
			echo "Error: " . $e->getMessage();
		}
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: HTML_pageid()
	 * purpose: To display a field where user can specify which page she wants to embed the gallery. If plugin cannot connect, does not display anything, since there is no point.
	 * called from: spae_jg::init::add_settings_field()
	 * calls: TBC
	 * returns: TBC
	 */
	function HTML_pageid() { //the html for selecting the page to include the gallery in
		$spae_vars = get_option('spae_DB'); //the DB key is needed here
		$pages = get_pages();
		echo "<select id='pageid' name='spae_DB[pageid]' >";
		foreach ($pages as $pagg) {
			$selected = (pageid == $pagg->ID) ? ' selected="selected" ' : '';
			$option = '<option value="'.$pagg->ID .'"'. $selected . '/>';
			$option .= $pagg->post_title;
			$option .= '</option>';
			echo $option;
		}
		echo "</select>";
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: HTML_nozend
	 * purpose: displays warning, informing user that the Zend library is not available, therefore it cannot connect to the google account
	 * called from: spae_jg::init::add_settings_field() after logic check
	 * calls: void
	 * returns: html
	 */
	function HTML_nozend() {
		?>
		<div class="trolling">
			<p>Sorry, Zend is not loaded. It is entirely pointless to select the album and the page you want to include the album to, because the connection will not be made.</p>
			<p>If this happens, download the <a href="http://wordpress.org/extend/plugins/zend-gdata-interfaces/" target="_blank">Zend Gdata Interfaces plugin</a>, and you should be ready to go.</p>
		</div>
		<?php
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: HTML_nogoogle
	 * purpose: to display a warning message should the user provide fake credentials
	 * called from: init()
	 * calls: void
	 * returns: html
	 */
	function HTML_nogoogle() {
		?>
		<div class="trolling">
			<p>Sorry, the credentials you supplied are not a valid pair, I cannot make the connection to your google account.</p>
			<p>Please enter the e-mail address and password correctly.</p>
		</div>
		<?php
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: validate($input)
	 * purpose: Validates and cleans input. E-mail field only accepts e-mails, etcetc
	 * called from: spae_jg::init::add_settings_section()
	 * calls: TBC
	 * returns: cleaned data
	 */
	function validate($input) {
		$remove = array('key');
		//function to remove the pageid and album from encryption if google login fails, and blank them
		$val_checkgoogle = self::google_login($input['gmail'], $input['pass']);
		if(!$val_checkgoogle) {
			array_push($remove, 'album','pageid', 'gmail', 'pass');
			$input['pageid'] = '';
			$input['album'] = '';
			$input['gmail'] = '';
			$input['pass'] = '';
		}

		$fremove = array_flip($remove);
		$to_encrypt = array_diff_key($input, $fremove);

		$encrypted = array();
		foreach ($to_encrypt as $key => $value) {
			$encrypted[$key] = Encryption::encrypt($value, $input['key']);

		}
		//$encrypted = array_map(array('Encryption','encrypt'),$to_encrypt, $key_array);
		$key_to_DB = array('key' => $input['key']);
		$to_DB = array_merge($encrypted, $key_to_DB);

		return $to_DB;
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: insert_gallery()
	 * purpose: Inserts the gallery retrieved from the album by the user to the specified page
	 * called from: TBC
	 * calls: TBC
	 * returns: html
	 */
	function insert_gallery($content) {
		self::include_zend();
		$currentid = get_the_ID();
		self::decrypt_all();
		if ($currentid != pageid ) {
			return $content;
		} else {
			$svc = Zend_Gdata_Photos::AUTH_SERVICE_NAME;
			try {
				$client = Zend_Gdata_ClientLogin::getHttpClient(email, password, $svc);
			}
			catch (Zend_Gdata_App_AuthException $e) {
				//echo "error: ". $e->getMessage();
				//echo "Sorry, e-mail and password don't match. Please correct it!";
			}
			$gphoto = new Zend_Gdata_Photos($client);

			// generate query to get album feed
			$query = $gphoto->newAlbumQuery();
			$query->setAlbumId(albumid);

			// get and parse album feed
			try
			{
				$feed = $gphoto->getAlbumFeed($query);
			}
			catch (Zend_Gdata_App_Exception $e)
			{
				echo "Error: " . $e->getResponse();
			}
			?>
			<!-- this bit is responsible for the output -->
			<?php
			// process each photo entry
			// print each entry's title, size, dimensions, tags, and thumbnail image
			$html_gallery ='<ul class="spae"><div id="picasaPictures">';
			foreach ($feed as $entry) {
				$title = $entry->getTitle();
				$summary = $entry->getSummary();
				$stuff = $entry->getMediaGroup()->getContent();
				$url = $stuff[0]->getUrl();
				$thumbnail = $entry->getMediaGroup()->getThumbnail();
				$tags = $entry->getMediaGroup()->getKeywords();
				$size = $entry->getGphotoSize();
				$height = $entry->getGphotoHeight();
				$width = $entry->getGphotoWidth();
				$html_gallery .= "<li><a href='".$url."' rel='lightbox[dude]'><img src='".$thumbnail[1]->url."' width='".$thumbnail[1]->width."' height='".$thumbnail[1]->height."'></a></li>";

			}
			$html_gallery .= "</div></ul>";

			$content = $html_gallery.$content;
			return $content;
		}
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: add_css()
	 * purpose: to add css -- will need to modify it
	 * called from: root
	 * calls: nope
	 * returns: nope
	 */

	function add_css() {
		$dir = dirname(__FILE__);
		$plugindir = str_replace(WP_PLUGIN_DIR, '', $dir);
		$absolute_css_url = WP_PLUGIN_URL . $plugindir . "/options.css";

		$css_include_relpath = str_replace(site_url(), '', $absolute_css_url);
		wp_register_style('spae_jg_admin', $css_include_relpath);
		wp_enqueue_style('spae_jg_admin');
	}

	/* ---- This is the description of the function immediately following this flag ----
	 *
	 * name: random_string()
	 * purpose: to generate a random string for a key
	 * called from:
	 * calls:
	 * returns:
	 */
	function random_string() {
		$count_int = 15;
		$tmp_num = array();
		for ($k = 0; $k <3 ; $k++) {
			if ($k != 2) {
				$tmp_num[$k] = rand(0,$count_int);
				$count_int = $count_int - $tmp_num[$k];
			} else {
				$tmp_num[$k] = $count_int;
			}
		}
		$character_set_array = array();
		$character_set_array[ ] = array( 'count' => $tmp_num[0], 'characters' => 'abcdefghijklmnopqrstuvwxyz' );
		$character_set_array[ ] = array( 'count' => $tmp_num[1], 'characters' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' );
		$character_set_array[ ] = array( 'count' => $tmp_num[2], 'characters' => '0123456789' );

		$temp_array = array();
		foreach ( $character_set_array as $character_set ) {
			for ( $i = 0; $i < $character_set[ 'count' ]; $i++ ) {
				$temp_array[ ] = $character_set[ 'characters' ][ rand( 0, strlen( $character_set[ 'characters' ] ) - 1 ) ];
			}
		}
		shuffle( $temp_array );
		return implode( '', $temp_array );
	}
}

class Encryption
{
	const CYPHER = 'blowfish';
	const MODE   = 'cfb';
	//const KEY    = '7QQvcT9Ga7R6QC3';
	//spae_jg::keyphrase;
	public function encrypt($plaintext, $key)
	{
		//$spae_vars = get_option('spae_DB'); //the DB key is needed here
		//$keyphrase2 = $spae_vars['key'];
		$td = mcrypt_module_open(self::CYPHER, '', self::MODE, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $key, $iv);
		$crypttext = mcrypt_generic($td, $plaintext);
		mcrypt_generic_deinit($td);
		return base64_encode($iv.$crypttext);
	}

	public function decrypt($deccrypttext, $key)
	{
		//$spae_vars = get_option('spae_DB'); //the DB key is needed here
		//$keyphrase2 = $spae_vars['key'];
		$crypttext = base64_decode($deccrypttext);
		$plaintext = '';
		$td        = mcrypt_module_open(self::CYPHER, '', self::MODE, '');
		$ivsize    = mcrypt_enc_get_iv_size($td);
		$iv        = substr($crypttext, 0, $ivsize);
		$crypttext = substr($crypttext, $ivsize);
		if ($iv)
		{
			mcrypt_generic_init($td, $key, $iv);
			$plaintext = mdecrypt_generic($td, $crypttext);
		}
		return $plaintext;
	}
}
?>