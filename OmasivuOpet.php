<?php
	session_start();
	
	if( !isset($_SESSION['kayttaja_id']) || !isset($_SESSION['opettaja']) ){
		header("Location: /~xxxxxx");
	}
	
	require "tietokanta.php";
	if(isset($_SESSION['kayttaja_id'])){
		$tehtavalistat = $conn->prepare('SELECT tl_id, luonut, kuvaus, tietokantakuva_osoite, luontipvm, luonut FROM tehtavalista ORDER BY tl_id DESC');
		$tehtavalistat->execute();
	}
	
	if(isset($_POST['poista']))
	{
		$conn->beginTransaction();
		
		$haeidt = $conn->prepare('SELECT t_id FROM sisaltyy WHERE tl_id = :tl_id');
		$haeidt->bindParam(':tl_id', $_POST['tl_id']);
		$haeidt->execute();
		
		while($id=$haeidt->fetch(PDO::FETCH_ASSOC)){
			$poista5 = $conn->prepare('DELETE FROM suoritus WHERE t_id = :t_id');
			$poista5->bindParam(':t_id', $id['t_id']);
			$poista5->execute();
		}
		
		$poista4 = $conn->prepare('DELETE FROM sessio WHERE toteuttaa = :toteuttaa');
		$poista4->bindParam(':toteuttaa', $_POST['tl_id']);
		$poista4->execute();
		
		$poista = $conn->prepare('DELETE FROM sisaltyy WHERE tl_id = :tl_id');
		$poista->bindParam(':tl_id', $_POST['tl_id']);
		$poista->execute();
		
		$haeidt2 = $conn->prepare('SELECT t_id FROM sisaltyy WHERE tl_id = :tl_id');
		$haeidt2->bindParam(':tl_id', $_POST['tl_id']);
		$haeidt2->execute();
		
		while($id=$haeidt2->fetch(PDO::FETCH_ASSOC)){
			$poista2 = $conn->prepare('DELETE FROM tehtava WHERE t_id = :t_id');
			$poista2->bindParam(':t_id', $id['t_id']);
			$poista2->execute();
		}
		
		$poista3 = $conn->prepare('DELETE FROM tehtavalista WHERE tl_id = :tl_id');
		$poista3->bindParam(':tl_id', $_POST['tl_id']);
		$poista3->execute();
		
		$schemanimi = 'tl'.pg_escape_string($_POST['tl_id']);
		$poista5 = $conn->prepare("DROP SCHEMA IF EXISTS ".$schemanimi." CASCADE");
		if($poista5->execute()){
			
		}
		else{
			echo "Virhe!";
		}

		
		$conn->commit();
		echo "<meta http-equiv='refresh' content='0'>";
	}
	
	if(isset($_POST['tilastot']))
	{
		$_SESSION['tl_id'] = $_POST['tl_id'];
		header("Location: /~xxxxxx/tehtavalistaInfo.php");
	}
	
	if(isset($_POST['muokkaa']))
	{
		$_SESSION['tl_id'] = $_POST['tl_id'];
		header("Location: /~xxxxxx/muokkaaTehtavalistaa.php");
	}
?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Omasivu</title>
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
		
		<h1>Tehtävälistat</h1>
		<a href="luoTehtavalista.php">Luo uusi tehtävälista</a>
		<?php
			echo "<table id='tehtavalista' align='center'>";
			echo "<tr>";  
			echo "<td align='center' width='50'><h3>ID</h3></td>";
			echo "<td align='center' width='75'><h3>Luonut</h3></td>";			
			echo "<td align='center' width='100'><h3>Luontipvm</h3></td>"; 
			echo "<td align='center' width='200'><h3>Kuvaus</h3></td>";  
			echo "<td align='center' width='200'><h3>Tietokantakuvan_osoite</h3></td>";
			echo "<td align='center' width='200'><h3>Tehtäviä</h3></td>";  
			echo "</tr>";
			while($rivi=$tehtavalistat->fetch(PDO::FETCH_ASSOC)){echo "<tr>";  
			echo "<td align='center' width='50'>" . $rivi['tl_id'] . "</td>";
			echo "<td align='center' width='50'>" . $rivi['luonut'] . "</td>";
			echo "<td align='center' width='100'>" . $rivi['luontipvm'] . "</td>";
			echo "<td align='center' width='200'>" . $rivi['kuvaus'] . "</td>";  
			echo "<td align='center' width='200'>" . $rivi['tietokantakuva_osoite'] . "</td>";
			$sql = "SELECT t_id FROM sisaltyy WHERE tl_id = :tl_id";
			$stmt = $conn->prepare($sql);
			$stmt->bindParam(':tl_id', $rivi['tl_id']);
			if($stmt->execute()){
				$tehtavaLkm = $stmt->rowCount();
			}
			else{
				$tehtavaLkm = 0;
			}
			echo "<td align='center' width='50'>" . $tehtavaLkm . "</td>";
			if($rivi['luonut'] == $_SESSION['kayttaja_id'] || isset($_SESSION['yllapitaja'])){
				echo "<td align='center' width='50'><form action='' method='post'><input type='hidden' name='tl_id' value=\"" . $rivi['tl_id'] . "\"><input type='submit' name='muokkaa' value='muokkaa' /></form></td>";
				echo "<td align='center' width='50'><form action='' method='post'><input type='hidden' name='tl_id' value=\"" . $rivi['tl_id'] . "\"><input type='submit' name='tilastot' value='tilastot' /></form></td>";
				echo "<td align='center' width='50'><form action='' method='post'><input type='hidden' name='tl_id' value=\"" . $rivi['tl_id'] . "\"><input type='submit' name='poista' value='poista' /></form></td>";
			}
			echo "</tr>";}  
			echo "</table>";
			echo "<br />";
		?>
	</body> 
</html> 
