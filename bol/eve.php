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

require_once OW_DIR_PLUGIN.'evesso'.DS.'lib'.DS.'httpcurl.php';

class EVE
{

  protected $appId;
  protected $appKey;
  protected $callbackUrl;

  private $scopes;

  const auth_endpoint = 'https://login.eveonline.com/oauth/authorize';
  const token_endpoint = 'https://login.eveonline.com/oauth/token';
  const user_endpoint = 'https://login.eveonline.com/oauth/verify';

  const xml_charactersheet_endpoint = 'https://api.eveonline.com/char/CharacterSheet.xml.aspx?accessType=character&characterID=';
  const xml_characterinfo_endpoint = 'https://api.eveonline.com/eve/CharacterInfo.xml.aspx?accessType=character&characterID=';

  private $auth_token;

  private $httpcurl;

  public function __construct($config)
  {
    $this->appId = $config['appId'];
    $this->appKey = $config['appKey'];
    $this->callbackUrl = $config['callbackURL'];
    $this->scopes = array();

    $this->auth_token = base64_encode($this->appId . ':' . $this->appKey);

    $this->httpcurl = new HTTPCurl();
    $this->httpcurl->setUserAgent('EVE SSO/Oxwall');
    $this->httpcurl->setSSLVerify(false);
    $this->httpcurl->setCache(false);
    $this->httpcurl->setHeaderBody(false);
  }

  public function generateOAuthUrl()
  {
    $data = array (
      'scope'=> implode(' ', $this->scopes),
      'redirect_uri'=> $this->callbackUrl,
      'response_type'=>'code',
      'client_id'=> $this->appId
    );

    return self::auth_endpoint.'?'.http_build_query ($data);
  }

  public function getToken($code)
  {
    $this->httpcurl->setUrl(self::token_endpoint);
    $this->httpcurl->setPostData(http_build_query(array('grant_type' => 'authorization_code', 'code' => $code), null, '&'));
    $this->httpcurl->setHeaders(array('Authorization: Basic ' . $this->auth_token, 'Content-Type: ' . 'application/x-www-form-urlencoded'));

    $this->httpcurl->execute();

    return json_decode($this->httpcurl->content, true);
  }

  public function getUser($token)
  {
    $this->httpcurl->setUrl(self::user_endpoint);
    $this->httpcurl->setPostMethod(false);
    $this->httpcurl->setHeaders(array('Authorization: Bearer ' . $token));

    $this->httpcurl->execute();

    return json_decode($this->httpcurl->content, true);
  }

  // This character sheet can provide all of the important info for the character,
  // but is only possible with an API key providing character sheet access
  public function getXMLCharacterSheet($charID, $apiKey, $vCode)
  {
    $this->httpcurl->setUrl(self::xml_charactersheet_endpoint . $charID . '&apiKey=' . $apiKey . '&vCode=' . $vCode);
    $this->httpcurl->setPostMethod(false);
    $this->httpcurl->setHeaders(array());

    $this->httpcurl->execute();

    $result = simplexml_load_string($this->httpcurl->content);

    if ($result === false)
    {
      return array();
    }

    return $result;
  }

  // Returns the public data available from the XML api for this character
  public function getXMLCharacterInfo($charID)
  {
    $this->httpcurl->setUrl(self::xml_characterinfo_endpoint . $charID);
    $this->httpcurl->setPostMethod(false);
    $this->httpcurl->setHeaders(array());

    $this->httpcurl->execute();

    $result = simplexml_load_string($this->httpcurl->content);

    if ($result === false)
    {
      return array();
    }

    return $result;
  }
}