<?php
	$server = "";
	$port = ;
	$database = "";
	$username = "";
	$password = "";

	
	try{
		$conn = new PDO('pgsql:host='.$server.';port='.$port.';dbname='.$database.';user='.$username. ';password='.$password);
	} catch(PDOException $e){
		die("Yhteys epäonnistui: " . $e->getMessage());
	}
?>
