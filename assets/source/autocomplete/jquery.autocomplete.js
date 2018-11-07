jQuery(function(){

    $.widget("custom.autocomplete", $.ui.autocomplete, {
        options: {
            removeIfInvalid: false,
            keyElement: null
        },
        keyElement: null,
        _create: function() {
            this._super();
            this.widget().menu( "option", "items", "> :not(.ui-autocomplete-category)" );
            this.keyElement = $(this.options.keyElement);
            this._on(this.element, {
                autocompletechange: this._update,
                autocompleteselect: this._update
            })
        },
        _update: function(event, ui){
            if (this.options.removeIfInvalid && !ui.item) {
                this.element.val("");
                this.term = "";
            }
            if(this.keyElement) {
                this.keyElement.val(ui.item ? ui.item.id : "");
            }
        },
        _renderMenu: function( ul, items ) {
            var that = this,
                currentCategory = "";
            $.each( items, function( index, item ) {
                var li;
                if ( item.category && item.category != currentCategory ) {
                    ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
                    currentCategory = item.category;
                }
                li = that._renderItemData( ul, item );
                if ( item.category ) {
                    li.attr( "aria-label", item.category + " : " + item.label );
                }
            });
        },
        _renderItem: function( ul, item ) {
            var li = $( "<li>" );
            if(item.icon)
                li.append($('<img>').prop('src', item.icon).addClass('ui-autocomplete-icon'));
            li.append(item.label);
            if(item.hint)
                li.append('<span class="ui-autocomplete-hint">' + item.hint + '</span>');
            return li.appendTo(ul);
        }
    });

})