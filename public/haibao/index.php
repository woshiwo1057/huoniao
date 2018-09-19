<?php
/*include 'phpqrcode.php';
$value = 'http://www.cnblogs.com/txw1958/'; //二维码内容
$errorCorrectionLevel = 'L';//容错级别
$matrixPointSize = 16;//生成图片大小*/
//生成二维码图片
/*QRcode::png($value, 'qrcode.png', $errorCorrectionLevel, $matrixPointSize, 2);*/
//$logo = 'logo.png';//准备好的logo图片
//$QR = 'qrcode.png';//已经生成的原始二维码图
$id = 2;
$beijing = './bj1.jpg';//背景图
//$QR = 'http://huoniao.net/haibao/bj1.jpg';//背景图
$headimg = './touxiang.png';//头像
/*if(is_file('logo_'.$id.'.png')){
    $a=time()-filemtime('logo_'.$id.'.png');
    if($a>20*24*60*60){}else{
        echo '/haibao/'.'logo_'.$id.'.png';
        exit;
    }
}*/
if ($beijing !== FALSE) {
    $filename = $beijing;
    $width=700;
    $height=600;
    //获取原图像$filename的宽度$width_orig和高度$height_orig
    /*  list($width_orig,$height_orig) = getimagesize($filename);
      //根据参数$width和$height值，换算出等比例缩放的高度和宽度
      if ($width && ($width_orig<$height_orig)){
          $width = ($height/$height_orig)*$width_orig;
      }else{
          $height = ($width / $width_orig)*$height_orig;
      }*/
    //将原图缩放到这个新创建的图片资源中
    //$image_p = imagecreatetruecolor($width, $height);
    //获取原图的图像资源
    //$image = imagecreatefromjpeg($filename);
    //使用imagecopyresampled()函数进行缩放设置
    //imagecopyresampled($image_p,$image,0,0,0,0,$width,$height,$width_orig,$height_orig);

    $QR = imagecreatefromstring(file_get_contents($beijing));
    //$logo = $image_p;
    $headimg = imagecreatefromstring(file_get_contents($headimg));
    $logo_width = 390;//logo图片宽度
    $logo_height = 360;//logo图片高度
    $head_width = imagesx($headimg);
    $head_height = imagesy($headimg);
    //重新组合图片并调整大小
    imagecopyresampled($QR, $headimg, 0, 50, 0, 0, $head_width,$head_height, $logo_width, $logo_height);
    //imagecopyresampled($QR, $headimg, $QR_width-115,25, 0,0, 100,100, $head_width,$head_height);

    $background_color = imagecolorallocate($QR, 255, 255, 255);
    $text_color = imagecolorallocate($QR, 233, 16, 91);
    //imagestring($QR, 30, 100, 100,  "A Simple Text String", $text_color);
    $text = 'dsadasdas';
    //$font = "D:/PHPTutorial/WWW/wangka/haibao/kt.ttf";
    $font = "./kt.ttf";
    imagettftext($QR, 30, 0, 180, 176, $text_color, $font, $text);

}
//输出图片
header("content-type:image/png;chart");
//imagejpeg($canvas);
imagepng($QR, 'logo_'.$id.'.png');
imagejpeg ($QR);
imagedestroy($QR);
//echo '/haibao/'.'logo_'.$id.'.png';
exit;