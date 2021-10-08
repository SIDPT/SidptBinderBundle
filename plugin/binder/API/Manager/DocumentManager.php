<?php

namespace Sidpt\BinderBundle\API\Manager;


use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\FinderProvider;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\DataSource;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Resource\ResourceType;
use Claroline\CoreBundle\Entity\Widget\Type\ListWidget;
use Claroline\CoreBundle\Entity\Widget\Type\ResourceWidget;
use Claroline\CoreBundle\Entity\Widget\Widget;
use Claroline\CoreBundle\Entity\Widget\WidgetContainer;
use Claroline\CoreBundle\Entity\Widget\WidgetContainerConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;

// entities
use Claroline\CoreBundle\Entity\Widget\WidgetInstanceConfig;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Manager\ContentTranslationManager;
use Claroline\CoreBundle\Manager\Organization\OrganizationManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\Workspace\WorkspaceManager;
use Claroline\HomeBundle\Entity\HomeTab;
use Claroline\TagBundle\Manager\TagManager;
use Sidpt\BinderBundle\Entity\Binder;
use Sidpt\BinderBundle\Entity\Document;

use UJM\ExoBundle\Library\Options\ExerciseType;

// logging for debug
use Claroline\AppBundle\Log\LoggableTrait;
use Psr\Log\LoggerAwareInterface;


class DocumentManager implements LoggerAwareInterface
{
  use LoggableTrait;

  // Class parameters
  private $om;
  private $crud;
  private $serializer;
  private $finder;
  private $organizationManager;
  private $tagManager;
  private $roleManager;
  private $workspaceManager;
  private $resourceManager;

  // Class variables for execute function (hoping it can be help performances)
  private $workspaceRepo;
  private $resourceNodeRepo;
  private $homeTabsRepo;
  private $resourceWidgetsRepo;
  private $listWidgetsRepo;

  private $binderType;
  private $documentType;
  private $directoryType;
  private $lessonType;
  private $exerciseType;
  private $textType;
  private $resourceDataSource;
  private $resourcesListDataSource;
  private $resourceWidgetType;
  private $listWidgetType;

  private $nodeSeralizer;

  // Tags hierarchy
  private $contentLevel;
  private $professionnalProfile;
  private $estimatedTime;
  private $includedResources;

  private $translations;

  /**
   *
   */
  public function __construct(
      ObjectManager $om,
      Crud $crud,
      SerializerProvider $serializer,
      FinderProvider $finder,
      OrganizationManager $organizationManager,
      TagManager $tagManager,
      RoleManager $roleManager,
      WorkspaceManager $workspaceManager,
      ResourceManager $resourceManager,
      ContentTranslationManager $translations
  ) {
      $this->translations = $translations;
      $this->om = $om;
      $this->crud = $crud;
      $this->serializer = $serializer;
      $this->finder = $finder;
      $this->organizationManager = $organizationManager;
      $this->tagManager = $tagManager;
      $this->roleManager = $roleManager;
      $this->workspaceManager = $workspaceManager;
      $this->resourceManager = $resourceManager;

      $this->workspaceRepo = $this->om->getRepository(Workspace::class);
      $this->resourceNodeRepo = $this->om->getRepository(ResourceNode::class);
      $this->homeTabsRepo = $this->om->getRepository(HomeTab::class);
      $this->resourceWidgetsRepo = $this->om->getRepository(ResourceWidget::class);
      $this->listWidgetsRepo = $this->om->getRepository(ListWidget::class);

      $widgetsTypeRepo = $this->om->getRepository(Widget::class);
      $dataSourceRepo = $this->om->getRepository(DataSource::class);
      $typesRepo = $this->om->getRepository(ResourceType::class);

      $this->binderType = $typesRepo->findOneBy(
          ['name' => 'sidpt_binder']
      );
      $this->documentType = $typesRepo->findOneBy(
          ['name' => 'sidpt_document']
      );

      $this->directoryType = $typesRepo->findOneBy(
          ['name' => 'directory']
      );

      $this->lessonType = $typesRepo->findOneBy(
          ['name' => 'icap_lesson']
      );

      $this->exerciseType = $typesRepo->findOneBy(
          ['name' => 'ujm_exercise']
      );

      $this->textType = $typesRepo->findOneBy(
          ['name' => 'text']
      );

      $this->resourceDataSource = $dataSourceRepo->findOneBy(
          ['name' => 'resource']
      );
      $this->resourcesListDataSource = $dataSourceRepo->findOneBy(
          ['name' => 'resources']
      );

      $this->resourceWidgetType = $widgetsTypeRepo->findOneBy(
          ['name' => 'resource']
      );
      $this->listWidgetType = $widgetsTypeRepo->findOneBy(
          ['name' => 'list']
      );
      $this->nodeSeralizer = $this->serializer->get(ResourceNode::class);

  }


