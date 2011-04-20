<?php
/**
  * @package Authentication
  */

/**
  * @package Authentication
  */
class LDAPAuthentication extends AuthenticationAuthority
{
    protected $authorityClass = 'ldap';
    protected $userClass='LDAPUser';
    protected $groupClass='LDAPUserGroup';
    protected $ldapServer;
    protected $ldapPort=389;
    protected $ldapSearchBase;
    protected $ldapUserSearchBase;
    protected $ldapGroupSearchBase;
    protected $ldapAdminDN;
    protected $ldapAdminPassword;
    protected $fieldMap=array();
    protected $ldapResource;
    
    public static function ldapEscape($str) 
    { 
        // see RFC2254 
        // http://msdn.microsoft.com/en-us/library/ms675768(VS.85).aspx 
        // http://www-03.ibm.com/systems/i/software/ldap/underdn.html        
            
        $metaChars = array('*', '(', ')', '\\', chr(0));
        $quotedMetaChars = array(); 
        foreach ($metaChars as $key => $value) {
            $quotedMetaChars[$key] = '\\'.str_pad(dechex(ord($value)), 2, '0'); 
        }
        $str = str_replace($metaChars, $quotedMetaChars, $str); 
        return ($str); 
    }
    
    protected function connectToServer()
    {
        if (!$this->ldapResource) {
            $this->ldapResource = ldap_connect($this->ldapServer, $this->ldapPort);
            if ($this->ldapResource) {
                ldap_set_option($this->ldapResource, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($this->ldapResource, LDAP_OPT_REFERRALS, 0);
            } else {
                error_log("Error connecting to LDAP Server $this->ldadServer using port $this->ldapPort");
                return false;
            }
        }
        
        return $this->ldapResource;
    }

    protected function validUserLogins()
    {
        return array('FORM', 'NONE');
    }
        
    public function auth($login, $password, &$user)
    {
        $ldap = $this->connectToServer();
        if (!$ldap) {
            return AUTH_ERROR;
        }

        // we need to find the user first to get the DN 
        if (!$user = $this->getUser($login)) {
            return AUTH_USER_NOT_FOUND;
        }
     
        // attempt to bind as this user
        $auth = @ldap_bind($ldap, $user->getDN(), $password);
        if ($auth) {
            return AUTH_OK;
        } else {
            return AUTH_FAILED;
        }
    }
    
    public function getField($field)
    {
        return isset($this->fieldMap[$field]) ? $this->fieldMap[$field] : null;
    }
    
    public function ldapSearchBase($type=null)
    {
        switch ($type)
        {
            case 'user':
                if ($this->ldapUserSearchBase) {
                    return $this->ldapUserSearchBase;
                }
                break;
            case 'group':
                if ($this->ldapGroupSearchBase) {
                    return $this->ldapGroupSearchBase;
                }
                break;
        }
        
        if ($this->ldapSearchBase) {
            return $this->ldapSearchBase;
        }
        
        //we can attempt to "discover" the search base in many cases, but this might have some performance implications
        $ldap = $this->connectToServer();
        if (!$ldap) {
            return false;
        }

        $search = ldap_read($ldap, "", "(objectClass=*)", array('namingcontexts'));
        if ($search) {
            $result = ldap_get_entries($ldap, $search);
            if ($result['count']>0) {
                $this->ldapSearchBase = $result[0]['namingcontexts'][0];
                return $this->ldapSearchBase;
            } else {
                error_log("Unable to determine search base for LDAP Server $this->ldapServer: " . ldap_error($ldap));
                return false;
            }
            
        } else {
            error_log("Error discovering search base for LDAP Server $this->ldapServer: " . ldap_error($ldap));
            return false;
        }
    }

    public function getUser($login)
    {
        // don't try if it's empty
        if (empty($login)) {
            return new AnonymousUser();       
        }

        $ldap = $this->connectToServer();
        if (!$ldap) {
            return false;
        }
        
        /*
            some servers don't permit anonymous searches so we need to bind as a valid user 
             Note: it does not, and should not be an account with administrative privilages. 
                    Usually a regular service account will suffice
        */
        if ($this->ldapAdminDN) {
            if (!ldap_bind($ldap, $this->ldapAdminDN, $this->ldapAdminPassword)) {
                error_log("Error binding to LDAP Server $this->ldapServer for $this->ldapAdminDN: " . ldap_error($ldap));
                return false;
            }
        }
        
        if (!$this->getField('uid')) {
            throw new Exception('LDAP uid field not specified');
        }
        
        /* dn searches don't work so we have to get the uid value */
        if (stripos($login, $this->ldapSearchBase())!==FALSE) {
            if ($sr = ldap_read($ldap, $login, "(objectclass=*)", array($this->getField('uid')))) {
                if ($entries = ldap_get_entries($ldap, $sr)) {
                    $login = $entries[0][$this->getField('uid')][0];
                }
            }
        }

        $searchStr = array(
            sprintf('(%s=%s)', $this->getField('uid'), $this->ldapEscape($login)),
        );
        
        if ($this->getField('email')) {
            $searchStr[] = sprintf('(%s=%s)', $this->getField('email'), $this->ldapEscape($login));
        }
        
        $searchStr = count($searchStr) > 1 ? "(|" . implode("", $searchStr) . ")" : implode("", $searchStr);
                
        $search = @ldap_search($ldap, $this->ldapSearchBase('user'), $searchStr);
        if ($search) {
            $result = @ldap_get_entries($ldap, $search);
            // see if we got a result back 
            if ($result['count']>0) {
                $entry = $result[0];
                $user = new $this->userClass($this);
                $user->setDN($entry['dn']);

                // single value attributes expect a maximum of one value
                $singleValueAttributes = $user->singleValueAttributes();
                for ($i=0; $i<$entry['count']; $i++) {
                    $attrib = $entry[$i];
                    
                    if (in_array($attrib, $singleValueAttributes)) {
                        $value = $entry[$attrib][0];
                    } else {
                        $value = $entry[$attrib];
                        unset($value['count']);
                    }
                    
                    $user->setAttribute($attrib, $value);
                }

                return $user;
            } else {
                return false;
                return AUTH_USER_NOT_FOUND; // not sure which one is correct yet
            }
        } else {
            error_log("Error searching LDAP Server $this->ldapServer for uid=$login: " . ldap_error($ldap));
            return false;
        }
    }

    public function getGroup($group)
    {
        // don't try if it's empty
        if (empty($group)) {
            return false;
        }

        $ldap = $this->connectToServer();
        if (!$ldap) {
            return false;
        }
        
        /*
            some servers don't permit anonymous searches so we need to bind as a valid user 
             Note: it does not, and should not be an account with administrative privilages. 
                    Usually a regular service account will suffice
        */
        if ($this->ldapAdminDN) {
            if (!ldap_bind($ldap, $this->ldapAdminDN, $this->ldapAdminPassword)) {
                error_log("Error binding to LDAP Server $this->ldapServer for $this->ldapAdminDN: " . ldap_error($ldap));
                return false;
            }
        }

        if (!$this->getField('groupname')) {
            throw new Exception('LDAP group name field not specified');
        }

        if (!$this->getField('members')) {
            throw new Exception('LDAP group members field not specified');
        }
        
        $searchStr = array(
            sprintf('(%s=%s)', $this->getField('groupname'), $this->ldapEscape($group))
        );
        
        $searchStr = count($searchStr) > 1 ? "(|" . implode("", $searchStr) . ")" : implode("", $searchStr);
                
        $search = @ldap_search($ldap, $this->ldapSearchBase('group'), $searchStr);
        if ($search) {
            $result = @ldap_get_entries($ldap, $search);
            // see if we got a result back 
            if ($result['count']>0) {
                $entry = $result[0];
                $group = new $this->groupClass($this);
                $group->setDN($entry['dn']);

                // single value attributes expect a maximum of one value
                $singleValueAttributes = $group->singleValueAttributes();
                for ($i=0; $i<$entry['count']; $i++) {
                    $attrib = $entry[$i];
                    
                    if (in_array($attrib, $singleValueAttributes)) {
                        $value = $entry[$attrib][0];
                    } else {
                        $value = $entry[$attrib];
                        unset($value['count']);
                    }
                    
                    $group->setAttribute($attrib, $value);
                }
                return $group;
            } else {
                return false;
            }
        } else {
            error_log("Error searching LDAP Server $this->ldapServer for group=$group: " . ldap_error($ldap));
            return false;
        }
    }
    
    protected function defaultFieldMap()
    {
        return array(
            'uid'=>'uid',
            'email'=>'mail',
            'firstname'=>'givenname',
            'lastname'=>'sn',
            'groupname'=>'cn',
            'members'=>'memberuid',
            'memberuid'=>'userid', 
            'gid'=>'gid'
        );
    }
    
    public function init($args)
    {
        parent::init($args);
        $args = is_array($args) ? $args : array();
        $this->ldapServer = isset($args['LDAP_HOST']) ? $args['LDAP_HOST'] : null;
        $this->ldapPort = isset($args['LDAP_PORT']) ? $args['LDAP_PORT'] : 389;
        $this->ldapSearchBase = isset($args['LDAP_SEARCH_BASE']) ? $args['LDAP_SEARCH_BASE'] : null;
        $this->ldapUserSearchBase = isset($args['LDAP_USER_SEARCH_BASE']) ? $args['LDAP_USER_SEARCH_BASE'] : null;
        $this->ldapGroupSearchBase = isset($args['LDAP_GROUP_SEARCH_BASE']) ? $args['LDAP_GROUP_SEARCH_BASE'] : null;

        //used if anonymous searches are not permitted (i.e. AD)
        $this->ldapAdminDN = isset($args['LDAP_ADMIN_DN']) ? $args['LDAP_ADMIN_DN'] : null;
        $this->ldapAdminPassword = isset($args['LDAP_ADMIN_PASSWORD']) ? $args['LDAP_ADMIN_PASSWORD'] : null;
        
        $this->fieldMap = $this->defaultFieldMap();
        
        foreach ($args as $arg=>$value) {
            if (preg_match("/^(user|group)_(.*?)_field$/", strtolower($arg), $bits)) {
                if (isset($this->fieldMap[$bits[2]])) {
                    $this->fieldMap[$bits[2]] = strtolower($value);
                }
            }
        }
        
        if ( empty($this->ldapServer)) {
            throw new Exception("Invalid LDAP Server");
        }
        
        if ( empty($this->ldapPort)) {
            throw new Exception("Invalid LDAP Port");
        }
    }
    
    public function validate(&$error) {
        $ldap = $this->connectToServer();
        if (!$ldap) {
            $error = new KurogoError(-1, "Error connecting", "Error connecting to $this->ldapServer");
            return false;
        }
        
        ldap_set_option($ldap, LDAP_OPT_TIMELIMIT, 5);
        if (defined('LDAP_OPT_NETWORK_TIMEOUT')) {
            ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 5);
        }

        if ($this->ldapAdminDN) {
            if (!@ldap_bind($ldap, $this->ldapAdminDN, $this->ldapAdminPassword)) {
                $error = new KurogoError(ldap_errno($ldap), "Error connecting", ldap_error($ldap));
                return false;
            }
        } else {
            if (!@ldap_bind($ldap)) {
                $error = new KurogoError(ldap_errno($ldap), "Error connecting", ldap_error($ldap));
                return false;
            }
        }
        
        if (!$search = @ldap_search($ldap, $this->ldapSearchBase('user'), '(objectclass=*)')) {
            $error = new KurogoError(ldap_errno($ldap), "Error connecting", "Error validating: " . ldap_error($ldap) . " (" . ldap_errno($ldap) . ")");
            return false;
        }
        
        //might need to test other things.... 
        return true;
    }
}

