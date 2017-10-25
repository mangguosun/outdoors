//dom加载完成后执行的js
;$(function(){
	//Check All Functionality

	$(".check-all").parent().click(function(e){
		e.stopPropagation();
	});
	$(document).on('change', '.check-all', function (e) {
		e.preventDefault();
		var set = $(this).attr('data-set');										
		//var set = $(".checkboxes");
		var checked = $(this).is(":checked");
		$(set).each(function () {
			if (checked) {
				$(this).prop("checked", true);
				$(this).parents('tr').addClass("active");
			} else {
				$(this).prop("checked", false);
				$(this).parents('tr').removeClass("active");
			}
		});
	
	});
	$('.ids').change(function () {					   
		$(this).parents('tr').toggleClass("active");
		
		var option = $(".ids");
		option.each(function(i){
			if(!this.checked){
				$(".check-all").prop("checked", false);
				return false;
			}else{
				$(".check-all").prop("checked", true);
			}
		});
		
	});
	
	$(".ids").each(function(i){
		$(".check-all").prop("checked", false);
		$(this).prop("checked", false);
			
	});
	

    //ajax get请求
    //$('.ajax-get').click(function(){
								  
    $(document).on('click', '.ajax-get', function (e) {
        //取消默认动作，防止跳转页面
        e.preventDefault();
								  
        var target;
        var that = this;
        if ( $(this).hasClass('confirm') ) {
            if(!confirm('确认要执行该操作吗?')){
                return false;
            }
        }
        if ( (target = $(this).attr('href')) || (target = $(this).attr('url')) ) {
            $.get(target).success(function(data){
                if (data.status==1) {
                    if (data.url) {
						Notify(data.info + '页面即将自动跳转~', 'center-center', '5000', 'success', 'fa-check', true);
                    }else{
                        Notify(data.info, 'center-center', '1000', 'success', 'fa-check', true);
                    }
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else if( $(that).hasClass('no-refresh')){
                        }else{
                            location.reload();
                        }
                    },1000);
                }else{
					Notify(data.info, 'center-center', '1000', 'darkorange', 'fa-times', true);
					
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else{
                            
                        }
                    },1500);
                }
            });

        }
        return false;
    });

    //ajax post submit请求
    $(document).on('click', '.ajax-post', function (e) {						   
		e.preventDefault();
		
        var target,query,form;
        var target_form = $(this).attr('target-form');
        var that = this;
        var nead_confirm=false;
        if( ($(this).attr('type')=='submit') || (target = $(this).attr('href')) || (target = $(this).attr('url')) ){
			
			target = $(this).attr('url');
            form = $('.'+target_form);
            if ($(this).attr('hide-data') === 'true'){//无数据时也可以使用的功能
            	form = $('.hide-data');
            	query = form.serialize();
            }else if (form.get(0)==undefined){
				
			
            	return false;
            }else if ( form.get(0).nodeName=='FORM' ){
                if ( $(this).hasClass('confirm') ) {
                    if(!confirm('确认要执行该操作吗?')){
                        return false;
                    }
                }
                if($(this).attr('url') !== undefined){
                	target = $(this).attr('url');
                }else{
                	target = form.get(0).action;
                }
                query = form.serialize();
            }else if( form.get(0).nodeName=='INPUT' || form.get(0).nodeName=='SELECT' || form.get(0).nodeName=='TEXTAREA') {
                form.each(function(k,v){
                    if(v.type=='checkbox' && v.checked==true){
                        nead_confirm = true;
                    }
                })
				
                if ( nead_confirm && $(this).hasClass('confirm') ) {
                    if(!confirm('确认要执行该操作吗?')){
                        return false;
                    }
                }
                query = form.serialize();
            }else{
                if ( $(this).hasClass('confirm') ) {
                    if(!confirm('确认要执行该操作吗?')){
                        return false;
                    }
                }
                query = form.find('input,select,textarea').serialize();
            }
			
					 
            $(that).addClass('disabled').attr('autocomplete','off').prop('disabled',true);
            $.post(target,query).success(function(data){								  
                if (data.status==1) {
					if (data.url) {
						Notify(data.info + '页面即将自动跳转~', 'center-center', '5000', 'success', 'fa-check', true);
                    }else{
                        Notify(data.info, 'center-center', '1000', 'success', 'fa-check', true);
                    }
					
					
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else if( $(that).hasClass('no-refresh')){
                            $(that).removeClass('disabled').prop('disabled',false);
                        }else{
					
							$(that).removeClass('disabled').prop('disabled',false);
                            location.reload();
							
                        }
                    },1500);
                }else{
					
					Notify(data.info, 'center-center', '1000', 'darkorange', 'fa-times', true);
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else{
                            $(that).removeClass('disabled').prop('disabled',false);
                        }
                    },1500);
                }
            });
        }
        return false;
    });
    /**
     * ajax-post
     * 将链接转换为ajax请求，并交给handleAjax处理
     * 参数：
     * data-confirm：如果存在，则点击后发出提示。
     * 示例：<a href="xxx" class="ajax-post">Test</a>
     */
    $(document).on('click', '.ajax-post-alert', function (e) {
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
        var loadingmsg=$(this).attr('loadingmsg');


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
            if(loadingmsg){ 
                 $(".reloadverify").click();
            }
            $('[type=submit]', form).removeClass('disabled');
        });

        //返回
        return false;
    });
	

    // 独立域表单获取焦点样式
    $(".text").focus(function(){
        $(this).addClass("focus");
    }).blur(function(){
        $(this).removeClass('focus');
    });
    $("textarea").focus(function(){
        $(this).closest(".textarea").addClass("focus");
    }).blur(function(){
        $(this).closest(".textarea").removeClass("focus");
    });
});

