<?php
session_start();

require("secu.php");
require("config.php");

try{
$bdd = new PDO($_SGBD["nsd"], $_SGBD["username"], $_SGBD["password"]);
}
catch(PDOException $e){
	die("Erreur : ".$e->getMessage());
}

// Enregistrement du temps de réponse pour tout le questionnaire
if ($_SESSION['first_question'] == 1) {
	date_default_timezone_set('Europe/Paris');
	$Temps_debut = new DateTime();
	$_SESSION['Temps_debut'] = $Temps_debut;
}

// Variable jauge
$id_personne = $_SESSION['id_utilisateur'];
$nb_aleatoire = $_SESSION['nb_aleatoire'];
$id_questionnaire = $_SESSION['id_questionnaire'];
$nb_insertion_jauge = $_SESSION['nb_insertion_jauge'];

// Gestion de la $jauge
$jauge = $_SESSION['jauge'];
$affichage_jauge = 0;


if($nb_aleatoire == 2){
	echo $jauge;
	$affichage_jauge = 1;

}

//Insertion dans table jeu si utilisateur aura la jauge ou non

if($nb_insertion_jauge == 1){

	$sql = $bdd->prepare('UPDATE jeux SET jauge= :affichage WHERE id_questionnaire="'.$id_questionnaire.'"');
	$sql->execute(array('affichage' => $affichage_jauge));
	$sql->closeCursor();
	$_SESSION['nb_insertion_jauge'] = 2;

}

// Fonction qui détermine le chemin que va suivre l'utilisateur
function determi_chemin($id_personne){

	require("config.php");

	try{
	$bdd = new PDO($_SGBD["nsd"], $_SGBD["username"], $_SGBD["password"]);
	}
	catch(PDOException $e){
		die("Erreur : ".$e->getMessage());
	}

	$max_question_difficile = $bdd->prepare('SELECT id_question FROM question_difficile WHERE id_question IN (SELECT MAX(id_question) FROM question_difficile)');
  $max_question_difficile -> execute();
	$valeur_difficile = $max_question_difficile->fetch();
	//echo $valeur_difficile['id_question'];

	$max_question_facile = $bdd->prepare('SELECT id_question FROM question_facile WHERE id_question IN (SELECT MAX(id_question) FROM question_facile)');
  $max_question_facile -> execute();
	$valeur_facile = $max_question_facile->fetch();
	//echo $valeur_facile['id_question'];

	// Chemin question difficile
		if($id_personne % 4 == 0){
			$compteur = 0;
			$num_question = 1;

			while ($num_question <= $valeur_difficile['id_question']) {

				$chemin[$compteur] = $num_question;
				$compteur++;
				$num_question = $num_question + 2;
			}

		return $chemin;

		}

// Chemin question facile
	if($id_personne % 4 == 1){
		$compteur = 0;
		$num_question = 2;

		while ($num_question <= $valeur_facile['id_question']) {

			$chemin[$compteur] = $num_question;
			$compteur++;
			$num_question = $num_question + 2;
	}

	return $chemin;

	}

	// Chemin question facile-difficile
		if($id_personne % 4 == 2){
			$compteur = 0;
			$num_question = 2;

			while ($num_question <= $valeur_difficile['id_question']) {

				$chemin[$compteur] = $num_question;
				$compteur++;
				$num_question = $num_question + 2;

				if ($num_question > $valeur_facile['id_question'] / 2 && $num_question % 2 == 0) {
					$num_question = $num_question - 1;
				}
			}

		return $chemin;

		}

		// Chemin question difficile-facile
			if($id_personne % 4 == 3){
				$compteur = 0;
				$num_question = 1;

				while ($num_question <= $valeur_facile['id_question']) {

					$chemin[$compteur] = $num_question;
					$compteur++;
					$num_question = $num_question + 2;

					if ($num_question > $valeur_facile['id_question'] / 2 && $num_question % 2 == 1) {
						$num_question = $num_question + 1;
					}
				}

			return $chemin;

			}
}


 ?>

