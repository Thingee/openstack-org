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

/**
 * Class Survey_Controller
 */
class Survey_Controller extends Page_Controller {

    const RoutePrefix  = 'surveys';

    static $allowed_actions_without_auth = array(
        'LandingPage',
        'RegisterForm',
        'MemberStart',
        'StartSurvey'
    );

    static $allowed_actions = array
    (
        'LandingPage',
        'RenderSurvey',
        'SurveyStepForm',
        'SkipStep',
        'RegisterForm',
        'MemberStart',
        'StartSurvey',
        'NextStep',
        'SurveyDynamicEntityStepForm',
        'NextDynamicEntityStep',
        'AddEntity',
        'EditEntity',
        'DeleteEntity',
        'suggestMember',
        'suggestOrganization',
        'addTeamMember',
        'deleteTeamMember',
        'getTeamMembers'
    );

    static $url_handlers = array
    (
        'landing'                                                         => 'LandingPage',
        'current/$STEP_SLUG/add-entity'                                   => 'AddEntity',
        'current/$STEP_SLUG/skip-step'                                    => 'SkipStep',
        'current/$STEP_SLUG/edit/$ENTITY_SURVEY_ID//$SUB_STEP_SLUG'       => 'EditEntity',
        'current/$STEP_SLUG/delete/$ENTITY_SURVEY_ID'                     => 'DeleteEntity',
        'current//$STEP_SLUG'                                             => 'RenderSurvey',
        'team-members/suggest'                                            => 'suggestMember',
        'GET entity-surveys/$ENTITY_SURVEY_ID/team-members'               => 'getTeamMembers',
        'POST entity-surveys/$ENTITY_SURVEY_ID/team-members/$MEMBER_ID'   => 'addTeamMember',
        'DELETE entity-surveys/$ENTITY_SURVEY_ID/team-members/$MEMBER_ID' => 'deleteTeamMember',
        'organizations/suggest'                                           => 'suggestOrganization',
        '$Action//$ID/$OtherID'                                           => 'handleAction',
    );

    /**
     * @var ISurvey
     */
    private $current_survey;

    /**
     * @var IEntitySurvey
     */
    private $current_entity_survey;

    /**
     * @var ISurveyManager
     */
    private $survey_manager;

    /**
     * @var ISurveyRepository
     */
    private $survey_repository;

    /**
     * @return ISurveyManager
     */
    public function getSurveyManager()
    {
        return $this->survey_manager;
    }

    /**
     * @param ISurveyManager $survey_manager
     */
    public function setSurveyManager(ISurveyManager $survey_manager)
    {
        $this->survey_manager = $survey_manager;
    }

    /**
     * @return ISurveyRepository
     */
    public function getSurveyRepository()
    {
        return $this->survey_repository;
    }

    /**
     * @param ISurveyRepository $survey_repository
     */
    public function setSurveyRepository(ISurveyRepository $survey_repository)
    {
        $this->survey_repository = $survey_repository;
    }