function is_login() {
    return parseInt(MID);
}

function hdl_lazyload(obj){
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


/**
 * 将所有的消息设为已读
 */
function setAllReaded() {
    $.post(U('Usercenter/Public/setAllMessageReaded'), function () {
        $hint_count.text(0);
		$(".dropdown-messages li").not(".dropdown-footer").remove();
		$(".dropdown-messages li.dropdown-footer").after('<li class="nomessage">暂无任何消息!</div>');
        $nav_bandage_count.hide();
        $nav_bandage_count.text(0);

    });
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
        Notify(text, 'center-center', '1000', 'success', 'fa-check', true);
    },
    /**
     * 失败提示
     * @param text 内容
     * @param title 标题
     */
    error: function (text, title) {
        Notify(text, 'center-center', '1000', 'danger', 'fa-times', true);
    },
    /**
     * 信息提示
     * @param text 内容
     * @param title 标题
     */
    info: function (text, title) {
        Notify(text, 'center-center', '1000', 'info', 'fa-envelope', true);
    },
    /**
     * 警告提示
     * @param text 内容
     * @param title 标题
     */
    warning: function (text, title) {
        Notify(text, 'center-center', '1000', 'warning', 'fa-warning', true);
    }
}

/**
 * 绑定登出事件
 */
$('[event-node=officiallogout]').click(function () {
	$.get(U('Official/Usercenter/officiallogout'), function (msg) {
		 toast.success(msg.message + '。', '温馨提示');
		setTimeout(function () {
			location.href = msg.url;
		}, 1500);
	}, 'json')
});
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

	//弹出提示消息Top Full Width 
	if (a.status) {
		Notify(a.info, 'center-center', '1000', 'success', 'fa-check', true);
	} else {
		Notify(a.info, 'center-center', '1000', 'darkorange', 'fa-times', true);
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
//获取光标位置函数
function getCursortPosition(ctrl) {

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
//设置光标位置函数
function setCaretPosition(ctrl, pos) {
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
//导航高亮
function highlight_subnav(url){
    $('.side-sub-menu').find('a[href="'+url+'"]').closest('li').addClass('current');
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


/*Loading*/
$(window)
    

/*Account Area --> Setting Button*/

/*Handles Popovers*/
var popovers = $('[data-toggle=popover]');
$.each(popovers, function () {
    $(this)
        .popover({
            html: true,
            template: '<div class="popover ' + $(this)
                .data("class") +
                '"><div class="arrow"></div><h3 class="popover-title ' +
                $(this)
                .data("titleclass") + '">Popover right</h3><div class="popover-content"></div></div>'
        });
});

var hoverpopovers = $('[data-toggle=popover-hover]');
$.each(hoverpopovers, function () {
    $(this)
        .popover({
            html: true,
            template: '<div class="popover ' + $(this)
                .data("class") +
                '"><div class="arrow"></div><h3 class="popover-title ' +
                $(this)
                .data("titleclass") + '">Popover right</h3><div class="popover-content"></div></div>',
            trigger: "hover"
        });
});


/*Handles ToolTips*/
$("[data-toggle=tooltip]")
    .tooltip({
        html: true
    });




/* Scroll To */
function scrollTo(el, offeset) {
    var pos = (el && el.size() > 0) ? el.offset().top : 0;
    jQuery('html,body').animate({ scrollTop: pos + (offeset ? offeset : 0) }, 'slow');
}

/*Show Notification*/
function Notify(message, position, timeout, theme, icon, closable) {
    toastr.options.positionClass = 'toast-' + position;
    toastr.options.extendedTimeOut = 0; //1000;
    toastr.options.timeOut = timeout;
    toastr.options.closeButton = closable;
    toastr.options.iconClass = icon + ' toast-' + theme;
    toastr['custom'](message);
}




//Switch Classes Function
function switchClasses(firstClass, secondClass) {

    var firstclasses = document.getElementsByClassName(firstClass);

    for (i = firstclasses.length - 1; i >= 0; i--) {
        if (!hasClass(firstclasses[i], 'dropdown-menu')) {
            addClass(firstclasses[i], firstClass + '-temp');
            removeClass(firstclasses[i], firstClass);
        }
    }

    var secondclasses = document.getElementsByClassName(secondClass);

    for (i = secondclasses.length - 1; i >= 0; i--) {
        if (!hasClass(secondclasses[i], 'dropdown-menu')) {
            addClass(secondclasses[i], firstClass);
            removeClass(secondclasses[i], secondClass);
        }
    }

    tempClasses = document.getElementsByClassName(firstClass + '-temp');

    for (i = tempClasses.length - 1; i >= 0; i--) {
        if (!hasClass(tempClasses[i], 'dropdown-menu')) {
            addClass(tempClasses[i], secondClass);
            removeClass(tempClasses[i], firstClass + '-temp');
        }
    }
}


//Add Classes Function
function addClass(elem, cls) {
    var oldCls = elem.className;
    if (oldCls) {
        oldCls += " ";
    }
    elem.className = oldCls + cls;
}

//Remove Classes Function
function removeClass(elem, cls) {
    var str = " " + elem.className + " ";
    elem.className = str.replace(" " + cls, "").replace(/^\s+/g, "").replace(/\s+$/g, "");
}

//Has Classes Function
function hasClass(elem, cls) {
    var str = " " + elem.className + " ";
    var testCls = " " + cls + " ";
    return (str.indexOf(testCls) != -1);
}





