<?php
/**
 * User Logout Action
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;

Auth::logout();
redirect(BASE_URL . '/modules/auth/login.php');
