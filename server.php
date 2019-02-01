<!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title>Page Title</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!--<link rel="stylesheet" type="text/css" media="screen" href="main.css" />
		<script src="main.js"></script>-->
	</head>

	<body>

		<form action='server.php' method='POST' enctype='multipart/form-data'>
			<input type='file' name='image' placeholder='Upload an image'>
			<input type='submit' name='submit'>
		</form>




<?php

function console( $data ) {
    $output = $data;
    if ( is_array( $output ) )
				$output = implode( ',', $output);
				 
    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
}


function dominantColors( $img){
	$fp = fopen($img, 'rb');
	console($fp);
	$size = filesize($img);
	$data = fread($fp, $size);
	fclose($fp);
	$encoded = bin2hex($data);
	console($encoded);
	echo $encoded;


}


$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["image"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) {
		$check = getimagesize($_FILES["image"]["tmp_name"]);
		console($check);
		console($_FILES["image"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
				$uploadOk = 1;
				dominantColors($_FILES["image"]["tmp_name"]);
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
		}
}

	
?>

	</body>

</html>