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

class EVESSO_CLASS_EventHandler
{
  public function onCollectButtonList( BASE_CLASS_EventCollector $event )
  {
    $cssUrl = OW::getPluginManager()->getPlugin('EVESSO')->getStaticCssUrl() . 'evesso.css';
    OW::getDocument()->addStyleSheet($cssUrl);

    $button = new EVESSO_CMP_EvessoButton();
    $event->add(array('iconClass' => 'ow_ico_signin_f', 'markup' => $button->render()));
  }

  public function afterUserRegistered( OW_Event $event )
  {
    $params = $event->getParams();
    
    if ( $params['method'] != 'evesso' )
    {
      return;
    }

    $userId = (int) $params['userId'];
    $user = BOL_UserService::getInstance()->findUserById($userId);

    if ( empty($user->accountType) )
    {
      BOL_PreferenceService::getInstance()->savePreferenceValue('evesso_user_credits', 1, $userId);
    }
    
    $event = new OW_Event('feed.action', array(
      'pluginKey' => 'base',
      'entityType' => 'user_join',
      'entityId' => $userId,
      'userId' => $userId,
      'replace' => true,
      ), array(
      'string' => 'joined our site using their EVE account!',
      'view' => array(
        'iconClass' => 'ow_ic_user'
      )
    ));

    OW::getEventManager()->trigger($event);
  }
  
  public function onCollectAccessExceptions( BASE_CLASS_EventCollector $e ) {
    $e->add(array('controller' => 'EVESSO_CTRL_Evesso', 'action' => 'login'));
  }
  
  public function onCollectAdminNotification( BASE_CLASS_EventCollector $e )
  {
    $language = OW::getLanguage();
    $configs = OW::getConfig()->getValues('evesso');

    if ( empty($configs['app_id']) || empty($configs['app_key']) || empty($configs['redirect_url']))
    {
      $href = OW::getRouter()->urlForRoute('evesso_configuration');
        
      $e->add("<a href=" . $href . ">EVE SSO</a> requires configuration");
    }
  }    

  public function onCollectQuestionFieldLabel( OW_Event $event )
  {
    $params = $event->getParams();

    $data = null;

    if ( empty($params['fieldName']) )
    {
      $event->setData($data);
      return $data;
    }

    switch ($params['fieldName'])
    {
      case "corporation":
        $data = "Corporation";
        break;
      case "alliance":
        $data = "Alliance";
        break;
      case "charactername":
        $data = "Character Name";
        break;
    }

    $event->setData($data);

    return $data;
  }
  
  public function getConfiguration( OW_Event $event )
  {
    $service = EVESSO_BOL_Service::getInstance();
    $access = $service->getEVEAccessDetails();
    $appId = $access->appId;
    $appKey = $access->appKey;
    $redirect = $access->redirectURL;

    if ( empty($appId) || empty($appKey) || empty($redirect))
    {
      return null;
    }
    
    $data = array(
      "appId" => $appId,
      "appKey" => $appKey,
      "redirectURL" => $redirect
    );
    
    $event->setData($data);
    
    return $data;
  }
  
  public function onAfterUserCompleteProfile( OW_Event $event )
  {        
    $params = $event->getParams();
    $userId = !empty($params['userId']) ? (int) $params['userId'] : OW::getUser()->getId();

    $userCreditPreference = BOL_PreferenceService::getInstance()->getPreferenceValue('evesso_user_credits', $userId);
    
    if( $userCreditPreference == 1 )
    {
      BOL_AuthorizationService::getInstance()->trackAction("base", "user_join");
        
      BOL_PreferenceService::getInstance()->savePreferenceValue('evesso_user_credits', 0, $userId);
    }
  }
  
  public function genericInit()
  {
    OW::getEventManager()->bind(BASE_CMP_ConnectButtonList::HOOK_REMOTE_AUTH_BUTTON_LIST, array($this, "onCollectButtonList"));
    OW::getEventManager()->bind(OW_EventManager::ON_USER_REGISTER, array($this, "afterUserRegistered"));
    OW::getEventManager()->bind(OW_EventManager::ON_USER_EDIT, array($this, "afterUserSynchronized"));
    
    OW::getEventManager()->bind('base.members_only_exceptions', array($this, "onCollectAccessExceptions"));
    OW::getEventManager()->bind('base.password_protected_exceptions', array($this, "onCollectAccessExceptions"));
    OW::getEventManager()->bind('base.splash_screen_exceptions', array($this, "onCollectAccessExceptions"));
    
    OW::getEventManager()->bind('base.questions_field_get_label', array($this, "onCollectQuestionFieldLabel"));

    OW::getEventManager()->bind('evesso.get_configuration', array($this, "getConfiguration"));
    OW::getEventManager()->bind(OW_EventManager::ON_AFTER_USER_COMPLETE_PROFILE, array($this, "onAfterUserCompleteProfile"));
  }
  
  public function init()
  {
    $this->genericInit();
    
    OW::getEventManager()->bind('admin.add_admin_notification', array($this, "afterUserSynchronized"));
  }
}