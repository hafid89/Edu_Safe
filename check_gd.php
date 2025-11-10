<?php
// check_gd.php
echo "<h3>🔍 Checking GD Library</h3>";

if (extension_loaded('gd')) {
    echo "✅ GD Library is LOADED<br>";
    
    $gd_info = gd_info();
    echo "<strong>GD Version:</strong> " . $gd_info['GD Version'] . "<br>";
    echo "<strong>JPEG Support:</strong> " . ($gd_info['JPEG Support'] ? '✅ Yes' : '❌ No') . "<br>";
    echo "<strong>PNG Support:</strong> " . ($gd_info['PNG Support'] ? '✅ Yes' : '❌ No') . "<br>";
    
} else {
    echo "❌ GD Library is NOT loaded<br>";
    echo "Please enable GD extension in php.ini";
}
?>