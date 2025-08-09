<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

// Vercel entry point for Laravel
// This file routes all requests to Laravel's public/index.php

// Change to the Laravel public directory
chdir('../public');

// Include Laravel's index.php
require_once 'index.php';
