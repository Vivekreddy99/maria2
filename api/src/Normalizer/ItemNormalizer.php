<?php

namespace App\Normalizer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Serializer\CustomItemNormalizer;

/**
 * This class overrides api-platform's built in ItemNormalizer in order to make it possible to POST resources
 * with custom provided ID.
 *
 * Related not merged PR and discussion: https://github.com/api-platform/core/pull/2022
 *   https://github.com/api-platform/api-platform/issues/343
 *   https://gist.github.com/moay/47ef07b67d701c2ef7355d0bbba8b4d6
 */
class ItemNormalizer extends AbstractItemNormalizer
{
    private const IDENTIFIER = 'id';

    /**
     * @var LoggerInterface
     */
    private $logger;
    private $customItemNormalizer;

    private $replaceIdsWithIRIs = [
        'ep' => 'entry_points',
        'ep_id' => 'entry_points',
        'entry_point' => 'entry_points',
        'manifest' => 'manifests',
        'manifest_id' => 'manifests',
        'overpack_id' => 'overpacks',
        'overpacks' => 'overpacks', // For Manifests POST
        'overpacks_details' => 'overpacks',
        'shipments' => 'shipments',
        'product_id' => 'products',
    ];
    private $iriPrefix = '/v2/';

    private $tokenStorage;

    public function __construct(
        PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        IriConverterInterface $iriConverter,
        ResourceClassResolverInterface $resourceClassResolver,
        PropertyAccessorInterface $propertyAccessor = null,
        NameConverterInterface $nameConverter = null,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        ItemDataProviderInterface $itemDataProvider = null,
        bool $allowPlainIdentifiers = false,
        LoggerInterface $logger = null,
        iterable $dataTransformers = [],
        ResourceMetadataFactoryInterface $resourceMetadataFactory = null,
        TokenStorageInterface $tokenStorage,
        CustomItemNormalizer $customItemNormalizer
    )
    {
        parent::__construct(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $iriConverter,
            $resourceClassResolver,
            $propertyAccessor,
            $nameConverter,
            $classMetadataFactory,
            $itemDataProvider,
            $allowPlainIdentifiers,
            [],
            $dataTransformers,
            $resourceMetadataFactory
        );

        $this->customItemNormalizer = $customItemNormalizer;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @param mixed $data
     * @param string $class
     * @param string $format
     * @param array $context
     *
     * @return object
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        //  Original code, not used.
        //     $data = $this->autoConvertIdsToIris($data, $class);
        //     $context['api_denormalize'] = true;
        //     if (!isset($context['resource_class'])) {
        //         $context['resource_class'] = $class;
        //     }
        //     $this->setObjectToPopulate($data, $context);

        // Only used for Shops which requires custom ids.
        if ($class == 'App\Entity\Shops' && isset($data['shop'])) {
            // Fix for setting custom id in Shops POST.
            if (isset($context['collection_operation_name']) && $context['collection_operation_name'] == 'post'){
                $flds = ['active', 'id', 'name', 'test'];
                foreach($flds as $fld) {
                    if (isset($data['shop'][$fld])) {
                        $data[$fld] = $data['shop'][$fld];
                    }
                }
            } elseif (isset($context['item_operation_name']) && $context['item_operation_name'] == 'put') {
                $flds = ['active', 'name'];
                foreach($flds as $fld) {
                    if (isset($data['shop'][$fld])) {
                        $data[$fld] = $data['shop'][$fld];
                    }
                }
            }

            // Restructure Shops settings object.
            if (isset($data['shop']['settings']) && is_array($data['shop']['settings'])) {
                if (isset($data['shop']['settings']['delay_processing'])) {
                    $data['delay_processing'] = $data['shop']['settings']['delay_processing'];
                }
                if (isset($data['shop']['settings']['partial_fulfillment'])) {
                    $data['default_partial'] = $data['shop']['settings']['partial_fulfillment'];
                }
                unset($data['shop']['settings']);
            }
        }
        else {
            return $this->customItemNormalizer->denormalize($data, $class, $format, $context);
        }

        // Add current user.
        $uid = $this->tokenStorage->getToken()->getUser()->getUserObject()->getId();
        if (is_int($uid) && intval($uid) > 0) {
            $data['user'] = '/v2/users/' . $uid;
        }

        return parent::denormalize($data, $class, $format, $context);
    }

