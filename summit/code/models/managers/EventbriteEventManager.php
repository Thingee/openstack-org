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
final class EventbriteEventManager implements IEventbriteEventManager
{

    /**
     * @var IEventbriteEventRepository
     */
    private $repository;

    /**
     * @var ITransactionManager
     */
    private $tx_manager;

    /**
     * @var IEventbriteEventFactory
     */
    private $factory;

    /**
     * @var IEventbriteRestApi
     */
    private $api;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitAttendeeFactory
     */
    private $attendee_factory;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitAttendeeTicketRepository
     */
    private $ticket_repository;

    public function __construct
    (
        IEventbriteEventRepository $repository,
        IEventbriteEventFactory $factory,
        IEventbriteRestApi $api,
        IMemberRepository $member_repository,
        ISummitAttendeeFactory $attendee_factory,
        ISummitAttendeeRepository $attendee_repository,
        ISummitRepository $summit_repository,
        ISummitAttendeeTicketRepository $ticket_repository,
        ITransactionManager $tx_manager
    ) {
        $this->repository          = $repository;
        $this->factory             = $factory;
        $this->api                 = $api;
        $this->member_repository   = $member_repository;
        $this->attendee_factory    = $attendee_factory;
        $this->attendee_repository = $attendee_repository;
        $this->summit_repository   = $summit_repository;
        $this->ticket_repository   = $ticket_repository;
        $this->tx_manager          = $tx_manager;

        $this->api->setCredentials(array('token' => EVENTBRITE_PERSONAL_OAUTH2_TOKEN));
    }

    /**
     * @param string $type
     * @param string $api_url
     * @return IEventbriteEvent
     */
    public function registerEvent($type, $api_url)
    {
        $repository = $this->repository;
        $factory = $this->factory;

        return $this->tx_manager->transaction(function () use ($type, $api_url, $repository, $factory) {

            $old_one = $repository->getByApiUrl($api_url);
            if ($old_one) {
                throw new EntityAlreadyExistsException('EventbriteEvent', sprintf("type %s - url %s", $type, $api_url));
            }

            $new_event = $factory->build($type, $api_url);

            $repository->add($new_event);

            return $new_event;
        });
    }

