<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Service
 */

namespace ZendService\Delicious;

use DateTime;

/**
 * Zend_Service_Delicious_Post represents a post of a user that can be edited
 *
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Delicious
 */
class Post extends SimplePost
{
    /**
     * Service that has downloaded the post
     *
     * @var Zend_Service_Delicious
     */
    protected $service;

    /**
     * @var int Number of people that have the same post
     */
    protected $others;

    /**
     * @var DateTime Post date
     */
    protected $date;

    /**
     * @var bool Post share
     */
    protected $shared = true;

    /**
     * @var string Post hash
     */
    protected $hash;

    /**
     * Constructs a new del.icio.us post
     *
     * @param  Zend_Service_Delicious $service Service that has downloaded the post
     * @param  DOMElement|array       $values  Post content
     * @throws Zend_Service_Delicious_Exception
     * @return void
     */
    public function __construct(Delicious $service, $values)
    {
        $this->service = $service;

        if ($values instanceof \DOMElement) {
            $values = self::parsePostNode($values);
        }

        if (!is_array($values) || !isset($values['url']) || !isset($values['title'])) {
            throw new Exception("Second argument must be array with at least 2 keys ('url' and"
                                                     . " 'title')");
        }

        if (isset($values['date']) && !$values['date'] instanceof DateTime) {
            throw new Exception("Date has to be an instance of DateTime");
        }

        foreach (array('url', 'title', 'notes', 'others', 'tags', 'date', 'shared', 'hash') as $key) {
            if (isset($values[$key])) {
                $this->{"_$key"}  = $values[$key];
            }
        }
    }

    /**
     * Setter for title
     *
     * @param  string $newTitle
     * @return Zend_Service_Delicious_Post
     */
    public function setTitle($newTitle)
    {
        $this->_title = (string) $newTitle;

        return $this;
    }

    /**
     * Setter for notes
     *
     * @param  string $newNotes
     * @return Zend_Service_Delicious_Post
     */
    public function setNotes($newNotes)
    {
        $this->_notes = (string) $newNotes;

        return $this;
    }

    /**
     * Setter for tags
     *
     * @param  array $tags
     * @return Zend_Service_Delicious_Post
     */
    public function setTags(array $tags)
    {
        $this->_tags = $tags;

        return $this;
    }

    /**
     * Add a tag
     *
     * @param  string $tag
     * @return Zend_Service_Delicious_Post
     */
    public function addTag($tag)
    {
        $this->_tags[] = (string) $tag;

        return $this;
    }

    /**
     * Remove a tag
     *
     * @param  string $tag
     * @return Zend_Service_Delicious_Post
     */
    public function removeTag($tag)
    {
        $this->_tags = array_diff($this->_tags, array((string) $tag));

        return $this;
    }

    /**
     * Getter for date
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Getter for others
     *
     * This property is only populated when posts are retrieved
     * with getPosts() method. The getAllPosts() and getRecentPosts()
     * methods will not populate this property.
     *
     * @return int
     */
    public function getOthers()
    {
        return $this->others;
    }

    /**
     * Getter for hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Getter for shared
     *
     * @return bool
     */
    public function getShared()
    {
        return $this->shared;
    }

    /**
     * Setter for shared
     *
     * @param  bool $isShared
     * @return Zend_Service_Delicious_Post
     */
    public function setShared($isShared)
    {
        $this->shared = (bool) $isShared;

        return $this;
    }

    /**
     * Deletes post
     *
     * @return Zend_Service_Delicious
     */
    public function delete()
    {
        return $this->service->deletePost($this->_url);
    }

    /**
     * Saves post
     *
     * @return DOMDocument
     */
    public function save()
    {
        $parms = array(
            'url'        => $this->_url,
            'description'=> $this->_title,
            'extended'   => $this->_notes,
            'shared'     => ($this->shared ? 'yes' : 'no'),
            'tags'       => implode(' ', (array) $this->_tags),
            'replace'    => 'yes'
        );

        return $this->service->makeRequest(Delicious::PATH_POSTS_ADD, $parms);
    }

    /**
     * Extracts content from the DOM element of a post
     *
     * @param  DOMElement $node
     * @return array
     */
    protected static function parsePostNode(\DOMElement $node)
    {
        return array(
            'url'    => $node->getAttribute('href'),
            'title'  => $node->getAttribute('description'),
            'notes'  => $node->getAttribute('extended'),
            'others' => (int) $node->getAttribute('others'),
            'tags'   => explode(' ', $node->getAttribute('tag')),
            'date'   => new DateTime($node->getAttribute('time')),
            'shared' => ($node->getAttribute('shared') == 'no' ? false : true),
            'hash'   => $node->getAttribute('hash')
        );
    }
}
