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

use amattu\x431\RemoteDiagnostic;

require(dirname(__FILE__) . "/RemoteDiagnostic.class.php");
require(dirname(__FILE__) . "/SocketConnection.class.php");
require(dirname(__FILE__) . "/websocket/src/ClientInterface.php");
require(dirname(__FILE__) . "/websocket/src/ConnectionException.php");
require(dirname(__FILE__) . "/websocket/src/Client.php");

$config = parse_ini_file(dirname(__FILE__) . "/config.ini");

session_start();

// Perform Login
try {
  if (!($_SESSION['wrapper'] instanceof RemoteDiagnostic))
    $_SESSION['wrapper'] = new RemoteDiagnostic($config['USERNAME'], $config['PASSWORD']);
  else
    echo "Session already exists with a wrapper.<br>";
} catch (Exception $e) {
  echo "<h1>Error</h1><br/>";
  echo $e->getMessage();
}

// Connect to Device
try {
  $_SESSION['wrapper']->connect($config['SERIAL']);
} catch (Exception $e) {
  echo "<h1>Error</h1><br/>";
  echo $e->getMessage();
}
