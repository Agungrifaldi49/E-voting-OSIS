<?php
// public/logout.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';

AuthController::logout();
