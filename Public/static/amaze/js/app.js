(function($) {
  'use strict';

	$(function() {
		//全屏
		$('#admin-fullscreen').on('click', function() {
			if ($.AMUI.fullscreen.enabled) {
				$.AMUI.fullscreen.request();
			} else {
				
			}
		});

	});
	//增加验证正则
	if ($.AMUI && $.AMUI.validator) {
		// 增加多个正则
		$.AMUI.validator.patterns = $.extend($.AMUI.validator.patterns, {
		  colorHex: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
		  mobile:  /^1([0-9]{1})\d{9}$/,
		  MobileOrEmail:  /^(1([0-9]{1})\d{9}|(\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*))$/
		});
		// 增加单个正则
		// $.AMUI.validator.patterns.yourpattern = /^your$/;
	}
})(jQuery);
(function() {

    function FileReaders(file, options) {
      this.file = file;
      this.defaults = {
        //quality: 0.7,
        // width: 1000,
        // height: 1000,
        done: null,
        fail: null,
        before: null,
        always: null
      };

      for (var p in options) {
        this.defaults[p] = options[p];
      }
      //if (this.defaults.quality > 1) this.defaults.quality = 1;

      this.results = {
        origin: null,
        base64: null,
        base64Len: null
      };

      this.init();
    }
    FileReaders.prototype = {
      constructor: FileReaders,

      /**
       * 初始化
       */
      init: function() {
        var that = this;


		if(typeof FileReader === 'undefined'){
		  var error = '不支持此设备';
          // 错误回调
          if (typeof that.defaults.fail === 'function') {
            that.defaults.fail(error);
          }

          // 压缩结束回调
          if (typeof that.defaults.always === 'function') {
            that.defaults.always();
          }
		   return;
		}
        that.create(that.file);
      },
	  
      /**
       * 生成base64
       * @param file
       * @param callback
       */
      create: function(file) {
		  
		  var that = this,
			results = that.results;
			if(!/image\/\w+/.test(file.type)){	
				var error = '请确保文件为图像类型';
				// 读取文件失败
				if (typeof that.defaults.fail === 'function') {
					that.defaults.fail(error);
				}
				
				// 压缩结束回调
				if (typeof that.defaults.always === 'function') {
					that.defaults.always();
				}
				return false;
			}
	
	
			// 压缩开始前回调
            if (typeof this.defaults.before === 'function') {
              this.defaults.before();
            }
	
	
	
	
			var reader = new FileReader();
			reader.readAsDataURL(file);//readAsText readAsBinaryString readAsDataURL
			//读取失败
			reader.onerror = function(e){
				var error = '图像加载失败，请重试';
				// 读取文件失败
				if (typeof that.defaults.fail === 'function') {
					that.defaults.fail(error);
				}
				// 压缩结束回调
				if (typeof that.defaults.always === 'function') {
					that.defaults.always();
				}
			},
			//读取完成触发，无论成功或失败
			reader.onloadend = function(e){
				// 压缩结束回调
				if (typeof that.defaults.always === 'function') {
					that.defaults.always();
				}
			},
			//读取开始时触发
			reader.onloadstart = function(e){

			},
			//读取中
			reader.onprogress  = function(e){

			},
			//文件读取成功完成时触发
			reader.onload = function(e){
				results.base64  = this.result;
				_resultCallback(results);
			}
		  
		  
		  /**
		   * 包装回调
		   */
		  function _resultCallback(results) {
			
			// 压缩成功回调
			if (typeof that.defaults.done === 'function') {
			  that.defaults.done(results);
			}

			// 压缩结束回调
			if (typeof that.defaults.always === 'function') {
			  that.defaults.always();
			}
		  } 
	  } 
	}

      // 暴露接口
      window.filereaders = function(file, options) {
        return new FileReaders(file, options);
      };

})();
$(function () {	
	mobiletalker.bind_ctrl_enter();//绑定
	if (is_login()) {
		bindMessageChecker();//绑定用户消息
	}
	//checkMessage();//检查一次消息	

    /**
     * ajax-post
     * 将链接转换为ajax请求，并交给handleAjax处理
     * 参数：
     * data-confirm：如果存在，则点击后发出提示。
     * 示例：<a href="xxx" class="ajax-post">Test</a>
     */
    $(document).on('click', '.ajax-post', function (e) {
        //取消默认动作，防止跳转页面
        e.preventDefault();

        //获取参数（属性）
        var url = $(this).attr('href');
        var confirmText = $(this).attr('data-confirm');

        //如果需要的话，发出确认提示信息
        if (confirmText) {
            var result = confirm(confirmText);
            if (!result) {
                return false;
            }
        }

        //发送AJAX请求
        $.post(url, {}, function (a, b, c) {
            handleAjax(a);
        });
    });

    /**
     * ajax-form
     * 通过ajax提交表单，通过oneplus提示消息
     * 示例：<form class="ajax-form" method="post" action="xxx">
     */
    $(document).on('submit', 'form.ajax-form', function (e) {
        //取消默认动作，防止表单两次提交
        e.preventDefault();

        //禁用提交按钮，防止重复提交
        var form = $(this);
        $('[type=submit]', form).addClass('disabled');

        //获取提交地址，方式
        var action = $(this).attr('action');
        var method = $(this).attr('method');

        //检测提交地址
        if (!action) {
            return false;
        }

        //默认提交方式为get
        if (!method) {
            method = 'get';
        }

        //获取表单内容
        var formContent = $(this).serialize();

        //发送提交请求
        var callable;
        if (method == 'post') {
            callable = $.post;
        } else {
            callable = $.get;
        }
        callable(action, formContent, function (a) {
            handleAjax(a);
            $('[type=submit]', form).removeClass('disabled');
        });

        //返回
        return false;
    });
	//回到首页
	$('.go_home').on('click', function() {
		window.location.href = U('Mobile/Index/Index');										
	});
	
	$('.people_ufollow').click(function () {
		var _this = $(this);
		var uid = _this.attr("data-uid");
		var isfollowing = _this.attr("data-isfollowing");
		
		 if (isfollowing == '0') {
			$.post(U('Mobile/Public/follow'), {uid: uid}, function (msg) {
				if (msg['status']==1) {
					//_this.remove();
					_this.html('取消关注');
					_this.removeClass('guanzhu');
					_this.addClass('defriending');
					
					_this.attr("data-isfollowing",'1');
					toasts.success('关注成功。');
				} else {
					toasts.error('关注失败。');
				}
			}, 'json'); 
		 }else{
			$.post(U('Mobile/Public/unfollow'), {uid: uid}, function (msg) {
				if (msg['status']==1) {
					_this.removeClass('defriending');
					_this.addClass('guanzhu');
					_this.html('关注');
					_this.attr("data-isfollowing",'0');
					toasts.success('取消关注成功。');
				} else {
					toasts.error('取消关注失败。');
				}
			}, 'json');
		 }	
		
	});
	$('.session_panel_btn').on('click', function(e) {								 
		$('#session_panel').modal({closeViaDimmer:0,relatedTarget: this});
        $('.am-navbar').hide();
	});
    $('.closeMessage').on('click',function(e){
        $('.am-navbar').show();
    });
	$('#session_panel').on('open.modal.amui', function() {
		$('#session_panel_main_box').load(U('Mobile/Session/panel'));
		$('#home_all_bar').offCanvas('close');
		
		//$('#session_panel_btn').hide();
	});
	$('#session_panel').on('closed.modal.amui', function() {
		//$('#session_panel_btn').show();
	});
	
	function timingtool(countdown,obj) {
		if (countdown <= 0) {
			obj.text("重新获取");
			obj.removeClass('am-disabled');
		} else {
			obj.text(countdown+"秒");
			countdown--;
			setTimeout(function() {timingtool(countdown,obj)},1000);
		}
	}
 	var verifyimg = $(".verifyimg").attr("src");
    $(".reloadverify").click(function () {
        if (verifyimg.indexOf('?') > 0) {
            $(".verifyimg").attr("src", verifyimg + '&random=' + Math.random());
        } else {
            $(".verifyimg").attr("src", verifyimg.replace(/\?.*$/, '') + '?' + Math.random());
        }
    });
	var verifyCode_login=$('#verifyCode_login');
	$('#login_mobile_btn').click(function () {							   
		if($(this).hasClass('am-disabled')) return false;
		//判读是否为手机号
		var mobileusername = $(".js-pattern-mobile").val();
		if(mobileusername.match(/^1[3456789]\d{9}$/)){
			$(this).text('Loading...');
			$(this).addClass('am-disabled');
			//加POST发送验证码
			$.post(U('Mobile/Public/getmobile_login_verify'),{mobile:mobileusername,verifyCodebythink:verifyCode_login.val()},function(msg){														 
				var obj = $('#login_mobile_btn');
				if(msg.status==1){
					toasts.success(msg['info']);
					obj.addClass('am-disabled');
					timingtool(60,obj);
				}else{
					$(".reloadverify").trigger("click");
					obj.removeClass('am-disabled');
					obj.text('重新获取');
					toasts.error(msg['info']);

				}
	
			},'json')
		}else{	
			toasts.error('请输入正确手机号');
		}
	})
	
	var verifyCode_public=$('#verifyCode_public');
	$('#public_mobile_btn').click(function () {							   
		if($(this).hasClass('am-disabled')) return false;
		//判读是否为手机号
		var mobileusername = $(".js-pattern-mobile").val();
		if(mobileusername.match(/^1[3456789]\d{9}$/)){
			$(this).text('Loading...');
			$(this).addClass('am-disabled');
			//加POST发送验证码
			$.post(U('Mobile/Public/getmobile_public_verify'),{mobile:mobileusername,verifyCodebythink:verifyCode_public.val()},function(msg){														 
				var obj = $('#public_mobile_btn');
				if(msg.status==1){
					toasts.success(msg['info']);
					obj.addClass('am-disabled');
					timingtool(60,obj);
				}else{				
					$(".reloadverify").trigger("click");
					obj.removeClass('am-disabled');
					obj.text('重新获取');
					toasts.error(msg['info']);
				}
	
			},'json')
		}else{	
			toasts.error('请输入正确手机号');
		}
	})



	var verifyCode_reg=$('#verifyCode_reg');
	$('#reg_mobile_btn').click(function () {
		if($(this).hasClass('am-disabled')) return false;
		var mobileusername = $(".js-pattern-mobile").val();
		if(mobileusername.match(/^1[3456789]\d{9}$/)){
			$.post(U('Mobile/User/domobile'),{mobile:mobileusername},function(datas){
				if(datas==1){
					toasts.error('该号已注册');
				}else{
					$(this).text('Loading...');
					$(this).addClass('am-disabled');
					//加POST发送验证码
					$.post(U('Mobile/Public/getmobile_reg_verify'),{mobile:mobileusername,verifyCodebythink:verifyCode_reg.val()},function(msg){																	  
						var obj = $('#reg_mobile_btn');
						if(msg.status==1){
							toasts.success(msg['info']);
							obj.addClass('am-disabled');
							timingtool(60,obj);
						}else{
							$(".reloadverify").trigger("click");
							obj.removeClass('am-disabled');
							obj.text('重新获取');
							toasts.error(msg['info']);
						}
					},'json')
				}
			
	
			},'json')
		}else{
			toasts.error('请输入正确手机号');
		}
	})
	
	$('#ajax_login_mobile_btn').click(function () {									
		if($(this).hasClass('am-disabled')) return false;
		//判读是否为手机号
		var mobileusername = $(".js-pattern-mobile").val();
		if(mobileusername.match(/^1[3456789]\d{9}$/)){
			
			$(this).html('Loading...');
			$(this).addClass('am-disabled');
			//加POST发送验证码
			$.post(U('Mobile/Public/getmobile_login_verify'),{mobile:mobileusername},function(msg){
				var obj = $('#ajax_login_mobile_btn'); 
				if(msg['status']==1){
					toasts.success(msg['info']);
					obj.addClass('am-disabled');
					timingtool(60,obj);
				}else{
					obj.removeClass('am-disabled');
					obj.text('重新获取');
					toasts.error(msg['info']);
				}

			},'json')
		}else{	
			toasts.error('请输入正确手机号');
		}

	})
	
	
	$('.reg_age_btn').click(function () {
		$('#reg_age').modal({closeViaDimmer:0,relatedTarget: this});				 
	})

	$("#inputMobile").blur(function(){
		var mobileval = $(this).val();
		if(mobileval.match(/^1[3456789]\d{9}$/)){
				$.post(U('Mobile/User/domobile'),{mobile:mobileval},function(datas){
				if(datas==1){
					toasts.error('该号已注册');
				}else{
					$('#reg_mobile_btn').removeClass('am-disabled');
				}
			},'json')
		}else{
			//toasts.error('请输入正确手机号');
		}					
	});
	
});
var hdl_lazyload =function(obj){
	$(obj).lazyload({
	   placeholder : "/Public/Core/images/blank.png",  
	   effect: "fadeIn",
	   appear: function(){
		   $(this).addClass('add_loading');
	   },
	   load: function(){
		  $(this).removeClass('add_loading');
	   },
	});
}
var insertFace = function (obj) {
    $('.XT_insert').css('z-index', '1000');
    $('.XT_face').remove();
	

    var html = '<div class="XT_face  XT_insert"><div class="triangle sanjiao"></div><div class="triangle_up sanjiao"></div>' +
        '<div class="XT_face_main"><div class="XT_face_title"><span class="XT_face_bt" style="float: left">常用表情</span>' +
        '<a onclick="close_face()" class="XT_face_close">X</a></div><div id="face" style="padding: 10px;"></div></div></div>';
    obj.parents('.weibo_post_box').find('#emot_content').html(html);
	
	
    getFace(obj);
};

