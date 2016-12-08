<?php

// Fuzzy Search based on Levenshtein distance between two strings
class fuzzySearch
{
    /**
     * @var array
     */
    private $sourceWordsArray = array();

    /**
     * @var int
     */
    private $expectedMinimumAccuracy;

    /**
     * fuzzySearch constructor.
     * @param array $words
     */
    public function __construct(array $words)
    {
        foreach ($words as $word) {
            $this->sourceWordsArray[] = strtolower($word);
        }
        $this->expectedMinimumAccuracy = EXPECTED_ACCURACY_FOR_WORD_MATCH;
    }

    /**
     *
     * @param $input
     * @return bool
     */
    public function searchWord($input)
    {
        // no shortest distance found, yet
        $shortest = -1;

        //var_dump($this->sourceWordsArray); die;

        // loop through words to find the closest
        foreach ($this->sourceWordsArray as $word) {

            // calculate the distance between the input word,
            // and the current word
            $lev = levenshtein($input, $word);

            // check for an exact match
            if ($lev == 0) {

                // closest word is this one (exact match)
                $closest = $word;
                $shortest = 0;

                // break out of the loop; we've found an exact match
                break;
            }

            // if this distance is less than the next found shortest
            // distance, OR if a next shortest word has not yet been found
            if ($lev <= $shortest || $shortest < 0) {
                // set the closest match, and shortest distance
                $closest = $word;
                $shortest = $lev;
            }
        }

        if ($shortest == 0) {
            return true;
        } else {
            /**
             * find out relevance of input word with the closest match.
             * Accuracy should be within the range defined.
             */
            similar_text($input, $closest, $accuracy);
            if ($accuracy >= $this->expectedMinimumAccuracy) {
                return true;
            }
        }

        return false;
    }
}
