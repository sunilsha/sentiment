<?php

// Comment Analyzer Class
class commentAnalyzer
{
    /**
     * @var array
     */
    private $data = array();

    /**
     * @var float
     */
    private $rating;

    /**
     * commentAnalyzer constructor.
     */
    public function __construct()
    {

    }

    /**
     * @param $comment
     * @return mixed
     */
    public function getAnalysis($comment)
    {
        $nlpApi = new nlpApiInterface();
        $result = $nlpApi->getSentiment($comment);
        if ($result['status'] === 'ERROR') {
            throw new Exception("Error while connecting to NLP API: ".$result['statusInfo']);
        }
        $this->extractData($result);
        $this->calculateRating($this->data);
        return array(
            'actionCategories' => $this->findActionCategories(),
            'rating' => $this->getRating(),
            'sentimentWords' => $this->getSentimentWords()
        );
    }

    /**
     * @param $result
     */
    public function extractData($result)
    {
        $rating = 0;
        if (empty($result['keywords'])) {
            return $rating;
        }

        // find average for netgative, positvite, neutral
        $data = array(
            'negative' => array('words' => array(), 'count' => 0, 'relevance' => 0, 'score' => 0),
            'positive' => array('words' => array(), 'count' => 0, 'relevance' => 0, 'score' => 0),
            'neutral' => array('words' => array(), 'count' => 0, 'relevance' => 0, 'score' => 0),
        );

        foreach ($result['keywords'] as $keyword) {
            $data[$keyword['sentiment']['type']]['words'][] = $keyword['text'];
            $data[$keyword['sentiment']['type']]['relevance'] += $keyword['relevance'];
            $data[$keyword['sentiment']['type']]['count'] += 1;
        }

        $this->data = $data;
    }


    public function calculateRating($data)
    {
        // Find Average Relevance of 1.
        $totalCount = 0;
        if (!empty($data['neutral']) && $data['neutral']['count']) {
            $data['neutral']['score'] = ($data['neutral']['relevance'] / $data['neutral']['count']);
            $totalCount += $data['neutral']['count'];
        }
        if (!empty($data['negative']) && $data['negative']['count']) {
            $data['negative']['score'] = ($data['negative']['relevance'] / $data['negative']['count']);
            //$data['negative']['count'] += ($data['neutral']['count'] / 2);
            $totalCount += $data['negative']['count'];

        }
        if (!empty($data['positive']) && $data['positive']['count']) {
            $data['positive']['score'] = ($data['positive']['relevance'] / $data['positive']['count']);
            //$data['positive']['count'] += ($data['neutral']['count'] / 2);
            $totalCount += $data['positive']['count'];
        }

        // Findout positive & negative percentage
        $positivePercentage = $data['positive']['count'] / $totalCount * 100;
        $negativePercentage = $data['negative']['count'] / $totalCount * 100;
        $neutralPercentage = $data['neutral']['count'] / $totalCount * 100;

        // Multiply positive & negative factors by average revelance score.
        $positiveFactor = $positivePercentage * $data['positive']['score'];
        $negativeFactor = $negativePercentage * $data['negative']['score'];
        $neutralFactor = $neutralPercentage * $data['neutral']['score'];
        $sumOfRelevanceScore = $positiveFactor + $negativeFactor + $neutralFactor;

        // Findout positive & negative percentage based on relevance factor.
        $postiveReleventPercentage = ($positiveFactor / $sumOfRelevanceScore) * 100;
        $negativeReleventPercentage = ($negativeFactor / $sumOfRelevanceScore) * 100;
        $neutralReleventPercentage = ($neutralFactor / $sumOfRelevanceScore) * 100;

        // Divide nuetral equally into posivite & negative
        $postiveReleventPercentage += $neutralReleventPercentage / 2;
        $negativeReleventPercentage += $neutralReleventPercentage / 2;

        $this->rating = round($postiveReleventPercentage / 20, 1);
        $this->rating = ($this->rating < 1) ? 1 : $this->rating;

        return $data;
    }

    /**
     * Iterate through negative workds user has talked about.
     * Find them into our dictionaries. If match is found based on exact & Fuzzy Search,
     * assign the comment to a category to be actioned upon.
     */
    public function findActionCategories()
    {
        $dictionary = new dictionary('mobile');
        $productCategories = $dictionary->getAvailableCategories();
        $actionCategories = array();
        foreach ($this->data['negative']['words'] as $word) {
            foreach ($productCategories as $category) {
                if ($dictionary->findMatch($word, $category)) {
                    $actionCategories[] = $category;
                }
            }
        }
        return $actionCategories;
    }

    /**
     * @return float
     */
    public function getRating()
    {
        return $this->rating;
    }

	/**
     * @return Array
     */
    public function getSentimentWords()
    {
        return array(
            'negativeWords' => $this->data['negative']['words'],
            'positiveWords' => $this->data['positive']['words'],
            'neutralWords' => $this->data['neutral']['words'],
        );
    }

	/**
	 * get Summary of product Review
	 * @param array
	 *
     * @return Array
     */
	function getAnalysisSummary($commentsDetails)
	{
        $numberOfComments = 0;
		$rating = 0;
		$actionCount = array();
		foreach ($commentsDetails as $comments) {
            $rating = $rating + $comments['rating'];
            $numberOfComments++;$action = $comments['action'];
			$actionArray = explode(',', $action);

			foreach($actionArray as $actionWords) {
				if (array_key_exists($actionWords, $actionCount) === true) {
					$actionCount[$actionWords] = $actionCount[$actionWords] + 1;
				} else {
					$actionCount[$actionWords] = 1;
				}
			}
		}
        $averaRating = $rating/$numberOfComments;

		return $data = array(
			'rating' => $averaRating,
			'top_actions' => $actionCount,
		);
	}
}