var face_chose = function (obj) {
    var textarea = obj.parents('.weibo_post_box').find('textarea');
    textarea.focus();
    //textarea.val(textarea.val()+'['+obj.attr('title')+']');

    pos = getCursortPosition(textarea[0]);
    s = textarea.val();


    if(obj.attr('data-type') == 'miniblog'){
        textarea.val(s.substring(0, pos) + '[' + obj.attr('title') + ']' + s.substring(pos));
        setCaretPosition(textarea[0], pos + 2 + obj.attr('title').length);
    }else{
        textarea.val(s.substring(0, pos) + '[' + obj.attr('title') + ':'+obj.attr('data-type')+']' + s.substring(pos));
        setCaretPosition(textarea[0], pos + 3 + obj.attr('title').length+obj.attr('data-type').length);
    }


}

var getFace = function (obj) {
    $.post(U('Mobile/Index/getSmile'), {}, function (data) {
        var _imgHtml = '';
        for (var k in data) {
            _imgHtml += '<a href="javascript:void(0)" data-type="'+data[k].type+'" title="' + data[k].title + '" onclick="face_chose($(this))";><img src="' + data[k].src + '" width="24" height="24" /></a>';
        }
        _imgHtml += '<div class="c"></div>';
        obj.parents('.weibo_post_box').find('#emot_content').find('#face').html(_imgHtml);

    }, 'json');
}

