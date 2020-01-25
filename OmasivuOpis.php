<?php
	session_start();
	require "tietokanta.php";
	$tehtavalistat;
	$ilmoitus;
	$tulokset;
	if( !isset($_SESSION['kayttaja_id']) || !isset($_SESSION['opiskelija']) ){
		header("Location: /~xxxxxx");
	}
	else{
		$sql = "SELECT tl_id, kuvaus FROM tehtavalista ORDER BY kuvaus";
		$tehtavalistat = $conn->prepare($sql);
		$tehtavalistat->execute();
		
		$sql = "SELECT date(sessio.alkamisaika) AS pvm, tehtavalista.kuvaus, (select count(vastattu_oikein) from suoritus where s_id = sessio.s_id and vastattu_oikein = 'true') as oikein, (select count(vastattu_oikein) from suoritus where s_id = sessio.s_id) as yht, case when lopetusaika is null then 'x' else '' end as keskeytys FROM sessio INNER JOIN tehtavalista ON tehtavalista.tl_id = sessio.toteuttaa WHERE sessio.o_id = :o_id ORDER BY pvm";
		$tulokset = $conn->prepare($sql);
		$tulokset->bindParam(':o_id', $_SESSION['kayttaja_id']);
		$tulokset->execute();
	}
	
	if(isset($_POST['tee']) && !empty($_POST['tehtavalistat'])){
		$_SESSION['lista'] = pg_escape_string($_POST['tehtavalistat']);
		$sql2 = "INSERT INTO sessio (o_id, alkamisaika, toteuttaa) VALUES(:o_id,now(),:toteuttaa)";
		$stmt = $conn->prepare($sql2);
		$stmt->bindParam(':o_id', $_SESSION['kayttaja_id']);
		$stmt->bindParam(':toteuttaa', $_POST['tehtavalistat']);
		if($stmt->execute()){
			$sql3 = "SELECT currval('s_id_seq')";
			$stmt3 = $conn->query($sql3);
			if( $stmt3->execute() ){
				$sid = $stmt3->fetchColumn();
				$_SESSION['sessio'] = $sid;
				if(isset($_SESSION['monesko'])){
					unset($_SESSION['monesko']);
					$_SESSION['monesko'];
				}
				else{
					$_SESSION['monesko'];
				}
				unset($_SESSION['yritys']);
				header("Location: /~xxxxxx
				/tehtavalistanSuoritus.php");
			}
			else{
				$ilmoitus = "Virhe1!";
			}
		}
		else{
			$ilmoitus = "Virhe2!";
		}
	}
?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Omasivu</title>
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
	
	<h2>Valitse tehtävälista</h2>
	<?php
		echo "<form action='OmasivuOpis.php' method='POST'>";
		echo "<select name='tehtavalistat'>";
		while($lista=$tehtavalistat->fetch(PDO::FETCH_ASSOC)){echo "<option value=".$lista['tl_id'].">".$lista['kuvaus']."</option>";}
		echo "</select><br /><br />";
		echo "<input type='submit' name='tee' value='Tee tehtävälista'>";
		echo "</form>";
		echo "<br />";
	?>
	<h3>Aikaisemmat suoritukset</h3>
	<?php
		echo "<table id='suoritukset' align='center'>";
		echo "<tr>";  
		echo "<td align='center' width='150'><h3>Suorituspvm</h3></td>";  
		echo "<td align='center' width='150'><h3>Tehtävälista</h3></td>"; 
		echo "<td align='center' width='100'><h3>Oikeat vastaukset</h3></td>";  
		echo "<td align='center' width='100'><h3>Tehtäviä suoritettu</h3></td>";
		echo "<td align='center' width='100'><h3>Keskeytetty</h3></td>"; 		
		echo "</tr>";
		while($rivi=$tulokset->fetch(PDO::FETCH_ASSOC)){echo "<tr>";
			echo "<td align='center' width='150'>" . $rivi['pvm'] . "</td>";
			echo "<td align='center' width='150'>" . $rivi['kuvaus'] . "</td>";
			echo "<td align='center' width='100'>" . $rivi['oikein'] . "</td>";
			echo "<td align='center' width='100'>" . $rivi['yht'] . "</td>";
			echo "<td align='center' width='100'>" . $rivi['keskeytys'] . "</td>";
			echo "</tr>";
		}  
		echo "</table>";
		echo "<br />";	
	?>
 </body> 
</html> 
