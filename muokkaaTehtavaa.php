<?php
	session_start();
	require "tietokanta.php";
	$tehtavalista;
	$valueArvot;
	$schemanimi;
	if( !isset($_SESSION['kayttaja_id']) || !isset($_SESSION['opettaja']) || !isset($_SESSION['tl_id']) || !isset($_SESSION['numero'])){
		header("Location: /~xxxxxx");
	}
	else{
		$schemanimi = 'tl'.pg_escape_string($_SESSION['tl_id']);
		if(empty($tehtavalista)){
			$sql = "SELECT tl_id, kuvaus, tietokantakuva_osoite FROM tehtavalista WHERE tl_id = :tl_id";
			$stmt = $conn->prepare($sql);
			$stmt->bindParam(':tl_id', $_SESSION['tl_id']);
			$stmt->execute();
			$tehtavalista = $stmt->fetch(PDO::FETCH_ASSOC);
		}
		
		$sql2 = "SELECT kuvaus, esimerkkivastaus, kyselytyyppi FROM tehtava INNER JOIN sisaltyy ON sisaltyy.t_id = tehtava.t_id WHERE sisaltyy.numero = :numero AND tl_id = :tl_id";
		$stmt2 = $conn->prepare($sql2);
		$stmt2->bindParam(':numero', $_SESSION['numero']);
		$stmt2->bindParam(':tl_id', $_SESSION['tl_id']);
		$stmt2->execute();
		$valueArvot = $stmt2->fetch(PDO::FETCH_ASSOC);
	}
	
	$ilmoitus = '';
	
	if(isset($_SESSION['numero']) && !empty($_POST['kuvaus']) && !empty(trim($_POST['esimerkkivastaus'])) && !empty($_POST['kyselytyyppi'])){
		$vastaus = '';
		$ilmoitus = '';
		$etsiLopetus = ';';
		$etsiSulku1 = '(';
		$etsiSulku2 = ')';
		$etsiMerkki = '\'';
		$etsiMerkki2 = '\"';
		$merkkiLkm = substr_count(trim($_POST['esimerkkivastaus']), $etsiMerkki);
		$merkki2Lkm = substr_count(trim($_POST['esimerkkivastaus']), $etsiMerkki2);
		$lopetusLkm = substr_count(trim($_POST['esimerkkivastaus']), $etsiLopetus);
		$Sulku1Lkm = substr_count(trim($_POST['esimerkkivastaus']), $etsiSulku1);
		$Sulku2Lkm = substr_count(trim($_POST['esimerkkivastaus']), $etsiSulku2);
		$sanat = explode(' ', strtoupper(trim($_POST['esimerkkivastaus'])));
		
		if($lopetusLkm != 1 || substr(trim($_POST['esimerkkivastaus']), -1) != ';' || $Sulku1Lkm != $Sulku2Lkm || strcmp($sanat[0], strtoupper($_POST['kyselytyyppi'])) != 0 || ($merkkiLkm % 2)!= 0 || ($merkki2Lkm % 2)!= 0){
			$ilmoitus = "Virheellinen syöte!";
			$valueArvot['esimerkkivastaus'] = trim($_POST['esimerkkivastaus']);
			$valueArvot['kuvaus'] = trim($_POST['kuvaus']);
			$valueArvot['kyselytyyppi'] = strtoupper($_POST['kyselytyyppi']);
		}
		elseif(strcmp("DELETE", strtoupper($_POST['kyselytyyppi'])) == 0){
			$conn->beginTransaction();
			$conn->prepare("SET search_path TO ".$schemanimi)->execute();
			$esimvastaus = trim($_POST['esimerkkivastaus']);
			$vast = $conn->prepare(substr_replace($esimvastaus,"", -1)."returning *");
			if($vast->execute()){
				$del = $vast->fetchAll(PDO::FETCH_ASSOC);
				if(!empty($del)){
					$ilmoitus = "Lause toimii!";
					$conn->rollBack();
					
					$conn->prepare("SET search_path = default")->execute();
					$sql3 = "UPDATE tehtava SET kuvaus = :kuvaus, esimerkkivastaus = :esimerkkivastaus, kyselytyyppi = :kyselytyyppi FROM sisaltyy WHERE sisaltyy.t_id = tehtava.t_id AND sisaltyy.numero = :numero AND sisaltyy.tl_id = :tl_id";
					$stmt3 = $conn->prepare($sql3);
					$stmt3->bindParam(':kuvaus', $_POST['kuvaus']);
					$stmt3->bindParam(':esimerkkivastaus', trim($_POST['esimerkkivastaus']));
					$stmt3->bindParam(':kyselytyyppi', strtoupper($_POST['kyselytyyppi']));
					$stmt3->bindParam(':numero', $_SESSION['numero']);
					$stmt3->bindParam(':tl_id', $_SESSION['tl_id']);
					
					if( $stmt3->execute() ):
						header("Location: /~xxxxxx/lisaaTehtavia.php");
					else:
						$ilmoitus = 'Virheelliset tiedot!';
						$valueArvot['kuvaus'] = $_POST['kuvaus'];
						$valueArvot['esimerkkivastaus'] = trim($_POST['esimerkkivastaus']);
						$valueArvot['kyselytyyppi'] = strtoupper($_POST['kyselytyyppi']);
					endif;
				}
				else{
					$ilmoitus = "Virheelliset tiedot!";
					$conn->rollBack();
					$valueArvot['esimerkkivastaus'] = trim($_POST['esimerkkivastaus']);
					$valueArvot['kuvaus'] = trim($_POST['kuvaus']);
					$valueArvot['kyselytyyppi'] = strtoupper($_POST['kyselytyyppi']);
				}
			}
			else{
				$ilmoitus = "Ei voitu suorittaa!";
				$conn->rollBack();
				$valueArvot['esimerkkivastaus'] = trim($_POST['esimerkkivastaus']);
				$valueArvot['kuvaus'] = trim($_POST['kuvaus']);
				$valueArvot['kyselytyyppi'] = strtoupper($_POST['kyselytyyppi']);
			}
		}
		else{
			$conn->beginTransaction();
			$conn->prepare("SET search_path TO ".$schemanimi)->execute();
			$esimvastaus = trim($_POST['esimerkkivastaus']);
			$vast = $conn->prepare($esimvastaus);
			if($vast->execute()){
				$ilmoitus = "Lause toimii!";
				$conn->rollBack();
				
				$conn->prepare("SET search_path = default")->execute();
				$sql3 = "UPDATE tehtava SET kuvaus = :kuvaus, esimerkkivastaus = :esimerkkivastaus, kyselytyyppi = :kyselytyyppi FROM sisaltyy WHERE sisaltyy.t_id = tehtava.t_id AND sisaltyy.numero = :numero AND sisaltyy.tl_id = :tl_id";
				$stmt3 = $conn->prepare($sql3);
				$stmt3->bindParam(':kuvaus', $_POST['kuvaus']);
				$stmt3->bindParam(':esimerkkivastaus', trim($_POST['esimerkkivastaus']));
				$stmt3->bindParam(':kyselytyyppi', strtoupper($_POST['kyselytyyppi']));
				$stmt3->bindParam(':numero', $_SESSION['numero']);
				$stmt3->bindParam(':tl_id', $_SESSION['tl_id']);
				
				if( $stmt3->execute() ):
					header("Location: /~xxxxxx/lisaaTehtavia.php");
				else:
					$ilmoitus = 'Virheelliset tiedot!';
					$valueArvot['kuvaus'] = $_POST['kuvaus'];
					$valueArvot['esimerkkivastaus'] = trim($_POST['esimerkkivastaus']);
					$valueArvot['kyselytyyppi'] = strtoupper($_POST['kyselytyyppi']);
				endif;
			}
			else{
				$ilmoitus = "Ei voitu suorittaa!";
				$conn->rollBack();
				$valueArvot['esimerkkivastaus'] = trim($_POST['esimerkkivastaus']);
				$valueArvot['kuvaus'] = trim($_POST['kuvaus']);
				$valueArvot['kyselytyyppi'] = strtoupper($_POST['kyselytyyppi']);
			}
		}
	}
