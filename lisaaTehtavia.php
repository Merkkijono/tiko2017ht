<?php
	session_start();
	require "tietokanta.php";
	$tehtavalista;
	$tehtavaNro;
	$vastaus;
	$kuvaus;
	$schemanimi;
	if( !isset($_SESSION['kayttaja_id']) || !isset($_SESSION['opettaja']) || !isset($_SESSION['tl_id'])){
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
		
		$sql2 = "SELECT t_id FROM sisaltyy WHERE tl_id = :tl_id";
		$stmt2 = $conn->prepare($sql2);
		$stmt2->bindParam(':tl_id', $_SESSION['tl_id']);
		if($stmt2->execute()){
			$tehtavaNro = $stmt2->rowCount()+1;
		}
		else{
			$tehtavaNro = 1;
		}
	}

	$ilmoitus = '';
	
	if(isset($_SESSION['kayttaja_id']) && !empty($_POST['kuvaus']) && !empty(trim($_POST['esimerkkivastaus'])) && !empty($_POST['kyselytyyppi']) && !empty($schemanimi) ){
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
			$vastaus = trim($_POST['esimerkkivastaus']);
			$kuvaus = trim($_POST['kuvaus']);
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
					
					$conn->beginTransaction();
					$conn->prepare("SET search_path = default")->execute();
					$sql3 = "INSERT INTO tehtava (kuvaus, esimerkkivastaus, kyselytyyppi, luonut, luontipvm) VALUES(:kuvaus, :esimerkkivastaus, :kyselytyyppi, :luonut, :luontipvm)";
					$stmt3 = $conn->prepare($sql3);
					$stmt3->bindParam(':kuvaus', $_POST['kuvaus']);
					$stmt3->bindParam(':esimerkkivastaus', trim($_POST['esimerkkivastaus']));
					$stmt3->bindParam(':kyselytyyppi', strtoupper($_POST['kyselytyyppi']));
					$stmt3->bindParam(':luontipvm', date('Y-m-d'));
					$stmt3->bindParam(':luonut', $_SESSION['kayttaja_id']);
					
					if( $stmt3->execute() ){
						$sql4 = "SELECT currval('t_id_seq')";
						$stmt4 = $conn->query($sql4);
						if( $stmt4->execute() ){
							$tid = $stmt4->fetchColumn();
							$sql5 = "INSERT INTO sisaltyy (t_id, tl_id, numero) VALUES(:t_id, :tl_id, :numero)";
							$stmt5 = $conn->prepare($sql5);
							$stmt5->bindParam(':t_id', $tid);
							$stmt5->bindParam(':tl_id', $_SESSION['tl_id']);
							$stmt5->bindParam(':numero', $tehtavaNro);
							if( $stmt5->execute() ){
								$ilmoitus = 'Tehtävä lisätty!';
								
								$haeid2 = $conn->prepare('SELECT t_id FROM sisaltyy WHERE tl_id = :tl_id');
								$haeid2->bindParam(':tl_id', $_SESSION['tl_id']);
								$haeid2->execute();
								
								while($tid2=$haeid2->fetch(PDO::FETCH_ASSOC)){
									$poista4 = $conn->prepare('DELETE FROM suoritus WHERE t_id = :t_id');
									$poista4->bindParam(':t_id', $tid2['t_id']);
									$poista4->execute();
								}
								
								$poista3 = $conn->prepare('DELETE FROM sessio WHERE toteuttaa = :tl_id');
								$poista3->bindParam(':tl_id', $_SESSION['tl_id']);
								$poista3->execute();
								
								header("Location: /~xxxxxx/lisaaTehtavia.php");
							}
							else{
								$ilmoitus = 'Virheelliset tiedot!';
								$vastaus = trim($_POST['esimerkkivastaus']);
								$kuvaus = trim($_POST['kuvaus']);
							}
						}
						else{
							$ilmoitus = 'Virheelliset tiedot!';
							$vastaus = trim($_POST['esimerkkivastaus']);
							$kuvaus = trim($_POST['kuvaus']);
						}
					}
					else{
						$ilmoitus = 'Virheelliset tiedot!';
						$vastaus = trim($_POST['esimerkkivastaus']);
						$kuvaus = trim($_POST['kuvaus']);
					}
					$conn->commit();
				}
				else{
					$ilmoitus = 'Virheelliset tiedot!';
					$conn->rollBack();
					$vastaus = trim($_POST['esimerkkivastaus']);
					$kuvaus = trim($_POST['kuvaus']);
				}
			}
			else{
				$ilmoitus = "Ei voitu suorittaa!";
				$conn->rollBack();
				$vastaus = trim($_POST['esimerkkivastaus']);
				$kuvaus = trim($_POST['kuvaus']);
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
				
				$conn->beginTransaction();
				$conn->prepare("SET search_path = default")->execute();
				$sql3 = "INSERT INTO tehtava (kuvaus, esimerkkivastaus, kyselytyyppi, luonut, luontipvm) VALUES(:kuvaus, :esimerkkivastaus, :kyselytyyppi, :luonut, :luontipvm)";
				$stmt3 = $conn->prepare($sql3);
				$stmt3->bindParam(':kuvaus', $_POST['kuvaus']);
				$stmt3->bindParam(':esimerkkivastaus', trim($_POST['esimerkkivastaus']));
				$stmt3->bindParam(':kyselytyyppi', strtoupper($_POST['kyselytyyppi']));
				$stmt3->bindParam(':luontipvm', date('Y-m-d'));
				$stmt3->bindParam(':luonut', $_SESSION['kayttaja_id']);
				
				if( $stmt3->execute() ){
					$sql4 = "SELECT currval('t_id_seq')";
					$stmt4 = $conn->query($sql4);
					if( $stmt4->execute() ){
						$tid = $stmt4->fetchColumn();
						$sql5 = "INSERT INTO sisaltyy (t_id, tl_id, numero) VALUES(:t_id, :tl_id, :numero)";
						$stmt5 = $conn->prepare($sql5);
						$stmt5->bindParam(':t_id', $tid);
						$stmt5->bindParam(':tl_id', $_SESSION['tl_id']);
						$stmt5->bindParam(':numero', $tehtavaNro);
						if( $stmt5->execute() ){
							$ilmoitus = 'Tehtävä lisätty!';
							
							$haeid2 = $conn->prepare('SELECT t_id FROM sisaltyy WHERE tl_id = :tl_id');
							$haeid2->bindParam(':tl_id', $_SESSION['tl_id']);
							$haeid2->execute();
							
							while($tid2=$haeid2->fetch(PDO::FETCH_ASSOC)){
								$poista4 = $conn->prepare('DELETE FROM suoritus WHERE t_id = :t_id');
								$poista4->bindParam(':t_id', $tid2['t_id']);
								$poista4->execute();
							}
							
							$poista3 = $conn->prepare('DELETE FROM sessio WHERE toteuttaa = :tl_id');
							$poista3->bindParam(':tl_id', $_SESSION['tl_id']);
							$poista3->execute();
							
							header("Location: /~xxxxxx/lisaaTehtavia.php");
						}
						else{
							$ilmoitus = 'Virheelliset tiedot!';
							$vastaus = trim($_POST['esimerkkivastaus']);
							$kuvaus = trim($_POST['kuvaus']);
						}
					}
					else{
						$ilmoitus = 'Virheelliset tiedot!';
						$vastaus = trim($_POST['esimerkkivastaus']);
						$kuvaus = trim($_POST['kuvaus']);
					}
				}
				else{
					$ilmoitus = 'Virheelliset tiedot!';
					$vastaus = trim($_POST['esimerkkivastaus']);
					$kuvaus = trim($_POST['kuvaus']);
				}
				
				$conn->commit();
			}
			else{
				$ilmoitus = "Ei voitu suorittaa!";
				$conn->rollBack();
				$vastaus = trim($_POST['esimerkkivastaus']);
				$kuvaus = trim($_POST['kuvaus']);
			}
		}
	}
	
	if(isset($_SESSION['kayttaja_id']) && isset($_SESSION['tl_id'])){
		$tehtavat = $conn->prepare('SELECT sisaltyy.numero AS numero, tehtava.kuvaus AS kuvaus, tehtava.esimerkkivastaus AS esimerkkivastaus, tehtava.kyselytyyppi AS kyselytyyppi FROM tehtava INNER JOIN sisaltyy ON sisaltyy.t_id = tehtava.t_id WHERE sisaltyy.tl_id = :tl_id ORDER BY sisaltyy.numero');
		$tehtavat->bindParam(':tl_id', $_SESSION['tl_id']);
		$tehtavat->execute();
	}
	
	if(isset($_POST['poista']) && isset($_POST['numero']))
	{
		$conn->beginTransaction();
		$haeid = $conn->prepare('SELECT t_id FROM sisaltyy WHERE numero = :numero AND tl_id = :tl_id');
		$haeid->bindParam(':numero', $_POST['numero']);
		$haeid->bindParam(':tl_id', $_SESSION['tl_id']);
		$haeid->execute();
		$tid = $haeid->fetchColumn();
		
		$haeid2 = $conn->prepare('SELECT t_id FROM sisaltyy WHERE tl_id = :tl_id');
		$haeid2->bindParam(':tl_id', $_SESSION['tl_id']);
		$haeid2->execute();
		
		while($tid2=$haeid2->fetch(PDO::FETCH_ASSOC)){
			$poista4 = $conn->prepare('DELETE FROM suoritus WHERE t_id = :t_id');
			$poista4->bindParam(':t_id', $tid2['t_id']);
			$poista4->execute();
		}
		
		$poista3 = $conn->prepare('DELETE FROM sessio WHERE toteuttaa = :tl_id');
		$poista3->bindParam(':tl_id', $_SESSION['tl_id']);
		$poista3->execute();
		
		$poista = $conn->prepare('DELETE FROM sisaltyy WHERE numero = :numero AND tl_id = :tl_id');
		$poista->bindParam(':numero', $_POST['numero']);
		$poista->bindParam(':tl_id', $_SESSION['tl_id']);
		$poista->execute();
		
		$poista2 = $conn->prepare('DELETE FROM tehtava WHERE t_id = :t_id');
		$poista2->bindParam(':t_id', $tid);
		$poista2->execute();
		
		$paivita = $conn->prepare('UPDATE sisaltyy SET numero = numero-1 WHERE numero > :numero AND tl_id = :tl_id');
		$paivita->bindParam(':numero', $_POST['numero']);
		$paivita->bindParam(':tl_id', $_SESSION['tl_id']);
		$paivita->execute();
		$conn->commit();
		echo "<meta http-equiv='refresh' content='0'>";
	}
	
	if(isset($_POST['muokkaa']))
	{
		$haeid2 = $conn->prepare('SELECT t_id FROM sisaltyy WHERE tl_id = :tl_id');
		$haeid2->bindParam(':tl_id', $_SESSION['tl_id']);
		$haeid2->execute();
		
		while($tid2=$haeid2->fetch(PDO::FETCH_ASSOC)){
			$poista4 = $conn->prepare('DELETE FROM suoritus WHERE t_id = :t_id');
			$poista4->bindParam(':t_id', $tid2['t_id']);
			$poista4->execute();
		}
		
		$poista3 = $conn->prepare('DELETE FROM sessio WHERE toteuttaa = :tl_id');
		$poista3->bindParam(':tl_id', $_SESSION['tl_id']);
		$poista3->execute();
		
		$_SESSION['numero'] = $_POST['numero'];
		header("Location: /~xxxxxx/muokkaaTehtavaa.php");
	}
	
	if(isset($_POST['valmis']))
	{
		header("Location: /~xxxxxx/OmasivuOpet.php");
	}
