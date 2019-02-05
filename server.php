<!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title>Page Title</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" type="text/css" media="screen" href="main.css" />
		<!--<script src="main.js"></script>-->
	</head>

	<body>

		<form action='server.php' method='POST' enctype='multipart/form-data'>
			<input type='file' name='image' placeholder='Upload an image'>
			<input type='submit' name='submit'>
		</form>

		

<?php
//console log debug tool
function console( $data ) {
    $output = $data;
    if ( is_array( $output ) )
				$output = implode( ',', $output);
				 
    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
}

$colorsToShowNum = 5;
$colors = array();

function colorsInBmp($p_sFile) { 
	//Load the image into a string 
	$file = fopen($p_sFile,"rb"); 
	$read = fread($file,10); 

	// While the file is open concatenate the next kb and keep it until it ends
	while(!feof($file)&&($read<>"")) 
			$read .= fread($file,1024); 
	
	//Unpack the read string to hexadecimal string for easy manipulation and reading;
	$temp = unpack("H*",$read); 
	$hex = $temp[1]; 
	// BMP Header is the first 108 characters
	$header = substr($hex,0,108); 
	
	
	//Process the header 
	//Structure: http://www.fastgraph.com/help/bmp_header_format.html 
	
	if (substr($header,0,4) == "424d"){  //BMP header
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
	
	// Create newimage 
	//$image = imagecreatetruecolor($width,$height); // GD library cant be used
	
	//Grab the body from the image 
	$body = substr($hex,108); 
	//Calculate if padding at the end-line is needed 
	//Divided by two to keep overview. 
	//1 byte = 2 HEX-chars 
	$body_size = (strlen($body)/2); 
	$header_size = ($width*$height); 

	//Use end-line padding? Only when needed 
	$usePadding = ($body_size>($header_size*3)+4); 
	
	//Using a for-loop with index-calculation instated of str_split to avoid large memory consumption 
	//Calculate the next DWORD-position in the body 
	for ($i=0;$i<$body_size;$i+=3) { 
		//Calculate line-ending and padding 
		if ($x>=$width) { 
			//If padding needed, ignore image-padding 
			//Shift i to the ending of the current 32-bit-block 
			if ($usePadding) 
				$i += $width%4; 
			
			//Reset horizontal position 
			$x = 0; 
			
			//Raise the height-position (bottom-up) 
			$y++; 
			
			//Reached the image-height? Break the for-loop 
			if ($y>$height) 
				break; 
		} 
							
			//Calculation of the RGB-pixel (defined as BGR in image-data) 
			//Define $i_pos as absolute position in the body 
			$i_pos = $i*2; 
			$r = hexdec($body[$i_pos+4].$body[$i_pos+5]); 
			$g = hexdec($body[$i_pos+2].$body[$i_pos+3]); 
			$b = hexdec($body[$i_pos].$body[$i_pos+1]); 
			
			// Combine the RGB to a string
			$tempString = ''.$r.','.$g.','.$b.'';
			
			// Insert the strings to array
			$colors[] = $tempString;
			
			//Calculate and draw the pixel 
			//$color = imagecolorallocate($image,$r,$g,$b); // GD library cant be used
			//imagesetpixel($image,$x,$height-$y,$color); // GD library cant be used
			
			//Raise the horizontal position 
			$x++; 
	} 

	//Unset the body / free the memory 
	unset($body); 
	
	//Count the number for each color
	$allTheColors = array_count_values($colors);
	
	//Sort the array higher first
	arsort($allTheColors);
	
	//Slice the top 5 dominant colors
	$result = array_slice($allTheColors, 0,5,true);

	//Echo to the screen
	echo '<section class="colorsSection">';
	foreach($result as $key => $val){
		echo '<div class="flex-row"><h3 class="colorsText"> Color: ' .$key. ' Showed: '.$val.'</h3><div class="colorBox" style="background: rgb('.$key.')"></div></div>';
	}		
	echo '</section>';
	return $result;
} 

//Will convert images to bmp with the GD library 

function convertImageToBmp($img){

	//Create new image from string generated from the file
	$image = imagecreatefromstring(file_get_contents($img));

	//Save the new temp file
	$temp = imagebmp($image,'tempTest.bmp');

	//Clear the memory
	imageDestroy($image);

	//Call the colorsInBmp and pass the new file
	colorsInBmp('tempTest.bmp');
}




// Check if image file is a actual image or fake image JPEG, PNG, GIF, BMP, WBMP.
if(isset($_POST["submit"])) {
	//Uploaded file target directory
	$target_dir = "uploads/";

	//Will create directory if necessary
	if(!file_exists('uploads/'))
		mkdir('uploads/', 0777, true);
	
	//The final name and path for the new file
	$target_file = $target_dir . basename($_FILES["image"]["name"]);

	//Image Type for later use 
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

	//Use the temp file before saving
	$imageTemp = $_FILES["image"]["tmp_name"];
	console($target_file);

	//Get the size and test the image type base on the data stored in the image
	$check = getimagesize($imageTemp);
	console($check);
	
	if($check !== false) {
		echo "<h2>File is an image - " . $imageFileType . ".</h2>";
		$uploadOk = 1;
		if( $check["mime"] === 'image/bmp'){
			colorsInBmp($imageTemp);
		} else {
			convertImageToBmp($imageTemp);
		}

		move_uploaded_file($imageTemp, $target_file);
		echo '<img src="' .$target_file. '" />';

	} else{
			echo "File is not an image.";
			$uploadOk = 0;
	}
}

	
?>

</body>

</html>