$(document).ready(function() {
	// 引入页头
	$('body').prepend('<div data-item=header></div>');
	$.get('./base-header-item.html').then(data => $('[data-item=header]').html(data));
	// 引入侧边栏
	$('body').append('<div data-item=sidebar></div>');
	$.get('./base-index-sidebar-item.html').then(data => $('[data-item=sidebar]').html(data));
	// 引入页脚
	$('body').append('<div data-item=footer></div>');
	$.get('./base-footer-item.html').then(data => $('[data-item=footer]').html(data));
	//引入个人中心导航
	$('.u-main').prepend('<div data-item=nav></div>');
	$.get('./base-ucenter-nav-item.html').then(res=> $('[data-item=nav]').html(res));

});

