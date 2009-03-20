<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
define("APP_BUILD", 899);
define("APP_MAIL_PATH", APP_STORAGE_PATH . '/mail/');

require_once(APP_PATH . "/api/DAO.class.php");
require_once(APP_PATH . "/api/Model.class.php");
require_once(APP_PATH . "/api/Extension.class.php");

// App Scope ClassLoading
$path = APP_PATH . '/api/app/';

DevblocksPlatform::registerClasses($path . 'Bayes.php', array(
	'CerberusBayes',
));

DevblocksPlatform::registerClasses($path . 'Mail.php', array(
	'CerberusMail',
));

DevblocksPlatform::registerClasses($path . 'Parser.php', array(
	'CerberusParser',
	'CerberusParserMessage',
));

DevblocksPlatform::registerClasses($path . 'Update.php', array(
	'ChUpdateController',
));

DevblocksPlatform::registerClasses($path . 'Utils.php', array(
	'CerberusUtils',
));

/**
 * Application-level Facade
 */
class CerberusApplication extends DevblocksApplication {
	const INDEX_TICKETS = 'tickets';
		
	const VIEW_SEARCH = 'search';
	const VIEW_MAIL_WORKFLOW = 'mail_workflow';
	const VIEW_OVERVIEW_ALL = 'overview_all';
	
	const CACHE_SETTINGS_DAO = 'ch_settings_dao';
	const CACHE_HELPDESK_FROMS = 'ch_helpdesk_froms';
	
	/**
	 * @return CerberusVisit
	 */
	static function getVisit() {
		$session = DevblocksPlatform::getSessionService();
		return $session->getVisit();
	}
	
	/**
	 * @return CerberusWorker
	 */
	static function getActiveWorker() {
		$visit = self::getVisit();
		return (null != $visit) 
			? $visit->getWorker()
			: null
			;
	}
	
	static function processRequest(DevblocksHttpRequest $request, $is_ajax=false) {
		/**
		 * Override the 'update' URI since we can't count on the database 
		 * being populated from XML beforehand when /update loads it.
		 */
		if(!$is_ajax && isset($request->path[0]) && 0 == strcasecmp($request->path[0],'update')) {
			if(null != ($update_controller = new ChUpdateController(null)))
				$update_controller->handleRequest($request);
			
		} else {
			// Hand it off to the platform
			DevblocksPlatform::processRequest($request, $is_ajax);
		}
	}
	