?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Muokkaa tehtävää</title>
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
		
		<div id="tehtavalistan_tiedot">
			<?php if(!empty($tehtavalista['kuvaus'])): ?>
				<h1><?= $tehtavalista['kuvaus'] ?></h1>
			<?php endif; ?>
			<?php if(!empty($tehtavalista['kuvaus'])): ?>
				<img src="<?= $tehtavalista['tietokantakuva_osoite'] ?>" alt="tietokantakuva" width="50%" height="auto">
			<?php endif; ?>	
		</div>
		
		<h2>Muokkaa tehtävää nro <?php echo $_SESSION['numero'] ?></h2>
		
		<?php
			echo "<form action='muokkaaTehtavaa.php' method='POST'>";
			echo "Kuvaus:<br /><input type='text' name='kuvaus' size='50' placeholder='Syötä kuvaus' value=\"".$valueArvot['kuvaus']."\"><br />";
			echo "Esimerkkivastaus:<br /><textarea rows='4' cols='50' name='esimerkkivastaus' id='esimerkkivastaus' placeholder='Syötä esimerkkivastaus'>".$valueArvot['esimerkkivastaus']."</textarea><br />";
			switch($valueArvot['kyselytyyppi']){
				case "SELECT": echo "Kyselytyyppi:<br /><select name='kyselytyyppi'><option value='SELECT' selected>SELECT</option><option value='INSERT'>INSERT</option><option value='DELETE'>DELETE</option><option value='UPDATE'>UPDATE</option></select><br /><br />";break;
				case "INSERT": echo "Kyselytyyppi:<br /><select name='kyselytyyppi'><option value='SELECT'>SELECT</option><option value='INSERT' selected>INSERT</option><option value='DELETE'>DELETE</option><option value='UPDATE'>UPDATE</option></select><br /><br />";break;
				case "DELETE": echo "Kyselytyyppi:<br /><select name='kyselytyyppi'><option value='SELECT'>SELECT</option><option value='INSERT'>INSERT</option><option value='DELETE' selected>DELETE</option><option value='UPDATE'>UPDATE</option></select><br /><br />";break;
				case "UPDATE": echo "Kyselytyyppi:<br /><select name='kyselytyyppi'><option value='SELECT'>SELECT</option><option value='INSERT'>INSERT</option><option value='DELETE'>DELETE</option><option value='UPDATE' selected>UPDATE</option></select><br /><br />";break;
				default: echo "Kyselytyyppi:<br /><select name='kyselytyyppi'><option value='SELECT' selected>SELECT</option><option value='INSERT'>INSERT</option><option value='DELETE'>DELETE</option><option value='UPDATE'>UPDATE</option></select><br /><br />";break;
			}
			echo "<input type='submit' name='valmis' value='Valmis'>";
			echo "</form>";
			echo "<br />";
		?>
	</body> 
</html> 
