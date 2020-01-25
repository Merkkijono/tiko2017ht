<?php
	session_start();
	require "tietokanta.php";
	$tehtava;
	$schemanimi;
	$ilmoitus;
	$tehtavaLkm;
	$tehtavalista;
	$vastaus;
	if( !isset($_SESSION['kayttaja_id']) || !isset($_SESSION['opiskelija']) || !isset($_SESSION['lista']) || !isset($_SESSION['sessio']) ){
		header("Location: /~xxxxxx");
	}
	else{
		if(empty($tehtavalista)){
			$sql = "SELECT tl_id, kuvaus, tietokantakuva_osoite FROM tehtavalista WHERE tl_id = :tl_id";
			$stmt = $conn->prepare($sql);
			$stmt->bindParam(':tl_id', $_SESSION['lista']);
			$stmt->execute();
			$tehtavalista = $stmt->fetch(PDO::FETCH_ASSOC);
		}
		
		$schemanimi = 'tl'.pg_escape_string($_SESSION['lista']);
		
		if(empty($tehtavaLkm)){
			$sql2 = "SELECT t_id FROM sisaltyy WHERE tl_id = :tl_id";
			$stmt2 = $conn->prepare($sql2);
			$stmt2->bindParam(':tl_id', $_SESSION['lista']);
			if($stmt2->execute()){
				$tehtavaLkm = $stmt2->rowCount();
				if(!isset($_SESSION['monesko'])){
					$_SESSION['monesko'] = 1;
				}
				if(!isset($_SESSION['yritys'])){
					$_SESSION['yritys'] = 1;
				}
			}
			else{
				$ilmoitus = "Tehtävälistaa ei löytynyt!";
			}	
		}
		
		if(isset($_SESSION['monesko']) && isset($_SESSION['lista']) && isset($_SESSION['sessio'])){
			$sql3 = "SELECT sisaltyy.numero, tehtava.t_id, tehtava.kuvaus, tehtava.esimerkkivastaus, tehtava.kyselytyyppi FROM tehtava, sisaltyy WHERE tehtava.t_id = sisaltyy.t_id AND sisaltyy.tl_id = :tl_id AND sisaltyy.numero = :numero";
			$stmt3 = $conn->prepare($sql3);
			$stmt3->bindParam(':tl_id', $_SESSION['lista']);
			$stmt3->bindParam(':numero', $_SESSION['monesko']);		
			if($stmt3->execute()){
				$tehtava = $stmt3->fetch(PDO::FETCH_ASSOC);
				$suorituslause = "INSERT INTO suoritus(s_id,t_id,monesko,vastattu_oikein,alkamisaika) VALUES(:s_id,:t_id,:monesko,'false',now())";
				$suoritus = $conn->prepare($suorituslause);
				$suoritus->bindParam(':s_id', $_SESSION['sessio']);
				$suoritus->bindParam(':t_id', $tehtava['t_id']);
				$suoritus->bindParam(':monesko', $_SESSION['monesko']);
				if(!$suoritus->execute()){
					$ilmoitus = "Virhe!";
				}
			}
			else{
				$ilmoitus = "Tehtävää ei löytynyt!";
			}	
		}
		else{
			$ilmoitus = "Tehtävää ei löytynyt!";
		}
	}
	
	function vertaaErot($a, $b)
	{
		 return array_diff($a, $b);
	}

	function vertaaYhdistavyys($a, $b)
	{
		 return strcmp($a['value'], $b['value']);
	}

	
	if(isset($_POST['vastaa']) && !empty($_POST['vastaus']) && !empty($schemanimi)){
		$etsiLopetus = ';';
		$etsiSulku1 = '(';
		$etsiSulku2 = ')';
		$etsiMerkki = '\'';
		$etsiMerkki2 = '\"';
		$merkkiLkm = substr_count(trim($_POST['vastaus']), $etsiMerkki);
		$merkki2Lkm = substr_count(trim($_POST['vastaus']), $etsiMerkki2);
		$lopetusLkm = substr_count(trim($_POST['vastaus']), $etsiLopetus);
		$Sulku1Lkm = substr_count(trim($_POST['vastaus']), $etsiSulku1);
		$Sulku2Lkm = substr_count(trim($_POST['vastaus']), $etsiSulku2);
		$sanat = explode(' ', strtoupper(trim($_POST['vastaus'])));
		
		if($lopetusLkm != 1 || substr(trim($_POST['vastaus']), -1) != ';' || $Sulku1Lkm != $Sulku2Lkm || strcmp($sanat[0], $tehtava['kyselytyyppi']) != 0 || ($merkkiLkm % 2)!= 0 || ($merkki2Lkm % 2)!= 0){
			$ilmoitus = "Tarkista syötteen oikeellisuus!<br />(kyselytyyppi, lopetusmerkki, sulut ja lainausmerkit)";
			$vastaus = trim($_POST['vastaus']);
		}
		else{
			$palautusOpis;
			$palautusOpet;
			
			if(strcmp("SELECT", $tehtava['kyselytyyppi']) == 0){
				$conn->beginTransaction();
				$conn->prepare("SET search_path TO ".$schemanimi)->execute();
				$opiskelijavastaus = trim($_POST['vastaus']);
				$opv = $conn->prepare($opiskelijavastaus);
				if($opv->execute()){
					$palautusOpis = $opv->fetchAll(PDO::FETCH_ASSOC);
				}
				else{
					$ilmoitus = "Annettua lausetta ei voitu suorittaa";
					$palautusOpis = null;
				}
				$conn->rollBack();
				
				$conn->beginTransaction();
				$conn->prepare("SET search_path TO ".$schemanimi)->execute();
				$esimerkkkivastaus = trim($tehtava['esimerkkivastaus']);
				$emv = $conn->prepare($esimerkkkivastaus);
				if($emv->execute()){
					$palautusOpet = $emv->fetchAll(PDO::FETCH_ASSOC);
				}
				else{
					$ilmoitus = "Esimerkkivastausta ei voitu suorittaa";
					$palautusOpet = null;
				}
				$conn->rollBack();
			}
			else{
				$conn->beginTransaction();
				$conn->prepare("SET search_path TO ".$schemanimi)->execute();
				$opiskelijavastaus = trim($_POST['vastaus']);
				$opv = $conn->prepare(substr_replace($opiskelijavastaus,"", -1)."returning *");
				if($opv->execute()){
					$palautusOpis = $opv->fetchAll(PDO::FETCH_ASSOC);
				}
				else{
					$palautusOpis = null;
					$ilmoitus = "Annettua lausetta ei voitu suorittaa";
				}
				$conn->rollBack();
				
				$conn->beginTransaction();
				$conn->prepare("SET search_path TO ".$schemanimi)->execute();
				$esimerkkkivastaus = trim($tehtava['esimerkkivastaus']);
				$emv = $conn->prepare(substr_replace($esimerkkkivastaus,"", -1)."returning *");
				if($emv->execute()){
					$palautusOpet = $emv->fetchAll(PDO::FETCH_ASSOC);
				}
				else{
					$palautusOpet = null;
					$ilmoitus = "Esimerkkivastausta ei voitu suorittaa";
				}
				$conn->rollBack();
			}
			
			$tulos = array_udiff($palautusOpis, $palautusOpet, 'vertaaErot');
			$tulos2 = array_uintersect($palautusOpis, $palautusOpet, 'vertaaYhdistavyys');
			$tulos3 = count($tulos2, COUNT_RECURSIVE);
			$tulos4 = count($palautusOpet, COUNT_RECURSIVE);
	
			if(empty($tulos) && !empty($tulos2) && ($tulos3 == $tulos4)){
				$ilmoitus = "Oikein!";
				if(isset($_SESSION['sessio']) && isset($_SESSION['yritys'])){
					$suorituspaivitys = "UPDATE suoritus SET vastaus = :vastaus, monesko = :monesko, vastattu_oikein = 'true', lopetusaika = now() WHERE s_id = :s_id AND t_id = :t_id";
					$paivitys = $conn->prepare($suorituspaivitys);
					$paivitys->bindParam(':vastaus', trim($_POST['vastaus']));
					$paivitys->bindParam(':s_id', $_SESSION['sessio']);
					$paivitys->bindParam(':monesko', $_SESSION['yritys']);
					$paivitys->bindParam(':t_id', $tehtava['t_id']);
					if(!$paivitys->execute()){
						$ilmoitus = "Virhe!";
					}
				}
				else{
					$ilmoitus = "Virhe!";
				}
				$_SESSION['yritys'] = 1;
				if($_SESSION['monesko'] < $tehtavaLkm){
					$_SESSION['monesko'] = $_SESSION['monesko']+1;
					echo "<meta http-equiv='refresh' content='0'>";
				}
				else{
					if(isset($_SESSION['sessio'])){
						$sql4 = "UPDATE sessio SET lopetusaika = now() WHERE s_id = :s_id";
						$stmt4 = $conn->prepare($sql4);
						$stmt4->bindParam(':s_id', $_SESSION['sessio']);
						if($stmt4->execute()){
							unset($_SESSION['monesko']);
							unset($_SESSION['yritys']);
							header("Location: /~xxxxxx/suoritusInfo.php");								
						}
						else{
							$ilmoitus = "Virhe!";
							unset($_SESSION['monesko']);
							unset($_SESSION['yritys']);
							header("Location: /~xxxxxx/suoritusInfo.php");	
						}
					}
					else{
							$ilmoitus = "Virhe!";
							unset($_SESSION['monesko']);
							unset($_SESSION['yritys']);
							header("Location: /~xxxxxx/suoritusInfo.php");
					}
				}	
			}
			else{
				$ilmoitus = "Väärin!";
				if($_SESSION['yritys'] < 3){
					$_SESSION['yritys'] = $_SESSION['yritys'] + 1;
					$vastaus = trim($_POST['vastaus']);
				}
				else{
					if(isset($_SESSION['sessio']) && isset($_SESSION['yritys'])){
						$suorituspaivitys = "UPDATE suoritus SET vastaus = :vastaus, monesko = :monesko, vastattu_oikein = 'false', lopetusaika = now() WHERE s_id = :s_id AND t_id = :t_id";
						$paivitys = $conn->prepare($suorituspaivitys);
						$paivitys->bindParam(':vastaus', trim($_POST['vastaus']));
						$paivitys->bindParam(':s_id', $_SESSION['sessio']);
						$paivitys->bindParam(':monesko', $_SESSION['yritys']);
						$paivitys->bindParam(':t_id', $tehtava['t_id']);
						if(!$paivitys->execute()){
							$ilmoitus = "Virhe!";
						}
					}
					else{
						$ilmoitus = "Virhe!";
					}
					$_SESSION['yritys'] = 1;
					if($_SESSION['monesko'] < $tehtavaLkm){
						$_SESSION['monesko'] = $_SESSION['monesko']+1;
						echo "<meta http-equiv='refresh' content='0'>";
					}
					else{
						if(isset($_SESSION['sessio'])){
							$sql4 = "UPDATE sessio SET lopetusaika = now() WHERE s_id = :s_id";
							$stmt4 = $conn->prepare($sql4);
							$stmt4->bindParam(':s_id', $_SESSION['sessio']);
							if($stmt4->execute()){
								unset($_SESSION['monesko']);
								unset($_SESSION['yritys']);
								header("Location: /~xxxxxx/suoritusInfo.php");								
							}
							else{
								$ilmoitus = "Virhe!";
								unset($_SESSION['monesko']);
								unset($_SESSION['yritys']);
								header("Location: /~xxxxxx/suoritusInfo.php");	
							}
						}
						else{
								$ilmoitus = "Virhe!";
								unset($_SESSION['monesko']);
								unset($_SESSION['yritys']);
								header("Location: /~xxxxxx/suoritusInfo.php");
						}
					}	
				}
			} 
		}
	}
?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Tehtävälistan suoritus</title>
  <link rel="stylesheet" type="text/css" href="css/yleis.css">
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
	<div id="tehtavan_tiedot">
		<?php echo "<h1>Tehtävä ".$_SESSION['monesko']."/".$tehtavaLkm."</h1>"; 
				echo "<h2>".$tehtava['kuvaus']."</h2>";
				echo "<p>Kyselytyyppi: ".$tehtava['kyselytyyppi']."</p>";
				echo "<p>Yritys ".$_SESSION['yritys']."/3</p>";
				echo "<form action='tehtavalistanSuoritus.php' method='POST'>";
				echo "Vastaus:<br /><textarea rows='4' cols='50' name='vastaus' id='vastaus' placeholder='Syötä vastaus'>".$vastaus."</textarea><br /><br />";
				echo "<input type='submit' name='vastaa' value='Vastaa'>";
				echo "</form>";
				echo "<br />";
				echo "<br />";
		?> 
	</div>
 </body> 
</html> 
