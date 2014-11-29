<?php defined('BASEPATH') OR exit('No direct script access allowed');



class Uuap{

	function __construct($params=array()){
		// Load the settings from the central config file
		require_once (dirname(__FILE__).'/phpcas/config.php');
		// Load the CAS lib
		require_once (dirname(__FILE__).'/phpcas/CAS.php');
		// Initialize phpCAS
		phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);
		
		// For production use set the CA certificate that is the issuer of the cert
		// on the CAS server and uncomment the line below
		// phpCAS::setCasServerCACert($cas_server_ca_cert_path);
		
		// For quick testing you can disable SSL validation of the CAS server.
		// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
		// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
		phpCAS::setNoCasServerValidation();
		
		// force CAS authentication
		phpCAS::forceAuthentication();
		//phpCAS::isAuthenticated();
	}
	
	public function getUser(){
		return phpCAS::getUser();
	}
	
	public function getAttributes(){
		return phpCAS::getAttributes();
	}
	
}
