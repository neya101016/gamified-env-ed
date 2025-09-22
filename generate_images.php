<?php
// Simple script to create placeholder badge images without database connection

// Create directories if they don't exist
$badge_dir = 'assets/img/badges/';
if (!file_exists($badge_dir)) {
    mkdir($badge_dir, 0755, true);
}

// Ensure assets/img exists
$img_dir = 'assets/img/';
if (!file_exists($img_dir)) {
    mkdir($img_dir, 0755, true);
}

// Badge colors
$badge_images = [
    'eco-novice.png' => '#4CAF50', // Green
    'green-thumb.png' => '#8BC34A', // Light Green
    'water-warrior.png' => '#03A9F4', // Light Blue
    'energy-expert.png' => '#FFC107', // Amber
    'recycling-champion.png' => '#009688', // Teal
    'quiz-master.png' => '#3F51B5', // Indigo
    'eco-scholar.png' => '#9C27B0', // Purple
    'community-leader.png' => '#E91E63', // Pink
    'earth-guardian.png' => '#FF5722', // Deep Orange
    'default-badge.png' => '#607D8B' // Blue Grey
];

// Create badge images
foreach ($badge_images as $filename => $color) {
    $filepath = $badge_dir . $filename;
    
    if (!file_exists($filepath)) {
        // Create a 200x200 image
        $image = imagecreatetruecolor(200, 200);
        
        // Enable alpha blending
        imagealphablending($image, true);
        imagesavealpha($image, true);
        
        // Convert hex color to RGB
        $color = ltrim($color, '#');
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        
        // Fill background with transparent color
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        
        // Create circle
        $circle_color = imagecolorallocate($image, $r, $g, $b);
        imagefilledellipse($image, 100, 100, 180, 180, $circle_color);
        
        // Add a white border
        $white = imagecolorallocate($image, 255, 255, 255);
        imageellipse($image, 100, 100, 180, 180, $white);
        
        // Add initial letter (using basic text function)
        $initial = strtoupper(substr(str_replace(['default-', '-'], '', $filename), 0, 1));
        imagestring($image, 5, 90, 85, $initial, $white);
        
        // Save the image
        imagepng($image, $filepath);
        imagedestroy($image);
        
        echo "Created badge image: $filename<br>";
    } else {
        echo "Badge image already exists: $filename<br>";
    }
}

// Create default avatar
$avatar_file = $img_dir . 'default-avatar.png';
if (!file_exists($avatar_file)) {
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
    imagepng($image, $avatar_file);
    imagedestroy($image);
    
    echo "<br>Created default avatar image: default-avatar.png";
} else {
    echo "<br>Default avatar already exists: default-avatar.png";
}

echo "<br><br><strong>All image generation completed!</strong>";
?>