	static function checkRequirements() {
		$errors = array();
		
		// Privileges
		
		// Make sure the temporary directories of Devblocks are writeable.
		if(!is_writeable(APP_TEMP_PATH)) {
			$errors[] = APP_TEMP_PATH ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!file_exists(APP_TEMP_PATH . "/templates_c")) {
			@mkdir(APP_TEMP_PATH . "/templates_c");
		}
		
		if(!is_writeable(APP_TEMP_PATH . "/templates_c/")) {
			$errors[] = APP_TEMP_PATH . "/templates_c/" . " is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}

		if(!file_exists(APP_TEMP_PATH . "/cache")) {
			@mkdir(APP_TEMP_PATH . "/cache");
		}
		
		if(!is_writeable(APP_TEMP_PATH . "/cache/")) {
			$errors[] = APP_TEMP_PATH . "/cache/" . " is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH)) {
			$errors[] = APP_STORAGE_PATH ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/import/fail")) {
			$errors[] = APP_STORAGE_PATH . "/import/fail/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/import/new")) {
			$errors[] = APP_STORAGE_PATH . "/import/new/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/attachments/")) {
			$errors[] = APP_STORAGE_PATH . "/attachments/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/mail/new/")) {
			$errors[] = APP_STORAGE_PATH . "/mail/new/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/mail/fail/")) {
			$errors[] = APP_STORAGE_PATH . "/mail/fail/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		// Requirements
		
		// PHP Version
		if(version_compare(PHP_VERSION,"5.1.4") >=0) {
		} else {
			$errors[] = 'Cerberus Helpdesk 4.x requires PHP 5.1.4 or later. Your server PHP version is '.PHP_VERSION;
		}
		
		// File Uploads
		$ini_file_uploads = ini_get("file_uploads");
		if($ini_file_uploads == 1 || strcasecmp($ini_file_uploads,"on")==0) {
		} else {
			$errors[] = 'file_uploads is disabled in your php.ini file. Please enable it.';
		}
		
		// File Upload Temporary Directory
		// [TODO] This isn't fatal
//		$ini_upload_tmp_dir = ini_get("upload_tmp_dir");
//		if(!empty($ini_upload_tmp_dir)) {
//		} else {
//			$errors[] = 'upload_tmp_dir is empty in your php.ini file.	Please set it.';
//		}
		
		// Memory Limit
		$memory_limit = ini_get("memory_limit");
		if ($memory_limit == '') { // empty string means failure or not defined, assume no compiled memory limits
		} else {
			$ini_memory_limit = intval($memory_limit);
			if($ini_memory_limit >= 16) {
			} else {
				$errors[] = 'memory_limit must be 16M or larger (32M recommended) in your php.ini file.  Please increase it.';
			}
		}
		
		// Extension: MySQL
		if(extension_loaded("mysql")) {
		} else {
			$errors[] = "The 'MySQL' PHP extension is required.  Please enable it.";
		}
		
		// Extension: Sessions
		if(extension_loaded("session")) {
		} else {
			$errors[] = "The 'Session' PHP extension is required.  Please enable it.";
		}
		
		// Extension: PCRE
		if(extension_loaded("pcre")) {
		} else {
			$errors[] = "The 'PCRE' PHP extension is required.  Please enable it.";
		}
		
		// Extension: GD
		if(extension_loaded("gd") && function_exists('imagettfbbox')) {
		} else {
			$errors[] = "The 'GD' PHP extension (with FreeType library support) is required.  Please enable them.";
		}
		
		// Extension: IMAP
		if(extension_loaded("imap")) {
		} else {
			$errors[] = "The 'IMAP' PHP extension is required.  Please enable it.";
		}
		
		// Extension: MailParse
		if(extension_loaded("mailparse")) {
		} else {
			$errors[] = "The 'MailParse' PHP extension is required.  Please enable it.";
		}
		
		// Extension: mbstring
		if(extension_loaded("mbstring")) {
		} else {
			$errors[] = "The 'MbString' PHP extension is required.  Please	enable it.";
		}
		
		// Extension: XML
		if(extension_loaded("xml")) {
		} else {
			$errors[] = "The 'XML' PHP extension is required.  Please enable it.";
		}
		
		// Extension: SimpleXML
		if(extension_loaded("simplexml")) {
		} else {
			$errors[] = "The 'SimpleXML' PHP extension is required.  Please enable it.";
		}
		
		// Extension: DOM
		if(extension_loaded("dom")) {
		} else {
			$errors[] = "The 'DOM' PHP extension is required.  Please enable it.";
		}
		
		// Extension: SPL
		if(extension_loaded("spl")) {
		} else {
			$errors[] = "The 'SPL' PHP extension is required.  Please enable it.";
		}
		
		return $errors;
	}
	
	static function generatePassword($length=8) {
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
		$len = strlen($chars)-1;
		$password = '';
		
		for($x=0;$x<$length;$x++) {
			$chars = str_shuffle($chars);
			$password .= substr($chars,mt_rand(0,$len),1);
		}
		
		return $password;		
	}
	
	// [JAS]: [TODO] Cleanup + move (platform, diff ext point, DAO?)
	/**
	 * @return DevblocksTourCallout[]
	 */
	static function getTourCallouts() {
	    static $callouts = null;
	    
	    if(!is_null($callouts))
	        return $callouts;
	    
	    $callouts = array();
	        
	    $listenerManifests = DevblocksPlatform::getExtensions('devblocks.listener.http');
	    foreach($listenerManifests as $listenerManifest) { /* @var $listenerManifest DevblocksExtensionManifest */
	         $inst = $listenerManifest->createInstance(); /* @var $inst IDevblocksTourListener */
	         
	         if($inst instanceof IDevblocksTourListener)
	             $callouts += $inst->registerCallouts();
	    }
	    
	    return $callouts;
	}
	
	static function stripHTML($str) {
		// Strip all CRLF and tabs, spacify </TD>
		$str = str_ireplace(
			array("\r","\n","\t","</TD>"),
			array('','',' ',' '),
			trim($str)
		);
		
		// Turn block tags into a linefeed
		$str = str_ireplace(
			array('<BR>','<P>','</P>','<HR>','</TR>','</H1>','</H2>','</H3>','</H4>','</H5>','</H6>','</DIV>'),
			"\n",
			$str
		);		
		
		// Strip tags
		$search = array(
			'@<script[^>]*?>.*?</script>@si',
		    '@<style[^>]*?>.*?</style>@siU',
		    '@<[\/\!]*?[^<>]*?>@si',
		    '@<![\s\S]*?--[ \t\n\r]*>@',
		);
		$str = preg_replace($search, '', $str);
		
		// Flatten multiple spaces into a single
		$str = preg_replace('# +#', ' ', $str);

		// Translate HTML entities into text
		$str = html_entity_decode($str, ENT_COMPAT, LANG_CHARSET_CODE);

		// Loop through each line, ltrim, and concat if not empty
		$lines = split("\n", $str);
		if(is_array($lines)) {
			$str = '';
			$blanks = 0;
			foreach($lines as $idx => $line) {
				$lines[$idx] = ltrim($line);
				
				if(empty($lines[$idx])) {
					if(++$blanks >= 2)
						unset($lines[$idx]);
						//continue; // skip more than 2 blank lines in a row
				} else {
					$blanks = 0;
				}
			}
			$str = implode("\n", $lines);
		}
		unset($lines);
		
		// Clean up bytes (needed after HTML entities)
		$str = mb_convert_encoding($str, LANG_CHARSET_CODE, LANG_CHARSET_CODE);
		
		return $str;
	}
	    
	/**
	 * Enter description here...
	 *
	 * @return a unique ticket mask as a string
	 */
	static function generateTicketMask($pattern = "LLL-NNNNN-NNN") {
		$letters = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
		$numbers = "123456789";

		do {		
			$mask = "";
			$bytes = preg_split('//', $pattern, -1, PREG_SPLIT_NO_EMPTY);
			
			if(is_array($bytes))
			foreach($bytes as $byte) {
				switch(strtoupper($byte)) {
					case 'L':
						$mask .= substr($letters,mt_rand(0,strlen($letters)-1),1);
						break;
					case 'N':
						$mask .= substr($numbers,mt_rand(0,strlen($numbers)-1),1);
						break;
					case 'C': // L or N
						if(mt_rand(0,100) >= 50) { // L
							$mask .= substr($letters,mt_rand(0,strlen($letters)-1),1);	
						} else { // N
							$mask .= substr($numbers,mt_rand(0,strlen($numbers)-1),1);	
						}
						break;
					case 'Y':
						$mask .= date('Y');
						break;
					case 'M':
						$mask .= date('m');
						break;
					case 'D':
						$mask .= date('d');
						break;
					default:
						$mask .= $byte;
						break;
				}
			}
		} while(null != DAO_Ticket::getTicketIdByMask($mask));
		
//		echo "Generated unique mask: ",$mask,"<BR>";
		
		return $mask;
	}
	
	/**
	 * Generate an RFC-compliant Message-ID
	 */
	static function generateMessageId() {
		$message_id = sprintf('<%s.%s@%s>', base_convert(time(), 10, 36), base_convert(mt_rand(), 10, 36), !empty($_SERVER['HTTP_HOST']) ?  $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
		return $message_id;
	}
	
	/**
	 * Translates the string version of a group/bucket combo into their 
	 * respective IDs.
	 * 
	 * @todo This needs a better name and home
	 */
	static function translateTeamCategoryCode($code) {
		$t_or_c = substr($code,0,1);
		$t_or_c_id = intval(substr($code,1));
		
		if($t_or_c=='c') {
			$categories = DAO_Bucket::getAll();
			$team_id = $categories[$t_or_c_id]->team_id;
			$category_id = $t_or_c_id; 
		} else {
			$team_id = $t_or_c_id;
			$category_id = 0;
		}
		
		return array($team_id, $category_id);
	}
	
	/**
	 * Looks up an e-mail address using a revolving cache.  This is helpful 
	 * in situations where you may look up the same e-mail address multiple 
	 * times (reports, audit log, views) and you don't want to waste code 
	 * filtering out dupes.
	 * 
	 * @param string $address The e-mail address to look up
	 * @param bool $create Should the address be created if not found?
	 * @return Model_Address The address object or NULL 
	 * 
	 * @todo [JAS]: Move this to a global cache/hash registry
	 */
	static public function hashLookupAddress($email, $create=false) {
	    static $hash_address_to_id = array();
	    static $hash_hits = array();
	    static $hash_size = 0;
	    
	    if(isset($hash_address_to_id[$email])) {
	    	$return = $hash_address_to_id[$email];
	    	
	        @$hash_hits[$email] = intval($hash_hits[$email]) + 1;
	        $hash_size++;
	        
	        // [JAS]: if our hash grows past our limit, crop hits array + intersect keys
	        if($hash_size > 250) {
	            arsort($hash_hits);
	            $hash_hits = array_slice($hash_hits,0,100,true);
	            $hash_address_to_id = array_intersect_key($hash_address_to_id,$hash_hits);
	            $hash_size = count($hash_address_to_id);
	        }
	        
	        return $return;
	    }
	    
	    $address = DAO_Address::lookupAddress($email, $create);
	    if(!empty($address)) {
	        $hash_address_to_id[$email] = $address;
	    }
	    return $address;
	}

	/**
	 * Looks up a ticket ID by the provided mask using a revolving cache.
	 * This is useful if you need to translate several ticket masks into 
	 * IDs where there may be a lot of redundancy (batches in the e-mail 
	 * parser, etc.)
	 * 
	 * @param string $mask The ticket mask to look up
	 * @return integer The ticket id, or NULL if not found
	 *  
	 * @todo [JAS]: Move this to a global cache/hash registry 
	 */
	static public function hashLookupTicketIdByMask($mask) {
	    static $hash_mask_to_id = array();
	    static $hash_hits = array();
	    static $hash_size = 0;
	    
	    if(isset($hash_mask_to_id[$mask])) {
	    	$return = $hash_mask_to_id[$mask];
	    	
	        @$hash_hits[$mask] = intval($hash_hits[$mask]) + 1;
	        $hash_size++;

	        // [JAS]: if our hash grows past our limit, crop hits array + intersect keys
	        if($hash_size > 250) {
	            arsort($hash_hits);
	            $hash_hits = array_slice($hash_hits,0,100,true);
	            $hash_mask_to_id = array_intersect_key($hash_mask_to_id,$hash_hits);
	            $hash_size = count($hash_mask_to_id);
	        }
	        
	        return $return;
	    }
	    
	    $ticket_id = DAO_Ticket::getTicketIdByMask($mask);
	    if(!empty($ticket_id)) {
	        $hash_mask_to_id[$mask] = $ticket_id;
	    }
	    return $ticket_id;
	}
	
	/**
	 * Enter description here...
	 * [TODO] Move this into a better API holding place
	 *
	 * @param integer $group_id
	 * @param integer $ticket_id
	 * @param integer $only_rule_id
	 * @return Model_GroupInboxFilter[]|false
	 */
	static public function runGroupRouting($group_id, $ticket_id, $only_rule_id=0) {
		static $moveMap = array();
		$dont_move = false;
		
		if(false != ($matches = Model_GroupInboxFilter::getMatches($group_id, $ticket_id, $only_rule_id))) { /* @var $match Model_GroupInboxFilter */
			if(is_array($matches))
			foreach($matches as $idx => $match) {
				/* =============== Prevent recursive assignments =============
				* If we ever get into a situation where many rules are sending a ticket
				* back and forth between them, ignore the last move action in the chain  
				* which is trying to start over.
				*/
				if(!isset($moveMap[$ticket_id])) {
					$moveMap[$ticket_id] = array();
				} else {
					if(isset($moveMap[$ticket_id][$group_id])) {
						$dont_move = true;
					}
				}
				$moveMap[$ticket_id][$group_id] = $match->id;
	
				// Stop any move actions if we're going to loop again
				if($dont_move) {
					unset($matches[$idx]->actions['move']);
				}
				
				// Run filter actions
				$match->run(array($ticket_id));
			}
		}
		
	    return $matches;
	}
	
	// [TODO] This probably has a better home
	public static function getHelpdeskSenders() {
		$cache = DevblocksPlatform::getCacheService();

		if(null === ($froms = $cache->load(self::CACHE_HELPDESK_FROMS))) {
			$froms = array();
			$settings = CerberusSettings::getInstance();
			$group_settings = DAO_GroupSettings::getSettings();
			
			// Global sender
			$from = strtolower($settings->get(CerberusSettings::DEFAULT_REPLY_FROM));
			@$froms[$from] = $from;
			
			// Group senders
			if(is_array($group_settings))
			foreach($group_settings as $group_id => $gs) {
				@$from = strtolower($gs[DAO_GroupSettings::SETTING_REPLY_FROM]);
				if(!empty($from))
					@$froms[$from] = $from;
			}
			
			$cache->save($froms, self::CACHE_HELPDESK_FROMS);
		}
		
		return $froms;
	}
};

