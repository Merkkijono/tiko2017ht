<?php
	session_start();
	require "tietokanta.php";
	$tehtavalista;
	$tehtavat;
	$ilmoitus;
	$perustiedot;
	if( !isset($_SESSION['kayttaja_id']) || !isset($_SESSION['opettaja']) || !isset($_SESSION['tl_id']) ){
		header("Location: /~xxxxxx");
	}
	else{
		$sql = "SELECT MAX(lopetusaika-alkamisaika) AS max, MIN(lopetusaika-alkamisaika) AS min, AVG(lopetusaika-alkamisaika) AS keskiarvo, 
		(select count(vastattu_oikein) from sessio AS ses, suoritus where suoritus.s_id = ses.s_id AND ses.toteuttaa = :tl_id and suoritus.vastattu_oikein = 'true' and suoritus.lopetusaika IS NOT NULL) as oikein, 
		(select count(vastattu_oikein) from sessio AS ses, suoritus where suoritus.s_id = ses.s_id AND ses.toteuttaa = :tl_id and suoritus.lopetusaika IS NOT NULL) as yht,
		(select count(vastattu_oikein) from sessio AS ses, suoritus where suoritus.s_id = ses.s_id AND ses.toteuttaa = :tl_id and suoritus.lopetusaika IS NULL) as keskeytys FROM sessio WHERE toteuttaa = :tl_id AND lopetusaika IS NOT NULL";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':tl_id', $_SESSION['tl_id']);
		$stmt->execute();
		$tehtavalista = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$sql2 = "SELECT kuvaus, (select count(t_id) from sisaltyy where tl_id = :tl_id) as tehtavia FROM tehtavalista WHERE tl_id = :tl_id";
		$stmt2 = $conn->prepare($sql2);
		$stmt2->bindParam(':tl_id', $_SESSION['tl_id']);
		$stmt2->execute();
		$perustiedot = $stmt2->fetch(PDO::FETCH_ASSOC);
		
		$sql3 = "SELECT s.numero, tehtava.kuvaus, (select count(vastattu_oikein) from suoritus, sisaltyy where suoritus.t_id = sisaltyy.t_id AND sisaltyy.tl_id  = :tl_id and sisaltyy.numero = s.numero and suoritus.vastattu_oikein = 'true' and suoritus.lopetusaika IS NOT NULL) as oikein,
		(select count(vastattu_oikein) from suoritus, sisaltyy where suoritus.t_id = sisaltyy.t_id AND sisaltyy.tl_id  = :tl_id and sisaltyy.numero = s.numero and suoritus.lopetusaika IS NOT NULL) as yht,
		(select count(vastattu_oikein) from suoritus, sisaltyy where suoritus.t_id = sisaltyy.t_id AND sisaltyy.tl_id  = :tl_id and sisaltyy.numero = s.numero) as kaikki,
		(select round(avg(monesko),0) from suoritus, sisaltyy where suoritus.t_id = sisaltyy.t_id AND sisaltyy.tl_id  = :tl_id and sisaltyy.numero = s.numero and suoritus.lopetusaika IS NOT NULL and suoritus.vastattu_oikein = 'true') as yritykset,
		(select count(vastattu_oikein) from suoritus, sisaltyy where suoritus.t_id = sisaltyy.t_id AND sisaltyy.tl_id  = :tl_id and sisaltyy.numero = s.numero and suoritus.lopetusaika IS NULL) as keskeytetty,
		(select max(lopetusaika-alkamisaika) from suoritus, sisaltyy where suoritus.t_id = sisaltyy.t_id AND sisaltyy.tl_id  = :tl_id and sisaltyy.numero = s.numero and suoritus.lopetusaika IS NOT NULL) as max,
		(select min(lopetusaika-alkamisaika) from suoritus, sisaltyy where suoritus.t_id = sisaltyy.t_id AND sisaltyy.tl_id  = :tl_id and sisaltyy.numero = s.numero and suoritus.lopetusaika IS NOT NULL) as min,
		(select avg(lopetusaika-alkamisaika) from suoritus, sisaltyy where suoritus.t_id = sisaltyy.t_id AND sisaltyy.tl_id  = :tl_id and sisaltyy.numero = s.numero and suoritus.lopetusaika IS NOT NULL) as keskiarvo
		FROM sisaltyy s, tehtava WHERE s.tl_id = :tl_id AND tehtava.t_id = s.t_id ORDER BY keskiarvo DESC, s.numero ASC";
		$tehtavat = $conn->prepare($sql3);
		$tehtavat->bindParam(':tl_id', $_SESSION['tl_id']);
		$tehtavat->execute();
		
		$sql4 = "SELECT (select count(tehtava.kyselytyyppi) from tehtava, sisaltyy where kyselytyyppi = 'SELECT' and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id) as select, 
		(select count(vastattu_oikein) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'SELECT' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.vastattu_oikein = 'true') as oikein,
		(select avg(monesko) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'SELECT' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null and suoritus.vastattu_oikein = 'true') as yritykset,		
		(select count(vastattu_oikein) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'SELECT' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null) as vastaukset,
		(select count(s_id) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'SELECT' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is null) as keskeytykset,
		(select avg(lopetusaika-alkamisaika) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'SELECT' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null) as aika";
		$stmt4 = $conn->prepare($sql4);
		$stmt4->bindParam(':tl_id', $_SESSION['tl_id']);
		$stmt4->execute();
		$select = $stmt4->fetch(PDO::FETCH_ASSOC);
		
		$sql5 = "SELECT (select count(tehtava.kyselytyyppi) from tehtava, sisaltyy where kyselytyyppi = 'INSERT' and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id) as insert, 
		(select count(vastattu_oikein) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'INSERT' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.vastattu_oikein = 'true') as oikein,
		(select avg(monesko) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'INSERT' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null and suoritus.vastattu_oikein = 'true') as yritykset,		
		(select count(vastattu_oikein) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'INSERT' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null) as vastaukset,
		(select count(s_id) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'INSERT' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is null) as keskeytykset,
		(select avg(lopetusaika-alkamisaika) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'INSERT' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null) as aika";
		$stmt5 = $conn->prepare($sql5);
		$stmt5->bindParam(':tl_id', $_SESSION['tl_id']);
		$stmt5->execute();
		$insert = $stmt5->fetch(PDO::FETCH_ASSOC);
		
		$sql6 = "SELECT (select count(tehtava.kyselytyyppi) from tehtava, sisaltyy where kyselytyyppi = 'UPDATE' and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id) as update, 
		(select count(vastattu_oikein) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'UPDATE' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.vastattu_oikein = 'true') as oikein,
		(select avg(monesko) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'UPDATE' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null and suoritus.vastattu_oikein = 'true') as yritykset,		
		(select count(vastattu_oikein) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'UPDATE' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null) as vastaukset,
		(select count(s_id) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'UPDATE' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is null) as keskeytykset,
		(select avg(lopetusaika-alkamisaika) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'UPDATE' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null) as aika";
		$stmt6 = $conn->prepare($sql6);
		$stmt6->bindParam(':tl_id', $_SESSION['tl_id']);
		$stmt6->execute();
		$update = $stmt6->fetch(PDO::FETCH_ASSOC);
		
		$sql7 = "SELECT (select count(tehtava.kyselytyyppi) from tehtava, sisaltyy where kyselytyyppi = 'DELETE' and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id) as delete, 
		(select count(vastattu_oikein) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'DELETE' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.vastattu_oikein = 'true') as oikein,
		(select avg(monesko) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'DELETE' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null and suoritus.vastattu_oikein = 'true') as yritykset,		
		(select count(vastattu_oikein) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'DELETE' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null) as vastaukset,
		(select count(s_id) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'DELETE' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is null) as keskeytykset,
		(select avg(lopetusaika-alkamisaika) from suoritus, tehtava, sisaltyy where tehtava.kyselytyyppi = 'DELETE' and suoritus.t_id = tehtava.t_id and sisaltyy.t_id = tehtava.t_id and sisaltyy.tl_id = :tl_id and suoritus.lopetusaika is not null) as aika";
		$stmt7 = $conn->prepare($sql7);
		$stmt7->bindParam(':tl_id', $_SESSION['tl_id']);
		$stmt7->execute();
		$delete = $stmt7->fetch(PDO::FETCH_ASSOC);
		
		$sql8 = "SELECT opiskelija.paaaine, count(suoritus.t_id) as vastauksia FROM opiskelija INNER JOIN sessio ON sessio.o_id = opiskelija.o_id 
		INNER JOIN suoritus ON sessio.s_id = suoritus.s_id WHERE suoritus.lopetusaika IS NOT NULL AND sessio.toteuttaa = :tl_id GROUP BY opiskelija.paaaine;";
		$kaikki = $conn->prepare($sql8);
		$kaikki->bindParam(':tl_id', $_SESSION['tl_id']);
		$kaikki->execute();
		
		$sql9 = "SELECT opiskelija.paaaine, count(suoritus.t_id) as oikeita FROM opiskelija INNER JOIN sessio ON sessio.o_id = opiskelija.o_id 
		INNER JOIN suoritus ON sessio.s_id = suoritus.s_id WHERE suoritus.lopetusaika IS NOT NULL AND sessio.toteuttaa = :tl_id AND suoritus.vastattu_oikein = 'true' GROUP BY opiskelija.paaaine;";
		$oikein = $conn->prepare($sql9);
		$oikein->bindParam(':tl_id', $_SESSION['tl_id']);
		$oikein->execute();
	}
	
	if(isset($_POST['vastaukset']) && isset($_POST['numero'])){
		$_SESSION['numero'] = $_POST['numero'];
		header("Location: /~xxxxxx/opiskelijatulokset.php");
	}
	
	if(isset($_POST['palaa'])){
		header("Location: /~xxxxxx");
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
	
	<div id="tehtavalistainfo">
		<?php if(!empty($perustiedot)): ?>
			<h1>Tietoja tehtävälistan suorituksista</h1>
			<h3>Kuvaus</h3>
			<p><?= $perustiedot['kuvaus'] ?></p>
			<h3>Tehtäviä</h3>
			<p><?= $perustiedot['tehtavia'] ?></p>
		<?php endif; ?>
		<?php
			echo "<table id='tehtavalista' align='center'>";
			echo "<tr>";  
			echo "<td align='center' width='200'><h3>Käytetty aika(max)</h3></td>";  
			echo "<td align='center' width='200'><h3>Käytetty aika(min)</h3></td>"; 
			echo "<td align='center' width='200'><h3>Käytetty aika(kesk)</h3></td>";  
			echo "<td align='center' width='200'><h3>Oikeat vastaukset</h3></td>";
			echo "<td align='center' width='200'><h3>Tehtyjä tehtäviä</h3></td>";
			echo "<td align='center' width='200'><h3>Suoritus keskeytetty</h3></td>";			
			echo "</tr>";
			echo "<tr>";  
			echo "<td align='center' width='200'>" . $tehtavalista['max'] . "</td>";
			echo "<td align='center' width='200'>" . $tehtavalista['min'] . "</td>";
			echo "<td align='center' width='200'>" . $tehtavalista['keskiarvo'] . "</td>";
			if ($tehtavalista['yht'] > 0){
				echo "<td align='center' width='200'>" . $tehtavalista['oikein'] . "  (". round($tehtavalista['oikein']/$tehtavalista['yht']*100,0) ." %)</td>";
			}
			else{
				echo "<td align='center' width='200'></td>";				
			}
			echo "<td align='center' width='200'>" . $tehtavalista['yht'] . "</td>";
			echo "<td align='center' width='200'>" . $tehtavalista['keskeytys'] . "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<br />";		
		
			echo "<table id='kyselytyypit' align='center'>";
			echo "<tr>";
			echo "<td align='center' width='100'><h3>Tyyppi</h3></td>";			
			echo "<td align='center' width='100'><h3>Lkm</h3></td>";  
			echo "<td align='center' width='100'><h3>Vastattu oikein</h3></td>";
			echo "<td align='center' width='100'><h3>Onnistuminen yrityksellä(kesk)</h3></td>";			
			echo "<td align='center' width='100'><h3>Vastauksia</h3></td>";  
			echo "<td align='center' width='100'><h3>Keskeytyksiä</h3></td>";
			echo "<td align='center' width='200'><h3>Käytetty aika(kesk)</h3></td>";			
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='100'>SELECT</td>";			
			echo "<td align='center' width='100'>" . $select['select'] . "</td>";
			if($select['vastaukset'] > 0):
				echo "<td align='center' width='100'>" . $select['oikein'] . " (" . round($select['oikein']/$select['vastaukset']*100,0) . " %)</td>";
			else:
				echo "<td align='center' width='100'>0 %</td>";
			endif;
			echo "<td align='center' width='100'>" . round($select['yritykset'],0) . "</td>";
			echo "<td align='center' width='100'>" . $select['vastaukset'] . "</td>";
			echo "<td align='center' width='100'>" . $select['keskeytykset'] . "</td>";
			echo "<td align='center' width='200'>" . $select['aika'] . "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='100'>INSERT</td>";				
			echo "<td align='center' width='100'>" . $insert['insert'] . "</td>";
			if($insert['vastaukset'] > 0):
				echo "<td align='center' width='100'>" . $insert['oikein'] . " (" . round($insert['oikein']/$insert['vastaukset']*100,0) . " %)</td>";
			else:
				echo "<td align='center' width='100'>0 %</td>";
			endif;
			echo "<td align='center' width='100'>" . round($insert['yritykset'],0) . "</td>";
			echo "<td align='center' width='100'>" . $insert['vastaukset'] . "</td>";
			echo "<td align='center' width='100'>" . $insert['keskeytykset'] . "</td>";
			echo "<td align='center' width='200'>" . $insert['aika'] . "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='100'>UPDATE</td>";			
			echo "<td align='center' width='100'>" . $update['update'] . "</td>";
			if($update['vastaukset'] > 0):
				echo "<td align='center' width='100'>" . $update['oikein'] . " (" . round($update['oikein']/$update['vastaukset']*100,0) . " %)</td>";
			else:
				echo "<td align='center' width='100'>0 %</td>";
			endif;
			echo "<td align='center' width='100'>" . round($update['yritykset'],0) . "</td>";
			echo "<td align='center' width='100'>" . $update['vastaukset'] . "</td>";
			echo "<td align='center' width='100'>" . $update['keskeytykset'] . "</td>";
			echo "<td align='center' width='200'>" . $update['aika'] . "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center' width='100'>DELETE</td>";
			echo "<td align='center' width='100'>" . $delete['delete'] . "</td>";
			if($delete['vastaukset'] > 0):
				echo "<td align='center' width='100'>" . $delete['oikein'] . " (" . round($delete['oikein']/$delete['vastaukset']*100,0) . " %)</td>";
			else:
				echo "<td align='center' width='100'>0 %</td>";
			endif;
			echo "<td align='center' width='100'>" . round($delete['yritykset'],0) . "</td>";
			echo "<td align='center' width='100'>" . $delete['vastaukset'] . "</td>";
			echo "<td align='center' width='100'>" . $delete['keskeytykset'] . "</td>";
			echo "<td align='center' width='200'>" . $delete['aika'] . "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<br />";	
		?>
	</div>
	<div id="tehtavainfo">
	<h2>Tietoja tehtävälistan tehtävistä</h2>
		<?php
			echo "<table id='tehtavat' align='center'>";
			echo "<tr>";  
			echo "<td align='center' width='100'><h3>Numero</h3></td>";  
			echo "<td align='center' width='200'><h3>Kuvaus</h3></td>"; 
			echo "<td align='center' width='200'><h3>Oikeita vastauksia</h3></td>";  
			echo "<td align='center' width='200'><h3>Annettuja vastaukset</h3></td>";
			echo "<td align='center' width='200'><h3>Onnistuminen yrityksellä(kesk)</h3></td>";
			echo "<td align='center' width='200'><h3>Keskeytetty</h3></td>";
			echo "<td align='center' width='200'><h3>Käytetty aika(max)</h3></td>";
			echo "<td align='center' width='200'><h3>Käytetty aika(min)</h3></td>";	
			echo "<td align='center' width='200'><h3>Käytetty aika(kesk)</h3></td>";				
			echo "</tr>";
			while($rivi=$tehtavat->fetch(PDO::FETCH_ASSOC)){echo "<tr>";  
			echo "<td align='center' width='100'>" . $rivi['numero'] . "</td>";
			echo "<td align='center' width='200'>" . $rivi['kuvaus'] . "</td>";
			if ($rivi['yht'] > 0){
				echo "<td align='center' width='200'>" . $rivi['oikein'] . "  (". round($rivi['oikein']/$rivi['yht']*100,0) ." %)</td>";
			}
			else{
				echo "<td align='center' width='200'></td>";				
			}
			echo "<td align='center' width='200'>" . $rivi['yht'] . "</td>";
			echo "<td align='center' width='200'>" . $rivi['yritykset'] . "</td>";
			if ($rivi['kaikki'] > 0){
				echo "<td align='center' width='200'>" . $rivi['keskeytetty'] . "  (". round($rivi['keskeytetty']/$rivi['kaikki']*100,0) . " %)</td>";
			}
			else{
				echo "<td align='center' width='200'></td>";				
			}
			echo "<td align='center' width='200'>" . $rivi['max'] . "</td>";
			echo "<td align='center' width='200'>" . $rivi['min'] . "</td>";
			echo "<td align='center' width='200'>" . $rivi['keskiarvo'] . "</td>";
			echo "<td align='center' width='50'><form action='' method='post'><input type='hidden' name='numero' value=\"" . $rivi['numero'] . "\"><input type='submit' name='vastaukset' value='vastaukset' /></form></td>";
			echo "</tr>";}
			echo "</table>";
			echo "<br />";
		?>
		
	<h2>Tietoja tehtävälistan suorituksista pääaineittain</h2>
		<?php
			echo "<table id='paaaineet' align='center'>";
			echo "<tr>";  
			echo "<td align='center' width='200'><h3>Pääaine</h3></td>";  
			echo "<td align='center' width='200'><h3>Vastauksia</h3></td>"; 
			echo "<td align='center' width='200'><h3>Oikeita vastauksia</h3></td>";  			
			echo "</tr>";
			while($tieto=$kaikki->fetch(PDO::FETCH_ASSOC)){echo "<tr>";  
			echo "<td align='center' width='200'>" . $tieto['paaaine'] . "</td>";
			echo "<td align='center' width='200'>" . $tieto['vastauksia'] . "</td>";
			$tieto2=$oikein->fetch(PDO::FETCH_ASSOC);
			if ($tieto['vastauksia'] > 0){
				echo "<td align='center' width='200'>" . $tieto2['oikeita'] . " (" . round($tieto2['oikeita']/$tieto['vastauksia']*100, 0) . " %)</td>";
			}
			else{
				echo "<td align='center' width='200'>" . $tieto2['oikeita'] . "</td>";			
			}
			echo "</tr>";}
			echo "</table>";
			echo "<br />";
		?>
	</div>
	<form action='tehtavalistaInfo.php' method='POST'>
		<input type='submit' name='palaa' value='Palaa etusivulle'>
	</form>
	<br />
 </body> 
</html> 
