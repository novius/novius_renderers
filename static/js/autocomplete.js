/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2013 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */

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

    return function($context, options) {
        $nos(function () {
            if (!($context instanceof $nos)) {
                $context = $nos($context);
            }
            $context.on('focus', 'input.autocomplete', function(event) {
                var $this = $nos(this);

                // Already initialized ?
                if ($this.attr('auto-initialized')) {
                    return ;
                }

                // Callback called when clicking on the list
                var callback = $this.data('autocomplete-callback') || $this.attr('data-autocomplete-callback') || options.on_click || false;

                // Initializes the cache
                var cache = [];
                var cache_enabled = $this.data('autocomplete-cache');
                if (typeof cache_enabled == 'undefined') {
                    cache_enabled = true;
                }

                // Initializes the list of suggestions
                var $liste = $nos('<ul class="autocomplete-liste"></ul>').hide().insertAfter($this);

                // Function to print the autocomplete results
                var printResults = function(data) {

                    // Clear old results
                    $liste.html('').hide();

                    // No results ?
                    if (typeof data != 'object' || !Object.keys(data).length) {
                        return ;
                    }

                    // Print the results
                    $nos.each(data, function(key, line) {
                        var $li = $nos('<li>'+line.label+'</li>');
                        if (typeof line.class != 'undefined') {
                            $li.addClass(line.class);
                        }
                        $li.data('value', line.value)
                            .bind('click', function(e) {
                                if (typeof callback === 'string') {
                                    callback = window[callback];
                                }
                                if ($nos.isFunction(callback)) {
                                    // Callback
                                    callback.call(this, {
                                        'root'      : $this,
                                        'value'     : $nos(this).data('value'),
                                        'label'     : $nos(this).html(),
                                        'event'     : e
                                    });
                                } else {
                                    // Default behaviour
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
                    });
                    $liste.show();
                };

                // Function to get the autocompletion URL
                var getUrl = function() {
                    return $this.data('autocomplete-url')  || $this.attr('data-autocomplete-url') || null;
                };

                // Function to get the minimum autocompletion length
                var getMinLength = function() {
                    return $this.data('autocomplete-minlength') || $this.attr('autocomplete-minlength') || 3;
                };

                // Function to get the posted vars
                var getPostedVars = function() {
                    var postedVars = {};

                    // Gets the posted vars
                    var post = $this.data('autocomplete-post') || $this.attr('data-autocomplete-post') || options.post || {};
                    if (typeof post === "object" ) {
                        $.extend(postedVars, post);
                    }

                    // Adds the config to the posted vars
                    var config = $this.data('autocomplete-config') || $this.attr('data-autocomplete-config') || {};
                    if (typeof post === "object" ) {
                        $.extend(postedVars, {
                            config: config
                        });
                    }

                    return postedVars;
                };

                // Keyboard navigation
                $this.bind('keydown', function(e) {
                    var code = e.keyCode ? e.keyCode : e.which;
                    if (!isSpecialKey(code)) {
                        return ;
                    }
                    if (!$liste.length) {
                        return ;
                    }
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
                });

                // Ajax autocompletion
                $this.bind('keyup', function(e) {

                    // Don't autocomplete on special key
                    if (isSpecialKey(e.keyCode ? e.keyCode : e.which)) {
                        return false;
                    }

                    // Reset the autocompletion list
                    $liste.html('').hide();

                    // Prevents ajax bubbling
                    if ($this.data('timer')) {
                        clearTimeout($this.data('timer'));
                    }
                    $this.data('timer', setTimeout(function() {

                        // Check if the search matches the minimum length
                        var search = $this.val();
                        if (search.length < getMinLength()) {
                            return ;
                        }

                        // Try to gets the results from the cache
                        if (cache_enabled && cache[search]) {
                            printResults.call($this, cache[search]);
                            return ;
                        }

                        // Prepares the posted vars
                        var post = $.extend(getPostedVars(), {
                            search: search
                        });

                        // Gets the results from an ajax query
                        $.ajax({
                            url : getUrl(),
                            method : 'POST',
                            data : post,
                            success: function(data) {
                                cache[search] = data;
                                printResults.call($this, data);
                            }
                        });
                    }, 200));
                    return false;
                });

                // Sets this renderer as initialized
                $this.attr('auto-initialized', true).trigger('init_autocomplete.renderer');
            });
            $nos(document).click(function() { $nos('ul.autocomplete-liste').html('').hide(); });
        });
    };
});
