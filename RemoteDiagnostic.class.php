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

/**
 * Remote Diagnostic X431 API Wrapper Class
 *
 * @author Alec M
 */
class RemoteDiagnostic {
  /**
   * AIT|Golo365 Device Serial Number Length
   *
   * @var int
   */
  public CONST SERIAL_NUMBER_LENGTH = 12;

  /**
   * HTTP Accept Headers
   *
   * @var string
   */
  private CONST ACCEPT_JSON = "application/json";

  /**
   * API Status Codes
   *
   * @var int
   */
  private CONST STATUS_CODE_OK = 0;
  private CONST STATUS_CODE_INVALID_USERNAME = 100002;
  private CONST STATUS_CODE_INVALID_PASSWORD = 100011;

  /**
   * x431 API Url
   *
   * @var string
   */
  private CONST API_URL = "https://remotediag.x431.com/";

  /**
   * x431 Remote Diagnostic Endpoints
   *
   * @var array [
   *  endpoint => [
   *    Method,
   *    Uri,
   *    Referrer,
   *    Accept
   *  ]
   * ]
   */
  private CONST ENDPOINTS = [
    "login" => ["POST", "login", self::API_URL, self::ACCEPT_JSON],
    "findUserByNumber" => ["GET", "cn/findUserByNumber", self::API_URL, self::ACCEPT_JSON],
  ];

  /**
   * x431 API Token
   *
   * @var string|null
   */
  private $token;

  /**
   * x431 API User Details
   *
   * @var array|null
   */
  private $user;

  /**
   * SocketConnection instance
   *
   * @var SocketConnection|null
   */
  private $socket;

  /**
   * Class Constructor - Performs a login to x431 remote diagnostic
   * via the AIT|Golo365 account credentials provided
   *
   * @param  string $username Username or Account Number
   * @param  string $password Password
   * @throws TypeError
   * @throws Exception
   */
  public function __construct(string $username, string $password)
  {
    // Perform Login
    $response = $this->request(self::ENDPOINTS["login"], [
      "username" => $username,
      "password" => $password,
    ]);

    // Check for successful login
    if (!$response) {
      throw new Exception("Fatal unknown login failure");
    }
    if (!isset($response->code) || !isset($response->msg) || !isset($response->data)) {
      throw new Exception("API Error - Invalid response");
    }
    if ($response->code == self::STATUS_CODE_INVALID_USERNAME) {
      throw new Exception("Login Error - Invalid username");
    }
    if ($response->code == self::STATUS_CODE_INVALID_PASSWORD) {
      throw new Exception("Login Error - Invalid password");
    }
    if ($response->code != self::STATUS_CODE_OK) {
      throw new Exception("Unknown login failure occurred");
    }

    // Store Response
    $this->token = $response->data->token;
    foreach($response->data->user as $key => $val) {
      $this->user[$key] = $val;
    }
  }

  /**
   * Connect to a x431 Device
   *
   * @param  string $serial_no Target device
   * @return void
   * @throws TypeError
   * @throws Exception
   */
  public function connect(string $serial_no) : void
  {
    // Check for valid serial number
    if (strlen($serial_no) !== self::SERIAL_NUMBER_LENGTH) {
      throw new Exception("Invalid serial number");
    }

    // Open Socket Connection
    if (!($this->socket instanceof SocketConnection)) {
      $this->socket = new SocketConnection($this->token, $this->user['user_id']);
    }

    throw new Exception("Not implemented");
  }

  /**
   * Perform cURL Request
   *
   * @param  array $endpoint
   * @param  array $data
   * @return mixed
   */
  private function request(array $endpoint, array $data) : mixed
  {
    $ch = curl_init(self::API_URL . $endpoint[1]);

    // Check request type
    if ($endpoint[0] === "POST") {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } else {
      curl_setopt($ch, CURLOPT_HTTPGET, true);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Content-Type: " . $endpoint[3],
      "Referer: " . $endpoint[2],
      "Accept: " . $endpoint[3],
    ]);
    $response = curl_exec($ch);

    curl_close($ch);
    return $endpoint[3] === self::ACCEPT_JSON && $response
      ? json_decode($response, false)
      : $response;
  }
}