var close_face = function () {
    $('.XT_face').remove();
}

var get_validator = function(obj){
	var $form = obj;
	var $tooltip = $('<div id="vld-tooltip">提示信息！</div>');
	$tooltip.appendTo(document.body);
	
	
	$form.validator({
		onValid: function(validity) {
		   $tooltip.hide();
		}
	});
	var validator = $form.data('amui.validator');
	$form.on('focusin focusout',  '.am-form-error input,.am-form-error textarea',
 function(e) {				
		if (e.type === 'focusin') {
		  var $this = $(this);
		  var offset = $this.offset();
		  var msg = $this.data('foolishMsg') || validator.getValidationMessage($this.data('validity'));
		
		  $tooltip.text(msg).show().css({
			left: offset.left + 10,
			top: offset.top + $(this).outerHeight() + 10
		  });
		} else {
		  $tooltip.hide();
		}
	});
	
}


function getCursortPosition(ctrl) {//获取光标位置函数

    var CaretPos = 0;	// IE Support
    if (document.selection) {
        ctrl.focus();
        var Sel = document.selection.createRange();
        Sel.moveStart('character', -ctrl.value.length);
        CaretPos = Sel.text.length;
    }
    // Firefox support
    else if (ctrl.selectionStart || ctrl.selectionStart == '0')
        CaretPos = ctrl.selectionStart;
    return (CaretPos);
}

function setCaretPosition(ctrl, pos) {//设置光标位置函数
    if (ctrl.setSelectionRange) {
        ctrl.focus();
        ctrl.setSelectionRange(pos, pos);
    }
    else if (ctrl.createTextRange) {
        var range = ctrl.createTextRange();
        range.collapse(true);
        range.moveEnd('character', pos);
        range.moveStart('character', pos);
        range.select();
    }
}
function is_login() {
    return parseInt(MID);
}

/**
 * 模拟U函数
 * @param url
 * @param params
 * @returns {string}
 * @constructor
 */
function U(url, params, rewrite) {


    if (window.Think.MODEL[0] == 2) {

        var website = _ROOT_ + '/';
        url = url.split('/');

        if (url[0] == '' || url[0] == '@')
            url[0] = APPNAME;
        if (!url[1])
            url[1] = 'Index';
        if (!url[2])
            url[2] = 'index';
        website = website + '' + url[0] + '/' + url[1] + '/' + url[2];

        if (params) {
            params = params.join('/');
            website = website + '/' + params;
        }
        if (!rewrite) {
            website = website + '.html';
        }

    } else {
        var website = _ROOT_ + '/index.php';
        url = url.split('/');
        if (url[0] == '' || url[0] == '@')
            url[0] = APPNAME;
        if (!url[1])
            url[1] = 'Index';
        if (!url[2])
            url[2] = 'index';
        website = website + '?s=/' + url[0] + '/' + url[1] + '/' + url[2];
        if (params) {
            params = params.join('/');
            website = website + '/' + params;
        }
        if (!rewrite) {
            website = website + '.html';
        }
    }

    if(typeof (window.Think.MODEL[1])!='undefined'){
        website=website.toLowerCase();
    }
    return website;
}

