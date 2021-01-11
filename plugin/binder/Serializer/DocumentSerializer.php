<?php

namespace Sidpt\BinderBundle\Serializer;

use Claroline\AppBundle\API\Serializer\SerializerTrait;
use Claroline\AppBundle\Persistence\ObjectManager;

use Claroline\CoreBundle\Entity\Widget\WidgetContainer;
use Claroline\CoreBundle\API\Serializer\Widget\WidgetContainerSerializer;

use Sidpt\BinderBundle\Entity\Document;

// logging for debug
use Claroline\AppBundle\Log\LoggableTrait;
use Psr\Log\LoggerAwareInterface;

class DocumentSerializer
{
    use LoggableTrait;

    use SerializerTrait;

    /** @var ObjectManager */
    private $om;
    /** @var WidgetContainerSerializer */
    private $widgetContainerSerializer;

    /**
     * DocumentSerializer constructor.
     *
     * @param ObjectManager             $om
     * @param WidgetContainerSerializer $widgetContainerSerializer
     */
    public function __construct(
        ObjectManager $om,
        WidgetContainerSerializer $widgetContainerSerializer
    ) {
        $this->om = $om;
        $this->widgetContainerSerializer = $widgetContainerSerializer;
    }

    public function getName()
    {
        return 'clarodoc';
        // or document, not sure if it is the resource codename in javascript or the php classname that is needed
    }

    public function getClass()
    {
        return Document::class;
    }

    /**
     * @return string
     */
    public function getSchema()
    {
        return '~/sidpt/binder-bundle/plugin/binder/document.json';
    }

    public function serialize(Document $document, array $options = []): array
    {

        /** @var WidgetContainer[] $savedContainers */
        $savedContainers = $document->getWidgetContainers()->toArray();
        $containers = [];

        foreach ($savedContainers as $container) {
            //temporary
            $widgetContainerConfig = $container->getWidgetContainerConfigs()[0];
            if ($widgetContainerConfig) {
                if (!array_key_exists($widgetContainerConfig->getPosition(), $containers)) {
                    $containers[$widgetContainerConfig->getPosition()] = $container;
                } else {
                    $containers[] = $container;
                }
            }
        }

        ksort($containers);
        $containers = array_values($containers);



        $data = [
            'id' => $document->getUuid(),
            'title' => $document->getResourceNode()->getName(),
            'longTitle' => $document->getLongTitle(),
            'centerTitle' => $document->isCenterTitle(),
            'widgets' => array_map(function ($container) use ($options) {
                return $this->widgetContainerSerializer->serialize($container, $options);
            }, $containers)
        ];

        return $data;
    }

    public function deserialize(array $data, Document $document = null, array $options = []): Document
    {

        if (empty($document)) {
            $document = new Document();
        }


        $this->sipe('longTitle', 'setLongTitle', $data, $document);
        $this->sipe('centerTitle', 'setCenterTitle', $data, $document);
        

        if (isset($data['widgets'])) {
            /** @var WidgetContainer[] $currentContainers */
            $currentContainers = $document->getWidgetContainers()->toArray();
            $containerIds = [];

            // update containers
            foreach ($data['widgets'] as $position => $widgetContainerData) {
                if (isset($widgetContainerData['id'])) {
                    $widgetContainer = $document->getWidgetContainer($widgetContainerData['id']);
                }

                if (empty($widgetContainer)) {
                    $widgetContainer = new WidgetContainer();
                    $document->addWidgetContainer($widgetContainer);
                }

                $this->widgetContainerSerializer->deserialize($widgetContainerData, $widgetContainer, $options);
                $widgetContainerConfig = $widgetContainer->getWidgetContainerConfigs()[0];
                $widgetContainerConfig->setPosition($position);
                $containerIds[] = $widgetContainer->getUuid();
            }

            // removes containers which no longer exists
            foreach ($currentContainers as $currentContainer) {
                if (!in_array($currentContainer->getUuid(), $containerIds)) {
                    $document->removeWidgetContainer($currentContainer);
                    $this->om->remove($currentContainer);
                }
            }
        }


        return $document;
    }
}
