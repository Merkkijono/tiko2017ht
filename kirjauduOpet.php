<?php
	session_start();
	
	if( isset($_SESSION['kayttaja_id']) ){
		header("Location: /~xxxxxx");
	}
	
	require "tietokanta.php";
	
	if(!empty($_POST['opettajanro']) && !empty($_POST['salasana'])):
		$lause = $conn->prepare('SELECT opettajanro, etunimi, sukunimi, salasana FROM opettaja WHERE opettajanro = :opettajanro');
		$lause->bindParam(':opettajanro', $_POST['opettajanro']);
		$lause->execute();
		$tulos = $lause->fetch(PDO::FETCH_ASSOC);
		
		$ilmoitus = '';
		
		if(count($tulos) > 0 && password_verify($_POST['salasana'], $tulos['salasana'])){
			$_SESSION['kayttaja_id'] = $tulos['opettajanro'];
			$_SESSION['opettaja'] = 'opettaja';
			header("Location: /~xxxxxx/OmasivuOpet.php");
		}
		else{
			$ilmoitus = "Väärä salasana!";
		}
	endif;
?>
<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Opettajan kirjautuminen</title>
  <link rel="stylesheet" type="text/css" href="css/kirjaudu.css">
 </head> 
	<body>
		<div class="header">
			<a href="index.php">Etusivu</a>
		</div>
		
		<?php if(!empty($ilmoitus)): ?>
			<p><?= $ilmoitus ?></p>
		<?php endif; ?>
		
		<h1>Opettajan kirjautuminen</h1>
		<form action="kirjauduOpet.php" method="POST">
			<input type="text" placeholder="Syötä opettajanumero" name="opettajanro">
			<input type="password" placeholder="Syötä salasana" name="salasana">		
			<input type="submit" value="Kirjaudu">
		</form>
		<span>Vai oletko <a href="lisaaOpet.php">uusi opettaja</a></span>
	</body> 
</html> 
