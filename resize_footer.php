<?php
// resize_footer.php
$source = 'c:/wamp64/www/pao/img/pie_pagina.png';
$dest = 'c:/wamp64/www/pao/img/pie_pagina_opt.png';

if (!file_exists($source)) {
    die("Error: Source file not found.\n");
}

// Get dimensions
list($width, $height) = getimagesize($source);
$new_width = 1500; // Sufficient for PDF footer
$new_height = ($height / $width) * $new_width;

// Resample
$image_p = imagecreatetruecolor($new_width, $new_height);
// Preserve transparency for PNG
imagealphablending($image_p, false);
imagesavealpha($image_p, true);

$image = imagecreatefrompng($source);
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

// Save optimized
if (imagepng($image_p, $dest, 6)) { // Quality 6 (0-9)
    echo "Success: Created $dest\n";
    echo "Original size: " . filesize($source) . " bytes\n";
    echo "New size: " . filesize($dest) . " bytes\n";
} else {
    echo "Error: Could not save image.\n";
}

imagedestroy($image_p);
imagedestroy($image);
?>
