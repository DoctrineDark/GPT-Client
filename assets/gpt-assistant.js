$(document).ready(function ()
{
    // Options form
    var gptServiceElement = $('#gpt_service');
    var gptService = gptServiceElement.val();

    updateOptionsForm(gptService);

    gptServiceElement.on('change', function () {
        gptService = gptServiceElement.val();

        updateOptionsForm(gptService);
    });

    function updateOptionsForm(gptService)
    {
        var openaiModelSelect = $('#openai_gpt_model');
        var yandexGptModelSelect = $('#yandex_gpt_model');
        var geminiGptModelSelect = $('#gemini_gpt_model');

        var switchElementVisibility = function(e, bool) {
            if(bool) { e.removeClass('d-none'); }
            else { e.addClass('d-none'); }

            e.attr('disabled', !bool);
        };

        switch (gptService) {
            case 'yandex-gpt':
                switchElementVisibility(openaiModelSelect, false);
                switchElementVisibility(yandexGptModelSelect, true);
                switchElementVisibility(geminiGptModelSelect, false);

                $('.gpt-folder-id-group').removeClass('d-none');
                $('.gpt-folder-id-group').find('input').attr('required', true);

                break;

            case 'gemini':
                switchElementVisibility(openaiModelSelect, false);
                switchElementVisibility(yandexGptModelSelect, false);
                switchElementVisibility(geminiGptModelSelect, true);

                $('.gpt-folder-id-group').addClass('d-none');
                $('.gpt-folder-id-group').find('input').attr('required', false);

                break;

            default:
                switchElementVisibility(openaiModelSelect, true);
                switchElementVisibility(yandexGptModelSelect, false);
                switchElementVisibility(geminiGptModelSelect, false);

                $('.gpt-folder-id-group').addClass('d-none');
                $('.gpt-folder-id-group').find('input').attr('required', false);
        }
    }

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
        var raw = $('.raw_request');
        var rawTemp = rawTemplate;

        if(raw.children().length < 1) {
            raw.append(rawTemp);

            $(this).addClass('d-none');
            $('.rm_raw').removeClass('d-none');
        }
    });

    // Remove Raw
    $(document).on('click', '.rm_raw', function () {
        var raw = $('.raw_request');

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
    $('.assistant-form').submit(function (e)
    {
        var method = $(this).attr('method');
        var url = $(this).attr('action');
        var data = $.merge(
            $('.assistant-option-form').serializeArray(),
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

            console.log(data);

            var message = '<div class="alert alert-success" role="alert">';
            var date = new Date(data.datetime);

            message += '<p class="response"><b>['+date.toLocaleString()+']</b> '+data.message+'</p>\n';
            message += '<p></p>\n';
            message += '<hr>\n';
            message += '<p class="m-0">Thread ID: '+data.thread_id+'</p>\n';
            message += '<p class="m-0">Run ID: '+data.run_id+'</p>\n';
            message += '<p class="m-0">Prompt tokens: '+data.prompt_tokens+'</p>\n';
            message += '<p class="m-0">Complete tokens: '+data.completion_tokens+'</p>\n';
            message += '<p class="m-0">Total tokens: '+data.total_tokens+'</p>\n';
            message += '<hr>\n';
            message += '</div>';

            displayMessage(message);

            loading(false);
        },
        error: function (response) {

            console.log(response);

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
    var element = $('#assistant_response');

    element.append(message);
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
            '           <label for="raw">Raw Request Template :</label>\n' +
            '           <textarea class="form-control" id="raw" name="raw" rows="18">' +
            rawRequestTemplate  +
            '</textarea>\n' +
            '       </div>\n' +
            '   </div>\n' +
            '</div>';
}