$(document).ready(function ()
{
    $('.data-upload-form').submit(function (e)
    {
        e.preventDefault();

        var method = $(this).attr('method');
        var url = $(this).attr('action');
        var formData = new FormData(this);

        requestDataUpload(method, url, formData);
    });

    $('.vectorize-form').submit(function (e)
    {
        var method = $(this).attr('method');
        var url = $(this).attr('action');
        var data = $(this).serializeArray();
        var formData = new FormData();

        data.forEach(function callback(element) {
            if(element.value) {
                formData.append(element.name, element.value);
            }
        });

        requestVectorize(method, url, formData);
    });

    $(document).on('click', '.upload', function (e) {
        e.preventDefault();
        submitDataUpload();
    });
});

function submitDataUpload()
{
    $('.data-upload-form').submit();
}

function requestDataUpload(method, url, formData)
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
            alert(data.message + '\n' + 'Articles count: ' + data.additions.articles_count + '\n' + 'Templates count: ' + data.additions.templates_count);
            clearInputs();
            submitVectorize();
            loading(false);
        },
        error: function (response) {
            alert(response.responseJSON.error);
            loading(false);
        }
    });
}

function submitVectorize()
{
    $('.vectorize-form').submit();
}

function requestVectorize(method, url, formData)
{
    $.ajax({
        type: method,
        url: url,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (data) {
            alert(data.message);
            loading(false);
        },
        error: function (response) {
            alert(response.responseJSON.error);
            loading(false);
        }
    });
}

function clearInputs()
{
    $('#articles').val('');
    $('#templates').val('');
}

function loading(enabled=true)
{
    if(enabled) {
        $('button.upload').addClass('disabled');
    }
    else {
        $('button.upload').removeClass('disabled');
    }
}
