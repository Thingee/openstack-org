<?php
/**
 * Copyright 2014 Openstack Foundation
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
/**
 * Used to vote on summit presentations
 */

class OldPresentationVotingPage extends Page {
  static $db = array(
  );
  static $has_one = array(
  );
  static $defaults = array(
        'ShowInMenus' => false
  );

  // Used to filter searches to only the presentations we want to see
  static $talk_limit_clause = ' AND MarkedToDelete IS NULL';

}
 
class OldPresentationVotingPage_Controller extends Page_Controller {

    static $allowed_actions = array(
          'SpeakerVotingLoginForm',
          'Presentation',
          'Category',
          'SaveRating',
          'Done',
          'FullPresentationList',
          'ShowFullPresentationList',
          'SearchForm'
    );

    function init() {
      if (!$this->request->param('Action')) $this->redirect($this->Link().'Presentation/');

      parent::init();
        
      Requirements::clear();
      Requirements::javascript('themes/openstack/javascript/jquery.min.js');
      Requirements::javascript('themes/openstack/javascript/bootstrap.min.js');
      Requirements::javascript('themes/openstack/javascript/bootstrap.min.js');
      Requirements::javascript('themes/openstack/javascript/presentationeditor/mousetrap.min.js');
      Requirements::javascript('themes/openstack/javascript/speaker-voting.js');                
        
    }

    function CategoryList() {

      $summit = Summit::get_active();

      if(!$summit) return $this->httpError(404, "That summit could not be found.");
      if(!$summit->exists()) return $this->httpError(404, "There is no active summit.");
      
      $categories = array ();

        foreach($summit->Categories() as $c) {
          $insert['ID'] = $c->ID;
          $insert['Name'] = $c->Title; 
          $categories[] = $insert;
        }

      return $categories;

    }  


    function LoggedOutPresentationList($catID) {

        $summit = Summit::get_active();

        if ($catID) {

            $filter = array (
                'CategoryID' => $catID,
                'Status' => 'Received'
            );

        } else {

            $filter = array (
                'Status' => 'Received'
            );

        }

        return Summit::get_active()->Presentations()->filter($filter);

    }
    
    function MemberPresentationList() {
        $member = Member::currentUser();
        $catID = Session::get('CategoryID');
        return $member->getRandomisedPresentations($catID);
    }

    // Render category buttons
    function CategoryLinks() {

      $items = new ArrayList();
      $Categories = $this->CategoryList();

      foreach($Categories as $Category) {
        $items->push( new ArrayData( $Category ) ); 
      }

      return $items;

    }

    function Category() {
      $ID = $this->request->param("ID");

      if($ID == 'All') {
        Session::clear('CategoryID');
        $Category = NULL;
      } elseif($ID) {
        Session::set('CategoryID',$ID);
      }
        
      $member = Member::currentUser();
    
      if($member) {
        $url = $member->getRandomisedPresentations($ID)->first()->ID;
      } else {
        $url = $this->LoggedOutPresentationList($ID)->first()->ID;
      }
        
          
      $this->redirect($this->Link().'Presentation/'.$url);

    }

    function PresentationByID($ID) {
      // Clean ID to be safe
      $ID = Convert::raw2sql($ID);
      if(is_numeric($ID)) {
        $Presentation = Presentation::get()->byID($ID);
        return $Presentation;
      }
    }

    function SearchForm() {
      $SearchForm = new PresentationVotingSearchForm($this, 'SearchForm');
      $SearchForm->disableSecurityToken();
      return $SearchForm;
    }

