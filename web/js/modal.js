jQuery(function(){
    var modals = [];
    var counter = 1;
    var blockOptions = {
        message: '<i><i class="fa fa-spinner fa-spin fa-fw"></i> Загрузка...',
        css: {
            top: '50%',
            position: 'fixed',
            border: '0',
            padding: '0',
            backgroundColor: 'none',
            'font-size': '18px'
        },
        overlayCSS: {
            backgroundColor: '#555',
            opacity: 0.3,
            cursor: 'wait'
        }
    }

    var modal = function(url){
        var index = counter++;

        url += ((url.indexOf('?') > -1) ? '&' : '?') + 'index=' + index;
        var element = $('<div>')
            .addClass('modal fade modal-scroll')
            .on('hidden', function(){
                element.remove();
                modals.pop();
            })
            .appendTo('html');

        $(element).on('submit', 'form', function(){
            that.request($(this).prop('action'), {
                type: $(this).prop('method'),
                data: $(this).serialize()
            });
            return false;
        });

        $(element).on('click', 'a', function(event){
            var href = $(this).attr('href');
            if(href == 'javascript:;') {
                return;
            }
            if(href.substr(0, 1) == '#') {
                return;
            }
            if($(this).data('modal') == '0') {
                return;
            }
            if($(this).data('pjax') == '0') {
                return;
            }
            that.request($(this).prop('href'));
            event.preventDefault();
            return false;
        });

        var callback = function(){};
        var that = {
            parent: null,
            success: function(func){
                callback = func;
                return that;
            },
            request: function(url, options){
                that.block();

                $.ajax(url, options, function(json){
                    that.unblock();
                    if(json.success) {
                        callback(json, that.parent);
                    }
                    if(json.content) {
                        $('.select2-hidden-accessible', element).select2("destroy");
                        element.html(json.content);
                    } else {
                        element.modal('hide');
                    }
                });
                return that;
            },
            callback: function(json) {
                callback(json, that.parent);
                return that;
            },
            close: function() {
                element.modal('hide');
                return that;
            },
            refresh: function() {
                return that.request(url);
            },
            block: function() {
                $('.modal-dialog', element).block(blockOptions);
            },
            unblock: function(){
                $('.modal-dialog', element).unblock();
            }
        }

        element.data('instance', that);

        $.blockUI(blockOptions);
        $.get(url, function(json){
            $.unblockUI();
            if(json.success) {
                callback(json, that.parent);
            }
            if(json.content) {
                element.html(json.content).modal({replace: false, width: json.width || 500, modalOverflow: true});
            } else {
                element.modal('hide');
            }
        });
        return that;
    };

    $.modal = function(query, params) {
        var end = modals.length ? modals[modals.length - 1] : null;
        if(query == undefined) {
            return end;
        } else {
            var object = new modal(url(query, params || null));
            object.parent = end;
            modals.push(object);
            return object;
        }
    }
})