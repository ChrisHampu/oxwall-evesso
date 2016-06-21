<?php

/**
 * The MIT License (MIT)
 * Copyright (c) 2016 Christopher Hampu
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software 
 * and associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR 
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

class EVESSO_CTRL_Evesso extends OW_ActionController
{
  public function init()
  {
    $this->service = EVESSO_BOL_Service::getInstance();
  }

  public function oauth( $params )
  {
    $backUri = empty($_GET['backUri']) ? '' : urldecode($_GET['backUri']);
    $backUrl = OW_URL_HOME . $backUri;
    $user = null;

    if (!empty ($_GET['code']))
    {
      $token = $this->service->getEVE()->getToken($_GET['code']);

      if (!isset($token['access_token']))
      {
        OW::getFeedback()->error('Failed to verify access token');
        $this->redirect(OW::getRouter()->urlForRoute('static_sign_in'));
        return;
      }

      $user = $this->service->getEVE()->getUser($token['access_token']);

      if (empty($user) || !isset($user['CharacterName']))
      {
        OW::getFeedback()->error('Failed to get character info');
        $this->redirect(OW::getRouter()->urlForRoute('static_sign_in'));
        return;
      }
    }
    else
    {
      OW::getFeedback()->error('Invalid sign-in request');
      $this->redirect(OW::getRouter()->urlForRoute('static_sign_in'));
      return;
    }

    $authAdapter = new EVESSO_CLASS_AuthAdapter($user['CharacterID']);

    if ( $authAdapter->isRegistered() )
    {
      $authResult = OW::getUser()->authenticate($authAdapter);

      if ( $authResult->isValid() )
      {
        OW::getFeedback()->info('You have successfully logged in with your EVE Online account');
      }
      else
      {
        OW::getFeedback()->error('There was an issue logging in with your EVE Online account');
      }

      $this->redirect($backUrl);
    }

    $form = new EVESSO_OauthForm();
    $form->setAction(OW::getRouter()->urlFor('EVESSO_CTRL_Evesso', 'oauthSubmit', array('access_token' => $token['access_token'])));

    $this->addForm($form);

    OW::getDocument()->setHeading('EVE SSO Sign-in');
    OW::getDocument()->setHeadingIconClass('ow_ic_key');
  }

  public function oauthSubmit($params)
  {
    if (empty($params) || !isset($params['access_token']))
    {
      OW::getFeedback()->error('Invalid access token'); 
      $this->redirect(OW_URL_HOME);
    }

    $form = new EVESSO_OauthForm();

    if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
    {
      if ( $form->process($params['access_token']) )
      {
        OW::getFeedback()->info('EVE sign-in complete');
        $this->redirect(OW_URL_HOME);
      }

      $this->redirect();
      return;
    }

    $this->redirect(OW_URL_HOME);
  }
}

class EVESSO_OauthForm extends Form
{
  private $service;

  public function __construct()
  {
      parent::__construct('EVESSO_OauthForm');

      $field = new TextField('email');
      $field->setRequired(true);
      $field->setValue('');
      $field->setLabel('Email');
      $this->addElement($field);

      // submit
      $submit = new Submit('save');
      $submit->setValue('Save');
      $this->addElement($submit);

      $this->service = EVESSO_BOL_Service::getInstance();
  }

  public function process($token)
  {
    $values = $this->getValues();

    $email = trim($values['email']);

    if (!UTIL_Validator::isEmailValid($email))
    {
      OW::getFeedback()->error('Invalid email address');
      return false;
    }

    $userByEmail = BOL_UserService::getInstance()->findByEmail($email);

    if ( $userByEmail !== null )
    {
      OW::getFeedback()->error('A user by that email address already exists');

      return false;
    }

    $user = $this->service->getEVE()->getUser($token);

    if (empty($user) || !isset($user['CharacterName']) || !isset($user['CharacterID']))
    {
      OW::getFeedback()->error('Failed to get character info');
      throw new Exception(print_r($user));
      return false;
    }

    $authAdapter = new EVESSO_CLASS_AuthAdapter($user['CharacterID']);

    if ( $authAdapter->isRegistered() )
    {
      $authResult = OW::getUser()->authenticate($authAdapter);

      if ( $authResult->isValid() )
      {
        OW::getFeedback()->info('You have successfully logged in with your EVE Online account');
        return true;
      }
      else
      {
        OW::getFeedback()->error('There was an issue logging in with your EVE Online account');
        return false;
      }
    }

    $charID = $user['CharacterID'];

    $characterSheet = $this->service->getEVE()->getXMLCharacterInfo($charID);

    if ( empty($characterSheet) || empty($characterSheet->result) )
    {
      OW::getFeedback()->error('Failed to get character info from EVE');
      return false;
    }

    $corporation = (string) $characterSheet->result->corporation;
    $alliance = (string) $characterSheet->result->alliance;

    $username = trim('eve_' . implode('_', explode(' ', $user['CharacterName'])));

    $validUsername = UTIL_Validator::isUserNameValid($username);
    $username = $validUsername ? $username : uniqid("eve_");

    $password = uniqid();

    $picture = 'https://image.eveonline.com/Character/' . $charID . '_256.jpg';
    
    try
    {
      $oxUser = BOL_UserService::getInstance()->createUser($username, $password, $email, null, true);
      
      if ( !$validUsername )
      {
        $oxUser->username = "eve_" . $oxUser->id;
        
        BOL_UserService::getInstance()->saveOrUpdate($oxUser);
      }
    }
    catch ( Exception $e )
    {
      switch ( $e->getCode() )
      {
        case BOL_UserService::CREATE_USER_DUPLICATE_EMAIL:
          OW::getFeedback()->error('A user by that email address already exists');
          break;

        case BOL_UserService::CREATE_USER_INVALID_USERNAME:
          OW::getFeedback()->error('Invalid username');
          break;

        default:
          OW::getFeedback()->error('Failed to complete user profile');
      }

      return false;
    }
    
    $questions = array(
      'charactername' => $user['CharacterName'],
      'alliance' => $alliance,
      'corporation' => $corporation,
      'realname' => $user['CharacterName']
    );

    BOL_AvatarService::getInstance()->setUserAvatar($oxUser->id, $picture, array('isModerable' => false, 'trackAction' => false ));
    BOL_QuestionService::getInstance()->saveQuestionsData($questions, $oxUser->id);

    $authAdapter->register($oxUser->id);

    $authResult = OW_Auth::getInstance()->authenticate($authAdapter);

    if ( $authResult->isValid() )
    {
      // authenticate user
      OW::getUser()->login($oxUser->id);

      $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array(
        'method' => 'evesso',
        'userId' => $oxUser->id,
        'params' => $_POST
      ));

      OW::getEventManager()->trigger($event);

      return true;
    }
    else
    {
      OW::getFeedback()->error('Failed to validate new user');
    }
    
    return false;
  }
}
