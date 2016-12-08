<?php

require_once('setup.php');
$objAnalyzer = new commentAnalyzer();
$db = new DatabaseClass();
// Create a Twitter Proxy object from our twitter_proxy.php class
$objTwitterHandler = new TwitterHandler(
    OAUTH_ACCESS_TOKEN,			    // 'Access token' on https://apps.twitter.com
    OAUTH_ACCESS_TOKEN_SECRET,		// 'Access token secret' on https://apps.twitter.com
    CONSUMER_KEY,					// 'API key' on https://apps.twitter.com
    CONSUMER_SECRET,				// 'API secret' on https://apps.twitter.com
    USER_ID,						// User id (http://gettwitterid.com/)
    SCREEN_NAME,					// Twitter handle
    COUNT							// The number of tweets to pull out
);

$type = filter_var($_GET['type'], FILTER_SANITIZE_STRING);

switch ($type) {
    case 'comments':
        /*$commentsUrl = 'statuses/mentions_timeline.json';
        $commentsUrl .= '?count='.COUNT;
        $tweets = $objTwitterHandler->getComments($commentsUrl);
        if (empty($tweets['errors'])){
            foreach ($tweets as $tweet) {
                $comments = $db->getRows('comments', array('where' => array('comment_id' => $tweet['id_str'])));
                if (empty($comments)) {
                    $commentText = str_ireplace('@'.SCREEN_NAME, '', $tweet['text']);
					$analysis = $objAnalyzer->getAnalysis($commentText);
                    $db->insert(
                        'comments',
                        array(
                            'comment_id' => $tweet['id_str'],
                            'comments' => $commentText,
                            'source' => 'Twitter',
                            'rating' => (!empty($analysis['rating'])) ? $analysis['rating'] : '',
                            'positive_word' => (!empty($analysis['sentimentWords']['positiveWords'])) ? implode(', ', $analysis['sentimentWords']['positiveWords']) : '',
                            'negative_word' => (!empty($analysis['sentimentWords']['negativeWords'])) ? implode(', ', $analysis['sentimentWords']['negativeWords']) : '',
                            'action' => (!empty($analysis['actionCategories'])) ? implode(', ', $analysis['actionCategories']) : '',
                            'created_on' => date('Y-m-d'),
                        )
                    );
                }

            }
        }*/

        $sentimentsData = $db->getRows('comments', array('order_by' => 'created_on DESC'));
        echo json_encode($sentimentsData);
        exit;
    break;
    default:
        $twitterUrl = 'statuses/user_timeline.json';
        $twitterUrl .= '?user_id='.$userId;
        $twitterUrl .= '&screen_name='.$screenName;
        $twitterUrl .= '&count='.COUNT;
        // Invoke the get method to retrieve results via a cURL request
        $tweets = $objTwitterHandler->get($twitterUrl);
        echo $tweets;
}

?>
