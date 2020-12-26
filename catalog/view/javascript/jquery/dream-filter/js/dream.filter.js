/**
 * Dream Filter
 *
 * @license Commercial
 * @author ig@redream.ru (http://redream.ru)
 */
(function($) {
    $.fn.dreamFilter = function (options) {
        var rdrform = (typeof $(this).selector != 'undefined') ? $(this).selector : '#' + $(this).attr('id'),
            action = $(rdrform).attr('action'),
            loader = options.loader,
            widget = $('#' + options.widget),
            popper,
            popover = $('#' + options.popper.id),
            decodeURI = true,
            callbackBefore = options.callbackBefore ? options.callbackBefore : false,
            callbackAfter = options.callbackAfter ? options.callbackAfter : false;

        //Filter init
        $(window).on('load', function() {
            $(rdrform).addClass('initialized');

            if($(window).width() < options.mobile.width) {
                if(options.mobile.mode == 'button') {
                    mobileButtonView();
                } else if(options.mobile.mode == 'fixed') {
                    mobileFixedView();
                }
            }
            initTruncate();
        });

        //Filter submit
        $(document).on('submit', rdrform, function(e) {
            beforeSubmit();
            hidePopper();
            if(options.ajax.enable) {
                e.preventDefault();
                var fData = $(this).serialize();
                loadItems(action, fData, true, true);
                afterSubmit();
            }
        });

        //Disable empty inputs before submit
        function beforeSubmit() {
            $(rdrform + ' input, ' + rdrform + ' select').each(function (index) {
                var input = $(this);
                if(input.hasClass('irs-hidden-input')) {
                    var wrapper = input.closest('.slidewrapper');
                    if(wrapper.hasClass('irs-notinit')) {
                        input.val('');
                    }
                }
                if(!input.val()) {
                    input.prop('disabled', true);
                }
            });
        }

        //Reset disabled inputs after submit
        function afterSubmit() {
            $(rdrform + ' input, ' + rdrform + ' select').filter(':disabled').each(function (index) {
                $(this).prop('disabled', false);
            });
        }

        //Auto submit
        if(options.search_mode == 'auto') {
            $(document).on('change', rdrform + ' input:not([type=hidden]), ' + rdrform + ' select', function () {
                $(rdrform).submit();
            });
            $(document).on('finish', rdrform + ' input.irs-hidden-input', function () {
                $(rdrform).submit();
            });
        }

        //Popper
        if(options.popper.enable) {
            $(document).on('change', rdrform + ' input:not([type=hidden]), ' + rdrform + ' select', function () {
                updatePopper($(this).closest('.panel'));
            });
            $(document).on('finish', rdrform + ' input.irs-hidden-input', function () {
                updatePopper($(this).closest('.panel'));
            });
            $(document).on('click', '#' + options.popper.button_id, function () {
                $(rdrform).submit();
                hidePopper();
            });
            $(document).mouseup(function (e){
                if (!popover.is(e.target) && popover.has(e.target).length === 0) {
                    hidePopper();
                }
            });
        }

        //Create new popper
        function showPopper(offset) {
            offset = offset || 0;

            setTimeout(function() {
                popper = new Popper($(rdrform), popover, {
                    placement: 'right-start',
                    modifiers: {
                        offset: {
                            offset: offset
                        },
                        computeStyle: {
                            gpuAcceleration: false
                        },
                        preventOverflow: {
                            enabled: true,
                            boundariesElement: 'viewport'
                        }
                    }
                });
                popover.fadeIn(200);
            }, 200);
        }

        //Destroy popper if exist
        function hidePopper() {
            if(popper) {
                popover.fadeOut(200, function(){
                    popper.destroy();
                });
            }
        }

        //Update popper text
        function updatePopper(panel) {
            var popperOffset = panel.offset().top + panel.outerHeight()/2 - popover.outerHeight()/2 - $(rdrform).offset().top;

            beforeSubmit();

            $.ajax({
                url: options.popper.action.replace(/&amp;/g, '&'),
                type: 'get',
                data: $(rdrform).serialize(),
                processData: false,
                dataType: 'html',
                beforeSend : function() {
                    $('#' + options.popper.button_id).button('loading');
                },
                success: function (data) {
                    popover.find('span').html(data);
                    showPopper(popperOffset);
                },
                complete : function() {
                    $('#' + options.popper.button_id).button('reset');
                }
            });
            afterSubmit();
        }

        //Filter reset
        $(document).on('click', '#' + options.reset_id, function(e) {
            $(rdrform + ' .rdf-filters input, ' + rdrform + ' .rdf-filters select').each(function (index) {
                clearFilter($(this).data('id'), false);
            });
            $(rdrform).submit();
        });

        //Clear trigger
        $(document).on('click', rdrform + ' [data-clear]', function () {
            clearFilter($(this).data('clear'));
        });

        //Change clear buttons
        $(document).on('change', rdrform + ' input:checkbox, ' + rdrform + ' input:radio', function () {
            var id = $(this).data('id');
            if ($(this).is(':radio')) {
                $(this).closest('.rdf-group').find('[data-clear]').remove();
            }
            if($(this).is(':checked')) {
                $('#' + id).find('.rdf-label').before('<span class="rdf-clear" data-clear="' + id + '">&times;</span>');
            } else {
                $('#' + id).find('.rdf-clear').remove();
            }
        });

        //Change clear buttons
        $(document).on('change', rdrform + ' select, ' + rdrform + ' input:text', function () {
            var id = $(this).data('id');
            if($(this).val()) {
                if(!$(this).closest('.input-group').find('[data-clear]').length) {
                    $(this).closest('.input-group').append('<span class="rdf-clear input-group-addon" data-clear="' + id + '">&times;</span>');
                }
            } else {
                $(this).closest('.input-group').find('.rdf-clear').remove();
            }
        });

        //Clear parameters
        function clearFilter(id, submit) {
            submit = (submit === undefined) ? true : submit;

            var group = $('#' + id),
                input = group.find('input, select'),
                pick = $(rdrform + ' .rdf-picked').find('[data-clear="' + id + '"]'),
                clear = group.find('[data-clear="' + id + '"]');

            if(input.is(':checkbox') || input.is(':radio')) {
                input.prop('checked', false);
                input.removeAttr('checked');
            } else {
                if(input.hasClass('irs-hidden-input')) {
                    var slider = input.data('ionRangeSlider'),
                        from = slider.options.from_min ? slider.options.from_min : slider.options.min,
                        to = slider.options.to_max ? slider.options.to_max : slider.options.max;

                    slider.update({
                        from: from,
                        to: to
                    });
                }
                input.val('');
            }
            if(submit) {
                if(options.search_mode == 'auto') {
                    $(rdrform).submit();
                } else if(options.popper.enable) {
                    updatePopper(group.closest('.panel'));
                }
            }
            pick.remove();
            clear.remove();
        }

        //Truncate
        function initTruncate() {
            if(options.truncate.mode == 'height') {
                $(rdrform + ' .rdf-truncate-height').each(function (index) {
                    if($(this).outerHeight() == parseInt(options.truncate.height)) {
                        if(options.truncate.scrollbar) {
                            if(!$(this).hasClass('scroll-wrapper')) {
                                $(this).scrollbar();
                            }
                        } else {
                            $(this).find('.rdf-group').css('padding-right', 0);
                        }
                    }
                });
            }
            if(options.truncate.mode == 'width' && options.truncate.scrollbar) {
                $(rdrform + ' .rdf-truncate-width').each(function (index) {
                    if(!$(this).hasClass('scroll-wrapper')) {
                        $(this).scrollbar();
                    }
                });
            }
            if(options.truncate.mode == 'element') {
                $(rdrform + ' .rdf-truncate-element .rdf-group').each(function (index) {
                    var lght = $(this).find('.rdf-val').filter(":visible").length;
                    if (lght > options.truncate.elements) {
                        $(this).css('padding-bottom', 0);
                        if($(this).parent().hasClass('rdf-show')) {
                            truncateShow($(this), 0);
                        } else {
                            truncateHide($(this), 0);
                        }
                    } else {
                        $(this).css('height', '').css('padding-bottom', '');
                        $(this).siblings('.rdf-truncate-show').removeClass('active');
                        $(this).siblings('.rdf-truncate-hide').removeClass('active');
                    }
                    $(this).css('max-height', 'none');
                    $(this).show();
                });
                $(document).on('click', rdrform + ' .rdf-truncate-show', function () {
                    truncateShow($(this).siblings('.rdf-group'));
                });
                $(document).on('click', rdrform + ' .rdf-truncate-hide', function () {
                    truncateHide($(this).siblings('.rdf-group'));
                });
                function truncateHide(group, animate) {
                    if (typeof animate === "undefined") {
                        animate = 400;
                    }
                    var height = 0;
                    group.find('.rdf-val').filter(':visible').each(function (index) {
                        if(index < options.truncate.elements) {
                            height += $(this).outerHeight(true);
                        }
                    });
                    group.animate({height: height + 'px'}, animate);
                    group.parent().removeClass('rdf-show');
                    group.siblings('.rdf-truncate-hide').removeClass('active');
                    group.siblings('.rdf-truncate-show').addClass('active');
                }
                function truncateShow(group, animate) {
                    if (typeof animate === "undefined") {
                        animate = 400;
                    }
                    var height = parseInt(group.css('padding-top'));
                    group.find('.rdf-val').filter(':visible').each(function (index) {
                        height += $(this).outerHeight(true);
                    });
                    group.animate({height: height + 'px'}, animate);
                    group.parent().addClass('rdf-show');
                    group.siblings('.rdf-truncate-show').removeClass('active');
                    group.siblings('.rdf-truncate-hide').addClass('active');
                }
            }
        }

        //Mobile view
        if(options.mobile.mode == 'fixed') {
            $(window).on('resize', function() {
                if($(window).width() < options.mobile.width) {
                    mobileFixedView();
                } else {
                    desktopFixedView();
                }
            });
            $(document).on('click', '.' + options.mobile.button_id, function() {
                widget.toggleClass('show');

                if(widget.hasClass('show')) {
                    if(options.mobile.side == 'right') {
                        widget.animate({
                            right: 0
                        });
                    } else {
                        widget.animate({
                            left: 0
                        });
                    }
                } else {
                    if(options.mobile.side == 'right') {
                        widget.animate({
                            right: '-255px'
                        });
                    } else {
                        widget.animate({
                            left: '-255px'
                        });
                    }
                }
            });

            function mobileFixedView() {
                if(!widget.hasClass('rdf-mobile-view')) {
                    widget.before('<div id="rdf-dummy"></div>');
                    widget.detach().prependTo('body');
                    widget.addClass('rdf-mobile-view');
                    widget.css('top', options.mobile.indenting_top + 'px');
                    widget.css(options.mobile.side, '-255px');
                }
                var formHeight = $(window).height() - options.mobile.indenting_top - options.mobile.indenting_bottom,
                    bodyHeight = formHeight - $(rdrform).find('.rdf-header').outerHeight() - $(rdrform).find('.rdf-footer').outerHeight() - 10;

                $(rdrform).css('max-height', formHeight + 'px');
                $(rdrform).find('.rdf-body').css('max-height', bodyHeight + 'px');
                if(!$(rdrform).find('.rdf-filters').hasClass('scroll-content') && $(rdrform).find('.rdf-picked').outerHeight() + $(rdrform).find('.panel-group').outerHeight() > bodyHeight) {
                    $(rdrform).find('.rdf-filters').scrollbar();
                }
            }
            function desktopFixedView() {
                if(widget.hasClass('rdf-mobile-view')) {
                    widget.removeClass('rdf-mobile-view');
                    $('#rdf-dummy').after(widget.detach()).remove();
                    $(rdrform).attr('style', '');
                    $(rdrform).find('.rdf-body').attr('style', '');
                }

                widget.removeClass('show');
            }
        }
        if(options.mobile.mode == 'button') {
            $(window).on('resize', function() {
                if($(window).width() < options.mobile.width) {
                    mobileButtonView();
                } else {
                    desktopButtonView();
                }
            });
            $(document).on('click', '.' + options.mobile.button_id, function() {
                $(rdrform).collapse('toggle')
            });

            function mobileButtonView() {
                if(!$(rdrform).hasClass('collapse')) {
                    $(rdrform).addClass('collapse');
                }
            }
            function desktopButtonView() {
                if($(rdrform).hasClass('collapse')) {
                    $(rdrform).removeClass('collapse');
                }
                $(rdrform).css('height', 'auto');
            }
        }

        //Ajax filter
        if(options.ajax.enable) {
            $(document).ready(function () {
                $(options.ajax.selector).prepend(loader);
                ajax_init();
            });

            //Popstate
            if(options.ajax.pushstate) {
                $(window).on('popstate', function (e) {
                    loadItems(location.href);
                });
            }

            //Pagination
            if(options.ajax.pagination) {
                $(document).on('click', options.ajax.pagination + ' a', function (e) {
                    loadItems($(this).attr('href'), null, true);
                    return false;
                });
            }

            //Sort
            if(options.ajax.sorter) {
                if(options.ajax.sorter_type == 'button') {
                    $(document).on('click', options.ajax.sorter + ' a', function (e) {
                        e.preventDefault();
                        var href = $(this).attr('href'),
                            sort = href.match('sort=([A-Za-z.]+)'),
                            order = href.match('order=([A-Z]+)');

                        $(rdrform + ' input[name="sort"]').val(sort[1]);
                        $(rdrform + ' input[name="order"]').val(order[1]);

                        loadItems(href, null, true);
                        return false;
                    });
                } else {
                    $(document).on('change', options.ajax.sorter, function (e) {
                        e.preventDefault();
                        var href = $(this).val(),
                            sort = href.match('sort=([A-Za-z.]+)'),
                            order = href.match('order=([A-Z]+)');

                        $(rdrform + ' input[name="sort"]').val(sort[1]);
                        $(rdrform + ' input[name="order"]').val(order[1]);

                        loadItems(href, null, true);
                    });
                }
            }

            //Limit
            if(options.ajax.limit) {
                if(options.ajax.limit_type == 'button') {
                    $(document).on('click', options.ajax.limit + ' a', function (e) {
                        e.preventDefault();
                        var href = $(this).attr('href'),
                            limit = href.match('limit=([0-9]+)');

                        $(rdrform + ' input[name="limit"]').val(limit[1]);

                        loadItems(href, null, true);
                        return false;
                    });
                } else {
                    $(document).on('change', options.ajax.limit, function (e) {
                        e.preventDefault();
                        var href = $(this).val(),
                            limit = href.match('limit=([0-9]+)');

                        $(rdrform + ' input[name="limit"]').val(limit[1]);

                        loadItems(href, null, true);
                    });
                }
            }
        }

        function ajax_init() {
            if(options.ajax.sorter && options.ajax.sorter_type == 'select') {
                $(options.ajax.sorter).removeAttr('onchange');
            }
            if(options.ajax.limit && options.ajax.limit_type == 'select') {
                $(options.ajax.limit).removeAttr('onchange');
            }
			
            $(options.ajax.selector).addClass('rdf-container');

            try {
                var view = false;
                if($.cookie) {
                    view = $.cookie('display');
                } else if($.totalStorage) {
                    view = $.totalStorage('display');
                }
                if (view && typeof (display) === "function") {
                    display(view);
                } else {
                    view = localStorage.getItem('display');
                    switch(view) {
                        case 'list':
                            if (typeof (list_view) === "function") {
                                list_view();
                            } else {
                                $('#list-view').trigger('click');
                            }
                            break;
                        case 'compact':
                            if (typeof (compact_view) === "function") {
                                compact_view();
                            } else {
                                $('#compact-view').trigger('click');
                            }
                            break;
                        case 'price':
                            if (typeof (price_view) === "function") {
                                price_view();
                            } else {
                                $('#price-view').trigger('click');
                            }
                            break;
                        default:
                            if (typeof (grid_view) === "function") {
                                grid_view();
                            } else {
                                $('#grid-view').trigger('click');
                            }
                    }
                }
            } catch(e) {
                console.error('Display error ' + e.name + ":" + e.message + "\n" + e.stack);
            }
        }

        //Loading results
        function loadItems(action, fData, push, reload) {
            var url = action + (fData ? ((action.indexOf('?') > 0 ? '&' : '?') + fData) : ''),
                filter = '',
                picked = fData;

            if(decodeURI) {
                url = decodeURIComponent(url);
            }

            fData = (fData ? fData + '&' : '') + 'rdf-ajax=1';


            if(fData && reload && options.disable_null !== 'leave') {
                fData += '&rdf-reload=1&rdf-module=' + options.module;
            } else {
                reload = false;
            }

            if (callbackBefore && typeof callbackBefore === "function") {
                callbackBefore(action, fData);
            }

            var grid_btn = '',
                list_btn = '',
                price_btn = '',
                compact_btn = '';

            $.ajax({
                url: action,
                type: 'get',
                data: fData,
                processData: false,
                dataType: reload ? 'json' : 'html',
                beforeSend : function() {
                    $(rdrform + ' .rdf-footer button').button('loading');
                    $(options.ajax.selector).addClass('rdf-loading');
                    grid_btn = $('#grid-view').clone(true);
                    list_btn = $('#list-view').clone(true);
                    price_btn = $('#price-view').clone(true);
                    compact_btn = $('#compact-view').clone(true);
                },
                success: function (data) {
                    var content = (reload && (typeof data.html != 'undefined')) ? data.html : data;

                    if($(options.ajax.selector).find('#' + options.widget).length) {
                        filter = $('#' + options.widget).detach();
                        $(content).find('#' + options.widget).remove();
                    } else if($(options.ajax.selector).find('#rdf-dummy').length) {
                        filter = $('#rdf-dummy').detach();
                    }

                    $(options.ajax.selector).children(':not(.rdf-loader)').remove();
                    $(options.ajax.selector).append($(content).find(options.ajax.selector).html());
                    $(options.ajax.selector).prepend(filter);

                    $('#grid-view').replaceWith(grid_btn);
                    $('#list-view').replaceWith(list_btn);
                    $('#price-view').replaceWith(price_btn);
                    $('#compact-view').replaceWith(compact_btn);

                    if(reload && (typeof data.filters != 'undefined')) {
                        reloadFilter(data.filters);
                        initTruncate();
                    }
                    if(options.ajax.pushstate && push) {
                        history.pushState(null, null, url);
                    }
                    if(picked) {
                        updatePicked();
                    }
                    ajax_init();
                    if (callbackAfter && typeof callbackAfter === "function") {
                        callbackAfter(content);
                    }
                },
                error: function( jqXHR, textStatus, errorThrown ) {
                    console.error('jqXHR', jqXHR);
                    console.error('textStatus', textStatus);
                    console.error('errorThrown', errorThrown);
                },
                complete : function() {
                    setTimeout(function() {
                        $(options.ajax.selector).removeClass('rdf-loading');
                        $(rdrform + ' .rdf-footer button').button('reset');
                        if(options.ajax.scroll) {
                            $('body,html').animate({
                                scrollTop: options.ajax.offset
                            }, 500);
                        }
                    }, 300);
                }
            });
        }

        //Filter reload
        function reloadFilter(filters) {
            $.each(options.filters, function(id, filter) {
                var panel = $('#' + id);

                if(typeof filters[id] == 'undefined') {
                    if(options.disable_null == 'hide') {
                        panel.hide();
                    }
                } else if(panel.is(':hidden')) {
                    panel.show();
                }

                if(filter.values) {
                    var prefix = (panel.find('input:checked').length) ? '+' : '';
                    $.each(filter.values, function(val_id, count) {
                        var val = $('#' + val_id);

                        if(val) {
                            if(val.is('option')) {
                                var text = val.text().replace(/\(.*\)/gm, "");
                                if(typeof filters[id] != 'undefined' && typeof filters[id].values[val_id] != 'undefined') {
                                    val.prop('disabled', false);
                                    if(val.is(':hidden')) {
                                        val.show();
                                    }
                                    if(options.count_show) {
                                        val.html(text + '(' + prefix + filters[id].values[val_id] + ')');
                                    }
                                } else {
                                    if(options.disable_null == 'disable' && !val.is(':selected')) {
                                        val.prop('disabled', true);
                                    } else if(options.disable_null == 'hide') {
                                        val.hide();
                                    }
                                    if(options.count_show) {
                                        val.html(text);
                                    }
                                }
                            } else {
                                if(typeof filters[id] != 'undefined' && typeof filters[id].values[val_id] != 'undefined') {
                                    if(options.disable_null == 'disable') {
                                        val.fadeTo('fast', 1);
                                    } else if(val.is(':hidden')) {
                                        val.show();
                                    }
                                    val.find('input').prop('disabled', false);

                                    if(options.count_show) {
                                        val.find('.rdf-label').html(prefix + filters[id].values[val_id]);
                                    }
                                } else {
                                    if(!val.find('input').is(":checked")) {
                                        if(options.disable_null == 'disable') {
                                            val.fadeTo('slow', 0.5);
                                        } else if(options.disable_null == 'hide') {
                                            val.hide();
                                        }
                                        val.find('input').prop('disabled', true);
                                    }
                                    if(options.count_show) {
                                        val.find('.rdf-label').html('');
                                    }
                                }
                            }
                        }
                    });
                } else if(filter.range || filter.slider) {
                    var input = $('#' + filter.input_id),
                        slider = input.data('ionRangeSlider');

                    if(slider && !input.val()) {
                        var min = slider.options.from_min !== null ? slider.options.from_min : slider.options.min,
                            max = slider.options.to_max !== null ? slider.options.to_max : slider.options.max;
                            update = {};
                        if(typeof filters[id] != 'undefined' && (filters[id].range || filters[id].slider)) {
                            if(filter.range && filters[id].range) {
                                if(filters[id].range.min != min) {
                                    update.from_min = filters[id].range.min;
                                    update.to_min = filters[id].range.min;
                                    update.from = filters[id].range.min;
                                }
                                if(filters[id].range.max != max) {
                                    update.from_max = filters[id].range.max;
                                    update.to_max = filters[id].range.max;
                                    update.to = filters[id].range.max;
                                }
                            }
                            if(filter.slider && filters[id].slider) {
                                var range_min,
                                    range_max;
                                $.each(filter.slider, function(val_index, val) {
                                    if(filters[id].slider.indexOf(val) != -1) {
                                        if(typeof range_min == 'undefined') {
                                            range_min = val_index;
                                        }
                                        range_max = val_index;
                                    }
                                });
                                if(typeof range_min !== 'undefined' && range_min != min) {
                                    update.from_min = range_min;
                                    update.to_min = range_min;
                                    update.from = range_min;
                                }

                                if(typeof range_max !== 'undefined' && range_max != max) {
                                    update.from_max = range_max;
                                    update.to_max = range_max;
                                    update.to = range_max;
                                }
                            }
                            if(slider.options.disable) {
                                update.disable = false;
                            }
                        } else {
                            update.disable = true;
                        }
                        if(!$.isEmptyObject(update)) {
                            slider.update(update);
                            input.val('');
                        }
                    }
                }
            });
        }

        //Update picked filters
        function updatePicked() {
            $(rdrform + ' .rdf-picked').html('');
            if(options.show_picked) {
                var picked = [];

                $.each(options.filters, function(f_id, filter) {
                    var panel = $('#' + f_id);

                    if(filter.values) {
                        $.each(panel.find('input:checked'), function(i) {
                            picked.push({
                                id: $(this).attr('data-id'),
                                name: filter.type == 'type_single' ? '' : filter.title,
                                value: $.trim($(this).closest('label').text())
                            });
                        });
                        $.each(panel.find('option:selected'), function(i) {
                            if($(this).val()) {
                                picked.push({
                                    id: f_id,
                                    name: filter.title,
                                    value: $.trim($(this).text().replace(/\(.*\)/gm, ""))
                                });
                            }
                        });
                    } else if(filter.input_id) {
                        var input = $('#' + filter.input_id);
                        if(input && input.val()) {
                            if(input.hasClass('irs-hidden-input')) {
                                var slider = input.data('ionRangeSlider'),
                                    pick = '',
                                    prettyFrom = (slider.options.p_values[slider.result.from] == 'undefined') ? slider.result.from_value : slider.options.p_values[slider.result.from],
                                    prettyTo = (slider.options.p_values[slider.result.to] == 'undefined') ? slider.result.to_value : slider.options.p_values[slider.result.to];

                                pick += slider.options.prefix;
                                pick += prettyFrom ? prettyFrom : slider.result.from;
                                pick += ' - ';
                                pick += prettyTo ? prettyTo : slider.result.to;
                                pick += slider.options.postfix;

                                picked.push({
                                    id: f_id,
                                    name: filter.title,
                                    value: pick
                                });
                            } else {
                                picked.push({
                                    id: f_id,
                                    name: filter.title,
                                    value: input.val()
                                });
                            }
                        }
                    }
                });
                $.each(picked, function(i, pick) {
                    $(rdrform + ' .rdf-picked').append(
                        '<button type="button" data-clear="' + pick.id + '" class="btn btn-default btn-xs">' +
                        (pick.name ? (pick.name + ': ') : '') + pick.value +
                        '<i>&times;</i></button>'
                    );
                });
            }
        }
    }
})(jQuery);