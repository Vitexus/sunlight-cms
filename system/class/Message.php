<?php

namespace Sunlight;

use Sunlight\Util\StringManipulator;

/**
 * System message
 */
class Message
{
    const OK = 'ok';
    const WARNING = 'warn';
    const ERROR = 'err';

    /** @var string */
    private $type;
    /** @var string */
    private $message;
    /** @var bool */
    private $isHtml;

    /**
     * @param string $type    see Message class constants
     * @param string $message the message
     * @param bool   $isHtml  display the message should be rendered as html (unescaped) 1/0
     */
    function __construct(string $type, string $message, bool $isHtml = false)
    {
        $this->type = $type;
        $this->message = $message;
        $this->isHtml = $isHtml;
    }

    /**
     * Render a message
     *
     * @see Message::__construct()
     */
    static function render(string $type, string $message, bool $isHtml = false): string
    {
        $message = new Message($type, $message, $isHtml);

        return $message->__toString();
    }

    /**
     * Render a formatted list of messages
     *
     * @param array       $messages    the messages
     * @param string|null $description description ("errors" = _lang('misc.errorlog.intro'), null = none, anything else = custom)
     * @param bool        $showKeys    render $message keys as well
     * @return string
     */
    static function renderList(array $messages, ?string $description = null, bool $showKeys = false): string
    {
        $output = '';

        if (!empty($messages)) {
            // description
            if ($description != null) {
                if ($description !== 'errors') {
                    $output .= $description;
                } else {
                    $output .= _lang('misc.errorlog.intro');
                }
                $output .= "\n";
            }

            // messages
            $output .= "<ul>\n";
            foreach($messages as $key => $item) {
                $output .= '<li>' . ($showKeys ? '<strong>' . _e($key) . '</strong>: ' : '') . StringManipulator::lcfirst($item) . "</li>\n";
            }
            $output .= "</ul>\n";
        }

        return $output;
    }

    /**
     * Create an informational message
     *
     * @param string $message the message
     * @param bool   $isHtml  display the message should be rendered as html (unescaped) 1/0
     * @return self
     */
    static function ok(string $message, bool $isHtml = false): self
    {
        return new self(self::OK, $message, $isHtml);
    }

    /**
     * Create a warning message
     *
     * @param string $message the message
     * @param bool   $isHtml  display the message should be rendered as html (unescaped) 1/0
     * @return self
     */
    static function warning(string $message, bool $isHtml = false): self
    {
        return new self(self::WARNING, $message, $isHtml);
    }

    /**
     * Create an error message
     *
     * @param string $message the message
     * @param bool   $isHtml  display the message should be rendered as html (unescaped) 1/0
     * @return self
     */
    static function error(string $message, bool $isHtml = false): self
    {
        return new self(self::ERROR, $message, $isHtml);
    }

    /**
     * Prefix a message
     */
    static function prefix(string $prefix, string $message): string
    {
        return $prefix . ': ' . StringManipulator::lcfirst($message);
    }

    /**
     * Render the message
     *
     * @return string
     */
    function __toString(): string
    {
        $output = Extend::buffer('message.render', ['message' => $this]);

        if ($output === '') {
            $output = "\n<div class='message message-" . _e($this->type) . "'>"
                . ($this->isHtml ? $this->message : _e($this->message))
                . "</div>\n";
        }

        return $output;
    }

    /**
     * Get the message type
     *
     * @return string
     */
    function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the message
     *
     * @return string
     */
    function getMessage(): string
    {
        return $this->message;
    }

    /**
     * See if the message is HTML
     *
     * @return bool
     */
    function isHtml(): bool
    {
        return $this->isHtml;
    }

    /**
     * Append a string to the message
     *
     * This forces the message to become HTML, if it isn't already
     *
     * @param string $str
     * @param bool   $isHtml
     */
    function append(string $str, bool $isHtml = false): void
    {
        if ($this->isHtml) {
            // append to current HTML
            $this->message .= $isHtml ? $str : _e($str);
        } elseif ($isHtml) {
            // convert message to HTML
            $this->message = _e($this->message) . $str;
            $this->isHtml = true;
        } else {
            // append as-is
            $this->message .= $str;
        }
    }
}
