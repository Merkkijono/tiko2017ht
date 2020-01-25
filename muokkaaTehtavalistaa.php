<?php
	session_start();
	require "tietokanta.php";
	$valueArvot;
	if( !isset($_SESSION['kayttaja_id']) || !isset($_SESSION['opettaja']) || !isset($_SESSION['tl_id'])){
		header("Location: /~xxxxxx");
	}
	else{
		$sql = "SELECT kuvaus, tietokantakuva_osoite FROM tehtavalista WHERE tl_id = :tl_id";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':tl_id', $_SESSION['tl_id']);
		$stmt->execute();
		$valueArvot = $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	$ilmoitus = '';
	
	if(isset($_SESSION['tl_id']) &&!empty($_POST['kuvaus']) && !empty($_POST['osoite']) ){
		$ilmoitus = '';
		$sql2 = "UPDATE tehtavalista SET kuvaus = :kuvaus, tietokantakuva_osoite = :tietokantakuva_osoite WHERE tl_id = :tl_id";
		$stmt2 = $conn->prepare($sql2);
		$stmt2->bindParam(':kuvaus', $_POST['kuvaus']);
		$stmt2->bindParam(':tietokantakuva_osoite', $_POST['osoite']);
		$stmt2->bindParam(':tl_id', $_SESSION['tl_id']);
		
		if( $stmt2->execute() ):
			header("Location: /~xxxxxx/taulutTehtavalistaan.php");
		else:
			$ilmoitus = 'Virheelliset tiedot!';
			$valueArvot['kuvaus'] = $_POST['kuvaus'];
			$valueArvot['tietokantakuva_osoite'] = $_POST['osoite'];
		endif;
	}
?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Muokkaa tehtävälistaa</title>
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
			echo "<form action='muokkaaTehtavalistaa.php' method='POST'>";
			echo "Kuvaus:<br /><input type='text' name='kuvaus' size='50' placeholder='Syötä kuvaus' value=\"".$valueArvot['kuvaus']."\"><br />";
			echo "Tietokantakuvan osoite:<br /><input type='text' name='osoite' size='50' placeholder='Syötä tietokantakuvan osoite'value=\"".$valueArvot['tietokantakuva_osoite']."\"><br /><br />";
			echo "<input type='submit' name='luonti' value='Taulujenhallinta'>";
			echo "</form>";
			echo "<br />";
		?>
	</body> 
</html> 
