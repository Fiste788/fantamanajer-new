<?php

namespace App\Utility\WebPush;

use JsonSerializable;

class WebPushMessage implements JsonSerializable {

    /**
     * The notification title.
     *
     * @var string
     */
    protected $title;

    /**
     * The notification body.
     *
     * @var string
     */
    protected $body;

    /**
     * The notification icon.
     *
     * @var string
     */
    protected $icon = null;

    /**
     * The notification actions.
     *
     * @var array
     */
    protected $actions = [];

    /**
     * The badge icon.
     *
     * @var string
     */
    protected $badge = null;

    /**
     * The text direction.
     *
     * @var string
     */
    protected $dir = 'auto';

    /**
     * The language.
     *
     * @var string
     */
    protected $lang = null;

    /**
     * The renotify.
     *
     * @var boolean
     */
    protected $renotify = true;

    /**
     * The renotify.
     *
     * @var boolean
     */
    protected $requireInteraction = true;

    /**
     * The tag for grouping
     * 
     * @var tag
     */
    protected $tag = null;
    
    /**
     * The vibrate.
     *
     * @var integer[]
     */
    protected $vibrate = [300, 200, 300];
    
    /**
     * The data object.
     *
     * @var array
     */
    protected $data = [];

    /**
     * @param string $body
     *
     * @return static
     */
    public static function create($body = '') {
        return new static($body);
    }

    /**
     * @param string $body
     */
    public function __construct($body = '') {
        $this->title = '';

        $this->body = $body;
    }

    /**
     * Set the notification title.
     *
     * @param  string $value
     * @return $this
     */
    public function title($value) {
        $this->title = $value;

        return $this;
    }

    /**
     * Set the notification body.
     *
     * @param  string $value
     * @return $this
     */
    public function body($value) {
        $this->body = $value;

        return $this;
    }

    /**
     * Set the notification icon.
     *
     * @param  string $value
     * @return $this
     */
    public function icon($value) {
        $this->icon = $value;

        return $this;
    }

    /**
     * Set an action.
     *
     * @param  string $title
     * @param  string $action
     * @return $this
     */
    public function action($title, $action, $icon = '') {
        $this->actions[] = compact('title', 'action', 'icon');

        return $this;
    }

    function badge($badge) {
        $this->badge = $badge;
        return $this;
    }

    function dir($dir) {
        $this->dir = $dir;
        return $this;
    }

    function lang($lang) {
        $this->lang = $lang;
        return $this;
    }

    function renotify($renotify) {
        $this->renotify = $renotify;
        return $this;
    }

    function requireInteraction($requireInteraction) {
        $this->requireInteraction = $requireInteraction;
        return $this;
    }
    
    function tag($tag) {
        $this->tag = $tag;
        return $this;
    }

    function vibrate($vibrate) {
        $this->vibrate = $vibrate;
        return $this;
    }
    
    function data($data = []) {
        $this->data = $data;
        return $this;
    }

    public function jsonSerialize() {
        return [
            'notification' => [
                'title' => $this->title,
                'actions' => $this->actions,
                'body' => $this->body,
                'dir' => $this->dir,
                'icon' => $this->icon,
                'badge' => $this->badge,
                'lang' => $this->lang,
                'renotify' => $this->renotify,
                'requireInteraction' => $this->requireInteraction,
                'tag' => $this->tag,
                'vibrate' => $this->vibrate,
                'data' => $this->data
            ]
        ];
    }

}
