$(document).ready(function ()
{
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

    $('.gpt-search-form').submit(function (e)
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

        var question = $(this).find('#question');

        displayQuestion(question.val());
        question.val('');

        request(method, url, formData);
    });
});

$('.clear_response').click(function() {
    var response = $('#response');

    response.html('');
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
            var date = new Date(data.gpt_response.datetime);

            message += '<p><b>['+date.toLocaleString()+']</b> '+data.gpt_response.message+'</p>\n';
            message += '<p></p>\n';

            message += '<p class="m-0"><b>Search result:</b></p>\n';
            if(data.search_result.length === 0) {
                message += '<p class="m-0">Nothing found</p>\n';
            } else {
                $.each(data.search_result, function(i, searchResult) {
                    switch (searchResult.type) {
                        case 'article':
                            message += '<p class="m-0"><b>Distance:</b> '+searchResult.distance+'<a target="_blank" href="/knowledgebase/articles/'+searchResult.entity.id+'" class="link-primary mx-3">'+(searchResult.entity.articleTitle || 'Article#'+searchResult.entity.id)+'</a></p>';
                            break;

                        case 'article_paragraph':
                            message += '<p class="m-0"><b>Distance:</b> '+searchResult.distance+'<a target="_blank" href="/knowledgebase/articles/'+searchResult.entity.article.id+'/paragraphs/'+searchResult.entity.id+'" class="link-primary mx-3">'+(searchResult.entity.paragraphTitle || 'ArticleParagraph#'+searchResult.entity.id)+'</a></p>';
                            break;

                        case 'template':
                            message += '<p class="m-0"><b>Distance:</b> '+searchResult.distance+'<a target="_blank" href="/knowledgebase/templates/'+searchResult.entity.id+'" class="link-primary mx-3">'+(searchResult.entity.templateTitle || 'Template#'+searchResult.entity.id)+'</a></p>';
                            break;
                    }
                }.bind(message));
            }
            message += '<hr>\n';

            message += '<p class="m-0">Prompt tokens: '+data.gpt_response.prompt_tokens+'</p>\n';
            message += '<p class="m-0">Complete tokens: '+data.gpt_response.completion_tokens+'</p>\n';
            message += '<p class="m-0">Total tokens: '+data.gpt_response.total_tokens+'</p>\n';
            message += '<hr>\n';
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
        $('button.search').addClass('disabled');
    }
    else {
        $('button.search').removeClass('disabled');
    }
}

function displayMessage(message)
{
    var element = $('#response');
    element.append(message);
}

function displayQuestion(question)
{
    var date = new Date();
    var message = '<div class="alert alert-primary" role="alert"><p class="m-0"><b>['+date.toLocaleString()+']</b> '+question+'</p></div>';

    displayMessage(message);
}
