$(document).ready(function(){

	$(document).on('click', '.btn__more', function(){

		var targetContainer = $(this).parent("div"),
			url =  $(this).data('more-url'),
			self = this;

		if (url !== undefined) {
			$.ajax({
				type: 'GET',
				url: url,
				dataType: 'html',
				success: function(elements){
					$(self).remove();

					targetContainer.append(elements);

				}
			})
		}

	});

	var scroll_flag = true;
	$(window).scroll(function() {
		var more_btn = $(".btn__more");

		if($(more_btn).length && scroll_flag)
		{
			var targetContainer = $(more_btn).parent("div"),
				url =  $(more_btn).data('more-url');

			if(!$(more_btn).is(':visible') && url && $(targetContainer).length)
			{
				if($(window).scrollTop() + $(window).innerHeight() >= $(targetContainer)[0].scrollHeight) {
					scroll_flag = false;

					$.ajax({
						type: 'GET',
						url: url,
						dataType: 'html',
						success: function(elements){
							$(more_btn).remove();

							targetContainer.append(elements);

							scroll_flag = true;
						}
					})
				}
			}
		}
	});

});