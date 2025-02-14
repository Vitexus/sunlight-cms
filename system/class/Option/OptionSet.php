<?php

namespace Sunlight\Option;

class OptionSet
{
    /** @var array */
    private $definition;
    /** @var array */
    private $knownIndexMap = [];
    /** @var bool */
    private $ignoreExtraIndexes = false;

    /**
     * Definition format:
     * ------------------
     * array(
     *      index1 => array(
     *          type            => scalar / boolean / integer / double / string / array / object / resource / NULL
     *          [required]      => true / false (false)
     *          [nullable]      => true / false (false)
     *          [default]       => anything (null)
     *          [normalizer]    => callback(mixed value, mixed context): mixed that should return the normalized value
     *                             (it is applied to the default value too and can throw OptionSetNormalizerException)
     *      ),
     *      ...
     * )
     *
     * @param array    $definition
     */
    function __construct(array $definition)
    {
        $this->definition = $definition;
    }

    /**
     * Get list of indexes that are valid for this set
     *
     * @return string[]
     */
    function getIndexes(): array
    {
        return array_merge(
            array_keys($this->definition),
            array_keys($this->knownIndexMap)
        );
    }

    /**
     * Add known indexes (additional allowed indexes)
     *
     * @param string[] $knownIndexes
     * @return $this
     */
    function addKnownIndexes(array $knownIndexes): self
    {
        $this->knownIndexMap += array_flip($knownIndexes);

        return $this;
    }

    /**
     * Set or replace known indexes (additional allowed indexes)
     *
     * @param string[] $knownIndexes
     * @return $this
     */
    function setKnownIndexes(array $knownIndexes): self
    {
        $this->knownIndexMap = array_flip($knownIndexes);

        return $this;
    }

    /**
     * See whether extra indexes are ignored
     *
     * @return bool
     */
    function getIgnoreExtraIndexes(): bool
    {
        return $this->ignoreExtraIndexes;
    }

    /**
     * Set whether to ignore unknown indexes
     *
     * @param bool $ignoreExtraIndexes
     * @return $this
     */
    function setIgnoreExtraIndexes(bool $ignoreExtraIndexes): self
    {
        $this->ignoreExtraIndexes = $ignoreExtraIndexes;

        return $this;
    }

    /**
     * Process given data
     *
     * The data may be modified by default values.
     *
     * @param array      &$data   data to process
     * @param mixed      $context normalizer context
     * @param array|null $errors  variable for error messages
     * @return bool true on success, false if there are errors
     */
    function process(array &$data, $context = null, ?array &$errors = null): bool
    {
        $errors = [];

        if (!is_array($data)) {
            $errors['_'] = sprintf('option data must be an array (got %s)', gettype($data));
            
            return false;
        }

        foreach ($this->definition as $index => $entry) {
            $indexIsValid = true;

            // validate
            if (array_key_exists($index, $data)) {
                // type
                if (
                    $entry['type'] === 'scalar' && !is_scalar($data[$index])
                    || (
                        $entry['type'] !== 'scalar'
                        && ($type = gettype($data[$index])) !== $entry['type']
                        && (
                            $data[$index] !== null
                            || !isset($entry['nullable'])
                            || !$entry['nullable']
                        )
                    )
                ) {
                    // invalid type
                    $errors[$index] = sprintf(
                        'expected "%s" to be "%s", got "%s"',
                        $index,
                        $entry['type'],
                        $type
                    );
                    $indexIsValid = false;
                }
            } elseif (!empty($entry['required'])) {
                // missing required
                $errors[$index] = sprintf('"%s" is required', $index);
                $indexIsValid = false;
            } else {
                // default value
                $data[$index] = $entry['default'] ?? null;
            }

            // normalize
            if ($indexIsValid && isset($entry['normalizer'])) {
                try {
                    $data[$index] = $entry['normalizer']($data[$index], $context);
                } catch (OptionSetNormalizerException $e) {
                    $errors[$index] = $e->getMessage();
                }
            }
        }

        if (!$this->ignoreExtraIndexes) {
            foreach (array_keys(array_diff_key($data, $this->definition, $this->knownIndexMap)) as $extraKey) {
                $errors[$extraKey] = sprintf('unknown option "%s"', $extraKey);
            }
        }

        return empty($errors);
    }
}
