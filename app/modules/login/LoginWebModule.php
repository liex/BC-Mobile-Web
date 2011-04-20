<?php
/**
  * @package Module
  * @subpackage Login
  */

/**
  * @package Module
  * @subpackage Login
  */
class LoginWebModule extends WebModule {
  protected $id = 'login';
  
  protected function getAccessControlLists($type) {
        return array(AccessControlList::allAccess());
  }

  protected function initializeForPage() {
    if (!Kurogo::getSiteVar('AUTHENTICATION_ENABLED')) {
        throw new Exception("Authentication is not enabled on this site");
    }
    
    $session = $this->getSession();
    $url = $this->getArg('url','');
    $allowRemainLoggedIn = Kurogo::getOptionalSiteVar('AUTHENTICATION_REMAIN_LOGGED_IN_TIME');
    if ($allowRemainLoggedIn) {
        $remainLoggedIn = $this->getArg('remainLoggedIn', 0);
    } else {
        $remainLoggedIn = 0;
    }
    
    $authenticationAuthorities = array(
        'direct'=>array(),
        'indirect'=>array()
    );
    
    foreach (AuthenticationAuthority::getDefinedAuthenticationAuthorities() as $authorityIndex=>$authorityData) {
        $USER_LOGIN = $this->argVal($authorityData, 'USER_LOGIN', 'NONE');
        
        try {
            $authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex);
            $authorityData['listclass'] = $authority->getAuthorityClass();
            $authorityData['title'] = $authorityData['TITLE'];
            $authorityData['url'] = $this->buildURL('login', array(
                'authority'=>$authorityIndex,
                'url'=>$url,
                'remainLoggedIn'=>$remainLoggedIn
            ));
            if ($USER_LOGIN=='FORM') {
                $authenticationAuthorities['direct'][$authorityIndex] = $authorityData;
            } elseif ($USER_LOGIN=='LINK') {
                $authenticationAuthorities['indirect'][$authorityIndex] = $authorityData;
            }
        } catch (Exception $e) {
        
        }
    }
                    
    if (count($authenticationAuthorities['direct'])==0 && count($authenticationAuthorities['indirect'])==0) {
        throw new Exception("No authentication authorities have been defined");
    }
    
    $this->assign('authenticationAuthorities', $authenticationAuthorities);
    $this->assign('allowRemainLoggedIn', $allowRemainLoggedIn);
    if ($forgetPasswordURL = $this->getOptionalModuleVar('FORGET_PASSWORD_URL')) {
        $this->assign('FORGET_PASSWORD_URL', $this->buildBreadcrumbURL('forgotpassword', array()));
    }
    
    $multipleAuthorities = count($authenticationAuthorities['direct']) + count($authenticationAuthorities['indirect']) > 1;
    
