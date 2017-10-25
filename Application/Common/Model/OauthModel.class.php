<?php
namespace Common\Model;

use Think\Model;

class OauthModel extends Model{

    protected   $Appid_w='wx4fa439e705c5456b';
    protected   $AppSecret_w='db1379060d792816fac8a54456d97165';

	public function httpRequest($url){

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            if($output == false){
                return 'curl error:'.curl_error($ch);
            }

            curl_close($ch);

            return $output;
        }


        //curl获取参数
        //https 中的 get  和  post
        public function https_request($url,$data=null){
            
            $curl = curl_init();
        
            curl_setopt($curl,CURLOPT_URL,$url);

            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);

            if($data){
                curl_setopt($curl,CURLOPT_POST,1);
                curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
            }

            curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
            
            $output = curl_exec($curl);
           
            curl_close($curl);
            return $output;
        }

        //获取接口票据access_token
        public function get_token(){

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->Appid_w."&secret=".$this->AppSecret_w;

            $json = $this->https_request($url);

            $arr = json_decode($json,true);
            
            return $arr['access_token'];
        }



        /*获取授权后重定向的回调链接地址
         * @param string $redirect_uri
         * @param string $state
         **/
        public function get_authorize_url($redirect_uri = '', $state = ''){

            $redirect_uri = urlencode($redirect_uri);
            return "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->Appid_w."&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";
        }

        public function get_code_access_token($code){
			//第一步：用户同意授权，获取code
			// 第二步：通过code换取网页授权access_token
			$access_token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->Appid_w."&secret=".$this->AppSecret_w."&code={$code}&grant_type=authorization_code";

			$access_token = $this->https_request($access_token_url);
			$arr = json_decode($access_token,true);

			return $arr;
		}

        /***
         *检验授权凭证（access_token）是否有效
         * @param string $access_token
         * @param string $openid
         ***/
        public function checkAvail($access_token='',$openid=''){

             if($access_token && $openid){

                $avail_url = "https://api.weixin.qq.com/sns/auth?access_token={$access_token}&openid={$openid}";

                $avail_data = $this->https_request($avail_url);

                return json_decode($avail_data, TRUE);
             }

              return FALSE;
        }
        /***
         * 刷新access_token
         *
         ***/
        public function refresh_access_token($refresh_token){

            //第三步：刷新access_token
            $refresh_url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=".$this->Appid_w."&grant_type=refresh_token&refresh_token={$refresh_token}";

            $access_token = $this->https_request($refresh_url);

            return json_decode($access_token,true);

        }
        /***
            * 获取授权后的微信用户信息
            *
            * @param string $access_token
            * @param string $openid
        ***/
        public function get_user_info($access_token = '', $openid = ''){

            // 第四步：获取用户信息

            if($access_token && $openid){

                $info_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang=zh_CN";
                
                $info_data = $this->https_request($info_url);

                return json_decode($info_data, TRUE);

            }
            
            return FALSE;
        }


}
?>