class CerberusLicense {
	public $name = '';
	public $users = 3;
	public $key = '';
	
	/**
	 * @return array
	 */
	public static function getInstance() {
		$settings = CerberusSettings::getInstance();
		$license = $settings->get(CerberusSettings::LICENSE,array());
		if(!empty($license)) {
			@$license = unserialize($license);
		}
		if(!is_array($license))
			$license = array();
		return $license;
	}

	public static function validate($key, $email) {
		/*  																																																																																																																														*/foreach(array('L0NvbXBhbnk6ICguKikv'=>'b','L1VzZXJzOiAoLiopLw=='=>'d','L1NlcmlhbDogKC4qKS8='=>'s') as $k=>$v)@preg_match(base64_decode($k),$key,$matches)?@$$v=trim($matches[1]):null;@$r=array('name'=>$b,'email'=>$email,'users'=>intval($d),'serial'=>$s);foreach(array(chr(97)=>0,chr(101)=>3) as $k=>$v)if(@substr(str_replace('-','',$s),0,1).@substr(str_replace('-','',$s),4,1).@substr(str_replace('-','',$s),8,1)==@substr(strtoupper(md5(@substr($b,0,1).@substr($b,-1,1).@strlen($b).$d.@substr($email,0,1).@substr($email,4,1).@strlen($email))),$v,3))@$r[$k]=$s;return $r;/*
		 * we're sure being generous here! [TODO]
		 */
		$lines = split("\n", $key);
		
		/*
		 * Remember that our cache can return stale data here. Be sure to
		 * clear caches.  The config area does already.
		 */
		return (!empty($key)) 
			? array(
				'name' => (list($k,$v)=split(":",$lines[1]))?trim($v):null,
				'email' => $email,
				'users' => (list($k,$v)=split(":",$lines[2]))?trim($v):null,
				'serial' => (list($k,$v)=split(":",$lines[3]))?trim($v):null,
				'date' => time()
			)
			: null;
	}
};

