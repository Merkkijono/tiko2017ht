<?php
	session_start();
	require "tietokanta.php";

	if( !isset($_SESSION['kayttaja_id']) || !isset($_SESSION['opettaja']) || !isset($_SESSION['tl_id']) ){
		header("Location: /~xxxxxx");
	}
	else{
		$sql = "SELECT date(suoritus.alkamisaika) AS pvm, sessio.s_id, sessio.o_id, suoritus.vastaus, case when suoritus.vastattu_oikein = 'true' then 'Kyllä' else 'Ei' end as oikein, suoritus.monesko, (suoritus.lopetusaika-suoritus.alkamisaika) AS aika FROM sisaltyy, sessio, suoritus 
		WHERE sisaltyy.tl_id = sessio.toteuttaa AND sessio.s_id = suoritus.s_id AND sisaltyy.t_id = suoritus.t_id AND sisaltyy.numero = :numero AND sisaltyy.tl_id = :tl_id AND suoritus.vastaus IS NOT NULL ORDER BY oikein DESC, aika ASC, pvm DESC";
		$suoritukset = $conn->prepare($sql);
		$suoritukset->bindParam(':tl_id', $_SESSION['tl_id']);
		$suoritukset->bindParam(':numero', $_SESSION['numero']);
		$suoritukset->execute();
		
		$sql2 = "SELECT date(suoritus.alkamisaika) AS pvm, sessio.s_id, sessio.o_id FROM sisaltyy, sessio, suoritus 
		WHERE sisaltyy.tl_id = sessio.toteuttaa AND sessio.s_id = suoritus.s_id AND sisaltyy.t_id = suoritus.t_id AND sisaltyy.numero = :numero AND sisaltyy.tl_id = :tl_id AND suoritus.vastaus IS NULL ORDER BY sessio.s_id DESC";
		$keskeytykset = $conn->prepare($sql2);
		$keskeytykset->bindParam(':tl_id', $_SESSION['tl_id']);
		$keskeytykset->bindParam(':numero', $_SESSION['numero']);
		$keskeytykset->execute();
		
		$sql3 = "SELECT tehtava.kuvaus, tehtava.esimerkkivastaus, tehtava.kyselytyyppi FROM tehtava, sisaltyy WHERE tehtava.t_id = sisaltyy.t_id AND sisaltyy.numero = :numero AND sisaltyy.tl_id = :tl_id";
		$stmt3 = $conn->prepare($sql3);
		$stmt3->bindParam(':tl_id', $_SESSION['tl_id']);
		$stmt3->bindParam(':numero', $_SESSION['numero']);
		$stmt3->execute();
		$perustiedot = $stmt3->fetch(PDO::FETCH_ASSOC);
	}
	
	if(isset($_POST['palaa'])){
		header("Location: /~xxxxxx");
	}
	
	if(isset($_POST['yleistulokset'])){
		header("Location: /~xxxxxx/tehtavalistaInfo.php");
	}
	
?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8">
  <title>Info</title>
  <link rel="stylesheet" type="text/css" href="css/yleis.css">
 </head> 
 <body>
	<div class="header">
		<a href="index.php">Etusivu</a>
		<?php if( isset($_SESSION['kayttaja_id']) ): ?>
		<a href="kirjauduUlos.php">Kirjaudu ulos</a>
		<?php endif; ?>
	</div>
	
	<div id="tehtavainfo">
		<h1>Tietoja tehtävästä</h1>
		<?php
			echo "<h3>Kuvaus</h3>";
			echo "<p>". $perustiedot['kuvaus'] . "</p>";
			echo "<h3>Esimerkkivastaus</h3>";
			echo "<p>". $perustiedot['esimerkkivastaus'] . "</p>";
			echo "<h3>Kyselytyyppi</h3>";
			echo "<p>". $perustiedot['kyselytyyppi'] . "</p>";
		?>
	</div>
	

	<div id="suoritusinfo">
		<h2>Tietoja suorituksista</h2>
		<?php
			echo "<table id='suoritukset' align='center'>";
			echo "<tr>";  
			echo "<td align='center' width='100'><h3>Pvm</h3></td>";  
			echo "<td align='center' width='100'><h3>Sessio</h3></td>"; 
			echo "<td align='center' width='100'><h3>Opiskelija</h3></td>";  
			echo "<td align='center' width='200'><h3>Vastaus</h3></td>";
			echo "<td align='center' width='100'><h3>Vastattu oikein</h3></td>";
			echo "<td align='center' width='100'><h3>Yrityksiä</h3></td>";
			echo "<td align='center' width='200'><h3>Suoritusaika</h3></td>";				
			echo "</tr>";
			while($rivi=$suoritukset->fetch(PDO::FETCH_ASSOC)){echo "<tr>";  
			echo "<td align='center' width='100'>" . $rivi['pvm'] . "</td>";
			echo "<td align='center' width='100'>" . $rivi['s_id'] . "</td>";
			echo "<td align='center' width='100'>" . $rivi['o_id'] . "</td>";
			echo "<td align='center' width='200'>" . $rivi['vastaus'] . "</td>";
			echo "<td align='center' width='100'>" . $rivi['oikein'] . "</td>";
			echo "<td align='center' width='100'>" . $rivi['monesko'] . "</td>";
			echo "<td align='center' width='200'>" . $rivi['aika'] . "</td>";
			echo "</tr>";}
			echo "</table>";
			echo "<br />";
		?>
		<h3>Keskeytykset</h3>
		<?php
			echo "<table id='keskeytykset' align='center'>";
			echo "<tr>";  
			echo "<td align='center' width='100'><h3>Pvm</h3></td>";  
			echo "<td align='center' width='100'><h3>Sessio</h3></td>"; 
			echo "<td align='center' width='100'><h3>Opiskelija</h3></td>";  			
			echo "</tr>";
			while($rivi2=$keskeytykset->fetch(PDO::FETCH_ASSOC)){echo "<tr>";  
			echo "<td align='center' width='100'>" . $rivi2['pvm'] . "</td>";
			echo "<td align='center' width='100'>" . $rivi2['s_id'] . "</td>";
			echo "<td align='center' width='100'>" . $rivi2['o_id'] . "</td>";
			echo "</tr>";}
			echo "</table>";
			echo "<br />";
		?>
	</div>
	
	<form action='opiskelijatulokset.php' method='POST'>
		<input type='submit' name='yleistulokset' value='Tehtävälistan tulokset'>
		<input type='submit' name='palaa' value='Palaa etusivulle'>
	</form>
	<br />
 </body> 
</html> 
