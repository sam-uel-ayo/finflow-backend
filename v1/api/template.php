<?php

require '../assets/header.php';

use cUtils\cUtils;

$requiredKeys = [];
$optionalKeys = [];

cUtils::validatePayload($requiredKeys, $data);

$data = cUtils::arrayToObject($data);

//Logic here