<?php
/**
 * DokuWiki Plugin smartcard (Auth Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Stephen Bowman <sbbowman@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

//class auth_plugin_authsmartcard extends DokuWiki_Auth_Plugin {
class auth_plugin_authsmartcard extends auth_plugin_authplain {


    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(); // for compatibility

        $this->cando['addUser']     = true; // can Users be created?
        $this->cando['delUser']     = true; // can Users be deleted?
        $this->cando['modLogin']    = true; // can login names be changed?
        $this->cando['modPass']     = true; // can passwords be changed?
        $this->cando['modName']     = true; // can real names be changed?
        $this->cando['modMail']     = true; // can emails be changed?
        $this->cando['modGroups']   = true; // can groups be changed?
        $this->cando['getUsers']    = true; // can a (filtered) list of users be retrieved?
        $this->cando['getUserCount']= true; // can the number of users be retrieved?
        $this->cando['getGroups']   = true; // can a list of available groups be retrieved?
        $this->cando['external']    = false; // does the module do external auth checking?
        $this->cando['logout']      = false; // can the user logout again? (eg. not possible with HTTP auth)

        $this->success = true;
    }


    /**
     * Check user+password
     *
     * May be ommited if trustExternal is used.
     *
     * @param   string $user the user name
     * @param   string $pass the clear text password
     * @return  bool
     */
    public function checkPass(&$username, &$password) {

	    session_start();
	    // test if already logged in
	    if(isset($_SESSION['smartcard_userdata']) && $_SESSION['smartcard_userdata']['username']==$username && md5($_SESSION['smartcard_userdata']['pass'])==$password){
		    return true;
	    }

	    // client certificate log in
	    if($username=='smartcard'){
		// if client cert exists
		if(isset($_SESSION['SSL_CLIENT_CERT']) && $_SESSION['SSL_CLIENT_CERT']){
			// parse cert data      
			$client_cert_data   = openssl_x509_parse($_SESSION['SSL_CLIENT_CERT']);
			$cn                     = $client_cert_data['subject']['CN'];
			// if cn was in cert 
			if($cn){
				$userdata = $this->findUserByCN($cn);
				if(!$userdata){ 
					$this->__log("Did not find user with cn=$cn");
					msg('Did not find user with cn: '.$cn, -1); 
				}
			}
		}
		// if client cert does not exist
		else{
			$this->__log("Client certificate not found");
			msg('Smartcard was not found. Please check that it is connected and drivers are installed.', -1);
			return false;
		}
	    }

	    // SEE THAT WE GOT SMTH AND LOG HIM IN
	    if($userdata){
		    // overwrite username and password with what was found in the user db (notice, function was defined: checkPass(&$username, &$password))
		    $username = $userdata['username'];
		    $password = md5($userdata['pass']);
		    // set $_SESSION['smartcard_userdata'] because otherwise auth will fail later on because invalid pw
		    $_SESSION['smartcard_userdata'] = $userdata;
		    session_write_close();
		    return true;
	    }

	    // logon failed for some other unknown reason...
	    unset($_SESSION['smartcard_userdata']);
	    session_write_close();
	    return false;
    }

    /**
     * Finds user by cn
     *
     */
    public function findUserByCN($cn){
	    if(!$cn){
		    $this->__log("passed an empty CN?");
		    return false;
	    }

	    // retrieve all users where the CN is in the group for a user.
	    $users = $this->retrieveUsers(0, 2000, array('grps'=>$cn));

	    // if user count 1
	    if(count($users)==1){
		    // create username value for user    
		    foreach($users as $key => &$value){
			    $value['username']  = $key;
		    }
		    $users  = array_values($users);
		    $this->__log("Found user=" . $users[0]['username'] ." with CN=$cn");
		    $this->__log(array($users[0]));
		    return $users[0];
	    }
	    // if user count more than 1
	    if(count($users)>1){
		    $this->__log("Found multiple users with group having CN=$cn, that should not happen");
		    return false;
	    }
	    // no users found
	    $this->__log("$cn not found");
	    return false;
    }

    /**
     * Finds user by username and password
     *
     */
    public function findUserByUsernameAndPassword($username, $password){
	    $username = preg_replace('/[^\w\d\.-_]/', '', $username);
	    $password = preg_replace('/[^\w\d\.-_]/', '', $password);

	    $userdata = $this->getUserData($username);
	    $userdata['username'] = $username;
	    return $userdata;
    }

    /**
     * Logs messages to data/log/auth_smartcard.log.txt
     *
     */
    public function __log($text){

	    $text = json_encode($text);

	    if($this->getConf('log_to_file') && $this->getConf('logfile')){
		    file_put_contents($this->getConf('logfile'), date('c').": ".$text."\n", FILE_APPEND);
	    }
    }

    /**
     * Return user info
     *
     * Returns info about the given user needs to contain
     * at least these fields:
     *
     * name string  full name of the user
     * mail string  email addres of the user
     * grps array   list of groups the user is in
     *
     * @param   string $user the user name
     * @return  array containing user data or false
     */
    public function getUserData($user) {
        return parent::getUserData($user);
    }

    /**
     * Create a new User [implement only where required/possible]
     *
     * Returns false if the user already exists, null when an error
     * occurred and true if everything went well.
     *
     * The new user HAS TO be added to the default group by this
     * function!
     *
     * Set addUser capability when implemented
     *
     * @param  string     $user
     * @param  string     $pass
     * @param  string     $name
     * @param  string     $mail
     * @param  null|array $grps
     * @return bool|null
     */
    public function createUser($user, $pass, $name, $mail, $grps = null) {
	    return parent::createUser($user, $pass, $name, $mail, $grps);
    }

    /**
     * Modify user data [implement only where required/possible]
     *
     * Set the mod* capabilities according to the implemented features
     *
     * @param   string $user    nick of the user to be changed
     * @param   array  $changes array of field/value pairs to be changed (password will be clear text)
     * @return  bool
     */
    public function modifyUser($user, $changes) {
	    return parent::modifyUser($user, $changes);
    }

    /**
     * Delete one or more users [implement only where required/possible]
     *
     * Set delUser capability when implemented
     *
     * @param   array  $users
     * @return  int    number of users deleted
     */
    public function deleteUsers($users) {
	    return parent::deleteUsers($users);
    }

    /**
     * Return a count of the number of user which meet $filter criteria
     * [should be implemented whenever retrieveUsers is implemented]
     *
     * Set getUserCount capability when implemented
     *
     * @param  array $filter array of field/pattern pairs, empty array for no filter
     * @return int
     */
    public function getUserCount($filter = array()) {
	    return parent::getUserCount($filter);
    }

    /**
     * Bulk retrieval of user data
     *
     * @param   int   $start index of first user to be returned
     * @param   int   $limit max number of users to be returned
     * @param   array $filter array of field/pattern pairs
     * @return  array userinfo (refer getUserData for internal userinfo details)
     */
    public function retrieveUsers($start, $limit, $filter) {
	    return parent::retrieveUsers($start, $limit, $filter);
    }

    /**
     * Define a group [implement only where required/possible]
     *
     * Set addGroup capability when implemented
     *
     * @param   string $group
     * @return  bool
     */
    public function addGroup($group) {
	    return parent::addGroup();
    }

    /**
     * Retrieve groups [implement only where required/possible]
     *
     * Set getGroups capability when implemented
     *
     * @param   int $start
     * @param   int $limit
     * @return  array
     */
    public function retrieveGroups($start = 0, $limit = 0) {
	    return parent::retrieveGroups();
    }

    /**
     * Return case sensitivity of the backend
     *
     * When your backend is caseinsensitive (eg. you can login with USER and
     * user) then you need to overwrite this method and return false
     *
     * @return bool
     */
    public function isCaseSensitive() {
	    return parent::isCaseSensitive();
    }
}

// vim:ts=4:sw=4:et:
