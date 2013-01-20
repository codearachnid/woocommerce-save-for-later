// WooCommerce: Save For Later
jQuery(document).ready(function($){
	wcsfl_wishlist = $('#wcsfl_wishlist');
	wcsfl_wishlist.find('#wcsfl_header > span').click(function( event ){
		event.preventDefault();
		container = wcsfl_wishlist.find('#wcsfl_products');
		if (container.is( ":visible" )){
			addClass = 'closed';
			removeClass = 'open';
			text = wcsvl.header_hide;
			container.slideUp();
		} else {
			addClass = 'open';
			removeClass = 'closed';
			text = wcsvl.header_show;
			container.slideDown();			 
		}
		$(this).addClass(addClass).removeClass(removeClass).find('span').text( text );
	}).find('span').text( wcsvl.header_show );
	$('.attachment-shop_catalog').hover(function(){
		$(this).next('img.wcsvl-save-for-later').css('visibility', 'visible');
	},function(){
		$(this).next('img.wcsvl-save-for-later').css('visibility', 'hidden');
	});
	$('img.wcsvl-save-for-later').click(function(e){
		e.preventDefault();
		alert('saving');
	});
  
 // call to wishlist genie
 $('.save_for_later').on('click', function(e){
   console.log("TEST:" + wcsvl.test);
   // getting data- key/values for current element
   $dataset = this.dataset;
   // getting form current data
   $form = $(this).parent('form').serialize();
   // setting the data to be send as post
   var data = {
      action: 'into_wishlist',
      dataset: $dataset,
      form: $form
    };
    // calling the post
    $.post(wcsvl.ajaxurl, data, function(response) {
      var is_json = true;
      try{
        response = $.parseJSON( response );
      }catch(err){
        is_json = false;
      }
      if(is_json){
        console.log(response.msg);
      }else{
        console.log("not json");
      }
    });
   return false;
 });

});