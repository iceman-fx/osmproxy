<?php
function deleteOSMCacheFiles($dir, $patterns = "*", int $timeout = 86400)
{
    foreach (glob($dir . "*" . "{{$patterns}}", GLOB_BRACE) as $f) {
        if (file_exists($f) && is_writable($f) && @filemtime($f) < (time() - $timeout))
            @unlink($f);
    }
}
$addon = rex_addon::get('osmproxy');
rex_dir::create($addon->getCachePath());

if (rex_get('osmtype', 'string')) {
    // Clear REDAXO OutputBuffers
    rex_response::cleanOutputBuffers();
    clearstatcache();
    if (!empty($_SERVER['HTTP_REFERER']) && parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) != $_SERVER['HTTP_HOST']) {
        die();
    }
    $type = $dir = $file = $server = $url = $x = $y = $z = $ch = $fp = $exp_gmt = $mod_gmt = '';
    $type = rex_escape(rex_get('osmtype', 'string'));
    $dir = $addon->getCachePath();
    $ttl = 86400;
    deleteOSMCacheFiles($dir,'*',$ttl);
    $x = rex_get('x', 'int');
    $y = rex_get('y', 'int');
    $z = rex_get('z', 'int');
    $file = $dir . "${z}_${x}_$y.png";
    if (!is_file($file) || filemtime($file) < time() - ($ttl * 30) and $type != '') {
        $server = array();
        switch ($type) {
            case "carto":
                $server[] = 'a.basemaps.cartocdn.com/rastertiles/voyager/';
                $server[] = 'b.basemaps.cartocdn.com/rastertiles/voyager/';
                $server[] = 'c.basemaps.cartocdn.com/rastertiles/voyager/';
                break;
            case "carto_light":
                $server[] = 'a.basemaps.cartocdn.com/rastertiles/light_all/';
                $server[] = 'b.basemaps.cartocdn.com/rastertiles/light_all/';
                $server[] = 'c.basemaps.cartocdn.com/rastertiles/light_all/';
                $server[] = 'd.basemaps.cartocdn.com/rastertiles/light_all/';
                break;
            case "carto_dark":
                $server[] = 'a.basemaps.cartocdn.com/rastertiles/dark_all/';
                $server[] = 'b.basemaps.cartocdn.com/rastertiles/dark_all/';
                $server[] = 'c.basemaps.cartocdn.com/rastertiles/dark_all/';
                $server[] = 'd.basemaps.cartocdn.com/rastertiles/dark_all/';
                break;           
               
            case "german":
                $server[] = 'a.tile.openstreetmap.de/';
                $server[] = 'b.tile.openstreetmap.de/';
                $server[] = 'c.tile.openstreetmap.de/';
                break;
            default:
                $server[] = 'a.tile.openstreetmap.org/';
                $server[] = 'b.tile.openstreetmap.org/';
                $server[] = 'c.tile.openstreetmap.org/';
        }
        $url = 'https://' . $server[array_rand($server)];
        $url .= $z . "/" . $x . "/" . $y . ".png";
        $ch = curl_init($url);
        $fp = fopen($file, "w");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		
		//disable SSL-Check
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_exec($ch);
        curl_close($ch);
        fflush($fp);
        fclose($fp);
        chmod($file, 0755);
    }
    $exp_gmt = gmdate("D, d M Y H:i:s", time() + $ttl * 60) . " GMT";
    $mod_gmt = gmdate("D, d M Y H:i:s", filemtime($file)) . " GMT";
    header("Expires: " . $exp_gmt);
    header("Last-Modified: " . $mod_gmt);
    header("Cache-Control: public, max-age=" . $ttl * 60);
    header('Content-Type: image/png');
    readfile($file);
    die();
}
