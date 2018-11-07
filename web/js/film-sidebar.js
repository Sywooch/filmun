jQuery(function(){

    var content = $('.film-sidebar');
    var loading = false;

    var blockOptions = {
        message: '<i><i class="fa fa-spinner fa-spin fa-fw"></i> Загрузка...',
        css: {
            top: '10%',
            border: '0',
            padding: '0',
            backgroundColor: 'none',
            'font-size': '16px'
        },
        overlayCSS: {
            backgroundColor: '#fff',
            opacity: 0.7,
            cursor: 'wait'
        }
    }

    window.viewFilm = function(id){
        if(loading) {
            return false;
        }
        $('.film-sidebar').animate({right: 0}, 300);

        $('.film-sidebar-wrap').show();
        $('body').css({overflow: 'hidden'});
        loadView(id);
    }

    $(document).on('click', '.film-sidebar-wrap', function(){
        if(loading) {
            return false;
        }
        $('.film-sidebar').animate({right:-800}, 300);
        $('.film-sidebar-wrap').hide();
        $('body').css({overflow: 'auto'});
    });

    var touchStartX = 0;
    var touchEndX = 0;

    $('.film-sidebar').on('touchstart', function(event){
        var touches = event.originalEvent.changedTouches[0];

        touchStartX = touches.screenX;
    })

    $('.film-sidebar').on('touchmove', function(event){
        var touches = event.originalEvent.changedTouches[0];

        touchEndX = touches.screenX;

        if(touchEndX < touchStartX) {
            return false;
        }

        $('.film-sidebar').css({right: touchStartX - touchEndX});
    })

    $('.film-sidebar').on('touchend', function(event){
        var width = $('.film-sidebar').width();

        var touches = event.originalEvent.changedTouches[0];

        touchEndX = touches.screenX;

        if(touchEndX - touchStartX > width / 4) {
            $('.film-sidebar').animate({right: -800}, 300);
            $('.film-sidebar-wrap').hide();
            $('body').css({overflow: 'auto'});
        } else {
            $('.film-sidebar').animate({right: 0}, 300);
        }
    })

    var loadView = function(film_id){
        /*if($('.toggle-wanted').is('hover')) {
            return false;
        }*/
        loading = true;
        content.block(blockOptions);

        $('body').css({cursor: 'wait'});
        $.get('film/inline-view', {id: film_id}, function(response){
            loading = false;
            $('body').css({cursor: 'auto'});
            content.html(response).unblock();
        })
    }

})