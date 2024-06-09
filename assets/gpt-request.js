$(document).ready(function ()
{
    // Add new List
    $('.add_list').click(function() {
        var listsWrapper = $('.lists_wrapper');
        listsWrapper.removeClass('d-none');

        var lists = $('.lists');
        var listsCount = lists.children().length;
        var lastList = lists.children().last();
        var list = lastList.find('.list_name');
        var listName = (list ? list.attr('name') : null);
        var indexes = (listName ? getIndexes(listName) : null);
        var listIndex = (indexes ? parseInt(indexes[0]) : 0);
        var newListIndex = listIndex + 1;

        var newList = listTemplate(newListIndex);

        lists.append(newList);
    });

    // Remove List
    $(document).on('click', '.rm_list', function () {
        var list = $(this).parent().closest('.list');
        list.remove();

        var lists = $('.lists');
        var listsCount = lists.children().length;

        if(listsCount == 0) {
            var listsWrapper = $('.lists_wrapper');
            listsWrapper.addClass('d-none');
        }
    });

    // Add new List Value
    $(document).on('click', '.add_list_value', function () {
        var listValues = $(this).closest('div').find('.list_values');
        var valuesCount = listValues.children().length;
        var lastValue = listValues.children().last();
        var value = lastValue.find('input');
        var valueName = (value ? value.attr('name') : null);
        var indexes = (valueName ? getIndexes(valueName) : null);
        var listIndex = parseInt(indexes[0]);
        var valueIndex = parseInt(indexes[1]);
        var newValueIndex = (valueIndex + 1);
        var newListValue = listValueTemplate(listIndex, newValueIndex);

        listValues.append(newListValue);
    });

    // Remove List Value
    $(document).on('click', '.rm_list_value', function () {
        var list = $(this).closest('.list');
        var listValues = $(this).closest('.list_values');
        var listValue = $(this).closest('.list_value');
        var valuesCount = listValues.children().length;

        if(valuesCount == 1) {
            list.remove();

            var lists = $('.lists');
            var listsCount = lists.children().length;

            if(listsCount == 0) {
                var listsWrapper = $('.lists_wrapper');
                listsWrapper.addClass('d-none');
            }
        }

        listValue.remove();
    });

    // Add new Checkbox
    $('.add_checkbox').click(function() {
        var checkboxesWrapper = $('.checkboxes_wrapper');
        checkboxesWrapper.removeClass('d-none');

        var checkboxes = $('.checkboxes');
        var checkboxesCount = checkboxes.children().length;
        var lastCheckbox = checkboxes.children().last();
        var checkbox = lastCheckbox.find('input');
        var checkboxName = (checkbox ? checkbox.attr('name') : null);
        var indexes = (checkboxName ? getIndexes(checkboxName) : null);
        var checkboxIndex = (indexes ? parseInt(indexes[0]) : 0);
        var newCheckboxIndex = checkboxIndex + 1;

        var newCheckbox = checkboxTemplate(newCheckboxIndex);

        checkboxes.append(newCheckbox);
    });

    // Remove Checkbox
    $(document).on('click', '.rm_checkbox', function () {
        var checkbox = $(this).closest('.checkbox');
        checkbox.remove();

        var checkboxes = $('.checkboxes');
        var checkboxesCount = checkboxes.children().length;

        if(checkboxesCount == 0) {
            var checkboxesWrapper = $('.checkboxes_wrapper');
            checkboxesWrapper.addClass('d-none');
        }
    });

    // Add Client Message Template
    $(document).on('click', '.add_client_message_template', function () {
        var clientMessageTemplate = $('.client_message_template');
        var clientMessageTemplateTemp = clientMessageTemplateTemplate;

        if(clientMessageTemplate.children().length < 1) {
            clientMessageTemplate.append(clientMessageTemplateTemp);

            $(this).addClass('d-none');
            $('.rm_client_message_template').removeClass('d-none');
        }
    });

    // Remove Client Message Template
    $(document).on('click', '.rm_client_message_template', function () {
        var clientMessageTemplate = $('.client_message_template');

        clientMessageTemplate.html('');

        $(this).addClass('d-none');
        $('.add_client_message_template').removeClass('d-none');
    });

    // Add Raw
    $(document).on('click', '.add_raw', function () {
        var raw = $('.raw_gpt_request');
        var rawTemp = rawTemplate;

        if(raw.children().length < 1) {
            raw.append(rawTemp);

            $(this).addClass('d-none');
            $('.rm_raw').removeClass('d-none');
        }
    });

    // Remove Raw
    $(document).on('click', '.rm_raw', function () {
        var raw = $('.raw_gpt_request');

        raw.html('');

        $(this).addClass('d-none');
        $('.add_raw').removeClass('d-none');
    });

    // Save Options
    $('.gpt-option-form').submit(function(e) {
        var method = $(this).attr('method');
        var url = $(this).attr('action');
        var data = $.merge(
            $('[name="gpt_service"]').serializeArray().concat(
                $('[name="client_message_template"]').serializeArray().concat(
                    $('[name="raw"]').serializeArray()
                ),
            ),
            $(this).serializeArray()
        );
        var formData = new FormData();

        data.forEach(function callback(element) {
            if(element.value) {
                formData.append(element.name, element.value);
            }
        });

        saveOption(method, url, formData);
    });

    // Request
    $('.gpt-form').submit(function (e)
    {
        var method = $(this).attr('method');
        var url = $(this).attr('action');
        var data = $.merge(
            $('.gpt-option-form').serializeArray(),
            $(this).serializeArray()
        );
        var formData = new FormData();

        data.forEach(function callback(element) {
            if(element.value) {
                formData.append(element.name, element.value);
            }
        });

        request(method, url, formData);
    });
});

