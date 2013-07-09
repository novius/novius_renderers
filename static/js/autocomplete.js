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
        $nos(function () {

            $nos(context).find('input.autocomplete').each(function() {
                var $this = $nos(this);

                // Callback called when clicking on the list
                var callback = $this.data('autocomplete-callback') || $this.attr('data-autocomplete-callback') || options.on_click || false;
                //Met-on à jour l'url d'autocomplete ?
                var maj_url = $this.data('maj_url') || $this.attr('data-maj_url') || false;

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
                var url = $this.data('autocomplete-url');
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

                        //on met à jour l'url
                        if (maj_url) {
                            var url = $this.attr('data-autocomplete-url');
                        }

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
            });
            $nos(document).click(function() { $nos('ul.autocomplete-liste').html('').hide(); });
        });
    };
});