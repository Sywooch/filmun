jQuery(function(){

    var content;
    var options;

    $.fn.viewDetail = function(params){
        options = $.extend({
            viewSelector: '#detail-view',
            offsetTop: 10
        }, params);

        content = $(options.viewSelector);
        var that = $(this);

        that.on('click', function(event) {
            if($(event.target).closest('.toggle-wanted').length) {
                return true;
            }
            that.removeClass('active');
            $(this).addClass('active');

            var film_id = $(this).closest(that).data('key');

            viewFilm(film_id);
        })
    }

})