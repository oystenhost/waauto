<?php
/**
 * waauto
 *
 */
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function waauto_config() {
	$configarray = array(
    	"name" => "waauto",
    	"description" => "Whatsapp Gateway for WHMCS, powered by Wa Auto",
    	"version" => "1.0",
    	"author" => "<a href='https://www.waauto.in/'>Waauto.in</a> Team",
    	"language" => "english",
    	"fields" => array(
                "instance_id" => array (
                        "FriendlyName" => "Instance Id",
                        "Type" => "text",
                        "Size" => "25",
                        "Description" => "609ACF283XXXX",
                        "Default" => "",
                    ),
                "tawkto-key" => array (
                        "FriendlyName" => "Access Token",
                        "Type" =>  "text",
                        "Size" => "25",
                        "Description" => "c29a0efe453589d0da92a561XXXXXXXX",
                        "Default" => "",
                    ),
                    
                )
    );
	return $configarray;
}
