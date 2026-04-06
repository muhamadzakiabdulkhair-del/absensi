<?php
session_start();

// Karakter yang dipakai (tanpa i,I,l,L,U,u,v,V)
$chars = 'abcdefghjkmnopqrstwxyzABCDEFGHJKMNOPQRSTWXYZ0123456789!@#$%^&*()?>\"\':{}\[\]\\|+-_~';

$captcha = '';
for ($i = 0; $i < 6; $i++) {
    $captcha .= $chars[random_int(0, strlen($chars) - 1)];
}

$_SESSION['captcha'] = $captcha;

// Buat gambar
$width  = 160;
$height = 50;
$image  = imagecreatetruecolor($width, $height);

// Warna background noise
$bg = imagecolorallocate($image, 240, 240, 240);
imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Tambah noise garis
for ($i = 0; $i < 6; $i++) {
    $color = imagecolorallocate($image, rand(150,200), rand(150,200), rand(150,200));
    imageline($image, rand(0,$width), rand(0,$height), rand(0,$width), rand(0,$height), $color);
}

// Tambah noise titik
for ($i = 0; $i < 80; $i++) {
    $color = imagecolorallocate($image, rand(150,220), rand(150,220), rand(150,220));
    imagesetpixel($image, rand(0,$width), rand(0,$height), $color);
}

// Tulis karakter satu per satu dengan rotasi & warna berbeda
$x = 10;
foreach (str_split($captcha) as $char) {
    $color = imagecolorallocate($image, rand(20,120), rand(20,120), rand(20,150));
    $size  = rand(16, 22);
    $angle = rand(-20, 20);
    $y     = rand(30, 40);

    // Pakai font bawaan GD kalau tidak ada TTF
    $font = 5;
    imagestring($image, $font, $x, $y - 15, $char, $color);
    $x += rand(22, 28);
}

// Output sebagai PNG
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store');
imagepng($image);
imagedestroy($image);
?>