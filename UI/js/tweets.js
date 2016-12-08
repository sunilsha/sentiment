function getMentionedComments(){
    $.ajax({
        url: '../sentiment2/getTweets.php?type=comments',
        type: 'GET',
        dataType: "json",
        success: function(response) {
            html = '';
            html += '<div class="table">';

            html += '<div class="row header blue">';
            html += '<div class="cell">Comments</div>';
            html += '<div class="cell">Source</div>';
            html += '<div class="cell">Rating</div>';
            html += '<div class="cell">Positive Words</div>';
            html += '<div class="cell">Negative Words</div>';
            html += '<div class="cell">Action Categories</div>';
            html += '<div class="cell">Date</div>';

            html += '</div>';
            if (response.length > 0) {
                $.each(response, function(key, value){
                    var createdDate = new Date(value['created_on']);
                    var formatedDate = getFormattedDate(createdDate);
                    html += '<div class="row">';
                    html += '<div class="cell" style="width: 65%;">'+value['comments']+'</div>';
                    html += '<div class="cellSource">'+value['source']+'</div>';
                    html += '<div class="cellSource"><div id="stars'+value['id']+'"></div></div>';
                    html += '<div class="cellPositive">'+value['positive_word']+'</div>';
                    html += '<div class="cellPositive">'+value['negative_word']+'</div>';
                    html += '<div class="cellPositive">'+value['action']+'</div>';
                    html += '<div class="cellDate">'+formatedDate+'</div>';
                    html += '</div>';
                });
            } else {
                html += '<div class="row">There are no comments.</div>';
            }

            html += '</div>';

            $('#commentSection').html(html);
            $.each(response, function(key, value){
                $("#stars"+value['id']).rateYo({
                    rating: value['rating'],
                    starWidth: "20px",
                    numStars: 5,
                    readOnly: true
                });
            });
        },
        error: function(errors) {
            $('#commentSection p:first').text('Request error');
        }
    });
}

$(function(){
    //getMentionedComments();
    //setInterval(getMentionedComments, 50000);
});