function user_login(){
	$('#user-login').modal({closeViaDimmer:0});
    $('.am-navbar').hide();

	get_validator($('#form_fast_login'));
	get_validator($('#form_user_login'));

	
}


var app_modal_popup = {
					   
					   
	alerts:function (text,url) {
		var alert_btn =true;
		var alert_btn_autoclose =true;
		var timeID;
		var $my_modal_alert = $("#my-modal-alert");
		$my_modal_alert.find("[alert_title]").html(text).show();
		$my_modal_alert.find("[alert_content]").hide();
		if(alert_btn){
			$my_modal_alert.find("[alert_btn]").show();
		}else{
			$my_modal_alert.find("[alert_btn]").hide();
		}
		$my_modal_alert.modal();
		$my_modal_alert.on('closed.modal.amui', function(e) {
			$my_modal_alert.find("[alert_title]").html('').hide();
			
			if(url){
				location.href = url;
			}	
			if(alert_btn_autoclose){
				clearTimeout(timeID);
			}
		});
		if(alert_btn_autoclose){
			$my_modal_alert.on('opened.modal.amui', function(e) {							 									 
				timeID = setTimeout(function () {
					$my_modal_alert.modal('close');
				}, 3000);
			});
		}	
	},
	confirms:function (confirms_options) {

	
		var  relatedTarget = this;
		if(confirms_options.relatedTarget){
			relatedTarget = confirms_options.relatedTarget;			
		}
		var alert_btn_autoclose =true;
		var timeID;
		var $my_modal_alert = $("#my-modal-confirm");
		$my_modal_alert.find("[alert_content]").html(confirms_options.text).show();
		$my_modal_alert.find("[alert_title]").hide();
		$my_modal_alert.modal({		  
			relatedTarget: relatedTarget,
			closeViaDimmer:0,
			onConfirm: function() { 
				if(typeof confirms_options.confirm == "function" && confirms_options.confirm){
					 confirms_options.confirm.call($(this.relatedTarget));
				}
			},
			onCancel: function() {
				if(typeof confirms_options.cancel == "function" && confirms_options.cancel){
					 confirms_options.cancel.call($(this.relatedTarget));
				}
			}
		});
		$my_modal_alert.on('closed.modal.amui', function(e) {									
			$my_modal_alert.find("[alert_content]").html('').hide();
		});	
	}
}


var setSpinner = (function () {

    function spinner_setup() {
    }

    spinner_setup.prototype.open = function (text) {
            $("body").append(function () {
                return "<div class='loadMask'><div id='load_mask'></div><div class='load_mask_box'></div></div>";
            });
                $('.load_mask_box').html(
                    '<i class="am-icon-spinner am-icon-pulse"></i>'+
                    '<span>'+
                    text+
                    '</span>');
        var pos_w = document.documentElement.clientWidth;
        var pos_h = document.documentElement.clientHeight;
        var dis_w = $('.load_mask_box').outerWidth();
        var dis_h = $('.load_mask_box').outerHeight();
        var out_left = (pos_w-dis_w)/2;
        var out_top = (pos_h-dis_h)/2;
        $('.load_mask_box').css({"left":out_left+"px","top":out_top+"px"});
    };
    spinner_setup.prototype.close = function(){
            $('div').remove('.loadMask');
    };

    return new spinner_setup;
})();



var resizeablePicture = function(image_target,url,urlImg,widthImg,heightImg,a) {
    // Some variable and settings
    var $container,
        orig_src = new Image(),
        image_target = $(image_target).get(0),
        event_state = {},
        constrain = false,


    init = function(){
        // When resizing, we will always use this copy of the original as the base
        orig_src.src=image_target.src;
        // Wrap the image with the container and add resize handles
        $(image_target).wrap('<div class="resize-container"></div>')
            .before('<span class="resize-handle resize-handle-nw"></span>')
            .before('<span class="resize-handle resize-handle-ne"></span>')
            .after('<span class="resize-handle resize-handle-se"></span>')
            .after('<span class="resize-handle resize-handle-sw"></span>');

        // Assign the container to a variable
        $container =  $(image_target).parent('.resize-container');

        // Add events
        $container.on('mousedown touchstart', 'img', startMoving);
        $('.js-crop').on('click', crop);
    };


    saveEventState = function(e){
        // Save the initial event details and container state
        event_state.container_width = $container.width();
        event_state.container_height = $container.height();
        event_state.container_left = $container.offset().left;
        event_state.container_top = $container.offset().top;
        event_state.mouse_x = (e.clientX || e.pageX || e.originalEvent.touches[0].clientX) + $(window).scrollLeft();
        event_state.mouse_y = (e.clientY || e.pageY || e.originalEvent.touches[0].clientY) + $(window).scrollTop();

        // This is a fix for mobile safari
        // For some reason it does not allow a direct copy of the touches property
        if(typeof e.originalEvent.touches !== 'undefined'){
            event_state.touches = [];
            $.each(e.originalEvent.touches, function(i, ob){
                event_state.touches[i] = {};
                event_state.touches[i].clientX = 0+ob.clientX;
                event_state.touches[i].clientY = 0+ob.clientY;
            });
        }
        event_state.evnt = e;
    };
    startMoving = function(e){
        e.preventDefault();
        e.stopPropagation();
        saveEventState(e);
        $(document).on('mousemove touchmove', moving);
        $(document).on('mouseup touchend', endMoving);
    };

    endMoving = function(e){
        e.preventDefault();
        $(document).off('mouseup touchend', endMoving);
        $(document).off('mousemove touchmove', moving);
    };

    moving = function(e){
        var  mouse={}, touches;
        e.preventDefault();
        e.stopPropagation();

        touches = e.originalEvent.touches;

        mouse.x = (e.clientX || e.pageX || touches[0].clientX) + $(window).scrollLeft();
        mouse.y = (e.clientY || e.pageY || touches[0].clientY) + $(window).scrollTop();
        $container.offset({
            'left': mouse.x - ( event_state.mouse_x - event_state.container_left ),
            'top': mouse.y - ( event_state.mouse_y - event_state.container_top )
        });
    };

    crop = function(){
        //Find the part of the image that is inside the crop box
        var crop_canvas,
            left = $('.overlay').offset().left - $container.offset().left,
            top =  $('.overlay').offset().top - $container.offset().top,
            width = $('.overlay').width(),
            height = $('.overlay').height();
        var data= new Array();
        data=a.split("/");
        var i = data.length;
        var img = data[i-2]+'/'+data[i-1];
        var crop2 = left / widthImg + ',' + top / heightImg + ',' + 200 / widthImg + ',' + 200 / heightImg;
        $.post(url, {img:img,crp:crop2}, function (a) {
            location.href = urlImg;
        },'json');
    }

    init();
};