class CerberusSettings {
	const DEFAULT_REPLY_FROM = 'default_reply_from'; 
	const DEFAULT_REPLY_PERSONAL = 'default_reply_personal'; 
	const DEFAULT_SIGNATURE = 'default_signature'; 
	const DEFAULT_SIGNATURE_POS = 'default_signature_pos'; 
	const HELPDESK_TITLE = 'helpdesk_title'; 
	const HELPDESK_LOGO_URL = 'helpdesk_logo_url'; 
	const SMTP_HOST = 'smtp_host'; 
	const SMTP_AUTH_ENABLED = 'smtp_auth_enabled'; 
	const SMTP_AUTH_USER = 'smtp_auth_user'; 
	const SMTP_AUTH_PASS = 'smtp_auth_pass'; 
	const SMTP_PORT = 'smtp_port'; 
	const SMTP_ENCRYPTION_TYPE = 'smtp_enc';
	const SMTP_MAX_SENDS = 'smtp_max_sends';
	const SMTP_TIMEOUT = 'smtp_timeout';
	const ATTACHMENTS_ENABLED = 'attachments_enabled'; 
	const ATTACHMENTS_MAX_SIZE = 'attachments_max_size'; 
	const PARSER_AUTO_REQ = 'parser_autoreq'; 
	const PARSER_AUTO_REQ_EXCLUDE = 'parser_autoreq_exclude'; 
	const AUTHORIZED_IPS = 'authorized_ips';
	const LICENSE = 'license';
	const ACL_ENABLED = 'acl_enabled';
	
