<?php
	session_start();
	require "tietokanta.php";
	
	$tehtavalista;
	$valueArvot;
	$schemanimi;
	$ilmoitus;
	if( !isset($_SESSION['kayttaja_id']) || !isset($_SESSION['opettaja']) || !isset($_SESSION['tl_id'])){
		header("Location: /~xxxxxx");
	}
	else{
		$schemanimi = 'tl'.pg_escape_string($_SESSION['tl_id']);
		$sql = "CREATE SCHEMA IF NOT EXISTS ".$schemanimi." AUTHORIZATION tiko2017db18";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		
		if(empty($tehtavalista)){
			$sql2 = "SELECT tl_id, kuvaus, tietokantakuva_osoite FROM tehtavalista WHERE tl_id = :tl_id";
			$stmt2 = $conn->prepare($sql2);
			$stmt2->bindParam(':tl_id', $_SESSION['tl_id']);
			$stmt2->execute();
			$tehtavalista = $stmt2->fetch(PDO::FETCH_ASSOC);
		}
	}
	
	if(!empty($schemanimi)){
		$taulut = $conn->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema = :nimi');
		$taulut->bindParam(':nimi', $schemanimi);
		$taulut->execute();
	}
	
	
	
	if(isset($_POST['luo']) && !empty($_POST['luontilause'])){
		$etsiLopetus = ';';
		$etsiSulku1 = '(';
		$etsiSulku2 = ')';
		$etsiMerkki = '\'';
		$etsiMerkki2 = '\"';
		$merkkiLkm = substr_count(trim($_POST['luontilause']), $etsiMerkki);
		$merkki2Lkm = substr_count(trim($_POST['luontilause']), $etsiMerkki2);
		$lopetusLkm = substr_count(trim($_POST['luontilause']), $etsiLopetus);
		$Sulku1Lkm = substr_count(trim($_POST['luontilause']), $etsiSulku1);
		$Sulku2Lkm = substr_count(trim($_POST['luontilause']), $etsiSulku2);
		$sanat = explode(' ', strtoupper(trim($_POST['luontilause'])));
		
		if($lopetusLkm != 1 || substr(trim($_POST['luontilause']), -1) != ';' || $Sulku1Lkm != $Sulku2Lkm || strcmp($sanat[0]." ".$sanat[1], "CREATE TABLE") != 0 || ($merkkiLkm % 2)!= 0 || ($merkki2Lkm % 2)!= 0){
			$ilmoitus = "Virheellinen syöte!";
			$valueArvot['luontilause'] = trim($_POST['luontilause']);
		}
		else{
			$conn->prepare("SET search_path TO ".$schemanimi)->execute();
			$taulunLuonti = $conn->prepare(trim($_POST['luontilause']));
			if($taulunLuonti->execute()){
				echo "<meta http-equiv='refresh' content='0'>";
				$ilmoitus = "Taulun luonti onnistui!";
			}
			else{
				$ilmoitus = "Taulun luonti epäonnistui!";
				$valueArvot['luontilause'] = trim($_POST['luontilause']);
			}
		}
	}
	
	if(isset($_POST['sisalto']) && !empty($_POST['sisaltolause'])){
		$etsiLopetus = ';';
		$etsiSulku1 = '(';
		$etsiSulku2 = ')';
		$etsiMerkki = '\'';
		$etsiMerkki2 = '\"';
		$merkkiLkm = substr_count(trim($_POST['sisaltolause']), $etsiMerkki);
		$merkki2Lkm = substr_count(trim($_POST['sisaltolause']), $etsiMerkki2);
		$lopetusLkm = substr_count(trim($_POST['sisaltolause']), $etsiLopetus);
		$Sulku1Lkm = substr_count(trim($_POST['sisaltolause']), $etsiSulku1);
		$Sulku2Lkm = substr_count(trim($_POST['sisaltolause']), $etsiSulku2);
		$sanat = explode(' ', strtoupper(trim($_POST['sisaltolause'])));
		
		if($lopetusLkm != 1 || substr(trim($_POST['sisaltolause']), -1) != ';' || $Sulku1Lkm != $Sulku2Lkm || strcmp($sanat[0]." ".$sanat[1], "INSERT INTO") != 0 || ($merkkiLkm % 2)!= 0 || ($merkki2Lkm % 2)!= 0){
			$ilmoitus = "Virheellinen syöte!";
			$valueArvot['sisaltolause'] = trim($_POST['sisaltolause']);
		}
		else{
			$conn->prepare("SET search_path TO ".$schemanimi)->execute();
			$taulunLuonti = $conn->prepare(trim($_POST['sisaltolause']));
			if($taulunLuonti->execute()){
				echo "<meta http-equiv='refresh' content='0'>";
				$ilmoitus = "Sisällön vienti onnistui!";
			}
			else{
				$ilmoitus = "Sisällön vienti epäonnistui!";
				$valueArvot['sisaltolause'] = trim($_POST['sisaltolause']);
			}
		}
	}
	
	if(isset($_POST['tehtavat']))
	{
		header("Location: /~xxxxxx/lisaaTehtavia.php");
	}
	
	if(isset($_POST['poista']) && !empty($schemanimi))
	{
		$sql3 = "DROP SCHEMA IF EXISTS ".$schemanimi." CASCADE";
		$stmt3 = $conn->prepare($sql3);
		if($stmt3->execute()){
			echo "<meta http-equiv='refresh' content='0'>";
			$ilmoitus = "Taulujen poisto onnistui!";
		}
		else{
			$ilmoitus = "Taulujen poisto epäonnistui!";
		}
	}
