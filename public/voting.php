<?php
// public/voting.php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Controllers/VotingController.php';

VotingController::showVotingPage();
