<?php

$plugin = OW::getPluginManager()->getPlugin('evesso');

OW::getRouter()->addRoute(new OW_Route('evesso_oauth', 'evesso/oauth', 'EVESSO_CTRL_Evesso', 'oauth'));

$route = new OW_Route('evesso_configuration', 'admin/plugins/evesso', 'EVESSO_CTRL_Admin', 'index');
OW::getRouter()->addRoute($route);

$route = new OW_Route('evesso_configuration_settings', 'admin/plugins/evesso/settings', 'EVESSO_CTRL_Admin', 'settings');
OW::getRouter()->addRoute($route);

$route = new OW_Route('evesso_configuration_fields', 'admin/plugins/evesso/settings', 'EVESSO_CTRL_Admin', 'settings');
OW::getRouter()->addRoute($route);

$registry = OW::getRegistry();
$registry->addToArray(BASE_CTRL_Join::JOIN_CONNECT_HOOK, array(new EVESSO_CMP_EvessoButton(), 'render'));

$eventHandler = new EVESSO_CLASS_EventHandler();
$eventHandler->init();