?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Taulut tehtävälistaan</title>
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
		
		<h2>Taulut</h2>
		<?php
			echo "<table id='taulut' align='center'>";
			echo "<tr>";  
			echo "<td align='center' width='100'><h3>Nimi</h3></td>";
			echo "<td align='center' width='100'><h3>Sarake</h3></td>";
			echo "<td align='center' width='100'><h3>Rivejä</h3></td>";
			echo "<td align='center' width='50'><form action='' method='post'><input type='submit' name='poista' value='Poista taulut' /></form></td>";
			echo "</tr>";
			$lippu = 1;
			while($rivi=$taulut->fetch(PDO::FETCH_ASSOC)){
				echo "<tr>";  
				echo "<td align='center' width='100'>" . $rivi['table_name'] . "</td>";
				$sarakkeet = $conn->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema=:schema AND table_name=:taulu');
				$sarakkeet->bindParam(':schema', $schemanimi);
				$sarakkeet->bindParam(':taulu', $rivi['table_name']);
				$sarakkeet->execute();
				
				$conn->prepare("SET search_path TO ".$schemanimi)->execute();
				$taulu = pg_escape_string($rivi['table_name']);
				$sisalto = $conn->prepare("SELECT * FROM ".$taulu);
				$riveja;
				if($sisalto->execute()){
					$riveja = $sisalto->rowCount();
				}
				else{
					$riveja = 0;
				}
				
				if($sarakkeet->rowCount() > 0){
					while($rivi2=$sarakkeet->fetch(PDO::FETCH_ASSOC)){
						if($lippu == 1){
							echo "<td align='center' width='100'>" . $rivi2['column_name'] . "</td>";
							echo "<td align='center' width='100'>" . $riveja . "</td>";
							echo "</tr>";
							$lippu = 0;
						}
						else{
							echo "<tr>";
							echo "<td align='center' width='100'></td>";
							echo "<td align='center' width='100'>" . $rivi2['column_name'] . "</td>";
							echo "</tr>";
						}
					}
				}
				else{
					echo "<td align='center' width='100'></td>";
					echo "<td align='center' width='100'>" . $riveja . "</td>";
					echo "</tr>";
				}
				$lippu = 1;
			}  
			echo "</table>";
		?>
		<h3>Taulun luonti</h3>
		<?php
			echo "<form action='taulutTehtavalistaan.php' method='POST'>";
			echo "Taulun luontilause:<br /><textarea rows='4' cols='50' name='luontilause' id='luontilause' placeholder='Syötä taulun luontilause'>".$valueArvot['luontilause']."</textarea><br /><br />";
			echo "<input type='submit' name='luo' value='Luo taulu'>";
			echo "</form>";
			echo "<br />";
		?>
		<h3>Sisältöä tauluihin</h3>
		<?php
			echo "<form action='taulutTehtavalistaan.php' method='POST'>";
			echo "Sisällön luontilause:<br /><textarea rows='4' cols='50' name='sisaltolause' id='sisaltolause' placeholder='Syötä sisällön luontilause'>".$valueArvot['sisaltolause']."</textarea><br /><br />";
			echo "<input type='submit' name='sisalto' value='Vie sisältö tauluun'>";
			echo "</form>";
			echo "<br />";
			echo "<br />";
			echo "<form action='' method='post'><input type='submit' name='tehtavat' value='Tehtävienhallinta' /></form>";
			echo "<br />";
		?>
	</body> 
</html> 
