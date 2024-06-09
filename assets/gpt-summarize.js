import moment from "moment";

$(document).ready(function ()
{
    // Scroll down
    scrollDown();

    // (Un)set checkbox
    $(document).on('click', '.message', function () {
        var checkbox = $(this).find(':checkbox');
        $(checkbox).prop('checked',(!checkbox.prop('checked')));
    });

    // Upload messages
    $('.upload-messages-form').submit(function (e) {
        e.preventDefault();

        var method = $(this).attr('method');
        var url = $(this).attr('action');
        var formData = new FormData(this);

        uploadMessages(method, url, formData);
    });

    // Add messages
    $('.add-message-form').submit(function (e) {
        e.preventDefault();

        var method = $(this).attr('method');
        var url = $(this).attr('action');
        var data = $(this).serializeArray();
        var formData = new FormData();

        data.forEach(function callback(element) {
            if(element.value) {
                formData.append(element.name, element.value);
            }
        });

        addMessage(method, url, formData);
    });

    // Delete selected Messages
    $('.delete-selected-messages-form').submit(function (e)
    {
        var method = $(this).attr('method');
        var url = $(this).attr('action');
        var data = $('.gpt-summarize-form').serializeArray();
        var formData = new FormData();

        var messageCount = 0;

        data.forEach(function callback(element) {
            formData.append('_method', 'delete');
            if(element.name === 'messages[]' && element.value) {
                formData.append(element.name, element.value);
                messageCount++;
            }
        });

        if(messageCount === 0) {
            alert('You must select at least one message.')
        } else {
            deleteMessages(method, url, formData);
        }
    });

    // Delete all Messages
    $('.delete-all-messages-form').submit(function (e)
    {
        var method = $(this).attr('method');
        var url = $(this).attr('action');
        var formData = new FormData();
        formData.append('_method', 'delete');

        deleteMessages(method, url, formData);
    });

    // Save Options
    $('.gpt-option-form').submit(function(e) {
        var method = $(this).attr('method');
        var url = $(this).attr('action');
        var data = $.merge(
            $('[name="gpt_service"]').serializeArray(),
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

    // Summarize
    $('.gpt-summarize-form').submit(function (e)
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

        summarize(method, url, formData);
    });
});

function summarize(method, url, formData)
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
            var message = '';

            $.each(data, function(index, response) {
                message +=  '<div class="message alert alert-warning" role="alert">\n' +
                            '<p><input type="checkbox" class="form-check-input me-3 select-message" name="messages[]" value="'+response.message.id+'"><b>'+moment(response.message.sentAt).format('MMMM Do YYYY | h:mma')+'</b> <b>AI</b></p>\n' +
                            '<p></p>\n' +
                            '<p class="m-0 text-break">'+response.message.content+'</p>\n' +
                            '</div>';
            });

            unsetCheckboxes();
            displayMessage(message);
            scrollDown();
            loading(false);
        },
        error: function (response) {
            alert(response.responseJSON.error);
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

function uploadMessages(method, url, formData)
{
    $.ajax({
        type: method,
        url: url,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (data) {
            alert(data.message + '\n' + 'Messages count: ' + data.additions.messages_count);
            location.reload();
        },
        error: function (response) {
            alert(response.responseJSON.error);
        }
    });
}

function addMessage(method, url, formData)
{
    $.ajax({
        type: method,
        url: url,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (data) {
            location.reload();
        },
        error: function (response) {
            alert(response.responseJSON.error);
        }
    });
}

function deleteMessages(method, url, formData)
{
    $.ajax({
        type: method,
        url: url,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (data) {
            location.reload();
        },
        error: function (response) {
            alert(response.responseJSON.error);
        }
    });
}

function displayMessage(message)
{
    var element = $('#messages');
    element.append(message);
}

function unsetCheckboxes()
{
    var checkboxes = $('.messages').find(':checkbox');

    checkboxes.prop('checked', false);
}

function loading(enabled=true)
{
    if(enabled) {
        $('button.summarize').addClass('disabled');
    }
    else {
        $('button.summarize').removeClass('disabled');
    }
}

function scrollDown()
{
    $('.messages').scrollTop($('.messages').prop("scrollHeight"));
}