    /**
     * @param int $bach_size
     * @param IMessageSenderService $invite_sender
     * @param IMessageSenderService $create_sender
     * @return mixed
     */
    public function ingestEvents($bach_size, IMessageSenderService $invite_sender, IMessageSenderService $create_sender)
    {
        $repository          = $this->repository;
        $api                 = $this->api;
        $member_repository   = $this->member_repository;
        $attendee_factory    = $this->attendee_factory;
        $attendee_repository = $this->attendee_repository;
        $summit_repository   = $this->summit_repository;
        $ticket_repository   = $this->ticket_repository;

        return $this->tx_manager->transaction(function () use (
            $bach_size,
            $repository,
            $api,
            $member_repository,
            $attendee_factory,
            $attendee_repository,
            $summit_repository,
            $ticket_repository,
            $invite_sender,
            $create_sender
        ) {

            list($list, $count) = $repository->getUnprocessed(0, $bach_size);
            $qty = 0;
            foreach ($list as $event)
            {
                $json_data = $api->getEntity($event->getApiUrl(), array('expand' => 'attendees'));
                if (isset($json_data['attendees']))
                {
                    $order_date       = $json_data['created'];
                    $status           = $json_data['status'];

                    if($status === 'placed')
                    {
                        foreach ($json_data['attendees'] as $attendee)
                        {
                            $profile         = $attendee['profile'];
                            $email           = $profile['email'];
                            $external_id     = $attendee['id'];
                            $answers         = $attendee['answers'];
                            $bought_date     = $attendee['created'];
                            $changed_date    = $attendee['changed'];
                            $order_id        = $attendee['order_id'];
                            $event_id        = $attendee['event_id'];
                            $ticket_class_id = $attendee['ticket_class_id'];
                            $cancelled       = $attendee['cancelled'];
                            $refunded        = $attendee['refunded'];
                            $status          = $attendee['status'];

                            if($cancelled || $refunded) continue;
                            if($status !== 'Attending') continue;

                            if (empty($email))
                            {
                                continue;
                            }
                            $member = $member_repository->findByEmail($email);

                            if (is_null($member)) {
                                // member not found ... send email to invite to openstack.org membership
                                echo sprintf("sending email (invite) to %s", $email).PHP_EOL;
                                $invite_sender->send($email);
                                continue;
                            }
                            $current_summit = $summit_repository->getByExternalEventId($event_id);

                            if (!$current_summit)
                            {
                                continue;
                            }

                            $ticket_type = $current_summit->findTicketTypeByExternalId($ticket_class_id);

                            if (!$ticket_type)
                            {
                                continue;
                            }

                            $old_attendee = $attendee_repository->getByMemberAndSummit
                            (
                                $member->getIdentifier(),
                                $current_summit->getIdentifier()
                            );

                            $old_ticket = $ticket_repository->getByExternalOrderIdAndExternalAttendeeId
                            (
                                $order_id,
                                $external_id
                            );

                            $ticket     = $attendee_factory->buildTicket($external_id , $order_id, $bought_date, $changed_date, $ticket_type);
                            if(!is_null($old_ticket)){
                                $former_attendee = $old_ticket->Owner();
                                if(is_null($old_attendee) || $old_attendee->getIdentifier() != $former_attendee->getIdentifier()){
                                    echo sprintf("(ticket reassignment) current member %s (%s) - former attendee %s - former member id %s (%s)", $member->ID, $email,  $former_attendee->getMemberFullName(), $former_attendee->Member()->ID, $former_attendee->Member()->Email).PHP_EOL;
                                    echo sprintf("(ticket reassignment) deleting former attendee %s", $former_attendee->getMemberFullName()).PHP_EOL;
                                    $former_attendee->delete();
                                }
                            }

                            if ($old_attendee)
                            {
                                if($old_attendee->hasTicket($ticket)){
                                    echo sprintf("old attendee %s member id %s (%s) already has ticket for external order id %s", $old_attendee->getMemberFullName(), $old_attendee->Member()->ID, $old_attendee->Member()->Email, $order_id).PHP_EOL;
                                    continue;
                                }
                                $attendee = $old_attendee;
                            }
                            else
                            {
                                $attendee = $attendee_factory->build
                                (
                                    $member,
                                    $current_summit
                                );
                                // send email informing that u became attendee...
                                echo sprintf("sending email (association) to %s (%s)", $attendee->getMemberFullName(), $email).PHP_EOL;
                                $create_sender->send($attendee);
                            }
                            $attendee->addTicket($ticket);
                            $attendee_repository->add($attendee);
                        }

                        $external_summit_id = $json_data['event_id'];
                        // associate the corresponding summit
                        $summit             = $summit_repository->getByExternalEventId($external_summit_id);
                        if(is_null(!$summit))
                            $event->SummitID = $summit->getIdentifier();
                    }
                }
                $event->markAsProcessed($status);
                $qty++;
            };
            return $qty;
        });
    }

    /**
     * @param $member
     * @param string $external_summit_id
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @param string $external_ticket_class_id
     * @param string $bought_date
     * @param bool $shared_contact_info
     * @return ISummitAttendee
     * @throws NotFoundEntityException
     * @throws RedeemTicketException
     */
    public function registerAttendee
    (
        $member,
        $external_summit_id,
        $external_order_id,
        $external_attendee_id,
        $external_ticket_class_id,
        $bought_date,
        $shared_contact_info = false
    )
    {
        $repository          = $this->repository;
        $member_repository   = $this->member_repository;
        $attendee_factory    = $this->attendee_factory;
        $attendee_repository = $this->attendee_repository;
        $summit_repository   = $this->summit_repository;

        return $this->tx_manager->transaction(function () use
        (
            $member,
            $external_summit_id,
            $external_order_id,
            $external_attendee_id,
            $external_ticket_class_id,
            $bought_date,
            $shared_contact_info,
            $repository,
            $member_repository,
            $attendee_factory,
            $attendee_repository,
            $summit_repository
        )
        {
            $summit = $summit_repository->getByExternalEventId($external_summit_id);

            if(is_null($summit))
                throw new NotFoundEntityException('Summit', sprintf('external_summit_id %s', $external_summit_id));

            $ticket_type = $summit->findTicketTypeByExternalId($external_ticket_class_id);

            if(is_null($ticket_type))
                throw new NotFoundEntityException
                (
                    'SummitTicketType',
                    sprintf
                    (
                        'external_ticket_class_id %s',
                        $external_ticket_class_id
                    )
                );

            $ticket = $this->createTicket($external_order_id, $external_attendee_id, $bought_date, $ticket_type, $member);

            $attendee = $attendee_factory->build
            (
                $member,
                $summit
            );
            $attendee->setShareContactInfo($shared_contact_info);

            $attendee->addTicket($ticket);
            $attendee_repository->add($attendee);

            return $attendee;
        });
    }

