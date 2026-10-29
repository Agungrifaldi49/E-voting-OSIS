<?php
// api/results.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Controllers/ResultController.php';

ResultController::apiResults();