$.fn.localResizeIMG = function(obj) {
    this.on('change', function() {
        setSpinner.open("图片加载中");
        var file = this.files[0];
        var URL = window.URL || window.webkitURL;
        var blob = URL.createObjectURL(file);

        // 执行前函数
        if ($.isFunction(obj.before)) {
            obj.before(this, blob, file)
        };

        _create(blob, file);
        this.value = ''; // 清空临时数据
    });

    /**
     * 生成base64
     * @param blob 通过file获得的二进制
     */
    function _create(blob) {
        var img = new Image();
        img.src = blob;

        img.onload = function() {
            var that = this;

            //生成比例
            var w = that.width,
                h = that.height,
                scale = w / h;
				if(w>=900){
					w=900;
				}
            w = obj.width || w;
            h = w / scale;
            //生成canvas
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            $(canvas).attr({
                width: w,
                height: h
            });
            ctx.drawImage(that, 0, 0, w, h);

            /**
             * 生成base64
             * 兼容修复移动设备需要引入mobileBUGFix.js
             */
            var base64 = canvas.toDataURL('image/jpeg', obj.quality || 0.6);

            // 修复IOS
            if (navigator.userAgent.match(/iphone/i)) {
                var mpImg = new MegaPixImage(img);
                mpImg.render(canvas, {
                    maxWidth: w,
                    maxHeight: h,
                    quality: obj.quality || 0.6
                });
                base64 = canvas.toDataURL('image/jpeg', obj.quality || 0.6);
            }

            // 修复android
            if (navigator.userAgent.match(/Android/i)) {
                var encoder = new JPEGEncoder();
                base64 = encoder.encode(ctx.getImageData(0, 0, w, h), obj.quality * 100 || 60);
            }

            // 生成结果
            var result = {
                base64: base64,
                clearBase64: base64.substr(base64.indexOf(',') + 1)
            };

            // 执行后函数
            obj.success(result);
        };
    }
};






/*自定义*/
var toasts = {
    /**
     * 成功提示
     * @param text 内容
     * @param title 标题
     */
    success: function (text,url) {
        $("html").append(function(){
            return "<div id='msg'></div>";
        });
        var title_msg = $('#msg');
        title_msg.removeClass();
        title_msg.addClass("toasts-success toasts-bottom-center");
        title_msg.html(text);
        var pos_w = document.documentElement.clientWidth;
		var pos_h = document.documentElement.clientHeight;
        var dis_w = title_msg.outerWidth();
		var dis_h = title_msg.outerHeight();
        var out_left = (pos_w-dis_w)/2;
		var out_top = (pos_h-dis_h)/2-60;
        title_msg.css({"left":out_left+"px","top":out_top+"px"}).fadeIn(10).delay(500).fadeOut(1000);
        setTimeout(function(){
            $('div').remove('#msg');
        },1510)
        if(url){
            location.href = url;
        }
    },
    /**
     * 失败提示
     * @param text 内容
     * @param title 标题
     */
    error: function (text,url) {
        $("html").append(function(){
            return "<div id='msg'></div>";
        });
        var title_msg = $('#msg');
        title_msg.removeClass();
        title_msg.addClass("toasts-error toasts-bottom-center");
        title_msg.html(text);
        var pos_w = document.documentElement.clientWidth;
		var pos_h = document.documentElement.clientHeight;
        var dis_w = title_msg.outerWidth();
		var dis_h = title_msg.outerHeight();
        var out_left = (pos_w-dis_w)/2;
		var out_top = (pos_h-dis_h)/2+140;
        title_msg.css({"left":out_left+"px","top":out_top+"px"}).fadeIn(10).delay(2000).fadeOut(1000);
        setTimeout(function(){
            $('div').remove('#msg');
        },3010)
        if(url){
            location.href = url;
        }
    },
    /**
     * 信息提示
     * @param text 内容
     * @param title 标题
     */
    info: function (text,url) {
        $("html").append(function(){
            return "<div id='msg'></div>";
        });
        var title_msg = $('#msg');
        title_msg.removeClass();
        title_msg.addClass("toasts-info toasts-bottom-center");
        title_msg.html(text);
        var pos_w = document.documentElement.clientWidth;
		var pos_h = document.documentElement.clientHeight;
        var dis_w = title_msg.outerWidth();
		var dis_h = title_msg.outerHeight();
        var out_left = (pos_w-dis_w)/2;
		var out_top = (pos_h-dis_h)/2;
        title_msg.css({"left":out_left+"px","top":out_top+"px"}).fadeIn(10).delay(500).fadeOut(1000);
        setTimeout(function(){
            $('div').remove('#msg');
        },1510)
        if(url){
            location.href = url;
        }
    },
    /**
     * 警告提示
     * @param text 内容
     * @param title 标题
     */
    warning: function (text,url) {
        $("html").append(function(){
            return "<div id='msg'></div>";
        });
        var title_msg = $('#msg');
        title_msg.removeClass();
        title_msg.addClass("toasts-warning toasts-bottom-center");
        title_msg.html(text);
        var pos_w = document.documentElement.clientWidth;
		var pos_h = document.documentElement.clientHeight;
        var dis_w = title_msg.outerWidth();
		var dis_h = title_msg.outerHeight();
        var out_left = (pos_w-dis_w)/2;
		var out_top = (pos_h-dis_h)/2;
        title_msg.css({"left":out_left+"px","top":out_top+"px"}).fadeIn(10).delay(500).fadeOut(1000);
        setTimeout(function(){
            $('div').remove('#msg');
        },1510)
        if(url){
            location.href = url;
        }
    }
}
/**
 * 操纵toastor的便捷类
 * @type {{success: success, error: error, info: info, warning: warning}}
 */
