<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;

require __DIR__."/../vendor/autoload.php";
require __DIR__."/_ip_check.php";

$request = Request::createFromGlobals();
omd($request);
