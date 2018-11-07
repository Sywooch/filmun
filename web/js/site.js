jQuery(function(){

    $('.scroll-top').on('click', function(){
        $('html, body').animate({scrollTop: 0},500);
    })

    $(document).on('scroll', function(){
        var maxTop = Math.round($(window).height() / 2);
        if($(document).scrollTop() > maxTop) {
            $('.scroll-top').show();
        } else {
            $('.scroll-top').hide();
        }
    }).scroll();

    $(document).on('click', '.toggle-wanted', function(event){
        var wanted = $(this);
        if(wanted.hasClass('loading')) {
            event.stopPropagation();
            return false;
        }
        var film_id = $(this).data('key');
        var mode = wanted.hasClass('added') ? 'remove' : 'add';
        wanted.addClass('loading');
        $.get('film/toggle-wanted', {id: film_id, mode: mode}, function(json){
            if(json.success) {
                wanted.toggleClass('added');
            }
            wanted.removeClass('loading');
        });
        event.stopPropagation();
        return false;
    })

    $.notify = function(notice) {
        if(!notice) {
            return false;
        }
        switch (notice.type) {
            case 'danger':
            case 'error':
                return toastr.error(notice.message);
            case 'info':
                return toastr.info(notice.message);
            case 'warning':
                return toastr.warning(notice.message);
            case 'success':
                return toastr.success(notice.message);
        }
    }

    $(document).ajaxError(function(event, response) {
        if(response.responseJSON) {
            $.notify({type: 'danger', 'message': response.responseJSON.message});
        }
    });

    $(document).ajaxComplete(function(event, response) {
        if(response.responseJSON) {
            $.notify(response.responseJSON.notice);
        }
    });
})