    /**
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @param string $bought_date
     * @param ISummitTicketType $ticket_type
     * @param $member
     * @throws NotFoundEntityException
     * @throws RedeemTicketException
     */
    public function createTicket($external_order_id, $external_attendee_id, $bought_date, $ticket_type, $member) {

        $attendee_factory = $this->attendee_factory;

        return $this->tx_manager->transaction(function () use
        (
            $member,
            $external_order_id,
            $external_attendee_id,
            $bought_date,
            $ticket_type,
            $attendee_factory
        )
        {
            $old_ticket = SummitAttendeeTicket::get()->filter(array
            (
                'ExternalOrderId'    => $external_order_id,
                'ExternalAttendeeId' => $external_attendee_id,
            ))->first();

            if(!is_null($old_ticket)) {
                if ($old_ticket->OwnerID > 0) {
                    // save intent and throw the exception

                    $redeem_error = new RedeemTicketError();
                    $redeem_error->ExternalOrderId    = $external_order_id;
                    $redeem_error->ExternalAttendeeId = $external_attendee_id;
                    $redeem_error->OriginatorID       = $member->ID;
                    $redeem_error->OriginalOwnerID    = $old_ticket->OwnerID;
                    $redeem_error->OriginalTicketID   = $old_ticket->ID;


                    $exception = new RedeemTicketException
                    (
                        sprintf
                        (
                            'Ticket already redeem external_order_id %s - external_attendee_id %s - old attendee id %s - current member id %s !',
                            $external_order_id,
                            $external_attendee_id,
                            $old_ticket->OwnerID,
                            $member->ID
                        )
                    );
                    $exception->setRedeemTicketError($redeem_error);

                    throw $exception;
                }
                $old_ticket->delete();
            }

            $ticket = $attendee_factory->buildTicket($external_attendee_id , $external_order_id, $bought_date, $bought_date, $ticket_type);

            return $ticket;
        });

    }

    /**
     * @param $order_external_id
     * @return mixed
     * @throws InvalidEventbriteOrderStatusException
     * @throws NotFoundEntityException
     */
    public function getOrderAttendees($order_external_id)
    {
        try {
            if (is_null($order_external_id))
                throw new InvalidEventbriteOrderStatusException('invalid');
            $order = $this->api->getOrder($order_external_id);
            if (isset($order['attendees'])) {
                $status = $order['status'];

                if ($status !== 'placed') throw new InvalidEventbriteOrderStatusException($status);

                $attendees = array();
                foreach ($order['attendees'] as $a) {
                    $attendees[$a['id']] = $a;
                }

                return $attendees;
            }
        }
        catch(GuzzleHttp\Exception\ClientException $ex){
            SS_Log::log($ex->getMessage(), SS_Log::WARN);
            throw new NotFoundEntityException(sprintf("order # %s does not exists!",$order_external_id));
        }
    }

    /**
     * @param ISummit $summit
     * @param $ticket_id
     * @param $member_id
     * @return mixed
     */
    public function reassignTicket(ISummit $summit, $ticket_id, $member_id)
    {
        $attendee_repository = $this->attendee_repository;
        $attendee_factory    = $this->attendee_factory;

        return $this->tx_manager->transaction(function() use($summit, $ticket_id, $member_id, $attendee_repository, $attendee_factory){

            if(!$ticket_id)
                throw new EntityValidationException('missing required param: id');

            $ticket = SummitAttendeeTicket::get()->byID($ticket_id);

            if (!$member_id) {
                $ticket->delete();
            } else {
                $attendee = $attendee_repository->getByMemberAndSummit($member_id,$summit->getIdentifier());
                $previous_owner = $ticket->Owner();

                if ($attendee) {
                    if ($attendee->Tickets()->count() > 0) {
                        throw new EntityValidationException('This member is already assigned to another tix');
                    } else {
                        $previous_owner->Tickets()->remove($ticket);
                        $attendee->Tickets()->add($ticket);
                    }
                } else {
                    $previous_owner->Tickets()->remove($ticket);
                    $member = Member::get()->byID($member_id);

                    $attendee = $attendee_factory->build($member, $summit);
                    $attendee->Tickets()->add($ticket);
                }

                $attendee->write();

                // if the attendee has no more tickets we delete it
                if ($previous_owner->Tickets()->count() == 0) {
                    $previous_owner->delete();
                }

                return $attendee;
            }
        });
    }
}