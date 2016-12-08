<html land="en">
<head>
    <title>Opinion Mining</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="UI/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.2.0/jquery.rateyo.min.css">
    <script type="text/javascript" src="UI/js/jquery.min.js"></script>
    <script type="text/javascript" src="UI/js/validation.js"></script>
</head>
<?php
    include('./classes/databaseClass.php');
    $objTwitterHandler = new TwitterHandler(
        OAUTH_ACCESS_TOKEN,             // 'Access token' on https://apps.twitter.com
        OAUTH_ACCESS_TOKEN_SECRET,      // 'Access token secret' on https://apps.twitter.com
        CONSUMER_KEY,                   // 'API key' on https://apps.twitter.com
        CONSUMER_SECRET,                // 'API secret' on https://apps.twitter.com
        USER_ID,                        // User id (http://gettwitterid.com/)
        SCREEN_NAME,                    // Twitter handle
        COUNT                           // The number of tweets to pull out
    );
    $dbObject = new databaseClass();
    $analyzer = new commentAnalyzer();
    
    //Twitter
    $commentsUrl = 'statuses/mentions_timeline.json';
    $commentsUrl .= '?count='.COUNT;
    $tweets = $objTwitterHandler->getComments($commentsUrl);

    if (!empty($tweets) && empty($tweets['errors'])){
        foreach ($tweets as $tweet) {
            $comments = $dbObject->getRows('comments', array('where' => array('comment_id' => $tweet['id_str'])));
            if (empty($comments)) {
                $commentText = str_ireplace('@'.SCREEN_NAME, '', $tweet['text']);
                $analysis = $analyzer->getAnalysis($commentText);
                $dbObject->insert(
                    'comments',
                    array(
                        'comment_id' => $tweet['id_str'],
                        'comments' => nl2br($commentText),
                        'source' => 'Twitter',
                        'rating' => (!empty($analysis['rating'])) ? $analysis['rating'] : '',
                        'positive_word' => (!empty($analysis['sentimentWords']['positiveWords'])) ? implode(', ', $analysis['sentimentWords']['positiveWords']) : '',
                        'negative_word' => (!empty($analysis['sentimentWords']['negativeWords'])) ? implode(', ', $analysis['sentimentWords']['negativeWords']) : '',
                        'action' => (!empty($analysis['actionCategories'])) ? implode(', ', $analysis['actionCategories']) : '',
                        'created_on' => date('Y-m-d H:i:s'),
                    )
                );
            }

        }
    }
        
    // sanitize post data
    $postData = filter_var_array($_POST, FILTER_SANITIZE_STRING);

    if (!empty($postData)) {
        $analysis = $analyzer->getAnalysis($postData['commentTextArea']);

		$data = array(
			'comments' => trim(nl2br($postData['commentTextArea'])),
			'source' => trim($postData['source']),
			'comment_id' => '',
			'rating' => $analysis['rating'],
			'positive_word' => implode(", ", $analysis['sentimentWords']['positiveWords']),
			'negative_word' => implode(", ", $analysis['sentimentWords']['negativeWords']),
			'action' => implode(", ", $analysis['actionCategories']),
			'created_on' => date('Y-m-d H:i:s'),
		);

        $dbObject->insert('comments', $data);
    }
    // Fetch all the comments
    $commentsDetails = $dbObject->getRows('comments', array('order_by' => 'created_on DESC'));
?>
<body>
<div id="main">

    <nav>
        <div id="menubar">
            <ul id="nav">
                <li class="current"><a href="#">Sentiment Analysis</a></li>
            </ul>
        </div>
    </nav>

    <div id="site_content">

        <div id="content">
            <div class="content_item">
                <h1>&nbsp;</h1>
                <form name="postcomment" action="index.php" method="post" onsubmit="return validateForm();">
                    <p>
                        <label> Please enter your review:</label>
                        <textarea id="commentTextArea" name="commentTextArea" rows="5" required> </textarea>
                        <input type="hidden" value="Manual" name="source"/>
                        <input type="submit" id="postYourOpinion" value="Submit" class="postComment"/>
                        <label id="errorMessage"> Please enter your review.</label>
                    </p>
                </form>
            </div>
        </div>

        <div id="content">
            <div class="contentItem">
				 <h1 class="customStyle">Product Review Summary:</h1>
				 <p class="customStylePara">
					<?php
						if (!empty($commentsDetails)) {
							$analysisSummary = $analyzer->getAnalysisSummary($commentsDetails);
						}
					
						echo '<div class="row" style="width: 100%;">
                            <label> Average Rating: </label>
                            <div id="averageRating" style="float: right;">
                            </div></div></br>';
                    if (count($analysisSummary['top_actions']) > 0) {
						echo '<label> Categorywise Analysis:</label></br>';
						    foreach ($analysisSummary['top_actions'] as $key => $value) {
						        if (trim($key)) {
                                    echo '<label>' . $key . ' : ' . $value . '</label></br>';
                                }
                            }
                        }
					?>
				</p>

            </div>
        </div>

        <div id="site_content"></div>

        <div id="commentSection">
            <div class="table">

                <div class="row header blue">
                    <div class="cell">
                        Comments
                    </div>
                    <div class="cellSource">
                        Source
                    </div>
                    <div class="cellSource">
                        Rating
                    </div>
                    <div class="cellPositive">
                        Positive Words
                    </div>
                    <div class="cellPositive">
                        Negative Words
                    </div>
                    <div class="cellPositive">
                        Actionable Categories
                    </div>
                    <div class="cellDate">
                        Date
                    </div>
                </div>
				<?php
					if (!empty($commentsDetails)) {
						foreach ($commentsDetails as $comments) {
                            $date = new DateTime($comments['created_on']);

							echo '<div class="row">
								<div class="cell">
									'.$comments['comments'].'
								</div>
								<div class="cellSource">
									'.$comments['source'].'
								</div>
								<div class="cellSource">
									<div id="stars'.$comments['id'].'"></div>
								</div>
								<div class="cellPositive">
									'.$comments['positive_word'].'
								</div>
								<div class="cellPositive">
									'.$comments['negative_word'].'
								</div>
								<div class="cellPositive">
									'.$comments['action'].'
								</div>
								<div class="cellDate">
									'.$date->format('d M Y').'
								</div>
							</div>';
						}
					} else {
						echo '<div class="row">There are no comments.</div>';
					}
				?>

            </div>
        </div>

    </div>


</div>

<footer>
    <!-- Footer Content-->
</footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.2.0/jquery.rateyo.min.js"></script>
<script type="text/javascript" src="UI/js/tweets.js"></script>
<script type="text/javascript">
    $("#averageRating").rateYo({
        rating: <?php echo number_format($analysisSummary['rating'], 2, '.', ''); ?>,
        starWidth: "20px",
        numStars: 5,
        readOnly: true
    });
<?php
	if (!empty($commentsDetails)) {
		foreach ($commentsDetails as $comments) {
			echo '
				$("#stars'.$comments['id'].'").rateYo({
					rating: '.$comments['rating'].',
					starWidth: "20px",
					numStars: 5,
					readOnly: true
				});
			';
		}
	}
?>
</script>
</body>
</html>