    function init()
    {
        $this->current_survey = $this->current_entity_survey = null;

        parent::init();

        Requirements::css(Director::protocol() . '://maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css');
        Requirements::css('survey_builder/css/user-survey.css');
        Requirements::css("themes/openstack/css/chosen.css", "screen,projection");
        Requirements::css("themes/openstack/javascript/jquery-ui-1.10.3.custom/css/smoothness/jquery-ui-1.10.3.custom.min.css");

        Requirements::block(SAPPHIRE_DIR . '/thirdparty/behaviour/behaviour.js');
        Requirements::block(SAPPHIRE_DIR . '/thirdparty/prototype/prototype.js');
        Requirements::block(SAPPHIRE_DIR . '/javascript/prototype_improvements.js');
        Requirements::block("themes/openstack/javascript/jquery.autocomplete.min.js");

        Requirements::javascript(Director::protocol() . "ajax.aspnetcdn.com/ajax/jquery.validate/1.13.1/jquery.validate.js");
        Requirements::javascript(Director::protocol() . "ajax.aspnetcdn.com/ajax/jquery.validate/1.13.1/additional-methods.js");
        Requirements::javascript("themes/openstack/javascript/chosen.jquery.min.js");
        Requirements::javascript("themes/openstack/javascript/jquery-ui-1.10.3.custom/js/jquery-ui-1.10.3.custom.js");
        Requirements::javascript('survey_builder/js/survey.validation.rules.jquery.js');
        Requirements::javascript('themes/openstack/javascript/pure.min.js');
        Requirements::javascript('survey_builder/js/survey.controller.js');

        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * @param $request
     * @param $action
     * @return HTMLText|SS_HTTPResponse
     */
    protected function handleAction($request, $action)
    {
        if (!Member::currentUser()) {
            if (!in_array($action, self::$allowed_actions_without_auth)) {
                return $this->redirect("/surveys/landing?BackURL=" . urlencode('/surveys/current'));
            }
        }
        if(strtolower($action) === 'index') return $this->redirect(self::RoutePrefix.'/current');
        return parent::handleAction($request, $action);
    }

    /**
     * @param $request
     * @return HTMLText|SS_HTTPResponse|void
     */
    public function RenderSurvey($request)
    {
        //check if user is logged in

        try {
            $step = $request->param('STEP_SLUG');

            $current_survey = $this->getCurrentSurveyInstance();

            if (!empty($step) && !$current_survey->isAllowedStep($step)) {
                // redirect
                return $this->redirect('/surveys/current/' . $current_survey->currentStep()->template()->title());
            }

            $this->survey_manager->registerCurrentStep($this->current_survey, $step);

            if ($current_survey->isLastStep() && !$current_survey->isEmailSent()) {
                $this->survey_manager->sendFinalStepEmail(new SurveyThankYouEmailSenderService, $current_survey);
            }

            return $this->customise(array(
                'Survey' => $current_survey,
            ))->renderWith(array('Surveys_CurrentSurveyContainer', 'Page'));
        }
        catch(Exception $ex){
            SS_Log::log($ex,SS_Log::WARN);
            return $this->httpError(404, "Survey not found!");
        }
    }

    /**
     * @return HTMLText
     */
    public function LandingPage()
    {
        return $this->customise(array('BackURL' => $this->request->requestVar('BackURL')))->renderWith(array('Surveys_LandingPage', 'Page'));
    }

    /**
     * @param $step_name
     * @return string
     * @throws NotFoundEntityException
     */
    public function SurveyStepClass($step_name){
        $css_step_class = '';
        $current_survey = $this->getCurrentSurveyInstance();
        $current_step   = $current_survey->currentStep();
        if($current_step->template()->title() == $step_name)
            $css_step_class = 'current';
        else{
            //check if future or complete
            if($current_survey->isAllowedStep($step_name)){
                $css_step_class = 'completed';
            }
            else{
                $css_step_class = 'future';
            }
        }
        return $css_step_class;
    }

    /**
     * @return ISurvey|null
     * @throws NotFoundEntityException
     */
    private function getCurrentSurveyInstance()
    {

        // get current template
        $current_template = $this->survey_manager->getCurrentSurveyTemplate();

        if (is_null($current_template))
            throw new NotFoundEntityException('SurveyTemplate', 'current template not set');

        if(!is_null($this->current_survey)) return $this->current_survey;

        $this->current_survey = $this->survey_repository->getByTemplateAndCreator
        (
            $current_template->getIdentifier(),
            Member::currentUserID()
        );

        // if not, create the survey and do the population
        if(is_null($this->current_survey))
        {

            $this->current_survey     = $this->survey_manager->buildSurvey
                                        (
                                            $current_template->getIdentifier(),
                                            Member::currentUserID()
                                        );
            // check if we should pre populate with former data ....

            if($current_template->shouldPrepopulateWithFormerData())
            {
                $this->survey_manager->doAutopopulation($this->current_survey, new OldSurveyDataAutopopulationStrategy);
            }
        }

        $this->current_survey = $this->survey_manager->updateSurveyWithTemplate($this->current_survey, $current_template);

        return $this->current_survey;
    }

    /**
     * @param int $entity_survey_id
     * @return IEntitySurvey
     * @throws NotFoundEntityException
     */
    private function getCurrentEntitySurveyInstance($entity_survey_id)
    {
        if(!is_null($this->current_entity_survey)) return $this->current_entity_survey;

        $this->current_survey        = $this->getCurrentSurveyInstance();
        $this->current_entity_survey = $this->current_survey->currentStep()->getEntitySurvey($entity_survey_id);
        return $this->current_entity_survey;
    }

    /**
     * @return Form|string
     * @throws NotFoundEntityException
     */
    public function SurveyStepForm()
    {
        $current_survey = $this->getCurrentSurveyInstance();
        $current_step   = $current_survey->currentStep();
        $builder        = SurveyStepUIBuilderFactory::getInstance()->build($current_step);
        if(is_null($builder)) return '<p>There is not set any form yet!</p>';
        $form           = $builder->build($current_step, 'NextStep');
        return $form;
    }

    /**
     * @param $data
     * @param $form
     * @return SS_HTTPResponse
     * @throws NotFoundEntityException
     */
    public function NextStep($data, $form)
    {
        $current_survey = $this->getCurrentSurveyInstance();
        $current_step   = $current_survey->currentStep();

        if($current_step instanceof ISurveyRegularStep)
            $next_step = $this->survey_manager->completeStep($current_step, $data);
        else
            $next_step = $current_step;

        return $this->redirect('/surveys/current/'.$next_step->template()->title());
    }

    /**
     * @param $request
     * @return SS_HTTPResponse
     * @throws NotFoundEntityException
     */
    public function SkipStep($request)
    {
        $current_survey = $this->getCurrentSurveyInstance();
        $current_step   = $current_survey->currentStep();
        $can_skip       = $current_step->canSkip();
        if($current_step instanceof ISurveyDynamicEntityStep) $can_skip = true;
        if(!$can_skip)  return $this->redirectBack();
        $next_step = $this->survey_manager->completeStep($current_step, $data = array());
        return $this->redirect('/surveys/current/'.$next_step->template()->title());
    }

    // Dynamic Entities


    /**
     * @param $request
     * @return SS_HTTPResponse
     * @throws NotFoundEntityException
     */
    public function DeleteEntity($request)
    {
        try
        {
            if(!Member::currentUser()) return $this->httpError(403);

            $step                 = $request->param('STEP_SLUG');
            $sub_step             = $request->param('SUB_STEP_SLUG');
            $entity_survey_id     = intval($request->param('ENTITY_SURVEY_ID'));

            $this->current_survey = $this->getCurrentSurveyInstance();
            $current_step         = $this->current_survey->currentStep();

            if(!($current_step instanceof ISurveyDynamicEntityStep))
            {
                if($this->current_survey->isAllowedStep($step))
                {
                    $current_step = $this->current_survey->getStep($step);
                    $this->survey_manager->registerCurrentStep($this->current_survey, $step);
                }
                else
                    throw new LogicException(sprintf('template %s', $current_step->template()->title()));
            }

            $this->survey_manager->deleteEntitySurvey($current_step, $entity_survey_id);

            return $this->redirect('/surveys/current/'.$step);
        }
        catch(Exception $ex)
        {
            SS_Log::log($ex->getMessage(), SS_Log::ERR);
            return $this->httpError(404);
        }
    }

    /**
     * @param $request
     * @throws NotFoundEntityException
     */
    public function AddEntity($request){

        try
        {
            if(!Member::currentUser()) return $this->httpError(403);

            $step                 = $request->param('STEP_SLUG');
            $sub_step             = $request->param('SUB_STEP_SLUG');

            $current_survey = $this->getCurrentSurveyInstance();
            $current_step   = $current_survey->currentStep();

            if(!($current_step instanceof ISurveyDynamicEntityStep))
            {
                if($this->current_survey->isAllowedStep($step)) {
                    $current_step = $this->current_survey->getStep($step);
                    $this->survey_manager->registerCurrentStep($this->current_survey, $step);
                }
                else
                    throw new LogicException(sprintf('template %s', $current_step->template()->title()));
            }

            //create the current survey entity
            $this->current_entity_survey = $this->survey_manager->buildEntitySurvey($current_step, Member::currentUserID());
            $url = sprintf('surveys/current/%s/edit/%s', $current_step->template()->title(), $this->current_entity_survey->getIdentifier());
            $this->redirect($url);
        }
        catch(Exception $ex)
        {
            SS_Log::log($ex->getMessage(), SS_Log::ERR);
            return $this->httpError(404);
        }
    }

    /**
     * @param $request
     * @return HTMLText|SS_HTTPResponse|void
     * @throws NotFoundEntityException
     */
    public function EditEntity($request)
    {
        try
        {
            if (!Member::currentUser()) return $this->httpError(403);

            $step             = $request->param('STEP_SLUG');
            $sub_step         = $request->param('SUB_STEP_SLUG');
            $entity_survey_id = intval($request->param('ENTITY_SURVEY_ID'));

            $this->current_survey = $this->getCurrentSurveyInstance();
            $current_step         = $this->current_survey->currentStep();

            if (!($current_step instanceof ISurveyDynamicEntityStep))
            {
                if ($this->current_survey->isAllowedStep($step)) {
                    $current_step = $this->current_survey->getStep($step);
                    $this->survey_manager->registerCurrentStep($this->current_survey, $step);
                } else {
                    throw new LogicException(sprintf('template %s', $current_step->template()->title()));
                }
            }

            $this->current_entity_survey = $current_step->getEntitySurvey($entity_survey_id);

            if (is_null($this->current_entity_survey))
            {
                //check if its a entity survey from a team that i belong
                $current_member = Member::currentUser();
                $this->current_entity_survey = $current_member->TeamEntitySurveys()->filter('EntitySurveyID',
                    $entity_survey_id)->first();
            }

            if (is_null($this->current_entity_survey))
            {
                throw new LogicException(sprintf('entity survey id is %s - member_id %s', $entity_survey_id, Member::currentUserID()));
            }

            $entity_step          = $this->current_entity_survey->currentStep();
            $entity_step_template = $entity_step->template();

            // check substep variable
            if (empty($sub_step))
            {
                // is not set, redirect to current steo uri
                return $this->redirect($request->getUrl() . '/' . $entity_step_template->title());
            }
            else
            {
                if ($sub_step !== $entity_step_template->title())
                {
                    // if set, but differs from current step check if we are allowed to use that step
                    $desired_sub_step = $this->current_entity_survey->getStep($sub_step);
                    if (!is_null($desired_sub_step) && $this->current_entity_survey->isAllowedStep($sub_step))
                    {
                        $this->current_entity_survey->registerCurrentStep($desired_sub_step);
                    }
                    else
                    {
                        // if we are not allowed to go to desired step , redirect to current step

                        $current_url = Controller::join_links
                        (
                            Director::absoluteBaseURL(),
                            'surveys/current/',
                            $step,
                            'edit',
                            $entity_survey_id,
                            $entity_step_template->title()
                        );

                        return $this->redirect($current_url);
                    }
                }
            }

            $this->current_entity_survey = $this->survey_manager->updateSurveyWithTemplate
            (
                $this->current_entity_survey,
                $this->current_entity_survey->template()
            );

            if ($sub_step === 'skip-step' && $this->current_entity_survey->currentStep()->canSkip())
            {
                $next_step = $this->survey_manager->completeStep
                (
                    $this->current_entity_survey->currentStep(),
                    $data = array()
                );
                return $this->go2DynEntityStep($this->current_entity_survey, $next_step);
            }

            return $this->customise
            (
                array
                (
                    'Survey'       => $this->current_survey,
                    'EntitySurvey' => $this->current_entity_survey
                )
            )->renderWith(array('Surveys_CurrentSurveyDynamicEntityContainer', 'Page'));
        }
        catch(Exception $ex)
        {
            SS_Log::log($ex->getMessage(), SS_Log::ERR);
            return $this->httpError(404);
        }
    }

    /**
     * @return Form|string
     * @throws NotFoundEntityException
     */
    public function SurveyDynamicEntityStepForm()
    {
        if(!Member::currentUser()) return $this->httpError(403);

        try
        {
            $request  = $this->getRequest();

            $step     = $request->param('STEP_SLUG');
            if(is_null($step))
                $step = $request->requestVar('STEP_SLUG');

            $sub_step = $request->param('SUB_STEP_SLUG');
            if(is_null($sub_step))
                $sub_step = $request->requestVar('SUB_STEP_SLUG');

            if(empty($step) || empty($sub_step))
                throw new LogicException(sprintf('step/sub_step empty (%s - %s) - member_id %s',$step, $sub_step, Member::currentUserID()));

            $this->current_survey = $this->getCurrentSurveyInstance();
            $current_step         = $this->current_survey->currentStep();

            if (!($current_step instanceof ISurveyDynamicEntityStep))
            {
                if ($this->current_survey->isAllowedStep($step))
                {
                    $current_step = $this->current_survey->getStep($step);
                    $this->survey_manager->registerCurrentStep($this->current_survey, $step);
                } else
                {
                    throw new LogicException(sprintf('template %s - member_id %s', $current_step->template()->title(), Member::currentUserID()));
                }
            }

            // check entity survey id
            $entity_survey_id = intval($request->param('ENTITY_SURVEY_ID'));
            if ($entity_survey_id === 0)
            {
                $entity_survey_id = intval($request->requestVar('ENTITY_SURVEY_ID'));
            }
            if ($entity_survey_id === 0)
            {
                throw new LogicException(sprintf('entity survey id is %s - member_id %s', $entity_survey_id, Member::currentUserID()));
            }

            $this->current_entity_survey = $current_step->getEntitySurvey($entity_survey_id);

            if (is_null($this->current_entity_survey))
            {
                //check if its a entity survey from a team that i belong
                $current_member = Member::currentUser();
                $this->current_entity_survey = $current_member->TeamEntitySurveys()->filter('EntitySurveyID',
                    $entity_survey_id)->first();
            }

            if (is_null($this->current_entity_survey))
            {
                throw new LogicException(sprintf('entity survey id is %s - member_id %s', $entity_survey_id, Member::currentUserID()));
            }

            if($this->current_entity_survey->currentStep()->template()->title() !== $sub_step)
            {

                if ($this->current_entity_survey->isAllowedStep($sub_step))
                {
                    $this->survey_manager->registerCurrentStep($this->current_entity_survey, $sub_step);
                }
                else
                {
                    throw new LogicException
                    (
                        sprintf
                        (
                            'current step %s differs from requested one %s - member_id %s',
                            $this->current_entity_survey->currentStep()->template()->title(),
                            $sub_step,
                            Member::currentUserID()
                        )
                    );
                }
            }

            $builder = SurveyStepUIBuilderFactory::getInstance()->build($this->current_entity_survey->currentStep());

            if (is_null($builder))
            {
                throw new LogicException(sprintf('There is not set any form yet! - member_id %s', Member::currentUserID()));
            }

            $form = $builder->build
            (
                $this->current_entity_survey->currentStep(),
                'NextDynamicEntityStep',
                'SurveyDynamicEntityStepForm'
            );

            $form->Fields()->add(new HiddenField('ENTITY_SURVEY_ID', 'ENTITY_SURVEY_ID', $entity_survey_id));
            $form->Fields()->add(new HiddenField('STEP_SLUG', 'STEP_SLUG', $step));
            $form->Fields()->add(new HiddenField('SUB_STEP_SLUG', 'SUB_STEP_SLUG', $sub_step));

            return $form;
        }
        catch(Exception $ex)
        {
            SS_Log::log($ex->getMessage(), SS_Log::ERR);
            return $this->httpError(404);
        }
    }

    /**
     * @param $data
     * @param $form
     * @return SS_HTTPResponse
     */
    public function NextDynamicEntityStep($data, $form)
    {
        try
        {

            $step     = $this->request->param('STEP_SLUG');
            if(is_null($step))
                $step = $this->request->requestVar('STEP_SLUG');

            $sub_step = $this->request->param('SUB_STEP_SLUG');
            if(is_null($sub_step))
                $sub_step = $this->request->requestVar('SUB_STEP_SLUG');

            $entity_survey = $this->getCurrentEntitySurveyInstance(intval($data['survey_id']));

            if(is_null($entity_survey))
                throw new LogicException('entity survey not found!');

            $current_step  = $entity_survey->currentStep();
            $next_step     = $this->survey_manager->completeStep($current_step, $data);
            if($entity_survey->isLastStep())
            {
                return $this->redirect('/surveys/current/'.$this->current_survey->currentStep()->template()->title());
            }
            else{
                return $this->go2DynEntityStep($entity_survey, $next_step);
            }
        }
        catch(Exception $ex)
        {
            SS_Log::log($ex->getMessage(), SS_Log::ERR);
            return $this->httpError(404);
        }
    }

    /**
     * @param IEntitySurvey $entity
     * @param ISurveyStep $next_step
     * @return SS_HTTPResponse
     */
    private function go2DynEntityStep(IEntitySurvey $entity,ISurveyStep $next_step){
        $dyn_step_holder_title = $this->current_survey->currentStep()->template()->title();
        $next_step_title       = $next_step->template()->title();
        return $this->redirect(sprintf('/surveys/current/%s/edit/%s/%s',$dyn_step_holder_title, $entity->getIdentifier(),$next_step_title));
    }

    // landing page

    public function getLoginPageTitle()
    {
        $res = '';
        if (empty($res)) {
            $res = 'OpenStack User Survey: Welcome!';
        }
        return $res;
    }

    public function getLoginPageContent()
    {
        $link = Controller::curr()->Link();
        $res = '';
        if (empty($res)) {
            $res = <<< HTML
<p>This survey provides users an opportunity to influence the community and software
direction. By sharing information about your configuration and requirements, the OpenStack
Foundation User Committee will be able to advocate on your behalf.</p>
<p><a href="{$link}faq" class="roundedButton">More Information About The Survey</a></p>
<hr/>
HTML;
        }
        return $res;
    }

    public function getLoginPageSlide1Content()
    {
        $res = '';
        if (empty($res)) {
            $res = 'This is the <strong>OpenStack User Survey</strong> for OpenStack cloud users and operators.';
        }
        return $res;
    }

    public function getLoginPageSlide2Content()
    {
        $res = '';
        if (empty($res)) {
            $res = 'It should only take <strong>10 minutes</strong> to complete.';
        }
        return $res;
    }

    public function getLoginPageSlide3Content()
    {
        $res = '';
        if (empty($res)) {
            $res = 'All of the information you provide is <strong>confidential</strong> to the Foundation (unless you specify otherwise).';
        }
        return $res;
    }

    public function RegisterForm()
    {
        return new SurveyRegistrationForm($this, 'RegisterForm');
    }

    public function MemberStart()
    {
        $member = null;
        if (isset($_REQUEST['m'])) {
            $member = Member::get()->byID((int)$_REQUEST['m']);
        }
        // Check whether we are merely changin password, or resetting.
        if (isset($_REQUEST['t']) && $member && $member->validateAutoLoginToken($_REQUEST['t'])) {
            $member->logIn();
            return $this->redirect(self::RoutePrefix.'/current');
        } elseif (Member::currentUser()) {
            return $this->redirect(self::RoutePrefix.'/current');
        } else {
            return $this->redirect(self::RoutePrefix.'/current');
        }
    }

    public function suggestMember(SS_HTTPRequest $request)
    {
        if (!Director::is_ajax()) return $this->httpError(403);

        $term       = Convert::raw2sql($request->getVar('term'));
        $split_term = explode(' ', $term);

        if(!Member::currentUser()) return $this->httpError(403);

        $current_user_id = Member::currentUserID();

        $full_name_condition = " FirstName LIKE '%{$term}%' OR Surname LIKE '%{$term}%' ";
        if(count($split_term) == 2)
        {
            $full_name_condition = " (FirstName LIKE '%{$split_term[0]}%' OR Surname LIKE '%{$split_term[1]}%') ";
        }

        $members = Member::get()
            ->where("ID <> {$current_user_id} AND Email <> '' AND ( {$full_name_condition} )")
            ->sort
            (
                array
                (
                    'Surname' => 'ASC',
                    'FirstName' => 'ASC',
                )
            )
            ->limit(100);

        $items = array();

        foreach ($members as $member)
        {
            $items[] = array(
                'id'    => $member->ID,
                'label' => sprintf('%s, %s (%s)',$member->Surname, $member->FirstName,($member->getCurrentAffiliation())? $member->getCurrentAffiliation()->Organization()->Name:'N/A') ,
                'value' => sprintf('%s, %s (%s)',$member->Surname, $member->FirstName,($member->getCurrentAffiliation())? $member->getCurrentAffiliation()->Organization()->Name:'N/A')
            );
        }

        $response = new SS_HTTPResponse();
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode($items));

        return $response;
    }

