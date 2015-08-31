<?php

/**
 * Copyright 2015 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
class SummitAppAdminController extends Page_Controller
{

    public function init()
    {
        parent::init();
        Requirements::css('themes/openstack/css/chosen.css');
        Requirements::css('summit/bower_components/bootstrap-tagsinput/dist/bootstrap-tagsinput.css');
        Requirements::css('summit/css/summit-admin.css');

        Requirements::javascript('summit/javascript/bootstrap-dropdown.js');
        Requirements::javascript('summit/bower_components/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js');
        Requirements::javascript('themes/openstack/javascript/chosen.jquery.min.js');
    }

    private static $url_segment = 'summit-admin';

    private static $allowed_actions = array(
        'directory',
        'dashboard',
        'publishedEvents',
        'pendingEvents',
        'editEvent',
        'presentationLists',
        'editPresentationList',
        'ticketTypes',
        'attendees',
        'editSummit',
    );

    private static $url_handlers = array (
        '$SummitID!/dashboard' => 'dashboard',
        '$SummitID!/events/published' => 'publishedEvents',
        '$SummitID!/events/unpublished' => 'pendingEvents',
        '$SummitID!/events/presentation-lists/$PresentationListId!'  => 'editPresentationList',
        '$SummitID!/events/presentation-lists'  => 'presentationLists',
        '$SummitID!/events/$EventID!'  => 'editEvent',
        '$SummitID!/tickets'  => 'ticketTypes',
        '$SummitID!/attendees'  => 'attendees',
        '$SummitID!/edit'  => 'editSummit'
    );

    /**
     * Ensure all root requests go to login
     * @return SS_HTTPResponse
     */
    public function index()
    {
        if(Member::currentUser())
            return $this->redirect($this->Link('directory'));
        return $this->redirect('/Security/login/?BackURL=/summit-admin');
    }

    public function Link($action = null)
    {
        return Controller::join_links($this->config()->url_segment, $action);
    }

    public function directory()
    {
        Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
        Requirements::javascript(Director::protocol()."ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js");
        Requirements::javascript("themes/openstack/javascript/jquery.validate.custom.methods.js");
        Requirements::javascript('marketplace/code/ui/admin/js/utils.js');
        Requirements::javascript('summit/javascript/summitapp-admin.js');
        $summits = Summit::get();
        return $this->getViewer('directory')->process
        (
          $this->customise
          (
              array
              (
                  'Summits' => $summits
              )
          )
        );
    }

    public function publishedEvents(SS_HTTPRequest $request)
    {
        $summit_id = intval($request->param('SummitID'));

        $summit = Summit::get()->byID($summit_id);

        Requirements::css('summit/css/simple-sidebar.css');
        Requirements::javascript('summit/javascript/simple-sidebar.js');

        $events = $summit->Events()->filter('Published', true);

        return $this->getViewer('publishedEvents')->process
        (
            $this->customise
            (
                array
                (
                    'Summit' => $summit,
                    'Events' => $events
                )
            )
        );
    }

    public function pendingEvents(SS_HTTPRequest $request)
    {
        $summit_id = intval($request->param('SummitID'));

        $summit = Summit::get()->byID($summit_id);

        Requirements::css('summit/css/simple-sidebar.css');
        Requirements::javascript('summit/javascript/simple-sidebar.js');

        $events = $summit->Events()->filter('Published', false);

        Requirements::javascript('summit/javascript/pending-events.js');

        return $this->getViewer('pendingEvents')->process
        (
            $this->customise
            (
                array
                (
                    'Summit' => $summit,
                    'Events' => $events
                )
            )
        );
    }

    public function presentationLists(SS_HTTPRequest $request)
    {
        $summit_id = intval($request->param('SummitID'));

        $summit = Summit::get()->byID($summit_id);

        Requirements::css('summit/css/simple-sidebar.css');
        Requirements::javascript('summit/javascript/simple-sidebar.js');

        $events = $summit->Events()->filter('Published', false);

        return $this->getViewer('presentationLists')->process
        (
            $this->customise
            (
                array
                (
                    'Summit' => $summit,
                )
            )
        );
    }

    public function editPresentationList(SS_HTTPRequest $request)
    {
        $summit_id = intval($request->param('SummitID'));

        $summit = Summit::get()->byID($summit_id);

        Requirements::css('summit/css/simple-sidebar.css');
        Requirements::javascript('summit/javascript/simple-sidebar.js');


        return $this->getViewer('editPresentationList')->process
        (
            $this->customise
            (
                array
                (
                    'Summit' => $summit,
                )
            )
        );
    }

    public function dashboard(SS_HTTPRequest $request)
    {
        $summit_id = intval($request->param('SummitID'));

        $summit = Summit::get()->byID($summit_id);

        Requirements::css('summit/css/simple-sidebar.css');
        Requirements::javascript('summit/javascript/simple-sidebar.js');
        return $this->getViewer('dashboard')->process
        (
            $this->customise
            (
                array
                (
                    'Summit' => $summit
                )
            )
        );
    }

    public function ticketTypes(SS_HTTPRequest $request)
    {
        $summit_id = intval($request->param('SummitID'));

        $summit = Summit::get()->byID($summit_id);

        Requirements::css('summit/css/simple-sidebar.css');
        Requirements::javascript('summit/javascript/simple-sidebar.js');
        return $this->getViewer('ticketTypes')->process
        (
            $this->customise
            (
                array
                (
                    'Summit' => $summit
                )
            )
        );
    }

    public function attendees(SS_HTTPRequest $request)
    {
        $summit_id = intval($request->param('SummitID'));

        $summit = Summit::get()->byID($summit_id);

        Requirements::css('summit/css/simple-sidebar.css');
        Requirements::javascript('summit/javascript/simple-sidebar.js');

        return $this->getViewer('attendees')->process
        (
            $this->customise
            (
                array
                (
                    'Summit' => $summit
                )
            )
        );
    }

    public function editEvent(SS_HTTPRequest $request)
    {
        $summit_id = intval($request->param('SummitID'));
        $summit = Summit::get()->byID($summit_id);
        $event_id = intval($request->param('EventID'));

        Requirements::css('summit/css/simple-sidebar.css');
        Requirements::javascript('summit/javascript/simple-sidebar.js');
        return $this->getViewer('EditEvent')->process
        (
            $this->customise
            (
                array
                (
                    'Summit' => $summit,
                    'Event' => null
                )
            )
        );
    }

    public function editSummit(SS_HTTPRequest $request)
    {
        Requirements::javascript('summit/javascript/summitapp-summitform.js');
        Requirements::javascript('summit/bower_components/bootstrap-tagsinput/dist/bootstrap-tagsinput.js');
        Requirements::css('summit/bower_components/bootstrap-tagsinput/dist/bootstrap-tagsinput.css');
        Requirements::css('summit/css/summit-admin.css');

        return $this->getViewer('EditSummit')->process($this->owner);
    }

    public function summitForm()
    {
        $summit_id = intval($this->request->param('SummitID'));
        $summit = Summit::get()->byID($summit_id);

        $form = SummitForm::create($summit,$this, "SummitForm", FieldList::create(FormAction::create('saveSummit','Save')));
        if($data = Session::get("FormInfo.{$form->FormName()}.data")) {
            $form->loadDataFrom($data);
        }
        else {
            $form->loadDataFrom($summit);
        }

        return $form;
    }

    public function saveSummit($data, $form) {
        Session::set("FormInfo.{$form->FormName()}.data", $data);
        /*if(empty(strip_tags($data['Bio']))) {
            $form->addErrorMessage('Bio','Please enter a bio', 'bad');
            return $this->redirectBack();
        }

        $speaker = Member::currentUser()->getCurrentSpeakerProfile();
        $form->saveInto($speaker);
        $speaker->write();

        $form->sessionMessage('Your bio has been updated', 'good');

        Session::clear("FormInfo.{$form->FormName()}.data", $data);*/

        return $this->redirectBack();
    }


}