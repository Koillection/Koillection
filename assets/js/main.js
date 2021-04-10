import Translator from './translator.min.js'
import * as utils from './utils'

$(document).ready(function() {
    //init image slider
    $('.slider-element').click(function (e) {
        e.preventDefault();
        var $imageFrame = $(this).closest('.slider-container').find('.slider-frame:first');
        console.log($(this).find('img').attr('data-image'));

        $imageFrame.find('a:first').attr('href', $(this).attr('href'));
        $imageFrame.find('a:first').attr('data-title', $(this).attr('data-title'));
        $imageFrame.find('img:first').attr('src', $(this).find('img').attr('data-image'));
        $imageFrame.find('.image-label:first').html($(this).attr('data-title'));
        $(this).closest('.slider-elements').find('.slider-element').removeClass('active');
        $(this).addClass('active');
    });

    utils.loadFilePreviews();


    //TEMPLATE FIELDS
    var $collectionHolder = $('#template-fields-holder');
    //Init sortable
    if ($('#template-fields-holder').find('.field').length > 0) {
        utils.reloadSortableList($collectionHolder, '.field');
    }

    var indexFieldTemplate = $collectionHolder.find('.field').length;
    var $addFieldLink = $('<a href="#" class="add_field_link waves-effect waves-light btn">Add a new field</a>');
    var $newLinkDiv = $('<div></div>').append($addFieldLink);
    $collectionHolder.append($newLinkDiv);
    utils.computePositions($collectionHolder);

    $addFieldLink.on('click', function(e) {
        e.preventDefault();
        addFieldForm($collectionHolder, $newLinkDiv);
    });

    function addFieldForm($collectionHolder, $newLinkDiv) {
        var prototype = $collectionHolder.attr('data-prototype');
        var $newForm = $(prototype.replace(/__name__/g, indexFieldTemplate));
        $newForm.find('.removeDatum').on('click', function(e) {
            e.preventDefault();
            $newForm.remove();
        });
        $newLinkDiv.before($newForm);
        console.log($newForm);
        indexFieldTemplate++;
        utils.computePositions($collectionHolder);
        utils.reloadSortableList($collectionHolder, '.field');
    }

    $('#template-fields-holder').on( "click", ".removeDatum", function() {
        $(this).closest('.field').remove();
        utils.computePositions($collectionHolder);
    });

    //Search autocomplete
    $('#search_term').click(function (e) {
        if ($(this).val().length > 2) {
            $('.autocomplete-results').show();
        }
    });

    $('#search_term').keyup(function (e) {
        let $this = $(this);
        delay(function() {
            let value = $this.val();
            let $autcompleteResultsWrapper = $('.autocomplete-results');
            if (value.length < 2) {
                $autcompleteResultsWrapper.hide();
                return;
            }

            $.get('/search/autocomplete/' + encodeURIComponent($this.val()), function( data ) {
                $autcompleteResultsWrapper.show();
                $autcompleteResultsWrapper.html('');
                $.each( data.results, function( key, result ) {
                    $autcompleteResultsWrapper.append(autocompleteResultFactory(highlight(result.label, value), result.url, result.type));
                });

                if (data.totalResultsCounter > 5) {
                    let url = "/search?search[term]=" + value + "&search[searchInCollections]=&search[searchInItems]=&search[searchInTags]=";
                    let label = Translator.transChoice('global.search.more_results', data.totalResultsCounter - 5);
                    $autcompleteResultsWrapper.append(autocompleteResultFactory(label, url));
                }

                if (data.totalResultsCounter == 0) {
                    let url = null;
                    let label = Translator.transChoice('global.search.no_results');
                    $autcompleteResultsWrapper.append(autocompleteResultFactory(label, url));
                }
            });
        }, 250 );
    });

    $(document).mouseup(function(e)
    {
        let container = $(".autocomplete-results");

        // if the target of the click isn't the container nor a descendant of the container
        if (!container.is(e.target) && container.has(e.target).length === 0)
        {
            container.hide();
        }
    });

    function autocompleteResultFactory(label, url, type = null)
    {
        let $li = $('<li class="autocomplete-result"></li>');

        if (url) {
            let $a = $('<a></a>').attr('href', url);
            $a.append(label)
            $li.append($a);

            if (type) {
                let $type = $('<span></span>').append(' (' + type + ')')
                $a.append($type);
            }
        } else {
            $li.append(label);
        }

        return $li;
    }

    function highlight(content, terms) {
        terms = terms.split(' ');
        $.each(terms, function( key, term ) {
            let search = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
            search += '(?![^<]*>)'; //Prevent matchig 'b' character inside the <b> tag
            let regex = new RegExp(search,'i');
            content = content.replace(regex, `<b>$&</b>`);
        });

        return content;
    }

    let delay = (function(){
        let timer = 0;
        return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
        };
    })();
});