?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Lisää tehtäviä</title>
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
			<?php if(!empty($tehtavalista['tietokantakuva_osoite'])): ?>
				<img src="<?= $tehtavalista['tietokantakuva_osoite'] ?>" alt="tietokantakuva" width="50%" height="auto">
			<?php endif; ?>	
		</div>
		
		<h2>Tehtävät </h2>
		<?php
			echo "<table id='tehtavat' align='center'>";
			echo "<tr>";  
			echo "<td align='center' width='50'><h3>Nro</h3></td>";
			echo "<td align='center' width='200'><h3>Kuvaus</h3></td>";  
			echo "<td align='center' width='200'><h3>Esimerkkivastaus</h3></td>";
			echo "<td align='center' width='200'><h3>Kyselytyyppi</h3></td>";
			echo "</tr>";
			while($rivi=$tehtavat->fetch(PDO::FETCH_ASSOC)){echo "<tr>";  
			echo "<td align='center' width='50'>" . $rivi['numero'] . "</td>";
			echo "<td align='center' width='100'>" . $rivi['kuvaus'] . "</td>";
			echo "<td align='center' width='200'>" . $rivi['esimerkkivastaus'] . "</td>";  
			echo "<td align='center' width='200'>" . $rivi['kyselytyyppi'] . "</td>";    
			echo "<td align='center' width='50'><form action='' method='post'><input type='hidden' name='numero' value=\"" . $rivi['numero'] . "\"><input type='submit' name='muokkaa' value='muokkaa' /></form></td>";
			echo "<td align='center' width='50'><form action='' method='post'><input type='hidden' name='numero' value=\"" . $rivi['numero'] . "\"><input type='submit' name='poista' value='poista' /></form></td>";
			echo "</tr>";}  
			echo "</table>";
		?>
		<h3>Lisää tehtävä</h3>
		<?php
			echo "<form action='lisaaTehtavia.php' method='POST'>";
			echo "Kuvaus:<br /><input type='text' name='kuvaus' size='50' placeholder='Syötä kuvaus' value=\"".$kuvaus."\"><br />";
			echo "Esimerkkivastaus:<br /><textarea rows='4' cols='50' name='esimerkkivastaus' id='esimerkkivastaus' placeholder='Syötä esimerkkivastaus'>".$vastaus."</textarea><br />";
			echo "Kyselytyyppi:<br /><select name='kyselytyyppi'><option value='SELECT'>SELECT</option><option value='INSERT'>INSERT</option><option value='DELETE'>DELETE</option><option value='UPDATE'>UPDATE</option></select><br /><br />";
			echo "<input type='submit' name='lisaa' value='Lisää tehtävä'>";
			echo "</form>";
			echo "<br />";
			echo "<br />";
			echo "<form action='' method='post'><input type='submit' name='valmis' value='Tehtävälista valmis' /></form>";
			echo "<br />";
		?>
	</body> 
</html> 
	
