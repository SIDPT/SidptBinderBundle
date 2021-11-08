<?php

namespace Sidpt\BinderBundle\API\Subscriber;

use Claroline\AppBundle\API\Options;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Event\GlobalSearchEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Manager\WidgetManager;

use Claroline\CoreBundle\Entity\Widget\Type\ResourceWidget;
use Claroline\CoreBundle\Entity\Widget\Type\SimpleWidget;


use Icap\LessonBundle\Entity\Chapter;

class ContentSearchSubscriber implements EventSubscriberInterface
{
    /** @var ObjectManager */
    private $om;
    /** @var SerializerProvider */
    private $serializer;

    private $resourceManager;

    private $documentManager;

    private $widgetManager;

    private $chapterSerializer;

    public function __construct(
      ObjectManager $om,
      SerializerProvider $serializer,
      ResourceManager $resourceManager,
      WidgetManager $widgetManager
    ) {
        $this->om = $om;
        $this->serializer = $serializer;
        $this->resourceManager = $resourceManager;
        $this->widgetManager = $widgetManager;
        $this->chapterSerializer = $serializer->get(Chapter::class);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GlobalSearchEvent::class => 'search',
        ];
    }

    public function search(GlobalSearchEvent $event)
    {
        $search = $event->getSearch();
        $limit = $event->getLimit();
        $foundNodes = [];
        //throw new \Exception(print_r($event));
        // Search in displayed content, that is :
        // - for each widget
        //   - if widget is simple, get the html content and search in the text
        //   - if widget is resource, search in the resource html content
        //   ( for SIDPT, we need to limit to lesson, quiz?, and text)
        //     - if resource is a lesson
        //       - search in chapter title (string type)
        //       - get the chapter html content and search in text node
        //    - if the resource is a simple text resource
        //      - get the html content and search in the text
        if ($event->includeItems('content')) {
          $resources = $this->om->getRepository(ResourceNode::class)
            ->searchWithMimeType(
              $search,
              'custom/sidpt_document',
              $limit
            );

          $event->addResults('resource', array_map(function (ResourceNode $resource) {
              return $this->serializer->serialize($resource, [Options::SERIALIZE_MINIMAL]);
          }, $resources));

            // search in published and active document nodes
            $documentNodes = $this->om->getRepository(ResourceNode::class)->findBy(
                [
                  'mimeType' => 'custom/sidpt_document',
                  'published' => true,
                  'active' => true
                ]
            );
            foreach ($documentNodes as $key => $documentNode) {
              $document = $this->resourceManager->getResourceFromNode($documentNode);
              if(!empty($document)){
                // Search in description
                $nodeSerialized = $this->serializer->serialize(
                  $documentNode,
                  [
                    Options::SERIALIZE_MINIMAL,
                    Options::SERIALIZE_LIST,
                    Options::NO_RIGHTS,
                    Options::TRANSLATED
                  ]
                );
                $descriptionContent = strip_tags($nodeSerialized['meta']['description'] ?? "");
                if(strpos($descriptionContent, $search)){
                  $foundNodes[] = $documentNode;
                  continue;
                }
                $containers = $document->getWidgetContainers();
                foreach ($containers as $key => $container) {
                  // for each instance
                  $instances = $container->getInstances();
                  foreach ($instances as $key => $instance) {
                    $found = false;
                    $realWidget = $this->widgetManager->getWidgetFromInstance($instance);
                    if($realWidget instanceof ResourceWidget){
                      $widgetResource = $realWidget->getResourceNode();
                      if($widgetResource->getResourceType()->getName() == 'ujm_exercise'){
                        $lesson = $this->resourceManager->getResourceFromNode($widgetResource);
                        $chapters = $this->om->getRepository(Chapter::class)->findBy(
                            [
                              'lesson' => $lesson->getId()
                            ]
                        );
                        foreach ($chapters as $key => $chapter) {
                          $chapterSerialized = $this->serializer->serialize(
                            $chapter,
                            [Options::SERIALIZE_MINIMAL,Options::TRANSLATED]
                          );
                          // search in title
                          $titleContent = $chapterSerialized['title'];
                          $found = strpos($titleContent, $search);
                          if($found){
                            break;
                          }
                          // search in text
                          $textContent = strip_tags($chapterSerialized['text']);
                          $found = strpos($titleContent, $search);
                          if($found){
                            break;
                          }
                        }
                      }
                    } elseif ($realWidget instanceof SimpleWidget) {
                      //    get serialized content
                      //    flatten html text node to continuous text (strip_tags)
                      //    search in the text
                    }
                    if($found){
                      $foundNodes[] = $documentNode;

                      break 2; // break instance and container loop
                    }
                  }
                }

              }
              if(count($foundNodes) == $limit){
                break; // break document loop
              }
            }
            $event->addResults(
              'resource',
              array_map(
                function (ResourceNode $doc) {
                   return $this->serializer->serialize($doc, [Options::SERIALIZE_MINIMAL,Options::TRANSLATED]);
                },
                $foundNodes
              )
            );
        }
    }
}
