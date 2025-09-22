<?php
// Generate a default avatar image if it doesn't exist

$filename = 'default-avatar.png';
$directory = '../assets/img/';
$filepath = $directory . $filename;

// Create the directory if it doesn't exist
if (!file_exists($directory)) {
    mkdir($directory, 0755, true);
}

if (!file_exists($filepath)) {
    // Create a 200x200 image
    $image = imagecreatetruecolor(200, 200);
    
    // Choose a blue color
    $blue = imagecolorallocate($image, 51, 122, 183);
    
    // Fill background with blue
    imagefill($image, 0, 0, $blue);
    
    // Draw a circle for the head
    $white = imagecolorallocate($image, 240, 240, 240);
    imagefilledellipse($image, 100, 80, 120, 120, $white);
    
    // Draw the body
    imagefilledarc($image, 100, 280, 180, 280, 0, 180, $white, IMG_ARC_PIE);
    
    // Save the image
    imagepng($image, $filepath);
    imagedestroy($image);
    
    echo "Created default avatar image: $filename";
} else {
    echo "Default avatar image already exists";
}
?>