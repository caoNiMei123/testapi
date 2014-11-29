<?php

/**
 * Config for IDC Location.
 **/

// Current IDC Location
$location = empty($_SERVER['HTTP_X_LOCATION']) ? 'jx' : $_SERVER['HTTP_X_LOCATION'];
define('CURRENT_CONF', $location);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */