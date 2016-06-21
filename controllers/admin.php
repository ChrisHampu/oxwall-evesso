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

class EVESSO_CTRL_Admin extends ADMIN_CTRL_Abstract
{
  private $service;

  public function __construct()
  {
    parent::__construct();
  }

  public function index()
  {
    $form = new EVESSO_AccessForm();
    $this->addForm($form);

    if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
    {
      if ( $form->process() )
      {
        OW::getFeedback()->info('EVE SSO has been configured.');
        $this->redirect(OW::getRouter()->urlForRoute('evesso_configuration_settings'));
      }

      OW::getFeedback()->error('Invalid EVE SSO settings.');
      $this->redirect();
    }

    OW::getDocument()->setHeading('EVE SSO');
    OW::getDocument()->setHeadingIconClass('ow_ic_key');
  }

  private function getMenu()
  {
    $language = OW::getLanguage();

    $menuItems = array();

    $item = new BASE_MenuItem();
    $item->setLabel('Settings');
    $item->setUrl(OW::getRouter()->urlForRoute('evesso_configuration_settings'));
    $item->setKey('evesso_settings');
    $item->setIconClass('ow_ic_gear_wheel');
    $item->setOrder(0);

    $menuItems[] = $item;

    return new BASE_CMP_ContentMenu($menuItems);
  }

  private function requireAppId()
  {
    $configs = OW::getConfig()->getValues('evesso');

    $wizardUrl = OW::getRouter()->urlForRoute('evesso_configuration');
    if ( empty($configs['app_id']) || empty($configs['app_key']) || empty($configs['callback_url']))
    {
        $this->redirect($wizardUrl);
    }

    return $configs['app_id'];
  }

  public function settings( $params )
  {
    $appId = $this->requireAppId();

    if ( !empty($_GET['rm-app']) && $_GET['rm-app'] == 1 )
    {
      OW::getConfig()->saveConfig('evesso', 'app_id', '');
      OW::getConfig()->saveConfig('evesso', 'app_key', '');
      OW::getConfig()->saveConfig('evesso', 'callback_url', '');
      $redirectUrl = OW::getRequest()->buildUrlQueryString(null, array('rm-app' => null));
      $this->redirect($redirectUrl);
    }

    $this->addComponent('menu', $this->getMenu());
    OW::getDocument()->setHeading('EVE SSO');
    OW::getDocument()->setHeadingIconClass('ow_ic_key');

    $removeAppUrl = OW::getRequest()->buildUrlQueryString(null, array('rm-app' => 1));
    $this->assign('deleteUrl', $removeAppUrl);

    $this->assign('resetRspUrl', OW::getRouter()->urlFor('EVESSO_CTRL_Admin', 'ajaxResetApplication'));
  }

  public function ajaxResetApplication()
  {
    if ( !OW::getRequest()->isAjax() )
    {
      throw new Redirect404Exception();
    }

    if ( EVESSO_BOL_AdminService::getInstance()->configureApplication() )
    {
      exit('Settings are reset');
    }

    exit('Failed to reset settings');
  }
}

class EVESSO_AccessForm extends Form
{

  public function __construct()
  {
    parent::__construct('EVESSO_AccessForm');

    $config = OW::getConfig();

    $field = new TextField('appId');
    $field->setRequired(true);
    $field->setValue($config->getValue('evesso', 'app_id'));
    $this->addElement($field);

    $field = new TextField('appKey');
    $field->setRequired(true);
    $field->setValue($config->getValue('evesso', 'app_key'));
    $this->addElement($field);

    $field = new TextField('callback_url');
    $field->setRequired(true);
    $field->setValue($config->getValue('evesso', 'callback_url'));
    $this->addElement($field);

    // submit
    $submit = new Submit('save');
    $submit->setValue('Save');
    $this->addElement($submit);
  }

  public function process()
  {
    $values = $this->getValues();
    $config = OW::getConfig();

    $apiId = trim($values['appId']);
    $apiKey = trim($values['appKey']);
    $callbackURL = trim($values['callback_url']);

    $config->saveConfig('evesso', 'app_id', $apiId);
    $config->saveConfig('evesso', 'app_key', $apiKey);
    $config->saveConfig('evesso', 'callback_url', $callbackURL);

    return true;
  }
}
