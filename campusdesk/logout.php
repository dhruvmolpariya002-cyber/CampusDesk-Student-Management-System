<?php
/**
 * CampusDesk — Secure Logout Script
 * File: logout.php
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

set_flash('success', 'You have been successfully signed out.');
logout_user('login.php');
