/* This function is used for add the current class on current and clicked anchor */

jQuery( document ).ready( function() {
	jQuery('ul.breadcrumb-for-frontend li:last-child a').addClass('current');
	jQuery('ul.breadcrumb-for-frontend li a').click(function() {
		jQuery('ul.breadcrumb-for-frontend li a').removeClass("current");
		jQuery(this).addClass("current");
	} );
} );
