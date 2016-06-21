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

BOL_LanguageService::getInstance()->addPrefix('evesso', 'EVE SSO');

$plugin = OW::getPluginManager()->getPlugin('evesso');

OW::getConfig()->addConfig('evesso', 'app_id', '', 'EVE SSO Client ID');
OW::getConfig()->addConfig('evesso', 'app_key', '', 'EVE SSO Secret Key');
OW::getConfig()->addConfig('evesso', 'callback_url', '', 'EVE SSO Callback URL');

OW::getPluginManager()->addPluginSettingsRouteName('evesso', 'evesso_configuration_settings');

$preference = BOL_PreferenceService::getInstance()->findPreference('evesso_user_credits');

if ( empty($preference) )
{
  $preference = new BOL_Preference();
}

$preference->key = 'evesso_user_credits';
$preference->sectionName = 'general';
$preference->defaultValue = 0;
$preference->sortOrder = 1;

BOL_PreferenceService::getInstance()->savePreference($preference);

$accountTypes = BOL_QuestionService::getInstance()->findAllAccountTypes();
$accountTypeList = array();

foreach ( $accountTypes as $key => $value )
{
  $accountTypeList[] = $value->name;
}

if ( empty(BOL_QuestionService::getInstance()->findQuestionByName('corporation')) )
{
  $corp_question = new BOL_Question();
  $corp_question->name = 'corporation';
  $corp_question->sectionName = 'EVE';
  $corp_question->type = 'text';
  $corp_question->presentation = 'text';
  $corp_question->sortOrder = 0;
  $corp_question->onView = 1;

  BOL_QuestionService::getInstance()->createQuestion( $corp_question, 'corporation');
}

if ( empty(BOL_QuestionService::getInstance()->findQuestionByName('alliance')) )
{
  $alliance_question = new BOL_Question();
  $alliance_question->name = 'alliance';
  $alliance_question->sectionName = 'EVE';
  $alliance_question->type = 'text';
  $alliance_question->presentation = 'text';
  $alliance_question->sortOrder = 1;
  $alliance_question->onView = 1;
  
  BOL_QuestionService::getInstance()->createQuestion( $alliance_question, 'alliance');
}

if ( empty(BOL_QuestionService::getInstance()->findQuestionByName('charactername')) )
{
  $charname_question = new BOL_Question();
  $charname_question->name = 'charactername';
  $charname_question->sectionName = 'EVE';
  $charname_question->type = 'text';
  $charname_question->presentation = 'text';
  $charname_question->sortOrder = 2;
  $charname_question->onView = 1;
  
  BOL_QuestionService::getInstance()->createQuestion( $charname_question, 'charactername');
}

BOL_QuestionService::getInstance()->addQuestionToAccountType('corporation', $accountTypeList);
BOL_QuestionService::getInstance()->addQuestionToAccountType('alliance', $accountTypeList);
BOL_QuestionService::getInstance()->addQuestionToAccountType('charactername', $accountTypeList);

BOL_LanguageService::getInstance()->addValue(
  OW::getLanguage()->getCurrentId(),
  'base',
  'questions_section_EVE_label',
  'EVE');