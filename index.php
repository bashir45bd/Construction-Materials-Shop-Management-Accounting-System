<?php
/**
 * Application Entry Point & Front Router
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

use App\Auth\Auth;

if (Auth::check()) {
    redirect(BASE_URL . '/modules/dashboard/');
} else {
    redirect(BASE_URL . '/modules/auth/login.php');
}
