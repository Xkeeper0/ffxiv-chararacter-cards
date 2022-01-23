<?php

	require 'vendor/autoload.php';
	require 'functions.php';
	require 'charsheet.php';

	throw_warnings(true);
	$api = new \XIVAPI\XIVAPI();

	$char	= $api->character->get(14531375);
	file_put_contents("14531375.txt", serialize($char));
	

//	$char	= unserialize(file_get_contents("cached.txt"));
	$x = new CharacterSheet($char);

	$x->generate();
	$x->save("sheet.png");

	die();
/*
	$api = new \XIVAPI\XIVAPI();

	$char	= $api->character->get(41209252);
	file_put_contents("cached.txt", serialize($char));
*/

	$char	= unserialize(file_get_contents("cached.txt"));

	$jobs	= getCharacterJobs($char);
	$roles	= getOrderedCharacterJobs($jobs);

	print_r($roles);

	die();
	print_r($char);

	var_dump($char->Character->Name);
	var_dump($char->Character->DC);
	var_dump($char->Character->Server);

	$jobs	= [];
	foreach ($char->Character->ClassJobs as $job) {
		printf("%2d, %2d,  // %s\n", $job->ClassID, $job->JobID, $job->Name);
		/*
		printf("%3d/%3d -> %-25s : %2d\n", $job->ClassID, $job->JobID, $job->UnlockedState->Name, $job->Level);

		if ($jobs[$job->ClassID]) {
			print "uhhh dup id... ". $job->ClassID .", ". $job->UnlockedState->Name ." is already used by ". $jobs[$job->ClassID] ."\n";
		}
		$jobs[$job->ClassID]	= $job->UnlockedState->Name;
		*/
	}

	ksort($jobs);
	print_r($jobs);