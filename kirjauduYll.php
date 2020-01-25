<?php
	session_start();
	
	if( isset($_SESSION['kayttaja_id']) ){
		header("Location: /~xxxxxx");
	}
	
	require "tietokanta.php";
	
	$ilmoitus = '';
	
	if(!empty($_POST['yllapitajanro']) && !empty($_POST['salasana'])):
		if($_POST['yllapitajanro'] == 123456789){
			$numero = 123456789;
			$lause = $conn->prepare('SELECT opettajanro, etunimi, sukunimi, salasana FROM opettaja WHERE opettajanro = :yllapitajanro');
			$lause->bindParam(':yllapitajanro', $numero);
			$lause->execute();
			$tulos = $lause->fetch(PDO::FETCH_ASSOC);
			
			if(count($tulos) > 0 && password_verify($_POST['salasana'], $tulos['salasana'])){
				$_SESSION['kayttaja_id'] = $tulos['opettajanro'];
				$_SESSION['yllapitaja'] = 'yllapitaja';
				$_SESSION['opettaja'] = 'opettaja';
				header("Location: /~xxxxxx/OmasivuOpet.php");
			}
			else{
				$ilmoitus = 'Väärä salasana!';
			}
		}
		else{
			$ilmoitus = 'Et ole ylläpitäjä';
		}
		
	endif;
?>
<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Ylläpitäjän kirjautuminen</title>
  <link rel="stylesheet" type="text/css" href="css/kirjaudu.css">
 </head> 
	<body>
		<div class="header">
			<a href="index.php">Etusivu</a>
		</div>
		
		<?php if(!empty($ilmoitus)): ?>
			<p><?= $ilmoitus ?></p>
		<?php endif; ?>
		
		<h1>Ylläpitäjän kirjautuminen</h1>
		<form action="kirjauduYll.php" method="POST">
			<input type="text" placeholder="Syötä ylläpitäjänumero" name="yllapitajanro">
			<input type="password" placeholder="Syötä salasana" name="salasana">		
			<input type="submit" value="Kirjaudu">
		</form>
	</body> 
</html> 