/**
  * @package Authentication
  */
class LDAPUser extends User
{
    protected $dn;
    
    public function getDN()
    {
        return $this->dn;
    }

    public function setDN($dn)
    {
        $this->dn = $dn;
    }
    
    public function setAttribute($attribute, $value)
    {
        switch ($attribute)
        {
            case $this->AuthenticationAuthority->getField('email'):
                $this->setEmail($value);
                break;
            case $this->AuthenticationAuthority->getField('lastname'):
                $this->setLastName($value);
                break;
            case $this->AuthenticationAuthority->getField('firstname'):
                $this->setFirstName($value);
                break;
            case $this->AuthenticationAuthority->getField('uid'):
                $this->setUserID($value);
                break;
            default:
                parent::setAttribute($attribute, $value);
                break;
        }
    }
    
    public function singleValueAttributes()
    {
        return array('dn', 
            $this->AuthenticationAuthority->getField('email'), 
            $this->AuthenticationAuthority->getField('uid'),
            $this->AuthenticationAuthority->getField('lastname'),
            $this->AuthenticationAuthority->getField('firstname'),
            $this->AuthenticationAuthority->getField('lastname')
        );
          
    }    

    protected function standardAttributes()
    {
        return array_merge(parent::standardAttributes(), array('dn', $this->AuthenticationAuthority->getField('uid')));
    }    
}

