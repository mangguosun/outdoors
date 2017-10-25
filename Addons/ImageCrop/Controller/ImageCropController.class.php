<?php
namespace Addons\ImageCrop\Controller;
use Home\Controller\AddonsController;
use Think\ImageCrop;

class ImageCropController extends AddonsController{
    public function index(){
 		$this->display();
    }

    public function imagetest(){
    	$data = $this->receive($_FILES['upload'],'./Uploads/xuping/');
    	echo json_encode($data);
    }
 	private function receive($file,$path){
        //存储相对地址
        $path = trim($path,'/').'/';
        //存储绝对地址
        // $savepath = rtrim(dirname(__FILE__),'/').'/'.$path;
        $savepath = $path;

        //url
        //$rootUrl = 'http://'.$_SERVER['SERVER_NAME'].'/';

        //初始检测
        if($file['error'] > 0){  
           $data['status'] = 0;
           switch($file['error']){  
             case 1: $data['info'] = '文件大小超过服务器限制';  
                     break;  
             case 2: $data['info'] = '文件太大！';  
                     break;  
             case 3: $data['info'] = '文件只加载了一部分！';  
                     break;  
             case 4: $data['info'] = '文件加载失败！';  
                     break;  
           }  
           return $data;
        }  

        //大小检测
        if($file['size'] > 1024*1024){  
           $data['status'] = 0;
           $data['info'] = '文件过大！';  
           return $data;
        }  
        
        //类型检测
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        //$ext = substr(strrchr($file['name'],"."),1);

        $typeAllow = array('jpg', 'jpeg', 'gif', 'png');

        if( in_array($ext, $typeAllow) ){
            //严格检测
            $imginfo = getimagesize($file['tmp_name']);
            if(empty($imginfo) || ($ext == 'gif' && empty($imginfo['bits']))){
                $data['status'] = 0;
                $data['info'] = '非法图像文件！';  
                return $data;
            }
        }else{
           $data['status'] = 0;
           $data['info'] = '文件类型不符！只接受'.implode(',',$typeAllow).'类型图片！';
           return $data;
        }

        //存储
        //$time = uniqid('upload_');
          $time=date('Ymdhis');
        if(!is_dir($savepath)){
            if( !mkdir($savepath, 0777, true) ){
                $data['status'] = 0;
                $data['info'] = '上传目录不存在或不可写！请尝试手动创建:'.$savepath;
                return $data;
            }
        }else{
            if( !is_writable($savepath) ){
                $data['status'] = 0;
                $data['info'] = '上传目录不可写！:'.$savepath;
                return $data;
            }
        }
        $filename = $time .'.'. $ext;
        $upfile = $savepath . $filename;

        if(is_uploaded_file($file['tmp_name'])){  
            if(!move_uploaded_file($file['tmp_name'], $upfile)){  
                $data['status'] = 0;
                $data['info'] = '移动文件失败！';  
                return $data;
            }else{
                $data['status'] = 1;
                $data['info'] = '成功';  

                $arr = getimagesize( $upfile );
                $strarr = explode("\"",$arr[3]);//分析图片宽高

                $data['data'] = array(
                    'path'=>$path.$filename,
                    'name'=>$filename,
                    'width'=>$strarr[1],
                    'height'=>$strarr[3]
                    //'url'=>$rootUrl.$path.$filename
                );
              
                return $data;
            }  
        }else{  
            $data['status'] = 0;
            $data['info'] = '文件丢失或不存在';  
            return $data;
        }  
    }

    public function cutImage(){
		$filename = $_POST['name'];

		$file = './Uploads/xuping/'.$filename;
		$cutPicfolder = 'Uploads/xuping/';
		$cutPicPath = './'.$cutPicfolder;

		$urlPath = $this->get_current_url();
		$urlPath = rtrim($urlPath,'/').'/';

		$x1 = $_POST['offsetLeft'];
		$y1 = $_POST['offsetTop'];
		$width = $_POST['width'];
		$height = $_POST['height'];

		// $type = exif_imagetype($file);
		$type = end(explode('.', $file));

		$support_type=array('jpg', 'png' , 'gif','jpeg');

		if(!in_array($type, $support_type)) {
		    $data['status'] = 0;
		    $data['info'] =  "不支持的格式！";
		    echo json_encode($data);
		    exit;
		}else{
		    switch($type) {
		    case 'jpg' :
		        $image = imagecreatefromjpeg($file);
		        break;
		    case 'png' :
		        $image = imagecreatefrompng($file);
		        break;
		    case 'gif' :
		        $image = imagecreatefromgif($file);
		        break;
		    default:
		        $data['status'] = 0;
		        $data['info'] =  "不支持的格式！";

		        echo json_encode($data);
		        exit;
		    }

		    $copy = $this->PIPHP_ImageCrop($image, $x1, $y1, $width, $height);

		    $newName = 'cut_'.$filename;
		    $targetPic = $cutPicPath.$newName;

		    //TODO 目录与写文件检测
		    if(false === imagejpeg($copy, $targetPic) ){
		        $data['status'] = 0;
		        $data['info'] =  "生成裁剪图片失败！请确认保存路径存在且可写！";
		        echo json_encode($data);
		        exit;
		    } 

		    unlink($file); //删除上传的图片 保留裁剪后的图片
		    $data['status'] = 1;
		    $data['path'] = $cutPicfolder.$newName;
		    $data['name'] = $newName;
		    $data['url']  = $urlPath.$data['path'];
		    echo json_encode($data);
		    exit;
		}
	}
	private function get_current_url($strip = true){
	    // filter function
	    $filter = function($input, $strip) {
	        $input = urldecode($input);
	        $input = str_ireplace(array("\0", '%00', "\x0a", '%0a', "\x1a", '%1a'), '', $input);
	        if ($strip) {
	            $input = strip_tags($input);
	        }
	        $input = htmlentities($input, ENT_QUOTES, 'UTF-8'); // or whatever encoding you use...
	        return trim($input);
	    };

	    $url = array();
	    // set protocol
	    $url['protocol'] = 'http://';
	    if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) === 'on' || $_SERVER['HTTPS'] == 1)) {
	        $url['protocol'] = 'https://';
	    } elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
	        $url['protocol'] = 'https://';
	    }
	    // set host
	    $url['host'] = $_SERVER['HTTP_HOST'];
	    // set request uri in a secure way
	    $url['request_uri'] = $filter( dirname($_SERVER['REQUEST_URI']), $strip);
	    return join('', $url);
	}

	private function PIPHP_ImageCrop($image, $x, $y, $w, $h){
	   $tw = imagesx($image);
	   $th = imagesy($image);
	   // if ($x > $tw || $y > $th || $w > $tw || $h > $th)
	   //     return FALSE;
	   $temp = imagecreatetruecolor($w, $h);

	   imagecopyresampled($temp, $image, 0, 0, $x, $y,$w, $h, $w, $h);
	   return $temp;
	}
 }