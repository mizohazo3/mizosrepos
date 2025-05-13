<?php
// Create a placeholder image
$width = 250;
$height = 250;
$image = imagecreatetruecolor($width, $height);

// Set the background color (light gray)
$bg = imagecolorallocate($image, 240, 240, 240);
imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Draw a border
$border = imagecolorallocate($image, 200, 200, 200);
imagerectangle($image, 0, 0, $width - 1, $height - 1, $border);

// Add text
$text = "Image Not Available";
$font_color = imagecolorallocate($image, 120, 120, 120);

// Get text dimensions
$font = 4; // Built-in font
$text_width = imagefontwidth($font) * strlen($text);
$text_height = imagefontheight($font);

// Calculate position to center the text
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

// Add the text to the image
imagestring($image, $font, $x, $y, $text, $font_color);

// Save the image to a file
imagepng($image, 'placeholder.png');
imagedestroy($image);

echo "Placeholder image created successfully.";
?> 