<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title> Test </title>
		<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
		<link rel="stylesheet" href="https://code.getmdl.io/1.3.0/material.blue_grey-blue.min.css" />
		<script defer src="https://code.getmdl.io/1.3.0/material.min.js"></script>
		<link rel="stylesheet" href="//apps.bdimg.com/libs/jqueryui/1.10.4/css/jquery-ui.min.css">
		<script src="//apps.bdimg.com/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="//apps.bdimg.com/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
		<Script src = "js/jquery-3.3.1.js"> </script>
		<Script src = "js/js.js"> </script>
		<link rel="stylesheet" type="text/css" href="style/styles.css"/>
	</head>

	<body>

		<audio autoplay="autoplay" loop="loop">
			<source src="son/tictac.mp3" type="audio/mp3" />
		</audio>
		<?php

		//On trouve le chemin de l'utilisateur selon son id
		$chemin = determi_chemin($id_personne);

		//On recupere la i=1 depuis question_debut.php
		//$_SESSION['i'] = 1;

		if ($_SESSION['first_question'] == 1) {
			$i = 0;
			$_SESSION['i'] = $i;
			// Enregistrement temps pour la première question
			$_SESSION['Temps_rep_prec'] = $_SESSION['Temps_debut'];

		}

		else if ($_SESSION['first_question'] != 1) {
		$i= $_SESSION['i'];
		// Enregistrement temps réponse pour toutes les autres réponse autre que la 1
		$Temps_rep_actu = new DateTime();
		$Temps_entre_rep = date_diff($_SESSION['Temps_rep_prec'], $Temps_rep_actu);
		echo $Temps_entre_rep->format("L'utilisateur a pris %H heures %i minutes %s secondes pour répondre à la question d'avant");
		$_SESSION['Temps_rep_prec'] = $Temps_rep_actu;
		}

		$num_question = $chemin[$i];
		//echo $_SESSION["newsession"];
	  $_SESSION['num_question'] = $num_question;
		$_SESSION['tab_question'] = $chemin;

		// Requêtes préparées permettant de pointer vers la table question et les choix correspondantes

		$question = $bdd->prepare('Select * from question where id_question = :id_question');
		$question ->bindValue(':id_question', $num_question, PDO::PARAM_INT);
		$question->execute();
		$intitule_question = $question->fetch();

		$choix = $bdd->prepare('Select * from choix_question where id_choix = :id_question');
		$choix ->bindValue(':id_question', $num_question, PDO::PARAM_INT);
		$choix->execute();
		$intitule_choix = $choix->fetch();

		?>

		<!-- Affichage sur l'écran de la question et des choix -->
		<div id="header">

			<h1> <?php echo 'Question'," ", $i+1; ?> </h1>
		</div>
		<div id = "question">
			<div class="image">
				<img src="images/Q<?php echo secu::affichage($num_question); ?>.png" height="318" width="449" >
			</div>
			<form name="formulaire-question" action="Reponse.php" method="post">
				<div class="list_question">
					<div class="question"> <br>
						<p> <?php echo secu::affichage($intitule_question['texte']); ?> </p><br>
					</div>
		<!-- Bouton permettant de rediriger vers Reponse.php pour envoyer les reponses à la bdd -->
					<div class="reponse">
						<span class="mdl-list__item-secondary-action">
							<p>
								<label class="demo-list-radio mdl-radio mdl-js-radio mdl-js-ripple-effect" for="list-option-choix1">
									<input type="radio" name="choix" id="list-option-choix1" class="mdl-radio__button" checked required value="<?php echo $intitule_choix['choix1']; ?>"> <?php echo $intitule_choix['choix1']; ?> <br>
								</label>
							</p>
							<p>
								<label class="demo-list-radio mdl-radio mdl-js-radio mdl-js-ripple-effect" for="list-option-choix2">
									<input type="radio" name="choix" id="list-option-choix2" class="mdl-radio__button" required value="<?php echo $intitule_choix['choix2']; ?>"> <?php echo $intitule_choix['choix2']; ?> <br>
								</label>
							</p>
						</span>
					</div>
				</div>

				<div class="outer-div">
					<div class="inner-div">
						<a id="controlButton"  class="progress-button"></a>
					</div>
				</div>
				<!-- <input type="hidden" name="id_qs" value="<?php $i ?>"-->
				<div class="button">
					<input type="submit"  class="mdl-button mdl-button--raised mdl-button--colored command increment" onclick="javascript:location.href='Questions.php'" value="Question suivante">
				</div>
			</form>
		</div>

		<?php


		// Fin des requêtes faisant appel à la bdd
		$question->closeCursor();
		$choix->closeCursor();

		 ?>

	</body>
</html>