var toast = {
	/**
	 * 成功提示
	 * @param text 内容
	 * @param title 标题
	 */
	success: function (text, title) {
		toastr.options = {
			"closeButton": true,
			"debug": false,
			"positionClass": "toast-center",
			"onclick": null,
			"showDuration": "1000",
			"hideDuration": "1000",
			"timeOut": "3000",
			"extendedTimeOut": "1000",
			"showEasing": "swing",
			"hideEasing": "linear",
			"showMethod": "fadeIn",
			"hideMethod": "fadeOut"
		}
		toastr.success(text, title);
	},
	/**
	 * 失败提示
	 * @param text 内容
	 * @param title 标题
	 */
	error: function (text, title) {
		toastr.options = {
			"closeButton": true,
			"debug": false,
			"positionClass": "toast-center",
			"onclick": null,
			"showDuration": "1000",
			"hideDuration": "1000",
			"timeOut": "3000",
			"extendedTimeOut": "1000",
			"showEasing": "swing",
			"hideEasing": "linear",
			"showMethod": "fadeIn",
			"hideMethod": "fadeOut"
		}

		toastr.error(text, title);
	},
	/**
	 * 信息提示
	 * @param text 内容
	 * @param title 标题
	 */
	info: function (text, title) {
		toastr.options = {
			"closeButton": true,
			"debug": false,
			"positionClass": "toast-center",
			"onclick": null,
			"showDuration": "1000",
			"hideDuration": "1000",
			"timeOut": "3000",
			"extendedTimeOut": "1000",
			"showEasing": "swing",
			"hideEasing": "linear",
			"showMethod": "fadeIn",
			"hideMethod": "fadeOut"
		}
		toastr.info(text, title);
	},
	/**
	 * 警告提示
	 * @param text 内容
	 * @param title 标题
	 */
	warning: function (text, title) {
		toastr.options = {
			"closeButton": true,
			"debug": false,
			"positionClass": "toast-center",
			"onclick": null,
			"showDuration": "1000",
			"hideDuration": "1000",
			"timeOut": "5000",
			"extendedTimeOut": "1000",
			"showEasing": "swing",
			"hideEasing": "linear",
			"showMethod": "fadeIn",
			"hideMethod": "fadeOut"
		}
		toastr.warning(text, title);
	}
}



/**
 * 渲染消息模板
 * @param message 消息体
 * @param mid 当前用户ID
 * @returns {string}
 */
function op_fetchMessageTpl(message, mid) {
    var tpl_right = '<div class="message-cf">' +
        '<div class="time"><span class="timespan">{ctime}</span></div>' +
        '<div class="talk_right"><i class="bubble_sharp"></i>' +
        '<div class="bubble_outter">' +
        '<h3>我</h3>' +
        '<div class="talk_bubble">{content}' +
        '</div>' +
        '</div>' +
        '<img class="avatar-img talk-avatar"' +
        'src="{avatar128}"/>' +
        '</div> </div>';

    var tpl_left = '<div class="message-cf">' +
        '<div class="time"><span class="timespan">{ctime}</span></div>' +
        '<div class="talk_left"><i class="bubble_sharp"></i>' +
        '<img class="avatar-img talk-avatar"' +
        'src="{avatar128}"/>' +
        '<div class="bubble_outter">' +
        '<h3>{nickname}</h3>' +
        '<div class="talk_bubble">{content}' +
        '</div></div></div></div>';
    var tpl = message.uid == mid ? tpl_right : tpl_left;
    $.each(message, function (index, value) {
        tpl = tpl.replace('{' + index + '}', value);
    });
    return tpl;
}
/**
 * 聊天对象
 * 主要用于处理前台聊天事件
 */
