<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-10
 * Time: PM7:40
 */

/**
	
 * @return array|null
 */
function read_website_info_cash($domain){
	return S('website_info_cash_'.$domain);

}
function write_website_info_cash($domain,$value){
	return S('website_info_cash_'.$domain,$value,3600);
}
function clean_website_info_cash($domain){ 
	S('website_info_cash_'.$domain,null);
}

?>
