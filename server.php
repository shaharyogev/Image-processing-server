<!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title>Colors From Image</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" type="text/css" media="screen" href="main.css" />
	</head>

	<body>
	<section class='inputSection'>
	<h1>Pleas select a image file:</h1>
		<form action='server.php' method='POST' enctype='multipart/form-data'>
			<span>Colors to show:</span>
			<input type='number' name='number' placeholder='5' value='5'>
			<br>
			<input type='file' name='image' placeholder='Upload an image'>
			<input type='submit' name='submit'>
		</form>
	</section>


<?php
//
// Log message to the client's browser console by inserting JS
/*function console( $data ) {
    $output = $data;
    if ( is_array( $output ) ){
				$output = implode( ',', $output);
		}
    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
}
*/

//
// Parse the BMP format and get the dominant colors
function colorsInBmp($p_sFile, $colors_num) { 
	$colors = array();

	
	//Load the image into a string 
	$file = fopen($p_sFile,"rb"); 
	$read = fread($file,10); 

	// While the file is open concatenate the next kb and keep it until it ends
	while(!feof($file)&&($read<>"")) {
		$read .= fread($file,1024); 
	}
	
	
	//Unpack the read string to hexadecimal string for easy manipulation and reading;
	$temp = unpack("H*",$read); 
	//print_r($temp);
	$hex = $temp[1]; 

	// BMP Header is the first 108 characters
	$header = substr($hex,0,108); 

	
	//Process the header 
	//Structure: http://www.fastgraph.com/help/bmp_header_format.html 
	if (substr($header,0,4) === "424d"){  //BMP header
			//Cut it in parts of 2 bytes 
			$header_parts = str_split($header,2); 
			
			//Get the width 4 bytes 
			$width = hexdec($header_parts[19].$header_parts[18]); 
			
			//Get the height 4 bytes 
			$height = hexdec($header_parts[23].$header_parts[22]); 
			
			//Unset the header params 
			unset($header_parts); 
	} 
	
	//Define starting X and Y 
	$x = 0; 
	$y = 1; 
	
	
	//Grab the body from the image 
	$body = substr($hex,108); 

	//Calculate if padding at the end-line is needed 
	//Divided by two to keep overview. 
	//1 byte = 2 HEX-chars 
	$body_size = (strlen($body)/2); 
	$header_size = ($width*$height); 

	//Use end-line padding? Only when needed 
	$use_padding = ($body_size>($header_size*3)+4); 
	
	//Using a for-loop with index-calculation instated of str_split to avoid large memory consumption 
	//Calculate the next DWORD-position in the body 
	for ($i=0;$i<$body_size;$i+=3) { 
		//Calculate line-ending and padding 

		if ($x>=$width) { 
			//If padding needed, ignore image-padding 
			//Shift i to the ending of the current 32-bit-block 
			if ($use_padding){
				$i += $width%4; 
			}

			//Reset horizontal position 
			$x = 0; 
			
			//Raise the height-position (bottom-up) 
			$y++; 
			
			//Reached the image-height? Break the for-loop 
			if ($y>$height) 
				break; 
		} 
			
			//Calculation of the RGB-pixel (defined as BGR in image-data hexadecimal string) 
			//Define $i_pos as absolute position in the body 
			$i_pos = $i*2; 
			$colors[] = $body[$i_pos+4].$body[$i_pos+5].$body[$i_pos+2].$body[$i_pos+3].$body[$i_pos].$body[$i_pos+1];

			//Raise the horizontal position 
			$x++; 
	} 

	//Unset the body / free the memory 
	unset($body); 
	
	//Count the number for each color
	$colors_array = array_count_values($colors);
	
	//Sort the array higher first
	arsort($colors_array);

	//Slice the top 5 dominant colors
	$result = array_slice($colors_array, 0,$colors_num,true);

	//Echo to the screen
	echo '<section class="colorsSection">';
	foreach($result as $key => $val){
		$temp_key = ''.hexdec(substr($key,0,2)).','.hexdec(substr($key,2,2)).','.hexdec(substr($key,4,2)).'';
		echo '<div class=" colorRow flex-row"><h3 class="colorsText"> Color: ' .$temp_key. ' Showed: '.$val.'</h3><div class="colorBox" style="background: rgb('.$temp_key.')"></div></div>';
	}		
	echo '</section>';
	return $result;
} 

//Will convert images to bmp with the GD library 

function convertImageToBmp($img){

	//Create new image from string generated from the file
	$img_data = file_get_contents($img);
	$im = imagecreatefromstring($img_data);
	unset($img_data);
	imagebmp($im,'uploads/tempTest.bmp');
	imageDestroy($im);
	unset($im);
	return('uploads/tempTest.bmp');
}

function is_image_allowed($mime) {
	$allowed_mime = array('image/png', 'image/jpg', 'image/jpeg', 'image/bmp');
	if(!in_array($mime, $allowed_mime)) {
		return false;
	}else{
		return true;
	}
}

// Check if image file is a actual image or fake image JPEG, PNG, GIF, BMP, WBMP.
if(isset($_POST["submit"])) {

	//300 seconds = 5 minutes
	ini_set('max_execution_time', 300); 
	//No memory limit
	ini_set('memory_limit', '2048M');

	//Uploaded file target directory
	$target_dir = "uploads/";
	if(!file_exists('uploads/')){
		mkdir('uploads/', 0777, true);
	}

	//The final name and path for the new file
	$colors_num = $_POST["number"];
	$target_file = $target_dir . basename($_FILES["image"]["name"]);
	$image_type = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
	$image_temp = $_FILES["image"]["tmp_name"];

	//Get the size and test the image type base on the data stored in the image
	$check = getimagesize($image_temp);

	if($check !== false) {
		if(is_image_allowed($check["mime"])){
			move_uploaded_file($image_temp, $target_file);
			echo "<h2 class='inputSection'>File is an " . $image_type . " - image.</h2>";

			if( $check["mime"] === 'image/bmp'){
				colorsInBmp($target_file, $colors_num);
			} else {
				// Convert to BMP if necessary
				$temp_file = convertImageToBmp($target_file);
				colorsInBmp($temp_file, $colors_num);
				unlink($temp_file);
			}

		echo '<div class="flex-row"><img class="imageCenter" src="' .$target_file. '" /></div>';

	}else{
		echo "<h2 class='inputSection'>Supported files: JPEG, JPG, PNG, BMP.</h2>";
	}
	} else{
		echo "<h2 class='inputSection'>File is not an image.</h2>";
	}
}

?>

</body>

</html>