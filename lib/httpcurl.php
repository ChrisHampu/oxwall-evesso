<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.
 *
 * ---
 * 2014 Dmitry Surin
 * The GPL License.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

class HTTPCurl
{

 public $content;
 public $headers;

 private $curlint;

 public function __construct ()
 {
    $this->curlint = curl_init();                   // curl init
    curl_setopt($this->curlint,CURLOPT_RETURNTRANSFER,1); // get content

    curl_setopt($this->curlint, CURLOPT_VERBOSE, true);
curl_setopt($this->curlint, CURLOPT_STDERR, fopen('php://stderr', 'w'));
 }

 public function setPostData ($postdata)
 {
    curl_setopt($this->curlint,CURLOPT_POST,1);
    curl_setopt($this->curlint,CURLOPT_POSTFIELDS,$postdata);
 }

 public function setPostMethod ($post)
 {
    curl_setopt($this->curlint,CURLOPT_POST,$post);
 }

 public function setUserAgent ($useragent)
 {
    curl_setopt($this->curlint,CURLOPT_USERAGENT,$useragent);
 }

 public function setHeaderBody ($header)
 {
    curl_setopt($this->curlint,CURLOPT_HEADER,$header);
 }

 public function setHeaders ($headers)
 {
    curl_setopt($this->curlint, CURLOPT_HTTPHEADER,$headers);
 }

 public function setTimeout ($timeout)
 {
    curl_setopt($this->curlint,CURLOPT_TIMEOUT,$timeout);
 }

 public function setSSLVerify ($verify)
 {
    curl_setopt($this->curlint,CURLOPT_SSL_VERIFYPEER,$verify);
 }

 public function setCache ($cache)
 {
    curl_setopt($this->curlint,CURLOPT_FRESH_CONNECT,!$cache);  //cache
 }

 public function setUrl ($url)
 {
    curl_setopt($this->curlint,CURLOPT_URL,$url);       // url
 }


 public function execute ()
    {
        $this->content = curl_exec ($this->curlint);
        $this->headers = curl_getinfo ($this->curlint);
        return curl_errno ($this->curlint);
    }
 public function __destruct ()
 {
  if (isset($this->curlint)) curl_close ($this->curlint);
 }


}

?>