    /**
     * @param $data
     * @param $class
     *
     * @throws \ApiPlatform\Core\Exception\PropertyNotFoundException
     */
    private function autoConvertIdsToIris($data, $class)
    {
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                $propertyMetadata = $this->propertyMetadataFactory->create($class, $key);
                $propertyType = $propertyMetadata->getType();

                if (
                    null === $value
                    || !$propertyType instanceof Type
                    || null === $propertyType->getClassName()
                    || !$this->resourceClassResolver->isResourceClass($propertyType->getClassName())
                ) {
                    continue;
                }

                if (is_array($value)) {
                    $value = $this->autoConvertIdsToIris($value, $propertyType->getClassName());
                    continue;
                }

                try {
                    $value = $this->iriConverter->getItemIriFromResourceClass($propertyType->getClassName(), [
                        'id' => $value,
                    ]);
                } catch (InvalidArgumentException $exception) {
                    // Do nothing if failing. The value is maybe already an IRI format.
                }
            }

            unset($value);
        }

        return $data;
    }

    /**
     * @param string|object $classOrObject
     * @param array $context
     * @param bool $attributesAsString
     *
     * @return array|bool|string[]|\Symfony\Component\Serializer\Mapping\AttributeMetadataInterface[]
     */
    protected function getAllowedAttributes($classOrObject, array $context, $attributesAsString = false)
    {
        $allowedAttributes = parent::getAllowedAttributes(
            $classOrObject,
            $context,
            $attributesAsString
        );

        if (\array_key_exists('allowed_extra_attributes', $context)) {
            $allowedAttributes = array_merge($allowedAttributes, $context['allowed_extra_attributes']);
        }

        return $allowedAttributes;
    }

    /**
     * @param mixed $data
     * @param array $context
     */
    protected function setObjectToPopulate($data, array &$context): void
    {
        // in PUT request OBJECT_TO_POPULATE is already set by this moment
        if (!\is_array($data) || isset($context[self::OBJECT_TO_POPULATE])) {
            return;
        }

        [$identifierName, $identifierMetadata] = $this->getResourceIdentifierData($context);

        $isUpdateAllowed = (bool)($context['api_allow_update'] ?? false);
        $hasIdentifierInRequest = \array_key_exists(self::IDENTIFIER, $data);
        $hasWritableIdentifierInRequest = $hasIdentifierInRequest && $identifierMetadata->isWritable();
        // when it is POST, update is not allowed for top level resource, but is allowed for nested resources
        $isTopLevelResourceInPostRequest = !$isUpdateAllowed
            && 'collection' === $context['operation_type']
            && 'post' === $context['collection_operation_name'];

        // if Resource does not have an ID OR if it is writable custom id - we should not populate Entity from DB
        if (!$hasIdentifierInRequest || ($hasWritableIdentifierInRequest && $isTopLevelResourceInPostRequest)) {
            return;
        }

        if (!$isUpdateAllowed) {
            throw new InvalidArgumentException('Update is not allowed for this operation.');
        }

        try {
            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getItemFromIri(
                (string)$data[self::IDENTIFIER],
                $context + ['fetch_data' => true]
            );
        } catch (InvalidArgumentException $e) {
            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getItemFromIri(
                sprintf(
                    '%s/%s',
                    $this->iriConverter->getIriFromResourceClass($context['resource_class']),
                    $data[$identifierName]
                ),
                $context + ['fetch_data' => true]
            );
        }
    }

    private function getResourceIdentifierData(array $context): array
    {
        $identifierPropertyName = null;
        $identifierPropertyMetadata = null;
        $className = $context['resource_class'];

        $properties = $this->propertyNameCollectionFactory->create($className, $context);

        foreach ($properties as $propertyName) {
            $property = $this->propertyMetadataFactory->create($className, $propertyName);

            if ($property->isIdentifier()) {
                $identifierPropertyName = $propertyName;
                $identifierPropertyMetadata = $property;
                break;
            }
        }

        if (null === $identifierPropertyMetadata) {
            throw new \LogicException(sprintf('Resource "%s" must have an identifier. Properties: %s.', $className, implode(',', iterator_to_array($properties->getIterator()))));
        }

        return [$identifierPropertyName, $identifierPropertyMetadata];
    }

    public function normalize($object, $format = null, array $context = [])
    {
        return $this->customItemNormalizer->normalize($object, $format, $context);
    }

}