var mobiletalker = {
    /**
     * 发起聊天请求
     * @param uid
     */
    start_talk: function (uid) {
        if (confirm('确定要和该用户发起聊天？')) {
			
            $.post(U('Mobile/Session/createTalk'), {uids: uid}, function (msg) {
                if (msg.status) {
                    //toast.success('聊天发起成功。', '聊天助手');
                    //$('#friend_panel_main').toggle();
                    //$('#session_panel_main').toggle();
                    mobiletalker.open(msg.info.id);
                    /*在面板中加入一个项目*/
                    mobiletalker.prepend_session(msg.info,false);
                } else {
                    //TODO 创建失败
					
                }

            }, 'json');
        }
    },
    /**
     * 向聊天窗添加一条消息
     * @param html 消息内容
     */
    append_message: function (html) {

        $('#scrollContainer_chat').append(html);
		
        //$('#scrollArea_chat').smoothScroll({position: $('#scrollContainer_chat').height()});
		//$('.talk-body').smoothScroll({position:$('#scrollContainer_chat').height()});
		 
    },
    /**
     * 渲染消息模板
     * @param message 消息体
     * @param mid 当前用户ID
     * @returns {string}
     */
    fetch_message_tpl: function (message, mid) {
        var tpl_right = '<div class="message-cf">' +
            '<div class="time"><span class="timespan">{ctime}</span></div>' +
            '<div class="talk_right"><i class="bubble_sharp"></i>' +
            '<div class="bubble_outter">' +
            '<h3>&nbsp;</h3>' +
            '<div class="talk_bubble">{content}' +
            '</div>' +
            '</div>' +
            '<img class="avatar-img talk-avatar"' +
            'src="{avatar64}"/>' +
            '</div> </div>';

        var tpl_left = '<div class="message-cf">' +
            '<div class="time"><span class="timespan">{ctime}</span></div>' +
            '<div class="talk_left"><i class="bubble_sharp"></i>' +
            '<img class="avatar-img talk-avatar"' +
            'src="{avatar64}"/>' +
            '<div class="bubble_outter chat_bubble">' +
            '<h3>&nbsp;</h3>' +
            '<div class="talk_bubble">{content}' +
            '</div></div></div></div>';
        var tpl = message.uid == mid ? tpl_right : tpl_left;
        $.each(message, function (index, value) {
            tpl = tpl.replace('{' + index + '}', value);
        });
        return tpl;
    },
    /**
     * 清空聊天框内的内容
     */
    clear_box: function () {
        $('#scrollContainer_chat').html('');
    },
    /**
     * 退出一个聊天框
     * @param id
     */
    exit: function (id) {
        if (confirm('确定退出该聊天？退出后无法再主动加入。')) {
            if (typeof (id) == 'undefined') {
                id = $('#chat_id').val();
            } else {
            }
            $.post(U('Mobile/Message/doDeleteTalk'), {talk_id: id}, function (msg) {
                if (msg.status) {
                    $('#chat_box').hide();
                    $('#chat_li_' + id).remove();
                    //toast.success('成功退出聊天。', '聊天助手');
                }

            }, 'json');
        }
    },
    /**
     * 绑定快速回复，ctrl+enter组合键
     */
    bind_ctrl_enter: function () {
        $('#chat_content').keypress(function (e) {
            if (e.ctrlKey && e.which == 13 || e.which == 10) {
                mobiletalker.post_message();
            }
        });
    },

    /**
     * 聊天框发送消息
     */
    post_message: function () {
        var myDate = new Date();
        $.post(U('Mobile/Message/postMessage'), {talk_id: $('#chat_id').val(), content: $('#chat_content').val()}, function (msg) {
            if(!msg.status){
				toasts.error(msg.info);
            }else{
                mobiletalker.append_message(op_fetchMessageTpl({uid: MID, content: msg.content,avatar128: myhead,ctime: myDate.toLocaleTimeString()}, MID));
				$('.talk-body').smoothScroll({position:$('#scrollContainer_chat').height()});
                $('#chat_content').val('');
                $('#chat_content').focus();
                $('.XT_face').remove();
            }

        }, 'json');
    },
    /**
     * 打开一个聊天框
     * @param id
     */
    open: function (id) {
        $.get(U('Mobile/Session/getSession'), {id: id}, function (data) {
            mobiletalker.clear_box();
            $('li', '#session_panel_main').removeClass();
            $('.badge_new', '#chat_li_' + id).remove();

            if (typeof ($('.friend_list').find('.badge_new').html()) == 'undefined') {
                $('.friend_has_new').html('0').hide();
            }else{
				$('.friend_has_new').html($('.friend_list').find('.badge_new').length).show();
			}

            $('#chat_li_' + id).addClass('active');
			$('#chat_box').modal({closeViaDimmer:0,relatedTarget: this});
			
			mobiletalker.set_current(data);
			$('.talk-body').smoothScroll({position:$('#scrollArea_chat').height()});
        }, 'json');
    },
    /**
     * 添加一个session到当前会话面板中
     * @param data
     */
    prepend_session: function (data,isshow_has_new) {
		
        var tpl = '<li id="chat_li_' +
            data.id + '"><a href="javascript:" onclick="mobiletalker.open(' + 
			data.id + ')" class="session_ico"><img class="lazy session-lists-thumb"  src="' +
            data.ico + '" data-original="' +
            data.ico + '" alt="" title=""/><h3 class="session-lists-title">' +
            data.title + '</h3><div class="session-lists-message"></div><div class="session-lists-uptime"></div><div class="session-lists-badge am-badge am-badge-danger am-round badge_new">&nbsp;</div></a><!--div class="session-lists-close"><i class="am-icon-power-off" onclick="mobiletalker.exit(' + 
			data.id + ')"></i></div--></li>';
        $('#session_panel_main .friend_list').prepend(tpl);
		
		if(isshow_has_new){
			$('.friend_has_new').html(parseInt($('.friend_has_new').html())+1).show();
		}
		
    },
    /**
     * 设置某个消息为未读
     * @param talk_id
     */
    set_session_unread: function (talk_id) {
        function chatpanel_has_loaded() {
            return typeof ($('#chat_li_' + talk_id).html()) != 'undefined';
        } 
		

        if (chatpanel_has_loaded()) {//当聊天面板已经载入了
            if (typeof ($('#chat_li_' + talk_id).find('.badge_new').html()) != 'undefined') {//检测是否已经存在新标记
                //如果已经存在新标记
                return true;
            } else {
                $('#chat_li_' + talk_id).find('.session_ico').append('<div class="session-lists-badge am-badge am-badge-danger am-round badge_new">&nbsp;</div>');
            }
        }
		$('.friend_has_new').html(parseInt($('.friend_has_new').html())+1).show();
	
	
		
		
        //TODO tox设置某个session未读
    },
    /**
     * 设置当前聊天框
     * @param chat
     */
    set_current: function (chat) {
        //$('#chat_ico').attr('src', chat.ico);
        $('#chat_title').text(chat.title);
        $('#chat_id').val(chat.id);
        $.each(chat.messages, function (i, item) {
            mobiletalker.append_message(mobiletalker.fetch_message_tpl(item, MID));
        });
        mobiletalker.append_message('<hr/>' +
            '<div style="text-align: center;color: #666">以上为历史聊天记录</div>', MID);
    }
}


/**播放背景音乐
 *
 * @param file 文件路径
 */
function playsound(file) {
    if (window.Think.ROOT == '') {
        file = '/' + file;
    } else {
        file = window.Think.ROOT + '/' + file;
    }
    $('embed').remove();
    $('body').append('<embed src="' + file + '" autostart="true" hidden="true" loop="false">');
    var div = document.getElementById('music');
    div.src = file;
}




/**播放背景音乐
 *
 * @param file 文件路径
 */
function playsound_ios() {
	$('#play_message').trigger("click");
}

$(function() {
		   
	var myaudio=document.getElementById("myaudio");
	myaudio.volume=1;//表示的是播放音量为一半
	myaudio.muted = false;
	//myaudio.preload = "metadata";
	//myaudio.autoPlay = true;
	myaudio.loop = false;
		   
	$("#play_message").on('click', function (e) {
		myaudio.play()
	});	 	 
})