    function doSearch($data, $form) {

      $Results = NULL;
        
      $summitID = Summit::get_active()->ID;

      $presentations = Presentation::get()
        ->where("SummitEvent.SummitID = {$summitID}");

      if($data['Search'] && strlen($data['Search']) > 1) {

           $k = Convert::raw2sql($data['Search']);

            $result = $presentations
              ->leftJoin("Presentation_Speakers", "Presentation_Speakers.PresentationID = Presentation.ID")
              ->leftJoin("PresentationSpeaker", "PresentationSpeaker.ID = Presentation_Speakers.PresentationSpeakerID")
              ->where("
                  SummitEvent.Title LIKE '%{$k}%' 
                  OR SummitEvent.Description LIKE '%{$k}%'
                  OR PresentationSpeaker.FirstName LIKE '%{$k}%'
                  OR PresentationSpeaker.LastName LIKE '%{$k}%'
            ");
        }   

	           
      // Clear the category if one was set
      Session::set('CategoryID',NULL);
      $data["SearchMode"] = TRUE;
      if($result) $data["SearchResults"] = $result;

      return $this->Customise($data);

   }

   function ShowIntro() {
      $MemberID = Member::currentUserID();
      If ($MemberID) {
        $Votes = SpeakerVote::get()->filter('VoterID', $MemberID);
        if(!$Votes && !(Session::get('IntroShown'))) {
          Session::set('IntroShown',TRUE);
          return 'yes';
        }
      } else {
        return 'no';
      }
      return 'no';

   }

    function CurrentVote($PresID) {
      if(Member::currentUserID()) {
        $PresentationVote = PresentationVote::get()->filter(array('MemberID'=>Member::currentUserID(),'PresentationID'=>$PresID))->first();
        if ($PresentationVote) return $PresentationVote->Vote;
      }
    }

    function RandomPresentationID($Category = NULL) {

      $Result = NULL;
      $CategoryID = $Category;

      $currentMemberID = Member::currentUserID();

      $summitID = Summit::get_active()->ID;
      $presentations = Presentation::get()
        ->where("SummitEvent.SummitID = {$summitID}");

      if($CategoryID) $presentations = $presentations->filter('CategoryID', $CategoryID);
      // if($currentMemberID) {
      //     $presentations = $presentations
      //                     //->leftJoin("PresentationVote", "PresentationVote.PresentationID = Presentation.ID")
      //                     ->where("IFNULL(PresentationVote.MemberID,0) = " . Member::currentUserID());              
      // }

      if($presentations->count()) $Result = $presentations->first();

      if($Result) {
        return $Result->ID;
      } else {
        return 'none';
      }

    }
    
    function Done() {

      $Member = Member::currentUser();

      if($Member) {
          
          $data = array();

          $CategoryID = Session::get('CategoryID');
          if(is_numeric($CategoryID)) $Category = PresentationCategory::get()->byID($CategoryID);
          if(isset($Category)) $data["CategoryName"] = $Category->Title;

          $Subject = 'Voting Event';


          if(isset($Category)) {
            $Body = $Member->FirstName . ' ' . $Member->Surname . ' just completed voting for all presentations in the category ' . $Category->Name;
          } else {
            $Body = $Member->FirstName . ' ' . $Member->Surname . ' just completed voting for every single presentation listed!';
          }

          //return our $Data to use on the page
          return $this->Customise($data);
      }

    }    


    // Used as a URL action to display a presentation
    function Presentation() {

      $presID = $this->request->param("ID");

      //set headers to NOT cache a page
      header("Cache-Control: no-cache, max-age=0, must-revalidate, no-store"); //HTTP 1.1

      // Send them to the done page if they are finished
      if($presID == 'none') {
        $this->redirect($this->Link().'Done');
        return;
      }

      // Otherwise, look for an ID
      if($presID) {
        $presentation = $this->PresentationByID($presID);

      } else {
        $CategoryID = Session::get('CategoryID');        
        $this->redirect($this->Link().'Presentation/'.$this->RandomPresentationID($CategoryID));
        return;
      }

      if($presentation) {
        $data["Presentation"] = $presentation;
        $data["VoteValue"] = $this->CurrentVote($presentation->ID);
        
        $CategoryID = Session::get('CategoryID');
        $data["CategoryID"] = $CategoryID;
        
        if(is_numeric($CategoryID)) $Category = PresentationCategory::get()->byID($CategoryID);
        if(isset($Category)) $data["CategoryName"] = $Category->Title;

        $data["Search"] = isset($_GET['s']) ? $_GET['s'] : null;
        if(isset($data['Search']) && strlen($data['Search']) > 1) {
            $data["PresentationWithSearch"] = TRUE;
            return $this->doSearch($data, null);
        }
        else {
            //return our $Data to use on the page
            return $this->Customise($data);
        }
      } else {
        //Talk not found
        return $this->httpError(404, 'Sorry that talk could not be found');
      }
    }

    function ClientIP() {
      $inSSL = ( isset($_SERVER['SSL']) || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ) ? true : false;
      if($inSSL) {
        $clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
      } else {
        $clientIP = $_SERVER['REMOTE_ADDR'];
      }
      return $clientIP;
    }

    /* function SpeakerVotingLoginForm() {
      $URLSegment = $this->request->param("ID");    
      Session::set('BackURL',$this->Link().'/Presentation/'.$URLSegment);
      $SpeakerVotingLoginForm = new SpeakerVotingLoginForm($this, 'SpeakerVotingLoginForm');
      return $SpeakerVotingLoginForm;
    } */


    function SaveRating() {

      if(!Member::currentUserID()) {
          return Security::permissionFailure($this);
      }

      $rating = '';
      $TalkID = '';

      if(isset($_GET['rating']) && is_numeric($_GET['rating'])) {
        $rating = $_GET['rating'];
      }

      if(isset($_GET['id']) && is_numeric($_GET['id'])) {
        $presentationID = $_GET['id'];
      }

      $Member = member::currentUser();

      $validRatings = array(-1,0,1,2,3);

      if($Member && isset($rating) && (in_array((int)$rating, $validRatings, true)) && $presentationID) {

        $previousVote = PresentationVote::get()->filter(array('PresentationID'=>$presentationID,'MemberID'=>$Member->ID))->first();
          
        $presentation = Presentation::get()->byID($presentationID);
        $CategoryID = Session::get('CategoryID');

        if(!$previousVote) {
          $vote = new PresentationVote;
          $vote->PresentationID = $presentationID;
          $vote->Vote = $rating;
          $vote->IP = $this->ClientIP();
          $vote->MemberID = $Member->ID;
          $vote->write();
          
          $this->redirectBack();
    
        } else {
          $previousVote->Vote = $rating;
          $previousVote->IP = $this->ClientIP();
          $previousVote->write();

          $this->redirectBack();

        }
        
      } else {
        return 'no rating saved.';
      }
    }


}
