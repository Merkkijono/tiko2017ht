<?php
	session_start();
	
	if( isset($_SESSION['kayttaja_id']) ){
		header("Location: /~xxxxxx");
	}
	
	require "tietokanta.php";
	
	if(!empty($_POST['opiskelijanro']) && !empty($_POST['salasana'])):
		$lause = $conn->prepare('SELECT o_id, opiskelijanro, paaaine, etunimi, sukunimi, salasana FROM opiskelija WHERE opiskelijanro = :opiskelijanro');
		$lause->bindParam(':opiskelijanro', $_POST['opiskelijanro']);
		$lause->execute();
		$tulos = $lause->fetch(PDO::FETCH_ASSOC);
		
		$ilmoitus ="";
		
		if(count($tulos) > 0 && password_verify($_POST['salasana'], $tulos['salasana'])){
			$_SESSION['kayttaja_id'] = $tulos['o_id'];
			$_SESSION['opiskelija'] = 'opiskelija';
			header("Location: /~xxxxxx/OmasivuOpis.php");
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
  <title>Opiskelijan kirjautuminen</title>
  <link rel="stylesheet" type="text/css" href="css/kirjaudu.css">
 </head> 
	<body>
		<div class="header">
			<a href="index.php">Etusivu</a>
		</div>
		
		<?php if(!empty($ilmoitus)): ?>
			<p><?= $ilmoitus ?></p>
		<?php endif; ?>
		
		<h1>Opiskelijan kirjautuminen</h1>
		<form action="kirjauduOpis.php" method="POST">
			<input type="text" placeholder="Syötä opiskelijanumero" name="opiskelijanro">
			<input type="password" placeholder="Syötä salasana" name="salasana">
			
			<input type="submit" value="Kirjaudu">
		</form>
		<span>Vai oletko <a href="lisaaOpis.php">uusi opiskelija</a></span>
	</body> 
</html> 
