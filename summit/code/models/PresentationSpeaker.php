<?php

/**
 * Class PresentationSpeaker
 */
class PresentationSpeaker extends DataObject
implements IPresentationSpeaker
{

    private static $db = array (
        'FirstName' => 'Varchar',
        'LastName' => 'Varchar',
        'Title' => 'Varchar',
        'Bio' => 'HTMLText',
        'IRCHandle' => 'Varchar',
        'TwitterHandle' => 'Varchar',
        'AvailableForBureau' => 'Boolean',
        'FundedTravel' => 'Boolean',
        'Expertise' => 'Text',
        'Country' => 'Varchar(2)',
        'BeenEmailed' => 'Boolean',
        'AnnouncementEmailTypeSent' => "Enum('ACCEPTED,REJECTED,ALTERNATE,ACCEPTED_ALTERNATE,ACCEPTED_REJECTED,ALTERNATE_REJECTED,NONE','NONE')",
        'AnnouncementEmailSentDate' => 'SS_Datetime',
        'ConfirmedDate' => 'SS_Datetime',
        'OnSitePhoneNumber' => 'Text',
        'RegisteredForSummit' => 'Boolean'
    );


    private static $has_one = array
    (
        'Photo'               => 'Image',
        'Member'              => 'Member',
        'Summit'              => 'Summit',
        'RegistrationRequest' => 'SpeakerRegistrationRequest',
        'SummitRegistrationPromoCode' => 'SpeakerSummitRegistrationPromoCode'
    );

    private static $has_many = array
    (
        'Feedback' => 'PresentationSpeakerFeedback',
    );

    private static $searchable_fields = array
    (
        'Member.Email',
        'FirstName',
        'LastName',
        'AnnouncementEmailTypeSent'
    );

    private static $indexes = array
    (
        //'EmailAddress' => true
    );

    private static $defaults = array(
        'MemberID' => 0,
    );


    private static $belongs_many_many = array
    (
        'Presentations' => 'Presentation',
    );

    private static $summary_fields = array
    (
        'FirstName'  => 'LastName',
        'LastName' => 'LastName',
        'Member.Email' => 'Email',
        'AnnouncementEmailTypeSent' => 'Announcement Email Sent',
    );

    /**
     * Gets a readable label for the speaker
     * 
     * @return  string
     */
    public function getName() {
        return "{$this->FirstName} {$this->LastName}";
    }

    public function getTitle()
    {
        return sprintf('%s (%s)', $this->getName(), $this->Member()->Email);
    }

    /**
     * Helper method to link to this speaker, given an action
     * 
     * @param   $action
     * @return  string
     */
    protected function linkTo($presentationID, $action = null) {
        if($page = PresentationPage::get()->first()) {
            return Controller::join_links(
                $page->Link(),
                'manage',
                $presentationID,
                'speaker',
                $this->ID,
                $action
            );
        }
    }

    /**
     * Gets a link to edit this record
     * 
     * @return  string
     */
    public function EditLink($presentationID) {
        return $this->linkTo($presentationID, 'edit');
    }

    /**
     * Gets a link to delete this presentation
     * 
     * @return  string
     */
    public function DeleteLink($presentationID) {
        return $this->linkTo($presentationID, 'delete?t='.SecurityToken::inst()->getValue());
    }

    /**
     * Gets a link to the speaker's review page, as seen in the email. Auto authenticates.
     * @param Int $presentationID
     */
    public function ReviewLink($presentationID) {
        $action = 'review';
        if($this->isPendingOfRegistration()){
            $action .= '?'.SpeakerRegistrationRequest::ConfirmationTokenParamName.'='.$this->RegistrationRequest()->getToken();
        }
        return $this->linkTo($presentationID, $action);
    }


     public function getCMSFields() {
        $fields =  FieldList::create(TabSet::create("Root"))
            ->text('FirstName',"Speaker's first name")
            ->text('LastName', "Speaker's last name")
            ->text('Title', "Speaker's title")
            ->tinyMCEEditor('Bio',"Speaker's Bio")
            ->text('IRCHandle','IRC Handle (optional)')
            ->text('TwitterHandle','Twitter Handle (optional)')
            ->imageUpload('Photo','Upload a speaker photo')
            ->memberAutoComplete('Member', 'Member');

         if($this->ID > 0)
         {
             // presentations
             $config = GridFieldConfig_RelationEditor::create();
             $config->removeComponentsByType('GridFieldAddNewButton');
             $gridField = new GridField('Presentations', 'Presentations', $this->Presentations(), $config);
             $fields->addFieldToTab('Root.Presentations', $gridField);

             //speaker feedback

             $config = GridFieldConfig_RecordEditor::create();
             $config->removeComponentsByType('GridFieldAddNewButton');
             $gridField = new GridField('Feedback', 'Feedback', $this->Feedback(), $config);
             $fields->addFieldToTab('Root.Feedback', $gridField);
         }

         return $fields;
    }

    public function AllPresentations() {
        return $this->Presentations()->filter(array(
            'Status' => 'Received'
        ));    
    }

    public function MyPresentations() {
        return Summit::get_active()->Presentations()->filter(array(
            'CreatorID' => $this->MemberID
        ));
    }


    public function OtherPresentations() {
        return $this->Presentations()->exclude(array(
            'CreatorID' => $this->MemberID
        ));        
    }

    /**
     * @return int
     */
    public function getIdentifier()
    {
        return (int)$this->getField('ID');
    }

    /**
     * @return bool
     */
    public function isPendingOfRegistration()
    {
        return $this->MemberID == 0 ;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
       return $this->MemberID > 0 ? $this->Member()->Email : $this->RegistrationRequest()->Email;
    }

    /**
     * @param ICommunityMember $member
     * @return void
     */
    public function associateMember(ICommunityMember $member)
    {
        $this->MemberID              = $member->getIdentifier();
        //$this->RegistrationRequestID = 0;
    }

    public function clearBeenEmailed() {
        $this->BeenEmailed = false;
        $this->write();
    }

    public function AcceptedPresentations() {
        $AcceptedPresentations = new ArrayList();

        $Presentations = $this->Presentations('`SummitID` = '.Summit::get_active()->ID);
        foreach ($Presentations as $Presentation) {
            if($Presentation->SelectionStatus() == "accepted") $AcceptedPresentations->push($Presentation);
        }

        return $AcceptedPresentations;
    }

    public function UnacceptedPresentations() {
        $UnacceptedPresentations = new ArrayList();

        $Presentations = $this->Presentations('`SummitID` = '.Summit::get_active()->ID);
        foreach ($Presentations as $Presentation) {
            if($Presentation->SelectionStatus() == "unaccepted") $UnacceptedPresentations->push($Presentation);
        }

        return $UnacceptedPresentations;
    }

    public function AlternatePresentations() {
        $AlternatePresentations = new ArrayList();

        $Presentations = $this->Presentations('`SummitID` = '.Summit::get_active()->ID);
        foreach ($Presentations as $Presentation) {
            if($Presentation->SelectionStatus() == "alternate") $AlternatePresentations->push($Presentation);
        }

        return $AlternatePresentations;
    }

    public function getSpeakerConfirmHash() {
        $id = $this->ID;
        $prefix = "000";
        $hash = base64_encode($prefix . $id);
        return $hash;
    }

    public function getSpeakerConfirmationLink()
    {
        $confirmation_page = SummitConfirmSpeakerPage::get()->filter('SummitID', Summit::get_active()->ID)->first();
        if(!$confirmation_page) throw new Exception('Confirmation Speaker Page not set on current summit!');
        $url = $confirmation_page->getAbsoluteLiveLink(false);
        $url = $url.'confirm?h='.$this->getSpeakerConfirmHash();
        return $url;
    }

    /**
     * @return bool
     */
    public function announcementEmailAlreadySent()
    {
        $email_type = $this->getAnnouncementEmailTypeSent();
        return !is_null($email_type) && $email_type !== 'NONE';
    }

    /**
     * @return string|null
     */
    public function getAnnouncementEmailTypeSent()
    {
       return $this->getField('AnnouncementEmailTypeSent');
    }

    /**
     * @param string $email_type
     * @throws Exception
     */
    public function registerAnnouncementEmailTypeSent($email_type)
    {
        if($this->announcementEmailAlreadySent()) throw new Exception('Announcement Email already sent');
        $this->AnnouncementEmailTypeSent = $email_type;
        $this->AnnouncementEmailSentDate = MySQLDatabase56::nowRfc2822();
    }

    /**
     * @return bool
     */
    public function hasRejectedPresentations()
    {
        return $this->UnacceptedPresentations()->count() > 0;
    }

    /**
     * @return bool
     */
    public function hasApprovedPresentations()
    {
        return $this->AcceptedPresentations()->count() > 0;
    }

    /**
     * @return bool
     */
    public function hasAlternatePresentations()
    {
        return $this->AlternatePresentations()->count() > 0;
    }

    /**
     * @param ISpeakerSummitRegistrationPromoCode $promo_code
     * @return $this
     */
    public function registerSummitPromoCode(ISpeakerSummitRegistrationPromoCode $promo_code)
    {
        $member = AssociationFactory::getInstance()->getMany2OneAssociation($this,'Member')->getTarget();
        $member->registerPromoCode($promo_code);
        $promo_code->assignSpeaker($this);
        AssociationFactory::getInstance()->getMany2OneAssociation($this,'SummitRegistrationPromoCode')->setTarget($promo_code);
    }

    /**
     * @return bool
     */
    public function hasSummitPromoCode()
    {
       $code = $this->getSummitPromoCode();
       return !is_null($code);
    }

    /**
     * @return ISpeakerSummitRegistrationPromoCode
     */
    public function getSummitPromoCode()
    {
        return AssociationFactory::getInstance()->getMany2OneAssociation($this,'SummitRegistrationPromoCode')->getTarget();
    }

    function ProfilePhoto($width=100){
        $img = $this->Photo();
        $twitter_name = $this->TwitterHandle;
        if(!is_null($img)  && $img->exists() && Director::fileExists($img->Filename)){
            $img = $img->SetWidth($width);
            return "<img alt='{$this->ID}_profile_photo' src='{$img->getURL()}' class='member-profile-photo'/>";
        } elseif (!empty($twitter_name)) {
            if ($width < 100) {
                return '<img src="https://twitter.com/'.$twitter_name.'/profile_image?size=normal" />';
            } else {
                return '<img src="https://twitter.com/'.$twitter_name.'/profile_image?size=bigger" />';
            }
        } else {
            if ($width < 100) {
                return "<img src='themes/openstack/images/generic-profile-photo-small.png'/>";
            } else {
                return "<img src='themes/openstack/images/generic-profile-photo.png'/>";
            }
        }
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canView($member = null) {
        return Permission::check("ADMIN") || Permission::check("ADMIN_SUMMIT_APP") || Permission::check("ADMIN_SUMMIT_APP_SCHEDULE");
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canEdit($member = null) {
        return Permission::check("ADMIN") || Permission::check("ADMIN_SUMMIT_APP") || Permission::check("ADMIN_SUMMIT_APP_SCHEDULE");
    }
}