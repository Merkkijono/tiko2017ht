<?php
	session_start();
?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Etusivu</title>
  <link rel="stylesheet" type="text/css" href="css/index.css">
 </head> 
 <body>
	<div class="header">
		<a href="index.php">Etusivu</a>
		<?php if( isset($_SESSION['kayttaja_id']) ): ?>
		<a href="kirjauduUlos.php">Kirjaudu ulos</a>
		<?php endif; ?>
	</div>
	
	<?php if( isset($_SESSION['kayttaja_id']) && isset($_SESSION['opettaja']) ){
		header("Location: /~xxxxxx/OmasivuOpet.php");
	}
	elseif(isset($_SESSION['kayttaja_id']) && isset($_SESSION['opiskelija'])){
		header("Location: /~xxxxxx/OmasivuOpis.php");
	}
	elseif(isset($_SESSION['kayttaja_id']) && isset($_SESSION['yllapitaja'])){
		header("Location: /~xxxxxx/OmasivuOpet.php");
	}
	else{ ?>
	<h1>Valitse puolesi</h1>
	<div class="linkit">
		<a href="kirjauduOpet.php">Opettaja</a><br />
		<a href="kirjauduOpis.php">Opiskelija</a><br />
		<a href="kirjauduYll.php">Ylläpitäjä</a>
	</div>
	<?php } ?>
 </body> 
</html> 
