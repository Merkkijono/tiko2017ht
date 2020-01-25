<?php
	session_start();
	require "tietokanta.php";
	$tulokset;
	if( !isset($_SESSION['kayttaja_id']) || !isset($_SESSION['opiskelija']) || !isset($_SESSION['lista']) || !isset($_SESSION['sessio']) ){
		header("Location: /~xxxxxx");
	}
	else{
		$sql = "select (select count(vastattu_oikein) from suoritus where s_id = :s_id and vastattu_oikein = 'true') as oikein,
  (select count(vastattu_oikein) from suoritus where s_id = :s_id) as yht";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':s_id', $_SESSION['sessio']);
		$stmt->execute();
		$tulokset = $stmt->fetch(PDO::FETCH_ASSOC);
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
	<div id="tulokset">
		<?php if(!empty($tulokset)): ?>
			<h2>Vastasit oikein <?= $tulokset['oikein'] ?>/<?= $tulokset['yht'] ?></h2>
		<?php endif; ?>
	</div>
	<form action='suoritusInfo.php' method='POST'>
		<input type='submit' name='palaa' value='Palaa etusivulle'>
	</form>
 </body> 
</html> 