/**
  * @package Authentication
  */
class LDAPUserGroup extends UserGroup
{
    protected $dn;
    
    public function getDN()
    {
        return $this->dn;
    }

    public function setDN($dn)
    {
        $this->dn = $dn;
    }

    public function singleValueAttributes()
    {
        return array(
            $this->AuthenticationAuthority->getField('groupname'), 
            $this->AuthenticationAuthority->getField('gid')
        ); //there's more here. 
    }    

    protected function standardAttributes()
    {
        return array_merge(parent::standardAttributes(), array('dn'));
    }    

    public function setAttribute($attribute, $value)
    {
        switch ($attribute)
        {
            case $this->AuthenticationAuthority->getField('groupname'):
                $this->setGroupName($value);
                break;
            case $this->AuthenticationAuthority->getField('gid'):
                $this->setGroupID($value);
                break;
            case $this->AuthenticationAuthority->getField('members'):
                $this->members=$value;
                break;
            default:
                parent::setAttribute($attribute, $value);
                break;
        }
    }

    public function getMembers()
    {
        //lazy load the members since performance might be a factor
        $members = array();
        foreach ($this->members as $userID) {
            if ($user = $this->AuthenticationAuthority->getUser($userID)) {
                $members[] = $user;
            }
        }
        
        return $members;
    }
    
    public function userIsMember(User $user)
    {
        //by definition LDAP groups can only contain users from the same authority
        if ($user->getAuthenticationAuthorityIndex()==$this->getAuthenticationAuthorityIndex()) {
            if (in_array($user->getAttribute($this->AuthenticationAuthority->getField('memberuid')), $this->members)) {
                return true;
            }
        }
        
        return false;
    }
}
