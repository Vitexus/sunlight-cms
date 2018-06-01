<?php

namespace Sunlight\Plugin;

use Sunlight\Core;

abstract class TemplateService
{
    const UID_TEMPLATE = 0;
    const UID_TEMPLATE_LAYOUT = 1;
    const UID_TEMPLATE_LAYOUT_SLOT = 2;

    /**
     * Check if a template exists
     *
     * @param string $idt
     * @return bool
     */
    static function templateExists($idt)
    {
        return Core::$pluginManager->has(PluginManager::TEMPLATE, $idt);
    }

    /**
     * Get a template for the given template identifier
     *
     * @param string $id
     * @return TemplatePlugin
     */
    static function getTemplate($id)
    {
        return Core::$pluginManager->getTemplate($id);
    }

    /**
     * Get default template
     *
     * @return TemplatePlugin
     */
    static function getDefaultTemplate()
    {
        return static::getTemplate(_default_template);
    }

    /**
     * Compose unique template component identifier
     *
     * @param string|TemplatePlugin $template
     * @param string|null           $layout
     * @param string|null           $slot
     * @return string
     */
    static function composeUid($template, $layout = null, $slot = null)
    {
        $uid = $template instanceof TemplatePlugin
            ? $template->getId()
            : $template;

        if ($layout !== null || $slot !== null) {
            $uid .= ':' . $layout;
        }
        if ($slot !== null) {
            $uid .= ':' . $slot;
        }

        return $uid;
    }

    /**
     * Parse the given unique template component identifier
     *
     * @param string $uid
     * @param int    $type see \Sunlight\Plugin\TemplateService::UID_* constants
     * @return string[] template, [layout], [slot]
     */
    static function parseUid($uid, $type)
    {
        $expectedComponentCount = $type + 1;

        return explode(':', $uid, $expectedComponentCount) + array_fill(0, $expectedComponentCount, '');
    }

    /**
     * Verify that the given unique template component identifier is valid
     * and points to existing components
     *
     * @param string $uid
     * @param int    $type see \Sunlight\Plugin\TemplateService::UID_* constants
     * @return bool
     */
    static function validateUid($uid, $type)
    {
        return static::getComponentsByUid($uid, $type) !== null;
    }

    /**
     * Get components identified by the given unique template component identifier
     *
     * @param string $uid
     * @param int    $type see \Sunlight\Plugin\TemplateService::UID_* constants
     * @return array|null array or null if the given identifier is not valid
     */
    static function getComponentsByUid($uid, $type)
    {
        return call_user_func_array(
            array(get_called_class(), 'getComponents'),
            static::parseUid($uid, $type)
        );
    }

    /**
     * Get template components
     *
     * Returns an array with the following keys or NULL if the given
     * combination does not exist.
     *
     *      template => (object) instance of TemplatePlugin
     *      layout   => (string) layout identifier (only if $layout is not NULL)
     *      slot     => (string) slot identifier (only if both $layout and $slot are not NULL)
     *
     * @param string      $template
     * @param string|null $layout
     * @param string|null $slot
     * @return array|null array or null if the given combination does not exist
     */
    static function getComponents($template, $layout = null, $slot = null)
    {
        if (!static::templateExists($template)) {
            return null;
        }

        $template = static::getTemplate($template);

        $components = array(
            'template' => $template,
        );

        if ($layout !== null) {
            if (!$template->hasLayout($layout)) {
                return null;
            }

            $components['layout'] = $layout;
        }

        if ($slot !== null && $layout !== null) {
            if (!$template->hasSlot($layout, $slot)) {
                return null;
            }

            $components['slot'] = $slot;
        }

        return $components;
    }

    /**
     * Get label for the given components
     *
     * @param TemplatePlugin $template
     * @param string|null    $layout
     * @param string|null    $slot
     * @param bool           $includeTemplateName
     * @return string
     */
    static function getComponentLabel(TemplatePlugin $template, $layout = null, $slot = null, $includeTemplateName = true)
    {
        $parts = array();

        if ($includeTemplateName) {
            $parts[] = $template->getOption('name');
        }
        if ($layout !== null || $slot !== null) {
            $parts[] = $template->getLayoutLabel($layout);
        }
        if ($slot !== null) {
            $parts[] = $template->getSlotLabel($layout, $slot);
        }

        return implode(' - ', $parts);
    }

    /**
     * Get label for the given component array
     *
     * @see \Sunlight\Plugin\TemplateService::getComponents()
     *
     * @param array $components
     * @param bool  $includeTemplateName
     * @return string
     */
    static function getComponentLabelFromArray(array $components, $includeTemplateName = true)
    {
        return static::getComponentLabel(
            $components['template'],
            isset($components['layout']) ? $components['layout'] : null,
            isset($components['slot']) ? $components['slot'] : null,
            $includeTemplateName
        );
    }

    /**
     * Get label for the given unique template component identifier
     *
     * @param string|null $uid
     * @param int         $type see \Sunlight\Plugin\TemplateService::UID_* constants
     * @param bool        $includeTemplateName
     * @return string
     */
    static function getComponentLabelByUid($uid, $type, $includeTemplateName = true)
    {
        if ($uid !== null) {
            $components = static::getComponentsByUid($uid, $type);
        } else {
            $components = array(
                'template' => static::getDefaultTemplate(),
            );

            if ($type >= static::UID_TEMPLATE_LAYOUT) {
                $components['layout'] = TemplatePlugin::DEFAULT_LAYOUT;
            }
            if ($type >= static::UID_TEMPLATE_LAYOUT_SLOT) {
                $components['slot'] = '';
            }
        }

        if ($components !== null) {
            return static::getComponentLabelFromArray($components, $includeTemplateName);
        }

        return $uid;
    }
}
