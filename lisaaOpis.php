<?php

	session_start();
	
	if( isset($_SESSION['kayttaja_id']) ){
		header("Location: /~xxxxxx");
	}
	
	require "tietokanta.php";
	
	$ilmoitus = '';

	if(!empty($_POST['opiskelijanro']) && !empty($_POST['etunimi']) && !empty($_POST['sukunimi'])
		&& !empty($_POST['paaaine']) && !empty($_POST['salasana']) && !empty($_POST['vahvista_salasana'])):
		if(strcmp($_POST['salasana'], $_POST['vahvista_salasana']) == 0):
			$sql = "INSERT INTO opiskelija (opiskelijanro, paaaine, etunimi, sukunimi, salasana) VALUES (:opiskelijanro, :paaaine, :etunimi, :sukunimi, :salasana)";
			$stmt = $conn->prepare($sql);
			$stmt->bindParam(':opiskelijanro', $_POST['opiskelijanro']);
			$stmt->bindParam(':paaaine', $_POST['paaaine']);
			$stmt->bindParam(':etunimi', $_POST['etunimi']);
			$stmt->bindParam(':sukunimi', $_POST['sukunimi']);
			$stmt->bindParam(':salasana', password_hash($_POST['salasana'], PASSWORD_BCRYPT));

			if( $stmt->execute() ):
				$ilmoitus = 'Opiskelija lisätty';
				header("Location: /~xxxxxx/kirjauduOpis.php");
			else:
				$ilmoitus = 'Virhe: Tarkista tiedot tai kokeile eri opiskelijanumeroa';
			endif;
		else:
			$ilmoitus = 'Salasanat eivät täsmää!';

		endif;
	endif;

?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Opiskelijan lisääminen</title>
  <link rel="stylesheet" type="text/css" href="css/kirjaudu.css">
 </head> 
	<body>
		<div class="header">
			<a href="index.php">Etusivu</a>
		</div>
		
		<?php if(!empty($ilmoitus)): ?>
			<p><?= $ilmoitus ?></p>
		<?php endif; ?>
		
		<h1>Opiskelijan lisääminen</h1>
		<form action="lisaaOpis.php" method="POST">
			<input type="text" placeholder="Syötä opiskelijanumero" name="opiskelijanro">
			<input type="text" placeholder="Syötä etunimi" name="etunimi">
			<input type="text" placeholder="Syötä sukunimi" name="sukunimi">
			<input type="text" placeholder="Syötä pääaine" name="paaaine">
			<input type="password" placeholder="Syötä salasana" name="salasana">
			<input type="password" placeholder="Vahvista salasana" name="vahvista_salasana">				
			<input type="submit" value="Lisää opiskelija">
		</form>
	</body> 
</html> 
