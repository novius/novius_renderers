define(['jquery-nos'], function ($nos) {

    function isSpecialKey(keycode) {
        // shift,ctrl,alt... + arrows
        if (keycode >= 16 && keycode <= 40) {
            return true;
        }
        // Enter
        if (keycode == 13) {
            return true;
        }
        return false;
    }

    return function(context, options) {
        var $context = $nos(context);
        $nos(function () {
            $context.on('focus', 'input.autocomplete', function(event) {
                var $this = $nos(this);
                if (typeof $this.attr('auto-initialized') == 'undefined' || !$this.attr('auto-initialized')) {
                    // Callback called when clicking on the list
                    var callback = $this.data('autocomplete-callback') || $this.attr('data-autocomplete-callback') || options.on_click || false;

                    var url = $this.data('autocomplete-url')  || $this.attr('data-autocomplete-url') || null;
                    //data sent by ajax are empty by default and will only contain the input
                    //but it is possible to take account of some sort of a config
                    var post = $this.data('autocomplete-post') || $this.attr('data-autocomplete-post') || options.post || {};
                    if ((typeof post) !== "object" ) {
                        post = {};
                    }

                    // Initialize list of suggestions
                    var $liste = $nos('<ul class="autocomplete-liste"></ul>').hide();
                    $this.after($liste);
                    // Initialize cache
                    var cache = [];

                    //update data when the custom event is triggered (otherwise it's useless)
                    //As any changes on dom attribute OR data will be done manually,
                    // user will always have the opportunity to trigger this custom event
                    $this.on('update_autocomplete.renderer', function(e) {
                        url = $nos(this).data('autocomplete-url');
                        post = $this.data('autocomplete-post') || post;
                    });

                    // function to display autocomplete
                    var print_autocomplete = function(data) {
                        $liste.html('').hide();
                        if ((data != null) && data.length > 0) {
                            for (x in data) {
                                var line = data[x];
                                var $li = $nos('<li>'+line.label+'</li>');
                                if(typeof line.class != 'undefined') {
                                    $li.addClass(line.class);
                                }
                                $li.data('value', line.value)
                                    .bind('click', function(e) {
                                        // Callback optionnel
                                        if (typeof callback === 'string') {
                                            callback = window[callback];
                                        }

                                        if ($nos.isFunction(callback)) {
                                            callback.call(this, {
                                                'root'      : $this,
                                                'value'     : $nos(this).data('value'),
                                                'label'     : $nos(this).html(),
                                                'event'     : e
                                            });
                                        } else {
                                            $this.val($nos(this).data('value')).trigger('focus');
                                            $liste.hide();
                                        }
                                    })
                                    // deal with current hover selection
                                    .mouseenter(function() {
                                        $liste.find('.current').removeClass('current');
                                        $nos(this).addClass('current');
                                    })
                                    .appendTo($liste);
                            }
                            $liste.show();
                        }
                    }

                    // Initialize ajax
                    if (url.length > 0) {
                        var minlen = $this.data('autocomplete-minlength') || $this.attr('autocomplete-minlength') || 3;
                        $this.bind('keydown', function(e) {
                            // Gestion de la navigation au clavier
                            var code = e.keyCode ? e.keyCode : e.which;
                            if (isSpecialKey(code)) {
                                if ($liste.length) {
                                    var $current = $liste.find('.current');
                                    // when using "Enter" key
                                    if (code == 13) {
                                        if ($current.length) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            $current.trigger('click');
                                        }
                                        return false;
                                    }
                                    // "Up" arrow
                                    else if (code == 38) {
                                        if ($current.length) {
                                            $current.removeClass('current').prev('li').addClass('current');
                                        }
                                        return false;
                                    }
                                    // "Down" arrow
                                    else if (code == 40) {
                                        if ($current.length && !$current.next('li').length) {
                                            return true;
                                        }
                                        var $target = ($current.length ? $current.removeClass('current').next('li') : $liste.find('li:first'));
                                        $target.addClass('current').trigger('hover');
                                        return false;
                                    }
                                }
                            }
                        });

                        $this.bind('keyup', function(e) {

                            if (isSpecialKey(e.keyCode ? e.keyCode : e.which)) {
                                return false;
                            }
                            $liste.html('').hide();
                            if ($this.data('timer')) {
                                clearTimeout($this.data('timer'));
                            }
                            $this.data('timer', setTimeout(function() {
                                var search = $this.val();
                                if (search.length >= minlen) {
                                    // Check si recherche en cache
                                    if (cache[search]) {
                                        print_autocomplete.call($this, cache[search]);
                                    } else {
                                        post.search = search;
                                        $.post(url, post, function(data) {
                                            cache[search] = data;
                                            print_autocomplete.call($this, data);
                                        });

                                    }
                                }
                            }, 200));
                            return false;
                        });
                    }
                    //using "off" remove handler on all input previously matched.
                    //In order to execute the handler only once, an attr is set on it.
                    $this.attr('auto-initialized', true);
                }
            });
            $nos(document).click(function() { $nos('ul.autocomplete-liste').html('').hide(); });
        });
    };
});