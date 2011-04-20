<?php

includePackage('Emergency');

class EmergencyWebModule extends WebModule 
{
    protected $id='emergency';

    protected function initializeForPage() {
        // construct controllers

        $config = $this->loadFeedData();
        if(isset($config['contacts'])) {
          $contactsController = DataController::factory($config['contacts']['CONTROLLER_CLASS'], $config['contacts']);
        } else {
          $contactsController = NULL;
        }
        
        if(isset($config['notice'])) {
          $emergencyNoticeController = DataController::factory('EmergencyNoticeDataController', $config['notice']);
        } else {
          $emergencyNoticeController = NULL;
        }        

        switch($this->page) {
            case 'pane':
                $hasEmergencyFeed = ($emergencyNoticeController !== NULL);
                $this->assign('hasEmergencyFeed', $hasEmergencyFeed);
                if($hasEmergencyFeed) {
                    $emergencyNotice = $emergencyNoticeController->getLatestEmergencyNotice();
                    
                    if($emergencyNotice !== NULL) {
                        $this->assign('emergencyFeedEmpty', FALSE);             
                        $this->assign('title', $emergencyNotice['title']);
                        $this->assign('content', $emergencyNotice['text']);
                        $this->assign('date', $emergencyNotice['date']);
                    } else {
                        $this->assign('emergencyFeedEmpty', TRUE);
                    }
                }
                break;
                
            case 'index':
                $contactNavListItems = array();
                if($contactsController !== NULL) {
                    foreach($contactsController->getPrimaryContacts() as $contact) {
                        $contactNavListItems[] = self::contactNavListItem($contact);
                    }

                    if($contactsController->hasSecondaryContacts()) {
                        $contactNavListItems[] = array(
                            'title' => $this->getModuleVar('MORE_CONTACTS'),
                            'url' => $this->buildBreadcrumbURL('contacts', array()),
                        );
                    }
                    $this->assign('contactNavListItems', $contactNavListItems);
                }
                $this->assign('hasContacts', (count($contactNavListItems) > 0));

                $hasEmergencyFeed = ($emergencyNoticeController !== NULL);
                $this->assign('hasEmergencyFeed', $hasEmergencyFeed);
                if($hasEmergencyFeed) {
                    $emergencyNotice = $emergencyNoticeController->getLatestEmergencyNotice();
                    
                    if($emergencyNotice !== NULL) {
                        $this->assign('emergencyFeedEmpty', FALSE);             
                        $this->assign('title', $emergencyNotice['title']);
                        $this->assign('content', $emergencyNotice['text']);
                        $this->assign('date', $emergencyNotice['date']);
                    } else {
                        $this->assign('emergencyFeedEmpty', TRUE);
                    }
                }

                break;

            case 'contacts':
                $contactNavListItems = array();
                foreach($contactsController->getAllContacts() as $contact) {
                    $contactNavListItems[] = self::contactNavListItem($contact);
                }
                $this->assign('contactNavListItems', $contactNavListItems);
                break;
        }
        
    }


    protected static function contactNavListItem($contact) {
        return array(
            'title' => $contact->getTitle(),
            'subtitle' => $contact->getSubtitle() . ' (' . $contact->getPhoneDelimitedByPeriods() . ')',
            'url' => 'tel:' . $contact->getPhoneDialable(),
            'class' => 'phone',
        );
    }
}
    