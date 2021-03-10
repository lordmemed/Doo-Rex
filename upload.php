<?php

if(isset($_FILES['cover'])) 
{
	$errors = [];
	$maxsize= 2097152;
	$acceptable = [
		'image/jpeg',
		'image/jpg',
		'image/gif',
		'image/png'
	];

	if(($_FILES['uploaded_file']['size'] >= $maxsize) || ($_FILES["uploaded_file"]["size"] == 0)) 
	{
		$errors[] = 'File too large. File must be less than 2 megabytes.';
	}

	if((!in_array($_FILES['uploaded_file']['type'], $acceptable)) && (!empty($_FILES["uploaded_file"]["type"]))) {
	$errors[] = 'Invalid file type. Only PDF, JPG, GIF and PNG types are accepted.';
	}

	if(count($errors) === 0) {
	move_uploaded_file($_FILES['uploaded_file']['tmpname'], '/store/to/location.file');
	} else {
	foreach($errors as $error) {
	echo '<script>alert("'.$error.'");</script>';
	}

	die(); //Ensure no more processing is done
	}
}