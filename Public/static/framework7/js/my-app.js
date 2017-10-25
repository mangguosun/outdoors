// Initialize your app
var myApp = new Framework7({
	//pushState:true,
	swipePanel:'left',
	//init: false //Disable App's automatica initialization				   
});

// Export selectors engine
var $$ = Dom7;


myApp.onPageInit('home_index', function (page) {
  //Do something here with home page
});

// Add view
var mainView = myApp.addView('.view-main', {
    // Because we use fixed-through navbar we can enable dynamic navbar
    dynamicNavbar: true,
	
	
});

// 初始化幻灯片，并把它的实例存储在mySlider变量中
var mySlider = myApp.slider('.slider-container', {
	pagination:'.slider-pagination',
	swipeBackPage:true,
	speed: 400,
	spaceBetween: 100
	
	
});
myApp.onPageInit('home_index', function (page) {
									
});


// Callbacks to run specific code for specific pages, for example for About page:
myApp.onPageBeforeInit('event_detail', function (page) {
			
	var event_id = $$('.event_id').attr('event_id');
	//报名								   
	$$('.event_sign_btn').on('click', function (e) {
		if(MID=="0"){								 
			alert('请登录后报名。');
			return false;
		}else{
			var bloodtype = $$('#bloodtype').val();
			if(bloodtype){
				
				var order_arr=bloodtype.split("_");
				var event_id = event_id;
				var schedule_id = order_arr[0];
				var ordertype = order_arr[1];
				if(!event_id || !schedule_id || !schedule_id){
					alert('参数错误，请联系管理员。');
					return false;
				}else{
					var url = "{:U('Mobile/Config/sign/event_id/"+event_id+"/schedule_id/"+schedule_id+"/ordertype/"+ordertype+"')}";
					location.href = url;
					return false;
				}
			}else{
				alert('请选择报名时间。');
				return false;
			}
		}
	});								   
	//收藏
	$$('.d_detail').on('click', function (e) {		
		var event_id = event_id;
		if(MID=="0"){
			alert('请在登录后再收藏');
			return false;
		}else{
			
			alert('执行收藏');
			/*$.post("{:U('Mobile/Event/dodetail')}", {id: event_id}, function (res) {
			 if(res=='1'){
			   toast.error('你已收藏过。');
			 }else{
			   toast.success('收藏成功。');
			 }
			}, 'json');*/
		}
	});
	$$('#get_content img').each(function() {								
		 var maxWidth = $$('#get_content').width(); // 图片最大宽度
		 var ratio = 0;  // 缩放比例   
		 var width = $$(this).width();    // 图片实际宽度   
		 var height = $$(this).height();  // 图片实际高度
		 // 检查图片是否超宽   
		 if(width > maxWidth){  
			 ratio = maxWidth / width;   // 计算缩放比例   
			 $$(this).css("width", maxWidth); // 设定实际显示宽度   
			 height = height * ratio;    // 计算等比例缩放后的高度    
			 $$(this).css("height", height);  // 设定等比例缩放后的高度   
		 }  
 	});
    // run createContentPage func after link was clicked
    $$('.mobile_moredetail').on('click', function () {
		$$(".detail_cont_contnet_ni").append($$(".detail_cont_title_content .detail_cont_title:first-child"));
		$$(".detail_cont_contnet_ni").append($$(".detail_cont_title_content .detail_cont_contnet:first-child"));	
       // createContentPage();
    });
});



// Generate dynamic page
var dynamicPageIndex = 0;
function createContentPage() {
	mainView.router.loadContent(
        '<!-- Top Navbar-->' +
        '<div class="navbar">' +
        '  <div class="navbar-inner">' +
        '    <div class="left"><a href="#" class="back link"><i class="icon icon-back"></i><span>Back</span></a></div>' +
        '    <div class="center sliding">Dynamic Page ' + (++dynamicPageIndex) + '</div>' +
        '  </div>' +
        '</div>' +
        '<div class="pages">' +
        '  <!-- Page, data-page contains page name-->' +
        '  <div data-page="dynamic-pages" class="page">' +
        '    <!-- Scrollable page content-->' +
        '    <div class="page-content">' +
        '      <div class="content-block">' +
        '        <div class="content-block-inner">' +
        '          <p>Here is a dynamic page created on ' + new Date() + ' !</p>' +
        '          <p>Go <a href="#" class="back">back</a> or go to <a href="services.html">Services</a>.</p>' +
        '        </div>' +
        '      </div>' +
        '    </div>' +
        '  </div>' +
        '</div>'
    );
	return;
}