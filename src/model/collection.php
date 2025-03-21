<?php
/**
 * Class Collection
 * 
 * Represents a collection of slides grouped together for easier management
 * and multi-zoom view creation.
 */
class Collection {
    // Collection properties
    public $id;
    public $name;
    public $description;
    public $slides = [];
    public $dateCreated;
    public $dateModified;
    public $tags = [];
    public $multizoomPath;
    
    /**
     * Constructor
     * 
     * @param string $name Collection name
     * @param string $description Collection description
     */
    public function __construct($name, $description = '') {
        $this->id = uniqid('collection_');
        $this->name = $name;
        $this->description = $description;
        $this->dateCreated = time();
        $this->dateModified = time();
    }
    
    /**
     * Load a collection from its ID
     * 
     * @param string $id Collection ID
     * @return Collection|null The collection or null if not found
     */
    public static function load($id) {
        $collectionsDir = defined('DATA_DIR') ? DATA_DIR . '/collections' : __DIR__ . '/../../www/data/collections';
        $filePath = $collectionsDir . '/' . $id . '.json';
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($filePath), true);
        if (!$data) {
            return null;
        }
        
        $collection = new self($data['name'], $data['description']);
        $collection->id = $data['id'];
        $collection->slides = $data['slides'] ?? [];
        $collection->dateCreated = $data['dateCreated'] ?? time();
        $collection->dateModified = $data['dateModified'] ?? time();
        $collection->tags = $data['tags'] ?? [];
        $collection->multizoomPath = $data['multizoomPath'] ?? null;
        
        return $collection;
    }
    
    /**
     * Get all collections
     * 
     * @return array List of all collections
     */
    public static function getAll() {
        $collectionsDir = defined('DATA_DIR') ? DATA_DIR . '/collections' : __DIR__ . '/../../www/data/collections';
        
        if (!is_dir($collectionsDir)) {
            mkdir($collectionsDir, 0755, true);
        }
        
        $files = glob($collectionsDir . '/*.json');
        $collections = [];
        
        foreach ($files as $file) {
            $id = pathinfo($file, PATHINFO_FILENAME);
            $collection = self::load($id);
            if ($collection) {
                $collections[] = $collection;
            }
        }
        
        // Sort by most recently modified
        usort($collections, function($a, $b) {
            return $b->dateModified - $a->dateModified;
        });
        
        return $collections;
    }
    
    /**
     * Add a slide to the collection
     * 
     * @param string $slideId ID of the slide to add
     * @return bool Success or failure
     */
    public function addSlide($slideId) {
        if (!in_array($slideId, $this->slides)) {
            $this->slides[] = $slideId;
            $this->dateModified = time();
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove a slide from the collection
     * 
     * @param string $slideId ID of the slide to remove
     * @return bool Success or failure
     */
    public function removeSlide($slideId) {
        $key = array_search($slideId, $this->slides);
        if ($key !== false) {
            unset($this->slides[$key]);
            $this->slides = array_values($this->slides); // Re-index array
            $this->dateModified = time();
            return true;
        }
        
        return false;
    }
    
    /**
     * Add tags to the collection
     * 
     * @param array $tags Tags to add
     * @return bool Success or failure
     */
    public function addTags($tags) {
        $this->tags = array_unique(array_merge($this->tags, $tags));
        $this->dateModified = time();
        return true;
    }
    
    /**
     * Remove tags from the collection
     * 
     * @param array $tags Tags to remove
     * @return bool Success or failure
     */
    public function removeTags($tags) {
        $this->tags = array_diff($this->tags, $tags);
        $this->dateModified = time();
        return true;
    }
    
    /**
     * Save the collection
     * 
     * @return bool Success or failure
     */
    public function save() {
        $collectionsDir = defined('DATA_DIR') ? DATA_DIR . '/collections' : __DIR__ . '/../../www/data/collections';
        
        if (!is_dir($collectionsDir)) {
            mkdir($collectionsDir, 0755, true);
        }
        
        $filePath = $collectionsDir . '/' . $this->id . '.json';
        
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slides' => $this->slides,
            'dateCreated' => $this->dateCreated,
            'dateModified' => $this->dateModified,
            'tags' => $this->tags,
            'multizoomPath' => $this->multizoomPath
        ];
        
        return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * Delete the collection
     * 
     * @return bool Success or failure
     */
    public function delete() {
        $collectionsDir = defined('DATA_DIR') ? DATA_DIR . '/collections' : __DIR__ . '/../../www/data/collections';
        $filePath = $collectionsDir . '/' . $this->id . '.json';
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }
    
    /**
     * Create a multi-zoom view from this collection
     * 
     * @param SlideManager $slideManager The slide manager instance
     * @param string $viewName Optional name for the view
     * @return array Result of operation
     */
    public function createMultizoomView($slideManager, $viewName = '') {
        if (empty($this->slides)) {
            return [
                'status' => 'error',
                'message' => 'No slides in the collection'
            ];
        }
        
        // Generate a default view name if not provided
        if (empty($viewName)) {
            $viewName = 'collection_' . $this->id . '_' . date('Ymd_His');
        }
        
        $result = $slideManager->createMultizoomView($this->slides, $viewName);
        
        if ($result['status'] === 'success') {
            $this->multizoomPath = $result['result']['viewPath'] ?? null;
            $this->save();
        }
        
        return $result;
    }
    
    /**
     * Get the formatted date created
     * 
     * @return string Formatted date
     */
    public function getFormattedDateCreated() {
        return date("Y-m-d H:i:s", $this->dateCreated);
    }
    
    /**
     * Get the formatted date modified
     * 
     * @return string Formatted date
     */
    public function getFormattedDateModified() {
        return date("Y-m-d H:i:s", $this->dateModified);
    }
}