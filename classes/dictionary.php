<?php

class dictionary
{
    /**
     * @var string
     */
    private $path = '';

    /**
     * @var string
     */
    private $categoryName = '';

    /**
     * @var array
     */
    private $availableCategories = array();

    /**
     * @var array
     */
    private static $data = array();


    public function __construct($categoryName)
    {
        $this->path = './dictionary';
        $this->categoryName = $categoryName;
        // Do not load dictionary, if it's already loaded.
        if (!isset(self::$data[$categoryName]) || !count(self::$data[$categoryName])) {
            $this->loadDictionary($categoryName);
        }
    }

    /**
     * @param $category
     */
    private function loadDictionary($category)
    {
        $scanned_directory = array_diff(scandir($this->path), array('..', '.'));

        foreach ($scanned_directory as $file) {
            if (preg_match('/' . $this->categoryName . '?/', $file)) {
                preg_match('/-(.*).txt/', $file, $ans);
                $fileList[$ans[1]] = file($this->path . '/' . $file);
                $this->availableCategories[] = $ans[1];
            }
        }
        $this->data = $fileList;
    }

    /**
     * @param $inputWord
     * @param $category
     * @return bool
     */
    public function findMatch($inputWord, $category)
    {
        // Initialize Fuzzy Search.
        $sourceWords = $this->data[$category];
        $fuzzySearch = new fuzzySearch($sourceWords);
        return $fuzzySearch->searchWord(strtolower($inputWord));
    }

    /**
     * @return array
     */
    public  function getAvailableCategories()
    {
        return $this->availableCategories;
    }
}
