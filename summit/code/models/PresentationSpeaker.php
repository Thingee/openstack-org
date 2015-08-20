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
        'BeenEmailed' => 'Boolean'
    );


    private static $has_one = array (
        'Photo'               => 'Image',
        'Member'              => 'Member',
        'Summit'              => 'Summit',
        'RegistrationRequest' => 'SpeakerRegistrationRequest',
    );

    private static $searchable_fields = array
    (
        'Member.Email',
        'FirstName',
        'LastName'
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
        'Presentations'      => 'Presentation',
    );

    /**
     * Gets a readable label for the speaker
     * 
     * @return  string
     */
    public function getName() {
        return "{$this->FirstName} {$this->LastName}";
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

    public function SpeakerConfirmHash() {
        $id = $this->ID;
        $prefix = "000";
        $hash = base64_encode($prefix . $id);
        return $hash;
    }

    public function RegistrationCode() {
        return SummitRegCode::get()->filter('MemberID', $this->MemberID)->first();
    }


}