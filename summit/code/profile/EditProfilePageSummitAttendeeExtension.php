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
class EditProfilePageSummitAttendeeExtension extends Extension
{

    /**
     * @var IEventbriteEventManager
     */
    private $manager;

    public function getEventbriteEventManager()
    {
        return $this->manager;
    }

    public function setEventbriteEventManager(IEventbriteEventManager $manager)
    {
        $this->manager = $manager;
    }

    public function onBeforeInit()
    {
        Config::inst()->update(get_class($this), 'allowed_actions', array
        (
            'attendeeInfoRegistration',
            'SummitAttendeeInfoForm',
            'saveSummitAttendeeInfo',
            'clearSummitAttendeeInfo',
        ));
    }

    public function getNavActionsExtensions(&$html)
    {
        $view = new SSViewer('EditProfilePage_SummitAttendeeNav');
        $html .= $view->process($this->owner);
    }

    public function getNavMessageExtensions(&$html){
        $view = new SSViewer('EditProfilePage_SummitAttendeeMessage');
        $html .= $view->process($this->owner);
    }

    public function UpcomingSummit()
    {
        return Summit::GetUpcoming();
    }

    public function CurrentSummit()
    {
        $current_summit = Summit::CurrentSummit();
        if(is_null($current_summit))
            $current_summit = $this->UpcomingSummit();
        return $current_summit;
    }


    public function attendeeInfoRegistration(SS_HTTPRequest $request)
    {
        //return $this->owner->customise(array())->renderWith(array('EditProfilePage_attendeeInfoRegistration', 'Page'));
        return $this->owner->getViewer('attendeeInfoRegistration')->process($this->owner);
    }

    public function SummitAttendeeInfoForm()
    {
        if ($current_member = Member::currentUser())
        {
            $form = new SummitAttendeeInfoForm($this->owner, 'SummitAttendeeInfoForm');
            //Populate the form with the current members data
            $attendee = $current_member->getCurrentSummitAttendee();
            if($attendee) $form->loadDataFrom($attendee->data());
            return $form;
        }
    }

    public function saveSummitAttendeeInfo($data, $form)
    {
        if ($current_member = Member::currentUser())
        {
            $attendee = $current_member->getCurrentSummitAttendee();
            if(!$attendee && !isset($data['SelectedAttendee']))
            {
                try
                {
                    $attendees = $this->manager->getOrderAttendees($data['ExternalOrderId']);
                    Session::set('attendees', $attendees);
                    Session::set('ExternalOrderId', $data['ExternalOrderId']);
                    return $this->owner->redirect($this->owner->Link('attendeeInfoRegistration?select=1'));
                }
                catch(InvalidEventbriteOrderStatusException $ex1)
                {
                    return $this->owner->redirect($this->owner->Link('attendeeInfoRegistration?error=1'));
                    Session::clear('attendees');
                    Session::clear('ExternalOrderId');
                }
            }
            if(isset($data['SelectedAttendee']))
            {
                // register attendee with current member
                $attendees = Session::get('attendees');
                $external_order_id = Session::get('ExternalOrderId');
                $external_attendee_id = $data['SelectedAttendee'];
                Session::clear('attendees');
                Session::clear('ExternalOrderId');
                $this->manager->registerAttendee($current_member, $external_order_id, $external_attendee_id);

                return $this->owner->redirect($this->owner->Link('attendeeInfoRegistration?saved=1'));
            }

        }
        return $this->owner->httpError(403);
    }

    public function clearSummitAttendeeInfo($data, $form)
    {
        Session::clear('attendees');
        Session::clear('ExternalOrderId');
        return $this->owner->redirect($this->owner->Link('attendeeInfoRegistration'));
    }
}