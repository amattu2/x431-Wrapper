<?php
/*
 * Produced: Sun Apr 24 2022
 * Author: Alec M.
 * GitHub: https://amattu.com/links/github
 * Copyright: (C) 2022 Alec M.
 * License: License GNU Affero General Public License v3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace amattu\x431;

use Exception;
use Paragi\PhpWebsocket\Client;
use Paragi\PhpWebsocket\ConnectionException;

/**
 * Remote Diagnostic Socket Connection Class
 *
 * Note:
 * (1) You do not need to access this class
 *     directly.  It is used internally by
 *     RemoteDiagnostic.class.php
 *
 * @author Alec M
 */
class SocketConnection {
  /**
   * x431 WebSocket Endpoints
   *
   * @var array
   */
  private CONST ENDPOINTS = [
    "host" => "remotediag.x431.com",
    "path" => "/socket.io/?EIO=3&transport=websocket&sid=",
    "sid" => "https://remotediag.x431.com/socket.io/?EIO=3&transport=polling&t=ABC",
    "connectionNotice" => "https://remotediag.x431.com/socket.io/?EIO=3&transport=polling&t=ABC&sid=",
  ];

  /**
   * x431 Successful Connection Notice
   *
   * @var string
   */
  private CONST NOTICE_OK = "ok";

  /**
   * WebSocket Client Instance
   *
   * @var Paragi\PhpWebsocket\Client|null
   */
  private $socket;

  /**
   * Socket Authentication Token
   *
   * @var string|null
   */
  private $token;

  /**
   * AIT|Golo365 User Account ID
   *
   * @var string|null
   */
  private $user_id;

  /**
   * Socket SID
   *
   * @var string|null
   */
  private $sid;

  /**
   * WebSocket Standard Error Output
   *
   * @var string|null
   */
  private $stdError;

  /**
   * Class Constructor
   *
   * @param  string $token
   * @param  string $user_id
   */
  public function __construct(string $token, string $user_id)
  {
    // Store Details
    $this->token = $token;
    $this->user_id = $user_id;
    $this->sid = $this->getSid();

    // Provide Intent to Connect
    $this->provideConnectNotice();

    // Open WebSocket Connection
    $this->socket = new Client(self::ENDPOINTS['host'],
      443,
      NULL,
      $this->stdError,
      10,
      true,
      true,
      self::ENDPOINTS['path'] . $this->sid
    );
  }

  /**
   * Fetch SID from WebSocket endpoint
   *
   * @return string SID
   * @throws Exception failure message
   */
  private function getSid() : string
  {
    // Fetch SID
    $ch = curl_init(self::ENDPOINTS["sid"]);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    // Parse Response
    if ($response && strpos($response, "sid") !== false) {
      $json = json_decode(strstr($response, "{"), false);
      return $json->sid;
    }

    // Default
    throw new Exception("Unable to get WebSocket SID");
  }

  /**
   * Provide AIT Notice of Intent to Connect
   *
   * @return boolean request status
   * @throws Exception failure message
   */
  private function provideConnectNotice() : bool
  {
    // Provide Connection Notice
    $ch = curl_init(self::ENDPOINTS["connectionNotice"] . $this->sid);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, "88:42['imReconnect',{'user_id':'{$this->user_id}','token':'{$this->token}'}]");
    $response = curl_exec($ch);
    curl_close($ch);

    // Parse Response
    if ($response && $response === self::NOTICE_OK) {
      return true;
    }

    // Default
    throw new Exception("Unable to provide WebSocket Connection Notice");
  }
}
