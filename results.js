/* 
 * results.js
 * 
 * currently only used to insert exterpts into the main page.
 */

$(document).ready(function() {
    $("a.getExerptLink").live('click', function(event) {
        var href  = $(this).attr('href');       
        var $parent = $(this).parent();
        
        $.get(href, function(data) {
            $parent.empty();
            $parent.append(data);
        });
        event.preventDefault();
    });
});

