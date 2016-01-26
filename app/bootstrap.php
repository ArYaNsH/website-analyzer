<?php
$loader = require_once __DIR__.'/../vendor/autoload.php';

/**
 * PHP ini_set
 * 
 */
ini_set('display_errors', 1);
ini_set('max_execution_time', 120);

/**
 * Loading custom classes without using Composer
 * 
 */
$loader->add('Front', __DIR__.'/../src/');

date_default_timezone_set('Europe/Paris');
setlocale (LC_ALL, 'fr_FR', 'fra'); 

/**
 * If PHP libraries are required for this app, they should be appended here:
 */