    switch ($this->page)
    {
        case 'logoutConfirm':
            $authorityIndex = $this->getArg('authority');
            
            if (!$this->isLoggedIn($authorityIndex)) {
                $this->redirectTo('index', array());
            } elseif ($user = $this->getUser($authorityIndex)) {
                $authority = $user->getAuthenticationAuthority();
                $this->assign('message', sprintf("You are signed in to %s %s as %s", 
                    Kurogo::getSiteString('SITE_NAME'),
                    $multipleAuthorities ? "(using ". $authority->getAuthorityTitle() . ")" : '',
                    $user->getFullName()));
                $this->assign('url', $this->buildURL('logout', array('authority'=>$authorityIndex)));
                $this->assign('linkText', 'Sign out');
                $this->setTemplatePage('message');
            } else {
                $this->redirectTo('index', array());
            }
            
            break;
        case 'logout':
            $authorityIndex = $this->getArg('authority');
            $hard = $this->getArg('hard', false);

            if (!$this->isLoggedIn($authorityIndex)) {
                $this->redirectTo('index', array());
            } elseif ($authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                $result = $session->logout($authority, $hard);
            } else {
                $this->redirectTo('index', array());
            }
                
            if ($result) { 
                if ($this->isLoggedIn()) {
                    $this->redirectTo('index', array('logout'=>$authorityIndex));
                } else {
                    $this->redirectToModule('home','',array('logout'=>$authorityIndex));
                }
            } else {
                $this->setTemplatePage('message');
                $this->assign('message', 'Sign out failed');
            }
        
            break;
            
        case 'login':
            $login          = $this->argVal($_POST, 'loginUser', '');
            $password       = $this->argVal($_POST, 'loginPassword', '');
            $options = array(
                'url'=>$url,
                'remainLoggedIn'=>$remainLoggedIn
            );
            
            $session  = $this->getSession();
            $session->setRemainLoggedIn($remainLoggedIn);

            $authorityIndex = $this->getArg('authority', '');
            if (!$authorityData = AuthenticationAuthority::getAuthenticationAuthorityData($authorityIndex)) {
                $this->redirectTo('index', $options);
            }

            if ($this->isLoggedIn($authorityIndex)) {
                $this->redirectTo('index', $options);
            }                    

            $this->assign('authority', $authorityIndex);
            $this->assign('remainLoggedIn', $remainLoggedIn);

            if ($authorityData['USER_LOGIN']=='FORM' && empty($login)) {
                $this->assign('authorityTitle', $authorityData['TITLE']);
                break;
            } elseif ($authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                $authority->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
                $result = $authority->login($login, $password, $session, $options);
            } else {
                $this->redirectTo('index', $options);
            }

            switch ($result)
            {
                case AUTH_OK:
                    if ($url) {
                        header("Location: $url");
                        exit();
                    } else {
                        $this->redirectToModule('home','',array('login'=>$authorityIndex));
                    }
                    break;

                default:
                    $this->assign('message', "We're sorry, but there was a problem with your sign-in. Please check your username and password and try again.");
                    break;
            }

            break;
            
        case 'forgotpassword':
            if ($forgetPasswordURL = $this->getOptionalModuleVar('FORGET_PASSWORD_URL')) {
                header("Location: $forgetPasswordURL");
                exit();
            } else {
                $this->redirectTo('index', array());
            }
            break;
            
        case 'index':
            if ($this->isLoggedIn()) {
                
                if ($url) {
                    header("Location: $url");
                    exit();
                }

                if (!$multipleAuthorities) {
                    $user = $this->getUser();
                    $this->redirectTo('logoutConfirm', array('authority'=>$user->getAuthenticationAuthorityIndex()));
                }

                $sessionUsers = $session->getUsers();
                $users = array();

                foreach ($sessionUsers as $authorityIndex=>$user) {
                    $authority = $user->getAuthenticationAuthority();
                    $users[] = array(
                        'class'=>$authority->getAuthorityClass(),
                        'title'=>count($sessionUsers)>1 ? $authority->getAuthorityTitle() . " as " . $user->getFullName() : 'Sign out',
                        'subtitle'=>count($sessionUsers)>1 ? 'Sign out' : '',
                        'url'  =>$this->buildBreadcrumbURL('logout', array('authority'=>$authorityIndex), false)
                    );
                    if (isset($authenticationAuthorities['direct'][$authorityIndex])) {
                        unset($authenticationAuthorities['direct'][$authorityIndex]);
                    }

                    if (isset($authenticationAuthorities['indirect'][$authorityIndex])) {
                        unset($authenticationAuthorities['indirect'][$authorityIndex]);
                    }
                }
                
                $this->assign('users', $users);
                $this->assign('authenticationAuthorities', $authenticationAuthorities);
                $this->assign('moreAuthorities', count($authenticationAuthorities['direct']) + count($authenticationAuthorities['indirect']));
                $this->setTemplatePage('loggedin');
            } else {
                if (!$multipleAuthorities && count($authenticationAuthorities['direct'])) {
                    $this->redirectTo('login', array('authority'=>AuthenticationAuthority::getDefaultAuthenticationAuthorityIndex()));
                }
                $this->assign('multipleAuthorities', $multipleAuthorities);
            }
            break;
    }
  }

}

