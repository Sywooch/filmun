var FilmList = function(){
    var config = {
        max_year: null
    };

    var findTag = function(name){
        var tag = null;
        $('.filter-tag').each(function(){
            if($(this).data('name') == name) {
                tag = $(this);
            }
        })
        return tag;
    }

    var addTag = function(label, name, value) {
        var tag = findTag(name);
        if(tag === null) {
            tag = $('<div class="filter-tag">');
            tag.data('name', name);
            tag.append($('<span>'));
            tag.append($('<input type="hidden">').prop('name', name));

            $('#active-filters').append(tag);
        }
        if($.isNumeric(value) && value < 0) {
            tag.addClass('filter-tag-without');
        } else {
            tag.removeClass('filter-tag-without');
        }
        $('input', tag).val(value);
        $('span', tag).text(label);
        $(tag).prop('title', label);

        return tag;
    }

    var removeTag = function(name){
        var tag = findTag(name);
        if(tag !== null) {
            tag.remove();
        }
    }

    var initActorSelect = function(){
        $('#actor_id').select2({
            theme: 'bootstrap',
            placeholder: '+ Актер',
            ajax: {
                url: 'person/auto-complete',
                cache: true,
                delay: 250,
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: { more: (params.page * 10) < data.total_count }
                    };
                }
            },
            templateResult: function(result, option){
                return selectTemplate(option, 'actor_id[' + result.id + ']', result.text);
            }
        });

        $('#actor_id').on('select2:selecting', function (evt) {
            selectEvt('actor_id[{n}]', evt);
            return false;
        });
    }

    var initDirectorSelect = function(){
        $('#director_id').select2({
            theme: 'bootstrap',
            placeholder: '+ Режиссер',
            ajax: {
                url: 'person/auto-complete',
                cache: true,
                delay: 250,
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: { more: (params.page * 10) < data.total_count }
                    };
                }
            },
            templateResult: function(result, option){
                return selectTemplate(option, 'director_id[' + result.id + ']', result.text);
            }
        });

        $('#director_id').on('select2:selecting', function (evt) {
            selectEvt('director_id[{n}]', evt);
            return false;
        });
    }

    var selectEvt = function(template, evt){
        var args = evt.params.args;
        var option = $(args.originalEvent.currentTarget);
        var without_it = $(args.originalEvent.target).closest('.select-without').is('.select-without');
        var is_checked = option.hasClass('select2-checked');
        var value = without_it ? -args.data.id : args.data.id;
        var name = template.replace(/\{n\}/g, Math.abs(value));
        if(is_checked && (without_it == option.hasClass('select2-checked-without'))) {
            removeTag(name);
            option.removeClass('select2-checked');
            option.removeClass('select2-checked-without');
        } else {
            addTag(args.data.text, name, value);
            option.addClass('select2-checked');
            if(without_it) {
                option.addClass('select2-checked-without');
            } else {
                option.removeClass('select2-checked-without');
            }
        }
    }

    var selectTemplate = function(option, name, label){
        var tag = findTag(name);
        if(tag !== null) {
            $(option).addClass('select2-checked');
            if(tag.hasClass('filter-tag-without')) {
                $(option).addClass('select2-checked-without');
            }
        }
        return $('<div>').text(label).append('<div class="pull-right select-without"><i class="fa fa-minus"></i></div>');
    }

    var initGenreSelect = function(){
        $('#genre_id').select2({
            theme: 'bootstrap',
            placeholder: '+ Жанр',
            templateResult: function(result, option){
                return selectTemplate(option, 'genre_id[' + result.id + ']', result.text);
            }
        });

        $('#genre_id').on('select2:selecting', function (evt) {
            selectEvt('genre_id[{n}]', evt);
            return false;
        });
    }

    var initCountrySelect = function(){
        $('#country_id').select2({
            theme: 'bootstrap',
            placeholder: '+ Страна',
            templateResult: function(result, option){
                return selectTemplate(option, 'country_id[' + result.id + ']', result.text);
            }
        });

        $('#country_id').on('select2:selecting', function (evt) {
            selectEvt('country_id[{n}]', evt);
            return false;
        });
    }

    var initYearRange = function(){
        $('#year').ionRangeSlider({
            type: 'double',
            min: 1939,
            max: config.max_year,
            grid: true,
            grid_num: 8,
            min_interval: 1,
            prettify: function (num) {
                switch(num) {
                    case 1939: return 'раньше';
                    case config.max_year: return 'скоро';
                    default: return num + ' год';
                }
            },
            onChange: function(data){
                var label = [];
                if(data.from > data.min) {
                    label.push('с ' + data.from);
                }
                if(data.to < data.max) {
                    label.push('по ' + data.to);
                }
                if(label.length) {
                    var prefix = data.to < config.max_year ? 'год' : 'года';
                    addTag(label.join(' ') + ' ' + prefix, 'year', data.from + ';' + data.to).on('click', function(){
                        $(data.slider.context).data("ionRangeSlider").update({from: data.min, to: data.max});
                    });
                } else {
                    removeTag('year');
                }
            }
        }).data("ionRangeSlider").callOnChange();
    }

    var initMarkSliders = function(){
        var change = function(label, name, data){
            var pieces = [];
            if(data.from > data.min) {
                pieces.push('от ' + data.from);
            }
            if(data.to < data.max) {
                pieces.push('до ' + data.to);
            }
            if(pieces.length) {
                addTag(label + ' ' + pieces.join(' '), name, data.from + ';' + data.to).on('click', function(){
                    $(data.slider.context).data("ionRangeSlider").update({from: data.min, to: data.max});
                });
            } else {
                removeTag(name);
            }
        }

        $('#imdb_mark').ionRangeSlider({
            type: 'double',
            min: 1,
            max: 10,
            step: 0.1,
            grid: true,
            grid_num: 9,
            min_interval: 1,
            hide_from_to: true,
            hide_min_max: true,
            onChange: function(data){ change('IMDb', 'imdb_mark', data); }
        }).data("ionRangeSlider").callOnChange();
        $('#kp_mark').ionRangeSlider({
            type: 'double',
            min: 1,
            max: 10,
            step: 0.1,
            grid: true,
            grid_num: 9,
            min_interval: 1,
            hide_from_to: true,
            hide_min_max: true,
            onChange: function(data){ change('КиноПоиск', 'kp_mark', data); }
        }).data("ionRangeSlider").callOnChange();
        $('#min_votes').ionRangeSlider({
            min: 0,
            max: 5000,
            step: 100,
            grid: true,
            grid_num: 10,
            min_interval: 1,
            hide_from_to: true,
            hide_min_max: true,
            onChange: function(data){
                if(data.from) {
                    addTag('Мин. оценок ' + data.from, 'min_votes', data.from).on('click', function(){
                        $(data.slider.context).data("ionRangeSlider").update({from: data.min});
                    });
                } else {
                    removeTag('min_votes');
                }

            }
        }).data("ionRangeSlider").callOnChange();
        $('#critic_rating').ionRangeSlider({
            type: 'double',
            grid_num: 10,
            min: 0,
            max: 100,
            grid: true,
            min_interval: 1,
            hide_from_to: true,
            hide_min_max: true,
            onChange: function(data){ change('Критики', 'critic_rating', data); }
        }).data("ionRangeSlider").callOnChange();
        $('#user_review_rating').ionRangeSlider({
            type: 'double',
            grid_num: 10,
            min: 0,
            max: 100,
            grid: true,
            min_interval: 1,
            hide_from_to: true,
            hide_min_max: true,
            onChange: function(data){ change('Рецензии', 'user_review_rating', data); }
        }).data("ionRangeSlider").callOnChange();

        $('.mark-tabs a').on('click', function(){
            $('.mark-tabs a').removeClass('active');
            $(this).addClass('active');
            var id = $(this).attr('href');
            $('.mark-tab').hide();
            $(id).show();

            return false;
        })
        $('.mark-tabs a:first').click();
    }

    var init = function(params) {
        config = $.extend(config, params);

        $('.fancybox').fancybox({
            helpers: { overlay: {locked: false} }
        });

        initActorSelect();
        initDirectorSelect();
        initYearRange();
        initGenreSelect();
        initMarkSliders();
        initCountrySelect();

        $('#is_series').select2({
            theme: 'bootstrap',
            minimumResultsForSearch: Infinity,
            placeholder: 'Что',
            allowClear: true
        });

        $('#max_quality').select2({
            theme: 'bootstrap',
            minimumResultsForSearch: Infinity,
            placeholder: 'Качество',
            allowClear: true,
            closeOnSelect: true
        });
        $('#max_quality').on('select2:selecting', function (evt) {
            selectEvt('max_quality', evt);
        });

        $('#active-filters').on('click', '.filter-tag', function(){
            $(this).remove();
        })

        $(document).on('mouseover', '.select2-results__option .select-without', function(){
            $(this).closest('.select2-results__option').addClass('select2-hover-without');
        })

        $(document).on('mouseout', '.select2-results__option .select-without', function(){
            $(this).closest('.select2-results__option').removeClass('select2-hover-without');
        })


        var top = $('.search-info').position().top;
        $(document).scroll(function(){
            if($(document).scrollTop() > top) {
                $('.search-info').css({top: $(document).scrollTop() - top, position: 'relative'}).addClass('stick');
            } else {
                $('.search-info').css({top: 0, position: 'static'}).removeClass('stick');
            }
        }).scroll();

        $('.film-list').on('click', function(event) {
            if($(event.target).closest('.toggle-wanted').length) {
                return true;
            }

            $('.film-list').removeClass('active');
            $(this).addClass('active');

            viewFilm($(this).data('key'));
        })

        $(document).scroll();
    }

    return {
        init: init
    }
}()