    public function suggestOrganization(SS_HTTPRequest $request)
    {
        if (!Director::is_ajax()) return $this->httpError(403);
        if(!Member::currentUser()) return $this->httpError(403);

        $term = Convert::raw2sql($request->getVar('term'));
        $orgs = Org::get()->filter('Name:PartialMatch', $term)
            ->sort('Name')
            ->limit(100);

        $items = array();
        foreach($orgs as $org)
        {
            $items[] = array(
                'id'    => $org->ID,
                'label' => $org->Name ,
                'value' => $org->Name
            );
        }

        $response = new SS_HTTPResponse();
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode($items));

        return $response;
    }

    public function addTeamMember(SS_HTTPRequest $request)
    {
        if (!Director::is_ajax()) return $this->httpError(403);
        if(!Member::currentUser()) return $this->httpError(403);

        $entity_survey_id  = (int)$request->param('ENTITY_SURVEY_ID');
        $member_id         = (int)$request->param('MEMBER_ID');

        try {
            $this->survey_manager->registerTeamMemberOnEntitySurvey
            (
                $entity_survey_id,
                $member_id,
                new EntitySurveyTeamMemberEmailSenderService
            );
            return true;
        }
        catch(Exception $ex)
        {
            return $this->httpError(401, $ex->getMessage());
        }
    }

    public function deleteTeamMember(SS_HTTPRequest $request)
    {
        if (!Director::is_ajax()) return $this->httpError(403);
        if(!Member::currentUser()) return $this->httpError(403);

        $entity_survey_id = (int)$request->param('ENTITY_SURVEY_ID');
        $member_id        = (int)$request->param('MEMBER_ID');

        try {
            $this->survey_manager->unRegisterTeamMemberOnEntitySurvey(
                $entity_survey_id,
                $member_id
            );
            return true;
        }
        catch(Exception $ex)
        {
            return $this->httpError(500);
        }
    }

    public function getTeamMembers(SS_HTTPRequest $request)
    {
        if (!Director::is_ajax()) return $this->httpError(403);
        if(!Member::currentUser()) return $this->httpError(403);

        $entity_survey_id = (int)$request->param('ENTITY_SURVEY_ID');

        try {
            $entity_survey = $this->survey_repository->getById($entity_survey_id);
            if(is_null($entity_survey) || !$entity_survey instanceof IEntitySurvey) return $this->httpError(404);
            $items = array();
            foreach($entity_survey->getTeamMembers() as $member)
            {
                $items[] = array
                (
                    'id'      => $member->ID,
                    'fname'   => $member->FirstName ,
                    'lname'   => $member->Surname ,
                    'pic_url' => $member->ProfilePhotoUrl(100)
                );
            }
            $response = new SS_HTTPResponse();
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode($items));
            return $response;
        }
        catch(Exception $ex)
        {
            return $this->httpError(500);
        }
    }

}