(function($){
    $(function () {
        const customSelect = new CustomSelect();
        const copyToClipBoard = function (str)
        {
            const el = document.createElement('textarea');
            el.value = str;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
        };

        updateCountersList();

        $('.yam-repeater-field__add-btn').on('click', function () {
            let $repeater = $(this).closest('.yam-repeater-field'),
                $row = $repeater.find('.yam-repeater-field__row_tpl').eq(0).clone().removeClass('yam-repeater-field__row_tpl');

            $('.yam-repeater-field__rows', $repeater).append($row);

            reindexRows($repeater);
        });

        $('.text-field__reset').on('click', function () {
            let $resetBtn = $(this),
                $textField = $resetBtn.closest('.text-field'),
                $input = $('.text-field__input', $textField);

            $input.val('');
        });

        $('[data-toggle]').on('click', function () {
            let $btn = $(this),
                selector = $btn.data('toggle'),
                $target = $(selector);

            if ($target.length) {
                $target.toggleClass('active');
            }
        });

        $('.yam__site').on('click', function () {
            let $siteBtn = $(this),
                siteId = $siteBtn.data('site'),
                $siteSettings = $('.yam__settings_site_' + siteId);

            if ($siteSettings.length) {
                $('.yam__settings, .yam__site').removeClass('active');
                $siteSettings.addClass('active');
                $siteBtn.addClass('active');

                updateCountersList();
            }
        });

        $(document).on('click', '.yam-logs_copy', function () {
            let brRegex = /<br\s*[\/]?>/gi;
            let copyText = document.querySelector(".info-block_logs span").innerHTML.replace(brRegex, "\r\n");
            copyToClipBoard(copyText);
        });

        $(document).on('click', '.yam-repeater-field__remove-btn', function () {
            let $btn = $(this),
                $repeater = $btn.closest('.yam-repeater-field'),
                $row = $btn.closest('.yam-repeater-field__row');

            $row.remove();
            reindexRows($repeater);

        });

        $(document).on('change keyup input click', '[data-input-type="number"]', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        $(document).on('click', '.yam-spoiler__btn', function () {
            var $btn = $(this),
                $spoiler = $btn.closest('.yam-spoiler'),
                $content = $spoiler.find('.yam-spoiler__content');

            $btn.toggleClass('active');

            if ($btn.hasClass('active')) {
                $content.addClass('active')
            } else {
                $content.removeClass('active')
            }
        });

        $(document).on('click', '.yam-repeater-field_counters .yam-repeater-field__add-btn', function () {
            updateCountersList();
        });

        $(document).on('click', '.yam-repeater-field_counters .yam-repeater-field__remove-btn', function () {
            updateCountersList();
        });

        $(document).on('change', '.yam-repeater-field_counters .yam-repeater-field__input', function () {
            updateCountersList();
        });

        //functions

        function reindexRows($repeater) {
            //reindex row inputs
            $repeater.find('.yam-repeater-field__row').not('.yam-repeater-field__row_tpl').each(function (index) {
                const $row = $(this);

                $row.find('.yam-repeater-field__input').each(function () {
                    let $input = $(this),
                        name = $input.attr('data-name');

                    $input.attr('name', name.replace(/\$/, index));
                });
            });
        }

        function updateCountersList() {
            let countersList = [];

            $('.yam__settings.active .yam-repeater-field_counters .yam-repeater-field__row').each(function () {
                const $this = $(this),
                    $number = $('.yam-repeater-field__input[name$="[number]"]', $this);

                let number;

                if ($number.length) {
                    number = $number.val();
                }

                if (number) {
                    countersList.push(number);
                }
            });

            updateMasksCounters(countersList);
        }


        function updateMasksCounters(countersList) {
            $('.yam__settings.active .yam-repeater-field_masks .custom-select__orig').each(function(){
                const $select = $(this);
                const $selectedOption = $('option:selected', $select);
                let selectedNumber = '';

                if ($selectedOption.length) {
                    selectedNumber = $selectedOption.val();
                }

                let optionsHtml = '<option value="">-</option>';
                countersList.forEach(function(number){
                    optionsHtml += '<option value="'+number+'" '+(selectedNumber === number ? 'selected' : '')+'>'+number+'</option>';
                });

                $select.html(optionsHtml);

                customSelect.decorate($select);
            });
        }
    });


    const CustomSelect = function () {
        $(document).on('click', function (e) {
            let $target = $(e.target),
                $select = $target.closest('.custom-select');

            if ($select.length) {
                $('.custom-select').not($select).removeClass('active');
            } else {
                $('.custom-select').removeClass('active');
            }
        });

        $('[data-toggle=".yam__logs"]').on('click', function (e) {
            $(this).toggleClass('opened');

            if($(this).hasClass('opened')) {
                $(this).text(BX.message("YANDEX_METRIKA_HIDE_LOGS"));
            }
            else {
                $(this).text(BX.message("YANDEX_METRIKA_SHOW_LOGS"));
            }

        });

        $(document).on('click', '.custom-select__active', function () {
            let $active = $(this),
                $value = $active.find('.custom-select__active-value'),
                $select = $value.closest('.custom-select'),
                $optionsContainer = $select.find('.custom-select__options'),
                isActive = $select.hasClass('active');

            $select.removeClass('active');

            if (!isActive) {
                $select.addClass('active');
            }
        });

        $(document).on('click', '.custom-select__option',  function () {
            let $option = $(this),
                $select = $option.closest('.custom-select'),
                $orig = $select.find('.custom-select__orig'),
                $value = $select.find('.custom-select__active-value'),
                $optionsContainer = $select.find('.custom-select__options'),
                value = $option.data('value'),
                label = $option.text();

            $orig.find('[value="' + value + '"]').prop('selected', true);
            $value.text(label);
            $select.removeClass('active');
        });

        this.decorate = function ($select) {
            $select = $($select);

            if (!$select.length) {
                return;
            }

            const options = this.getSelectOptionsData($select);
            const optionsHtml = this.generateOptions(options);
            let $customSelect = $select.closest('.custom-select');

            if (!$customSelect.length) {
                const customSelectHtml = this.generateHtml(),
                    modifier = $select.data('modifier');

                $customSelect = $(customSelectHtml);
                $select.after($customSelect);
                $customSelect.prepend($select);
                if (modifier) {
                    $customSelect.addClass('custom-select_'+modifier);
                }
            }

            $customSelect.find('.custom-select__options').html(optionsHtml);

            const activeOption = options.find(function(optionData){
                return optionData.active;
            });
            if (activeOption) {
                $customSelect.find('.custom-select__active-value').text(activeOption.label);
            }
        }

        this.generateHtml = function () {
            return '<div class="custom-select">' +
                '<div class="custom-select__active"><div class="custom-select__active-value"></div></div>' +
                '<div class="custom-select__options"></div>' +
                '</div>';
        }

        this.generateOptions = function(options){
            let optionsHtml = '';

            options.forEach(function (option) {
                let activeClass = '';

                if (option.active) {
                    activeClass = 'active';
                }

                optionsHtml += '<div class="custom-select__option ' + activeClass + '" data-value="' + option.key + '">' + option.label + '</div>';
            });

            return optionsHtml;
        }

        this.getSelectOptionsData = function ($select) {
            const options = [];

            $('option', $select).each(function () {
                const $option = $(this),
                    key = $option.val(),
                    label = $option.text(),
                    active = $option.is(':selected');

                options.push({
                    key: key,
                    label: label,
                    active: active
                })
            });

            return options;
        }
    }

})(jQuery);





const format = (state) => {

    //const option = that.countriesPhones.find(c => c.tag === state.id);
    const $state = $(
        `<span class="custom-option__title">${state.text}</span>
        ${state.element?.getAttribute('data-title')? `<span class="custom-option__text">${state.element?.getAttribute('data-title')}</span>` : ''}
        `
    );

    if (state.newOption) {
        $state.append(" <em>(new)</em>");
    }

    return $state;
}
const formatSelection = (state) => {
    const $state = $(
        `<span>${state.text}</span>`
    );
    return $state;
}



$(function() {


    function ymEventsInitSelects() {

        $('.ym-events_rows .select2-param:not(.inited)').select2({
            minimumResultsForSearch: 0,
            placeholder: BX.message("YANDEX_METRIKA_EVENT_PARAM_JS_EVENT_PARAM"),
            templateResult: format,
            templateSelection: formatSelection,
            language: {
                noResults: function () {
                    return "Ничего не найдено";
                }
            }
        });


        $('.ym-events_rows .select2-event:not(.inited)').select2({
            minimumResultsForSearch: 0,
            placeholder: BX.message("YANDEX_METRIKA_EVENT_DESCR_H"),
            templateResult: format,
            templateSelection: formatSelection,
            tags: true,
            language: {
                noResults: function () {
                    return "Ничего не найдено";
                }
            },
            createTag: function (params) {
                return {
                    id: params.term,
                    text: params.term,
                    newOption: true
                }
            },
            /*templateResult: function (data) {
                var $result = $("<span></span>");

                $result.text(data.text);

                if (data.newOption) {
                    $result.append(" <em>(new)</em>");
                }

                return $result;
            }*/
        });

        $('.select2-param').addClass("inited");
        $('.select2-event').addClass("inited");

    }


    function ymEventsAddRow(dataRow = null, site = null) {
        let	$row = $('.yam__settings_site_' + site + ' .ym-events_row-tempate').clone();

        $row.removeClass('ym-events_row-tempate').addClass('ym-events_row');

        $row.find(".select2-param").removeClass("inited")
        $row.find(".select2-event").removeClass("inited")

        if (dataRow !== null) {
            $row.find(".select2-param").val( dataRow.param)
            $row.find(".input-selector").val(dataRow.selector)


            if($row.find(".select2-event").find("option[value='" + dataRow.event + "']").length) {
                $row.find(".select2-event").val( dataRow.event)
            } else {
                var newState = new Option(dataRow.event, dataRow.event, true, true);
                $row.find(".select2-event").append(newState).trigger('change');
            }


        }
        else {
            $row.find(".input-selector").attr("disabled", true)
            $row.find(".select2-event").attr("disabled", true)
            $('.yam-events_add-btn').attr("disabled", true)
        }

        $(".yam__settings_site_" + site + " .ym-events_rows").append($row);
    }


    function ymEventsSetData() {
        $.each( ymEvents, function( siteid, value ) {
            if (value !== null) {
                $.each(value, function (key, value) {
                    ymEventsAddRow(value, siteid);
                });
            }
        });

        if (ymEvents.length==0) {
            ymEventsAddRow();
        }

        ymEventsInitSelects();
    }


    ymEventsSetData();


    $(document).on('click', '.js_ym_events_remove', function () {
        let $btn = $(this),
            $row = $btn.closest('.ym-events_row');

        $row.remove();
        $('.yam-events_add-btn').attr("disabled", false);
    });


    $('.yam-events_add-btn').on('click', function () {
        ymEventsAddRow(null, $(this).data('siteid'));
        ymEventsInitSelects();
    });







    // это можно запустить если программно надо выбрать опцию
    //$("#select").val('Ru').trigger("change");


    $('#select').on('select2:select', (e) => {
        //alert('select:' + e.params.data.text);
    });

    $(document).on('change keyup', '.input-selector, .select2-event', function (e) {
        let event = $(this).closest('.ym-events_row').find('.select2-event').val().length;
        let selector = $(this).closest('.ym-events_row').find('.input-selector').length;
        let param = $(this).closest('.ym-events_row').find('.select2-param').length;

        if(event > 0 && selector > 0 && param > 0) {
            $('.yam-events_add-btn').attr("disabled", false)
        }

    });

    $(document).on('change', '.select2-param', function () {
       let selectVal = $(this).val().length;

       if(selectVal > 0) {
           $(this).closest('.ym-events_field_wrapper').next().find('.input-selector').attr("disabled", false)
           $(this).closest('.ym-events_field_wrapper').next().next().find('.select2-event').attr("disabled", false)
       }
       else {
           $(this).closest('.ym-events_field_wrapper').next().find('.input-selector').attr("disabled", true)
           $(this).closest('.ym-events_field_wrapper').next().next().find('.select2-event').attr("disabled", true)
       }
    });

    $(document).on('select2:open', (e) => {
        const selectId = e.target.id

        $(".select2-search__field[aria-controls='select2-" + selectId + "-results']").each(function (
            key,
            value,
        ){
            value.focus();
        })
    })


});