	private static $instance = null;
	
	private $settings = array( // defaults
		self::DEFAULT_REPLY_FROM => '',
		self::DEFAULT_REPLY_PERSONAL => '',
		self::DEFAULT_SIGNATURE => '',
		self::DEFAULT_SIGNATURE_POS => 0,
		self::HELPDESK_TITLE => 'Cerberus Helpdesk :: Team-based E-mail Management',
		self::HELPDESK_LOGO_URL => '',
		self::SMTP_HOST => 'localhost',
		self::SMTP_AUTH_ENABLED => 0,
		self::SMTP_AUTH_USER => '',
		self::SMTP_AUTH_PASS => '',
		self::SMTP_PORT => 25,
		self::SMTP_ENCRYPTION_TYPE => 'None',
		self::SMTP_MAX_SENDS => 20,
		self::SMTP_TIMEOUT => 30,
		self::ATTACHMENTS_ENABLED => 1,
		self::ATTACHMENTS_MAX_SIZE => 10, // MB
		self::AUTHORIZED_IPS => '127.0.0.1', 
		self::LICENSE => '',
		self::ACL_ENABLED => 0,
	);

	/**
	 * @return CerberusSettings
	 */
	private function __construct() {
	    // Defaults (dynamic)
		$saved_settings = DAO_Setting::getSettings();
		foreach($saved_settings as $k => $v) {
			$this->settings[$k] = $v;
		}
	}
	