  public function configureAsLearningUnit(Document $learningUnitDocument){
    $learningUnitNode = $learningUnitDocument->getResourceNode();
    $learningUnitDocument->setShowOverview(true);
    $learningUnitDocument->setWidgetsPagination(true);

    $learningUnitDocument->setOverviewMessage(
        <<<HTML
    <table class="table table-striped table-hover table-condensed data-table" style="height: 133px; width: 100%; border-collapse: collapse; margin-left: auto; margin-right: auto;" border="1" cellspacing="5px" cellpadding="20px">
    <tbody>
    <tr style="height: 19px;">
    <td style="width: 50%; height: 19px;">{trans('Learning unit','clarodoc')}</td>
    <td class="text-left string-cell" style="width: 50%; height: 19px;"><a id="{{ resource.resourceNode.slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.slug}}">{{ resource.resourceNode.name }}</a></td>
    </tr>
    <tr style="height: 19px;">
    <td style="width: 50%; height: 19px;">{trans('Module','clarodoc')}</td>
    <td class="text-left string-cell" style="width: 50%; height: 19px;"><a id="{{ resource.resourceNode.path[-2].slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.path[-2].slug}}">{{ resource.resourceNode.path[-2].name }}</a></td>
    </tr>
    <tr style="height: 19px;">
    <td style="width: 50%; height: 19px;">{trans('Course','clarodoc')}</td>
    <td class="text-left string-cell" style="width: 50%; height: 19px;"><a id="{{ resource.resourceNode.path[-3].slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.path[-3].slug}}">{{ resource.resourceNode.path[-3].name }}</a></td>
    </tr>
    <tr style="height: 19px;">
    <td style="width: 50%; height: 19px;">{trans('Who is it for?','clarodoc')}</td>
    <td style="width: 50%; height: 19px;">{{#resource.resourceNode.tags["professional-profile"]}}{{childrenNames}}{{/resource.resourceNode.tags["professional-profile"]}}</td>
    </tr>
    <tr style="height: 19px;">
    <td style="width: 50%; height: 19px;">{trans('What is included?','clarodoc')}</td>
    <td style="width: 50%; height: 19px;">{{#resource.resourceNode.tags["included-resource-type"]}}{{childrenNames}}{{/resource.resourceNode.tags["included-resource-type"]}}</td>
    </tr>
    <tr style="height: 19px;">
    <td style="width: 50%; height: 19px;">{trans('How long will it take?','clarodoc')}</td>
    <td style="width: 50%; height: 19px;">{{#resource.resourceNode.tags["time-frame"]}}{{childrenNames}}{{/resource.resourceNode.tags["time-frame"]}}</td>
    </tr>
    <tr style="height: 19px;">
    <td style="width: 50%; height: 19px;">{trans('Last updated','clarodoc')}</td>
    <td style="width: 50%; height: 19px;">{{#resource.resourceNode.meta.updated}}{{formatDate}}{{/resource.resourceNode.meta.updated}}</td>
    </tr>
    </tbody>
    </table>
    HTML
    );

    $learningUnitDocument->setShowDescription(true);
    $learningUnitDocument->setDisclaimer(
        <<<HTML
    <p id="disclaimer-start">{{#resource.resourceNode.tags["disclaimer"] }}</p>
    <h3>{trans('Disclaimer','clarodoc')}</h3>
    <p class="p1">{trans('This learning unit contains images that may not be accessible to some learners. This content is used to support learning. Whenever possible the information presented in the images is explained in the text.','clarodoc')}</p>
    <p>{{/resource.resourceNode.tags["disclaimer"] }}</p>
    HTML
    );

    // updated description template
    $learningUnitDocument->setDescriptionTitle(
        <<<HTML
    <h3>{trans('Learning outcomes','clarodoc')}</h3>
    HTML
    );

    $description = $learningUnitNode->getDescription();
    $learningOutcomeContent = $description;
     // update previous description
     if (!empty($description)) {
         // original template
         $searchedOutcome = explode(
             "<h3>Learning outcomes</h3>",
             $description
         );
         if (count($searchedOutcome) > 1) {
             $docIsLU = true;
             $learningOutcomeContent = explode(
                 "<p>{{#resource.resourceNode.tags[\"Disclaimer\"] }}</p>",
                 $searchedOutcome[1]
             )[0];
             $learningOutcomeContent = trim($learningOutcomeContent);
             $learningOutcomeContent = substr(
                 $learningOutcomeContent,
                 0,
                 strlen($learningOutcomeContent)
             );
         } else {
           $searchedOutcome = explode(
               "<h3>Learning outcome</h3>",
               $description
           );
           if (count($searchedOutcome) > 1) {
               $docIsLU = true;
               $learningOutcomeContent = explode(
                   "<p>{{#resource.resourceNode.tags[\"Disclaimer\"] }}</p>",
                   $searchedOutcome[1]
               )[0];
               $learningOutcomeContent = trim($learningOutcomeContent);
               $learningOutcomeContent = substr(
                   $learningOutcomeContent,
                   0,
                   strlen($learningOutcomeContent)
               );
           } else {
               // template v2
               // (translations)
               $searchedOutcome = explode(
                   "<h3>{trans('Learning outcomes','clarodoc')}</h3>",
                   $description
               );
               if (count($searchedOutcome) > 1) {
                   $docIsLU = true;
                   $learningOutcomeContent = explode(
                       "<p id=\"disclaimer-start\">",
                       $searchedOutcome[1]
                   )[0];
                   $learningOutcomeContent = trim($learningOutcomeContent);
               }
           }
        }
     }

    $learningUnitNode->setDescription($learningOutcomeContent);

    $user = $learningUnitNode->getCreator();
    $requiredKnowledgeNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "Required knowledge",
        $this->directoryType,
        false
    );

    $learningUnitDocument->setRequiredResourceNodeTreeRoot($requiredKnowledgeNode);

    // - a practice exercise:
    $practiceNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "Practice",
        $this->exerciseType
    );
    // - A theroy lesson
    $theoryNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "Theory",
        $this->lessonType
    );

    // - An assessment exercice
    $assessmentNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "Assessment",
        $this->exerciseType
    );


    // - An activity text
    $activityNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "Activity",
        $this->textType
    );

    // - A references document
    $referencesNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $learningUnitNode,
        "References",
        $this->documentType
    );
    $referencesDocument = $this->resourceManager->getResourceFromNode($referencesNode);
    $this->om->flush();
    // The reference document contains 2 sections :
    // - an external reference textual section
    $externalReferencesNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $referencesNode,
        "External references",
        $this->textType
    );
    // - an internal reference folder, to store a hierarchy of shortcuts
    $internalReferencesNode = $this->addOrUpdateDocumentSubObject(
        $user,
        $referencesNode,
        "IPIP references",
        $this->directoryType
    );
    // */

    $this->om->persist($learningUnitNode);
    $this->om->persist($learningUnitDocument);
    $this->om->flush();
    // TODO build tag hierarchy
    // Get or create the the module tag (plateforme tags)
    // $learningUnitTag = $this->tagManager->getOnePlatformTagByName($learningUnitNode->getName(), $moduleTag);
    // if (empty($learningUnitTag)) {
    //     $learningUnitTag = new Tag();
    //     $learningUnitTag->setName($learningUnit);
    //     $learningUnitTag->setParent($moduleTag);
    //     $this->om->persist($learningUnitTag);
    // }
    // $this->tagManager->tagData(
    //     ['Content level/Learning unit', $learningUnitTag->getPath()],
    //     [ 0 => [
    //         'id'=> $learningUnitNode->getUuid(),
    //         'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
    //         'name' => "{$curriculum}/{$course}/{$module}/${learningUnit}"
    //     ]]
    // );
    // $this->om->flush();

  }

  public function configureAsModule(Document $moduleDocument){
    // set description
    // set learning unit resource list
  }

  public function configureAsCourse(Document $courseDocument){
    //  set description
    // set modules resources list
  }


  public function addOrUpdateDocumentSubObject(
      $user,
      $documentNode,
      $subnodeName,
      $resourceType,
      $withWidget = true
  ) {
      $document = $this->resourceManager->getResourceFromNode($documentNode);
      $subNode = $this->resourceNodeRepo->findOneBy(
          [
              'name' => $subnodeName,
              'parent' => $documentNode->getId(),
              'resourceType' => $resourceType->getId(),
          ]
      );
      if (empty($subNode)) {
          $subNode = new ResourceNode();
          $subNode->setName($subnodeName);
          $subNode->setWorkspace($documentNode->getWorkspace());
          $subNode->setResourceType($resourceType);
          $subNode->setParent($documentNode);
          $subNode->setCreator($user);
          $subNode->setMimeType("custom/" . $resourceType->getName());
          $this->om->persist($subNode);

          $resourceclass = $resourceType->getClass();
          $subResource = new $resourceclass();
          $subResource->setResourceNode($subNode);
          $subResource->setName($subnodeName);

          if ($resourceType->getName() == "ujm_exercise") {
              if ($subnodeName == "Practice") {
                  $subResource->setType(ExerciseType::CONCEPTUALIZATION);
                  $subResource->setScoreRule(json_encode(["type" => "none"]));
              } else if ($subnodeName == "Assessment") {
                  $subResource->setType(ExerciseType::SUMMATIVE);
                  $subResource->setScoreRule(json_encode(["type" => "sum"]));
              }
          }

          $this->om->persist($subResource);

          $this->om->persist($document);

          if ($withWidget) {
              $this->addResourceWidget($document, $subNode, $subnodeName);
          }
      } else {
          // Update the document or node
          // Update the underlying resource
          $subResource = $this->resourceManager->getResourceFromNode($subNode);
          if ($subResource->getMimeType() == "custom/ujm_exercise") {
              if ($subResource->getType() == ExerciseType::SUMMATIVE) {
                  if (empty($subResource->getScoreRule())) {
                      $subResource->setScoreRule(
                          json_encode(["type" => "none"])
                      );
                  }
              } else if ($subResource->getType() == ExerciseType::SUMMATIVE) {
                  if (empty($subResource->getScoreRule())) {
                      $subResource->setScoreRule(
                          json_encode(["type" => "sum"])
                      );
                  }
              }
          }
          // update the widget
          $subNodeWidgets = $this->resourceWidgetsRepo->findBy(
              [
                  'resourceNode' => $subNode->getId(),
              ]
          );
          if (!empty($subNodeWidgets)) {
              // widget was found
              foreach ($subNodeWidgets as $widget) {
                  $widget->setShowResourceHeader(false);
                  $instance = $widget->getWidgetInstance();
                  $container = $instance->getContainer();
                  if (!$withWidget) {
                      // widget is no more requested
                      // remove the widget container
                      $this->om->remove($container);
                      $this->om->remove($instance);
                      $this->om->remove($widget);
                  } else {
                      $containerConfig = $container->getWidgetContainerConfigs()->first();
                      $containerConfig->setName($subnodeName);

                      $this->om->persist($widget);
                  }
              }
          } elseif ($withWidget) {
              // subnode is alledgedly used in a widget but was not found
              // So we add it
              $this->addResourceWidget($document, $subNode, $subnodeName);
          }
      }
      //$this->om->flush();
      return $subNode;
  }

  /**
   * [addResourceWidget description]
   * @param [type] $document     [description]
   * @param [type] $resourceNode [description]
   */
  public function addOrUpdateResourceListWidget($document, $parentNode, $name = null)
  {
      if ($document->getWidgetContainers()->isEmpty()) {
          $newWidget = new ListWidget();
          $newWidget->setFilters(
              [0 => [
                  "property" => "parent",
                  "value" => [
                      "id" => $parentNode->getUuid(),
                      "name" => $parentNode->getName(),
                  ],
                  "locked" => true,
              ],
              ]
          );

          $widget->setDisplay("table");
          $widget->setActions(false);
          $widget->setCount(true);
          $widget->setDisplayedColumns(["name", "meta.description"]);
          if ($name == "Learning units") {
              // update widget to display two columns
              // - the name column should be labeled "Select a learning unit" with a
              // - the meta.description should be title "Learning outcomes"
              $widget->setColumnsCustomization(
                  [
                      "name" => [
                          "label" => "Select a learning unit",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                      "meta.description" => [
                          "label" => "Learning outcomes",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                  ]
              );
          } elseif ($name == "Modules") {
              // update widget to display two columns
              // - the name column should be labeled "Select a module" with a
              // - the meta.description title should be removed
              $widget->setColumnsCustomization(
                  [
                      "name" => [
                          "label" => "Select a module",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                      "meta.description" => [
                          "hideLabel" => true,
                      ],
                  ]
              );
          } else {
              // possibly course list, but might be done manually
          }

          $newWidgetInstance = new WidgetInstance();
          $newWidgetInstance->setWidget($this->listWidgetType);
          $newWidgetInstance->setDataSource($this->resourcesListDataSource);
          $newWidget->setWidgetInstance($newWidgetInstance);
          $newWidgetInstanceConfig = new WidgetInstanceConfig();
          $newWidgetInstanceConfig->setType("list");
          $newWidgetInstanceConfig->setWidgetInstance($newWidgetInstance);
          $newWidgetContainer = new WidgetContainer();
          $newWidgetContainer->addInstance($newWidgetInstance);
          $newWidgetInstance->setContainer($newWidgetContainer);
          $newWidgetContainerConfig = new WidgetContainerConfig();
          $newWidgetContainerConfig->setName($name);
          $newWidgetContainerConfig->setBackgroundType("color");
          $newWidgetContainerConfig->setBackground("#ffffff");
          $newWidgetContainerConfig->setPosition(0);
          $newWidgetContainerConfig->setLayout(array(1));
          $newWidgetContainerConfig->setWidgetContainer($newWidgetContainer);
          $this->om->persist($newWidget);
          $this->om->persist($newWidgetInstance);
          $this->om->persist($newWidgetContainer);

          $document->addWidgetContainer($newWidgetContainer);
      } else {
          $container = $document->getWidgetContainers()->first();
          $containerConfig = $container->getWidgetContainerConfigs()->first();
          $instance = $container->getInstances()->first();

          $widget = $this->listWidgetsRepo->findOneBy(
              [
                  "widgetInstance" => $instance->getId(),
              ]
          );

          $widget->setFilters(
              [0 => [
                  "property" => "parent",
                  "value" => [
                      "id" => $parentNode->getUuid(),
                      "name" => $parentNode->getName(),
                  ],
                  "locked" => true,
              ],
              ]
          );
          $widget->setDisplay("table");
          $widget->setActions(false);
          $widget->setCount(true);
          $widget->setDisplayedColumns(["name", "meta.description"]);

          $containerConfig->setName($name);

          if ($name == "Learning units") {
              // update widget to display two columns
              // - the name column should be labeled "Select a learning unit" with a
              // - the meta.description should be title "Learning outcomes"
              $widget->setColumnsCustomization(
                  [
                      "name" => [
                          "label" => "Select a learning unit",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                      "meta.description" => [
                          "label" => "Learning outcomes",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                  ]
              );
          } elseif ($name == "Modules") {
              // update widget to display two columns
              // - the name column should be labeled "Select a module" with a
              // - the meta.description title should be removed
              $widget->setColumnsCustomization(
                  [
                      "name" => [
                          "label" => "Select a module",
                          "translateLabel" => true,
                          "translationDomain" => 'clarodoc',
                      ],
                      "meta.description" => [
                          "hideLabel" => true,
                      ],
                  ]
              );
          } else {
              // possibly course list, but might be done manually
          }

          $this->om->persist($widget);
          $this->om->persist($containerConfig);
      }
      $this->om->persist($document);
      //$this->om->flush();
  }

  /**
   * [addResourceWidget description]
   * @param [type] $document     [description]
   * @param [type] $resourceNode [description]
   */
  public function addResourceWidget($document, $resourceNode, $name = null)
  {
      $newWidget = new ResourceWidget();
      $newWidget->setResourceNode($resourceNode);
      $newWidget->setShowResourceHeader(false);
      $this->om->persist($newWidget);

      $newWidgetInstance = new WidgetInstance();
      $newWidgetInstance->setWidget($this->resourceWidgetType);
      $newWidgetInstance->setDataSource($this->resourceDataSource);
      $this->om->persist($newWidgetInstance);
      $newWidget->setWidgetInstance($newWidgetInstance);

      $newWidgetInstanceConfig = new WidgetInstanceConfig();
      $newWidgetInstanceConfig->setType("resource");
      $newWidgetInstanceConfig->setWidgetInstance($newWidgetInstance);
      $this->om->persist($newWidgetInstanceConfig);

      $newWidgetContainer = new WidgetContainer();
      $newWidgetContainer->addInstance($newWidgetInstance);
      $newWidgetInstance->setContainer($newWidgetContainer);
      $this->om->persist($newWidgetContainer);

      $newWidgetContainerConfig = new WidgetContainerConfig();
      $newWidgetContainerConfig->setName($name);
      $newWidgetContainerConfig->setBackgroundType("color");
      $newWidgetContainerConfig->setBackground("#ffffff");
      $newWidgetContainerConfig->setPosition(0);
      $newWidgetContainerConfig->setLayout(array(1));
      $newWidgetContainerConfig->setWidgetContainer($newWidgetContainer);
      $this->om->persist($newWidgetContainerConfig);

      $document->addWidgetContainer($newWidgetContainer);
      $this->om->persist($document);
  }


}
