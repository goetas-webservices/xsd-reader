<?php

namespace GoetasWebservices\XML\XSDReader\Schema;

use GoetasWebservices\XML\XSDReader\SchemaReaderLoadAbstraction;
use GoetasWebservices\XML\XSDReader\Schema\Exception\TypeNotFoundException;

class Schema extends AbstractSchema
{
    /**
     * {@inheritdoc}
     */
    protected function findSomethingNoThrow(
        $getter,
        $name,
        $namespace = null,
        array &$calling = array()
    ) {
        $calling[spl_object_hash($this)] = true;
        $cid = "$getter, $name, $namespace";

        if (isset($this->typeCache[$cid])) {
            return $this->typeCache[$cid];
        } elseif (
            $this->getTargetNamespace() === $namespace
        ) {
            /**
             * @var SchemaItem|null
             */
            $item = $this->$getter($name);

            if ($item instanceof SchemaItem) {
                return $this->typeCache[$cid] = $item;
            }
        }

        return $this->findSomethingNoThrowSchemas(
            $this->getSchemas(),
            $cid,
            $getter,
            $name,
            $namespace,
            $calling
        );
    }

    /**
     * @param Schema[] $schemas
     *                          {@inheritdoc}
     */
    protected function findSomethingNoThrowSchemas(
        array $schemas,
        $cid,
        $getter,
        $name,
        $namespace = null,
        array &$calling = array()
    ) {
        foreach ($schemas as $childSchema) {
            if (!isset($calling[spl_object_hash($childSchema)])) {
                /**
                 * @var SchemaItem|null
                 */
                $in = $childSchema->findSomethingNoThrow($getter, $name, $namespace, $calling);

                if ($in instanceof SchemaItem) {
                    return $this->typeCache[$cid] = $in;
                }
            }
        }
    }

    /**
     * @param string $getter
     *                       {@inheritdoc}
     */
    protected function findSomething($getter, $name, $namespace = null, &$calling = array())
    {
        $in = $this->findSomethingNoThrow(
            $getter,
            $name,
            $namespace,
            $calling
        );

        if ($in instanceof SchemaItem) {
            return $in;
        }

        throw new TypeNotFoundException(sprintf("Can't find the %s named {%s}#%s.", substr($getter, 3), $namespace, $name));
    }

    /**
     * @param string $namespace
     * @param string $file
     *                          {@inheritdoc}
     */
    protected static function loadImportFreshKeys(
        SchemaReaderLoadAbstraction $reader,
        $namespace,
        $file
    ) {
        $globalSchemaInfo = $reader->getGlobalSchemaInfo();

        $keys = [];

        if (isset($globalSchemaInfo[$namespace])) {
            $keys[] = $globalSchemaInfo[$namespace];
        }

        $keys[] = $reader->getNamespaceSpecificFileIndex(
            $file,
            $namespace
        );

        $keys[] = $file;

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    protected static function loadImportFreshCallbacksNewSchema(
        $namespace,
        SchemaReaderLoadAbstraction $reader,
        Schema $schema,
        $file
    ) {
        /**
         * @var string
         */
        $newSchema = self::setLoadedFile(
            $file,
            ($namespace ? new self() : $schema)
        );

        if ($namespace) {
            $newSchema->addSchema($reader->getGlobalSchema());
            $schema->addSchema($newSchema);
        }

        return $newSchema;
    }

    /**
     * {@inheritdoc}
     */
    protected static function loadImportFreshCallbacks(
        $namespace,
        SchemaReaderLoadAbstraction $reader,
        Schema $schema,
        $file
    ) {
        /**
         * @var string
         */
        $file = $file;

        return $reader->schemaNode(
            static::loadImportFreshCallbacksNewSchema(
                $namespace,
                $reader,
                $schema,
                $file
            ),
            $reader->getDOM(
                $reader->hasKnownSchemaLocation($file)
                    ? $reader->getKnownSchemaLocation($file)
                    : $file
            )->documentElement,
            $schema
        );
    }

    /**
     * {@inheritdoc}
     */
    protected static function loadImportFresh(
        $namespace,
        SchemaReaderLoadAbstraction $reader,
        Schema $schema,
        $file
    ) {
        return function () use ($namespace, $reader, $schema, $file) {
            foreach (
                static::loadImportFreshCallbacks(
                    $namespace,
                    $reader,
                    $schema,
                    $file
                ) as $callback
            ) {
                $callback();
            }
        };
    }
}