	/**
	 * @return CerberusSettings
	 */
	public static function getInstance() {
		if(self::$instance==null) {
			self::$instance = new CerberusSettings();	
		}
		
		return self::$instance;		
	}
	
	public function set($key,$value) {
		DAO_Setting::set($key,$value);
		$this->settings[$key] = $value;
		
	    $cache = DevblocksPlatform::getCacheService();
		$cache->remove(CerberusApplication::CACHE_SETTINGS_DAO);
		
		// Nuke sender cache
		if($key == self::DEFAULT_REPLY_FROM) {
			$cache->remove(CerberusApplication::CACHE_HELPDESK_FROMS);
		}
		
		return TRUE;
	}
	
	/**
	 * @param string $key
	 * @param string $default
	 * @return mixed
	 */
	public function get($key,$default=null) {
		if(isset($this->settings[$key]))
			return $this->settings[$key];
		else 
			return $default;
	}
};

// [TODO] This gets called a lot when it happens after the registry cache
class C4_DevblocksExtensionDelegate implements DevblocksExtensionDelegate {
	static function shouldLoadExtension(DevblocksExtensionManifest $extension_manifest) {
		// Always allow core
		if("cerberusweb.core" == $extension_manifest->plugin_id)
			return true;
		
		// [TODO] This should limit to just things we can run with no session
		// Community Tools, Cron/Update.  They are still limited by their own
		// isVisible() otherwise.
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			return true;
			
		return $active_worker->hasPriv('plugin.'.$extension_manifest->plugin_id);
	}
};
