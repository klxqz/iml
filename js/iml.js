(function ($) {
    "use strict";
    $.storage = new $.store();
    $.iml = {
        options: {},
        // last list view user has visited: {title: "...", hash: "..."}
        lastView: null,
        init: function (options) {
            var that = this;
            that.options = options;
            if (typeof ($.History) != "undefined") {
                $.History.bind(function () {
                    that.dispatch();
                });
            }
            $.wa.errorHandler = function (xhr) {
                if ((xhr.status === 403) || (xhr.status === 404)) {
                    var text = $(xhr.responseText);
                    if (text.find('.dialog-content').length) {
                        text = $('<div class="block double-padded"></div>').append(text.find('.dialog-content *'));

                    } else {
                        text = $('<div class="block double-padded"></div>').append(text.find(':not(style)'));
                    }
                    $("#iml-content").empty().append(text);
                    that.onPageNotFound();
                    return false;
                }
                return true;
            };
            var hash = this.getHash();
            if (hash === '#/' || !hash) {
                this.dispatch();
            } else {
                $.wa.setHash(hash);
            }

        },
        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
        // *   Dispatch-related
        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

        // if this is > 0 then this.dispatch() decrements it and ignores a call
        skipDispatch: 0,
        /** Cancel the next n automatic dispatches when window.location.hash changes */
        stopDispatch: function (n) {
            this.skipDispatch = n;
        },
        /** Force reload current hash-based 'page'. */
        redispatch: function () {
            this.currentHash = null;
            this.dispatch();
        },
        dispatch: function (hash) {
            if (this.skipDispatch > 0) {
                this.skipDispatch--;
                return false;
            }

            if (hash === undefined || hash === null) {
                hash = this.getHash();
            }
            if (this.currentHash == hash) {
                return;
            }

            this.currentHash = hash;
            hash = hash.replace('#/', '');

            if (hash) {
                hash = hash.split('/');
                if (hash[0]) {
                    var actionName = "";
                    var attrMarker = hash.length;
                    for (var i = 0; i < hash.length; i++) {
                        var h = hash[i];
                        if (i < 2) {
                            if (i === 0) {
                                actionName = h;
                            } else if (parseInt(h, 10) != h && h.indexOf('=') == -1) {
                                actionName += h.substr(0, 1).toUpperCase() + h.substr(1);
                            } else {
                                attrMarker = i;
                                break;
                            }
                        } else {
                            attrMarker = i;
                            break;
                        }
                    }
                    var attr = hash.slice(attrMarker);
                    this.preExecute(actionName);
                    if (typeof (this[actionName + 'Action']) == 'function') {
                        $.shop.trace('$.iml.dispatch', [actionName + 'Action', attr]);
                        this[actionName + 'Action'].apply(this, attr);
                    } else {
                        $.shop.error('Invalid action name:', actionName + 'Action');
                    }
                    this.postExecute(actionName);
                } else {
                    this.preExecute();
                    this.defaultAction();
                    this.postExecute();
                }
            } else {
                this.preExecute();
                this.defaultAction();
                this.postExecute();
            }


        },
        preExecute: function (actionName, attr) {
        },
        postExecute: function (actionName, attr) {
            this.actionName = actionName;
        },
        defaultAction: function () {
            var self = this;
            this.load('?plugin=iml&action=requests', function () {
                self.initButtons();
            });
        },
        requestFileAction: function (file) {
            this.load('?plugin=iml&action=request&file=' + file, function () {

            });
        },
        initButtons: function () {
            $(document).on('click', '.delete-button', function () {
                var self = $(this);
                $.ajax({
                    type: 'POST',
                    url: '?plugin=iml&action=deleteFile',
                    data: {
                        file: $(this).data('file')
                    },
                    dataType: 'json',
                    success: function (data, textStatus, jqXHR) {
                        if (data.status == 'ok') {
                            self.closest('tr').remove();
                        } else {
                            alert(data.errors.join(' '));
                        }
                    },
                    error: function (jqXHR, errorText) {
                        alert(jqXHR.responseText);
                    }
                });
                return false;
            });
        },
        /** Current hash */
        getHash: function () {
            return this.cleanHash();
        },
        /** Make sure hash has a # in the begining and exactly one / at the end.
         * For empty hashes (including #, #/, #// etc.) return an empty string.
         * Otherwise, return the cleaned hash.
         * When hash is not specified, current hash is used. */
        cleanHash: function (hash) {
            if (typeof hash == 'undefined') {
                hash = window.location.hash.toString();
            }

            if (!hash.length) {
                hash = '' + hash;
            }
            while (hash.length > 0 && hash[hash.length - 1] === '/') {
                hash = hash.substr(0, hash.length - 1);
            }
            hash += '/';

            if (hash[0] != '#') {
                if (hash[0] != '/') {
                    hash = '/' + hash;
                }
                hash = '#' + hash;
            } else if (hash[1] && hash[1] != '/') {
                hash = '#/' + hash.substr(1);
            }

            if (hash == '#/') {
                return '';
            }

            return hash;
        },
        load: function (url, options, fn) {
            if (typeof options === 'function') {
                fn = options;
                options = {};
            } else {
                options = options || {};
            }
            var r = Math.random();
            this.random = r;
            var self = this;



            (options.content || $("#iml-content")).html('<div class="block triple-padded"><i class="icon16 loading"></i>Loading...</div>');
            return  $.get(url, function (result) {
                if ((typeof options.check === 'undefined' || options.check) && self.random != r) {
                    // too late: user clicked something else.
                    return;
                }

                (options.content || $("#iml-content")).removeClass('bordered-left').html(result);
                if (typeof fn === 'function') {
                    fn.call(this);
                }

            });
        },
        onPageNotFound: function () {
            //this.defaultAction();
        }
    };
})(jQuery);