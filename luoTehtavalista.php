<?php
	session_start();
	
	if( !isset($_SESSION['kayttaja_id']) || !isset($_SESSION['opettaja']) ){
		header("Location: /~xxxxxx");
	}
	
	require "tietokanta.php";
	
	$ilmoitus = '';
	
	if(isset($_SESSION['kayttaja_id']) && !empty($_POST['kuvaus']) && !empty($_POST['osoite']) ){
		$ilmoitus = '';
		$conn->beginTransaction();
		$sql = "INSERT INTO tehtavalista (kuvaus, tietokantakuva_osoite, luontipvm, luonut) VALUES (:kuvaus, :tietokantakuva_osoite, :luontipvm, :luonut)";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':kuvaus', $_POST['kuvaus']);
		$stmt->bindParam(':tietokantakuva_osoite', $_POST['osoite']);
		$stmt->bindParam(':luontipvm', date('Y-m-d'));
		$stmt->bindParam(':luonut', $_SESSION['kayttaja_id']);
		if( $stmt->execute() ):
			$sql2 = "SELECT currval('tl_id_seq')";
			$stmt2 = $conn->query($sql2);
			if( $stmt2->execute() ):
				$tulos = $stmt2->fetchColumn();
				$_SESSION['tl_id'] = $tulos;
				header("Location: /~xxxxxx/taulutTehtavalistaan.php");
			else:
				$ilmoitus = 'Virhe!';
			endif;
		else:
			$ilmoitus = 'Virheelliset tiedot!';
		endif;
		$conn->commit();
	}
	
?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Luo tehtävälista</title>
  <link rel="stylesheet" type="text/css" href="css/yleis.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
 </head> 
	<body>
		<div class="header">
			<a href="index.php">Etusivu</a>
			<?php if( isset($_SESSION['kayttaja_id']) ): ?>
			<a href="kirjauduUlos.php">Kirjaudu ulos</a>
			<?php endif; ?>
		</div>
		
		<?php if(!empty($ilmoitus)): ?>
			<p><?= $ilmoitus ?></p>
		<?php endif; ?>
		
		<h1>Tehtävälista</h1>
		<?php
			echo "<form action='luoTehtavalista.php' method='POST'>";
			echo "Kuvaus:<br /><input type='text' name='kuvaus' size='50' placeholder='Syötä kuvaus'><br />";
			echo "Tietokantakuvan osoite:<br /><input type='text' name='osoite' size='50' placeholder='Syötä tietokantakuvan osoite'><br /><br />";
			echo "<input type='submit' name='luo' value='Taulujen hallinta'>";
			echo "</form>";
			echo "<br />";
		?>
	</body> 
</html> 
