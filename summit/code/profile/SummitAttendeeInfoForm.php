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
final class SummitAttendeeInfoForm extends BootstrapForm
{
    function __construct($controller, $name)
    {
        $fields = new FieldList
        (
            array
            (
                $t1 = new TextField('ExternalOrderId', 'Eventbrite Order #'),
                $checkbox = new CheckboxField('SharedContactInfo', 'Allow to share contact info?')
            )
        );

        $t1->setAttribute('placeholder', 'Enter your Eventbrite order #');
        $t1->addExtraClass('event-brite-order-number');

        $attendees = Session::get('attendees');

        if(count($attendees) > 0)
        {
            $t1->setValue(Session::get('ExternalOrderId'));
            $t1->setReadonly(true);
            $checkbox->setValue(intval(Session::get('SharedContactInfo')) === 1);

            $fields->add(new LiteralField('ctrl1','Current Order has following registered attendees, please select one:'));
            $options = array();
            foreach($attendees as $attendee)
            {
                $options[$attendee['id']] = $attendee['profile']['name'];
            }
            $attendees_ctrl = new OptionSetField('SelectedAttendee','', $options);
            $fields->add($attendees_ctrl);

            $validator = new RequiredFields(array('ExternalOrderId'));

            // Create action
            $actions = new FieldList
            (
                $btn_clear = new FormAction('clearSummitAttendeeInfo', 'Clear'),
                $btn = new FormAction('saveSummitAttendeeInfo', 'Done')
            );

            $btn->addExtraClass('btn btn-default active');
            $btn_clear->addExtraClass('btn btn-danger active');
        }
        else {


            $validator = new RequiredFields(array('ExternalOrderId'));
            // Create action
            $actions = new FieldList
            (
                $btn = new FormAction('saveSummitAttendeeInfo', 'Get Order')
            );

            $btn->addExtraClass('btn btn-default active');
        }

        parent::__construct($controller, $name, $fields, $actions, $validator);

    }

    public function loadDataFrom($data, $mergeStrategy = 0, $fieldList = null)
    {
        parent::loadDataFrom($data, $mergeStrategy, $fieldList);


        if($data && $data instanceof SummitAttendee && $data->ID > 0)
        {
            $this->fields->insertAfter($t1 = new TextField('TicketBoughtDate', 'Ticket Bought Date', $data->TicketBoughtDate),'ExternalOrderId');
            $t2 = $this->fields->fieldByName('ExternalOrderId');
            $this->fields->insertAfter($t3 = new TextField('TicketType', 'Ticket Type', $data->TicketType()->Name), 'TicketBoughtDate');

            $t1->setReadonly(true);
            $t2->setReadonly(true);
            $t3->setReadonly(true);

            $checkbox = $this->getField('SharedContactInfo');

            if(!is_null($checkbox))
                $checkbox->setValue(intval($data->SharedContactInfo) === 1);

            $btn = $this->Actions()->first();
            if(!is_null($btn))
                $btn->setTitle('Save');
        }

    }
}