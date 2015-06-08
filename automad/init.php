<?php
/*
 *	                  ....
 *	                .:   '':.
 *	                ::::     ':..
 *	                ::.         ''..
 *	     .:'.. ..':.:::'    . :.   '':.
 *	    :.   ''     ''     '. ::::.. ..:
 *	    ::::.        ..':.. .''':::::  .
 *	    :::::::..    '..::::  :. ::::  :
 *	    ::'':::::::.    ':::.'':.::::  :
 *	    :..   ''::::::....':     ''::  :
 *	    :::::.    ':::::   :     .. '' .
 *	 .''::::::::... ':::.''   ..''  :.''''.
 *	 :..:::'':::::  :::::...:''        :..:
 *	 ::::::. '::::  ::::::::  ..::        .
 *	 ::::::::.::::  ::::::::  :'':.::   .''
 *	 ::: '::::::::.' '':::::  :.' '':  :
 *	 :::   :::::::::..' ::::  ::...'   .
 *	 :::  .::::::::::   ::::  ::::  .:'
 *	  '::'  '':::::::   ::::  : ::  :
 *	            '::::   ::::  :''  .:
 *	             ::::   ::::    ..''
 *	             :::: ..:::: .:''
 *	               ''''  '''''
 *	
 *
 *	AUTOMAD
 *
 *	Copyright (c) 2014 by Marc Anton Dahmen
 *	http://marcdahmen.de
 *
 *	Licensed under the MIT license.
 *	http://automad.org/license
 */
 
 
namespace Automad\Core;


defined('AUTOMAD') or die('Direct access not permitted!');


// Autoload core classes and libraries.
spl_autoload_register(function($class) {
	
	$file = strtolower(str_replace('\\', '/', $class)) . '.php';
		
	if (strpos($file, 'automad') === 0) {	
		// Load Automad class.
		require_once AM_BASE_DIR . '/' . $file;
	} else {	
		// Load 3rd party library.
		require_once AM_BASE_DIR . '/automad/lib/' . $file;
	}
		
});


// Load configuration.
require AM_BASE_DIR . '/automad/const.php';


// Remove trailing slash from URL to keep relative links consistent.
if (substr(AM_PATH_INFO, -1) == '/' && AM_PATH_INFO != '/') {
	header('Location: ' . AM_BASE_URL . AM_INDEX . rtrim(AM_PATH_INFO, '/'), false, 301);
	die;
}


// Test if PATH_INFO is the GUI page and AM_PAGE_GUI is defined (GUI active).
if (AM_PATH_INFO == AM_PAGE_GUI && AM_PAGE_GUI) {	
	$guiEnabled = true;
}
	

// The cache folder must be writable (resized images), also when caching is disabled!
if (!is_writable(AM_BASE_DIR . AM_DIR_CACHE)) {	
	die('The folder "' . AM_DIR_CACHE . '" must be writable by the web server!');
}


// Enable full error reporting, when debugging is enabled.
Debug::errorReporting();


// Split GUI form regular pages
if (isset($guiEnabled)) {
	
	$GUI = new \Automad\GUI\GUI();
	$output = $GUI->output;
	
} else {

	// Load page from cache or process template
	$Cache = new Cache();

	if ($Cache->pageCacheIsApproved()) {

		// If cache is up to date and the cached file exists,
		// just get the page from the cache.
		$output = $Cache->readPageFromCache();
	
	} else {
	
		// Else check if the site object cache is ok...
		if ($Cache->automadObjectCacheIsApproved()) {
		
			// If approved, load site from cache...
			$Automad = $Cache->readAutomadObjectFromCache();
		
		} else {
	
			// Else create new Automad.
			$Automad = new Automad();
			$Cache->writeAutomadObjectToCache($Automad);
	
		}
	
		// Render template
		$Template = new Template($Automad);
		$output = $Template->render();
	
		// Save output to cache if page actually exists.
		if ($Automad->currentPageExists()) {
			
			$Cache->writePageToCache($output);
			
		} else {
			
			Debug::log('Page does not exist, caching will be skipped!');
			
		}
		
		// Generate sitemap.xml
		new Sitemap($Automad->getCollection());
	
	}
	
}


// If debug is enabled, prepend the logged information to the closing </body> tag and echo the page.
echo str_replace('</body>', Debug::getLog() . '</body>', $output);


?>