<?php


if (!defined( 'ABSPATH' ) || !defined( 'GOURL' )) exit;


final class gourlclass
{
	private $options 			= array(); 		// global setting values
	private $hash_url			= "";			// security; save your gourl public/private keys sha1 hash in file (db and file)
	private $errors			= array(); 		// global setting errors
	private $payments			= array(); 		// global activated payments (bitcoin, litecoin, etc)
	private $adminform          	= "gourl_adminform";
	private $admin_form_key      	= "";  		// unique form key

	private $options2 		= array(); 		// pay-per-view settings
	private $options3 		= array(); 		// pay-per-membership settings

	private $page 			= array(); 		// current page url
	private $id 			= 0; 			// current record id
	private $record 	     		= array(); 		// current record values
	private $record_errors 		= array(); 		// current record errors
	private $record_info		= array(); 		// current record messages
	private $record_fields		= array(); 		// current record fields

	private $updated			= false;		// publish 'record updated' message

	private $lock_type		= "";			// membership or view

	private $coin_names     	= array();
	private $coin_chain     	= array();
	private $coin_www       	= array();
	private $languages		= array();

	private $custom_images 		= array('img_plogin'=>'Payment Login', 'img_flogin'=>'File Download Login', 'img_sold'=>'Product Sold', 'img_pdisable'=>'Payments Disabled', 'img_fdisable'=>'File Payments Disabled', 'img_nofile'=>'File Not Exists'); // custom payment box images
	private $expiry_period 		= array('NO EXPIRY', '10 MINUTES', '20 MINUTES', '30 MINUTES', '1 HOUR', '2 HOURS', '3 HOURS', '6 HOURS', '12 HOURS', '1 DAY', '2 DAYS', '3 DAYS', '4 DAYS', '5 DAYS',  '1 WEEK', '2 WEEKS', '3 WEEKS', '4 WEEKS', '1 MONTH', '2 MONTHS', '3 MONTHS', '6 MONTHS', '12 MONTHS'); // payment expiry period
	private $store_visitorid 	= array('COOKIE','SESSION','IPADDRESS','MANUAL'); // Save auto-generated unique visitor ID in cookies, sessions or use the IP address to decide unique visitors (without use cookies)
	private $addon 				= array("gourlwoocommerce", "gourlwpecommerce", "gourljigoshop", "gourlappthemes", "gourlmarketpress", "gourlpmpro", "gourlgive", "gourledd");

	private $fields_download 	= array("fileID" => 0,  "fileTitle" => "", "active" => 1, "fileName"  => "", "fileUrl"  => "", "fileText" => "", "fileSize" => 0, "priceUSD"  => "0.00", "priceCoin"  => "0.0000", "priceLabel"  => "BTC", "purchases"  => "0", "userFormat"  => "COOKIE", "expiryPeriod" => "2 DAYS", "lang"  => "en", "defCoin" => "", "defShow" => 0, "image"  => "", "imageWidth" => 200,  "priceShow" => 1, "paymentCnt" => 0, "paymentTime" => "", "updatetime"  => "", "createtime"  => "");
	private $fields_product 	= array("productID" => 0,  "productTitle" => "", "active" => 1,"priceUSD"  => "0.00", "priceCoin"  => "0.0000", "priceLabel"  => "BTC", "purchases"  => "0", "expiryPeriod" => "NO EXPIRY", "lang"  => "en", "defCoin" => "", "defShow" => 0, "productText"  => "", "finalText" => "", "emailUser" => 0, "emailUserFrom" => "", "emailUserTitle" => "", "emailUserBody" => "", "emailAdmin" => 0, "emailAdminFrom" => "", "emailAdminTitle" => "", "emailAdminBody" => "", "emailAdminTo" => "", "paymentCnt" => 0, "paymentTime" => "", "updatetime"  => "", "createtime"  => "");

	private $fields_view 		= array("ppvPrice" => "0.00", "ppvPriceCoin" => "0.0000", "ppvPriceLabel" => "BTC", "ppvExpiry" => "1 DAY", "ppvLevel"  => 0, "ppvLang" => "en", "ppvCoin"  => "", "ppvOneCoin"  => "", "ppvTextAbove"  => "", "ppvTextBelow"  => "", "ppvTitle" => "", "ppvTitle2" => "", "ppvCommentAuthor"  => "", "ppvCommentBody"  => "", "ppvCommentReply"  => "");
	private $expiry_view		= array("2 DAYS", "1 DAY", "12 HOURS", "6 HOURS", "3 HOURS", "2 HOURS", "1 HOUR");
	private $lock_level_view 	= array("Unregistered Visitors", "Unregistered Visitors + Registered Subscribers", "Unregistered Visitors + Registered Subscribers/Contributors", "Unregistered Visitors + Registered Subscribers/Contributors/Authors");

	private $fields_membership 		= array("ppmPrice" => "0.00", "ppmPriceCoin" => "0.0000", "ppmPriceLabel" => "BTC", "ppmExpiry" => "1 MONTH", "ppmLevel"  => 0, "ppmProfile" => 0, "ppmLang" => "en", "ppmCoin"  => "", "ppmOneCoin"  => "", "ppmTextAbove"  => "", "ppmTextBelow"  => "", "ppmTextAbove2"  => "", "ppmTextBelow2"  => "", "ppmTitle" => "", "ppmTitle2" => "", "ppmCommentAuthor"  => "", "ppmCommentBody"  => "", "ppmCommentReply"  => "");
	private $fields_membership_newuser 	= array("userID" => 0, "paymentID" => 0, "startDate"  => "", "endDate" => "", "disabled" => 0, "recordCreated"  => "");
	private $lock_level_membership 	= array("Registered Subscribers", "Registered Subscribers/Contributors", "Registered Subscribers/Contributors/Authors");



	/*
	 *  1. Initialize plugin
	 */
	public function __construct()
	{
		// --------------------------------------

		// path to images/js/php files. Use in gourl payment library class cryptobox.class.php
		DEFINE("CRYPTOBOX_PHP_FILES_PATH",    plugins_url('/includes/', __FILE__));      // path to directory with files: cryptobox.class.php / cryptobox.callback.php / cryptobox.newpayment.php;
		DEFINE("CRYPTOBOX_IMG_FILES_PATH",    plugins_url('/images/coins/', __FILE__));  // path to directory with coin image files (directory '/images' by default)
		DEFINE("CRYPTOBOX_JS_FILES_PATH",     plugins_url('/js/', __FILE__));		      // path to directory with files: ajax.min.js/support.min.js

		$val1 = "vlng";
		$val2 = "vcni";
		$val3 = substr(strtolower(preg_replace("/[^a-zA-Z]+/", "", base64_encode(home_url('/', 'http')))), -7, 5)."_";
		if (!$val3 || strlen($val3) < 5) $val3 = "vprf_";
		if (is_admin())
		{
		    $val1 = "gourlcryptolang";
		    $val2 = "gourlcryptocoin";
		    $val3 = "acrypto_";
		}
		DEFINE("CRYPTOBOX_LANGUAGE_HTMLID",   $val1);	    // language selection list html id; any value
		DEFINE("CRYPTOBOX_COINS_HTMLID",      $val2);	    // coins selection list html id; any value
		DEFINE("CRYPTOBOX_PREFIX_HTMLID",     $val3);	    // prefix for all html elements; any value


		// security data hash; you can change path / file location
		$this->hash_url = GOURL_PHP."/gourl.hash";

		// admin form
		$this->adminform      	= "gourl_adminform_" . md5(sha1(AUTH_KEY.NONCE_KEY.AUTH_KEY));
		$this->admin_form_key	= 'gourl_adminformkey_' . sha1(md5(AUTH_KEY.NONCE_KEY));

		$this->coin_names 	= self::coin_names();
		$this->coin_chain 	= self::coin_chain();
		$this->coin_www 		= self::coin_www();
		$this->languages 		= self::languages();

		// compatible test
		$ver 		= get_option(GOURL.'version');         //  current plugin version; '-empty-' if you unistalled plugin
		$prevver 	= get_option(GOURL.'prev_version');	   //  current plugin version; ; keep version value when plugin uninstalled; $ver ==  $prevver if plugin activated
		if (!$ver || version_compare($ver, GOURL_VERSION) < 0 || version_compare($prevver, $ver) < 0) $this->upgrade();
		elseif (is_admin()) gourl_retest_dir();



		// Current Page, Record ID
		$this->page = (isset($_GET['page'])) ? substr(preg_replace("/[^A-Za-z0-9\_\-]+/", "", $_GET['page']), 0, 50) : "";
		$this->id 	= (isset($_GET['id']) && intval($_GET['id'])) ? intval($_GET['id']) : 0;

		$this->updated = (isset($_GET['updated']) && $_GET["updated"] == "true") ? true : false;


		// Redirect
		if ($this->page == GOURL."contact") { header("Location: ".GOURL_ADMIN.GOURL."#i7"); die; }
		if ($this->page == GOURL."addons") { header("Location: ".GOURL_ADMIN.GOURL."#j2"); die; }


		// A. General Plugin Settings
		$this->get_settings();
		if (!($_POST && $this->page == GOURL.'settings')) $this->check_settings();


		// B. Pay-Per-Download - New File
		if ($this->page == GOURL.'file' && is_admin())
		{
			$this->record_fields = $this->fields_download;
			$this->get_record('file');
			if ($this->id && !$_POST) $this->check_download();
			ini_set('max_execution_time', 3600);
			ini_set('max_input_time', 3600);
		}


		// C. Pay-Per-View
		if ($this->page == GOURL.'payperview' && is_admin())
		{
			$this->get_view();
			if (!$_POST) $this->check_view();
		}


		// D. Pay-Per-Membership
		if ($this->page == GOURL.'paypermembership' && is_admin())
		{
			$this->get_membership();
			if (!$_POST) $this->check_membership();
		}


		// E. Pay-Per-Membership - New User
		if ($this->page == GOURL.'paypermembership_user' && is_admin())
		{
			$this->record_fields = $this->fields_membership_newuser;
			if (!$this->id) // default for new record
			{
				$this->record["startDate"] = gmdate("Y-m-d");
				$this->record["endDate"] = gmdate("Y-m-d", strtotime("+1 month"));
				if (isset($_GET['userID']) && intval($_GET['userID'])) $this->record["userID"] = intval($_GET['userID']);
			}
		}


		// F. Pay-Per-Product - New Product
		if ($this->page == GOURL.'product' && is_admin())
		{
			$this->record_fields = $this->fields_product;
			$this->get_record('product');
			if ($this->id && !$_POST) $this->check_product();
		}


		// Admin
		if (is_admin())
		{
			if ($this->errors && $this->page != 'gourlsettings') add_action('admin_notices', array(&$this, 'admin_warning'));
			if (!file_exists(GOURL_DIR."files") || !file_exists(GOURL_DIR."images") || !file_exists(GOURL_DIR."lockimg") || !file_exists(GOURL_PHP)) add_action('admin_notices', array(&$this, 'admin_warning_reactivate'));
			add_action('admin_menu', 			array(&$this, 'admin_menu'));
			add_action('init', 					array(&$this, 'admin_init'));
			add_action('admin_head', 			array(&$this, 'admin_header'), 15);
			add_filter('plugin_row_meta',       array(&$this, 'admin_plugin_meta'), 10, 2 );

			if (strpos($this->page, GOURL) === 0)  add_action("admin_enqueue_scripts", array(&$this, "admin_scripts"));

			if (in_array($this->page, array("gourl", "gourlpayments", "gourlproducts", "gourlproduct", "gourlfiles", "gourlfile", "gourlpayperview", "gourlpaypermembership", "gourlpaypermembership_users", "gourlpaypermembership_user", "gourlsettings"))) add_action('admin_footer_text', array(&$this, 'admin_footer_text'), 15);
		}
		else
		{
			add_action("init", 					array(&$this, "front_init"));
			add_action("wp", 					array(&$this, "front_html"));
			add_action("wp_enqueue_scripts",    array(&$this, "front_scripts"));

			add_shortcode(GOURL_TAG_DOWNLOAD, 	array(&$this, "shortcode_download"));
			add_shortcode(GOURL_TAG_PRODUCT, 	array(&$this, "shortcode_product"));
			add_shortcode(GOURL_TAG_VIEW, 	  	array(&$this, "shortcode_view"));
			add_shortcode(GOURL_TAG_MEMBERSHIP, array(&$this, "shortcode_membership"));
			add_shortcode(GOURL_TAG_MEMCHECKOUT,array(&$this, "shortcode_memcheckout"));
		}


		// Process Callbacks from GoUrl.io Payment Server
		add_action('parse_request', array(&$this, 'callback_parse_request'), 1);


		// Force Login - external plugins
		add_filter('v_forcelogin_whitelist', array(&$this, "v_forcelogin_whitelist"), 10, 1); // https://wordpress.org/plugins/wp-force-login/


		// Exclude gourl js file from aggregation
		add_filter('autoptimize_filter_js_exclude', array(&$this, "exclude_js_file"), 10, 1);

		// Disable BJ Lazy Load  iframes
		$bj_lazy_load_options = get_option('bj_lazy_load_options');
		if ($bj_lazy_load_options && is_array($bj_lazy_load_options)) update_option('bj_lazy_load_options', array_merge( $bj_lazy_load_options, array("lazy_load_iframes" => "no") ));

	}


	/*
	 *  2.
	 */
	public function admin_scripts()
	{
	    wp_enqueue_style ( 'cr-style-admin',   plugins_url('/css/style.admin.css?', __FILE__), array(), GOURL_VERSION);
	    wp_enqueue_style ( 'cr-style',         plugins_url('/css/style.front.css', __FILE__), array(), GOURL_VERSION);
	    wp_enqueue_style ( 'cr-font',          "//fonts.googleapis.com/css?family=Tenor+Sans", array(), null );

	    return true;
	}


	/*
	 *  3.
	 */
	public function front_scripts()
	{
	    wp_enqueue_style ( 'cr-style',         plugins_url('/css/style.front.css', __FILE__) );

	    return true;
	}


	/*
	 *  6.
	 */
	public function iframe_scripts()
	{
	    $tmp = "<script type='text/javascript' src='".plugins_url("/js/cryptobox.min.js?ver=".GOURL_VERSION, __FILE__)."'></script>";

	    return $tmp;
	}


	/*
	 *  7.
	 */
	public function bootstrap_scripts()
	{
	    $theme = $this->options['box_theme'];

	    if ($theme == "black")          $css =  plugins_url("/css/darkly.min.css", __FILE__); // original https://bootswatch.com/4/darkly/bootstrap.css
	    elseif ($theme == "greyred")    $css =  plugins_url("/css/superhero.min.css", __FILE__);
	    elseif ($theme == "greygreen")  $css =  plugins_url("/css/solar.min.css", __FILE__);
	    elseif ($theme == "whiteblue")  $css =  plugins_url("/css/cerulean.min.css", __FILE__); // original https://bootswatch.com/4/cerulean/bootstrap.css
	    elseif ($theme == "whitered")   $css =  plugins_url("/css/united.min.css", __FILE__);
	    elseif ($theme == "whitegreen") $css =  plugins_url("/css/flatly.min.css", __FILE__);
	    elseif ($theme == "whiteblack") $css =  plugins_url("/css/lux.min.css", __FILE__);
	    elseif ($theme == "whitepurple")$css =  plugins_url("/css/pulse.min.css", __FILE__);
	    elseif ($theme == "litera")     $css =  plugins_url("/css/litera.min.css", __FILE__);
	    elseif ($theme == "minty")      $css =  plugins_url("/css/minty.min.css", __FILE__);
	    elseif ($theme == "sandstone")  $css =  plugins_url("/css/sandstone.min.css", __FILE__);
	    elseif ($theme == "sketchy")    $css =  plugins_url("/css/sketchy.min.css", __FILE__);
	    else                            $css =  plugins_url("/css/bootstrapcustom.min.css", __FILE__);


	    $tmp  = "<link rel='stylesheet' id='cr-bootstrapcss-css'  href='".$css."' type='text/css' media='all' />";
	    $tmp .= "<script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js' crossorigin='anonymous'></script>";
	    $tmp .= "<script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js' crossorigin='anonymous'></script>";
	    $tmp .= "<script type='text/javascript' src='https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js' crossorigin='anonymous'></script>";
	    $tmp .= "<script type='text/javascript' src='https://use.fontawesome.com/releases/v5.0.9/js/all.js' crossorigin='anonymous'></script>";
	    $tmp .= "<script type='text/javascript' src='".plugins_url("/js/support.min.js?ver=".GOURL_VERSION, __FILE__)."' crossorigin='anonymous'></script>";

	    return $tmp;
	}


	/*
	 *  8.
	 */
	public function payments()
	{
		return $this->payments;
	}


	/*
	 *  9.
	*/
	public static function coin_names()
	{
		return array('BTC' => 'bitcoin', 'BCH' => 'bitcoincash', 'BSV' => 'bitcoinsv', 'LTC' => 'litecoin', 'DASH' => 'dash', 'DOGE' => 'dogecoin', 'SPD' => 'speedcoin', 'RDD' => 'reddcoin', 'POT' => 'potcoin', 'FTC' => 'feathercoin', 'VTC' => 'vertcoin', 'PPC' => 'peercoin', 'MUE' => 'monetaryunit');
	}


	/*
	 *  10.
	*/
	public static function coin_chain()
	{
		return array('bitcoin' => 'https://www.blockchain.com/btc/', 'bitcoincash' => 'https://www.blockchain.com/bch/', 'bitcoinsv' => 'https://bchsvexplorer.com/', 'litecoin' => 'https://chainz.cryptoid.info/ltc/', 'dash' => 'https://chainz.cryptoid.info/dash/', 'dogecoin' => 'https://dogechain.info/', 'speedcoin' => 'http://speedcoin.org:2750/', 'reddcoin' => 'http://live.reddcoin.com/', 'potcoin' => 'https://chainz.cryptoid.info/pot/', 'feathercoin' => 'https://chainz.cryptoid.info/ftc/', 'vertcoin' => 'https://chainz.cryptoid.info/vtc/', 'peercoin' => 'https://chainz.cryptoid.info/ppc/', 'monetaryunit' => 'https://chainz.cryptoid.info/mue/');
	}


	/*
	 *  11.
	*/
	public static function coin_www()
	{
		return array('bitcoin' => 'https://bitcoin.org/', 'bitcoincash' => 'https://www.bitcoincash.org/', 'bitcoinsv' => 'https://bitcoinsv.io/', 'litecoin' => 'https://litecoin.org/', 'dash' => 'https://www.dashpay.io/', 'dogecoin' => 'http://dogecoin.com/', 'speedcoin' => 'https://speedcoin.org/', 'reddcoin' => 'http://reddcoin.com/', 'potcoin' => 'http://www.potcoin.com/', 'feathercoin' => 'https://www.feathercoin.com/', 'vertcoin' => 'http://vertcoin.org/', 'peercoin' => 'http://peercoin.net/', 'monetaryunit' => 'http://www.monetaryunit.org/');
	}


	/*
	 *  12.
	*/
	public static function languages()
	{
		return array('en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German', 'it' => 'Italian', 'nl' => 'Dutch', 'ru' => 'Russian', 'sv' => 'Swedish', 'sq' => 'Albanian', 'ar' => 'Arabic', 'cn' => 'Simplified Chinese', 'zh' => 'Traditional Chinese', 'cs' => 'Czech', 'et' => 'Estonian', 'fi' => 'Finnish', 'el' => 'Greek', 'hi' => 'Hindi', 'id' => 'Indonesian', 'ja' => 'Japanese', 'ko' => 'Korean', 'fa' => 'Persian', 'pl' => 'Polish', 'pt' => 'Portuguese', 'sr' => 'Serbian', 'sl' => 'Slovenian', 'tr' => 'Turkish');
	}


	/*
	 *  13.
	*/
	public function box_width()
	{
		return $this->options['box_width'];
	}


	/*
	 *  14.
	*/
	public function box_height()
	{
		return $this->options['box_height'];
	}


	/*
	 *  15. Return payment box custom image (need login / payment box disabled / etc)
	*/
	public function box_image($type = "plogin") // plogin, flogin, sold, pdisable, fdisable, nofile
	{
		$type = "img_" . $type;
		if (!isset($this->custom_images[$type])) return "";

		if ($this->options[$type] == 1 && $this->options[$type."url"] && file_exists(GOURL_DIR."box/".$this->options[$type.'url']))
			return GOURL_DIR2."box/".$this->options[$type.'url'];
		else
			return plugins_url("/images/".$type.".png", __FILE__);
	}



	/*
	 *  15b. Return your company logo for payment box
	 */
	public function box_logo()
	{
	    if ($this->options['boxlogo'] == 1) return plugins_url('/images/your_logo.png', __FILE__);
	    elseif ($this->options['boxlogo'] == 2 && $this->options['boxlogo_url'] && file_exists(GOURL_DIR."box/".$this->options['boxlogo_url'])) return GOURL_DIR2."box/".$this->options['boxlogo_url'];
        else return "";
	}



	/*
	 *  15c.
	 */
	public function currencyconverterapi_key()
	{
	    return $this->options['currencyconverterapi_key'];
	}



	/*
	 *  16. Return transaction url to block explorer
	*/
	public function blockexplorer_tr_url($txID, $coinName)
	{
	    $coinName = strtolower($coinName);

	    if (!isset($this->coin_chain[$coinName])) return "";

	    $explorer = $this->coin_chain[$coinName];
	    $url = $explorer . (stripos($explorer,'cryptoid.info') ? 'tx.dws?' : (stripos($explorer,'blockdozer.com') ? 'tx/' : 'tx/')) . $txID;

		return $url;
	}


	/*
	 *  17. Return address url to block explorer
	 */
	public function blockexplorer_addr_url($address, $coinName)
	{
	    $coinName = strtolower($coinName);

	    if (!isset($this->coin_chain[$coinName])) return "";

	    $explorer = $this->coin_chain[$coinName];
	    $url = $explorer . (stripos($explorer,'cryptoid.info') ? 'address.dws?' : (stripos($explorer,'bchain.info') ? 'addr/' : (stripos($explorer,'blockdozer.com') ? 'address/' : 'address/'))) . $address;

	    return $url;
	}


	/*
	 *  18.
	*/
	public function page_summary()
	{
		global $wpdb;



		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title(__('Summary', GOURL).$this->space(1).'<a class="add-new-h2" target="_blank" href="https://gourl.io/bitcoin-wordpress-plugin.html">' . __('version', GOURL).' '.GOURL_VERSION.'</a>');

		$tmp .= "<div class='postbox'>";
		$tmp .= "<div class='inside gourlsummary'>";

		foreach($this->coin_names as $k => $v)  $tmp .= '<a target="_blank" href="'.$this->coin_www[$v].'"><img width="70" hspace="20" vspace="15" alt="'.$v.'" src="'.plugins_url('/images/'.$v.'2.png', __FILE__).'" border="0"></a>';

		// 1
		$us_products = "";
		$dt_products = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt from crypto_products", OBJECT);
		$all_products = ($res) ? $res->cnt : 0;
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID like 'product\_%'", OBJECT);
		$tr_products = ($res) ? $res->cnt : 0;
		if ($tr_products)
		{
			$us_products = " ( $" . gourl_number_format($res->total, 2) . " )";
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID like 'product\_%' order by txDate desc", OBJECT);
			$dt_products = "<span title='".__('Latest Payment to Pay-Per-Product', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
			"<a title='".__('Latest Payment', GOURL)."' href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}

		// 2
		$us_files = "";
		$dt_files = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt from crypto_files", OBJECT);
		$all_files = ($res) ? $res->cnt : 0;
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID like 'file\_%'", OBJECT);
		$tr_files = ($res) ? $res->cnt : 0;
		if ($tr_files)
		{
			$us_files = " ( $" . gourl_number_format($res->total, 2) . " )";
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID like 'file\_%' order by txDate desc", OBJECT);
			$dt_files = "<span title='".__('Latest Payment to Pay-Per-Download', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
			"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}

		// 3
		$us_membership = "";
		$dt_membership = "";
		$dt = gmdate('Y-m-d H:i:s');
		$res = $wpdb->get_row("SELECT count(distinct userID) as cnt from crypto_membership where startDate <= '$dt' && endDate >= '$dt' && disabled = 0", OBJECT);
		$all_users = ($res) ? $res->cnt : 0;
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID like 'membership%'", OBJECT);
		$tr_membership = ($res) ? $res->cnt : 0;
		if ($tr_membership)
		{
			$us_membership = " ( $" . gourl_number_format($res->total, 2) . " )";
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID like 'membership%' order by txDate desc", OBJECT);
			$dt_membership = "<span title='".__('Latest Payment to Pay-Per-Membership', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
			"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}

		// 4
		$us_payperview = "";
		$dt_payperview = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID = 'payperview'", OBJECT);
		$tr_payperview = ($res) ? $res->cnt : 0;
		if ($tr_payperview)
		{
			$us_payperview = " ( $" . gourl_number_format($res->total, 2) . " )";
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID = 'payperview' order by txDate desc", OBJECT);
			$dt_payperview = "<span title='".__('Latest Payment to Pay-Per-View', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
			"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}

		// 5
		$sql_where = "";
		$us_addon = $dt_addon = $tr_addon = array();
		foreach ($this->addon as $v)
		{
			$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID like '".esc_sql($v).".%'", OBJECT);
			$tr_addon[$v] = ($res) ? $res->cnt : 0;
			if ($tr_addon[$v])
			{
				$us_addon[$v] = " ( $" . gourl_number_format($res->total, 2) . " )";
				$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID like '".esc_sql($v).".%' order by txDate desc", OBJECT);
				$dt_addon[$v] = "<span title='".__('Latest Payment', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
				($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
				"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
			}
			$sql_where .= " && orderID not like '".esc_sql($v).".%'";
		}

		// 6
		$us_other = "";
		$dt_other = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where orderID like '%.%'".$sql_where, OBJECT);
		$tr_other = ($res) ? $res->cnt : 0;
		if ($tr_other)
		{
			$us_other = " ( $" . gourl_number_format($res->total, 2) . " )";
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where orderID like '%.%' ".$sql_where." order by txDate desc", OBJECT);
			$dt_other = "<span title='".__('Latest Payment to Other Plugins', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='16' border='0' style='margin-right:9px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
			"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}

		// 7
		$us_unrecognised = "";
		$dt_unrecognised = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments where unrecognised = 1", OBJECT);
		$tr_unrecognised = ($res) ? $res->cnt : 0;
		if ($tr_unrecognised)
		{
			$us_unrecognised = " ( $" . gourl_number_format($res->total, 2) . " )";
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments where unrecognised = 1 order by txDate desc", OBJECT);
			$dt_unrecognised = "<span title='".__('Unrecognised Latest Payment', GOURL)."'>".$this->space(2).$res->dt.$this->space()."-".$this->space().
			"<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . "</span>";
		}

		// 8
		$all_details = "";
		$dt_last = "";
		$res = $wpdb->get_row("SELECT count(*) as cnt, sum(amountUSD) as total from crypto_payments", OBJECT);
		$all_payments = ($res) ? $res->cnt : 0;
		if ($all_payments)
		{
			$all_details .= $this->space()."~ ".gourl_number_format($res->total, 2)." ".__('USD', GOURL);
			$res = $wpdb->get_row("SELECT paymentID, amount, coinLabel, amountUSD, countryID, DATE_FORMAT(txDate, '%d %b %Y, %H:%i %p') as dt from crypto_payments order by txDate desc", OBJECT);
			$dt_last = ($res->countryID?"<a href='".GOURL_ADMIN.GOURL."payments&s=".$res->countryID."'><img width='20' border='0' style='margin-right:13px' alt='".$res->countryID."' src='".plugins_url('/images/flags/'.$res->countryID.'.png', __FILE__)."' border='0'></a>":"") .
						$res->dt.$this->space()."-".$this->space()."<a title='".__('Latest Payment', GOURL)."' href='".GOURL_ADMIN.GOURL."payments&s=payment_".$res->paymentID."'>" . gourl_number_format($res->amount, 4) . "</a> " . $res->coinLabel . $this->space() . "<small>( " . gourl_number_format($res->amountUSD, 2)." ".__('USD', GOURL). " )</small>";
		}


		// Re-test MySQL connection
		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
		$sql = "SELECT fileID as nme FROM crypto_files LIMIT 1";
		run_sql($sql);


		$tmp .= "<a name='i1'></a>";
		$tmp .= "<div class='gourltitle'>".__('Summary', GOURL)."</div>";
		$tmp .= "<div class='gourlsummaryinfo'>";
		$tmp .= '<div style="min-width:1200px;width:100%;">';

		$tmp .= "<table border='0'>";

		if ($tr_products || $tr_files || $tr_membership || $tr_payperview || !$all_payments)
		{
			// 1
			$tmp .= "<tr><td>GoUrl Pay-Per-Product</td><td><a href='".GOURL_ADMIN.GOURL."products'>".sprintf(__('%s  paid products', GOURL), $all_products)."</a></td>
					<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=products'>".$tr_products."</a> ".__('payments', GOURL).$us_products."</small></td><td><small>".$dt_products."</small></td></tr>";
			// 2
			$tmp .= "<tr><td>GoUrl Pay-Per-Download</td><td><a href='".GOURL_ADMIN.GOURL."files'>".sprintf(__('%s  paid files', GOURL), $all_files)."</a></td>
					<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=files'>".$tr_files."</a> ".__('payments', GOURL).$us_files."</small></td><td><small>".$dt_files."</small></td></tr>";
			// 3
			$tmp .= "<tr><td>GoUrl Pay-Per-Membership</td><td><a href='".GOURL_ADMIN.GOURL."paypermembership_users&s=active'>".sprintf(__('%s  premium users', GOURL), $all_users)."</a></td>
					<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=membership'>".$tr_membership."</a> ".__('payments', GOURL).$us_membership."</small></td><td><small>".$dt_membership."</small></td></tr>";
			// 4
			$tmp .= "<tr><td>GoUrl Pay-Per-View</td><td></td>
					<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=payperview'>".$tr_payperview."</a> ".__('payments', GOURL).$us_payperview."</small></td><td><small>".$dt_payperview."</small></td></tr>";
		}

		// 5
		foreach ($us_addon as $k => $v)
		{
			if ($k == "gourlwoocommerce") 		$nme = "GoUrl WooCommerce";
			elseif ($k == "gourlwpecommerce") 	$nme = "GoUrl WP eCommerce";
			elseif ($k == "gourljigoshop") 		$nme = "GoUrl Jigoshop";
			elseif ($k == "gourlappthemes") 	$nme = "GoUrl AppThemes";
			elseif ($k == "gourlmarketpress") 	$nme = "GoUrl MarketPress";
			elseif ($k == "gourlpmpro") 		$nme = "GoUrl Paid Memberships Pro";
			elseif ($k == "gourlgive") 			$nme = "GoUrl Give/Donations";
			elseif ($k == "gourledd") 			$nme = "GoUrl Easy Digital Downloads";
			elseif (strpos($k, "gourl") === 0) 	$nme = "GoUrl " . ucfirst(str_replace("gourl", "", $k));
			else 								$nme = ucfirst($k);

			$tmp .= "<tr><td>".$nme."</td><td></td>
				<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=".$k."'>".$tr_addon[$k]." ".__('payments', GOURL)."</a> ".$us_addon[$k]."</small></td><td><small>".$dt_addon[$k]."</small></td></tr>";
		}

		// 6
		$tmp .= "<tr><td>".__('Other Plugins with GoUrl', GOURL)."</td><td></td>
				<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=plugins'>".$tr_other." ".__('payments', GOURL)."</a> ".$us_other."</small></td><td><small>".$dt_other."</small></td></tr>";
		// 7
		$tmp .= "<tr><td>".__('Unrecognised Payments', GOURL)."</td><td></td>
				<td><small><a href='".GOURL_ADMIN.GOURL."payments&s=unrecognised'>".$tr_unrecognised." ".__('payments', GOURL)."</a> ".$us_unrecognised."</small></td><td><small>".$dt_unrecognised."</small></td></tr>";
		// 8
		$tmp .= "<tr><td><small>---------</small><br>".__('Total Received', GOURL)."</td><td colspan='2'><br><a href='".GOURL_ADMIN.GOURL."payments'>".sprintf(__('%s payments', GOURL), $all_payments)."</a>".$all_details."</td></tr>";
		$tmp .= "<tr><td><a name='chart' id='chart'></a>".__('Recent Payment', GOURL)."</td><td colspan='3'>".$dt_last."</td></tr>";
		$tmp .= "</table>";

		$charts = array('BTC' => 7777, 'LTC' => 3, 'DOGE' => 132, 'DASH' => 155, 'RDD' => 169, 'POT' => 173, 'FTC' => 5, 'VTC' => 151, 'VRC' => 209, 'PPC' => 28);
		$chart = (isset($_GET["chart"]) && isset($charts[$_GET["chart"]])) ? substr($_GET["chart"], 0, 10) : "BTC";

		$days = array(5=>"5 days", 10=>"10 days", 15=>"15 days", 31=>"1 month", 60=>"2 months", 90=>"3 months",120=>"4 months",180=>"6 months",240=>"9 months",360=>"1 year");
		$day = (isset($_GET["days"]) && isset($days[$_GET["days"]])) ? intval($_GET["days"]) : 120;

		$tmp .= "<div style='margin:90px 0 30px 0;height:auto;'>";
		$tmp .= "<iframe width='1200' height='500' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' src='https://myip.ms/crypto.php?m=".$charts[$chart]."&amp;d=".$day."&amp;a=2&amp;c18=dddddd&amp;c19=dddddd&amp;h=500&amp;w=1200&amp;t=usd".($this->options['chart_reverse']?"":"&amp;r=1")."'></iframe>";
		$tmp .= "<div>";
		// $tmp .= '<select id="'.GOURL.'chart" onchange="window.location.href = \''.admin_url('admin.php?page='.GOURL.'&amp;days='.$day).'&amp;chart=\'+this.options[this.selectedIndex].value+\'#chart\';">';
		// foreach($this->coin_names as $k => $v) if (isset($charts[$k])) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $chart).'>'.ucfirst($v).$this->space().'('.$k.')</option>';
		// $tmp .= '</select>';
		$tmp .= '<select id="'.GOURL.'days" onchange="window.location.href = \''.admin_url('admin.php?page='.GOURL.'&amp;chart='.$chart).'&amp;days=\'+this.options[this.selectedIndex].value+\'#chart\';">';
		foreach($days as $k => $v) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $day).'>'.__($v, GOURL).'</option>';
		$tmp .= '</select>' . $this->space(3);
		$tmp .= "<a class='".GOURL."smalltext' target='_blank' href='https://gourl.io/bitcoin-payment-gateway-api.html'>".__("GoUrl Live Currency Rates", GOURL)." &#187</a>";
		$tmp .="</div>";
		$tmp .="</div>";

		$tmp .="</div></div>";

		$tmp .= "<div class='gourlimgphone'><a target='_blank' href='https://gourl.io/'><img src='".plugins_url('/images/screen.png', __FILE__)."' border='0'></a></div>";


		$tmp .= "<a name='i2'></a>";
		$tmp .= "<br><br><br><br>";
		$tmp .= "<div class='gourltitle'>".__('What Makes Us Unique', GOURL)."</div>";

		$tmp .="<div class='gourllist'>";

		$img  = "<img title='".__('Example', GOURL)."' class='gourlimgpreview' src='".plugins_url('/images/example.png', __FILE__)."' border='0'>";
		$tmp .= "<ul>";
		$tmp .= "<li> ".sprintf(__("100%% Free Open Source on <a target='_blank' href='%s'>Github.com</a>", GOURL), "https://github.com/cryptoapi/")."</li>";
		$tmp .= '<li> '.sprintf(__("No Monthly Fee, Transaction Fee from 0%%. Set your own prices in USD, <a href='%s'>EUR, GBP, RUB, AUD (100 currencies)</a>", GOURL), 'https://wordpress.org/plugins/gourl-woocommerce-bitcoin-altcoin-payment-gateway-addon/').'</li>';
		$tmp .= "<li> ".sprintf(__("No ID Required, No Bank Account Needed. Global, Anonymous, Secure, No Chargebacks, Zero Risk", GOURL), "https://gourl.io/#usd")."</li>";
		$tmp .= "<li> ".sprintf(__("Get payments straight to your bitcoin/altcoin wallets and convert to <a target='_blank' href='%s'>USD/EUR/etc</a> later. All in automatic mode", GOURL), "https://gourl.io/#usd")."</li>";
		$tmp .= '<li> '.sprintf(__("<a href='%s'>Pay-Per-Download</a> - simple solution for your <b>unregistered</b> visitors: make money on file downloads", GOURL), GOURL_ADMIN.GOURL.'files')." <a target='_blank' href='https://gourl.io/lib/examples/pay-per-download-multi.php'>".$img."</a></li>";
		$tmp .= '<li> '.sprintf(__("<a href='%s'>Pay-Per-View/Page</a> - for your <b>unregistered</b> visitors: offer paid access to your premium content/videos", GOURL), GOURL_ADMIN.GOURL.'payperview')." <a target='_blank' href='https://gourl.io/lib/examples/pay-per-page-multi.php'>".$img."</a></li>";
		$tmp .= '<li> '.sprintf(__("<a href='%s'>Pay-Per-Membership</a> - for your <b>registered users</b>: offer paid access to your premium content, custom <a href='%s'>actions</a>", GOURL), GOURL_ADMIN.GOURL.'paypermembership', plugins_url("/images/dir/membership_actions.txt", __FILE__))." <a target='_blank' href='https://gourl.io/lib/examples/pay-per-membership-multi.php'>".$img."</a></li>";
		$tmp .= '<li> '.sprintf(__("<a href='%s'>Pay-Per-Product</a> - advanced solution for your <b>registered users</b>: sell any products on website, invoices with buyer confirmation email, etc", GOURL), GOURL_ADMIN.GOURL.'products')." <a target='_blank' href='https://gourl.io/lib/examples/pay-per-product-multi.php'>".$img."</a></li>";
		$tmp .= '<li> '.__("<a href='#addon'>Working with third-party plugins</a> - good support for third party plugins (WoCommerce, Jigoshop, bbPress, AppThemes, etc)", GOURL).'</li>';
		$tmp .= '<li> '.__("Support payments in Bitcoin, Bitcoin Cash, Bitcoin SV, Litecoin, Dash, Dogecoin, Speedcoin, Reddcoin, Potcoin, Feathercoin, Vertcoin, Peercoin, MonetaryUnit", GOURL).'</li>';
		$tmp .= '<li> '.__("<b>Auto Synchronization</b> - between payments data stored on your GoUrl.io account and your Website. If GoUrl attempts to deliver a payment notification/transaction confirmation but your website is unavailable, the notification is stored on the queue, and delivered to the your website when it becomes available (re-check connection with your website every hour)", GOURL).'</li>';
		$tmp .= '<li> '.sprintf(__("Free <a href='%s'>Plugin Support</a> and <a href='#addon'>Free Add-ons</a> for You", GOURL), "https://gourl.io/view/contact/Contact_Us.html").'</li>';
		$tmp .= "</ul>";

		$tmp .= "<a name='j2'></a>";
		$tmp .= "</div>";

		$tmp .= "<a name='addon'></a>";
		$tmp .= "<br><br><br><br>";
		$tmp .= "<div class='gourltitle'>".__('Free Bitcoin Gateway Add-ons', GOURL)."</div>";
		$tmp .= "<p>".__('The following Add-ons extend the functionality of GoUrl -', GOURL)."</p>";
		$tmp .= '<p><a style="margin-left:20px" target="_blank" href="https://wordpress.org/plugins/search/gourl/" class="button-primary">'.__('All Add-ons on Wordpress.prg', GOURL).'<span class="dashicons dashicons-external"></span></a>';
		$tmp .= '<a style="margin-left:30px" href="'.admin_url('plugin-install.php?tab=search&type=author&s=gourl').'" class="button-primary">'.__("View on 'Add Plugins' Page", GOURL).'<span class="dashicons dashicons-external"></span></a>';
		$tmp .= "</p>";

		$tmp .= "<table class='gourltable gourltable-addons'>";
		$tmp .= "<tr><th style='width:10px'></th><th>".__('Bitcoin/Altcoin Gateway', GOURL)."</th><th style='padding-left:60px'>".__('Description', GOURL)."</th><th>".__('Homepage', GOURL)."</th><th>".__('Wordpress.org', GOURL)."</th><th>".__('Installation pages', GOURL)."</th></tr>";
		$tmp .= "<tr><td class='gourlnum'>1.</td><td><a target='_blank' href='https://wordpress.org/plugins/woocommerce/'><img src='".plugins_url('/images/logos/woocommerce.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".sprintf(__("Provides a GoUrl Bitcoin/Altcoin Payment Gateway for wordpress E-Commerce - <a target='_blank' href='%s'>WooCommerce 2.1+</a>", GOURL), "https://wordpress.org/plugins/woocommerce/")."</td><td><a target='_blank' href='https://gourl.io/bitcoin-payments-woocommerce.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-payments-woocommerce.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-woocommerce-bitcoin-altcoin-payment-gateway-addon/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-Woocommerce'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+woocommerce+addon')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?s=WooCommerce+Automattic+eCommerce+web+services+Android+and+iOS&tab=search&type=term')."'>".__('WooCommerce', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>2.</td><td><a target='_blank' href='https://woocommerce.com/products/woocommerce-subscriptions/'><img src='".plugins_url('/images/logos/woocommerce_subscriptions.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".sprintf(__("Provides a GoUrl Bitcoin/Altcoin Payment Gateway for- <a target='_blank' href='%s'>WooCommerce Subscriptions</a><br><br>NOTE: WOOCOMMERCE SUBSCRIPTIONS PLUGIN IS FREE OPEN SOURCE, DO NOT PAY $199!<br>Free plugin download from <a target='_blank' href='%s'>Github Plugin Repository</a>", GOURL), "https://wordpress.org/plugins/woocommerce/", "https://github.com/wp-premium/woocommerce-subscriptions")."</td><td><a target='_blank' href='https://gourl.io/bitcoin-payments-woocommerce.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-payments-woocommerce.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-woocommerce-bitcoin-altcoin-payment-gateway-addon/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-Woocommerce'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+woocommerce+addon')."'>".__('GoUrl Install Now', GOURL)." &#187;</a><br><br>b. <a target='_blank' href='https://github.com/wp-premium/woocommerce-subscriptions'>".__('Woo Subscriptions', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>3.</td><td><a target='_blank' href='https://wordpress.org/plugins/give/'><img src='".plugins_url('/images/logos/give.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".sprintf(__("Bitcoin/Altcoin & Paypal Donations in Wordpress. Provides a GoUrl Bitcoin/Altcoin Payment Gateway for <a target='_blank' href='%s'>Give 0.8+</a> - easy to use wordpress donation plugin for accepting bitcoins, altcoins, paypal, authorize.net, stripe, paymill donations directly onto your website.", GOURL), "https://wordpress.org/plugins/give/")."</td><td><a target='_blank' href='https://gourl.io/bitcoin-donations-wordpress-plugin.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-donations-wordpress-plugin.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-bitcoin-paypal-donations-give-addon/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Paypal-Donations-Wordpress'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+donation+addon')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='https://github.com/cryptoapi/Give-Wordpress-Donations-Bitcoin'>".__('Give', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>4.</td><td><a target='_blank' href='https://wordpress.org/plugins/easy-digital-downloads/'><img src='".plugins_url('/images/logos/edd.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".sprintf(__("Provides a GoUrl Bitcoin/Altcoin Payment Gateway for <a target='_blank' href='%s'>Easy Digital Downloads 2.4+</a> - sell digital files / downloads through WordPress.", GOURL), "https://wordpress.org/plugins/easy-digital-downloads/")."</td><td><a target='_blank' href='https://gourl.io/bitcoin-easy-digital-downloads-edd.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-easy-digital-downloads-edd.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-bitcoin-easy-digital-downloads-edd/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Easy-Digital-Downloads'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+easy+digital+Downloads+edd')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=Easy+Digital+Downloads+easiest+way+sell+digital+products+Track+dozen')."'>".__('EDD', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>5.</td><td><a target='_blank' href='https://wordpress.org/plugins/paid-memberships-pro/'><img src='".plugins_url('/images/logos/paid-memberships-pro.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".sprintf(__("Provides a GoUrl Bitcoin/Altcoin Payment Gateway for advanced wordpress membership plugin - <a target='_blank' href='%s'>Paid Memberships Pro 1.8.4+</a>", GOURL), "https://wordpress.org/plugins/paid-memberships-pro/")."</td><td><a target='_blank' href='https://gourl.io/bitcoin-payments-paid-memberships-pro.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-payments-paid-memberships-pro.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-bitcoin-paid-memberships-pro/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Gateway-Paid-Memberships-Pro'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+paid+memberships+addon')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?s=paid+memberships+pro+stranger+studios+member+management&tab=search&type=term')."'>".__('PaidMembPro', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>6.</td><td><a target='_blank' href='https://wordpress.org/plugins/bbpress/'><img src='".plugins_url('/images/logos/bbpress.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".sprintf(__("This addon will add Premium Membership and Bitcoin payment gateway to <a target='_blank' href='%s'>bbPress 2.5+</a> Forum / Customer Support System.<br>You can mark some topics on your forum/customer support system as Premium and can easily monetise it with Bitcoins/altcoins - user pay to read / pay to create / add new replies to the topic, etc.<br>You can add premium user support to your web site using <a target='_blank' href='%s'>bbPress</a>. Any user can place questions (create new premium topic in bbPress), and only paid/premium users will see your answers, etc.", GOURL), "https://wordpress.org/plugins/bbpress/", "https://wordpress.org/plugins/bbpress/")."</td><td><a target='_blank' href='https://gourl.io/bbpress-premium-membership.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bbpress-premium-membership.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-bbpress-premium-membership-bitcoin-payments/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/bbPress-Premium-Membership-Bitcoins'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+bbpress+topics')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=bbPress+scale+forum+growing+community+contributors')."'>".__('bbPress', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>7.</td><td><a target='_blank' href='https://www.appthemes.com/themes/'><img src='".plugins_url('/images/logos/appthemes.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".sprintf(__("Provides a GoUrl Bitcoin/Altcoin Payment Gateway and Escrow for all <a target='_blank' href='%s'>AppThemes Premium Themes</a> - Classipress, Vantage, JobRoller, Clipper, Taskerr, HireBee, Ideas, Quality Control, etc.", GOURL), "https://www.appthemes.com/themes/")."</td><td><a target='_blank' href='https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-appthemes-bitcoin-payments-classipress-vantage-jobroller/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-Appthemes'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+appthemes+escrow')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='https://www.appthemes.com/themes/'>".__('AppThemes', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>8.</td><td><a target='_blank' href='https://wordpress.org/plugins/jigoshop/'><img src='".plugins_url('/images/logos/jigoshop.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".sprintf(__("Provides a GoUrl Bitcoin/Altcoin Payment Gateway for <a target='_blank' href='%s'>Jigoshop 1.12+</a>", GOURL), "https://wordpress.org/plugins/jigoshop/")."</td><td><a target='_blank' href='https://gourl.io/bitcoin-payments-jigoshop.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-payments-jigoshop.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-jigoshop-bitcoin-payment-gateway-processor/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-Jigoshop'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+jigoshop+processor')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=jigoshop+excellent+performance+dynamic')."'>".__('Jigoshop', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>9.</td><td><a target='_blank' href='https://wordpress.org/plugins/wp-e-commerce/'><img src='".plugins_url('/images/logos/wp-ecommerce.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".sprintf(__("Provides a GoUrl Bitcoin/Altcoin Payment Gateway for <a target='_blank' href='%s'>WP eCommerce 3.8.10+</a>", GOURL), "https://wordpress.org/plugins/wp-e-commerce/")."</td><td><a target='_blank' href='https://gourl.io/bitcoin-payments-wp-ecommerce.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-payments-wp-ecommerce.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-wp-ecommerce-bitcoin-altcoin-payment-gateway-addon/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-WP-eCommerce'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+wp+ecommerce+addon')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=wp+ecommerce+empowers+sell+anything+ssl')."'>".__('WP eCommerce', GOURL)." &#187;</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>10.</td><td><a target='_blank' href='https://wordpress.org/plugins/wordpress-ecommerce/'><img src='".plugins_url('/images/logos/marketpress.png', __FILE__)."' border='0'></a></td><td class='gourldesc'>".sprintf(__("Provides a GoUrl Bitcoin/Altcoin Payment Gateway for <a target='_blank' href='%s'>MarketPress 2.9+</a>", GOURL), "https://wordpress.org/plugins/wordpress-ecommerce/")."</td><td><a target='_blank' href='https://gourl.io/bitcoin-payments-wpmudev-marketpress.html'>".__('Plugin Homepage', GOURL)."</a><br><br><a target='_blank' href='https://gourl.io/bitcoin-payments-wpmudev-marketpress.html#screenshot'>".__('Screenshots', GOURL)."</a></td><td><a target='_blank' href='https://wordpress.org/plugins/gourl-wpmudev-marketpress-bitcoin-payment-gateway-addon/'>".__('Wordpress Page', GOURL)."</a><br><br><a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-MarketPress'>".__('Open Source', GOURL)."</a></td><td>a. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+marketpress+addon')."'>".__('Install Now', GOURL)." &#187;</a><br><br>b. <a href='".admin_url('plugin-install.php?tab=search&type=term&s=marketpress+WordPress+eCommerce+Beautiful+Checkout')."'>".__('MarketPress', GOURL)." &#187;</a><br><a style='font-size:12px;margin-left:20px' target='_blank' href='https://gourl.io/bitcoin-payments-wpmudev-marketpress.html#notes'>".__('Important Notes', GOURL)."</a></td></tr>";
		$tmp .= "<tr><td class='gourlnum'>11.</td><td><a target='_blank' href='https://gourl.io/affiliates.html'><img src='".plugins_url('/images/logos/affiliate.png', __FILE__)."' border='0'></a><td colspan='4' class='gourldesc'><h4>".__("Supports Bitcoin/Altcoin Payments in Any Other Wordpress Plugins", GOURL)."</h4>";
		$tmp .= sprintf(__("Other wordpress plugin developers can easily integrate Bitcoin payments to their own plugins (<a target='_blank' href='%s'>source example</a> and <a target='_blank' href='%s'>result</a>) using this GoUrl Plugin with payment gateway functionality. Please ask Wordpress Plugin Developers to add <a href='#i6'>a few lines of code below</a> to their plugins (gourl bitcoin payment gateway with optional <a target='_blank' href='%s'>Bitcoin Affiliate Program - 33.3%% lifetime revenue share</a> for them) and bitcoin/litecoin/dogecoin/etc payments will be automatically used in their plugins. It's easy!", GOURL), "https://github.com/cryptoapi/Bitcoin-Payments-Woocommerce/blob/master/gourl-woocommerce.php", "https://gourl.io/bitcoin-payments-woocommerce.html#screenshot", "https://gourl.io/affiliates.html");
		$tmp .= "</td></tr>";
		$tmp .= "<tr><td class='gourlnum'>12.</td><td colspan='5'><h3>".__("Webmaster Spelling Notifications Plugin", GOURL)."</h3>".sprintf(__("Plugin allows site visitors to send reports to the webmaster/owner about any spelling or grammatical errors. Spelling checker on your website. <a href='%s'>Live Demo</a>", GOURL), "https://gourl.io/php-spelling-notifications.html#live");
		$tmp .= "<div style='margin:20px 0 10px 0'>";
		$tmp .= "<a target='_blank' href='https://gourl.io/php-spelling-notifications.html'>".__('Plugin Homepage', GOURL)."</a> &#160; &#160; &#160; ";
		$tmp .= "<a target='_blank' href='https://wordpress.org/plugins/gourl-spelling-notifications/'>".__('Wordpress Page', GOURL)."</a> &#160; &#160; &#160; ";
		$tmp .= "<a target='_blank' href='https://github.com/cryptoapi/Wordpress-Spelling-Notifications'>".__('Open Source', GOURL)."</a> &#160; &#160; &#160; ";
		$tmp .= "<a href='".admin_url('plugin-install.php?tab=search&type=term&s=gourl+spelling')."'>".__('Install Now', GOURL)." &#187;</a>";
		$tmp .= "</div>";
		$tmp .= "<a target='_blank' href='https://wordpress.org/plugins/gourl-spelling-notifications/'><img src='".plugins_url('/images/logos/spelling.png', __FILE__)."' border='0'></a>";
		$tmp .= "<a name='i3'></a>";
		$tmp .= "</td></tr>";
		$tmp .= "</table>";


		$tmp .= "<br><br><br><br><br><br><br>";
		$tmp .= "<div class='gourltitle'>3. ".__('GoUrl Instruction', GOURL)."</div>";

		$tmp .= "<ul class='gourllist'>";
		$tmp .= "<li> ".sprintf(__("Free <a target='_blank' href='%s'>Register</a> or <a target='_blank' href='%s'>Login</a> on GoUrl.io - Global Bitcoin Payment Gateway", GOURL), "https://gourl.io/view/registration", "https://gourl.io/info/memberarea/My_Account.html")."</li>";
		$tmp .= "<li> ".sprintf(__("Create <a target='_blank' href='%s'>Payment Box</a> Records for all coin types you will accept on your website", GOURL), "https://gourl.io/editrecord/coin_boxes/0")."</li>";
		$tmp .= "<li> ".sprintf(__("You will need to place <a href='%s'>Callback URL</a> on Gourl.io, please use: <b>%s</b>", GOURL), plugins_url('/images/callback_field.png', __FILE__), trim(get_site_url(), "/ ")."/?cryptobox.callback.php")."</li>";
		$tmp .= "<li> ".sprintf(__("You will get Free GoUrl <a href='%s'>Public/Private keys</a> from new created <a target='_blank' href='%s'>payment box</a>, save them on <a href='%s'>Settings Page</a>", GOURL), plugins_url('/images/keys_field.png', __FILE__), "https://gourl.io/editrecord/coin_boxes/0", GOURL_ADMIN.GOURL."settings#".GOURL."currencyconverterapi_key")."</li>";
		$tmp .= "<li> ".sprintf(__("Optional - add your <a href='%s'>company logo</a> to payment box on <a href='%s'>Settings Page</a>", GOURL), plugins_url('/images/compare_box.png', __FILE__), GOURL_ADMIN.GOURL."settings#".GOURL."box_theme")."</li>";
		$tmp .= "</ul>";

		$tmp .= "<p>".__("THAT'S IT! YOUR WEBSITE IS READY TO ACCEPT BITCOINS ONLINE!", GOURL)."</p>";

		$tmp .= "<br><p>".sprintf(__("<b>Testing environment</b>: You can use <a target='_blank' href='%s'>500 free Speedcoins</a> or <a target='_blank' href='%s'>Dogecoins</a> for testing", GOURL), "https://speedcoin.org/info/free_coins/Free_Speedcoins.html", "https://poloniex.com/");
		$tmp .= "<a name='i4'></a>";
		$tmp .= "</p>";




		$tmp .= "<br><br><br><br><br><br><br><br>";
		$tmp .= "<div class='gourltitle'>4. ".__('Differences between Pay-Per-View and Pay-Per-Membership', GOURL)."</div>";

		$tmp .= "<div class='gourlimginstruction'>";
		$tmp .= '<a target="_blank" title="'.__('Click to see full size image', GOURL).'" href="'.plugins_url('/images/tagexample_membership_full.png', __FILE__).'"><img width="400" height="379" alt="'.__('Add GoUrl Shortcodes to pages. Example', GOURL).'" src="'.plugins_url('/images/tagexample.png', __FILE__).'" border="0"></a>';
		$tmp .= "</div>";


		$tmp .= "<ul class='gourllist'>";
		$tmp .= "<li> ".sprintf(__("<a href='%s'>Pay-Per-View</a> - shortcode <b>[%s]</b> - you can use it for unregistered website visitors. Plugin will automatically generate a unique user identification for every user and save it in user browser cookies. User can have a maximum of 2 days membership with Pay-Per-View and after they will need to pay again. Because if a user clears browser cookies, they will lose their membership and a new payment box will be displayed.", GOURL), GOURL_ADMIN.GOURL."payperview", GOURL_TAG_VIEW)."</li>";
		$tmp .= "<li> ".sprintf(__("<a href='%s'>Pay-Per-Membership</a> - shortcode <b>[%s]</b> - similar to pay-per-view but for registered users only. It is a better safety solution because plugin uses registered userID not cookies. And a membership period from 1 hour to 1 year of your choice. You need to have website <a href='%s'>registration enabled</a>.", GOURL), GOURL_ADMIN.GOURL."paypermembership", GOURL_TAG_MEMBERSHIP, admin_url('options-general.php'))."</li>";
		$tmp .= "<li> ".__('You can use <b>custom actions with Pay-Per-Membership</b> on your website (premium and free webpages).<br>For example, hide ads for premium users, php code below -', GOURL)."<br>";
		$tmp .= "<a href='".plugins_url('/images/dir/membership_actions.txt', __FILE__)."'><img src='".plugins_url('/images/paypermembership_code.png', __FILE__)."'></a>";
		$tmp .= "</li>";
		$tmp .= "<li> ".__('You can use <b>custom actions with Pay-Per-View</b> on your website too -', GOURL)."<br>";
		$tmp .= "<a href='".plugins_url('/images/dir/payperview_actions.txt', __FILE__)."'><img src='".plugins_url('/images/payperview_code.png', __FILE__)."'></a>";
		$tmp .= "</li>";
		$tmp .= "<li> ".sprintf(__("<b>Pay-Per-Membership</b> integrated with <a href='%s'>bbPress Forum/Customer Support</a> also ( use our <a href='%s'>GoUrl bbPress Addon</a> ). You can mark some topics on your bbPress as Premium and can easily monetise it with Bitcoins/altcoins.", GOURL), admin_url('plugin-install.php?tab=search&type=term&s=bbPress+forum+keeping+lean'), admin_url('plugin-install.php?tab=search&type=term&s=gourl+bbpress+topics'))."</li>";
		$tmp .= "<li> ".sprintf(__("<b>Both solutions</b> - Pay-Per-Membership and Pay-Per-View hide content on premium pages from unpaid users/visitors and allow to use custom actions on free website pages; Pay-Per-Membership provides premium membership mode in <a href='%s'>bbPress</a> also.", GOURL), "https://wordpress.org/plugins/bbpress/")."</li>";
		$tmp .= "<li> ".__("If a visitor goes to a premium page and have not logged in -<br>Pay-Per-View will show a payment box and accept payments from the unregistered visitor.<br>Pay-Per-Membership will show a message that the user needs to login/register on your website first and after show a payment box for logged in users only.", GOURL)."</li>";
		$tmp .= "</ul>";

		$tmp .= "<br><p>";
		$tmp .= sprintf(__("For example, you might offer paid unlimited access to your 50 website premium pages/posts for the price of 1 USD for 2 DAYS to all your website visitors (<span class='gourlnowrap'>non-registered</span> visitors or registered users). Simple <a href='%s'>add</a> shortcode <a href='%s'>[%s]</a> or <a href='%s'>[%s]</a> for all those fifty your premium pages/posts. When visitors go on any of those pages, they will see automatic cryptocoin payment box (the original page content will be hidden). After visitor makes their payment, they will get access to original pages content/videos and after 2 days will see a new payment box. Visitor can make payment on any your premium page and they will get access to all other premium pages also.<br>Optional - You can <a href='%s'>show ads</a> for unpaid users on other your free webpages, etc.", GOURL), plugins_url('/images/tagexample_membership_full.png', __FILE__), GOURL_ADMIN.GOURL."payperview", GOURL_TAG_VIEW, GOURL_ADMIN.GOURL."paypermembership", GOURL_TAG_MEMBERSHIP, plugins_url('/images/paypermembership_code.png', __FILE__));
		$tmp .= "<br><br>";
		$tmp .= sprintf(__("<b>Notes:</b><br>- Do not use [%s] and [%s] together on the same page.<br>- Website Editors / Admins will have all the time full access to premium pages and see original page content", GOURL), GOURL_TAG_VIEW, GOURL_TAG_MEMBERSHIP);
		$tmp .= "<a name='i5'></a>";
		$tmp .= "</p>";



		$tmp .= "<br><br><br><br><br><br><br>";
		$tmp .= "<div class='gourltitle'>5. ".__('Adding Custom Actions after Payment has been received', GOURL)."</div>";
		$tmp .= "<p><b>".__('Using for Pay-Per-Product, Pay-Per-Download, Pay-Per-View, Pay-Per-Membership only', GOURL)."</b></p>";
		$tmp .= "<p id='gourl_successful_payment'>".sprintf(__("Optional - You can use additional actions after a payment has been received (for example create/update database records, etc) using gourl instant payment notification system. Simply edit php file <a href='%s'>gourl_ipn.php</a> in directory %s and add section with your order_ID in function <b>%s</b>.", GOURL), plugins_url('/images/dir/gourl_ipn.default.txt', __FILE__), GOURL_PHP, 'gourl_successful_payment(...)')." ";
		$tmp .= __("This function will appear every time when a new payment from any user is received successfully. Function gets user_ID - user who made payment, current order_ID (the same value as at the bottom of record edit page Pay-Per-Product, Pay-Per-Download, etc.) and payment details as array.", GOURL)."</p>";

		$tmp .= "<p><a target='_blank' href='https://gourl.io/affiliate-bitcoin-wordpress-plugins.html'><img alt='".__('Example of PHP code', GOURL)."' src='".plugins_url('/images/output.png', __FILE__)."' border='0'></a></p>";
		$tmp .= "<br><p>".sprintf(__("P.S. If you use <a href='#addon'>additional plugins/add-ons</a> with gourl payment gateway, you can add your custom actions inside of function %s. That function will appear when a payment is received. Variable values received that add-on function identically to values received function gourl_successful_payment(), see <a href='%s'>screenshot</a> above.", GOURL), '<b>..addonname.."_gourlcallback"</b> ($user_ID = 0, $order_ID = "", $payment_details = array(), $box_status = "")', "#gourl_successful_payment");
		$tmp .= "<a name='i6'></a></p>";




		$tmp .= "<br><br><br><br><br><br><br>";
		$tmp .= "<div class='gourltitle'>6. ".__('Bitcoin Payments with Any Other Wordpress Plugins', GOURL)."</div>";
		$tmp .= "<p>".sprintf(__("<b>Other wordpress plugin developers can easily integrate Bitcoin payments to their own plugins</b> using this plugin with cryptocurrency payment gateway functionality. For example, see other add-on <a target='_blank' href='%s'>PHP source code</a> and <a target='_blank' href='%s'>result</a> - Bitcoin payments for <a target='_blank' href='%s'>WooCommerce</a>, which uses this plugin functionality. Please ask Wordpress Plugin Developers to add a few lines of code below to their plugins (gourl bitcoin payment gateway with optional <a target='_blank' href='%s'>Bitcoin Affiliate Program - 33.3%% lifetime revenue share</a> for them ) and bitcoin/altcoin payments will be automatically used in their plugins. GoUrl Payment Gateway will do all the work - display payment form, process received payments, etc and will submit that information to the plugin used. Around 5 seconds after cryptocoin payment is made, user will see confirmation on your webpage with any wordpress plugin that payment is received (i.e. very fast).", GOURL), "https://github.com/cryptoapi/Bitcoin-Payments-Woocommerce/blob/master/gourl-woocommerce.php", "https://gourl.io/bitcoin-payments-woocommerce.html#screenshot", "https://wordpress.org/plugins/woocommerce/", "https://gourl.io/affiliates.html")."</p>";
		$tmp .= "<p>".sprintf(__("<b>Beneficial for You and other users.</b> Simply use this GoUrl Bitcoin/Altcoin Gateway for Wordpress which will automatically be used by other plugins and you will only need to enter your bitcoin/litecoin/dogecoin wallet addresses once. No multiple times, for different plugins. Also you will see the bitcoin/altcoin payment statistics in one common table <a href='%s'>All Payments</a> with details of all received payments. So it is easy to control everything. Of course, other plugins also can show bitcoin/altcoin transactions which linked with them, using data from that common 'All Payments' table.", GOURL), GOURL_ADMIN.GOURL."payments")."</p>";

		$tmp .= "<br><h3>".__('Example of php code with GoUrl Bitcoin Payment Gateway for other wordpress plugins -', GOURL)."<br>";
		$tmp .= "<a target='_blank' href='https://gourl.io/affiliate-bitcoin-wordpress-plugins.html'><img alt='".__('Example of PHP code', GOURL)."' src='".plugins_url('/images/script.png', __FILE__)."' border='0'></a>";
		$tmp .= "</h3><p>";
		$tmp .= sprintf(__("And add custom actions after payment has been received. <a href='%s'>Integration Instruction &#187;</a>", GOURL), "https://gourl.io/affiliate-bitcoin-wordpress-plugins.html");
		$tmp .= "<a name='i7'></a>";
		$tmp .= "</p>";



		$tmp .= "<br><br><br><br><br><br><br>";
		$tmp .= "<div class='gourltitle'>7. ".__('GoUrl Contacts', GOURL)."</div>";

		$btc = "16oxamUoh6zwLgFUoADkr5KnNC6mTbBbsj";
		$bch = "15ZGAHwvwDiDhoDZtFjF3j5c5cpF8KFLZY";
		$bsv = "17wDBhNE2syKCtUyFoFaLU4QVbtXG514Z3";
		$ltc = "LarmyXoQpydpUCYHx9DZeYoxcQ4YzMfHDt";
		$spd = "SiDHas473qf8JPJFvFLcNuAAnwXhxtvv9s";
		$doge = "DNhHdAxV7CCqjPuwg2W4qTESd5jkF7iC1C";
		$dash = "XfMTeciUUZEvRRHB49qaY9Jzi1E5HAJawJ";
		$rdd = "RmB8ysK4YG4D3axNPHsKEoqxvg5KwySSJz";
		$pot = "PKwNNWo6YdweQk2F87UDGp84TQK878PWho";
		$ftc = "6otKdaB1aasmQ5kA9wKBXJM5mi9e19VxYQ";
		$vtc = "VeRUojCEkZn9u8AswqiKvpfHW4BW8Uas7V";
		$ppc = "PUxNprg24a8JjgG5pETKqesSiC5HprutvB";
		$mue = "7SA3Ht7CvoVueRvnKqqRR7fW6xg5hZk8TX";

		$tmp .= "<p>".sprintf(__('Please contact us with any questions - %s', GOURL), "<a href='https://gourl.io/view/contact/Contact_Us.html'>https://gourl.io/view/contact/Contact_Us.html</a>")."</p>";

		$tmp .= "<p>".sprintf(__("A great way to get involved in open source is to contribute to the existing projects you're using. GitHub is home to more than 5 million open source projects. <a target='_blank' href='%s'>A pull request</a> is a method of submitting contributions to an open development project. You can create a pull request with your new add-ons/php code for this free open source plugin <a target='_blank' href='%s'>here</a>", GOURL), "http://readwrite.com/2014/07/02/github-pull-request-etiquette", "https://github.com/cryptoapi/Bitcoin-Wordpress-Plugin") ."</p>";
		$tmp .= "<br><br>";

		$tmp .= "<div style='float:right;margin:20px 20px 100px 0;width:570px'>";
		$tmp .= "<h3>".__('Buttons For Your Website -', GOURL)."</h3>";
		$tmp .= '<img hspace="10" vspace="10" src="'.plugins_url('/images/gourl.png', __FILE__).'" border="0">';
		$tmp .= '<img hspace="10" vspace="10" src="'.plugins_url('/images/gourlpayments.png', __FILE__).'" border="0"><br>';
		$tmp .= '<img hspace="10" vspace="10" src="'.plugins_url('/images/bitcoin_accepted.png', __FILE__).'" border="0">';
		$tmp .= '<img hspace="10" vspace="10" src="'.plugins_url('/images/bitcoin_donate.png', __FILE__).'" border="0"><br>';
		foreach($this->coin_names as $k => $v)  $tmp .= '<img width="70" hspace="10" vspace="10" alt="'.$v.'" src="'.plugins_url('/images/'.$v.'2.png', __FILE__).'" border="0"> ';
		$tmp .= "<br><br><br>";
		$tmp .= "<img width='570' src='".plugins_url('/images/coins.png', __FILE__)."' border='0'>";
		$tmp .= "</div>";

		$tmp .= "<div style='margin:50px 0'>";
		$tmp .= "<h3>".__('Our Project Donation Addresses -', GOURL)."</h3>";
		$tmp .= "<p>Bitcoin: &#160; <a href='bitcoin:".$btc."?label=Donation'>".$btc."</a></p>";
		$tmp .= "<p>BitcoinCash: &#160; <a href='bitcoincash:".$bch."?label=Donation'>".$bch."</a></p>";
		$tmp .= "<p>BitcoinSV: &#160; <a href='bitcoinsv:".$bsv."?label=Donation'>".$bsv."</a></p>";
		$tmp .= "<p>Litecoin: &#160; <a href='litecoin:".$ltc."?label=Donation'>".$ltc."</a></p>";
		$tmp .= "<p>Dash: &#160; <a href='dash:".$dash."?label=Donation'>".$dash."</a></p>";
		$tmp .= "<p>Dogecoin: &#160; <a href='dogecoin:".$doge."?label=Donation'>".$doge."</a></p>";
		$tmp .= "<p>Speedcoin: &#160; <a href='speedcoin:".$spd."?label=Donation'>".$spd."</a></p>";
		$tmp .= "<p>Reddcoin: &#160; <a href='reddcoin:".$rdd."?label=Donation'>".$rdd."</a></p>";
		$tmp .= "<p>Potcoin: &#160; <a href='potcoin:".$pot."?label=Donation'>".$pot."</a></p>";
		$tmp .= "<p>Feathercoin: &#160; <a href='feathercoin:".$ftc."?label=Donation'>".$ftc."</a></p>";
		$tmp .= "<p>Vertcoin: &#160; <a href='vertcoin:".$vtc."?label=Donation'>".$vtc."</a></p>";
		$tmp .= "<p>MonetaryUnit: &#160; <a href='monetaryunit:".$mue."?label=Donation'>".$mue."</a></p>";
		$tmp .= "<p>Peercoin: &#160; <a href='peercoin:".$ppc."?label=Donation'>".$ppc."</a></p>";
		$tmp .= "</div>";
		$tmp .= "<br><br><br><br><br><br><br>";





		$tmp .= "</div>";
		$tmp .= "</div>";
		$tmp .= "</div>";

		echo $tmp;

		return true;
	}








	// list -
	// function get
	// function post
	// function check
	// function save
	// function adminpage
	// function shortcode


	/**************** A. GENERAL OPTIONS ************************************/


	/*
	 *  19. Get values from the options table
	*/
	private function get_settings()
	{

	    $arr = array("box_type"=>"", "box_theme"=>"", "box_width"=>540, "box_height"=>230, "box_border"=>"", "box_style"=>"", "message_border"=>"", "message_style"=>"", "login_type"=>"", "rec_per_page"=>20, "popup_message"=>__('It is a Paid Download ! Please pay below', GOURL), "file_columns"=>"", "chart_reverse"=>"", "boxlogo"=>0, "boxlogo2"=>"", "boxlogo_url"=>"", "currencyconverterapi_key"=>"");
		foreach($arr as $k => $v) $this->options[$k] = "";

		foreach($this->custom_images as $k => $v)
		{
			$this->options[$k] = 0;
			$this->options[$k."2"] = "";
			$this->options[$k."url"] = "";
		}

		foreach($this->coin_names as $k => $v)
		{
			$this->options[$v."public_key"] = "";
			$this->options[$v."private_key"] = "";
		}

		foreach ($this->options as $key => $value)
		{
			$this->options[$key] = get_option(GOURL.$key);
		}

		// default
		foreach($arr as $k => $v)
		{
			if (!$this->options[$k]) $this->options[$k] = $v;
		}

		foreach($this->custom_images as $k => $v)
		{
			if (!$this->options[$k."url"]) $this->options[$k] = 0;
		}

		if ((!$this->options["boxlogo_url"] && $this->options["boxlogo"] == 2) || !in_array($this->options["boxlogo"], array(0, 1, 2))) $this->options["boxlogo"] = 0;


		// Additional Security - compare gourl public/private keys sha1 hash with hash stored in file $this->hash_url
		// ------------------
		$txt = (is_readable($this->hash_url)) ? file_get_contents($this->hash_url) : "";
		$arr = json_decode($txt, true);

		/*
		if (isset($arr["nonce"]) && $arr["nonce"] != sha1(md5(NONCE_KEY)))
		{
		    $this->save_cryptokeys_hash(); // admin changed NONCE_KEY
		    $txt = (is_readable($this->hash_url)) ? file_get_contents($this->hash_url) : "";
		    $arr = json_decode($txt, true);
		}
		*/

		foreach($this->coin_names as $k => $v)
		{
		    $pub  = $v."public_key";
		    $prv  = $v."private_key";
		    if (($this->options[$pub] || $this->options[$prv]) &&
		        (!isset($arr[$pub]) || !isset($arr[$prv]) ||
		         $arr[$pub] != sha1($this->options[$pub].NONCE_KEY.$this->options[$pub]) ||
		         $arr[$prv] != sha1($this->options[$prv].NONCE_KEY.$this->options[$prv])))
		         {
		              $this->options[$pub] = $this->options[$prv] = "";
		              update_option(GOURL.$pub, "");
		              update_option(GOURL.$prv, "");

		              if (!isset($this->errors["md5_error"])) $this->errors["md5_error"] = sprintf(__("Invalid %s keys md5 hash in file %s. Please delete this file and re-enter your GoUrl Public/Private Keys"), $v, $this->hash_url);
		         }
		}

		return true;
	}



	/*
	 *  20.
	*/
	private function post_settings()
	{

		foreach ($this->options as $key => $value)
		{
			$this->options[$key] = (isset($_POST[GOURL.$key])) ? stripslashes($_POST[GOURL.$key]) : "";
			if (is_string($this->options[$key])) $this->options[$key] = trim($this->options[$key]);
		}

		return true;
	}



	/*
	 *  21.
	*/
	private function check_settings()
	{
		$f = true;
		foreach($this->coin_names as $k => $v)
		{
			$public_key  = trim($this->options[$v."public_key"]);
			$private_key = trim($this->options[$v."private_key"]);

			$boxID = $this->left($public_key, "AA");
			if ($public_key &&  (strpos($public_key, " ") !== false  || strlen($public_key) != 50  || $public_key != preg_replace('/[^A-Za-z0-9]/', '', $public_key)   || !strpos($public_key, "AA")  || !$boxID || !is_numeric($boxID) || !strpos($public_key, ucfirst(strtolower($v))."77".strtoupper($k)."PUB")))  $this->errors[$v."public_key"] = ucfirst($v) . ' ' . __('Box Invalid Public Key', GOURL)  . ' : ' . $public_key;

			$boxID = $this->left($private_key, "AA");
			if ($private_key && (strpos($private_key, " ") !== false || strlen($private_key) != 50 || $private_key != preg_replace('/[^A-Za-z0-9]/', '', $private_key) || !strpos($private_key, "AA") || !$boxID || !is_numeric($boxID) || !strpos($private_key, ucfirst(strtolower($v))."77".strtoupper($k)."PRV") || $boxID != $this->left($public_key, "AA"))) $this->errors[$v."private_key"] = ucfirst($v) . ' ' . __('Box Invalid Private Key', GOURL) . ' : ' . $private_key;

			if ($public_key && !$private_key) $this->errors[$v."private_key"] = ucfirst($v) . ' ' . __('Box Private Key  - cannot be empty', GOURL);
			if ($private_key && !$public_key) $this->errors[$v."public_key"]  = ucfirst($v) . ' ' . __('Box Public Key  - cannot be empty', GOURL);

			if ($public_key || $private_key) $f = false;

			if ($public_key && $private_key  && !isset($this->errors[$v."public_key"]) && !isset($this->errors[$v."private_key"])) $this->payments[$k] = ucfirst($v);
		}

		if ($f && !isset($this->errors["md5_error"]))  $this->errors[] = sprintf(__("You must choose at least one payment method. Please enter your GoUrl Public/Private Keys. <a href='%s'>Instruction here &#187;</a>", GOURL), GOURL_ADMIN.GOURL."#i3");

		if (!is_numeric($this->options["box_width"]) || round($this->options["box_width"]) != $this->options["box_width"] || $this->options["box_width"] < 480 || $this->options["box_width"] > 700) $this->errors[] = __('Invalid Payment Box Width. Allowed 480..700px', GOURL);
		if (!is_numeric($this->options["box_height"]) || round($this->options["box_height"]) != $this->options["box_height"] || $this->options["box_height"] < 200 || $this->options["box_height"] > 400) $this->errors[] = __('Invalid Payment Box Height. Allowed 200..400px', GOURL);

		if (!is_numeric($this->options["rec_per_page"]) || round($this->options["rec_per_page"]) != $this->options["rec_per_page"] || $this->options["rec_per_page"] < 5 || $this->options["rec_per_page"] > 200) $this->errors[] = __('Invalid Records Per Page value. Allowed 5..200', GOURL);

		if (mb_strlen($this->options["popup_message"]) < 15 || mb_strlen($this->options["popup_message"]) > 400) $this->errors[] = __('Invalid Popup Message text size. Allowed 15 - 400 characters text length', GOURL);

		if ($this->options["box_style"] && (in_array($this->options["box_style"][0], array("'", "\"")) || $this->options["box_style"] != preg_replace('/[^A-Za-z0-9_\-\ \.\,\:\;\!\"\'\#]/', '', $this->options["box_style"]))) $this->errors[] = __('Invalid Payment Box Style', GOURL);
		if ($this->options["message_style"] && (in_array($this->options["message_style"][0], array("'", "\"")) || $this->options["message_style"] != preg_replace('/[^A-Za-z0-9_\-\ \.\,\:\;\!\"\'\#]/', '', $this->options["message_style"]))) $this->errors[] = __('Invalid Payment Messages Style', GOURL);


		// upload files
		if ($_FILES && $_POST && is_admin() && $this->page == GOURL.'settings')
		{
			foreach($this->custom_images as $k => $v)
			{
				$file = (isset($_FILES[GOURL.$k."2"]["name"]) && $_FILES[GOURL.$k."2"]["name"]) ? $_FILES[GOURL.$k."2"] : "";
				if ($file)
				{
				    if ($this->options[$k."url"] && file_exists(GOURL_DIR."box/".$this->options[$k.'url']) && current_user_can('administrator')) unlink(GOURL_DIR."box/".$this->options[$k.'url']);
					$this->options[$k."url"] = $this->upload_file($file, "box");

				}
			}

			// upload company logo
			$file = (isset($_FILES[GOURL."boxlogo2"]["name"]) && $_FILES[GOURL."boxlogo2"]["name"]) ? $_FILES[GOURL."boxlogo2"] : "";
			if ($file)
			{
			    if ($this->options["boxlogo_url"] && file_exists(GOURL_DIR."box/".$this->options["boxlogo_url"]) && current_user_can('administrator')) unlink(GOURL_DIR."box/".$this->options["boxlogo_url"]);
			    $this->options["boxlogo_url"] = $this->upload_file($file, "box");
			}

			if ($this->record_errors) $this->errors = array_merge($this->errors, $this->record_errors);
		}


		// test currencyconverterapi.com api key
		$err = "";
		if ($this->options["currencyconverterapi_key"] && $this->options["currencyconverterapi_key"] != get_option(GOURL.'currencyconverterapi_key'))
		{
		    $val = json_decode(gourl_get_url("https://free.currconv.com/api/v7/convert?q=AUD_USD&compact=ultra&apiKey=".$this->options["currencyconverterapi_key"], 10, TRUE), TRUE);
		    if (is_array($val) && isset($val["error"])) $err .= "<li>- Free key: ".$val["error"]."</li>";
		    if (is_array($val) && isset($val["AUD_USD"]) && $val["AUD_USD"] > 0) $val = $val["AUD_USD"];

 		    if (is_array($val) || $val <= 0)
		    {
    		    		$val = json_decode(gourl_get_url("https://prepaid.currconv.com/api/v7/convert?q=AUD_USD&compact=ultra&apiKey=".$this->options["currencyconverterapi_key"], 10, TRUE), TRUE);
				if (is_array($val) && isset($val["error"])) $err .= "<li>- Prepaid key: ".$val["error"]."</li>";
		    		if (is_array($val) && isset($val["AUD_USD"]) && $val["AUD_USD"] > 0) $val = $val["AUD_USD"];

				if (is_array($val) || $val <= 0)
				{
					$val = json_decode(gourl_get_url("https://api.currconv.com/api/v7/convert?q=AUD_USD&compact=ultra&apiKey=".$this->options["currencyconverterapi_key"], 10, TRUE), TRUE);
					if (is_array($val) && isset($val["error"])) $err .= "<li>- Premium key: ".$val["error"]."</li>";
		    			if (is_array($val) && isset($val["AUD_USD"]) && $val["AUD_USD"] > 0) $val = $val["AUD_USD"];

					if (is_array($val) || $val <= 0)  $this->errors[] = __('Invalid Currencyconverterapi.com Free/Prepaid/Premium API Keys', GOURL) . ($err? "<div style='font-weight:normal;margin:0 25px;padding:0;'>".__('Currencyconverterapi.com website Responses:', GOURL).' <ul>'.$err.'</ul></div>':"");
				}
	    	    }
		}


		// system re-test
		if (!function_exists( 'curl_init' )) 				$this->errors[] = sprintf(__("Error. Please enable <a target='_blank' href='%s'>CURL extension</a> in PHP. <a target='_blank' href='%s'>Read here &#187;</a>", GOURL), "http://php.net/manual/en/book.curl.php", "http://stackoverflow.com/questions/1347146/how-to-enable-curl-in-php-xampp");
		if (!function_exists( 'mysqli_connect' )) 			$this->errors[] = sprintf(__("Error. Please enable <a target='_blank' href='%s'>MySQLi extension</a> in PHP. <a target='_blank' href='%s'>Read here &#187;</a>", GOURL), "http://php.net/manual/en/book.mysqli.php", "http://crybit.com/how-to-enable-mysqli-extension-on-web-server/");
		if (version_compare(phpversion(), '5.4.0', '<')) 	$this->errors[] = sprintf(__("Error. You need PHP 5.4.0 (or greater). Current php version: %s", GOURL), phpversion());

		// writable directory
		if (!file_exists($this->hash_url) && !is_writable(dirname($this->hash_url))) $this->errors[] = sprintf(__("Error. Cannot write file %s - please make directory %s writable.", GOURL), $this->hash_url, dirname($this->hash_url));

		return true;
	}




	/*
	 *  22.
	*/
	private function save_settings()
	{
		$arr = array();
		$editable = (!file_exists($this->hash_url) || is_writable($this->hash_url)) ? true : false;

		if (!(is_admin() && is_user_logged_in() && current_user_can('administrator')))
		{
  			$this->errors[] = __('You don\'t have permission to edit this page. Please login as ADMIN user!', GOURL);
			return false;
		}
		else
		{
			foreach ($this->options as $key => $value)
			{
			    $boxkey = (strpos($key, "public_key") || strpos($key, "private_key")) ? true : false;
			    if ($editable || !$boxkey)
			    {
			    	$oldval = get_option(GOURL.$key);
			    	if ($boxkey && $oldval != $value) $arr[$key] = array("old_key" => ($oldval ? substr($oldval, 0, -20)."....." : "-empty-"), "new_key" => ($value ? substr($value, 0, -20)."....." : "-empty-"));
			    	update_option(GOURL.$key, $value);
			    }
			}

			if ($arr)
			{
				wp_mail(get_bloginfo('admin_email'), 'Notification - GoUrl Bitcoin Payment Gateway Plugin - Cryptobox Keys Changed',
				date("r")." GMT \n\nGoUrl Bitcoin Payment Gateway for Wordpress plugin \n\nCrypto payment box/es keys was changed on your website (gourl plugin Settings Page).\n\nIF YOU DIDN'T CHANGE YOUR GOURL KEYS, PLEASE CHANGE YOUR WORDPRESS ADMIN PASSWORD AND RESTORE ORIGINAL KEYS !\nALSO UPDATE GOURL PLUGIN TO THE LATEST VERSION IF YOU ARE USING AN OLD VERSION ! \n\n".print_r($arr, true));

				$this->save_cryptokeys_hash();
			}


		}

		return true;
	}



	/*
	 *  23. Additional Security
	 *  Save gourl public/private keys sha1 hash in file $this->hash_url
	*/
	private function save_cryptokeys_hash()
	{
	    if (!file_exists($this->hash_url) || is_writable($this->hash_url))
	    {
        	$arr = array("nonce" => sha1(md5(NONCE_KEY)));
        	foreach($this->coin_names as $k => $v)
        	{
        	    $pub  = $v."public_key";
        	    $prv  = $v."private_key";
        	    if ($this->options[$pub] && $this->options[$prv])
        	    {
        	        $arr[$pub] = sha1($this->options[$pub].NONCE_KEY.$this->options[$pub]);
        	        $arr[$prv] = sha1($this->options[$prv].NONCE_KEY.$this->options[$prv]);
        	    }
        	}

        	file_put_contents($this->hash_url, json_encode($arr));
	    }

	    return true;
	}


	/*
	 *  Notice for non-admin users
	*/
	private function is_nonadmin_user ()
	{
		if (!(is_admin() && is_user_logged_in() && current_user_can('administrator')))
		{
			$tmp  = "<div class='wrap ".GOURL."admin'>";
			$tmp .= $this->page_title(__('Admin Area', GOURL));
			$tmp .= "<br><br><br><br><h2><center>".__('Only Admin users can access to this page !', GOURL)."</center></h2><br><br><br>";
			$tmp .= "</div>";

			echo $tmp;

			return true;
		}
		else return false;
	}



	/*
	 *  24.
	*/
	public function page_settings()
	{

		if ($this->is_nonadmin_user()) return true;

		$readonly = (file_exists($this->hash_url) && !is_writable($this->hash_url)) ? 'readonly' : '';

		if ($readonly)
		{
			$txt = (is_readable($this->hash_url)) ? file_get_contents($this->hash_url) : "";
			$arr = json_decode($txt, true);
			if (isset($arr["nonce"]) && $arr["nonce"] != sha1(md5(NONCE_KEY)))
			{
			    $this->errors[] = sprintf(__('The value of wordpress constant NONCE_KEY has been changed. <br>Please unlock "%s" and re-enter your gourl keys; and after that, you can lock gourl.hash file again', GOURL), $this->hash_url);
			}
			unset($arr); unset($txt);
		}



		if ($this->errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Settings have been updated <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";

        if (!$this->errors && ((isset($_GET['testconnect']) && $_GET["testconnect"] == "true") || $this->updated))
        {
            if (!(is_admin() && is_user_logged_in() && current_user_can('administrator'))) $message .= "<div class='error'><p>".__('Cannot test connection to GoUrl.io Payment Server. You should be ADMIN user!', GOURL)."</p></div>";
            else
            {
                $messages = $this->test_gourl_connection( $this->updated );
                if (isset($messages["error"]))
                {
                    unset($messages["error"]);
                    $message .= "<div class='error'><p>".__('Connection to GoUrl.io Payment Server - Errors found -', GOURL)."</p><ol><li>".implode("</li><li>", $messages)."</li></ol>";
                    $message .= "<br><br><div style='color:#23282d'>".sprintf( __("Note: As alternative, you can use old <a href='%s'>iFrame Payment Box Type</a>", GOURL), plugins_url('/images/compare_box.png', __FILE__)) . " (option below)";
                    $message .= "</div><br></div>";
                }
                elseif (!$this->updated) $message .= "<div class='updated'><p><b>".__('ALL CONNECTIONS ARE OK!', GOURL)."</b></p><ol><li>".implode("</li><li>", $messages)."</li></ol></div>";
            }
        }


		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';

		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title(__('Settings', GOURL));


		if (!$this->payments)
		{
			$tmp .= "<div class='".GOURL."intro postbox'>";
			$tmp .= sprintf( __("Simple register on <a target='_blank' href='%s'>GoUrl.io</a> and get your Free Public/Private Payment Box keys. &#160; <a href='%s'>Read more &#187;</a>", GOURL), "https://gourl.io/info/memberarea/My_Account.html", GOURL_ADMIN.GOURL."#i3");
			$tmp .= "</div>";
		}

		$tmp .= $message;

		$tmp .= "<form enctype='multipart/form-data' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."settings'>";

		$tmp .= "<div class='postbox'>";
		$tmp .= "<h3 class='hndle'>".__('General Settings', GOURL)."</h3>";
		$tmp .= "<div class='inside'>";

		$tmp .= '<input type="hidden" name="'.$this->adminform.'" value="'.GOURL.'save_settings" />';
		$tmp .= wp_nonce_field( $this->admin_form_key );

		$tmp .= '<p>'.sprintf(__( "If you use multiple websites online, please create separate <a target='_blank' href='%s'>GoUrl Payment Box</a> records (with unique payment box public/private keys) for each of your websites. Do not use the same GoUrl Payment Box with the same public/private keys on your different websites.", GOURL ), "https://gourl.io/editrecord/coin_boxes/0") . '</p>';
		$tmp .= '<p>'.sprintf(__( "If you want to use plugin in a language other than English, see the page <a href='%s'>Languages and Translations</a>. &#160;  This enables you to easily customize the texts of all the labels visible to your users.", GOURL ), "https://gourl.io/languages.html", "https://gourl.io/languages.html") . '</p>';
		if (!$readonly) $tmp .= '<p class="blue">'.sprintf(__( "<b style='color:red'>ADDITIONAL PAYMENTS SECURITY</b>  - You can make file <a href='%s'>%s</a> - <a target='_blank' href='%s'>readonly</a> (<b>file location</b> - %s; <a target='_blank' href='%s'>instruction</a>) <br>GoUrl Public/Private keys on page below will be not editable anymore (readonly mode). <br>Optional - for full security make <a target='_blank' href='%s'>readonly</a> gourl main plugin file <a href='%s'>gourl.php</a> also.", GOURL ), $this->hash_url, "<b>".basename($this->hash_url)."</b>", "https://www.cyberciti.biz/faq/linux-write-protecting-a-file/", (strpos($this->hash_url, "wp-content") ? "wp-content".$this->right($this->hash_url, "wp-content") : $this->hash_url), "https://www.cyberciti.biz/faq/linux-write-protecting-a-file/", "https://www.cyberciti.biz/faq/linux-write-protecting-a-file/", plugin_dir_url( __FILE__ )."gourl.php") . '</p>';
		$tmp .= '<br><br>';
		$tmp .= '<div class="alignright">';
		$tmp .= '<img id="gourlsubmitloading" src="'.plugins_url('/images/loading.gif', __FILE__).'" border="0">';
		$tmp .= '<input type="submit" onclick="this.value=\''.__('Please wait...', GOURL).'\';document.getElementById(\'gourlsubmitloading\').style.display=\'inline\';return true;" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Settings', GOURL).'">';
		if ($this->payments) $tmp .= '<a href="'.GOURL_ADMIN.GOURL.'#i3" class="'.GOURL.'button button-secondary">'.__('Instruction', GOURL).'</a>'.$this->space();
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'settings">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '</div><br><br>';

		$tmp .= "<table class='".GOURL."table ".GOURL."settings'>";

		$callback_url = trim(get_site_url(), "/ ").'/?cryptobox.callback.php';
		$callback_url_encoded = trim(base64_encode($callback_url), "= ");

		$tmp .= '<tr><th>'.__('Your Callback Url', GOURL).':</th>';
		$tmp .= '<td><b>'.$callback_url.'</b> '.($this->payments? '&#160; &#160; <a target="_blank" href="https://gourl.io/info/ipn/callback/'.$callback_url_encoded.'/IPN_Website_Testing.html">'.__('Test here &#187;', GOURL).'</a>' : '').'<br><br><em>'.sprintf(__("IMPORTANT - Please place this url in field <a href='%s'>Callback URL</a> for all your Payment Boxes on gourl.io. <a href='%s'>See screenshot</a>", GOURL), "https://gourl.io/editrecord/coin_boxes/0", plugins_url('/images/callback_field.png', __FILE__)).'</em></td>';
		$tmp .= '</tr>';


		$tmp .= '<tr><th colspan="2" id="st"><br><br><br><h3>'.__('Payment Box Settings', GOURL).'</h3>';
		if (!$this->errors)
        {
            $tmp .= '<p style="font-weight:normal"> &#160; a. <a href="'.GOURL_ADMIN.GOURL.'settings&testconnect=true" class="'.GOURL.'button button-secondary">'.__('Click to Test Connection to GoUrl.io Server', GOURL).'</a>';
            $tmp .= ' &#160;  &#160;  &#160; b. <a target="_blank" href="https://gourl.io/info/ipn/callback/'.$callback_url_encoded.'/IPN_Website_Testing.html" class="'.GOURL.'button button-secondary">'.__('Test your Callback Url', GOURL).'</a></p><br>';
        }
		$tmp .= '</th>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('PaymentBox Type', GOURL).': <img width="70" hspace="20" alt="new" src="'.plugins_url('/images/new.png', __FILE__).'" border="0"></th><td>';
		$tmp .= '<p>';
		$tmp .= '<b><input type="radio" name="'.GOURL.'box_type" value="" '.$this->chk($this->options['box_type'], "").'> '.__('White Label, Mobile Friendly', GOURL).$this->space(4);
		$tmp .= '<input type="radio" name="'.GOURL.'box_type" value="2" '.$this->chk($this->options['box_type'], 2).'> '.__('iFrame (Legacy)', GOURL) . '</b>' . $this->space(4) . '<a target="_blank" href="'.plugins_url('/images/compare_box.png', __FILE__).'">'.__('screenshots', GOURL).'</a>';
		$tmp .= '<br><em>'.__('White Label Payment Box - user browser receive payment data from your website only (does not even know about gourl.io); your website receive data from gourl.io (curl method). It use Bootstrap4. You can use your own payment logo', GOURL).' &#160; <a target="_blank" href="https://gourl.io/lib/examples/example_customize_box.php?logo=no&numcoin=1#b">'.__('White Label Example', GOURL).'</a>'. '</em>';
		$tmp .= '<em>'.__('iFrame - display gourl.io payment box in iFrame on your webpage. Not mobile friendly.', GOURL).' &#160; <a target="_blank" href="https://gourl.io/bitcoin-payment-gateway-api.html?gourlcryptolang=en#gourlcryptolang">'.__('iFrame Example', GOURL).'</a>'. '</em>';
		$tmp .= '</p>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Payment Box Theme', GOURL).':</th><td>';
		$tmp .= '<p>';
		$tmp .= '<select id="'.GOURL.'box_theme" name="'.GOURL.'box_theme">';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], '').' value="">Default</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'greyred').' value="greyred">LightGrey/Red</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'greygreen').' value="greygreen">DarkGrey/Green</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'black').' value="black">Black</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'whiteblue').' value="whiteblue">White/Blue</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'whitered').' value="whitered">White/Red</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'whitegreen').' value="whitegreen">White/Green</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'sandstone').' value="sandstone">White/Lime Green</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'whiteblack').' value="whiteblack">White/Black</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'whitepurple').' value="whitepurple">White/Purple</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'litera').' value="litera">Light Blue (Rounded)</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'minty').' value="minty">Light Green (Rounded)</option>';
		$tmp .= '<option '.$this->sel($this->options['box_theme'], 'sketchy').' value="sketchy">Sketchy - comics :)</option>';
		$tmp .= '</select>';
		$tmp .= ' &#160; &#160; <a href="'.GOURL_ADMIN.GOURL.'payperview&example=2&preview=true#previewcrypto">'.__('Live Preview &#187;', GOURL).'</a>';
		$tmp .= '<em>'.sprintf(__("Payment Box color theme (<a href='%s'>white</a> / <a href='%s'>black</a> / <a href='%s'>sketchy</a> / blue / red / etc)", GOURL), "https://gourl.io/images/woocommerce/screenshot-3.png", "https://gourl.io/images/woocommerce/screenshot-9.png", "https://gourl.io/images/woocommerce/screenshot-10.png"). '</em>';
		$tmp .= '</p>';


		$preview = ' &#160; &#160; &#160; &#160; <a href="'.GOURL_ADMIN.GOURL.'payperview&example=2&preview=true#previewcrypto">'.__('Live Preview &#187;', GOURL).'</a>';
		$tmp .= '<tr><th>'.__('Your Company Logo in Payment Box', GOURL).':</th><td>';
		$tmp .= '<p><input type="radio" name="'.GOURL.'boxlogo" value="0" '.$this->chk($this->options['boxlogo'], 0).'> '.__('No Logo', GOURL).($this->options['boxlogo']==0?$preview:'').'</p>';
		$tmp .= '<p><input type="radio" name="'.GOURL.'boxlogo" value="1" '.$this->chk($this->options['boxlogo'], 1).'> '.__('Example Logo', GOURL).($this->options['boxlogo']==1?$preview:' -').'</p>';
		$tmp .= "<img src='".plugins_url('/images/your_logo.png', __FILE__)."' border='0'>";
		$tmp .= '<p><input type="radio" name="'.GOURL.'boxlogo" value="2" '.$this->chk($this->options['boxlogo'], 2).'> '.__('Custom Image', GOURL).($this->options['boxlogo']==2?$preview:' -').'</p>';
		if ($this->options['boxlogo_url'] && file_exists(GOURL_DIR."box/".$this->options['boxlogo_url'])) $tmp .= "<img style='max-width:200px;max-height:40px;' src='".GOURL_DIR2."box/".$this->options['boxlogo_url']."' border='0'>"; else $this->options['boxlogo_url'] = "";
		$tmp .= "<input type='hidden' id='".GOURL."boxlogo_url' name='".GOURL."boxlogo_url' value='".htmlspecialchars($this->options['boxlogo_url'], ENT_QUOTES)."'>";
		$tmp .= '<input type="file" accept="image/*" id="'.GOURL.'boxlogo2" name="'.GOURL.'boxlogo2" class="widefat"><br><em>'.__('Optimal size: 200x40px. Allowed images: JPG, GIF, PNG.', GOURL).'</em>';
		$tmp .= '</td></tr>';

		$tmp .= '<tr><th><br>'.__('Free CurrencyConverterApi.com Key (optional)', GOURL).':</th>';
		$txt2 = ($this->options['currencyconverterapi_key']) ? "&#160; ".__('and', GOURL)." &#160; <a target='_blank' href='https://free.currconv.com/api/v7/convert?q=AUD_USD&compact=ultra&apiKey=".$this->options['currencyconverterapi_key']."'>".__('Test Your Free API Key Now &#187;', GOURL)."</a>" : "";
		$txt3 = ($this->options['currencyconverterapi_key']) ? sprintf(__('Test your prepaid key <a target="_blank" href="%s"><b>here</b></a> or premium key <a target="_blank" href="%s"><b>here</b></a>', GOURL), "https://prepaid.currconv.com/api/v7/convert?q=AUD_USD&compact=ultra&apiKey=".$this->options['currencyconverterapi_key'], "https://api.currconv.com/api/v7/convert?q=AUD_USD&compact=ultra&apiKey=".$this->options['currencyconverterapi_key']) : "";
		$tmp .= '<td><br><input type="text" id="'.GOURL.'currencyconverterapi_key" name="'.GOURL.'currencyconverterapi_key" value="'.htmlspecialchars($this->options['currencyconverterapi_key'], ENT_QUOTES).'" class="widefat"><br><em>'. sprintf( __('place free/paid api key, if you accept payments other than USD, EUR, JPY, BGN, CZK, DKK, GBP, HUF, PLN, RON, SEK, CHF, ISK, NOK, HRK, RUB, TRY, AUD, BRL, CAD, CNY, HKD, IDR, ILS, INR, KRW, MXN, MYR, NZD, PHP, SGD, THB, ZAR (<a target="_blank" href="%s">ECB Rates are used</a> for these currencies).<br><a target="_blank" href="%s">Get free API key on currencyconverterapi.com</a>', GOURL), "https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml", "https://free.currencyconverterapi.com/free-api-key") .$txt2.' <br> ( '. sprintf( __('you can use <a target="_blank" href="%s">PREPAID</a> / <a target="_blank" href="%s">PREMIUM</a> key also.', GOURL), "https://www.currencyconverterapi.com/dev/register-app?plan=prepaid", "https://www.currencyconverterapi.com/dev/register-app?plan=premium" ) .' ' .$txt3.' )</em><br><br><br></td>';
		$tmp .= '</tr>';


		foreach ($this->coin_names as $k => $v)
		{
			$v2 = ucfirst($v);
			if ($v2 == "Bitcoincash") $v2 = "Bitcoin Cash BCH";
			if ($v2 == "Bitcoinsv")   $v2 = "Bitcoin SV";
			if ($k == "BCH") $k .= "/BCHN";
			if ($k == "BSV") $k .= "/BCHSV";

			$tmp .= '<tr><th>'.$v2.' '.__('Payments', GOURL).':<br><a target="_blank" href="'.$this->coin_www[$v].'"><img title="'.$v2.' Payment API" src="'.plugins_url('/images/'.$v.'.png', __FILE__).'" border="0"></a></th>';
			$tmp .= '<td>';
			$tmp .= '<div>GoUrl '.$v2.' '.sprintf(__('Box (%s) Public Key', GOURL), $k).' -</div><input type="text" '.$readonly.' id="'.GOURL.$v.'public_key" name="'.GOURL.$v.'public_key" value="'.htmlspecialchars($this->options[$v.'public_key'], ENT_QUOTES).'" class="widefat">';
			$tmp .= '<div>GoUrl '.$v2.' '.sprintf(__('Box (%s) Private Key', GOURL), $k).' -</div><input type="text" '.$readonly.' id="'.GOURL.$v.'private_key" name="'.GOURL.$v.'private_key" value="'.htmlspecialchars($this->options[$v.'private_key'], ENT_QUOTES).'" class="widefat">';
			if ($this->options[$v.'public_key'] && $this->options[$v.'private_key'] && !$this->errors) $tmp .= '<em><span class="gourlpayments"><b>'.sprintf(__("%s (%s) payments are active!", GOURL), $v2, $k).'</b></span></em>';
			elseif (!$readonly) $tmp .= '<em>'.sprintf(__("<b>That is not a %s wallet private key!</b> &#160; GoUrl %s Box Private/Public Keys are used for communicating between your website and GoUrl.io Payment Gateway server (similar like paypal id/keys).<br>If you want to start accepting payments in <a target='_blank' href='%s'>%s (%s)</a>, please create a <a target='_blank' href='%s'>%s Payment Box</a> on GoUrl.io and then enter the received free GoUrl %s Box Public/Private Keys. Leave field blank if you do not accept payments in %s", GOURL), $v2, $v2, $this->coin_www[$v], $v2, $k, "https://gourl.io/editrecord/coin_boxes/0/", $v2, $v2, $v2).'</em>';
			if ($readonly) $tmp .= '<em><span class="gourlpayments"><b>'.sprintf(__("You cannot modify this values because security hash file <a href='%s'>%s</a> is readonly!", GOURL), $this->hash_url, basename($this->hash_url)).'</b></span></em>';
			$tmp .= '</td></tr>';
		}











		$tmp .= '<tr><th colspan="2"><br><br><br><h3>'.__('Payment Box (iFrame type only)', GOURL).'</h3></th>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th><br>'.__('Payment Box Width', GOURL).':</th>';
		$tmp .= '<td><br><input class="gourlnumeric" type="text" id="'.GOURL.'box_width" name="'.GOURL.'box_width" value="'.htmlspecialchars($this->options['box_width'], ENT_QUOTES).'" class="widefat"><label>'.__('px', GOURL).'</label><br><em>'.sprintf(__("Cryptocoin Payment Box Width, default 540px. <a href='%s'>See screenshot &#187;</a>", GOURL), plugins_url("/images/sizes.png", __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Payment Box Height', GOURL).':</th>';
		$tmp .= '<td><input class="gourlnumeric" type="text" id="'.GOURL.'box_height" name="'.GOURL.'box_height" value="'.htmlspecialchars($this->options['box_height'], ENT_QUOTES).'" class="widefat"><label>'.__('px', GOURL).'</label><br><em>'.__('Cryptocoin Payment Box Height, default 230px', GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Payment Box Style', GOURL).':</th><td>';
		$tmp .= '<p>';
		$tmp .= '<input type="radio" name="'.GOURL.'box_border" value="" '.$this->chk($this->options['box_border'], "").'> '.__('Box with Default Shadow', GOURL).$this->space(4);
		$tmp .= '<input type="radio" name="'.GOURL.'box_border" value="1" '.$this->chk($this->options['box_border'], 1).'> '.__('Box with light Border', GOURL).$this->space(4);
		$tmp .= '<input type="radio" name="'.GOURL.'box_border" value="2" '.$this->chk($this->options['box_border'], 2).'> '.__('Box without Border', GOURL);
		$tmp .= '</p>';
		$tmp .= '<p><input type="radio" name="'.GOURL.'box_border" value="3" '.$this->chk($this->options['box_border'], 3).'> '.__('Custom Style', GOURL).' -</p>';
		$tmp .= '<textarea id="'.GOURL.'box_style" name="'.GOURL.'box_style" class="widefat" style="height: 60px;">'.htmlspecialchars($this->options['box_style'], ENT_QUOTES).'</textarea><br><em>'.sprintf(__("Payment Box Visual CSS Style. <a href='%s'>See screenshot &#187;</a><br>Example: border-radius:15px;border:1px solid #eee;padding:3px 6px;margin:10px", GOURL), plugins_url("/images/styles.png", __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Payment Messages Style', GOURL).':</th><td>';
		$tmp .= '<p>';
		$tmp .= '<input type="radio" name="'.GOURL.'message_border" value="" '.$this->chk($this->options['message_border'], "").'> '.__('Messages with Default Shadow', GOURL).$this->space(4);
		$tmp .= '<input type="radio" name="'.GOURL.'message_border" value="1" '.$this->chk($this->options['message_border'], 1).'> '.__('Messages with light Border', GOURL).$this->space(4);
		$tmp .= '<input type="radio" name="'.GOURL.'message_border" value="2" '.$this->chk($this->options['message_border'], 2).'> '.__('Messages without Border', GOURL);
		$tmp .= '</p>';
		$tmp .= '<p><input type="radio" name="'.GOURL.'message_border" value="3" '.$this->chk($this->options['message_border'], 3).'> '.__('Custom Style', GOURL).' -</p>';
		$tmp .= '<textarea id="'.GOURL.'message_style" name="'.GOURL.'message_style" class="widefat" style="height: 50px;">'.htmlspecialchars($this->options['message_style'], ENT_QUOTES).'</textarea><br><em>'.sprintf(__("Payment Notifications CSS Style (when user click on payment button which is located at the bottom of payment box). <a href='%s'>See screenshot &#187;</a><br>Example: display:inline-block;max-width:580px;padding:15px 20px;box-shadow:0 0 3px #aaa;margin:7px;line-height:25px;", GOURL), plugins_url("/images/styles.png", __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr id="images"><th colspan="2"><br><br><br><h3>'.__('Images for Payment Box', GOURL).'</h3></th>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('1. Pay-Per-Product', GOURL).':</th><td>';
		$tmp .= '<p>';
		$tmp .= '<input type="radio" name="'.GOURL.'login_type" value="" '.$this->chk($this->options['login_type'], "").'> '.__('Display Website Login Form', GOURL).$this->space(4);
		$tmp .= '<input type="radio" name="'.GOURL.'login_type" value="1" '.$this->chk($this->options['login_type'], 1).'> '.__('Display Payment Login Image', GOURL).$this->space(4);
		$tmp .= '<br><em>'.sprintf(__("Unregistered visitors will see that on your webpages with <a href='%s'>Pay-Per-Product</a> items", GOURL), GOURL_ADMIN.GOURL."products").'</em>';
		$tmp .= '</p>';
		$tmp .= '</tr>';

		$i = 2;
		foreach($this->custom_images as $k => $v)
		{
			$tmp .= '<tr><th>'.$i.'. '.__($v, GOURL).':</th><td>';
			$tmp .= '<p><input type="radio" name="'.GOURL.$k.'" value="0" '.$this->chk($this->options[$k], 0).'> '.__('Default '.$v.' Image', GOURL).' -</p>';
			$tmp .= "<img src='".plugins_url('/images', __FILE__)."/".$k.".png' border='0'>";
			$tmp .= '<p><input type="radio" name="'.GOURL.$k.'" value="1" '.$this->chk($this->options[$k], 1).'> '.__('Custom Image', GOURL).' -</p>';
			if ($this->options[$k.'url'] && file_exists(GOURL_DIR."box/".$this->options[$k.'url'])) $tmp .= "<img src='".GOURL_DIR2."box/".$this->options[$k.'url']."' border='0'>"; else $this->options[$k.'url'] = "";
			$tmp .= "<input type='hidden' id='".GOURL.$k."url' name='".GOURL.$k."url' value='".htmlspecialchars($this->options[$k.'url'], ENT_QUOTES)."'>";
			if ($k == "img_plogin") 	$hint = __("This image will be displayed if your site requires registration for unregistered buyer before paying for a product/service.", GOURL);
			elseif ($k == "img_flogin") $hint = __("This image will be displayed if only registered users can buy/download your paid files.", GOURL);
			else $hint = "";
			$tmp .= '<input type="file" accept="image/*" id="'.GOURL.$k.'2" name="'.GOURL.$k.'2" class="widefat"><br><em>'.$hint." ".__('Allowed images: JPG, GIF, PNG.', GOURL).'</em>';
			$tmp .= '</td></tr>';
			$i++;
		}

		$tmp .= '<tr><th colspan="2"><h3>'.__('Other', GOURL).'</h3></th>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th><br>'.__('Records Per Page', GOURL).':</th>';
		$tmp .= '<td><br><input class="gourlnumeric" type="text" id="'.GOURL.'rec_per_page" name="'.GOURL.'rec_per_page" value="'.htmlspecialchars($this->options['rec_per_page'], ENT_QUOTES).'" class="widefat"><label>'.__('records', GOURL).'</label><br><em>'.__("Set number of records per page in tables 'All Payments' and 'All Files'", GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th><br>'.__('Popup Message', GOURL).':</th>';
		$tmp .= '<td><br><input type="text" id="'.GOURL.'popup_message" name="'.GOURL.'popup_message" value="'.htmlspecialchars($this->options['popup_message'], ENT_QUOTES).'" class="widefat"><br><em>'.__('Pay-Per-Download: A pop-up message that a visitor will see when trying to download a paid file without payment<br>Default text: It is a Paid Download ! Please pay below It', GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Additional Fields', GOURL).':</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'file_columns" id="'.GOURL.'file_columns" value="1" '.$this->chk($this->options['file_columns'], 1).' class="widefat"><br><em>'.__("Pay-Per-Download: If box is checked, display on 'All Payments' statistics page two additional columns 'File Downloaded By User?' and 'File Downloaded Time'. Use it if you sell files online (Pay-Per-Download)", GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Reverse Bitcoin Chart', GOURL).':</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'chart_reverse" id="'.GOURL.'chart_reverse" value="1" '.$this->chk($this->options['chart_reverse'], 1).' class="widefat"><br><em>'.sprintf(__("<a href='%s'>Bitcoin Chart</a>: Reverse the X axis of time", GOURL), GOURL_ADMIN.GOURL.'#chart').'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '</table>';

		$tmp .= '</div></div>';
		$tmp .= '</form>';

		$tmp .= '</div>';

		echo $tmp;

		return true;
	}



	/*
	 *  25.
	*/
	private function payment_box_style()
	{
		$opt = $this->options["box_border"];

		if (!$opt) $tmp = "";
		elseif ($opt == 1) $tmp = "border-radius:15px;border:1px solid #eee;padding:3px 6px;margin:10px;";
		elseif ($opt == 2) $tmp = "padding:5px;margin:10px;";
		elseif ($opt == 3) $tmp = $this->options["box_style"];

		return $tmp;
	}



	/*
	 *  26.
	*/
	private function payment_message_style()
	{
		$opt = $this->options["message_border"];

		if (!$opt) $tmp = "";
		elseif ($opt == 1) $tmp = "display:inline-block;max-width:580px;padding:15px 20px;border:1px solid #eee;margin:7px;line-height:25px;";
		elseif ($opt == 2) $tmp = "display:inline-block;max-width:580px;padding:15px 20px;margin:7px;line-height:25px;";
		elseif ($opt == 3) $tmp = $this->options["message_style"];

		return $tmp;
	}




	/*
	 *
	*/
    private function test_gourl_connection($one_key = true)
    {
        $messages = array();
        $arr = $arr2 = array();

        foreach ($this->coin_names as $k => $v)
        if (!$one_key || !$arr)
        {
            $public_key 	= $this->options[$v.'public_key'];
            $private_key 	= $this->options[$v.'private_key'];

            if ($public_key || $private_key) $arr[$v] = array("public_key" => $public_key, "private_key" => $private_key);
            if ($private_key) $arr2[] = $private_key;
        }

        if (!$arr) return array("error" => true, "desc" => 'Please add your GoUrl Cryptobox Public/Private Keys on this settings page');
		elseif(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $arr2));


        include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");

        foreach($arr as $k => $v)
        {

            $options = array(
                "public_key"  => $v["public_key"],
                "private_key" => $v["private_key"],
                "orderID"     => "test_order",
                "userID"      => "test_user",
                "amountUSD"   => 10,
                "period"      => "1 DAY",
                );

            $box = new Cryptobox ($options);

            $data = $box->get_json_values();

            if (!isset($data["status"]) || !isset($data["texts"]) || !in_array($data["status"], array("payment_received", "payment_not_received")))
            {
                if (isset($data["data_hash"])) unset($data["data_hash"]);
                if (isset($data["err"]) && $data["err"])    $messages[$k] = ucwords($k) . " - " . sprintf( __("GoUrl.io server (<a target='_blank' href='%s'>Raw Data Url</a>) return error: %s", GOURL), $box->cryptobox_json_url(), "<pre>" . print_r($data, true) . "</pre>");
                else                                        $messages[$k] = ucwords($k) . " - " . sprintf( __("Unable to connect to Gourl.io server through CURL - <a target='_blank' href='%s'>Raw Data Url</a><br>Check your network connection / add GoUrl.io IPs in <a target='_blank' href='%s'>whitelist</a><br>Your website received data: %s", GOURL), $box->cryptobox_json_url(), "https://gourl.io/api-php.html#cdn", "<pre>" . ($data?print_r($data, true):"- empty - &#160; <a target='_blank' href='".$box->cryptobox_json_url()."'>See Raw Data &#187;</a>") . "</pre>");
                $messages["error"] = true;
            }
            else $messages[$k] = "<div style='color:green !important'>" . ucwords($k) . " - " . sprintf(__('Connection to GoUrl.io Server is OK! &#160; <a target="_blank" href="%s">Raw Data Url &#187;</a> &#160; &#160; Payment Box <a href="%s">Preview &#187;</a>', GOURL), $box->cryptobox_json_url(), GOURL_ADMIN.GOURL.'payperview&example=2&gourlcryptocoin='.$k.'&preview=true#previewcrypto') . "</div>";
        }

        return $messages;
    }




	/**************** COMMON FUNCTIONS **************************/

	/*
	 *  27.
	*/
	private function get_record($page)
	{
		global $wpdb;

		if 		($page == "file") 	{ $idx = "fileID"; 	$table = "crypto_files"; }
		elseif 	($page == "product") 	{ $idx = "productID"; 	$table = "crypto_products"; }
		else 	return false;

		$this->record = array();

		if ($this->id)
		{
			$tmp = $wpdb->get_row("SELECT * FROM ".$table." WHERE ".$idx." = ".intval($this->id)." LIMIT 1", ARRAY_A);
			if (!$tmp) { header('Location: '.GOURL_ADMIN.GOURL.$page); die(); }
		}

		// values - from db or default
		foreach ($this->record_fields as $key => $val) $this->record[$key] = ($this->id) ? $tmp[$key] : $val;

		return true;
	}



	/*
	 *  28.
	*/
	private function post_record()
	{
		$this->record = array();

		foreach ($this->record_fields as $key => $val)
		{
			$this->record[$key] = (isset($_POST[GOURL.$key])) ? $_POST[GOURL.$key] : "";
			if (is_string($this->record[$key])) $this->record[$key] = trim(stripslashes($this->record[$key]));
		}

		return true;
	}






	/**************** B. PAY-PER-FILE ************************************/


	/*
	 *  29.
	*/
	private function check_download()
	{
		$this->record_errors = array();

		if ($this->record["fileID"] != $this->id) $this->record_errors[] = __('Invalid File ID, Please reload page', GOURL);


		// uploaded file
		$file = (isset($_FILES[GOURL."fileName2"]["name"]) && $_FILES[GOURL."fileName2"]["name"]) ? $_FILES[GOURL."fileName2"] : "";
		if ($file)
        {
            $this->record["fileName"] = $this->upload_file($file, "files");
        }
		elseif ($this->record["fileUrl"])
        {
            if (!strpos($this->record["fileUrl"], "://")) $this->record["fileUrl"] = "http://" . $this->record["fileUrl"];
            $ch = curl_init();
            curl_setopt ($ch, CURLOPT_URL, $this->record["fileUrl"]);
            curl_setopt ($ch, CURLOPT_NOBODY, true);
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt ($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
            curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko");
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 7);
            curl_setopt ($ch, CURLOPT_TIMEOUT, 7);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if($httpCode == 404 || $httpCode == 0) $this->record_errors[] = sprintf(__('File not exists on URL - %s', GOURL), $this->record["fileUrl"]);
            curl_close($ch);
        }
        if ($this->record["fileName"] && $this->record["fileUrl"]) $this->record_errors[] = __('Your Local File &nbsp;/&nbsp; Remote File URL - please use one of them only (not both)', GOURL);
        if (!$this->record["fileName"] && !$this->record["fileUrl"]) $this->record_errors[] = __('Your Local File &nbsp;OR&nbsp; Remote File URL - cannot be empty', GOURL);


		// uploaded featured image
		$file = (isset($_FILES[GOURL."image2"]["name"]) && $_FILES[GOURL."image2"]["name"]) ? $_FILES[GOURL."image2"] : "";
		if ($file) $this->record["image"] = $this->upload_file($file, "images");
		elseif (!$this->record["image"])  $this->record_errors[] = __('Featured Image - select image', GOURL);


		if (!$this->record["fileTitle"]) 								$this->record_errors[] = __('Title - cannot be empty', GOURL);
		elseif (mb_strlen($this->record["fileTitle"]) > 100) 			$this->record_errors[] = __('Title - Max size 100 symbols', GOURL);

		$this->record["priceUSD"] = str_replace(",", "", $this->record["priceUSD"]);
		$this->record["priceCoin"] = str_replace(",", "", $this->record["priceCoin"]);
		if ($this->record["priceUSD"] == 0 && $this->record["priceCoin"] == 0) 	$this->record_errors[] = __('Price - cannot be empty', GOURL);
		if ($this->record["priceUSD"] != 0 && $this->record["priceCoin"] != 0) 	$this->record_errors[] = __('Price - use price in USD or in Cryptocoins. You cannot place values in two boxes together', GOURL);
		if ($this->record["priceUSD"] != 0 && (!is_numeric($this->record["priceUSD"]) || round($this->record["priceUSD"], 2) != $this->record["priceUSD"] || $this->record["priceUSD"] < 0.01 || $this->record["priceUSD"] > 1000000)) $this->record_errors[] = sprintf(__('Price - %s USD - invalid value. Min value: 0.01 USD', GOURL), $this->record["priceUSD"]);
		if ($this->record["priceCoin"] != 0 && (!is_numeric($this->record["priceCoin"]) || round($this->record["priceCoin"], 4) != $this->record["priceCoin"] || $this->record["priceCoin"] < 0.0001 || $this->record["priceCoin"] > 500000000)) $this->record_errors[] = sprintf(__('Price - %s %s - invalid value. Min value: 0.0001 %s. Allow 4 digits max after floating point', GOURL), $this->record["priceCoin"], $this->record["priceLabel"], $this->record["priceLabel"]);

		if ($this->record["priceLabel"] && !isset($this->coin_names[$this->record["priceLabel"]])) $this->record_errors[] = sprintf(__("Price label '%s' - invalid value", GOURL), $this->record["priceLabel"]);

		if ($this->record["purchases"] && (!is_numeric($this->record["purchases"]) || round($this->record["purchases"]) != $this->record["purchases"] || $this->record["purchases"] < 0)) $this->record_errors[] = __('Purchase Limit - invalid value', GOURL);

		if (!$this->record["expiryPeriod"]) $this->record_errors[] = __("Field 'Expiry Period' - cannot be empty", GOURL);
		elseif (!in_array($this->record["expiryPeriod"], $this->expiry_period))	$this->record_errors[] = __("Field 'Expiry Period' - invalid value", GOURL);

		if (!in_array($this->record["userFormat"], $this->store_visitorid)) $this->record_errors[] = __('Store Visitor IDs - invalid value', GOURL);

		if (!isset($this->languages[$this->record["lang"]])) $this->record_errors[] = __('PaymentBox Language - invalid value', GOURL);

		if (!$this->record["defCoin"]) $this->record_errors[] = __("Field 'PaymentBox Coin' - cannot be empty", GOURL);
		elseif (!isset($this->coin_names[$this->record["defCoin"]])) $this->record_errors[] = __("Field 'PaymentBox Coin' - invalid value", GOURL);
		elseif (!isset($this->payments[$this->record["defCoin"]])) {
 			if (!$this->payments) $this->record_errors[] = sprintf(__("You must choose at least one payment method. Please enter your GoUrl Public/Private Keys on <a href='%s'>settings page</a>. Instruction <a href='%s'>here &#187;</a>", GOURL),  GOURL_ADMIN.GOURL.'settings#gourlcurrencyconverterapi_key', GOURL_ADMIN.GOURL."#i3");
			$this->record_errors[] = sprintf( __("Field 'PaymentBox Coin' - payments in %s not available. Please re-save record", GOURL), $this->coin_names[$this->record["defCoin"]]);
		}
		elseif ($this->record["priceCoin"] != 0 && $this->record["defCoin"] != $this->record["priceLabel"])
		{
			if (isset($this->payments[$this->record["priceLabel"]])) $this->record["defCoin"] = $this->record["priceLabel"];
			else $this->record_errors[] = sprintf(__("Field 'PaymentBox Coin' - please select '%s' because you have entered price in %s", GOURL), $this->coin_names[$this->record["priceLabel"]], $this->coin_names[$this->record["priceLabel"]]);
		}

		if ($this->record["priceCoin"] != 0 && !$this->record["defShow"]) $this->record["defShow"] = 1;

		if (!is_numeric($this->record["imageWidth"]) || round($this->record["imageWidth"]) != $this->record["imageWidth"] || $this->record["imageWidth"] < 1 || $this->record["imageWidth"] > 2000) $this->record_errors[] = __('Invalid Image Width. Allowed 1..2,000px', GOURL);


		return true;
	}




	/*
	 *  30.
	*/
	private function save_download()
	{
		global $wpdb;

		$dt = gmdate('Y-m-d H:i:s');

		if (!(is_admin() && is_user_logged_in() && current_user_can('administrator')))
		{
			$this->record_errors[] = __('You don\'t have permission to edit this page. Please login as ADMIN user!', GOURL);
			return false;
		}

		$fileSize = ($this->record['fileName']) ? filesize(GOURL_DIR."files/".$this->record['fileName']) : 0;

		if ($this->record['priceUSD'] <= 0)  $this->record['priceUSD'] = 0;
		if ($this->record['priceCoin'] <= 0 || $this->record['priceUSD'] > 0) { $this->record['priceCoin'] = 0; $this->record['priceLabel'] = ""; }

		if ($this->id)
		{
			$sql = "UPDATE crypto_files
					SET
						fileTitle 	= '".esc_sql($this->record['fileTitle'])."',
						active 		= '".$this->record['active']."',
						fileName 	= '".esc_sql($this->record['fileName'])."',
						fileUrl 	= '".esc_sql($this->record['fileUrl'])."',
						fileText	= '".esc_sql($this->record['fileText'])."',
						fileSize 	= ".$fileSize.",
						priceUSD 	= ".$this->record['priceUSD'].",
						priceCoin 	= ".$this->record['priceCoin'].",
						priceLabel 	= '".$this->record['priceLabel']."',
						purchases 	= '".$this->record['purchases']."',
						userFormat 	= '".$this->record['userFormat']."',
						expiryPeriod= '".esc_sql($this->record['expiryPeriod'])."',
						lang 		= '".$this->record['lang']."',
						defCoin		= '".esc_sql($this->record['defCoin'])."',
						defShow 	= '".$this->record['defShow']."',
						image 		= '".esc_sql($this->record['image'])."',
						imageWidth 	= '".$this->record['imageWidth']."',
						priceShow	= '".$this->record['priceShow']."',
						updatetime 	= '".$dt."'
					WHERE fileID 	= ".$this->id."
					LIMIT 1";
		}
		else
		{
			$sql = "INSERT INTO crypto_files (fileTitle, active, fileName, fileUrl, fileText, fileSize, priceUSD, priceCoin, priceLabel, purchases, userFormat, expiryPeriod, lang, defCoin, defShow, image, imageWidth, priceShow, paymentCnt, updatetime, createtime)
					VALUES (
							'".esc_sql($this->record['fileTitle'])."',
							1,
							'".esc_sql($this->record['fileName'])."',
							'".esc_sql($this->record['fileUrl'])."',
							'".esc_sql($this->record['fileText'])."',
							".$fileSize.",
							".$this->record['priceUSD'].",
							".$this->record['priceCoin'].",
							'".$this->record['priceLabel']."',
							'".$this->record['purchases']."',
							'".$this->record['userFormat']."',
							'".esc_sql($this->record['expiryPeriod'])."',
							'".$this->record['lang']."',
							'".esc_sql($this->record['defCoin'])."',
							'".$this->record['defShow']."',
							'".esc_sql($this->record['image'])."',
							'".$this->record['imageWidth']."',
							'".$this->record['priceShow']."',
							0,
							'".$dt."',
							'".$dt."'
						)";
		}

		if ($wpdb->query($sql) === false) $this->record_errors[] = "Error in SQL : " . $sql;
		elseif (!$this->id) $this->id = $wpdb->insert_id;

		return true;
	}




	/*
	 *  31.
	*/
	public function page_newfile()
	{
		if ($this->is_nonadmin_user()) return true;

		$preview = ($this->id && isset($_GET["preview"]) && $_GET["preview"] == "true") ? true : false;

		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Record has been saved <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";

		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';


		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title($this->id?__('Edit File', GOURL):__('New File', GOURL), 2);
		$tmp .= $message;

		$short_code = '['.GOURL_TAG_DOWNLOAD.' id="'.$this->id.'"]';

		if ($preview)
		{
			$tmp .= "<div class='postbox'>";
			$tmp .= "<h3 class='hndle'>".sprintf(__('Preview Shortcode &#160; &#160; %s', GOURL), $short_code);
			$tmp .= "<a href='".GOURL_ADMIN.GOURL."file&id=".$this->id."' class='gourlright ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
			$tmp .= "</h3>";
			$tmp .= "<div class='inside'>";
			$tmp .= $this->shortcode_download(array("id"=>$this->id));
			$tmp .= "</div>";
			$tmp .= '<div class="gourlright"><small>'.__('Shortcode', GOURL).': &#160;  '.$short_code.'</small></div>';
			$tmp .= "</div>";
		}

		$tmp .= "<form enctype='multipart/form-data' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."file&id=".$this->id."'>";

		$tmp .= "<div class='postbox".($preview?" previewactive":"")."'>";

		$tmp .= '<div class="alignright"><br>';
		if ($this->id && $this->record['paymentCnt']) $tmp .= "<a style='margin-top:-7px' href='".GOURL_ADMIN.GOURL."payments&s=file_".$this->id."' class='".GOURL."button button-secondary'>".sprintf(__('Sold %d copies', GOURL), $this->record['paymentCnt'])."</a>".$this->space();
		if ($this->id) $tmp .= '<a href="'.GOURL_ADMIN.GOURL.'file">'.__('New File', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'file&id='.$this->id.'">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'files">'.__('All Paid Files', GOURL).'</a>';
		$tmp .= '</div>';

		$tmp .= "<h3 class='hndle'>".__(($this->id?'Edit file':'Upload New File, Music, Picture, Video'), GOURL)."</h3>";
		$tmp .= "<div class='inside'>";

		$tmp .= '<input type="hidden" name="'.$this->adminform.'" value="'.GOURL.'save_download" />';
		$tmp .= wp_nonce_field( $this->admin_form_key );

		$tmp .= '<div class="alignright">';
		$tmp .= '<img id="gourlsubmitloading" src="'.plugins_url('/images/loading.gif', __FILE__).'" border="0">';
		$tmp .= '<input type="submit" onclick="this.value=\''.__('Please wait...', GOURL).'\';document.getElementById(\'gourlsubmitloading\').style.display=\'inline\';return true;" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Record', GOURL).'">';
		if ($this->id && !$preview) $tmp .= "<a href='".GOURL_ADMIN.GOURL."file&id=".$this->id."&gourlcryptocoin=".$this->coin_names[$this->record['defCoin']]."&gourlcryptolang=".$this->record['lang']."&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview', GOURL)."</a>".$this->space(2);
		$tmp .= "<a target='_blank' href='".plugins_url('/images/tagexample_download_full.png', __FILE__)."' class='".GOURL."button button-secondary'>".__('Instruction', GOURL)."</a>".$this->space();
		$tmp .= '</div><br><br>';


		$tmp .= "<table class='".GOURL."table ".GOURL."file'>";

		if ($this->id)
		{
			$tmp .= '<tr><th>'.__('File ID', GOURL).':</th>';
			$tmp .= '<td><b>'.$this->record['fileID'].'</b></td>';
			$tmp .= '</tr>';
			$tmp .= '<tr><th>'.__('Shortcode', GOURL).':</th>';
			$tmp .= '<td><b>['.GOURL_TAG_DOWNLOAD.' id="'.$this->id.'"]</b><br><em>'.sprintf(__("Just <a target='_blank' href='%s'>add this shortcode</a> to any your page or post (in html view) and cryptocoin payment box will be display", GOURL), plugins_url('/images/tagexample_download_full.png', __FILE__)).'</em></td>';
			$tmp .= '</tr>';
		}

		$tmp .= '<tr><th>'.__('Title', GOURL).':';
		$tmp .= '<input type="hidden" name="'.GOURL.'fileID" id="'.GOURL.'fileID" value="'.htmlspecialchars($this->record['fileID'], ENT_QUOTES).'">';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'fileTitle" id="'.GOURL.'fileTitle" value="'.htmlspecialchars($this->record['fileTitle'], ENT_QUOTES).'" class="widefat"><br><em>'.__('Title / Friendly name for the file. Visitors will see this title', GOURL).'</em></td>';
		$tmp .= '</tr>';

		if ($this->id)
		{
			$tmp .= '<tr><th>'.__('Active ?', GOURL).'</th>';
			$tmp .= '<td><input type="checkbox" name="'.GOURL.'active" id="'.GOURL.'active" value="1" '.$this->chk($this->record['active'], 1).' class="widefat"><br><em>'.__('If box is not checked, visitors cannot pay you for this file', GOURL).'</em></td>';
			$tmp .= '</tr>';
		}

		$tmp .= '<tr><th>'.__('Your File', GOURL).':</th>';
		$tmp .= '<td>a) Local File &nbsp; <select name="'.GOURL.'fileName" id="'.GOURL.'fileName" onchange="document.getElementById(\''.GOURL.'fileSize_info\').innerHTML=\'\'; var v=document.getElementById(\''.GOURL.'preview\');v.style.display=(this.value?\'inline\':\'none\');v.title=this.value; v.href=\''.GOURL_ADMIN.GOURL.'&'.GOURL_PREVIEW.'=\'+this.value;">';
		$tmp .= '<option value="">-- '.__('Select pre-uploaded file', GOURL).' --</option>';


		$files = array();
		if (file_exists(GOURL_DIR."files") && is_dir(GOURL_DIR."files"))
		{
			$all_files = scandir(GOURL_DIR."files");
			for ($i=0; $i<sizeof($all_files); $i++)
			if (!in_array($all_files[$i], array(".", "..", "index.htm", "index.html", "index.php", ".htaccess", "gourl_ipn.php", "gourl.hash")) && is_file(GOURL_DIR.'/files/'.$all_files[$i]))
			{
				$files[] = $all_files[$i];
			}
	}

	for ($i=0; $i<sizeof($files); $i++)$tmp .= '<option value="'.htmlspecialchars($files[$i], ENT_QUOTES).'"'.$this->sel($files[$i], $this->record['fileName']).'>'.htmlspecialchars($files[$i], ENT_QUOTES).'</option>';


	$tmp .= "</select>";
	$tmp .= '<label> &#160; <small><a '.($this->record['fileName']?'':'style="display:none"').' id="'.GOURL.'preview" title="'.$this->record['fileName'].'" href="'.GOURL_ADMIN.GOURL.'&'.GOURL_PREVIEW.'='.$this->record['fileName'].'">'.__('Download', GOURL).'</a> <span id="'.GOURL.'fileSize_info">'.($this->record['fileSize']?$this->space(2).__('size', GOURL).': '.gourl_byte_format($this->record['fileSize']):'').'</span></small></label>';
	$tmp .= '<br><em>'.sprintf(__('If the file has already been uploaded to the server, you can select that file from this drop-down list (files folder %s)<br><strong>OR</strong><br> upload new file below -', GOURL), GOURL_DIR2."files").'</em>';
	$tmp .= '<input type="file" accept=".jpg,.jpeg,.png,.gif,.mp3,.aac,.ogg,.avi,.mov,.mp4,.mkv,.txt,.doc,.pdf,.iso,.7z,.rar,.zip" name="'.GOURL.'fileName2" id="'.GOURL.'fileName2" class="widefat"><br><em>'.__("Allowed: .jpg .png .gif .mp3 .aac .ogg .avi .mov .mp4 .mkv .txt .doc .pdf .iso .7z .rar .zip", GOURL)."<br>".__('Please use simple file names on <b>English</b>. Click on the Choose File button. Locate the file that you want to use, left click on it and click on the Open button. The path of the file that you have selected will appear in the File field', GOURL).'</em>';
	$tmp .= '<br><strong>OR</strong><p>b) '.__('Alternatively enter Remote File URL', GOURL).' -</p>';
	$tmp .= '<input type="text" class="widefat" name="'.GOURL.'fileUrl" id="'.GOURL.'fileUrl" value="'.htmlspecialchars($this->record['fileUrl'], ENT_QUOTES).'">';
	if ($this->record['fileUrl']) $tmp .= '<br><em><a target="_blank" href="'.$this->record['fileUrl'].'">'.__('Test Your Url Now &#187;', GOURL).'</a></em>';
	$tmp .= '<br><br></td>';
	$tmp .= '</tr>';


	$tmp .= '<tr><th>'.__('Price', GOURL).':</th><td>';
	$tmp .= '<input type="text" class="gourlnumeric" name="'.GOURL.'priceUSD" id="'.GOURL.'priceUSD" value="'.htmlspecialchars($this->record['priceUSD'], ENT_QUOTES).'"><label><b>'.__('USD', GOURL).'</b></label>';
	$tmp .= $this->space(2).'<label>'.__('or', GOURL).'</label>'.$this->space(5);
	$tmp .= '<input type="text" class="gourlnumeric2" name="'.GOURL.'priceCoin" id="'.GOURL.'priceCoin" value="'.htmlspecialchars($this->record['priceCoin'], ENT_QUOTES).'">'.$this->space();
	$tmp .= '<select name="'.GOURL.'priceLabel" id="'.GOURL.'priceLabel">';
	foreach($this->coin_names as $k => $v) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['priceLabel']).'>'.$k.$this->space().'('.$v.')</option>';
	$tmp .= '</select>';
	$tmp .= '<br><em>'.sprintf(__("Please specify price in USD or in Cryptocoins. You cannot place prices in two boxes together. If you want to accept multiple coins - please use price in USD, payment box will automatically convert that USD amount to cryptocoin amount using today live cryptocurrency exchange rates (updated every 30min). Using that functionality (price in USD), you don't need to worry if cryptocurrency prices go down or go up. Visitors will pay you all times the actual price which is linked on daily exchange price in USD on the time of purchase. Also you can use <a target='_blank' href='%s'>Poloniex 'autosell' feature</a> (auto trade your cryptocoins to USD).", GOURL), "https://poloniex.com/").'</em>';
	$tmp .= '</td></tr>';

	$tmp .= '<tr><th>'.__('Show File Name/Price', GOURL).':</th>';
	$tmp .= '<td><input type="checkbox" name="'.GOURL.'priceShow" id="'.GOURL.'priceShow" value="1" '.$this->chk($this->record['priceShow'], 1).' class="widefat"><br><em>'.__('If box is checked, visitors will see approximate file price in USD and uploaded file name/size', GOURL).'</em></td>';
	$tmp .= '</tr>';

	$tmp .= '<tr><th>'.__('Purchase Limit', GOURL).':</th>';
	$tmp .= '<td><input type="text" class="gourlnumeric" name="'.GOURL.'purchases" id="'.GOURL.'purchases" value="'.htmlspecialchars($this->record['purchases'], ENT_QUOTES).'"><label>'.__('copies', GOURL).'</label><br><em>'.__('The maximum number of times a file may be purchased/downloaded. Leave blank or set to 0 for unlimited number of purchases/downloads', GOURL).'</em></td>';
	$tmp .= '</tr>';

	$tmp .= '<tr><th>'.__('Expiry Period', GOURL).':</th>';
	$tmp .= '<td><select name="'.GOURL.'expiryPeriod" id="'.GOURL.'expiryPeriod">';

		foreach($this->expiry_period as $v)
			if (!stripos($v, "minute")) $tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->record['expiryPeriod']).'>'.$v.'</option>';

	$tmp .= '</select>';
	$tmp .= '<br><em>'.__("Period after which the payment becomes obsolete and new Cryptocoin Payment Box will be shown for this file (you can use it to take new payments from users periodically on daily/monthly basis).<br>If Expiry Period more than '2days', please use option - Store Visitor IDs: 'Registered Users'; because 'Cookie/Session' not safety for long expiry period", GOURL).'</em></td>';
	$tmp .= '</tr>';


	$tmp .= '<tr><th>'.__('Store Visitor IDs', GOURL).':</th>';
	$tmp .= '<td><select name="'.GOURL.'userFormat" id="'.GOURL.'userFormat">';

		foreach($this->store_visitorid as $v)
			$tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->record['userFormat']).'>'.($v=="MANUAL"?"Registered Users":$v).'</option>';

	$tmp .= '</select>';
	$tmp .= '<br><em>'.__("For Unregistered Your Website Visitors - Save auto-generated unique visitor ID in cookies, sessions or use the IP address to decide unique visitors (without use cookies).<br>If you use 'session', value in field - Expiry Period will be ignored.  PHP sessions have default life time until the browser is closed.<br>-----<br>If you have registration on the website enabled, <u>please use option 'Registered Users'</u> - only registered users can pay/download this file (Gourl will use wordpress userID instead of cookies for user identification). It is much better to use 'Registered users' than 'Cookie/Session/Ipaddress'", GOURL).'</em></td>';
	$tmp .= '</tr>';



	$tmp .= '<tr><th>'.__('PaymentBox Language', GOURL).':</th>';
	$tmp .= '<td><select name="'.GOURL.'lang" id="'.GOURL.'lang">';

		foreach($this->languages as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['lang']).'>'.$v.'</option>';

	$tmp .= '</select>';
	$tmp .= '<br><em>'.__('Default Payment Box Localisation', GOURL).'</em></td>';
			$tmp .= '</tr>';



	$tmp .= '<tr><th>'.__('PaymentBox Coin', GOURL).':</th>';
	$tmp .= '<td><select name="'.GOURL.'defCoin" id="'.GOURL.'defCoin">';

		foreach($this->payments as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['defCoin']).'>'.$v.'</option>';

	$tmp .= '</select>';
	$tmp .= '<span class="gourlpayments">' . __('Activated Payments :', GOURL) . " <a href='".GOURL_ADMIN.GOURL."settings'><b>" . ($this->payments?implode(", ", $this->payments):__('- Please Setup -', GOURL)) . '</b></a></span>';
	$tmp .= '<br><em>'.__('Default Coin in Payment Box', GOURL).'</em></td>';
	$tmp .= '</tr>';



	$tmp .= '<tr><th>'.__('Use Default Coin only:', GOURL).'</th>';
	$tmp .= '<td><input type="checkbox" name="'.GOURL.'defShow" id="'.GOURL.'defShow" value="1" '.$this->chk($this->record['defShow'], 1).' class="widefat"><br><em>'.__("If box is checked, payment box will accept payments in one default coin 'PaymentBox Coin' for this file (no multiple coins). Please use price in USD if you want to accept multiple coins", GOURL).'</em></td>';
	$tmp .= '</tr>';


	$tmp .= '<tr><th>'.__('Description (Optional)', GOURL).':</th><td>';
	echo $tmp;
	wp_editor( $this->record['fileText'], GOURL.'fileText', array('textarea_name' => GOURL.'fileText', 'quicktags' => true, 'media_buttons' => false, 'textarea_rows' => 8, 'wpautop' => false));
	$tmp  = '<br><em>'.__('Short File Description', GOURL).'</em>';
	$tmp .= '</td></tr>';



	$tmp .= '<tr><th>'.__('Featured Image', GOURL).':</th><td>';

		if (file_exists(GOURL_DIR."images") && is_dir(GOURL_DIR."images"))
			{
				$arr = scandir(GOURL_DIR."images/");
				sort($arr);
				foreach ($arr as $v)
				if (in_array(substr($v, -4), array(".png", ".jpg", ".jpeg", ".gif")))
				{
					$tmp .= '<div class="gourlimagebox"><input type="radio" name="'.GOURL.'image" id="'.$v.'" value="'.$v.'"'.$this->chk($this->record['image'], $v).'><label for="'.$v.'"><img width="100" src="'.GOURL_DIR2."images/".$v.'" border="0"></label></div>';
				}
			}

	$tmp .= '<div class="clear"></div>';
	$tmp .= '... '.__('OR', GOURL).' ...';
	$tmp .= '<div class="clear"></div>';
	$tmp .= '<div class="gourlimagebox"><input type="radio" name="'.GOURL.'image" value=""'.$this->chk($this->record['image'], '').'>'.__('Custom Featured Image', GOURL).'<br>';
	$tmp .= '<input type="file" accept="image/*" id="'.GOURL.'image2" name="'.GOURL.'image2" class="widefat"><br><em>'.__('This featured image represent your uploaded file above. Max sizes: 800px x 600px, allowed images: JPG, GIF, PNG.', GOURL).'</em></div>';
	$tmp .= '</td></tr>';

	$tmp .= '<tr><th>'.__('Image Width', GOURL).':</th>';
	$tmp .= '<td><input type="text" class="gourlnumeric" name="'.GOURL.'imageWidth" id="'.GOURL.'imageWidth" value="'.htmlspecialchars($this->record['imageWidth'], ENT_QUOTES).'"><label>'.__('px', GOURL).'</label><br><em>'.__('Your featured image width', GOURL).'</em></td>';
	$tmp .= '</tr>';


	if ($this->id)
	{
		$tmp .= '<tr><th>'.__('Total Sold', GOURL).':</th>';
		$tmp .= '<td><input type="hidden" name="'.GOURL.'paymentCnt" id="'.GOURL.'paymentCnt" value="'.htmlspecialchars($this->record['paymentCnt'], ENT_QUOTES).'"><b>'.$this->record['paymentCnt'].' '.__('copies', GOURL).'</b></td>';
		$tmp .= '</tr>';

		if ($this->record['paymentCnt'])
		{
			$tmp .= '<tr><th>'.__('Latest Received Payment', GOURL).':</th>';
			$tmp .= '<td><input type="hidden" name="'.GOURL.'paymentTime" id="'.GOURL.'paymentTime" value="'.htmlspecialchars($this->record['paymentTime'], ENT_QUOTES).'"><b>'.date('d M Y, H:i:s a', strtotime($this->record['paymentTime'])).' GMT</b></td>';
			$tmp .= '</tr>';
		}

		if ($this->record['updatetime'] && $this->record['updatetime'] != $this->record['createtime'])
		{
			$tmp .= '<tr><th>'.__('Record Updated', GOURL).':</th>';
			$tmp .= '<td><input type="hidden" name="'.GOURL.'updatetime" id="'.GOURL.'updatetime" value="'.htmlspecialchars($this->record['updatetime'], ENT_QUOTES).'">'.date('d M Y, H:i:s a', strtotime($this->record['updatetime'])).' GMT</td>';
			$tmp .= '</tr>';
		}

		$tmp .= '<tr><th>'.__('Record Created', GOURL).':</th>';
		$tmp .= '<td><input type="hidden" name="'.GOURL.'createtime" id="'.GOURL.'createtime" value="'.htmlspecialchars($this->record['createtime'], ENT_QUOTES).'">'.date('d M Y, H:i:s a', strtotime($this->record['createtime'])).' GMT</td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Custom Actions', GOURL).':</th>';
		$tmp .= '<td><em>'.sprintf(__("Optional - add in file gourl_ipn.php code below. <a href='%s'>Read more &#187;</a>", GOURL), GOURL_ADMIN.GOURL."#i5");
		$tmp .= '<br><i>case "file_'.$this->id.'": &#160; &#160; // order_ID = file_'.$this->id.'<br>// ...your_code...<br>break;</i></em>';
		$tmp .= '</td></tr>';
	}


	$tmp .= '</table>';


	$tmp .= '</div></div>';
	$tmp .= '</form></div>';

	echo $tmp;

	return true;
}







	/*
	*  32.
	*/
	public function page_files()
	{
		global $wpdb;

 		if ($this->is_nonadmin_user()) return true;

		if (isset($_GET["intro"]))
		{
			$intro = intval($_GET["intro"]);
			update_option(GOURL."page_files_intro", $intro);
		}
		else $intro = get_option(GOURL."page_files_intro");


		$search = "";
		if (isset($_GET["s"]) && trim($_GET["s"]))
		{
			$s = esc_sql(trim(mb_substr($_GET["s"], 0, 50)));

			if ($s == "sold") 			$search = " && paymentCnt > 0";
			elseif ($s == "active") 	$search = " && active != 0";
			elseif ($s == "inactive") 	$search = " && active = 0";
			elseif (strtolower($s) == "registered users") $search = " && userFormat = 'MANUAL'";
			elseif (in_array(strtolower($s), $this->coin_names)) $search = " && (priceLabel = '".array_search(strtolower($s), $this->coin_names)."' || defCoin = '".array_search(strtolower($s), $this->coin_names)."')";
			elseif (isset($this->coin_names[strtoupper($s)])) $search = " && (priceLabel = '".strtoupper($s)."' || defCoin = '".strtoupper($s)."')";

			if (!$search)
			{
				if (in_array(ucwords(strtolower($s)), $this->languages)) $s = esc_sql(array_search(ucwords(strtolower($s)), $this->languages));
				if (substr(strtoupper($s), -4) == " USD") $s = substr($s, 0, -4);

				$search = " && (fileTitle LIKE '%".$s."%' || fileName LIKE '%".$s."%' || fileUrl LIKE '%".$s."%' || fileText LIKE '%".$s."%' || priceUSD LIKE '%".$s."%' || priceCoin LIKE '%".$s."%' || priceLabel LIKE '%".$s."%' || userFormat LIKE '%".$s."%' || expiryPeriod LIKE '%".$s."%' || defCoin LIKE '%".$s."%' || image LIKE '%".$s."%' || imageWidth LIKE '%".$s."%' || paymentCnt LIKE '%".$s."%' || lang LIKE '%".$s."%' || DATE_FORMAT(createtime, '%d %M %Y') LIKE '%".$s."%')";
			}
		}

		$res = $wpdb->get_row("SELECT count(fileID) as cnt from crypto_files WHERE active != 0".$search, OBJECT);
		$active = (int)$res->cnt;

		$res = $wpdb->get_row("SELECT count(fileID) as cnt from crypto_files WHERE active = 0".$search, OBJECT);
		$inactive = (int)$res->cnt;

		$res = $wpdb->get_row("SELECT sum(paymentCnt) as total from crypto_files WHERE paymentCnt > 0".$search, OBJECT);
		$sold = (int)$res->total;


		$wp_list_table = new  gourl_table_files($search, $this->options['rec_per_page']);
		$wp_list_table->prepare_items();


		echo "<div class='wrap ".GOURL."admin'>";
		echo $this->page_title(__('All Paid Files', GOURL).$this->space(1).'<a class="add-new-h2" href="'.GOURL_ADMIN.GOURL.'file">' . __('Add New File', GOURL) . '</a>', 2);

		if (!$intro)
		{
			echo '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'files&intro=1" class="'.GOURL.'button button-secondary">'.__('Hide Introduction', GOURL).' &#8595;</a></div>';
			echo "<div class='".GOURL."intro postbox'>";
			echo '<a style="float:right" target="_blank" href="https://gourl.io/lib/examples/pay-per-download-multi.php"><img width="110" hspace="10" title="Example - Pay Per Download" src="'.plugins_url('/images/pay-per-download.png', __FILE__).'" border="0"></a>';
			echo '<p>'.sprintf(__("Easily Sell Files, Videos, Music, Photos, Software (digital downloads) on your WordPress site/blog and accept %s payments online. No Chargebacks, Global, Secure. Anonymous Bitcoins & Cryptocurrency Payments. All in automatic mode. &#160; <a target='_blank' href='%s'>Example</a><br>If your site requires registration - activate website registration (General Settings &#187; Membership - <a href='%s'>Anyone can register</a>) and customize <a href='%s'>login</a> image.", GOURL), "<b>Bitcoin</b>, BitcoinCash, BitcoinSV, Litecoin, Dash, Dogecoin, Speedcoin, Reddcoin, Potcoin, Feathercoin, Vertcoin, Peercoin, MonetaryUnit", "https://gourl.io/lib/examples/pay-per-download-multi.php", admin_url('options-general.php'), GOURL_ADMIN.GOURL."settings#images") .'</p>';
			echo '<p>'.sprintf(__("Create <a href='%s'>New Paid File Downloads</a> and place new generated <a href='%s'>shortcode</a> on your public page/post. Done!", GOURL), GOURL_ADMIN.GOURL.'file', plugins_url('/images/tagexample_download_full.png', __FILE__)).$this->space(1);
			echo sprintf(__("<a href='%s'>Read more</a>", GOURL), GOURL_ADMIN.GOURL."#i3").'</p>';
			echo '<p><b>-----------------<br>'.sprintf(__("Alternatively, you can use free <a href='%s'>Easy Digital Downloads</a> plugin (advanced digital selling plugin with Credit Cards/Paypal) with our <a href='%s'>EDD Bitcoin/Altcoin Gateway</a> addon", GOURL), admin_url('plugin-install.php?tab=search&type=term&s=Easy+Digital+Downloads+sell+complete+management+sales+charts+Email+Subscribers+csv'), admin_url('plugin-install.php?tab=search&type=term&s=gourl+easy+digital+Downloads+edd')) . '</b></p>';
			echo  "</div>";
		}


		echo '<form class="gourlsearch" method="get" accept-charset="utf-8" action="">';
		if ($intro) echo '<a href="'.GOURL_ADMIN.GOURL.'files&intro=0" class="'.GOURL.'button button-secondary">'.__('Show Introduction', GOURL).' &#8593;</a> &#160; &#160; ';
		echo '<input type="hidden" name="page" value="'.$this->page.'" />';
		$wp_list_table->search_box( 'search', 'search_id' );
		echo '</form>';

		echo "<div class='".GOURL."tablestats'>";
		echo "<div>";
		echo "<b>" . __($search?__('Found', GOURL):__('Total Files', GOURL)). ":</b> " . ($active+$inactive) . " " . __('files', GOURL) . $this->space(1) . "( ";
		echo "<b>" . __('Active', GOURL). ":</b> " . ($search?$active:"<a href='".GOURL_ADMIN.GOURL."files&s=active'>$active</a>"). " " . __('files', GOURL) . $this->space(2);
		echo "<b>" . __('Inactive', GOURL). ":</b> " . ($search?$inactive:"<a href='".GOURL_ADMIN.GOURL."files&s=inactive'>$inactive</a>") . " " . __('files', GOURL) . $this->space(1) . ")" . $this->space(4);
		echo "<b>" . __('Total Sold', GOURL). ":</b> " . ($search?$sold:"<a href='".GOURL_ADMIN.GOURL."files&s=sold'>$sold</a>") . " " . __('files', GOURL);
		if ($search) echo "<br><a href='".GOURL_ADMIN.GOURL."files'>" . __('Reset Search Filters', GOURL). "</a>";
		echo "</div>";
		echo "</div>";

		echo '<div class="'.GOURL.'widetable">';
		echo '<div style="min-width:1690px;width:100%;">';

		$wp_list_table->display();

		echo  '</div>';
		echo  '</div>';
		echo  '</div>';
		echo  '<br><br>';

		return true;
	}



	/*
	 *  33.
	*/
	public function shortcode_download($arr)
	{
		global $wpdb, $current_user;

		// not available activated coins
		if (!$this->payments) { $html = $this->display_error_nokeys(); return $html; }

		if (!isset($arr["id"]) || !intval($arr["id"])) return '<div>'.sprintf(__('Invalid format. Use %s', GOURL), '&#160; ['.GOURL_TAG_DOWNLOAD.' id=..id..]').'</div>';

		$id 			= intval($arr["id"]);
		$short_code 	= '['.GOURL_TAG_DOWNLOAD.' id="<b>'.$id.'</b>"]';
		$download_key	= 'gourldownload_file';


		$is_paid		= false;
		$coins_list 	= "";
		$languages_list	= "";


		// Current File Info
		// --------------------------
		$arr = $wpdb->get_row("SELECT * FROM crypto_files WHERE fileID = ".intval($id)." LIMIT 1", ARRAY_A);
		if (!$arr) return '<div>'.sprintf(__("Invalid file id '%s' -", GOURL), $id)." ".$short_code.'</div>';


		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->payment_box_style();
		$message_style	= $this->payment_message_style();

		$active 		= $arr["active"];
		$fileTitle 		= $arr["fileTitle"];
		$fileName 		= $arr["fileName"];
		$fileUrl 		= $arr["fileUrl"];
		$fileText 		= $arr["fileText"];
		$fileSize 		= $arr["fileSize"];

		$priceUSD 		= $arr["priceUSD"];
		$priceCoin 		= $arr["priceCoin"];
		$priceLabel 	= $arr["priceLabel"];
		if ($priceUSD > 0 && $priceCoin > 0) $priceCoin = 0;
		if ($priceCoin > 0) { $arr["defCoin"] = $priceLabel; $arr["defShow"] = 1; }

		$purchases 		= $arr["purchases"];
		$userFormat 	= $arr["userFormat"];
		$expiryPeriod	= $arr["expiryPeriod"];
		$lang 			= $arr["lang"];
		$defCoin		= $this->coin_names[$arr["defCoin"]];
		$defShow		= $arr["defShow"];
		$image			= $arr["image"];
		$imageWidth		= $arr["imageWidth"];
		$priceShow		= $arr["priceShow"];
		$paymentCnt		= $arr["paymentCnt"];
		$paymentTime	= $arr["paymentTime"];
		$updatetime		= $arr["updatetime"];
		$createtime		= $arr["createtime"];
		$userID 		= ($userFormat == "MANUAL" ? "user_".$current_user->ID : "");
		$orderID 		= "file_".$id; // file_+fileID as orderID
		$filePath 		= GOURL_DIR."files/".mb_substr(preg_replace('/[\(\)\?\!\;\,\>\<\'\"\/\%]/', '', str_replace("..", "", $fileName)), 0, 100);
		$anchor 		= "gbx".$this->icrc32($id);



		if (strip_tags(mb_strlen($fileText)) < 5) $fileText = '';


		// Registered Users can Pay Only
		// --------------------------

		if ($userFormat == "MANUAL" && (!is_user_logged_in() || !$current_user->ID))
		{
			$box_html = "<div align='center'><a href='".wp_login_url(get_permalink())."'><img title='".__('Please register or login to download this file', GOURL)."' alt='".__('Please register or login to download this file', GOURL)."' src='".$this->box_image("flogin")."' border='0'></a></div><br><br>";
			$download_link = "onclick='alert(\"".__('Please register or login to download this file', GOURL)."\")' href='#a'";
		}
		else if (!$fileUrl  && (!$fileName || !file_exists($filePath) || !is_file($filePath)))
		{
			$box_html = "<div align='center'><img alt='".__('File does not exist on the server', GOURL)."' src='".$this->box_image("nofile")."' border='0'></div><br><br>";
			$download_link = "onclick='alert(\"".__('Error! File does not exist on the server !', GOURL)."\")' href='#a'";
		}
		else
		{

			// GoUrl Payments
			// --------------------------

			$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
			$available_coins 		= array(); 		// List of coins that you accept for payments
			$cryptobox_private_keys = array();		// List Of your private keys

			foreach ($this->coin_names as $k => $v)
			{
				$public_key 	= $this->options[$v.'public_key'];
				$private_key 	= $this->options[$v.'private_key'];

				if ($public_key && !strpos($public_key, "PUB"))    return '<div>'.sprintf(__('Invalid %s Public Key %s -', GOURL), $v, $public_key)." ".$short_code.'</div>';
				if ($private_key && !strpos($private_key, "PRV"))  return '<div>'.sprintf(__('Invalid %s Private Key -', GOURL), $v)." ".$short_code.'</div>';

				if ($private_key) $cryptobox_private_keys[] = $private_key;
				if ($private_key && $public_key && (!$defShow || $v == $defCoin))
				{
					$all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
					$available_coins[] = $v;
				}
			}

			if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));

			if (!$available_coins) { $html = '<div>'.$this->display_error_nokeys().' '.$short_code.'</div>'; return $html; }

			if (!in_array($defCoin, $available_coins)) { $vals = array_values($available_coins); $defCoin = array_shift($vals); }




			/// GoUrl Payment Class
			// --------------------------
			include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");



			// Current selected coin by user
			$coinName = cryptobox_selcoin($available_coins, $defCoin);


			// Current Coin public/private keys
			$public_key  = $all_keys[$coinName]["public_key"];
			$private_key = $all_keys[$coinName]["private_key"];


			// PAYMENT BOX CONFIG
			$options = array(
					"public_key"  => $public_key, 		// your box public key
					"private_key" => $private_key, 		// your box private key
					"orderID"     => $orderID, 			// file name hash as order id
					"userID"      => $userID, 			// unique identifier for each your user
					"userFormat"  => $userFormat, 		// save userID in
					"amount"   	  => $priceCoin,		// file price in coin
					"amountUSD"   => $priceUSD,			// file price in USD
					"period"      => $expiryPeriod, 	// download link valid period
					"language"	  => $lang  			// text on EN - english, FR - french, etc
			);



			// Initialise Payment Class
			$box = new Cryptobox ($options);


			// Coin name
			$coinName = $box->coin_name();


			// Paid or not
			$is_paid = $box->is_paid();




			// Payment Box HTML
			// ----------------------
			if (!$is_paid && $purchases > 0 && $paymentCnt >= $purchases)
			{
				// A. Sold
				$box_html = "<div align='center'><img alt='".__('Sold Out', GOURL)."' src='".$this->box_image("sold")."' border='0'></div><br><br>";

			}
			elseif (!$is_paid && !$active)
			{
				// B. Box Not Active
				$box_html = "<div align='center'><img alt='".__('Cryptcoin Payments Disabled for this File', GOURL)."' src='".$this->box_image("fdisable")."' border='0'></div><br><br>";
			}
			else
			{

        		// Payment Box HTML
        		// ----------------------

        		if ($this->options["box_type"] == 2)
        		{
                    // Active Payment Box - iFrame

                    // Coins selection list (html code)
                    $coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $defCoin, $lang, 60, "margin:60px 0 30px 0;text-align:center;font-weight:normal;", plugins_url('/images', __FILE__), $anchor) : "";

                    // Language selection list for payment box (html code)
                    $languages_list = display_language_box($lang, $anchor);

                    // Active Box
                    $box_html  = $this->iframe_scripts();
                    $box_html .= $box->display_cryptobox (true, $box_width, $box_height, $box_style, $message_style, $anchor);
        		}
        		else
        		{
                    // Active Payment Box - jQuery

                    $box_html  = $this->bootstrap_scripts();
                    $box_html .= $box->display_cryptobox_bootstrap ($available_coins, $defCoin, $lang, "", 70, 180, true, $this->box_logo(), "default", 250, "", "curl");

                    // Re-test after receive json data from live server
                    $is_paid = $box->is_paid();
        		}

			}




			// Download Link
			if ($is_paid)
			{
				$get_arr = $_GET;
				if (isset($get_arr[$download_key])) unset($get_arr[$download_key]);
				$download_link = 'href="'.$this->left($_SERVER["REQUEST_URI"], "?")."?".http_build_query($get_arr).($get_arr?"&amp;":"").$download_key."=".$this->icrc32($orderID).'"';
			}
			else
			{
				$download_link = "onclick='alert(\"".htmlspecialchars($this->options['popup_message'], ENT_QUOTES)."\")' href='#".$anchor."'";
			}




			// User Paid and Start To Download File - Send file to user browser
			// ---------------------
			if ($is_paid && isset($_GET[$download_key]) && $_GET[$download_key] == $this->icrc32($orderID))
			{
			    // Starting Download
	                if ($fileUrl)
	                {
	                    $box->set_status_processed();

	                    // Erase Old Cache
	                    ob_clean();

	                    // Open file url
	                    header('Location: ' . $fileUrl);
	                    echo "<script>window.location.href = '".$fileUrl."';</script>";

	                    die;
	                }
	                elseif (trim(dirname($filePath),"/") == trim(GOURL_DIR."files","/"))
	                {
	                    $this->download_file($filePath);

	                    // Set Status - User Downloaded File
	                    $box->set_status_processed();

	                    // Flush Cache
	                    if (ob_get_level()) ob_flush();

	                    die;
	                }
			}
		}




		// Html code
		// ---------------------

		$tmp  = "<div class='gourlbox'".($languages_list?" style='min-width:".$box_width."px'":"").">";
		$tmp .= "<h1>".htmlspecialchars($fileTitle, ENT_QUOTES)."</h1>";

		// Display Price in USD
		if ($priceShow)
		{
			if (!$fileUrl) $tmp .= "<h3> &#160; ".__('File', GOURL).": &#160; <a class='gourlfilename' style='text-decoration:none;color:inherit;' ".$download_link.">".$fileName."</a>".$this->space(2)."<small style='white-space:nowrap'>".__('size', GOURL).": ".gourl_byte_format($fileSize)."</small></h3>";
			$tmp .= "<div class='gourlprice'>".__('Price', GOURL).": ".($priceUSD>0?"~".$priceUSD." ".__('USD', GOURL):gourl_number_format($priceCoin, 4)." ".$priceLabel)."</div>";
		}

		// Download Link
		$tmp .= "<div align='center'><a ".$download_link."><img class='gourlimg' width='".$imageWidth."' alt='".htmlspecialchars($fileTitle, ENT_QUOTES)."' src='".GOURL_DIR2."images/".$image."' border='0'></a></div>";
		if ($fileText) $tmp .= "<br><div class='gourlfiledescription'>" . $fileText . "</div><br><br>";
		if (!$is_paid) $tmp .= "<a id='".$anchor."' name='".$anchor."'></a>";
		$tmp .= "<div class='gourldownloadlink'><a ".$download_link.">".__('Download File', GOURL)."</a></div>";

		if ($is_paid) 			$tmp .= "<br><br><br>";
		elseif (!$coins_list) 	$tmp .= "<br><br>";
		else 					$tmp .= $coins_list;

		// Cryptocoin Payment Box
		if ($languages_list) $tmp .= "<div style='margin:20px 0 5px 290px;font-family:\"Open Sans\",sans-serif;font-size:13px;color:#666;font-weight:normal;white-space:nowrap;'>".__('Language', GOURL).": ".$this->space(2).$languages_list."</div>";
		$tmp .= $box_html;

		// End
		$tmp .= "</div>";

		return $tmp;
	}













	/**************** C. PAY-PER-VIEW ************************************/




	/*
	 *  34.
	*/
	private function get_view()
	{
		$this->options2 = array();

		foreach ($this->fields_view as $key => $value)
		{
			$this->options2[$key] = get_option(GOURL.$key);
			if (!$this->options2[$key])
			{
				if ($value) $this->options2[$key] = $value; // default
				elseif ($key == "ppvCoin" && $this->payments)
				{
					$values = array_keys($this->payments);
					$this->options2[$key] = array_shift($values);
				}
			}

		}
		if ($this->options2["ppvPrice"] <= 0 && $this->options2["ppvPriceCoin"] <= 0) $this->options2["ppvPrice"] = 1;
		if (!$this->options2["ppvExpiry"]) $this->options2["ppvExpiry"] = "1 MONTH";

		return true;
	}



	/*
	 *  35.
	*/
	private function post_view()
	{
		$this->options2 = array();

		foreach ($this->fields_view as $key => $value)
		{
			$this->options2[$key] = (isset($_POST[GOURL.$key])) ? stripslashes($_POST[GOURL.$key]) : "";
			if (is_string($this->options2[$key])) $this->options2[$key] = trim($this->options2[$key]);
		}

		return true;
	}



	/*
	 *  36.
	*/
	private function check_view()
	{
		$this->record_errors = array();

		$this->options2["ppvPrice"] = str_replace(",", "", $this->options2["ppvPrice"]);
		$this->options2["ppvPriceCoin"] = str_replace(",", "", $this->options2["ppvPriceCoin"]);
		if ($this->options2["ppvPrice"] == 0 && $this->options2["ppvPriceCoin"] == 0) 	$this->record_errors[] = __('Price - cannot be empty', GOURL);
		if ($this->options2["ppvPrice"] != 0 && $this->options2["ppvPriceCoin"] != 0) 	$this->record_errors[] = __('Price - use price in USD or in Cryptocoins. You cannot place values in two boxes together', GOURL);
		if ($this->options2["ppvPrice"] != 0 && (!is_numeric($this->options2["ppvPrice"]) || round($this->options2["ppvPrice"], 2) != $this->options2["ppvPrice"] || $this->options2["ppvPrice"] < 0.01 || $this->options2["ppvPrice"] > 100000)) $this->record_errors[] = sprintf(__('Price - %s USD - invalid value. Min value: 0.01 USD', GOURL), $this->options2["ppvPrice"]);
		if ($this->options2["ppvPriceCoin"] != 0 && (!is_numeric($this->options2["ppvPriceCoin"]) || round($this->options2["ppvPriceCoin"], 4) != $this->options2["ppvPriceCoin"] || $this->options2["ppvPriceCoin"] < 0.0001 || $this->options2["ppvPriceCoin"] > 500000000)) $this->record_errors[] = sprintf(__('Price - %s %s - invalid value. Min value: 0.0001 %s. Allow 4 digits max after floating point', GOURL), $this->options2["ppvPriceCoin"], $this->options2["ppvPriceLabel"], $this->options2["ppvPriceLabel"]);

		if (!in_array($this->options2["ppvExpiry"], $this->expiry_view))	$this->record_errors[] = __("Field 'Expiry Period' - invalid value", GOURL);
		if ($this->lock_level_view && !in_array($this->options2["ppvLevel"], array_keys($this->lock_level_view)))	$this->record_errors[] = __('Lock Page Level - invalid value', GOURL);
		if (!isset($this->languages[$this->options2["ppvLang"]])) $this->record_errors[] = __('PaymentBox Language - invalid value', GOURL);

		if (!$this->options2["ppvCoin"]) $this->record_errors[] = __("Field 'PaymentBox Coin' - cannot be empty", GOURL);
		elseif (!isset($this->coin_names[$this->options2["ppvCoin"]])) $this->record_errors[] = __("Field 'PaymentBox Coin' - invalid value", GOURL);
		elseif (!isset($this->payments[$this->options2["ppvCoin"]])) {
			if (!$this->payments) $this->record_errors[] = sprintf(__("You must choose at least one payment method. Please enter your GoUrl Public/Private Keys on <a href='%s'>settings page</a>. Instruction <a href='%s'>here &#187;</a>", GOURL),  GOURL_ADMIN.GOURL.'settings#gourlcurrencyconverterapi_key', GOURL_ADMIN.GOURL."#i3");
			$this->record_errors[] = sprintf( __("Field 'PaymentBox Coin' - payments in %s not available. Please click on 'Save Settings' button", GOURL), $this->coin_names[$this->options2["ppvCoin"]]);
		}
		elseif ($this->options2["ppvPriceCoin"] != 0 && $this->options2["ppvCoin"] != $this->options2["ppvPriceLabel"]) $this->record_errors[] = sprintf(__("Field 'PaymentBox Coin' - please select '%s' because you have entered price in %s", GOURL), $this->coin_names[$this->options2["ppvPriceLabel"]], $this->coin_names[$this->options2["ppvPriceLabel"]]);

		if ($this->options2["ppvPriceCoin"] != 0 && !$this->options2["ppvOneCoin"]) $this->record_errors[] = sprintf(__("Field 'Use Default Coin Only' - check it because you have entered price in %s. Please use price in USD if you want to accept multiple coins", GOURL), $this->coin_names[$this->options2["ppvPriceLabel"]]);

		return true;
	}


	/*
	 *  37.
	*/
	private function save_view()
	{
		if ($this->options2['ppvPrice'] <= 0)  $this->options2['ppvPrice'] = 0;
		if ($this->options2['ppvPriceCoin'] <= 0 || $this->options2['ppvPrice'] > 0) { $this->options2['ppvPriceCoin'] = 0; $this->options2['ppvPriceLabel'] = ""; }


     		if (!(is_admin() && is_user_logged_in() && current_user_can('administrator')))
		{
			$this->record_errors[] = __('You don\'t have permission to edit this page. Please login as ADMIN user!', GOURL);
			return false;
		}
		else
		foreach ($this->options2 as $key => $value)
		{
			update_option(GOURL.$key, $value);
		}

		return true;
	}



	/*
	 *  38.
	*/
	public function page_view()
	{
		if ($this->is_nonadmin_user()) return true;

		$example = 0;
		$preview = (isset($_GET["preview"]) && $_GET["preview"] == "true") ? true : false;

		if (isset($_GET["intro"]))
		{
			$intro = intval($_GET["intro"]);
			update_option(GOURL."page_payperview_intro", $intro);
		}
		else $intro = get_option(GOURL."page_payperview_intro");


		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Pay-Per-View Settings have been updated <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";

		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';


		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title(__('Settings', GOURL), 3);


		if ($preview)
		{
			$example = intval($_GET["example"]);
			if ($example == 1 || $example == 2) $short_code = '['.GOURL_TAG_VIEW.' img="image'.$example.'.jpg"]';
			else $short_code = '['.GOURL_TAG_VIEW.' frame="https://www.youtube.com/embed/Eg58KaXjCFI" w="800" h="480"]';

			$tmp .= "<div class='postbox'>";
			$tmp .= "<h3 class='hndle'>".sprintf(__('Preview Shortcode &#160; &#160; %s', GOURL), $short_code);
			$tmp .= "<a href='".GOURL_ADMIN.GOURL."payperview' class='gourlright ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
			$tmp .= "</h3>";
			$tmp .= "<div class='inside'><br><br>";

			if ($example == 1 || $example == 2) $tmp .= $this->shortcode_view_init("image".$example.".jpg");
			else $tmp .= $this->shortcode_view_init("", "https://www.youtube.com/embed/Eg58KaXjCFI", 800, 480);

			$tmp .= "</div>";
			$tmp .= '<div class="gourlright"><small>'.__('Shortcode', GOURL).': &#160; '.$short_code.'</small></div>';
			$tmp .= "</div>";
		}
		elseif ($intro)
		{
			$tmp .= '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'payperview&intro=0" class="'.GOURL.'button button-secondary">'.__('Show Introduction', GOURL).' &#8593;</a></div>';
		}
		else
		{
			$tmp .= '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'payperview&intro=1" class="'.GOURL.'button button-secondary">'.__('Hide Introduction', GOURL).' &#8595;</a></div>';
			$tmp .= "<div class='".GOURL."intro postbox'>";
			$tmp .= "<div class='gourlimgright'>";
			$tmp .= "<div align='center'>";
			$tmp .= '<a target="_blank" href="https://gourl.io/lib/examples/pay-per-page-multi.php"><img title="Example - Pay Per View - Video/Page Access for Unregistered Visitors" src="'.plugins_url('/images/pay-per-page.png', __FILE__).'" border="0"></a>';
			$tmp .= "</div>";
			$tmp .= "</div>";
			$tmp .= sprintf(__("<b>Pay-Per-View Summary</b> - <a target='_blank' href='%s'>Example</a>", GOURL), "https://gourl.io/lib/examples/pay-per-page-multi.php");
			$tmp .= "<br>";
			$tmp .= __("Your unregistered anonymous website visitors  will need to send you a set amount of cryptocoins for access to your website's specific pages & videos during a specific time. All will be in automatic mode - allowing you to receive payments, open webpage access to your visitors, when payment expired a new payment box will appear, payment notifications to your email, etc.", GOURL);
			$tmp .= "<br><br>";
			$tmp .= sprintf(__("Pay-Per-View supports <a href='%s'>custom actions</a> (for example, show ads to free users on all website pages, <a href='%s'>see code</a>)", GOURL), GOURL_ADMIN.GOURL."#i4", plugins_url('/images/dir/payperview_actions.txt', __FILE__)) . "<br>";
			$tmp .= sprintf(__("<a href='%s'>Read how it works</a> and differences between Pay-Per-View and Pay-Per-Membership.", GOURL), GOURL_ADMIN.GOURL."#i4").$this->space();
			$tmp .= "<br><br>";
			$tmp .= "<b>".__('Pay-Per-View Pages -', GOURL)."</b>";
			$tmp .= "<br>";
			$tmp .= sprintf(__('You can customize lock-image / preview video for each page or not use preview at all.<br>Default image directory: %s or use full image path %s', GOURL), "<b class='gourlnowrap'>".GOURL_DIR2."lockimg</b>", "(http://...)");
			$tmp .= "<br><br>";
			$tmp .= __('Shortcodes with preview images/videos for premium locked pages:', GOURL);
			$tmp .= '<div class="gourlshortcode">['.GOURL_TAG_VIEW.' img="image1.jpg"]</div>';
			$tmp .= '<div class="gourlshortcode">['.GOURL_TAG_VIEW.' frame="..url.." w="640" h="480"]</div>';
			$tmp .= sprintf(__("Place one of that tags <a target='_blank' href='%s'>anywhere</a> in the original text on your premium pages/posts or use <a href='%s'>your custom code</a>", GOURL), plugins_url('/images/tagexample_payperview_full.png', __FILE__), plugins_url('/images/payperview_code.png', __FILE__));
			$tmp .= "<br><br>";
			$tmp .= __('Ready to use shortcodes:', GOURL);
			$tmp .= "<ol>";
			$tmp .= '<li>['.GOURL_TAG_VIEW.' img="image1.jpg"] &#160; - <small>'.__('locked page with default preview image', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_VIEW.' img="image2.jpg"] &#160; - <small>'.__('locked page with default preview video', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_VIEW.' img="my_image_etc.jpg"] &#160; - <small>'.sprintf(__('locked page with any custom preview image stored in directory %s', GOURL), GOURL_DIR2."lockimg").'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_VIEW.' img="my_image_etc.jpg" w="400" h="200"] &#160; - <small>'.__('locked page with custom image, image width=400px height=200px', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_VIEW.' img="http://....."] &#160; - <small>'.__('locked page with any custom image', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_VIEW.' frame="http://..." w="800" h="440"] &#160; - <small>'.__('locked page with any custom video preview, etc (iframe). Iframe width=800px, height=440px', GOURL).'</small></li>';
			$tmp .= "</ol>";
			$tmp .= "</div>";
		}

		$tmp .= $message;




		$tmp .= "<form id='".GOURL."form' name='".GOURL."form' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."payperview'>";

		$tmp .= "<div class='postbox".($preview?" previewactive":"")."'>";

		$tmp .= '<div class="alignright"><br>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'payperview">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '</div>';

		$tmp .= "<h3 class='hndle'>".__('Paid Access to Premium Webages for Unregistered Visitors', GOURL)."</h3>";
		$tmp .= "<div class='inside'>";

		$tmp .= '<input type="hidden" name="'.$this->adminform.'" value="'.GOURL.'save_view" />';
		$tmp .= wp_nonce_field( $this->admin_form_key );

		$tmp .= '<div class="alignright">';
		$tmp .= '<input type="submit" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Settings', GOURL).'">';
		if ($example != 2 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."payperview&gourlcryptocoin=".$this->coin_names[$this->options2['ppvCoin']]."&gourlcryptolang=".$this->options2['ppvLang']."&example=2&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview 1', GOURL)."</a>";
		if ($example != 1 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."payperview&gourlcryptocoin=".$this->coin_names[$this->options2['ppvCoin']]."&gourlcryptolang=".$this->options2['ppvLang']."&example=1&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview 2', GOURL)."</a>";
		if ($example != 3 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."payperview&gourlcryptocoin=".$this->coin_names[$this->options2['ppvCoin']]."&gourlcryptolang=".$this->options2['ppvLang']."&example=3&preview=true' class='".GOURL."button button-secondary'>".__('Video Preview 3', GOURL)."</a>";
		$tmp .= "<a target='_blank' href='".plugins_url('/images/tagexample_payperview_full.png', __FILE__)."' class='".GOURL."button button-secondary'>".__('Instruction', GOURL)."</a>".$this->space();
		$tmp .= '</div><br><br>';


		$tmp .= "<table class='".GOURL."table ".GOURL."payperview'>";

		$tmp .= '<tr><th>'.__('Price', GOURL).':</th><td>';
		$tmp .= '<input type="text" class="gourlnumeric" name="'.GOURL.'ppvPrice" id="'.GOURL.'ppvPrice" value="'.htmlspecialchars($this->options2['ppvPrice'], ENT_QUOTES).'"><label><b>'.__('USD', GOURL).'</b></label>';
		$tmp .= $this->space(2).'<label>'.__('or', GOURL).'</label>'.$this->space(5);
		$tmp .= '<input type="text" class="gourlnumeric2" name="'.GOURL.'ppvPriceCoin" id="'.GOURL.'ppvPriceCoin" value="'.htmlspecialchars($this->options2['ppvPriceCoin'], ENT_QUOTES).'">'.$this->space();
		$tmp .= '<select name="'.GOURL.'ppvPriceLabel" id="'.GOURL.'ppvPriceLabel">';
		foreach($this->coin_names as $k => $v) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options2['ppvPriceLabel']).'>'.$k.$this->space().'('.$v.')</option>';
		$tmp .= '</select>';
		$tmp .= '<br><em>'.sprintf(__("Please specify price in USD or in Cryptocoins. You cannot place prices in two boxes together. If you want to accept multiple coins - please use price in USD, payment box will automatically convert that USD amount to cryptocoin amount using today live cryptocurrency exchange rates (updated every 30min). Using that functionality (price in USD), you don't need to worry if cryptocurrency prices go down or go up. Visitors will pay you all times the actual price which is linked on daily exchange price in USD on the time of purchase. Also you can use <a target='_blank' href='%s'>Poloniex 'autosell' feature</a> (auto trade your cryptocoins to USD).", GOURL), "https://poloniex.com/").'</em>';
		$tmp .= '</td></tr>';

		$tmp .= '<tr><th>'.__('Expiry Period', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppvExpiry" id="'.GOURL.'ppvExpiry">';

		foreach($this->expiry_view as $v)
			$tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->options2['ppvExpiry']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<br><em>'.sprintf(__("Period after which the payment becomes obsolete and new Cryptocoin Payment Box will be shown (you can use it to take new payments from users periodically on daily basis). We use randomly generated strings as user identification and this is saved in user cookies. If user clears browser cookies, new payment box will be displayed. Therefore max expiry period is 2 DAYS. If you need more, please use <a href='%s'>pay-per-membership</a>", GOURL), GOURL_ADMIN.GOURL."paypermembership").'</em></td>';
		$tmp .= '</tr>';


		$tmp .= '<tr><th>'.__('Lock Page Level', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppvLevel" id="'.GOURL.'ppvLevel">';

		foreach($this->lock_level_view as $k=>$v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options2['ppvLevel']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<br><em>'.sprintf(__("Select user access level who will see lock premium page/blog and need to make payment for unlock and view original page content. Website Editors / Admins will have all the time full access to premium pages and see original page content.<br>If your site requires registration - activate website registration (General Settings &#187; Membership - <a href='%s'>Anyone can register</a>) and customize <a href='%s'>login</a> image", GOURL), admin_url('options-general.php'), GOURL_ADMIN.GOURL."settings#images").'</em>';
		$tmp .= '</td></tr>';



		$tmp .= '<tr><th>'.__('PaymentBox Language', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppvLang" id="'.GOURL.'ppvLang">';

		foreach($this->languages as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options2['ppvLang']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<br><em>'.__('Default Payment Box Localisation', GOURL).'</em></td>';
		$tmp .= '</tr>';


		$tmp .= '<tr><th>'.__('PaymentBox Coin', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppvCoin" id="'.GOURL.'ppvCoin">';

		foreach($this->payments as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options2['ppvCoin']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<span class="gourlpayments">' . __('Activated Payments :', GOURL) . " <a href='".GOURL_ADMIN.GOURL."settings'><b>" . ($this->payments?implode(", ", $this->payments):__('- Please Setup -', GOURL)) . '</b></a></span>';
		$tmp .= '<br><em>'.__('Default Coin in Payment Box', GOURL).'</em></td>';
		$tmp .= '</tr>';


		$tmp .= '<tr><th>'.__('Use Default Coin only:', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvOneCoin" id="'.GOURL.'ppvOneCoin" value="1" '.$this->chk($this->options2['ppvOneCoin'], 1).' class="widefat"><br><em>'.__("If box is checked, payment box will accept payments in one default coin 'PaymentBox Coin' (no multiple coins)", GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('PaymentBox Style:', GOURL).'</th>';
		$tmp .= '<td>'.sprintf(__("Payment Box <a target='_blank' href='%s'>sizes</a> and border <a target='_blank' href='%s'>shadow</a> you can change <a href='%s'>here &#187;</a>", GOURL ), plugins_url("/images/sizes.png", __FILE__), plugins_url("/images/styles.png", __FILE__), GOURL_ADMIN.GOURL."settings#gourlmonetaryunitprivate_key").'<br><br><br><br></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Text - Above Payment Box', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options2['ppvTextAbove'], GOURL.'ppvTextAbove', array('textarea_name' => GOURL.'ppvTextAbove', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br><em>'.__('Your Custom Text and Image above Payment Box on Locked premium pages (original pages content will be hidden)', GOURL).'</em>';
		$tmp .= '</td></tr>';


		$tmp .= '<tr><th>'.__('Text - Below Payment Box', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options2['ppvTextBelow'], GOURL.'ppvTextBelow', array('textarea_name' => GOURL.'ppvTextBelow', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br><em>'.__('Your Custom Text and Image below Payment Box on Locked premium pages (original pages content will be hidden)', GOURL).'</em>';
		$tmp .= '</td></tr>';

		$tmp .= '<tr><th>'.__('Hide Page Title ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvTitle2" id="'.GOURL.'ppvTitle2" value="1" '.$this->chk($this->options2['ppvTitle2'], 1).' class="widefat"><br><em>'.sprintf(__("If box is checked, unpaid users will not see current premium page title (<a href='%s'>screenshot</a>)", GOURL), plugins_url('/images/ppv_settings.png', __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Hide Menu Titles ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvTitle" id="'.GOURL.'ppvTitle" value="1" '.$this->chk($this->options2['ppvTitle'], 1).' class="widefat"><br><em>'.sprintf(__("If box is checked, unpaid users will not see any link titles on premium pages (<a href='%s'>screenshot</a>)", GOURL), plugins_url('/images/ppv_settings.png', __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Hide Comments Authors ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvCommentAuthor" id="'.GOURL.'ppvCommentAuthor" value="1" '.$this->chk($this->options2['ppvCommentAuthor'], 1).' class="widefat"><br><em>'.sprintf(__("If box is checked, unpaid users will not see authors of comments on bottom of premium pages (<a href='%s'>screenshot</a>)", GOURL), plugins_url('/images/ppv_settings.png', __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Hide Comments Body ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvCommentBody" id="'.GOURL.'ppvCommentBody" value="1" '.$this->chk($this->options2['ppvCommentBody'], 1).' class="widefat"><br><em>'.sprintf(__("If box is checked, unpaid users will not see comments body on bottom of premium pages (<a href='%s'>screenshot</a>)", GOURL), plugins_url('/images/ppv_settings.png', __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Disable Comments Reply ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppvCommentReply" id="'.GOURL.'ppvCommentReply" value="1" '.$this->chk($this->options2['ppvCommentReply'], 1).' class="widefat"><br><em>'.sprintf(__("If box is checked, unpaid users cannot reply/add comments on bottom of premium pages (<a href='%s'>screenshot</a>)", GOURL), plugins_url('/images/ppv_settings.png', __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Custom Actions', GOURL).':</th>';
		$tmp .= '<td><em>'.sprintf(__("Optional - add in file gourl_ipn.php code below. <a href='%s'>Read more &#187;</a>", GOURL), GOURL_ADMIN.GOURL."#i5");
		$tmp .= '<br><i>case "payperview": &#160; &#160; // order_ID = payperview<br>// ...your_code...<br>break;</i></em>';
		$tmp .= '</td></tr>';

		$tmp .= '</table>';


		$tmp .= '</div></div>';
		$tmp .= '</form></div>';

		echo $tmp;

		return true;
	}




	/*
	 *  39. Premium User or not
	*/
	public function is_premium_payperview_user ($full = true)
	{
		global $wpdb, $current_user;
		static $premium = "-1";

		if ($premium !== "-1") return $premium;

		$logged	= (is_user_logged_in() && $current_user->ID) ? true : false;

		$level = get_option(GOURL."ppvLevel");
		if (!$level || !in_array($level, array_keys($this->lock_level_view))) $level = 0;

		// Wordpress roles - array('administrator', 'editor', 'author', 'contributor', 'subscriber')
		$_administrator =  $_editor = $_author = $_contributor = false;
		if ($logged)
		{
			$_administrator = in_array('administrator', $current_user->roles);
			$_editor 		= in_array('editor', 		$current_user->roles);
			$_author 		= in_array('author', 		$current_user->roles);
			$_contributor 	= in_array('contributor', 	$current_user->roles);
		}

		$free_user = false;
		if (!$logged) 															 			 $free_user = true;  	// Unregistered Visitors will see lock screen all time
		elseif ($level == 0 && !$logged) 													 $free_user = true; 	// Unregistered Visitors
		elseif ($level == 1 && !$_administrator && !$_editor && !$_author && !$_contributor) $free_user = true; 	// Unregistered Visitors + Registered Subscribers
		elseif ($level == 2 && !$_administrator && !$_editor && !$_author) 					 $free_user = true; 	// Unregistered Visitors + Registered Subscribers/Contributors
		elseif ($level == 3 && !$_administrator && !$_editor) 					 			 $free_user = true; 	// Unregistered Visitors + Registered Subscribers/Contributors/Authors


		if ($free_user && $full)
		{
			// Current Settings
			// --------------------------
			$this->get_view();

			$priceUSD 		= $this->options2["ppvPrice"];
			$priceCoin 		= $this->options2["ppvPriceCoin"];
			if ($priceUSD == 0 && $priceCoin == 0) $priceUSD = 1;
			if ($priceUSD > 0 && $priceCoin > 0) $priceCoin = 0;

			$expiryPeriod	= $this->options2["ppvExpiry"];
			$lang 			= $this->options2["ppvLang"];
			$defCoin		= $this->coin_names[$this->options2["ppvCoin"]];
			$defShow		= $this->options2["ppvOneCoin"];

			$userFormat 	= "COOKIE";
			$userID 		= "";	// We use randomly generated strings as user identification and this is saved in user cookies
			$orderID 		= "payperview";
			$anchor 		= "gbx".$this->icrc32($orderID);
			$dt 			= gmdate('Y-m-d H:i:s');


			// GoUrl Payments
			// --------------------------
			$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
			$available_coins 		= array(); 		// List of coins that you accept for payments
			$cryptobox_private_keys = array();		// List Of your private keys

			foreach ($this->coin_names as $k => $v)
			{
				$public_key 	= $this->options[$v.'public_key'];
				$private_key 	= $this->options[$v.'private_key'];

				if ($public_key && !strpos($public_key, "PUB"))    { echo '<div>'.sprintf(__('Invalid %s Public Key %s -', GOURL), $v, $public_key).$short_code.'</div>'; return false; }
				if ($private_key && !strpos($private_key, "PRV"))  { echo '<div>'.sprintf(__('Invalid %s Private Key -', GOURL), $v).$short_code.'</div>'; return false; }

				if ($private_key) $cryptobox_private_keys[] = $private_key;
				if ($private_key && $public_key && (!$defShow || $v == $defCoin))
				{
					$all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
					$available_coins[] = $v;
				}
			}

			if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));

			if (!$available_coins) { echo '<div>'.$this->display_error_nokeys().' '.$short_code.'</div>'; return false; }

			if (!in_array($defCoin, $available_coins)) { $vals = array_values($available_coins); $defCoin = array_shift($vals); }


			/// GoUrl Payment Class
			// --------------------------
			include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");


			// Current selected coin by user
			$coinName = cryptobox_selcoin($available_coins, $defCoin);


			// Current Coin public/private keys
			$public_key  = $all_keys[$coinName]["public_key"];
			$private_key = $all_keys[$coinName]["private_key"];


			// PAYMENT BOX CONFIG
			$options = array(
					"public_key"  => $public_key, 		// your box public key
					"private_key" => $private_key, 		// your box private key
					"orderID"     => $orderID, 			// hash as order id
					"userID"      => $userID, 			// unique identifier for each your user
					"userFormat"  => $userFormat, 		// save userID in
					"amount"   	  => $priceCoin,		// price in coins
					"amountUSD"   => $priceUSD,			// price in USD
					"period"      => $expiryPeriod, 	// download link valid period
					"language"	  => $lang  			// text on EN - english, FR - french, etc
			);

			// Initialise Payment Class
			$box = new Cryptobox ($options);

			// Paid or not
			$premium = $box->is_paid();

			return $premium;
		}

		if ($free_user) return false;
		else return true;
	}





	/*
	 *  40.
	*/
	public function shortcode_view($arr)
	{
		$image   = (isset($arr["img"])) 	? trim($arr["img"]) 	: "";
		$frame  = (isset($arr["frame"])) 	? trim($arr["frame"]) 	: "";
		$iwidth  = (isset($arr["w"])) 		? trim($arr["w"]) 		: "";
		$iheight = (isset($arr["h"])) 		? trim($arr["h"]) 		: "";
		return $this->shortcode_view_init($image, $frame, $iwidth, $iheight);
	}




	/*
	 *  41.
	*/
	private function shortcode_view_init($image = "", $frame = "", $iwidth = "", $iheight = "")
	{
		global $wpdb, $current_user;
		static $html = "-1";

		// Marks the current page as noncacheable. https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:api
		if ( class_exists( 'LiteSpeed_Cache_API' ) ) LiteSpeed_Cache_API::set_nocache();


		if ($html !== "-1") return $html;

		// empty by dafault
		$html = "";


		// another tag [gourl-membership] with hgh priority exists on page
		if ($this->lock_type == GOURL_TAG_MEMBERSHIP) return "";

		// preview admin mode
		$preview_mode	= (stripos($_SERVER["REQUEST_URI"], "wp-admin/admin.php?") && $this->page == "gourlpayperview" && current_user_can('administrator')) ? true : false;


		// not available activated bitcoin/altcoin
		if (!$this->payments)
		{
			if (!$preview_mode)
			{
			    	add_filter('the_content', 		'gourl_lock_filter', 11111);
				add_filter('the_content_rss', 	'gourl_lock_filter', 11111);
				add_filter('the_content_feed', 	'gourl_lock_filter', 11111);
				add_filter("wp_title", 		'gourl_hide_headtitle', 11111);
				add_filter("wp_title_rss", 	'gourl_hide_headtitle', 11111);
				add_filter('the_title', 	'gourl_hide_all_titles', 11111);
				add_filter('the_title_rss', 'gourl_hide_all_titles', 11111);
				add_filter('the_title', 	'gourl_hide_menu_titles', 11111);
				add_filter('the_title_rss', 'gourl_hide_menu_titles', 11111);
				add_filter("wp_title", 		'gourl_hide_headtitle', 11111);
				add_filter("wp_title_rss", 	'gourl_hide_headtitle', 11111);
				add_filter('the_title', 	'gourl_hide_page_title', 11111);
				add_filter('the_title_rss', 'gourl_hide_page_title', 11111);
				add_filter('get_comment_author_link', 	'gourl_return_false', 11111);
				add_filter('comment_text',	'gourl_lock_comments', 11111);
				add_filter('post_comments_link',     'gourl_return_false', 1);
				add_filter('comment_reply_link',     'gourl_return_false', 1);
				add_filter('comments_open', 		'gourl_return_false', 1);
				add_action('do_feed',      'gourl_disable_feed', 1);
				add_action('do_feed_rdf',  'gourl_disable_feed', 1);
				add_action('do_feed_rss',  'gourl_disable_feed', 1);
				add_action('do_feed_rss2', 'gourl_disable_feed', 1);
				add_action('do_feed_atom', 'gourl_disable_feed', 1);
			}

			$html = GOURL_LOCK_START.$this->display_error_nokeys().GOURL_LOCK_END;

			return $html;
		}




		// if user already bought pay-per-view
		if (!$preview_mode && $this->is_premium_payperview_user( false )) return "";





		// shortcode options
		$orig = $image;
		if ($image && strpos($image, "/") === false) $image = GOURL_DIR2 . "lockimg/" . $image;
		if ($image && strpos($image, "//") === false && (!file_exists(ABSPATH.$image) || !is_file(ABSPATH.$image))) $image = "";
		if ($image && $frame) $frame = "";

		if ($frame && strpos($frame, "//") === false) $frame = "http://" . $frame;

		$short_code = '['.GOURL_TAG_VIEW.($image?' img="<b>'.$orig.'</b>':'').($frame?' frame="<b>'.$frame.'</b>':'').($iwidth?' w="<b>'.$iwidth.'</b>':'').($iheight?' h="<b>'.$iheight.'</b>':'').'"]';

		$iwidth = str_replace("px", "", $iwidth);
		if (!$iwidth || !is_numeric($iwidth) || $iwidth < 50) 	 $iwidth = "";
		$iheight = str_replace("px", "", $iheight);
		if (!$iheight || !is_numeric($iheight) || $iheight < 50) $iheight = "";

		if ($frame && !$iwidth)  $iwidth  = "640";
		if ($frame && !$iheight) $iheight = "480";



		$is_paid		= false;
		$coins_list 	= "";
		$languages_list	= "";



		// Current Settings
		// --------------------------
		$this->get_view();

		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->payment_box_style();
		$message_style	= $this->payment_message_style();

		$priceUSD 		= $this->options2["ppvPrice"];
		$priceCoin 		= $this->options2["ppvPriceCoin"];
		$priceLabel 	= $this->options2["ppvPriceLabel"];
		if ($priceUSD == 0 && $priceCoin == 0) $priceUSD = 1;
		if ($priceUSD > 0 && $priceCoin > 0) $priceCoin = 0;
		if ($priceCoin > 0) { $this->options2["ppvCoin"] = $priceLabel; $this->options2["ppvOneCoin"] = 1; }

		$expiryPeriod	= $this->options2["ppvExpiry"];
		$lang 			= $this->options2["ppvLang"];
		$defCoin		= $this->coin_names[$this->options2["ppvCoin"]];
		$defShow		= $this->options2["ppvOneCoin"];

		$textAbove		= $this->options2["ppvTextAbove"];
		$textBelow		= $this->options2["ppvTextBelow"];
		$hideCurTitle	= $this->options2["ppvTitle2"];
		$hideTitles		= $this->options2["ppvTitle"];
		$commentAuthor	= $this->options2["ppvCommentAuthor"];
		$commentBody	= $this->options2["ppvCommentBody"];
		$commentReply	= $this->options2["ppvCommentReply"];


		$userFormat 	= "COOKIE";
		$userID 		= "";	// We use randomly generated strings as user identification and this is saved in user cookies
		$orderID 		= "payperview";
		$anchor 		= "gbx".$this->icrc32($orderID);
		$dt 			= gmdate('Y-m-d H:i:s');







		// GoUrl Payments
		// --------------------------

		$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
		$available_coins 		= array(); 		// List of coins that you accept for payments
		$cryptobox_private_keys = array();		// List Of your private keys

		foreach ($this->coin_names as $k => $v)
		{
			$public_key 	= $this->options[$v.'public_key'];
			$private_key 	= $this->options[$v.'private_key'];

			if ($public_key && !strpos($public_key, "PUB"))    { $html = '<div>'.sprintf(__('Invalid %s Public Key %s -', GOURL), $v, $public_key).$short_code.'</div>'; return $html; }
			if ($private_key && !strpos($private_key, "PRV"))  { $html = '<div>'.sprintf(__('Invalid %s Private Key -', GOURL), $v).$short_code.'</div>'; return $html; }

			if ($private_key) $cryptobox_private_keys[] = $private_key;
			if ($private_key && $public_key && (!$defShow || $v == $defCoin))
			{
				$all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
				$available_coins[] = $v;
			}
		}

		if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));

		if (!$available_coins) { $html = '<div>'.$this->display_error_nokeys().' '.$short_code.'</div>'; return $html; }

		if (!in_array($defCoin, $available_coins)) { $vals = array_values($available_coins); $defCoin = array_shift($vals); }



		/// GoUrl Payment Class
		// --------------------------
		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");



		// Current selected coin by user
		$coinName = cryptobox_selcoin($available_coins, $defCoin);


		// Current Coin public/private keys
		$public_key  = $all_keys[$coinName]["public_key"];
		$private_key = $all_keys[$coinName]["private_key"];


		// PAYMENT BOX CONFIG
		$options = array(
				"public_key"  => $public_key, 		// your box public key
				"private_key" => $private_key, 		// your box private key
				"orderID"     => $orderID, 			// hash as order id
				"userID"      => $userID, 			// unique identifier for each your user
				"userFormat"  => $userFormat, 		// save userID in
				"amount"   	  => $priceCoin,		// price in coins
				"amountUSD"   => $priceUSD,			// price in USD
				"period"      => $expiryPeriod, 	// download link valid period
				"language"	  => $lang  			// text on EN - english, FR - french, etc
		);



		// Initialise Payment Class
		$box = new Cryptobox ($options);


		// Coin name
		$coinName = $box->coin_name();


		// Paid or not
		$is_paid = $box->is_paid();


		// Paid Already
		if ($is_paid && !$preview_mode) return "";



		// Payment Box HTML
		// ----------------------

		if ($this->options["box_type"] == 2)
		{
            // Active Payment Box - iFrame

            // Coins selection list (html code)
            $coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $defCoin, $lang, 60, "margin:60px 0 15px 0;text-align:center;font-weight:normal;", plugins_url('/images', __FILE__), $anchor) : "";

            // Language selection list for payment box (html code)
            $languages_list = display_language_box($lang, $anchor);

            // Active Box
            $box_html  = $this->iframe_scripts();
            $box_html .= $box->display_cryptobox (true, $box_width, $box_height, $box_style, $message_style, $anchor);
		}
		else
		{
            // Active Payment Box - jQuery

            $box_html  = $this->bootstrap_scripts();
            $box_html .= $box->display_cryptobox_bootstrap ($available_coins, $defCoin, $lang, "", 70, 180, true, $this->box_logo(), "default", 250, "", "curl");


            // Re-test after receive json data from live server
            $is_paid = $box->is_paid();
            if ($is_paid && !$preview_mode) return "";
		}




		// Html code
		// ---------------------

		$tmp  = "<br>";
		if (!$is_paid && $textAbove) $tmp .= "<div class='gourlviewtext'>".$textAbove."</div>".($image || $frame ? "<br><br>" : ""); else $tmp .= "<br>";

		// Start
		$tmp .= "<div align='center'>";

		if (!$is_paid)
		{
			if ($image) 	$tmp .= "<a href='#".$anchor."'><img style='border:none;box-shadow:none;max-width:100%;".($iwidth?"width:".$iwidth."px;":"").($iheight?"height:".$iheight."px;":"")."' title='".__('Page Content Locked! Please pay below', GOURL)."' alt='".__('Page Content Locked! Please pay below', GOURL)."' border='0' src='".$image."'></a><br>";
			elseif ($frame) $tmp .= "<iframe style='max-width:100%' width='".$iwidth."' height='".$iheight."' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' allowfullscreen src='".htmlspecialchars($frame)."'></iframe><br>";

			$tmp .= "<a id='".$anchor."' name='".$anchor."'></a>";
			if ($preview_mode) $tmp .= "<a id='previewcrypto' name='previewcrypto'></a>";
		}

		if ($is_paid) 			$tmp .= "<br><br><br>";
		elseif (!$coins_list) 	$tmp .= "<br><br>";
		else 					$tmp .= "<br>".$coins_list;


		if ($this->options["box_type"] == 2) // iFrame Payment Box
		{
		    $tmp .= "<div class='gourlbox' style='min-width:".$box_width."px;'>";
		    if ($languages_list) $tmp .= "<div style='margin:20px 0 5px 290px;font-family:\"Open Sans\",sans-serif;font-size:13px;color:#666;font-weight:normal;white-space:nowrap;'>".__('Language', GOURL).": ".$this->space(2).$languages_list."</div>";
		    $tmp .= $box_html;
		    $tmp .= "</div>";
		}
		else  // Bootstrap Payment Box
		{
		    $tmp .= $box_html;
		}

		// End
		$tmp .= "</div>";


		if (!$is_paid && $textBelow) $tmp .= "<br><br><br><div class='gourlviewtext'>".$textBelow."</div>";



		// Lock Page
		if (!$is_paid && !$preview_mode)
		{
			$tmp = GOURL_LOCK_START.$tmp.GOURL_LOCK_END;

			add_filter('the_content', 		'gourl_lock_filter', 11111);
			add_filter('the_content_rss', 	'gourl_lock_filter', 11111);
			add_filter('the_content_feed', 	'gourl_lock_filter', 11111);


			if ($hideTitles && $hideCurTitle)
			{
				add_filter("wp_title", 		'gourl_hide_headtitle', 11111);
				add_filter("wp_title_rss", 	'gourl_hide_headtitle', 11111);

				add_filter('the_title', 	'gourl_hide_all_titles', 11111);
				add_filter('the_title_rss', 'gourl_hide_all_titles', 11111);
			}
			elseif ($hideTitles)
			{
				add_filter('the_title', 	'gourl_hide_menu_titles', 11111);
				add_filter('the_title_rss', 'gourl_hide_menu_titles', 11111);
			}
			elseif ($hideCurTitle)
			{
				add_filter("wp_title", 		'gourl_hide_headtitle', 11111);
				add_filter("wp_title_rss", 	'gourl_hide_headtitle', 11111);

				add_filter('the_title', 	'gourl_hide_page_title', 11111);
				add_filter('the_title_rss', 'gourl_hide_page_title', 11111);
			}


			if ($commentAuthor) add_filter('get_comment_author_link', 	'gourl_return_false', 11111);

			if ($commentBody) add_filter('comment_text',	'gourl_lock_comments', 11111);


			if ($commentBody || $commentReply)
			{
				add_filter('post_comments_link',     'gourl_return_false', 1);
				add_filter('comment_reply_link',     'gourl_return_false', 1);
			}

			if ($commentReply)
			{
				add_filter('comments_open', 		'gourl_return_false', 1);
			}

			add_action('do_feed',      'gourl_disable_feed', 1);
			add_action('do_feed_rdf',  'gourl_disable_feed', 1);
			add_action('do_feed_rss',  'gourl_disable_feed', 1);
			add_action('do_feed_rss2', 'gourl_disable_feed', 1);
			add_action('do_feed_atom', 'gourl_disable_feed', 1);
		}


		$html = $tmp;

		return $tmp;
	}














	/**************** D. PAY-PER-MEMBERSHIP ************************************/


	/*
	 *  42.
	*/
	public function get_membership()
	{
		$this->options3 = array();

		foreach ($this->fields_membership as $key => $value)
		{
			$this->options3[$key] = get_option(GOURL.$key);
			if (!$this->options3[$key])
			{
				if ($value) $this->options3[$key] = $value; // default
				elseif ($key == "ppmCoin" && $this->payments)
				{
					$values = array_keys($this->payments);
					$this->options3[$key] = array_shift($values);
				}
			}


		}
		if ($this->options3["ppmPrice"] <= 0 && $this->options3["ppmPriceCoin"] <= 0) $this->options3["ppmPrice"] = 10;
		if (!$this->options3["ppmExpiry"]) $this->options3["ppmExpiry"] = "1 MONTH";


		return $this->options3;
	}



	/*
	 *  43.
	*/
	private function post_membership()
	{
		$this->options3 = array();

		foreach ($this->fields_membership as $key => $value)
		{
			$this->options3[$key] = (isset($_POST[GOURL.$key])) ? stripslashes($_POST[GOURL.$key]) : "";
			if (is_string($this->options3[$key])) $this->options3[$key] = trim($this->options3[$key]);
		}

		return true;
	}



	/*
	 *  44.
	*/
	private function check_membership()
	{
		$this->record_errors = array();

		$this->options3["ppmPrice"] = str_replace(",", "", $this->options3["ppmPrice"]);
		$this->options3["ppmPriceCoin"] = str_replace(",", "", $this->options3["ppmPriceCoin"]);
		if ($this->options3["ppmPrice"] == 0 && $this->options3["ppmPriceCoin"] == 0) 	$this->record_errors[] = __('Price - cannot be empty', GOURL);
		if ($this->options3["ppmPrice"] != 0 && $this->options3["ppmPriceCoin"] != 0) 	$this->record_errors[] = __('Price - use price in USD or in Cryptocoins. You cannot place values in two boxes together', GOURL);
		if ($this->options3["ppmPrice"] != 0 && (!is_numeric($this->options3["ppmPrice"]) || round($this->options3["ppmPrice"], 2) != $this->options3["ppmPrice"] || $this->options3["ppmPrice"] < 0.01 || $this->options3["ppmPrice"] > 100000)) $this->record_errors[] = sprintf(__('Price - %s USD - invalid value. Min value: 0.01 USD', GOURL), $this->options3["ppmPrice"]);
		if ($this->options3["ppmPriceCoin"] != 0 && (!is_numeric($this->options3["ppmPriceCoin"]) || round($this->options3["ppmPriceCoin"], 4) != $this->options3["ppmPriceCoin"] || $this->options3["ppmPriceCoin"] < 0.0001 || $this->options3["ppmPriceCoin"] > 500000000)) $this->record_errors[] = sprintf(__('Price - %s %s - invalid value. Min value: 0.0001 %s. Allow 4 digits max after floating point', GOURL), $this->options3["ppmPriceCoin"], $this->options3["ppmPriceLabel"], $this->options3["ppmPriceLabel"]);

		if (!in_array($this->options3["ppmExpiry"], $this->expiry_period))	$this->record_errors[] = __('Membership Period - invalid value', GOURL);
		if ($this->lock_level_membership && !in_array($this->options3["ppmLevel"], array_keys($this->lock_level_membership)))	$this->record_errors[] = __('Lock Page Level - invalid value', GOURL);
		if (!isset($this->languages[$this->options3["ppmLang"]])) $this->record_errors[] = __('PaymentBox Language - invalid value', GOURL);

		if (!$this->options3["ppmCoin"]) $this->record_errors[] = __("Field 'PaymentBox Coin' - cannot be empty", GOURL);
		elseif (!isset($this->coin_names[$this->options3["ppmCoin"]])) $this->record_errors[] = __("Field 'PaymentBox Coin' - invalid value", GOURL);
		elseif (!isset($this->payments[$this->options3["ppmCoin"]])) {
			if (!$this->payments) $this->record_errors[] = sprintf(__("You must choose at least one payment method. Please enter your GoUrl Public/Private Keys on <a href='%s'>settings page</a>. Instruction <a href='%s'>here &#187;</a>", GOURL),  GOURL_ADMIN.GOURL.'settings#gourlcurrencyconverterapi_key', GOURL_ADMIN.GOURL."#i3");
			$this->record_errors[] = sprintf( __("Field 'PaymentBox Coin' - payments in %s not available. Please click on 'Save Settings' button", GOURL), $this->coin_names[$this->options3["ppmCoin"]]);
		}
		elseif ($this->options3["ppmPriceCoin"] != 0 && $this->options3["ppmCoin"] != $this->options3["ppmPriceLabel"]) $this->record_errors[] = sprintf(__("Field 'PaymentBox Coin' - please select '%s' because you have entered price in %s", GOURL), $this->coin_names[$this->options3["ppmPriceLabel"]], $this->coin_names[$this->options3["ppmPriceLabel"]]);

		if ($this->options3["ppmPriceCoin"] != 0 && !$this->options3["ppmOneCoin"]) $this->record_errors[] = sprintf(__("Field 'Use Default Coin Only' - check it because you have entered price in %s. Please use price in USD if you want to accept multiple coins", GOURL), $this->coin_names[$this->options3["ppmPriceLabel"]]);


		return true;
	}


	/*
	 *  45.
	*/
	private function save_membership()
	{
		if ($this->options3['ppmPrice'] <= 0)  $this->options3['ppmPrice'] = 0;
		if ($this->options3['ppmPriceCoin'] <= 0 || $this->options3['ppmPrice'] > 0) { $this->options3['ppmPriceCoin'] = 0; $this->options3['ppmPriceLabel'] = ""; }

     		if (!(is_admin() && is_user_logged_in() && current_user_can('administrator')))
		{
			$this->record_errors[] = __('You don\'t have permission to edit this page. Please login as ADMIN user!', GOURL);
			return false;
		}
		else
		foreach ($this->options3 as $key => $value)
		{
			update_option(GOURL.$key, $value);
		}


		return true;
	}






	/*
	 *  46.
	*/
	public function page_membership()
	{
		global $current_user;

		if ($this->is_nonadmin_user()) return true;

		$example = 0;
		$preview = (isset($_GET["preview"]) && $_GET["preview"] == "true") ? true : false;

		if (isset($_GET["intro"]))
		{
			$intro = intval($_GET["intro"]);
			update_option(GOURL."page_membership_intro", $intro);
		}
		else $intro = get_option(GOURL."page_membership_intro");


		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Pay-Per-Membership Settings have been updated <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";

		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';


		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title(__('Settings', GOURL), 4);


		if ($preview)
		{
			if ($_GET["example"] == "4")
			{
				$tmp .= "<div class='postbox'>";
				$tmp .= "<h3 class='hndle'>".__('Unregistered visitors / non-logged users will see on your premium pages - login form with custom text', GOURL);
				$tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership' class='gourlright ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
				$tmp .= "</h3>";
				$tmp .= "<br><br><div class='inside' align='center'>";
				$tmp .= $this->options3['ppmTextAbove2'];
				$tmp .= "<br><br><br><img src='".plugins_url('/images/loginform.png', __FILE__)."' border='0'><br><br><br>";
				$tmp .= $this->options3['ppmTextBelow2'];
				$tmp .= "<br><br></div>";
				$tmp .= "</div>";
			}
			else
			{
				$example = intval($_GET["example"]);
				if ($example == 1 || $example == 2) $short_code = '['.GOURL_TAG_MEMBERSHIP.' img="image'.$example.($example==2?'.jpg':'.png').'"]';
				else $short_code = '['.GOURL_TAG_MEMBERSHIP.' frame="https://www.youtube.com/embed/_YEyzvtMx3s" w="700" h="380"]';

				$tmp .= "<div class='postbox'>";
				$tmp .= "<h3 class='hndle'>".sprintf(__('Preview Shortcode &#160; &#160; %s', GOURL), $short_code);
				$tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership' class='gourlright ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
				$tmp .= "</h3>";
				$tmp .= "<div class='inside'><br><br>";

				if ($example == 1 || $example == 2) $tmp .= $this->shortcode_membership_init("image".$example.($example==2?'.jpg':'.png'));
				else $tmp .= $this->shortcode_membership_init("", "https://www.youtube.com/embed/_YEyzvtMx3s", 700, 380);

				$tmp .= "</div>";
				$tmp .= '<div class="gourlright"><small>'.__('Shortcode', GOURL).': &#160; '.$short_code.'</small></div>';
				$tmp .= "</div>";
			}
		}
		elseif ($intro)
		{
			$tmp .= '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'paypermembership&intro=0" class="'.GOURL.'button button-secondary">'.__('Show Introduction', GOURL).' &#8593;</a></div>';
		}
		else
		{
			$tmp .= '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'paypermembership&intro=1" class="'.GOURL.'button button-secondary">'.__('Hide Introduction', GOURL).' &#8595;</a></div>';
			$tmp .= "<div class='".GOURL."intro postbox'>";
			$tmp .= "<div class='gourlimgright'>";
			$tmp .= "<div align='center'>";
			$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership&gourlcryptocoin='.($this->options3['ppmCoin']?$this->coin_names[$this->options3['ppmCoin']]:"").'&gourlcryptolang='.$this->options3['ppmLang'].'&example=1&preview=true"><img title="Example - Bitcoin - Pay Per Membership" src="'.plugins_url('/images/pay-per-membership.png', __FILE__).'" border="0"></a>';
			$tmp .= "</div>";
			$tmp .= "</div>";
			$tmp .= sprintf(__("<b>Pay-Per-Membership</b> - Your <b>registered</b> website users will need to send you a set amount of cryptocoins for access to your website's specific premium pages & videos during a specific time. All will be in automatic mode. Pay-Per-Membership - is a better safety solution than pay-per-view because plugin uses registered userID not cookies. You need to have website registration <a href='%s'>enabled</a>.", GOURL), admin_url('options-general.php'));
			$tmp .= "<br><br>";
			$tmp .= sprintf(__("<b>Pay-Per-Membership</b> supports <a href='%s'>custom actions</a> (for example, show ads to free users on all website pages, <a href='%s'>see code</a>)<br>and it integrated with <a href='%s'>bbPress Forum/Customer Support</a> ( use our <a href='%s'>GoUrl bbPress Addon</a> ). You can mark some topics on your bbPress as Premium and can easily monetise it with Bitcoins/altcoins. &#160; <a href='%s'>More info</a>", GOURL), GOURL_ADMIN.GOURL."#i4", plugins_url('/images/dir/membership_actions.txt', __FILE__), admin_url('plugin-install.php?tab=search&type=term&s=bbPress+forum+keeping+lean'), admin_url('plugin-install.php?tab=search&type=term&s=gourl+bbpress+topics'), "https://gourl.io/bbpress-premium-membership.html");
			$tmp .= "<br><br>";
			$tmp .= sprintf(__("Pay-Per-Membership supports ONE paid membership level for website.<br>For few membership levels (ex. basic, pro, premium), alternatively you can use <a class='gourlnowrap' href='%s'>Paid Memberships Pro</a> plugin with our <a class='gourlnowrap' href='%s'>GoUrl Gateweay PMP Addon</a>.<br>Therefore you can use one of two membership systems - Paid Memberships Pro or Pay-Per-Membership shortcodes (current page). Please use one of them only (not both) on your website, because these different membership systems are not compatible.", GOURL), admin_url('plugin-install.php?tab=search&type=term&s=paid+memberships+pro+revenue+generating+machine'), admin_url('plugin-install.php?tab=search&type=term&s=gourl+paid+memberships+addon'));
			$tmp .= "<br><br>";
			$tmp .= "<b>".__('Pay-Per-Membership Premium Pages -', GOURL)."</b>";
			$tmp .= "<br>----------------------<br>";
			$tmp .= sprintf(__('You can customize lock-image / preview video for each page or not use preview at all.<br>Default image directory: %s or use full image path %s', GOURL), "<b class='gourlnowrap'>".GOURL_DIR2."lockimg</b>", "(http://...)");
			$tmp .= "<br><br>";
			$tmp .= __('Shortcodes with preview images/videos for premium locked pages:', GOURL);
			$tmp .= '<div class="gourlshortcode">['.GOURL_TAG_MEMBERSHIP.' img="image1.png"]</div>';
			$tmp .= '<div class="gourlshortcode">['.GOURL_TAG_MEMBERSHIP.' frame="..url.." w="700" h="380"]</div>';
			$tmp .= sprintf(__("Place one of that tags <a target='_blank' href='%s'>anywhere</a> in the original text on your premium pages/posts or use <a href='%s'>your custom code</a>", GOURL), plugins_url('/images/tagexample_membership_full.png', __FILE__), plugins_url('/images/paypermembership_code.png', __FILE__));
			$tmp .= "<br><br>";
			$tmp .= __('Ready to use shortcodes:', GOURL);
			$tmp .= "<ol>";
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' img="image1.png"] &#160; - <small>'.__('locked premium page with default preview image; visible for unpaid logged-in users', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' img="image2.jpg"] &#160; - <small>'.__('locked page with default preview video', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' img="my_image_etc.jpg"] &#160; - <small>'.sprintf(__('locked page with any custom preview image stored in directory %s', GOURL), GOURL_DIR2."lockimg").'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' img="my_image_etc.jpg" w="400" h="200"] &#160; - <small>'.__('locked page with custom image, image width=400px height=200px', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' img="http://....."] &#160; - <small>'.__('locked page with any custom image', GOURL).'</small></li>';
			$tmp .= '<li>['.GOURL_TAG_MEMBERSHIP.' frame="http://..." w="750" h="410"] &#160; - <small>'.__('locked page with any custom video preview, etc (iframe). Iframe width=750px, height=410px', GOURL).'</small></li>';

			$tmp .= "</ol>";
			$tmp .= "</div>";
		}

		$tmp .= $message;




		$tmp .= "<form id='".GOURL."form' name='".GOURL."form' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."paypermembership'>";

		$tmp .= "<div class='postbox".($preview?" previewactive":"")."'>";

		$tmp .= '<div class="alignright"><br>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership_users">'.__('All Premium Users', GOURL).'</a>';
		$tmp .= '</div>';

		$tmp .= "<h3 class='hndle'>".__('Paid Access to Premium Pages for Registered Users', GOURL)."</h3>";
		$tmp .= "<div class='inside'>";

		$tmp .= '<input type="hidden" name="'.$this->adminform.'" value="'.GOURL.'save_membership" />';
		$tmp .= wp_nonce_field( $this->admin_form_key );

		$tmp .= '<div class="alignright">';
		$tmp .= '<input type="submit" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Settings', GOURL).'">';
		if ($example != 4 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership&example=4&preview=true' class='".GOURL."button button-secondary'>".__('Screen for non-logged users', GOURL)."</a>";
		if ($example != 1 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership&gourlcryptocoin=".$this->coin_names[$this->options3['ppmCoin']]."&gourlcryptolang=".$this->options3['ppmLang']."&example=1&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview 1', GOURL)."</a>";
		if ($example != 2 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership&gourlcryptocoin=".$this->coin_names[$this->options3['ppmCoin']]."&gourlcryptolang=".$this->options3['ppmLang']."&example=2&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview 2', GOURL)."</a>";
		if ($example != 3 && !$this->record_errors) $tmp .= "<a href='".GOURL_ADMIN.GOURL."paypermembership&gourlcryptocoin=".$this->coin_names[$this->options3['ppmCoin']]."&gourlcryptolang=".$this->options3['ppmLang']."&example=3&preview=true' class='".GOURL."button button-secondary'>".__('Video Preview 3', GOURL)."</a>";
		$tmp .= "<a target='_blank' href='".plugins_url('/images/tagexample_membership_full.png', __FILE__)."' class='".GOURL."button button-secondary'>".__('Instruction', GOURL)."</a>".$this->space();
		$tmp .= '</div><br><br>';


		$tmp .= "<table class='".GOURL."table ".GOURL."paypermembership'>";

		$tmp .= '<tr><th>'.__('Membership Price', GOURL).':</th><td>';
		$tmp .= '<input type="text" class="gourlnumeric" name="'.GOURL.'ppmPrice" id="'.GOURL.'ppmPrice" value="'.htmlspecialchars($this->options3['ppmPrice'], ENT_QUOTES).'"><label><b>'.__('USD', GOURL).'</b></label>';
		$tmp .= $this->space(2).'<label>'.__('or', GOURL).'</label>'.$this->space(5);
		$tmp .= '<input type="text" class="gourlnumeric2" name="'.GOURL.'ppmPriceCoin" id="'.GOURL.'ppmPriceCoin" value="'.htmlspecialchars($this->options3['ppmPriceCoin'], ENT_QUOTES).'">'.$this->space();
		$tmp .= '<select name="'.GOURL.'ppmPriceLabel" id="'.GOURL.'ppmPriceLabel">';
		foreach($this->coin_names as $k => $v) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options3['ppmPriceLabel']).'>'.$k.$this->space().'('.$v.')</option>';
		$tmp .= '</select>';
		$tmp .= '<br><em>'.sprintf(__("Please specify price in USD or in Cryptocoins. You cannot place prices in two boxes together. If you want to accept multiple coins - please use price in USD, payment box will automatically convert that USD amount to cryptocoin amount using today live cryptocurrency exchange rates (updated every 30min). Using that functionality (price in USD), you don't need to worry if cryptocurrency prices go down or go up. Visitors will pay you all times the actual price which is linked on daily exchange price in USD on the time of purchase. Also you can use <a target='_blank' href='%s'>Poloniex 'autosell' feature</a> (auto trade your cryptocoins to USD).", GOURL), "https://poloniex.com/").'</em>';
		$tmp .= '</td></tr>';

		$tmp .= '<tr><th>'.__('Membership Period', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppmExpiry" id="'.GOURL.'ppmExpiry">';

		foreach($this->expiry_period as $v)
			if (!stripos($v, "minute")) $tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->options3['ppmExpiry']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<br><em>'.__('Period after which the payment becomes obsolete and new Cryptocoin Payment Box will be shown.', GOURL).'</em></td>';
		$tmp .= '</tr>';


		$tmp .= '<tr><th>'.__('Lock Page Level', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppmLevel" id="'.GOURL.'ppmLevel">';

		foreach($this->lock_level_membership as $k=>$v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options3['ppmLevel']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<br><em>'.sprintf(__("Select user access level who will see lock premium page/blog and need to make payment for unlock and view original page content. Website Editors / Admins will have all the time full access to locked pages and see original page content.<br>Please activate website registration ( General Settings &#187; Membership - <a href='%s'>Anyone can register</a> )", GOURL), admin_url('options-general.php')).'</em>';
		$tmp .= '</td></tr>';

		$tmp .= '<tr><th>'.__('Add to User Profile', GOURL).':</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmProfile" id="'.GOURL.'ppmProfile" value="1" '.$this->chk($this->options3['ppmProfile'], 1).' class="widefat"><br><em>'.sprintf(__("If box is checked, users will see own membership status on user profile page (<a href='%s'>profile.php</a>)", GOURL), admin_url('profile.php')).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('PaymentBox Language', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppmLang" id="'.GOURL.'ppmLang">';

		foreach($this->languages as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options3['ppmLang']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<br><em>'.__('Default Payment Box Localisation', GOURL).'</em></td>';
		$tmp .= '</tr>';


		$tmp .= '<tr><th>'.__('PaymentBox Coin', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'ppmCoin" id="'.GOURL.'ppmCoin">';

		foreach($this->payments as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->options3['ppmCoin']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<span class="gourlpayments">' . __('Activated Payments :', GOURL) . " <a href='".GOURL_ADMIN.GOURL."settings'><b>" . ($this->payments?implode(", ", $this->payments):__('- Please Setup -', GOURL)) . '</b></a></span>';
		$tmp .= '<br><em>'.__('Default Coin in Payment Box', GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Use Default Coin only:', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmOneCoin" id="'.GOURL.'ppmOneCoin" value="1" '.$this->chk($this->options3['ppmOneCoin'], 1).' class="widefat"><br><em>'.__("If box is checked, payment box will accept payments in one default coin 'PaymentBox Coin' (no multiple coins)", GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('PaymentBox Style:', GOURL).'</th>';
		$tmp .= '<td>'.sprintf(__("Payment Box <a target='_blank' href='%s'>sizes</a> and border <a target='_blank' href='%s'>shadow</a> you can change <a href='%s'>here &#187;</a>", GOURL ), plugins_url("/images/sizes.png", __FILE__), plugins_url("/images/styles.png", __FILE__), GOURL_ADMIN.GOURL."settings#gourlmonetaryunitprivate_key").'<br><br><br></td>';
		$tmp .= '</tr>';


		$tmp .= '<tr><th colspan="2"><br>';
		$tmp .= '<h3>'.__('A. Unregistered Users will see Login Form with custom text/images -', GOURL).'</h3>';
		$tmp .= '<p>'.__('You can separate the content your logged-in users see from what your unregistered users see; things like a log-in form + custom text A for unregistered users &#160;or&#160; payment box + other custom text B for unpaid logged-in users.', GOURL).'</p>';
		$tmp .= '<p>'.sprintf(__("IMPORTANT: Please check that Website Registration is enabled (option Membership - <a href='%s'>Anyone can register</a>)", GOURL), admin_url('options-general.php')).'</p>';
		$tmp .= '</th>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Text - Above Login Form', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options3['ppmTextAbove2'], GOURL.'ppmTextAbove2', array('textarea_name' => GOURL.'ppmTextAbove2', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br><em>'.__('Your Custom Text and Image For Unregistered Users (original pages content will be hidden). This text will publish <b>Above</b> Login Form', GOURL).'</em>';
		$tmp .= '</td></tr>';


		$tmp .= '<tr><th>'.__('Text - Below Login Form', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options3['ppmTextBelow2'], GOURL.'ppmTextBelow2', array('textarea_name' => GOURL.'ppmTextBelow2', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br><em>'.__('Your Custom Text and Image For Unregistered Users (original pages content will be hidden). This text will publish <b>Below</b> Login Form', GOURL).'</em>';
		$tmp .= '</td></tr>';

		$tmp .= '<tr><th colspan="2"><br>';
		$tmp .= '<h3>'.__('B. Unpaid logged-in users will see payment box with custom text -', GOURL).'</h3>';
		$tmp .= '</th>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Text - Above Payment Box', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options3['ppmTextAbove'], GOURL.'ppmTextAbove', array('textarea_name' => GOURL.'ppmTextAbove', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br><em>'.__('Your Custom Text and Image above Payment Box on Locked premium pages (original pages content will be hidden)', GOURL).'</em>';
		$tmp .= '</td></tr>';


		$tmp .= '<tr><th>'.__('Text - Below Payment Box', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->options3['ppmTextBelow'], GOURL.'ppmTextBelow', array('textarea_name' => GOURL.'ppmTextBelow', 'quicktags' => true, 'media_buttons' => true, 'wpautop' => false));
		$tmp  = '<br><em>'.__('Your Custom Text and Image below Payment Box on Locked premium pages (original pages content will be hidden)', GOURL).'</em>';
		$tmp .= '</td></tr>';

		$tmp .= '<tr><th colspan="2"><br><h3>'.__('General Content Restriction', GOURL).'</h3></th>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Hide Page Title ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmTitle2" id="'.GOURL.'ppmTitle2" value="1" '.$this->chk($this->options3['ppmTitle2'], 1).' class="widefat"><br><em>'.sprintf(__("If box is checked, unpaid users will not see current premium page title (<a href='%s'>screenshot</a>)", GOURL), plugins_url('/images/ppm_settings.png', __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Hide Menu Titles ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmTitle" id="'.GOURL.'ppmTitle" value="1" '.$this->chk($this->options3['ppmTitle'], 1).' class="widefat"><br><em>'.sprintf(__("If box is checked, unpaid users will not see any link titles on premium pages (<a href='%s'>screenshot</a>)", GOURL), plugins_url('/images/ppm_settings.png', __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Hide Comments Authors ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmCommentAuthor" id="'.GOURL.'ppmCommentAuthor" value="1" '.$this->chk($this->options3['ppmCommentAuthor'], 1).' class="widefat"><br><em>'.sprintf(__("If box is checked, unpaid users will not see authors of comments on bottom of premium pages (<a href='%s'>screenshot</a>)", GOURL), plugins_url('/images/ppm_settings.png', __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Hide Comments Body ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmCommentBody" id="'.GOURL.'ppmCommentBody" value="1" '.$this->chk($this->options3['ppmCommentBody'], 1).' class="widefat"><br><em>'.sprintf(__("If box is checked, unpaid users will not see comments body on bottom of premium pages (<a href='%s'>screenshot</a>)", GOURL), plugins_url('/images/ppm_settings.png', __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Disable Comments Reply ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'ppmCommentReply" id="'.GOURL.'ppmCommentReply" value="1" '.$this->chk($this->options3['ppmCommentReply'], 1).' class="widefat"><br><em>'.sprintf(__("If box is checked, unpaid users cannot reply/add comments on bottom of premium pages (<a href='%s'>screenshot</a>)", GOURL), plugins_url('/images/ppm_settings.png', __FILE__)).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Custom Actions', GOURL).':</th>';
		$tmp .= '<td><em>'.sprintf(__("Optional - add in file gourl_ipn.php code below. <a href='%s'>Read more &#187;</a>", GOURL), GOURL_ADMIN.GOURL."#i5");
		$tmp .= '<br><i>case "membership": &#160; &#160; // order_ID = membership<br>// ...your_code...<br>break;</i></em>';
		$tmp .= '</td></tr>';


		$tmp .= '</table>';


		$tmp .= '</div></div>';
		$tmp .= '</form></div>';

		echo $tmp;

		return true;
	}



	/*
	 *  47. Display or not membership upgrade payment box
	*/
	public function is_premium_user ()
	{
		global $wpdb, $current_user;
		static $premium = "-1";

		if ($premium !== "-1") return $premium;

		$logged	= (is_user_logged_in() && $current_user->ID) ? true : false;

		$level = get_option(GOURL."ppmLevel");
		if (!$level || !in_array($level, array_keys($this->lock_level_membership))) $level = 0;

		// Wordpress roles - array('administrator', 'editor', 'author', 'contributor', 'subscriber')
		$_administrator =  $_editor = $_author = $_contributor = false;
		if ($logged)
		{
			$_administrator = in_array('administrator', $current_user->roles);
			$_editor 		= in_array('editor', 		$current_user->roles);
			$_author 		= in_array('author', 		$current_user->roles);
			$_contributor 	= in_array('contributor', 	$current_user->roles);
		}

		$free_user = false;
		if 		(!$logged) 															 		 $free_user = true;  // Unregistered Visitors will see lock screen/login all time
		elseif  ($level == 0 && !$_administrator && !$_editor && !$_author && !$_contributor)$free_user = true; 	// Registered Subscribers will see lock screen
		elseif 	($level == 1 && !$_administrator && !$_editor && !$_author) 				 $free_user = true; 	// Registered Subscribers/Contributors will see lock screen
		elseif 	($level == 2 && !$_administrator && !$_editor) 					 			 $free_user = true; 	// Registered Subscribers/Contributors/Authors will see lock screen

		// if premium user already
		$dt = gmdate('Y-m-d H:i:s');
		if ($free_user && $logged && $wpdb->get_row("SELECT membID FROM crypto_membership WHERE userID = ".intval($current_user->ID)." && startDate <= '$dt' && endDate >= '$dt' && disabled = 0 LIMIT 1", OBJECT)) $free_user = false;


		$premium = ($free_user) ? false : true;

		return $premium;
	}



	/*
	 *  48.
	*/
	public function shortcode_membership($arr, $checkout = false)
	{
		$image   = (isset($arr["img"])) 	? trim($arr["img"]) 	: "";
		$frame  = (isset($arr["frame"]))	? trim($arr["frame"]) 	: "";
		$iwidth  = (isset($arr["w"])) 		? trim($arr["w"]) 		: "";
		$iheight = (isset($arr["h"])) 		? trim($arr["h"]) 		: "";
		return $this->shortcode_membership_init($image, $frame, $iwidth, $iheight, $checkout);
	}



	/*
	 *  49.
	*/
	public function shortcode_memcheckout($arr)
	{
		return $this->shortcode_membership($arr, true);
	}



	/*
	 *  50.
	*/
	private function shortcode_membership_init($image = "", $frame = "", $iwidth = "", $iheight = "", $checkout = false)
	{
		global $wpdb, $current_user;
		static $html = "-1";


		if ($html !== "-1") return $html;

		// empty by dafault
		$html = "";

   		// preview admin mode
		$preview_mode	= (stripos($_SERVER["REQUEST_URI"], "wp-admin/admin.php?") && $this->page == "gourlpaypermembership" && current_user_can('administrator')) ? true : false;


		// not available activated bitcoin/altcoin
		if (!$this->payments)
		{
			if (!$preview_mode)
			{
				add_filter('the_content', 		'gourl_lock_filter', 11111);
				add_filter('the_content_rss', 	'gourl_lock_filter', 11111);
				add_filter('the_content_feed', 	'gourl_lock_filter', 11111);
				add_filter("wp_title", 		'gourl_hide_headtitle_unlogged', 11111);
				add_filter("wp_title_rss", 	'gourl_hide_headtitle_unlogged', 11111);
				add_filter('the_title', 	'gourl_hide_all_titles', 11111);
				add_filter('the_title_rss', 'gourl_hide_all_titles', 11111);
				add_filter('get_comment_author_link', 	'gourl_return_false', 11111);
				add_filter('comment_text',	'gourl_lock_comments', 11111);
				add_filter('post_comments_link',     'gourl_return_false', 1);
				add_filter('comment_reply_link',     'gourl_return_false', 1);
				add_filter('comments_open', 		'gourl_return_false', 1);
				add_action('do_feed',      'gourl_disable_feed', 1);
				add_action('do_feed_rdf',  'gourl_disable_feed', 1);
				add_action('do_feed_rss',  'gourl_disable_feed', 1);
				add_action('do_feed_rss2', 'gourl_disable_feed', 1);
				add_action('do_feed_atom', 'gourl_disable_feed', 1);
			}

			$html = GOURL_LOCK_START.$this->display_error_nokeys().GOURL_LOCK_END;

			return $html;
		}




		// if premium user already or don't need upgade user membership
		if (!$preview_mode && !$checkout && $this->is_premium_user()) return "";


		// user logged or not
		$logged	= (is_user_logged_in() && $current_user->ID) ? true : false;





		// shortcode options
		$orig = $image;
		if ($image && strpos($image, "/") === false) $image = GOURL_DIR2 . "lockimg/" . $image;
		if ($image && strpos($image, "//") === false && (!file_exists(ABSPATH.$image) || !is_file(ABSPATH.$image))) $image = "";
		if ($image && $frame) $frame = "";

		if ($frame && strpos($frame, "//") === false) $frame = "http://" . $frame;

		$short_code 	= '['.GOURL_TAG_MEMBERSHIP.($image?' img="<b>'.$orig.'</b>':'').($frame?' frame="<b>'.$frame.'</b>':'').($iwidth?' w="<b>'.$iwidth.'</b>':'').($iheight?' h="<b>'.$iheight.'</b>':'').'"]';

		$iwidth = str_replace("px", "", $iwidth);
		if (!$iwidth || !is_numeric($iwidth) || $iwidth < 50) 	 $iwidth = "";
		$iheight = str_replace("px", "", $iheight);
		if (!$iheight || !is_numeric($iheight) || $iheight < 50) $iheight = "";

		if ($frame && !$iwidth)  $iwidth  = "640";
		if ($frame && !$iheight) $iheight = "480";




		$is_paid		= false;
		$coins_list 	= "";
		$languages_list	= "";
		$box_html = "";




		// Current Settings
		// --------------------------
		$this->get_membership();

		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->payment_box_style();
		$message_style	= $this->payment_message_style();

		$priceUSD 		= $this->options3["ppmPrice"];
		$priceCoin 		= $this->options3["ppmPriceCoin"];
		$priceLabel 	= $this->options3["ppmPriceLabel"];
		if ($priceUSD == 0 && $priceCoin == 0) 	$priceUSD = 10;
		if ($priceUSD > 0 && $priceCoin > 0) 	$priceCoin = 0;
		if ($priceCoin > 0) { $this->options3["ppmCoin"] = $priceLabel; $this->options3["ppmOneCoin"] = 1; }

		$expiryPeriod	= $this->options3["ppmExpiry"];
		$lang 			= $this->options3["ppmLang"];
		$defCoin		= $this->coin_names[$this->options3["ppmCoin"]];
		$defShow		= $this->options3["ppmOneCoin"];

		$textAbove		= ($logged) ? $this->options3["ppmTextAbove"] : $this->options3["ppmTextAbove2"];
		$textBelow		= ($logged) ? $this->options3["ppmTextBelow"] : $this->options3["ppmTextBelow2"];
		$hideCurTitle	= $this->options3["ppmTitle2"];
		$hideTitles		= $this->options3["ppmTitle"];
		$commentAuthor	= $this->options3["ppmCommentAuthor"];
		$commentBody	= $this->options3["ppmCommentBody"];
		$commentReply	= $this->options3["ppmCommentReply"];


		$userFormat 	= "MANUAL";
		$userID 		= "user_".$current_user->ID;
		$orderID 		= "membership";
		$anchor 		= "gbx".$this->icrc32($orderID);
		$dt 			= gmdate('Y-m-d H:i:s');






	if (!$logged)
	{
		// Html code
		$tmp  = "<div align='center'>";

		if ($textAbove) $tmp .= "<div class='gourlmembershiptext2'>".$textAbove."</div>";

		$tmp .= $this->login_form();

		if ($textBelow) $tmp .= "<div class='gourlmembershiptext2'>".$textBelow."</div>";

		$tmp .= "</div>";
	}
	else
	{
		// if admin disabled valid user membership, display new payment form with new unique orderID for that user
		$prev_payments = $wpdb->get_row("SELECT count(membID) as cnt FROM crypto_membership WHERE userID = ".intval($current_user->ID)." && disabled = 1 && startDate <= '$dt' && endDate >= '$dt' && paymentID > 0", OBJECT);
		if ($prev_payments && $prev_payments->cnt > 0)
		{
			$orderID 		= "membership".($prev_payments->cnt+1);
			$anchor 		= "gbx".$this->icrc32($orderID);
		}


		// GoUrl Payments
		// --------------------------

		$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
		$available_coins 		= array(); 		// List of coins that you accept for payments
		$cryptobox_private_keys = array();		// List Of your private keys

		foreach ($this->coin_names as $k => $v)
		{
			$public_key 	= $this->options[$v.'public_key'];
			$private_key 	= $this->options[$v.'private_key'];

			if ($public_key && !strpos($public_key, "PUB"))    { $html = '<div>'.sprintf(__('Invalid %s Public Key %s -', GOURL), $v, $public_key).$short_code.'</div>'; return $html; }
			if ($private_key && !strpos($private_key, "PRV"))  { $html = '<div>'.sprintf(__('Invalid %s Private Key -', GOURL), $v).$short_code.'</div>'; return $html; }

			if ($private_key) $cryptobox_private_keys[] = $private_key;
			if ($private_key && $public_key && (!$defShow || $v == $defCoin))
			{
				$all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
				$available_coins[] = $v;
			}
		}

		if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));

		if (!$available_coins) { $html = '<div>'.$this->display_error_nokeys().' '.$short_code.'</div>'; return $html; }

		if (!in_array($defCoin, $available_coins)) { $vals = array_values($available_coins); $defCoin = array_shift($vals); }



		/// GoUrl Payment Class
		// --------------------------
		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");



		// Current selected coin by user
		$coinName = cryptobox_selcoin($available_coins, $defCoin);


		// Current Coin public/private keys
		$public_key  = $all_keys[$coinName]["public_key"];
		$private_key = $all_keys[$coinName]["private_key"];


		// PAYMENT BOX CONFIG
		$options = array(
				"public_key"  => $public_key, 		// your box public key
				"private_key" => $private_key, 		// your box private key
				"orderID"     => $orderID, 			// hash as order id
				"userID"      => $userID, 			// unique identifier for each your user
				"userFormat"  => $userFormat, 		// save userID in
				"amount"   	  => $priceCoin,		// price in coins
				"amountUSD"   => $priceUSD,			// price in USD
				"period"      => $expiryPeriod, 	// download link valid period
				"language"	  => $lang  			// text on EN - english, FR - french, etc
		);



		// Initialise Payment Class
		$box = new Cryptobox ($options);


		// Coin name
		$coinName = $box->coin_name();


		// Paid or not
		$is_paid = $box->is_paid();


		// Paid Already
		if ($is_paid && !$preview_mode && !$checkout) return "";



		// Payment Box HTML
		// ----------------------

		if ($this->options["box_type"] == 2)
		{
            // Active Payment Box - iFrame

            // Coins selection list (html code)
            $coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $defCoin, $lang, 60, "margin:60px 0 15px 0;text-align:center;font-weight:normal;", plugins_url('/images', __FILE__), $anchor) : "";

            // Language selection list for payment box (html code)
            $languages_list = display_language_box($lang, $anchor);

            // Active Box
            $box_html  = $this->iframe_scripts();
            $box_html .= $box->display_cryptobox (true, $box_width, $box_height, $box_style, $message_style, $anchor);

		}
		else
		{
            // Active Payment Box - jQuery

            $box_html  = $this->bootstrap_scripts();
            $box_html .= $box->display_cryptobox_bootstrap ($available_coins, $defCoin, $lang, "", 70, 180, true, $this->box_logo(), "default", 250, "", "curl");

            // Re-test after receive json data from live server
            $is_paid = $box->is_paid();
            if ($is_paid && !$preview_mode && !$checkout) return "";
		}




		// Html code
		// ---------------------

		$checkout_done = ($checkout && !current_user_can('manage_options') && $this->is_premium_user()) ? true : false;

		$tmp  = "";
		if (!$checkout_done)
		{
			$tmp  .= "<br>";
			if (!$is_paid && $textAbove) $tmp .= "<div class='gourlmembershiptext'>".$textAbove."</div>" . ($image || $frame ? "<br><br>" : ""); else $tmp .= "<br>";
		}


		// Start
		$tmp .= "<div align='center'>";

		if ($checkout_done)
		{
			$tmp .= "<p><b>".__("Thank you.")."</b></p><p>".__("Your Premium membership is active.")."</p>";
		}
		elseif (!$is_paid)
		{
			if ($image)
			{
				$imageWidthMax = "100%;";
				if ($this->right($image, "/", false) == "image1.png")
				{
					$tmp .= "<div align='center' style='width:555px;'><div class='".($priceUSD>0 || $expiryPeriod=="NO EXPIRY"?"gourlmembershipprice":"gourlmembershipprice2")."'>".($priceUSD>0?"$".$priceUSD:gourl_number_format($priceCoin, 4)." ".$priceLabel).($expiryPeriod!="NO EXPIRY"?($priceUSD>0?" <span>/":"<br><span>").$expiryPeriod."</span>":"")."</div></div>";
					if (is_user_logged_in() && $current_user->ID) $image = str_replace("image1.png", "image1b.png", $image);
					$imageWidthMax = "none;";
					$iwidth = 555;
				}
				$tmp .= "<a href='#".$anchor."'><img style='border:none;box-shadow:none;max-width:".$imageWidthMax.($iwidth?"width:".$iwidth."px;":"").($iheight?"height:".$iheight."px;":"")."' title='".__('Page Content Locked! Please pay below', GOURL)."' alt='".__('Page Content Locked! Please pay below', GOURL)."' border='0' src='".$image."'></a><br>";
			}
			elseif ($frame) $tmp .= "<iframe style='max-width:100%' width='".$iwidth."' height='".$iheight."' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' allowfullscreen src='".htmlspecialchars($frame)."'></iframe><br>";

			$tmp .= "<a id='".$anchor."' name='".$anchor."'></a>";
		}
		elseif ($is_paid && $preview_mode) 	$tmp .= sprintf(__("<b>ADMIN NOTE:</b> Your test payment received successfully.<br>Please <a href='%s'>disable your test membership</a> and you will see payment box again", GOURL), GOURL_ADMIN.GOURL."paypermembership_users&s=user".$current_user->ID);

		if ($is_paid) 			$tmp .= "<br><br><br>";
		elseif (!$coins_list) 	$tmp .= "<br><br>";
		else 					$tmp .= "<br>".$coins_list;


		$tmp .= "<div class='gourlbox' style='min-width:".$box_width."px;'>";

		// Cryptocoin Payment Box
		if ($languages_list) $tmp .= "<div style='margin:20px 0 5px 290px;font-family:\"Open Sans\",sans-serif;font-size:13px;color:#666;font-weight:normal;white-space:nowrap;'>".__('Language', GOURL).": ".$this->space(2).$languages_list."</div>";
		$tmp .= $box_html;

		$tmp .= "</div>";


		// End
		$tmp .= "</div>";


		if (!$is_paid && $textBelow && !$checkout_done) $tmp .= "<br><br><br>" . "<div class='gourlmembershiptext'>".$textBelow."</div>";
	}




		// Lock Page
		// -----------------------
		if (!$is_paid && !$preview_mode && !$checkout)
		{
			$tmp = GOURL_LOCK_START.$tmp.GOURL_LOCK_END;

			add_filter('the_content', 		'gourl_lock_filter', 11111);
			add_filter('the_content_rss', 	'gourl_lock_filter', 11111);
			add_filter('the_content_feed', 	'gourl_lock_filter', 11111);


			if ($hideTitles && $hideCurTitle)
			{
				if (!$logged)
				{
					add_filter("wp_title", 		'gourl_hide_headtitle_unlogged', 11111);
					add_filter("wp_title_rss", 	'gourl_hide_headtitle_unlogged', 11111);
				}
				else
				{
					add_filter("wp_title", 		'gourl_hide_headtitle', 11111);
					add_filter("wp_title_rss", 	'gourl_hide_headtitle', 11111);
				}

				add_filter('the_title', 	'gourl_hide_all_titles', 11111);
				add_filter('the_title_rss', 'gourl_hide_all_titles', 11111);
			}
			elseif ($hideTitles)
			{
				add_filter('the_title', 	'gourl_hide_menu_titles', 11111);
				add_filter('the_title_rss', 'gourl_hide_menu_titles', 11111);
			}
			elseif ($hideCurTitle)
			{
				if (!$logged)
				{
					add_filter("wp_title", 		'gourl_hide_headtitle_unlogged', 11111);
					add_filter("wp_title_rss", 	'gourl_hide_headtitle_unlogged', 11111);
				}
				else
				{
					add_filter("wp_title", 		'gourl_hide_headtitle', 11111);
					add_filter("wp_title_rss", 	'gourl_hide_headtitle', 11111);
				}

				add_filter('the_title', 	'gourl_hide_page_title', 11111);
				add_filter('the_title_rss', 'gourl_hide_page_title', 11111);
			}


			if ($commentAuthor) add_filter('get_comment_author_link', 	'gourl_return_false', 11111);

			if ($commentBody) add_filter('comment_text',	'gourl_lock_comments', 11111);


			if ($commentBody || $commentReply)
			{
				add_filter('post_comments_link',     'gourl_return_false', 1);
				add_filter('comment_reply_link',     'gourl_return_false', 1);
			}

			if ($commentReply)
			{
				add_filter('comments_open', 		'gourl_return_false', 1);
			}

			add_action('do_feed',      'gourl_disable_feed', 1);
			add_action('do_feed_rdf',  'gourl_disable_feed', 1);
			add_action('do_feed_rss',  'gourl_disable_feed', 1);
			add_action('do_feed_rss2', 'gourl_disable_feed', 1);
			add_action('do_feed_atom', 'gourl_disable_feed', 1);
		}

		$html = $tmp;

		return $tmp;
	}







	/*
	 *  51.
	*/
	public function page_membership_users()
	{
		global $wpdb;

		if ($this->is_nonadmin_user()) return true;

		$dt = gmdate('Y-m-d H:i:s');

		$search = "";
		if (isset($_GET["s"]) && trim($_GET["s"]))
		{
			$s = esc_sql(trim(mb_substr($_GET["s"], 0, 50)));

			if ($s == "active") $search = " && startDate <= '$dt' && endDate >= '$dt' && disabled = 0";
			elseif ($s == "manual") $search = " && paymentID = 0";
			elseif ($s == "disabled") $search = " && disabled = 1";
			elseif (strpos($s, "user_") === 0 && is_numeric(substr($s, 5))) $search = " && userID = ".intval(substr($s, 5));
			elseif (strpos($s, "user") === 0 && is_numeric(substr($s, 4))) $search = " && userID = ".intval(substr($s, 4));
			elseif (strpos($s, "payment_") === 0) $search = " && paymentID = ".intval(substr($s, 8));

			if (!$search)
			{
				$ids = "";
				$result = $wpdb->get_results("SELECT ID FROM $wpdb->users WHERE user_login LIKE '%".$s."%' || user_nicename LIKE '%".$s."%' || user_email LIKE '%".$s."%' || display_name LIKE '%".$s."%' LIMIT 200");
				foreach ( $result as $obj ) $ids .= ", " . intval($obj->ID);
				$ids = trim($ids, ", ");
				if ($ids) $ids = " || userID IN (".$ids.")";
				$search = " && (userID LIKE '%".$s."%' || paymentID LIKE '%".$s."%' || DATE_FORMAT(startDate, '%d %M %Y') LIKE '%".$s."%' || DATE_FORMAT(endDate, '%d %M %Y') LIKE '%".$s."%'".$ids.")";
			}
		}

		$res = $wpdb->get_row("SELECT count(membID) as cnt from crypto_membership WHERE 1".$search, OBJECT);
		$total = (int)$res->cnt;

		$res = $wpdb->get_row("SELECT count(distinct userID) as cnt from crypto_membership WHERE startDate <= '$dt' && endDate >= '$dt' && disabled = 0".$search, OBJECT);
		$active = (int)$res->cnt;

		$res = $wpdb->get_row("SELECT count(distinct userID) as cnt from crypto_membership WHERE paymentID = 0".$search, OBJECT);
		$manual = (int)$res->cnt;

		$res = $wpdb->get_row("SELECT count(distinct userID) as cnt from crypto_membership WHERE disabled = 1".$search, OBJECT);
		$disabled = (int)$res->cnt;


		$wp_list_table = new  gourl_table_premiumusers($search, $this->options['rec_per_page']);
		$wp_list_table->prepare_items();

		echo "<div class='wrap ".GOURL."admin'>";
		echo $this->page_title(__('All Premium Users', GOURL).$this->space(1).'<a class="add-new-h2" href="'.GOURL_ADMIN.GOURL.'paypermembership_user">' . __('Manually Add New User', GOURL) . '</a>'.$this->space(1).'<a class="add-new-h2" href="'.GOURL_ADMIN.GOURL.'paypermembership">' . __('Options', GOURL) . '</a>', 4);

		echo '<form class="gourlsearch" method="get" accept-charset="utf-8" action="">';
		echo '<input type="hidden" name="page" value="'.$this->page.'" />';
		$wp_list_table->search_box( 'search', 'search_id' );
		echo '</form>';

		echo "<div class='".GOURL."tablestats'>";
		echo "<div>";
		echo "<b>" . ($search?__('Found', GOURL):__('Total', GOURL)). ":</b> " . $total . " " . __('records', GOURL) . $this->space(3);
		echo "<b>" . __('Active Premium Users', GOURL). ":</b> ".$this->space().($search?$active:"<a href='".GOURL_ADMIN.GOURL."paypermembership_users&s=active'>$active</a>") . " " . __('users', GOURL) . $this->space(3);
		echo "<b>" . __('Manually Added', GOURL). ":</b> ".$this->space().($search?$manual:"<a href='".GOURL_ADMIN.GOURL."paypermembership_users&s=manual'>$manual</a>") . " " . __('users', GOURL) . $this->space(3);
		echo "<b>" . __('Manually Disabled', GOURL). ":</b> ".$this->space().($search?$disabled:"<a href='".GOURL_ADMIN.GOURL."paypermembership_users&s=disabled'>$disabled</a>") . " " . __('users', GOURL);
		if ($search) echo "<br><a href='".GOURL_ADMIN.GOURL."paypermembership_users'>" . __('Reset Search Filters', GOURL). "</a>";
		echo "</div>";
		echo "</div>";

		echo '<div class="'.GOURL.'userstable">';

		if ($this->updated)  echo '<div class="updated"><p>'.__('Table have been updated <strong>successfully</strong>', GOURL).'</p></div>';

		$wp_list_table->display();

		echo  '</div>';
		echo  '</div>';
		echo  '<br><br>';

		return true;
	}






	/**************** E. PAY-PER-MEMBERSHIP - NEW PREMIUM USER ************************************/


	/*
	 *  52.
	*/
	public function page_membership_user()
	{
		global $wpdb;

		if ($this->is_nonadmin_user()) return true;

		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		else $message = "";

		$tmp  = "<div class='wrap ".GOURL."admin'>";

		$tmp .= $this->page_title($this->id?__('Edit Premium User Membership', GOURL):__('New User Membership', GOURL), 4);
		$tmp .= "<div class='".GOURL."intro postbox'>";
		$tmp .=  __('Create Premium Membership manually if a user has sent the wrong amount of payment - therefore plugin cannot process payment and cannot create user premium membership in automatic mode', GOURL);
		$tmp .= "</div>";
		$tmp .= $message;

		$tmp .= "<form enctype='multipart/form-data' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."paypermembership_user&id=".$this->id."'>";

		$tmp .= "<div class='postbox'>";

		$tmp .= '<div class="alignright"><br>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership_user&id='.$this->id.(isset($_GET['userID'])?"&userID=".intval($_GET['userID']):"").'">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership_users">'.__('All Premium Users', GOURL).'</a>';
		$tmp .= '</div>';

		$tmp .= "<h3 class='hndle'>".__('Manually create Premium Membership', GOURL)."</h3>";
		$tmp .= "<div class='inside'>";

		$tmp .= '<input type="hidden" name="'.$this->adminform.'" value="'.GOURL.'save_membership_newuser" />';
		$tmp .= wp_nonce_field( $this->admin_form_key );

		$tmp .= '<div class="alignright">';
		$tmp .= '<img id="gourlsubmitloading" src="'.plugins_url('/images/loading.gif', __FILE__).'" border="0">';
		$tmp .= '<input type="submit" onclick="this.value=\''.__('Please wait...', GOURL).'\';document.getElementById(\'gourlsubmitloading\').style.display=\'inline\';return true;" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Record', GOURL).'">';
		if ($this->id) $tmp .= '<a href="'.GOURL_ADMIN.GOURL.'paypermembership_user">'.__('New Membership', GOURL).'</a>';
		$tmp .= '</div><br><br>';


		$tmp .= "<table class='".GOURL."table ".GOURL."newmembership'>";

		$tmp .= '<tr><th>'.__('User', GOURL).':</th>';
		$tmp .= '<td>';

		// User Selected
		$f = true;
		$this->record["userID"] = 0;
		if (isset($_GET['userID']) && intval($_GET['userID']))
		{
			$obj =  get_userdata(intval($_GET['userID']));
			if ($obj->data->user_nicename)
			{
				$tmp .= "<b>".$obj->data->user_nicename . $this->space(3)."-".$this->space(2)."id ".$obj->ID."</b>";
				$tmp .= '<input type="hidden" name="'.GOURL.'userID" id="'.GOURL.'userID" value="'.$obj->ID.'" />';
				$this->record["userID"] = $obj->ID;
				$f = false;
			}
		}


		if ($f)
		{
			$arr = get_users();

			$tmp .= '<select name="'.GOURL.'userID" id="'.GOURL.'userID">';
			$tmp .= '<option value="0">'.__('Select User', GOURL).'</option>';

			$arr = array_slice($arr, 0, 5000);

			$arr2 = array();
			foreach($arr as $row) $arr2[$row->ID] = $row->data->user_login . $this->space(3) . "-" . $this->space(2) . "id ".$row->ID." ";
			$arr = $arr2;
			asort($arr);

			foreach($arr as $k => $v)
					$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['userID']).'>'.$v.'</option>';

			$tmp .= '</select>';
		}

		$tmp .= '<br><em>'.sprintf(__("Select User. &#160; Current lock pages level: <a href='%s'>%s</a>.<br>Website Editors / Admins will have all the time full access to premium pages and see original page content.", GOURL), GOURL_ADMIN.GOURL.'paypermembership#'.GOURL.'form', $this->lock_level_membership[intval(get_option(GOURL.'ppmLevel'))]).'</em>';
		$tmp .= '</td></tr>';

		$tmp .= '<tr><th>'.__('Premium Start Date', GOURL).':</th>';
		$tmp .= '<td><input type="date" id="'.GOURL.'startDate" name="'.GOURL.'startDate" value="'.htmlspecialchars($this->record['startDate'], ENT_QUOTES).'" />';
		$tmp .= '<br><em>'.__('Premium Membership Start Date. Format: dd/mm/yyyy', GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Premium End Date', GOURL).':</th>';
		$tmp .= '<td><input type="date" id="'.GOURL.'endDate" name="'.GOURL.'endDate" value="'.htmlspecialchars($this->record['endDate'], ENT_QUOTES).'" />';
		$tmp .= '<br><em>'.__('Premium Membership End Date. Format: dd/mm/yyyy', GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '</table>';


		$tmp .= '</div>';
		$tmp .= '</div>';
		$tmp .= '</div>';


		echo $tmp;

	}



	/*
	 *  53.
	*/
	private function check_membership_newuser()
	{
		$this->record_errors = array();

		if (!$this->record["userID"]) 		$this->record_errors[] = __('User - cannot be empty', GOURL);
		if (!$this->record["startDate"]) 	$this->record_errors[] = __('Start Date - cannot be empty', GOURL);

		if (!$this->record["endDate"]) 		$this->record_errors[] = __('End Date - cannot be empty', GOURL);
		elseif (strtotime($this->record["startDate"]) >= strtotime($this->record["endDate"])) $this->record_errors[] = __('End Date - invalid value', GOURL);
		elseif (strtotime($this->record["endDate"]) <= strtotime(gmdate("Y-m-d"))) $this->record_errors[] = __('End Date - invalid value', GOURL);

		return true;
	}



	/*
	 *  54.
	*/
	private function save_membership_newuser()
	{
		global $wpdb;


		if (!(is_admin() && is_user_logged_in() && current_user_can('administrator')))
		{
			$this->record_errors[] = __('You don\'t have permission to edit this page. Please login as ADMIN user!', GOURL);
			return false;
		}


		$sql = "INSERT INTO crypto_membership (userID, paymentID, startDate, endDate, disabled, recordCreated)
				VALUES (
						'".esc_sql($this->record['userID'])."',
						0,
						'".esc_sql($this->record['startDate']." 00:00:00")."',
						'".esc_sql($this->record['endDate']." 23:59:00")."',
						0,
						'".esc_sql(gmdate('Y-m-d H:i:s'))."'
					)";

		$wpdb->query($sql);

		return true;
	}





	/**************** E. PAY-PER-PRODUCT ************************************/



	/*
	 *  55.
	*/
	public function check_product()
	{
		$this->record_errors = array();

		if ($this->record["productID"] != $this->id) $this->record_errors[] = __('Invalid Product ID, Please reload page', GOURL);

		if (!$this->record["productTitle"]) 							$this->record_errors[] = __('Product Title - cannot be empty', GOURL);
		elseif (mb_strlen($this->record["productTitle"]) > 100) 		$this->record_errors[] = __('Product Title - Max size 100 symbols', GOURL);

		$this->record["priceUSD"] = str_replace(",", "", $this->record["priceUSD"]);
		$this->record["priceCoin"] = str_replace(",", "", $this->record["priceCoin"]);
		if ($this->record["priceUSD"] == 0 && $this->record["priceCoin"] == 0) 	$this->record_errors[] = __('Price - cannot be empty', GOURL);
		if ($this->record["priceUSD"] != 0 && $this->record["priceCoin"] != 0) 	$this->record_errors[] = __('Price - use price in USD or in Cryptocoins. You cannot place values in two boxes together', GOURL);
		if ($this->record["priceUSD"] != 0 && (!is_numeric($this->record["priceUSD"]) || round($this->record["priceUSD"], 2) != $this->record["priceUSD"] || $this->record["priceUSD"] < 0.01 || $this->record["priceUSD"] > 100000)) $this->record_errors[] = sprintf(__('Price - %s USD - invalid value. Min value: 0.01 USD', GOURL), $this->record["priceUSD"]);
		if ($this->record["priceCoin"] != 0 && (!is_numeric($this->record["priceCoin"]) || round($this->record["priceCoin"], 4) != $this->record["priceCoin"] || $this->record["priceCoin"] < 0.0001 || $this->record["priceCoin"] > 500000000)) $this->record_errors[] = sprintf(__('Price - %s %s - invalid value. Min value: 0.0001 %s. Allow 4 digits max after floating point', GOURL), $this->record["priceCoin"], $this->record["priceLabel"], $this->record["priceLabel"]);

		if ($this->record["priceLabel"] && !isset($this->coin_names[$this->record["priceLabel"]])) $this->record_errors[] = sprintf(__("Price label '%s' - invalid value", GOURL), $this->record["priceLabel"]);

		if ($this->record["purchases"] && (!is_numeric($this->record["purchases"]) || round($this->record["purchases"]) != $this->record["purchases"] || $this->record["purchases"] < 0)) $this->record_errors[] = __('Purchase Limit - invalid value', GOURL);

		if (!$this->record["expiryPeriod"]) $this->record_errors[] = __("Field 'Expiry Period' - cannot be empty", GOURL);
		elseif (!in_array($this->record["expiryPeriod"], $this->expiry_period))	$this->record_errors[] = __("Field 'Expiry Period' - invalid value", GOURL);

		if (!isset($this->languages[$this->record["lang"]])) $this->record_errors[] = __("PaymentBox Language - invalid value", GOURL);

		if (!$this->record["defCoin"]) $this->record_errors[] = __("Field 'PaymentBox Coin' - cannot be empty", GOURL);
		elseif (!isset($this->coin_names[$this->record["defCoin"]])) $this->record_errors[] = __("Field 'PaymentBox Coin' - invalid value", GOURL);
		elseif (!isset($this->payments[$this->record["defCoin"]])) {
			if (!$this->payments) $this->record_errors[] = sprintf(__("You must choose at least one payment method. Please enter your GoUrl Public/Private Keys on <a href='%s'>settings page</a>. Instruction <a href='%s'>here &#187;</a>", GOURL),  GOURL_ADMIN.GOURL.'settings#gourlcurrencyconverterapi_key', GOURL_ADMIN.GOURL."#i3");
			$this->record_errors[] = sprintf( __("Field 'PaymentBox Coin' - payments in %s not available. Please re-save record", GOURL), $this->coin_names[$this->record["defCoin"]]);
		}
		elseif ($this->record["priceCoin"] != 0 && $this->record["defCoin"] != $this->record["priceLabel"]) $this->record_errors[] = sprintf(__("Field 'PaymentBox Coin' - please select '%s' because you have entered price in %s", GOURL), $this->coin_names[$this->record["priceLabel"]], $this->coin_names[$this->record["priceLabel"]]);

		if ($this->record["emailUser"])
		{
			if (!$this->record["emailUserFrom"]) 	$this->record_errors[] = __('Email to Buyer: From Email - cannot be empty', GOURL);
			if (!$this->record["emailUserTitle"]) 	$this->record_errors[] = __('Purchase Email Subject - cannot be empty', GOURL);
			if (!$this->record["emailUserBody"]) 	$this->record_errors[] = __('Purchase Email Body - cannot be empty', GOURL);
		}

		if ($this->record["emailAdmin"])
		{
			if (!$this->record["emailAdminFrom"]) 		$this->record_errors[] = __('Sale Notification From - cannot be empty', GOURL);
			if (!$this->record["emailAdminTitle"]) 		$this->record_errors[] = __('Sale Notification Subject - cannot be empty', GOURL);
			if (!$this->record["emailAdminBody"]) 		$this->record_errors[] = __('Sale Notification - cannot be empty', GOURL);
			if (!trim($this->record["emailAdminTo"])) 	$this->record_errors[] = __('Sale Notification To - cannot be empty', GOURL);
		}

		if ($this->record["emailUserFrom"] && !filter_var($this->record["emailUserFrom"], FILTER_VALIDATE_EMAIL)) $this->record_errors[] = sprintf(__('Email to Buyer: From Email - %s - invalid email format', GOURL), $this->record["emailUserFrom"]);
		if ($this->record["emailAdminFrom"] && !filter_var($this->record["emailAdminFrom"], FILTER_VALIDATE_EMAIL)) $this->record_errors[] = sprintf(__('Sale Notification From - %s - invalid email format', GOURL), $this->record["emailAdminFrom"]);
		if ($this->record["emailAdminTo"])
			foreach(explode("\n", $this->record["emailAdminTo"]) as $v)
				if (trim($v) && !filter_var(trim($v), FILTER_VALIDATE_EMAIL)) $this->record_errors[] = sprintf(__('Sale Notification To - %s - invalid email format', GOURL), trim($v));

		if ($this->record["priceCoin"] != 0 && !$this->record["defShow"] && !$this->record_errors) $this->record["defShow"] = 1;
		//if ($this->record["priceCoin"] != 0 && !$this->record["defShow"]) $this->record_errors[] = sprintf(__('Field "Use Default Coin Only" - check this field because you have entered price in %s. Please use price in USD if you want to accept multiple coins', GOURL), $this->coin_names[$this->record["priceLabel"]]);

		return true;

	}


	/*
	 *  56.
	*/
	public function save_product()
	{
		global $wpdb;

		$dt = gmdate('Y-m-d H:i:s');

		if (!(is_admin() && is_user_logged_in() && current_user_can('administrator')))
		{
			$this->record_errors[] = __('You don\'t have permission to edit this page. Please login as ADMIN user!', GOURL);
			return false;
		}


		if ($this->record['priceUSD'] <= 0)  $this->record['priceUSD'] = 0;
		if ($this->record['priceCoin'] <= 0 || $this->record['priceUSD'] > 0) { $this->record['priceCoin'] = 0; $this->record['priceLabel'] = ""; }

		if ($this->id)
		{
			$sql = "UPDATE crypto_products
					SET
						productTitle 	= '".esc_sql($this->record['productTitle'])."',
						productText 	= '".esc_sql($this->record['productText'])."',
						finalText 		= '".esc_sql($this->record['finalText'])."',
						active 			= '".$this->record['active']."',
						priceUSD 		= ".$this->record['priceUSD'].",
						priceCoin 		= ".$this->record['priceCoin'].",
						priceLabel 		= '".$this->record['priceLabel']."',
						purchases 		= '".$this->record['purchases']."',
						expiryPeriod	= '".esc_sql($this->record['expiryPeriod'])."',
						lang 			= '".$this->record['lang']."',
						defCoin			= '".esc_sql($this->record['defCoin'])."',
						defShow 		= '".$this->record['defShow']."',
						emailUser		= '".$this->record['emailUser']."',
						emailUserFrom	= '".esc_sql($this->record['emailUserFrom'])."',
						emailUserTitle	= '".esc_sql($this->record['emailUserTitle'])."',
						emailUserBody	= '".esc_sql($this->record['emailUserBody'])."',
						emailAdmin		= '".$this->record['emailAdmin']."',
						emailAdminFrom	= '".esc_sql($this->record['emailAdminFrom'])."',
						emailAdminTitle	= '".esc_sql($this->record['emailAdminTitle'])."',
						emailAdminBody	= '".esc_sql($this->record['emailAdminBody'])."',
						emailAdminTo= '".esc_sql($this->record['emailAdminTo'])."',
						updatetime 		= '".$dt."'
					WHERE productID 	= ".$this->id."
					LIMIT 1";
		}
		else
		{
			$sql = "INSERT INTO crypto_products (productTitle, productText, finalText, active, priceUSD, priceCoin, priceLabel, purchases, expiryPeriod, lang, defCoin, defShow,
							emailUser, emailUserFrom, emailUserTitle, emailUserBody, emailAdmin, emailAdminFrom, emailAdminTitle, emailAdminBody, emailAdminTo, paymentCnt, updatetime, createtime)
					VALUES (
							'".esc_sql($this->record['productTitle'])."',
							'".esc_sql($this->record['productText'])."',
							'".esc_sql($this->record['finalText'])."',
							1,
							".$this->record['priceUSD'].",
							".$this->record['priceCoin'].",
							'".$this->record['priceLabel']."',
							'".$this->record['purchases']."',
							'".esc_sql($this->record['expiryPeriod'])."',
							'".$this->record['lang']."',
							'".esc_sql($this->record['defCoin'])."',
							'".$this->record['defShow']."',
							'".$this->record['emailUser']."',
							'".esc_sql($this->record['emailUserFrom'])."',
							'".esc_sql($this->record['emailUserTitle'])."',
							'".esc_sql($this->record['emailUserBody'])."',
							'".$this->record['emailAdmin']."',
							'".esc_sql($this->record['emailAdminFrom'])."',
							'".esc_sql($this->record['emailAdminTitle'])."',
							'".esc_sql($this->record['emailAdminBody'])."',
							'".esc_sql($this->record['emailAdminTo'])."',
							0,
							'".$dt."',
							'".$dt."'
						)";
		}

		if (!get_option('users_can_register')) update_option('users_can_register', 1);

		if ($wpdb->query($sql) === false) $this->record_errors[] = "Error in SQL : " . $sql;
		elseif (!$this->id) $this->id = $wpdb->insert_id;

		return true;

	}




	/*
	 *  57.
	*/
	public function page_newproduct()
	{

		if ($this->is_nonadmin_user()) return true;

		$preview 		= ($this->id && isset($_GET["preview"]) && $_GET["preview"] == "true") ? true : false;
		$preview_final  = ($this->id && isset($_GET["previewfinal"]) && $_GET["previewfinal"] == "true") ? true : false;
		$preview_email  = ($this->id && isset($_GET["previewemail"]) && $_GET["previewemail"] == "true") ? true : false;

		if ($this->record_errors) $message = "<div class='error'>".__('Please fix errors below:', GOURL)."<ul><li>- ".implode("</li><li>- ", $this->record_errors)."</li></ul></div>";
		elseif ($this->updated)  $message = '<div class="updated"><p>'.__('Record has been saved <strong>successfully</strong>', GOURL).'</p></div>';
		else $message = "";

		if ($this->record_info) $message .= '<div class="updated"><ul><li>- '.implode("</li><li>- ", $this->record_info).'</li></ul></div>';


		$tmp  = "<div class='wrap ".GOURL."admin'>";
		$tmp .= $this->page_title($this->id?__('Edit Product', GOURL):__('New Product', GOURL), 5);
		$tmp .= $message;

		$short_code = '['.GOURL_TAG_PRODUCT.' id="'.$this->id.'"]';

		if ($preview || $preview_final || $preview_email)
		{
			$tmp .= "<div class='postbox'>";
			$tmp .= "<h3 class='hndle'>".sprintf(__('Preview Shortcode &#160; &#160; %s', GOURL), $short_code) . ($preview_email?$this->space(2)."-".$this->space().__('Emails', GOURL):"");
			$tmp .= "<a href='".GOURL_ADMIN.GOURL."product&id=".$this->id."' class='gourlright ".GOURL."button button-primary'>".__('Close Preview', GOURL)."</a>";
			$tmp .= "</h3>";
			$tmp .= "<div class='inside'>";


			if ($preview_email)
			{
				$txt_from = array("{user_fullname}", "{user_username}", "{user_id}", "{user_email}", "{user_url}", "{paid_amount}", "{paid_amount_usd}", "{payment_id}", "{payment_url}", "{transaction_id}", "{transaction_time}");
				$txt_to = array("John Smith", "john2", 1, "john@example.com", admin_url("user-edit.php?user_id=1"), "0.335301 BTC", "~112.3 USD", 11, GOURL_ADMIN.GOURL."payments&s=payment_11", "2bed6fb8bb35d42842519d445b099fdee6da5d65280167333342d879b4ab93a1", "18 Dec 2014, 11:15:48 am");

				$tmp .= "<p>".__('Used template tags for preview:', GOURL)."<br><i><b>{user_fullname}</b> - John Smith, <b>{user_username}</b> - john2, <b>{user_id}</b> - 1, <b>{user_email}</b> - john@example.com, <b>{user_url}</b> - ".admin_url("user-edit.php?user_id=1").", <b>{paid_amount}</b> - 0.335301 BTC, <b>{paid_amount_usd}</b> - ~112.3 USD, <b>{payment_id}</b> - 11, <b>{payment_url}</b> - ".GOURL_ADMIN.GOURL."payments&s=payment_11, <b>{transaction_id}</b> - 2bed6fb8bb35d42842519d445b099fdee6da5d65280167333342d879b4ab93a1, <b>{transaction_time}</b> - 18 Dec 2014, 11:15:48 am</i></p>";


				$subject = (mb_strpos($this->record['emailUserTitle'], "{")=== false) ? $this->record['emailUserTitle'] : str_replace($txt_from, $txt_to, $this->record['emailUserTitle']);
				$body = (mb_strpos($this->record['emailUserBody'], "{")=== false) ? $this->record['emailUserBody'] : str_replace($txt_from, $txt_to, $this->record['emailUserBody']);

				$tmp .= "<h3><br>".__('Email to Buyer - Purchase Receipt', GOURL).$this->space(2).gourl_checked_image($this->record['emailUser']).$this->space()."<small class='".($this->record['emailUser']?"updated":"error")."'>".($this->record['emailUser']?__('Activated', GOURL):__('Not Active', GOURL))."</small></h3>";
				$tmp .= "<hr align='left' width='200'>";
				$tmp .= "<p><b>".__('From:', GOURL)."</b>".$this->space().htmlspecialchars($this->record['emailUserFrom'], ENT_QUOTES)."</p>";
				$tmp .= "<p><b>".__('To:', GOURL)."</b>".$this->space().__('- user registered email -', GOURL)."</p>";
				$tmp .= "<p><b>".__('Subject:', GOURL)."</b>".$this->space().htmlspecialchars($subject, ENT_QUOTES)."</p>";
				$tmp .= "<p><b>".__('Body:', GOURL)."</b></p>".nl2br(htmlspecialchars($body, ENT_QUOTES));


				$tmp .= "<br><br>";

				$subject = (mb_strpos($this->record['emailAdminTitle'], "{")=== false) ? $this->record['emailAdminTitle'] : str_replace($txt_from, $txt_to, $this->record['emailAdminTitle']);
				$body = (mb_strpos($this->record['emailAdminBody'], "{")=== false) ? $this->record['emailAdminBody'] : str_replace($txt_from, $txt_to, $this->record['emailAdminBody']);

				$tmp .= "<h3>".__('Email to Seller/Admin - Sale Notification', GOURL).$this->space(2).gourl_checked_image($this->record['emailAdmin']).$this->space()."<small class='".($this->record['emailAdmin']?"updated":"error")."'>".($this->record['emailAdmin']?__('Activated', GOURL):__('Not Active', GOURL))."</small></h3>";
				$tmp .= "<hr align='left' width='200'>";
				$tmp .= "<p><b>".__('From:', GOURL)."</b>".$this->space().htmlspecialchars($this->record['emailAdminFrom'], ENT_QUOTES)."</p>";
				$tmp .= "<p><b>".__('To:', GOURL)."</b>".$this->space().htmlspecialchars($this->record['emailAdminTo'], ENT_QUOTES)."</p>";
				$tmp .= "<p><b>".__('Subject:', GOURL)."</b>".$this->space().htmlspecialchars($subject, ENT_QUOTES)."</p>";
				$tmp .= "<p><b>".__('Body:', GOURL)."</b></p>".nl2br(htmlspecialchars($body, ENT_QUOTES));

			}
			else
			{
				$tmp .= $this->shortcode_product(array("id"=>$this->id), $preview_final);
			}
			$tmp .= "</div>";
			$tmp .= '<div class="gourlright"><small>'.__('Shortcode', GOURL).': &#160;  '.$short_code.'</small></div>';
			$tmp .= "</div>";
		}

		$tmp .= "<form enctype='multipart/form-data' method='post' accept-charset='utf-8' action='".GOURL_ADMIN.GOURL."product&id=".$this->id."'>";

		$tmp .= "<div class='postbox".($preview?" previewactive":"")."'>";

		$tmp .= '<div class="alignright"><br>';
		if ($this->id && $this->record['paymentCnt']) $tmp .= "<a style='margin-top:-7px' href='".GOURL_ADMIN.GOURL."payments&s=product_".$this->id."' class='".GOURL."button button-secondary'>".sprintf(__('Sold %d copies', GOURL), $this->record['paymentCnt'])."</a>".$this->space();
		if ($this->id) $tmp .= '<a href="'.GOURL_ADMIN.GOURL.'product">'.__('New product', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'product&id='.$this->id.'">'.__('Reload Page', GOURL).'</a>';
		$tmp .= '<a href="'.GOURL_ADMIN.GOURL.'products">'.__('All Paid Products', GOURL).'</a>';
		$tmp .= '</div>';

		$tmp .= "<h3 class='hndle'>".__($this->id?__('Edit Product', GOURL):__('Create New Product', GOURL))."</h3>";
		$tmp .= "<div class='inside'>";

		$tmp .= '<input type="hidden" name="'.$this->adminform.'" value="'.GOURL.'save_product" />';
		$tmp .= wp_nonce_field( $this->admin_form_key );

		$tmp .= '<div class="alignright">';
		$tmp .= '<img id="gourlsubmitloading" src="'.plugins_url('/images/loading.gif', __FILE__).'" border="0">';
		$tmp .= '<input type="submit" onclick="this.value=\''.__('Please wait...', GOURL).'\';document.getElementById(\'gourlsubmitloading\').style.display=\'inline\';return true;" class="'.GOURL.'button button-primary" name="submit" value="'.__('Save Record', GOURL).'">';
		if ($this->id && !$preview) 		$tmp .= "<a href='".GOURL_ADMIN.GOURL."product&id=".$this->id."&gourlcryptocoin=".$this->coin_names[$this->record['defCoin']]."&gourlcryptolang=".$this->record['lang']."&preview=true' class='".GOURL."button button-secondary'>".__('Show Preview', GOURL)."</a>".$this->space(2);
		if ($this->id && !$preview_final) 	$tmp .= "<a href='".GOURL_ADMIN.GOURL."product&id=".$this->id."&gourlcryptocoin=".$this->coin_names[$this->record['defCoin']]."&gourlcryptolang=".$this->record['lang']."&previewfinal=true' class='".GOURL."button button-secondary'>".__('Preview - Paid', GOURL)."</a>".$this->space(2);
		if ($this->id && !$preview_email) 	$tmp .= "<a href='".GOURL_ADMIN.GOURL."product&id=".$this->id."&gourlcryptocoin=".$this->coin_names[$this->record['defCoin']]."&gourlcryptolang=".$this->record['lang']."&previewemail=true' class='".GOURL."button button-secondary'>".__('Preview - Emails', GOURL)."</a>".$this->space(2);
		$tmp .= "<a target='_blank' href='".plugins_url('/images/tagexample_product_full.png', __FILE__)."' class='".GOURL."button button-secondary'>".__('Instruction', GOURL)."</a>".$this->space();
		$tmp .= '</div><br><br>';


		$tmp .= "<table class='".GOURL."table ".GOURL."product'>";

		if ($this->id)
		{
			$tmp .= '<tr><th>'.__('Product ID', GOURL).':</th>';
			$tmp .= '<td><b>'.$this->record['productID'].'</b></td>';
			$tmp .= '</tr>';
			$tmp .= '<tr><th>'.__('Shortcode', GOURL).':</th>';
			$tmp .= '<td><b>['.GOURL_TAG_PRODUCT.' id="'.$this->id.'"]</b><br><em>'.sprintf(__("Just <a target='_blank' href='%s'>add this shortcode</a> to any your page or post (in html view) and cryptocoin payment box will be display", GOURL), plugins_url('/images/tagexample_product_full.png', __FILE__)).'</em></td>';
			$tmp .= '</tr>';
		}

		$tmp .= '<tr><th>'.__('Product Title', GOURL).':';
		$tmp .= '<input type="hidden" name="'.GOURL.'productID" id="'.GOURL.'productID" value="'.htmlspecialchars($this->record['productID'], ENT_QUOTES).'">';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'productTitle" id="'.GOURL.'productTitle" value="'.htmlspecialchars($this->record['productTitle'], ENT_QUOTES).'" class="widefat"><br><em>'.__('Title for the product. Users will see this title', GOURL).'</em></td>';
		$tmp .= '</tr>';

		if ($this->id)
		{
			$tmp .= '<tr><th>'.__('Active ?', GOURL).'</th>';
			$tmp .= '<td><input type="checkbox" name="'.GOURL.'active" id="'.GOURL.'active" value="1" '.$this->chk($this->record['active'], 1).' class="widefat"><br><em>'.__('If box is not checked, visitors cannot pay you for this product', GOURL).'</em></td>';
			$tmp .= '</tr>';
		}

		$tmp .= '<tr><th>'.__('Price', GOURL).':</th><td>';
		$tmp .= '<input type="text" class="gourlnumeric" name="'.GOURL.'priceUSD" id="'.GOURL.'priceUSD" value="'.htmlspecialchars($this->record['priceUSD'], ENT_QUOTES).'"><label><b>'.__('USD', GOURL).'</b></label>';
		$tmp .= $this->space(2).'<label>'.__('or', GOURL).'</label>'.$this->space(5);
		$tmp .= '<input type="text" class="gourlnumeric2" name="'.GOURL.'priceCoin" id="'.GOURL.'priceCoin" value="'.htmlspecialchars($this->record['priceCoin'], ENT_QUOTES).'">'.$this->space();
		$tmp .= '<select name="'.GOURL.'priceLabel" id="'.GOURL.'priceLabel">';
		foreach($this->coin_names as $k => $v) $tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['priceLabel']).'>'.$k.$this->space().'('.$v.')</option>';
		$tmp .= '</select>';
		$tmp .= '<br><em>'.sprintf(__("Please specify price in USD or in Cryptocoins. You cannot place prices in two boxes together. If you want to accept multiple coins - please use price in USD, payment box will automatically convert that USD amount to cryptocoin amount using today live cryptocurrency exchange rates (updated every 30min). Using that functionality (price in USD), you don't need to worry if cryptocurrency prices go down or go up. Visitors will pay you all times the actual price which is linked on daily exchange price in USD on the time of purchase. Also you can use <a target='_blank' href='%s'>Poloniex 'autosell' feature</a> (auto trade your cryptocoins to USD).", GOURL), "https://poloniex.com/").'</em>';
		$tmp .= '</td></tr>';


		$tmp .= '<tr><th>'.__('Purchase Limit', GOURL).':</th>';
		$tmp .= '<td><input type="text" class="gourlnumeric" name="'.GOURL.'purchases" id="'.GOURL.'purchases" value="'.htmlspecialchars($this->record['purchases'], ENT_QUOTES).'"><label>'.__('copies', GOURL).'</label><br><em>'.__('The maximum number of times a product may be purchased. Leave blank or set to 0 for unlimited number of product purchases', GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Expiry Period', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'expiryPeriod" id="'.GOURL.'expiryPeriod">';

		foreach($this->expiry_period as $v)
			$tmp .= '<option value="'.$v.'"'.$this->sel($v, $this->record['expiryPeriod']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<br><em>'.sprintf(__("Period after which the payment becomes obsolete and new Payment Box will be shown for this product (you can use it to take new payments from users periodically on daily/monthly basis)<br>For quickly repeated purchases with shopping cart, you can use <a href='%s'>WooCommerce</a> with <a href='%s'>GoUrl WooCommerce Addon</a> also", GOURL), "https://wordpress.org/plugins/woocommerce/", admin_url('plugin-install.php?tab=search&type=term&s=gourl+woocommerce+addon')).'</em></td>';
		$tmp .= '</tr>';


		$tmp .= '<tr><th>'.__('PaymentBox Language', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'lang" id="'.GOURL.'lang">';

		foreach($this->languages as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['lang']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<br><em>'.__('Default Payment Box Localisation', GOURL).'</em></td>';
		$tmp .= '</tr>';



		$tmp .= '<tr><th>'.__('PaymentBox Coin', GOURL).':</th>';
		$tmp .= '<td><select name="'.GOURL.'defCoin" id="'.GOURL.'defCoin">';

		foreach($this->payments as $k => $v)
			$tmp .= '<option value="'.$k.'"'.$this->sel($k, $this->record['defCoin']).'>'.$v.'</option>';

		$tmp .= '</select>';
		$tmp .= '<span class="gourlpayments">' . __('Activated Payments :', GOURL) . " <a href='".GOURL_ADMIN.GOURL."settings'><b>" . ($this->payments?implode(", ", $this->payments):__('- Please Setup -', GOURL)) . '</b></a></span>';
		$tmp .= '<br><em>'.__('Default Coin in Payment Box', GOURL).'</em></td>';
		$tmp .= '</tr>';



		$tmp .= '<tr><th>'.__('Use Default Coin only:', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'defShow" id="'.GOURL.'defShow" value="1" '.$this->chk($this->record['defShow'], 1).' class="widefat"><br><em>'.__("If box is checked, payment box will accept payments in one default coin 'PaymentBox Coin' (no multiple coins)", GOURL).'</em></td>';
		$tmp .= '</tr>';


		$tmp .= '<tr><th>'.__('A. Product Description (Unpaid yet)', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->record['productText'], GOURL.'productText', array('textarea_name' => GOURL.'productText', 'quicktags' => true, 'media_buttons' => true, 'textarea_rows' => 8, 'wpautop' => false));
		$tmp  = '<br><em>'.__('Product Description. Users will see this product description when no payment has been received yet', GOURL).'</em>';
		$tmp .= '</td></tr>';

		$tmp .= '<tr><th>'.__('B. Product Description (Paid already)', GOURL).':</th><td>';
		echo $tmp;
		wp_editor( $this->record['finalText'], GOURL.'finalText', array('textarea_name' => GOURL.'finalText', 'quicktags' => true, 'media_buttons' => true, 'textarea_rows' => 8, 'wpautop' => false));
		$tmp  = '<br><em>'.sprintf(__("Users will see this product description when payment has been successfully received. If you leave field empty, it will display content from 'A. Product Description - unpaid' field<br>Available template tags: %s", GOURL), '{user_fullname} {user_username} {user_id} {user_email} {paid_amount} {paid_amount_usd} {payment_id} {transaction_id} {transaction_time}').'</em>';
		$tmp .= '</td></tr>';


		$tmp .= '<tr><th>'.__('Email to Buyer ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'emailUser" id="'.GOURL.'emailUser" value="1" '.$this->chk($this->record['emailUser'], 1).' class="widefat"><br><em>'.__('If box is checked, purchase receipt email will be sent to Buyer on user registered email', GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Purchase Email - From', GOURL).':';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'emailUserFrom" id="'.GOURL.'emailUserFrom" value="'.htmlspecialchars($this->record['emailUserFrom'], ENT_QUOTES).'" class="widefat"><br><em>'.__("Email to Buyer: This will act as the 'from' and 'reply-to' address in email", GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Purchase Email - Subject', GOURL).':';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'emailUserTitle" id="'.GOURL.'emailUserTitle" value="'.htmlspecialchars($this->record['emailUserTitle'], ENT_QUOTES).'" class="widefat"><br><em>'.__('Email to Buyer: Enter the subject line for the purchase receipt email', GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Purchase Email - Body', GOURL).':</th>';
		$tmp .= '<td><textarea id="'.GOURL.'emailUserBody" name="'.GOURL.'emailUserBody" class="widefat" style="height: 200px;">'.htmlspecialchars($this->record['emailUserBody'], ENT_QUOTES).'</textarea><br><em>'.sprintf(__('Email to Buyer: Enter email body that is sent to users after completing a successful purchase. HTML is not accepted.<br>Available template tags: %s', GOURL), '{user_fullname} {user_username} {user_id} {user_email} {user_url} {paid_amount} {paid_amount_usd} {payment_id} {payment_url} {transaction_id} {transaction_time}').'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Email to Seller/Admin ?', GOURL).'</th>';
		$tmp .= '<td><input type="checkbox" name="'.GOURL.'emailAdmin" id="'.GOURL.'emailAdmin" value="1" '.$this->chk($this->record['emailAdmin'], 1).' class="widefat"><br><em>'.__('If box is checked, new sale notification email will be sent to Seller/Admin', GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Sale Notification - From', GOURL).':';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'emailAdminFrom" id="'.GOURL.'emailAdminFrom" value="'.htmlspecialchars($this->record['emailAdminFrom'], ENT_QUOTES).'" class="widefat"><br><em>'.__("Email to Seller: This will act as the 'from' and 'reply-to' email address", GOURL).'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Sale Notification - Subject', GOURL).':';
		$tmp .= '</th>';
		$tmp .= '<td><input type="text" name="'.GOURL.'emailAdminTitle" id="'.GOURL.'emailAdminTitle" value="'.htmlspecialchars($this->record['emailAdminTitle'], ENT_QUOTES).'" class="widefat"><br><em>'.sprintf(__('Email to Seller: Enter the subject line for the sale notification email<br>Available template tags: %s', GOURL), '{user_fullname} {user_username} {user_id} {user_email} {user_url} {paid_amount} {paid_amount_usd} {payment_id} {payment_url} {transaction_id} {transaction_time}').'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Sale Notification - Body', GOURL).':</th>';
		$tmp .= '<td><textarea id="'.GOURL.'emailAdminBody" name="'.GOURL.'emailAdminBody" class="widefat" style="height: 200px;">'.htmlspecialchars($this->record['emailAdminBody'], ENT_QUOTES).'</textarea><br><em>'.sprintf(__('Email to Seller: Enter the sale notification email that is sent to seller/admin after user completing a successful purchase.<br>Available template tags: %s', GOURL), '{user_fullname} {user_username} {user_id} {user_email} {user_url} {paid_amount} {paid_amount_usd} {payment_id} {payment_url} {transaction_id} {transaction_time}').'</em></td>';
		$tmp .= '</tr>';

		$tmp .= '<tr><th>'.__('Sale Notification - To', GOURL).':</th>';
		$tmp .= '<td><textarea id="'.GOURL.'emailAdminTo" name="'.GOURL.'emailAdminTo" class="widefat" style="height: 120px;">'.htmlspecialchars($this->record['emailAdminTo'], ENT_QUOTES).'</textarea><br><em>'.__('Email to Seller: Enter the email address(es) that should receive a notification anytime a sale is made, one per line', GOURL).'</em></td>';
		$tmp .= '</tr>';



		if ($this->id)
		{
			$tmp .= '<tr><th>'.__('Total Sold', GOURL).':</th>';
			$tmp .= '<td><input type="hidden" name="'.GOURL.'paymentCnt" id="'.GOURL.'paymentCnt" value="'.htmlspecialchars($this->record['paymentCnt'], ENT_QUOTES).'"><b>'.$this->record['paymentCnt'].' '.__('copies', GOURL).'</b></td>';
			$tmp .= '</tr>';

			if ($this->record['paymentCnt'])
			{
				$tmp .= '<tr><th>'.__('Latest Received Payment', GOURL).':</th>';
				$tmp .= '<td><input type="hidden" name="'.GOURL.'paymentTime" id="'.GOURL.'paymentTime" value="'.htmlspecialchars($this->record['paymentTime'], ENT_QUOTES).'"><b>'.date('d M Y, H:i:s a', strtotime($this->record['paymentTime'])).' GMT</b></td>';
				$tmp .= '</tr>';
			}

			if ($this->record['updatetime'] && $this->record['updatetime'] != $this->record['createtime'])
			{
				$tmp .= '<tr><th>'.__('Record Updated', GOURL).':</th>';
				$tmp .= '<td><input type="hidden" name="'.GOURL.'updatetime" id="'.GOURL.'updatetime" value="'.htmlspecialchars($this->record['updatetime'], ENT_QUOTES).'">'.date('d M Y, H:i:s a', strtotime($this->record['updatetime'])).' GMT</td>';
				$tmp .= '</tr>';
			}

			$tmp .= '<tr><th>'.__('Record Created', GOURL).':</th>';
			$tmp .= '<td><input type="hidden" name="'.GOURL.'createtime" id="'.GOURL.'createtime" value="'.htmlspecialchars($this->record['createtime'], ENT_QUOTES).'">'.date('d M Y, H:i:s a', strtotime($this->record['createtime'])).' GMT</td>';
			$tmp .= '</tr>';

			$tmp .= '<tr><th>'.__('Custom Actions', GOURL).':</th>';
			$tmp .= '<td><em>'.sprintf(__("Optional - add in file gourl_ipn.php code below. <a href='%s'>Read more &#187;</a>", GOURL), GOURL_ADMIN.GOURL."#i5");
			$tmp .= '<br><i>case "product_'.$this->id.'": &#160; &#160; // order_ID = product_'.$this->id.'<br>// ...your_code...<br>break;</i></em>';
			$tmp .= '</td></tr>';
		}

		$tmp .= '</table>';


		$tmp .= '</div></div>';
		$tmp .= '</form></div>';

		echo $tmp;

		return true;
	}



	/*
	 *  58.
	*/
	public function page_products()
	{
		global $wpdb;

		if ($this->is_nonadmin_user()) return true;

		if (isset($_GET["intro"]))
		{
			$intro = intval($_GET["intro"]);
			update_option(GOURL."page_products_intro", $intro);
		}
		else $intro = get_option(GOURL."page_products_intro");


		$search = "";
		if (isset($_GET["s"]) && trim($_GET["s"]))
		{
			$s = esc_sql(trim(mb_substr($_GET["s"], 0, 50)));

			if ($s == "sold") 			$search = " && paymentCnt > 0";
			elseif ($s == "active") 	$search = " && active != 0";
			elseif ($s == "inactive") 	$search = " && active = 0";
			elseif (in_array(strtolower($s), $this->coin_names)) $search = " && (priceLabel = '".array_search(strtolower($s), $this->coin_names)."' || defCoin = '".array_search(strtolower($s), $this->coin_names)."')";
			elseif (isset($this->coin_names[strtoupper($s)])) $search = " && (priceLabel = '".strtoupper($s)."' || defCoin = '".strtoupper($s)."')";

			if (!$search)
			{
				if (in_array(ucwords(strtolower($s)), $this->languages)) $s = esc_sql(array_search(ucwords(strtolower($s)), $this->languages));
				if (substr(strtoupper($s), -4) == " USD") $s = substr($s, 0, -4);

				$search = " && (productTitle LIKE '%".$s."%' || productText LIKE '%".$s."%' || finalText LIKE '%".$s."%' || priceUSD LIKE '%".$s."%' || priceCoin LIKE '%".$s."%' || priceLabel LIKE '%".$s."%' || expiryPeriod LIKE '%".$s."%' || defCoin LIKE '%".$s."%' || emailUserFrom LIKE '%".$s."%' || emailUserTitle LIKE '%".$s."%' || emailUserBody LIKE '%".$s."%' || emailAdminFrom LIKE '%".$s."%' || emailAdminTitle LIKE '%".$s."%' || emailAdminBody LIKE '%".$s."%' || emailAdminTo LIKE '%".$s."%' || paymentCnt LIKE '%".$s."%' || lang LIKE '%".$s."%' || DATE_FORMAT(createtime, '%d %M %Y') LIKE '%".$s."%')";
			}
		}

		$res = $wpdb->get_row("SELECT count(productID) as cnt from crypto_products WHERE active != 0".$search, OBJECT);
		$active = (int)$res->cnt;

		$res = $wpdb->get_row("SELECT count(productID) as cnt from crypto_products WHERE active = 0".$search, OBJECT);
		$inactive = (int)$res->cnt;

		$res = $wpdb->get_row("SELECT sum(paymentCnt) as total from crypto_products WHERE paymentCnt > 0".$search, OBJECT);
		$sold = (int)$res->total;


		$wp_list_table = new  gourl_table_products($search, $this->options['rec_per_page']);
		$wp_list_table->prepare_items();

		echo "<div class='wrap ".GOURL."admin'>";
		echo $this->page_title(__('All Paid Products', GOURL).$this->space(1).'<a class="add-new-h2" href="'.GOURL_ADMIN.GOURL.'product">' . __('Add New Product', GOURL) . '</a>', 5);

		if (!$intro)
		{
			echo '<div class="'.GOURL.'intro_btn"><a href="'.GOURL_ADMIN.GOURL.'products&intro=1" class="'.GOURL.'button button-secondary">'.__('Hide Introduction', GOURL).' &#8595;</a></div>';
			echo "<div class='".GOURL."intro postbox'>";
			echo '<a style="float:right" target="_blank" href="https://gourl.io/lib/examples/pay-per-product-multi.php"><img hspace="10" width="240" height="95" title="Example - Pay Per Product" src="'.plugins_url('/images/pay-per-product.png', __FILE__).'" border="0"></a>';
			echo '<p>'.__("Use 'Pay-Per-product' - sell any of your products online to registered users. Email notifications to Buyer/Seller.", GOURL) . '</p>';
			echo '<p>'.sprintf(__("You will need to <a href='%s'>create a new product record</a> of what you are selling, you get custom WordPress shortcode, <a href='%s'>place that shortcode</a> on any of your website pages and user will see the product payment box.", GOURL), GOURL_ADMIN.GOURL.'product', plugins_url('/images/tagexample_product_full.png', __FILE__)).'</p>';
			echo '<p>'.sprintf(__("Please activate website registration (General Settings &#187; Membership - <a href='%s'>Anyone can register</a>). &#160; For unregistered visitors - you can customize <a href='%s'>Login Image</a> or choose to display <a href='%s'>Login Form</a>", GOURL), admin_url('options-general.php'), GOURL_ADMIN.GOURL."settings#images", GOURL_ADMIN.GOURL."settings#images").'</p>';
			echo '<p>'.sprintf(__("See also - <a href='%s'>Installation Instruction</a>", GOURL), GOURL_ADMIN.GOURL.'#i3') . '</p>';
			echo '<p><b>-----------------<br>'.sprintf(__("Alternatively, you can use free <a href='%s'>WooCommerce</a> plugin (advanced shopping plugin with 'GUEST CHECKOUT' option) with our <a href='%s'>Woocommerce Bitcoin/Altcoin Gateway</a> addon", GOURL), admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce+excelling+eCommerce+WooThemes+Beautifully'), admin_url('plugin-install.php?tab=search&type=term&s=gourl+woocommerce+addon')) . '</b></p>';
			echo  "</div>";
		}

		echo '<form class="gourlsearch" method="get" accept-charset="utf-8" action="">';
		if ($intro) echo '<a href="'.GOURL_ADMIN.GOURL.'products&intro=0" class="'.GOURL.'button button-secondary">'.__('Show Introduction', GOURL).' &#8593;</a> &#160; &#160; ';
		echo '<input type="hidden" name="page" value="'.$this->page.'" />';
		$wp_list_table->search_box( 'search', 'search_id' );
		echo '</form>';

		echo "<div class='".GOURL."tablestats'>";
		echo "<div>";
		echo "<b>" . __($search?__('Found', GOURL):__('Total products', GOURL)). ":</b> " . ($active+$inactive) . " " . __('products', GOURL) . $this->space(1) . "( ";
		echo "<b>" . __('Active', GOURL). ":</b> " . ($search?$active:"<a href='".GOURL_ADMIN.GOURL."products&s=active'>$active</a>"). " " . __('products', GOURL) . $this->space(2);
		echo "<b>" . __('Inactive', GOURL). ":</b> " . ($search?$inactive:"<a href='".GOURL_ADMIN.GOURL."products&s=inactive'>$inactive</a>") . " " . __('products', GOURL) . $this->space(1) . ")" . $this->space(4);
		echo "<b>" . __('Total Sold', GOURL). ":</b> " . ($search?$sold:"<a href='".GOURL_ADMIN.GOURL."products&s=sold'>$sold</a>") . " " . __('products', GOURL);
		if ($search) echo "<br><a href='".GOURL_ADMIN.GOURL."products'>" . __('Reset Search Filters', GOURL). "</a>";
		echo "</div>";
		echo "</div>";

		echo '<div class="'.GOURL.'widetable">';
		echo '<div class="'.GOURL.'producttable" style="min-width:1550px;width:100%;">';

		$wp_list_table->display();

		echo  '</div>';
		echo  '</div>';
		echo  '</div>';
		echo  '<br><br>';

		return true;

	}



	/*
	 *  59.
	*/
	public function shortcode_product($arr, $preview_final = false)
	{
		global $wpdb, $current_user;

		// not available activated coins
		if (!$this->payments) { $html = $this->display_error_nokeys(); return $html; }

		if (!isset($arr["id"]) || !intval($arr["id"])) return '<div>'.sprintf(__('Invalid format. Use %s', GOURL), '&#160; ['.GOURL_TAG_PRODUCT.' id="..id.."]').'</div>';

		$id 			= intval($arr["id"]);
		$short_code 	= '['.GOURL_TAG_PRODUCT.' id="<b>'.$id.'</b>"]';


		$is_paid		= false;
		$coins_list 	= "";
		$languages_list	= "";


		// Current File Info
		// --------------------------
		$arr = $wpdb->get_row("SELECT * FROM crypto_products WHERE productID = ".intval($id)." LIMIT 1", ARRAY_A);
		if (!$arr) return '<div>'.sprintf(__("Invalid product id '%s' -", GOURL), $id)." ".$short_code.'</div>';


		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->payment_box_style();
		$message_style	= $this->payment_message_style();

		$active 		= $arr["active"];
		$productTitle 	= $arr["productTitle"];
		$productText 	= $arr["productText"];
		$finalText 		= $arr["finalText"];

		$priceUSD 		= $arr["priceUSD"];
		$priceCoin 		= $arr["priceCoin"];
		$priceLabel 	= $arr["priceLabel"];
		if ($priceUSD > 0 && $priceCoin > 0) $priceCoin = 0;
		if ($priceCoin > 0) { $arr["defCoin"] = $priceLabel; $arr["defShow"] = 1; }

		$purchases 		= $arr["purchases"];
		$expiryPeriod	= $arr["expiryPeriod"];
		$lang 			= $arr["lang"];
		$defCoin		= $this->coin_names[$arr["defCoin"]];
		$defShow		= $arr["defShow"];

		$paymentCnt		= $arr["paymentCnt"];
		$paymentTime	= $arr["paymentTime"];
		$updatetime		= $arr["updatetime"];
		$createtime		= $arr["createtime"];
		$userID 		= "user_".$current_user->ID;
		$orderID 		= "product_".$id; // product_+productID as orderID
		$anchor 		= "gbx".$this->icrc32($id);

		if (strip_tags(mb_strlen($productText)) < 5) $productText = '';
		if (strip_tags(mb_strlen($finalText)) < 5) 	 $finalText = $productText;



		// Registered Users can Pay Only
		// --------------------------

		if (!is_user_logged_in() || !$current_user->ID)
		{
			$box_html = "<div align='center'>";
			if ($this->options['login_type'] != "1") $box_html .= $this->login_form();
			else $box_html .= "<br><a href='".wp_login_url(get_permalink())."'><img title='".__('You need first to login or register on the website to make Bitcoin/Altcoin Payments', GOURL)."' alt='".__('You need first to login or register on the website to make Bitcoin/Altcoin Payments', GOURL)."' src='".$this->box_image("plogin")."' border='0'></a>";
			$box_html .= "</div><br><br>";
		}
		else
		{

			// GoUrl Payments
			// --------------------------

			$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
			$available_coins 		= array(); 		// List of coins that you accept for payments
			$cryptobox_private_keys = array();		// List Of your private keys

			foreach ($this->coin_names as $k => $v)
			{
				$public_key 	= $this->options[$v.'public_key'];
				$private_key 	= $this->options[$v.'private_key'];

				if ($public_key && !strpos($public_key, "PUB"))    return '<div>'.sprintf(__('Invalid %s Public Key %s -', GOURL), $v, $public_key).$short_code.'</div>';
				if ($private_key && !strpos($private_key, "PRV"))  return '<div>'.sprintf(__('Invalid %s Private Key -', GOURL), $v).$short_code.'</div>';

				if ($private_key) $cryptobox_private_keys[] = $private_key;
				if ($private_key && $public_key && (!$defShow || $v == $defCoin))
				{
					$all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
					$available_coins[] = $v;
				}
			}

			if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));

			if (!$available_coins) { $html = '<div>'.$this->display_error_nokeys().' '.$short_code.'</div>'; return $html; }

			if (!in_array($defCoin, $available_coins)) { $vals = array_values($available_coins); $defCoin = array_shift($vals); }




			/// GoUrl Payment Class
			// --------------------------
			include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");



			// Current selected coin by user
			$coinName = cryptobox_selcoin($available_coins, $defCoin);


			// Current Coin public/private keys
			$public_key  = $all_keys[$coinName]["public_key"];
			$private_key = $all_keys[$coinName]["private_key"];


			// PAYMENT BOX CONFIG
			$options = array(
					"public_key"  => $public_key, 		// your box public key
					"private_key" => $private_key, 		// your box private key
					"orderID"     => $orderID, 			// file name hash as order id
					"userID"      => $userID, 			// unique identifier for each your user
					"userFormat"  => "MANUAL", 			// registered users only
					"amount"   	  => $priceCoin,		// product price in coin
					"amountUSD"   => $priceUSD,			// product price in USD
					"period"      => $expiryPeriod, 	// payment valid period
					"language"	  => $lang  			// text on EN - english, FR - french, etc
			);



			// Initialise Payment Class
			$box = new Cryptobox ($options);


			// Coin name
			$coinName = $box->coin_name();


			// Paid or not
			$is_paid = $box->is_paid();



			// Payment Box HTML
			// ----------------------
			if (!$is_paid && $purchases > 0 && $paymentCnt >= $purchases)
			{
				// A. Sold
				$box_html = "<img alt='".__('Sold Out', GOURL)."' src='".$this->box_image("sold")."' border='0'><br><br>";

			}
			elseif (!$is_paid && !$active)
			{
				// B. Box Not Active
				$box_html = "<img alt='".__('Cryptcoin Payment Box Disabled', GOURL)."' src='".$this->box_image("pdisable")."' border='0'><br><br>";
			}
			elseif (!$is_paid && $preview_final)
			{
				// C. Preview Final Screen
				$box_html = "<img width='580' height='240' alt='".__('Cryptcoin Payment Box Preview', GOURL)."' src='".plugins_url('/images', __FILE__)."/cryptobox_completed.png' border='0'><br><br>";
			}
			else
			{
        		// Payment Box HTML
        		// ----------------------

        		if ($this->options["box_type"] == 2)
        		{
                    // Active Payment Box - iFrame

                    // Coins selection list (html code)
                    $coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $defCoin, $lang, 60, "margin:60px 0 30px 0;text-align:center;font-weight:normal;", plugins_url('/images', __FILE__), $anchor) : "";

                    // Language selection list for payment box (html code)
                    $languages_list = display_language_box($lang, $anchor);

                    // Active Box
                    $box_html  = $this->iframe_scripts();
                    $box_html .= $box->display_cryptobox (true, $box_width, $box_height, $box_style, $message_style, $anchor);

        		}
        		else
        		{
                    // Active Payment Box - jQuery

                    $box_html  = $this->bootstrap_scripts();
                    $box_html .= $box->display_cryptobox_bootstrap ($available_coins, $defCoin, $lang, "", 70, 180, true, $this->box_logo(), "default", 250, "", "curl");

                    // Re-test after receive json data from live server
                    $is_paid = $box->is_paid();
        		}

			}

		}


		// Tags
		// ---------------------
		$adminIntro 	= "";
		if ($is_paid || (!$is_paid && $preview_final))
		{
			$productText = $finalText;

			if (mb_strpos($productText, "{") !== false)
			{
				if (!$is_paid && $preview_final)
				{
					$adminIntro = "<p>".__('Used template tags for preview:', GOURL)."<br><i><b>{user_fullname}</b> - John Smith, <b>{user_username}</b> - john2, <b>{user_id}</b> - 7, <b>{user_email}</b> - john@example.com, <b>{paid_amount}</b> - 0.335301 BTC, <b>{paid_amount_usd}</b> - ~112.3 USD, <b>{payment_id}</b> - 11, <b>{transaction_id}</b> - 2bed6fb8bb35d42842519d445b099fdee6da5d65280167333342d879b4ab93a1, <b>{transaction_time}</b> - 18 Dec 2014, 11:15:48 am</i></p><br><br>";
					$txt_to 	= array("John Smith", "john2", 7, "john@example.com", "0.335301 BTC", "~112.3 USD", 11, "2bed6fb8bb35d42842519d445b099fdee6da5d65280167333342d879b4ab93a1", "18 Dec 2014, 11:15:48 am");
				}
				else
				{
					$user_fullname 		= trim($current_user->user_firstname . " " . $current_user->user_lastname);
					$user_username 		= $current_user->user_login;
					$user_email 		= $current_user->user_email;
					$user_id			= $current_user->ID;
					if (!$user_fullname) $user_fullname =  $user_username;

					$details			= $box->payment_info();
					$paid_amount		= gourl_number_format($details->amount, 8) . " " . $details->coinLabel;
					$paid_amount_usd	= gourl_number_format($details->amountUSD, 2) . " USD";
					$payment_id			= $details->paymentID;
					$transaction_id		= $details->txID;
					$transaction_time	= date("d M Y, H:i:s a", strtotime($details->txDate));

					$txt_to 			= array($user_fullname, $user_username, $user_id, $user_email, $paid_amount, $paid_amount_usd, $payment_id, $transaction_id, $transaction_time);
				}

				$txt_from 			= array("{user_fullname}", "{user_username}", "{user_id}", "{user_email}", "{paid_amount}", "{paid_amount_usd}", "{payment_id}", "{transaction_id}", "{transaction_time}");
				$productText 		= str_replace($txt_from, $txt_to, $productText);
			}
		}


		// Html code
		// ---------------------

		$tmp  = "<div class='gourlbox'".($languages_list?" style='min-width:".$box_width."px'":"").">";
		if ($adminIntro) 	$tmp .= $adminIntro;
		if ($productTitle) 	$tmp .= "<h1>".htmlspecialchars($productTitle, ENT_QUOTES)."</h1>";
		if ($productText) 	$tmp .= "<div class='gourlproducttext'>".$productText."</div><br>";

		if (!$is_paid) $tmp .= "<a id='".$anchor."' name='".$anchor."'></a>";

		if ($is_paid) 			$tmp .= "<br><br>";
		elseif (!$coins_list) 	$tmp .= "<br>";
		else 					$tmp .= $coins_list;

		// Cryptocoin Payment Box
		if ($languages_list) $tmp .= "<div style='margin:20px 0 5px 290px;font-family:\"Open Sans\",sans-serif;font-size:13px;color:#666;font-weight:normal;white-space:nowrap;'>".__('Language', GOURL).": ".$this->space(2).$languages_list."</div>";
		$tmp .= $box_html;

		// End
		$tmp .= "</div>";

		return $tmp;
	}










	/**************** F. ALL PAYMENTS ************************************/


	/*
	 *  60.
	*/
	public function page_payments()
	{
		global $wpdb;

		if ($this->is_nonadmin_user()) return true;

		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");

		$search = $sql_where = "";

		if (isset($_GET["s"]) && trim($_GET["s"]))
		{
			$s = esc_sql(trim(mb_strtolower(mb_substr($_GET["s"], 0, 50))));

			foreach ($this->addon as $v)
			{
				if 	($s == $v) $search = " && orderID like '".esc_sql($v).".%'";
				$sql_where .= " && orderID not like '".esc_sql($v).".%'";
			}
			if (!$search)
			{
				if 	   ($s == "recognised") 	$search = " && unrecognised = 0";
				elseif ($s == "unrecognised") 	$search = " && unrecognised != 0";
				elseif ($s == "products") 		$search = " && orderID LIKE 'product\_%'";
				elseif ($s == "files") 			$search = " && orderID LIKE 'file\_%'";
				elseif ($s == "membership") 	$search = " && orderID LIKE 'membership%'";
				elseif ($s == "payperview") 	$search = " && orderID = 'payperview'";
				elseif ($s == "guest" || $s == "guests") $search = " && userID NOT LIKE 'user%'";
				elseif ($s == "plugins") 		$search = " && orderID LIKE '%.%'".$sql_where;
				elseif (strpos($s, "user_") === 0 && is_numeric(substr($s, 5))) $search = " && (userID = 'user".intval(substr($s, 5))."' || userID = 'user_".intval(substr($s, 5))."')";
				elseif (strpos($s, "user") === 0 && is_numeric(substr($s, 4)))  $search = " && (userID = 'user".intval(substr($s, 4))."' || userID = 'user_".intval(substr($s, 4))."')";
				elseif (strpos($s, "file_") === 0 && is_numeric(substr($s, 5))) $search = " && orderID = 'file_".intval(substr($s, 5))."'";
				elseif (strpos($s, "payment_") === 0 && is_numeric(substr($s, 8))) $search = " && paymentID = ".intval(substr($s, 8));
				elseif (strpos($s, "order ") === 0 && is_numeric(substr($s, 6))) $search = " && orderID like '%".esc_sql(str_replace("order ", "", $s))."%'";
				elseif (in_array(strtolower($s), $this->coin_names)) $search = " && coinLabel = '".array_search(strtolower($s), $this->coin_names)."'";
				elseif (isset($this->coin_names[strtoupper($s)])) $search = " && coinLabel = '".strtoupper($s)."'";
			}

			$s = esc_sql(trim(mb_substr($_GET["s"], 0, 50)));

			if (!$search)
			{
				$key = get_country_name($s, true);
				if ($key) $s = esc_sql($key);
				if (substr(strtoupper($s), -4) == " USD") $s = substr($s, 0, -4);
				elseif (strtolower($s) == "wp ecommerce") $s = "wpecommerce";

				$ids = "";
				$result = $wpdb->get_results("SELECT ID FROM $wpdb->users WHERE user_login LIKE '%".$s."%' || user_nicename LIKE '%".$s."%' || user_email LIKE '%".$s."%' || display_name LIKE '%".$s."%' LIMIT 200");
				foreach ( $result as $obj ) $ids .= ", 'user_" . intval($obj->ID) . "', 'user" . intval($obj->ID) . "'";
				$ids = trim($ids, ", ");
				if ($ids) $ids = " || userID IN (".$ids.")";
				$search = " && (orderID LIKE '%".$s."%' || userID LIKE '%".$s."%' || countryID LIKE '%".$s."%' || coinLabel LIKE '%".$s."%' || amount LIKE '%".$s."%' || amountUSD LIKE '%".$s."%' || addr LIKE '%".$s."%' || txID LIKE '%".$s."%' || DATE_FORMAT(txDate, '%d %M %Y') LIKE '%".$s."%'".$ids.")";
			}
		}

		$res = $wpdb->get_row("SELECT sum(amountUSD) as total from crypto_payments WHERE 1".$search, OBJECT);
		$total = $res->total;
		$total = number_format($total, 2);
		if (strpos($total, ".")) $num = rtrim(rtrim($total, "0"), ".");

		$res = $wpdb->get_row("SELECT DATE_FORMAT(txDate, '%d %M %Y, %H:%i %p') as latest from crypto_payments WHERE 1".$search." ORDER BY txDate DESC LIMIT 1", OBJECT);
		$latest = ($res) ? $res->latest . " " . __('GMT', GOURL) : "";


		$res = $wpdb->get_row("SELECT count(paymentID) as cnt from crypto_payments WHERE unrecognised = 0".$search, OBJECT);
		$recognised = (int)$res->cnt;

		$res = $wpdb->get_row("SELECT count(paymentID) as cnt from crypto_payments WHERE unrecognised != 0".$search, OBJECT);
		$unrecognised = (int)$res->cnt;


		echo "<div class='wrap ".GOURL."admin'>";
		echo $this->page_title(__('All Received Payments', GOURL));

		if (!(isset($_GET["b"]) && is_numeric($_GET["b"])))
		{
		  echo "<div class='".GOURL."intro postbox'>";
		  echo sprintf( __("Notes: Please wait bitcoin/altcoin transaction confirmations (column 'Confirmed Payment?' in table below) before sending any purchased products / services to users. A transaction confirmation is needed to prevent <a target='_blank' href='%s'>double spending of the same money</a> because somebody may from time to time try to do so.", GOURL), "https://en.bitcoin.it/wiki/Double-spending");
		  echo "</div>";
		}


		if (isset($_GET["b"]) && is_numeric($_GET["b"]))
		{
			$c = $this->check_payment_confirmation(intval($_GET["b"]));

			echo  "<div class='".($c?"updated":"error")." postbox'>";
			if ($c) echo  "<span style='color:green'>".sprintf(__('GoUrl.io Live Status : Payment id <b>%s</b> transaction - <b>CONFIRMED</b>', GOURL), '#'.intval($_GET["b"]))."</span>";
			else echo  "<span style='color:red'>".sprintf(__('GoUrl.io Live Status : Payment id <b>%s</b> transaction - <b>NOT confirmed yet</b>', GOURL), '#'.intval($_GET["b"]))."</span>";
			echo "</div>";
		}


		if (isset($_GET["d"]) && $_GET["d"] == "deltest")
		{
			payment_ipntest_delete();

			echo  "<div class='updated postbox'>";
			echo  "<span style='color:green'>".sprintf(__('Demo Test Payments Deleted from database! Test IPN Url <a target="_blank" href="%s">here &#187;</a>', GOURL), "https://gourl.io/info/ipn/IPN_Website_Testing.html")."</span>";
			echo "</div>";
		}

		$wp_list_table = new  gourl_table_payments($search, $this->options['rec_per_page'], $this->options['file_columns']);
		$wp_list_table->prepare_items();

		echo '<form class="gourlsearch" method="get" accept-charset="utf-8" action="">';
		echo '<input type="hidden" name="page" value="'.$this->page.'" />';
		$wp_list_table->search_box( 'search', 'search_id' );
		echo '</form>';


		if ((isset($_GET["s"]) && trim($_GET["s"])) || isset($_GET["b"]) || isset($_GET["d"]) || isset($_GET["orderby"]) || isset($_GET["order"])) echo "<span>&#160;<a href='".GOURL_ADMIN.GOURL."payments' class='".GOURL."button button-secondary'>".__('Reset', GOURL)."</a></span>";

		if (payment_ipntest()) echo "<span> &#160; &#160; &#160; &#160; &#160; <a href='".GOURL_ADMIN.GOURL."payments&d=deltest' class='".GOURL."button button-secondary'>".__('Delete Test payments sent from GoUrl IPN TEST webpage', GOURL)."</a></span>";

		echo "<br><br><div class='".GOURL."tablestats'>";
		echo "<div>";
		echo "<span><b>" . ($search?__('Found', GOURL):__('Total Received', GOURL)). ":</b> " . number_format($recognised+$unrecognised) . " " . __('payments', GOURL) . $this->space(1) . "</span> <span><small>( ";
		echo "<b>" . __('Recognised', GOURL). ":</b> " . ($search?number_format($recognised):"<a href='".GOURL_ADMIN.GOURL."payments&s=recognised'>".number_format($recognised)."</a>") . " " . __('payments', GOURL) . $this->space(1);
		echo "<b>" . __('Unrecognised', GOURL). ":</b> " . ($search?number_format($unrecognised):"<a href='".GOURL_ADMIN.GOURL."payments&s=unrecognised'>".number_format($unrecognised)."</a>") . " " . __('payments', GOURL) . " )</small></span>" . $this->space(4);
		echo "<span><b>" . __('Total Sum', GOURL). ":</b> " . $total . " " . __('USD', GOURL) . "</span>" . $this->space(4);
		echo "<span><b>" . __('Latest Payment', GOURL). ":</b> " . $latest . "</span>";
		if ($search) echo "<br><a href='".GOURL_ADMIN.GOURL."payments'>" . __('Reset Search Filters', GOURL). "</a>";
		echo "</div>";
		echo "</div>";


		echo '<div class="'.GOURL.'widetable">';


		echo '<div style="min-width:1640px;width:100%;"'.(!$this->options['file_columns']?' class="'.GOURL.'nofilecolumn"':'').'>';

		$wp_list_table->display();

		echo  '</div>';
		echo  '</div>';
		echo  '</div>';
		echo  '<br><br>';

		return true;
	}



	/*
	 *  61.
	*/
	private function check_payment_confirmation($paymentID)
	{
		global $wpdb;

		$res = $wpdb->get_row("SELECT * from crypto_payments WHERE paymentID = ".intval($paymentID), OBJECT);

		if (!$res) return false;
		if ($res->txConfirmed) return true;

		$public_key 	= $this->options[$this->coin_names[$res->coinLabel].'public_key'];
		$private_key 	= $this->options[$this->coin_names[$res->coinLabel].'private_key'];

		if (!$public_key || !$private_key) return false;

		if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", $private_key);

		$options = array(
				"public_key"  => $public_key,
				"private_key" => $private_key,
				"orderID"     => $res->orderID,
				"userID"      => $res->userID,
				"amount"   	  => $res->amount,
				"period"      => "NO EXPIRY"
				);

		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");

		$box = new Cryptobox ($options);

		$box->is_paid();

		return $box->is_confirmed();
	}








	/**************** G. FRONT ************************************/



	/*
	 *  62.
	*/
	public function  front_init()
	{
		ob_start();

		return true;
	}




	/*
	 *  63.
	*/
	public function front_html($text)
	{
		global $post;

		$m = $v = false;

		if (isset($post->post_content))
		{
			if (has_shortcode($post->post_content, GOURL_TAG_MEMBERSHIP)) 	$m = true;
			elseif (has_shortcode($post->post_content, GOURL_TAG_VIEW)) 	$v = true;
		}

		if ($m || $v)
		{
			$img 	 = array(GOURL_TAG_MEMBERSHIP => "",  GOURL_TAG_VIEW => "");
			$frame   = array(GOURL_TAG_MEMBERSHIP => "",  GOURL_TAG_VIEW => "");
			$iwidth  = array(GOURL_TAG_MEMBERSHIP => "",  GOURL_TAG_VIEW => "");
			$iheight = array(GOURL_TAG_MEMBERSHIP => "",  GOURL_TAG_VIEW => "");

			preg_match_all( '/' . get_shortcode_regex() . '/s', $post->post_content, $matches, PREG_SET_ORDER );
			foreach ($matches as $v)
				if (GOURL_TAG_MEMBERSHIP === $v[2] || GOURL_TAG_VIEW === $v[2])
				{
					preg_match('/(img(\s*)=(\s*)["\'](.*?)["\'])/', $v[3], $match);
					if (isset($match["4"])) $img[$v[2]] = trim($match["4"]);

					preg_match('/(frame(\s*)=(\s*)["\'](.*?)["\'])/', $v[3], $match);
					if (isset($match["4"])) $frame[$v[2]] = trim($match["4"]);

					preg_match('/(w(\s*)=(\s*)["\'](.*?)["\'])/', $v[3], $match);
					if (isset($match["4"])) $iwidth[$v[2]] = trim($match["4"]);

					preg_match('/(h(\s*)=(\s*)["\'](.*?)["\'])/', $v[3], $match);
					if (isset($match["4"])) $iheight[$v[2]] = trim($match["4"]);
				}

				if ($m)
				{
					$this->lock_type = GOURL_TAG_MEMBERSHIP;
					$this->shortcode_membership_init($img[GOURL_TAG_MEMBERSHIP], $frame[GOURL_TAG_MEMBERSHIP], $iwidth[GOURL_TAG_MEMBERSHIP], $iheight[GOURL_TAG_MEMBERSHIP]);
				}
				elseif ($v)
				{
					$this->lock_type = GOURL_TAG_VIEW;
					$this->shortcode_view_init($img[GOURL_TAG_VIEW], $frame[GOURL_TAG_VIEW], $iwidth[GOURL_TAG_VIEW], $iheight[GOURL_TAG_VIEW]);
				}
		}

		return $text;
	}









	/*
	 * 64.
	*/
	private function login_form()
	{
		global $user;

		$err = "";
		$tmp = '<a id="info" name="info"></a>';

		if (isset($_POST[GOURL.'login_submit']))
		{
			$creds = array();
			$creds['user_login'] = $_POST['login_name'];
			$creds['user_password'] =  $_POST['login_password'];
			if (!$creds['user_login'] && !$creds['user_password']) $creds['user_login'] = "no";
			$creds['remember'] = true;
			$user = wp_signon( $creds, false );
			if ( is_wp_error($user) ) {
				$err = $user->get_error_message();
			}
			if ( !is_wp_error($user) ) {
				wp_redirect(site_url($_SERVER['REQUEST_URI']));
			}
		}

		$tmp .=
			'<div id="gourllogin">
				<div class="login">
					<div class="app-title"><h3>'.__('Login', GOURL).'</h3>'.$err.'</div>
					<form method="post" action="'.$_SERVER['REQUEST_URI'].'#info">
						<div class="login-form">
							<div class="control-group" align="center">
								<input type="text" class="login-field" value="" placeholder="'.__('username', GOURL).'" name="login_name" id="login_name">
								<label class="login-field-icon fui-user" for="login_name"></label>
							</div>
							<div class="control-group" align="center">
								<input type="password" class="login-field" value="" placeholder="'.__('password', GOURL).'" name="login_password" id="login_password">
								<label class="login-field-icon fui-lock" for="login_password"></label>
							</div>
							<input class="btn btn-primary btn-large btn-block" type="submit" name="'.GOURL.'login_submit" value="'.__('Log in').'" />
								<a class="login-link" href="'.wp_lostpassword_url(site_url($_SERVER['REQUEST_URI'])).'">'.__( 'Lost your password?' ).'</a>
								'.wp_register('<div class="reg-link">'.__('Free', GOURL).' ', '</div>', false).'
						</div>
					</form>
				</div>
			</div>';

		return $tmp;
	}







	/**************** I. ADMIN ************************************/




	/*
	 *  65.
	*/
	public function admin_init()
	{
		global $wpdb;

		ob_start();

		// Actions POST

		if (isset($_POST[$this->adminform]) && strpos($this->page, GOURL) === 0)
		{
		     check_admin_referer( $this->admin_form_key );

			switch($_POST[$this->adminform])
			{
				case GOURL.'save_settings':

					$this->post_settings();
					$this->check_settings();

					if (!$this->errors)
					{
						$this->save_settings();

						if (!$this->errors)
						{
							header('Location: '.GOURL_ADMIN.GOURL.'settings&updated=true');
							die();
						}
					}

					break;

				case GOURL.'save_download':

					$this->post_record();
					$this->check_download();

					if (!$this->record_errors)
					{
						$this->save_download();

						if (!$this->record_errors)
						{
							header('Location: '.GOURL_ADMIN.GOURL.'file&id='.$this->id.'&updated=true');
							die();
						}
					}

					break;

				case GOURL.'save_product':

					$this->post_record();
					$this->check_product();

					if (!$this->record_errors)
					{
						$this->save_product();

						if (!$this->record_errors)
						{
							header('Location: '.GOURL_ADMIN.GOURL.'product&id='.$this->id.'&updated=true');
							die();
						}
					}

					break;

				case GOURL.'save_view':

					$this->post_view();
					$this->check_view();

					if (!$this->record_errors)
					{
						$this->save_view();

						if (!$this->record_errors)
						{
							header('Location: '.GOURL_ADMIN.GOURL.'payperview&updated=true');
							die();
						}
					}

					break;

				case GOURL.'save_membership':

					$this->post_membership();
					$this->check_membership();

					if (!$this->record_errors)
					{
						$this->save_membership();

						if (!$this->record_errors)
						{
							header('Location: '.GOURL_ADMIN.GOURL.'paypermembership&updated=true');
							die();
						}
					}

					break;

				case GOURL.'save_membership_newuser':

					$this->post_record();
					$this->check_membership_newuser();

					if (!$this->record_errors)
					{
						$this->save_membership_newuser();

						if (!$this->record_errors)
						{
							header('Location: '.GOURL_ADMIN.GOURL.'paypermembership_users&updated=true');
							die();
						}
					}



				default:

					break;
			}
		}


		// Actions GET

		if (!isset($_POST[$this->adminform]) && strpos($this->page, GOURL) === 0 && is_admin() && is_user_logged_in() && current_user_can('administrator'))
		{

			switch($this->page)
			{
				case GOURL.'premiumuser_delete':

					if ($this->id) $wpdb->query("delete from crypto_membership where membID = ".intval($this->id)." && paymentID = 0 limit 1");

					header('Location: '.GOURL_ADMIN.GOURL.'paypermembership_users&updated=true');
					die();

					break;


				case GOURL.'premiumuser_enable':

					if ($this->id) $wpdb->query("update crypto_membership set disabled = 0 where membID = ".intval($this->id)." limit 1");

					header('Location: '.GOURL_ADMIN.GOURL.'paypermembership_users&updated=true');
					die();

					break;


				case GOURL.'premiumuser_disable':

					if ($this->id) $wpdb->query("update crypto_membership set disabled = 1 where membID = ".intval($this->id)." limit 1");

					header('Location: '.GOURL_ADMIN.GOURL.'paypermembership_users&updated=true');
					die();

					break;
			}
		}


		return true;
	}







	/*
	 *  66.
	*/
	public function admin_header()
	{
		global $current_user;

		// File Preview Downloads

		// Wordpress roles - array('administrator', 'editor', 'author', 'contributor', 'subscriber')
		$_administrator = $_editor = false;
		if (is_user_logged_in())
		{
			$_administrator = in_array('administrator', $current_user->roles);
			$_editor 	    = in_array('editor', 	  $current_user->roles);
		}

		if (isset($_GET[GOURL_PREVIEW]) && $_GET[GOURL_PREVIEW] && !$_POST && is_admin() && $_administrator && current_user_can('administrator')) //($_administrator || $_editor))
		{

			$filePath = GOURL_DIR."files/".mb_substr(preg_replace('/[\(\)\?\!\;\,\>\<\'\"\/\%]/', '', str_replace("..", "", $_GET[GOURL_PREVIEW])), 0, 100);


			if (file_exists($filePath) && is_file($filePath) && trim(dirname($filePath),"/") == trim(GOURL_DIR."files","/"))
			{
				// Starting Download
				$this->download_file($filePath);

				// Flush Cache
				if (ob_get_level()) ob_flush();

				die;
			}
		}

		return true;
	}




	/*
	 *  66b.
	 */
	public function admin_plugin_meta( $links, $file ) {

	    if ( strpos( $file, 'gourl_wordpress.php' ) !== false ) {

	        // Set link for Reviews.
	        $new_links = array('<a style="color:#0073aa" href="https://wordpress.org/support/plugin/gourl-bitcoin-payment-gateway-paid-downloads-membership/reviews/?filter=5" target="_blank"><span class="dashicons dashicons-thumbs-up"></span> ' . __( 'Vote!', GOURL ) . '</a>',
	        );

	        $links = array_merge( $links, $new_links );
	    }

	    return $links;
	}




	/*
	 *  67.
	*/
	public function admin_footer_text()
	{
		return sprintf( __( "If you like <strong>GoUrl Bitcoin/Altcoins Gateway</strong> please leave us a %s rating on %s. A huge thank you from GoUrl  in advance!", GOURL ), "<a href='https://wordpress.org/support/view/plugin-reviews/gourl-bitcoin-payment-gateway-paid-downloads-membership?filter=5#postform' target='_blank'>&#9733;&#9733;&#9733;&#9733;&#9733;</a>", "<a href='https://wordpress.org/support/view/plugin-reviews/gourl-bitcoin-payment-gateway-paid-downloads-membership?filter=5#postform' target='_blank'>WordPress.org</a>");
	}




	/*
	 *  68.
	*/
	public function admin_warning()
	{
		echo '<div class="updated"><p>'.sprintf(__("<strong>%s Plugin is almost ready to use!</strong> All you need to do is to <a style='text-decoration:underline' href='%s'>update your plugin settings</a>", GOURL), __('Official GoUrl Bitcoin Payment Gateway for Wordpress', GOURL), GOURL_ADMIN.GOURL."settings").'</p></div>';

		return true;
	}



	/*
	 *  69.
	*/
	public function admin_warning_reactivate()
	{
		echo '<div class="error"><p>'.sprintf(__("<strong>Please deactivate %s Plugin,<br>manually set folder %s permission to 0777 and activate it again.</strong><br><br>if you have already done so before, please create three folders below manually and set folder permissions to 0777:<br>- %s<br>- %s<br>- %s", GOURL), __('Official GoUrl Bitcoin Payment Gateway for Wordpress', GOURL), GOURL_DIR2, GOURL_DIR2."files/", GOURL_DIR2."images/", GOURL_DIR2."lockimg/").'</p></div>';

		return true;
	}




	/*
	 *  70.
	*/
	public function admin_menu()
	{
		global $submenu;

		add_menu_page(
				__("GoUrl Bitcoin", GOURL)
				, __('GoUrl Bitcoin', GOURL)
				, GOURL_PERMISSION
				, GOURL
				, array(&$this, 'page_summary'),
				plugins_url('/images/btc_icon.png', __FILE__),
				'21.777'
		);

		add_submenu_page(
		GOURL
				, __('&#149; Summary', GOURL)
				, __('&#149; Summary', GOURL)
				, GOURL_PERMISSION
				, GOURL
				, array(&$this, 'page_summary')
		);

		add_submenu_page(
		GOURL
				, __('&#149; All Payments', GOURL)
				, __('&#149; All Payments', GOURL)
				, GOURL_PERMISSION
				, GOURL."payments"
				, array(&$this, 'page_payments')
		);

		add_submenu_page(
		GOURL
				, __('&#149; Pay-Per-Product', GOURL)
				, __('&#149; Pay-Per-Product', GOURL)
				, GOURL_PERMISSION
				, GOURL."products"
				, array(&$this, 'page_products')
		);


		add_submenu_page(
		GOURL
				, $this->space(2).__('Add New Product', GOURL)
				, $this->space(2).__('Add New Product', GOURL)
				, GOURL_PERMISSION
				, GOURL."product"
				, array(&$this, 'page_newproduct')
		);


		add_submenu_page(
		GOURL
				, __('&#149; Pay-Per-Download', GOURL)
				, __('&#149; Pay-Per-Download', GOURL)
				, GOURL_PERMISSION
				, GOURL."files"
				, array(&$this, 'page_files')
		);

		add_submenu_page(
		GOURL
				, $this->space(2).__('Add New File', GOURL)
				, $this->space(2).__('Add New File', GOURL)
				, GOURL_PERMISSION
				, GOURL."file"
				, array(&$this, 'page_newfile')
		);


		add_submenu_page(
		GOURL
				, __('&#149; Pay-Per-View', GOURL)
				, __('&#149; Pay-Per-View', GOURL)
				, GOURL_PERMISSION
				, GOURL."payperview"
				, array(&$this, 'page_view')
		);


		add_submenu_page(
		GOURL
				, __('&#149; Pay-Per-Membership', GOURL)
				, '<span class="gourlnowrap">'.__('&#149; Pay-Per-Membership', GOURL).'</span>'
				, GOURL_PERMISSION
				, GOURL."paypermembership"
				, array(&$this, 'page_membership')
		);


		add_submenu_page(
		GOURL
				, $this->space(2).__('Premium Users', GOURL)
				, $this->space(2).__('Premium Users', GOURL)
				, GOURL_PERMISSION
				, GOURL."paypermembership_users"
				, array(&$this, 'page_membership_users')
		);

		add_submenu_page(
		GOURL
				, $this->space(2).__('________________', GOURL)
				, $this->space(2).__('________________', GOURL)
				, GOURL_PERMISSION
				, GOURL."paypermembership_user"
				, array(&$this, 'page_membership_user')
		);

		add_submenu_page(
		GOURL
				, __('Settings', GOURL)
				, __('Settings', GOURL)
				, GOURL_PERMISSION
				, GOURL."settings"
				, array(&$this, 'page_settings')
		);

		add_submenu_page(
		GOURL
				, __('Add-ons', GOURL)
				, __('Add-ons', GOURL)
				, GOURL_PERMISSION
				, GOURL."addons"
				, array(&$this, 'page_summary')
		);

		add_submenu_page(
		GOURL
				, __('Contacts', GOURL)
				, __('Contacts', GOURL)
				, GOURL_PERMISSION
				, GOURL."contact"
				, array(&$this, 'page_summary')
		);

		return true;
	}






	/**************** K. ADD-ON ************************************/





	/*
	 *  71.
	*/
	private function page_title($title, $type = 1) // 1 - Plugin Name, 2 - Pay-Per-Download,  3 - Pay-Per-View ,  4 - Pay-Per-Membership, 5 - Pay-Per-Product, 20 - Custom
	{
		if ($type == 2) 		$text = __("GoUrl Pay-Per-Download (Paid File Downloads)", GOURL);
		elseif ($type == 3) 	$text = __("GoUrl Pay-Per-View (Anonymous Access to Premium Pages/Video)", GOURL);
		elseif ($type == 4) 	$text = __("GoUrl Premium Pay-Per-Membership", GOURL);
		elseif ($type == 5) 	$text = __("GoUrl Pay-Per-Product (selling online)", GOURL);
		else 					$text = __('Official GoUrl Bitcoin Payment Gateway for Wordpress', GOURL);

		$tmp = "<div class='".GOURL."logo'><a href='https://gourl.io/' target='_blank'><img title='".__('CRYPTO-CURRENCY PAYMENT GATEWAY', GOURL)."' src='".plugins_url('/images/gourl.png', __FILE__)."' border='0'></a></div>";
		if ($title) $tmp .= "<div id='icon-options-general' class='icon32'><br></div><h2>".__(($text?$text.' - ':'').$title, GOURL)."</h2><br>";

		return $tmp;
	}



	/*
	 *  72.
	*/
	private function upload_file($file, $dir, $english = true)
	{
		$fileName 	= mb_strtolower($file["name"]);
		$ext 		= $this->right($fileName, ".", false);
		$fileName 	= $this->left($fileName, ".", false);

		if ($fileName == $ext) $ext = "";
		$ext = trim($ext);
		if (mb_strpos($ext, " ")!==false)         $ext = str_replace(" ", "_", $ext);
		if (mb_strpos($fileName, ".")!==false)    $fileName = str_replace(".", "_", $fileName);

		if (!(is_admin() && is_user_logged_in() && current_user_can('administrator')))
		{
		    	$this->record_errors[] = sprintf(__("Cannot upload file '%s' on server. Please login as ADMIN user!", GOURL), $file["name"]);
			return "";
		}
		else
		{
		    if (!is_uploaded_file($file["tmp_name"])) $this->record_errors[] = sprintf(__("Cannot upload file '%s' on server. Alternatively, you can upload your file to '%s' using the FTP File Manager", GOURL), $file["name"], GOURL_DIR2.$dir);
		    elseif (in_array($dir, array("images", "box")) && !in_array($ext, array("jpg", "jpeg", "png", "gif"))) $this->record_errors[] = sprintf(__("Invalid image file '%s', supported *.gif, *.jpg, *.png files only", GOURL), $file["name"]);
		    elseif (in_array($dir, array("files")) && !in_array($ext, array("jpg","jpeg","png","gif","mp3","aac","ogg","avi","mov","mp4","mkv","txt","doc","pdf","iso","7z","rar","zip"))) $this->record_errors[] = sprintf(__("Invalid file '%s', supported *.jpg, *.png, *.gif, *.mp3, *.aac, *.ogg, *.avi, *.mov, *.mp4, *.mkv, *.txt, *.doc, *.pdf, *.iso, *.7z, *.rar, *.zip files only", GOURL), $file["name"]);
		    else
		    {
    			if ($english) $fileName = preg_replace('/[^A-Za-z0-9\-\_]/', ' ', $fileName); // allowed english symbols only
    			else $fileName = preg_replace('/[\(\)\?\!\;\,\.\>\<\'\"\/\%\#\&]/', ' ', $fileName);

    			$fileName = mb_strtolower(str_replace(" ", "_", preg_replace("{[ \t]+}", " ", trim($fileName))));
    			$fileName = mb_substr($fileName, 0, 90);
    			$fileName = trim($fileName, ".,!;_-");
    			if (mb_strlen($fileName) < 4) $fileName = date("Ymd")."_".strtotime("now");
    			if (in_array($dir, array("images", "box")) && is_numeric($fileName[0])) $fileName = "i".$fileName;

    			if (file_exists(GOURL_DIR.$dir."/".$fileName.".".$ext))
    			{
    			    $i = 1;
    			    while (file_exists(GOURL_DIR.$dir."/".$fileName."-".$i.".".$ext)) $i++;
    			    $fileName = $fileName."-".$i;
    			}
    			$fileName = $fileName.".".$ext;

    			if (!move_uploaded_file($file["tmp_name"], GOURL_DIR.$dir."/".$fileName)) $this->record_errors[] = sprintf(__("Cannot move file '%s' to directory '%s' on server. Please check directory permissions", GOURL), $file["name"], GOURL_DIR2.$dir);
    			elseif ($dir == "images")
    			{
    				$this->record_info[] = sprintf(__('Your Featured Image %s has been uploaded <strong>successfully</strong>', GOURL), ($file["name"] == $fileName ? '"'.$fileName.'"' : ''));

    				return $fileName;

    			}
    			else
    			{
    				$this->record_info[] = sprintf(__('Your File %s has been uploaded <strong>successfully</strong>', GOURL), ($file["name"] == $fileName ? '"'.$fileName.'"' : '')) . ($file["name"] != $fileName ? '. '.sprintf(__('New File Name is <strong>%s</strong>', GOURL), $fileName):'');

    				return $fileName;
    			}
		    }
		}

		return "";
	}




	/*
	 *  73.
	*/
	private function download_file($file)
	{
		// Erase/turn off output buffering
		if (ob_get_level()) ob_end_clean();

		// Starting Download
		$size = filesize($file);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($file));
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . $size);
		readfile($file);

		return true;
	}




	/*
	 *  74.
	*/
	public function callback_parse_request()
	{
		if (in_array(strtolower($this->right($_SERVER["REQUEST_URI"], "/", false)), array("?cryptobox.callback.php", "index.php?cryptobox.callback.php", "?cryptobox_callback_php", "index.php?cryptobox_callback_php", "?cryptobox-callback-php", "index.php?cryptobox-callback-php")))
		{
			ob_clean();

			$cryptobox_private_keys = array();
			foreach($this->coin_names as $k => $v)
			{
				$val = get_option(GOURL.$v."private_key");
				if ($val) $cryptobox_private_keys[] = $val;
			}

			if ($cryptobox_private_keys) DEFINE("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));

			include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");
			include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.callback.php");

			ob_flush();

			die;
		}

		return true;
	}








	/********************************************************************/




	/*
	 *  75. Bitcoin Payments with Any Other Wordpress Plugins
	*/
	public function cryptopayments ($pluginName, $amount, $amountLabel = "USD", $orderID = "", $period = "", $default_language = "en", $default_coin = "bitcoin", $affiliate_key = "", $userID = "auto", $icon_width = 60, $emultiplier = 1, $additional_options = array())
	{

		// Security Test
		// ---------------------

		if (!$pluginName) 																												return array("error" => __("Error. Please place in variable \$YourPluginName - your plugin name", GOURL));
		if (preg_replace('/[^a-z0-9\_\-]/', '', $pluginName) != $pluginName || strlen($pluginName) < 5 || strlen($pluginName) > 17) return array("error" => sprintf(__("Error. Invalid plugin name - %s. Size: 5-17 symbols. Allowed symbols: a..Z0..9_-", GOURL), $pluginName));
		if (stripos($pluginName, "product") === 0 || stripos($pluginName, "file") === 0 || stripos($pluginName, "pay") === 0 || stripos($pluginName, "membership") === 0 || stripos($pluginName, "user") === 0) return array("error" => __("Error. Please change plugin name. Plugin name can not begin with: 'file..', 'product..', 'pay..', 'membership..', 'user..'", GOURL));
		if (stripos($pluginName, "gourl") !== false && $pluginName != "gourlwoocommerce" && $affiliate_key != "gourl") return array("error" => __("Error. Please change plugin name. Plugin name can not use in name '..gourl..'", GOURL));
		$pluginName = strtolower(substr($pluginName, 0, 17));

		$amountLabel = trim(strtoupper($amountLabel));
		if ($amountLabel == "USD" && (!is_numeric($amount) ||  $amount > 1000000))	return array("error" => sprintf(__("Error. Invalid amount value - %s. Min value for USD: 0.01", GOURL), $amount));
		if ($amountLabel != "USD" && (!is_numeric($amount) ||  $amount > 500000000))	return array("error" => sprintf(__("Error. Invalid amount value - %s. Min value: 0.0001", GOURL), $amount));
		if ($amountLabel != "USD" && !isset($this->coin_names[$amountLabel])) return array("error" => sprintf(__("Error. Invalid amountCurrency - %s. Allowed: USD, %s", GOURL), $amountLabel, implode(", ", array_keys($this->coin_names))));

		if ($amountLabel == "USD" && $amount < 0.01)   $amount = 0.01;
		if ($amountLabel != "USD" && $amount < 0.0001) $amount = 0.0001;

		if (!$orderID || preg_replace('/[^A-Za-z0-9\_\-]/', '', $orderID) != $orderID || strlen($orderID) > 32) return array("error" => sprintf(__("Error. Invalid Order ID - %s. Max size: 32 symbols. Allowed symbols: a..Z0..9_-", GOURL), $orderID));

		$period = trim(strtoupper(str_replace(" ", "", $period)));
		if (substr($period, -1) == "S") $period = substr($period, 0, -1);
		for ($i=1; $i<=90; $i++) { $arr[] = $i."MINUTE"; $arr[] = $i."HOUR"; $arr[] = $i."DAY"; $arr[] = $i."WEEK"; $arr[] = $i."MONTH"; }
		if ($period != "NOEXPIRY" && !in_array($period, $arr)) return array("error" => sprintf(__("Error. Invalid period value - %s. Allowed: NOEXPIRY, 1..90 HOUR, 1..90 DAY, 1..90 WEEK, 1..90 MONTH; example: 2 DAYS", GOURL), $period));
		$period = str_replace(array("MINUTE", "HOUR", "DAY", "WEEK", "MONTH"), array(" MINUTE", " HOUR", " DAY", " WEEK", " MONTH", GOURL), $period);

		if (!$default_language) $default_language = "en";
		if (!in_array($default_language, array_keys($this->languages))) return array("error" => sprintf(__("Error. Invalid language - %s. Allowed: %s"), GOURL), $default_language, implode(", ", array_keys($this->languages)));

		if (!$default_coin) $default_coin = "bitcoin";
		if (!in_array($default_coin, $this->coin_names)) return array("error" => sprintf(__("Error. Invalid Coin - %s. Allowed: %s", GOURL), $default_coin, implode(",", $this->coin_names)));

		if ($affiliate_key == "gourl") $affiliate_key = "";
		if ($affiliate_key && (strpos($affiliate_key, "DEV") !== 0 || preg_replace('/[^A-Za-z0-9]/', '', $affiliate_key) != $affiliate_key)) return array("error" => __("Error. Invalid affiliate_key, you can leave it empty", GOURL));

		if (!$userID || $userID == "auto") $userID = get_current_user_id();
		if ($userID && $userID != "guest" && (!is_numeric($userID) || preg_replace('/[^0-9]/', '', $userID) != $userID)) return array("error" => sprintf(__("Error. Invalid User ID - %s. Allowed numeric values or 'guest' value", GOURL), $userID));
		if (!$userID) return array("error" => __("Error.", GOURL).__("You need first to login or register on the website to make Bitcoin/Altcoin Payments", GOURL));

		if (!$this->payments) return array("error" => __("Error. Please try a different payment method. GoUrl.io Bitcoin plugin is not configured yet. Need to setup GoUrl Public/Private Keys on plugin settings page. Please contact the website administrator.", GOURL));

		$icon_width = str_replace("px", "", $icon_width);
		if (!is_numeric($icon_width) || $icon_width < 30 || $icon_width > 250) $icon_width = 60;

		if (!$emultiplier || !is_numeric($emultiplier) || $emultiplier < 0.01) $emultiplier = 1;
		$emultiplier = floatval($emultiplier);


		$customtext = isset($additional_options["customtext"]) ? $additional_options["customtext"] : "";
		$qrcodesize = isset($additional_options["qrcodesize"]) ? $additional_options["qrcodesize"] : 200;
		$showlanguages = isset($additional_options["showlanguages"]) ? $additional_options["showlanguages"] : true;
		$redirect   = isset($additional_options["redirect"]) ? $additional_options["redirect"] : "";

		$qrcodesize = str_replace("px", "", $qrcodesize);
		if (!is_numeric($qrcodesize) || $qrcodesize < 0 || $qrcodesize > 500) $qrcodesize = 200;

		if (!is_bool($showlanguages)) $showlanguages = true;
		if (stripos($redirect, "http") !== 0) $redirect = '';



		/// GoUrl Payment Class
		// --------------------------

		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");

		$amountUSD              = 0;
		$exchange_error         = false;
		$all_keys 				= array(); 		// Your payment boxes public / private keys from GoUrl.io
		$available_coins 		= array(); 		// List of coins that you accept for payments
		$cryptobox_private_keys = array();		// List Of your private keys
		$coins_list				= "";
		$languages_list			= "";
		$html                   = "";




		// A. Initialize all available payments
		// -----
		foreach ($this->coin_names as $k => $v)
		{
		    $public_key 	= $this->options[$v.'public_key'];
		    $private_key 	= $this->options[$v.'private_key'];

		    if ($public_key && !strpos($public_key, "PUB"))    return array("error" => sprintf(__('Invalid %s Public Key - %s', GOURL), $v, $public_key));
		    if ($private_key && !strpos($private_key, "PRV"))  return array("error" => sprintf(__('Invalid %s Private Key', GOURL), $v));

		    if ($private_key) $cryptobox_private_keys[] = $private_key;
		    if ($private_key && $public_key)
		    {
		        $all_keys[$v] = array("public_key" => $public_key,  "private_key" => $private_key);
		        $available_coins[] = $v;
		    }
		}

		if(!defined("CRYPTOBOX_PRIVATE_KEYS")) define("CRYPTOBOX_PRIVATE_KEYS", implode("^", $cryptobox_private_keys));

		if (!$available_coins) return array("error" => sprintf(__("Error. Please enter Payment Private/Public Keys on GoUrl Options page for %s.", GOURL), "<b>".strtoupper($default_coin)."</b>"));

		if (!in_array($default_coin, $available_coins)) { $vals = array_values($available_coins); $default_coin = array_shift($vals); }




		// B. Current selected coin by user
		// -----
		$coinName = cryptobox_selcoin($available_coins, $default_coin);
		$coinLabel = array_search($coinName, $this->coin_names);

		// Current coin public/private keys
		$public_key  = $all_keys[$coinName]["public_key"];
		$private_key = $all_keys[$coinName]["private_key"];




		// C. Total Amount for Pay
		// ------------------------

		// Products prices in USD; convert USD to crypto on remote gateway side (gourl.io)
		if ($amountLabel == "USD")
		{
			$amountUSD		= $amount * $emultiplier;
			$amount 	    = 0;
		}

		// product prices in cryptocurrency; convert crypto to other crypto (DASH to LTC directly, etc) on your server side (yourwebsite.com)
		// for example 112 LTC ($amount=112 $amountLabel=LTC) need to convert to $coinName (Current selected coin by user)
		elseif ($amountLabel != $coinLabel)
		{
	        // for example, convert LTC to DASH
	        $s  = $amountLabel == "BTC" ? $amount : $amount * gourl_altcoin_btc_price($amountLabel); // total order price in bitcoins (LTC->BTC)
	        $e  = $coinLabel == "BTC" ? 1 : gourl_altcoin_btc_price($coinLabel); // coinName rate in bitcoins (DASH->BTC)
	        $s = (!$e) ? 0 : $s / $e;

	        // successfully
	        if ($s > 0)
	        {
	            if ($emultiplier == 1) $emultiplier = 1.01;
	            $amount = $s * $emultiplier;
	        }
	        // error, cannot get exchange rates
	        else
	        {
	            $amount = 99999;
	            $exchange_error = true;
	        }
		}

 		if ($amount && $amount < 0.0001)     $amount = 0.0001;
		if ($amountUSD && $amountUSD < 0.01) $amountUSD = 0.01;


		// D. PAYMENT BOX CONFIG
		// --------------------------


		$box_width		= $this->options["box_width"];
		$box_height		= $this->options["box_height"];
		$box_style		= $this->payment_box_style();
		$message_style	= $this->payment_message_style();



		$options = array(
				"public_key"  => $public_key, 								// your box public key
				"private_key" => $private_key, 								// your box private key
				"webdev_key"  => $affiliate_key,							// your gourl.io affiliate key, optional
				"orderID"     => $pluginName.".".$orderID, 					// unique  order id
				"userID"      => ($userID == "guest" ? $pluginName.".".$userID : "user".$userID), // unique identifier for each your user
				"userFormat"  => "MANUAL", 									// save userID in
				"amount"   	  => $amount,								    // price in coins
				"amountUSD"   => $amountUSD,								// price in USD
				"period"      => $period, 									// payment valid period
				"language"	  => $default_language  						// text on EN - english, FR - french, etc
		);



		// Initialise Payment Class
		$box = new Cryptobox ($options);


		// Coin name
		$coinName = $box->coin_name();


		// Paid or not
		$is_paid = $box->is_paid();



		// Payment Box HTML
		// ----------------------

        if ($this->options["box_type"] == 2)
        {
            // Active Payment Box - iFrame

            // page anchor
            $anchor = "go".$this->icrc32($pluginName.".".$orderID);

            // Coins selection list (html code)
            $coins_list = (count($available_coins) > 1) ? display_currency_box($available_coins, $default_coin, $default_language, $icon_width, "margin:10px 0 30px 0;text-align:center;font-weight:normal;", plugins_url('/images', __FILE__), $anchor) : "";

            // Language selection list for payment box (html code)
            $languages_list = display_language_box($default_language, $anchor);

            // Payment Box
            $box_html  = $this->iframe_scripts();
            $box_html .= $box->display_cryptobox(true, $box_width, $box_height, $box_style, $message_style, $anchor);

            $html = "<a id='".$anchor."' name='".$anchor."'></a>";

            if ($is_paid) 			$html .= "<br>";
            else 					$html .= $coins_list;

        }
        else
        {
            // Active Payment Box - jQuery

            $box_html  = $this->bootstrap_scripts();
            $box_html .= $box->display_cryptobox_bootstrap ($available_coins, $default_coin, $default_language, $customtext, $icon_width, $qrcodesize, $showlanguages, $this->box_logo(), "default", 250, $redirect, "curl") . "<br>";

            // info function display_cryptobox_bootstrap ($coins = array(), $def_coin = "", $def_language = "en", $customtext = "", $coinImageSize = 70, $qrcodeSize = 200, $show_languages = true, $logoimg_path = "default", $resultimg_path = "default", $resultimgSize = 250, $redirect = "", $method = "ajax", $debug = false)

            // Re-test after receive json data from live server
            $is_paid = $box->is_paid();
        }




		// Cryptocoin Payment Box
		if (!$exchange_error || $is_paid)
		{
    		if ($languages_list)
    		{
    			$html .= "<table cellspacing='0' cellpadding='0' border='0' width='100%' style='border:0;box-shadow:none;margin:0;padding:0;background-color:transparent'>";
    			$html .= "<tr style='background-color:transparent'><td style='border:0;margin:0;padding:0;background-color:transparent'><div style='margin:".($coins_list?25:50)."px 0 5px ".($this->options['box_width']/2-115)."px;min-width:100%;text-align:center;font-size:13px;color:#666;font-weight:normal;white-space:nowrap;'>".__('Language', GOURL).": ".$this->space(1).$languages_list."</div></td></tr>";
    			$html .= "<tr style='background-color:transparent'><td style='border:0;margin:0;padding:0;background-color:transparent'>".$box_html."</td></tr>";
    			$html .= "</table>";
    		}
    		else $html .= $box_html;
		}
		elseif ($exchange_error)
		{
		    $html .= "<br><br><div class='woocommerce-error'><p style='color:red'>";
		    $html .= sprintf(__("Error! Cannot get exchange rates for %s. Please try a different cryptocurrency.", GOURL), "<b>".strtoupper($coinName)."</b>");
		    $html .= "</p></div><br><br><br>";
		}


		// Result
		$obj = ($is_paid) ? $box->payment_info() : "";

		$arr = array   ("status"        	=> ($is_paid ? "payment_received" : "payment_not_received"),
						"error" 			=> "",
						"is_paid"			=> $is_paid,

						"paymentID"     	=> ($is_paid ? $obj->paymentID : 0),
						"paymentDate"		=> ($is_paid ? $obj->txDate : ""), // GMT
						"paymentLink"		=> ($is_paid ? GOURL_ADMIN.GOURL."payments&s=payment_".$obj->paymentID : ""), // page access for admin only
						"addr"       		=> ($is_paid ? $obj->addr : ""), 		// website admin cryptocoin wallet address
						"tx"            	=> ($is_paid ? $obj->txID : ""),			// transaction id, see also paymentDate
						"is_confirmed"     	=> ($is_paid ? $obj->txConfirmed : ""), 	// confirmed transaction or not, need wait 10+ min for confirmation

						"amount"			=> ($is_paid ? $obj->amount : ""), // paid coins amount (bitcoin, litecoin, etc)
						"amountusd"			=> $amountUSD,
						"coinlabel"			=> ($is_paid ? $obj->coinLabel : ""),
						"coinname"			=> ($is_paid ? strtolower($coinName) : ""),

						"boxID"     		=> ($is_paid ? $obj->boxID : 0),
						"boxtype"    		=> ($is_paid ? $obj->boxType : ""),
						"boxLink"    		=> ($is_paid ? "https://gourl.io/view/coin_boxes/".$obj->boxID."/statistics.html" : ""), // website owner have access only

						"orderID"       	=> $orderID,
						"userID"        	=> $userID,
						"usercountry"		=> ($is_paid ? $obj->countryID : ""),
						"userLink"        	=> ($userID=="guest"?"": admin_url("user-edit.php?user_id=".$userID)),

						"is_processed"		=> ($is_paid ? $obj->processed : ""),	// first time after payment received return TRUE, later return FALSE
						"processedDate"		=> ($is_paid && $obj->processed ? $obj->processedDate : ""),

						"callback_function"	=> $orderID."_gourlcallback", // information - your IPN callback function name
						"available_payments"=> $this->payments, 				// information - activated payments on website (bitcoin, litecoin, etc)

						"html_payment_box"	=> $html // html payment box

						);

		if ($is_paid && !$obj->processed) $box->set_status_processed();

		return $arr;
	}



	/********************************************************************/




	/*
	 *  76.
	 */
	private function upgrade()
	{
		global $wpdb;

		// TABLE 1 - crypto_files
		// ---------------------------
		if($wpdb->get_var("SHOW TABLES LIKE 'crypto_files'") != 'crypto_files')
		{
			$sql = "CREATE TABLE `crypto_files` (
			  `fileID` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `fileTitle` varchar(100) NOT NULL DEFAULT '',
			  `active` tinyint(1) NOT NULL DEFAULT '1',
			  `fileName` varchar(100) NOT NULL DEFAULT '',
			  `fileUrl` varchar(255) NOT NULL DEFAULT '',
			  `fileSize` double(15,0) NOT NULL DEFAULT '0',
			  `fileText` text,
			  `priceUSD` double(10,2) NOT NULL DEFAULT '0.00',
			  `priceCoin` double(17,5) NOT NULL DEFAULT '0.00000',
			  `priceLabel` varchar(6) NOT NULL DEFAULT '',
			  `purchases` mediumint(8) NOT NULL DEFAULT '0',
			  `userFormat` enum('MANUAL','COOKIE','SESSION','IPADDRESS') NOT NULL,
			  `expiryPeriod` varchar(15) NOT NULL DEFAULT '',
			  `lang` varchar(2) NOT NULL DEFAULT '',
			  `defCoin` varchar(5) NOT NULL DEFAULT '',
			  `defShow` tinyint(1) NOT NULL DEFAULT '1',
			  `image` varchar(100) NOT NULL DEFAULT '',
			  `imageWidth` smallint(5) NOT NULL DEFAULT '0',
			  `priceShow` tinyint(1) NOT NULL DEFAULT '1',
			  `paymentCnt` smallint(5) NOT NULL DEFAULT '0',
			  `paymentTime` datetime DEFAULT NULL,
			  `updatetime` datetime DEFAULT NULL,
			  `createtime` datetime DEFAULT NULL,
			  PRIMARY KEY (`fileID`),
			  KEY `fileTitle` (`fileTitle`),
			  KEY `active` (`active`),
			  KEY `fileName` (`fileName`),
			  KEY `fileUrl` (`fileUrl`),
			  KEY `fileSize` (`fileSize`),
			  KEY `priceUSD` (`priceUSD`),
			  KEY `priceCoin` (`priceCoin`),
			  KEY `priceLabel` (`priceLabel`),
			  KEY `purchases` (`purchases`),
			  KEY `userFormat` (`userFormat`),
			  KEY `expiryPeriod` (`expiryPeriod`),
			  KEY `lang` (`lang`),
			  KEY `defCoin` (`defCoin`),
			  KEY `defShow` (`defShow`),
			  KEY `image` (`image`),
			  KEY `imageWidth` (`imageWidth`),
			  KEY `priceShow` (`priceShow`),
			  KEY `paymentCnt` (`paymentCnt`),
			  KEY `paymentTime` (`paymentTime`),
			  KEY `updatetime` (`updatetime`),
			  KEY `createtime` (`createtime`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

			$wpdb->query($sql);
		}
		// upgrade
		elseif ($wpdb->query("select priceCoin from crypto_files limit 1") === false)
		{
			$wpdb->query("alter table crypto_files add `priceCoin` double(17,5) NOT NULL DEFAULT '0.00000' after priceUSD");
			$wpdb->query("alter table crypto_files add `priceLabel` varchar(6) NOT NULL DEFAULT '' after priceCoin");
			$wpdb->query("alter table crypto_files add key `priceCoin` (priceCoin)");
			$wpdb->query("alter table crypto_files add key `priceLabel` (priceLabel)");
		}
		elseif ($wpdb->query("select fileUrl from crypto_files limit 1") === false)
		{
			$wpdb->query("alter table crypto_files add `fileUrl` varchar(255) NOT NULL DEFAULT '' after fileName");
			$wpdb->query("alter table crypto_files add key `fileUrl` (fileUrl)");
			$wpdb->query("ALTER TABLE `crypto_files` CHANGE `priceCoin` `priceCoin` DOUBLE(17,5) NOT NULL DEFAULT '0.00000'");
		}



		// TABLE 2 - crypto_payments
		// ------------------------------
		if ($wpdb->get_var("SHOW TABLES LIKE 'crypto_payments'") != 'crypto_payments')
		{
			$sql = "CREATE TABLE `crypto_payments` (
			  `paymentID` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `boxID` int(11) unsigned NOT NULL DEFAULT '0',
			  `boxType` enum('paymentbox','captchabox') NOT NULL,
			  `orderID` varchar(50) NOT NULL DEFAULT '',
			  `userID` varchar(50) NOT NULL DEFAULT '',
			  `countryID` varchar(3) NOT NULL DEFAULT '',
			  `coinLabel` varchar(6) NOT NULL DEFAULT '',
			  `amount` double(20,8) NOT NULL DEFAULT '0.00000000',
			  `amountUSD` double(20,8) NOT NULL DEFAULT '0.00000000',
			  `unrecognised` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `addr` varchar(34) NOT NULL DEFAULT '',
			  `txID` char(64) NOT NULL DEFAULT '',
			  `txDate` datetime DEFAULT NULL,
			  `txConfirmed` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `txCheckDate` datetime DEFAULT NULL,
			  `processed` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `processedDate` datetime DEFAULT NULL,
			  `recordCreated` datetime DEFAULT NULL,
			  PRIMARY KEY (`paymentID`),
			  KEY `boxID` (`boxID`),
			  KEY `boxType` (`boxType`),
			  KEY `userID` (`userID`),
			  KEY `countryID` (`countryID`),
			  KEY `orderID` (`orderID`),
			  KEY `amount` (`amount`),
			  KEY `amountUSD` (`amountUSD`),
			  KEY `coinLabel` (`coinLabel`),
			  KEY `unrecognised` (`unrecognised`),
			  KEY `addr` (`addr`),
			  KEY `txID` (`txID`),
			  KEY `txDate` (`txDate`),
			  KEY `txConfirmed` (`txConfirmed`),
			  KEY `txCheckDate` (`txCheckDate`),
			  KEY `processed` (`processed`),
			  KEY `processedDate` (`processedDate`),
			  KEY `recordCreated` (`recordCreated`),
			  KEY `key1` (`boxID`,`orderID`),
			  KEY `key2` (`boxID`,`orderID`,`userID`),
			  KEY `key3` (`boxID`,`orderID`,`userID`,`txID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

			$wpdb->query($sql);
		}


		// TABLE 3 - crypto_membership
		// ------------------------------
		if ($wpdb->get_var("SHOW TABLES LIKE 'crypto_membership'") != 'crypto_membership')
		{
			$sql = "CREATE TABLE `crypto_membership` (
			  `membID` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `userID` bigint(20) unsigned NOT NULL DEFAULT '0',
			  `paymentID` int(11) unsigned NOT NULL DEFAULT '0',
			  `startDate` datetime DEFAULT NULL,
			  `endDate` datetime DEFAULT NULL,
			  `disabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `recordCreated` datetime DEFAULT NULL,
			  PRIMARY KEY (`membID`),
			  KEY `userID` (`userID`),
			  KEY `paymentID` (`paymentID`),
			  KEY `startDate` (`startDate`),
			  KEY `endDate` (`endDate`),
			  KEY `disabled` (`disabled`),
			  KEY `recordCreated` (`recordCreated`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

			$wpdb->query($sql);
		}


		// TABLE 4 - crypto_products
		// ------------------------------
		if ($wpdb->get_var("SHOW TABLES LIKE 'crypto_products'") != 'crypto_products')
		{
			$sql = "CREATE TABLE `crypto_products` (
				  `productID` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `productTitle` varchar(100) NOT NULL DEFAULT '',
				  `active` tinyint(1) NOT NULL DEFAULT '1',
				  `priceUSD` double(10,2) NOT NULL DEFAULT '0.00',
				  `priceCoin` double(17,5) NOT NULL DEFAULT '0.00000',
				  `priceLabel` varchar(6) NOT NULL DEFAULT '',
				  `purchases` mediumint(8) NOT NULL DEFAULT '0',
				  `expiryPeriod` varchar(15) NOT NULL DEFAULT '',
				  `lang` varchar(2) NOT NULL DEFAULT '',
				  `defCoin` varchar(5) NOT NULL DEFAULT '',
				  `defShow` tinyint(1) NOT NULL DEFAULT '1',
				  `productText` text,
				  `finalText` text,
				  `emailUser` tinyint(1) NOT NULL DEFAULT '1',
				  `emailUserFrom` varchar(50) NOT NULL DEFAULT '',
				  `emailUserTitle` varchar(100) NOT NULL DEFAULT '',
				  `emailUserBody` text,
				  `emailAdmin` tinyint(1) NOT NULL DEFAULT '1',
				  `emailAdminFrom` varchar(50) NOT NULL DEFAULT '',
				  `emailAdminTo` text,
				  `emailAdminTitle` varchar(100) NOT NULL DEFAULT '',
				  `emailAdminBody` text,
				  `paymentCnt` smallint(5) NOT NULL DEFAULT '0',
				  `paymentTime` datetime DEFAULT NULL,
				  `updatetime` datetime DEFAULT NULL,
				  `createtime` datetime DEFAULT NULL,
				  PRIMARY KEY (`productID`),
				  KEY `productTitle` (`productTitle`),
				  KEY `active` (`active`),
				  KEY `priceUSD` (`priceUSD`),
				  KEY `priceCoin` (`priceCoin`),
				  KEY `priceLabel` (`priceLabel`),
				  KEY `purchases` (`purchases`),
				  KEY `expiryPeriod` (`expiryPeriod`),
				  KEY `lang` (`lang`),
				  KEY `defCoin` (`defCoin`),
				  KEY `defShow` (`defShow`),
				  KEY `emailUser` (`emailUser`),
				  KEY `emailAdmin` (`emailAdmin`),
				  KEY `paymentCnt` (`paymentCnt`),
				  KEY `paymentTime` (`paymentTime`),
				  KEY `updatetime` (`updatetime`),
				  KEY `createtime` (`createtime`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

			$wpdb->query($sql);
		}


		if (true === version_compare(get_option(GOURL.'prev_version'), '1.6.0', '<'))
		foreach($this->coin_names as $k => $v)
		{
			update_option(GOURL.$v."public_key", "");
			update_option(GOURL.$v."private_key", "");
		}


		// upload dir
		gourl_retest_dir();

		if (!file_exists($this->hash_url)) file_put_contents($this->hash_url, '{"nonce":"1"}');

		// current plugin version
		update_option(GOURL.'prev_version', GOURL_VERSION);
		update_option(GOURL.'version', GOURL_VERSION);

		ob_flush();

		return true;
	}



	/*
	 *  77. Make Compatible with Force Login plugin
	 */
	public function v_forcelogin_whitelist ($arr)
	{
		$url = trim(get_site_url(), "/ ") . "/";

		$arr[] = $url . "?cryptobox.callback.php";
		$arr[] = $url . "index.php?cryptobox.callback.php";
		$arr[] = $url . "?cryptobox_callback_php";
		$arr[] = $url . "index.php?cryptobox_callback_php";
		$arr[] = $url . "?cryptobox-callback-php";
		$arr[] = $url . "index.php?cryptobox-callback-php";

		return $arr;
	}



	/*
	 *  78. Exclude gourl js file from aggregation in Autoptimize
	 */
	public function exclude_js_file($exclude)
	{
	    return $exclude . ", cryptobox.min";
	}



	/*
	 *  79. Need to setup gourl.io keys
	 */
	public function display_error_nokeys()
	{
		return "<div align='center'><a href='".$_SERVER['REQUEST_URI']."'><img border='0' src='".plugins_url('/images/error_keys.png', __FILE__)."'></img></a></div>";
	}



	/*
	 *  80. Supported Functions
	 */
	private function sel($val1, $val2)
	{
		$tmp = ((is_array($val1) && in_array($val2, $val1)) || strval($val1) == strval($val2)) ? ' selected="selected"' : '';

		return $tmp;
	}
	private function chk($val1, $val2)
	{
		$tmp = (strval($val1) == strval($val2)) ? ' checked="checked"' : '';

		return $tmp;
	}
	public function left($str, $findme, $firstpos = true)
	{
		$pos = ($firstpos)? mb_stripos($str, $findme) : mb_strripos($str, $findme);

		if ($pos === false) return $str;
		else return mb_substr($str, 0, $pos);
	}
	public function right($str, $findme, $firstpos = true)
	{
		$pos = ($firstpos)? mb_stripos($str, $findme) : mb_strripos($str, $findme);

		if ($pos === false) return $str;
		else return mb_substr($str, $pos + mb_strlen($findme));
	}
	private function icrc32($str)
	{
		$in = crc32($str);
		$int_max = pow(2, 31)-1;
		if ($in > $int_max) $out = $in - $int_max * 2 - 2;
		else $out = $in;
		$out = abs($out);

		return $out;
	}
	private function space($n=1)
	{
		$tmp = "";
		for ($i=1;$i<=$n;$i++) $tmp .= " &#160; ";
		return $tmp;
	}
}
// end class gourlclass








/*
 *  I. Activate Plugin
*/
function gourl_activate()
{
	if (!function_exists( 'mb_stripos' ) || !function_exists( 'mb_strripos' ))  { echo sprintf(__("Error. Please enable <a target='_blank' href='%s'>MBSTRING extension</a> in PHP. <a target='_blank' href='%s'>Read here &#187;</a>", GOURL), "http://php.net/manual/en/book.mbstring.php", "http://www.knowledgebase-script.com/kb/article/how-to-enable-mbstring-in-php-46.html"); die(); }
	if (!function_exists( 'curl_init' )) 										{ echo sprintf(__("Error. Please enable <a target='_blank' href='%s'>CURL extension</a> in PHP. <a target='_blank' href='%s'>Read here &#187;</a>", GOURL), "http://php.net/manual/en/book.curl.php", "http://stackoverflow.com/questions/1347146/how-to-enable-curl-in-php-xampp"); die(); }
	if (!function_exists( 'mysqli_connect' )) 									{ echo sprintf(__("Error. Please enable <a target='_blank' href='%s'>MySQLi extension</a> in PHP. <a target='_blank' href='%s'>Read here &#187;</a>", GOURL), "http://php.net/manual/en/book.mysqli.php", "http://crybit.com/how-to-enable-mysqli-extension-on-web-server/"); die(); }
	if (version_compare(phpversion(), '5.4.0', '<')) 							{ echo sprintf(__("Error. You need PHP 5.4.0 (or greater). Current php version: %s", GOURL), phpversion()); die(); }
}


/*
 *  Deactivate Plugin
*/
function gourl_deactivate()
{
	update_option(GOURL.'version', '');
}




/*
 *  II.
*/
function gourl_retest_dir()
{

	$elevel = error_reporting();
	error_reporting(0);

	$dir = plugin_dir_path( __FILE__ )."images/dir/";

	if (!file_exists(GOURL_DIR."files")) wp_mkdir_p(GOURL_DIR."files");
	if (!file_exists(GOURL_DIR."files/.htaccess")) copy($dir."files/.htaccess", GOURL_DIR."files/.htaccess");
	if (!file_exists(GOURL_DIR."files/index.html")) copy($dir."files/index.html", GOURL_DIR."files/index.html");

	if (!file_exists(GOURL_DIR."lockimg")) wp_mkdir_p(GOURL_DIR."lockimg");
	if (!file_exists(GOURL_DIR."lockimg/index.html")) copy($dir."lockimg/index.html", GOURL_DIR."lockimg/index.html");
	if (!file_exists(GOURL_DIR."lockimg/image1.jpg")) copy($dir."lockimg/image1.jpg", GOURL_DIR."lockimg/image1.jpg");
	if (!file_exists(GOURL_DIR."lockimg/image1.png")) copy($dir."lockimg/image1.png", GOURL_DIR."lockimg/image1.png");
	if (!file_exists(GOURL_DIR."lockimg/image1b.png")) copy($dir."lockimg/image1b.png", GOURL_DIR."lockimg/image1b.png");
	if (!file_exists(GOURL_DIR."lockimg/image2.jpg")) copy($dir."lockimg/image2.jpg", GOURL_DIR."lockimg/image2.jpg");

	if (!file_exists(GOURL_DIR."box")) wp_mkdir_p(GOURL_DIR."box");

	if (!file_exists(GOURL_DIR."images"))
	{
		wp_mkdir_p(GOURL_DIR."images");

		$files = scandir($dir."images");
		foreach($files as $file)
			if (is_file($dir."images/".$file) && !in_array($file, array(".", "..")))
			copy($dir."images/".$file, GOURL_DIR."images/".$file);
	}

	if (!file_exists(GOURL_PHP)) wp_mkdir_p(GOURL_PHP);
	if (!file_exists(GOURL_PHP."/.htaccess")) copy($dir."files/.htaccess", GOURL_PHP."/.htaccess");
	if (!file_exists(GOURL_PHP."/index.html")) copy($dir."files/index.html", GOURL_PHP."/index.html");
	if (!file_exists(GOURL_PHP."/gourl_ipn.php"))
	{
	    if (file_exists(GOURL_DIR."files/gourl_ipn.php") && filesize(GOURL_DIR."files/gourl_ipn.php") != "4104")
	        copy(GOURL_DIR."files/gourl_ipn.php", GOURL_PHP."/gourl_ipn.php");
	    else
	        copy($dir."gourl_ipn.default.txt", GOURL_PHP."/gourl_ipn.php");

	    if (file_exists(GOURL_DIR."files/gourl_ipn.php"))  unlink(GOURL_DIR."files/gourl_ipn.php");
	    if (file_exists(GOURL_DIR."files/gourl.hash"))     unlink(GOURL_DIR."files/gourl.hash");
	    chmod(GOURL_PHP."/gourl.hash", 0755);
	    chmod(GOURL_PHP."/gourl_ipn.php", 0755);
	}



	error_reporting($elevel);

	return true;
}



/*
 *  III.
*/
function gourl_byte_format ($num, $precision = 1)
{
	if ($num >= 1000000000000)
	{
		$num = round($num / 1099511627776, $precision);
		$unit = __('TB', GOURL);
	}
	elseif ($num >= 1000000000)
	{
		$num = round($num / 1073741824, $precision);
		$unit = __('GB', GOURL);
	}
	elseif ($num >= 1000000)
	{
		$num = round($num / 1048576, $precision);
		$unit = __('MB', GOURL);
	}
	elseif ($num >= 1000)
	{
		$num = round($num / 1024, $precision);
		$unit = __('kb', GOURL);
	}
	else
	{
		$unit = __('Bytes', GOURL);
		return number_format($num).' '.$unit;
	}

	$num = gourl_number_format($num, $precision);

	return $num.' '.$unit;
}



/*
 *  IV.
*/
function gourl_number_format ($num, $precision = 1)
{
	$num = number_format($num, $precision);
	if (strpos($num, ".")) $num = rtrim(rtrim($num, "0"), ".");

	return $num;
}


/*
 *  V.
*/

function gourl_checked_image ($val)
{
	$val = ($val) ? "checked" : "unchecked";
	$tmp = "<img alt='".__(ucfirst($val), GOURL)."' src='".plugins_url('/images/'.$val.'.gif', __FILE__)."' border='0'>";
	return $tmp;
}


/*
 *  VI. User Details
*/
function gourl_userdetails($val, $br = true)
{
	$tmp = $val;

	if ($val)
	{
		if (strpos($val, "user_") === 0)    $userID = substr($val, 5);
		elseif (strpos($val, "user") === 0) $userID = substr($val, 4);
		else $userID = $val;

		$userID = intval($userID);
		if ($userID)
		{
			$obj =  get_userdata($userID);
			if ($obj && $obj->data->user_nicename) $tmp = "user".$userID." - <a href='".admin_url("user-edit.php?user_id=".$userID)."'>".$obj->data->user_nicename . ($br?"<br>":", &#160; ") . $obj->data->user_email . "</a>";
			else $tmp = "user".$userID;
		}
	}

	return $tmp;
}




/*
 *  VII. User Membership Edit Screen
*/
function gourl_edit_user_profile($user)
{
	global $wpdb;

	$tmp  = "";
	if ($user->ID)
	{

		$obj = $wpdb->get_results("SELECT txDate FROM crypto_payments WHERE userID = 'user".intval($user->ID)."' || userID = 'user_".intval($user->ID)."' ORDER BY txDate DESC LIMIT 1", OBJECT);

		$tmp .= "<table class='form-table'>";
		$tmp .= "<tr><th>".__('Bitcoin/altcoin Payments?', GOURL)."</th><td>";
		if ($obj) $tmp .= "<b><a href='".GOURL_ADMIN.GOURL."payments&s=user".$user->ID."'>".__('YES', GOURL)."</a></b> &#160; &#160; &#160; ".__('Latest payment', GOURL)." : &#160;" . date("d M Y, H:i A", strtotime($obj[0]->txDate)) . "&#160; ".__('GMT', GOURL);
		else $tmp .= "<b><a href='".GOURL_ADMIN.GOURL."payments&s=user".$user->ID."'>".__('NO', GOURL)."</a></b>";
		$tmp .= "</td></tr>";
		$tmp .= "</table>";

		if (get_option(GOURL."ppmProfile"))
		{
			$min = $max = "";
			$dt = gmdate('Y-m-d H:i:s');
			$obj = $wpdb->get_results("SELECT * FROM crypto_membership WHERE userID = ".intval($user->ID)." && startDate <= '$dt' && endDate >= '$dt' && disabled = 0", OBJECT);

			if ($obj)
				foreach($obj as $row)
				{
					if (!$min || strtotime($row->startDate) < $min) $min = strtotime($row->startDate);
					if (!$max || strtotime($row->endDate) > $max) $max = strtotime($row->endDate);
				}


			$yes = current_user_can('administrator') ? "<a href='".GOURL_ADMIN.GOURL."paypermembership_users&s=user".$user->ID."'>".__('YES', GOURL)."</a>" : __('YES', GOURL);

			$tmp .= "<table class='form-table'>";
			$tmp .= "<tr><th>".__('Premium Membership', GOURL)."</th><td>";
			if ($obj) $tmp .= "<b>".$yes."</b> &#160; &#160; &#160; ".__('Period', GOURL)." : &#160; " .date("d M Y, H:i A", $min) . "&#160; - &#160;" . date("d M Y, H:i A", $max) . "&#160; ".__('GMT', GOURL);
			else $tmp .= "<b>".__('NO', GOURL)."</b>	 &#160; &#160; &#160; <a href='".GOURL_ADMIN.GOURL."paypermembership_user&userID=".$user->ID."'><small>".__('Manually Add Premium Membership', GOURL)."</small></a>";
			$tmp .= "</td></tr>";
			$tmp .= "</table>";
		}

		echo $tmp;
	}

	return true;
}



/*
 *  VIII. User Profile Screen
*/
function gourl_show_user_profile($user)
{
	global $wpdb;

	$tmp  = "";
	if ($user->ID && get_option(GOURL."ppmProfile"))
	{

		$min = $max = "";
		$dt = gmdate('Y-m-d H:i:s');
		$obj = $wpdb->get_results("SELECT * FROM crypto_membership WHERE userID = ".intval($user->ID)." && startDate <= '$dt' && endDate >= '$dt' && disabled = 0", OBJECT);

		if ($obj)
			foreach($obj as $row)
			{
				if (!$min || strtotime($row->startDate) < $min) $min = strtotime($row->startDate);
				if (!$max || strtotime($row->endDate) > $max) $max = strtotime($row->endDate);
			}


			$yes = current_user_can('administrator') ? "<a href='".GOURL_ADMIN.GOURL."paypermembership_users&s=user".$user->ID."'>".__('YES', GOURL)."</a>" : __('YES', GOURL);

			$tmp .= "<table class='form-table'>";
			$tmp .= "<tr><th>".__('Premium Membership', GOURL)."</th><td>";
			if ($obj) $tmp .= "<b>".$yes."</b> &#160; &#160; &#160; ".__('Period', GOURL)." : &#160; " . date("d M Y", $min) . "&#160; - &#160;" . date("d M Y", $max);
			else $tmp .= "<b>".__('NO', GOURL)."</b>";
			$tmp .= "</td></tr>";
			$tmp .= "</table>";

		echo $tmp;
	}

	return true;
}








/*
 *  IX. User-defined function for new payment
*/
function cryptobox_new_payment($paymentID, $arr, $box_status)
{
	$dt = gmdate('Y-m-d H:i:s');
	$order_id = '';

	if (!isset($arr["status"]) || !in_array($arr["status"], array("payment_received", "payment_received_unrecognised")) || !in_array($box_status, array("cryptobox_newrecord", "cryptobox_updated"))) return false;

	if ($box_status == "cryptobox_newrecord")
	{

		// Pay-Per-Download
		// ----------------------
		$fileID = ($arr["order"] && strpos($arr["order"], "file_") === 0) ? substr($arr["order"], 5) : 0;
		if ($fileID && is_numeric($fileID))
		{
			$sql = "UPDATE crypto_files SET paymentCnt = paymentCnt + 1, paymentTime = '".$dt."' WHERE fileID = '".$fileID."' LIMIT 1";
			run_sql($sql);
		}

		// Pay-Per-Product
		// ----------------------
		$productID = ($arr["order"] && strpos($arr["order"], "product_") === 0) ? substr($arr["order"], 8) : 0;
		if ($productID && is_numeric($productID))
		{
			$sql = "UPDATE crypto_products SET paymentCnt = paymentCnt + 1, paymentTime = '".$dt."' WHERE productID = '".$productID."' LIMIT 1";
			run_sql($sql);

			// Send email notifications
			gourl_email_notifications($productID, $paymentID, $arr, "product");
		}

		// Pay-Per-Membership
		// ----------------------
		if (strpos($arr["order"], "membership") === 0)
		{
			$userID = ($arr["user"] && strpos($arr["user"], "user_") === 0) ? intval(substr($arr["user"], 5)) : 0;

			$expiry = get_option(GOURL."ppmExpiry");
			if ($expiry == "NO EXPIRY") $endDate = "2030-01-01 00:00:00";
			else
			{
				if (!$expiry) $expiry = "1 MONTH";
				$endDate = date('Y-m-d H:i:s', strtotime("+".$expiry." GMT"));
			}

			$sql = "INSERT INTO crypto_membership  (userID, paymentID, startDate, endDate, disabled, recordCreated)
											VALUES ($userID, $paymentID, '$dt', '$endDate', 0, '$dt')";

			run_sql($sql);
		}

	}



	// Custom Callback
	// ----------------------
	$func_callback = "";
	if (strpos($arr["user"], "user_") === 0) 	$user_id  = substr($arr["user"], 5);
	elseif (strpos($arr["user"], "user") === 0) $user_id  = substr($arr["user"], 4);
	else $user_id = $arr["user"];


	// A.Pay-Per-.. IPN notifications
	if (!strpos($arr["order"], ".") &&
		(strpos($arr["order"], "product_") === 0  || strpos($arr["order"], "file_") === 0 ||
		strpos($arr["order"], "membership") === 0 || $arr["order"] == "payperview" || !$arr["order"]))
		{

			if (!defined('GOURL_IPN'))  DEFINE('GOURL_IPN', true);
			if (strpos($arr["order"], "membership") === 0) $arr["order"] = "membership";
			include_once(GOURL_PHP."/gourl_ipn.php");

			$order_id		= $arr["order"];
			$func_callback 	= "gourl_successful_payment";
		}

	// B. Other Plugins IPN notifications
	if (strpos($arr["user"], "user_") !== 0 && strpos($arr["order"], "."))
	{
		$order_id 		= mb_substr($arr["order"], mb_strpos($arr["order"], ".") + 1);
		$func_callback 	= mb_substr($arr["order"], 0, mb_stripos($arr["order"], "."))."_gourlcallback";
		if (strpos($user_id, ".guest")) $user_id = "guest";
	}


	$payment_details = array (
	    "status"        	=> $arr["status"],
	    "error" 			=> $arr["err"],
	    "is_paid"			=> 1,

	    "paymentID"     	=> intval($paymentID),
	    "paymentDate"		=> $arr["datetime"], 				// GMT 2015-01-30 13:32:45
	    "paymentTimestamp"	=> $arr["timestamp"], 				// 1422624765
	    "paymentLink"		=> GOURL_ADMIN.GOURL."payments&s=payment_".$paymentID,
	    "addr"       		=> $arr["addr"], 					// website admin cryptocoin wallet address
	    "tx"            	=> $arr["tx"],						// transaction id, see also paymentDate
	    "is_confirmed"     	=> intval($arr["confirmed"]), 		// confirmed transaction or not, need wait 10+ min for confirmation

	    "amount"			=> $arr["amount"], 					// paid coins amount (bitcoin, litecoin, etc)
	    "amountusd"			=> $arr["amountusd"],
	    "coinlabel"			=> $arr["coinlabel"],
	    "coinname"			=> strtolower($arr["coinname"]),

	    "boxID"     		=> $arr["box"],
	    "boxtype"    		=> $arr["boxtype"],
	    "boxLink"    		=> "https://gourl.io/view/coin_boxes/".$arr["box"]."/statistics.html", // website owner have access only

	    "orderID"       	=> $order_id,
	    "userID"        	=> $user_id,
	    "usercountry"		=> $arr["usercountry"],
	    "userLink"        	=> (strpos($arr["user"], "user")===0 ? admin_url("user-edit.php?user_id=".$user_id) : "")
	);


	// Hook - Execute user function cryptobox_after_new_payment
	do_action('cryptobox_after_new_payment', $user_id, $order_id, $payment_details, $box_status);


	// Call IPN function
	if ($func_callback && function_exists($func_callback))
	{
		$func_callback($user_id, $order_id, $payment_details, $box_status);
	}


	return true;
}





/*
 *  X.
*/
function gourl_lock_filter($content)
{

	$content = mb_substr($content, mb_strpos($content, GOURL_LOCK_START));
	$content = mb_substr($content, 0, mb_strpos($content, GOURL_LOCK_END));

	return $content;
}



/*
 *  XI.
*/
function gourl_lock_comments($content)
{
	$content = "<br>* * * * * * * * * * * * * * * * * * * * * * *<br> * * * * * * * * * * * * * * * * * * * * * * *";

	return $content;
}



/*
 *  XII. Content Restriction
*/

function gourl_hide_all_titles($title)
{
	$title = (in_the_loop()) ? "" : "* * * * * * * * &#160; * * * * * *";

	return $title;
}

function gourl_hide_menu_titles($title)
{
	if (!in_the_loop()) $title = "* * * * * * * * &#160; * * * * * *";

	return $title;
}
function gourl_hide_page_title($title)
{
	if (in_the_loop()) $title = "";

	return $title;
}

function gourl_hide_headtitle($title)
{
	return get_bloginfo('name');
}

function gourl_hide_headtitle_unlogged($title)
{
	return __("Please Login", GOURL) . " | " . get_bloginfo('name');
}



/*
 *  XIII.
*/
function gourl_return_false()
{

	return false;
}



/*
 *  XIV.
*/
function gourl_disable_feed()
{
	wp_die(sprintf(__("<h1>Feed not available, please visit our <a href='%s'>Home Page</a>!</h1>"), get_bloginfo('url')));
}


/*
 *  XV.
*/

function gourl_email_notifications($productID, $paymentID, $details, $type)
{
	global $wpdb, $gourl;

	$payment_id 		= $paymentID;
	$transaction_id 	= $details["tx"];
	$transaction_time 	= date("d M Y, H:i:s a", $details["timestamp"]);
	$payment_url		= GOURL_ADMIN.GOURL."payments&s=payment_".$paymentID; // visible for admin only
	$payment_url		= "<a href='".$payment_url."'>".$payment_url."</a>";
	$paid_amount 		= gourl_number_format($details["amount"], 8) . " " . $details["coinlabel"];
	$paid_amount_usd 	= "~".gourl_number_format($details["amountusd"], 2) . " USD";

	$user_id 			= 0;
	$user_fullname 		= "User";
	$user_username 		= "";
	$user_email 		= "";
	$user_url 			= "";

	if (!$productID || !$paymentID || !$transaction_id || !$type) return false;

	if ($transaction_id) $transaction_id = "<a href='".$gourl->blockexplorer_tr_url($transaction_id, $details["coinname"])."' target='_blank'>".$transaction_id."</a>";

	$txt_to 			= array($user_fullname, $user_username, $user_id, $user_email, $user_url, $paid_amount, $paid_amount_usd, $payment_id, $payment_url, $transaction_id, $transaction_time);
	$txt_from 			= array("{user_fullname}", "{user_username}", "{user_id}", "{user_email}", "{user_url}", "{paid_amount}", "{paid_amount_usd}", "{payment_id}", "{payment_url}", "{transaction_id}", "{transaction_time}");


	if ($type == "product")
	{
		$res = $wpdb->get_row("SELECT * from crypto_products where productID =".intval($productID), OBJECT);
		if ($res)
		{
			$user_id = 0;
			if (strpos($details["user"], "user_") === 0 && is_numeric(substr($details["user"], 5))) 	$user_id = intval(substr($details["user"], 5));
			elseif (strpos($details["user"], "user") === 0 && is_numeric(substr($details["user"], 4))) 	$user_id = intval(substr($details["user"], 4));

			// send email to user
			if ($user_id)
			{
				$user_info = get_userdata($user_id);
				if ($user_info)
				{
					$user_fullname  = trim($user_info->first_name." ".$user_info->last_name);
					$user_username 	= $user_info->user_login;
					$user_email 	= $user_info->user_email;
					$user_url 		= admin_url("user-edit.php?user_id=".$user_id);
					$user_url 		= "<a href='".$user_url."'>".$user_url."</a>";

					if (!$user_fullname) $user_fullname =  $user_username;

					$txt_to 			= array($user_fullname, $user_username, $user_id, $user_email, $user_url, $paid_amount, $paid_amount_usd, $payment_id, $payment_url, $transaction_id, $transaction_time);
					$emailUserFrom	= $res->emailUserFrom;
					$emailToUser 	= $user_email;
					$emailUserTitle = htmlspecialchars($res->emailUserTitle, ENT_NOQUOTES);
					$emailUserTitle = (mb_strpos($emailUserTitle, "{")=== false) ? $emailUserTitle : str_replace($txt_from, $txt_to, $emailUserTitle);
					$emailUserBody 	= htmlspecialchars($res->emailUserBody, ENT_QUOTES);
					$emailUserBody 	= (mb_strpos($emailUserBody, "{")=== false) ? $emailUserBody : str_replace($txt_from, $txt_to, $emailUserBody);

					$headers	= array();
					$headers[] 	= 'From: '.$emailUserFrom.' <'.$emailUserFrom.'>';
					$headers[] 	= 'Content-type: text/html';
					if ($res->emailUser) wp_mail($emailToUser, $emailUserTitle, nl2br($emailUserBody), $headers);
				}
			}


			// send email to seller/admin
			$emailAdminFrom	 = $res->emailAdminFrom;
			$emailToAdmin 	 = trim($res->emailAdminTo);
			$emailAdminTitle = htmlspecialchars($res->emailAdminTitle, ENT_NOQUOTES);
			$emailAdminTitle = (mb_strpos($emailAdminTitle, "{")=== false) ? $emailAdminTitle : str_replace($txt_from, $txt_to,  $emailAdminTitle);
			$emailAdminBody  = htmlspecialchars($res->emailAdminBody, ENT_QUOTES);
			$emailAdminBody  = (mb_strpos($emailAdminBody, "{")=== false) ? $emailAdminBody : str_replace($txt_from, $txt_to,  $emailAdminBody);

			$headers	= array();
			$headers[] 	= 'From: '.$emailAdminFrom.' <'.$emailAdminFrom.'>';
			$headers[] 	= 'Content-type: text/html';

			$emails = explode("\n", $emailToAdmin);
			if (count($emails) > 1)
			{
				$emailToAdmin 	= array_shift($emails);
				foreach($emails as $v) $headers[] = 'Cc: '.$v.' <'.$v.'>';
			}

			if ($res->emailAdmin) wp_mail($emailToAdmin, $emailAdminTitle, nl2br($emailAdminBody), $headers);
		}
	} // end product

	return true;
}








/********************************************************************/








// XVI. TABLE1 - "All Paid Files"  WP_Table Class
// ----------------------------------------

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class gourl_table_files extends WP_List_Table
{
	private $coin_names = array();
	private $languages	= array();

	private $search 		= '';
	private $rec_per_page	= 20;

	function __construct($search = '', $rec_per_page = 20)
	{

		$this->coin_names 	= gourlclass::coin_names();
		$this->languages	= gourlclass::languages();
		$this->search 		= $search;
		$this->rec_per_page = $rec_per_page;
		if ($this->rec_per_page < 5) $this->rec_per_page = 20;


		global $status, $page;
		parent::__construct( array(
				'singular'=> 'mylist',
				'plural' => 'mylists',
				'ajax'    => false

		) );
	}

	function column_default( $item, $column_name )
	{
		$tmp = "";
		switch( $column_name )
		{
			case 'active':
			case 'defShow':
			case 'priceShow':
				$tmp = gourl_checked_image($item->$column_name);
				break;

			case 'fileName':
				if ($item->fileUrl) $tmp = "<a href='".$item->fileUrl."'>".$item->fileUrl."</a>";
				else $tmp = "<a href='".GOURL_ADMIN.GOURL.'&'.GOURL_PREVIEW.'='.$item->fileName."'>".$item->fileName."</a>";
				break;

			case 'fileSize':
				if ($item->fileUrl) $tmp = "<a href='".$item->fileUrl."'><img width='30' alt='url' src='".plugins_url('/images/url.png', __FILE__)."' border='0'></a>";
				else $tmp = gourl_byte_format($item->$column_name);
				break;

			case 'priceUSD':
				if ($item->$column_name > 0)
				{
					$num = gourl_number_format($item->$column_name, 2);
					$tmp = $num . ' ' . __('USD', GOURL);
				}
				break;

			case 'priceCoin':
				if ($item->$column_name > 0 && $item->priceUSD <= 0)
				{
					$num = gourl_number_format($item->$column_name, 4);
					$tmp = $num . ' ' . $item->priceLabel;
				}
				break;

			case 'paymentCnt':
				$tmp = ($item->$column_name > 0) ? '<a href="'.GOURL_ADMIN.GOURL.'payments&s=file_'.$item->fileID.'">'.$item->$column_name.'</a>' : '-';
				break;

			case 'image':
				$img = GOURL_DIR2.'images/'.$item->$column_name;
				$tmp = "<a target='_blank' href='".$img."'><img width='80' height='80' src='".$img."' border='0'></a>";
				break;

			case 'defCoin':
				if ($item->$column_name)
				{
					$val = $this->coin_names[$item->$column_name];
					$tmp = "<a href='".GOURL_ADMIN.GOURL."files&s=".$val."'><img width='40' alt='".$val."' title='".__('Show this coin transactions only', GOURL)."' src='".plugins_url('/images/'.$val.'.png', __FILE__)."' border='0'></a>";
				}
				break;

			case 'lang':
				$tmp = $this->languages[$item->$column_name];
				break;

			case 'purchases':
				$tmp = ($item->$column_name == 0) ?  __('unlimited', GOURL) : $item->$column_name . ' ' . __('copies', GOURL);
				break;

			case 'imageWidth':
				$tmp = ($item->$column_name > 0) ?  $item->$column_name. ' ' . __('px', GOURL) : '-';
				break;

			case 'userFormat':
				$tmp = ($item->$column_name == 'MANUAL') ?   __('Registered Users', GOURL) : $item->$column_name;
				break;

			case 'paymentTime':
			case 'updatetime':
			case 'createtime':
				$tmp = ($item->$column_name && date("Y", strtotime($item->$column_name)) > 2010) ? date("d M Y, H:i A", strtotime($item->$column_name)) : '-';
				break;

			default:
				$tmp = $item->$column_name;
				break;
		}

		return $tmp;
	}




	function get_columns()
	{
		$columns = array(
				'fileID'  		=> __('ID', GOURL),
				'active'  		=> __('Acti-ve?', GOURL),
				'fileName'  	=> __('File Name', GOURL),
				'fileTitle' 	=> __('Title', GOURL),
				'fileSize'  	=> __('File Size', GOURL),
				'priceUSD'  	=> __('Price USD', GOURL),
				'priceCoin'  	=> __('Price in Coins', GOURL),
				'priceShow'  	=> __('Show FileName/Price?', GOURL),
				'paymentCnt'  	=> __('Total Sold', GOURL),
				'paymentTime'  	=> __('Latest Received Payment, GMT', GOURL),
				'updatetime'  	=> __('Record Updated, GMT', GOURL),
				'createtime'  	=> __('Record Created, GMT', GOURL),
				'image'  		=> __('Featured Image', GOURL),
				'imageWidth'  	=> __('Image Width', GOURL),
				'expiryPeriod'  => __('Payment Expiry Period', GOURL),
				'defCoin'  		=> __('Default Payment Box Coin', GOURL),
				'defShow'  		=> __('Default Coin only?', GOURL),
				'lang'  		=> __('Default Box Language', GOURL),
				'purchases'  	=> __('Purchase Limit', GOURL),
				'userFormat'  	=> __('Store Visitor IDs', GOURL)
		);
		return $columns;
	}


	function get_sortable_columns()
	{
		$sortable_columns = array
		(
				'fileID'  		=> array('fileID', false),
				'active'  		=> array('active', true),
				'fileName'  	=> array('fileName', true),
				'fileTitle' 	=> array('fileTitle', false),
				'fileSize'  	=> array('fileSize', false),
				'priceUSD'  	=> array('priceUSD', false),
				'priceCoin'  	=> array('priceCoin', false),
				'priceShow'  	=> array('priceShow', true),
				'paymentCnt'  	=> array('paymentCnt', true),
				'paymentTime'  	=> array('paymentTime', true),
				'updatetime'  	=> array('updatetime', true),
				'createtime'  	=> array('createtime', true),
				'image'  		=> array('image', false),
				'imageWidth'  	=> array('imageWidth', false),
				'expiryPeriod'  => array('expiryPeriod', false),
				'defCoin'  		=> array('defCoin', false),
				'defShow'  		=> array('defShow', true),
				'lang'  		=> array('lang', false),
				'purchases'  	=> array('purchases', false),
				'userFormat'  	=> array('userFormat', false)
		);

		return $sortable_columns;
	}


	function column_fileTitle($item)
	{
		$actions = array(
				'edit'      => sprintf('<a href="'.GOURL_ADMIN.GOURL.'file&id='.$item->fileID.'">'.__('Edit', GOURL).'</a>',$_REQUEST['page'],'edit',$item->fileID),
				'delete'    => sprintf('<a href="'.GOURL_ADMIN.GOURL.'file&id='.$item->fileID.'&gourlcryptocoin='.$this->coin_names[$item->defCoin].'&gourlcryptolang='.$item->lang.'&preview=true">'.__('Preview', GOURL).'</a>',$_REQUEST['page'],'preview',$item->fileID),
		);

		return sprintf('%1$s %2$s', $item->fileTitle, $this->row_actions($actions) );
	}


	function prepare_items()
	{
		global $wpdb, $_wp_column_headers;

		$screen = get_current_screen();

		$query = "SELECT * FROM crypto_files WHERE 1 ".$this->search;

		$orderby = !empty($_GET["orderby"]) ? esc_sql(substr($_GET["orderby"], 0, 30)) : 'ASC';
		$order = !empty($_GET["order"]) ? esc_sql(substr($_GET["order"], 0, 30)) : '';
		if(!empty($orderby) & !empty($order)) { $query.=' ORDER BY '.$orderby.' '.$order; }
		else $query.=' ORDER BY updatetime DESC';


		$totalitems = $wpdb->query($query);

		$paged = !empty($_GET["paged"]) ? esc_sql(substr($_GET["paged"], 0, 30)) : '';

		if(empty($paged) || !is_numeric($paged) || $paged<=0 ) { $paged=1; }

		$totalpages = ceil($totalitems/$this->rec_per_page);

		if(!empty($paged) && !empty($this->rec_per_page))
		{
			$offset=($paged-1)*$this->rec_per_page;
			$query.=' LIMIT '.(int)$offset.','.(int)$this->rec_per_page;
		}

		$this->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $this->rec_per_page,
		) );

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $wpdb->get_results($query);
	}

}
// end class gourl_table_files









/********************************************************************/








// XVII. TABLE2 - "All Paid Products"  WP_Table Class
// ----------------------------------------

class gourl_table_products extends WP_List_Table
{
	private $coin_names = array();
	private $languages	= array();

	private $search 		= '';
	private $rec_per_page	= 20;

	function __construct($search = '', $rec_per_page = 20)
	{
		$this->coin_names 	= gourlclass::coin_names();
		$this->languages	= gourlclass::languages();
		$this->search 		= $search;
		$this->rec_per_page = $rec_per_page;
		if ($this->rec_per_page < 5) $this->rec_per_page = 20;


		global $status, $page;
		parent::__construct( array(
				'singular'=> 'mylist',
				'plural' => 'mylists',
				'ajax'    => false

		) );
	}

	function column_default( $item, $column_name )
	{
		$tmp = "";
		switch( $column_name )
		{
			case 'active':
			case 'defShow':
			case 'emailUser':
			case 'emailAdmin':
				$tmp = gourl_checked_image($item->$column_name);
				break;

			case 'priceUSD':
				if ($item->$column_name > 0)
				{
					$num = gourl_number_format($item->$column_name, 2);
					$tmp = $num . ' ' . __('USD', GOURL);
				}
				break;

			case 'priceCoin':
				if ($item->$column_name > 0 && $item->priceUSD <= 0)
				{
					$num = gourl_number_format($item->$column_name, 4);
					$tmp = $num . ' ' . $item->priceLabel;
				}
				break;

			case 'paymentCnt':
				$tmp = ($item->$column_name > 0) ? '<a href="'.GOURL_ADMIN.GOURL.'payments&s=product_'.$item->productID.'">'.$item->$column_name.'</a>' : '-';
				break;

			case 'defCoin':
				if ($item->$column_name)
				{
					$val = $this->coin_names[$item->$column_name];
					$tmp = "<a href='".GOURL_ADMIN.GOURL."products&s=".$val."'><img width='40' alt='".$val."' title='".__('Show this coin transactions only', GOURL)."' src='".plugins_url('/images/'.$val.'.png', __FILE__)."' border='0'></a>";
				}
				break;

			case 'lang':
				$tmp = $this->languages[$item->$column_name];
				break;

			case 'purchases':
				$tmp = ($item->$column_name == 0) ?  __('unlimited', GOURL) : $item->$column_name . ' ' . __('copies', GOURL);
				break;

			case 'paymentTime':
			case 'updatetime':
			case 'createtime':
				$tmp = ($item->$column_name && date("Y", strtotime($item->$column_name)) > 2010) ? date("d M Y, H:i A", strtotime($item->$column_name)) : '-';
				break;

			default:
				$tmp = $item->$column_name;
				break;
		}

		return $tmp;
	}




	function get_columns()
	{
		$columns = array(
				'productID'  	=> __('ID', GOURL),
				'active'  		=> __('Acti-ve?', GOURL),
				'productTitle' 	=> __('Title', GOURL),
				'priceUSD'  	=> __('Price in USD', GOURL),
				'priceCoin'  	=> __('Price in Coins', GOURL),
				'paymentCnt'  	=> __('Total Sold', GOURL),
				'paymentTime'  	=> __('Latest Received Payment, GMT', GOURL),
				'updatetime'  	=> __('Record Updated, GMT', GOURL),
				'createtime'  	=> __('Record Created, GMT', GOURL),
				'expiryPeriod'  => __('Payment Expiry Period', GOURL),
				'defCoin'  		=> __('Default Payment Box Coin', GOURL),
				'defShow'  		=> __('Default Coin only?', GOURL),
				'lang'  		=> __('Default Box Language', GOURL),
				'purchases'  	=> __('Purchase Limit', GOURL),
				'emailUser'  	=> __('Email to Buyer?', GOURL),
				'emailAdmin'  	=> __('Email to Seller?', GOURL)
		);
		return $columns;
	}


	function get_sortable_columns()
	{
		$sortable_columns = array
		(
				'productID'  		=> array('productID', false),
				'active'  		=> array('active', true),
				'productTitle' 	=> array('productTitle', false),
				'priceUSD'  	=> array('priceUSD', false),
				'priceCoin'  	=> array('priceCoin', false),
				'paymentCnt'  	=> array('paymentCnt', true),
				'paymentTime'  	=> array('paymentTime', true),
				'updatetime'  	=> array('updatetime', true),
				'createtime'  	=> array('createtime', true),
				'expiryPeriod'  => array('expiryPeriod', false),
				'defCoin'  		=> array('defCoin', false),
				'defShow'  		=> array('defShow', true),
				'lang'  		=> array('lang', false),
				'purchases'  	=> array('purchases', false),
				'emailUser'  	=> array('emailUser', true),
				'emailAdmin'  	=> array('emailAdmin', true)
		);

		return $sortable_columns;
	}


	function column_productTitle($item)
	{
		$actions = array(
				'edit'      => sprintf('<a href="'.GOURL_ADMIN.GOURL.'product&id='.$item->productID.'">'.__('Edit', GOURL).'</a>',$_REQUEST['page'],'edit',$item->productID),
				'delete'    => sprintf('<a href="'.GOURL_ADMIN.GOURL.'product&id='.$item->productID.'&gourlcryptocoin='.$this->coin_names[$item->defCoin].'&gourlcryptolang='.$item->lang.'&preview=true">'.__('Preview', GOURL).'</a>',$_REQUEST['page'],'preview',$item->productID),
		);

		return sprintf('%1$s %2$s', $item->productTitle, $this->row_actions($actions) );
	}


	function prepare_items()
	{
		global $wpdb, $_wp_column_headers;

		$screen = get_current_screen();

		$query = "SELECT * FROM crypto_products WHERE 1 ".$this->search;

		$orderby = !empty($_GET["orderby"]) ? esc_sql(substr($_GET["orderby"], 0, 30)) : 'ASC';
		$order = !empty($_GET["order"]) ? esc_sql(substr($_GET["order"], 0, 30)) : '';
		if(!empty($orderby) & !empty($order)) { $query.=' ORDER BY '.$orderby.' '.$order; }
		else $query.=' ORDER BY updatetime DESC';


		$totalitems = $wpdb->query($query);

		$paged = !empty($_GET["paged"]) ? esc_sql(substr($_GET["paged"], 0, 30)) : '';

		if(empty($paged) || !is_numeric($paged) || $paged<=0 ) { $paged=1; }

		$totalpages = ceil($totalitems/$this->rec_per_page);

		if(!empty($paged) && !empty($this->rec_per_page))
		{
			$offset=($paged-1)*$this->rec_per_page;
			$query.=' LIMIT '.(int)$offset.','.(int)$this->rec_per_page;
		}

		$this->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $this->rec_per_page,
		) );

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $wpdb->get_results($query);
	}

}
// end class gourl_table_products











/********************************************************************/








// XVIII. TABLE3 - "All Payments"  WP_Table Class
// ----------------------------------------
class gourl_table_payments extends WP_List_Table
{
	private $coin_names = array();

	private $search 		= '';
	private $rec_per_page	= 20;
	private $file_columns 	= false;

	function __construct($search = '', $rec_per_page = 20, $file_columns = false)
	{

		$this->coin_names 	= gourlclass::coin_names();

		$this->search = $search;
		$this->file_columns = $file_columns;
		$this->rec_per_page = $rec_per_page;
		if ($this->rec_per_page < 5) $this->rec_per_page = 20;


		global $status, $page;
		parent::__construct( array(
				'singular'=> 'mylist',
				'plural' => 'mylists',
				'ajax'    => false
				)
			);

		include_once(plugin_dir_path( __FILE__ )."includes/cryptobox.class.php");

	}

	function column_default( $item, $column_name )
	{
		global $gourl;

		$tmp = "";
		switch( $column_name )
		{
			case 'unrecognised':
			case 'txConfirmed':
			case 'processed':
				if (!($column_name == "processed" && strpos($item->orderID, "file_") !== 0))
				{
					$title = "";
					if ($column_name=='processed') $title = "title='". (($item->$column_name) ? __('User already downloaded this file from your website', GOURL) : __('User not downloaded this file yet', GOURL))."'";
					$tmp = gourl_checked_image($item->$column_name);
				}
				break;

			case 'boxID':
				if ($item->$column_name)
				{
					$tmp = "<a title='".__('View Statistics', GOURL)."' href='https://gourl.io/view/coin_boxes/".$item->$column_name."/statistics.html' target='_blank'>".$item->$column_name."</a>";
				}
				break;


			case 'orderID':
				if ($item->$column_name)
				{
					$url = "";
					if (strpos($item->$column_name, "product_") === 0) 			$url = GOURL_ADMIN.GOURL."product&id=".substr($item->$column_name, 8)."&gourlcryptocoin=".$this->coin_names[$item->coinLabel]."&preview=true";
					elseif (strpos($item->$column_name, "file_") === 0) 		$url = GOURL_ADMIN.GOURL."file&id=".substr($item->$column_name, 5)."&gourlcryptocoin=".$this->coin_names[$item->coinLabel]."&preview=true";
					elseif ($item->$column_name == "payperview") 				$url = GOURL_ADMIN.GOURL."payperview";
					elseif (strpos($item->$column_name, "membership") === 0)	$url = GOURL_ADMIN.GOURL."paypermembership";
					elseif (strpos($item->$column_name, "gourlwoocommerce") === 0) 	$item->$column_name = __('woocommerce', GOURL).", <a class='gourlnowrap' href='".admin_url("post.php?post=".str_replace("gourlwoocommerce.order", "", $item->$column_name)."&action=edit")."'>".__('order', GOURL)." ".str_replace("gourlwoocommerce.order", "", $item->$column_name)."</a>";
					elseif (strpos($item->$column_name, "gourlwpecommerce") === 0) 	$item->$column_name = __('wp ecommerce', GOURL).", <a class='gourlnowrap' href='".admin_url("index.php?page=wpsc-purchase-logs&c=item_details&id=".str_replace("gourlwpecommerce.order", "", $item->$column_name)."&action=edit")."'>".__('order', GOURL)." ".str_replace("gourlwpecommerce.order", "", $item->$column_name)."</a>";
					elseif (strpos($item->$column_name, "gourljigoshop") === 0) 	$item->$column_name = __('jigoshop', GOURL).", <a class='gourlnowrap' href='".admin_url("post.php?post=".$gourl->left($gourl->right($item->$column_name, ".order"), "_")."&action=edit")."'>".__('order', GOURL)." ".str_replace("_", " (", str_replace("gourljigoshop.order", "", $item->$column_name)).")"."</a>";
					elseif (strpos($item->$column_name, "gourlappthemes") === 0)
					{
						$escrow = (strpos($item->$column_name, "gourlappthemes.escrow") === 0) ? true : false;
						$item->$column_name = __('appthemes', GOURL).", <a class='gourlnowrap' href='".admin_url("post.php?post=".str_replace(array( "gourlappthemes.order", "gourlappthemes.escrow"), array("", ""), $item->$column_name)."&action=edit")."'>".($escrow?__('escrow', GOURL):__('order', GOURL))." ".str_replace(array( "gourlappthemes.order", "gourlappthemes.escrow"), array("", ""), $item->$column_name)."</a>";
					}
					elseif (strpos($item->$column_name, "gourlmarketpress") === 0) 	$item->$column_name = __('marketpress', GOURL).", <a class='gourlnowrap' href='".admin_url("edit.php?post_type=product&page=marketpress-orders&s=".str_replace("gourlmarketpress.", "", $item->$column_name))."'>".__('order', GOURL)." ".str_replace("gourlmarketpress.", "", $item->$column_name)."</a>";
					elseif (strpos($item->$column_name, "gourlpmpro") === 0) 		$item->$column_name = __('pmpro', GOURL).", <a class='gourlnowrap' href='".admin_url("admin.php?page=pmpro-orders&order=".$gourl->left($gourl->right($item->$column_name, ".order"), "_"))."'>".__('order', GOURL)." ".str_replace("gourlpmpro.order", "", $item->$column_name)."</a>";
					elseif (strpos($item->$column_name, "gourlgive") === 0) 		$item->$column_name = __('give', GOURL).", <a class='gourlnowrap' href='".admin_url("edit.php?post_type=give_forms&page=give-payment-history&view=view-order-details&id=".$gourl->left($gourl->right($item->$column_name, ".donation"), "_"))."'>".__('donation', GOURL)." ".str_replace("gourlgive.donation", "", $item->$column_name)."</a>";
					elseif (strpos($item->$column_name, "gourledd") === 0) 		$item->$column_name = __('edd', GOURL).", <a class='gourlnowrap' href='".admin_url("edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=".$gourl->left($gourl->right($item->$column_name, ".order"), "_"))."'>".__('order', GOURL)." ".str_replace("gourledd.order", "", $item->$column_name)."</a>";
					else	$item->$column_name = str_replace(".", ", ", str_replace("gourl", "", $item->$column_name));

					$tmp = ($url) ? "<a href='".$url."'>".$item->$column_name."</a>" : $item->$column_name;
				}
				break;


			case 'userID':
				if ($item->$column_name)
				{
					$tmp = (strpos($item->$column_name, "user") === 0) ? gourl_userdetails($item->$column_name) : __('Guest', GOURL);
				}
				elseif ($item->unrecognised) $tmp = "? <small>".__('wrong paid amount', GOURL)."</small>";

				break;


			case 'amountUSD':
				$num = gourl_number_format($item->$column_name, 8);
				$tmp = $num . ' ' . __('USD', GOURL);
				break;


			case 'amount':
				$num = gourl_number_format($item->$column_name, 8);
				$tmp = $num . ' ' . $item->coinLabel;
				break;


			case 'coinLabel':
				if ($item->$column_name)
				{
					$val = $this->coin_names[$item->$column_name];
					$tmp = "<a href='".GOURL_ADMIN.GOURL."payments&s=".$val."'><img width='40' alt='".$val."' title='".__('Show this coin transactions only', GOURL)."' src='".plugins_url('/images/'.$val.'.png', __FILE__)."' border='0'></a>";
				}
				break;


			case 'countryID':
				if ($item->$column_name)
				{
					$tmp = "<a title='".__('Show Only Visitors from this Country', GOURL)."' href='".GOURL_ADMIN.GOURL."payments&s=".$item->$column_name."'><img width='16' border='0' style='margin-right:7px' alt='".$item->$column_name."' src='".plugins_url('/images/flags/'.$item->$column_name.'.png', __FILE__)."' border='0'></a>" . get_country_name($item->$column_name);
				}
				break;


			case 'txID':
				if ($item->$column_name) $tmp = "<a title='".__('Transaction Details', GOURL)." - ".$item->$column_name."' href='".$gourl->blockexplorer_tr_url($item->$column_name, $this->coin_names[$item->coinLabel])."' target='_blank'>".$item->$column_name."</a>";
				break;


			case 'addr':
				if ($item->$column_name) $tmp = "<a title='".__('Wallet Details', GOURL)." - ".$item->$column_name."' href='".$gourl->blockexplorer_addr_url($item->$column_name, $this->coin_names[$item->coinLabel])."' target='_blank'>".$item->$column_name."</a>";
				break;


			case 'txDate':
			case 'txCheckDate':
			case 'recordCreated':
			case 'processedDate':
				if (!($column_name == "processedDate" && strpos($item->orderID, "file_") !== 0))
				{
					$tmp = ($item->$column_name && date("Y", strtotime($item->$column_name)) > 2010) ? (date("d M Y", strtotime($item->$column_name)).", <span class='gourlnowrap'>".date("H:i A", strtotime($item->$column_name))."</span>") : '-';
				}
				break;

			default:
				$tmp = $item->$column_name;
				break;
		}

		return $tmp;
	}




	function get_columns()
	{
		$columns = array(
					'paymentID'  		=> __('Payment ID', GOURL),
					'boxID'				=> __('Payment Box ID', GOURL),
					'coinLabel'			=> __('Coin', GOURL),
					'orderID'			=> __('Order ID', GOURL),
					'amount'			=> __('Paid Amount', GOURL),
					'amountUSD'			=> __('Approximate in USD', GOURL),
					'unrecognised'		=> __('Unrecogn. Payment?', GOURL),
					'userID'			=> __('User ID', GOURL),
					'txDate'			=> __('Transaction Time, GMT', GOURL),
					'countryID'			=> __('User Location', GOURL),
					'txConfirmed'		=> __('Confirmed Payment?', GOURL),
					'processed'			=> __('User Downl. File?', GOURL),
					'processedDate'		=> __('File Downloaded Time, GMT', GOURL),
					'txID'				=> __('Transaction ID', GOURL),
					'addr'				=> __('Your GoUrl Wallet Address', GOURL)
		);

		if (!$this->file_columns)
		{
			unset($columns['processed']);
			unset($columns['processedDate']);
		}

		return $columns;
	}


	function get_sortable_columns()
	{
		$sortable_columns = array
		(
				'paymentID'  		=> array('paymentID', true),
				'boxID'				=> array('boxID', false),
				'boxType'			=> array('boxType', false),
				'orderID'			=> array('orderID', false),
				'userID'			=> array('userID', false),
				'countryID'			=> array('countryID', true),
				'coinLabel'			=> array('coinLabel', false),
				'amount'			=> array('amount', false),
				'amountUSD'			=> array('amountUSD', true),
				'unrecognised'		=> array('unrecognised', false),
				'txDate'			=> array('txDate', true),
				'txConfirmed'		=> array('txConfirmed', true),
				'addr'				=> array('addr', false),
				'txID'				=> array('txID', false),
				'txCheckDate'		=> array('txCheckDate', true),
				'processed'			=> array('processed', true),
				'processedDate'		=> array('processedDate', true),
				'recordCreated'		=> array('recordCreated', true)
		);

		return $sortable_columns;
	}



	function column_txConfirmed($item)
	{
		$tmp = gourl_checked_image($item->txConfirmed);

		if ($item->txConfirmed || !$item->userID) return $tmp;

		$actions = array(
				'edit' => sprintf('<a title="'.__('Re-check Payment Status', GOURL).'" href="'.GOURL_ADMIN.GOURL.'payments&b='.$item->paymentID.'">'.__('Check', GOURL).'</a>',$_REQUEST['page'],'edit',$item->paymentID)
		);

		return sprintf('%1$s %2$s', $tmp, $this->row_actions($actions) );
	}



	function prepare_items()
	{
		global $wpdb, $_wp_column_headers;

		$screen = get_current_screen();

		$query = "SELECT * FROM crypto_payments WHERE 1 ".$this->search;

		$orderby = !empty($_GET["orderby"]) ? esc_sql(substr($_GET["orderby"], 0, 30)) : 'ASC';
		$order = !empty($_GET["order"]) ? esc_sql(substr($_GET["order"], 0, 30)) : '';
		if(!empty($orderby) & !empty($order)) { $query.=' ORDER BY '.$orderby.' '.$order; }
		else $query.=' ORDER BY paymentID DESC';


		$totalitems = $wpdb->query($query);

		$paged = !empty($_GET["paged"]) ? esc_sql(substr($_GET["paged"], 0, 30)) : '';

		if(empty($paged) || !is_numeric($paged) || $paged<=0 ) { $paged=1; }

		$totalpages = ceil($totalitems/$this->rec_per_page);

		if(!empty($paged) && !empty($this->rec_per_page))
		{
			$offset=($paged-1)*$this->rec_per_page;
			$query.=' LIMIT '.(int)$offset.','.(int)$this->rec_per_page;
		}

		$this->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $this->rec_per_page,
		) );

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $wpdb->get_results($query);
	}

}
// end class gourl_table_payments





/********************************************************************/








// XVIV. TABLE4 - "All Premium Users"  WP_Table Class
// ----------------------------------------
class gourl_table_premiumusers extends WP_List_Table
{
	private $search 		= '';
	private $rec_per_page	= 20;

	function __construct($search = '', $rec_per_page = 20)
	{

		$this->search 		= $search;
		$this->rec_per_page = $rec_per_page;
		if ($this->rec_per_page < 5) $this->rec_per_page = 20;


		global $status, $page;
		parent::__construct( array(
				'singular'=> 'mylist',
				'plural' => 'mylists',
				'ajax'    => false

		) );
	}

	function column_default( $item, $column_name )
	{
		$tmp = "";
		switch( $column_name )
		{
			case 'disabled':
				$tmp = gourl_checked_image($item->$column_name);
				break;

			case 'userID':
				if ($item->$column_name)
				{
					$tmp = (strpos($item->$column_name, "user") === 0) ? gourl_userdetails($item->$column_name, false) : __('Guest', GOURL);
				}
				elseif ($item->unrecognised) $tmp = "? <small>".__('wrong paid amount', GOURL)."</small>";

				break;

			case 'paymentID':
				if ($item->$column_name)
				{
					$tmp = "<a href='".GOURL_ADMIN.GOURL."payments&s=payment_".$item->$column_name."'>".$item->$column_name."</a>";
				}
				else $tmp = __('manually', GOURL);
				break;

			case 'startDate':
			case 'endDate':
			case 'recordCreated':
				$tmp = ($item->$column_name && date("Y", strtotime($item->$column_name)) > 2010) ? date("d M Y, H:i A", strtotime($item->$column_name)) : '-';
				break;

			default:
				$tmp = $item->$column_name;
				break;
		}

		return $tmp;
	}



	function get_columns()
	{
		$columns = array(
				'membID'  		=> __('ID', GOURL),
				'userID'  		=> __('User', GOURL),
				'paymentID'  	=> __('Payment ID', GOURL),
				'startDate' 	=> __('Premium Membership Start, GMT', GOURL),
				'endDate'  		=> __('Premium Membership End, GMT', GOURL),
				'disabled'  	=> __('Premium Memb. Disabled?', GOURL),
				'recordCreated'	=> __('Record Created, GMT', GOURL)
		);
		return $columns;
	}


	function get_sortable_columns()
	{
		$sortable_columns = array
		(
				'membID'  		=> array('membID', false),
				'userID'  		=> array('userID', false),
				'paymentID'  	=> array('paymentID', false),
				'startDate' 	=> array('startDate', true),
				'endDate'  		=> array('endDate', true),
				'disabled'  	=> array('disabled', false),
				'recordCreated' => array('recordCreated', true)
		);

		return $sortable_columns;
	}


	function column_userID($item)
	{
		$tmp = gourl_userdetails($item->userID, false);

		$enabled = ($item->disabled) ? false : true;

		$actions = array(
			'edit'  	=> '<a onclick="if (confirm(\''.($enabled?__('Are you sure you want to DISABLE Premium Membership?', GOURL):__('Are you sure you want to ENABLE Premium Membership?', GOURL)).'\')) location.href=\''.GOURL_ADMIN.GOURL.($enabled?'premiumuser_disable':'premiumuser_enable').'&id='.$item->membID.'\'; else return false;" href="#a">'.($enabled?__('Disable', GOURL):__('Enable', GOURL)).'</a>',
			'delete'	=> '<a onclick="if (confirm(\''.__('Are you sure you want to DELETE this record?', GOURL).'\')) location.href=\''.GOURL_ADMIN.GOURL.'premiumuser_delete&id='.$item->membID.'\'; else return false;" href="#a">'.__('Delete', GOURL).'</a>',
			'download'	=> '<a href="'.admin_url('user-edit.php?user_id='.$item->userID).'">'.__('Profile', GOURL).'</a>'
		);

		if ($item->paymentID > 0) unset($actions['delete']);

		return sprintf('%1$s %2$s', $tmp, $this->row_actions($actions) );
	}





	function prepare_items()
	{
		global $wpdb, $_wp_column_headers;

		$screen = get_current_screen();

		$query = "SELECT * FROM crypto_membership WHERE 1 ".$this->search;

		$orderby = !empty($_GET["orderby"]) ? esc_sql(substr($_GET["orderby"], 0, 30)) : 'ASC';
		$order = !empty($_GET["order"]) ? esc_sql(substr($_GET["order"], 0, 30)) : '';
		if(!empty($orderby) & !empty($order)) { $query.=' ORDER BY '.$orderby.' '.$order; }
		else $query.=' ORDER BY recordCreated DESC';


		$totalitems = $wpdb->query($query);

		$paged = !empty($_GET["paged"]) ? esc_sql(substr($_GET["paged"], 0, 30)) : '';

		if(empty($paged) || !is_numeric($paged) || $paged<=0 ) { $paged=1; }

		$totalpages = ceil($totalitems/$this->rec_per_page);

		if(!empty($paged) && !empty($this->rec_per_page))
		{
			$offset=($paged-1)*$this->rec_per_page;
			$query.=' LIMIT '.(int)$offset.','.(int)$this->rec_per_page;
		}

		$this->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $this->rec_per_page,
		) );

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $wpdb->get_results($query);
	}

}
// end class gourl_table_premiumusers


/*
 *  XX.
*/
function gourl_action_links($links, $file)
{
	static $this_plugin;

	if (false === isset($this_plugin) || true === empty($this_plugin)) {
		$this_plugin = GOURL_BASENAME;
	}

	if ($file == $this_plugin) {
		$payments_link = '<a href="'.admin_url('admin.php?page='.GOURL.'payments').'">'.__( 'All Payments', GOURL ).'</a>';
		$unrecognised_link = '<a href="'.admin_url('admin.php?page='.GOURL.'payments&s=unrecognised').'">'.__( 'Unrecognised', GOURL ).'</a>';
		$settings_link = '<a href="'.admin_url('admin.php?page='.GOURL).'">'.__( 'Summary', GOURL ).'</a>';
		array_unshift($links, $unrecognised_link);
		array_unshift($links, $payments_link);
		array_unshift($links, $settings_link);
	}
	return $links;
}



/*
 *  XXI.
*/
function gourl_load_textdomain()
{
	load_plugin_textdomain( GOURL, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}



/*
 *  XXII.
*/
if (!function_exists('has_shortcode') && version_compare(get_bloginfo('version'), "3.6") < 0)
{
	function has_shortcode( $content, $tag ) {
		if ( false === strpos( $content, '[' ) ) {
			return false;
		}

		preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
		if ( empty( $matches ) )
			return false;

		foreach ( $matches as $shortcode ) {
			if ( $tag === $shortcode[2] ) {
				return true;
			} elseif ( ! empty( $shortcode[5] ) && has_shortcode( $shortcode[5], $tag ) ) {
				return true;
			}
		}

		return false;
	}
}



/*
 *	XXIII. Get URL Data
 */
function gourl_get_url( $url, $timeout = 20, $ignore_httpcode = false )
{
    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
    curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:73.0) Gecko/20100101 Firefox/73.0");
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
    $data 		= curl_exec($ch);
    $httpcode 	= curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return (($httpcode>=200 && $httpcode<300) || $ignore_httpcode) ? $data : false;
}



/*
 *	XXV. Convert USD to AUD, EUR to GBP, etc using live exchange rates websites
 *  Update interval in hours; default 1 hour
 */
function gourl_convert_currency($from_Currency, $to_Currency, $amount, $interval = 1, $error_info = false)
{
    global $gourl;

    $error  = "";
    $currencyconverterapi_key = $gourl->currencyconverterapi_key();

    $from_Currency = trim(strtoupper(urlencode($from_Currency)));
    $to_Currency   = trim(strtoupper(urlencode($to_Currency)));

    if ($from_Currency == "TRL") $from_Currency = "TRY"; // fix for Turkish Lyra
    if ($from_Currency == "ZWD") $from_Currency = "ZWL"; // fix for Zimbabwe Dollar
    if ($from_Currency == "RM")  $from_Currency = "MYR"; // fix for Malaysian Ringgit
    if ($from_Currency == "XBT") $from_Currency = "BTC"; // fix for Bitcoin
    if ($to_Currency   == "XBT") $to_Currency   = "BTC"; // fix for Bitcoin

    if ($from_Currency == "RIAL") $from_Currency = "IRR"; // fix for Iranian Rial
    if ($from_Currency == "IRT") { $from_Currency = "IRR"; $amount = $amount * 10; } // fix for Iranian Toman; 1IRT = 10IRR


    $key 	= GOURL.'_exchange_'.preg_replace("/[^A-Za-z0-9]+/", "", $currencyconverterapi_key).'_'.$from_Currency.'_'.$to_Currency;



    // a. data from buffer; update exchange rate one time per 1 hour
    // ----------------------------
    $arr = get_option($key);
    if ($arr && isset($arr["price"]) && $arr["price"] > 0 && isset($arr["time"]) && ($arr["time"] + $interval*60*60) > strtotime("now"))
    {
        $total = $arr["price"]*$amount;
        if ($to_Currency=="BTC" || $total<0.01) $total = sprintf('%.5f', round($total, 5));
        else $total = round($total, 2);
        if ($total == 0) $total = sprintf('%.5f', 0.00001);

        if (isset($arr["error"])) $error = $arr["error"];
        if ($error_info) $total = array("val" => $total, "error" => $error);
        return $total;

    }



    $val = 0;
    if ($from_Currency == $to_Currency)  $val = 1;



    // b. get BTC rates
    // ----------------
    $bitcoinUSD = 0;
    if (!$val && ($from_Currency == "BTC" || $to_Currency == "BTC"))
    {
        $aval = array ('BTC', 'USD', 'AUD', 'BRL', 'CAD', 'CHF', 'CLP', 'CNY', 'DKK', 'EUR', 'GBP', 'HKD', 'INR', 'ISK', 'JPY', 'KRW', 'NZD', 'PLN', 'RUB', 'SEK', 'SGD', 'THB', 'TWD');
        if (in_array($from_Currency, $aval) && in_array($to_Currency, $aval))
        {
            $data = json_decode(gourl_get_url("https://blockchain.info/ticker"), true);

            // rates BTC->...
            $rates = array("BTC" => 1);
            if ($data) foreach($data as $k => $v) $rates[$k] = ($v["15m"] > 1000) ? round($v["15m"]) : ($v["last"] > 1000 ? round($v["last"]) : 0);
            // convert BTC/USD, EUR/BTC, etc.
            if (isset($rates[$to_Currency]) && $rates[$to_Currency] > 0 && isset($rates[$from_Currency]) && $rates[$from_Currency] > 0) $val = $rates[$to_Currency] / $rates[$from_Currency];
            if (isset($rates["USD"]) && $rates["USD"] > 0) $bitcoinUSD = $rates["USD"];
        }

        if (!$val && $bitcoinUSD < 1000)
        {
            $data = json_decode(gourl_get_url("https://www.bitstamp.net/api/ticker/"), true);
            if (isset($data["last"]) && isset($data["volume"]) && $data["last"] > 1000) $bitcoinUSD = round($data["last"]);
        }

        if ($from_Currency == "BTC" && $to_Currency == "USD" && $bitcoinUSD > 0) $val  =  $bitcoinUSD;
        if ($from_Currency == "USD" && $to_Currency == "BTC" && $bitcoinUSD > 0) $val  =  1 / $bitcoinUSD;
    }



    // c. get rates from European Central Bank https://www.ecb.europa.eu
    // ----------------
    $aval = array ('EUR', 'USD', 'JPY', 'BGN', 'CZK', 'DKK', 'GBP', 'HUF', 'PLN', 'RON', 'SEK', 'CHF', 'ISK', 'NOK', 'HRK', 'RUB', 'TRY', 'AUD', 'BRL', 'CAD', 'CNY', 'HKD', 'IDR', 'ILS', 'INR', 'KRW', 'MXN', 'MYR', 'NZD', 'PHP', 'SGD', 'THB', 'ZAR');
    if ($bitcoinUSD > 0) $aval[] = "BTC";
    if (!$val && in_array($from_Currency, $aval) && in_array($to_Currency, $aval))
    {
        $xml = simplexml_load_string(gourl_get_url("https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml"));
        $json = json_encode($xml);
        $data = json_decode($json,TRUE);

        if (isset($data["Cube"]["Cube"]))
        {
            $data = $data["Cube"]["Cube"];
            $time = $data["@attributes"]["time"];

            // rates EUR->...
            $rates = array("EUR" => 1);
            foreach($data["Cube"] as $v) $rates[$v["@attributes"]["currency"]] = floatval($v["@attributes"]["rate"]);
            if ($bitcoinUSD > 0 && $rates["USD"] > 0) $rates["BTC"] = $rates["USD"] / $bitcoinUSD;

            // convert USD/JPY, EUR/GBP, etc.
            if ($rates[$to_Currency] > 0 && $rates[$from_Currency] > 0) $val = $rates[$to_Currency] / $rates[$from_Currency];
        }
    }


    /* d. get rates from
     https://free.currconv.com/api/v7/convert?q=BTC_EUR&compact=ultra&apiKey=sample-api-key
     https://prepaid.currconv.com/api/v7/convert?q=BTC_EUR&compact=ultra&apiKey=sample-api-key
     https://api.currconv.com/api/v7/convert?q=BTC_EUR&compact=ultra&apiKey=sample-api-key
     ---------------- */
    if (!$val)
    {
        $key2 	= $from_Currency.'_'.$to_Currency;
        $data = json_decode(gourl_get_url("https://free.currconv.com/api/v7/convert?q=".$key2."&compact=ultra&apiKey=".$currencyconverterapi_key, 10, TRUE), TRUE);
        if (isset($data[$key2]) && $data[$key2] > 0) $val = $data[$key2];
        elseif(isset($data["error"]))
        {
            $error = $data["error"] . "<br>-> <a style='text-decoration: underline;' href='".admin_url('admin.php?page=gourlsettings#'.GOURL.'boxlogo2')."'>" . __("Please check/save your free currencyconverterapi.com key on GoUrl plugin Settings page", GOURL )."</a>";

            // try prepaid key
            $data = json_decode(gourl_get_url("https://prepaid.currconv.com/api/v7/convert?q=".$key2."&compact=ultra&apiKey=".$currencyconverterapi_key, 10, TRUE), TRUE);
            if (isset($data[$key2]) && $data[$key2] > 0) { $val = $data[$key2]; $error = ""; }
            else {
                // try premium key
                $data = json_decode(gourl_get_url("https://api.currconv.com/api/v7/convert?q=".$key2."&compact=ultra&apiKey=".$currencyconverterapi_key, 10, TRUE), TRUE);
                if (isset($data[$key2]) && $data[$key2] > 0) { $val = $data[$key2]; $error = ""; }
            }
        }
    }



    // result; save exchange rate on next $interval
    // ------------
    if ($val > 0)
    {
        $arr = array("price" => $val, "error" => $error, "time" => strtotime("now"));
        update_option($key, $arr);

        $total = $val*$amount;
        if ($to_Currency=="BTC" || $total<0.01) $total = sprintf('%.5f', round($total, 5));
        else $total = round($total, 2);
        if ($total == 0) $total = sprintf('%.5f', 0.00001);
        if ($error_info) $total = array("val" => $total, "error" => $error);
        return $total;
    }

    elseif ($arr && isset($arr["price"]) && $arr["price"] > 0 && isset($arr["time"]) && ($arr["time"] + 5*60*60) > strtotime("now"))
    {
        $total = $arr["price"]*$amount;
        if ($to_Currency=="BTC" || $total<0.01) $total = sprintf('%.5f', round($total, 5));
        else $total = round($total, 2);
        if ($total == 0) $total = sprintf('%.5f', 0.00001);
        if ($error_info) $total = array("val" => $total, "error" => $error);
        return $total;
    }

    // no valid result
    $total = 0;
    if ($error_info) $total = array("val" => $total, "error" => $error);

    return $total;
}



/*
 *	XXV. Get Live Rates for BTC-USD, BTC-EUR, BTC-AUD, etc.
 *  Update interval in hours; default 1 hour
 */
function gourl_bitcoin_live_price ($currency, $interval = 1)
{

    $price 	= 0;
    $min 	= 200;
    $key 	= GOURL.'_exchange_BTC_'.$currency;

    if (!in_array($currency, array_keys(json_decode(GOURL_RATES, true)))) return 0;


    // return exchange rate if data less then 1hour; update exchange rate one time per 1 hour
    $arr2 = get_option($key);
    if ($arr2 && isset($arr2["price"]) && $arr2["price"] > 0 && isset($arr2["time"]) && ($arr2["time"] + $interval*60*60) > strtotime("now")) return $arr2["price"];



    // a. bitstamp.net
    if ($currency == "USD")
    {
        $data = gourl_get_url("https://www.bitstamp.net/api/ticker/");
        $arr = json_decode($data, true);
        if (isset($arr["last"]) && isset($arr["volume"]) && $arr["last"] > $min) $price = round($arr["last"]);
    }

    // b. blockchain.info
    if (!$price)
    {
        $data = gourl_get_url("https://blockchain.info/ticker");
        $arr = json_decode($data, true);
        if (isset($arr[$currency]["15m"]) && $arr[$currency]["15m"] > $min) $price = round($arr[$currency]["15m"]);
        if (!$price && isset($arr[$currency]["last"]) && $arr[$currency]["last"] > $min) $price = round($arr[$currency]["last"]);
    }



    // save exchange rate on next 1hour
    if ($price > 0)
    {
        $arr2 = array("price" => $price, "time" => strtotime("now"));
        update_option($key, $arr2);

        return $price;
    }
    // temporary connection problems with bitstamp, blockchain,info
    elseif ($arr2 && isset($arr2["price"]) && $arr2["price"] > 0 && isset($arr2["time"]) && ($arr2["time"] + 5*60*60) > strtotime("now"))
    {
        return $arr2["price"];
    }


    return 0;
}



/*
 *	XXVI. Get Altcoins Live Rates to BTC - DASH/BTC, LTC/BTC, BCH/BTC
 *  Update interval in hours; default 1 hour
 */
function gourl_altcoin_btc_price ($altcoin, $interval = 1)
{
    global $gourl;

    $price 	= 0;
    $key 	= GOURL.'_exchange_'.$altcoin.'_BTC';

    if ($altcoin == "BTC") return 1;

    // return exchange rate if data less then 1hour; update exchange rate one time per 1 hour
    $arr2 = get_option($key);
    if ($arr2 && isset($arr2["price"]) && $arr2["price"] > 0 && isset($arr2["time"]) && ($arr2["time"] + $interval*60*60) > strtotime("now")) return $arr2["price"];

    if ($altcoin == "SPD") return 0.00000010; // use speedcoin for tests; get free SPD - https://speedcoin.org/info/free_coins


    // A. Poloniex.com
    // -----------------
    $data = gourl_get_url('https://poloniex.com/public?command=returnTicker');
    $arr = json_decode($data, true);

    if (isset($arr["BTC_LTC"]) && $arr["BTC_LTC"]["last"] > 0)
    {
        foreach ($arr as $k => $v)
        {
            $main = $gourl->left($k, "_");
            $alt  = $gourl->right($k, "_");
            if ($alt == "BCH") 		$alt = "oldBCH"; 	// not used any more on poloniex
            if ($alt == "BCHABC") 	$alt = "BCH"; 		// using now
            if ($alt == "BCHSV") 	$alt = "BSV"; 		// using now
            if ($v["last"] > 0 && $main == "BTC" && $alt == $altcoin) $price = sprintf('%.8f', $v["last"]);
        }
    }


    // B. Bittrex.com
    // ----------------
    if (!$price)
    {
        $data = gourl_get_url('https://bittrex.com/api/v1.1/public/getmarketsummaries');
        $arr = json_decode($data, true);

        if (isset($arr["success"]) && $arr["success"] == "1" && is_array($arr["result"]))
        {
            foreach ($arr["result"] as $k => $v)
            {
                $main = $gourl->left($v["MarketName"], "-");
                $alt  = $gourl->right($v["MarketName"], "-");
                if ($alt == "BCC") 	$alt = "BCH";
                if ($v["Last"] > 0 && $main == "BTC" && $alt == $altcoin) $price = sprintf('%.8f', $v["Last"]);
            }
        }
    }


    // C. Coinexchange.io
    // -------------------

    $data = gourl_get_url('https://www.coinexchange.io/api/v1/getmarketsummaries');
    $arr = json_decode($data, true);
    $coinexchangeIds = array('FTC' => 78, 'MUE' => 280); // "MarketID":"305" from https://www.coinexchange.io/api/v1/getmarkets
    // full list $coinexchangeIds = array('LTC' => 18, 'DOGE' => 21, 'FTC' => 78, 'MUE' => 280, 'DASH' => 281, 'UNIT' => 305, 'BCH' => 1018);

    if (isset($arr["success"]) && $arr["success"] == "1" && is_array($arr["result"]))
    {
        foreach ($arr["result"] as $k => $v)
            if (in_array($v["MarketID"], $coinexchangeIds))
            {
                $main = "BTC";
                $alt  = array_search($v["MarketID"], $coinexchangeIds);
                if ($v["LastPrice"] > 0 &&  $alt == $altcoin) $price = sprintf('%.8f', $v["LastPrice"]);
            }
    }




    // save exchange rate on next 1hour
    if ($price > 0)
    {
        $arr2 = array("price" => $price, "time" => strtotime("now"));
        update_option($key, $arr2);

        return $price;
    }
    // temporary connection problems with poloniex/birrex
    elseif ($arr2 && isset($arr2["price"]) && $arr2["price"] > 0 && isset($arr2["time"]) && ($arr2["time"] + 5*60*60) > strtotime("now"))
    {
        return $arr2["price"];
    }


    return 0; 
}