/**
 * 绑定消息检查
 */
function bindMessageChecker() {
    //$hint_count = $('#nav_hint_count');
    //$nav_bandage_count = $('#nav_bandage_count');

    setInterval(function () {
        checkMessage();
    }, 5000);
}

function play_bubble_sound() {
    playsound('Public/Core/js/ext/toastr/message.wav');
}
function paly_ios_sound() {
    playsound('Public/Core/js/ext/toastr/tip.mp3');
}
/**
 * 检查是否有新的消息
 */
function checkMessage() {
	
    $.get(U('Mobile/Public/getInformation'), {}, function (msg) {									   
        if (msg.messages) {
            /*var count = parseInt($hint_count.text());
            if (count == 0) {
                $('#nav_message').html('');
            }
            paly_ios_sound();
            for (var index in msg.messages) {

                tip_message(msg['messages'][index]['content'] + '<div style="text-align: right"> ' + msg['messages'][index]['ctime'] + '</div>', msg['messages'][index]['title']);
                //  var url=msg[index]['url']===''?U('') //设置默认跳转到消息中心


                var new_html = $('<span><li><a data-url="' + msg['messages'][index]['url'] + '"' + 'onclick="readMessage(this,' + msg['messages'][index]['id'] + ')"><i class="glyphicon glyphicon-bell"></i>' +
                    msg['messages'][index]['title'] + '<br/><span class="time">' + msg['messages'][index]['ctime'] +
                    '</span> </a></li></span>');
                $('#nav_message').prepend(new_html.html());


            }

            $hint_count.text(count + msg.messages.length);
            $nav_bandage_count.show();
            $nav_bandage_count.text(count + msg.messages.length);*/
        }
        if (msg.new_talks) {		
            //playsound_ios();
            //发现有新的聊天
            $.each(msg.new_talks, function (index, talk) {
                    mobiletalker.prepend_session(talk.talk,true);
                }
            );
        }


        function message_box_showing(talk_message) {
            return ($('#chat_id').val() == talk_message.talk_id) && (!$('#chat_box').is(":hidden"));
        }

        if (msg.new_talk_messages) {
            playsound_ios();
            //发现有新的聊天
            $.each(msg.new_talk_messages, function (index, talk_message) {
                    if (message_box_showing(talk_message)) {
                        mobiletalker.append_message(mobiletalker.fetch_message_tpl(talk_message, MID));
						$('.talk-body').smoothScroll({position:$('#scrollContainer_chat').height()});
                        //发起一个获取聊天的请求来将该聊天设为已读
                        $.get(U('Mobile/Session/getSession'), {id: talk_message.talk_id}, function () {

                        }, 'json');

                    }
                    else {
                        mobiletalker.set_session_unread(talk_message.talk_id);
                    }
                }
            );
			
        }


    }, 'json');

}
function readMessage(obj, message_id) {
    var url = $(obj).attr('data-url');
    $.post(U('Mobile/Public/readMessage'), {message_id: message_id}, function (msg) {
        if (msg.status) {
            location.href = url;
        }
    }, 'json');
}

/**
 * 将所有的消息设为已读
 */
function setAllReaded() {
    $.post(U('Mobile/Public/setAllMessageReaded'), function () {
        //$hint_count.text(0);
        $('#nav_message').html('<div style="font-size: 18px;color: #ccc;font-weight: normal;text-align: center;line-height: 150px">暂无任何消息!</div>');
        //$nav_bandage_count.hide();
        //$nav_bandage_count.text(0);

    });
}


/**
 * 消息中心提示有新的消息
 * @param text
 * @param title
 */
function tip_message(text, title) {
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "positionClass": "toast-top-right",
        "onclick": null,
        "showDuration": "1000",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    }
    toastr.info(text, title);
}
/**
 * 友好时间
 * @param sTime
 * @param cTime
 * @returns {string}
 */
function friendlyDate(sTime, cTime) {
    var formatTime = function (num) {
        return (num < 10) ? '0' + num : num;
    };

    if (!sTime) {
        return '';
    }

    var cDate = new Date(cTime * 1000);
    var sDate = new Date(sTime * 1000);
    var dTime = cTime - sTime;
    var dDay = parseInt(cDate.getDate()) - parseInt(sDate.getDate());
    var dMonth = parseInt(cDate.getMonth() + 1) - parseInt(sDate.getMonth() + 1);
    var dYear = parseInt(cDate.getFullYear()) - parseInt(sDate.getFullYear());

    if (dTime < 60) {
        if (dTime < 10) {
            return '刚刚';
        } else {
            return parseInt(Math.floor(dTime / 10) * 10) + '秒前';
        }
    } else if (dTime < 3600) {
        return parseInt(Math.floor(dTime / 60)) + '分钟前';
    } else if (dYear === 0 && dMonth === 0 && dDay === 0) {
        return '今天' + formatTime(sDate.getHours()) + ':' + formatTime(sDate.getMinutes());
    } else if (dYear === 0) {
        return formatTime(sDate.getMonth() + 1) + '月' + formatTime(sDate.getDate()) + '日 ' + formatTime(sDate.getHours()) + ':' + formatTime(sDate.getMinutes());
    } else {
        return sDate.getFullYear() + '-' + formatTime(sDate.getMonth() + 1) + '-' + formatTime(sDate.getDate()) + ' ' + formatTime(sDate.getHours()) + ':' + formatTime(sDate.getMinutes());
    }
}

/**
 * Ajax系列
 */

/**
 * 处理ajax返回结果
 */
function handleAjax(a) {
    //如果需要跳转的话，消息的末尾附上即将跳转字样
    if (a.url) {
        a.info += '，页面即将跳转～';
    }

    //弹出提示消息
    if (a.status) {
        toasts.success(a.info);
    } else {
        toasts.error(a.info);
    }

    //需要跳转的话就跳转
    var interval = 1500;
    if (a.url == "refresh") {
        setTimeout(function () {
            location.href = location.href;
        }, interval);
    } else if (a.url) {
        setTimeout(function () {
            location.href = a.url;
        }, interval);
    }
}