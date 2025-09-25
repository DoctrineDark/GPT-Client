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

    var searchModeElement = $('#search_mode');
    if ($.inArray(searchModeElement.find(':selected').val(), ['hybrid']) > -1) {
        $('.hybrid-mode-boost-group').removeClass('d-none');
        $('.hybrid-mode-boost-group').find('input').attr('disabled', false);
    } else {
        $('.hybrid-mode-boost-group').addClass('d-none');
        $('.hybrid-mode-boost-group').find('input').attr('disabled', true);
    }
    searchModeElement.on('change', function () {
        if ($.inArray($(this).find(':selected').val(), ['hybrid']) > -1) {
            $('.hybrid-mode-boost-group').removeClass('d-none');
            $('.hybrid-mode-boost-group').find('input').attr('disabled', false);
        } else {
            $('.hybrid-mode-boost-group').addClass('d-none');
            $('.hybrid-mode-boost-group').find('input').attr('disabled', true);
        }
    });

    function updateOptionsForm(gptService)
    {
        var openaiGptEmbeddingModelSelect = $('#openai_gpt_embedding_model');
        var cloudflareGptEmbeddingModelSelect = $('#cloudflare_gpt_embedding_model');
        var bgeGptEmbeddingModelSelect = $('#bge_gpt_embedding_model');

        var openaiGptModelSelect = $('#openai_gpt_model');
        var cloudflareGptModelSelect = $('#cloudflare_gpt_model');

        var switchElementVisibility = function(e, bool) {
            if(bool) { e.removeClass('d-none'); }
            else { e.addClass('d-none'); }

            e.attr('disabled', !bool);
        };

        switch (gptService) {
            case 'cloudflare':
                switchElementVisibility(openaiGptEmbeddingModelSelect, false);
                switchElementVisibility(cloudflareGptEmbeddingModelSelect, true);
                switchElementVisibility(bgeGptEmbeddingModelSelect, false);

                switchElementVisibility(openaiGptModelSelect, false);
                switchElementVisibility(cloudflareGptModelSelect, true);

                $('.account-id-group').removeClass('d-none');
                $('.account-id-group').find('input').attr('required', true);

                $('.cloudflare-index-group').removeClass('d-none');
                $('.cloudflare-index-group').find('select').removeClass('d-none');
                $('.cloudflare-index-group').find('select').attr('required', true);
                $('.cloudflare-index-group').find('select').prop('disabled', false);

                $('.opensearch-index-group').addClass('d-none');
                $('.opensearch-index-group').find('select').addClass('d-none');
                $('.opensearch-index-group').find('select').attr('required', false);
                $('.opensearch-index-group').find('select').prop('disabled', true);

                $('#vector_search_distance_limit').attr('disabled', true);

                $('.min-score-group').addClass('d-none');
                $('.min-score-group').find('input').attr('disabled', true);

                $('.search-mode-group').addClass('d-none');
                $('.search-mode-group').find('input').attr('disabled', true);

                $('.hybrid-mode-boost-group').addClass('d-none');
                $('.hybrid-mode-boost-group').find('input').attr('disabled', true);

                break;

            case 'bge':
                switchElementVisibility(openaiGptEmbeddingModelSelect, false);
                switchElementVisibility(cloudflareGptEmbeddingModelSelect, false);
                switchElementVisibility(bgeGptEmbeddingModelSelect, true);

                switchElementVisibility(openaiGptModelSelect, true);
                switchElementVisibility(cloudflareGptModelSelect, false);

                $('.account-id-group').addClass('d-none');
                $('.account-id-group').find('input').attr('required', false);

                $('.cloudflare-index-group').addClass('d-none');
                $('.cloudflare-index-group').find('select').addClass('d-none');
                $('.cloudflare-index-group').find('select').attr('required', false);
                $('.cloudflare-index-group').find('select').prop('disabled', true);

                $('.opensearch-index-group').removeClass('d-none');
                $('.opensearch-index-group').find('select').removeClass('d-none');
                $('.opensearch-index-group').find('select').attr('required', true);
                $('.opensearch-index-group').find('select').prop('disabled', false);

                $('#vector_search_distance_limit').attr('disabled', true);

                $('.min-score-group').removeClass('d-none');
                $('.min-score-group').find('input').attr('disabled', false);

                $('.search-mode-group').removeClass('d-none');
                $('.search-mode-group').find('input').attr('disabled', false);

                var searchModeElement = $('#search_mode');
                if ($.inArray(searchModeElement.find(':selected').val(), ['hybrid']) > -1) {
                    $('.hybrid-mode-boost-group').removeClass('d-none');
                    $('.hybrid-mode-boost-group').find('input').attr('disabled', false);
                } else {
                    $('.hybrid-mode-boost-group').addClass('d-none');
                    $('.hybrid-mode-boost-group').find('input').attr('disabled', true);
                }

                break;

            default:
                switchElementVisibility(openaiGptEmbeddingModelSelect, true);
                switchElementVisibility(cloudflareGptEmbeddingModelSelect, false);
                switchElementVisibility(bgeGptEmbeddingModelSelect, false);

                switchElementVisibility(openaiGptModelSelect, true);
                switchElementVisibility(cloudflareGptModelSelect, false);

                $('.account-id-group').addClass('d-none');
                $('.account-id-group').find('input').attr('required', false);

                $('.cloudflare-index-group').addClass('d-none');
                $('.cloudflare-index-group').find('select').addClass('d-none');
                $('.cloudflare-index-group').find('select').attr('required', false);
                $('.cloudflare-index-group').find('select').prop('disabled', true);

                $('.opensearch-index-group').addClass('d-none');
                $('.opensearch-index-group').find('select').addClass('d-none');
                $('.opensearch-index-group').find('select').attr('required', false);
                $('.opensearch-index-group').find('select').prop('disabled', true);

                $('#vector_search_distance_limit').attr('disabled', false);

                $('.min-score-group').addClass('d-none');
                $('.min-score-group').find('input').attr('disabled', true);

                $('.search-mode-group').addClass('d-none');
                $('.search-mode-group').find('input').attr('disabled', true);

                $('.hybrid-mode-boost-group').addClass('d-none');
                $('.hybrid-mode-boost-group').find('input').attr('disabled', true);

                break;
        }
    }

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
                    if (null !== searchResult.entity) {
                        switch (searchResult.type) {
                            case 'article':
                                message += '<p class="m-0">';
                                message += searchResult.distance ? '<b>Distance:</b> '+ searchResult.distance : '';
                                message += searchResult.score ? '<b>Score:</b> '+ searchResult.score : '';
                                message += '<a target="_blank" href="/knowledgebase/articles/'+searchResult.entity.id+'" class="link-primary mx-3">'+(searchResult.entity.articleTitle || 'Article#'+searchResult.entity.id)+'</a></p>';
                                break;

                            case 'article_paragraph':
                                message += '<p class="m-0">';
                                message += searchResult.distance ? '<b>Distance:</b> '+ searchResult.distance : '';
                                message += searchResult.score ? '<b>Score:</b> '+ searchResult.score : '';
                                message += '<a target="_blank" href="/knowledgebase/articles/'+searchResult.entity.article.id+'/paragraphs/'+searchResult.entity.id+'" class="link-primary mx-3">'+(searchResult.entity.paragraphTitle || 'ArticleParagraph#'+searchResult.entity.id)+'</a></p>';
                                break;

                            case 'template':
                                message += '<p class="m-0">';
                                message += searchResult.distance ? '<b>Distance:</b> '+ searchResult.distance : '';
                                message += searchResult.score ? '<b>Score:</b> '+ searchResult.score : '';
                                message += '<a target="_blank" href="/knowledgebase/templates/'+searchResult.entity.id+'" class="link-primary mx-3">'+(searchResult.entity.templateTitle || 'Template#'+searchResult.entity.id)+'</a></p>';
                                break;
                        }
                    } else {
                        message += '<p class="m-0">';
                        message += searchResult.distance ? '<b>Distance:</b> '+ searchResult.distance : '';
                        message += searchResult.score ? '<b>Score:</b> '+ searchResult.score : '';
                        message += '<a target="_blank" href="/knowledgebase/articles" class="link-primary mx-3">' + searchResult.type + ' #' + searchResult.id + ' (inactive)' + '</a></p>';
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
