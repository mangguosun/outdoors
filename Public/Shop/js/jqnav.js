$(document).ready(function(){
  maphover();
});
function maphover(){
  var self = "";
  $(".city").hover(
	function(){
	  self = $(this);
	  self.addClass("hover").children("div").show();
	},
	function(){
	  self = $(this);
	  self.children("div").hide();
	  self.removeClass("hover");
	}
  );
  $(".city").click(
	function(){
	  self = $(this);
	  self.addClass("hover").children("div").show();
	},
	function(){
	  self = $(this);
	  self.children("div").hide();
  	  self.removeClass("hover");
	}
  );
};
$(document).ready(function(){
  $(".slider .bd li").first().before($(".slider .bd li").last());
  $(".slider").hover(function(){
	$(this).find(".arrow").stop(true,true).fadeIn(300)
  },function(){
	$(this).find(".arrow").fadeOut(300)
  });
  $(".slider").slide({
	titCell:".hd ul", 
	mainCell:".bd ul", 
	effect:"leftLoop",
	autoPlay:true, 
	vis:3,
	autoPage:true, 
	trigger:"click"
  });
  $(".go_top").click(function(){
    $("html, body").animate({scrollTop:0},"show");
  });
  $(".head_city").click(function(){
    $(".map_box").css("display","block")
  })
  $(".map_close").click(function(){
    $(".map_box").css("display","none")
  })
  $(window).scroll(function() {
    if($(window).scrollTop()>=100){
	  $(".go_top").css("display","block")
    }else{
      $(".go_top").css("display","none")
    }
    if($(window).scrollTop()>=120){
	  $(".search_top").css("height","50px")
    }else{
      $(".search_top").css("height","0px")
    }
  });
  $(".right_li_1").click(function(){
    $("#right_box_1").show()
	$("#right_box_2").hide()
	$("#right_box_3").hide()
	$("#right_box_4").hide()
  })
  $("#right_none_1").click(function(){
    $("#right_box_1").hide()
  })
  $(".right_li_2").click(function(){
    $("#right_box_2").show()
	$("#right_box_1").hide()
	$("#right_box_3").hide()
	$("#right_box_4").hide()
  })
  $("#right_none_2").click(function(){
    $("#right_box_2").hide()
  })
  $(".right_li_3").click(function(){
    $("#right_box_3").show()
	$("#right_box_2").hide()
	$("#right_box_1").hide()
	$("#right_box_4").hide()
  })
  $("#right_none_3").click(function(){
    $("#right_box_3").hide()
  })
  $(".right_li_4").click(function(){
    $("#right_box_4").show()
	$("#right_box_2").hide()
	$("#right_box_3").hide()
	$("#right_box_1").hide()
  })
  $("#right_none_4").click(function(){
    $("#right_box_4").hide()
  })
/*  $("html").niceScroll({
    touchbehavior:false,cursorcolor:"#09f",cursoropacitymax:1,cursorwidth:6,horizrailenabled:true,cursorborderradius:3,autohidemode:true,background:'none',cursorborder:'none'
  });*/
  $(".items ul").niceScroll({
    touchbehavior:false,cursorcolor:"#666",cursoropacitymax:1,cursorwidth:4,horizrailenabled:true,cursorborderradius:2,autohidemode:true,background:'none',cursorborder:'none'
  });
  $(".sku_box").niceScroll({
	cursorcolor:"#ea4848", cursoropacitymax:1,cursoropacitymin:1 , touchbehavior:false,  cursorwidth:4, cursorborder:"0", cursorborderradius:2 ,background:"#FFECF5"
  }); 
  $('.item_class_r ul li').click(function(){
	var Index = $(this).index();
	  $(this).addClass('item_class_li').siblings().removeClass('item_class_li');
   });
   $('.item_con_r1_ul li').click(function(){
	var Index = $(this).index();
	  $(this).addClass('item_con_li').siblings().removeClass('item_con_li');
   });
   $('.item_page a').click(function(){
	var Index = $(this).index();
	  $(this).addClass('item_page_a').siblings().removeClass('item_page_a');
   });
   $('.ser_top li').click(function(){
	var Index = $(this).index();
	  $(this).addClass('ser_top_li').siblings().removeClass('ser_top_li');
   });
   $(".item_li_1").click(function(){
     $(".item_con_i1").css("display","block")
	 $(".item_con_i2").css("display","none")
	 $(".item_con_i3").css("display","none")
   })
   $(".item_li_2").click(function(){
     $(".item_con_i2").css("display","block")
	 $(".item_con_i1").css("display","none")
	 $(".item_con_i3").css("display","none")
   })
   $(".item_li_3").click(function(){
     $(".item_con_i3").css("display","block")
	 $(".item_con_i2").css("display","none")
	 $(".item_con_i1").css("display","none")
   })
   $(".item_b1").click(function(){
     $(".item_eva_b1").css("display","block")
	 $(".item_eva_b2").css("display","none")
	 $(".item_eva_b3").css("display","none")
	 $(".item_eva_b4").css("display","none")
   })
   $(".item_b2").click(function(){
     $(".item_eva_b2").css("display","block")
	 $(".item_eva_b1").css("display","none")
	 $(".item_eva_b3").css("display","none")
	 $(".item_eva_b4").css("display","none")
   })
   $(".item_b3").click(function(){
     $(".item_eva_b3").css("display","block")
	 $(".item_eva_b2").css("display","none")
	 $(".item_eva_b1").css("display","none")
	 $(".item_eva_b4").css("display","none")
   })
   $(".item_b4").click(function(){
     $(".item_eva_b4").css("display","block")
	 $(".item_eva_b2").css("display","none")
	 $(".item_eva_b3").css("display","none")
	 $(".item_eva_b1").css("display","none")
   })
   
  //login
  $(".login_jz span").click(function(){
	if (document.getElementById('jz').checked == true) {
	  $('input').attr('checked',false); 
	  $(".login_jz em").css("display","none")                          
	}else{
	  $('input').attr('checked',true);
	  $(".login_jz em").css("display","block")  
	}
  })
  $("#jz").click(function(){
	if (document.getElementById('jz').checked == true) {
	  $(".login_jz em").css("display","block")                          
	}else{
	  $(".login_jz em").css("display","none")  
	}
  })
});