function request(method, url, formData)
{
    loading(true);

    $.ajax({
        type: method,
        url: url,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (data) {
            var message = '<div class="alert alert-success" role="alert">';

            for (let i = 0; i < data.length; i++) {
                var date = new Date(data[i].datetime);

                message += '<p><b>['+date.toLocaleString()+']</b> '+data[i].message+'</p>\n';
                message += '<p></p>\n';
                message += '<p class="m-0">Prompt tokens: '+data[i].prompt_tokens+'</p>\n';
                message += '<p class="m-0">Complete tokens: '+data[i].completion_tokens+'</p>\n';
                message += '<p class="m-0">Total tokens: '+data[i].total_tokens+'</p>\n';
                message += '<hr>\n';
            }

            message += '</div>';

            displayMessage(message);

            loading(false);
        },
        error: function (response) {
            var date = new Date;
            var message =   '<div class="alert alert-danger" role="alert">\n' +
                            '   <p class="m-0"><b>['+date.toLocaleString()+']</b> '+(response.responseJSON.error ?? 'Unknown request error.')+'</p>\n' +
                            '</div>';

            displayMessage(message);

            loading(false);
        }
    });
}

function saveOption(method, url, formData)
{
    $.ajax({
        type: method,
        url: url,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (data) {
            alert('Options have been saved successfully.')
        },
        error: function (response) {
            alert(response.responseJSON.error);
        }
    });
}

function loading(enabled=true)
{
    if(enabled) {
        $('button.request').addClass('disabled');
    }
    else {
        $('button.request').removeClass('disabled');
    }
}

function displayMessage(message)
{
    var element = $('#gpt_response');

    element.append(message);
}

function getIndexes(name, regex = /\[(\d+)\]/g)
{
    var indexes = [];
    var match;

    while ((match = regex.exec(name)) !== null) {
        indexes.push(parseInt(match[1]));
    }

    return indexes;
}

function listTemplate(index)
{
    var listValue = listValueTemplate(index, 0);

    return  '<div class="row list p-3">\n' +
            '   <div class="col-sm">\n' +
            '       <div class="form-group">\n' +
            '           <input type="text" class="form-control list_name" id="lists['+index+']" name="lists['+index+']">\n' +
            '       </div>\n' +
            '   </div>\n' +
            '   <div class="col-sm">\n' +
            '       <div class="list_values">' + listValue + '</div>\n' +
            '       <button type="button" name="add_value" class="btn btn-link add_list_value">Add Value</button>\n' +
            '   </div>\n' +
            '   <button type="button" name="rm_list" class="btn btn-link rm_list">Remove List</button>\n' +
            '</div>';
}

function listValueTemplate(listIndex, valueIndex)
{
    return  '<div class="form-group list_value">\n' +
            '   <div class="input-group mb-3">\n' +
            '       <input type="text" class="form-control" id="lists_values[' + listIndex + '][' + valueIndex + ']" name="lists_values[' + listIndex + '][' + valueIndex + ']" placeholder="" aria-label="" aria-describedby="basic-addon1">\n' +
            '       <div class="input-group-prepend">\n' +
            '           <button class="btn btn-outline-danger rm_list_value" type="button">X</button>\n' +
            '       </div>\n' +
            '   </div>\n' +
            '</div>';
}

function checkboxTemplate(index)
{
    return  '<div class="row checkbox p-3">\n' +
            '   <div class="col">\n' +
            '       <div class="input-group mb-3">\n' +
            '           <input type="text" class="form-control" id="checkboxes['+index+']" name="checkboxes['+index+']" placeholder="" aria-label="" aria-describedby="basic-addon1">\n' +
            '           <div class="input-group-prepend">\n' +
            '               <button class="btn btn-outline-danger rm_checkbox" type="button">X</button>\n' +
            '           </div>\n' +
            '       </div>\n' +
            '   </div>\n' +
            '</div>';
}

function clientMessageTemplateTemplate()
{
    var clientMessageTemplate = $('#client_message_template_buffer').val();

    return  '<div class="row">\n' +
            '   <div class="col">\n' +
            '       <div class="form-group">\n' +
            '           <label for="client_message_template">Client Message Template:</label>\n' +
            '           <textarea class="form-control" id="client_message_template" name="client_message_template" rows="4">' + clientMessageTemplate + '</textarea>\n' +
            '       </div>\n' +
            '   </div>\n' +
            '</div>';
}

function rawTemplate()
{
    var rawRequestTemplate = $('#raw_request_template_buffer').val();

    return  '<div class="row">\n' +
            '   <div class="col">\n' +
            '       <div class="form-group">\n' +
            '           <label for="raw">Custom GPT-Request:</label>\n' +
            '           <textarea class="form-control" id="raw" name="raw" rows="18">' +
            rawRequestTemplate  +
            '</textarea>\n' +
            '       </div>\n' +
            '   </div>\n' +
            '</div>';
}