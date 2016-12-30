<?php
	
	//Multi-Source Database 
	
	//Source Database Config 1
	//ShunQing
	//Connection String here.Default "( DESCRIPTION = ( ADDRESS = (PROTOCOL = TCP) (HOST = localhost) (PORT = 1521) ) ( CONNECT_DATA = (SID=XE) (SERVICE_NAME = XE) ) )"
	$db_conn_string = "( DESCRIPTION = ( ADDRESS = (PROTOCOL = tcp) (HOST = *********) (PORT = 1521) ) ( CONNECT_DATA = (SID=XE) (SERVICE_NAME = XE) ) )";
	
	//User name,May be SYS,SYSTEM or NETVIEW
	$db_user = "NETVIEW";
	
	//Password
	$db_password = "******";
	
	
	//Cache Config
	$cache_addr = "*********";
	
	$cache_user = "****";
	
	$cache_password = "******";
	
	$cache_schemas = "****";
?>
