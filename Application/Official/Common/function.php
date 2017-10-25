<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------





/*
*获取中文首字母
*/
function getLimit(){
		$limit = 	array( //gb2312 拼音排序
		array(45217,45252), //A
		array(45253,45760), //B
		array(45761,46317), //C
		array(46318,46825), //D
		array(46826,47009), //E
		array(47010,47296), //F
		array(47297,47613), //G
		array(47614,48118), //H
		array(0,0),         //I
		array(48119,49061), //J
		array(49062,49323), //K
		array(49324,49895), //L
		array(49896,50370), //M
		array(50371,50613), //N
		array(50614,50621), //O
		array(50622,50905), //P
		array(50906,51386), //Q
		array(51387,51445), //R
		array(51446,52217), //S
		array(52218,52697), //T
		array(0,0),         //U
		array(0,0),         //V
		array(52698,52979), //W
		array(52980,53688), //X
		array(53689,54480), //Y
		array(54481,55289), //Z
	);
	return $limit;
}

function getWords($str){
	$str= iconv("UTF-8","gb2312", $str);
	$i=0;
	while($i<strlen($str)){
		$tmp=bin2hex(substr($str,$i,1));
		if($tmp>='B0'){ //汉字的开始
			$t=getLetter(hexdec(bin2hex(substr($str,$i,2))));
			$value[] = sprintf("%c",$t==-1 ? '*' : $t );
			$i+=2;
		}
		else{
			$value[] = sprintf("%s",substr($str,$i,1));
			$i++;
		}
	}
	$result = implode('',$value); ;
	return $result;
}
/*
**/
function getLetter($num){
	$limit	= getLimit();
	$char_index=65;
	foreach($limit as $k=>$v){
		if($num>=$v[0] && $num<=$v[1]){
			$char_index+=$k;
			return $char_index;
		}
	}
	return -1;
}